<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Workspace;
use App\Models\LeaveEditor;
use Illuminate\Support\Str;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use App\Services\DeletionService;
use App\Services\LeaveCalculationService;
use App\Services\LeaveRequestValidator;
use App\Services\LeaveBalanceEngine;
use App\Events\LeaveRequestCreated;
use App\Events\LeaveRequestApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\UserClientPreference;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    protected $workspace;
    protected $user;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // fetch session and use it in entire class with constructor
            $this->workspace = Workspace::find(getWorkspaceId());
            $this->user = getAuthenticatedUser();
            return $next($request);
        });
    }
    public function index()
    {

        $leave_requests = is_admin_or_leave_editor() ? $this->workspace->leave_requests() : $this->user->leave_requests();
        $leaveEditors = User::whereHas('leaveEditors')->get();
        return view('leave_requests.list', ['leave_requests' => $leave_requests->count(), 'leaveEditors' => $leaveEditors, 'auth_user' => $this->user]);
    }


    /**
     * Create a new leave request.
     *
     * This endpoint creates a new leave request with the provided details. The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Leave Request Management
     *
     * @bodyParam reason string required The reason for the leave. Example: Family function
     * @bodyParam from_date date required The start date of the leave in the format specified in the general settings. Example: 2024-08-05
     * @bodyParam to_date date required The end date of the leave in the format specified in the general settings. Example: 2024-08-01
     * @bodyParam from_time time required_if:partialLeave,on The start time of the leave in HH:MM format. Example: 09:00
     * @bodyParam to_time time required_if:partialLeave,on The end time of the leave in HH:MM format. Example: 17:00
     * @bodyParam status string nullable The status of the leave request. Can be 'pending', 'approved', or 'rejected'. Example: pending
     * @bodyParam leaveVisibleToAll string optional Set to 'on' if the leave should be visible to all users in the workspace. Example: on
     * @bodyParam visible_to_ids array The IDs of users who can see the leave if it is not visible to all. Example: [1, 2, 3]
     * @bodyParam user_id int The ID of the user requesting the leave. Only admins or leave editors can specify this. Example: 4
     * @bodyParam partialLeave string optional Set to 'on' if the leave is partial (specific times within a day). Example: on
     * @bodyParam comment string optional An optional comment that can only be set by admin or leave editor. Example: Approved due to exceptional circumstances
     *
     * @response 200 {
     * "error": false,
     * "message": "Leave request created successfully.",
     * "id": 187,
     * "type": "leave_request",
     * "data": {
     *   "id": 187,
     *   "user_name": "Madhavan Vaidya",
     *   "user_photo": "https://test-taskify.infinitietech.com/storage/photos/yxNYBlFLALdLomrL0JzUY2USPLILL9Ocr16j4n2o.png",
     *   "action_by": null,
     *   "action_by_id": null,
     *   "from_date": "Wed, 07-08-2024",
     *   "to_date": "Wed, 07-08-2024",
     *   "type": "Full",
     *   "duration": "1 day",
     *   "reason": "Test",
     *   "status": "Pending",
     *   "visible_to": null,
     *   "created_at": "07-08-2024 18:31:28",
     *   "updated_at": "07-08-2024 18:31:28"
     * }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "reason": [
     *       "The reason field is required."
     *     ],
     *     "from_date": [
     *       "The from date field is required."
     *     ],
     *     "to_date": [
     *       "The to date field is required."
     *     ],
     *     "from_time": [
     *       "The from time field is required when partial leave is checked."
     *     ],
     *     "to_time": [
     *       "The to time field is required when partial leave is checked."
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while creating the leave request."
     * }
     */

    public function store(Request $request)
    {
        $isApi = request()->get('isApi', false);
        $calculationService = app(LeaveCalculationService::class);
        $validator = app(LeaveRequestValidator::class);
        $balanceEngine = app(LeaveBalanceEngine::class);

        // Basic validation rules
        $rules = [
            'reason' => ['required'],
            'from_date' => ['required'],
            'to_date' => ['required'],
            'from_time' => ['required_if:partialLeave,on'],
            'to_time' => ['required_if:partialLeave,on'],
            'status' => ['nullable'],
            'user_id' => 'nullable|exists:users,id',
            'visible_to_ids.*' => 'exists:users,id',
            'comment' => ['nullable']
        ];
        $messages = [
            'from_time.required_if' => 'The from time field is required when partial leave is checked.',
            'to_time.required_if' => 'The to time field is required when partial leave is checked.',
        ];

        DB::beginTransaction();
        try {
            // [Validate Dates/Times] - Flow 1
            $formFields = $request->validate($rules, $messages);

            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');

            // Validate dates BEFORE conversion to catch format errors early
            $dateValidation = $validator->validateDates(
                $from_date,
                $to_date,
                $request->input('from_time'),
                $request->input('to_time'),
                $isApi
            );

            if (!$dateValidation['valid']) {
                DB::rollBack();
                return formatApiValidationError($isApi, $dateValidation['errors']);
            }

            // Convert to database format AFTER validation passes
            $formFields['from_date'] = format_date($from_date, false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
            $formFields['to_date'] = format_date($to_date, false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');

            // Determine user and workspace
            $formFields['workspace_id'] = $this->workspace->id;
            $formFields['user_id'] = is_admin_or_leave_editor() && $request->filled('user_id')
                ? $request->input('user_id')
                : $this->user->id;

            $userId = $formFields['user_id'];
            $workspaceId = $formFields['workspace_id'];

            // [Calculate total_days] - Flow 1
            $totalDays = $calculationService->calculateLeaveDays(
                $formFields['from_date'],
                $formFields['to_date'],
                $request->input('from_time'),
                $request->input('to_time')
            );
            $formFields['total_days'] = $totalDays;

            // [Check if Admin/Approved] - Flow 1
            $isAdmin = is_admin_or_leave_editor();
            $status = $request->input('status', 'pending');
            $isApproved = $status === 'approved' && $isAdmin;

            // Validate status transition
            if ($isApproved) {
                $statusValidation = $validator->validateStatusTransition('pending', 'approved', $isAdmin);
                if (!$statusValidation['valid']) {
                    DB::rollBack();
                    return response()->json(['error' => true, 'message' => $statusValidation['error']], 422);
                }
            }

            // [Validate Overlap] - Critical validation
            $overlapCheck = $validator->validateLeaveOverlap(
                $userId,
                $workspaceId,
                $formFields['from_date'],
                $formFields['to_date'],
                $request->input('from_time'),
                $request->input('to_time')
            );

            if ($overlapCheck['has_overlap']) {
                // Log overlap
                $validator->logOverlap(0, $overlapCheck, 'warned', $this->user->id);

                // For now, warn but allow (can be changed to block)
                // Uncomment to block overlapping leaves:
                // DB::rollBack();
                // return response()->json([
                //     'error' => true,
                //     'message' => 'This leave overlaps with existing approved leaves.',
                //     'overlapping_leaves' => $overlapCheck['overlapping_leaves']
                // ], 422);
            }

            // [Calculate Paid/Unpaid] - Flow 1 (only if approved)
            if ($isApproved) {
                // Check if admin wants to mark as paid
                $isPaidToggle = $request->has('is_paid') && $request->input('is_paid') == '1';

                if ($isPaidToggle) {
                    // [Check Balance] - Flow 1
                    $balanceCheck = $validator->checkBalanceSufficiency(
                        $userId,
                        $workspaceId,
                        $totalDays
                    );

                    // [Calculate Paid/Unpaid] based on balance
                    $paidUnpaidDays = $calculationService->calculatePaidUnpaidDays(
                        $userId,
                        $workspaceId,
                        $totalDays
                    );

                    $formFields['paid_days'] = $paidUnpaidDays['paid_days'];
                    $formFields['unpaid_days'] = $paidUnpaidDays['unpaid_days'];
                    $formFields['is_paid'] = $paidUnpaidDays['paid_days'] > 0;
                } else {
                    // Admin marked as unpaid
                    $formFields['paid_days'] = 0;
                    $formFields['unpaid_days'] = $totalDays;
                    $formFields['is_paid'] = false;
                }
            } else {
                // Pending leave - no paid/unpaid calculation yet
                $formFields['paid_days'] = 0;
                $formFields['unpaid_days'] = 0;
                $formFields['is_paid'] = false;
            }

            // Set other fields
            $formFields['status'] = $status;
            if ($isAdmin && $status != 'pending') {
                $formFields['action_by'] = $this->user->id;
            }
            $formFields['comment'] = $isAdmin && $request->filled('comment')
                ? $request->input('comment')
                : NULL;

            $leaveVisibleToAll = $request->input('leaveVisibleToAll') && $request->filled('leaveVisibleToAll') && $request->input('leaveVisibleToAll') == 'on' ? 1 : 0;
            $formFields['visible_to_all'] = $leaveVisibleToAll;

            // Add from_time and to_time for partial leave
            if ($request->has('partialLeave') && $request->input('partialLeave') == 'on') {
                $formFields['from_time'] = $request->input('from_time');
                $formFields['to_time'] = $request->input('to_time');
            } else {
                $formFields['from_time'] = null;
                $formFields['to_time'] = null;
            }

            // [Create Leave Request] - Flow 1
            $lr = LeaveRequest::create($formFields);

            if (!$lr) {
                DB::rollBack();
                return response()->json(['error' => true, 'message' => 'Leave request couldn\'t be created.']);
            }

            // Sync visibility
            if ($leaveVisibleToAll == 0) {
                $visibleToUsers = $request->input('visible_to_ids', []);
                $lr->visibleToUsers()->sync($visibleToUsers);
            }

            // [Update Balance] - Flow 1 (if approved)
            if ($lr->status == 'approved' && $lr->is_paid && $lr->paid_days > 0) {
                $balanceEngine->updateBalance($userId, $workspaceId, $lr);
            }

            // [Send Notifications] - Flow 1
            $this->sendLeaveRequestNotifications($lr, 'created');

            // Fire event
            event(new LeaveRequestCreated($lr));
            if ($lr->status === 'approved') {
                event(new LeaveRequestApproved($lr, 'pending'));
            }

            DB::commit();

            $leaveRequest = $lr->fresh();
            return formatApiResponse(
                false,
                'Leave request created successfully.',
                [
                    'id' => $lr->id,
                    'type' => 'leave_request',
                    'data' => formatLeaveRequest($leaveRequest)
                ]
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Leave request creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while creating the leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notifications for leave request
     *
     * @param LeaveRequest $leaveRequest
     * @param string $action 'created' or 'updated'
     * @return void
     */
    protected function sendLeaveRequestNotifications(LeaveRequest $leaveRequest, string $action = 'created', ?string $oldStatus = null): void
    {
        $fromDate = Carbon::parse($leaveRequest->from_date);
        $toDate = Carbon::parse($leaveRequest->to_date);
        $fromDateDayOfWeek = $fromDate->format('D');
        $toDateDayOfWeek = $toDate->format('D');

        $calculationService = app(LeaveCalculationService::class);
        $totalDays = $calculationService->calculateLeaveDays(
            $leaveRequest->from_date,
            $leaveRequest->to_date,
            $leaveRequest->from_time,
            $leaveRequest->to_time
        );

        $leaveType = $leaveRequest->isPartialLeave()
            ? get_label('partial', 'Partial')
            : get_label('full', 'Full');

        // Normalize time format - ensure it has seconds for format_date
        $fromTimeFormatted = $leaveRequest->from_time
            ? (strlen($leaveRequest->from_time) == 5 ? $leaveRequest->from_time . ':00' : $leaveRequest->from_time)
            : null;
        $toTimeFormatted = $leaveRequest->to_time
            ? (strlen($leaveRequest->to_time) == 5 ? $leaveRequest->to_time . ':00' : $leaveRequest->to_time)
            : null;

        $from = $fromDateDayOfWeek . ', ' . ($fromTimeFormatted
            ? format_date($leaveRequest->from_date . ' ' . $fromTimeFormatted, true, null, null, false)
            : format_date($leaveRequest->from_date));
        $to = $toDateDayOfWeek . ', ' . ($toTimeFormatted
            ? format_date($leaveRequest->to_date . ' ' . $toTimeFormatted, true, null, null, false)
            : format_date($leaveRequest->to_date));

        $duration = $leaveRequest->isPartialLeave()
            ? number_format($totalDays * 8, 2) . ' hour' . ($totalDays * 8 > 1 ? 's' : '')
            : $totalDays . ' day' . ($totalDays > 1 ? 's' : '');

        $user = User::find($leaveRequest->user_id);
        $workspaceUsers = $this->workspace->users()->pluck('users.id')->toArray();

        // Determine recipients (admins and leave editors)
        $adminModelIds = DB::table('model_has_roles')
            ->select('model_id')
            ->where('role_id', 1)
            ->pluck('model_id')
            ->toArray();

        $leaveEditorIds = DB::table('leave_editors')
            ->pluck('user_id')
            ->toArray();

        $adminInWorkspace = array_intersect($adminModelIds, $workspaceUsers);
        $leaveEditorsInWorkspace = array_intersect($leaveEditorIds, $workspaceUsers);

        $adminIds = array_map(function ($modelId) {
            return 'u_' . $modelId;
        }, $adminInWorkspace);

        $leaveEditorIdsWithPrefix = array_map(function ($leaveEditorId) {
            return 'u_' . $leaveEditorId;
        }, $leaveEditorsInWorkspace);

        $recipients = array_merge($adminIds, $leaveEditorIdsWithPrefix);

        // Send creation/update notification
        $notificationData = [
            'type' => $action === 'created' ? 'leave_request_creation' : 'leave_request_status_updation',
            'type_id' => $leaveRequest->id,
            'team_member_first_name' => $user->first_name,
            'team_member_last_name' => $user->last_name,
            'leave_type' => $leaveType,
            'from' => $from,
            'to' => $to,
            'duration' => $duration,
            'reason' => $leaveRequest->reason,
            'comment' => $leaveRequest->comment ?? '-',
            'status' => ucfirst($leaveRequest->status),
            'action' => $action
        ];

        if ($action === 'updated') {
            $notificationData['updater_first_name'] = $this->user->first_name;
            $notificationData['updater_last_name'] = $this->user->last_name;
            // Add old_status and new_status for status update notifications
            // getTitle() expects these to be capitalized (e.g., "Pending", "Approved")
            $notificationData['old_status'] = $oldStatus ? ucfirst($oldStatus) : ucfirst($leaveRequest->status);
            $notificationData['new_status'] = ucfirst($leaveRequest->status);
        }

        // Add requester to recipients for status updates
        if ($action === 'updated') {
            $recipients[] = 'u_' . $leaveRequest->user_id;
        }

        processNotifications($notificationData, $recipients);

        // Send team member on leave alert if approved and not ended
        if ($leaveRequest->status == 'approved') {
            $appTimezone = config('app.timezone');
            $currentDateTime = new \DateTime('now', new \DateTimeZone($appTimezone));
            $leaveEndDate = new \DateTime($leaveRequest->to_date, new \DateTimeZone($appTimezone));

            if ($leaveRequest->to_time) {
                $leaveEndDate->setTime((int) substr($leaveRequest->to_time, 0, 2), (int) substr($leaveRequest->to_time, 3, 2));
            } else {
                $leaveEndDate->setTime(23, 59, 59);
            }
            $leaveEndDate->setTimezone(new \DateTimeZone($appTimezone));

            if ($currentDateTime < $leaveEndDate) {
                if ($leaveRequest->visible_to_all == 1) {
                    $recipientTeamMembers = $workspaceUsers;
                } else {
                    $recipientTeamMembers = $leaveRequest->visibleToUsers->pluck('id')->toArray();
                    $recipientTeamMembers = array_merge($adminInWorkspace, $leaveEditorsInWorkspace, $recipientTeamMembers);
                }

                $recipientTeamMembers = array_diff($recipientTeamMembers, [$leaveRequest->user_id]);
                $recipientTeamMemberIds = array_map(function ($userId) {
                    return 'u_' . $userId;
                }, $recipientTeamMembers);

                $notificationData = [
                    'type' => 'team_member_on_leave_alert',
                    'type_id' => $leaveRequest->id,
                    'team_member_first_name' => $user->first_name,
                    'team_member_last_name' => $user->last_name,
                    'leave_type' => $leaveType,
                    'from' => $from,
                    'to' => $to,
                    'duration' => $duration,
                    'reason' => $leaveRequest->reason,
                    'action' => 'team_member_on_leave_alert'
                ];
                processNotifications($notificationData, $recipientTeamMemberIds);
            }
        }
    }


    public function list()
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $user_ids = request('user_ids');
        $action_by_ids = request('action_by_ids');
        $types = request('types');
        $statuses = request('statuses');
        $date_between_from = request('lr_date_between_from') ?: (request('date_between_from') ?: "");
        $date_between_to = request('lr_date_between_to') ?: (request('date_between_to') ?: "");
        $start_date_from = request('lr_start_date_from') ?: (request('start_date_from') ?: "");
        $start_date_to = request('lr_start_date_to') ?: (request('start_date_to') ?: "");
        $end_date_from = request('lr_end_date_from') ?: (request('end_date_from') ?: "");
        $end_date_to = request('lr_end_date_to') ?: (request('end_date_to') ?: "");
        $where = ['workspace_id' => $this->workspace->id];

        if (!is_admin_or_leave_editor()) {
            // If the user is not an admin or leave editor, filter by user_id
            $where['user_id'] = $this->user->id;
        }

        $leave_requests = LeaveRequest::select(
            'leave_requests.*',
            'users.photo AS user_photo',
            DB::raw('CONCAT(users.first_name, " ", users.last_name) AS user_name'),
            DB::raw('CONCAT(action_users.first_name, " ", action_users.last_name) AS action_by_name')
        )
            ->leftJoin('users', 'leave_requests.user_id', '=', 'users.id')
            ->leftJoin('users AS action_users', 'leave_requests.action_by', '=', 'action_users.id');

        if (!empty($user_ids)) {
            $leave_requests = $leave_requests->whereIn('user_id', $user_ids);
        }

        if (!empty($action_by_ids)) {
            $leave_requests = $leave_requests->whereIn('action_by', $action_by_ids);
        }

        if (!empty($statuses)) {
            $leave_requests = $leave_requests->whereIn('leave_requests.status', $statuses);
        }

        if (!empty($types)) {
            $leave_requests = $leave_requests->where(function ($query) use ($types) {
                if (in_array('full', $types)) {
                    $query->orWhereNull('from_time')->whereNull('to_time');
                }
                if (in_array('partial', $types)) {
                    $query->orWhereNotNull('from_time')->whereNotNull('to_time');
                }
            });
        }
        if ($date_between_from && $date_between_to) {
            // Overlap detection: Find leave requests that overlap with the date range
            $leave_requests = $leave_requests->where(function ($q) use ($date_between_from, $date_between_to) {
                $q->where('from_date', '<=', $date_between_to)
                    ->where('to_date', '>=', $date_between_from);
            });
        }
        if ($start_date_from && $start_date_to) {
            $leave_requests = $leave_requests->whereBetween('from_date', [$start_date_from, $start_date_to]);
        }
        if ($end_date_from && $end_date_to) {
            $leave_requests = $leave_requests->whereBetween('to_date', [$end_date_from, $end_date_to]);
        }
        if ($search) {
            $leave_requests = $leave_requests->where(function ($query) use ($search) {
                $query->where('reason', 'like', '%' . $search . '%')
                    ->orWhere('leave_requests.id', 'like', '%' . $search . '%');
            });
        }

        $leave_requests->where($where);
        $total = $leave_requests->count();

        $isAdmin = $this->user->hasRole('admin');
        $isAdminOrLeaveEditor = is_admin_or_leave_editor();

        $leave_requests = $leave_requests->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(function ($leave_request) use ($isAdmin, $isAdminOrLeaveEditor) {
                // Calculate the duration in hours if both from_time and to_time are provided
                $fromDate = Carbon::parse($leave_request->from_date);
                $toDate = Carbon::parse($leave_request->to_date);

                $fromDateDayOfWeek = $fromDate->format('D');
                $toDateDayOfWeek = $toDate->format('D');

                if ($leave_request->from_time && $leave_request->to_time) {
                    $duration = 0;
                    // Loop through each day
                    while ($fromDate->lessThanOrEqualTo($toDate)) {
                        // Create Carbon instances for the start and end times of the leave request for the current day
                        $fromDateTime = Carbon::parse($fromDate->toDateString() . ' ' . $leave_request->from_time);
                        $toDateTime = Carbon::parse($fromDate->toDateString() . ' ' . $leave_request->to_time);

                        // Calculate the duration for the current day and add it to the total duration
                        $duration += $fromDateTime->diffInMinutes($toDateTime) / 60; // Duration in hours

                        // Move to the next day
                        $fromDate->addDay();
                    }
                } else {
                    // Calculate the inclusive duration in days
                    $duration = $fromDate->diffInDays($toDate) + 1;
                }

                // Format "from_date" and "to_date" with labels
                $formattedDates = $duration > 1 ? format_date($leave_request->from_date) . ' ' . get_label('to', 'To') . ' ' . format_date($leave_request->to_date) : format_date($leave_request->from_date);
                $statusBadges = [
                    'pending' => '<span class="badge bg-warning">' . get_label('pending', 'Pending') . '</span>',
                    'approved' => '<span class="badge bg-success">' . get_label('approved', 'Approved') . '</span>',
                    'rejected' => '<span class="badge bg-danger">' . get_label('rejected', 'Rejected') . '</span>',
                ];
                $statusBadge = $statusBadges[$leave_request->status] ?? '';

            if ($leave_request->visible_to_all == 1) {
                    $visibleTo = get_label('all', 'All');
                } else {
                    $visibleTo = $leave_request->visibleToUsers->isEmpty()
                        ? '-'
                        : $leave_request->visibleToUsers->map(function ($user) {
                            if ($this->user->can('manage_users')) {
                                // Render clickable link if permission exists
                                $profileLink = route('users.profile', ['id' => $user->id]);
                                return '<a href="' . $profileLink . '">' . $user->first_name . ' ' . $user->last_name . '</a>';
                    } else {
                        // Render plain text if no permission
                        return $user->first_name . ' ' . $user->last_name;
                    }
                        })->implode(', ');
                }

                $actions = '<div class="d-flex justify-content-center">';
                $actions .= '<button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>';
                $actions .= '<ul class="dropdown-menu dropdown-menu-end">';
                $hasAction = false;

                if ($isAdmin || $leave_request->action_by === null) {
                    $actions .= '<li><a class="dropdown-item edit-leave-request" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_leave_request_modal" data-id="' . $leave_request->id . '"><i class="bx bx-edit text-muted"></i> ' . get_label('update', 'Update') . '</a></li>';
                    $hasAction = true;
                }

                if ($isAdminOrLeaveEditor || $leave_request->status == 'pending') {
                    $actions .= '<li><a class="dropdown-item delete" href="javascript:void(0);" data-id="' . $leave_request->id . '" data-type="leave-requests" data-table="lr_table"><i class="bx bx-trash text-muted"></i> ' . get_label('delete', 'Delete') . '</a></li>';
                    $hasAction = true;
                }
                
                $actions .= '</ul></div>';
                $actions = $hasAction ? $actions : '-';

                return [
                    'id' => $leave_request->id,
                    'user_name' => formatUserHtml($leave_request->user),
                    'action_by' => formatUserHtml(User::find($leave_request->action_by)),
                    'from_date' => $fromDateDayOfWeek . ', ' . ($leave_request->from_time ? format_date($leave_request->from_date . ' ' . $leave_request->from_time, true, null, null, false) : format_date($leave_request->from_date)),
                    'to_date' => $toDateDayOfWeek . ', ' . ($leave_request->to_time ? format_date($leave_request->to_date . ' ' . $leave_request->to_time, true, null, null, false) : format_date($leave_request->to_date)),
                    'type' => $leave_request->from_time && $leave_request->to_time ? '<span class="badge bg-info">' . get_label('partial', 'Partial') . '</span>' : '<span class="badge bg-primary">' . get_label('full', 'Full') . '</span>',
                    'duration' => $leave_request->from_time && $leave_request->to_time ? number_format($duration, 2) . ' hour' . ($duration > 1 ? 's' : '') : $duration . ' day' . ($duration > 1 ? 's' : ''),
                    'reason' => $leave_request->reason,
                    'comment' => $leave_request->comment,
                    'status' => $statusBadge,
                    'visible_to' => $visibleTo,
                    'created_at' => format_date($leave_request->created_at, true),
                    'updated_at' => format_date($leave_request->updated_at, true),
                'actions' => $actions ? $actions : '-'
                ];
            });
        return response()->json([
            "rows" => $leave_requests->items(),
            "total" => $total,
        ]);
    }

    /**
     * List or search leave requests.
     *
     * This endpoint retrieves a list of leave requests based on various filters. The user must be authenticated to perform this action. The request allows filtering by status, user, action_by, date ranges, type, and search term.
     *
     * @authenticated
     *
     * @group Leave Request Management
     *
     * @urlParam id int optional The ID of the leave request to retrieve. Example: 1
     *
     * @queryParam search string optional The search term to filter leave requests by reason or id. Example: Vacation
     * @queryParam sort string optional The field to sort by. Defaults to "id". Sortable fields include: id, from_date, to_date, type, reason, status, action_by_id, created_at, and updated_at. Example: id
     * @queryParam order string optional The sort order, either "ASC" or "DESC". Defaults to "DESC". Example: ASC
     * @queryParam status string optional The status of the leave request to filter by. Can be "pending", "approved", "rejected", etc. Example: pending
     * @queryParam user_id int optional The user ID to filter leave requests by. Example: 1
     * @queryParam action_by_id int optional The ID of the user who acted on the request to filter by. Example: 2
     * @queryParam start_date_from string optional The start date range's start in YYYY-MM-DD format. Example: 2024-01-01
     * @queryParam start_date_to string optional The start date range's end in YYYY-MM-DD format. Example: 2024-12-31
     * @queryParam end_date_from string optional The end date range's start in YYYY-MM-DD format. Example: 2024-01-01
     * @queryParam end_date_to string optional The end date range's end in YYYY-MM-DD format. Example: 2024-12-31
     * @queryParam type string optional The type of leave request. Can be "full" or "partial". Example: full
     * @queryParam limit int optional The number of leave requests per page for pagination. Example: 10
     * @queryParam offset int optional The offset for pagination, indicating the starting point of results. Example: 0
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Leave requests retrieved successfully",
     *   "total": 25,
     *   "data": [
     *     {
     *       "id": 175,
     *       "user_name": "Admin Test",
     *       "user_photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg",
     *       "action_by": null,
     *       "from_date": "Mon, 29-07-2024",
     *       "to_date": "Mon, 29-07-2024",
     *       "type": "Full",
     *       "duration": "1 day",
     *       "reason": "dsdsdsd",
     *       "status": "Pending",
     *       "visible_to": [
     *         {
     *           "id": 183,
     *           "first_name": "Girish",
     *           "last_name": "Thacker",
     *           "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     *         }
     *       ],
     *       "created_at": "29-07-2024 10:02:45",
     *       "updated_at": "29-07-2024 10:02:45"
     *     }
     *   ]
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Leave request not found",
     *   "total": 0,
     *   "data": []
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Leave requests not found",
     *   "total": 0,
     *   "data": []
     * }
     */
    public function apiList(Request $request, $id = '')
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $status = $request->input('status', '');
        $user_id = $request->input('user_id', '');
        $action_by_id = $request->input('action_by_id', '');
        $date_between_from = $request->input('lr_date_between_from') ?: $request->input('date_between_from', '');
        $date_between_to = $request->input('lr_date_between_to') ?: $request->input('date_between_to', '');
        $start_date_from = $request->input('lr_start_date_from') ?: $request->input('start_date_from', '');
        $start_date_to = $request->input('lr_start_date_to') ?: $request->input('start_date_to', '');
        $end_date_from = $request->input('lr_end_date_from') ?: $request->input('end_date_from', '');
        $end_date_to = $request->input('lr_end_date_to') ?: $request->input('end_date_to', '');
        $type = $request->input('type', '');
        $limit = $request->input('limit', 10); // default limit
        $offset = $request->input('offset', 0); // default offset

        if ($id) {
            $leaveRequest = LeaveRequest::find($id);
            if (!$leaveRequest) {
                return formatApiResponse(
                    false,
                    'Leave request not found',
                    [
                        'total' => 0,
                        'data' => []
                    ]
                );
            } else {
                return formatApiResponse(
                    false,
                    'Leave request retrieved successfully',
                    [
                        'total' => 1,
                        'data' => [formatLeaveRequest($leaveRequest)]
                    ]
                );
            }
        }

        $leaveRequestsQuery = isAdminOrHasAllDataAccess() ? $this->workspace->leave_requests() : $this->user->leave_requests();

        if (!is_admin_or_leave_editor()) {
            // If the user is not an admin or leave editor, filter by user_id
            $leaveRequestsQuery->where('leave_requests.user_id', $this->user->id);
        }
        if ($status != '') {
            $leaveRequestsQuery->where('leave_requests.status', $status);
        }
        if ($user_id) {
            $leaveRequestsQuery->where('leave_requests.user_id', $user_id);
        }
        if ($action_by_id) {
            $leaveRequestsQuery->where('leave_requests.action_by', $action_by_id);
        }
        if ($date_between_from && $date_between_to) {
            $leaveRequestsQuery->where(function ($q) use ($date_between_from, $date_between_to) {
                $q->where('from_date', '<=', $date_between_to)
                    ->where('to_date', '>=', $date_between_from);
            });
        }
        if ($start_date_from && $start_date_to) {
            $leaveRequestsQuery->whereBetween('leave_requests.from_date', [$start_date_from, $start_date_to]);
        }
        if ($end_date_from && $end_date_to) {
            $leaveRequestsQuery->whereBetween('leave_requests.to_date', [$end_date_from, $end_date_to]);
        }
        if ($type) {
            if ($type == 'full') {
                $leaveRequestsQuery->whereNull('leave_requests.from_time')->whereNull('leave_requests.to_time');
            } elseif ($type == 'partial') {
                $leaveRequestsQuery->whereNotNull('leave_requests.from_time')->whereNotNull('leave_requests.to_time');
            }
        }
        if ($search) {
            $leaveRequestsQuery->where(function ($query) use ($search) {
                $query->where('leave_requests.reason', 'like', '%' . $search . '%')
                    ->orWhere('leave_requests.id', 'like', '%' . $search . '%');
            });
        }

        $total = $leaveRequestsQuery->count();

        $leaveRequests = $leaveRequestsQuery->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        if ($leaveRequests->isEmpty()) {
            return formatApiResponse(
                false,
                'Leave requests not found',
                [
                    'total' => 0,
                    'data' => []
                ]
            );
        }

        $data = $leaveRequests->map(function ($leaveRequest) {
            return formatLeaveRequest($leaveRequest);
        });

        return formatApiResponse(
            false,
            'Leave requests retrieved successfully',
            [
                'total' => $total,
                'data' => $data
            ]
        );
    }



    public function get($id)
    {
        $lr = LeaveRequest::with('user')->findOrFail($id);
        $visibleTo = $lr->visibleToUsers;
        return response()->json(['lr' => $lr, 'visibleTo' => $visibleTo]);
    }


    /**
     * Update an existing leave request.
     *
     * This endpoint updates an existing leave request with the provided details. The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Leave Request Management
     *
     * @bodyParam id int required The ID of the leave request to be updated. Example: 1
     * @bodyParam reason string required The reason for the leave. Example: Family function
     * @bodyParam from_date date required The start date of the leave in the format specified in the general settings. Example: 2024-08-05
     * @bodyParam to_date date required The end date of the leave in the format specified in the general settings. Example: 2024-08-01
     * @bodyParam from_time time required_if:partialLeave,on The start time of the leave in HH:MM format. Example: 09:00
     * @bodyParam to_time time required_if:partialLeave,on The end time of the leave in HH:MM format. Example: 17:00
     * @bodyParam status string nullable The status of the leave request. Can be 'pending', 'approved', or 'rejected'. Example: pending
     * @bodyParam leaveVisibleToAll string optional Set to 'on' if the leave should be visible to all users in the workspace. Example: on
     * @bodyParam visible_to_ids array nullable The IDs of users who can see the leave if it is not visible to all. Example: [1, 2, 3]
     * @bodyParam partialLeave string optional Set to 'on' if the leave is partial (specific times within a day). Example: on
     * @bodyParam comment string optional An optional comment that can only be set by admin or leave editor. Example: Approved due to exceptional circumstances
     *
     * @response 200 {
     * "error": false,
     * "message": "Leave request updated successfully.",
     * "id": 187,
     * "type": "leave_request",
     * "data": {
     *   "id": 187,
     *   "user_name": "Madhavan Vaidya",
     *   "user_photo": "https://test-taskify.infinitietech.com/storage/photos/yxNYBlFLALdLomrL0JzUY2USPLILL9Ocr16j4n2o.png",
     *   "action_by": null,
     *   "action_by_id": null,
     *   "from_date": "Wed, 07-08-2024",
     *   "to_date": "Wed, 07-08-2024",
     *   "type": "Full",
     *   "duration": "1 day",
     *   "reason": "Test",
     *   "status": "Pending",
     *   "visible_to": null,
     *   "created_at": "07-08-2024 18:31:28",
     *   "updated_at": "07-08-2024 18:31:28"
     * }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "id": [
     *       "The id field is required.",
     *       "The selected id is invalid."
     *     ],
     *     "reason": [
     *       "The reason field is required."
     *     ],
     *     "from_date": [
     *       "The from date field is required."
     *     ],
     *     "to_date": [
     *       "The to date field is required."
     *     ],
     *     "from_time": [
     *       "The from time field is required when partial leave is checked."
     *     ],
     *     "to_time": [
     *       "The to time field is required when partial leave is checked."
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while updating the leave request."
     * }
     */

    public function update(Request $request)
    {
        $isApi = request()->get('isApi', false);
        $isAdminOrLe = is_admin_or_leave_editor();
        $calculationService = app(LeaveCalculationService::class);
        $validator = app(LeaveRequestValidator::class);
        $balanceEngine = app(LeaveBalanceEngine::class);

        $rules = [
            'id' => 'required|exists:leave_requests,id',
            'reason' => ['required'],
            'from_date' => ['required'],
            'to_date' => ['required'],
            'from_time' => ['required_if:partialLeave,on'],
            'to_time' => ['required_if:partialLeave,on'],
            'status' => $isAdminOrLe ? 'required|in:pending,approved,rejected' : 'nullable|in:pending,approved,rejected',
            'visible_to_ids.*' => 'exists:users,id',
            'comment' => ['nullable'],
            'is_paid' => ['nullable', 'boolean']
        ];
        $messages = [
            'from_time.required_if' => 'The from time field is required when partial leave is checked.',
            'to_time.required_if' => 'The to time field is required when partial leave is checked.',
        ];

        DB::beginTransaction();
        try {
            $validatedData = $request->validate($rules, $messages);

            // Find the leave request
            $leaveRequest = LeaveRequest::findOrFail($validatedData['id']);
            $currentStatus = $leaveRequest->status;
            $newStatus = $validatedData['status'] ?? $currentStatus;

            // Permission checks
            if (!is_null($leaveRequest->action_by) && !$this->user->hasRole('admin')) {
                DB::rollBack();
                return response()->json([
                    'error' => true,
                    'message' => 'Once actioned only admin can update leave request.',
                ], 403);
            }

            // [Validate Dates/Times] - Flow 1
            // Validate dates BEFORE conversion to catch format errors early
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');

            Log::info('[LeaveRequest Update] Validating dates', [
                'leave_id' => $validatedData['id'],
                'from_date_raw' => $from_date,
                'to_date_raw' => $to_date,
                'is_api' => $isApi,
                'php_date_format' => $isApi ? 'Y-m-d' : app('php_date_format'),
            ]);

            // Validate the request dates in their original format
            $dateValidation = $validator->validateDates(
                $from_date,
                $to_date,
                $request->input('from_time'),
                $request->input('to_time'),
                $isApi
            );

            if (!$dateValidation['valid']) {
                Log::warning('[LeaveRequest Update] Date validation failed', [
                    'leave_id' => $validatedData['id'],
                    'errors' => $dateValidation['errors'],
                ]);
                DB::rollBack();
                return formatApiValidationError($isApi, $dateValidation['errors']);
            }

            // Convert to database format AFTER validation passes
            $validatedData['from_date'] = format_date($from_date, false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
            $validatedData['to_date'] = format_date($to_date, false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');

            Log::info('[LeaveRequest Update] Dates converted to database format', [
                'leave_id' => $validatedData['id'],
                'from_date_converted' => $validatedData['from_date'],
                'to_date_converted' => $validatedData['to_date'],
            ]);

            if (!$dateValidation['valid']) {
                DB::rollBack();
                return formatApiValidationError($isApi, $dateValidation['errors']);
            }

            // [Validate Status Transition] - Flow 1
            $statusValidation = $validator->validateStatusTransition($currentStatus, $newStatus, $this->user->hasRole('admin'));
            if (!$statusValidation['valid']) {
                DB::rollBack();
                return response()->json([
                    'error' => true,
                    'message' => $statusValidation['error'],
                ], 422);
            }

            // [Calculate total_days] - Flow 1
            $totalDays = $calculationService->calculateLeaveDays(
                $validatedData['from_date'],
                $validatedData['to_date'],
                $request->input('from_time'),
                $request->input('to_time')
            );
            $validatedData['total_days'] = $totalDays;

            // [Validate Overlap] - Flow 1 (exclude current leave)
            $overlapCheck = $validator->validateLeaveOverlap(
                $leaveRequest->user_id,
                $leaveRequest->workspace_id,
                $validatedData['from_date'],
                $validatedData['to_date'],
                $request->input('from_time'),
                $request->input('to_time'),
                $leaveRequest->id
            );

            if ($overlapCheck['has_overlap']) {
                $validator->logOverlap($leaveRequest->id, $overlapCheck, 'warned', $this->user->id);
            }

            // [Check if Admin/Approved] - Flow 1
            $isApproved = $newStatus === 'approved' && $isAdminOrLe;

            // [Calculate Paid/Unpaid] - Flow 1 (only if approved)
            if ($isApproved) {
                // Check if admin wants to mark as paid
                $isPaidToggle = $request->has('is_paid') && $request->input('is_paid') == '1';

                // If editing approved leave and toggle not provided, use existing value
                if ($currentStatus === 'approved' && !$request->has('is_paid')) {
                    $isPaidToggle = $leaveRequest->is_paid ?? false;
                }

                if ($isPaidToggle) {
                    // [Check Balance] - Flow 1 (exclude current leave for edit scenario)
                    $balanceCheck = $validator->checkBalanceSufficiency(
                        $leaveRequest->user_id,
                        $leaveRequest->workspace_id,
                        $totalDays,
                        null,
                        $leaveRequest->id
                    );

                    // [Calculate Paid/Unpaid] based on balance
                    $paidUnpaidDays = $calculationService->calculatePaidUnpaidDays(
                        $leaveRequest->user_id,
                        $leaveRequest->workspace_id,
                        $totalDays,
                        null,
                        null
                    );

                    $validatedData['paid_days'] = $paidUnpaidDays['paid_days'];
                    $validatedData['unpaid_days'] = $paidUnpaidDays['unpaid_days'];
                    $validatedData['is_paid'] = $paidUnpaidDays['paid_days'] > 0;
                } else {
                    // Admin marked as unpaid
                    $validatedData['paid_days'] = 0;
                    $validatedData['unpaid_days'] = $totalDays;
                    $validatedData['is_paid'] = false;
                }
            } elseif ($newStatus === 'rejected' && $currentStatus === 'approved') {
                // Restore balance if changing from approved to rejected
                $balanceEngine->restoreBalance(
                    $leaveRequest->user_id,
                    $leaveRequest->workspace_id,
                    $leaveRequest
                );
            }

            // Set other fields
            if ($newStatus != $currentStatus) {
                $validatedData['action_by'] = $this->user->id;
            }
            $leaveVisibleToAll = $request->input('leaveVisibleToAll') && $request->filled('leaveVisibleToAll') && $request->input('leaveVisibleToAll') == 'on' ? 1 : 0;
            $validatedData['visible_to_all'] = $leaveVisibleToAll;
            $validatedData['comment'] = $isAdminOrLe && $request->filled('comment') ? $request->input('comment') : NULL;

            // [Update Leave Request] - Flow 1
            Log::info('[LeaveRequest Update] Updating leave request', [
                'leave_id' => $leaveRequest->id,
                'current_status' => $currentStatus,
                'new_status' => $newStatus,
                'is_paid' => $validatedData['is_paid'] ?? null,
                'paid_days' => $validatedData['paid_days'] ?? null,
                'unpaid_days' => $validatedData['unpaid_days'] ?? null,
            ]);

            $leaveRequest->update($validatedData);
            $leaveRequest = $leaveRequest->fresh();

            Log::info('[LeaveRequest Update] Leave request updated successfully', [
                'leave_id' => $leaveRequest->id,
                'status' => $leaveRequest->status,
                'is_paid' => $leaveRequest->is_paid,
                'paid_days' => $leaveRequest->paid_days,
                'unpaid_days' => $leaveRequest->unpaid_days,
            ]);

            // Sync visibility
            if ($leaveVisibleToAll == 0) {
                $visibleToUsers = $request->input('visible_to_ids', []);
                $leaveRequest->visibleToUsers()->sync($visibleToUsers);
            } else {
                $leaveRequest->visibleToUsers()->detach();
            }

            // [Update Balance] - Flow 1 (if approved)
            // NOTE: Balance update is handled by LeaveRequestApproved event listener
            // We do NOT update balance directly here to avoid double updates
            // The event listener (UpdateLeaveBalanceOnApproval) will handle the balance update
            if ($leaveRequest->status == 'approved' && $leaveRequest->is_paid && $leaveRequest->paid_days > 0) {
                Log::info('[LeaveRequest Update] Leave is approved and paid - balance will be updated via event', [
                    'leave_id' => $leaveRequest->id,
                    'user_id' => $leaveRequest->user_id,
                    'workspace_id' => $leaveRequest->workspace_id,
                    'paid_days' => $leaveRequest->paid_days,
                ]);
            }

            // [Send Notifications] - Flow 1
            if ($newStatus != $currentStatus) {
                Log::info('[LeaveRequest Update] Status changed - sending notifications and firing events', [
                    'leave_id' => $leaveRequest->id,
                    'old_status' => $currentStatus,
                    'new_status' => $newStatus,
                ]);

                $this->sendLeaveRequestNotifications($leaveRequest, 'updated', $currentStatus);

                // Fire events
                if ($newStatus === 'approved') {
                    Log::info('[LeaveRequest Update] Firing LeaveRequestApproved event', [
                        'leave_id' => $leaveRequest->id,
                        'user_id' => $leaveRequest->user_id,
                        'workspace_id' => $leaveRequest->workspace_id,
                        'previous_status' => $currentStatus,
                    ]);
                    event(new LeaveRequestApproved($leaveRequest, $currentStatus));
                    Log::info('[LeaveRequest Update] LeaveRequestApproved event fired', [
                        'leave_id' => $leaveRequest->id,
                    ]);
                } elseif ($newStatus === 'rejected' && $currentStatus === 'approved') {
                    Log::info('[LeaveRequest Update] Firing LeaveRequestRejected event', [
                        'leave_id' => $leaveRequest->id,
                        'previous_status' => $currentStatus,
                    ]);
                    event(new \App\Events\LeaveRequestRejected($leaveRequest, $currentStatus));
                }
            }

            DB::commit();

            return formatApiResponse(
                false,
                'Leave request updated successfully.',
                [
                    'id' => $leaveRequest->id,
                    'type' => 'leave_request',
                    'data' => formatLeaveRequest($leaveRequest)
                ]
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage(), $e->getTraceAsString(), $e->getLine(), $e->getFile(), $e->getCode(), $e->getPrevious());
            Log::error('[LeaveRequest Update] Exception occurred', [
                'leave_request_id' => $request->input('id'),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_code' => $e->getCode(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
                'previous_exception' => $e->getPrevious() ? [
                    'message' => $e->getPrevious()->getMessage(),
                    'file' => $e->getPrevious()->getFile(),
                    'line' => $e->getPrevious()->getLine(),
                ] : null,
            ]);
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while updating the leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update_editors(Request $request)
    {

        $userIds = $request->input('user_ids') ?? [];
        $currentLeaveEditorUserIds = LeaveEditor::pluck('user_id')->toArray();
        $usersToDetach = array_diff($currentLeaveEditorUserIds, $userIds);
        LeaveEditor::whereIn('user_id', $usersToDetach)->delete();
        foreach ($userIds as $assignedUserId) {
            // Check if a leave editor with the same user_id already exists
            $existingLeaveEditor = LeaveEditor::where('user_id', $assignedUserId)->first();

            if (!$existingLeaveEditor) {
                // Create a new LeaveEditor only if it doesn't exist
                $leaveEditor = new LeaveEditor();
                $leaveEditor->user_id = $assignedUserId;
                $leaveEditor->save();
            }
        }
        Session::flash('message', 'Leave editors updated successfully.');
        return response()->json(['error' => false]);
    }

    /**
     * Remove the specified leave request.
     *
     * This endpoint deletes a leave request item based on the provided ID. The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Leave Request Management
     *
     * @urlParam id int required The ID of the leave request to be deleted. Example: 1
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Leave request deleted successfully.",
     *   "id": 1,
     *   "type": "leave_request",
     *   "data": []
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Leave request not found.",
     *   "data": []
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while deleting the leave request."
     * }
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $leaveRequest = LeaveRequest::find($id);
            if (!$leaveRequest) {
                DB::rollBack();
                return formatApiResponse(
                    true,
                    'Leave request not found.',
                    []
                );
            }

            // Fire cancellation event before deletion
            if ($leaveRequest->status === 'approved') {
                event(new \App\Events\LeaveRequestCancelled($leaveRequest));
            }

            // Restore balance if the leave was approved and paid
            if ($leaveRequest->status === 'approved' && $leaveRequest->is_paid && $leaveRequest->paid_days > 0) {
                $balanceEngine = app(LeaveBalanceEngine::class);
                $balanceEngine->restoreBalance(
                    $leaveRequest->user_id,
                    $leaveRequest->workspace_id,
                    $leaveRequest
                );
            }

            // Delete notifications
            $leaveRequest->notificationsForLeaveRequest()->delete();

            // Delete leave request
            $response = DeletionService::delete(LeaveRequest::class, $id, 'Leave request');
            $responseData = json_decode($response->getContent(), true);

            if ($responseData['error']) {
                DB::rollBack();
                return response()->json($responseData);
            }

            DB::commit();

            return formatApiResponse(
                false,
                'Leave request deleted successfully.',
                [
                    'id' => $id,
                    'type' => 'leave_request',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Leave request deletion failed', [
                'leave_request_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while deleting the leave request.'
            ], 500);
        }
    }

    public function destroy_multiple(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:leave_requests,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);

        $ids = $validatedData['ids'];
        $deletedIds = [];
        $balanceEngine = app(LeaveBalanceEngine::class);

        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $LeaveRequest = LeaveRequest::find($id);
            if ($LeaveRequest) {
                // Fire cancellation event before deletion
                if ($LeaveRequest->status === 'approved') {
                    event(new \App\Events\LeaveRequestCancelled($LeaveRequest));
                }

                // Restore balance if the leave was approved and paid
                if ($LeaveRequest->status === 'approved' && $LeaveRequest->is_paid && $LeaveRequest->paid_days > 0) {
                    $balanceEngine->restoreBalance(
                        $LeaveRequest->user_id,
                        $LeaveRequest->workspace_id,
                        $LeaveRequest
                    );
                }

                $deletedIds[] = $id;
                $LeaveRequest->notificationsForLeaveRequest()->delete();
                DeletionService::delete(LeaveRequest::class, $id, 'Leave request');
            }
        }

        return response()->json(['error' => false, 'message' => 'Leave request(s) deleted successfully.', 'id' => $deletedIds, 'type' => 'leave_request']);
    }

    public function saveViewPreference(Request $request)
    {
        $view = $request->input('view');
        $prefix = isClient() ? 'c_' : 'u_';
        if (
            UserClientPreference::updateOrCreate(
                ['user_id' => $prefix . $this->user->id, 'table_name' => 'leave_requests'],
                ['default_view' => $view]
            )
        ) {
            return response()->json(['error' => false, 'message' => 'Default View Set Successfully.']);
        } else {
            return response()->json(['error' => true, 'message' => 'Something Went Wrong.']);
        }
    }
    public function calendar_view()
    {
        return view('leave_requests.calendar_view');
    }
    public function get_calendar_data(Request $request)
    {
        // dd($request->all());
        // Parse date range with proper timezone handling
        $start = $request->query('date_from')
            ? format_date($request->query('date_from'), false, app('php_date_format'), 'Y-m-d')
            : Carbon::now()->startOfMonth();

        $end = $request->query('date_to')
            ? format_date($request->query('date_to'), false, app('php_date_format'), 'Y-m-d')
            : Carbon::now()->endOfMonth();

        // Retrieve leave requests based on user access
        $leaveRequestsQuery = isAdminOrHasAllDataAccess()
            ? $this->workspace->leave_requests()
            : $this->user->leave_requests();

        // dd($start, $end, $leaveRequestsQuery->get());
        // Apply date range filter
        $leave_requests = $leaveRequestsQuery->where(function ($query) use ($start, $end) {


            $query->whereBetween('from_date', [$start, $end])
                ->orWhereBetween('to_date', [$start, $end]);
        })->get();


        // Format leave request for FullCalendar
        $events = $leave_requests->map(function ($leave_request) {
            switch ($leave_request->status) {
                case 'approved':
                    $backgroundColor = '#4caf50';
                    $borderColor = '#4caf50';
                    $textColor = '#ffffff';
                    break;
                case 'pending':
                    $backgroundColor = '#ffeb3b';
                    $borderColor = '#ffeb3b';
                    $textColor = '#000000';
                    break;
                case 'rejected':
                    $backgroundColor = '#f44336';
                    $borderColor = '#f44336';
                    $textColor = '#ffffff';
                    break;
                default:
            }


            return [
                'id' => $leave_request->id,
                'title' => ucwords($leave_request->user->first_name . ' ' . $leave_request->user->last_name) . ' (' . ucwords($leave_request->status) . ')',
                'start' => $leave_request->from_date,
                'end' => $leave_request->to_date,
                'from_time' => $leave_request->from_time,
                'end_time' => $leave_request->to_time,
                'backgroundColor' => $backgroundColor,
                'borderColor' => $borderColor,
                'textColor' => $textColor,
                'description' => "
            <strong>Reason:</strong> " . ucwords(Str::limit($leave_request->reason, 20, '....')) . "<br>
            <strong>Status:</strong> " . ucfirst($leave_request->status) . "<br>
           <strong>From:</strong> " . format_date($leave_request->from_date) . " at " . ($leave_request->from_time ? date('H:i', strtotime($leave_request->from_time)) : '00:00') . "<br>
<strong>To:</strong> " . format_date($leave_request->to_date) . " at " . ($leave_request->to_time ? date('H:i', strtotime($leave_request->to_time)) : '24:00'),
                'allDay' => false,
                'extendedProps' => [
                    'status' => $leave_request->status,
                ]
            ];
        });

        return response()->json($events);
    }

    /**
     * Get user leave balance
     * Optionally exclude a specific leave ID (useful when editing)
     */
    public function getUserLeaveBalance(Request $request)
    {
        $userId = $request->input('user_id', $this->user->id);
        $year = $request->input('year', get_current_company_year()); // Use company year
        $excludeLeaveId = $request->input('exclude_leave_id', null); // Exclude current leave when editing

        // Check if requesting user has permission to view this user's balance
        if ($userId != $this->user->id && !is_admin_or_leave_editor()) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $balanceEngine = app(LeaveBalanceEngine::class);
        $balanceSummary = $balanceEngine->getBalanceSummary($userId, $this->workspace->id, $year, $excludeLeaveId);

        return response()->json([
            'error' => false,
            'balance' => $balanceSummary
        ]);
    }
}
