<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Payslip;
use App\Models\Workspace;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\DeletionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\LeaveBalanceService;
use App\Services\LeaveBalanceSyncService;
use App\Services\LeaveCalculationService;
use App\Services\LeaveBalanceEngine;

class PayslipsController extends Controller
{
    protected $workspace;
    protected $user;
    protected LeaveBalanceService $leaveBalanceService;
    protected LeaveCalculationService $calculationService;
    protected LeaveBalanceEngine $balanceEngine;

    public function __construct(LeaveBalanceService $leaveBalanceService)
    {
        $this->leaveBalanceService = $leaveBalanceService;
        $this->calculationService = app(LeaveCalculationService::class);
        $this->balanceEngine = app(LeaveBalanceEngine::class);
        $this->middleware(function ($request, $next) {
            // fetch session and use it in entire class with constructor
            $this->workspace = Workspace::find(getWorkspaceId());
            $this->user = getAuthenticatedUser();
            return $next($request);
        });
    }
    public function index(Request $request)
    {
        $payslips = isAdminOrHasAllDataAccess() ? $this->workspace->payslips() : $this->user->payslips();
        $payslips = $payslips->count();
        return view('payslips.list', ['payslips' => $payslips]);
    }

    public function create(Request $request)
    {
        $users = $this->workspace->users;
        $payment_methods = $this->workspace->payment_methods;
        return view('payslips.create', ['users' => $users, 'payment_methods' => $payment_methods]);
    }


    /**
     * Create a new payslip.
     *
     * Creates a new payslip with salary details, allowances, and deductions. Requires valid user ID, salary components, and optional payment information if marked as paid.
     *
     * @group Payslip Management
     *
     * @bodyParam user_id integer required The user ID. Example: 3
     * @bodyParam month string required Month of payslip. Format: YYYY-MM. Example: 2025-05
     * @bodyParam basic_salary number required Basic salary. Example: 50000.00
     * @bodyParam working_days integer required Working days in the month. Example: 22
     * @bodyParam lop_days integer required Loss of pay days. Example: 2
     * @bodyParam paid_days integer required Paid days. Example: 20
     * @bodyParam bonus number required Bonus amount. Example: 2000.00
     * @bodyParam incentives number required Incentives amount. Example: 1500.00
     * @bodyParam leave_deduction number required Leave deduction amount. Example: 500.00
     * @bodyParam ot_hours integer required Overtime hours. Example: 5
     * @bodyParam ot_rate number required Overtime rate per hour. Example: 100.00
     * @bodyParam ot_payment number required Overtime payment. Example: 500.00
     * @bodyParam total_allowance number required Total allowance amount. Example: 3500.00
     * @bodyParam total_deductions number required Total deductions. Example: 500.00
     * @bodyParam total_earnings number required Total earnings. Example: 53500.00
     * @bodyParam net_pay number required Final payable amount. Example: 53000.00
     * @bodyParam payment_method_id integer optional Required if status is 1 (paid). Example: 2
     * @bodyParam payment_date date optional Required if status is 1 (paid). Format: Y-m-d. Example: 2025-05-20
     * @bodyParam status integer required Payslip status (0 = Unpaid, 1 = Paid). Example: 1
     * @bodyParam note string optional Additional notes. Example: Bonus included for performance.
     * @bodyParam allowances array optional Array of allowance IDs to associate with the payslip. Example: [1, 2]
     * @bodyParam deductions array optional Array of deduction IDs to associate with the payslip. Example: [3, 4]
     * @bodyParam isApi boolean optional Whether to return a formatted API response. Defaults to false. Example: true
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Payslip created successfully.",
     *   "data": {
     *     "id": 10,
     *     "user_id": 3,
     *     "user_name": "John Doe",
     *     "month": "2025-05-01",
     *     "basic_salary": 50000.00,
     *     "working_days": 22,
     *     "lop_days": 2,
     *     "paid_days": 20,
     *     "bonus": 2000.00,
     *     "incentives": 1500.00,
     *     "leave_deduction": 500.00,
     *     "ot_hours": 5,
     *     "ot_rate": 100.00,
     *     "ot_payment": 500.00,
     *     "total_allowance": 3500.00,
     *     "total_deductions": 500.00,
     *     "total_earnings": 53500.00,
     *     "net_pay": 53000.00,
     *     "status": 1,
     *     "status_label": "Paid",
     *     "payment_method_id": 2,
     *     "payment_method": "Bank Transfer",
     *     "payment_date": "2025-05-20",
     *     "note": "Bonus included for performance.",
     *     "created_at": "2025-05-15",
     *     "updated_at": "2025-05-15"
     *   }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "The given data was invalid.",
     *   "data": {
     *     "errors": {
     *       "user_id": ["The user field is required."],
     *       "basic_salary": ["The basic salary must be a valid number with or without decimals."]
     *     }
     *   }
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred.",
     *   "data": []
     * }
     */


    public function store(Request $request)
    {

        try {
            $isApi = request()->get('isApi', false);
            // dd($isApi);
            $formFields = $request->validate([
                'user_id' => ['required'],
                'month' => ['required'],
                'basic_salary' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'working_days' => ['required'],
                'lop_days' => ['required'],
                'paid_days' => ['required'],
                'bonus' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'incentives' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'leave_deduction' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'ot_hours' => ['required'],
                'ot_rate' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'ot_payment' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'total_allowance' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'total_deductions' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'total_earnings' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'net_pay' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'payment_method_id' => ['nullable', 'required_if:status,1'],
                'payment_date' => ['nullable', 'required_if:status,1'],
                'status' => ['required'],
                'note' => ['nullable'],
                'auto_from_leave' => ['nullable']
            ], [
                'user_id.required' => 'The user field is required.',
                'payment_date.required_if' => 'The payment date is required when status is paid.',
                'payment_method_id.required_if' => 'The payment method is required when status is paid.',
                'basic_salary.regex' => 'The basic salary must be a valid number with or without decimals.',
                'bonus.regex' => 'The bonus must be a valid number with or without decimals.',
                'incentives.regex' => 'The incentives must be a valid number with or without decimals.',
                'leave_deduction.regex' => 'The leave deduction must be a valid number with or without decimals.',
                'ot_rate.regex' => 'The over time rate must be a valid number with or without decimals.',
                'ot_payment.regex' => 'The over time payment must be a valid number with or without decimals.',
                'total_allowance.regex' => 'The total allowances must be a valid number with or without decimals.',
                'total_deductions.regex' => 'The total deductions must be a valid number with or without decimals.',
                'total_earnings.regex' => 'The total earnings must be a valid number with or without decimals.',
                'net_pay.regex' => 'The net payable must be a valid number with or without decimals.'
            ]);

            $payment_date = $request->input('payment_date');

            $status = $request->input('status');

            if ($status == '0') {
                $formFields['payment_date'] = null;
                $formFields['payment_method_id'] = null;
            } elseif (!empty($payment_date)) {
             $formFields['payment_date'] = format_date(trim($payment_date), false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d H:i:s');

            }

            $formFields['workspace_id'] = $this->workspace->id;
            $formFields['created_by'] = isClient() ? 'c_' . $this->user->id : 'u_' . $this->user->id;
            $allowance_ids = $request->input('allowances') ?? [];
            $deduction_ids = $request->input('deductions') ?? [];

            // Debug data collection
            $debugData = [];
            $isDebugMode = $request->boolean('debug', false) || config('app.debug', false);

            // If auto_from_leave is on, recalculate leave-related fields server-side
            if ($request->boolean('auto_from_leave')) {
                $summary = $this->calculateLeaveSummary(
                    (int)$formFields['user_id'],
                    $formFields['month'],
                    (float)$formFields['basic_salary']
                );

                $baselineLop = (float) $summary['lop_days'];
                $submittedLop = (float) $request->input('lop_days', $baselineLop);
                $workingDays = (float) $summary['working_days'];

                // Store debug info
                if ($isDebugMode) {
                    $debugData['auto_from_leave'] = true;
                    $debugData['baseline_lop'] = $baselineLop;
                    $debugData['submitted_lop'] = $submittedLop;
                    $debugData['baseline_paid_days'] = $summary['paid_days'];
                    $debugData['baseline_leave_deduction'] = $summary['leave_deduction'];
                }

                // If submitted LOP differs from baseline, it's a manual adjustment
                // Use a small tolerance (0.01) for floating point comparison
                if (abs($submittedLop - $baselineLop) > 0.01) {
                    // Preserve manual adjustment
                    $formFields['working_days'] = $workingDays;
                    $formFields['lop_days'] = $submittedLop;
                    $formFields['paid_days'] = max(0, $workingDays - $submittedLop);

                    // Recalculate leave deduction based on manual LOP
                    $perDaySalary = $workingDays > 0 ? ((float)$formFields['basic_salary'] / $workingDays) : 0;
                    $formFields['leave_deduction'] = round($perDaySalary * $submittedLop, 2);

                    if ($isDebugMode) {
                        $debugData['manual_adjustment'] = true;
                        $debugData['delta_lop'] = $submittedLop - $baselineLop;
                        $debugData['delta_paid_leave'] = - ($submittedLop - $baselineLop);
                        $debugData['final_lop'] = $submittedLop;
                        $debugData['final_paid_days'] = $formFields['paid_days'];
                        $debugData['final_leave_deduction'] = $formFields['leave_deduction'];
                    }

                    \Log::info('Payslip manual LOP adjustment detected', [
                        'user_id' => $formFields['user_id'],
                        'month' => $formFields['month'],
                        'baseline_lop' => $baselineLop,
                        'submitted_lop' => $submittedLop,
                        'delta' => $submittedLop - $baselineLop
                    ]);
                } else {
                    // Use calculated values (no manual adjustment)
                    $formFields['working_days'] = $summary['working_days'];
                    $formFields['lop_days'] = $summary['lop_days'];
                    $formFields['paid_days'] = $summary['paid_days'];
                    $formFields['leave_deduction'] = $summary['leave_deduction'];

                    if ($isDebugMode) {
                        $debugData['manual_adjustment'] = false;
                        $debugData['final_lop'] = $summary['lop_days'];
                        $debugData['final_paid_days'] = $summary['paid_days'];
                        $debugData['final_leave_deduction'] = $summary['leave_deduction'];
                    }
                }

                // Recompute totals consistent with client logic
                $formFields['total_earnings'] = (float)$formFields['basic_salary'] + (float)$formFields['bonus'] + (float)$formFields['incentives'] + (float)$formFields['ot_payment'];
                $formFields['net_pay'] = (float)$formFields['total_earnings'] + (float)$formFields['total_allowance'] - ((float)$formFields['total_deductions'] + (float)$formFields['leave_deduction']);
            } else {
                if ($isDebugMode) {
                    $debugData['auto_from_leave'] = false;
                    $debugData['final_lop'] = (float) $formFields['lop_days'];
                }
            }

            // Pre-check override requirement BEFORE creating payslip
            // Create a temporary payslip object to check override requirement
            $tempPayslip = new Payslip($formFields);
            $syncService = app(LeaveBalanceSyncService::class);
            $overrideCheck = $syncService->checkOverrideRequired($tempPayslip);

            // If override is required and not confirmed, return special response
            if ($overrideCheck['override_required'] && !$request->boolean('override_confirmed', false)) {
                return response()->json([
                    'error' => false,
                    'override_required' => true,
                    'message' => 'Override confirmation required',
                    'override_data' => [
                        'delta_paid_leave' => $overrideCheck['delta_paid_leave'],
                        'available_balance' => $overrideCheck['available_balance'],
                        'excess_paid_leave' => $overrideCheck['excess_paid_leave'],
                        'baseline_lop' => $overrideCheck['baseline_lop'],
                        'submitted_lop' => $overrideCheck['submitted_lop'],
                    ]
                ], 200);
            }

            if ($payslip = Payslip::create($formFields)) {
                $payslip->allowances()->attach($allowance_ids);
                $payslip->deductions()->attach($deduction_ids);

                // Sync leave balance after payslip creation
                $syncResult = null;
                try {
                    $syncService = app(LeaveBalanceSyncService::class);
                    $syncResult = $syncService->syncFromPayslip($payslip, [
                        'override_confirmed' => $request->boolean('override_confirmed', false)
                    ]);

                    if ($isDebugMode) {
                        $debugData['sync_result'] = $syncResult;
                    }

                    \Log::info('Payslip balance sync completed', [
                        'payslip_id' => $payslip->id,
                        'user_id' => $payslip->user_id,
                        'month' => $payslip->month,
                        'sync_success' => $syncResult['success'] ?? false,
                        'balance_updated' => $syncResult['balance_updated'] ?? false,
                        'delta_paid_leave' => $syncResult['delta_paid_leave'] ?? 0,
                        'delta_lop' => $syncResult['delta_lop'] ?? 0
                    ]);
                } catch (\Exception $e) {
                    // Log error but don't fail payslip creation
                    \Log::error('Leave balance sync failed during payslip creation', [
                        'payslip_id' => $payslip->id,
                        'user_id' => $payslip->user_id,
                        'month' => $payslip->month,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    if ($isDebugMode) {
                        $debugData['sync_error'] = [
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ];
                    }
                }

                if ($isApi) {
                    $responseData = ['data' => formatPayslip($payslip)];
                    if ($isDebugMode && !empty($debugData)) {
                        $responseData['debug'] = $debugData;
                    }

                    return formatApiResponse(
                        false,
                        'Payslip created successfully.',
                        $responseData,
                        200
                    );
                }

                Session::flash('message', 'Payslip created successfully.');

                $response = ['error' => false, 'id' => $payslip->id];
                if ($isDebugMode && !empty($debugData)) {
                    $response['debug'] = $debugData;
                }

                return response()->json($response);
            } else {
                return response()->json(['error' => true, 'message' => 'Payslip couldn\'t created.']);
            }
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {

            return formatApiResponse(
                true,
                config('app.debug') ? $e->getMessage() : 'An error occurred.',
                [],
                500
            );
        }
    }

    public function list()
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $statuses = request('statuses', []);
        $user_ids = request('user_ids', []);
        $created_by_user_ids = request('created_by_user_ids', []);
        $created_by_client_ids = request('created_by_client_ids', []);
        $month = (request('month')) ? request('month') : "";
        $where = ['payslips.workspace_id' => $this->workspace->id];


        $payslips = Payslip::select(
            'payslips.*',
            DB::raw('CONCAT(users.first_name, " ", users.last_name) AS user_name'),
            'payment_methods.title as payment_method'
        )
            ->leftJoin('users', 'payslips.user_id', '=', 'users.id')
            ->leftJoin('payment_methods', 'payslips.payment_method_id', '=', 'payment_methods.id');


        if (!isAdminOrHasAllDataAccess()) {
            $payslips = $payslips->where(function ($query) {
                $query->where('payslips.created_by', isClient() ? 'c_' . $this->user->id : 'u_' . $this->user->id)
                    ->orWhere('payslips.user_id', $this->user->id);
            });
        }

        if (!empty($statuses)) {
            $payslips->whereIn('payslips.status', $statuses);
        }

        if (!empty($user_ids)) {
            $payslips->whereIn('payslips.user_id', $user_ids);
        }

        if (!empty($created_by_user_ids)) {
            $payslips->whereIn('payslips.created_by', array_map(function ($id) {
                return 'u_' . $id;
            }, $created_by_user_ids));
        }

        if (!empty($created_by_client_ids)) {
            $payslips->whereIn('payslips.created_by', array_map(function ($id) {
                return 'c_' . $id;
            }, $created_by_client_ids));
        }

        if ($month) {
            $where['month'] = $month;
        }
        if ($search) {
            $payslips = $payslips->where(function ($query) use ($search) {
                $query->where('payslips.id', 'like', '%' . $search . '%')
                    ->orWhere(DB::raw('CONCAT("' . get_label('payslip_id_prefix', 'PSL-') . '", payslips.id)'), 'like', '%' . $search . '%')
                    ->orWhere('payslips.note', 'like', '%' . $search . '%')
                    ->orWhere('payment_methods.title', 'like', '%' . $search . '%');
            });
        }

        $payslips->where($where);
        $total = $payslips->count();

        $canCreate = checkPermission('create_payslips');
        $canEdit = checkPermission('edit_payslips');
        $canDelete = checkPermission('delete_payslips');

        $payslips = $payslips->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(function ($payslip) use ($canEdit, $canDelete, $canCreate) {
                $month = Carbon::parse($payslip->month);
                $payment_date = $payslip->payment_date !== null ? Carbon::parse($payslip->payment_date) : '';

                $hasActions = $canEdit || $canDelete || $canCreate;

                if ($hasActions) {
                    $actions = '<div class="dropdown">';
                    $actions .= '<button class="btn p-0 dropdown-toggle hide-arrow " type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $actions .= '<i class="bx bx-dots-vertical-rounded fs-5"></i>';
                    $actions .= '</button>';
                    $actions .= '<ul class="dropdown-menu dropdown-menu-end">';

                    if ($canEdit) {
                        $actions .= '<li><a href="' . url("/payslips/edit/{$payslip->id}") . '" class="dropdown-item d-block">';
                        $actions .= '<i class="bx bx-edit text-primary me-2"></i>' . get_label('update', 'Update') . '</a></li>';
                    }

                    if ($canCreate) {
                        $actions .= '<li><a href="javascript:void(0);" class="dropdown-item duplicate d-block" data-id="' . $payslip->id . '" data-type="payslips" data-table="payslips_table">';
                        $actions .= '<i class="bx bx-copy text-warning me-2"></i>' . get_label('duplicate', 'Duplicate') . '</a></li>';
                    }

                    if ($canDelete) {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a href="javascript:void(0);" class="dropdown-item delete text-danger d-block" data-id="' . $payslip->id . '" data-type="payslips" data-table="payslips_table">';
                        $actions .= '<i class="bx bx-trash me-2"></i>' . get_label('delete', 'Delete') . '</a></li>';
                    }

                    $actions .= '</ul>';
                    $actions .= '</div>';
                } else {
                    $actions = '-';
                }


                return [
                    'id' => $payslip->id,
                    'user' => formatUserHtml($payslip->user),
                    'payment_method' => $payslip->payment_method,
                    'month' => $month->format('F, Y'),
                    'working_days' => $payslip->working_days,
                    'lop_days' => $payslip->lop_days,
                    'paid_days' => $payslip->paid_days,
                    'basic_salary' => format_currency($payslip->basic_salary),
                    'leave_deduction' => format_currency($payslip->leave_deduction),
                    'ot_hours' => $payslip->ot_hours,
                    'ot_rate' => format_currency($payslip->ot_rate),
                    'ot_payment' => format_currency($payslip->ot_payment),
                    'total_allowance' => format_currency($payslip->total_allowance),
                    'incentives' => format_currency($payslip->incentives),
                    'bonus' => format_currency($payslip->bonus),
                    'total_earnings' => format_currency($payslip->total_earnings),
                    'total_deductions' => format_currency($payslip->total_deductions),
                    'net_pay' => format_currency($payslip->net_pay),
                    'payment_date' => $payment_date != '' ? format_date($payment_date) : '-',
                    'status' => $payslip->status == 1 ? '<span class="badge bg-success">' . get_label('paid', 'Paid') . '</span>' : '<span class="badge bg-danger">' . get_label('unpaid', 'Unpaid') . '</span>',
                    'note' => $payslip->note,
                    'created_by' => strpos($payslip->created_by, 'u_') === 0 ? formatUserHtml(User::find(substr($payslip->created_by, 2))) : formatClientHtml(Client::find(substr($payslip->created_by, 2))),
                    'created_at' => format_date($payslip->created_at, true),
                    'updated_at' => format_date($payslip->updated_at, true),
                    'actions' => $actions
                ];
            });


        return response()->json([
            "rows" => $payslips->items(),
            "total" => $total,
        ]);
    }






    public function edit(Request $request, $id)
    {

        $payslip = Payslip::select(
            'payslips.*',
            DB::raw('CONCAT(users.first_name, " ", users.last_name) AS user_name'),
            'payment_methods.title as payment_method'
        )->where('payslips.id', '=', $id)
            ->leftJoin('users', 'payslips.user_id', '=', 'users.id')
            ->leftJoin('payment_methods', 'payslips.payment_method_id', '=', 'payment_methods.id')->first();

        $creator = User::find(substr($payslip->created_by, 2));
        if ($creator !== null) {
            $payslip->creator = $creator->first_name . ' ' . $creator->last_name;
        } else {
            $payslip->creator = ' -';
        }
        $users = $this->workspace->users;
        $payment_methods = $this->workspace->payment_methods;
        return view('payslips.update', ['payslip' => $payslip, 'users' => $users, 'payment_methods' => $payment_methods]);
    }


    /**
     * Update an existing payslip.
     *
     * Updates an existing payslip by ID with revised salary and payment details. Fields must be validated as in creation.
     *
     * @group Payslip Management
     *
     * @bodyParam id integer required The ID of the payslip to update. Must exist in the `payslips` table. Example: 7
     * @bodyParam user_id integer required The user ID. Example: 3
     * @bodyParam month string required Month of payslip. Format: YYYY-MM. Example: 2025-05
     * @bodyParam basic_salary number required Basic salary. Example: 50000.00
     * @bodyParam working_days integer required Working days in the month. Example: 22
     * @bodyParam lop_days integer required Loss of pay days. Example: 2
     * @bodyParam paid_days integer required Paid days. Example: 20
     * @bodyParam bonus number required Bonus amount. Example: 2000.00
     * @bodyParam incentives number required Incentives amount. Example: 1500.00
     * @bodyParam leave_deduction number required Leave deduction. Example: 500.00
     * @bodyParam ot_hours integer required Overtime hours. Example: 5
     * @bodyParam ot_rate number required Overtime rate. Example: 100.00
     * @bodyParam ot_payment number required Overtime payment. Example: 500.00
     * @bodyParam total_allowance number required Allowance total. Example: 3500.00
     * @bodyParam total_deductions number required Deductions total. Example: 500.00
     * @bodyParam total_earnings number required Earnings total. Example: 53500.00
     * @bodyParam net_pay number required Net pay. Example: 53000.00
     * @bodyParam payment_method_id integer optional Required if status is 1 (paid). Example: 2
     * @bodyParam payment_date date optional Required if status is 1 (paid). Format: Y-m-d. Example: 2025-05-20
     * @bodyParam status integer required Status (0 = Unpaid, 1 = Paid). Example: 1
     * @bodyParam note string optional Note text. Example: Updated after bonus revision.
     * @bodyParam allowances array optional Array of allowance IDs to associate with the payslip. Example: [1, 2]
     * @bodyParam deductions array optional Array of deduction IDs to associate with the payslip. Example: [3, 4]
     * @bodyParam isApi boolean optional Whether to return a formatted API response. Defaults to false. Example: true
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Payslip updated successfully.",
     *   "data": {
     *     "id": 7,
     *     "user_id": 3,
     *     "user_name": "John Doe",
     *     "month": "2025-05-01",
     *     "basic_salary": 50000.00,
     *     "working_days": 22,
     *     "lop_days": 2,
     *     "paid_days": 20,
     *     "bonus": 2000.00,
     *     "incentives": 1500.00,
     *     "leave_deduction": 500.00,
     *     "ot_hours": 5,
     *     "ot_rate": 100.00,
     *     "ot_payment": 500.00,
     *     "total_allowance": 3500.00,
     *     "total_deductions": 500.00,
     *     "total_earnings": 53500.00,
     *     "net_pay": 53000.00,
     *     "status": 1,
     *     "status_label": "Paid",
     *     "payment_method_id": 2,
     *     "payment_method": "Bank Transfer",
     *     "payment_date": "2025-05-20",
     *     "note": "Updated after bonus revision.",
     *     "created_at": "2025-05-15",
     *     "updated_at": "2025-05-15"
     *   }
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Payslip not found.",
     *   "data": []
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "The given data was invalid.",
     *   "data": {
     *     "errors": {
     *       "user_id": ["The user field is required."],
     *       "basic_salary": ["The basic salary must be a valid number with or without decimals."]
     *     }
     *   }
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred.",
     *   "data": []
     * }
     */


    public function update(Request $request)
    {

        try {

            $isApi = request()->get('isApi', false);

            $formFields = $request->validate([
                'id' => ['required'],
                'user_id' => ['required'],
                'month' => ['required'],
                'basic_salary' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'working_days' => ['required'],
                'lop_days' => ['required'],
                'paid_days' => ['required'],
                'bonus' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'incentives' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'leave_deduction' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'ot_hours' => ['required'],
                'ot_rate' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'ot_payment' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'total_allowance' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'total_deductions' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'total_earnings' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'net_pay' => ['required', 'regex:/^\d+(\.\d+)?$/'],
                'payment_method_id' => ['nullable', 'required_if:status,1'],
                'payment_date' => ['nullable', 'required_if:status,1'],
                'status' => ['required'],
                'note' => ['nullable'],
                'auto_from_leave' => ['nullable']
            ], [
                'user_id.required' => 'The user field is required.',
                'payment_date.required_if' => 'The payment date is required when status is paid.',
                'payment_method_id.required_if' => 'The payment method is required when status is paid.',
                'basic_salary.regex' => 'The basic salary must be a valid number with or without decimals.',
                'bonus.regex' => 'The bonus must be a valid number with or without decimals.',
                'incentives.regex' => 'The incentives must be a valid number with or without decimals.',
                'leave_deduction.regex' => 'The leave deduction must be a valid number with or without decimals.',
                'ot_rate.regex' => 'The over time rate must be a valid number with or without decimals.',
                'ot_payment.regex' => 'The over time payment must be a valid number with or without decimals.',
                'total_allowance.regex' => 'The total allowances must be a valid number with or without decimals.',
                'total_deductions.regex' => 'The total deductions must be a valid number with or without decimals.',
                'total_earnings.regex' => 'The total earnings must be a valid number with or without decimals.',
                'net_pay.regex' => 'The net payable must be a valid number with or without decimals.'
            ]);


            $payment_date = $request->input('payment_date');
            $status = $request->input('status');

            if ($status == '0') {
                $formFields['payment_date'] = null;
                $formFields['payment_method_id'] = null;
            } elseif (!empty($payment_date)) {
                $formFields['payment_date'] = format_date(trim($payment_date), false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d H:i:s');
            }

            $formFields['workspace_id'] = $this->workspace->id;
            $formFields['created_by'] = isClient() ? 'c_' . $this->user->id : 'u_' . $this->user->id;
            $allowance_ids = $request->input('allowances') ?? [];
            $deduction_ids = $request->input('deductions') ?? [];

            // Find the Payslip by its ID
            $payslip = Payslip::findOrFail($request->input('id'));

            // Reverse old adjustment before updating (to restore balance to baseline)
            try {
                $syncService = app(LeaveBalanceSyncService::class);
                $syncService->reverseAdjustment($payslip);
            } catch (\Exception $e) {
                // Log error but don't fail payslip update
                \Log::error('Leave balance reversal failed during payslip update: ' . $e->getMessage());
            }

            // Debug data collection
            $debugData = [];
            $isDebugMode = $request->boolean('debug', false) || config('app.debug', false);

            // If auto_from_leave is on, recalculate leave-related fields server-side
            if ($request->boolean('auto_from_leave')) {
                $summary = $this->calculateLeaveSummary(
                    (int)$formFields['user_id'],
                    $formFields['month'],
                    (float)$formFields['basic_salary']
                );

                $baselineLop = (float) $summary['lop_days'];
                $submittedLop = (float) $request->input('lop_days', $baselineLop);
                $workingDays = (float) $summary['working_days'];

                // Store debug info
                if ($isDebugMode) {
                    $debugData['auto_from_leave'] = true;
                    $debugData['baseline_lop'] = $baselineLop;
                    $debugData['submitted_lop'] = $submittedLop;
                    $debugData['baseline_paid_days'] = $summary['paid_days'];
                    $debugData['baseline_leave_deduction'] = $summary['leave_deduction'];
                }

                // If submitted LOP differs from baseline, it's a manual adjustment
                // Use a small tolerance (0.01) for floating point comparison
                if (abs($submittedLop - $baselineLop) > 0.01) {
                    // Preserve manual adjustment
                    $formFields['working_days'] = $workingDays;
                    $formFields['lop_days'] = $submittedLop;
                    $formFields['paid_days'] = max(0, $workingDays - $submittedLop);

                    // Recalculate leave deduction based on manual LOP
                    $perDaySalary = $workingDays > 0 ? ((float)$formFields['basic_salary'] / $workingDays) : 0;
                    $formFields['leave_deduction'] = round($perDaySalary * $submittedLop, 2);

                    if ($isDebugMode) {
                        $debugData['manual_adjustment'] = true;
                        $debugData['delta_lop'] = $submittedLop - $baselineLop;
                        $debugData['delta_paid_leave'] = - ($submittedLop - $baselineLop);
                        $debugData['final_lop'] = $submittedLop;
                        $debugData['final_paid_days'] = $formFields['paid_days'];
                        $debugData['final_leave_deduction'] = $formFields['leave_deduction'];
                    }

                    \Log::info('Payslip manual LOP adjustment detected (update)', [
                        'payslip_id' => $payslip->id,
                        'user_id' => $formFields['user_id'],
                        'month' => $formFields['month'],
                        'baseline_lop' => $baselineLop,
                        'submitted_lop' => $submittedLop,
                        'delta' => $submittedLop - $baselineLop
                    ]);
                } else {
                    // Use calculated values (no manual adjustment)
                    $formFields['working_days'] = $summary['working_days'];
                    $formFields['lop_days'] = $summary['lop_days'];
                    $formFields['paid_days'] = $summary['paid_days'];
                    $formFields['leave_deduction'] = $summary['leave_deduction'];

                    if ($isDebugMode) {
                        $debugData['manual_adjustment'] = false;
                        $debugData['final_lop'] = $summary['lop_days'];
                        $debugData['final_paid_days'] = $summary['paid_days'];
                        $debugData['final_leave_deduction'] = $summary['leave_deduction'];
                    }
                }

                // Recompute totals consistent with client logic
                $formFields['total_earnings'] = (float)$formFields['basic_salary'] + (float)$formFields['bonus'] + (float)$formFields['incentives'] + (float)$formFields['ot_payment'];
                $formFields['net_pay'] = (float)$formFields['total_earnings'] + (float)$formFields['total_allowance'] - ((float)$formFields['total_deductions'] + (float)$formFields['leave_deduction']);
            } else {
                if ($isDebugMode) {
                    $debugData['auto_from_leave'] = false;
                    $debugData['final_lop'] = (float) $formFields['lop_days'];
                }
            }

            // Pre-check override requirement BEFORE updating payslip
            // Create a temporary payslip object with new values to check override requirement
            $tempPayslip = new Payslip($formFields);
            $tempPayslip->id = $payslip->id; // Preserve ID for checkOverrideRequired
            $syncService = app(LeaveBalanceSyncService::class);
            $overrideCheck = $syncService->checkOverrideRequired($tempPayslip);

            // If override is required and not confirmed, return special response
            if ($overrideCheck['override_required'] && !$request->boolean('override_confirmed', false)) {
                return response()->json([
                    'error' => false,
                    'override_required' => true,
                    'message' => 'Override confirmation required',
                    'override_data' => [
                        'delta_paid_leave' => $overrideCheck['delta_paid_leave'],
                        'available_balance' => $overrideCheck['available_balance'],
                        'excess_paid_leave' => $overrideCheck['excess_paid_leave'],
                        'baseline_lop' => $overrideCheck['baseline_lop'],
                        'submitted_lop' => $overrideCheck['submitted_lop'],
                    ]
                ], 200);
            }

            // Update the Payslip attributes
            $payslip->update($formFields);

            // Sync the related allowances and deductions
            if (!empty($allowance_ids)) {
                $payslip->allowances()->sync($allowance_ids);
            }
            if (!empty($deduction_ids)) {
                $payslip->deductions()->sync($deduction_ids);
            }

            // Apply new adjustment after update
            $syncResult = null;
            try {
                $syncService = app(LeaveBalanceSyncService::class);
                $syncResult = $syncService->syncFromPayslip($payslip, [
                    'is_update' => true,
                    'override_confirmed' => $request->boolean('override_confirmed', false)
                ]);

                if ($isDebugMode) {
                    $debugData['sync_result'] = $syncResult;
                }

                \Log::info('Payslip balance sync completed (update)', [
                    'payslip_id' => $payslip->id,
                    'user_id' => $payslip->user_id,
                    'month' => $payslip->month,
                    'sync_success' => $syncResult['success'] ?? false,
                    'balance_updated' => $syncResult['balance_updated'] ?? false,
                    'delta_paid_leave' => $syncResult['delta_paid_leave'] ?? 0,
                    'delta_lop' => $syncResult['delta_lop'] ?? 0
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail payslip update
                \Log::error('Leave balance sync failed during payslip update', [
                    'payslip_id' => $payslip->id,
                    'user_id' => $payslip->user_id,
                    'month' => $payslip->month,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                if ($isDebugMode) {
                    $debugData['sync_error'] = [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ];
                }
            }

            if ($isApi) {
                $responseData = ['data' => formatPayslip($payslip)];
                if ($isDebugMode && !empty($debugData)) {
                    $responseData['debug'] = $debugData;
                }

                return formatApiResponse(
                    false,
                    'Payslips updated successfully.',
                    $responseData,
                    200
                );
            }

            Session::flash('message', 'Payslip updated successfully.');

            $response = ['error' => false, 'id' => $payslip->id];
            if ($isDebugMode && !empty($debugData)) {
                $response['debug'] = $debugData;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return formatApiResponse(
                true,
                config('app.debug') ? $e->getMessage() : 'An error occurred',
                [],
                500
            );
        }
    }

    // Compute leave summary for a user-month and basic salary
    /**
     * Calculate leave summary for payslip
     *
     * Implements Flow 3: [Calculate Baseline LOP] using LeaveCalculationService
     *
     * @param int $userId
     * @param string $month YYYY-MM format
     * @param float $basicSalary
     * @return array
     */
    private function calculateLeaveSummary(int $userId, string $month, float $basicSalary): array
    {
        $workspaceId = $this->workspace->id;
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $workingDays = (float)$monthStart->daysInMonth;

        // [Calculate Baseline LOP] - Flow 3: Use LeaveCalculationService
        $baseline = $this->calculationService->calculateBaselineLOP(
            $userId,
            $workspaceId,
            $month
        );

        $paidLeaveDays = (float)($baseline['paid_leave_days'] ?? 0);
        $unpaidLeaveDays = (float)($baseline['unpaid_leave_days'] ?? 0);
        $lopDays = (float)($baseline['lop_days'] ?? 0);

        // Calculate paid days (working days - LOP)
        $paidDaysOut = max($workingDays - $lopDays, 0.0);

        // Calculate leave deduction
        $perDay = $workingDays > 0 ? ($basicSalary / $workingDays) : 0.0;
        $leaveDeduction = $perDay * $lopDays;

        return [
            'working_days' => $workingDays,
            'paid_days' => $paidDaysOut,
            'lop_days' => $lopDays,
            'leave_deduction' => round($leaveDeduction, 2),
            'breakdown' => [
                'paid_leave_days' => $paidLeaveDays,
                'unpaid_leave_days' => $unpaidLeaveDays,
                'total_leave_days' => (float)($baseline['total_leave_days'] ?? 0),
            ],
        ];
    }

    // API for JS to fetch summary
    public function leaveSummary(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'month' => ['required', 'string'], // YYYY-MM
            'basic_salary' => ['required', 'regex:/^\d+(\.\d+)?$/'],
        ]);
        $userId = (int)$request->user_id;
        $month = $request->month;
        $basicSalary = (float)$request->basic_salary;

        $summary = $this->calculateLeaveSummary($userId, $month, $basicSalary);

        $year = get_current_company_year();
        $annualSummary = $this->balanceEngine->getBalanceSummary(
            $userId,
            $this->workspace->id,
            $year
        );

        $summary['annual_summary'] = [
            'total_annual_leaves' => (float)($annualSummary['total_annual_leaves'] ?? 0),
            'accrued_leaves' => isset($annualSummary['accrued_leaves'])
                ? (float)$annualSummary['accrued_leaves']
                : null,
            'used_paid_leaves' => (float)($annualSummary['used_paid_leaves'] ?? 0),
            'remaining_paid_leaves' => (float)($annualSummary['remaining_paid_leaves'] ?? 0),
            'unpaid_leaves_taken' => (float)($annualSummary['unpaid_leaves_taken'] ?? 0),
            'utilization_percentage' => (float)($annualSummary['utilization_percentage'] ?? 0),
            'accrual_utilization_percentage' => isset($annualSummary['accrual_utilization_percentage'])
                ? (float)$annualSummary['accrual_utilization_percentage']
                : null,
        ];

        return response()->json(['error' => false, 'data' => $summary]);
    }

    // Generate PDF for a payslip
    public function pdf($id)
    {
        $payslip = Payslip::with(['user', 'allowances', 'deductions', 'paymentMethod'])->findOrFail($id);

        // Convert logo to base64 for PDF
        $logoBase64 = '';
        $general_settings = get_settings('general_settings');
        if (!empty($general_settings['full_logo'])) {
            $logoPath = public_path('storage/' . $general_settings['full_logo']);
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
            }
        }

        $data = ['payslip' => $payslip, 'logo_base64' => $logoBase64];
        $pdf = Pdf::loadView('payslips.pdf', $data);
        return $pdf->download(get_label('payslip_id_prefix', 'PSL-') . $payslip->id . '.pdf');
    }

    // Send payslip PDF via email
    public function sendEmail(Request $request, $id)
    {
        $payslip = Payslip::with(['user', 'allowances', 'deductions', 'paymentMethod'])->findOrFail($id);
        $to = $request->input('to', $payslip->user->email ?? null);
        $cc = $request->input('cc');

        if (!$to) {
            return response()->json(['error' => true, 'message' => 'Recipient email not found.'], 422);
        }

        // Get template similar to AssignmentNotification
        $general_settings = get_settings('general_settings');
        $company_title = $general_settings['company_title'] ?? 'Taskify';
        $fetched_data = Template::where('type', 'email')
            ->where('name', 'payslip')
            ->first();

        // Get subject
        $subject = $request->input('subject');
        if (empty($subject)) {
            if ($fetched_data && !empty($fetched_data->subject)) {
                $subject = $fetched_data->subject;
            } else {
                $subject = get_label('payslip', 'Payslip') . ' ' . get_label('payslip_id_prefix', 'PSL-') . $payslip->id;
            }
        }

        // Get content
        $content = $request->input('body');
        if (empty($content)) {
            if ($fetched_data && !empty($fetched_data->content)) {
                $templateContent = $fetched_data->content;
            } else {
                $defaultTemplatePath = resource_path('views/mail/default_templates/payslip.blade.php');
                $templateContent = File::get($defaultTemplatePath);
            }
        } else {
            $templateContent = $content;
        }

        // Prepare placeholders
        $monthYear = Carbon::parse($payslip->month)->format('F, Y');
        $contentPlaceholders = [
            '{FIRST_NAME}' => $payslip->user->first_name ?? '',
            '{LAST_NAME}' => $payslip->user->last_name ?? '',
            '{PAYSLIP_ID}' => get_label('payslip_id_prefix', 'PSL-') . $payslip->id,
            '{MONTH_YEAR}' => $monthYear,
            '{COMPANY_TITLE}' => $company_title,
            '{CURRENT_YEAR}' => date('Y'),
        ];

        // Replace placeholders
        $finalContent = str_replace(array_keys($contentPlaceholders), array_values($contentPlaceholders), $templateContent);

        // Get logo
        $full_logo_path = !isset($general_settings['full_logo']) || empty($general_settings['full_logo'])
            ? 'logos/default_full_logo.png'
            : $general_settings['full_logo'];
        $full_logo_url = asset('storage/' . $full_logo_path);

        // Convert logo to base64 for PDF
        $logoBase64 = '';
        if (!empty($general_settings['full_logo'])) {
            $logoPath = public_path('storage/' . $general_settings['full_logo']);
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
            }
        }

        $pdf = Pdf::loadView('payslips.pdf', ['payslip' => $payslip, 'logo_base64' => $logoBase64]);
        $filename = get_label('payslip_id_prefix', 'PSL-') . $payslip->id . '.pdf';

        // Parse CC emails
        $ccEmails = [];
        if (!empty($cc)) {
            $ccEmails = array_filter(array_map('trim', explode(',', $cc)));
        }

        Mail::send('mail.html', ['content' => $finalContent, 'logo_url' => $full_logo_url], function ($message) use ($to, $ccEmails, $subject, $pdf, $filename) {
            $message->to($to)->subject($subject);
            if (!empty($ccEmails)) {
                $message->cc($ccEmails);
            }
            $message->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
        });

        return response()->json(['error' => false, 'message' => 'Payslip emailed successfully.']);
    }

    public function view(Request $request, $id)
    {
        $payslip = Payslip::with(['user', 'allowances', 'deductions', 'paymentMethod'])->findOrFail($id);

        // Add helper properties for view compatibility
        $payslip->user_name = $payslip->user ? ($payslip->user->first_name . ' ' . $payslip->user->last_name) : '';
        $payslip->user_email = $payslip->user->email ?? '';
        $payslip->payment_method = $payslip->paymentMethod->title ?? '';

        // The ID corresponds to a user
        $creator = User::find(substr($payslip->created_by, 2)); // Remove the 'u_' prefix
        if ($creator !== null) {
            $payslip->creator = $creator->first_name . ' ' . $creator->last_name;
        } else {
            $payslip->creator = ' -';
        }
        $payslip->month = Carbon::parse($payslip->month);
        $payment_date = $payslip->payment_date !== null ? Carbon::parse($payslip->payment_date) : '';
        $payment_date = $payment_date != '' ? format_date($payment_date) : '-';
        $payslip->payment_date = $payment_date;
        $payslip->status = $payslip->status == 1 ? '<span class="badge bg-success">' . get_label('paid', 'Paid') . '</span>' : '<span class="badge bg-danger">' . get_label('unpaid', 'Unpaid') . '</span>';
        return view('payslips.view', compact('payslip'));
    }


    /**
     * Delete a payslip.
     *
     * This endpoint deletes the specified payslip and detaches all associated allowances and deductions.
     *
     * @authenticated
     *
     * @group Payslip Management
     *
     * @urlParam id integer required The ID of the payslip to delete. Example: 10
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Payslip deleted successfully."
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Payslip not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred."
     * }
     */


    public function destroy($id)
    {

        try {
            $payslip = Payslip::findOrFail($id);

            // Reverse leave balance adjustment before deletion
            try {
                $syncService = app(LeaveBalanceSyncService::class);
                $syncService->reverseAdjustment($payslip);
            } catch (\Exception $e) {
                // Log error but don't fail payslip deletion
                \Log::error('Leave balance reversal failed during payslip deletion: ' . $e->getMessage());
            }

            $payslip->allowances()->detach();
            $payslip->deductions()->detach();
            $response = DeletionService::delete(Payslip::class, $id, 'Payslip');
            return $response;
        } catch (\Exception $e) {
            formatApiResponse(
                false,
                config('app.debug') ? $e->getMessage() : 'An error occurred',
                [],
                500
            );
        }
    }

    public function destroy_multiple(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:payslips,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);

        $ids = $validatedData['ids'];
        $deletedPayslips = [];
        $deletedPayslipTitles = [];

        // Perform deletion using validated IDs
        $syncService = app(LeaveBalanceSyncService::class);
        foreach ($ids as $id) {
            $payslip = Payslip::findOrFail($id);
            if ($payslip) {
                // Reverse leave balance adjustment before deletion
                try {
                    $syncService->reverseAdjustment($payslip);
                } catch (\Exception $e) {
                    // Log error but don't fail payslip deletion
                    \Log::error('Leave balance reversal failed during payslip deletion (ID: ' . $id . '): ' . $e->getMessage());
                }

                $deletedPayslips[] = $id;
                $deletedPayslipTitles[] = get_label('payslip_id_prefix', 'PSL-') . $id;
                $payslip->allowances()->detach();
                $payslip->deductions()->detach();
                DeletionService::delete(Payslip::class, $id, 'Payslip');
            }
        }

        return response()->json(['error' => false, 'message' => 'Payslip(s) deleted successfully.', 'id' => $deletedPayslips, 'titles' => $deletedPayslipTitles]);
    }

    public function duplicate($id)
    {
        $relatedTables = ['deductions', 'allowances']; // Include related tables as needed

        // Use the general duplicateRecord function
        $duplicate = duplicateRecord(Payslip::class, $id, $relatedTables);

        if (!$duplicate) {
            return response()->json(['error' => true, 'message' => 'Payslip duplication failed.']);
        }
        return response()->json(['error' => false, 'message' => 'Payslip duplicated successfully.', 'id' => $id]);
    }



    /**
     * List payslips with filtering (API format).
     *
     * Retrieves a list of payslips in API format with support for searching, filtering by user, status, and month.
     *
     * @group Payslip Management
     *
     * @queryParam search string optional Search term to filter payslips by ID, note, or payment method. Example: PSL-5
     * @queryParam sort string optional Field to sort by. Defaults to id. Example: month
     * @queryParam order string optional Sort order: ASC or DESC. Defaults to DESC. Example: ASC
     * @queryParam limit integer optional Number of records to return. Default: 10. Example: 20
     * @queryParam statuses array optional Filter by status values (0 = Unpaid, 1 = Paid). Example: [0, 1]
     * @queryParam user_ids array optional Filter by user IDs. Example: [3, 4]
     * @queryParam created_by_user_ids array optional Filter by user IDs who created the payslip. Example: [2]
     * @queryParam created_by_client_ids array optional Filter by client IDs who created the payslip. Example: [1]
     * @queryParam month string optional Filter by month. Format: YYYY-MM. Example: 2025-05
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Payslips retrieved successfully.",
     *   "data": {
     *     "total": 5,
     *     "data": [
     *       {
     *         "id": 7,
     *         "user_id": 3,
     *         "user_name": "John Doe",
     *         "month": "2025-05-01",
     *         "basic_salary": 50000.00,
     *         "working_days": 22,
     *         "lop_days": 2,
     *         "paid_days": 20,
     *         "bonus": 2000.00,
     *         "incentives": 1500.00,
     *         "leave_deduction": 500.00,
     *         "ot_hours": 5,
     *         "ot_rate": 100.00,
     *         "ot_payment": 500.00,
     *         "total_allowance": 3500.00,
     *         "total_deductions": 500.00,
     *         "total_earnings": 53500.00,
     *         "net_pay": 53000.00,
     *         "status": 1,
     *         "status_label": "Paid",
     *         "payment_method_id": 2,
     *         "payment_method": "Bank Transfer",
     *         "payment_date": "2025-05-20",
     *         "note": "Bonus included for performance.",
     *         "created_at": "2025-05-15",
     *         "updated_at": "2025-05-15"
     *       }
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred.",
     *   "data": []
     * }
     */

    public function apiList()
    {

        try {
            $search = request('search');
            $sort = (request('sort')) ? request('sort') : "id";
            $order = (request('order')) ? request('order') : "DESC";
            $limit = (request('limit')) ? request('limit') : 10;
            $statuses = request('statuses', []);
            $user_ids = request('user_ids', []);
            $created_by_user_ids = request('created_by_user_ids', []);
            $created_by_client_ids = request('created_by_client_ids', []);
            $month = (request('month')) ? request('month') : "";
            $where = ['payslips.workspace_id' => $this->workspace->id];


            $payslips = Payslip::select(
                'payslips.*',
                DB::raw('CONCAT(users.first_name, " ", users.last_name) AS user_name'),
                'payment_methods.title as payment_method'
            )
                ->leftJoin('users', 'payslips.user_id', '=', 'users.id')
                ->leftJoin('payment_methods', 'payslips.payment_method_id', '=', 'payment_methods.id');


            if (!isAdminOrHasAllDataAccess()) {
                $payslips = $payslips->where(function ($query) {
                    $query->where('payslips.created_by', isClient() ? 'c_' . $this->user->id : 'u_' . $this->user->id)
                        ->orWhere('payslips.user_id', $this->user->id);
                });
            }

            if (!empty($statuses)) {
                $payslips->whereIn('payslips.status', $statuses);
            }

            if (!empty($user_ids)) {
                $payslips->whereIn('payslips.user_id', $user_ids);
            }

            if (!empty($created_by_user_ids)) {
                $payslips->whereIn('payslips.created_by', array_map(function ($id) {
                    return 'u_' . $id;
                }, $created_by_user_ids));
            }

            if (!empty($created_by_client_ids)) {
                $payslips->whereIn('payslips.created_by', array_map(function ($id) {
                    return 'c_' . $id;
                }, $created_by_client_ids));
            }

            if ($month) {
                $where['month'] = $month;
            }
            if ($search) {
                $payslips = $payslips->where(function ($query) use ($search) {
                    $query->where('payslips.id', 'like', '%' . $search . '%')
                        ->orWhere(DB::raw('CONCAT("' . get_label('payslip_id_prefix', 'PSL-') . '", payslips.id)'), 'like', '%' . $search . '%')
                        ->orWhere('payslips.note', 'like', '%' . $search . '%')
                        ->orWhere('payment_methods.title', 'like', '%' . $search . '%');
                });
            }

            $payslips->where($where);
            $total = $payslips->count();

            $payslips = $payslips->orderBy($sort, $order)
                ->take($limit)
                ->get()
                ->map(function ($payslip) {
                    return formatPayslip($payslip);
                });


            return formatApiResponse(
                false,
                'Payslip retrieved successfully.',
                [
                    'total' => $total,
                    'data' => $payslips
                ]
            );
        } catch (\Exception $e) {
            dd($e);
            return formatApiResponse(
                true,
                config('app.debug') ? $e->getMessage() : 'An error occurred'
            );
        }
    }
}
