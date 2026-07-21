@extends('layout')
@section('title')
    <?= $is_favorite == 1 ? get_label('favorite_projects', 'Favorite projects') : get_label('projects', 'Projects') ?> -
    <?= get_label('grid_view', 'Grid view') ?>
@endsection
@php
    $user = getAuthenticatedUser();
@endphp
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
                        @if ($is_favorite == 1)
                            <li class="breadcrumb-item">
                                <a href="javascript:void(0);"><?= get_label('favorite', 'Favorite') ?></a>
                            </li>
                        @endif
                        <li class="breadcrumb-item active">
                            <?= get_label('grid', 'Grid') ?>
                        </li>
                    </ol>
                </nav>
                
                @php
                    $projectDefaultView = getUserPreferences('projects', 'default_view');
                @endphp
                @if (!$projectDefaultView || $projectDefaultView === 'projects')
                    <span class="badge bg-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);" id="set-default-view" data-type="projects" data-view="grid">
                        <span class="badge bg-secondary"><?= get_label('set_as_default_view', 'Set as Default View') ?></span>
                    </a>
                @endif
            </div>

            <!-- Right Side: View modes and Actions -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                @php
                    // Base URLs for different views
                    $listUrl = $is_favorite == 1 ? url('projects/list/favorite') : url('projects/list');
                    $kanbanUrl = $is_favorite == 1 ? route('projects.kanban_view', ['type' => 'favorite']) : route('projects.kanban_view');
                    $ganttChartUrl = $is_favorite == 1 ? route('projects.gantt_chart', ['type' => 'favorite']) : route('projects.gantt_chart');

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
                    $finalKanbanUrl = $kanbanUrl . $queryParams;
                @endphp

                <!-- View Toggles -->
                <div class="seg">
                    <a href="{{ $finalListUrl }}" class="seg-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('list_view', 'List view') ?>">
                        <i class='bx bx-list-ul'></i>
                    </a>
                    <a href="javascript:void(0);" class="seg-btn on" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('grid_view', 'Grid view') ?>">
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
        @php
                // Get selected statuses and tags from the request
                $selectedStatuses = request()->input('statuses', []);
                $selectedTags = request()->input('tags', []);

                $filterStatuses = \App\Models\Status::whereIn('id', $selectedStatuses)->get();
                $filterTags = \App\Models\Tag::whereIn('id', $selectedTags)->get();
            @endphp
        <div class="card mb-4">
            
            <div class="card-body py-3">
                <div class="row g-3 align-items-end justify-content-center">
                    <div class="col-md-4">
                        <select class="tk-select tom-select-sort" id="sort" aria-label="Default select example"
                            data-placeholder="<?= get_label('sort_by', 'Sort By') ?>" data-allow-clear="true">
                            <option value=""></option>
                            <option value="newest" <?= request()->sort && request()->sort == 'newest' ? 'selected' : '' ?>>
                                <?= get_label('newest', 'Newest') ?></option>
                            <option value="oldest" <?= request()->sort && request()->sort == 'oldest' ? 'selected' : '' ?>>
                                <?= get_label('oldest', 'Oldest') ?></option>
                            <option value="recently-updated"
                                <?= request()->sort && request()->sort == 'recently-updated' ? 'selected' : '' ?>>
                                <?= get_label('most_recently_updated', 'Most recently updated') ?></option>
                            <option value="earliest-updated"
                                <?= request()->sort && request()->sort == 'earliest-updated' ? 'selected' : '' ?>>
                                <?= get_label('least_recently_updated', 'Least recently updated') ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="tk-select tom_statuses_filter" id="selected_statuses" name="statuses[]"
                            aria-label="Default select example"
                            data-placeholder="<?= get_label('filter_by_statuses', 'Filter by statuses') ?>" data-allow-clear="true"
                            multiple>
                            @foreach ($filterStatuses as $status)
                                <option value="{{ $status->id }}" selected>{{ $status->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="selected_tags" class="tk-select tom_tags_filter" name="tag[]" multiple="multiple"
                            data-placeholder="<?= get_label('filter_by_tags', 'Filter by tags') ?>" data-allow-clear="true">
                            @foreach ($filterTags as $tag)
                                <option value="{{ $tag->id }}" selected>{{ $tag->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @if (is_countable($projects) && count($projects) > 0)
            @php
                $showSettings =
                    $user->can('edit_projects') || $user->can('delete_projects') || $user->can('create_projects');
                $canEditProjects = $user->can('edit_projects');
                $canDeleteProjects = $user->can('delete_projects');
                $canDuplicateProjects = $user->can('create_projects');
                $webGuard = Auth::guard('web')->check();
            @endphp
            <div class="row g-4 mt-1 tk-project-grid">
                @foreach ($projects as $project)
                    <div class="col-md-6 col-xl-4">
                        <div class="tcard h-100 tk-project-card" data-card-id="{{ $project->id }}">
                            <div class="tcard-meta">
                                <a href="{{ url('projects/information/' . $project->id) }}" class="tcard-code mono" target="_blank">#{{ $project->id }}</a>
                                <div class="tcard-actions">
                                    <i class='bx {{ getFavoriteStatus($project->id) ? "bxs-star text-warning" : "bx-star" }} favorite-icon tcard-ic tk-ic-fav' data-id="{{ $project->id }}" data-favorite="{{ getFavoriteStatus($project->id) ? 1 : 0 }}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ getFavoriteStatus($project->id) ? get_label('remove_favorite', 'Click to remove from favorite') : get_label('add_favorite', 'Click to mark as favorite') }}" style="cursor:pointer"></i>
                                    <i class='bx {{ getPinnedStatus($project->id) ? "bxs-pin text-danger" : "bx-pin" }} pinned-icon tcard-ic tk-ic-pin' data-id="{{ $project->id }}" data-pinned="{{ getPinnedStatus($project->id) ? 1 : 0 }}" data-type="projects" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ getPinnedStatus($project->id) ? get_label('click_unpin', 'Click to Unpin') : get_label('click_pin', 'Click to Pin') }}" style="cursor:pointer"></i>
                                    @if ($showSettings)
                                    <div class="dropdown tcard-ic">
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"><x-tk-icon name="moreV" /></a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($canEditProjects)
                                            <li><a href="javascript:void(0);" class="dropdown-item edit-project" data-offcanvas="true" data-id="{{ $project->id }}"><x-tk-icon name="edit" /> {{ get_label('update', 'Update') }}</a></li>
                                            @endif
                                            @if ($canDuplicateProjects)
                                            <li><a href="javascript:void(0);" class="dropdown-item duplicate" data-type="projects" data-id="{{ $project->id }}" data-title="{{ $project->title }}" data-reload="true"><x-tk-icon name="copy" /> {{ get_label('duplicate', 'Duplicate') }}</a></li>
                                            @endif
                                            @if ($canDeleteProjects)
                                            <li><a href="javascript:void(0);" class="dropdown-item delete" data-reload="true" data-type="projects" data-id="{{ $project->id }}"><x-tk-icon name="trash" class="tk-ic-danger" /> {{ get_label('delete', 'Delete') }}</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <h4 class="tcard-title"><a href="{{ url('projects/information/' . $project->id) }}" target="_blank">{{ $project->title }}</a></h4>

                            <div class="tcard-tags">
                                <span class="tag tag-priority" style="background: var(--bg-1); color: var(--fg-1);">
                                    <strong class="text-muted fw-normal me-1"><?= get_label('status', 'Status') ?>:</strong>
                                    <span class="text-{{ $project->status->color }}">●</span> {{ $project->status->title }}
                                </span>
                                @if ($project->priority)
                                @php
                                    $pColorMap = [
                                        'success' => 'var(--ok)', 'danger' => 'var(--err)', 'warning' => 'var(--warn)',
                                        'info' => 'var(--info)', 'primary' => 'var(--signal)', 'secondary' => 'var(--fg-3)',
                                    ];
                                    $pColor = $pColorMap[$project->priority->color] ?? 'var(--fg-3)';
                                @endphp
                                <span class="tag tag-priority" style="color: {{ $pColor }};">
                                    <strong class="text-muted fw-normal me-1"><?= get_label('priority', 'Priority') ?>:</strong>
                                    ● {{ $project->priority->title }}
                                </span>
                                @endif
                                @foreach ($project->tags as $tag)
                                <span class="tag"><span class="text-{{$tag->color}}">●</span> {{$tag->title}}</span>
                                @endforeach
                                @if ($project->start_date)
                                <span class="tag tag-due" style="color: var(--ok);">
                                    <strong class="text-muted fw-normal me-1"><?= get_label('starts_at', 'Starts at') ?>:</strong>
                                    <x-tk-icon name="calendar" size="12" /> {{ format_date($project->start_date) }}
                                </span>
                                @endif
                                @if ($project->end_date)
                                <span class="tag tag-due">
                                    <strong class="text-muted fw-normal me-1"><?= get_label('ends_at', 'Ends at') ?>:</strong>
                                    <x-tk-icon name="calendar" size="12" /> {{ format_date($project->end_date) }}
                                </span>
                                @endif
                                @if ($project->budget != '')
                                <span class="tag tag-note">
                                    <strong class="text-muted fw-normal me-1"><?= get_label('budget', 'Budget') ?>:</strong>
                                    {{ format_currency($project->budget) }}
                                </span>
                                @endif
                            </div>

                            <div class="d-flex align-items-center mt-2 mb-2 flex-grow-1" style="font-size: 13px; color: var(--fg-2);">
                                <x-tk-icon name="task" class="me-1 tk-ic-muted" />
                                <b style="font-family: monospace; font-size: 14px; margin-right: 4px;"><?= isAdminOrHasAllDataAccess() ? count($project->tasks) : $auth_user->project_tasks($project->id)->count() ?></b>
                                <?= get_label('tasks', 'Tasks') ?>
                                <a href="{{ url('projects/tasks/draggable/' . $project->id) }}" class="ms-auto"><button type="button" class="btn btn-sm rounded-pill btn-outline-primary" style="padding: 2px 10px; font-size: 11px;"><?= get_label('tasks', 'Tasks') ?></button></a>
                            </div>

                            <div class="tcard-foot tk-pfoot mt-1">
                                <div class="tk-pfoot-people">
                                    <div class="tk-pgroup">
                                        <span class="tk-pgroup-lbl">{{ get_label('users', 'Users') }}</span>
                                        <span class="av-stack tk-av-stack">
                                            @php $users = $project->users; $userCount = count($users); $displayed = 0; @endphp
                                            @if ($userCount > 0)
                                                @foreach ($users as $u)
                                                    @if ($displayed < 3)
                                                        <a href="{{ url('/users/profile/' . $u->id) }}" target="_blank" class="av">
                                                            <img src="{{ $u->photo ? asset('storage/' . $u->photo) : asset('storage/photos/no-image.jpg') }}" loading="lazy" alt="{{ $u->first_name }}" class="rounded-circle" title="{{ $u->first_name }} {{ $u->last_name }}">
                                                        </a>
                                                    @php $displayed++; @endphp
                                                    @else @break @endif
                                                @endforeach
                                                @if ($userCount > 3) <span class="av av-more">+{{ $userCount - 3 }}</span> @endif
                                            @else
                                                <span class="text-muted small">{{ get_label('not_assigned', 'Not assigned') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="tk-pgroup">
                                        <span class="tk-pgroup-lbl">{{ get_label('clients', 'Clients') }}</span>
                                        <span class="av-stack tk-av-stack">
                                            @php $clients = $project->clients; $clientCount = $clients->count(); $displayedClients = 0; @endphp
                                            @if ($clientCount > 0)
                                                @foreach ($clients as $c)
                                                    @if ($displayedClients < 3)
                                                        <a href="{{ url('/clients/profile/' . $c->id) }}" target="_blank" class="av">
                                                            <img src="{{ $c->photo ? asset('storage/' . $c->photo) : asset('storage/photos/no-image.jpg') }}" loading="lazy" alt="{{ $c->first_name }}" class="rounded-circle" title="{{ $c->first_name }} {{ $c->last_name }}">
                                                        </a>
                                                    @php $displayedClients++; @endphp
                                                    @else @break @endif
                                                @endforeach
                                                @if ($clientCount > 3) <span class="av av-more">+{{ $clientCount - 3 }}</span> @endif
                                            @else
                                                <span class="text-muted small">{{ get_label('not_assigned', 'Not assigned') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="tcard-stats mono">
                                    <a href="javascript:void(0);" class="quick-view tcard-ic" data-id="{{ $project->id }}" data-type="project" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('quick_view', 'Quick View') }}"><x-tk-icon name="info" /></a>
                                    @if ($webGuard || $project->client_can_discuss)
                                    <a href="{{ route('projects.info', ['id' => $project->id]) }}#navs-top-discussions" class="tcard-ic" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('discussions', 'Discussions') }}"><x-tk-icon name="msg" /></a>
                                    @endif
                                    <a href="{{ url('projects/mind-map/' . $project->id) }}" class="tcard-ic" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('mind_map', 'Mind Map') }}"><x-tk-icon name="sitemap" /></a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-12 mt-3 tk-project-pager">
                    {{ $projects->links('components.pagination') }}
                </div>
            </div>
            <!-- delete project modal -->
        @else
            <?php $type = 'projects'; ?>
            <x-empty-state-card :type="$type" />
        @endif
    </div>
    <script>
        var add_favorite = '<?= get_label('add_favorite', 'Click to mark as favorite') ?>';
        var remove_favorite = '<?= get_label('remove_favorite', 'Click to remove from favorite') ?>';
    </script>
@endsection
@section('page_scripts')
    <script src="{{ asset('assets/js/pages/project-grid.js') }}"></script>
@endsection
