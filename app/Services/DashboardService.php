<?php

namespace App\Services;

use App\Models\Status;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Build dashboard data for the given workspace, user and filters.
     *
     * This method does not perform any HTTP or logging side effects – it only
     * computes the data structure returned by the original controller method.
     *
     * @param  Workspace|null  $workspace
     * @param  User|null  $user
     * @param  array{start_date?:string|null,end_date?:string|null,user_ids?:array<int,int>}  $filters
     * @return array<string,mixed>
     */
    public function getDashboardData(?Workspace $workspace, ?User $user, array $filters = []): array
    {
        $startDateInput = $filters['start_date'] ?? null;
        $endDateInput = $filters['end_date'] ?? null;
        $userIds = $filters['user_ids'] ?? [];

        // Use Carbon to set defaults if inputs are null or invalid.
        // If both are empty/null, we intentionally leave them null so date filters are skipped.
        $startDate = ($startDateInput && Carbon::hasFormat($startDateInput, 'Y-m-d'))
            ? $startDateInput
            : null;

        $endDate = ($endDateInput && Carbon::hasFormat($endDateInput, 'Y-m-d'))
            ? $endDateInput
            : null;

        // Validate date range only when both are supplied
        if ($startDate && $endDate && Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            throw new \InvalidArgumentException('start_date cannot be after end_date.');
        }

        $dateRangeWithTime = ($startDate && $endDate)
            ? [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            : null;

        [
            $projectsCount,
            $tasksCount,
            $usersCount,
            $clientsCount,
            $meetingsCount
        ] = $this->buildTileCounts($workspace, $user, $dateRangeWithTime, $userIds, $startDate, $endDate);

        $todosCount = $this->countTodos($workspace, $dateRangeWithTime, $userIds);
        $todos = $this->buildTodos($user, $dateRangeWithTime, $userIds);
        $activities = $this->buildActivities($workspace, $dateRangeWithTime, $userIds);

        [
            $projectData,
            $taskData,
            $projectStatusCounts,
            $taskStatusCounts,
            $labels,
            $bgColors,
            $statuses
        ] = $this->buildStatusChartData($workspace, $user, $userIds, $startDate, $endDate);

        $todoData = $this->buildTodoStatusData($workspace, $dateRangeWithTime, $userIds);

        return [
            'projects_count' => $projectsCount,
            'tasks_count' => $tasksCount,
            'users_count' => $usersCount,
            'clients_count' => $clientsCount,
            'meetings_count' => $meetingsCount,
            'todos_count' => $todosCount,
            'project_data' => $projectData,
            'task_data' => $taskData,
            'todo_data' => $todoData,
            'labels' => $labels,
            'bg_colors' => $bgColors,
            'todos' => $todos,
            'activities' => $activities,
            'statuses' => $statuses->map(
                static fn ($status) => [
                    'id' => $status->id,
                    'title' => $status->title,
                    'color' => $status->color,
                ]
            ),
            'project_status_counts' => $projectStatusCounts,
            'task_status_counts' => $taskStatusCounts,
            'total_projects' => array_sum($projectData),
            'total_tasks' => array_sum($taskData),
            'trends' => $this->buildTrends($workspace, $user, $userIds, $startDate, $endDate),
        ];
    }

    /**
     * Build real per-metric trend series (cumulative daily record counts) over
     * the selected date range. Read-only and additive: it does not alter any
     * existing count/chart logic. Each series ends at the cumulative total of
     * records created up to $endDate, scoped identically to the tile counts
     * (workspace/user, admin-access, selected user ids). Powers the KPI
     * sparklines on the dashboard.
     *
     * @param  Workspace|null  $workspace
     * @param  User|null  $user
     * @param  array<int,int>  $userIds
     * @param  string  $startDate
     * @param  string  $endDate
     * @return array<string,array<int,int>>
     */
    protected function buildTrends(
        ?Workspace $workspace,
        ?User $user,
        array $userIds,
        ?string $startDate,
        ?string $endDate
    ): array {
        $empty = [
            'projects' => [], 'tasks' => [], 'users' => [],
            'clients' => [], 'meetings' => [], 'todos' => [],
        ];

        if (!$workspace) {
            return $empty;
        }

        $isAdmin = isAdminOrHasAllDataAccess();

        // Each factory returns a FRESH relation query (so it can be re-run for
        // the baseline + grouped passes) or null when the relation is absent.
        $projectsFactory = static function () use ($isAdmin, $workspace, $user, $userIds) {
            if ($isAdmin) {
                $q = $workspace->projects();
            } elseif ($user && method_exists($user, 'projects')) {
                $q = $user->projects();
            } else {
                return null;
            }
            if (!empty($userIds)) {
                $q->whereHas('users', static fn ($u) => $u->whereIn('users.id', $userIds));
            }
            return $q;
        };

        $tasksFactory = static function () use ($isAdmin, $workspace, $user, $userIds) {
            if ($isAdmin) {
                $q = $workspace->tasks();
            } elseif ($user && method_exists($user, 'tasks')) {
                $q = $user->tasks();
            } else {
                return null;
            }
            if (!empty($userIds)) {
                $q->whereHas('users', static fn ($u) => $u->whereIn('users.id', $userIds));
            }
            return $q;
        };

        $meetingsFactory = static function () use ($isAdmin, $workspace, $user, $userIds) {
            if ($isAdmin) {
                $q = $workspace->meetings();
            } elseif ($user && method_exists($user, 'meetings')) {
                $q = $user->meetings();
            } else {
                return null;
            }
            if (!empty($userIds)) {
                $q->whereHas('users', static fn ($u) => $u->whereIn('users.id', $userIds));
            }
            return $q;
        };

        $usersFactory = static function () use ($workspace, $userIds) {
            $q = $workspace->users();
            if (!empty($userIds)) {
                $q->whereIn('users.id', $userIds);
            }
            return $q;
        };

        $clientsFactory = static fn () => $workspace->clients();

        $todosFactory = method_exists($workspace, 'todos')
            ? static function () use ($workspace, $userIds) {
                $q = $workspace->todos();
                if (!empty($userIds)) {
                    $q->whereIn('creator_id', $userIds);
                }
                return $q;
            }
            : null;

        return [
            'projects' => $this->growthSeries($projectsFactory, 'projects.created_at'),
            'tasks' => $this->growthSeries($tasksFactory, 'tasks.created_at'),
            'users' => $this->growthSeries($usersFactory, 'users.created_at'),
            'clients' => $this->growthSeries($clientsFactory, 'clients.created_at'),
            'meetings' => $this->growthSeries($meetingsFactory, 'meetings.created_at'),
            'todos' => $this->growthSeries($todosFactory, 'todos.created_at'),
        ];
    }

    /**
     * Compute a metric's real all-time growth series: the cumulative record
     * count (scoped by the given relation) from before the first record up to
     * today, sampled over evenly-spaced buckets. The line therefore starts near
     * 0 and rises to the metric's true current total, reflecting genuine growth
     * over its actual creation history — independent of the dashboard's date
     * filter, so it is never flat just because nothing was created this week.
     *
     * One grouped query per metric (counts per creation day); the buckets are
     * accumulated in PHP. Returns [] when the relation is absent or has no rows.
     *
     * @param  callable|null  $factory   returns a fresh relation query, or null
     * @param  string  $dateColumn       fully-qualified created_at column
     * @return array<int,int>
     */
    protected function growthSeries(?callable $factory, string $dateColumn): array
    {
        if ($factory === null) {
            return [];
        }

        $probe = $factory();
        if ($probe === null) {
            return [];
        }

        try {
            $perDay = $factory()
                ->whereNotNull($dateColumn)
                ->selectRaw("DATE($dateColumn) as d, COUNT(*) as c")
                ->groupBy('d')
                ->orderBy('d')
                ->pluck('c', 'd')
                ->toArray();
        } catch (\Throwable $e) {
            // Never break the dashboard for a decorative trend.
            return [];
        }

        if (empty($perDay)) {
            return [];
        }

        // Real day -> count, as sorted parallel arrays of [timestamp, count].
        $dayTs = [];
        $dayCount = [];
        foreach ($perDay as $day => $count) {
            $dayTs[] = Carbon::parse($day)->endOfDay()->getTimestamp();
            $dayCount[] = (int) $count;
        }

        $first = reset($dayTs);
        $last = Carbon::now()->endOfDay()->getTimestamp();
        // Start one day before the first record so the series begins at 0.
        $start = Carbon::createFromTimestamp($first)->subDay()->getTimestamp();
        if ($last <= $start) {
            $last = $start + 86400;
        }
        $span = $last - $start;

        $points = 24;
        $series = [];
        for ($i = 0; $i < $points; $i++) {
            $boundary = $start + (int) round($span * $i / ($points - 1));
            $cumulative = 0;
            foreach ($dayTs as $j => $ts) {
                if ($ts <= $boundary) {
                    $cumulative += $dayCount[$j];
                } else {
                    break; // arrays are sorted ascending by day
                }
            }
            $series[] = $cumulative;
        }

        return $series;
    }

    /**
     * Build count tiles (projects, tasks, users, clients, meetings).
     *
     * @param  Workspace|null  $workspace
     * @param  User|null  $user
     * @param  array{0:string,1:string}  $dateRangeWithTime
     * @param  array<int,int>  $userIds
     * @param  string  $startDate
     * @param  string  $endDate
     * @return array<int,int>
     */
    protected function buildTileCounts(
        ?Workspace $workspace,
        ?User $user,
        ?array $dateRangeWithTime,
        array $userIds,
        ?string $startDate,
        ?string $endDate
    ): array {
        $projectsCount = 0;
        $tasksCount = 0;
        $usersCount = 0;
        $clientsCount = 0;
        $meetingsCount = 0;

        if ($workspace) {
            // Define overlap queries for projects and tasks
            $projectOverlapQuery = static function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($endDate) {
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $endDate . ' 23:59:59');
                })->where(function ($q) use ($startDate) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startDate . ' 00:00:00');
                });
            };

            $taskOverlapQuery = static function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($endDate) {
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $endDate . ' 23:59:59');
                })->where(function ($q) use ($startDate) {
                    $q->whereNull('due_date')
                        ->orWhere('due_date', '>=', $startDate . ' 00:00:00');
                });
            };

            // Projects and tasks are NOT filtered by date range — the tile shows ALL records.
            // Only meetings, clients, todos, and activities respect the date picker.
            $projectsQuery = $workspace->projects();

            $tasksQuery = $workspace->tasks();

            $meetingsQuery = $workspace->meetings();
            if ($dateRangeWithTime) {
                $meetingsQuery->whereBetween('created_at', $dateRangeWithTime);
            }

            if (!isAdminOrHasAllDataAccess()) {
                // For non-admins, filter by user-specific relationships
                $projectsQuery = $user && method_exists($user, 'projects')
                    ? $user->projects()
                    : $workspace->projects()->whereRaw('1=0');

                $tasksQuery = $user && method_exists($user, 'tasks')
                    ? $user->tasks()
                    : $workspace->tasks()->whereRaw('1=0');

                $meetingsQuery = $user && method_exists($user, 'meetings')
                    ? ($dateRangeWithTime ? $user->meetings()->whereBetween('created_at', $dateRangeWithTime) : $user->meetings())
                    : $workspace->meetings()->whereRaw('1=0');
            }

            if (!empty($userIds)) {
                $projectsQuery->whereHas('users', static fn ($q) => $q->whereIn('users.id', $userIds));
                $tasksQuery->whereHas('users', static fn ($q) => $q->whereIn('users.id', $userIds));
                $meetingsQuery->whereHas('users', static fn ($q) => $q->whereIn('users.id', $userIds));
            }

            $projectsCount = $projectsQuery->count();
            $tasksCount = $tasksQuery->count();
            $usersCount = $workspace->users()
                ->when($userIds, static fn ($query) => $query->whereIn('users.id', $userIds))
                ->count();

            // Clients — no date filter, show all
            $clientsCount = $workspace->clients()->count();
            $meetingsCount = $meetingsQuery->count();
        }

        return [
            $projectsCount,
            $tasksCount,
            $usersCount,
            $clientsCount,
            $meetingsCount,
        ];
    }

    /**
     * Count todos for the given workspace and filters.
     *
     * @param  Workspace|null  $workspace
     * @param  array{0:string,1:string}  $dateRangeWithTime
     * @param  array<int,int>  $userIds
     * @return int
     */
    protected function countTodos(?Workspace $workspace, ?array $dateRangeWithTime, array $userIds): int
    {
        if (!$workspace || !method_exists($workspace, 'todos')) {
            return 0;
        }

        return $workspace->todos()
            ->count();
    }

    /**
     * Build todos list collection for the authenticated user.
     *
     * @param  User|null  $user
     * @param  array{0:string,1:string}|null  $dateRangeWithTime
     * @param  array<int,int>  $userIds
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    protected function buildTodos(?User $user, ?array $dateRangeWithTime, array $userIds): Collection
    {
        if (!$user) {
            return collect();
        }

        // Use workspace todos so ALL todos appear, not just the logged-in user's own
        $workspace = \App\Models\Workspace::find(getWorkspaceId());
        if (!$workspace || !method_exists($workspace, 'todos')) {
            return collect();
        }

        return $workspace->todos()
            ->where('is_completed', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(static function ($todo) {
                return [
                    'id' => $todo->id,
                    'title' => ucfirst($todo->title),
                    'is_completed' => $todo->is_completed,
                    'created_at' => format_date($todo->created_at, true),
                ];
            });
    }

    /**
     * Build recent activities collection for the workspace.
     *
     * @param  Workspace|null  $workspace
     * @param  array{0:string,1:string}|null  $dateRangeWithTime
     * @param  array<int,int>  $userIds
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    protected function buildActivities(?Workspace $workspace, ?array $dateRangeWithTime, array $userIds): Collection
    {
        if (!$workspace || !method_exists($workspace, 'activity_logs')) {
            return collect();
        }

        return $workspace->activity_logs()
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(static function ($activity) {
                return [
                    'id' => $activity->id,
                    'message' => $activity->message,
                    'activity' => $activity->activity,
                    'created_at' => $activity->created_at->toIso8601String(),
                    'created_at_diff' => $activity->created_at->diffForHumans(),
                    'created_at_formatted' => format_date($activity->created_at, true),
                ];
            });
    }

    /**
     * Build status-wise chart data for projects and tasks.
     *
     * @param  Workspace|null  $workspace
     * @param  User|null  $user
     * @param  array<int,int>  $userIds
     * @param  string  $startDate
     * @param  string  $endDate
     * @return array<int,mixed>
     */
    protected function buildStatusChartData(
        ?Workspace $workspace,
        ?User $user,
        array $userIds,
        ?string $startDate,
        ?string $endDate
    ): array {
        $projectData = [];
        $taskData = [];
        $projectStatusCounts = [];
        $taskStatusCounts = [];
        $labels = [];
        $bgColors = [];

        $colorMap = [
            'primary' => '#6777ef',
            'secondary' => '#6c757d',
            'success' => '#63ed7a',
            'danger' => '#fc544b',
            'warning' => '#ffa426',
            'info' => '#00c4b4',
        ];

        $statuses = Status::all();

        if (!$workspace) {
            // Keep behaviour identical: if workspace is null, everything stays empty.
            return [
                $projectData,
                $taskData,
                $projectStatusCounts,
                $taskStatusCounts,
                $labels,
                $bgColors,
                $statuses,
            ];
        }

        $projectOverlapQuery = static function ($query) use ($startDate, $endDate) {
            $query->where(function ($q) use ($endDate) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $endDate . ' 23:59:59');
            })->where(function ($q) use ($startDate) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startDate . ' 00:00:00');
            });
        };

        $taskOverlapQuery = static function ($query) use ($startDate, $endDate) {
            $query->where(function ($q) use ($endDate) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $endDate . ' 23:59:59');
            })->where(function ($q) use ($startDate) {
                $q->whereNull('due_date')
                    ->orWhere('due_date', '>=', $startDate . ' 00:00:00');
            });
        };

        foreach ($statuses as $status) {
            // Status charts count ALL projects/tasks (no date filter) so the donut total matches the tile.
            $projectStatusQuery = $workspace->projects()
                ->where('status_id', $status->id);

            $taskStatusQuery = $workspace->tasks()
                ->where('status_id', $status->id);

            if (!isAdminOrHasAllDataAccess()) {
                $projectStatusQuery = $user && method_exists($user, 'projects')
                    ? $user->projects()->where('status_id', $status->id)
                    : $workspace->projects()->whereRaw('1=0');

                $taskStatusQuery = $user && method_exists($user, 'tasks')
                    ? $user->tasks()->where('status_id', $status->id)
                    : $workspace->tasks()->whereRaw('1=0');
            }

            if (!empty($userIds)) {
                $projectStatusQuery->whereHas('users', static fn ($q) => $q->whereIn('users.id', $userIds));
                $taskStatusQuery->whereHas('users', static fn ($q) => $q->whereIn('users.id', $userIds));
            }

            $projectCount = $projectStatusQuery->count();
            $taskCount = $taskStatusQuery->count();

            $projectData[] = $projectCount;
            $taskData[] = $taskCount;
            $projectStatusCounts[$status->id] = $projectCount;
            $taskStatusCounts[$status->id] = $taskCount;
            $labels[] = $status->title;
            $bgColors[] = $colorMap[$status->color] ?? '#64748B';
        }

        return [
            $projectData,
            $taskData,
            $projectStatusCounts,
            $taskStatusCounts,
            $labels,
            $bgColors,
            $statuses,
        ];
    }

    /**
     * Build completed vs pending todo counts for charts.
     *
     * @param  Workspace|null  $workspace
     * @param  array{0:string,1:string}  $dateRangeWithTime
     * @param  array<int,int>  $userIds
     * @return array<int,int>
     */
    protected function buildTodoStatusData(?Workspace $workspace, ?array $dateRangeWithTime, array $userIds): array
    {
        if (!$workspace || !method_exists($workspace, 'todos')) {
            return [0, 0];
        }

        $completed = $workspace->todos()
            ->where('is_completed', true)
            ->count();

        $pending = $workspace->todos()
            ->where('is_completed', false)
            ->count();

        return [$completed, $pending];
    }
}