<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Http\Requests\DashboardDataRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\Client;
use App\Models\Status;
use App\Models\Project;
use App\Models\Workspace;
use App\Models\CustomField;
use App\Models\LeaveRequest;
use App\Services\DashboardService;
use App\Services\CalendarEventService;
use App\Services\BirthdayAnniversaryService;
use App\Services\LeaveQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

class HomeController extends Controller
{
    protected $workspace;
    protected $user;
    protected $statuses;
    protected DashboardService $dashboardService;
    protected CalendarEventService $calendarEventService;
    protected BirthdayAnniversaryService $birthdayAnniversaryService;
    protected LeaveQueryService $leaveQueryService;

    public function __construct(DashboardService $dashboardService, CalendarEventService $calendarEventService, BirthdayAnniversaryService $birthdayAnniversaryService, LeaveQueryService $leaveQueryService)
    {
        $this->dashboardService = $dashboardService;
        $this->calendarEventService = $calendarEventService;
        $this->birthdayAnniversaryService = $birthdayAnniversaryService;
        $this->leaveQueryService = $leaveQueryService;
        $this->middleware(function ($request, $next) {
            // fetch session and use it in entire class with constructor
            $this->workspace = Workspace::find(getWorkspaceId());
            $this->user = getAuthenticatedUser();
            return $next($request);
        });
        $this->statuses = Status::all();
    }
    public function index(Request $request)
    {
        $auth_user = getAuthenticatedUser(); // Or auth()->user(), depending on your setup
        return view('dashboard', compact('auth_user'));
    }

    public function getDashboardData(DashboardDataRequest $request)
    {
        try {
            $data = $this->dashboardService->getDashboardData(
                $this->workspace,
                $this->user,
                $request->validated()
            );

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Dashboard data error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An error occurred while fetching dashboard data.', 'message' => $e->getMessage()], 500);
        }
    }
    public function getUsersList(Request $request)
    {
        $search = $request->input('search');
        $ids = $request->input('ids', []);

        $users = $this->workspace->users()
            ? ($this->workspace->users()
            ->when($search, fn($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($ids, fn($query) => $query->whereIn('id', $ids))
                ->get(['id', 'name']))
            : collect();

        return response()->json($users);
    }
    public function upcoming_birthdays()
    {
        $filters = [
            'search' => request('search'),
            'order' => request('order', 'ASC'),
            'upcoming_days' => (int)request('upcoming_days', 30),
            'user_ids' => request('user_ids'),
            'client_ids' => request('client_ids'),
            'page' => request('page', 1),
            'limit' => request('limit', 10),
        ];

        $result = $this->birthdayAnniversaryService->getUpcomingBirthdays($this->workspace, $filters);
        return response()->json($result);
    }
    /**
     * List or search users with birthdays today or upcoming.
     *
     * This endpoint retrieves a list of users with birthdays occurring today or within a specified range of days. The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Dashboard Management
     *
     * @queryParam search string Optional. The search term to filter users by first name or last name or combination of first name and last name or User ID or date of birth. Example: John
     * @queryParam order string Optional. The sort order for the `dob` column. Acceptable values are `ASC` or `DESC`. Default is `ASC`. Example: DESC
     * @queryParam upcoming_days integer Optional. The number of days from today to consider for upcoming birthdays. Default is 30. Example: 15
     * @queryParam user_ids array Optional. The specific user IDs to filter the results. Example: [123, 456]
     * @queryParam limit integer Optional. The number of results to return per page. Default is 15. Example: 10
     * @queryParam offset integer Optional. The number of results to skip before starting to collect the result set. Default is 0. Example: 5
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Upcoming birthdays retrieved successfully",
     *   "total": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "member": "John Doe",
     *       "photo": "http://example.com/storage/photos/john_doe.jpg",
     *       "birthday_count": 30,
     *       "days_left": 10,
     *       "dob": "Tue, 2024-08-08"
     *     }
     *   ]
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Upcoming birthdays not found.",
     *   "data": []
     * }
     */
    public function upcomingBirthdaysApi(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'order' => $request->input('order', 'ASC'),
            'upcoming_days' => (int)$request->input('upcoming_days', 30),
            'user_ids' => (array)$request->input('user_ids', []),
            'client_ids' => (array)$request->input('client_ids', []),
            'limit' => (int)$request->input('limit', 15),
            'offset' => (int)$request->input('offset', 0),
        ];

        $result = $this->birthdayAnniversaryService->getUpcomingBirthdaysApi($this->workspace, $filters);
        return formatApiResponse($result['error'], $result['message'], ['total' => $result['total'], 'data' => $result['data']]);
    }
    public function upcoming_work_anniversaries()
    {
        $filters = [
            'search' => request('search'),
            'order' => request('order', 'ASC'),
            'upcoming_days' => (int)request('upcoming_days', 30),
            'user_ids' => request('user_ids', []),
            'client_ids' => request('client_ids', []),
            'page' => request('page', 1),
            'limit' => request('limit', 10),
        ];

        $result = $this->birthdayAnniversaryService->getUpcomingAnniversaries($this->workspace, $filters);
        return response()->json($result);
    }
    /**
     * List or search users with work anniversaries today or upcoming.
     *
     * This endpoint retrieves a list of users with work anniversaries occurring today or within a specified range of days. The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Dashboard Management
     *
     * @queryParam search string Optional. The search term to filter users by first name or last name or combination of first name and last name or User ID or date of joining. Example: John
     * @queryParam order string Optional. The sort order for the `doj` column. Acceptable values are `ASC` or `DESC`. Default is `ASC`. Example: DESC
     * @queryParam upcoming_days integer Optional. The number of days from today to consider for upcoming work anniversaries. Default is 30. Example: 15
     * @queryParam user_ids array Optional. The specific user IDs to filter the results. Example: [123, 456]
     * @queryParam limit integer Optional. The number of results to return per page. Default is 15. Example: 10
     * @queryParam offset integer Optional. The number of results to skip before starting to collect the result set. Default is 0. Example: 5
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Upcoming work anniversaries retrieved successfully",
     *   "total": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "member": "John Doe",
     *       "photo": "http://example.com/storage/photos/john_doe.jpg",
     *       "anniversary_count": 5,
     *       "days_left": 10,
     *       "doj": "Tue, 2024-08-08"
     *     }
     *   ]
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Upcoming work anniversaries not found.",
     *   "data": []
     * }
     */
    public function upcomingWorkAnniversariesApi(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'order' => $request->input('order', 'ASC'),
            'upcoming_days' => (int)$request->input('upcoming_days', 30),
            'user_ids' => (array)$request->input('user_ids', []),
            'client_ids' => (array)$request->input('client_ids', []),
            'limit' => (int)$request->input('limit', 15),
            'offset' => (int)$request->input('offset', 0),
        ];

        $result = $this->birthdayAnniversaryService->getUpcomingAnniversariesApi($this->workspace, $filters);
        return formatApiResponse($result['error'], $result['message'], ['total' => $result['total'], 'data' => $result['data']]);
    }
    public function members_on_leave()
    {
        $filters = [
            'search'        => request('search'),
            'sort'          => request('sort', 'from_date'),
            'order'         => request('order', 'ASC'),
            'upcoming_days' => (int)request('upcoming_days', 30),
            'user_ids'      => request('user_ids', []),
            'limit'         => (int)request('limit', 10),
            'page'          => (int)request('page', 1),
        ];

        $result = $this->leaveQueryService->getMembersOnLeave($this->workspace, $this->user, $filters);

        return response()->json([
            'rows'  => $result['rows'],
            'total' => $result['total'],
        ]);
    }
    /**
     * List members currently on leave or scheduled to be on leave.
     *
     * This endpoint retrieves a list of members who are currently on leave or scheduled to be on leave within a specified range of days.
     * The user must be authenticated to perform this action.
     *
     * @authenticated
     *
     * @group Dashboard Management
     *
     * @queryParam search string Optional. The search term to filter users by first name or last name or combination of first name and last name or User ID or date of joining. Example: John
     * @queryParam sort string Optional. The field to sort by. Acceptable values are `from_date` and `to_date`. Default is `from_date`. Example: to_date
     * @queryParam order string Optional. The sort order. Acceptable values are `ASC` or `DESC`. Default is `ASC`. Example: DESC
     * @queryParam upcoming_days integer Optional. The number of days from today to consider for upcoming leave. Default is 30. Example: 15
     * @queryParam user_ids array Optional. The specific user IDs to filter the results. Example: [123, 456]
     * @queryParam limit integer Optional. The number of results to return per page. Default is 15. Example: 10
     * @queryParam offset integer Optional. The number of results to skip before starting to collect the result set. Default is 0. Example: 5
     *
     * @response 200 {
     *   "error": false,
     *   "message": "Members on leave retrieved successfully.",
     *   "total": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "member": "John Doe",
     *       "photo": "http://example.com/storage/photos/john_doe.jpg",
     *       "from_date": "Mon, 2024-07-15",
     *       "to_date": "Fri, 2024-07-19",
     *       "type": "Full",
     *       "duration": "5 days",
     *       "days_left": 0
     *     }
     *   ]
     * }
     *
     * @response 200 {
     *   "error": true,
     *   "message": "Members on leave not found.",
     *   "data": []
     * }
     */
    public function membersOnLeaveApi(Request $request)
    {
        $filters = [
            'search'        => $request->input('search'),
            'sort'          => $request->input('sort', 'from_date'),
            'order'         => $request->input('order', 'ASC'),
            'upcoming_days' => (int)$request->input('upcoming_days', 30),
            'user_ids'      => (array)$request->input('user_ids', []),
            'limit'         => (int)$request->input('limit', 15),
            'offset'        => (int)$request->input('offset', 0),
        ];

        $result = $this->leaveQueryService->getMembersOnLeaveApi($this->workspace, $this->user, $filters);

        return formatApiResponse(
            $result['error'],
            $result['message'],
            [
                'total' => $result['total'],
                'data'  => $result['data'],
            ]
        );
    }
    public function upcoming_birthdays_calendar(Request $request)
    {
        // Parse ISO 8601 dates from FullCalendar (handles timezone offset)
        // Try multiple formats to handle different scenarios
        try {
            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:sP', $request->startDate)->startOfDay();
        } catch (\Exception $e) {
            $startDate = \Carbon\Carbon::parse($request->startDate)->startOfDay();
        }

        try {
            $endDate = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:sP', $request->endDate)->startOfDay();
        } catch (\Exception $e) {
            $endDate = \Carbon\Carbon::parse($request->endDate)->startOfDay();
        }

        $events = $this->calendarEventService->getBirthdayEvents($this->workspace, $startDate, $endDate);

        return response()->json($events);
    }

    public function upcoming_work_anniversaries_calendar(Request $request)
    {
        $startDate = \Carbon\Carbon::parse($request->startDate);
        $endDate = \Carbon\Carbon::parse($request->endDate);

        $events = $this->calendarEventService->getWorkAnniversaryEvents($this->workspace, $startDate, $endDate);

        return response()->json($events);
    }

    public function members_on_leave_calendar(Request $request)
    {
        $startDate = \Carbon\Carbon::parse($request->startDate);
        $endDate = \Carbon\Carbon::parse($request->endDate);

        $events = $this->calendarEventService->getLeaveEvents(
            $this->workspace,
            $startDate,
            $endDate,
            is_admin_or_leave_editor(),
            $this->user->id
        );

        return response()->json($events);
    }
    /**
     * Get Statistics
     *
     * This endpoint retrieves workspace-specific statistics related to projects, tasks, users, clients, todos, and meetings. The user must be authenticated and have the necessary permissions to manage (if applicable) each respective module.
     *
     * @group Dashboard Management
     *
     * @authenticated
     *
     * @response {
     *   "error": false,
     *   "message": "Statistics retrieved successfully",
     *   "data": {
     *     "total_projects": 8,
     *     "total_tasks": 8,
     *     "total_users": 8,
     *     "total_clients": 8,
     *     "total_meetings": 8,
     *     "total_todos": 0,
     *     "completed_todos": 0,
     *     "pending_todos": 0,
     *     "status_wise_projects": [
     *       {
     *         "id": 1,
     *         "title": "In Progress",
     *         "color": "primary",
     *         "total_projects": 4
     *       },
     *       {
     *         "id": 2,
     *         "title": "Completed",
     *         "color": "success",
     *         "total_projects": 4
     *       }
     *     ],
     *     "status_wise_tasks": [
     *       {
     *         "id": 1,
     *         "title": "In Progress",
     *         "color": "primary",
     *         "total_tasks": 4
     *       },
     *       {
     *         "id": 2,
     *         "title": "Completed",
     *         "color": "success",
     *         "total_tasks": 4
     *       }
     *     ]
     *   }
     * }
     * @response 500 {
     *   "error": true,
     *   "message": "An error occurred while retrieving statistics: Internal server error message"
     * }
     */
    public function getStatistics()
    {
        try {
            // Define an array of colors
            $colors = [
                '#63ed7a',
                '#ffa426',
                '#fc544b',
                '#6777ef',
                '#FF00FF',
                '#53ff1a',
                '#ff3300',
                '#0000ff',
                '#00ffff',
                '#99ff33',
                '#003366',
                '#cc3300',
                '#ffcc00',
                '#ff9900',
                '#3333cc',
                '#ffff00',
                '#FF5733',
                '#33FF57',
                '#5733FF',
                '#FFFF33',
                '#A6A6A6',
                '#FF99FF',
                '#6699FF',
                '#666666',
                '#FF6600',
                '#9900CC',
                '#FF99CC',
                '#FFCC99',
                '#99CCFF',
                '#33CCCC',
                '#CCFFCC',
                '#99CC99',
                '#669999',
                '#CCCCFF',
                '#6666FF',
                '#FF6666',
                '#99CCCC',
                '#993366',
                '#339966',
                '#99CC00',
                '#CC6666',
                '#660033',
                '#CC99CC',
                '#CC3300',
                '#FFCCCC',
                '#6600CC',
                '#FFCC33',
                '#9933FF',
                '#33FF33',
                '#FFFF66',
                '#9933CC',
                '#3300FF',
                '#9999CC',
                '#0066FF',
                '#339900',
                '#666633',
                '#330033',
                '#FF9999',
                '#66FF33',
                '#6600FF',
                '#FF0033',
                '#009999',
                '#CC0000',
                '#999999',
                '#CC0000',
                '#CCCC00',
                '#00FF33',
                '#0066CC',
                '#66FF66',
                '#FF33FF',
                '#CC33CC',
                '#660099',
                '#663366',
                '#996666',
                '#6699CC',
                '#663399',
                '#9966CC',
                '#66CC66',
                '#0099CC',
                '#339999',
                '#00CCCC',
                '#CCCC99',
                '#FF9966',
                '#99FF00',
                '#66FF99',
                '#336666',
                '#00FF66',
                '#3366CC',
                '#CC00CC',
                '#00FF99',
                '#FF0000',
                '#00CCFF',
                '#000000',
                '#FFFFFF'
            ];
            // Initialize response data
            $statusCountsProjects = [];
            $statusCountsTasks = [];
            $total_projects_count = 0;
            $total_tasks_count = 0;
            $total_users_count = 0;
            $total_clients_count = 0;
            $total_todos_count = 0;
            $total_completed_todos_count = 0;
            $total_pending_todos_count = 0;
            $total_meetings_count = 0;
            // Fetch total counts
            if ($this->user->can('manage_projects')) {
                $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects ?? [] : $this->user->projects ?? [];
                $total_projects_count = $projects->count();
            }
            if ($this->user->can('manage_tasks')) {
                $tasks = isAdminOrHasAllDataAccess() ? $this->workspace->tasks ?? [] : $this->user->tasks() ?? [];
                $total_tasks_count = $tasks->count();
            }
            if ($this->user->can('manage_users')) {
                $users = $this->workspace->users ?? [];
                $total_users_count = count($users);
            }
            if ($this->user->can('manage_clients')) {
                $clients = $this->workspace->clients ?? [];
                $total_clients_count = count($clients);
            }
            $todos = $this->user->todos;
            $total_todos_count = $todos->count();
            $total_completed_todos_count = $todos->where('is_completed', true)->count();
            $total_pending_todos_count = $todos->where('is_completed', false)->count();
            if ($this->user->can('manage_meetings')) {
                $meetings = isAdminOrHasAllDataAccess() ? $this->workspace->meetings ?? [] : $this->user->meetings ?? [];
                $total_meetings_count = $meetings->count();
            }
            // Assign colors to status-wise projects
            if ($this->user->can('manage_projects')) {
                foreach ($this->statuses as $status) {
                    $projectCount = isAdminOrHasAllDataAccess() ? count($status->projects) : $this->user->status_projects($status->id)->count();
                    $statusCountsProjects[] = [
                        'id' => $status->id,
                        'title' => $status->title,
                        'color' => $status->color,
                        'chart_color' => '0Xff' . strtoupper(ltrim($colors[array_rand($colors)], '#')),
                        // Assign random chart color
                        'total_projects' => $projectCount
                    ];
                }
                usort($statusCountsProjects, fn($a, $b) => $b['total_projects'] <=> $a['total_projects']);
            }
            // Assign colors to status-wise tasks
            if ($this->user->can('manage_tasks')) {
                foreach ($this->statuses as $status) {
                    $taskCount = isAdminOrHasAllDataAccess() ? count($status->tasks) : $this->user->status_tasks($status->id)->count();
                    $statusCountsTasks[] = [
                        'id' => $status->id,
                        'title' => $status->title,
                        'color' => $status->color,
                        'chart_color' => '0Xff' . strtoupper(ltrim($colors[array_rand($colors)], '#')),    // Assign random chart color
                        'total_tasks' => $taskCount
                    ];
                }
                usort($statusCountsTasks, fn($a, $b) => $b['total_tasks'] <=> $a['total_tasks']);
            }
            // Return response
            return formatApiResponse(
                false,
                'Statistics retrieved successfully.',
                [
                    'data' => [
                        'total_projects' => $total_projects_count,
                        'total_tasks' => $total_tasks_count,
                        'total_users' => $total_users_count,
                        'total_clients' => $total_clients_count,
                        'total_meetings' => $total_meetings_count,
                        'total_todos' => $total_todos_count,
                        'completed_todos' => $total_completed_todos_count,
                        'pending_todos' => $total_pending_todos_count,
                        'status_wise_projects' => $statusCountsProjects,
                        'status_wise_tasks' => $statusCountsTasks
                    ]
                ]
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while retrieving statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
