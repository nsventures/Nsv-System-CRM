@extends('layout')
@section('title')
<?= $is_favorite == 1 ? get_label('favorite_projects', 'Favorite projects') : get_label('projects', 'Projects') ?> - <?= get_label('gantt_chart_view', 'Gantt Chart View') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
        <!-- Left Side: Breadcrumbs and Badge -->
        <div class="d-flex align-items-center flex-wrap gap-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{url('home')}}">{{ get_label('home', 'Home') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{url(getUserPreferences('projects', 'default_view'))}}"><?= get_label('projects', 'Projects') ?></a>
                    </li>
                    @if ($is_favorite==1)
                    <li class="breadcrumb-item"><?= get_label('favorite', 'Favorite') ?></li>
                    @endif
                    <li class="breadcrumb-item active">{{ get_label('gantt_chart_view', 'Gantt Chart View') }}</li>
                </ol>
            </nav>
            
            @php
                $projectDefaultView = getUserPreferences('projects', 'default_view');
            @endphp
            @if ($projectDefaultView === 'projects/gantt-chart')
                <span class="badge badge-primary"><?= get_label('default_view', 'Default View') ?></span>
            @else
                <a href="javascript:void(0);" id="set-default-view" data-type="projects" data-view="gantt-chart">
                    <span class="badge badge-neutral"><?= get_label('set_as_default_view', 'Set as Default View') ?></span>
                </a>
            @endif
        </div>

        <!-- Right Side: View modes and Actions -->
        <div class="d-flex align-items-center flex-wrap gap-2">
            @php
                // Base URLs for different views
                $listUrl = $is_favorite == 1 ? url('projects/list/favorite') : url('projects/list');
                $gridUrl = $is_favorite == 1 ? url('projects/favorite') : url('projects');
                $kanbanUrl = $is_favorite == 1 ? route('projects.kanban_view', ['type' => 'favorite']) : route('projects.kanban_view');
                
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
                <a href="javascript:void(0);" class="seg-btn on" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('gantt_chart_view', 'Gantt Chart View') ?>">
                    <i class='bx bx-bar-chart'></i>
                </a>
                <a href="{{ route('projects.calendar_view') }}" class="seg-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('calendar_view', 'Calendar view') ?>">
                    <i class='bx bx-calendar'></i>
                </a>
            </div>

            <!-- Create Action -->
            <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#create_project_offcanvas">
                <button type="button" class="btn btn-sm btn-primary action_create_projects" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('create_project', 'Create project') ?>">
                    <i class='bx bx-plus'></i>
                </button>
            </a>
        </div>
    </div>
    @php
    // Get selected statuses and tags from the request
    $selectedStatuses = request()->input('statuses', []);
    $selectedTags = request()->input('tags', []);

    $filterStatuses = \App\Models\Status::whereIn('id', $selectedStatuses)->get();
    $filterTags = \App\Models\Tag::whereIn('id', $selectedTags)->get();
    @endphp
    <div class="row d-none">
        <div class="col-md-4 mb-3">
            <select class="tk-select statuses_filter" id="selected_statuses" name="statuses[]" aria-label="Default select example" data-placeholder="<?= get_label('filter_by_statuses', 'Filter by statuses') ?>" data-allow-clear="true" multiple>
                @foreach($filterStatuses as $status)
                <option value="{{ $status->id }}" selected>{{ $status->title }}</option>
                @endforeach
            </select>
        </div>
        <!-- <div class="col-md-4 mb-3">
            <select id="selected_tags" class="tk-select tags_select" name="tag[]" multiple="multiple" data-placeholder="<?= get_label('filter_by_tags', 'Filter by tags') ?>" data-allow-clear="true" multiple>
                @foreach($filterTags as $tag)
                <option value="{{ $tag->id }}" selected>{{ $tag->title }}</option>
                @endforeach
            </select>
        </div> -->
        <div class="col-md-1">
            <div>
                <button type="button" id="filter" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('filter', 'Filter') ?>"><i class='bx bx-filter-alt'></i></button>
            </div>
        </div>
    </div>
    <input type="hidden" id="favorite" value="{{$is_favorite}}">
    <div class="alert alert-primary" role="alert">
        <i class="bx bx-info-circle"></i>
        {{ get_label('project_gantt_info', 'Double-click a project or task to view the detail page.') }}
    </div>
    <input type="hidden" id="is_favorites" value="{{$is_favorite??''}}">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <!-- Right Section: Views and Current Date -->
                <div class="d-flex align-items-center">
                    <div class="btn-group me-3">
                        <button id="day-view"
                            class="btn btn-light view-btns border btn-primary">{{ get_label('days', 'Days') }}</button>
                        <button id="week-view"
                            class="btn btn-light view-btns border">{{ get_label('weeks', 'Weeks') }}</button>
                        <button id="month-view"
                            class="btn btn-light view-btns border">{{ get_label('months', 'Months') }}</button>
                    </div>
                </div>
            </div>
            <!-- Gantt chart container -->
            <div id="gantt" class="rounded-3 border"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmUpdateDates" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="exampleModalLabel2"><?= get_label('confirm', 'Confirm!') ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?= get_label('confirm_update_dates', 'Do you want to update the date(s)?') ?></p>
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
<script src="{{ asset('assets/js/pages/project-gantt-chart.js') }}"></script>
@endsection
