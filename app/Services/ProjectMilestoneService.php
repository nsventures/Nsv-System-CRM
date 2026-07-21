<?php

namespace App\Services;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\DeletionService;
use Illuminate\Support\Facades\Log;

class ProjectMilestoneService
{
    /**
     * Create a new milestone
     */
    public function createMilestone(Workspace $workspace, User $user, array $data, bool $isApi = false): Milestone
    {
        try {
            // Format dates
            if (!empty($data['start_date'])) {
                $data['start_date'] = format_date($data['start_date'], false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
            }
            if (!empty($data['end_date'])) {
                $data['end_date'] = format_date($data['end_date'], false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
            }

            // Format cost
            if (isset($data['cost'])) {
                $data['cost'] = str_replace(',', '', $data['cost']);
            }

            // Set workspace and creator
            $data['workspace_id'] = $workspace->id;
            $data['created_by'] = isClient() ? 'c_' . $user->id : 'u_' . $user->id;

            return Milestone::create($data);
        } catch (\Exception $e) {
            Log::error('Milestone creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing milestone
     */
    public function updateMilestone(Milestone $milestone, array $data, bool $isApi = false): Milestone
    {
        try {
            // Format dates
            if (isset($data['start_date'])) {
                $data['start_date'] = $data['start_date']
                    ? format_date($data['start_date'], false, app('php_date_format'), 'Y-m-d')
                    : null;
            }
            if (isset($data['end_date'])) {
                $data['end_date'] = $data['end_date']
                    ? format_date($data['end_date'], false, app('php_date_format'), 'Y-m-d')
                    : null;
            }

            // Format cost
            if (isset($data['cost'])) {
                $data['cost'] = str_replace(',', '', $data['cost']);
            }

            $milestone->update($data);
            return $milestone->fresh();
        } catch (\Exception $e) {
            Log::error('Milestone update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'milestone_id' => $milestone->id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Get milestones for a project with filters
     *
     * Always returns an Eloquent\Builder instance (not a Relation) to satisfy the
     * return type and avoid TypeError in PHP 8.3+.
     */
    public function getMilestones(Project $project, array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        // Start directly from Milestone model query to get Eloquent\Builder
        // This ensures we return the correct type instead of a Relation
        $query = Milestone::query()->where('project_id', $project->id);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')
                    ->orWhere('cost', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
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
        }

        // End date range filter
        if (!empty($filters['end_date_from']) && !empty($filters['end_date_to'])) {
            $query->whereBetween('end_date', [$filters['end_date_from'], $filters['end_date_to']]);
        }

        // Status filter
        if (!empty($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        return $query;
    }

    /**
     * Delete multiple milestones
     */
    public function deleteMultipleMilestones(array $milestoneIds): array
    {
        $deletedIds = [];
        $deletedTitles = [];
        $parentIds = [];

        foreach ($milestoneIds as $id) {
            $milestone = Milestone::findOrFail($id);
            $deletedIds[] = $id;
            $deletedTitles[] = $milestone->title;
            $parentIds[] = $milestone->project_id;
            DeletionService::delete(Milestone::class, $id, 'Milestone');
        }

        return [
            'deleted_ids' => $deletedIds,
            'deleted_titles' => $deletedTitles,
            'parent_ids' => $parentIds
        ];
    }
}

