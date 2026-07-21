@extends('layout')
@section('title')
    <?= get_label('task_details', 'Task details') ?>
@endsection
@section('content')
    <div class="container-fluid">
    <header class="tk-proj-head mt-4 mb-4">
        <div class="tk-proj-headmain">
            <div class="tk-proj-eyebrow mono">
                <span class="kcol-dot kcol-dot-{{ $task->status->color }}"></span>
                {{ strtoupper(str_replace(' ', '-', $task->project ? $task->project->title : get_label('task', 'Task'))) }}@if(count($task->project?->clients ?? []) > 0) · {{ strtoupper($task->project->clients->first()->first_name.' '.$task->project->clients->first()->last_name) }}@endif
            </div>
            <h1 class="tk-proj-title">
                {{ $task->title }}
                <a href="javascript:void(0);" class="tk-proj-ic mx-1">
                    <i class='bx {{ getFavoriteStatus($task->id) ? "bxs" : "bx" }}-star favorite-icon text-warning' data-id="{{ $task->id }}" data-type="tasks" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ getFavoriteStatus($task->id) ? get_label('remove_favorite', 'Click to remove from favorite') : get_label('add_favorite', 'Click to mark as favorite') }}" data-favorite="{{ getFavoriteStatus($task->id) ? 1 : 0 }}"></i>
                </a>
                <a href="javascript:void(0);" class="tk-proj-ic">
                    <i class='bx {{ getPinnedStatus($task->id, \App\Models\Task::class) ? "bxs" : "bx" }}-pin pinned-icon text-success' data-id="{{ $task->id }}" data-type="tasks" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ getPinnedStatus($task->id, \App\Models\Task::class) ? get_label('click_unpin', 'Click to Unpin') : get_label('click_pin', 'Click to Pin') }}" data-pinned="{{ getPinnedStatus($task->id, \App\Models\Task::class) }}" data-require_reload="0"></i>
                </a>
            </h1>
            <div class="tk-proj-meta">
                @if($task->due_date)
                <span><x-tk-icon name="calendar" size="13" /> {{ get_label('due', 'Due') }} {{ format_date($task->due_date) }}</span>
                @endif
                @if($task->priority)
                <span class="text-{{ $task->priority->color }}">● {{ $task->priority->title }}</span>
                @endif
                @if ($task->completion_percentage != null)
                <span><x-tk-icon name="check" size="13" /> {{ $task->completion_percentage }}%</span>
                @endif
            </div>
        </div>
        <div class="tk-proj-headside">
            <span class="av-stack tk-av-stack">
                @php $hu = $task->users; $huCount = $hu->count(); $huShown = 0; @endphp
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
            <a href="javascript:void(0)" class="btn btn-sm btn-outline-secondary edit-task update-users-clients" data-id="{{ $task->id }}">
                <x-tk-icon name="users" /> {{ get_label('users', 'Users') }}
            </a>
        </div>
    </header>    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    
                    @php
                        use Carbon\Carbon;
                        $fromDate = $task->start_date ? Carbon::parse($task->start_date) : null;
                        $toDate = $task->due_date ? Carbon::parse($task->due_date) : null;
                        if ($fromDate && $toDate) {
                            $duration = $fromDate->diffInDays($toDate) + 1;
                            $durationText = $duration . ' ' . ($duration > 1 ? get_label('days', 'days') : get_label('day', 'day'));
                        } else {
                            $durationText = '-';
                        }
                    @endphp

                    <!-- Top Info Grid: Status, Priority, Due Date -->
                    <div class="row mb-4 g-3">
                        <div class="col-md-4">
                            <div class="p-3 border rounded d-flex align-items-center justify-content-between h-100">
                                <div>
                                    <h6 class="mb-2 text-muted fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;"><?= get_label('status', 'Status') ?></h6>
                                    <div class="d-flex align-items-center gap-1">
                                        <x-badges.status-pill :status="$task->status->color">{{$task->status->title}}</x-badges.status-pill>
                                        @if($task->note)
                                        <div class="ms-1 text-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{$task->note}}">
                                            <x-tk-icon name="note" size="15" />
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <i class="bx bx-loader-circle fs-2 text-secondary opacity-50"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded d-flex align-items-center justify-content-between h-100">
                                <div>
                                    <h6 class="mb-2 text-muted fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;"><?= get_label('priority', 'Priority') ?></h6>
                                    @if($task->priority)
                                    <x-badges.badge tone="{{$task->priority->color}}">{{$task->priority->title}}</x-badges.badge>
                                    @else
                                    <x-badges.badge>-</x-badges.badge>
                                    @endif
                                </div>
                                <i class="bx bx-error-circle fs-2 text-secondary opacity-50"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded d-flex align-items-center justify-content-between h-100">
                                <div>
                                    <h6 class="mb-2 text-muted fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;"><?= get_label('due_date', 'Due Date') ?></h6>
                                    <span class="fw-bold fs-6">{{ $task->due_date ? format_date($task->due_date) : '-' }}</span>
                                </div>
                                <i class="bx bx-calendar-exclamation fs-2 text-secondary opacity-50"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Grid: Starts At, Ends At, Duration -->
                    <div class="row mb-4 g-3">
                        <div class="col-md-4">
                            <div class="p-3 border rounded d-flex align-items-center h-100">
                                <div class="bg-label-primary rounded p-2 me-3 d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                                    <i class="bx bx-calendar text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;"><?= get_label('starts_at', 'Starts At') ?></h6>
                                    <span class="fw-bold">{{ $task->start_date ? format_date($task->start_date) : '-' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded d-flex align-items-center h-100">
                                <div class="bg-label-primary rounded p-2 me-3 d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                                    <i class="bx bx-calendar-check text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;"><?= get_label('ends_at', 'Ends At') ?></h6>
                                    <span class="fw-bold">{{ $task->due_date ? format_date($task->due_date) : '-' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded d-flex align-items-center h-100">
                                <div class="bg-label-primary rounded p-2 me-3 d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                                    <i class="bx bx-time-five text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;"><?= get_label('duration', 'Duration') ?></h6>
                                    <span class="fw-bold">{{ $durationText }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <h6 class="text-muted fw-semibold mb-2 d-flex align-items-center">
                            <i class="bx bx-align-left fs-5 me-2"></i> <?= get_label('description', 'Description') ?>
                        </h6>
                        <div class="border rounded p-3 {{ filled($task->description) ? '' : 'text-muted' }}" style="min-height: 120px;">
                            <?= (filled($task->description)) ? $task->description : '<i>' . get_label('no_description_provided', 'No description provided') . '</i>' ?>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Task Reminder -->
                        <div class="col-md-6">
                            <h6 class="text-muted fw-semibold mb-3 d-flex align-items-center">
                                <i class="bx bx-bell fs-5 me-2"></i> {{ get_label('task_reminder', 'Task Reminder') }}
                            </h6>
                            <div class="border rounded p-3 h-100">
                                @if ($task->reminders->isNotEmpty())
                                    @php
                                        $reminder = $task->reminders->first();
                                        $timeOfDay = \Carbon\Carbon::parse($reminder->time_of_day)->format('h:i A');
                                    @endphp
                                    <div class="mb-3">
                                        <span class="d-block text-muted text-xs mb-1">{{ get_label('frequency', 'Frequency') }}</span>
                                        <span class="fw-medium d-flex align-items-center">
                                            @switch($reminder->frequency_type)
                                                @case('daily')
                                                    <i class="bx bx-time me-1 text-muted"></i> {{ get_label('daily_at', 'Daily at') }} {{ $timeOfDay }}
                                                @break
                                                @case('weekly')
                                                    <i class="bx bx-calendar me-1 text-muted"></i> {{ get_label('weekly_on', 'Weekly on') }} {{ $reminder->day_of_week ? get_label(strtolower(date('l', strtotime("Sunday +{$reminder->day_of_week} days"))), date('l', strtotime("Sunday +{$reminder->day_of_week} days"))) : get_label('any_day', 'Any Day') }} {{ get_label('at', 'at') }} {{ $timeOfDay }}
                                                @break
                                                @case('monthly')
                                                    <i class="bx bx-calendar me-1 text-muted"></i> {{ get_label('monthly_on_the', 'Monthly on the') }} {{ $reminder->day_of_month ?: get_label('any_day', 'Any Day') }} {{ get_label('at', 'at') }} {{ $timeOfDay }}
                                                @break
                                            @endswitch
                                        </span>
                                    </div>
                                    @if ($reminder->last_sent_at)
                                    <div>
                                        <span class="d-block text-muted text-xs mb-1">{{ get_label('last_reminder_sent', 'Last Reminder Sent') }}</span>
                                        <span class="fw-medium d-flex align-items-center"><i class="bx bx-send me-1 text-muted"></i> {{ \Carbon\Carbon::parse($reminder->last_sent_at)->diffForHumans() }}</span>
                                    </div>
                                    @endif
                                @else
                                    <div class="text-center text-muted my-3">
                                        <i class="bx bx-bell-off fs-2 mb-2 opacity-50"></i>
                                        <p class="mb-0">{{ get_label('no_reminders_set', 'No reminders set') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Recurrence Details -->
                        <div class="col-md-6">
                            <h6 class="text-muted fw-semibold mb-3 d-flex align-items-center">
                                <i class="bx bx-refresh fs-5 me-2"></i> {{ get_label('recurrence_details', 'Recurrence Details') }}
                            </h6>
                            <div class="border rounded p-3 h-100">
                                @if ($task->recurringTask)
                                    @php $recurringTask = $task->recurringTask; @endphp
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="d-block text-muted text-xs mb-1">{{ get_label('frequency', 'Frequency') }}</span>
                                            <span class="fw-medium d-flex align-items-center">
                                                @switch($recurringTask->frequency)
                                                    @case('daily')
                                                        <i class="bx bx-time me-1 text-muted"></i> {{ get_label('daily_at', 'Daily at') }} 00:00
                                                    @break
                                                    @case('weekly')
                                                        <i class="bx bx-calendar me-1 text-muted"></i> {{ get_label('weekly_on', 'Weekly on') }} {{ $recurringTask->day_of_week ? get_label(strtolower(date('l', strtotime("Sunday +{$recurringTask->day_of_week} days"))), date('l', strtotime("Sunday +{$recurringTask->day_of_week} days"))) : get_label('any_day', 'Any Day') }}
                                                    @break
                                                    @case('monthly')
                                                        <i class="bx bx-calendar me-1 text-muted"></i> {{ get_label('monthly_on_the', 'Monthly on the') }} {{ $recurringTask->day_of_month ?: get_label('any_day', 'Any Day') }}
                                                    @break
                                                    @case('yearly')
                                                        <i class="bx bx-calendar me-1 text-muted"></i> {{ get_label('yearly_on_the', 'Yearly on the') }} {{ $recurringTask->day_of_month ?: get_label('any_day', 'Any Day') }} {{ get_label('of', 'of') }} {{ \Carbon\Carbon::create()->month($recurringTask->month_of_year)->format('F') }}
                                                    @break
                                                @endswitch
                                            </span>
                                        </div>
                                        <x-badges.status-pill :status="$recurringTask->is_active ? 'success' : 'danger'">{{ $recurringTask->is_active ? get_label('active', 'Active') : get_label('inactive', 'Inactive') }}</x-badges.status-pill>
                                    </div>
                                    <div class="row">
                                        @if ($recurringTask->starts_from)
                                        <div class="col-6">
                                            <span class="d-block text-muted text-xs mb-1">{{ get_label('starts_from', 'Starts From') }}</span>
                                            <span class="fw-medium">{{ format_date($recurringTask->starts_from) }}</span>
                                        </div>
                                        @endif
                                        <div class="col-6">
                                            <span class="d-block text-muted text-xs mb-1">{{ get_label('completed', 'Completed') }}</span>
                                            <span class="fw-medium">{{ $recurringTask->completed_occurrences ?? 0 }} / {{ $recurringTask->number_of_occurrences }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center text-muted my-3">
                                        <i class="bx bx-block fs-2 mb-2 opacity-50"></i>
                                        <p class="mb-0">{{ get_label('no_recurrence_set', 'No recurrence set') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Custom Fields -->
                    <div class="mt-4">
                        <h6 class="text-muted fw-semibold mb-3 d-flex align-items-center">
                            <i class="bx bx-list-plus fs-5 me-2"></i> {{ get_label('additional_fields', 'Additional Fields') }}
                        </h6>
                        <div class="border rounded p-3">
                            @php
                                $hasValues = false;
                                foreach ($customFields as $field) {
                                    if ($task->getCustomFieldValue($field->id)) {
                                        $hasValues = true;
                                        break;
                                    }
                                }
                            @endphp

                            @if ($customFields->isNotEmpty() && $hasValues)
                                <div class="row g-3">
                                    @foreach ($customFields as $field)
                                        @php $fieldValue = $task->getCustomFieldValue($field->id); @endphp
                                        @if ($fieldValue)
                                            <div class="col-md-6">
                                                <div class="p-2 border rounded h-100">
                                                    <span class="d-block text-muted text-xs fw-semibold mb-1">{{ $field->field_label }}</span>
                                                    @switch($field->field_type)
                                                        @case('text')
                                                        @case('number')
                                                        @case('password')
                                                        @case('email')
                                                        @case('date')
                                                        @case('textarea')
                                                        @case('select')
                                                        @case('radio')
                                                            <span class="fw-medium">{{ $fieldValue }}</span>
                                                        @break
                                                        @case('checkbox')
                                                            @php
                                                                $currentValues = $fieldValue ? json_decode($fieldValue, true) : [];
                                                                if (!is_array($currentValues)) $currentValues = [$currentValues];
                                                            @endphp
                                                            <span class="fw-medium">{{ implode(', ', $currentValues) }}</span>
                                                        @break
                                                        @case('file')
                                                            <a href="{{ asset('storage/' . $fieldValue) }}" target="_blank" class="d-inline-flex align-items-center fw-medium">
                                                                <i class="bx bx-file me-1"></i> {{ get_label('view_file', 'View File') }}
                                                            </a>
                                                        @break
                                                        @case('color')
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-2 rounded border shadow-sm" style="width: 20px; height: 20px; background-color: {{ $fieldValue ?? '#FFFFFF' }};"></div>
                                                                <span class="fw-medium">{{ $fieldValue }}</span>
                                                            </div>
                                                        @break
                                                    @endswitch
                                                    @if ($field->guide_text)
                                                        <small class="d-block text-muted mt-1" style="font-size: 0.7rem;">{{ $field->guide_text }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted my-3">
                                    <i class="bx bx-info-circle fs-2 mb-2 opacity-50"></i>
                                    <p class="mb-0">{{ get_label('no_custom_fields', 'No custom fields for this task') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            <input type="hidden" id="media_type_id" value="{{ $task->id }}">
        </div>
    </div>
            @if (Auth::guard('web')->check() ||
                $task->client_can_discuss ||
                $auth_user->can('manage_media') ||
                $auth_user->can('manage_activity_log'))
            <div class="card mt-4 border-0 shadow-sm">
                <div class="card-body">
                <ul class="nav nav-tabs tk-tabs" role="tablist">
                    @php $activeTab = ''; @endphp

                    @if (Auth::guard('web')->check() || $task->client_can_discuss)
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ empty($activeTab) ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-discussions" aria-controls="navs-top-discussions">
                                <x-tk-icon name="msg" size="14" class="me-1 text-muted" /> <?= get_label('discussions', 'Discussions') ?>
                            </button>
                        </li>
                        @php if (empty($activeTab)) $activeTab = 'discussions'; @endphp
                    @endif

                    @if (!$task->parent_id)
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $activeTab == 'sub_task' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-sub-task" aria-controls="navs-top-sub-task">
                                <x-tk-icon name="check" size="14" class="me-1 text-muted" /> <?= get_label('sub_task', 'Sub Task') ?>
                            </button>
                        </li>
                        @php if (empty($activeTab)) $activeTab = 'sub_task'; @endphp
                    @endif

                    @if ($task->project->enable_tasks_time_entries == 1)
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ empty($activeTab) ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-time-entries" aria-controls="navs-top-time-entries">
                                <x-tk-icon name="clock" size="14" class="me-1 text-muted" /> {{ get_label('time_entries', 'Time Entries') }}
                            </button>
                        </li>
                        @php if (empty($activeTab)) $activeTab = 'time_entries'; @endphp
                    @endif

                    @if ($auth_user->can('manage_media'))
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ empty($activeTab) ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-media" aria-controls="navs-top-media">
                                <x-tk-icon name="image" size="14" class="me-1 text-muted" /> <?= get_label('media', 'Media') ?>
                            </button>
                        </li>
                        @php if (empty($activeTab)) $activeTab = 'media'; @endphp
                    @endif

                    <li class="nav-item">
                        <button type="button" class="nav-link {{ empty($activeTab) ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-status_timeline" aria-controls="navs-top-status_timeline">
                            <x-tk-icon name="list" size="14" class="me-1 text-muted" /> {{ get_label('status_timeline', 'Status Timeline') }}
                        </button>
                    </li>
                    @php if (empty($activeTab)) $activeTab = 'status_timeline'; @endphp

                    @if ($auth_user->can('manage_activity_log'))
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $activeTab == 'activity_log' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-activity-log" aria-controls="navs-top-activity-log">
                                <x-tk-icon name="activity" size="14" class="me-1 text-muted" /> <?= get_label('activity_log', 'Activity log') ?>
                            </button>
                        </li>
                        @php if (empty($activeTab)) $activeTab = 'activity_log'; @endphp
                    @endif
                </ul>

                    <div class="tab-content">

                        @if (Auth::guard('web')->check() || $task->client_can_discuss)
                            <div class="tab-pane fade {{ $activeTab == 'discussions' ? 'active show' : '' }}"
                                id="navs-top-discussions" role="tabpanel">
                                <!-- Discussions content -->
                                <x-task-discussions-card :task="$task" />
                            </div>
                        @endif
                        @if ($auth_user->can('manage_media'))
                            <div class="tab-pane fade {{ $activeTab == 'media' ? 'active show' : '' }}"
                                id="navs-top-media" role="tabpanel">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div></div>
                                        <a href="javascript:void(0);" data-bs-toggle="modal"
                                            data-bs-target="#add_media_modal">
                                            <button type="button" class="btn btn-sm btn-primary"
                                                data-bs-toggle="tooltip" data-bs-placement="left"
                                                data-bs-original-title="<?= get_label('add_media', 'Add Media') ?>">
                                                <i class="bx bx-plus"></i>
                                            </button>
                                        </a>
                                    </div>
                                    @php
                                        $visibleColumns = getUserPreferences('task_media');
                                    @endphp
                                    <div class="table-responsive text-nowrap">
                                        <input type="hidden" id="data_type" value="task-media">
                                        <input type="hidden" id="data_table" value="task_media_table">
                                        <input type="hidden" id="save_column_visibility">
                                        <table id="task_media_table" data-toggle="table"
                                            data-loading-template="loadingTemplate"
                                            data-url="{{ url('/tasks/get-media/' . $task->id) }}" data-icons-prefix="bx"
                                            data-icons="icons" data-show-refresh="true" data-total-field="total"
                                            data-trim-on-search="false" data-data-field="rows"
                                            data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                                            data-query-params="queryParamsTaskMedia">
                                            <thead>
                                                <tr>
                                                    <th data-checkbox="true"></th>
                                                    <th data-field="id"
                                                        data-visible="{{ in_array('id', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('id', 'ID') ?>
                                                    </th>
                                                    <th data-field="file"
                                                        data-visible="{{ in_array('file', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('file', 'File') ?>
                                                    </th>
                                                    <th data-field="file_name" data-sortable="true"
                                                        data-visible="{{ in_array('file_name', $visibleColumns) ? 'true' : 'false' }}">
                                                        {{ get_label('file_name', 'File name') }}</th>
                                                    <th data-field="file_size"
                                                        data-visible="{{ in_array('file_size', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('file_size', 'File size') ?>
                                                    </th>
                                                    <th data-field="created_at" data-sortable="true"
                                                        data-visible="{{ in_array('created_at', $visibleColumns) ? 'true' : 'false' }}">
                                                        {{ get_label('created_at', 'Created at') }}</th>
                                                    <th data-field="updated_at" data-sortable="true"
                                                        data-visible="{{ in_array('updated_at', $visibleColumns) ? 'true' : 'false' }}">
                                                        {{ get_label('updated_at', 'Updated at') }}</th>
                                                    <th data-field="actions"
                                                        data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="false">{{ get_label('actions', 'Actions') }}</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="tab-pane fade {{ $activeTab == 'status_timeline' ? 'active show' : '' }}"
                            id="navs-top-status_timeline" role="tabpanel">
                            <!-- Status timeline content -->
                            <x-status-timeline :timelines="$task->statusTimelines->sortByDesc('changed_at')" />
                        </div>
                        <div id="navs-top-sub-task"
                            class="tab-pane fade {{ $activeTab == 'sub_task' ? 'active show' : '' }}">
                            <?php
                            $is_favorites = '';
                            $subtasks = $task->subtasks->toArray();
                            $id = isset($project->id) ? 'project_' . $project->id : '';
                            ?>

                            <div class="d-flex justify-content-end">
                                <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#create_task_offcanvas">
                                    <button type="button" class="btn btn-sm btn-primary action_create_tasks"
                                        data-bs-toggle="tooltip" data-bs-placement="right"
                                        data-bs-original-title="<?= get_label('create_task', 'Create task') ?>">
                                        <i class="bx bx-plus"></i>
                                    </button>
                                </a>
                            </div>
                            <x-tasks-card :tasks="$subtasks" :id="$id" :project="$project" :favorites="$is_favorites" />
                        </div>

                        @if ($auth_user->can('manage_activity_log'))
                            <div class="tab-pane fade {{ $activeTab == 'activity_log' ? 'active show' : '' }}"
                                id="navs-top-activity-log" role="tabpanel">
                                <div class="col-12">
                                    <div class="row mt-4">
                                        <div class="col-md-4 mb-3">
                                            <div class="input-group input-group-merge">
                                                <input type="text" id="activity_log_between_date" class="form-control"
                                                    placeholder="<?= get_label('date_between', 'Date between') ?>"
                                                    autocomplete="off">
                                            </div>
                                        </div>
                                        @if ($auth_user->can('manage_users'))
                                            <div class="col-md-4 mb-3">
                                                <select class="form-select users_select" id="user_filter"
                                                    aria-label="Default select example"
                                                    data-placeholder="<?= get_label('select_actioned_by_users', 'Select Actioned By Users') ?>"
                                                    multiple>
                                                </select>
                                            </div>
                                        @endif
                                        @if ($auth_user->can('manage_clients'))
                                            <div class="col-md-4 mb-3">
                                                <select class="form-select clients_select" id="client_filter"
                                                    aria-label="Default select example"
                                                    data-placeholder="<?= get_label('select_actioned_by_clients', 'Select Actioned By Clients') ?>"
                                                    multiple>
                                                </select>
                                            </div>
                                        @endif
                                        <div class="col-md-4 mb-3">
                                            <select class="form-select js-example-basic-multiple" id="activity_filter"
                                                aria-label="Default select example"
                                                data-placeholder="<?= get_label('select_activities', 'Select Activities') ?>"
                                                data-allow-clear="true" multiple>
                                                <option value="created">
                                                    <?= get_label('created', 'Created') ?>
                                                </option>
                                                <option value="updated">
                                                    <?= get_label('updated', 'Updated') ?>
                                                </option>
                                                <option value="duplicated">
                                                    <?= get_label('duplicated', 'Duplicated') ?>
                                                </option>
                                                <option value="uploaded">
                                                    <?= get_label('uploaded', 'Uploaded') ?>
                                                </option>
                                                <option value="deleted">
                                                    <?= get_label('deleted', 'Deleted') ?>
                                                </option>
                                                <option value="updated_status">
                                                    <?= get_label('updated_status', 'Updated status') ?>
                                                </option>
                                                <option value="updated_priority">
                                                    <?= get_label('updated_priority', 'Updated priority') ?>
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <select class="form-select js-example-basic-multiple" id="type_filter"
                                                aria-label="Default select example"
                                                data-placeholder="<?= get_label('select_types', 'Select types') ?>"
                                                data-allow-clear="true" multiple>
                                                <option value="task">{{ get_label('task', 'Task') }}</option>
                                                <option value="media">{{ get_label('media', 'Media') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    @php
                                        $visibleColumns = getUserPreferences('activity_log');
                                    @endphp
                                    <div class="table-responsive text-nowrap">
                                        <input type="hidden" id="activity_log_between_date_from">
                                        <input type="hidden" id="activity_log_between_date_to">
                                        <input type="hidden" id="data_type" value="activity-log">
                                        <input type="hidden" id="data_table" value="activity_log_table">
                                        <input type="hidden" id="type_id" value="{{ $task->id }}">
                                        <input type="hidden" id="save_column_visibility">
                                        <input type="hidden" id="multi_select">
                                        <table id="activity_log_table" data-toggle="table"
                                            data-loading-template="loadingTemplate"
                                            data-url="{{ url('/activity-log/list') }}" data-icons-prefix="bx"
                                            data-icons="icons" data-show-refresh="true" data-total-field="total"
                                            data-trim-on-search="false" data-data-field="rows"
                                            data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                                            data-query-params="queryParams">
                                            <thead>
                                                <tr>
                                                    <th data-checkbox="true"></th>
                                                    <th data-field="id"
                                                        data-visible="{{ in_array('id', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('id', 'ID') ?>
                                                    </th>
                                                    <th data-field="actor_id"
                                                        data-visible="{{ in_array('actor_id', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('actioned_by_id', 'Actioned By ID') ?>
                                                    </th>
                                                    <th data-field="actor_name"
                                                        data-visible="{{ in_array('actor_name', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('actioned_by', 'Actioned By') ?>
                                                    </th>
                                                    <th data-field="actor_type"
                                                        data-visible="{{ in_array('actor_type', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('actioned_by_type', 'Actioned By Type') ?>
                                                    </th>
                                                    <th data-field="type_id"
                                                        data-visible="{{ in_array('type_id', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('type_id', 'Type ID') ?>
                                                    </th>
                                                    <th data-field="parent_type_id"
                                                        data-visible="{{ in_array('parent_type_id', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('parent_type_id', 'Parent type ID') ?>
                                                    </th>
                                                    <th data-field="activity"
                                                        data-visible="{{ in_array('activity', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('activity', 'Activity') ?>
                                                    </th>
                                                    <th data-field="type"
                                                        data-visible="{{ in_array('type', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('type', 'Type') ?>
                                                    </th>
                                                    <th data-field="parent_type"
                                                        data-visible="{{ in_array('parent_type', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('parent_type', 'Parent type') ?>
                                                    </th>
                                                    <th data-field="type_title"
                                                        data-visible="{{ in_array('type_title', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('type_title', 'Type title') ?>
                                                    </th>
                                                    <th data-field="parent_type_title"
                                                        data-visible="{{ in_array('parent_type_title', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('parent_type_title', 'Parent type title') ?>
                                                    </th>
                                                    <th data-field="message"
                                                        data-visible="{{ in_array('message', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('message', 'Message') ?>
                                                    </th>
                                                    <th data-field="created_at"
                                                        data-visible="{{ in_array('created_at', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('created_at', 'Created at') ?>
                                                    </th>
                                                    <th data-field="updated_at"
                                                        data-visible="{{ in_array('updated_at', $visibleColumns) ? 'true' : 'false' }}"
                                                        data-sortable="true">
                                                        <?= get_label('updated_at', 'Updated at') ?>
                                                    </th>
                                                    <th data-field="actions"
                                                        data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">
                                                        <?= get_label('actions', 'Actions') ?>
                                                    </th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($task->project->enable_tasks_time_entries == 1)
                            <div class="tab-pane fade" id="navs-top-time-entries" role="tabpanel">
                                <x-task-time-entries-card :task="$task" />
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            <div class="modal fade" id="add_media_modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <form class="modal-content form-horizontal" id="media-upload"
                        action="{{ url('tasks/upload-media') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel1">
                                <?= get_label('add_media', 'Add Media') ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-primary alert-dismissible" role="alert">
                                <?= $media_storage_settings['media_storage_type'] == 's3' ? get_label('storage_type_set_as_aws_s3', 'Storage type is set as AWS S3 storage') : get_label('storage_type_set_as_local', 'Storage type is set as local storage') ?>,
                                <a href="{{ url('settings/media-storage') }}">
                                    <?= get_label('click_here_to_change', 'Click here to change.') ?>
                                </a>
                            </div>
                            <div class="alert alert-info">
                                <?= get_label('allowed_file_types', 'Allowed file types') ?>:
                                <strong>
                                    <?= str_replace(',', ', ', $general_settings['allowed_file_types']) ?>
                                </strong>
                            </div>
                            <div class="dropzone dz-clickable" id="media-upload-dropzone">
                            </div>
                            <div class="form-group mt-4 text-center">
                                <button class="btn btn-primary" id="upload_media_btn">
                                    <?= get_label('upload', 'Upload') ?>
                                </button>
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

            <div class="modal fade" id="add_task_time_entries" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <form class="modal-content form-submit-event" id="time-entries"
                        action="{{ route('tasks.time_entries.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                        <input type="hidden" name="dnr">
                        <input type="hidden" name="table" value="task-time-entries">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel1">
                                {{ get_label('add_task_time_entry', 'Add Task Time Entry') }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <!-- Entry Date -->
                                <div class="col-md-6">
                                    <label for="entry_date"
                                        class="form-label">{{ get_label('entry_date', 'Entry Date') }}</label>
                                    <span class="asterisk">*</span>
                                    <input type="text" name="entry_date" class="form-control" id="entry_date"
                                        required>
                                    @error('entry_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <!-- Entry Type -->
                                <div class="col-md-6">
                                    <label for="entry_type"
                                        class="form-label">{{ get_label('entry_type', 'Entry Type') }}</label>
                                    <span class="asterisk">*</span>
                                    <select name="entry_type" id="entry_type" class="form-select" required>
                                        <option value="standard">{{ get_label('standard', 'Standard') }}</option>
                                        <option value="flexible">{{ get_label('flexible', 'Flexible') }}</option>
                                    </select>
                                </div>
                                <!-- Standard Hours -->
                                <div class="col-md-12" id="standard_hours_div">
                                    <label for="standard_hours"
                                        class="form-label">{{ get_label(
                                            'standard_hours',
                                            'Standard
                                                                            Hours',
                                        ) }}</label>
                                    <span class="asterisk">*</span>
                                    <input type="time" name="standard_hours" class="form-control" id="standard_hours"
                                        required>
                                    @error('standard_hours')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <!-- Start Time -->
                                <div class="col-md-6" id="start_time_div">
                                    <label for="start_time"
                                        class="form-label">{{ get_label('start_time', 'Start Time') }}</label>
                                    <span class="asterisk">*</span>
                                    <input type="time" name="start_time" class="form-control" id="start_time"
                                        required>
                                    @error('start_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <!-- End Time -->
                                <div class="col-md-6" id="end_time_div">
                                    <label for="end_time"
                                        class="form-label">{{ get_label('end_time', 'End Time') }}</label>
                                    <span class="asterisk">*</span>
                                    <input type="time" name="end_time" class="form-control" id="end_time" required>
                                    @error('end_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <!-- Billable Checkbox -->
                                <input type="hidden" name="is_billable" value="0">
                                @if ($task->billing_type == 'billable')
                                    <div class="col-md-6">
                                        <label for="is_billable"
                                            class="form-label">{{ get_label('billable', 'Billable') }}</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_billable"
                                                name="is_billable" value="1">
                                            <label class="form-check-label"
                                                for="is_billable">{{ get_label('yes', 'Yes') }}</label>
                                        </div>
                                    </div>
                                @endif
                                <!-- Description -->
                                <div class="col-md-12">
                                    <label for="description"
                                        class="form-label">{{ get_label('description', 'Description') }}</label>
                                    <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                {{ get_label('close', 'Close') }}
                            </button>
                            <button type="submit" class="btn btn-primary">
                                {{ get_label('save', 'Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
        <script>
            var label_delete = '<?= get_label('delete', 'Delete') ?>';
            var task_parent_id = "{{ $task->id }}";
        </script>
        <script src="{{ asset('assets/js/pages/task-information.js') }}"></script>
    @endsection
