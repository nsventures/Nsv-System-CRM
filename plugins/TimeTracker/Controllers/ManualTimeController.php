<?php

namespace Plugins\TimeTracker\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Plugins\TimeTracker\Models\TimeTrackerActivityLog;
use Plugins\TimeTracker\Models\TimeTrackerConfig;

class ManualTimeController extends Controller
{
    public function index()
    {
        $employees = User::select('id', 'first_name', 'last_name')->orderBy('first_name')->get();
        if (! isAdminOrHasAllDataAccess() && ! $this->isManualTimeApprover()) {
            $employees = $employees->filter(function ($employee) {
                return $employee->id === getAuthenticatedUser()->id;
            });
        }
        $canApprove = $this->isManualTimeApprover() || isAdminOrHasAllDataAccess();
        return view('timetracker::manual_time.index', compact('employees', 'canApprove'));
    }

    public function data(Request $request)
    {
        $query = TimeTrackerActivityLog::with('user')
            ->whereIn('action', [
                'manual-start',
                'manual-stop',
                'manual-processing-start',
                'manual-processing-stop',
            ]);

        if (! isAdminOrHasAllDataAccess() && ! $this->isManualTimeApprover()) {
            $query->where('user_id', getAuthenticatedUser()->id);
        }

        if ($request->filled('user_id')) {
            $query->whereIn('user_id', (array) $request->user_id);
        }

        if ($request->filled(['start_date', 'end_date'])) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('timestamp', [$startDate, $endDate]);
        }

        // Handle search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Handle sorting
        $sort = $request->input('sort', 'timestamp');
        $order = $request->input('order', 'asc');

        // Only allow safe columns
        $allowedSorts = [
            'employee_name',
            'date',
            'start_time',
            'end_time',
            'duration',
            'reason',
            'status',
            'approved_at',
            'approver',
            'timestamp',
        ];

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'timestamp';
        }

        // Since most sort fields are virtual, we sort by timestamp for consistency
        // $query->orderBy('timestamp', $order);

        // Fetch all for pairing logic
        $logs = $query->get();
        $grouped = $logs->groupBy(function ($item) {
            return $item->user_id . '-' . Carbon::parse($item->timestamp)->toDateString();
        });

        $data = [];

        // Get timezone from general settings for display
        $general_settings = get_settings('general_settings');
        $userTimezone = $general_settings['timezone'] ?? 'UTC';

        foreach ($grouped as $items) {
            $user = $items->first()->user;
            $date = Carbon::parse($items->first()->timestamp)->toDateString();

            $pendingStart = null;

            foreach ($items as $item) {
                if (in_array($item->action, ['manual-start', 'manual-processing-start'])) {
                    $pendingStart = $item;
                } elseif (in_array($item->action, ['manual-stop', 'manual-processing-stop']) && $pendingStart) {
                    // Timestamps are stored in UTC, convert to user timezone for display
                    $startTime = Carbon::parse($pendingStart->timestamp)->setTimezone($userTimezone);
                    $endTime = Carbon::parse($item->timestamp)->setTimezone($userTimezone);

                    if ($endTime->lessThan($startTime)) {
                        [$startTime, $endTime] = [$endTime, $startTime];
                    }

                    $durationInMinutes = $startTime->diffInMinutes($endTime);
                    $durationFormatted = sprintf('%02d:%02d', intdiv($durationInMinutes, 60), $durationInMinutes % 60);

                    $metadata = $this->decodeMetadata($item->metadata);

                    // Reason lives in the reason column (tracker/form); fall back to legacy
                    // metadata. It may be attached to the start entry rather than the stop.
                    $startMetadata = $this->decodeMetadata($pendingStart->metadata);
                    $reason = $item->reason
                        ?? $pendingStart->reason
                        ?? $metadata['reason']
                        ?? ($startMetadata['reason'] ?? 'N/A');

                    $approvalStatus = $metadata['approval_status'] ?? 'Pending';
                    $approvedAt = $metadata['approved_at'] ?? ($metadata['rejected_at'] ?? null);
                    $approverId = $metadata['approved_by'] ?? ($metadata['rejected_by'] ?? null);

                    $approverName = 'N/A';
                    if ($approverId) {
                        $approverUser = \App\Models\User::find($approverId);
                        if ($approverUser) {
                            $approverName = ucwords($approverUser->first_name . ' ' . $approverUser->last_name);
                        }
                    }

                    $actionsHtml = '-';
                    if ($approvalStatus === 'Pending' && ($this->isManualTimeApprover() || isAdminOrHasAllDataAccess())) {
                        $actionsHtml = '<button class="btn btn-sm btn-primary approve-manual-time" data-id="' . $pendingStart->id . '">
                        <i class="bx bx-check-double"></i> Approve
                        </button>';
                    }

                    // Convert approved_at from UTC to user timezone for display
                    $approvedAtDisplay = 'N/A';
                    if ($approvedAt) {
                        $approvedAtDisplay = Carbon::parse($approvedAt)->setTimezone($userTimezone)->format('d M Y h:i A');
                    }

                    $data[] = [
                        'id' => $pendingStart->id,
                        'employee_name' => ucwords($user->first_name . ' ' . $user->last_name),
                        'date' => format_date($date),
                        'start_time' => $startTime->format('h:i A'),
                        'end_time' => $endTime->format('h:i A'),
                        'duration' => $durationFormatted,
                        'status' => ucfirst($approvalStatus),
                        'approved_at' => $approvedAtDisplay,
                        'approver' => $approverName,
                        'reason' => $reason,
                        'remarks' => $metadata['remarks'] ?? 'N/A',
                        'actions' => $actionsHtml,
                    ];

                    $pendingStart = null;
                }
            }
        }

        // Apply frontend sorting if sorting on virtual fields
        if ($sort !== 'timestamp') {
            usort($data, function ($a, $b) use ($sort, $order) {
                return $order === 'asc'
                    ? strcmp($a[$sort], $b[$sort])
                    : strcmp($b[$sort], $a[$sort]);
            });
        }

        // Pagination
        $limit = (int) $request->input('limit', 10);
        $offset = (int) $request->input('offset', 0);
        $pagedData = array_slice($data, $offset, $limit);

        return response()->json([
            'total' => count($data),
            'rows' => $pagedData,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'reason' => 'required|string|max:255 ',
        ]);
        if (isAdminOrHasAllDataAccess() || $this->isManualTimeApprover()) {
            $user_id = $request->user_id;
        } else {
            $user_id = getAuthenticatedUser()->id;
        }

        // Get timezone from general settings
        $general_settings = get_settings('general_settings');
        $userTimezone = $general_settings['timezone'] ?? 'UTC';

        // Parse times in user's timezone and convert to UTC for storage (consistent with regular logs)
        $startDateTime = Carbon::parse($request->date . ' ' . $request->start_time, $userTimezone)->setTimezone('UTC');
        $endDateTime = Carbon::parse($request->date . ' ' . $request->end_time, $userTimezone)->setTimezone('UTC');

        // Insert manual_processing_start (stored in UTC). Reason is a first-class column now.
        TimeTrackerActivityLog::create([
            'user_id' => $user_id,
            'action' => 'manual-processing-start',
            'reason' => $request->reason,
            'timestamp' => $startDateTime,
        ]);

        // Insert manual_processing_stop (stored in UTC).
        TimeTrackerActivityLog::create([
            'user_id' => $user_id,
            'action' => 'manual-processing-stop',
            'reason' => $request->reason,
            'timestamp' => $endDateTime,
        ]);

        return response()->json(['message' => 'Manual time added successfully.']);
    }
    public function approve(Request $request)
    {
        if (! isAdminOrHasAllDataAccess() && ! $this->isManualTimeApprover()) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized Action. You are not authorized to perform this action. ',
            ], 403);
        }

        $request->validate([
            'manual_time_id' => 'required|exists:time_tracker_activity_logs,id',
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string|max:250 ',
        ]);

        $manualStart = TimeTrackerActivityLog::findOrFail($request->manual_time_id);

        $manualStop = TimeTrackerActivityLog::where('user_id', $manualStart->user_id)
            ->where('action', 'manual-processing-stop')
            ->where('timestamp', '>', $manualStart->timestamp)
            ->orderBy('timestamp')
            ->first();

        if (! $manualStop) {
            return response()->json([
                'error' => true,
                'message' => 'Matching stop entry not found. ',
            ], 404);
        }

        DB::transaction(function () use ($manualStart, $manualStop, $request) {
            $userId = getAuthenticatedUser()->id;
            $now = now();

            if ($request->status === 'approved') {
                $approvedMetadata = [
                    'approval_status' => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => $now,
                ];

                $manualStart->update([
                    'action' => 'manual-start',
                    'metadata' => array_merge(
                        $this->decodeMetadata($manualStart->metadata),
                        $approvedMetadata
                    ),
                ]);

                $manualStop->update([
                    'action' => 'manual-stop',
                    'metadata' => array_merge(
                        $this->decodeMetadata($manualStop->metadata),
                        $approvedMetadata
                    ),
                ]);
            } else {
                $remarks = $request->remarks ?? null;
                $rejectedMetadata = [
                    'approval_status' => 'rejected',
                    'remarks' => $remarks,
                    'rejected_by' => $userId,
                    'rejected_at' => $now,
                ];

                $manualStart->update([
                    'metadata' => array_merge(
                        $this->decodeMetadata($manualStart->metadata),
                        $rejectedMetadata
                    ),
                ]);

                $manualStop->update([
                    'metadata' => array_merge(
                        $this->decodeMetadata($manualStop->metadata),
                        $rejectedMetadata
                    ),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $request->status === 'approved'
                ? 'Manual time approved successfully.'
                : 'Manual time rejected successfully. ',
        ]);
    }

    public function fetch(Request $request)
    {
        // Find the pending manual-processing-start and manual-processing-stop logs for the given ID
        $startLog = TimeTrackerActivityLog::where('id', $request->id)
            ->where('action', 'manual-processing-start')
            ->first();

        if (! $startLog) {
            return response()->json(['error' => 'Manual time entry not found.'], 404);
        }

        $stopLog = TimeTrackerActivityLog::where('user_id', $startLog->user_id)
            ->where('action', 'manual-processing-stop')
            ->whereDate('timestamp', Carbon::parse($startLog->timestamp)->toDateString())
            ->orderBy('timestamp', 'desc')
            ->first();

        $user = $startLog->user;
        $reason = $startLog->reason ?? $this->decodeMetadata($startLog->metadata)['reason'] ?? '';

        // Get timezone from general settings for display
        $general_settings = get_settings('general_settings');
        $userTimezone = $general_settings['timezone'] ?? 'UTC';

        // Convert timestamps from UTC to user timezone for display
        $startTimeDisplay = Carbon::parse($startLog->timestamp)->setTimezone($userTimezone);
        $endTimeDisplay = $stopLog ? Carbon::parse($stopLog->timestamp)->setTimezone($userTimezone) : null;

        return response()->json([
            'id' => $startLog->id,
            'employee_name' => ucwords($user->first_name . ' ' . $user->last_name),
            'date' => $startTimeDisplay->toDateString(),
            'start_time' => $startTimeDisplay->format('H:i'),
            'end_time' => $endTimeDisplay ? $endTimeDisplay->format('H:i') : '',
            'reason' => $reason,
        ]);
    }

    /**
     * Normalize an activity log's metadata to an array.
     *
     * The `metadata` column is JSON-cast (returns an array), but older/form entries
     * may have been stored as a JSON string. Handle both safely.
     */
    private function decodeMetadata($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function isManualTimeApprover()
    {
        // Check if the user has the role or permission to approve manual time entries
        $configData = TimeTrackerConfig::where('name', 'time_tracker_config')->value('value');

        $manualApprovers = $configData['manualTimeApprover'] ?? [];
        if (is_array($manualApprovers) && in_array(getAuthenticatedUser()->id, $manualApprovers)) {
            return true;
        }
        return false;
    }
}
