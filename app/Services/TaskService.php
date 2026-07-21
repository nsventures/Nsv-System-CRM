<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Status;
use App\Models\User;
use App\Models\Workspace;
use App\Services\DeletionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class TaskService
{
    /**
     * Create a new task with all relationships and setup
     */
    public function createTask(Workspace $workspace, User $user, array $data, array $userIds = [], bool $isFavorite = false, array $customFields = [], bool $isApi = false): Task
    {
        try {
            // Handle priority_id = 0 means null
            if (isset($data['priority_id']) && $data['priority_id'] == 0) {
                $data['priority_id'] = null;
            }

            // Format dates
            if (!empty($data['start_date'])) {
                $data['start_date'] = format_date($data['start_date'], false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
            }
            if (!empty($data['due_date'])) {
                $data['due_date'] = format_date($data['due_date'], false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
            }

            // Set workspace and creator
            $data['workspace_id'] = $workspace->id;
            $data['created_by'] = $user->id;

            // Handle client_can_discuss
            $data['client_can_discuss'] = isAdminOrHasAllDataAccess() && isset($data['clientCanDiscuss']) && $data['clientCanDiscuss'] == 'on' ? 1 : 0;
            unset($data['clientCanDiscuss']);

            // Remove relationship fields from data array before creating
            unset($data['user_id']);

            // Create the task
            $task = Task::create($data);

            // Create status timeline entry
            if (isset($data['status_id'])) {
                $status = Status::find($data['status_id']);
                if ($status) {
                    $task->statusTimelines()->create([
                        'status' => $status->title,
                        'new_color' => $status->color,
                        'previous_status' => '-',
                        'changed_at' => now(),
                    ]);
                }
            }

            // Ensure creator is added as participant if not admin
            if (!isAdminOrHasAllDataAccess()) {
                $guardName = getGuardName();
                if ($guardName == 'web' && !in_array($user->id, $userIds)) {
                    array_unshift($userIds, $user->id);
                }
            }

            // Attach users
            if (!empty($userIds)) {
                $task->users()->attach($userIds);
            }

            // Handle favorite
            if ($isFavorite) {
                $user->favorites()->create([
                    'favoritable_type' => Task::class,
                    'favoritable_id' => $task->id,
                ]);
            }

            // Handle reminder
            if (isset($data['enable_reminder']) && $data['enable_reminder'] == 'on') {
                $task->reminders()->create([
                    'frequency_type' => $data['frequency_type'] ?? null,
                    'day_of_week' => $data['day_of_week'] ?? null,
                    'day_of_month' => $data['day_of_month'] ?? null,
                    'time_of_day' => $data['time_of_day'] ?? null,
                ]);
            }

            // Handle recurring task
            if (isset($data['enable_recurring_task']) && $data['enable_recurring_task'] == 'on') {
                $task->recurringTask()->create([
                    'frequency' => $data['recurrence_frequency'] ?? null,
                    'day_of_week' => $data['recurrence_day_of_week'] ?? null,
                    'day_of_month' => $data['recurrence_day_of_month'] ?? null,
                    'month_of_year' => $data['recurrence_month_of_year'] ?? null,
                    'starts_from' => $data['recurrence_starts_from'] ?? null,
                    'number_of_occurrences' => $data['recurrence_occurrences'] ?? null,
                ]);
            }

            // Store custom field values
            if (!empty($customFields)) {
                foreach ($customFields as $fieldId => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $task->customFields()->create([
                        'custom_field_id' => $fieldId,
                        'value' => $value
                    ]);
                }
            }

            // Send notifications
            $this->sendTaskAssignmentNotifications($task, $userIds);

            return $task->fresh();
        } catch (\Exception $e) {
            Log::error('Task creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing task
     */
    public function updateTask(Task $task, User $user, array $data, array $userIds = [], array $customFields = [], bool $isApi = false): Task
    {
        try {
            $currentStatusId = $task->status_id;
            $oldStatus = null;

            // Handle priority_id = 0 means null
            if (isset($data['priority_id']) && $data['priority_id'] == 0) {
                $data['priority_id'] = null;
            }

            // Format dates
            if (isset($data['start_date'])) {
                $data['start_date'] = $data['start_date']
                    ? format_date($data['start_date'], false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d')
                    : null;
            }
            if (isset($data['due_date'])) {
                $data['due_date'] = $data['due_date']
                    ? format_date($data['due_date'], false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d')
                    : null;
            }

            // Handle client_can_discuss
            if (isset($data['clientCanDiscuss'])) {
                $data['client_can_discuss'] = isAdminOrHasAllDataAccess()
                    ? ($data['clientCanDiscuss'] == 'on' ? 1 : 0)
                    : $task->client_can_discuss;
                unset($data['clientCanDiscuss']);
            }

            // Remove relationship fields from data array before updating
            unset($data['user_id']);

            // Check if status changed and create timeline entry
            if (isset($data['status_id']) && $currentStatusId != $data['status_id']) {
                $newStatus = Status::findOrFail($data['status_id']);
                $oldStatus = Status::findOrFail($currentStatusId);

                $task->statusTimelines()->create([
                    'status' => $newStatus->title,
                    'new_color' => $newStatus->color,
                    'previous_status' => $oldStatus->title,
                    'old_color' => $oldStatus->color,
                    'changed_at' => now()
                ]);
            }

            // Update task fields
            $task->update($data);

            // Handle reminder
            $this->handleReminder($task, $data);

            // Handle recurring task
            $this->handleRecurringTask($task, $data);

            // Sync users
            $currentUsers = $task->users->pluck('id')->toArray();
            $currentClients = $task->project->clients->pluck('id')->toArray();
            $task->users()->sync($userIds);
            $newUsers = array_diff($userIds, $currentUsers);

            // Update custom fields
            if (!empty($customFields)) {
                foreach ($customFields as $fieldId => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $fieldValue = $task->customFields()->where('custom_field_id', $fieldId)->first();
                    if ($fieldValue) {
                        $fieldValue->update(['value' => $value]);
                    } else {
                        $task->customFields()->create([
                            'custom_field_id' => $fieldId,
                            'value' => $value
                        ]);
                    }
                }
            }

            // Send notifications for new users
            if (!empty($newUsers)) {
                $this->sendTaskAssignmentNotifications($task, $newUsers);
            }

            // Send status change notifications
            if ($oldStatus && isset($data['status_id']) && $currentStatusId != $data['status_id']) {
                $this->sendTaskStatusChangeNotifications($task, $user, $oldStatus, Status::findOrFail($data['status_id']), $currentUsers, $currentClients);
            }

            return $task->fresh();
        } catch (\Exception $e) {
            Log::error('Task update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'task_id' => $task->id
            ]);
            throw $e;
        }
    }

    /**
     * Handle reminder creation/update/deletion
     */
    private function handleReminder(Task $task, array $data): void
    {
        if (isset($data['enable_reminder']) && $data['enable_reminder'] === 'on') {
            $reminder = $task->reminders()->first();
            $reminderData = [
                'frequency_type' => $data['frequency_type'] ?? null,
                'day_of_week' => ($data['frequency_type'] ?? '') === 'weekly' ? ($data['day_of_week'] ?? null) : null,
                'day_of_month' => ($data['frequency_type'] ?? '') === 'monthly' ? ($data['day_of_month'] ?? null) : null,
                'time_of_day' => $data['time_of_day'] ?? null,
                'is_active' => 1,
                'last_sent_at' => null,
            ];
            if ($reminder) {
                $reminder->update($reminderData);
            } else {
                $task->reminders()->create($reminderData);
            }
        } else {
            $reminder = $task->reminders()->first();
            if ($reminder) {
                $reminder->update(['is_active' => 0]);
            }
        }
    }

    /**
     * Handle recurring task creation/update/deletion
     */
    private function handleRecurringTask(Task $task, array $data): void
    {
        $enableRecurringTask = isset($data['enable_recurring_task']) && $data['enable_recurring_task'] === 'on';

        if ($enableRecurringTask) {
            $recurringTaskData = [
                'frequency' => $data['recurrence_frequency'] ?? null,
                'day_of_week' => $data['recurrence_day_of_week'] ?? null,
                'day_of_month' => $data['recurrence_day_of_month'] ?? null,
                'month_of_year' => $data['recurrence_month_of_year'] ?? null,
                'starts_from' => $data['recurrence_starts_from'] ?? null,
                'number_of_occurrences' => $data['recurrence_occurrences'] ?? null,
            ];
            if ($task->recurringTask) {
                $task->recurringTask->update($recurringTaskData);
            } else {
                $task->recurringTask()->create($recurringTaskData);
            }
        } elseif ($task->recurringTask) {
            $task->recurringTask->delete();
        }
    }

    /**
     * Delete a task and all related data
     */
    public function deleteTask(Task $task): array
    {
        try {
            // Get task info before deletion
            $taskId = $task->id;
            $taskTitle = $task->title;
            $projectId = $task->project_id;

            // Delete comment attachments
            $comments = $task->comments()->with('attachments')->get();
            foreach ($comments as $comment) {
                foreach ($comment->attachments as $attachment) {
                    Storage::disk('public')->delete($attachment->file_path);
                    $attachment->delete();
                }
            }

            // Delete related records
            $task->favorites()->delete();
            $task->pinned()->delete();
            $task->comments()->forceDelete();
            $task->notificationsForTask()->delete();

            // Use DeletionService for main deletion
            $response = DeletionService::delete(Task::class, $taskId, 'Task');
            $responseData = json_decode($response->getContent(), true);

            if ($responseData['error']) {
                return $responseData;
            }

            return [
                'error' => false,
                'message' => 'Task deleted successfully.',
                'id' => $taskId,
                'title' => $taskTitle,
                'parent_id' => $projectId,
                'parent_type' => 'project',
                'data' => []
            ];
        } catch (\Exception $e) {
            Log::error('Task deletion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'task_id' => $task->id
            ]);
            throw $e;
        }
    }

    /**
     * Delete multiple tasks
     */
    public function deleteMultipleTasks(array $ids): array
    {
        $deletedTasks = [];
        $deletedTaskTitles = [];
        $parentIds = [];

        foreach ($ids as $id) {
            $task = Task::find($id);
            if ($task) {
                $deletedTaskTitles[] = $task->title;

                // Delete comment attachments
                $comments = $task->comments()->with('attachments')->get();
                foreach ($comments as $comment) {
                    foreach ($comment->attachments as $attachment) {
                        Storage::disk('public')->delete($attachment->file_path);
                        $attachment->delete();
                    }
                }

                // Delete related records
                $task->favorites()->delete();
                $task->pinned()->delete();
                $task->comments()->forceDelete();
                $task->notificationsForTask()->delete();

                // Use DeletionService
                DeletionService::delete(Task::class, $id, 'Task');

                $deletedTasks[] = $id;
                $parentIds[] = $task->project_id;
            }
        }

        return [
            'error' => false,
            'message' => 'Task(s) deleted successfully.',
            'id' => $deletedTasks,
            'titles' => $deletedTaskTitles,
            'parent_id' => $parentIds,
            'parent_type' => 'project'
        ];
    }

    /**
     * Duplicate a task
     */
    public function duplicateTask(int $taskId, ?string $title = null): ?Task
    {
        $relatedTables = ['users'];
        $duplicate = duplicateRecord(Task::class, $taskId, $relatedTables, $title);

        if (!$duplicate) {
            return null;
        }

        if (request()->has('reload') && request()->input('reload') === 'true') {
            Session::flash('message', 'Task duplicated successfully.');
        }

        return $duplicate;
    }

    /**
     * Update task status
     */
    public function updateStatus(Task $task, Status $status, User $user): Task
    {
        $oldStatus = Status::findOrFail($task->status_id);

        $task->statusTimelines()->create([
            'status' => $status->title,
            'new_color' => $status->color,
            'previous_status' => $oldStatus->title,
            'old_color' => $oldStatus->color,
            'changed_at' => now()
        ]);

        $task->update(['status_id' => $status->id]);

        // Send notifications
        $currentUsers = $task->users->pluck('id')->toArray();
        $currentClients = $task->project->clients->pluck('id')->toArray();
        $this->sendTaskStatusChangeNotifications($task, $user, $oldStatus, $status, $currentUsers, $currentClients);

        return $task->fresh();
    }

    /**
     * Update task priority
     */
    public function updatePriority(Task $task, ?int $priorityId): Task
    {
        $task->update(['priority_id' => $priorityId == 0 ? null : $priorityId]);
        return $task->fresh();
    }

    /**
     * Update task favorite status
     */
    public function updateFavorite(Task $task, User $user, bool $isFavorite): bool
    {
        if ($isFavorite) {
            $user->favorites()->firstOrCreate([
                'favoritable_type' => Task::class,
                'favoritable_id' => $task->id,
            ]);
        } else {
            $user->favorites()
                ->where('favoritable_type', Task::class)
                ->where('favoritable_id', $task->id)
                ->delete();
        }
        return $isFavorite;
    }

    /**
     * Update task pinned status
     */
    public function updatePinned(Task $task, User $user, bool $isPinned): bool
    {
        if ($isPinned) {
            $user->pinnedTasks()->firstOrCreate([
                'pinnable_type' => Task::class,
                'pinnable_id' => $task->id,
            ]);
        } else {
            $user->pinnedTasks()
                ->where('pinnable_type', Task::class)
                ->where('pinnable_id', $task->id)
                ->delete();
        }
        return $isPinned;
    }

    /**
     * Update task dates
     */
    public function updateTaskDates(Task $task, ?string $startDate, ?string $dueDate, bool $isApi = false): Task
    {
        $data = [];

        if ($startDate !== null) {
            $data['start_date'] = $startDate
                ? format_date($startDate, false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d')
                : null;
        }

        if ($dueDate !== null) {
            $data['due_date'] = $dueDate
                ? format_date($dueDate, false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d')
                : null;
        }

        $task->update($data);
        return $task->fresh();
    }

    /**
     * Send notifications for task assignment
     */
    private function sendTaskAssignmentNotifications(Task $task, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        $notificationData = [
            'type' => 'task',
            'type_id' => $task->id,
            'type_title' => $task->title,
            'access_url' => 'tasks/information/' . $task->id,
            'action' => 'assigned'
        ];

        $recipients = array_map(fn($userId) => 'u_' . $userId, $userIds);
        processNotifications($notificationData, $recipients);
    }

    /**
     * Send notifications for task status change
     */
    private function sendTaskStatusChangeNotifications(Task $task, User $user, Status $oldStatus, Status $newStatus, array $userIds, array $clientIds = []): void
    {
        $notificationData = [
            'type' => 'task_status_updation',
            'type_id' => $task->id,
            'type_title' => $task->title,
            'updater_first_name' => $user->first_name,
            'updater_last_name' => $user->last_name,
            'old_status' => $oldStatus->title,
            'new_status' => $newStatus->title,
            'access_url' => 'tasks/information/' . $task->id,
            'action' => 'status_updated'
        ];

        $recipients = array_merge(
            array_map(fn($userId) => 'u_' . $userId, $userIds),
            array_map(fn($clientId) => 'c_' . $clientId, $clientIds)
        );

        if (!empty($recipients)) {
            processNotifications($notificationData, $recipients);
        }
    }
}

