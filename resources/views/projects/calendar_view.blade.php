@extends('layout')
@section('title')
    {{ get_label('projects', 'Projects') }} - {{ get_label('calendar_view', 'Calendar View') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
            <!-- Left Side: Breadcrumbs and Badge -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ url(getUserPreferences('projects', 'default_view')) }}">{{ get_label('projects', 'Projects') }}</a>
                        </li>
                        <li class="breadcrumb-item active">
                            {{ get_label('calendar_view', 'Calendar View') }}
                        </li>
                    </ol>
                </nav>
                
                @php
                    $projectsDefaultView = getUserPreferences('projects', 'default_view');
                @endphp
                @if ($projectsDefaultView === 'projects/calendar-view')
                    <span class="badge badge-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);" id="set-default-view" data-type="projects" data-view="calendar">
                        <span class="badge badge-neutral"><?= get_label('set_as_default_view', 'Set as Default View') ?></span>
                    </a>
                @endif
            </div>

            <!-- Right Side: View modes and Actions -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                @php
                    // Base URLs for different views
                    $listUrl = $is_favorites == 1 ? url('projects/list/favorite') : url('projects/list');
                    $gridUrl = $is_favorites == 1 ? url('projects/favorite') : url('projects');
                    $kanbanUrl = $is_favorites == 1 ? route('projects.kanban_view', ['type' => 'favorite']) : route('projects.kanban_view');
                    $ganttChartUrl = $is_favorites == 1 ? route('projects.gantt_chart', ['type' => 'favorite']) : route('projects.gantt_chart');
                    
                    // Get the statuses and tags from the request, if they exist
                    $selectedStatuses = request()->has('statuses') ? 'statuses[]=' . implode('&statuses[]=', request()->input('statuses')) : '';
                    $selectedTags = request()->has('tags') ? 'tags[]=' . implode('&tags[]=', request()->input('tags')) : '';
                    
                    // Build the query string by concatenating statuses and tags if they exist
                    $queryParams = '';
                    if ($selectedStatuses || $selectedTags) {
                        $queryParams = '?' . trim($selectedStatuses . '&' . $selectedTags, '&');
                    }
                    
                    // Final URLs with filters
                    $finalListUrl = url($listUrl . $queryParams);
                    $finalGridUrl = url($gridUrl . $queryParams);
                    $finalKanbanUrl = $kanbanUrl . $queryParams;
                @endphp

                <!-- View Toggles -->
                <div class="seg">
                    <a href="{{ $finalListUrl }}" class="seg-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('list_view', 'List view') ?>">
                        <i class='bx bx-list-ul'></i>
                    </a>
                    <a href="{{ $finalGridUrl }}" class="seg-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('grid_view', 'Grid view') ?>">
                        <i class='bx bxs-grid-alt'></i>
                    </a>
                    <a href="{{ $finalKanbanUrl }}" class="seg-btn d-none d-md-flex" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('kanban_view', 'Kanban View') ?>">
                        <i class='bx bx-layout'></i>
                    </a>
                    <a href="{{ $ganttChartUrl }}" class="seg-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('gantt_chart_view', 'Gantt Chart View') ?>">
                        <i class='bx bx-bar-chart'></i>
                    </a>
                    <a href="javascript:void(0);" class="seg-btn on" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('calendar_view', 'Calendar view') ?>">
                        <i class='bx bx-calendar'></i>
                    </a>
                </div>

                <!-- Create Action -->
                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_project_modal">
                    <button type="button" class="btn btn-sm btn-primary action_create_projects" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('create_project', 'Create project') ?>">
                        <i class='bx bx-plus'></i>
                    </button>
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-12">

                <x-ui.calendar-wrapper calendarId="projectCalenderDiv"
                    createButtonText="{{ get_label('create_project', 'Create project') }}"
                    createOffcanvasTarget="#create_project_offcanvas" entityType="projects" sidebarTitle="Project"
                    showStatusFilters="true" showPriorityFilters="true" />
            </div>
        </div>
    </div>
    <div class="modal fade" id="confirmDragProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLabel2"><?= get_label('confirm', 'Confirm!') ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?= get_label('confirm_update_project_dates', 'Are You Want to Update the Project Dates?') ?></p>
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
    <div class="modal fade" id="confirmResizeProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLabel2"><?= get_label('confirm', 'Confirm!') ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?= get_label('confirm_update_project_end_date', 'Are You Want to Update the Project End Date?') ?>
                    </p>
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
    <script src="{{ asset('assets/js/pages/project-calendar.js') }}"></script>
@endsection
