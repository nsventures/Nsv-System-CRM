<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\Status;
use App\Models\Priority;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class ProjectQueryService
{
    /**
     * Build base project query based on permissions and context
     */
    public function buildBaseQuery(Workspace $workspace, User $user, ?string $belongsTo = null, ?int $belongsToId = null): Builder
    {
        // IMPORTANT: Always return an Eloquent\Builder instance (not a Relation)
        if ($belongsTo && $belongsToId) {
            if ($belongsTo === 'user') {
                $targetUser = User::find($belongsToId);

                $relation = isAdminOrHasAllDataAccess($belongsTo, $belongsToId)
                    ? $workspace->projects()
                    : ($targetUser ? $targetUser->projects() : $workspace->projects());

                return $relation->getQuery();
            } elseif ($belongsTo === 'client') {
                $targetClient = Client::find($belongsToId);

                $relation = isAdminOrHasAllDataAccess($belongsTo, $belongsToId)
                    ? $workspace->projects()
                    : ($targetClient ? $targetClient->projects() : $workspace->projects());

                return $relation->getQuery();
            }
        }

        $relation = isAdminOrHasAllDataAccess()
            ? $workspace->projects()
            : $user->projects();

        return $relation->getQuery();
    }

    /**
     * Apply filters to project query
     */
    public function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        // Status filter - handle both 'status_ids' and 'statuses' keys
        $statusIds = $filters['status_ids'] ?? $filters['statuses'] ?? [];
        if (!empty($statusIds)) {
            $query->whereIn('status_id', $statusIds);
        }

        // Priority filter
        if (!empty($filters['priority_ids'])) {
            $query->whereIn('priority_id', $filters['priority_ids']);
        }

        // User filter
        if (!empty($filters['user_ids'])) {
            $query->whereHas('users', function ($q) use ($filters) {
                $q->whereIn('users.id', $filters['user_ids']);
            });
        }

        // Client filter
        if (!empty($filters['client_ids'])) {
            $query->whereHas('clients', function ($q) use ($filters) {
                $q->whereIn('clients.id', $filters['client_ids']);
            });
        }

        // Tag filter - handle both 'tag_ids' and 'tags' keys
        $tagIds = $filters['tag_ids'] ?? $filters['tags'] ?? [];
        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Date between filter (overlap detection)
        if (!empty($filters['date_between_from']) && !empty($filters['date_between_to'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('start_date', '<=', $filters['date_between_to'])
                    ->where('end_date', '>=', $filters['date_between_from']);
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
            $query->whereBetween('end_date', [$filters['end_date_from'], $filters['end_date_to']]);
        } elseif (!empty($filters['end_date_to'])) {
            $query->where('end_date', '<=', $filters['end_date_to']);
        }

        // Favorites filter - handle both 'is_favorites' and 'is_favorite' keys
        $isFavorite = $filters['is_favorites'] ?? $filters['is_favorite'] ?? null;
        if ($isFavorite === 1 || $isFavorite === '1' || $isFavorite === true) {
            $favoriteProjectIds = $user->favoriteProjects()
                ->pluck('favoritable_id')
                ->toArray();
            $query->whereIn('projects.id', $favoriteProjectIds);
        }

        // Search filter (note: list() method doesn't include description, apiList() does)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $includeDescription = $filters['search_include_description'] ?? false;
            $query->where(function ($q) use ($search, $includeDescription) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('projects.id', 'like', '%' . $search . '%');
                if ($includeDescription) {
                    $q->orWhere('projects.description', 'like', '%' . $search . '%');
                }
            });
        }

        return $query;
    }

    /**
     * Apply sorting to project query
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
            $join->on('pinned.pinnable_id', '=', 'projects.id')
                ->where('pinned.pinnable_type', '=', Project::class)
                ->where('pinned.' . $authUserColumn, '=', $user->id);
        })
        ->select('projects.*', 'pinned.id as pinned_id')
        ->orderByRaw('pinned.id IS NULL ASC')
        ->orderBy('projects.' . $sort, $order);

        return $query;
    }

    /**
     * Get filtered projects query builder for web view (list method)
     * Returns the query builder so controller can add pagination and formatting
     */
    public function getProjectListQuery(Workspace $workspace, User $user, array $filters): Builder
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
     * Get filtered projects query builder for API (apiList method)
     * Returns the query builder so controller can handle pagination and formatting
     */
    public function getProjectApiListQuery(Workspace $workspace, User $user, array $filters): Builder
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

