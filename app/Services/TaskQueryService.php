<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TaskQueryService
{
    /**
     * Build base task query based on permissions and context
     * IMPORTANT: Always returns Eloquent\Builder (not a Relation) to avoid TypeError in PHP 8.3+
     */
    public function buildBaseQuery(Workspace $workspace, User $user, ?string $belongsTo = null, ?int $belongsToId = null): Builder
    {
        // IMPORTANT: Always return an Eloquent\Builder instance (not a Relation)
        if ($belongsTo && $belongsToId) {
            if ($belongsTo === 'project') {
                $project = Project::find($belongsToId);
                $relation = $project ? $project->tasks() : $workspace->tasks();
                return $relation->getQuery();
            } elseif ($belongsTo === 'user') {
                $targetUser = User::find($belongsToId);
                $relation = isAdminOrHasAllDataAccess($belongsTo, $belongsToId)
                    ? $workspace->tasks()
                    : ($targetUser ? $targetUser->tasks() : $workspace->tasks());
                return $relation->getQuery();
            } elseif ($belongsTo === 'client') {
                $targetClient = Client::find($belongsToId);
                $relation = isAdminOrHasAllDataAccess($belongsTo, $belongsToId)
                    ? $workspace->tasks()
                    : ($targetClient ? $targetClient->tasks() : $workspace->tasks());
                return $relation->getQuery();
            }
        }

        $relation = isAdminOrHasAllDataAccess()
            ? $workspace->tasks()
            : $user->tasks();

        return $relation->getQuery();
    }

    /**
     * Apply filters to task query
     */
    public function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        // Status filter
        if (!empty($filters['status_ids'])) {
            $query->whereIn('status_id', $filters['status_ids']);
        }

        // Priority filter
        if (!empty($filters['priority_ids'])) {
            $query->whereIn('priority_id', $filters['priority_ids']);
        }

        // User filter
        if (!empty($filters['user_ids'])) {
            $taskIds = DB::table('task_user')->whereIn('user_id', $filters['user_ids'])->pluck('task_id')->toArray();
            $query->whereIn('tasks.id', $taskIds);
        }

        // Client filter (through project)
        if (!empty($filters['client_ids'])) {
            $projectIds = DB::table('client_project')->whereIn('client_id', $filters['client_ids'])->pluck('project_id')->toArray();
            $query->whereIn('project_id', $projectIds);
        }

        // Project filter
        if (!empty($filters['project_ids'])) {
            $query->whereIn('project_id', $filters['project_ids']);
        }

        // Date between filter (overlap detection)
        if (!empty($filters['date_between_from']) && !empty($filters['date_between_to'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('start_date', '<=', $filters['date_between_to'])
                    ->where('due_date', '>=', $filters['date_between_from']);
            });
        }

        // Start date range filter
        if (!empty($filters['start_date_from']) && !empty($filters['start_date_to'])) {
            $query->whereBetween('start_date', [$filters['start_date_from'], $filters['start_date_to']]);
        } elseif (!empty($filters['start_date_from'])) {
            $query->where('start_date', '>=', $filters['start_date_from']);
        }

        // End date range filter
        if (!empty($filters['end_date_from']) && !empty($filters['end_date_to'])) {
            $query->whereBetween('due_date', [$filters['end_date_from'], $filters['end_date_to']]);
        } elseif (!empty($filters['end_date_to'])) {
            $query->where('due_date', '<=', $filters['end_date_to']);
        }

        // Favorites filter
        $isFavorite = $filters['is_favorites'] ?? $filters['is_favorite'] ?? null;
        if ($isFavorite === 1 || $isFavorite === '1' || $isFavorite === true || $isFavorite === 'true') {
            $favoriteTaskIds = $user->favorites()
                ->where('favoritable_type', Task::class)
                ->pluck('favoritable_id')
                ->toArray();
            $query->whereIn('tasks.id', $favoriteTaskIds);
        }

        // Parent task filter
        if (isset($filters['task_parent_id'])) {
            if ($filters['task_parent_id'] === '' || $filters['task_parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['task_parent_id']);
            }
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $includeDescription = $filters['search_include_description'] ?? false;
            $query->where(function ($q) use ($search, $includeDescription) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('tasks.id', 'like', '%' . $search . '%');
                if ($includeDescription) {
                    $q->orWhere('description', 'like', '%' . $search . '%');
                }
            });
        }

        return $query;
    }

    /**
     * Apply sorting to task query
     */
    public function applySorting(Builder $query, string $sort = 'id', string $order = 'DESC', User $user): Builder
    {
        // Normalize order to lowercase for consistency
        $order = strtolower($order);

        // Handle sort aliases
        switch ($sort) {
            case 'newest':
                $sort = 'created_at';
                $order = 'desc';
                break;
            case 'oldest':
                $sort = 'created_at';
                $order = 'asc';
                break;
            case 'recently-updated':
                $sort = 'updated_at';
                $order = 'desc';
                break;
            case 'earliest-updated':
                $sort = 'updated_at';
                $order = 'asc';
                break;
        }

        // Join pinned table for pinning functionality
        $authUserColumn = $user instanceof \App\Models\User ? 'user_id' : 'client_id';

        $query->leftJoin('pinned', function ($join) use ($authUserColumn, $user) {
            $join->on('pinned.pinnable_id', '=', 'tasks.id')
                ->where('pinned.pinnable_type', '=', Task::class)
                ->where('pinned.' . $authUserColumn, '=', $user->id);
        })
        ->select('tasks.*', 'pinned.id as pinned_id')
        ->orderByDesc('pinned.id')
        ->orderBy('tasks.' . $sort, $order);

        return $query;
    }

    /**
     * Get filtered tasks query builder for web view (list method)
     * Returns the query builder so controller can add pagination and formatting
     */
    public function getTaskListQuery(Workspace $workspace, User $user, array $filters): Builder
    {
        $belongsTo = $filters['belongs_to'] ?? null;
        $belongsToId = $filters['belongs_to_id'] ?? null;

        $query = $this->buildBaseQuery($workspace, $user, $belongsTo, $belongsToId);
        $query = $this->applyFilters($query, $filters, $user);

        $sort = $filters['sort'] ?? 'id';
        $order = $filters['order'] ?? 'DESC';
        $query = $this->applySorting($query, $sort, $order, $user);

        return $query;
    }

    /**
     * Get filtered tasks query builder for API (apiList method)
     * Returns the query builder so controller can handle pagination and formatting
     */
    public function getTaskApiListQuery(Workspace $workspace, User $user, array $filters): Builder
    {
        $belongsTo = $filters['belongs_to'] ?? null;
        $belongsToId = $filters['belongs_to_id'] ?? null;

        $query = $this->buildBaseQuery($workspace, $user, $belongsTo, $belongsToId);
        // Set flag for API search to include description
        $filters['search_include_description'] = true;
        $query = $this->applyFilters($query, $filters, $user);

        $sort = $filters['sort'] ?? 'id';
        $order = $filters['order'] ?? 'DESC';
        $query = $this->applySorting($query, $sort, $order, $user);

        return $query;
    }

    /**
     * Build query for index/kanban views
     */
    public function buildIndexQuery(Workspace $workspace, User $user, array $filters): Builder
    {
        $query = $this->buildBaseQuery($workspace, $user);
        $query = $this->applyFilters($query, $filters, $user);

        $sort = $filters['sort'] ?? 'id';
        $order = $filters['order'] ?? 'desc';
        $query = $this->applySorting($query, $sort, $order, $user);

        return $query;
    }
}











