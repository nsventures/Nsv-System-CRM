<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\User;
use App\Models\Client;
use App\Models\Status;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Priority;
use App\Models\Milestone;
use App\Models\Workspace;
use App\Models\CustomField;
use App\Models\ProjectUser;
use Illuminate\Http\Request;
use App\Models\ProjectClient;
use App\Imports\ProjectsImport;
use App\Models\CommentAttachment;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\StoreMilestoneRequest;
use App\Http\Requests\UpdateMilestoneRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Requests\UpdateFavoriteRequest;
use App\Http\Requests\UpdatePinnedRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Requests\UpdatePriorityRequest;
use App\Http\Requests\UpdateProjectDatesRequest;
use App\Http\Requests\DeleteMultipleMediaRequest;
use App\Http\Requests\DeleteMultipleMilestoneRequest;
use App\Services\DeletionService;
use App\Services\ProjectQueryService;
use App\Services\ProjectService;
use App\Services\ProjectMediaService;
use App\Services\ProjectMilestoneService;
use App\Services\ProjectCommentService;
use App\Services\ProjectCalendarService;
use App\Services\ProjectMetaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UserClientPreference;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\FileValidationHelper;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Chatify\Facades\ChatifyMessenger as Chatify;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Request as FacadesRequest;

class ProjectsController extends Controller
{
    protected $workspace;
    protected $user;
    protected ProjectQueryService $projectQueryService;
    protected ProjectService $projectService;
    protected ProjectMediaService $projectMediaService;
    protected ProjectMilestoneService $projectMilestoneService;
    protected ProjectCommentService $projectCommentService;
    protected ProjectCalendarService $projectCalendarService;
    protected ProjectMetaService $projectMetaService;

    public function __construct(ProjectQueryService $projectQueryService, ProjectService $projectService, ProjectMediaService $projectMediaService, ProjectMilestoneService $projectMilestoneService, ProjectCommentService $projectCommentService, ProjectCalendarService $projectCalendarService, ProjectMetaService $projectMetaService)
    {
        $this->projectQueryService = $projectQueryService;
        $this->projectService = $projectService;
        $this->projectMediaService = $projectMediaService;
        $this->projectMilestoneService = $projectMilestoneService;
        $this->projectCommentService = $projectCommentService;
        $this->projectCalendarService = $projectCalendarService;
        $this->projectMetaService = $projectMetaService;
        $this->middleware(function ($request, $next) {
            // fetch session and use it in entire class with constructor
            $this->workspace = Workspace::find(getWorkspaceId());
            $this->user = getAuthenticatedUser();
            return $next($request);
        });
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $type = null)
    {
        $filters = [
            'statuses' => $request->input('statuses', []),
            'tags' => $request->input('tags', []),
            'is_favorite' => $type === 'favorite' ? 1 : 0,
            'sort' => $request->input('sort', 'id'),
            'order' => 'desc',
        ];

        $projectsQuery = $this->projectQueryService->buildIndexQuery($this->workspace, $this->user, $filters);
        $projects = $projectsQuery->paginate(6);

        $customFields = CustomField::where('module', 'project')->get();

        return view('projects.grid_view', [
            'projects' => $projects,
            'auth_user' => $this->user,
            'selectedTags' => $filters['tags'],
            'is_favorite' => $filters['is_favorite'],
            'customFields' => $customFields
        ]);
    }
    public function kanban_view(Request $request, $type = null)
    {
        $filters = [
            'statuses' => $request->input('statuses', []),
            'tags' => $request->input('tags', []),
            'is_favorite' => $type === 'favorite' ? 1 : 0,
            'sort' => request('sort', 'id'),
            'order' => 'desc',
        ];

        $projectsQuery = $this->projectQueryService->buildIndexQuery($this->workspace, $this->user, $filters);
        $projects = $projectsQuery->get();

        $customFields = CustomField::where('module', 'project')->get();
        return view('projects.kanban', [
            'projects' => $projects,
            'auth_user' => $this->user,
            'selectedTags' => $filters['tags'],
            'is_favorite' => $filters['is_favorite'],
            'customFields' => $customFields
        ]);
    }
    public function list_view(Request $request, $type = null)
    {
        $filters = [
            'is_favorite' => $type === 'favorite' ? 1 : 0,
        ];

        $projectsQuery = $this->projectQueryService->buildBaseQuery($this->workspace, $this->user);

        if ($filters['is_favorite']) {
            $projectsQuery = $this->projectQueryService->applyFilters($projectsQuery, $filters, $this->user);
        }

        $projects = $projectsQuery->get();
        $customFields = CustomField::where('module', 'project')->get();

        return view('projects.projects', [
            'projects' => $projects,
            'is_favorites' => $filters['is_favorite'],
            'customFields' => $customFields
        ]);
    }
    public function ganttChartView(Request $request, $type = null)
    {
        $customFields = CustomField::where('module', 'project')->get();
        $is_favorite = 0;
        if ($type === 'favorite') {
            $is_favorite = 1;
        }
        return view('projects.gantt_chart', ['is_favorite' => $is_favorite, 'customFields' => $customFields]);
    }
    /**
     * Create a new project.
     *
     * This endpoint creates a new project with the provided details. The user must be authenticated to perform this action. The request validates various fields, including title, status, priority, dates, and task accessibility.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @bodyParam title string required The title of the project. Example: New Website Launch
     * @bodyParam status_id int required The ID of the project's status. Example: 1
     * @bodyParam priority_id int optional The ID of the project's priority. Example: 2
     * @bodyParam start_date string|null optional The start date of the project in the format specified in the general settings. Example: 2024-08-01
     * @bodyParam end_date string|null optional The end date of the project in the format specified in the general settings. Example: 2024-08-31
     * @bodyParam budget string|null optional Only digits, commas as thousand separators, and a single decimal point are allowed. digits can optionally be grouped in thousands with commas, where each group of digits must be exactly three digits long (e.g., 1,000 is correct; 10,0000 is not). Example: 5000.00
     * @bodyParam task_accessibility string required Indicates who can access the task. Must be either 'project_users' or 'assigned_users'. Example: project_users
     * @bodyParam description string|null optional A description of the project. Example: A project to launch a new company website.
     * @bodyParam note string|null optional Additional notes for the project. Example: Ensure all team members are informed.
     * @bodyParam user_id array|null optional Array of user IDs to be associated with the project. Example: [1, 2, 3]
     * @bodyParam client_id array|null optional Array of client IDs to be associated with the project. Example: [5, 6]
     * @bodyParam tag_ids array|null optional Array of tag IDs to be associated with the project. Example: [10, 11]
     * @bodyParam clientCanDiscuss string optional Indicates if the client can participate in project discussions. Can only specify if `is_admin_or_has_all_data_access` is true for the logged-in user; otherwise, it will be considered 0 by default. The value should be 'on' to allow client participation. Example: on
     *
     * @response 200 {
     * "error": false,
     * "message": "Project created successfully.",
     * "id": 438,
     * "data": {
     *   "id": 438,
     *   "title": "Res Test",
     *   "status": "Default",
     *   "priority": "dsfdsf",
     *   "users": [
     *     {
     *       "id": 7,
     *       "first_name": "Madhavan",
     *       "last_name": "Vaidya",
     *       "photo": "https://test-taskify.infinitietech.com/storage/photos/yxNYBlFLALdLomrL0JzUY2USPLILL9Ocr16j4n2o.png"
     *     },
     *     {
     *       "id": 185,
     *       "first_name": "Admin",
     *       "last_name": "Test",
     *       "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     *     }
     *   ],
     *   "clients": [
     *     {
     *       "id": 103,
     *       "first_name": "Test",
     *       "last_name": "Test",
     *       "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     *     }
     *   ],
     *   "tags": [
     *     {
     *       "id": 45,
     *       "title": "Tag from update project"
     *     }
     *   ],
     *   "start_date": null,
     *   "end_date": null,
     *   "budget": "1000",
     *   "task_accessibility": "assigned_users",
     *   "description": null,
     *   "note": null,
     *   "favorite": 0,
     *   "created_at": "07-08-2024 14:38:51",
     *   "updated_at": "07-08-2024 14:38:51"
     * }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "title": [
     *       "The title field is required."
     *     ],
     *     "status_id": [
     *       "The status_id field is required."
     *     ],
     *     "start_date": [
     *       "The start date must be before or equal to the end date."
     *     ],
     *     "budget": [
     *       "The budget format is invalid."
     *     ],
     *     "task_accessibility": [
     *       "The task accessibility must be either project_users or assigned_users."
     *     ]
     *   }
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "You are not authorized to set this status."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while creating the project."
     * }
     */
    public function store(StoreProjectRequest $request)
    {
        $isApi = request()->get('isApi', false);

        try {
            $customFieldsReq = CustomField::where('module', 'project')->where('required', 1)->get();
            $customRules = [];
            $customMessages = [];
            foreach ($customFieldsReq as $field) {
                $customRules["custom_fields.{$field->id}"] = 'required';
                $customMessages["custom_fields.{$field->id}.required"] = "The {$field->field_label} field is required.";
            }
            if (!empty($customRules)) {
                $request->validate($customRules, $customMessages);
            }

            $formFields = $request->validated();
            $status = Status::findOrFail($request->input('status_id'));

            if (!canSetStatus($status)) {
                return response()->json(['error' => true, 'message' => 'You are not authorized to set this status.']);
            }

            // Extract relationship data
            $userIds = $request->input('user_id', []);
            $clientIds = $request->input('client_id', []);
            $tagIds = $request->input('tag_ids', []);
            $isFavorite = $request->has('is_favorite') && $request->input('is_favorite') == 1;
            $customFields = $request->has('custom_fields') ? $request->input('custom_fields') : [];

            // Handle clientCanDiscuss in formFields for service
            if ($request->filled('clientCanDiscuss')) {
                $formFields['clientCanDiscuss'] = $request->input('clientCanDiscuss');
            }

            // Create project using service
            $project = $this->projectService->createProject(
                $this->workspace,
                $this->user,
                $formFields,
                $userIds,
                $clientIds,
                $tagIds,
                $isFavorite,
                $customFields,
                $isApi
            );

            return formatApiResponse(
                false,
                'Project created successfully.',
                [
                    'id' => $project->id,
                    'data' => formatProject($project)
                ]
            );
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            // dd($e);
            // Handle any unexpected errors
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while creating the project.'
            ], 500);
        }
    }
    public function showBulkUploadForm(Request $request)
    {
        $sampleFileUrl = asset('storage/files/Projects bulk upload sample.xlsx');
        $helpUrl = asset('storage/files/Projects bulk upload instructions.pdf');
        return view('bulk-upload', [
            'entity' => 'projects',
            'form_action' => url('projects/process-bulk-upload'),
            'sample_file_url' => $sampleFileUrl,
            'help_url' => $helpUrl
        ]);
    }
    public function importBulkProjects(Request $request)
    {
        // Validate file type (ensure it's Excel or CSV)
        $request->validate([
            'bulk_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            // Initialize the import class
            $import = new ProjectsImport;

            // Use the import class for bulk upload
            Excel::import($import, $request->file('bulk_file'));

            // Get validation errors
            $validationErrors = $import->getValidationErrors();
            $validationErrors = array_filter($validationErrors, function ($value) {
                return $value !== null && $value !== '';
            });

            // Get the count of successful imports (you might want to track this in your import class)
            $successfulImports = 0; // You can add a counter in your import class to track this

            if (!empty($validationErrors)) {
                // Return partial success with validation errors
                return response()->json([
                    'error' => false, // Changed to false since some might have succeeded
                    'message' => count($validationErrors) > 0 ?
                        "Import completed with some errors. {$successfulImports} projects imported successfully." :
                        'Some validation errors occurred.',
                    'validation_errors' => $validationErrors,
                    'partial_success' => true
                ], 200); // Changed to 200 since it's partial success
            }

            // If no validation errors, return success message
            return response()->json([
                'error' => false,
                'message' => 'All projects imported successfully.'
            ]);
        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('Project import error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'An error occurred while importing projects: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = Project::findOrFail($id);
        $projectTags = $project->tags;
        $types = getControllerNames();
        $comments = $project->comments;
        $customFields = CustomField::where('module', 'project')->get();
        return view('projects.project_information', ['project' => $project, 'projectTags' => $projectTags, 'types' => $types, 'auth_user' => $this->user, 'comments' => $comments, 'customFields' => $customFields]);
    }
    public function get($projectId)
    {
        $project = Project::findOrFail($projectId);
        $project->budget = format_currency($project->budget, false, false);
        $users = $project->users()->get();
        $clients = $project->clients()->get();
        $tags = $project->tags()->get();
        $workspace_users = $this->workspace->users;
        $workspace_clients = $this->workspace->clients;
        $project->load("customFieldValues");
        // Prepare custom field values for API response
        $customFields = CustomField::where('module', 'project')->get();
        // dd($project->customFieldValues);
        // Prepare custom field values for the view
        $customFieldValues = [];
        foreach ($project->customFieldValues as $fieldValue) {
            $customFieldValues[$fieldValue->custom_field_id] = $fieldValue->value;
        }

        return response()->json([
            'error' => false,
            'project' => $project,
            'users' => $users,
            'clients' => $clients,
            'workspace_users' => $workspace_users,
            'workspace_clients' => $workspace_clients,
            'tags' => $tags,
            'customFields' => $customFields,
            'customFieldValues' => $customFieldValues
        ]);
    }
    /**
     * Update an existing project.
     *
     * This endpoint updates an existing project with the provided details. The user must be authenticated to perform this action. The request validates various fields, including title, status, priority, dates, and task accessibility.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @bodyParam id int required The ID of the project to update. Example: 1
     * @bodyParam title string required The title of the project. Example: Updated Project Title
     * @bodyParam status_id int required The ID of the project's status. Example: 2
     * @bodyParam priority_id int optional The ID of the project's priority. Example: 3
     * @bodyParam budget string|null optional Only digits, commas as thousand separators, and a single decimal point are allowed. digits can optionally be grouped in thousands with commas, where each group of digits must be exactly three digits long (e.g., 1,000 is correct; 10,0000 is not). Example: 5000.00
     * @bodyParam task_accessibility string required Indicates who can access the task. Must be either 'project_users' or 'assigned_users'. Example: assigned_users
     * @bodyParam start_date string|null optional The start date of the project in the format specified in the general settings. Example: 2024-08-01
     * @bodyParam end_date string|null optional The end date of the project in the format specified in the general settings. Example: 2024-08-31
     * @bodyParam description string|null optional A description of the project. Example: Updated project description.
     * @bodyParam note string|null optional Additional notes for the project. Example: Updated note for the project.
     * @bodyParam user_id array|null optional Array of user IDs to be associated with the project. Example: [2, 3]
     * @bodyParam client_id array|null optional Array of client IDs to be associated with the project. Example: [5, 6]
     * @bodyParam tag_ids array|null optional Array of tag IDs to be associated with the project. Example: [10, 11]
     * @bodyParam clientCanDiscuss string optional Indicates if the client can participate in project discussions. Can only specify if `is_admin_or_has_all_data_access` is true for the logged-in user; otherwise, it will be considered current value by default. The value should be 'on' to allow client participation. Example: on
     *
     * @response 200 {
     * "error": false,
     * "message": "Project updated successfully.",
     * "id": 438,
     * "data": {
     *   "id": 438,
     *   "title": "Res Test",
     *   "status": "Default",
     *   "priority": "dsfdsf",
     *   "users": [
     *     {
     *       "id": 7,
     *       "first_name": "Madhavan",
     *       "last_name": "Vaidya",
     *       "photo": "https://test-taskify.infinitietech.com/storage/photos/yxNYBlFLALdLomrL0JzUY2USPLILL9Ocr16j4n2o.png"
     *     },
     *     {
     *       "id": 185,
     *       "first_name": "Admin",
     *       "last_name": "Test",
     *       "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     *     }
     *   ],
     *   "clients": [
     *     {
     *       "id": 103,
     *       "first_name": "Test",
     *       "last_name": "Test",
     *       "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     *     }
     *   ],
     *   "tags": [
     *     {
     *       "id": 45,
     *       "title": "Tag from update project"
     *     }
     *   ],
     *   "start_date": null,
     *   "end_date": null,
     *   "budget": "1000",
     *   "task_accessibility": "assigned_users",
     *   "description": null,
     *   "note": null,
     *   "favorite": 0,
     *   "created_at": "07-08-2024 14:38:51",
     *   "updated_at": "07-08-2024 14:38:51"
     * }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "id": [
     *       "The project ID is required.",
     *       "The project ID does not exist in our records."
     *     ],
     *     "status_id": [
     *       "The status field is required."
     *     ],
     *     "budget": [
     *       "The budget format is invalid."
     *     ],
     *     "task_accessibility": [
     *       "The task accessibility must be either project_users or assigned_users."
     *     ],
     *     "start_date": [
     *       "The start date must be before or equal to the end date."
     *     ]
     *   }
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "You are not authorized to set this status."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while updating the project."
     * }
     */
    public function update(UpdateProjectRequest $request)
    {
        $isApi = request()->get('isApi', false);

        try {
            $customFieldsReq = CustomField::where('module', 'project')->where('required', 1)->get();
            $customRules = [];
            $customMessages = [];
            foreach ($customFieldsReq as $field) {
                $customRules["custom_fields.{$field->id}"] = 'required';
                $customMessages["custom_fields.{$field->id}.required"] = "The {$field->field_label} field is required.";
            }
            if (!empty($customRules)) {
                $request->validate($customRules, $customMessages);
            }

            $id = $request->input('id');
            $project = Project::findOrFail($id);

            // Check status authorization if status is being changed
            $currentStatusId = $project->status_id;
            $newStatusId = $request->input('status_id');
            if ($currentStatusId != $newStatusId) {
                $status = Status::findOrFail($newStatusId);
                if (!canSetStatus($status)) {
                    return response()->json(['error' => true, 'message' => 'You are not authorized to set this status.']);
                }
            }

            // Prepare update data
            $formFieldsToUpdate = [
                'title' => $request->input('title'),
                'status_id' => $newStatusId,
                'priority_id' => $request->input('priority_id'),
                'budget' => $request->input('budget'),
                'task_accessibility' => $request->input('task_accessibility'),
                'description' => $request->input('description'),
                'note' => $request->input('note'),
                'enable_tasks_time_entries' => $request->input('enable_tasks_time_entries', false),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];

            // Handle clientCanDiscuss
            if ($request->filled('clientCanDiscuss')) {
                $formFieldsToUpdate['clientCanDiscuss'] = $request->input('clientCanDiscuss');
            }

            // Extract relationship data
            $userIds = $request->input('user_id', []);
            $clientIds = $request->input('client_id', []);
            $tagIds = $request->input('tag_ids', []);
            $customFields = $request->has('custom_fields') ? $request->input('custom_fields') : [];

            // Update project using service
            $project = $this->projectService->updateProject(
                $project,
                $this->user,
                $formFieldsToUpdate,
                $userIds,
                $clientIds,
                $tagIds,
                $customFields,
                $isApi
            );

            return formatApiResponse(
                false,
                'Project updated successfully.',
                [
                    'id' => $project->id,
                    'data' => formatProject($project)
                ]
            );
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            // Handle any unexpected errors
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while updating the project.'
            ], 500);
        }
    }
    /**
     * Remove the specified project.
     *
     * This endpoint deletes a project based on the provided ID. The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @urlParam id int required The ID of the project to be deleted. Example: 1
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Project deleted successfully.",
     *   "id": 1,
     *   "title": "Project Title",
     *   "data": []
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Project not found.",
     *   "data": []
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while deleting the project."
     * }
     */
    public function destroy($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return formatApiResponse(
                true,
                'Project not found.',
                []
            );
        }

        // Delete project using DeletionService
        $response = DeletionService::delete(Project::class, $id, 'Project');
        $data = $response->getData();

        if ($data->error) {
            return response()->json(['error' => true, 'message' => $data->message]);
        }

        // Clean up related data using service
        $this->projectService->deleteProject($project);

        return $response;
    }
    public function destroy_multiple(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:projects,id'
        ]);

        $ids = $validatedData['ids'];
        $deletedProjects = [];
        $deletedProjectTitles = [];

        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $project = Project::find($id);
            if ($project) {
                $deletedProjectTitles[] = $project->title;

                // Clean up related data using service
                $this->projectService->deleteProject($project);

                // Delete project using DeletionService
                DeletionService::delete(Project::class, $id, 'Project');
                $deletedProjects[] = $id;
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'Project(s) deleted successfully.',
            'id' => $deletedProjects,
            'titles' => $deletedProjectTitles
        ]);
    }
    public function list(Request $request, $id = '', $type = '')
    {
        // Prepare filters array
        $filters = [
            'search' => request('search'),
            'sort' => request('sort', 'id'),
            'order' => request('order', 'DESC'),
            'status_ids' => request('status_ids', []),
            'priority_ids' => request('priority_ids', []),
            'user_ids' => request('user_ids', []),
            'client_ids' => request('client_ids', []),
            'tag_ids' => $request->input('tag_ids', []),
            'date_between_from' => request('project_date_between_from') ?: '',
            'date_between_to' => request('project_date_between_to') ?: '',
            'start_date_from' => request('project_start_date_from') ?: '',
            'start_date_to' => request('project_start_date_to') ?: '',
            'end_date_from' => request('project_end_date_from') ?: '',
            'end_date_to' => request('project_end_date_to') ?: '',
            'is_favorites' => request('is_favorites') ?: '',
            'belongs_to' => null,
            'belongs_to_id' => null,
            'limit' => request('limit', 10),
        ];

        // Parse id parameter if provided
        if ($id) {
            $idParts = explode('_', $id);
            $filters['belongs_to'] = $idParts[0];
            $filters['belongs_to_id'] = (int)$idParts[1];
        }

        // Build query using service
        $projectsQuery = $this->projectQueryService->getProjectListQuery($this->workspace, $this->user, $filters);
        $totalprojects = $projectsQuery->count();

        // Permissions and formatting data
        $canCreate = checkPermission('create_projects');
        $canEdit = checkPermission('edit_projects');
        $canDelete = checkPermission('delete_projects');
        $statuses = Status::all();
        $priorities = Priority::all();
        $isHome = $request->query('from_home') == '1';
        $webGuard = Auth::guard('web')->check();

        // Paginate and format (query already has sorting and pinned join applied)
        $projects = $projectsQuery->paginate($filters['limit'])
            ->through(
                function ($project) use ($statuses, $priorities, $canEdit, $canDelete, $canCreate, $isHome, $webGuard) {
                    // Status and priority dropdown options removed as per request to only display data.
                    $isFavorite = getFavoriteStatus($project->id);
                    $isPinned = !is_null($project->pinned_id) ? 1 : 0; // Use pinned_id from the query

                    $actions = '<div class="dropdown">';
                    $actions .= '<button class="btn p-0 dropdown-toggle hide-arrow " type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-boundary="window">';
                    $actions .= '<i class="bx bx-dots-vertical-rounded fs-5"></i>';
                    $actions .= '</button>';
                    $actions .= '<ul class="dropdown-menu  project-actions-dropdown z-3">';

                    if ($canEdit) {
                        $actions .= '<li><a href="javascript:void(0);" class="dropdown-item edit-project d-block" data-offcanvas="true" data-id="' . $project->id . '">';
                        $actions .= '<i class="bx bx-edit text-primary me-2"></i>' . get_label('update', 'Update') . '</a></li>';
                    }
                    if ($canCreate) {
                        $actions .= '<li><a href="javascript:void(0);" class="dropdown-item duplicate d-block" data-id="' . $project->id . '" data-title="' . $project->title . '" data-type="projects" data-table="projects_table" data-reload="' . ($isHome ? 'true' : '') . '">';
                        $actions .= '<i class="bx bx-copy text-warning me-2"></i>' . get_label('duplicate', 'Duplicate') . '</a></li>';
                    }
                    $actions .= '<li><a href="javascript:void(0);" class="dropdown-item quick-view d-block" data-id="' . $project->id . '" data-type="project">';
                    $actions .= '<i class="bx bx-info-circle text-info me-2"></i>' . get_label('quick_view', 'Quick View') . '</a></li>';

                    $actions .= '<li><a href="' . url('projects/mind-map/' . $project->id) . '" target="_blank" class="dropdown-item d-block">';
                    $actions .= '<i class="bx bx-sitemap text-secondary me-2"></i>' . get_label('mind_map', 'Mind Map') . '</a></li>';

                    // Favorite & Pin in Dropdown (Static Labels)
                    $actions .= '<li><a href="javascript:void(0);" class="dropdown-item favorite-icon d-block" data-favorite="' . $isFavorite . '" data-id="' . $project->id . '">';
                    $actions .= '<i class="bx ' . ($isFavorite ? 'bxs-star text-warning' : 'bx-star text-muted') . ' me-2"></i>' . get_label('favorite', 'Favorite') . '</a></li>';

                    $actions .= '<li><a href="javascript:void(0);" class="dropdown-item pinned-icon d-block" data-pinned="' . $isPinned . '" data-id="' . $project->id . '" data-require_reload="0">';
                    $actions .= '<i class="bx ' . ($isPinned ? 'bxs-pin text-success' : 'bx-pin text-muted') . ' me-2"></i>' . get_label('pin', 'Pin') . '</a></li>';

                    if ($webGuard || $project->client_can_discuss) {
                        $actions .= '<li><a href="' . route('projects.info', ['id' => $project->id]) . '#navs-top-discussions" class="dropdown-item d-block">';
                        $actions .= '<i class="bx bx-chat text-success me-2"></i>' . get_label('discussions', 'Discussions') . '</a></li>';
                    }

                    if ($canDelete) {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a href="javascript:void(0);" class="dropdown-item delete text-danger d-block  " data-id="' . $project->id . '" data-type="projects" data-table="projects_table" data-reload="' . ($isHome ? 'true' : '') . '">';
                        $actions .= '<i class="bx bx-trash me-2"></i>' . get_label('delete', 'Delete') . '</a></li>';
                    }

                    $actions .= '</ul>';
                    $actions .= '</div>';
                    $userHtml = '';
                if (!empty($project->users) && count($project->users) > 0) {
                        $userHtml .= '<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">';
                        foreach ($project->users as $user) {
                            $userHtml .= "<li class='avatar avatar-sm pull-up'><a href='" . url("/users/profile/{$user->id}") . "' target='_blank' title='{$user->first_name} {$user->last_name}'><img src='" . ($user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle' /></a></li>";
                        }

                    $userHtml .= '</ul>';
                    } else {
                    $userHtml = '<span class="text-muted small">' . get_label('not_assigned', 'Not Assigned') . '</span>';
                }
                    $clientHtml = '';
                if (!empty($project->clients) && count($project->clients) > 0) {
                        $clientHtml .= '<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">';
                        foreach ($project->clients as $client) {
                            $clientHtml .= "<li class='avatar avatar-sm pull-up'><a href='" . url("/clients/profile/{$client->id}") . "' title='{$client->first_name} {$client->last_name}' target='_blank'><img src='" . ($client->photo ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle' /></a></li>";
                        }

                    $clientHtml .= '</ul>';
                    } else {
                    $clientHtml = '<span class="text-muted small">' . get_label('not_assigned', 'Not Assigned') . '</span>';
                }
                    $tagHtml = '';
                    foreach ($project->tags as $tag) {
                        $tagHtml .= "<span class='text-muted small me-1'>#{$tag->title}</span>";
                    }
                    $isFavorite = getFavoriteStatus($project->id);
                $isPinned = !is_null($project->pinned_id) ? 1 : 0; // Use pinned_id from the query
                return [
                        'id' => $project->id,
                    'title' => "<a href='" . url("/projects/information/{$project->id}") . "' target='_blank'><strong>{$project->title}</strong></a>",
                        'users' => $userHtml,
                        'clients' => $clientHtml,
                        'start_date' => format_date($project->start_date),
                        'end_date' => format_date($project->end_date),
                    'budget' => !empty($project->budget) && $project->budget !== null ? format_currency($project->budget) : '-',
                        'status_id' => "<div class='d-flex align-items-center'>
                            <span class='badge bg-label-{$project->status->color}'>{$project->status->title}</span>
                            " . ($project->note ?
                            "<i class='bx bx-notepad ms-2 text-primary' title='{$project->note}'></i>"
                        : "") . "
                        </div>",
                        'priority_id' => "<span class='badge bg-label-" . ($project->priority ? $project->priority->color : 'secondary') . "'>" . ($project->priority ? $project->priority->title : '-') . "</span>",
                    'task_accessibility' => get_label($project->task_accessibility, ucwords(str_replace("_", " ", $project->task_accessibility))),
                    'tags' => $tagHtml ?: ' - ',
                        'created_at' => format_date($project->created_at, true),
                        'updated_at' => format_date($project->updated_at, true),
                    'actions' => $actions
                    ];
                }
            );
        return response()->json([
            "rows" => $projects->items(),
            "total" => $totalprojects,
        ]);
    }
    /**
     * List or search projects.
     *
     * This endpoint retrieves a list of projects based on various filters. The user must be authenticated to perform this action. The request allows filtering by status, user, client, priority, tag, date ranges, and other parameters.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @urlParam id int optional The ID of the project to retrieve. Example: 1
     *
     * @queryParam search string optional The search term to filter projects by title or id. Example: Project
     * @queryParam sort string optional The field to sort by. Defaults to "id". Sortable fields include: id, title, status, priority, start_date, end_date, budget, created_at, and updated_at. Example: title
     * @queryParam order string optional The sort order, either "ASC" or "DESC". Defaults to "DESC". Example: ASC
     * @queryParam status_ids array optional An array of status IDs to filter projects by. Example: [2, 3]
     * @queryParam user_ids array optional An array of user IDs to filter projects by. Example: [1, 2, 3]
     * @queryParam client_ids array optional An array of client IDs to filter projects by. Example: [5, 6]
     * @queryParam priority_ids array optional An array of priority IDs to filter projects by. Example: [1, 2]
     * @queryParam tag_ids array optional An array of tag IDs to filter projects by. Example: [1, 2]
     * @queryParam project_start_date_from string optional The start date range's start in YYYY-MM-DD format. Example: 2024-01-01
     * @queryParam project_start_date_to string optional The start date range's end in YYYY-MM-DD format. Example: 2024-12-31
     * @queryParam project_end_date_from string optional The end date range's start in YYYY-MM-DD format. Example: 2024-01-01
     * @queryParam project_end_date_to string optional The end date range's end in YYYY-MM-DD format. Example: 2024-12-31
     * @queryParam is_favorites boolean optional Filter projects marked as favorites. Example: true
     * @queryParam limit int optional The number of projects per page for pagination. Example: 10
     * @queryParam offset int optional The offset for pagination, indicating the starting point of results. Example: 0
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Projects retrieved successfully",
     *   "total": 1,
     *   "data": [
     *     {
     *       "id": 351,
     *       "title": "rwer",
     *       "status": "Rel test",
     *       "priority": "Default",
     *       "users": [
     *         {
     *           "id": 7,
     *           "first_name": "Madhavan",
     *           "last_name": "Vaidya",
     *           "photo": "https://test-taskify.infinitietech.com/storage/photos/yxNYBlFLALdLomrL0JzUY2USPLILL9Ocr16j4n2o.png"
     *         },
     *         {
     *           "id": 183,
     *           "first_name": "Girish",
     *           "last_name": "Thacker",
     *           "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     *         }
     *       ],
     *       "clients": [],
     *       "tags": [],
     *       "start_date": "14-06-2024",
     *       "end_date": "14-06-2024",
     *       "budget": "",
     *       "created_at": "14-06-2024 17:50:09",
     *       "updated_at": "17-06-2024 19:08:16"
     *     }
     *   ]
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Project not found",
     *   "total": 0,
     *   "data": []
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Projects not found",
     *   "total": 0,
     *   "data": []
     * }
     */
    public function apiList(Request $request, $id = '')
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'array',
            'user_ids.*' => 'integer|exists:users,id',
            'client_ids' => 'array',
            'client_ids.*' => 'integer|exists:clients,id',
            'priority_ids' => 'array',
            'priority_ids.*' => 'integer|exists:priorities,id',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'status_ids' => 'array',
            'status_ids.*' => 'integer|exists:statuses,id',
        ]);
        // If validation fails, return a response
        if ($validator->fails()) {
            return formatApiValidationError(1, $validator->errors());
        }
        $search = $request->input('search');
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $status_ids = $request->input('status_ids', []);
        $priority_ids = $request->input('priority_ids', []);
        $user_ids = $request->input('user_ids', []);
        $client_ids = $request->input('client_ids', []);
        $tag_ids = $request->input('tag_ids', []);
        $start_date_from = $request->input('project_start_date_from', '');
        $start_date_to = $request->input('project_start_date_to', '');
        $end_date_from = $request->input('project_end_date_from', '');
        $end_date_to = $request->input('project_end_date_to', '');
        $is_favorites = $request->input('is_favorites', '');
        $limit = $request->input('limit', 10); // default limit
        $offset = $request->input('offset', 0); // default offset
        if ($id) {
            $project = Project::find($id);
            if (!$project) {
                return formatApiResponse(
                    false,
                    'Project not found',
                    [
                        'total' => 0,
                        'data' => []
                    ]
                );
            } else {
                return formatApiResponse(
                    false,
                    'Project retrieved successfully',
                    [
                        'total' => 1,
                        'data' => [formatProject($project)]
                    ]
                );
            }
        } else {
            // Prepare filters array
            $filters = [
                'search' => $search,
                'sort' => $sort,
                'order' => $order,
                'status_ids' => $status_ids,
                'priority_ids' => $priority_ids,
                'user_ids' => $user_ids,
                'client_ids' => $client_ids,
                'tag_ids' => $tag_ids,
                'start_date_from' => $start_date_from,
                'start_date_to' => $start_date_to,
                'end_date_from' => $end_date_from,
                'end_date_to' => $end_date_to,
                'is_favorites' => $is_favorites,
                'limit' => $limit,
                'offset' => $offset,
                'search_include_description' => true, // API includes description in search
            ];

            // Build query using service
            $projectsQuery = $this->projectQueryService->getProjectApiListQuery($this->workspace, $this->user, $filters);
            $total = $projectsQuery->count();

            // Apply pagination
            $projects = $projectsQuery->skip($offset)->take($limit)->get();

            if ($projects->isEmpty()) {
                return formatApiResponse(
                    false,
                    'Projects not found',
                    [
                        'total' => 0,
                        'data' => []
                    ]
                );
            }

            $data = $projects->map(function ($project) {
                return formatProject($project);
            });

            return formatApiResponse(
                false,
                'Projects retrieved successfully',
                [
                    'total' => $total,
                    'data' => $data
                ]
            );
        }
    }
    /**
     * Update the favorite status of a project.
     *
     * This endpoint updates whether a project is marked as a favorite or not. The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @urlParam id int required The ID of the project to update.
     * @bodyParam is_favorite int required Indicates whether the project is a favorite. Use 1 for true and 0 for false.
     *
     * @response 200 {
     * "error": false,
     * "message": "Project favorite status updated successfully",
     * "data": {
     * "id": 438,
     * "title": "Res Test",
     * "status": "Default",
     * "priority": "dsfdsf",
     * "users": [
     * {
     * "id": 7,
     * "first_name": "Madhavan",
     * "last_name": "Vaidya",
     * "photo": "https://test-taskify.infinitietech.com/storage/photos/yxNYBlFLALdLomrL0JzUY2USPLILL9Ocr16j4n2o.png"
     * }
     * ],
     * "clients": [
     * {
     * "id": 103,
     * "first_name": "Test",
     * "last_name": "Test",
     * "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     * }
     * ],
     * "tags": [
     * {
     * "id": 45,
     * "title": "Tag from update project"
     * }
     * ],
     * "start_date": null,
     * "end_date": null,
     * "budget": "1000.00",
     * "task_accessibility": "assigned_users",
     * "description": null,
     * "note": null,
     * "favorite": 1,
     * "created_at": "07-08-2024 14:38:51",
     * "updated_at": "12-08-2024 13:36:10"
     * }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "is_favorite": [
     *       "The is favorite field must be either 0 or 1."
     *     ]
     *   }
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Project not found",
     *   "data": []
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while updating the favorite status."
     * }
     */
    public function update_favorite(UpdateFavoriteRequest $request, $id)
    {
        $isApi = request()->get('isApi', false);
        try {
            $project = Project::find($id);
            if (!$project) {
                return formatApiResponse(true, 'Project not found', []);
            }

            $authUser = getAuthenticatedUser();
            $isFavorite = (bool) $request->input('is_favorite');

            $updatedProject = $this->projectService->updateFavorite($project, $authUser, $isFavorite);

            return formatApiResponse(
                false,
                'Project favorite status updated successfully',
                ['data' => formatProject($updatedProject)]
            );
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            Log::error('Favorite update error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while updating the project favorite status.'
            ], 500);
        }
    }
    /**
     * Update the pinned status of a project.
     *
     * This endpoint updates whether a project is marked as pinned or not. The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @urlParam id int required The ID of the project to update.
     * @bodyParam is_pinned int required Indicates whether the project is pinned. Use 1 for true and 0 for false.
     *
     * @response 200 {
     * "error": false,
     * "message": "Project pinned status updated successfully",
     * "data": {
     *   "id": 438,
     *   "title": "Res Test"
     *   // Other project details will be included in the actual response
     * }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "is_pinned": [
     *       "The is pinned field must be either 0 or 1."
     *     ]
     *   }
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Project not found",
     *   "data": []
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while updating the pinned status."
     * }
     */
    public function update_pinned(UpdatePinnedRequest $request, $id)
    {
        $isApi = request()->get('isApi', false);
        try {
            $project = Project::find($id);
            if (!$project) {
                return formatApiResponse(true, 'Project not found', []);
            }

            $authUser = getAuthenticatedUser();
            $isPinned = (bool) $request->input('is_pinned');

            $result = $this->projectService->updatePinned($project, $authUser, $isPinned);

            return formatApiResponse(
                false,
                $result['message'],
                ['data' => formatProject($result['project'])]
            );
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            Log::error('Pinned update error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while updating the project pinned status.'
            ], 500);
        }
    }
    public function duplicate($id)
    {
        try {
            $title = (request()->has('title') && !empty(trim(request()->title))) ? request()->title : null;
            $duplicate = $this->projectService->duplicateProject($id, $title);

            if (!$duplicate) {
                return response()->json(['error' => true, 'message' => 'Project duplication failed.']);
            }

            if (request()->has('reload') && request()->input('reload') === 'true') {
                Session::flash('message', 'Project duplicated successfully.');
            }

            return response()->json(['error' => false, 'message' => 'Project duplicated successfully.', 'id' => $duplicate->id]);
        } catch (\Exception $e) {
            Log::error('Project duplication error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => true, 'message' => 'Project duplication failed.']);
        }
    }
    /**
     * Upload media files to a project.
     *
     * This endpoint allows authenticated users to upload media files related to a project.
     *
     * @authenticated
     *
     * @group Project Media
     *
     * @bodyParam id int required The ID of the project to which media files are being uploaded.
     * @bodyParam media_files[] file required An array of media files to be uploaded. Maximum file size is defined in the config.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "File(s) uploaded successfully.",
     *   "id": [101, 102],
     *   "type": "media",
     *   "parent_type": "project",
     *   "parent_id": 438
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "id": ["The selected id is invalid."],
     *     "media_files": ["The media file size exceeds the limit."]
     *   }
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Project not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred during file upload."
     * }
     */
    public function upload_media(Request $request)
    {
        $isApi = request()->get('isApi', false);
        try {
            $maxFileSizeBytes = config('media-library.max_file_size');
            $maxFileSizeKb = (int) ($maxFileSizeBytes / 1024);
            $validatedData = $request->validate([
                'id' => ['required', 'integer', 'exists:projects,id'],
                'media_files.*' => "file|max:$maxFileSizeKb"
            ]);

            if (!$request->hasFile('media_files')) {
                return response()->json([
                    'error' => true,
                    'message' => 'No file(s) chosen.'
                ]);
            }

            $project = Project::findOrFail($validatedData['id']);
            $mediaFiles = $request->file('media_files');
            $mediaIds = $this->projectMediaService->uploadMedia($project, $mediaFiles);

            return response()->json([
                'error' => false,
                'message' => 'File(s) uploaded successfully.',
                'id' => $mediaIds,
                'type' => 'media',
                'parent_type' => 'project',
                'parent_id' => $project->id
            ]);
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Project not found."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "An error occurred during file upload."
            ], 500);
        }
    }

    public function get_media($id)
    {
        try {
            $project = Project::findOrFail($id);
            $search = request('search');
            $sort = request('sort', 'id');
            $order = request('order', 'DESC');
            $canDelete = checkPermission('delete_media');

            $media = $this->projectMediaService->getMedia($project, $search, $sort, $order);
            $formattedMedia = $this->projectMediaService->formatMediaForWeb($media, $canDelete);

            return response()->json([
                'error' => false,
                'message' => 'Media files retrieved successfully.',
                'rows' => $formattedMedia->values()->toArray(),
                'total' => $formattedMedia->count(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Project not found."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "Could not retrieve media files."
            ], 500);
        }
    }
    /**
     * Get project media files.
     *
     * This endpoint retrieves all media files associated with a specific project, including sorting and search capabilities.
     *
     * @authenticated
     *
     * @group Project Media
     *
     * @urlParam id int required The ID of the project whose media files are to be retrieved.
     * @queryParam search string optional A search query to filter media files by name, ID, or upload date.
     * @queryParam sort string optional The column to sort by (default: "id").
     * @queryParam order string optional The sorting order: "ASC" or "DESC" (default: "DESC").
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Media files retrieved successfully.",
     *   "rows": [
     *     {
     *       "id": 101,
     *       "file": "<a href='https://example.com/storage/project-media/image.jpg' data-lightbox='project-media'><img src='https://example.com/storage/project-media/image.jpg' alt='image.jpg' width='50'></a>",
     *       "file_name": "image.jpg",
     *       "file_size": "2 MB",
     *       "created_at": "2025-03-03",
     *       "updated_at": "2025-03-03",
     *
     *     }
     *   ],
     *   "total": 1
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Project not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Could not retrieve media files."
     * }
     */
    public function get_media_api($id)
    {
        try {
            $project = Project::findOrFail($id);
            $search = request('search');
            $sort = request('sort', 'id');
            $order = request('order', 'DESC');
            $canDelete = checkPermission('delete_media');

            $media = $this->projectMediaService->getMedia($project, $search, $sort, $order);
            $formattedMedia = $this->projectMediaService->formatMediaForApi($media, $canDelete);

            return response()->json([
                'error' => false,
                'message' => 'Media files retrieved successfully.',
                'data' => $formattedMedia->values()->toArray(),
                'total' => $formattedMedia->count(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Project not found."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "Could not retrieve media files."
            ], 500);
        }
    }
    /**
     * Delete a media file.
     *
     * This endpoint deletes a specified media file associated with a project. The user must be authenticated and have permission to delete media files.
     *
     * @authenticated
     *
     * @group Project Media
     *
     * @urlParam mediaId int required The ID of the media file to delete.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "File deleted successfully.",
     *   "id": 101,
     *   "title": "image.jpg",
     *   "parent_id": 438,
     *   "type": "media",
     *   "parent_type": "project"
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "File not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "File couldn't be deleted."
     * }
     */
    public function delete_media($mediaId)
    {
        try {
            $mediaItem = Media::findOrFail($mediaId);
            $this->projectMediaService->deleteMedia($mediaItem);

            return response()->json([
                'error' => false,
                'message' => 'File deleted successfully.',
                'id' => $mediaItem->id,
                'title' => $mediaItem->file_name,
                'parent_id' => $mediaItem->model_id,
                'type' => 'media',
                'parent_type' => 'project'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'File not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => "File couldn't be deleted."
            ], 500);
        }
    }
    public function delete_multiple_media(DeleteMultipleMediaRequest $request)
    {
        try {
            $result = $this->projectMediaService->deleteMultipleMedia($request->ids);

            return response()->json([
                'error' => false,
                'message' => 'Files(s) deleted successfully.',
                'id' => $result['deleted_ids'],
                'titles' => $result['deleted_titles'],
                'parent_id' => $result['parent_ids'],
                'type' => 'media',
                'parent_type' => 'project'
            ]);
        } catch (\Exception $e) {
            Log::error('Multiple media deletion error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while deleting media files.'
            ], 500);
        }
    }
    /**
     * Store a new milestone.
     *
     * This endpoint creates a new milestone for a specified project. The user must be authenticated and have permission to create milestones.
     *
     * @authenticated
     *
     * @group Milestone Management
     *
     * @bodyParam project_id int required The ID of the project to which the milestone belongs.
     * @bodyParam title string required The title of the milestone.
     * @bodyParam status string required The status of the milestone.
     * @bodyParam start_date date optional The start date of the milestone (YYYY-MM-DD).
     * @bodyParam end_date date optional The end date of the milestone (YYYY-MM-DD).
     * @bodyParam cost numeric required The cost of the milestone.
     * @bodyParam description string optional A description of the milestone.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Milestone created successfully.",
     *   "id": 12,
     *   "type": "milestone",
     *   "parent_type": "project",
     *   "parent_id": 438
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "project_id": ["The selected project_id is invalid."],
     *     "title": ["The title field is required."],
     *     "cost": ["The cost format is invalid."]
     *   }
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Milestone couldn't be created."
     * }
     */
    public function store_milestone(StoreMilestoneRequest $request)
    {
        $isApi = request()->get('isApi', false);
        try {
            $formFields = $request->only(['project_id', 'title', 'status', 'description', 'start_date', 'end_date', 'cost']);
            $milestone = $this->projectMilestoneService->createMilestone(
                $this->workspace,
                $this->user,
                $formFields,
                $isApi
            );

            return formatApiResponse(
                false,
                'Milestone created successfully.',
                [
                    'id' => $milestone->id,
                    'type' => 'milestone',
                    'parent_type' => 'project',
                    'parent_id' => $milestone->project_id
                ]
            );
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            Log::error('Milestone creation error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'Milestone couldn\'t be created.'
            ], 500);
        }
    }
    public function get_milestones($id)
    {
        try {
            $project = Project::findOrFail($id);
            $filters = [
                'search' => request('search'),
                'statuses' => request('statuses'),
                'date_between_from' => request('date_between_from', ''),
                'date_between_to' => request('date_between_to', ''),
                'start_date_from' => request('start_date_from', ''),
                'start_date_to' => request('start_date_to', ''),
                'end_date_from' => request('end_date_from', ''),
                'end_date_to' => request('end_date_to', ''),
            ];
            $sort = request('sort', 'id');
            $order = request('order', 'DESC');

            $milestonesQuery = $this->projectMilestoneService->getMilestones($project, $filters);
            $total = $milestonesQuery->count();
            $canEdit = checkPermission('edit_milestones');
            $canDelete = checkPermission('delete_milestones');
            $milestones = $milestonesQuery->orderBy($sort, $order)
                ->paginate(request("limit"))
                ->through(function ($milestone) use ($canEdit, $canDelete) {
                    $statusBadge = match ($milestone->status) {
                        'incomplete' => '<span class="badge bg-danger">' . get_label('incomplete', 'Incomplete') . '</span>',
                        'complete' => '<span class="badge bg-success">' . get_label('complete', 'Complete') . '</span>',
                        default => '<span class="badge bg-warning">' . get_label('pending', 'Pending') . '</span>'
                    };
                    $progress = '
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: ' . $milestone->progress . '%" aria-valuenow="' . $milestone->progress . '" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
                <h6 class="mt-2">' . $milestone->progress . '%</h6>';
                    $actions = '';
                    if ($canEdit) {
                        $actions .= '<a href="javascript:void(0);" class="edit-milestone" data-bs-toggle="modal" data-bs-target="#edit_milestone_modal" data-id="' . $milestone->id . '" title="' . get_label('update', 'Update') . '">' .
                            '<i class="bx bx-edit mx-1"></i>' .
                            '</a>';
                    }
                    if ($canDelete) {
                        $actions .= '<button title="' . get_label('delete', 'Delete') . '" type="button" class="btn delete" data-id="' . $milestone->id . '" data-type="milestone" data-table="project_milestones_table">' .
                            '<i class="bx bx-trash text-danger mx-1"></i>' .
                            '</button>';
                    }
                    return [
                        'id' => $milestone->id,
                        'title' => $milestone->title,
                        'status' => $statusBadge,
                        'progress' => $progress,
                        'cost' => format_currency($milestone->cost),
                        'start_date' => format_date($milestone->start_date),
                        'end_date' => format_date($milestone->end_date),
                        'created_by' => strpos($milestone->created_by, 'u_') === 0
                            ? formatUserHtml(User::find(substr($milestone->created_by, 2)))
                            : formatClientHtml(Client::find(substr($milestone->created_by, 2))),
                        'description' => $milestone->description,
                        'created_at' => format_date($milestone->created_at, true),
                        'updated_at' => format_date($milestone->updated_at, true),
                    'actions' => $actions ?: '-'
                    ];
                });
            return formatApiResponse(
                false,
                'Milestones Retrieved Successfully',
                ['rows' => $milestones->items(), 'total' => $total]
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Project not found."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "Could not retrieve milestones."
            ], 500);
        }
    }
    /**
     * Get a list of milestones for a project.
     *
     * This endpoint retrieves all milestones associated with a given project.
     * It supports searching, filtering, and sorting functionalities.
     *
     * @authenticated
     *
     * @group Milestone Management
     *
     * @urlParam id int required The ID of the project whose milestones are to be retrieved.
     * @queryParam search string optional Search for milestones by title, ID, cost, or description.
     * @queryParam sort string optional Field to sort by (default: "id").
     * @queryParam order string optional Sorting order (ASC/DESC, default: "DESC").
     * @queryParam statuses array optional Filter by milestone statuses.
     * @queryParam date_between_from date optional Filter milestones starting from this date.
     * @queryParam date_between_to date optional Filter milestones ending at this date.
     * @queryParam start_date_from date optional Filter milestones with a start date after this date.
     * @queryParam start_date_to date optional Filter milestones with a start date before this date.
     * @queryParam end_date_from date optional Filter milestones with an end date after this date.
     * @queryParam end_date_to date optional Filter milestones with an end date before this date.
     * @queryParam limit int optional Number of records per page.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 12,
     *       "title": "Design Phase",
     *       "status": Complete,
     *       "progress": "75",
     *       "cost": "₹1,500.00",
     *       "start_date": "2025-03-10",
     *       "end_date": "2025-03-20",
     *       "created_by": "John Doe",
     *       "description": "Initial design phase for the project.",
     *       "created_at": "2025-03-01",
     *       "updated_at": "2025-03-05",
     *
     *     }
     *   ],
     *   "total": 1
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Project not found."
     * }
     */
    public function get_milestones_api($id)
    {
        try {
            $project = Project::findOrFail($id);
            $filters = [
                'search' => request('search'),
                'statuses' => request('statuses'),
                'date_between_from' => request('date_between_from', ''),
                'date_between_to' => request('date_between_to', ''),
                'start_date_from' => request('start_date_from', ''),
                'start_date_to' => request('start_date_to', ''),
                'end_date_from' => request('end_date_from', ''),
                'end_date_to' => request('end_date_to', ''),
            ];
            $sort = request('sort', 'id');
            $order = request('order', 'DESC');

            $milestonesQuery = $this->projectMilestoneService->getMilestones($project, $filters);
            $total = $milestonesQuery->count();
            $milestones = $milestonesQuery->orderBy($sort, $order)
                ->paginate(request("limit"))
                ->through(function ($milestone) {
                    $statusBadge = match ($milestone->status) {
                        'incomplete' => get_label('incomplete', 'Incomplete'),
                        'complete' => get_label('complete', 'Complete'),
                        default => get_label('pending', 'Pending')
                    };
                    $progress = $milestone->progress;
                    $creator = strpos($milestone->created_by, 'u_') === 0
                        ? User::find(substr($milestone->created_by, 2))
                        : Client::find(substr($milestone->created_by, 2));
                    return [
                        'id' => $milestone->id,
                        'title' => $milestone->title,
                        'status' => $statusBadge,
                        'progress' => $progress,
                        'cost' => format_currency($milestone->cost),
                        'start_date' => format_date($milestone->start_date, to_format: 'Y-m-d'),
                        'end_date' => format_date($milestone->end_date, to_format: 'Y-m-d'),
                        'created_by' => $creator ? ucwords($creator->first_name) . ' ' . ucwords($creator->last_name) : 'N/A',
                        'description' => $milestone->description ? $milestone->description : ' ',
                        'created_at' => format_date($milestone->created_at, to_format: 'Y-m-d'),
                        'updated_at' => format_date($milestone->updated_at, to_format: 'Y-m-d'),
                    ];
                });
            return formatApiResponse(
                false,
                'Milestones Retrieved Successfully',
                ['data' => $milestones->items(), 'total' => $total]
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Project not found."
            ], 404);
        } catch (\Exception $e) {
            Log::error('Milestone API retrieval error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                "error" => true,
                "message" => "Could not retrieve milestones."
            ], 500);
        }
    }
    /**
     * Get details of a specific milestone.
     *
     * This endpoint retrieves details of a specific milestone by its ID.
     *
     * @authenticated
     *
     * @group Milestone Management
     *
     * @urlParam id int required The ID of the milestone to retrieve.
     *
     * @response 200 {
     *   "error": false,
     *   "milestone": {
     *     "id": 12,
     *     "title": "Design Phase",
     *     "status": "In Progress",
     *     "cost": "₹1,500.00",
     *     "start_date": "2025-03-10",
     *     "end_date": "2025-03-20",
     *     "description": "Initial design phase for the project.",
     *     "created_at": "2025-03-01",
     *     "updated_at": "2025-03-05"
     *   }
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Milestone not found."
     * }
     */
    public function get_milestone($id)
    {
        try {
            $milestone = Milestone::findOrFail($id);
            $milestone->cost = format_currency($milestone->cost, false, false);

            return formatApiResponse(
                false,
                'Milestone Retrieved Successfully',
                ['ms' => $milestone]
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Milestone not found."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "Could not retrieve milestone."
            ], 500);
        }
    }
    /**
     * Update an existing milestone.
     *
     * This endpoint updates a specified milestone. The user must be authenticated and have permission to modify the milestone.
     *
     * @authenticated
     *
     * @group Milestone Management
     *
     * @urlParam id int required The ID of the milestone to be updated.
     * @bodyParam title string required The updated title of the milestone.
     * @bodyParam status string required The updated status of the milestone.
     * @bodyParam start_date date optional The updated start date of the milestone (YYYY-MM-DD).
     * @bodyParam end_date date optional The updated end date of the milestone (YYYY-MM-DD).
     * @bodyParam cost numeric required The updated cost of the milestone.
     * @bodyParam progress int required The updated progress percentage of the milestone.
     * @bodyParam description string optional An updated description of the milestone.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Milestone updated successfully.",
     *   "id": 12,
     *   "type": "milestone",
     *   "parent_type": "project",
     *   "parent_id": 438
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "title": ["The title field is required."],
     *     "cost": ["The cost format is invalid."],
     *     "progress": ["The progress field is required."]
     *   }
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Milestone not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Milestone couldn't be updated."
     * }
     */
    public function update_milestone(Request $request)
    {
        $isApi = request()->get('isApi', false);
        try {
            $rules = [
                'title' => ['required', 'string', 'max:255'],
                'status' => ['required', 'string'],
                'start_date' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        $endDate = request()->input('end_date');
                        $errors = validate_date_format_and_order($value, $endDate);
                        if (!empty($errors['start_date'])) {
                            foreach ($errors['start_date'] as $error) {
                                $fail($error);
                            }
                        }
                    },
                ],
                'end_date' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        $startDate = request()->input('start_date');
                        $errors = validate_date_format_and_order($startDate, $value);
                        if (!empty($errors['end_date'])) {
                            foreach ($errors['end_date'] as $error) {
                                $fail($error);
                            }
                        }
                    },
                ],
                'cost' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        $error = validate_currency_format($value, 'cost');
                        if ($error) {
                            $fail($error);
                        }
                    }
                ],
                'progress' => ['required', 'integer', 'min:0', 'max:100'],
                'description' => ['nullable', 'string'],
            ];
            $request->validate($rules);
            $milestone = Milestone::findOrFail($request->input('id'));
            $milestone->update([
                'title' => $request->input('title'),
                'status' => $request->input('status'),
                'cost' => str_replace(',', '', $request->input('cost')),
                'progress' => $request->input('progress'),
                'description' => $request->input('description'),
                'start_date' => $request->filled('start_date')
                    ? format_date($request->input('start_date'), false, app('php_date_format'), 'Y-m-d')
                    : null,
                'end_date' => $request->filled('end_date')
                    ? format_date($request->input('end_date'), false, app('php_date_format'), 'Y-m-d')
                    : null,
            ]);
            return formatApiResponse(
                false,
                'Milestone Updated Successfully',
                [
                    'ms' => $milestone,
                    'id' => $milestone->id,
                    'type' => 'milestone',
                    'parent_type' => 'project',
                    'parent_id' => $milestone->project_id
                ]
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Milestone not found."
            ], 404);
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "Milestone couldn't be updated."
            ], 500);
        }
    }
    /**
     * Delete a milestone.
     *
     * This endpoint deletes a specified milestone. The user must be authenticated and have permission to delete milestones.
     *
     * @authenticated
     *
     * @group Milestone Management
     *
     * @urlParam id int required The ID of the milestone to delete.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Milestone deleted successfully.",
     *   "id": 12,
     *   "title": "Design Phase",
     *   "type": "milestone",
     *   "parent_type": "project",
     *   "parent_id": 438
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Milestone not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Milestone couldn't be deleted."
     * }
     */
    public function delete_milestone($id)
    {
        try {
            $milestone = Milestone::findOrFail($id);
            // Call the deletion service to delete the milestone
            DeletionService::delete(Milestone::class, $id, 'Milestone');
            return formatApiResponse(
                false,
                'Milestone Deleted Successfully',
                [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'type' => 'milestone',
                    'parent_type' => 'project',
                    'parent_id' => $milestone->project_id
                ]
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Milestone not found."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "Milestone couldn't be deleted."
            ], 500);
        }
    }
    public function delete_multiple_milestone(DeleteMultipleMilestoneRequest $request)
    {
        try {
            $result = $this->projectMilestoneService->deleteMultipleMilestones($request->ids);

            return response()->json([
                'error' => false,
                'message' => 'Milestone(s) deleted successfully.',
                'id' => $result['deleted_ids'],
                'titles' => $result['deleted_titles'],
                'type' => 'milestone',
                'parent_type' => 'project',
                'parent_id' => $result['parent_ids']
            ]);
        } catch (\Exception $e) {
            Log::error('Multiple milestone deletion error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while deleting milestones.'
            ], 500);
        }
    }
    /**
     * Update the status of a project.
     *
     * This endpoint updates the status of a specified project. The user must be authenticated and have permission to set the new status. A notification will be sent to all users and clients associated with the project.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @urlParam id int required The ID of the project whose status is to be updated.
     * @bodyParam statusId int required The ID of the new status to set for the project.
     * @bodyParam note string optional An optional note to attach to the project update.
     *
     * @response 200 {
     * "error": false,
     * "message": "Status updated successfully.",
     * "id": "438",
     * "type": "project",
     * "activity_message": "Madhavan Vaidya updated project status from Default to vbnvbnvbn",
     * "data": {
     * "id": 438,
     * "title": "Res Test",
     * "status": "vbnvbnvbn",
     * "priority": "dsfdsf",
     * "users": [
     * {
     * "id": 7,
     * "first_name": "Madhavan",
     * "last_name": "Vaidya",
     * "photo": "https://test-taskify.infinitietech.com/storage/photos/yxNYBlFLALdLomrL0JzUY2USPLILL9Ocr16j4n2o.png"
     * }
     * ],
     * "clients": [
     * {
     * "id": 103,
     * "first_name": "Test",
     * "last_name": "Test",
     * "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     * }
     * ],
     * "tags": [
     * {
     * "id": 45,
     * "title": "Tag from update project"
     * }
     * ],
     * "start_date": null,
     * "end_date": null,
     * "budget": "1000.00",
     * "task_accessibility": "assigned_users",
     * "description": null,
     * "note": null,
     * "favorite": 1,
     * "created_at": "07-08-2024 14:38:51",
     * "updated_at": "12-08-2024 13:49:33"
     * }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "id": [
     *       "The selected id is invalid."
     *     ],
     *     "statusId": [
     *       "The selected status id is invalid."
     *     ]
     *   }
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "You are not authorized to set this status."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Status couldn't be updated."
     * }
     */
    public function update_status(UpdateStatusRequest $request, $id = null)
    {
        $isApi = request()->get('isApi', false);
        try {
            if ($id) {
                $request->merge(['id' => $id]);
            }

            $project = Project::findOrFail($request->id);
            $status = Status::findOrFail($request->statusId);

            if (!canSetStatus($status)) {
                return response()->json(['error' => true, 'message' => 'You are not authorized to set this status.']);
            }

            $result = $this->projectService->updateStatus(
                $project,
                $this->user,
                $status,
                $request->input('note')
            );

            if ($result['activity_message'] === null) {
                return response()->json(['error' => true, 'message' => 'No status change detected.']);
            }

            return formatApiResponse(
                false,
                'Status updated successfully.',
                [
                    'id' => $project->id,
                    'type' => 'project',
                    'activity_message' => $result['activity_message'],
                    'data' => formatProject($result['project'])
                ]
            );
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            Log::error('Status update error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'Status couldn\'t be updated.'
            ], 500);
        }
    }
    /**
     * Update the priority of a project.
     *
     * This endpoint updates the priority of a specified project. The user must be authenticated and have permission to set the new priority.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @urlParam id int required The ID of the project whose priority is to be updated.
     * @bodyParam priorityId int required The ID of the new priority to set for the project.
     *
     * @response 200 {
     * "error": false,
     * "message": "Priority updated successfully.",
     * "id": "438",
     * "type": "project",
     * "activity_message": "Madhavan Vaidya updated project priority from Low to Medium",
     * "data": {
     * "id": 438,
     * "title": "Res Test",
     * "status": "Test From Pro",
     * "priority": "Medium",
     * "users": [
     * {
     * "id": 7,
     * "first_name": "Madhavan",
     * "last_name": "Vaidya",
     * "photo": "https://test-taskify.infinitietech.com/storage/photos/yxNYBlFLALdLomrL0JzUY2USPLILL9Ocr16j4n2o.png"
     * }
     * ],
     * "clients": [
     * {
     * "id": 103,
     * "first_name": "Test",
     * "last_name": "Test",
     * "photo": "https://test-taskify.infinitietech.com/storage/photos/no-image.jpg"
     * }
     * ],
     * "tags": [
     * {
     * "id": 45,
     * "title": "Tag from update project"
     * }
     * ],
     * "start_date": null,
     * "end_date": null,
     * "budget": "1000.00",
     * "task_accessibility": "assigned_users",
     * "description": null,
     * "note": null,
     * "favorite": 1,
     * "created_at": "07-08-2024 14:38:51",
     * "updated_at": "12-08-2024 13:58:55"
     * }
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "id": [
     *       "The selected id is invalid."
     *     ],
     *     "priorityId": [
     *       "The selected priority id is invalid."
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Priority couldn't be updated."
     * }
     */
    public function update_priority(UpdatePriorityRequest $request, $id = null)
    {
        $isApi = request()->get('isApi', false);
        try {
            if ($id) {
                $request->merge(['id' => $id]);
            }

            $project = Project::findOrFail($request->id);
            $priorityId = $request->priorityId;

            $result = $this->projectService->updatePriority($project, $this->user, $priorityId);

            if ($result['activity_message'] === null) {
                return response()->json(['error' => true, 'message' => 'No priority change detected.']);
            }

            return formatApiResponse(
                false,
                'Priority updated successfully.',
                [
                    'id' => $project->id,
                    'type' => 'project',
                    'activity_message' => $result['activity_message'],
                    'data' => formatProject($result['project'])
                ]
            );
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            Log::error('Priority update error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'Priority couldn\'t be updated.'
            ], 500);
        }
    }
    /**
     * Add a comment.
     *
     * This endpoint allows authenticated users to add comments to a specific model (e.g., tasks, projects).
     * Users can also attach files and mention other users.
     *
     * @authenticated
     *
     * @group Project Comments
     *
     * @urlParam id int required The ID of the project to add a comment to.
     * @bodyParam model_type string required The type of model being commented on (e.g., "Task", "Project").
     * @bodyParam model_id int required The ID of the model being commented on.
     * @bodyParam content string required The comment text.
     * @bodyParam parent_id int optional The ID of the parent comment (for replies).
     * @bodyParam attachments[] file optional An array of files to attach to the comment. Maximum file size is defined in the config.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Comment Added Successfully",
     *   "comment": {
     *     "id": 45,
     *     "commentable_type": "App\Models\Project",
     *     "commentable_id": 438,
     *     "content": "This is a sample comment with a mention @JohnDoe",
     *     "commenter_id": 7,
     *     "commenter_type": "App\\Models\\User",
     *     "parent_id": null,
     *     "created_at": "2 minutes ago",
     *     "attachments": [
     *       {
     *         "id": 1,
     *         "file_name": "document.pdf",
     *         "file_path": "comment_attachments/document.pdf",
     *         "file_type": "application/pdf"
     *       }
     *     ]
     *   },
     *   "user": {
     *     "id": 7,
     *     "name": "John Doe"
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "content": ["Please enter a comment."]
     *   }
     * }
     *
     * @response 500 {
     *   "success": false,
     *   "message": "Comment could not be added."
     * }
     */
    public function comments(StoreCommentRequest $request)
    {
        $isApi = request()->get('isApi', false);
        try {
            $fileValidationResponse = FileValidationHelper::validateFileUpload($request, 'attachments');
            if ($fileValidationResponse !== true) {
                return $fileValidationResponse;
            }

            $attachments = $request->hasFile('attachments') ? $request->file('attachments') : [];
            $result = $this->projectCommentService->createComment(
                $request->model_type,
                $request->model_id,
                $this->user,
                $request->content,
                $request->parent_id,
                $attachments
            );

            $comment = $result['comment'];
            sendMentionNotification(
                $comment,
                $result['mentioned_user_ids'],
                $this->workspace->id,
                $this->user->id,
                $result['mentioned_client_ids']
            );

            return response()->json([
                'success' => true,
                'message' => 'Comment Added Successfully',
                'comment' => $comment,
                'user' => $comment->commenter,
                'created_at' => $comment->created_at->diffForHumans()
            ]);
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            Log::error('Comment creation error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Comment could not be added.'
            ], 500);
        }
    }
    /**
     * Get details of a specific comment.
     *
     * This endpoint retrieves details of a specific comment by its ID, including any attachments.
     *
     * @authenticated
     *
     * @group Project Comments
     *
     * @urlParam id int required The ID of the comment to retrieve.
     *
     * @response 200 {
     *   "error": false,
     *   "comment": {
     *     "id": 45,
     *     "commentable_type": "App\\Models\\Project",
     *     "commentable_id": 438,
     *     "content": "This is a sample comment with a mention @JohnDoe",
     *     "commenter_id": 7,
     *     "commenter_type": "App\\Models\\User",
     *     "parent_id": null,
     *     "created_at": "2025-03-03 14:00:00",
     *     "updated_at": "2025-03-03 16:00:00",
     *     "attachments": [
     *       {
     *         "id": 1,
     *         "file_name": "document.pdf",
     *         "file_path": "comment_attachments/document.pdf",
     *         "file_type": "application/pdf"
     *       }
     *     ]
     *   }
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Comment not found."
     * }
     */
    public function get_comment(Request $request, $id)
    {
        try {
            $comment = Comment::with('attachments')->findOrFail($id);
            return response()->json([
                'error' => false,
                'comment' => $comment,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Comment not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Could not retrieve comment.'
            ], 500);
        }
    }
    /**
     * Update a comment.
     *
     * This endpoint updates a specified comment. The user must be authenticated and have permission to modify the comment.
     *
     * @authenticated
     *
     * @group Project Comments
     *
     * @bodyParam comment_id int required The ID of the comment to be updated.
     * @bodyParam content string required The updated content of the comment.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Comment updated successfully.",
     *   "id": 45,
     *   "type": "project"
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "content": ["Please enter a comment."]
     *   }
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Comment not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Comment couldn't be updated."
     * }
     */
    public function update_comment(UpdateCommentRequest $request)
    {
        $isApi = request()->get('isApi', false);
        try {
            $comment = Comment::findOrFail($request->comment_id);
            $result = $this->projectCommentService->updateComment($comment, $request->content);

            sendMentionNotification(
                $result['comment'],
                $result['mentioned_user_ids'],
                $this->workspace->id,
                $this->user->id,
                $result['mentioned_client_ids']
            );

            return response()->json([
                'error' => false,
                'message' => 'Comment updated successfully.',
                'id' => $result['comment']->id,
                'type' => 'project'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Comment not found."
            ], 404);
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            Log::error('Comment update error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                "error" => true,
                "message" => "Comment couldn't be updated."
            ], 500);
        }
    }
    /**
     * Delete a comment.
     *
     * This endpoint deletes a specified comment and removes its attachments from storage.
     * The user must be authenticated and have permission to delete comments.
     *
     * @authenticated
     *
     * @group Project Comments
     *
     * @queryParam comment_id int required The ID of the comment to delete.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Comment deleted successfully.",
     *   "id": 45,
     *   "type": "project"
     * }
     *
     * @response 422 {
     *   "error": true,
     *   "message": "Validation errors occurred",
     *   "errors": {
     *     "comment_id": ["The comment_id field is required."]
     *   }
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Comment not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Comment couldn't be deleted."
     * }
     */
    public function destroy_comment(Request $request)
    {
        try {
            $request->validate([
                'comment_id' => ['required', 'integer', 'exists:comments,id'],
            ]);
            $comment = Comment::findOrFail($request->comment_id);
            $this->projectCommentService->deleteComment($comment);

            return response()->json([
                'error' => false,
                'message' => 'Comment deleted successfully.',
                'id' => $comment->id,
                'type' => 'project'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Comment not found."
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                "error" => true,
                "message" => "Validation errors occurred",
                "errors" => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "Comment couldn't be deleted."
            ], 500);
        }
    }

    /**
     * Delete a comment attachment.
     *
     * This endpoint deletes a specific attachment belonging to a comment and removes its file from storage.
     * The user must be authenticated and have permission to delete comment attachments.
     *
     * @authenticated
     *
     * @group Project Comments
     *
     * @urlParam id int required The ID of the comment attachment to delete.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Attachment deleted successfully."
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Attachment not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Attachment couldn't be deleted."
     * }
     */
    public function destroy_comment_attachment($id)
    {
        try {
            $attachment = CommentAttachment::findOrFail($id);
            $this->projectCommentService->deleteCommentAttachment($attachment);

            return response()->json([
                'error' => false,
                'message' => 'Attachment deleted successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Attachment not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Comment attachment deletion error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    /**
     * Get all comments for a project with attachments and children.
     *
     * @authenticated
     * @group Project Comments
     * @urlParam id int required The ID of the project.
     * @response 200 {
     *   "error": false,
     *   "comments": [
     *     {
     *       "id": 1,
     *       "content": "Parent comment",
     *       "attachments": [...],
     *       "children": [
     *         {
     *           "id": 2,
     *           "content": "Reply",
     *           "attachments": [...],
     *           "children": [...]
     *         }
     *       ]
     *     }
     *   ]
     * }
     * @response 404 {
     *   "error": true,
     *   "message": "Project not found."
     * }
     */
    public function get_project_comments_api($id)
    {
        $limit = request('limit', 10);
        $offset = request('offset', 0);
        $search = request('search');

        try {
            $project = Project::findOrFail($id);

            $commentsQuery = $project->comments()
                ->whereNull('parent_id') // Only get parent comments, not child comments
                ->when($search, function ($query, $search) {
                    $query->where('content', 'LIKE', '%' . $search . '%');
                })
                ->orderBy('created_at', 'desc');
            $total = $commentsQuery->count();


            $comments = $commentsQuery
                ->skip($offset)
                ->take($limit)
                ->get();

            $result = $comments->map(function ($comment) {
                return formatComment($comment);
            });

            return response()->json([
                'error' => false,
                'data' => $result,
                'total' => $total,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Project not found.'
            ], 404);
        } catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => 'Could not retrieve comments.'
            ], 500);
        }
    }


    public function saveViewPreference(Request $request)
    {
        $view = $request->input('view');
        $prefix = isClient() ? 'c_' : 'u_';
        if (
            UserClientPreference::updateOrCreate(
                ['user_id' => $prefix . $this->user->id, 'table_name' => 'projects'],
                ['default_view' => $view]
            )
        ) {
            return response()->json(['error' => false, 'message' => 'Default View Set Successfully.']);
        } else {
            return response()->json(['error' => true, 'message' => 'Something Went Wrong.']);
        }
    }
    public function mind_map(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        $mindMapData = $this->getMindMapData($projectId);

        $customFields = CustomField::where('module', 'project')->get();

        return view('projects.mind_map', compact('mindMapData', 'project', 'customFields'));
    }

    /**
     * Get Mind Map Data of a specific project.
     *
     * This endpoint retrieves mind map data of a specific project by its ID.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @urlParam id int required The ID of the data to retrieve.
     *
     * @response 200 {
     *   "error": false,
     *   "data":  {
     *       "id": "project_2",
     *       "topic": "hola",
            "link": "https://dev-taskify.taskhub.company/projects/information/2",
            "isroot": true,
            "level": 1,
            "children": [
                {
                    "id": "tasks",
                    "topic": "Tasks",
                    "level": 2,
                    "children": [
                        {
                            "id": "task_4",
                            "topic": "Test",
                            "link": "https://dev-taskify.taskhub.company/tasks/information/4",
                            "children": [
                                {
                                    "id": "task_users_4",
                                    "topic": "Users",
                                    "children": [
                                        {
                                            "id": "task_user_4_1",
                                            "topic": "Admin User",
                                            "link": "https://dev-taskify.taskhub.company/users/profile/1"
                                        }
                                    ]
                                },
                                {
                                    "id": "task_clients_4",
                                    "topic": "Clients",
                                    "children": []
                                }
                            ]
                        },
                        {
                            "id": "task_5",
                            "topic": "test",
                            "link": "https://dev-taskify.taskhub.company/tasks/information/5",
                            "children": [
                                {
                                    "id": "task_users_5",
                                    "topic": "Users",
                                    "children": [
                                        {
                                            "id": "task_user_5_1",
                                            "topic": "Admin User",
                                            "link": "https://dev-taskify.taskhub.company/users/profile/1"
                                        }
                                    ]
                                },
                                {
                                    "id": "task_clients_5",
                                    "topic": "Clients",
                                    "children": []
                                }
                            ]
                        },
                        {
                            "id": "task_6",
                            "topic": "test 1",
                            "link": "https://dev-taskify.taskhub.company/tasks/information/6",
                            "children": [
                                {
                                    "id": "task_users_6",
                                    "topic": "Users",
                                    "children": [
                                        {
                                            "id": "task_user_6_1",
                                            "topic": "Admin User",
                                            "link": "https://dev-taskify.taskhub.company/users/profile/1"
                                        }
                                    ]
                                },
                                {
                                    "id": "task_clients_6",
                                    "topic": "Clients",
                                    "children": []
                                }
                            ]
                        }
                    ]
                },
                {
                    "id": "users",
                    "topic": "Users",
                    "children": [
                        {
                            "id": "user_1",
                            "topic": "Admin User",
                            "link": "https://dev-taskify.taskhub.company/users/profile/1"
                        }
                    ]
                },
                {
                    "id": "clients",
                    "topic": "Clients",
                    "children": []
                },
                {
                    "id": "milestones",
                    "topic": "Milestones",
                    "children": []
                },
                {
                    "id": "media",
                    "topic": "Media",
                    "children": []
                }
            ]
        }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Project not found."
     * }
     */
    public function getMindMapData($id)
    {
        $isApi = request()->get('isApi', false);

        try {
            $project = Project::with(['users', 'tasks.users', 'clients', 'milestones', 'media'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            if ($isApi) {
                return response()->json([
                    'error' => true,
                    'message' => 'Project not found.'
                ], 404);
            }
            if (!$project) {
                return response()->json([
                    'error' => true,
                    'message' => 'Project not found.'
                ], 404);
            }
        }

        $mindMapData = [
            'meta' => [
                'name' => $project->title,
                'author' => $project->created_by,
                'version' => '1.0'
            ],
            'format' => 'node_tree', // Specify format if required by your jsMind version
            'data' => [
                'id' => 'project_' . $project->id,
                'topic' => $project->title,
                'link' => route('projects.info', $project->id),
                'isroot' => true,
                'level' => 1,
                'children' => [
                    [
                        'id' => 'tasks',
                        'topic' => 'Tasks',
                        'level' => 2,
                        'children' => $project->tasks->map(function ($task) {
                            return [
                                'id' => 'task_' . $task->id,
                                'topic' => $task->title,
                                'link' => route('tasks.info', $task->id),
                                'children' => [
                                    [
                                        'id' => 'task_users_' . $task->id, // Make it unique with task ID
                                        'topic' => 'Users',
                                        'children' => $task->users->map(function ($user) use ($task) {
                                            return [
                                                'id' => 'task_user_' . $task->id . '_' . $user->id, // Unique ID
                                                'topic' => $user->first_name . ' ' . $user->last_name,
                                                'link' => route('users.profile', $user->id)
                                            ];
                                        })->toArray()
                                    ],
                                    [
                                        'id' => 'task_clients_' . $task->id, // Make it unique with task ID
                                        'topic' => 'Clients',
                                        'children' => $task->project->clients->map(function ($client) use ($task) {
                                            return [
                                                'id' => 'task_client_' . $task->id . '_' . $client->id, // Unique ID
                                                'topic' => $client->first_name . ' ' . $client->last_name,
                                                'link' => route('clients.profile', $client->id)
                                            ];
                                        })->toArray()
                                    ]
                                ]
                            ];
                        })->toArray()
                    ],
                    [
                        'id' => 'users',
                        'topic' => 'Users',
                        'children' => $project->users->map(function ($user) {
                            return [
                                'id' => 'user_' . $user->id,
                                'topic' => $user->first_name . ' ' . $user->last_name,
                                'link' => route('users.profile', $user->id)
                            ];
                        })->toArray()
                    ],
                    [
                        'id' => 'clients',
                        'topic' => 'Clients',
                        'children' => $project->clients->map(function ($client) {
                            return [
                                'id' => 'client_' . $client->id,
                                'topic' => $client->first_name . ' ' . $client->last_name,
                                'link' => route('clients.profile', $client->id)
                            ];
                        })->toArray()
                    ],
                    [
                        'id' => 'milestones',
                        'topic' => 'Milestones',
                        'children' => $project->milestones->map(function ($milestone) {
                            return [
                                'id' => 'milestone_' . $milestone->id,
                                'topic' => $milestone->title
                            ];
                        })->toArray()
                    ],
                    [
                        'id' => 'media',
                        'topic' => 'Media',
                        'children' => $project->media->map(function ($mediaItem) {
                            $isPublicDisk = $mediaItem->disk == 'public' ? 1 : 0;
                            $fileUrl = $isPublicDisk
                                ? asset('storage/project-media/' . $mediaItem->file_name)
                                : $mediaItem->getFullUrl();
                            return [
                                'id' => 'media_' . $mediaItem->id,
                                'topic' => $mediaItem->file_name,
                                'data' => [
                                    'url' => $fileUrl
                                ],
                                'link' => $fileUrl
                            ];
                        })->toArray()
                    ],
                ]
            ]
        ];
        if ($isApi) {

            return response()->json(
                formatApiResponse(
                    false,
                    'Mind map data retrieved successfully.',
                    [
                        'data' => $mindMapData['data']
                    ],
                )
            );
        } else {
            return $mindMapData;
        }
    }
    public function ganttProjectsTasks(Request $request)
    {
        $favorite = $request->input('favorite');
        $ganttData = $this->projectCalendarService->getGanttData(
            $this->workspace,
            $this->user,
            $favorite
        );
        return response()->json($ganttData);
    }
    protected function parseDate($dateString)
    {
        // Remove timezone abbreviation and parse the date
        $dateString = preg_replace('/\s\([^)]+\)$/', '', $dateString);
        try {
            $date = Carbon::parse($dateString);
            return $date->format('Y-m-d'); // Format to 'YYYY-MM-DD'
        } catch (\Exception $e) {
            return null;
        }
    }
    public function update_module_dates(Request $request)
    {
        $request->validate([
            'module' => 'required|array',
            'module.type' => 'required|string|in:project,task',
            'module.id' => 'required|integer',
            'start_date' => 'required|string',
            'end_date' => 'required|string',
        ]);
        $module = $request->input('module');
        // Preprocess and parse dates
        $startDateString = $request->input('start_date');
        $endDateString = $request->input('end_date');
        $startDate = $this->parseDate($startDateString);
        $endDate = $this->parseDate($endDateString);
        $request->validate([
            'start_date' => [
                'required',
                function ($attribute, $value, $fail) use ($startDate) {
                    if (!$startDate) {
                        $fail('The start date is not valid.');
                    }
                }
            ],
            'end_date' => [
                'required',
                function ($attribute, $value, $fail) use ($endDate, $startDate) {
                    if (!$endDate) {
                        $fail('The end date is not valid.');
                    } elseif ($endDate < $startDate) {
                        $fail('The end date must be after or equal to the start date.');
                    }
                }
            ],
        ]);
        if ($module['type'] == 'project') {
            $project = Project::find($module['id']);
            if ($project) {
                $this->projectService->updateProjectDates($project, $startDate, $endDate, false);
                return response()->json(['error' => false, 'message' => 'Project dates updated successfully.']);
            } else {
                return response()->json(['error' => true, 'message' => 'Project not found.']);
            }
        } elseif ($module['type'] == 'task') {
            $task = Task::find($module['id']);
            if ($task) {
                $task->start_date = $startDate;
                $task->due_date = $endDate;
                $task->save();
                return response()->json(['error' => false, 'message' => 'Task dates updated successfully.']);
            } else {
                return response()->json(['error' => true, 'message' => 'Task not found.']);
            }
        } else {
            return response()->json(['error' => true, 'message' => 'Unknown module type.']);
        }
    }
    /**
     * Get project status timeline.
     *
     * This endpoint retrieves the status change history of a project, sorted in descending order.
     *
     * @authenticated
     *
     * @group Project Management
     *
     * @urlParam id int required The ID of the project whose status timeline is to be retrieved.
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Status timeline retrieved successfully.",
     *   "status_timeline": [
     *     {
     *       "id": 1,
     *       "status": "In Progress",
     *       "previous_status": "Pending",
     *       "new_color": "#ffcc00",
     *       "old_color": "#cccccc",
     *       "changed_at": "2025-03-03"
     *     },
     *     {
     *       "id": 2,
     *       "status": "Completed",
     *       "previous_status": "In Progress",
     *       "new_color": "#00cc66",
     *       "old_color": "#ffcc00",
     *       "changed_at": "2025-03-05 16:00:00"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Project not found."
     * }
     *
     * @response 500 {
     *   "error": true,
     *   "message": "Could not retrieve status timeline."
     * }
     */
    public function get_status_timelines_api($id)
    {
        try {
            $project = Project::findOrFail($id);
            $statusTimelines = $project->statusTimelines
                ->sortByDesc('changed_at')
                ->map(function ($timeline) {
                    return [
                        'id' => $timeline->id,
                        'entity_id' => $timeline->entity_id,
                        'entity_type' => $timeline->entity_type,
                        'status' => $timeline->status,
                        'previous_status' => $timeline->previous_status,
                        'new_color' => $timeline->new_color,
                        'old_color' => $timeline->old_color,
                        'time_diff' => Carbon::parse($timeline->changed_at ?? null)->diffForHumans(),
                        'changed_time' => format_date(Carbon::parse($timeline->changed_at ?? null), false, to_format: 'H:i:s'),
                        'changed_at' => format_date(Carbon::parse($timeline->changed_at ?? null)),
                        'created_at' => format_date(Carbon::parse($timeline->created_at ?? null), to_format: 'Y-m-d'),
                        'updated_at' => format_date(Carbon::parse($timeline->updated_at ?? null), to_format: 'Y-m-d'),

                    ];
                })
                ->values();

            return formatApiResponse(
                false,
                'Status timelines retrieved successfully.',
                [
                    'data' => $statusTimelines,
                    'total' => $statusTimelines->count()
                ]
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "error" => true,
                "message" => "Project not found."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "Could not retrieve status timelines."
            ], 500);
        }
    }

    public function calendar_view()
    {
        $customFields = CustomField::where('module', 'project')->get();
        $is_favorites = 0;
        return view('projects.calendar_view', compact('is_favorites', 'customFields'));
    }
    public function get_calendar_data(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $events = $this->projectCalendarService->getCalendarEvents(
            $this->workspace,
            $this->user,
            $start,
            $end
        );

        return response()->json($events);
    }
    public function updateProjectDates(UpdateProjectDatesRequest $request)
    {
        $isApi = $request->get('isApi', false);
        try {
            $project = Project::findOrFail($request->input('id'));
            $updatedProject = $this->projectService->updateProjectDates(
                $project,
                $request->input('start_date'),
                $request->input('end_date'),
                $isApi
            );

            return formatApiResponse(
                false,
                'Updated successfully.',
                [
                    'id' => $updatedProject->id,
                    'type' => 'project',
                    'data' => formatProject($updatedProject)
                ]
            );
        } catch (ValidationException $e) {
            return formatApiValidationError($isApi, $e->errors());
        } catch (\Exception $e) {
            Log::error('Project dates update error in controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while updating project dates.',
            ], 500);
        }
    }

    public function getStatuses()
    {
        try {
            $statuses = $this->projectMetaService->getStatuses();
            return response()->json([
                'error' => false,
                'statuses' => $statuses,
                'message' => 'Statuses retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getStatuses: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while fetching statuses'
            ], 500);
        }
    }

    public function getPriorities()
    {
        try {
            $priorities = $this->projectMetaService->getPriorities();
            return response()->json([
                'error' => false,
                'priorities' => $priorities,
                'message' => 'Priorities retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getPriorities: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while fetching priorities'
            ], 500);
        }
    }
}
