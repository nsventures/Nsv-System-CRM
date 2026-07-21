<?php

namespace App\Services\BulkImport\Modules;

use App\Models\Project;
use App\Models\Status;
use App\Services\BulkImport\ImportModuleInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProjectImportModule implements ImportModuleInterface
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
        return Project::class;
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
            $title      = $row['title'] ?? null;
            $statusId   = $row['status_id'] ?? null;
            $startDate  = $row['start_date'] ?? null;
            $endDate    = $row['end_date'] ?? null;
            $userIds    = $row['user_ids'] ?? null;
            $clientIds  = $row['client_ids'] ?? null;
            $tagIds     = $row['tag_ids'] ?? null;
            $priorityId = $row['priority_id'] ?? null;
            $taskAccess = $row['task_accessibility'] ?? null;

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

            // task_accessibility
            if (!empty($taskAccess) && !in_array($taskAccess, ['project_users', 'assigned_users'])) {
                $errors[] = "Invalid task_accessibility '{$taskAccess}' at Row {$rowNumber}. Allowed: project_users, assigned_users.";
            }

            // priority_id existence
            if (!empty($priorityId) && !DB::table('priorities')->where('id', $priorityId)->exists()) {
                $errors[] = "Invalid Priority ID '{$priorityId}' at Row {$rowNumber}.";
            }

            // Date validation
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

            // user_ids existence
            if (!empty($userIds)) {
                $ids = is_array($userIds) ? $userIds : array_map('trim', explode(',', $userIds));
                foreach (array_filter($ids) as $id) {
                    if (!DB::table('users')->where('id', $id)->exists()) {
                        $errors[] = "Invalid User ID '{$id}' at Row {$rowNumber}.";
                    }
                }
            }

            // client_ids existence
            if (!empty($clientIds)) {
                $ids = is_array($clientIds) ? $clientIds : array_map('trim', explode(',', $clientIds));
                foreach (array_filter($ids) as $id) {
                    if (!DB::table('clients')->where('id', $id)->exists()) {
                        $errors[] = "Invalid Client ID '{$id}' at Row {$rowNumber}.";
                    }
                }
            }

            // tag_ids existence
            if (!empty($tagIds)) {
                $ids = is_array($tagIds) ? $tagIds : array_map('trim', explode(',', $tagIds));
                foreach (array_filter($ids) as $id) {
                    if (!DB::table('tags')->where('id', $id)->exists()) {
                        $errors[] = "Invalid Tag ID '{$id}' at Row {$rowNumber}.";
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

        // Parse comma-separated ID fields into arrays
        foreach (['user_ids', 'client_ids', 'tag_ids'] as $field) {
            if (isset($row[$field]) && !empty($row[$field])) {
                $row[$field] = is_array($row[$field])
                    ? array_map('trim', $row[$field])
                    : array_map('trim', explode(',', $row[$field]));
            } else {
                $row[$field] = [];
            }
        }

        // Clean budget
        if (isset($row['budget'])) {
            $row['budget'] = str_replace(',', '', $row['budget']) ?: null;
        }

        // Defaults
        $row['task_accessibility'] = $row['task_accessibility'] ?? 'project_users';
        $row['client_can_discuss'] = isset($row['client_can_discuss']) ? (bool)$row['client_can_discuss'] : false;
        $row['workspace_id']       = $this->workspaceId;
        $row['created_by']         = $this->authUserId;

        // Ensure authenticated user is included
        if (!$isPreview && !$this->isAdmin) {
            if ($this->guardName === 'client' && !in_array($this->authUserId, $row['client_ids'])) {
                array_unshift($row['client_ids'], $this->authUserId);
            } elseif ($this->guardName === 'web' && !in_array($this->authUserId, $row['user_ids'])) {
                array_unshift($row['user_ids'], $this->authUserId);
            }
        }

        // Null out empty dates
        $row['start_date'] = !empty($row['start_date']) ? $row['start_date'] : null;
        $row['end_date']   = !empty($row['end_date']) ? $row['end_date'] : null;

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
            'priority_id'        => 'nullable|exists:priorities,id',
            'start_date'         => 'nullable|date_format:Y-m-d',
            'end_date'           => 'nullable|date_format:Y-m-d',
            'budget'             => 'nullable|numeric|min:0',
            'task_accessibility' => 'required|in:project_users,assigned_users',
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

    public function afterCreate($project, array $row): void
    {
        try {
            // Attach relationships
            if (!empty($row['user_ids'])) {
                $project->users()->attach(array_filter($row['user_ids']));
            }
            if (!empty($row['client_ids'])) {
                $project->clients()->attach(array_filter($row['client_ids']));
            }
            if (!empty($row['tag_ids'])) {
                $project->tags()->attach(array_filter($row['tag_ids']));
            }

            // Handle favorites
            if (isset($row['is_favorite']) && $row['is_favorite'] == 1) {
                $this->authUser->favorites()->create([
                    'favoritable_type' => Project::class,
                    'favoritable_id'   => $project->id,
                ]);
            }

            // Notifications
            $notificationData = [
                'type'       => 'project',
                'type_id'    => $project->id,
                'type_title' => $project->title,
                'access_url' => 'projects/information/' . $project->id,
                'action'     => 'assigned',
            ];

            $recipients = array_merge(
                array_map(fn($id) => 'u_' . $id, $row['user_ids'] ?? []),
                array_map(fn($id) => 'c_' . $id, $row['client_ids'] ?? [])
            );

            if (!empty($recipients)) {
                processNotifications($notificationData, $recipients);
            }

            logActivity('project', $project->id, $project->title);

        } catch (Throwable $e) {
            $project->delete();
            throw new \Exception("Project post-processing failed: " . $e->getMessage());
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