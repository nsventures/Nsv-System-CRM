<?php

namespace Plugins\TimeTracker\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Plugins\TimeTracker\Models\TimeTrackerActivityLog;
use Plugins\TimeTracker\Models\TimeTrackerConfig;

class TimeAndAttendanceController extends Controller
{
    public function index()
    {
        $workDayStartTime = $this->loadConfig()['workDayStartTime'] ?? '09:00:00'; // Default to 9 AM if not set
        $workDayStartTime = Carbon::parse($workDayStartTime)->format('H:i:s');
        return view('timetracker::time_and_attendance.index', compact('workDayStartTime'));
    }

    public function timeAndAttendanceData(Request $request)
    {
        $user = getAuthenticatedUser();
        $user_ids = $request->get('user_id', null);

        // Get timezone dynamically from general settings
        $general_settings = get_settings('general_settings');
        $timezone = $general_settings['timezone'] ?? 'UTC';

        $currentDate = now()->setTimezone($timezone);
        $startDate = $request->get('start_date', $currentDate->copy()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', $currentDate->format('Y-m-d'));

        // Parse the date in the user's timezone and convert range boundaries to UTC for DB query
        $startDateTime = Carbon::parse($startDate, $timezone)->startOfDay()->setTimezone('UTC');
        $endDateTime = Carbon::parse($endDate, $timezone)->endOfDay()->setTimezone('UTC');

        // Debugging logs
        \Illuminate\Support\Facades\Log::info('TimeAndAttendance fetch range:', [
            'startDate_req' => $startDate,
            'endDate_req' => $endDate,
            'timezone' => $timezone,
            'startDT_UTC' => $startDateTime->toDateTimeString(),
            'endDT_UTC' => $endDateTime->toDateTimeString(),
            'user_ids' => $user_ids
        ]);

        $query = TimeTrackerActivityLog::between($startDateTime, $endDateTime)
            ->orderBy('user_id')
            ->orderBy('timestamp');

        if ($user_ids) {
            $query->where('user_id', $user_ids);
        }
        if (! isAdminOrHasAllDataAccess()) {
            $query->forUser($user->id);
        }

        $logs = $query->get();

        // Detailed Debug Logging
        if ($logs->isNotEmpty()) {
            $debugUserLogs = $logs->where('user_id', 19); // Target specific user from issue
            if ($debugUserLogs->isNotEmpty()) {
                \Illuminate\Support\Facades\Log::info('TimeAndAttendance actions for User 19:',
                    $debugUserLogs->map(function($l) {
                        return [
                            'id' => $l->id,
                            'action' => $l->action,
                            'timestamp' => $l->timestamp->toDateTimeString(),
                            'ts_tz' => $l->timestamp->timezone
                        ];
                    })->toArray()
                );
            }
        }

        $grouped = [];

        foreach ($logs as $log) {
            $userId = $log->user_id;
            // Group by date relative to User Timezone, NOT UTC
            $date = Carbon::parse($log->timestamp)->setTimezone($timezone)->format('Y-m-d');
            $grouped[$userId][$date][] = $log;
        }

        $attendanceData = [];

        foreach ($grouped as $userId => $dates) {
            $userModel = \App\Models\User::find($userId);
            if (! $userModel) {
                continue;
            }

            foreach ($dates as $date => $logs) {
                $attendanceData[] = $this->processDay($userModel, $date, $logs);
            }
        }

        usort(
            $attendanceData,
            fn ($a, $b) => $a['employee'] === $b['employee']
                ? strcmp($a['date'], $b['date'])
                : strcmp($a['employee'], $b['employee'])
        );

        
        $page = max(1, (int) $request->get('page', 1));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;
        $total = count($attendanceData);
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        $page = min($page, max($totalPages, 1));
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($attendanceData, $offset, $perPage);

        // Debug: Find User 19 data
        $debugUser19 = collect($attendanceData)->firstWhere('user_id', 19);

        return response()->json([
            'data' => $paginatedData,
            'summary' => $this->calculateSummary($attendanceData),
            'chart_data' => $attendanceData,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $total,
                'total_pages' => max($totalPages, 1),
            ],
            // 'debug_user_19' => $debugUser19, // Ensure this is also gone if it was there, but strictly removing debug_manual_counts
        ]);
    }

    public function getUsers(Request $request)
    {
        $users = User::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        // If user is not admin or doesn't have all data access, only show their own user
        if (! isAdminOrHasAllDataAccess()) {
            $authenticatedUser = getAuthenticatedUser();
            $users = $users->filter(function ($user) use ($authenticatedUser) {
                return $user->id === $authenticatedUser->id;
            });
        }

        $userList = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => trim($user->first_name . ' ' . $user->last_name),
            ];
        })->values();

        return response()->json([
            'users' => $userList,
        ]);
    }

    public function timeline(Request $request)
    {
        $userId = $request->get('user_id');
        $dateInput = $request->get('date');

        // Try to parse the date - handle multiple formats
        try {
            $date = Carbon::parse($dateInput)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid date format',
                'date_received' => $dateInput
            ], 400);
        }

        // Get timezone dynamically from general settings
        $general_settings = get_settings('general_settings');
        $timezone = $general_settings['timezone'] ?? 'UTC';

        // Parse the date in the user's timezone and convert range boundaries to UTC for DB query
        $startOfDay = Carbon::parse($date, $timezone)->startOfDay()->setTimezone('UTC');
        $endOfDay = Carbon::parse($date, $timezone)->endOfDay()->setTimezone('UTC');

        $logs = TimeTrackerActivityLog::where('user_id', $userId)
            ->whereBetween('timestamp', [$startOfDay, $endOfDay])
            ->orderBy('timestamp')
            ->get();


        $shifts = $this->buildTimelineShifts($logs, $timezone);

        $sessions = collect($shifts)
            ->flatMap(function ($shift) {
                return collect($shift['segments'] ?? [])->map(function ($segment) {
                    return [
                        'start' => $segment['start'],
                        'end' => $segment['end'],
                        'type' => $segment['type'],
                    ];
                });
            })
            ->values()
            ->all();

        return response()->json([
            'sessions' => $sessions, // Backward compatibility
            'shifts' => $shifts,
            'timezone' => $timezone
        ]);
    }

    private function processDay($user, $date, $logs)
    {
        $logsCollection = collect($logs)->sortBy('timestamp')->values();

        $general_settings = get_settings('general_settings');
        $timezone = $general_settings['timezone'] ?? 'UTC';

        $shifts = $this->buildTimelineShifts($logsCollection, $timezone);

        $clockInDisplay = '--';
        $clockOutDisplay = '--';
        $lastCompletedClockOut = null;
        $totalWorkSeconds = 0;
        $totalActiveSeconds = 0;
        $totalManualSeconds = 0;
        $totalPendingManualSeconds = 0;
        $totalBreakSeconds = 0;
        $totalIdleSeconds = 0;
        $hasOngoingShift = false;

        if (! empty($shifts)) {
            $firstShift = $shifts[0];
            $clockInDisplay = $firstShift['clock_in_display'] ?? '--';

            foreach ($shifts as $shift) {
                $totalWorkSeconds += $shift['duration_seconds'] ?? 0;

                $summary = $shift['summary'] ?? [];
                $totalActiveSeconds += $summary['active_seconds'] ?? 0;
                $totalManualSeconds += $summary['manual_seconds'] ?? 0;
                $totalPendingManualSeconds += $summary['pending_manual_seconds'] ?? 0;
                $totalBreakSeconds += $summary['break_seconds'] ?? 0;
                $totalIdleSeconds += $summary['idle_seconds'] ?? 0;

                $clockOutCandidate = $shift['clock_out_display'] ?? null;
                if (($shift['status'] ?? '') === 'completed' && $clockOutCandidate && $clockOutCandidate !== '--') {
                    $lastCompletedClockOut = $clockOutCandidate;
                }

                if (($shift['status'] ?? '') === 'ongoing') {
                    $hasOngoingShift = true;
                }
            }
        }

        if ($hasOngoingShift) {
            $clockOutDisplay = '--';
        } elseif ($lastCompletedClockOut) {
            $clockOutDisplay = $lastCompletedClockOut;
        }

        $aggregatedSegments = $totalActiveSeconds + $totalManualSeconds + $totalPendingManualSeconds + $totalBreakSeconds + $totalIdleSeconds;

        if ($aggregatedSegments > 0 && $totalWorkSeconds === 0) {
            $totalWorkSeconds = $aggregatedSegments;
        }

        $workTime = $this->formatTime($totalWorkSeconds);
        $activeTime = $this->formatTime($totalActiveSeconds);
        $manualTime = $this->formatTime($totalManualSeconds);
        $pendingManualTime = $this->formatTime($totalPendingManualSeconds);
        $breakTime = $this->formatTime($totalBreakSeconds);
        $idleTime = $this->formatTime($totalIdleSeconds);

        $productiveSeconds = $totalActiveSeconds + $totalManualSeconds;
        $utilization = $totalWorkSeconds > 0 ? round($productiveSeconds / $totalWorkSeconds * 100, 1) : 0;

        $status = $hasOngoingShift ? 'Active' : ($clockInDisplay === '--' ? 'Absent' : 'Completed');

        $result = [
            'employee' => trim($user->first_name . ' ' . $user->last_name),
            'user_id' => $user->id,
            'date' => $date, // Use Y-m-d format for valid HTML IDs and consistent date handling
            'date_formatted' => format_date($date), // Keep formatted date for display if needed
            'clock_in' => $clockInDisplay,
            'clock_out' => $clockOutDisplay,
            'work_time' => $workTime,
            'active_time' => $activeTime,
            'manual_time' => $manualTime,
            'pending_manual_time' => $pendingManualTime,
            'break_time' => $breakTime,
            'idle_time' => $idleTime,
            'utilization' => $utilization . '%',
            'status' => $status,
            'has_ongoing_shift' => $hasOngoingShift,
        ];

        return $result;
    }

    private function formatTime($seconds)
    {
        // Ensure we're working with positive seconds
        $seconds = max(0, $seconds);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    private function calculateSummary($data)
    {
        $totalWork = 0;
        $totalBreak = 0;
        $totalIdle = 0;
        $totalUtil = 0;
        $countUtil = 0;
        $totalPendingManual = 0; // <-- Add this
        $users = [];

        foreach ($data as $row) {
            $users[$row['user_id']] = true;

            $totalWork += $this->timeToSeconds($row['work_time']);
            $totalBreak += $this->timeToSeconds($row['break_time']);
            $totalIdle += $this->timeToSeconds($row['idle_time']);
            $totalPendingManual += $this->timeToSeconds($row['pending_manual_time']); // <-- Add this

            $util = floatval(str_replace('%', '', $row['utilization']));
            if ($util > 0) {
                $totalUtil += $util;
                $countUtil++;
            }
        }

        $avgUtil = $countUtil ? round($totalUtil / $countUtil, 1) . '%' : '0%';

        return [
            'total_employees' => count($users),
            'total_records' => count($data),
            'total_work_hours' => $this->formatTime($totalWork),
            'total_break_time' => $this->formatTime($totalBreak),
            'total_idle_time' => $this->formatTime($totalIdle),
            'total_pending_manual_time' => $this->formatTime($totalPendingManual), // <-- Add this
            'average_utilization' => $avgUtil,
        ];
    }

    private function timeToSeconds($time)
    {
        if (strpos($time, ':') === false) {
            return 0;
        }

        $parts = explode(':', $time);
        if (count($parts) !== 2) {
            return 0;
        }

        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];

        return max(0, ($hours * 3600) + ($minutes * 60));
    }

    private function loadConfig()
    {
        // Default values in milliseconds
        $defaultConfig = [
            'screenshotInterval' => 60000,        // 1 minute
            'idleTimeThreshold' => 300000,        // 5 minutes
            'breakTimeThreshold' => 600000,       // 10 minutes
            'maxDailyBreakTime' => 3600000,       // 1 hour
            'manualTimeApprover' => [],
            'workDayStartTime' => '09:00',        // Default work day start
        ];
        // Get saved config from DB
        $config = TimeTrackerConfig::where('name', 'time_tracker_config')->value('value');
        // Decode JSON if available
        $decoded = is_array($config) ? $config : json_decode($config, true);
        // Merge decoded config with defaults
        $time_tracker_config = array_merge($defaultConfig, $decoded ?? []);
        // Ensure workDayStartTime is in correct format
        if (isset($time_tracker_config['workDayStartTime'])) {
            $time_tracker_config['workDayStartTime'] = Carbon::parse($time_tracker_config['workDayStartTime'])->format('H:i:s');
        } else {
            $time_tracker_config['workDayStartTime'] = '09:00:00'; // Default to 9 AM if not set
        }
        return $time_tracker_config;
    }

    private function buildTimelineShifts($logs, string $timezone): array
    {
        if (! $logs instanceof \Illuminate\Support\Collection) {
            $logs = collect($logs);
        }

        if ($logs->isEmpty()) {
            return [];
        }

        $sortedLogs = $logs->sortBy(function ($log) {
            return $log->timestamp instanceof Carbon ? $log->timestamp : Carbon::parse($log->timestamp);
        })->values();

        $shifts = [];
        $currentShift = null;
        $shiftIndex = 1;

        // Track states that should persist across shifts (bridging)
        $ongoingStates = [
            'manual' => null,
            'pending_manual' => null
        ];

        foreach ($sortedLogs as $log) {
            $timestamp = $log->timestamp instanceof Carbon
                ? $log->timestamp->copy()
                : Carbon::parse($log->timestamp);

            switch ($log->action) {
                case 'clock-in':
                    if ($currentShift) {
                        // Close previous shift
                        $this->closeAllOpenStates($currentShift, $timestamp);
                        $currentShift['clock_out'] = $timestamp;
                        $currentShift['effective_end'] = $timestamp;
                        $currentShift['status'] = 'incomplete';
                        $shifts[] = $this->formatTimelineShift($currentShift, $timezone, $shiftIndex++);

                        // Handle bridging for incomplete shifts too
                        foreach ($ongoingStates as $stateKey => $startVal) {
                            if ($startVal) {
                                // If we were manual, we update the bridge start to now (start of new shift covers from here)
                                // Actually, if we closed the state effectively above, we reset the bridge time to now.
                                $ongoingStates[$stateKey] = $timestamp;
                            }
                        }
                    }

                    $currentShift = $this->initializeTimelineShift($timestamp);

                    // Restore ongoing manual states into the new shift
                    foreach ($ongoingStates as $stateKey => $startVal) {
                        if ($startVal) {
                            $currentShift['open_states'][$stateKey] = $startVal;
                        }
                    }
                    break;

                case 'clock-out':
                    if (! $currentShift) {
                        break;
                    }
                    $this->closeAllOpenStates($currentShift, $timestamp);
                    $currentShift['clock_out'] = $timestamp;
                    $currentShift['effective_end'] = $timestamp;
                    $currentShift['status'] = 'completed';
                    $shifts[] = $this->formatTimelineShift($currentShift, $timezone, $shiftIndex++);

                    // Update ongoing states to start from this clock-out time (The Bridge)
                    foreach ($ongoingStates as $stateKey => $startVal) {
                        if ($startVal) {
                             $ongoingStates[$stateKey] = $timestamp;
                        }
                    }

                    $currentShift = null;
                    break;

                case 'manual-start':
                    $ongoingStates['manual'] = $timestamp; // Start tracking

                    if (! $currentShift) {
                        $currentShift = $this->initializeTimelineShift($timestamp);
                        $currentShift['is_manual_only'] = true;
                        $currentShift['open_states']['active'] = null;
                    } else {
                        $this->closeAllOpenStates($currentShift, $timestamp);
                    }
                    $currentShift['open_states']['manual'] = $timestamp;
                    break;

                case 'manual-stop':
                    $ongoingStates['manual'] = null; // Stop tracking

                    if (! $currentShift) {
                         // Handle Orphaned Stop (Carry-over from previous day)
                         $startOfDay = $timestamp->copy()->startOfDay();
                         // Only process if the stop is actually after the start of day (sanity check)
                         if ($timestamp->gt($startOfDay)) {
                             $driftShift = $this->initializeTimelineShift($startOfDay);
                             $driftShift['is_manual_only'] = true;
                             $driftShift['open_states']['manual'] = $startOfDay;

                             $this->closeState($driftShift, 'manual', $timestamp);

                             $driftShift['clock_out'] = $timestamp;
                             $driftShift['effective_end'] = $timestamp;
                             $driftShift['status'] = 'completed';
                             $shifts[] = $this->formatTimelineShift($driftShift, $timezone, $shiftIndex++);
                         }
                        break;
                    }

                    $this->closeState($currentShift, 'manual', $timestamp);

                    if (isset($currentShift['is_manual_only']) && $currentShift['is_manual_only']) {
                        $currentShift['clock_out'] = $timestamp;
                        $currentShift['effective_end'] = $timestamp;
                        $currentShift['status'] = 'completed';
                        $shifts[] = $this->formatTimelineShift($currentShift, $timezone, $shiftIndex++);
                        $currentShift = null;
                    } else {
                        $currentShift['open_states']['active'] = $timestamp;
                    }
                    break;

                case 'manual-processing-start':
                    $ongoingStates['pending_manual'] = $timestamp;
                    if (! $currentShift) {
                        $currentShift = $this->initializeTimelineShift($timestamp);
                        $currentShift['is_manual_only'] = true;
                        $currentShift['open_states']['active'] = null;
                    } else {
                        $this->closeAllOpenStates($currentShift, $timestamp);
                    }
                    $currentShift['open_states']['pending_manual'] = $timestamp;
                    break;

                case 'manual-processing-stop':
                    $ongoingStates['pending_manual'] = null;
                    if (! $currentShift) {
                         // Handle Orphaned Stop (Carry-over from previous day)
                         $startOfDay = $timestamp->copy()->startOfDay();
                         if ($timestamp->gt($startOfDay)) {
                             $driftShift = $this->initializeTimelineShift($startOfDay);
                             $driftShift['is_manual_only'] = true;
                             $driftShift['open_states']['pending_manual'] = $startOfDay;

                             $this->closeState($driftShift, 'pending_manual', $timestamp);

                             $driftShift['clock_out'] = $timestamp;
                             $driftShift['effective_end'] = $timestamp;
                             $driftShift['status'] = 'completed';
                             $shifts[] = $this->formatTimelineShift($driftShift, $timezone, $shiftIndex++);
                         }
                        break;
                    }
                    $this->closeState($currentShift, 'pending_manual', $timestamp);
                    if (isset($currentShift['is_manual_only']) && $currentShift['is_manual_only']) {
                        $currentShift['clock_out'] = $timestamp;
                        $currentShift['effective_end'] = $timestamp;
                        $currentShift['status'] = 'completed';
                        $shifts[] = $this->formatTimelineShift($currentShift, $timezone, $shiftIndex++);
                        $currentShift = null;
                    } else {
                        $currentShift['open_states']['active'] = $timestamp;
                    }
                    break;

                // ... (Break/Idle logic remains mostly same but should respect manual priority)
                case 'break-start':
                    if (!$currentShift) break;
                    // Ignore break if manual is forcefully open (bridged or local)
                    if (($currentShift['open_states']['manual'] ?? null) || ($currentShift['open_states']['pending_manual'] ?? null)) break;
                    $this->closeAllOpenStates($currentShift, $timestamp);
                    $currentShift['open_states']['break'] = $timestamp;
                    break;

                case 'break-stop':
                    if (!$currentShift) break;
                    // Ignore if manual is open
                    if (($currentShift['open_states']['manual'] ?? null) || ($currentShift['open_states']['pending_manual'] ?? null)) break;
                    $this->closeState($currentShift, 'break', $timestamp);
                    $currentShift['open_states']['active'] = $timestamp;
                    break;

                case 'idle-start':
                    if (!$currentShift) break;
                    if (($currentShift['open_states']['manual'] ?? null) || ($currentShift['open_states']['pending_manual'] ?? null)) break;
                    $this->closeAllOpenStates($currentShift, $timestamp);
                    $currentShift['open_states']['idle'] = $timestamp;
                    break;

                case 'idle-stop':
                    if (!$currentShift) break;
                    if (($currentShift['open_states']['manual'] ?? null) || ($currentShift['open_states']['pending_manual'] ?? null)) break;
                    $this->closeState($currentShift, 'idle', $timestamp);
                    $currentShift['open_states']['active'] = $timestamp;
                    break;
            }
        }

        if ($currentShift) {
            $shiftStart = $currentShift['clock_in'] ? $currentShift['clock_in']->copy() : null;
            $now = Carbon::now($timezone);

            // Determine the cut-off time.
            // If the shift belongs to a past day, capping it at End of Day.
            // If it matches Today (in user timezone), cap at Now.

            $cutoff = $now;
            $status = 'ongoing';

            if ($shiftStart && !$shiftStart->isSameDay($now)) {
                 $cutoff = $shiftStart->endOfDay();
                 $status = 'completed'; // Auto-complete past shifts
            }

            $this->closeAllOpenStates($currentShift, $cutoff);
            $currentShift['clock_out'] = ($status === 'completed') ? $cutoff : null;
            $currentShift['effective_end'] = $cutoff;
            $currentShift['status'] = $status;
            $shifts[] = $this->formatTimelineShift($currentShift, $timezone, $shiftIndex++);
        }

        return $shifts;
    }

    private function initializeTimelineShift(Carbon $clockIn): array
    {
        return [
            'clock_in' => $clockIn,
            'clock_out' => null,
            'effective_end' => null,
            'status' => 'ongoing',
            'segments' => [],
            'durations' => [
                'active' => 0,
                'manual' => 0,
                'pending_manual' => 0,
                'break' => 0,
                'idle' => 0,
            ],
            'open_states' => [
                'active' => $clockIn,
                'manual' => null,
                'pending_manual' => null,
                'break' => null,
                'idle' => null,
            ],
        ];
    }

    private function closeAllOpenStates(array &$shift, Carbon $timestamp): void
    {
        foreach ($shift['open_states'] as $state => $start) {
            if ($start instanceof Carbon) {
                $this->closeState($shift, $state, $timestamp);
            }
        }
    }

    private function closeState(array &$shift, string $state, Carbon $endTime): void
    {
        if (! isset($shift['open_states'][$state]) || ! $shift['open_states'][$state] instanceof Carbon) {
            return;
        }

        $start = $shift['open_states'][$state];
        if ($endTime->lessThan($start)) {
            $shift['open_states'][$state] = null;
            return;
        }

        $duration = $start->diffInSeconds($endTime);
        $shift['durations'][$state] += $duration;

        $shift['segments'][] = [
            'type' => $state,
            'start' => $start->copy(),
            'end' => $endTime->copy(),
            'duration' => $duration,
        ];

        $shift['open_states'][$state] = null;
    }

    private function formatTimelineShift(array $shift, string $timezone, int $index): array
    {
        $clockInLocal = $shift['clock_in']->copy()->setTimezone($timezone);
        $effectiveEnd = $shift['effective_end']
            ? $shift['effective_end']->copy()
            : ($shift['clock_out'] ?? $shift['clock_in'])->copy();
        $effectiveEndLocal = $effectiveEnd->copy()->setTimezone($timezone);

        $clockOutLocal = $shift['clock_out']
            ? $shift['clock_out']->copy()->setTimezone($timezone)
            : null;

        $segments = collect($shift['segments'])
            ->sortBy(fn ($segment) => $segment['start'])
            ->map(function ($segment) use ($timezone) {
                $startLocal = $segment['start']->copy()->setTimezone($timezone);
                $endLocal = $segment['end']->copy()->setTimezone($timezone);

                return [
                    'type' => $segment['type'],
                    'start' => $startLocal->toIso8601String(),
                    'end' => $endLocal->toIso8601String(),
                    'duration_seconds' => $segment['duration'],
                ];
            })
            ->values()
            ->all();

        $totalSeconds = max($effectiveEnd->diffInSeconds($shift['clock_in']), 0);
        $segmentTotal = array_sum($shift['durations']);
        if ($segmentTotal > 0 && $totalSeconds === 0) {
            $totalSeconds = $segmentTotal;
        }

        return [
            'shift_index' => $index,
            'clock_in' => $clockInLocal->toIso8601String(),
            'clock_out' => $clockOutLocal ? $clockOutLocal->toIso8601String() : null,
            'clock_in_display' => $clockInLocal->format('h:i A'),
            'clock_out_display' => $clockOutLocal ? $clockOutLocal->format('h:i A') : '--',
            'status' => $shift['status'] ?? ($clockOutLocal ? 'completed' : 'ongoing'),
            'effective_end' => $effectiveEndLocal->toIso8601String(),
            'duration_seconds' => $totalSeconds,
            'total_display' => $this->formatTime($totalSeconds),
            'segments' => $segments,
            'summary' => [
                'active_seconds' => $shift['durations']['active'],
                'manual_seconds' => $shift['durations']['manual'],
                'pending_manual_seconds' => $shift['durations']['pending_manual'],
                'break_seconds' => $shift['durations']['break'],
                'idle_seconds' => $shift['durations']['idle'],
            ],
        ];
    }
}
