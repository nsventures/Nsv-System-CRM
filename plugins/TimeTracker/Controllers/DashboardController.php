<?php

namespace Plugins\TimeTracker\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Plugins\TimeTracker\Models\TimeTrackerActivityLog;

class DashboardController extends Controller
{
    /**
     * Display the productivity dashboard with filters and comparison.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->endOfMonth()->format('Y-m-d'));
        $comparisonStartDate = $request->input('comparison_start_date');
        $comparisonEndDate = $request->input('comparison_end_date');
        if (! isAdminOrHasAllDataAccess()) {
            $userIds = [getAuthenticatedUser()->id];
        } else {
            $userIds = array_filter($request->input('user_id', [])); // array or null
        }

        $response = [
            'current_period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'metrics' => $this->calculateMetrics($startDate, $endDate, $userIds),
                'daily_breakdown' => $this->getDailyBreakdown($startDate, $endDate, $userIds),
                'activity_distribution' => $this->getActivityDistribution($startDate, $endDate, $userIds),
            ],
        ];

        if ($comparisonStartDate && $comparisonEndDate) {
            $response['comparison_period'] = [
                'start_date' => $comparisonStartDate,
                'end_date' => $comparisonEndDate,
                'metrics' => $this->calculateMetrics($comparisonStartDate, $comparisonEndDate, $userIds),
            ];

            $response['percentage_changes'] = $this->calculatePercentageChanges(
                $response['current_period']['metrics'],
                $response['comparison_period']['metrics']
            );

            $response['absolute_changes'] = $this->calculateAbsoluteChanges(
                $response['current_period']['metrics'],
                $response['comparison_period']['metrics']
            );
        }
        $response['top_productive_users'] = $this->getTopProductiveUsers($startDate, $endDate);
        $response['average_productive_hours_per_user'] = $this->getAverageProductiveHoursPerUser($startDate, $endDate, $userIds ?? null);

        return response()->json([
            'data' => $response,
            'employees' => $this->getEmployeeList(),
            'presets' => $this->getPresetDateRanges(),
        ]);
    }

    private function calculateAbsoluteChanges(array $current, array $previous): array
    {
        $changes = [];
        foreach ($current as $key => $cur) {
            if ($key === 'utilization') {
                $curVal = $cur['value'];
                $prevVal = $previous[$key]['value'];
                $diff = $curVal - $prevVal;
                $changes[$key] = [
                    'value_difference' => round($diff, 2),
                    'display' => ($diff >= 0 ? '+' : '') . round($diff, 2) . '%',
                ];
            } else {
                $curMinutes = $cur['minutes'];
                $prevMinutes = $previous[$key]['minutes'];
                $diffMinutes = $curMinutes - $prevMinutes;
                $hours = floor(abs($diffMinutes) / 60);
                $minutes = abs($diffMinutes) % 60;
                $display = ($diffMinutes >= 0 ? '+' : '-') . sprintf('%d:%02d h', $hours, $minutes);

                $changes[$key] = [
                    'minute_difference' => $diffMinutes,
                    'decimal_difference' => round($diffMinutes / 60, 2),
                    'display' => $display,
                ];
            }
        }
        return $changes;
    }

    /**
     * The configured display timezone (from general settings). Timestamps are stored in UTC;
     * day boundaries and grouping must use this timezone to match the timeline.
     */
    private function settingsTimezone(): string
    {
        $general_settings = get_settings('general_settings');
        return $general_settings['timezone'] ?? 'UTC';
    }

    private function calculateMetrics($startDate, $endDate, $userIds = null): array
    {
        // Parse the range in the configured timezone, then convert to UTC for the query so the
        // day boundaries line up with the stored UTC timestamps (and with the timeline view).
        $tz = $this->settingsTimezone();
        $startDateTime = Carbon::parse($startDate, $tz)->startOfDay()->setTimezone('UTC');
        $endDateTime = Carbon::parse($endDate, $tz)->endOfDay()->setTimezone('UTC');

        $query = TimeTrackerActivityLog::whereBetween('timestamp', [$startDateTime, $endDateTime]);

        if (! isAdminOrHasAllDataAccess()) {
            $query->where('user_id', getAuthenticatedUser()->id);
        } elseif ($userIds) {
            $query->whereIn('user_id', $userIds);
        }

        $logs = $query->orderBy('timestamp')->get();
        return $this->processActivityLogs($logs);
    }

    private function processActivityLogs($logs): array
    {
        $metrics = [
            'work_time' => 0,
            'active_time' => 0,
            'break_time' => 0,
            'idle_time' => 0,
            'manual_time' => 0,
            'manual_processing_time' => 0, // Pending approval - not productive yet
            'productive_time' => 0,
            'unproductive_time' => 0,
            // 'pending_time' => 0, // Add this to track pending approval time
            'utilization' => 0,
        ];

        $userSessions = $this->groupLogsByUserAndDay($logs);

        foreach ($userSessions as $days) {
            foreach ($days as $dayLogs) {
                $dayMetrics = $this->calculateDayMetrics($dayLogs);
                foreach ($dayMetrics as $key => $value) {
                    $metrics[$key] += $value;
                }
            }
        }

        // manual_processing_time is NOT productive until approved
        $metrics['productive_time'] = $metrics['active_time'] + $metrics['manual_time'];
        $metrics['unproductive_time'] = $metrics['break_time'] + $metrics['idle_time'];
        // $metrics['pending_time'] = $metrics['manual_processing_time']; // Track pending approval time

        if ($metrics['work_time'] > 0) {
            $metrics['utilization'] = $metrics['productive_time'] / $metrics['work_time'] * 100;
        }

        return $this->formatMetrics($metrics);
    }

    private function calculateDayMetrics($logs)
    {
        if (! $logs instanceof Collection) {
            $logs = collect($logs);
        }

        $sortedLogs = $logs->sortBy(function ($log) {
            return $log->timestamp instanceof Carbon ? $log->timestamp : Carbon::parse($log->timestamp);
        })->values();

        $metrics = [
            'work_time' => 0,
            'manual_time' => 0,
            'manual_processing_time' => 0, // Pending approval
            'break_time' => 0,
            'idle_time' => 0,
            'active_time' => 0,
            // 'pending_time' => 0, // Add for consistency
        ];

        $activeSessionStart = null;
        $breakStartTime = null;
        $idleStartTime = null;
        $manualStartTime = null;
        $manualProcessingStartTime = null;

        /** @var \Carbon\CarbonInterface $timezoneReference */
        $timezoneReference = $sortedLogs->first()
            ? ($sortedLogs->first()->timestamp instanceof Carbon
                ? $sortedLogs->first()->timestamp
                : Carbon::parse($sortedLogs->first()->timestamp))
            : Carbon::now();

        $referenceNow = Carbon::now($timezoneReference->getTimezone());

        foreach ($sortedLogs as $log) {
            $timestamp = $log->timestamp instanceof Carbon
                ? $log->timestamp->copy()
                : Carbon::parse($log->timestamp);

            switch ($log->action) {
                case 'clock-in':
                    if (! $activeSessionStart) {
                        $activeSessionStart = $timestamp;
                    }
                    break;
                case 'clock-out':
                    if ($activeSessionStart && $timestamp->greaterThanOrEqualTo($activeSessionStart)) {
                        $metrics['work_time'] += $activeSessionStart->diffInSeconds($timestamp);
                        $activeSessionStart = null;
                    }
                    break;
                case 'break-start':
                    $breakStartTime = $timestamp;
                    break;
                case 'break-stop':
                    if ($breakStartTime && $timestamp->greaterThanOrEqualTo($breakStartTime)) {
                        $metrics['break_time'] += $breakStartTime->diffInSeconds($timestamp);
                        $breakStartTime = null;
                    }
                    break;
                case 'idle-start':
                    $idleStartTime = $timestamp;
                    break;
                case 'idle-stop':
                    if ($idleStartTime && $timestamp->greaterThanOrEqualTo($idleStartTime)) {
                        $metrics['idle_time'] += $idleStartTime->diffInSeconds($timestamp);
                        $idleStartTime = null;
                    }
                    break;
                case 'manual-start':
                    $manualStartTime = $timestamp;
                    break;
                case 'manual-stop':
                    if ($manualStartTime && $timestamp->greaterThanOrEqualTo($manualStartTime)) {
                        $metrics['manual_time'] += $manualStartTime->diffInSeconds($timestamp);
                        $manualStartTime = null;
                    }
                    break;
                case 'manual-processing-start':
                    $manualProcessingStartTime = $timestamp;
                    break;
                case 'manual-processing-stop':
                    if ($manualProcessingStartTime && $timestamp->greaterThanOrEqualTo($manualProcessingStartTime)) {
                        $metrics['manual_processing_time'] += $manualProcessingStartTime->diffInSeconds($timestamp);
                        $manualProcessingStartTime = null;
                    }
                    break;
            }
        }

        if ($activeSessionStart && $referenceNow->greaterThanOrEqualTo($activeSessionStart)) {
            $lastActivityLog = $sortedLogs->last();
            $lastActivityTs = $lastActivityLog ? ($lastActivityLog->timestamp instanceof Carbon ? $lastActivityLog->timestamp->copy() : Carbon::parse($lastActivityLog->timestamp)) : $activeSessionStart->copy();

            // Check latest screenshot timestamp for today
            $latestScreenshot = \Plugins\TimeTracker\Models\Screenshot::when(!empty($userIds), function ($q) use ($userIds) {
                return $q->whereIn('user_id', (array) $userIds);
            })->whereDate('created_at', Carbon::today()->format('Y-m-d'))
              ->latest('created_at')
              ->first();

            if ($latestScreenshot) {
                $ssTs = Carbon::parse($latestScreenshot->created_at);
                if ($ssTs->greaterThan($lastActivityTs)) {
                    $lastActivityTs = $ssTs;
                }
            }

            // If user has been inactive for > 15 minutes, cap time at last activity
            $effectiveNow = $referenceNow;
            if ($referenceNow->diffInSeconds($lastActivityTs) > 15 * 60) {
                $effectiveNow = $lastActivityTs;
            }

            if ($effectiveNow->greaterThanOrEqualTo($activeSessionStart)) {
                $metrics['work_time'] += $activeSessionStart->diffInSeconds($effectiveNow);
            }
        }

        if ($breakStartTime && $referenceNow->greaterThanOrEqualTo($breakStartTime)) {
            $metrics['break_time'] += $breakStartTime->diffInSeconds($referenceNow);
        }

        if ($idleStartTime && $referenceNow->greaterThanOrEqualTo($idleStartTime)) {
            $metrics['idle_time'] += $idleStartTime->diffInSeconds($referenceNow);
        }

        if ($manualStartTime && $referenceNow->greaterThanOrEqualTo($manualStartTime)) {
            $metrics['manual_time'] += $manualStartTime->diffInSeconds($referenceNow);
        }

        if ($manualProcessingStartTime && $referenceNow->greaterThanOrEqualTo($manualProcessingStartTime)) {
            $metrics['manual_processing_time'] += $manualProcessingStartTime->diffInSeconds($referenceNow);
        }

        // Calculate active time - subtract all tracked activities from work time
        $calculatedActive = $metrics['work_time'] - ($metrics['manual_time'] + $metrics['manual_processing_time'] + $metrics['break_time'] + $metrics['idle_time']);
        $metrics['active_time'] = max(0, $calculatedActive);

        // Set pending time for consistency
        // $metrics['pending_time'] = $metrics['manual_processing_time'];

        return $metrics;
    }

    private function getDailyBreakdown($startDate, $endDate, $userIds = []): array
    {
        // Parse the range in the configured timezone, then convert to UTC for the query.
        $tz = $this->settingsTimezone();
        $startDateTime = Carbon::parse($startDate, $tz)->startOfDay()->setTimezone('UTC');
        $endDateTime = Carbon::parse($endDate, $tz)->endOfDay()->setTimezone('UTC');

        $query = TimeTrackerActivityLog::whereBetween('timestamp', [$startDateTime, $endDateTime]);

        $logs = $query->orderBy('timestamp')->get();
        $userSessions = $this->groupLogsByUserAndDay($logs);
        $data = [];

        $period = new \DatePeriod(Carbon::parse($startDate), \DateInterval::createFromDateString('1 day'), Carbon::parse($endDate)->addDay());

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $metrics = [
                'active_time' => 0,
                'break_time' => 0,
                'manual_time' => 0,
                'manual_processing_time' => 0, // Pending approval
                // 'pending_time' => 0, // Add for consistency
                'idle_time' => 0,
            ];

            foreach ($userSessions as $days) {
                if (isset($days[$dateStr])) {
                    $dayMetrics = $this->calculateDayMetrics($days[$dateStr]);
                    foreach ($metrics as $key => $val) {
                        $metrics[$key] += $dayMetrics[$key]; // seconds
                    }
                }
            }

            // calculateDayMetrics returns seconds; the frontend expects minutes.
            $data[] = [
                'date' => $dateStr,
                'day_name' => $date->format('M j'),
                'active_time' => round($metrics['active_time'] / 60, 2),
                'break_time' => round($metrics['break_time'] / 60, 2),
                'manual_time' => round($metrics['manual_time'] / 60, 2),
                'manual_processing_time' => round($metrics['manual_processing_time'] / 60, 2),
                'idle_time' => round($metrics['idle_time'] / 60, 2),
            ];
        }

        return $data;
    }

    private function getActivityDistribution($startDate, $endDate, $userIds = null): array
    {
        $metrics = $this->calculateMetrics($startDate, $endDate, $userIds);

        return [
            'active_time' => $metrics['active_time'],
            'manual_time' => $metrics['manual_time'],
            'manual_processing_time' => $metrics['manual_processing_time'], // Pending approval
            // 'pending_time' => $metrics['pending_time'], // Add for consistency
            'break_time' => $metrics['break_time'],
            'idle_time' => $metrics['idle_time'],
        ];
    }

    private function groupLogsByUserAndDay(Collection $logs): array
    {
        $grouped = [];
        $tz = $this->settingsTimezone();

        foreach ($logs as $log) {
            $userId = $log->user_id;
            // Timestamps are stored in UTC; group by the calendar day in the configured
            // timezone so day buckets match the timeline (and the requested range).
            $date = Carbon::parse($log->timestamp)->setTimezone($tz)->format('Y-m-d');
            $grouped[$userId][$date][] = $log;
        }

        return $grouped;
    }

    private function formatMetrics(array $metrics): array
    {
        $formatted = [];
        foreach ($metrics as $key => $val) {
            if ($key === 'utilization') {
                $formatted[$key] = [
                    'value' => round($val, 1),
                    'display' => round($val, 1) . '%',
                ];
            } else {
                // Values are accumulated in seconds; convert to minutes here (once), so
                // sub-minute intervals aggregate correctly instead of each rounding to zero.
                $seconds = (int) round($val);
                $totalMinutes = intdiv($seconds, 60);
                $hours = intdiv($totalMinutes, 60);
                $minutes = $totalMinutes % 60;
                $formatted[$key] = [
                    'minutes' => $totalMinutes,
                    'display' => sprintf('%d:%02d h', $hours, $minutes),
                    'decimal_hours' => round($seconds / 3600, 2),
                ];
            }
        }
        return $formatted;
    }

    private function calculatePercentageChanges(array $current, array $previous): array
    {
        $changes = [];
        foreach ($current as $key => $cur) {
            $curVal = $key === 'utilization' ? $cur['value'] : $cur['minutes'];
            $prevVal = $key === 'utilization' ? $previous[$key]['value'] : $previous[$key]['minutes'];

            if ($prevVal > 0) {
                $percent = ($curVal - $prevVal) / $prevVal * 100;
                $changes[$key] = [
                    'percentage' => round($percent, 1),
                    'direction' => $percent > 0 ? 'increase' : ($percent < 0 ? 'decrease' : 'no_change'),
                    'display' => ($percent > 0 ? '+' : '') . round($percent, 1) . '%',
                ];
            } else {
                $changes[$key] = [
                    'percentage' => 0,
                    'direction' => 'no_change',
                    'display' => '0%',
                ];
            }
        }
        return $changes;
    }

    private function getEmployeeList(): array
    {
        $query = TimeTrackerActivityLog::select('user_id')->distinct();
        if (! isAdminOrHasAllDataAccess()) {
            $query->where('user_id', getAuthenticatedUser()->id);
        }

        $userIds = $query->pluck('user_id');
        $employees = [];

        foreach ($userIds as $id) {
            $user = User::find($id);

            $employees[] = [

                'id' => $user->id,
                'name' => ucwords($user->first_name . ' ' . $user->last_name),
                'initials' => substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1),
                'selected' => false,
            ];
        }

        return $employees;
    }

    private function getPresetDateRanges(): array
    {
        $today = Carbon::today();

        return [
            'today' => [
                'label' => 'Today',
                'start_date' => $today->toDateString(),
                'end_date' => $today->toDateString(),
            ],
            'yesterday' => [
                'label' => 'Yesterday',
                'start_date' => $today->copy()->subDay()->toDateString(),
                'end_date' => $today->copy()->subDay()->toDateString(),
            ],
            'this_week' => [
                'label' => 'This Week',
                'start_date' => $today->startOfWeek()->toDateString(),
                'end_date' => $today->endOfWeek()->toDateString(),
            ],
            'last_7_days' => [
                'label' => 'Last 7 Days',
                'start_date' => $today->copy()->subDays(6)->toDateString(),
                'end_date' => $today->toDateString(),
            ],
            'previous_week' => [
                'label' => 'Previous Week',
                'start_date' => $today->copy()->subWeek()->startOfWeek()->toDateString(),
                'end_date' => $today->copy()->subWeek()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                'label' => 'This Month',
                'start_date' => $today->startOfMonth()->toDateString(),
                'end_date' => $today->endOfMonth()->toDateString(),
            ],
            'previous_month' => [
                'label' => 'Previous Month',
                'start_date' => $today->copy()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => $today->copy()->subMonth()->endOfMonth()->toDateString(),
            ],
        ];
    }

    private function getTopProductiveUsers($startDate, $endDate, $limit = 5)
    {
        $tz = $this->settingsTimezone();
        $startDateTime = Carbon::parse($startDate, $tz)->startOfDay()->setTimezone('UTC');
        $endDateTime = Carbon::parse($endDate, $tz)->endOfDay()->setTimezone('UTC');

        $query = TimeTrackerActivityLog::whereBetween('timestamp', [$startDateTime, $endDateTime]);

        if (! isAdminOrHasAllDataAccess()) {
            $query->where('user_id', getAuthenticatedUser()->id);
        }

        $logs = $query->orderBy('timestamp')->get();
        $grouped = $logs->groupBy('user_id');

        // ✅ Correctly retrieve user details with keyBy('id')
        $userDetails = User::whereIn('id', $grouped->keys())
            ->select('id', 'first_name', 'last_name')
            ->get()
            ->keyBy('id');

        $userStats = [];

        foreach ($grouped as $userId => $userLogs) {
            $processed = $this->processActivityLogs($userLogs);

            // Get daily breakdown
            $dailyBreakdown = $this->getDailyBreakdown($startDate, $endDate, $userId);
            $dailyProductive = [];
            foreach ($dailyBreakdown as $day) {
                $dailyProductive[] = $day['active_time'] + $day['manual_time'];
            }

            $user = $userDetails->get($userId);
            $fullName = $user ? trim("{$user->first_name} {$user->last_name}") : "User {$userId}";

            $userStats[] = [
                'user_id' => $userId,
                'productive_minutes' => $processed['productive_time']['minutes'],
                'productive_display' => $processed['productive_time']['display'],
                'utilization' => $processed['utilization']['value'],
                'utilization_display' => $processed['utilization']['display'],
                'name' => $fullName,
                'initials' => $user ? substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1) : '',
                'daily_productive_minutes' => $dailyProductive,
            ];
        }

        return collect($userStats)
            ->sortByDesc('productive_minutes')
            ->take($limit)
            ->values()
            ->all();
    }

    private function getAverageProductiveHoursPerUser($startDate, $endDate, $userIds = null)
    {
        $tz = $this->settingsTimezone();
        $startDateTime = Carbon::parse($startDate, $tz)->startOfDay()->setTimezone('UTC');
        $endDateTime = Carbon::parse($endDate, $tz)->endOfDay()->setTimezone('UTC');

        $query = TimeTrackerActivityLog::whereBetween('timestamp', [$startDateTime, $endDateTime]);

        if (! isAdminOrHasAllDataAccess()) {
            $query->where('user_id', getAuthenticatedUser()->id);
        } elseif ($userIds) {
            $query->whereIn('user_id', $userIds);
        }

        $logs = $query->orderBy('timestamp')->get();
        $grouped = $logs->groupBy('user_id');

        // ✅ FIX: Use keyBy for correct retrieval
        $userDetails = User::whereIn('id', $grouped->keys())
            ->select('id', 'first_name', 'last_name')
            ->get()
            ->keyBy('id');

        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        $results = [];
        foreach ($grouped as $userId => $userLogs) {
            $metrics = $this->processActivityLogs($userLogs);
            $average = $metrics['productive_time']['minutes'] / $days;

            $user = $userDetails->get($userId);
            $fullName = $user ? trim("{$user->first_name} {$user->last_name}") : "User {$userId}";

            $results[] = [
                'user_id' => $userId,
                'name' => $fullName,
                'average_productive_minutes' => $average,
                'average_productive_hours' => round($average / 60, 2),
            ];
        }

        return collect($results)
            ->sortByDesc('average_productive_minutes')
            ->values()
            ->all();
    }

    public function apiDashboard(Request $request)
    {
        return $this->calculateMetrics(null, null, [getAuthenticatedUser()->id]);
    }
}
