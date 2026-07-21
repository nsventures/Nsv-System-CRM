@extends('layout')
@section('title')
    @if ($is_favorites == 1)
        {{ get_label('favorite', 'Favorite') }}
    @endif
    <?= get_label('tasks', 'Tasks') ?> - <?= get_label('calendar_view', 'Calendar View') ?>
@endsection
@section('content')
    @php
        $routePrefix = Route::getCurrentRoute()->getPrefix();
    @endphp
    <div class="container-fluid">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
            <!-- Left Side: Breadcrumbs and Badge -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('home.index') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        @isset($project->id)
                            <li class="breadcrumb-item">
                                <a href="{{ url(getUserPreferences('projects', 'default_view')) }}"><?= get_label('projects', 'Projects') ?></a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ url('projects/information/' . $project->id) }}">{{ $project->title }}</a>
                            </li>
                        @endisset
                        <li class="breadcrumb-item">
                            <a href="{{ route('tasks.index') }}"><?= get_label('tasks', 'Tasks') ?></a>
                        </li>
                        @if ($is_favorites == 1)
                            <li class="breadcrumb-item">
                                <a href="javascript:void(0);"><?= get_label('favorite', 'Favorite') ?></a>
                            </li>
                        @endif
                        <li class="breadcrumb-item active">
                            <?= get_label('calendar', 'Calendar') ?>
                        </li>
                    </ol>
                </nav>

                @php
                    $taskDefaultView = getUserPreferences('tasks', 'default_view');
                @endphp
                @if ($taskDefaultView === 'tasks/calendar')
                    <span class="badge bg-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);" id="set-default-view" data-type="tasks" data-view="calendar">
                        <span class="badge bg-secondary"><?= get_label('set_as_default_view', 'Set as Default View') ?></span>
                    </a>
                @endif
            </div>

            <!-- Right Side: View modes and Actions -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                @php
                    $projectId = isset($project->id) ? $project->id : (request()->has('project') ? request('project') : '');
                    
                    // List View Url
                    $listUrl = isset($project->id) ? url('/projects/tasks/list/' . $project->id) : url('/tasks');
                    if (request()->has('status')) {
                        $listUrl .= (strpos($listUrl, '?') === false ? '?' : '&') . 'status=' . request('status');
                    }
                    if ($is_favorites) {
                        $listUrl .= (strpos($listUrl, '?') === false ? '?' : '&') . 'favorite=1';
                    }

                    // Draggable View Url
                    $draggableUrl = isset($project->id) ? url('/projects/tasks/draggable/' . $projectId) : url('/tasks/draggable');
                    if (request()->has('status')) {
                        $draggableUrl .= (strpos($draggableUrl, '?') === false ? '?' : '&') . 'status=' . request('status');
                    }
                    if ($is_favorites) {
                        $draggableUrl .= (strpos($draggableUrl, '?') === false ? '?' : '&') . 'favorite=1';
                    }
                @endphp

                <div class="seg">
                    <a href="{{ $listUrl }}" class="seg-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('list_view', 'List view') ?>">
                        <i class='bx bx-list-ul'></i>
                    </a>
                    <a href="{{ $draggableUrl }}" class="seg-btn d-none d-md-flex" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('draggable_view', 'Draggable View') ?>">
                        <i class='bx bxs-dashboard'></i>
                    </a>
                    <a href="{{ route('tasks.groupByTaskList') }}" class="seg-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('group_by_task_list', 'Group By Task List') ?>">
                        <i class='bx bx-align-middle'></i>
                    </a>
                    <a href="javascript:void(0);" class="seg-btn on" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('calendar_view', 'Calendar View') ?>">
                        <i class='bx bx-calendar'></i>
                    </a>
                </div>

                @if (getAuthenticatedUser() && getAuthenticatedUser()->can('create_tasks'))
                <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#create_task_offcanvas">
                    <button type="button" class="btn btn-sm btn-primary action_create_tasks" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('create_task', 'Create task') ?>">
                        <i class='bx bx-plus'></i>
                    </button>
                </a>
                @endif
            </div>
        </div>
        <input type="hidden" id="is_favorites" value="{{ $favorites ?? '' }}">
        <div class="row">
            <div class="col-12">
                  <x-ui.calendar-wrapper
                    calendarId="taskCalenderDiv"
                    createButtonText="{{ get_label('add_task', 'Add Task') }}"
                    createModalTarget="#create_task_modal"
                    entityType="tasks"
                    :showMiniCalendar="true"
                    sidebarTitle="Task"
                    showStatusFilters="true"
    showPriorityFilters="true"
                />
            </div>
        </div>
        <input type="hidden" id="projectId" value="{{ $projectId }}">
    </div>
    <div class="modal fade" id="confirmDragTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLabel2"><?= get_label('confirm', 'Confirm!') ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?= get_label('confirm_update_task_dates', 'Are You Want to Update the Task Dates?') ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="cancel" data-bs-dismiss="modal">
                        <?= get_label('close', 'Close') ?>
                    </button>
                    <button type="submit" class="btn btn-primary" id="confirm"><?= get_label('yes', 'Yes') ?></button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="confirmResizeTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLabel2"><?= get_label('confirm', 'Confirm!') ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?= get_label('confirm_update_task_end_date', 'Are You Want to Update the Task End Date?') ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="cancel" data-bs-dismiss="modal">
                        <?= get_label('close', 'Close') ?>
                    </button>
                    <button type="submit" class="btn btn-primary" id="confirm"><?= get_label('yes', 'Yes') ?></button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('page_scripts')
    <script src="{{ asset('assets/js/pages/tasks-calendar.js') }}"></script>
@endsection
