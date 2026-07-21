@extends('layout')
@section('title')
<?= get_label('project_details', 'Project details') ?>
@endsection
@section('content')
<div class="container-fluid">
    {{-- ============================================================
         PROJECT BOARD HEADER  (design-system: eyebrow / title / meta
         / avatar-stack / actions) — matches Taskify Revamp Kit
         pages/projects/index.blade.php
         ============================================================ --}}
    <header class="tk-proj-head mt-4 mb-3">
        <div class="tk-proj-headmain">
            <div class="tk-proj-eyebrow mono">
                <span class="kcol-dot kcol-dot-{{ $project->status->color }}"></span>
                {{ strtoupper(str_replace(' ', '-', $project->title)) }}@if($project->clients->isNotEmpty()) · {{ strtoupper($project->clients->first()->first_name.' '.$project->clients->first()->last_name) }}@endif
            </div>
            <h1 class="tk-proj-title">
                {{ $project->title }}
                <a href="javascript:void(0);" class="tk-proj-ic">
                    <i class='bx {{getFavoriteStatus($project->id) ? "bxs" : "bx"}}-star favorite-icon text-warning' data-id="{{$project->id}}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{getFavoriteStatus($project->id) ? get_label('remove_favorite', 'Click to remove from favorite') : get_label('add_favorite', 'Click to mark as favorite')}}" data-favorite="{{getFavoriteStatus($project->id) ? 1 : 0}}"></i>
                </a>
                <a href="javascript:void(0);" class="tk-proj-ic">
                    <i class='bx {{getPinnedStatus($project->id) ? "bxs" : "bx"}}-pin pinned-icon text-success' data-id="{{$project->id}}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{getPinnedStatus($project->id) ? get_label('click_unpin', 'Click to Unpin') : get_label('click_pin', 'Click to Pin')}}" data-pinned="{{getPinnedStatus($project->id)}}" data-require_reload="0"></i>
                </a>
            </h1>
            <div class="tk-proj-meta">
                <span><x-tk-icon name="check" size="13" /> {{ $project->tasks->count() }} {{ get_label('tasks', 'tasks') }}</span>
                <span><x-tk-icon name="users" size="13" /> {{ $project->users->count() }} {{ get_label('members', 'members') }}</span>
                @if($project->end_date)
                <span><x-tk-icon name="calendar" size="13" /> {{ get_label('due', 'Due') }} {{ format_date($project->end_date) }}</span>
                @endif
                @if($project->priority)
                <span class="text-{{ $project->priority->color }}">● {{ $project->priority->title }}</span>
                @endif
            </div>
        </div>
        <div class="tk-proj-headside">
            <span class="av-stack tk-av-stack">
                @php $hu = $project->users; $huCount = $hu->count(); $huShown = 0; @endphp
                @foreach($hu as $u)
                    @if($huShown < 4)
                    <a href="{{ url('/users/profile/' . $u->id) }}" class="av" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ $u->first_name }} {{ $u->last_name }}">
                        <img src="{{ $u->photo ? asset('storage/' . $u->photo) : asset('storage/photos/no-image.jpg') }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('storage/photos/no-image.jpg') }}'" alt="{{ $u->first_name }}">
                    </a>
                    @php $huShown++; @endphp
                    @endif
                @endforeach
                @if($huCount > 4) <span class="av av-more">+{{ $huCount - 4 }}</span> @endif
            </span>
            <a href="{{url('projects/mind-map/'.$project->id)}}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="<?= get_label('mind_map', 'Mind Map') ?>">
                <x-tk-icon name="sitemap" />
            </a>
            @if ($auth_user->can('manage_tasks'))
            <a href="javascript:void(0);" class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#create_task_offcanvas" aria-controls="create_task_offcanvas">
                <x-tk-icon name="plus" class="me-1" />{{ get_label('new_task', 'New task') }}
            </a>
            @endif
        </div>
    </header>

    {{-- ============================ TOOLBAR ============================ --}}
    <div class="tk-board-toolbar mb-3">
        <div class="tk-seg" role="tablist">
            <a href="javascript:void(0);" class="tk-seg-item active"><x-tk-icon name="kanban" size="14" /> {{ get_label('board', 'Board') }}</a>
            <a href="{{ url('projects/tasks/list/'.$project->id) }}" class="tk-seg-item"><x-tk-icon name="list" size="14" /> {{ get_label('list', 'List') }}</a>
            <a href="{{ url('projects/tasks/calendar/'.$project->id) }}" class="tk-seg-item"><x-tk-icon name="calendar" size="14" /> {{ get_label('timeline', 'Timeline') }}</a>
        </div>
        <div class="tk-board-search">
            <x-tk-icon name="search" size="14" />
            <input type="text" id="tk_board_search" placeholder="{{ get_label('search_tasks', 'Search tasks…') }}" autocomplete="off">
        </div>
        <span class="tk-toolbar-spacer"></span>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#project_detail_panel" aria-controls="project_detail_panel">
            <x-tk-icon name="info" size="14" class="me-1" />{{ get_label('project_details', 'Project details') }}
        </button>
    </div>

    {{-- ============= WORKSPACE: board (left) + docked project detail panel (right) ============= --}}
    <div class="tk-workspace" id="tk_workspace">
    <div class="tk-workspace-main">
    @if ($auth_user->can('manage_tasks'))
    @if ($project->tasks->count() > 0)
    {{-- Drag-and-drop hooks preserved: column id={slug} + data-status are the
         dragula containers; task cards (data-task-id) are the draggables. --}}
    <div class="kanban-board tk-kanban" id="project_task_board" data-project-id="{{ $project->id }}">
        @foreach ($statuses as $status)
        @php $statusTaskCount = $project->tasks->where('status_id', $status->id)->count(); @endphp
        <div class="kcol kanban-column">
            <div class="kcol-head">
                <span class="kcol-dot kcol-dot-{{ $status->color }}"></span>
                <span class="kcol-name">{{ $status->title }}</span>
                <span class="kcol-count">{{ $statusTaskCount }}</span>
            </div>
            <div class="kanban-tasks kcol-body" id="{{ $status->slug }}" data-status="{{ $status->id }}">
                @foreach ($project->tasks as $task)
                @if($task->status_id == $status->id)
                <x-kanban :task="$task" />
                @endif
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @else
    <?php $type = 'Tasks'; ?>
    <x-empty-state-card :type="$type" />
    @endif
    @endif
    </div>{{-- /.tk-workspace-main --}}

    {{-- ===== DOCKED PROJECT DETAIL PANEL (always open; project info + module tabs) ===== --}}
    <div class="offcanvas offcanvas-end offcanvas-responsive" id="project_detail_panel" tabindex="-1" aria-labelledby="projectDetailPanelLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="projectDetailPanelLabel"><x-tk-icon name="info" size="14" class="me-1" />{{ get_label('project_details', 'Project details') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ get_label('close', 'Close') }}"></button>
        </div>
        <div class="offcanvas-body">
        <div class="row">
        <div class="col-md-12">
            <div class="mb-4">
                <div>
                    <div class="row">
                        <div class="col-md-12">
                            @if ($projectTags->isNotEmpty())
                            <div class="mb-2">
                                @foreach ($projectTags as $tag)
                                <x-badges.badge tone="{{ $tag->color }}">{{ $tag->title }}</x-badges.badge>
                                @endforeach
                            </div>
                            @endif
                            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">{{ $project->title }}
                                <a href="javascript:void(0);">
                                    <i class='bx {{getFavoriteStatus($project->id) ? "bxs" : "bx"}}-star favorite-icon text-warning' data-id="{{$project->id}}" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="{{getFavoriteStatus($project->id) ? get_label('remove_favorite', 'Click to remove from favorite') : get_label('add_favorite', 'Click to mark as favorite')}}" data-favorite="{{getFavoriteStatus($project->id) ? 1 : 0}}"></i>
                                </a>
                                <a href="javascript:void(0);">
                                    <i class='bx {{getPinnedStatus($project->id) ? "bxs" : "bx"}}-pin pinned-icon text-success' data-id="{{$project->id}}" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="{{getPinnedStatus($project->id) ? get_label('click_unpin', 'Click to Unpin') : get_label('click_pin', 'Click to Pin')}}" data-pinned="{{getPinnedStatus($project->id)}}" data-require_reload="0"></i>
                                </a>
                            </h5>
                            <div class="row">
                                <div class="col-md-6 mt-3 mb-3">
                                    <label class="form-label text-muted d-flex align-items-center gap-2" for="start_date">
                                        <?= get_label('users', 'Users') ?>
                                        <a href="javascript:void(0)" class="edit-project update-users-clients text-muted" data-offcanvas="true" data-id="{{$project->id}}"><x-tk-icon name="edit" size="14" /></a>
                                    </label>
                                    <?php
                                    $users = $project->users;
                                    if (count($users) > 0) { ?>
                                        <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center flex-wrap">
                                            @foreach($users as $user)
                                            <li class="avatar avatar-sm pull-up" title="{{ $user->first_name }} {{ $user->last_name }}"><a href="{{ url('/users/profile/' . $user->id) }}">
                                                    <img src="{{ $user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg') }}" class="rounded-circle" alt="{{$user->first_name}} {{$user->last_name}}">
                                                </a></li>
                                            @endforeach
                                        </ul>
                                    <?php } else { ?>
                                        <p class="mb-0"><x-badges.badge tone="primary"><?= get_label('not_assigned', 'Not assigned') ?></x-badges.badge></p>
                                    <?php } ?>
                                </div>
                                <div class="col-md-6  mt-3 mb-3">
                                    <label class="form-label text-muted d-flex align-items-center gap-2" for="end_date">
                                        <?= get_label('clients', 'Clients') ?>
                                        <a href="javascript:void(0)" class="edit-project update-users-clients text-muted" data-offcanvas="true" data-id="{{$project->id}}"><x-tk-icon name="edit" size="14" /></a>
                                    </label>
                                    <?php
                                    $clients = $project->clients;
                                    if (count($clients) > 0) { ?>
                                        <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center flex-wrap">
                                            @foreach($clients as $client)
                                            <li class="avatar avatar-sm pull-up" title="{{ $client->first_name }} {{ $client->last_name }}"><a href="{{ url('/clients/profile/' . $client->id) }}">
                                                    <img src="{{ $client->photo ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg') }}" class="rounded-circle" alt="{{$client->first_name}} {{$client->last_name}}">
                                                </a></li>
                                            @endforeach
                                        </ul>
                                    <?php } else { ?>
                                        <p class="mb-0"><x-badges.badge tone="primary"><?= get_label('not_assigned', 'Not assigned') ?></x-badges.badge></p>
                                    <?php } ?>
                                </div>
                                <div class="col-md-{{$project->note ? '7' : '6'}} mb-3">
                                    <label class="form-label text-muted"><?= get_label('status', 'Status') ?></label>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <x-badges.status-pill :status="$project->status->color">{{$project->status->title}}</x-badges.status-pill>
                                        @if($project->note)
                                        <div class="ms-1 text-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{$project->note}}">
                                            <x-tk-icon name="note" size="15" />
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-{{$project->note ? '5' : '6'}} mb-3">
                                    <label class="form-label text-muted"><?= get_label('priority', 'Priority') ?></label>
                                    <div class="mt-1">
                                        @if($project->priority)
                                        <x-badges.badge tone="{{$project->priority->color}}">{{$project->priority->title}}</x-badges.badge>
                                        @else
                                        <x-badges.badge>-</x-badges.badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-0 mb-4" />
                <div>
                    <div class="row">

                        @php
                            $fromDate = $project->start_date ? \Carbon\Carbon::parse($project->start_date) : null;
                            $toDate = $project->end_date ? \Carbon\Carbon::parse($project->end_date) : null;
                            if ($fromDate && $toDate) {
                                $duration = $fromDate->diffInDays($toDate) + 1;
                                $durationText = $duration . ' ' . ($duration > 1 ? get_label('days', 'days') : get_label('day', 'day'));
                            } else {
                                $durationText = '-';
                            }
                        @endphp
                        <div class="col-12 mb-2">
                            <div class="tk-facts">
                                <div class="tk-fact">
                                    <x-tk-icon name="calendar" size="15" />
                                    <div class="tk-fact-txt">
                                        <span class="tk-fact-k"><?= get_label('starts_at', 'Starts at') ?></span>
                                        <span class="tk-fact-v">{{ $project->start_date ? format_date($project->start_date) : '-' }}</span>
                                    </div>
                                </div>
                                <div class="tk-fact">
                                    <x-tk-icon name="calendar" size="15" />
                                    <div class="tk-fact-txt">
                                        <span class="tk-fact-k"><?= get_label('ends_at', 'Ends at') ?></span>
                                        <span class="tk-fact-v">{{ $project->end_date ? format_date($project->end_date) : '-' }}</span>
                                    </div>
                                </div>
                                <div class="tk-fact">
                                    <x-tk-icon name="clock" size="15" />
                                    <div class="tk-fact-txt">
                                        <span class="tk-fact-k"><?= get_label('duration', 'Duration') ?></span>
                                        <span class="tk-fact-v">{{ $durationText }}</span>
                                    </div>
                                </div>
                                <div class="tk-fact">
                                    <x-tk-icon name="wallet" size="15" />
                                    <div class="tk-fact-txt">
                                        <span class="tk-fact-k"><?= get_label('budget', 'Budget') ?></span>
                                        <span class="tk-fact-v">{{ !empty($project->budget) ? format_currency($project->budget) : '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-4">
                            <h6 class="text-muted fw-semibold mb-2"><?= get_label('description', 'Description') ?></h6>
                            <p class="{{ filled($project->description) ? '' : 'text-muted mb-0' }}">
                                <!-- Add your project description here -->
                                <?= (filled($project->description)) ? $project->description : '-' ?>
                            </p>
                        </div>


                        <div class="mt-4">
                        <h6 class="text-muted fw-semibold mb-3">{{ get_label('additional_fields', 'Additional Fields') }}</h6>
                        <div class="mt-3">

                            {{-- @dd($customFields); --}}
                            @php
                                $hasValues = false;
                                foreach ($customFields as $field) {
                                    if ($project->getCustomFieldValue($field->id)) {
                                        $hasValues = true;
                                        break;
                                    }
                                }
                            @endphp

                            @if ($customFields->isNotEmpty())
                                @if ($hasValues)
                                    <div class="row">
                                        @foreach ($customFields as $field)
                                            @php
                                                $fieldValue = $project->getCustomFieldValue($field->id);
                                            @endphp
                                            @if ($fieldValue)
                                                <div class="col-12 col-sm-6 mb-3">
                                                    <label class="form-label">{{ $field->field_label }}</label>

                                                    @switch($field->field_type)
                                                        @case('text')
                                                        @case('number')

                                                        @case('password')
                                                        @case('email')
                                                            <input class="form-control form-control-sm" value="{{ $fieldValue }}" readonly>
                                                        @break

                                                        @case('textarea')
                                                            <textarea class="form-control" readonly>{{ $fieldValue }}</textarea>
                                                        @break

                                                        @case('select')
                                                        @case('radio')
                                                            @php
                                                                $options = json_decode($field->options, true);
                                                                $currentValue = $fieldValue;
                                                            @endphp
                                                            @if ($field->field_type == 'select')
                                                                <select class="form-select form-select-sm" disabled>
                                                                    <option value="">Select an option</option>
                                                                    @foreach ($options as $option)
                                                                        <option value="{{ $option }}"
                                                                            {{ $currentValue == $option ? 'selected' : '' }}>
                                                                            {{ $option }}</option>
                                                                    @endforeach
                                                                </select>
                                                            @else
                                                                <div class="d-flex flex-column gap-1">
                                                                    @foreach ($options as $option)
                                                                        <div class="form-check mb-0">
                                                                            <input class="form-check-input" type="radio"
                                                                                value="{{ $option }}"
                                                                                {{ $currentValue == $option ? 'checked' : '' }}
                                                                                disabled>
                                                                            <label class="form-check-label text-muted">
                                                                                {{ $option }}
                                                                            </label>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        @break

                                                        @case('date')
                                                            <input type="text" class="form-control form-control-sm"
                                                                value="{{ $fieldValue ? $fieldValue : '' }}" readonly>
                                                        @break

                                                        @case('checkbox')
                                                            @php
                                                                $options = json_decode($field->options, true) ?? [];
                                                                $currentValues = $fieldValue
                                                                    ? json_decode($fieldValue, true)
                                                                    : [];
                                                                if (!is_array($currentValues)) {
                                                                    $currentValues = [$currentValues];
                                                                }
                                                            @endphp
                                                            <div class="d-flex flex-column gap-1">
                                                                @foreach ($options as $option)
                                                                    <div class="form-check mb-0">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            value="{{ $option }}"
                                                                            {{ in_array($option, $currentValues) ? 'checked' : '' }}
                                                                            disabled>
                                                                        <label class="form-check-label text-muted">
                                                                            {{ $option }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @break

                                                        @case('file')
                                                            @if ($fieldValue)
                                                                <div>
                                                                    <a href="{{ asset('storage/' . $fieldValue) }}" target="_blank"
                                                                        class="btn btn-sm btn-outline-primary">
                                                                        <x-tk-icon name="file" size="14" /> View File
                                                                    </a>
                                                                </div>
                                                            @else
                                                                <p class="text-muted mb-0">No file uploaded</p>
                                                            @endif
                                                        @break

                                                        @case('color')
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-2"
                                                                    style="width: 25px; height: 25px; background-color: {{ $fieldValue ?? '#FFFFFF' }}; border: 1px solid #ddd; border-radius: 4px;">
                                                                </div>
                                                                <input type="text" class="form-control form-control-sm"
                                                                    value="{{ $fieldValue }}" readonly>
                                                            </div>
                                                        @break
                                                    @endswitch

                                                    @if ($field->guide_text)
                                                        <small
                                                            class="form-text text-muted">{{ $field->guide_text }}</small>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted mb-0">
                                        <x-tk-icon name="info" size="15" class="me-1" />
                                        {{ get_label('no_custom_fields', 'No custom fields for this project') }}
                                    </p>
                                @endif
                            @else
                                <p class="text-muted mb-0">
                                    <x-tk-icon name="info" size="15" class="me-1" />
                                    {{ get_label('no_custom_fields', 'No custom fields for this project') }}
                                </p>
                            @endif


                        </div>

                    </div>


                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="media_type_id" value="{{$project->id}}">
        @if($auth_user->can('manage_tasks') || Auth::guard('web')->check() || $project->client_can_discuss || $auth_user->can('manage_milestones') || $auth_user->can('manage_media') || $auth_user->can('manage_activity_log'))
        <!-- Tabs -->
        <div class="mt-2">
            <ul class="nav nav-tabs tk-tabs" role="tablist">
                @php
                // First available module becomes the active tab inside the details offcanvas.
                if (Auth::guard('web')->check() || $project->client_can_discuss) {
                    $activeTab = 'discussions';
                } elseif ($auth_user->can('manage_milestones')) {
                    $activeTab = 'milestones';
                } elseif ($auth_user->can('manage_media')) {
                    $activeTab = 'media';
                } else {
                    $activeTab = 'status_timeline';
                }
                @endphp

                @if (Auth::guard('web')->check() || $project->client_can_discuss)
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'discussions' ? 'active' : '' }}" role="tab"
                        data-bs-toggle="tab" data-bs-target="#navs-top-discussions" aria-controls="navs-top-discussions">
                        <x-tk-icon name="msg" size="14" class="me-1" /><?= get_label('discussions', 'Discussions') ?>
                    </button>
                </li>
                @php
                if (empty($activeTab)) {
                $activeTab = 'discussions';
                }
                @endphp
                @endif

                @if ($auth_user->can('manage_milestones'))
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'milestones' ? 'active' : '' }}" role="tab"
                        data-bs-toggle="tab" data-bs-target="#navs-top-milestones" aria-controls="navs-top-milestones">
                        <x-tk-icon name="flag" size="14" class="me-1" /><?= get_label('milestones', 'Milestones') ?>
                    </button>
                </li>
                @php
                if (empty($activeTab)) {
                $activeTab = 'milestones';
                }
                @endphp
                @endif

                @if ($auth_user->can('manage_media'))
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'media' ? 'active' : '' }}" role="tab"
                        data-bs-toggle="tab" data-bs-target="#navs-top-media" aria-controls="navs-top-media">
                        <x-tk-icon name="note" size="14" class="me-1" /><?= get_label('media', 'Media') ?>
                    </button>
                </li>
                @php
                if (empty($activeTab)) {
                $activeTab = 'media';
                }
                @endphp
                @endif

                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'status_timeline' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-status_timeline" aria-controls="navs-top-status_timeline">
                        <x-tk-icon name="clock" size="14" class="me-1" /><?= get_label('status_timeline', 'Status Timeline') ?>
                    </button>
                </li>
                @php
                if (empty($activeTab)) {
                $activeTab = 'status_timeline';
                }
                @endphp

                @if ($auth_user->can('manage_activity_log'))
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'activity_log' ? 'active' : '' }}" role="tab"
                        data-bs-toggle="tab" data-bs-target="#navs-top-activity-log" aria-controls="navs-top-activity-log">
                        <x-tk-icon name="chart" size="14" class="me-1" /><?= get_label('activity_log', 'Activity log') ?>
                    </button>
                </li>
                @php
                if (empty($activeTab)) {
                $activeTab = 'activity_log';
                }
                @endphp
                @endif
            </ul>

            <div class="tab-content">
                @if (Auth::guard('web')->check() || $project->client_can_discuss)
                <div class="tab-pane fade {{ $activeTab == 'discussions' ? 'active show' : '' }}" id="navs-top-discussions"
                    role="tabpanel">
                    <!-- Discussions content -->
                    <x-discussions-card :project="$project" />
                </div>
                @endif
                @if ($auth_user->can('manage_milestones'))
                @php
                $visibleColumns = getUserPreferences('milestones');
                @endphp
                <div class="tab-pane fade {{ $activeTab == 'milestones' ? 'active show' : '' }}" id="navs-top-milestones" role="tabpanel">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold"><?= get_label('milestones', 'Milestones') ?></h6>
                            <button type="button" class="btn btn-sm btn-primary action_create_milestones" data-bs-toggle="modal" data-bs-target="#create_milestone_modal" aria-controls="create_milestone_modal">
                                <i class="bx bx-plus me-1"></i><?= get_label('create_milestone', 'Create milestone') ?>
                            </button>
                        </div>
                        
                        <div class="card mb-3 border shadow-none">
                            <div class="card-body p-3">
                                <div class="row g-3 align-items-end tk-filter-row">
                                    <div class="col-md-3">
                                        <label class="form-label" for="ms_date_between"><?= get_label('date_between', 'Date Between') ?></label>
                                        <div class="input-group input-group-merge">
                                            <input type="text" class="form-control form-control-sm" id="ms_date_between" placeholder="<?= get_label('date_between', 'Date Between') ?>" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="status_filter"><?= get_label('status', 'Status') ?></label>
                                        <select class="form-select form-select-sm tom_static_select" id="status_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_statuses', 'Select statuses') ?>" data-allow-clear="true" multiple>
                                            <option value="incomplete"><?= get_label('incomplete', 'Incomplete') ?></option>
                                            <option value="complete"><?= get_label('complete', 'Complete') ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="start_date_between"><?= get_label('start_date_between', 'Start date between') ?></label>
                                        <div class="input-group input-group-merge">
                                            <input type="text" id="start_date_between" name="start_date_between" class="form-control form-control-sm" placeholder="<?= get_label('start_date_between', 'Start date between') ?>" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="end_date_between"><?= get_label('end_date_between', 'End date between') ?></label>
                                        <div class="input-group input-group-merge">
                                            <input type="text" id="end_date_between" name="end_date_between" class="form-control form-control-sm" placeholder="<?= get_label('end_date_between', 'End date between') ?>" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="ms_date_between_from">
                        <input type="hidden" id="ms_date_between_to">
                        <input type="hidden" name="start_date_from" id="start_date_from">
                        <input type="hidden" name="start_date_to" id="start_date_to">
                        <input type="hidden" name="end_date_from" id="end_date_from">
                        <input type="hidden" name="end_date_to" id="end_date_to">
                        
                        <div class="card border shadow-none">
                            <div class="card-body p-0">
                                <div class="table-responsive text-nowrap">
                                    <input type="hidden" id="data_type" value="milestones">
                                    <input type="hidden" id="data_table" value="project_milestones_table">
                                    <input type="hidden" id="save_column_visibility">
                                    <input type="hidden" id="multi_select">
                                    <table id="project_milestones_table" data-toggle="table" data-loading-template="loadingTemplate" data-url="{{ url('/projects/get-milestones/' . $project->id) }}" data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total" data-trim-on-search="false" data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-side-pagination="server" data-show-columns="true" data-pagination="true" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-query-params="queryParamsProjectMilestones">
                                        <thead>
                                            <tr>
                                                <th data-checkbox="true"></th>
                                                <th data-field="id" data-visible="{{ (in_array('id', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('id', 'ID') ?></th>
                                                <th data-field="title" data-visible="{{ (in_array('title', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('title', 'Title') ?></th>
                                                <th data-field="start_date" data-visible="{{ (in_array('start_date', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('start_date', 'Start date') ?></th>
                                                <th data-field="end_date" data-visible="{{ (in_array('end_date', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('end_date', 'End date') ?></th>
                                                <th data-field="cost" data-visible="{{ (in_array('cost', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('cost', 'Cost') ?></th>
                                                <th data-field="progress" data-visible="{{ (in_array('progress', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('progress', 'Progress') ?></th>
                                                <th data-field="status" data-visible="{{ (in_array('status', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('status', 'Status') ?></th>
                                                <th data-field="description" data-sortable="true" data-visible="{{ (in_array('description', $visibleColumns)) ? 'true' : 'false' }}">{{ get_label('description', 'Description') }}</th>
                                                <th data-field="created_by" data-sortable="true" data-visible="{{ (in_array('created_by', $visibleColumns)) ? 'true' : 'false' }}">{{ get_label('created_by', 'Created by') }}</th>
                                                <th data-field="created_at" data-sortable="true" data-visible="{{ (in_array('created_at', $visibleColumns)) ? 'true' : 'false' }}">{{ get_label('created_at', 'Created at') }}</th>
                                                <th data-field="updated_at" data-sortable="true" data-visible="{{ (in_array('updated_at', $visibleColumns)) ? 'true' : 'false' }}">{{ get_label('updated_at', 'Updated at') }}</th>
                                                <th data-field="actions" data-visible="{{ (in_array('actions', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}">{{ get_label('actions', 'Actions') }}</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @if ($auth_user->can('manage_media'))
                <div class="tab-pane fade {{ $activeTab == 'media' ? 'active show' : '' }}" id="navs-top-media" role="tabpanel">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold"><?= get_label('media', 'Media') ?></h6>
                            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#add_media_modal">
                                <button type="button" class="btn btn-sm btn-primary action_create_media" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('add_media', 'Add Media') ?>">
                                    <i class="bx bx-plus me-1"></i><?= get_label('add_media', 'Add Media') ?>
                                </button>
                            </a>
                        </div>
                        @php
                        $visibleColumns = getUserPreferences('project_media');
                        @endphp
                        
                        <div class="card border shadow-none">
                            <div class="card-body p-0">
                                <div class="table-responsive text-nowrap">
                                    <input type="hidden" id="data_type" value="project-media">
                                    <input type="hidden" id="data_table" value="project_media_table">
                                    <input type="hidden" id="save_column_visibility">
                                    <table id="project_media_table" data-toggle="table" data-loading-template="loadingTemplate" data-url="{{ url('/projects/get-media/' . $project->id) }}" data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total" data-trim-on-search="false" data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-side-pagination="server" data-show-columns="true" data-pagination="true" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-query-params="queryParamsProjectMedia">
                                        <thead>
                                            <tr>
                                                <th data-checkbox="true"></th>
                                                <th data-field="id" data-visible="{{ (in_array('id', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('id', 'ID') ?></th>
                                                <th data-field="file" data-visible="{{ (in_array('file', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('file', 'File') ?></th>
                                                <th data-field="file_name" data-sortable="true" data-visible="{{ (in_array('file_name', $visibleColumns)) ? 'true' : 'false' }}">{{ get_label('file_name', 'File name') }}</th>
                                                <th data-field="file_size" data-visible="{{ (in_array('file_size', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('file_size', 'File size') ?></th>
                                                <th data-field="created_at" data-sortable="true" data-visible="{{ (in_array('created_at', $visibleColumns)) ? 'true' : 'false' }}">{{ get_label('created_at', 'Created at') }}</th>
                                                <th data-field="updated_at" data-sortable="true" data-visible="{{ (in_array('updated_at', $visibleColumns)) ? 'true' : 'false' }}">{{ get_label('updated_at', 'Updated at') }}</th>
                                                <th data-field="actions" data-visible="{{ (in_array('actions', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="false">{{ get_label('actions', 'Actions') }}</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                 <div class="tab-pane fade {{ $activeTab == 'status_timeline' ? 'active show' : '' }}" id="navs-top-status_timeline" role="tabpanel">
                  <!-- Status timeline content -->
                  <x-status-timeline :timelines="$project->statusTimelines->sortByDesc('changed_at')" />
              </div>
                @if ($auth_user->can('manage_activity_log'))
                <div class="tab-pane fade {{ $activeTab == 'activity_log' ? 'active show' : '' }}" id="navs-top-activity-log" role="tabpanel">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold"><?= get_label('activity_log', 'Activity log') ?></h6>
                        </div>
                        
                        <div class="card mb-3 border shadow-none">
                            <div class="card-body p-3">
                                <div class="row g-3 align-items-end tk-filter-row">
                                    <div class="col-6">
                                        <label class="form-label" for="activity_log_between_date"><?= get_label('date_between', 'Date between') ?></label>
                                        <div class="input-group input-group-merge">
                                            <input type="text" id="activity_log_between_date" class="form-control form-control-sm" placeholder="<?= get_label('date_between', 'Date between') ?>" autocomplete="off">
                                        </div>
                                    </div>
                                    @if ($auth_user->can('manage_users'))
                                    <div class="col-6">
                                        <label class="form-label" for="user_filter"><?= get_label('actioned_by_users', 'Actioned By Users') ?></label>
                                        <select class="form-select form-select-sm tom_users_select" id="user_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_actioned_by_users', 'Select Actioned By Users') ?>" multiple>
                                        </select>
                                    </div>
                                    @endif
                                    @if ($auth_user->can('manage_clients'))
                                    <div class="col-6">
                                        <label class="form-label" for="client_filter"><?= get_label('actioned_by_clients', 'Actioned By Clients') ?></label>
                                        <select class="form-select form-select-sm tom_clients_select" id="client_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_actioned_by_clients', 'Select Actioned By Clients') ?>" multiple>
                                        </select>
                                    </div>
                                    @endif
                                    <div class="col-6">
                                        <label class="form-label" for="activity_filter"><?= get_label('activities', 'Activities') ?></label>
                                        <select class="form-select form-select-sm tom_static_select" id="activity_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_activities', 'Select Activities') ?>" data-allow-clear="true" multiple>
                                            <option value="created"><?= get_label('created', 'Created') ?></option>
                                            <option value="updated"><?= get_label('updated', 'Updated') ?></option>
                                            <option value="duplicated"><?= get_label('duplicated', 'Duplicated') ?></option>
                                            <option value="uploaded"><?= get_label('uploaded', 'Uploaded') ?></option>
                                            <option value="deleted"><?= get_label('deleted', 'Deleted') ?></option>
                                            <option value="updated_status"><?= get_label('updated_status', 'Updated status') ?></option>
                                            <option value="updated_priority"><?= get_label('updated_priority', 'Updated priority') ?></option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label" for="type_filter"><?= get_label('types', 'Types') ?></label>
                                        <select class="form-select form-select-sm tom_static_select" id="type_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_types', 'Select types') ?>" data-allow-clear="true" multiple>
                                            <option value="task">{{ get_label('project', 'Project') }}</option>
                                            <option value="task">{{ get_label('task', 'Task') }}</option>
                                            <option value="milestone">{{ get_label('milestone', 'Milestone') }}</option>
                                            <option value="media">{{ get_label('media', 'Media') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @php
                        $visibleColumns = getUserPreferences('activity_log');
                        @endphp
                        
                        <div class="card border shadow-none">
                            <div class="card-body p-0">
                                <div class="table-responsive text-nowrap">
                                    <input type="hidden" id="activity_log_between_date_from">
                                    <input type="hidden" id="activity_log_between_date_to">
                                    <input type="hidden" id="data_type" value="activity-log">
                                    <input type="hidden" id="data_table" value="activity_log_table">
                                    <input type="hidden" id="type_id" value="{{$project->id}}">
                                    <input type="hidden" id="save_column_visibility">
                                    <input type="hidden" id="multi_select">
                                    <table id="activity_log_table" data-toggle="table" data-loading-template="loadingTemplate" data-url="{{ url('/activity-log/list') }}" data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total" data-trim-on-search="false" data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-side-pagination="server" data-show-columns="true" data-pagination="true" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-checkbox="true"></th>
                                        <th data-field="id" data-visible="{{ (in_array('id', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('id', 'ID') ?></th>
                                        <th data-field="actor_id" data-visible="{{ (in_array('actor_id', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('actioned_by_id', 'Actioned By ID') ?></th>
                                        <th data-field="actor_name" data-visible="{{ (in_array('actor_name', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('actioned_by', 'Actioned By') ?></th>
                                        <th data-field="actor_type" data-visible="{{ (in_array('actor_type', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('actioned_by_type', 'Actioned By Type') ?></th>
                                        <th data-field="type_id" data-visible="{{ (in_array('type_id', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('type_id', 'Type ID') ?></th>
                                        <th data-field="parent_type_id" data-visible="{{ (in_array('parent_type_id', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('parent_type_id', 'Parent type ID') ?></th>
                                        <th data-field="activity" data-visible="{{ (in_array('activity', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('activity', 'Activity') ?></th>
                                        <th data-field="type" data-visible="{{ (in_array('type', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('type', 'Type') ?></th>
                                        <th data-field="parent_type" data-visible="{{ (in_array('parent_type', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('parent_type', 'Parent type') ?></th>
                                        <th data-field="type_title" data-visible="{{ (in_array('type_title', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('type_title', 'Type title') ?></th>
                                        <th data-field="parent_type_title" data-visible="{{ (in_array('parent_type_title', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('parent_type_title', 'Parent type title') ?></th>
                                        <th data-field="message" data-visible="{{ (in_array('message', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('message', 'Message') ?></th>
                                        <th data-field="created_at" data-visible="{{ (in_array('created_at', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('created_at', 'Created at') ?></th>
                                        <th data-field="updated_at" data-visible="{{ (in_array('updated_at', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('updated_at', 'Updated at') ?></th>
                                        <th data-field="actions" data-visible="{{ (in_array('actions', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}"><?= get_label('actions', 'Actions') ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                </div>
            </div>
        @endif
        </div>{{-- /.tab-content --}}
        </div>{{-- /.mt-2 --}}
        </div>{{-- /.row inside offcanvas-body --}}
        </div>{{-- /.offcanvas-body --}}
    </div>{{-- /.offcanvas --}}
    </div>{{-- /.tk-workspace --}}

        <div class="modal fade" id="create_milestone_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form class="modal-content form-submit-event" action="{{url('projects/store-milestone')}}" method="POST">
                    <input type="hidden" name="project_id" value="{{$project->id}}">
                    <input type="hidden" name="dnr">
                    <input type="hidden" name="table" value="project_milestones_table">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1"><?= get_label('create_milestone', 'Create milestone') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('title', 'Title') ?> <span class="asterisk">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="<?= get_label('please_enter_title', 'Please enter title') ?>">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('starts_at', 'Starts at') ?></label>
                                <input type="text" id="start_date" name="start_date" class="form-control" placeholder="{{get_label('please_select','Please Select')}}" autocomplete="off">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('ends_at', 'Ends at') ?></label>
                                <input type="text" id="end_date" name="end_date" class="form-control" placeholder="{{get_label('please_select','Please Select')}}" autocomplete="off">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('status', 'Status') ?> <span class="asterisk">*</span></label>
                                <select class="form-select" name="status">
                                    <option value="incomplete"><?= get_label('incomplete', 'Incomplete') ?></option>
                                    <option value="complete"><?= get_label('complete', 'Complete') ?></option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('cost', 'Cost') ?> <span class="asterisk">*</span></label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">{{$general_settings['currency_symbol']}}</span>
                                    <input type="text" name="cost" class="form-control currency" placeholder="<?= get_label('please_enter_cost', 'Please enter cost') ?>">
                                </div>
                            </div>
                        </div>
                        <label for="description" class="form-label"><?= get_label('description', 'Description') ?></label>
                        <textarea class="form-control description" name="description" placeholder="<?= get_label('please_enter_description', 'Please enter description') ?>"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <?= get_label('close', 'Close') ?>
                        </button>
                        <button type="submit" id="submit_btn" class="btn btn-primary"><?= get_label('create', 'Create') ?></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="edit_milestone_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form class="modal-content form-submit-event" action="{{url('projects/update-milestone')}}" method="POST">
                    <input type="hidden" name="id" id="milestone_id">
                    <input type="hidden" name="project_id" value="{{$project->id}}">
                    <input type="hidden" name="dnr">
                    <input type="hidden" name="table" value="project_milestones_table">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1"><?= get_label('update_milestone', 'Update milestone') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('title', 'Title') ?> <span class="asterisk">*</span></label>
                                <input type="text" name="title" id="milestone_title" class="form-control" placeholder="<?= get_label('please_enter_title', 'Please enter title') ?>">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('starts_at', 'Starts at') ?></label>
                                <input type="text" id="update_milestone_start_date" name="start_date" class="form-control" placeholder="{{get_label('please_select','Please Select')}}" autocomplete="off">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('ends_at', 'Ends at') ?></label>
                                <input type="text" id="update_milestone_end_date" name="end_date" class="form-control" placeholder="{{get_label('please_select','Please Select')}}" autocomplete="off">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('status', 'Status') ?> <span class="asterisk">*</span></label>
                                <select class="form-select" id="milestone_status" name="status">
                                    <option value="incomplete"><?= get_label('incomplete', 'Incomplete') ?></option>
                                    <option value="complete"><?= get_label('complete', 'Complete') ?></option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('cost', 'Cost') ?> <span class="asterisk">*</span></label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">{{$general_settings['currency_symbol']}}</span>
                                    <input type="text" name="cost" id="milestone_cost" class="form-control currency" placeholder="<?= get_label('please_enter_cost', 'Please enter cost') ?>">
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="nameBasic" class="form-label"><?= get_label('progress', 'Progress') ?></label>
                                <input type="range" name="progress" id="milestone_progress" class="form-range">
                                <h6 class="mt-2 milestone-progress"></h6>
                            </div>
                        </div>
                        <label for="description" class="form-label"><?= get_label('description', 'Description') ?></label>
                        <textarea class="form-control description" name="description" id="milestone_description" placeholder="<?= get_label('please_enter_description', 'Please enter description') ?>"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <?= get_label('close', 'Close') ?>
                        </button>
                        <button type="submit" id="submit_btn" class="btn btn-primary"><?= get_label('update', 'Update') ?></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="add_media_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form class="modal-content form-horizontal" id="media-upload" action="{{url('projects/upload-media')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1"><?= get_label('add_media', 'Add Media') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-primary alert-dismissible" role="alert"><?= $media_storage_settings['media_storage_type'] == 's3' ? get_label('storage_type_set_as_aws_s3', 'Storage type is set as AWS S3 storage') : get_label('storage_type_set_as_local', 'Storage type is set as local storage') ?>, <a href="{{ url('settings/media-storage') }}"><?= get_label('click_here_to_change', 'Click here to change.') ?></a></div>
                        <div class="alert alert-info">
                            <?= get_label('allowed_file_types', 'Allowed file types') ?>: <strong><?= str_replace(',', ', ', $general_settings['allowed_file_types']) ?></strong>
                        </div>
                        <div class="dropzone dz-clickable" id="media-upload-dropzone">
                        </div>
                        <div class="form-group mt-4 text-center">
                            <button class="btn btn-primary" id="upload_media_btn"><?= get_label('upload', 'Upload') ?></button>
                        </div>
                        <div class="d-flex justify-content-center">
                            <div class="form-group" id="error_box">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <?= get_label('close', 'Close') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
$titles = [];
$task_counts = [];
$bg_colors = [];
$total_tasks = 0;
$ran = array(
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
);
$task_counts = [];
$titles = [];
$bg_colors = [];
$total_tasks = 0;
foreach ($statuses as $status) {
    $statusCount = 0;
    if (isAdminOrHasAllDataAccess()) {
        $statusCount = $project->tasks->where('status_id', $status->id)->count();
    } else {
        if (isClient()) {
            $statusCount = $project->tasks()
                ->whereIn('project_id', getAuthenticatedUser()->projects->pluck('id'))
                ->where('status_id', $status->id)
                ->count();
        } else {
            $statusCount = $project->tasks()
                ->whereIn('id', getAuthenticatedUser()->tasks->pluck('id'))
                ->where('status_id', $status->id)
                ->count();
        }
    }
    $task_counts[] = $statusCount;
    $titles[] = "'" . $status->title . "'";
    $v = array_shift($ran);
    array_push($bg_colors, "'" . $v . "'");
    $total_tasks += $statusCount;
}
$titles = implode(",", $titles);
$task_counts = implode(",", $task_counts);
$bg_colors = implode(",", $bg_colors);
?>
<script>
    var labels = [<?= $titles ?>];
    var task_data = [<?= $task_counts ?>];
    // Design-system palette for the statistics donut (replaces the random neon set).
    var bg_colors = (function () {
        var pal = ['#4c6ef5', '#22c55e', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#64748b', '#6366f1', '#84cc16'];
        var n = (typeof task_data !== 'undefined' && task_data.length) ? task_data.length : pal.length;
        var out = [];
        for (var i = 0; i < n; i++) { out.push(pal[i % pal.length]); }
        return out;
    })();
    var total_tasks = [<?= $total_tasks ?>];
    //labels
    var total = '<?= get_label('total', 'Total') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
    var label_download = '<?= get_label('download', 'Download') ?>';
</script>
<script src="{{asset('assets/js/apexcharts.js')}}"></script>
<script src="{{asset('assets/js/pages/project-information.js')}}"></script>
{{-- Project board: status data (drag-and-drop + task inspector) --}}
<script>
    var statusArray = <?php echo json_encode($statuses); ?>;
    window.statusArray = statusArray;
    window.priorityArray = <?php echo json_encode($priorities); ?>;
</script>
<script src="{{asset('assets/js/pages/task-board.js')}}"></script>
@endsection
