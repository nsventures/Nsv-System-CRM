@extends('layout')
@section('title')
{{ get_label('tasks_report', 'Tasks Report') }}
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mt-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home.index') }}">{{ get_label('home', 'Home') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        {{ get_label('reports', 'Reports') }}
                    </li>
                    <li class="breadcrumb-item active">
                        {{ get_label('tasks', 'Tasks') }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-body-secondary small fw-semibold text-uppercase">{{ get_label('total_tasks', 'Total Tasks') }}</span>
                        <div class="avatar avatar-sm">
                            <i class="bx bx-task"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold lh-sm" id="total-tasks">{{ get_label('loading', 'Loading...') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-body-secondary small fw-semibold text-uppercase">{{ get_label('due_tasks', 'Due Tasks') }}</span>
                            <i class="bx bx-info-circle ms-1 text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('due_tasks_info', 'Tasks have deadline today.') }}"></i>
                        </div>
                        <div class="avatar avatar-sm">
                            <i class="bx bx-calendar-exclamation"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold lh-sm" id="due-tasks">{{ get_label('loading', 'Loading...') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-body-secondary small fw-semibold text-uppercase">{{ get_label('overdue_tasks', 'Overdue Tasks') }}</span>
                        <div class="avatar avatar-sm">
                            <i class="bx bx-calendar-exclamation"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold lh-sm" id="overdue-tasks">{{ get_label('loading', 'Loading...') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-body-secondary small fw-semibold text-uppercase">{{ get_label('average_task_completion_days', 'Avg. Task Completion Days') }}</span>
                        <div class="avatar avatar-sm">
                            <i class="bx bx-time-five"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold lh-sm" id="average-task-completion-time">{{ get_label('loading', 'Loading...') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-body-secondary small fw-semibold text-uppercase">{{ get_label('urgent_tasks', 'Urgent Tasks') }}</span>
                            <i class="bx bx-info-circle ms-1 text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('urgent_tasks_info', 'Tasks with a \'Danger\' priority color that have passed their due date are considered urgent.') }}"></i>
                        </div>
                        <div class="avatar avatar-sm">
                            <i class="bx bx-calendar"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold lh-sm" id="urgent-tasks">{{ get_label('loading', 'Loading...') }}</h3>
                </div>
            </div>
        </div>
    </div>


    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                
                <div>
                    <h5 class="card-title mb-1">{{ get_label('tasks_report', 'Tasks Report') }}</h5>
                    <p class="text-muted mb-0 small">{{ get_label('filter_tasks_report', 'Filter tasks by date, project, user, client, status and priority') }}</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-primary" id="export_button" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('export_tasks_report', 'Export Tasks Report') }}">
                    <i class="bx bx-export"></i> {{ get_label('export', 'Export') }}
                </button>
                <button class="btn btn-secondary clear-report-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
                    <i class="bx bx-refresh"></i> {{ get_label('clear_filters', 'Clear Filters') }}
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <x-advanced-date-filters prefix="report" />
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('projects', 'Projects') }}</label>
                    <select class="form-select tom_projects_select" id="project_filter" aria-label="{{ get_label('select_projects', 'Select Projects') }}" multiple data-placeholder="<?= get_label('select_projects', 'Select Projects') ?>">
                    </select>
                </div>
                @if(isAdminOrHasAllDataAccess())
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('users', 'Users') }}</label>
                    <select class="form-select tom_users_select" id="user_filter" aria-label="{{ get_label('select_users', 'Select Users') }}" multiple data-placeholder="<?= get_label('select_users', 'Select Users') ?>">
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('clients', 'Clients') }}</label>
                    <select class="form-select tom_clients_select" id="client_filter" aria-label="{{ get_label('select_clients', 'Select Clients') }}" multiple data-placeholder="<?= get_label('select_clients', 'Select Clients') ?>">
                    </select>
                </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('status', 'Status') }}</label>
                    <select class="form-select tom_statuses_filter" id="status_filter" aria-label="{{ get_label('select_statuses', 'Select Statuses') }}" multiple data-placeholder="<?= get_label('select_statuses', 'Select Statuses') ?>">
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('priority', 'Priority') }}</label>
                    <select class="form-select tom_priorities_filter" id="priority_filter" aria-label="{{ get_label('select_priorities', 'Select Priorities') }}" multiple data-placeholder="<?= get_label('select_priorities', 'Select Priorities') ?>">
                    </select>
                </div>
            </div>
        </div>
    </div>
    @php
    $visibleColumns = getUserPreferences('tasks_report');
    @endphp
    <div class="card border shadow-sm">
        <div class="card-body p-0">
            <!-- Table -->
            <div class="table-responsive text-nowrap">
                <input type="hidden" id="multi_select">
                <input type="hidden" id="data_type" value="report">
                <input type="hidden" id="save_column_visibility" data-type="tasks_report" data-table="tasks_report_table">
                <table id="tasks_report_table" class="table table-striped table-bordered"
                    data-toggle="table"
                    data-url="{{ route('reports.tasks-report-data') }}"
                data-loading-template="loadingTemplate"
                data-icons-prefix="bx"
                data-icons="icons"
                data-show-refresh="true"
                data-total-field="total"
                data-trim-on-search="false"
                data-data-field="tasks"
                data-page-list="[5, 10, 20, 50, 100, 200]"
                data-search="true"
                data-side-pagination="server"
                data-show-columns="true"
                data-pagination="true"
                data-sort-name="id"
                data-sort-order="desc"
                data-mobile-responsive="true"
                data-query-params="tasks_report_query_params">
                <thead>
                    <tr>
                        <th rowspan="2" data-field="id" data-visible="{{ (in_array('id', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('id', 'ID') }}</th>
                        <th rowspan="2" data-field="title" data-visible="{{ (in_array('title', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('title', 'Title') }}</th>
                        <th rowspan="2" data-field="description" data-visible="{{ (in_array('description', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('description', 'Description') }}</th>
                        <th rowspan="2" data-field="project" data-visible="{{ (in_array('project', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('project', 'Project') }}</th>
                        <th colspan="2">{{ get_label('dates', 'Dates') }}</th>
                        <th rowspan="2" data-field="status" data-visible="{{ (in_array('status', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('status', 'Status') }}</th>
                        <th rowspan="2" data-field="priority" data-visible="{{ (in_array('priority', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('priority', 'Priority') }}</th>
                        <th colspan="4">{{ get_label('duration', 'Duration') }}</th>
                        <th colspan="2">{{ get_label('team', 'Team') }}</th>
                        <th colspan="2">{{ get_label('clients', 'Clients') }}</th>
                    </tr>
                    <tr>
                        <th data-field="start_date" data-visible="{{ (in_array('start_date', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('start_date', 'Start Date') }}</th>
                        <th data-field="due_date" data-visible="{{ (in_array('due_date', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('due_date', 'Due Date') }}</th>
                        <th data-field="time.total_days" data-visible="{{ (in_array('total_days', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('total_days', 'Total Days') }}</th>
                        <th data-field="time.days_elapsed" data-visible="{{ (in_array('days_elapsed', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('days_elapsed', 'Days Elapsed') }}</th>
                        <th data-field="time.days_remaining" data-visible="{{ (in_array('days_remaining', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('days_remaining', 'Days Remaining') }}</th>
                        <th data-field="time.overdue_days" data-visible="{{ (in_array('overdue_days', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('overdue_days', 'Overdue Days') }}</th>
                        <th data-field="users" data-visible="{{ (in_array('members', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}">{{ get_label('members', 'Members') }}</th>
                        <th data-field="total_users" data-visible="{{ (in_array('total_users', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('total', 'Total') }}</th>
                        <th data-field="clients" data-visible="{{ (in_array('clients', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}">{{ get_label('clients', 'Clients') }}</th>
                        <th data-field="total_clients" data-visible="{{ (in_array('total_clients', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}">{{ get_label('total', 'Total') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('page_scripts')
<script>
    var tasks_report_export_url = "{{ route('reports.export-tasks-report') }}";
</script>
<script src="{{ asset('assets/js/pages/tasks-report.js') }}?v={{ time() }}"></script>
@endsection
