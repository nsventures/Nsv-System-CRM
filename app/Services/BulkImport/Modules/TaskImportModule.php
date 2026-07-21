<?php

namespace App\Services\BulkImport\Modules;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Services\BulkImport\ImportModuleInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class TaskImportModule implements ImportModuleInterface
{
    protected int $workspaceId;
    protected bool $isAdmin;
    protected string $guardName;
    protected int $authUserId;
    protected $authUser;

    public function __construct()
    {
        $this->workspaceId  = getWorkspaceId();
        $this->isAdmin      = isAdminOrHasAllDataAccess();
        $this->guardName    = getGuardName();
        $this->authUserId   = getAuthenticatedUser()->id;
        $this->authUser     = getAuthenticatedUser();
    }

    public function getModelClass(): string
    {
        return Task::class;
    }

    /*
    |--------------------------------------------------------------------------
    | Sheet-Level Validation (Before Import)
    |--------------------------------------------------------------------------
    */

    public function beforeImport(array $rows): array
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber  = $index + 2;
            $statusId   = $row['status_id'] ?? null;
            $projectId  = $row['project_id'] ?? null;
            $startDate  = $row['start_date'] ?? null;
            $endDate    = $row['end_date'] ?? null;
            $userIds    = $row['user_ids'] ?? null;
            $priorityId = $row['priority_id'] ?? null;
            $title      = $row['title'] ?? null;

            // Required fields
            if (empty($title)) {
                $errors[] = "Title is required at Row {$rowNumber}.";
            }

            if (empty($statusId)) {
                $errors[] = "Status ID is required at Row {$rowNumber}.";
            } else {
                $status = Status::find($statusId);
                if (!$status) {
                    $errors[] = "Invalid Status ID '{$statusId}' at Row {$rowNumber}.";
                } elseif (!canSetStatus($status)) {
                    $errors[] = "Not authorized to use Status ID '{$statusId}' at Row {$rowNumber}.";
                }
            }

            if (empty($projectId)) {
                $errors[] = "Project ID is required at Row {$rowNumber}.";
            } else {
                $project = Project::find($projectId);
                if (!$project) {
                    $errors[] = "Invalid Project ID '{$projectId}' at Row {$rowNumber}.";
                } elseif (!$this->isAdmin && !$this->authUser->projects->contains($projectId)) {
                    $errors[] = "You are not a participant of Project ID '{$projectId}' at Row {$rowNumber}.";
                }
            }

            // priority existence
            if (!empty($priorityId) && !DB::table('priorities')->where('id', $priorityId)->exists()) {
                $errors[] = "Invalid Priority ID '{$priorityId}' at Row {$rowNumber}.";
            }

            // Date range check
            if (!empty($startDate) && !empty($endDate)) {
                try {
                    $start = Carbon::parse($startDate);
                    $end   = Carbon::parse($endDate);
                    if ($start->gt($end)) {
                        $errors[] = "Start date must be before or equal to end date at Row {$rowNumber}.";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Invalid date format at Row {$rowNumber}. Expected: Y-m-d.";
                }
            }

            // user_ids: existence + project membership
            if (!empty($userIds)) {
                $ids = is_array($userIds)
                    ? $userIds
                    : array_map('trim', explode(',', $userIds));

                $ids = array_filter($ids);

                foreach ($ids as $id) {
                    if (!DB::table('users')->where('id', $id)->exists()) {
                        $errors[] = "Invalid User ID '{$id}' at Row {$rowNumber}.";
                        continue;
                    }

                    // Check user is participant of the project
                    if (!empty($projectId)) {
                        $project = Project::with('users')->find($projectId);
                        if ($project) {
                            $projectUserIds = $project->users->pluck('id')->toArray();
                            if (!in_array($id, $projectUserIds)) {
                                $errors[] = "User ID '{$id}' is not a participant of Project ID '{$projectId}' at Row {$rowNumber}.";
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | Row Transformation
    |--------------------------------------------------------------------------
    */

    public function transformRow(array $row, bool $isPreview = false): array
    {
        $row = $this->sanitize($row);

        // Parse user_ids into array
        if (!empty($row['user_ids'])) {
            $row['user_ids'] = is_array($row['user_ids'])
                ? array_map('trim', $row['user_ids'])
                : array_map('trim', explode(',', $row['user_ids']));
        } else {
            $row['user_ids'] = [];
        }

        // Ensure auth user is included if not admin
        if (!$isPreview && !$this->isAdmin) {
            if ($this->guardName === 'web' && !in_array($this->authUserId, $row['user_ids'])) {
                array_unshift($row['user_ids'], $this->authUserId);
            }
        }

        // Map end_date → due_date (task model uses due_date)
        $row['due_date']   = !empty($row['end_date']) ? $row['end_date'] : null;
        $row['start_date'] = !empty($row['start_date']) ? $row['start_date'] : null;

        $row['client_can_discuss'] = isset($row['client_can_discuss']) ? (bool)$row['client_can_discuss'] : false;
        $row['workspace_id']       = $this->workspaceId;
        $row['created_by']         = $this->authUserId;

        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | Row Validation Rules
    |--------------------------------------------------------------------------
    */

    public function getValidationRules(array $row): array
    {
        return [
            'title'              => 'required|string',
            'status_id'          => 'required',
            'project_id'         => 'required|exists:projects,id',
            'priority_id'        => 'nullable|exists:priorities,id',
            'start_date'         => 'nullable|date_format:Y-m-d',
            'due_date'           => 'nullable|date_format:Y-m-d',
            'client_can_discuss' => 'required|boolean',
            'description'        => 'nullable|string',
            'note'               => 'nullable|string',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | After Model Creation
    |--------------------------------------------------------------------------
    */

    public function afterCreate($task, array $row): void
    {
        try {
            // Attach users
            if (!empty($row['user_ids'])) {
                $task->users()->attach(array_filter($row['user_ids']));
            }

            // Handle favorites
            if (isset($row['is_favorite']) && $row['is_favorite'] == 1) {
                $this->authUser->favorites()->create([
                    'favoritable_type' => Task::class,
                    'favoritable_id'   => $task->id,
                ]);
            }

            // Notifications
            $notificationData = [
                'type'       => 'task',
                'type_id'    => $task->id,
                'type_title' => $task->title,
                'access_url' => 'tasks/information/' . $task->id,
                'action'     => 'assigned',
            ];

            $recipients = array_map(
                fn($id) => 'u_' . $id,
                $row['user_ids'] ?? []
            );

            if (!empty($recipients)) {
                processNotifications($notificationData, $recipients);
            }

            logActivity(
                'task',
                $task->id,
                $task->title,
                parentId: $row['project_id'],
                parentType: 'project'
            );

        } catch (Throwable $e) {
            $task->delete();
            throw new \Exception("Task post-processing failed: " . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function sanitize(array $row): array
    {
        return array_map(function ($value) {
            return is_string($value) ? trim(strip_tags($value)) : $value;
        }, $row);
    }
}