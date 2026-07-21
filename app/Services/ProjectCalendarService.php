<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;

class ProjectCalendarService
{
    /**
     * Get calendar events for projects
     */
    public function getCalendarEvents(Workspace $workspace, User $user, ?string $start = null, ?string $end = null): Collection
    {
        $projectsQuery = isAdminOrHasAllDataAccess() ? $workspace->projects() : $user->projects();

        // Apply date range filter
        if ($start && $end) {
            $projectsQuery->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end]);
            });
        }

        $projects = $projectsQuery->get();

        return $projects->map(function ($project) {
            $backgroundColor = $this->getStatusBackgroundColor($project->status->color);
            $title = $project->title . ' : ' . format_date($project->start_date);
            if ($project->end_date != $project->start_date) {
                $title .= ' ' . get_label('to', 'to') . ' ' . format_date($project->end_date);
            }

            return [
                'id' => $project->id,
                'project_info_url' => route('projects.info', ['id' => $project->id]),
                'title' => $title,
                'start' => $project->start_date,
                'end' => $project->end_date,
                'status_id' => $project->status_id,
                'priority_id' => $project->priority_id,
                'backgroundColor' => $backgroundColor,
                'borderColor' => '#ffffff',
                'textColor' => '#000000',
            ];
        });
    }

    /**
     * Get Gantt chart data for projects and tasks
     */
    public function getGanttData(Workspace $workspace, User $user, ?bool $favorite = false): Collection
    {
        $query = isAdminOrHasAllDataAccess() ? $workspace->projects()->with('tasks') : $user->projects()->with('tasks');

        // Apply favorite filter if necessary
        if ($favorite) {
            $favoriteProjectIds = $user->favoriteProjects()
                ->pluck('favoritable_id')
                ->toArray();
            $query->whereIn('projects.id', $favoriteProjectIds);
        }

        $projects = $query->get();

        // Filter projects with valid start and end dates
        $filteredProjects = $projects->filter(function ($project) {
            return !is_null($project->start_date) && !is_null($project->end_date);
        });

        // Filter tasks within each project for valid start and due dates
        $filteredProjects->each(function ($project) {
            $project->tasks = $project->tasks->filter(function ($task) {
                return !is_null($task->start_date) && !is_null($task->due_date);
            });
        });

        return $filteredProjects->values();
    }

    /**
     * Get background color based on status color
     */
    private function getStatusBackgroundColor(string $statusColor): string
    {
        return match ($statusColor) {
            'primary' => '#9bafff',
            'success' => '#a0e4a3',
            'danger' => '#ff6b5c',
            'warning' => '#ffca66',
            'info' => '#6ed4f0',
            'secondary' => '#aab0b8',
            'dark' => '#4f5b67',
            'light' => '#ffffff',
            default => '#5ab0ff',
        };
    }
}

