<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Workspace;
use App\Models\User;
use App\Services\LeaveBalanceEngine;
use App\Services\LeaveBalanceService;
use App\Services\LeaveCalculationService;
use App\Models\LeaveBalanceAdjustment;

class InitializeLeaveBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaves:initialize-balances {--year= : The year to initialize balances for (default: current year)} {--workspace= : Specific workspace ID to initialize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize leave balances for all users in all workspaces for a given company year';

    protected $balanceEngine;
    protected $leaveBalanceService;
    protected $calculationService;

    public function __construct(LeaveBalanceEngine $balanceEngine)
    {
        parent::__construct();
        $this->balanceEngine = $balanceEngine;
        $this->leaveBalanceService = new LeaveBalanceService();
        $this->calculationService = app(LeaveCalculationService::class);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?: get_current_company_year(); // Use company year instead of calendar year
        $workspaceId = $this->option('workspace');

        $this->info("Initializing leave balances for company year: {$year}");

        // Step 1: Fix existing adjustment records with incorrect delta_paid values
        $this->info("Step 1: Fixing existing adjustment records...");
        $adjustmentsFixed = $this->fixAdjustmentRecords();
        $this->info("  ✓ Fixed {$adjustmentsFixed} adjustment records");

        // Get workspaces to process
        $workspaces = $workspaceId
            ? Workspace::where('id', $workspaceId)->get()
            : Workspace::all();

        if ($workspaces->isEmpty()) {
            $this->error('No workspaces found!');
            return 1;
        }

        $totalUsers = 0;
        $initialized = 0;
        $recalculated = 0;

        foreach ($workspaces as $workspace) {
            $this->info("Processing workspace: {$workspace->title}");

            // Get all users in the workspace
            $users = $workspace->users;

            if ($users->isEmpty()) {
                $this->warn("  No users found in workspace: {$workspace->title}");
                continue;
            }

            foreach ($users as $user) {
                try {
                    $totalUsers++;

                    // Get or create balance using LeaveBalanceEngine
                    $balance = $this->balanceEngine->getOrCreateBalance(
                        $user->id,
                        $workspace->id,
                        $year
                    );

                    // Check if it was just created
                    if (!$balance->wasRecentlyCreated) {
                        // Get current total annual leaves from settings (may have changed!)
                        $currentTotalAnnualLeaves = $this->leaveBalanceService->getTotalAnnualLeaves();

                        // Update total_annual_leaves if settings changed
                        if (abs((float)$balance->total_annual_leaves - (float)$currentTotalAnnualLeaves) > 0.01) {
                            $balance->total_annual_leaves = $currentTotalAnnualLeaves;
                            $balance->save();
                            $this->line("  ↻ Updated total_annual_leaves for: {$user->first_name} {$user->last_name}");
                        }

                        // Recalculate balance using LeaveBalanceEngine
                        // This will include adjustments from LeaveBalanceAdjustment records
                        $balance = $this->balanceEngine->recalculateBalance($balance);

                        $this->line("  ↻ Recalculated balance for: {$user->first_name} {$user->last_name}");
                        $recalculated++;
                    } else {
                        $this->line("  ✓ Initialized balance for: {$user->first_name} {$user->last_name}");
                        $initialized++;
                    }
                } catch (\Exception $e) {
                    $this->error("  ✗ Error processing balance for {$user->first_name} {$user->last_name}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("✅ Initialization complete!");
        $this->info("📊 Summary:");
        $this->info("   - Workspaces processed: " . $workspaces->count());
        $this->info("   - Users processed: {$totalUsers}");
        $this->info("   - Balances initialized: {$initialized}");
        $this->info("   - Balances recalculated: {$recalculated}");
        $this->info("   - Adjustments fixed: {$adjustmentsFixed}");

        return 0;
    }

    /**
     * Fix existing adjustment records with incorrect delta_paid values
     *
     * @return int Number of adjustments fixed
     */
    protected function fixAdjustmentRecords(): int
    {
        $adjustmentsFixed = 0;

        // Find adjustments where delta_paid is 0 but delta_advance > 0
        // This indicates the old bug where delta_paid wasn't set correctly during override
        $adjustments = LeaveBalanceAdjustment::where('delta_paid', 0)
            ->where('delta_advance', '>', 0)
            ->get();

        foreach ($adjustments as $adjustment) {
            $payslip = $adjustment->payslip;
            if (!$payslip) {
                continue;
            }

            try {
                // Calculate what deltaPaidLeave should have been
                $baseline = $this->calculationService->calculateBaselineLOP(
                    $adjustment->user_id,
                    $adjustment->workspace_id,
                    \Carbon\Carbon::parse($payslip->month)->format('Y-m')
                );

                $baselineLop = (float) ($baseline['lop_days'] ?? 0);
                $submittedLop = (float) $payslip->lop_days;
                $deltaLop = $submittedLop - $baselineLop;
                $deltaPaidLeave = -$deltaLop; // Inverse relationship

                if ($deltaPaidLeave > 0) {
                    $adjustment->delta_paid = $deltaPaidLeave;
                    $adjustment->save();
                    $adjustmentsFixed++;
                }
            } catch (\Exception $e) {
                // Log error but continue processing
                \Log::warning("Error fixing adjustment {$adjustment->id}: {$e->getMessage()}");
            }
        }

        return $adjustmentsFixed;
    }
}
