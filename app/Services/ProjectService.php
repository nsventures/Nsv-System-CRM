<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    /**
     * Create a new project with all relationships and setup
     */
    public function createProject(Workspace $workspace, User $user, array $data, array $userIds = [], array $clientIds = [], array $tagIds = [], bool $isFavorite = false, array $customFields = [], bool $isApi = false): Project
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
            if (!empty($data['end_date'])) {
                $data['end_date'] = format_date($data['end_date'], false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
            }

            // Format budget (remove commas)
            if (isset($data['budget'])) {
                $data['budget'] = str_replace(',', '', $data['budget']);
            }

            // Set workspace and creator
            $data['workspace_id'] = $workspace->id;
            $data['created_by'] = $user->id;

            // Handle client_can_discuss
            if (isset($data['clientCanDiscuss'])) {
                $data['client_can_discuss'] = isAdminOrHasAllDataAccess()
                    ? ($data['clientCanDiscuss'] == 'on' ? 1 : 0)
                    : 0;
                unset($data['clientCanDiscuss']);
            } elseif (!isset($data['client_can_discuss'])) {
                $data['client_can_discuss'] = 0;
            }

            // Remove relationship fields from data array before creating
            unset($data['user_id'], $data['client_id'], $data['tag_ids']);

            // Create the project
            $project = Project::create($data);

            // Ensure creator is added as participant if not admin
            if (!isAdminOrHasAllDataAccess()) {
                $guardName = getGuardName();
                if ($guardName == 'client' && !in_array($user->id, $clientIds)) {
                    array_unshift($clientIds, $user->id);
                } elseif ($guardName == 'web' && !in_array($user->id, $userIds)) {
                    array_unshift($userIds, $user->id);
                }
            }

            // Attach relationships
            if (!empty($userIds)) {
                $project->users()->attach($userIds);
            }
            if (!empty($clientIds)) {
                $project->clients()->attach($clientIds);
            }
            if (!empty($tagIds)) {
                $project->tags()->attach($tagIds);
            }

            // Handle favorite
            if ($isFavorite) {
                $user->favorites()->create([
                    'favoritable_type' => Project::class,
                    'favoritable_id' => $project->id,
                ]);
            }

            // Create status timeline entry
            if (isset($data['status_id'])) {
                $status = Status::find($data['status_id']);
                if ($status) {
                    $project->statusTimelines()->create([
                        'status' => $status->title,
                        'new_color' => $status->color,
                        'previous_status' => '-',
                        'changed_at' => now(),
                    ]);
                }
            }

            // Store custom field values
            if (!empty($customFields)) {
                foreach ($customFields as $fieldId => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $project->customFieldValues()->create([
                        'custom_field_id' => $fieldId,
                        'value' => $value
                    ]);
                }
            }

            // Send notifications
            $this->sendProjectAssignmentNotifications($project, $userIds, $clientIds);

            return $project->fresh();
        } catch (\Exception $e) {
            Log::error('Project creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Send notifications for project assignment
     */
    private function sendProjectAssignmentNotifications(Project $project, array $userIds, array $clientIds): void
    {
        $notificationData = [
            'type' => 'project',
            'type_id' => $project->id,
            'type_title' => $project->title,
            'access_url' => 'projects/information/' . $project->id,
            'action' => 'assigned'
        ];

        $recipients = array_merge(
            array_map(fn($userId) => 'u_' . $userId, $userIds),
            array_map(fn($clientId) => 'c_' . $clientId, $clientIds)
        );

        if (!empty($recipients)) {
            processNotifications($notificationData, $recipients);
        }
    }

    /**
     * Update an existing project
     */
    public function updateProject(Project $project, User $user, array $data, array $userIds = [], array $clientIds = [], array $tagIds = [], array $customFields = [], bool $isApi = false): Project
    {
        try {
            $currentStatusId = $project->status_id;

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
            if (isset($data['end_date'])) {
                $data['end_date'] = $data['end_date']
                    ? format_date($data['end_date'], false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d')
                    : null;
            }

            // Format budget
            if (isset($data['budget'])) {
                $data['budget'] = str_replace(',', '', $data['budget']);
            }

            // Handle client_can_discuss
            if (isset($data['clientCanDiscuss'])) {
                $data['client_can_discuss'] = isAdminOrHasAllDataAccess()
                    ? ($data['clientCanDiscuss'] == 'on' ? 1 : 0)
                    : $project->client_can_discuss;
                unset($data['clientCanDiscuss']);
            }

            // Track status change for timeline
            if (isset($data['status_id']) && $currentStatusId != $data['status_id']) {
                $oldStatus = Status::find($currentStatusId);
                $newStatus = Status::find($data['status_id']);

                if ($oldStatus && $newStatus) {
                    $project->statusTimelines()->create([
                        'status' => $newStatus->title,
                        'new_color' => $newStatus->color,
                        'previous_status' => $oldStatus->title,
                        'old_color' => $oldStatus->color,
                        'changed_at' => now(),
                    ]);
                }
            }

            // Get existing relationships for notification diff
            $existingUserIds = $project->users->pluck('id')->toArray();
            $existingClientIds = $project->clients->pluck('id')->toArray();

            // Remove relationship fields before updating
            unset($data['user_id'], $data['client_id'], $data['tag_ids']);

            // Update project
            $project->update($data);

            // Sync relationships
            if (isset($userIds)) {
                $project->users()->sync($userIds);
            }
            if (isset($clientIds)) {
                $project->clients()->sync($clientIds);
            }
            if (isset($tagIds)) {
                $project->tags()->sync($tagIds);
            }

            // Update custom field values
            if (!empty($customFields)) {
                foreach ($customFields as $fieldId => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }

                    $fieldValue = $project->customFieldValues()
                        ->where('custom_field_id', $fieldId)
                        ->first();

                    if ($fieldValue) {
                        $fieldValue->update(['value' => $value]);
                    } else {
                        $project->customFieldValues()->create([
                            'custom_field_id' => $fieldId,
                            'value' => $value
                        ]);
                    }
                }
            }

            // Send notifications for new assignments
            $newUserIds = array_diff($userIds, $existingUserIds);
            $newClientIds = array_diff($clientIds, $existingClientIds);
            $this->sendProjectAssignmentNotifications($project, $newUserIds, $newClientIds);

            // Send status change notifications
            if (isset($data['status_id']) && $currentStatusId != $data['status_id']) {
                $this->sendStatusChangeNotifications($project, $user, $currentStatusId, $data['status_id'], $existingUserIds, $existingClientIds);
            }

            return $project->fresh();
        } catch (\Exception $e) {
            Log::error('Project update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'project_id' => $project->id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Send notifications for status change
     */
    private function sendStatusChangeNotifications(Project $project, User $updater, int $oldStatusId, int $newStatusId, array $userIds, array $clientIds): void
    {
        $oldStatus = Status::find($oldStatusId);
        $newStatus = Status::find($newStatusId);

        if (!$oldStatus || !$newStatus) {
            return;
        }

        $notificationData = [
            'type' => 'project_status_updation',
            'type_id' => $project->id,
            'type_title' => $project->title,
            'updater_first_name' => $updater->first_name,
            'updater_last_name' => $updater->last_name,
            'old_status' => $oldStatus->title,
            'new_status' => $newStatus->title,
            'access_url' => 'projects/information/' . $project->id,
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

    /**
     * Delete a project and all its related data
     */
    public function deleteProject(Project $project): bool
    {
        try {
            // Get all attachments before deletion
            $comments = $project->comments()->with('attachments')->get();

            // Delete all comment attachment files
            $comments->each(function ($comment) {
                $comment->attachments->each(function ($attachment) {
                    Storage::disk('public')->delete($attachment->file_path);
                    $attachment->delete();
                });
            });

            // Delete associated favorites
            $project->favorites()->delete();

            // Delete all pinned records
            $project->pinned()->delete();

            // Force delete comments
            $project->comments()->forceDelete();

            // Delete project notifications
            $project->notificationsForProject()->delete();

            // Delete the project itself (handled by DeletionService in controller)
            return true;
        } catch (\Exception $e) {
            Log::error('Project cleanup error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'project_id' => $project->id
            ]);
            throw $e;
        }
    }

    /**
     * Update favorite status for a project
     */
    public function updateFavorite(Project $project, User $user, bool $isFavorite): Project
    {
        $favorite = $user->favorites()
            ->where('favoritable_type', Project::class)
            ->where('favoritable_id', $project->id)
            ->first();

        if ($isFavorite) {
            if (!$favorite) {
                $user->favorites()->create([
                    'favoritable_type' => Project::class,
                    'favoritable_id' => $project->id,
                ]);
            }
        } else {
            if ($favorite) {
                $favorite->delete();
            }
        }

        return $project->fresh();
    }

    /**
     * Update pinned status for a project
     */
    public function updatePinned(Project $project, User $user, bool $isPinned): array
    {
        $pinned = $user->pinnedProjects()
            ->where('pinnable_id', $project->id)
            ->first();

        if ($isPinned) {
            if (!$pinned) {
                $user->pinnedProjects()->create([
                    'pinnable_type' => Project::class,
                    'pinnable_id' => $project->id,
                ]);
                $message = 'Pinned Successfully.';
            } else {
                $message = 'Already pinned.';
            }
        } else {
            if ($pinned) {
                $pinned->delete();
                $message = 'Unpinned Successfully.';
            } else {
                $message = 'Already unpinned.';
            }
        }

        return [
            'project' => $project->fresh(),
            'message' => $message
        ];
    }

    /**
     * Update project status
     */
    public function updateStatus(Project $project, User $user, Status $newStatus, ?string $note = null): array
    {
        $oldStatus = Status::find($project->status_id);
        $currentStatusTitle = $project->status->title;

        if ($project->status_id != $newStatus->id) {
            $project->status_id = $newStatus->id;
            if ($note !== null) {
                $project->note = $note;
            }
            $project->save();

            // Create status timeline entry
            if ($oldStatus) {
                $project->statusTimelines()->create([
                    'status' => $newStatus->title,
                    'new_color' => $newStatus->color,
                    'previous_status' => $oldStatus->title,
                    'old_color' => $oldStatus->color,
                    'changed_at' => now(),
                ]);
            }

            // Reload to get updated status
            $project = $project->fresh();
            $newStatusTitle = $project->status->title;

            // Send notifications
            $userIds = $project->users->pluck('id')->toArray();
            $clientIds = $project->clients->pluck('id')->toArray();

            $notificationData = [
                'type' => 'project_status_updation',
                'type_id' => $project->id,
                'type_title' => $project->title,
                'updater_first_name' => $user->first_name,
                'updater_last_name' => $user->last_name,
                'old_status' => $currentStatusTitle,
                'new_status' => $newStatusTitle,
                'access_url' => 'projects/information/' . $project->id,
                'action' => 'status_updated'
            ];

            $recipients = array_merge(
                array_map(fn($userId) => 'u_' . $userId, $userIds),
                array_map(fn($clientId) => 'c_' . $clientId, $clientIds)
            );

            if (!empty($recipients)) {
                processNotifications($notificationData, $recipients);
            }

            $activityMessage = trim($user->first_name) . ' ' . trim($user->last_name) .
                ' updated project status from ' . trim($currentStatusTitle) . ' to ' . trim($newStatusTitle);

            return [
                'project' => $project,
                'activity_message' => $activityMessage
            ];
        }

        return [
            'project' => $project,
            'activity_message' => null
        ];
    }

    /**
     * Update project priority
     */
    public function updatePriority(Project $project, User $user, ?int $priorityId): array
    {
        $currentPriority = $project->priority ? $project->priority->title : '-';

        if ($project->priority_id != $priorityId) {
            $project->priority_id = $priorityId;
            $project->save();

            $project = $project->fresh();
            $newPriority = $project->priority ? $project->priority->title : '-';

            $activityMessage = trim($user->first_name) . ' ' . trim($user->last_name) .
                ' updated project priority from ' . trim($currentPriority) . ' to ' . trim($newPriority);

            return [
                'project' => $project,
                'activity_message' => $activityMessage
            ];
        }

        return [
            'project' => $project,
            'activity_message' => null
        ];
    }

    /**
     * Duplicate a project with related tables
     */
    public function duplicateProject(int $projectId, ?string $title = null): ?Project
    {
        $relatedTables = ['users', 'clients', 'tasks', 'tags'];
        $duplicate = duplicateRecord(Project::class, $projectId, $relatedTables, $title ?? '');

        if ($duplicate) {
            return Project::find($duplicate->id);
        }

        return null;
    }

    /**
     * Update project dates
     */
    public function updateProjectDates(Project $project, string $startDate, string $endDate, bool $isApi = false): Project
    {
        $project->start_date = format_date($startDate, false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
        $project->end_date = format_date($endDate, false, $isApi ? 'Y-m-d' : app('php_date_format'), 'Y-m-d');
        $project->save();

        return $project->fresh();
    }
}

