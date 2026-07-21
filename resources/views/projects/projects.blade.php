@extends('layout')
@section('title')
    <?= get_label('projects', 'Projects') ?> - <?= get_label('list_view', 'List view') ?>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
            <!-- Left Side: Breadcrumbs and Badge -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ url(getUserPreferences('projects', 'default_view')) }}"><?= get_label('projects', 'Projects') ?></a>
                        </li>
                        @if ($is_favorites == 1)
                            <li class="breadcrumb-item">
                                <a href="javascript:void(0);"><?= get_label('favorite', 'Favorite') ?></a>
                            </li>
                        @endif
                        <li class="breadcrumb-item active">
                            <?= get_label('list', 'List') ?>
                        </li>
                    </ol>
                </nav>
                
                @php
                    $projectDefaultView = getUserPreferences('projects', 'default_view');
                @endphp
                @if ($projectDefaultView === 'projects/list')
                    <span class="badge bg-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);" id="set-default-view" data-type="projects" data-view="list">
                        <span class="badge bg-secondary"><?= get_label('set_as_default_view', 'Set as Default View') ?></span>
                    </a>
                @endif
            </div>

            <!-- Right Side: View modes and Actions -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                @php
                    // Base URLs for different views
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
                    $finalGridUrl = url($gridUrl . $queryParams);
                    $finalKanbanUrl = $kanbanUrl . $queryParams;
                @endphp

                <!-- View Toggles -->
                <div class="seg">
                    <a href="javascript:void(0);" class="seg-btn on" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('list_view', 'List view') ?>">
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
        {{-- @dd($is_favorites) --}}
        <x-projects-card :projects="$projects" :favorites="$is_favorites" :customFields="$projectCustomFields" />
    </div>
@endsection
