<?php

namespace App\Imports;

use App\Models\Project;
use App\Models\Status;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Carbon\Carbon;

class ProjectsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithEvents
{
    use SkipsFailures;
    private $workspaceId;
    private $authenticatedUserId;
    private $authenticatedUser;
    private $guardName;
    private $isAdminOrHasAllDataAccess;
    // To store validation errors
    public $validationErrors = [];
    public static $manualValidationErrors = [];
    private $currentRowNumber = 1; // Track current row for better error reporting

    public function __construct()
    {
        $this->workspaceId = getWorkspaceId();
        $this->authenticatedUserId = getAuthenticatedUser()->id;
        $this->authenticatedUser = getAuthenticatedUser();
        $this->guardName = getGuardName();
        $this->isAdminOrHasAllDataAccess = isAdminOrHasAllDataAccess();
    }

    public function model(array $row)
    {
        $this->currentRowNumber++;

        // REMOVE THIS CONDITION - it was preventing all imports if ANY validation error existed
        // if (!empty(self::$manualValidationErrors)) {
        //     return;
        // }

        $row = $this->sanitizeAndTrim($row);

        // Skip empty rows
        if (empty($row['title']) || empty($row['status_id'])) {
            return null;
        }

        // Validate individual row manually before processing
        $rowErrors = $this->validateIndividualRow($row, $this->currentRowNumber);
        if (!empty($rowErrors)) {
            // Add row-specific errors to validation errors
            $this->validationErrors = array_merge($this->validationErrors, $rowErrors);
            return null; // Skip this row but continue with others
        }

        try {
            // Pre-process row data with better error handling
            $data = [
                'title' => $row['title'],
                'status_id' => $row['status_id'],
                'priority_id' => $row['priority_id'] ?? null,
                'start_date' => isset($row['start_date']) && !empty($row['start_date']) ? $row['start_date'] : null,
                'end_date' => isset($row['end_date']) && !empty($row['end_date']) ? $row['end_date'] : null,
                'budget' => isset($row['budget']) ? str_replace(',', '', $row['budget']) : null,
                'task_accessibility' => $row['task_accessibility'] ?? 'project_users',
                'description' => $row['description'] ?? null,
                'note' => $row['note'] ?? null,
                'user_ids' => isset($row['user_ids']) && !empty($row['user_ids']) ?
                    (is_array($row['user_ids']) ? array_map('trim', $row['user_ids']) : array_map('trim', explode(',', $row['user_ids']))) : [],
                'client_ids' => isset($row['client_ids']) && !empty($row['client_ids']) ?
                    (is_array($row['client_ids']) ? array_map('trim', $row['client_ids']) : array_map('trim', explode(',', $row['client_ids']))) : [],
                'tag_ids' => isset($row['tag_ids']) && !empty($row['tag_ids']) ?
                    (is_array($row['tag_ids']) ? array_map('trim', $row['tag_ids']) : array_map('trim', explode(',', $row['tag_ids']))) : [],
                'client_can_discuss' => isset($row['client_can_discuss']) ? (bool)$row['client_can_discuss'] : false,
                'workspace_id' => $this->workspaceId,
                'created_by' => $this->authenticatedUserId
            ];

            // Additional user/client assignment logic
            if (!$this->isAdminOrHasAllDataAccess) {
                if ($this->guardName == 'client' && !in_array($this->authenticatedUserId, $data['client_ids'])) {
                    array_unshift($data['client_ids'], $this->authenticatedUserId);
                } else if ($this->guardName == 'web' && !in_array($this->authenticatedUserId, $data['user_ids'])) {
                    array_unshift($data['user_ids'], $this->authenticatedUserId);
                }
            }

            // Create the project
            $new_project = Project::create($data);

            // Attach relationships
            if (!empty($data['user_ids'])) {
                $new_project->users()->attach(array_filter($data['user_ids']));
            }
            if (!empty($data['client_ids'])) {
                $new_project->clients()->attach(array_filter($data['client_ids']));
            }
            if (!empty($data['tag_ids'])) {
                $new_project->tags()->attach(array_filter($data['tag_ids']));
            }

            // Handle favorites
            if (isset($row['is_favorite']) && $row['is_favorite'] == 1) {
                $this->authenticatedUser->favorites()->create([
                    'favoritable_type' => Project::class,
                    'favoritable_id' => $new_project->id,
                ]);
            }

            // Send notifications
            $notification_data = [
                'type' => 'project',
                'type_id' => $new_project->id,
                'type_title' => $new_project->title,
                'access_url' => 'projects/information/' . $new_project->id,
                'action' => 'assigned'
            ];

            $recipients = array_merge(
                array_map(fn($userId) => 'u_' . $userId, $data['user_ids']),
                array_map(fn($clientId) => 'c_' . $clientId, $data['client_ids'])
            );

            if (!empty($recipients)) {
                processNotifications($notification_data, $recipients);
            }

            logActivity('project', $new_project->id, $new_project->title);

            return $new_project;
        } catch (\Exception $e) {
            $this->validationErrors[] = "Error creating project at row {$this->currentRowNumber}: " . $e->getMessage();
            return null;
        }
    }

    private function validateIndividualRow($row, $rowNumber)
    {
        $errors = [];

        // Check required fields
        if (empty($row['title'])) {
            $errors[] = "Title is required at Row {$rowNumber}.";
        }

        if (empty($row['status_id'])) {
            $errors[] = "Status ID is required at Row {$rowNumber}.";
        } else {
            // Validate status exists and user can set it
            $status = Status::find($row['status_id']);
            if (!$status) {
                $errors[] = "Invalid Status ID {$row['status_id']} at Row {$rowNumber}. Status does not exist.";
            } elseif (!canSetStatus($status)) {
                $errors[] = "Not authorized to set status ID {$row['status_id']} at Row {$rowNumber}.";
            }
        }

        // Validate dates
        if (!empty($row['start_date']) && !empty($row['end_date'])) {
            try {
                $startDate = Carbon::parse($row['start_date']);
                $endDate = Carbon::parse($row['end_date']);
                if ($startDate->gt($endDate)) {
                    $errors[] = "Start date must be less than or equal to end date at Row {$rowNumber}.";
                }
            } catch (\Exception $e) {
                $errors[] = "Invalid date format at Row {$rowNumber}.";
            }
        }

        // Validate IDs exist
        if (!empty($row['user_ids'])) {
            $userIds = is_array($row['user_ids']) ? $row['user_ids'] : array_map('trim', explode(',', $row['user_ids']));
            foreach ($userIds as $userId) {
                if (!empty($userId) && !DB::table('users')->where('id', $userId)->exists()) {
                    $errors[] = "Invalid user ID {$userId} at Row {$rowNumber}. User does not exist.";
                }
            }
        }

        if (!empty($row['client_ids'])) {
            $clientIds = is_array($row['client_ids']) ? $row['client_ids'] : array_map('trim', explode(',', $row['client_ids']));
            foreach ($clientIds as $clientId) {
                if (!empty($clientId) && !DB::table('clients')->where('id', $clientId)->exists()) {
                    $errors[] = "Invalid client ID {$clientId} at Row {$rowNumber}. Client does not exist.";
                }
            }
        }

        if (!empty($row['tag_ids'])) {
            $tagIds = is_array($row['tag_ids']) ? $row['tag_ids'] : array_map('trim', explode(',', $row['tag_ids']));
            foreach ($tagIds as $tagId) {
                if (!empty($tagId) && !DB::table('tags')->where('id', $tagId)->exists()) {
                    $errors[] = "Invalid tag ID {$tagId} at Row {$rowNumber}. Tag does not exist.";
                }
            }
        }

        return $errors;
    }

    private function sanitizeAndTrim(array $row): array
    {
        $allowedTags = '
            <a><abbr><acronym><address><b><bdo><blockquote><br><caption><cite>
            <code><col><colgroup><dd><del><dfn><div><dl><dt><em><h1><h2><h3><h4>
            <h5><h6><hr><i><img><ins><kbd><label><legend><li><object><ol><p>
            <pre><q><s><samp><small><span><strike><strong><sub><sup><table>
            <tbody><td><tfoot><th><thead><tr><tt><u><ul><var>';

        return Arr::map($row, function ($value, $key) use ($allowedTags) {
            // Handle array values (when CSV parser converts comma-separated values to arrays)
            if (is_array($value)) {
                // Convert array back to comma-separated string for ID fields
                if (in_array($key, ['user_ids', 'client_ids', 'tag_ids'])) {
                    return implode(',', array_map('trim', $value));
                }
                // For other arrays, take the first value
                return isset($value[0]) ? trim(strip_tags($value[0], $allowedTags)) : '';
            }

            if (is_string($value)) {
                return trim(strip_tags($value, $allowedTags));
            }

            return $value;
        });
    }

    public function rules(): array
    {
        return [
            'title' => 'required',
            'status_id' => 'required',
            'priority_id' => 'nullable|exists:priorities,id',
            'start_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today'
            ],
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'budget' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $error = validate_currency_format($value, 'budget');
                        if ($error) {
                            $fail($error);
                        }
                    }
                }
            ],
            'task_accessibility' => 'required|in:project_users,assigned_users',
            // Remove string requirement and handle arrays/strings in validation
            'user_ids' => ['nullable', function ($attribute, $value, $fail) {
                if (!empty($value)) {
                    $this->validateCommaSeparatedIds($value, 'users', $attribute, $fail);
                }
            }],
            'client_ids' => ['nullable', function ($attribute, $value, $fail) {
                if (!empty($value)) {
                    $this->validateCommaSeparatedIds($value, 'clients', $attribute, $fail);
                }
            }],
            'tag_ids' => ['nullable', function ($attribute, $value, $fail) {
                if (!empty($value)) {
                    $this->validateCommaSeparatedIds($value, 'tags', $attribute, $fail);
                }
            }],
            'client_can_discuss' => 'required|boolean'
        ];
    }

    public function onFailure(...$failures)
    {
        foreach ($failures as $failure) {
            foreach ($failure->errors() as $error) {
                $rowNumber = $failure->row();
                $field = $failure->attribute();
                $value = $failure->values()[$field] ?? null;

                $message = $this->formatErrorMessage($field, $error, $rowNumber, $value);
                if ($message) {
                    $this->validationErrors[] = $message;
                }
            }
        }
    }

    protected function validateCommaSeparatedIds($value, $table, $attribute, $fail)
    {
        // Handle both string and array inputs
        if (is_array($value)) {
            $ids = $value;
        } else if (is_string($value) && !empty($value)) {
            $ids = explode(',', $value);
        } else {
            return; // Empty value, skip validation
        }

        foreach ($ids as $id) {
            $id = trim($id);
            if (!empty($id) && !is_numeric($id)) {
                $fail("Invalid {$attribute} format. Expected numeric IDs separated by commas.");
                return;
            }
            if (!empty($id)) {
                $exists = DB::table($table)->where('id', $id)->exists();
                if (!$exists) {
                    $fail("Invalid {$attribute} ID {$id}. {$table} ID does not exist.");
                }
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => [self::class, 'beforeImport'],
        ];
    }

    public static function beforeImport(BeforeImport $event)
    {
        // Clear previous manual validation errors
        self::$manualValidationErrors = [];

        // You can keep this method for any global validations if needed
        // but don't let it block all imports
    }

    private function formatErrorMessage($field, $error, $rowNumber, $value = null)
    {
        // Simplified error message formatting
        switch ($field) {
            case 'title':
                return str_contains($error, 'required') ? "Title is required at Row {$rowNumber}." : null;
            case 'status_id':
                return str_contains($error, 'required') ? "Status ID is required at Row {$rowNumber}." : null;
            case 'priority_id':
                return str_contains($error, 'exists') ? "Invalid priority ID {$value} at Row {$rowNumber}." : null;
            case 'start_date':
            case 'end_date':
                if (str_contains($error, 'date_format')) {
                    return "Invalid date format for " . str_replace('_', ' ', $field) . " at Row {$rowNumber}. Expected format: YYYY-MM-DD.";
                }
                if (str_contains($error, 'after_or_equal')) {
                    return ucfirst(str_replace('_', ' ', $field)) . " cannot be in the past at Row {$rowNumber}.";
                }
                return null;
            case 'budget':
                return "Invalid budget format at Row {$rowNumber}.";
            case 'task_accessibility':
                if (str_contains($error, 'required')) {
                    return "Task accessibility is required at Row {$rowNumber}.";
                }
                if (str_contains($error, 'in')) {
                    return "Invalid task accessibility at Row {$rowNumber}. Allowed values: project_users, assigned_users.";
                }
                return null;
            case 'client_can_discuss':
                if (str_contains($error, 'required')) {
                    return "Client can discuss is required at Row {$rowNumber}.";
                }
                if (str_contains($error, 'boolean')) {
                    return "Client can discuss must be a boolean value (0 or 1) at Row {$rowNumber}.";
                }
                return null;
            default:
                return "Validation error in field '{$field}' at Row {$rowNumber}: {$error}";
        }
    }

    public function getValidationErrors()
    {
        return array_merge($this->validationErrors, self::$manualValidationErrors);
    }

    public function getManualValidationErrors()
    {
        return self::$manualValidationErrors;
    }
}
