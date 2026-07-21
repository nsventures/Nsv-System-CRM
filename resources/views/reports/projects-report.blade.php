@extends('layout')
@section('title')
{{ get_label('projects_report', 'Projects Report') }}
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
                        {{ get_label('projects', 'Projects') }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Summary Cards -->
   <div class="row g-3 mb-4">

    <!-- Total Projects -->
    <div class="col-xl col-lg-4 col-md-6">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-body-secondary small fw-semibold text-uppercase">
                        {{ get_label('total_projects', 'Total Projects') }}
                    </span>
                    <div class="avatar avatar-sm ">
                        <i class="bx bx-briefcase-alt-2"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold lh-sm" id="total-projects">
                    {{ get_label('loading', 'Loading...') }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Total Tasks -->
    <div class="col-xl col-lg-4 col-md-6">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-body-secondary small fw-semibold text-uppercase">
                        {{ get_label('total_tasks', 'Total Tasks') }}
                    </span>
                    <div class="avatar avatar-sm ">
                        <i class="bx bx-task"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold lh-sm" id="total-tasks">
                    {{ get_label('loading', 'Loading...') }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Team Members -->
    <div class="col-xl col-lg-4 col-md-6">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-body-secondary small fw-semibold text-uppercase">
                        {{ get_label('total_team_members', 'Total Team Members') }}
                    </span>
                    <div class="avatar avatar-sm ">
                        <i class="bx bx-group"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold lh-sm" id="total-team-members">
                    {{ get_label('loading', 'Loading...') }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Avg Overdue -->
    <div class="col-xl col-lg-4 col-md-6">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-body-secondary small fw-semibold text-uppercase">
                        {{ get_label('average_overdue_days_per_project', 'Avg. Overdue Days/Project') }}
                    </span>
                    <div class="avatar avatar-sm ">
                        <i class="bx bx-time-five"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold lh-sm" id="average-overdue-days-per-project">
                    {{ get_label('loading', 'Loading...') }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Due Projects -->
    <div class="col-xl col-lg-4 col-md-6">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="text-body-secondary small fw-semibold text-uppercase">
                            {{ get_label('due_projects', 'Due Projects') }}
                        </span>
                        <i class="bx bx-info-circle  ms-1"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="{{ get_label('due_projects_info', 'Projects have deadline today.') }}">
                        </i>
                    </div>
                    <div class="avatar avatar-sm">
                        <i class="bx bx-calendar-check"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold lh-sm" id="due-projects-percentage">
                    {{ get_label('loading', 'Loading...') }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Overdue Projects -->
    <div class="col-xl col-lg-4 col-md-6">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-body-secondary small fw-semibold text-uppercase">
                        {{ get_label('overdue_projects', 'Overdue Projects') }}
                    </span>
                    <div class="avatar avatar-sm ">
                        <i class="bx bx-calendar-exclamation"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold lh-sm" id="overdue-projects-percentage">
                    {{ get_label('loading', 'Loading...') }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Total Overdue Days -->
    <div class="col-xl col-lg-4 col-md-6">
        <div class="card h-100 border shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-body-secondary small fw-semibold text-uppercase">
                        {{ get_label('total_overdue_days', 'Total Overdue Days') }}
                    </span>
                    <div class="avatar avatar-sm ">
                        <i class="bx bx-calendar"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold lh-sm" id="total-overdue-days">
                    {{ get_label('loading', 'Loading...') }}
                </h3>
            </div>
        </div>
    </div>

</div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
               
                <div>
                    <h5 class="card-title mb-1">{{ get_label('projects_report', 'Projects Report') }}</h5>
                    <p class="text-muted mb-0 small">{{ get_label('filter_projects_report', 'Filter projects by date, user, client, status and priority') }}</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary" id="export_button" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('export_projects_report', 'Export Projects Report') }}">
                    <i class="bx bx-export"></i> {{ get_label('export', 'Export') }}
                </button>
                <button type="button" class="btn btn-secondary clear-report-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
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
    <div class="card border shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive text-nowrap">
                <input type="hidden" id="data_type" value="report">
                <table id="projects_report_table" data-toggle="table"
                    data-url="{{ route('reports.project-report-data') }}" data-loading-template="loadingTemplate"
                    data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total"
                    data-trim-on-search="false" data-data-field="projects" data-page-list="[5, 10, 20, 50, 100, 200]"
                    data-search="true" data-side-pagination="server" data-pagination="true"
                    data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                    data-query-params="project_report_query_params">
                    <thead>
                        <tr>
                            <th rowspan="2" data-field="id" data-sortable="true">{{ get_label('id', 'ID') }}</th>
                            <th rowspan="2" data-field="title" data-sortable="true">{{ get_label('title', 'Title') }}</th>
                            <th rowspan="2" data-field="description" data-sortable="true" data-visible="false">{{ get_label('description', 'Description') }}</th>
                            <th colspan="2">{{ get_label('dates', 'Dates') }}</th>
                            <th rowspan="2" data-field="status" data-sortable="true">{{ get_label('status', 'Status') }}</th>
                            <th rowspan="2" data-field="priority" data-sortable="true">{{ get_label('priority', 'Priority') }}</th>
                            <th rowspan="2" data-field="budget.total" data-sortable="true">{{ get_label('budget', 'Budget') }}</th>
                            <th colspan="4">{{ get_label('duration', 'Duration') }}</th>
                            <th colspan="4">{{ get_label('tasks', 'Tasks') }}</th>
                            <th colspan="2">{{ get_label('team', 'Team') }}</th>
                            <th colspan="2">{{ get_label('clients', 'Clients') }}</th>
                        </tr>
                        <tr>
                            <th data-field="start_date" data-sortable="true">{{ get_label('start_date', 'Start Date') }}</th>
                            <th data-field="end_date" data-sortable="true">{{ get_label('end_date', 'End Date') }}</th>
                            <th data-field="time.total_days" data-sortable="true">{{ get_label('total_days', 'Total Days') }}</th>
                            <th data-field="time.days_elapsed" data-sortable="true">{{ get_label('days_elapsed', 'Days Elapsed') }}</th>
                            <th data-field="time.days_remaining" data-sortable="true">{{ get_label('days_remaining', 'Days Remaining') }}</th>
                            <th data-field="time.overdue_days" data-sortable="true">{{ get_label('overdue_days', 'Overdue Days') }}</th>
                            <th data-field="tasks.total" data-sortable="true">{{ get_label('total', 'Total') }}</th>
                            <th data-field="tasks.due" data-sortable="true">{{ get_label('due', 'Due') }}</th>
                            <th data-field="tasks.overdue" data-sortable="true">{{ get_label('overdue', 'Overdue') }}</th>
                            <th data-field="tasks.overdue_days" data-sortable="true">{{ get_label('overdue_days', 'Overdue Days') }}</th>
                            <th data-field="users">{{ get_label('users', 'Users') }}</th>
                            <th data-field="team.total_members" data-sortable="true">{{ get_label('total', 'Total') }}</th>
                            <th data-field="clients">{{ get_label('clients', 'Clients') }}</th>
                            <th data-field="total_clients">{{ get_label('total', 'Total') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_scripts')
<script>
    var projects_report_export_url = "{{ route('reports.export-projects-report') }}";
</script>
<script src="{{ asset('assets/js/pages/projects-report.js') }}?v={{ time() }}"></script>
@endsection

