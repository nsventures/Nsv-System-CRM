<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;

class TaskCalendarService
{
    /**
     * Get calendar events for tasks
     */
    public function getCalendarEvents(Workspace $workspace, User $user, ?string $start = null, ?string $end = null, ?int $projectId = null, bool $isFavorites = false): Collection
    {
        // Build base query
        if ($projectId) {
            $project = Project::find($projectId);
            if ($project) {
                $tasksQuery = isAdminOrHasAllDataAccess() ? $project->tasks() : $user->project_tasks($projectId);
            } else {
                $tasksQuery = isAdminOrHasAllDataAccess() ? $workspace->tasks() : $user->tasks();
            }
        } else {
            $tasksQuery = isAdminOrHasAllDataAccess() ? $workspace->tasks() : $user->tasks();
        }

        // Apply date range filter
        if ($start && $end) {
            $tasksQuery->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('due_date', [$start, $end]);
            });
        }

        // Apply favorites filter
        if ($isFavorites) {
            $favoriteTaskIds = $user->favorites()
                ->where('favoritable_type', Task::class)
                ->pluck('favoritable_id')
                ->toArray();
            $tasksQuery->whereIn('tasks.id', $favoriteTaskIds);
        }

        $tasks = $tasksQuery->get();

        return $tasks->map(function ($task) {
            $backgroundColor = $this->getStatusBackgroundColor($task->status->color ?? 'primary');
            $title = $task->title . ' : ' . format_date($task->start_date);
            if ($task->due_date != $task->start_date) {
                $title .= ' ' . get_label('to', 'to') . ' ' . format_date($task->due_date);
            }

            return [
                'id' => $task->id,
                'tasks_info_url' => route('tasks.info', ['id' => $task->id]),
                'title' => $title,
                'start' => $task->start_date,
                'status_id' => $task->status_id,
                'priority_id' => $task->priority_id,
                'end' => $task->due_date,
                'backgroundColor' => $backgroundColor,
                'borderColor' => '#ffffff',
                'textColor' => '#000000',
            ];
        });
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











