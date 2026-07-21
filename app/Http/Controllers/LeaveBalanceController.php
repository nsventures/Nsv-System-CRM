<?php

namespace App\Http\Controllers;

use App\Services\LeaveBalanceService;
use App\Models\Workspace;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    protected $workspace;
    protected $user;
    protected $leaveBalanceService;

    public function __construct(LeaveBalanceService $leaveBalanceService)
    {
        $this->leaveBalanceService = $leaveBalanceService;

        $this->middleware(function ($request, $next) {
            $this->workspace = Workspace::find(getWorkspaceId());
            $this->user = getAuthenticatedUser();

            if (!is_admin_or_leave_editor()) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // Get date range or default to current company year
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // If no date range provided, use current company year
        if (!$dateFrom || !$dateTo) {
            $currentYear = get_current_company_year();
            $yearDates = get_company_year_dates($currentYear);
            $dateFrom = $yearDates['start']->format('Y-m-d');
            $dateTo = $yearDates['end']->format('Y-m-d');
        }

        // Still use year for balance records (balances are stored per year)
        $selectedYear = $request->input('year') ?? get_current_company_year();

        $filters = [
            'user_ids' => $request->input('user_ids', []),
            'balance_status' => $request->input('balance_status'),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $summary = $this->leaveBalanceService->getWorkspaceSummary($this->workspace->id, $selectedYear, $filters);
        $recent = $this->leaveBalanceService->getRecentWorkspaceRequests($this->workspace->id, $summary['year'], 5, $filters);
        $yearOptions = $this->leaveBalanceService->getAvailableYearsForWorkspace($this->workspace->id);

        if (!in_array($summary['year'], $yearOptions, true)) {
            $yearOptions[] = $summary['year'];
        }

        rsort($yearOptions);

        return view('leave_balances.index', [
            'summary' => $summary,
            'recentRequests' => $recent,
            'years' => $yearOptions,
            'selectedYear' => $summary['year'],
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function list(Request $request)
    {
        $filters = [
            'year' => $request->input('year'),
            'search' => $request->input('search'),
            'limit' => $request->input('limit'),
            'offset' => $request->input('offset'),
            'order' => $request->input('order'),
            'sort' => $request->input('sort'),
            'balance_status' => $request->input('balance_status'),
            'user_ids' => $request->input('user_ids', []),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $tableData = $this->leaveBalanceService->getWorkspaceBalanceTableData($this->workspace->id, $filters);
        $summary = $this->leaveBalanceService->getWorkspaceSummary($this->workspace->id, $filters['year'] ?? null, $filters);
        $recent = $this->leaveBalanceService->getRecentWorkspaceRequests($this->workspace->id, $summary['year'], 5, $filters);

        return response()->json(array_merge($tableData, [
            'summary' => $summary,
            'recent_requests' => $recent,
        ]));
    }
}


