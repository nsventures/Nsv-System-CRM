@props(['task'])
@php
    $user = getAuthenticatedUser();
    $showSettings = $user->can('edit_tasks') || $user->can('delete_tasks') || $user->can('create_tasks');
    $canEditTasks = $user->can('edit_tasks');
    $canDeleteTasks = $user->can('delete_tasks');
    $canDuplicateTasks = $user->can('create_tasks');
    $isFav = getFavoriteStatus($task->id, \App\Models\Task::class);
    $isPin = getPinnedStatus($task->id, \App\Models\Task::class);
    $pColorMap = [
        'success' => 'var(--ok)', 'danger' => 'var(--err)', 'warning' => 'var(--warn)',
        'info' => 'var(--info)', 'primary' => 'var(--signal)', 'secondary' => 'var(--fg-3)',
    ];
    $pColor = $task->priority ? ($pColorMap[$task->priority->color] ?? 'var(--fg-3)') : 'var(--fg-3)';
    $taskUsers = $task->users;
    $userCount = count($taskUsers);
@endphp
{{-- Taskify v2 — Kanban task card (design-system .tcard). Drag hook
     (data-task-id) + every action hook (favorite / pin / quick-view /
     edit / delete / duplicate / edit-users) preserved. --}}
<div class="tcard" data-task-id="{{ $task->id }}">
    <div class="tcard-meta">
        <a href="{{ url('tasks/information/' . $task->id) }}" class="tcard-code mono">#{{ $task->id }}</a>
        <div class="tcard-actions">
            <a href="javascript:void(0);" class="favorite-icon tcard-ic" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-original-title="{{ $isFav ? get_label('remove_favorite', 'Click to remove from favorite') : get_label('add_favorite', 'Click to mark as favorite') }}" data-id="{{ $task->id }}" data-type="tasks" data-favorite="{{ $isFav ? 1 : 0 }}">
                <i class='bx {{ $isFav ? "bxs" : "bx" }}-star {{ $isFav ? "tcard-star-on" : "" }}'></i>
            </a>
            <a href="javascript:void(0);" class="pinned-icon tcard-ic" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-original-title="{{ $isPin ? get_label('click_unpin', 'Click to Unpin') : get_label('click_pin', 'Click to Pin') }}" data-id="{{ $task->id }}" data-pinned="{{ $isPin ? 1 : 0 }}" data-type="tasks">
                <i class='bx {{ $isPin ? "bxs" : "bx" }}-pin {{ $isPin ? "tcard-pin-on" : "" }}'></i>
            </a>
            @if ($showSettings)
            <div class="dropdown tcard-ic">
                <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"><i class='bx bx-dots-vertical-rounded'></i></a>
                <ul class="dropdown-menu dropdown-menu-end">
                    @if ($canEditTasks)
                    <li><a href="javascript:void(0);" class="dropdown-item edit-task" data-id="{{ $task->id }}"><i class='bx bx-edit'></i> {{ get_label('update', 'Update') }}</a></li>
                    @endif
                    @if ($canDuplicateTasks)
                    <li><a href="javascript:void(0);" class="dropdown-item duplicate" data-reload="true" data-type="tasks" data-id="{{ $task->id }}" data-title="{{ $task->title }}"><i class='bx bx-copy'></i> {{ get_label('duplicate', 'Duplicate') }}</a></li>
                    @endif
                    @if ($canDeleteTasks)
                    <li><a href="javascript:void(0);" class="dropdown-item delete" data-reload="true" data-type="tasks" data-id="{{ $task->id }}"><i class='bx bx-trash'></i> {{ get_label('delete', 'Delete') }}</a></li>
                    @endif
                </ul>
            </div>
            @endif
        </div>
    </div>

    <h4 class="tcard-title"><a href="{{ url('tasks/information/' . $task->id) }}">{{ $task->title }}</a></h4>

    <div class="tcard-tags">
        @if ($task->priority)
        <span class="tag tag-priority" style="color: {{ $pColor }};">● {{ $task->priority->title }}</span>
        @endif
        <a href="{{ route('projects.info', ['id' => $task->project->id]) }}" class="tag">{{ $task->project->title }}</a>
        @if ($task->due_date)
        <span class="tag tag-due">{{ format_date($task->due_date) }}</span>
        @endif
        @if ($task->note)
        <span class="tag tag-note" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ $task->note }}"><i class='bx bx-notepad'></i></span>
        @endif
    </div>

    <div class="tcard-foot">
        <span class="av-stack tk-av-stack">
            @php $displayed = 0; @endphp
            @if ($userCount > 0)
                @foreach ($taskUsers as $u)
                    @if ($displayed < 4)
                    <a href="{{ url('/users/profile/' . $u->id) }}" class="av" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ $u->first_name }} {{ $u->last_name }}">
                        <img src="{{ $u->photo ? asset('storage/' . $u->photo) : asset('storage/photos/no-image.jpg') }}" alt="{{ $u->first_name }}">
                    </a>
                    @php $displayed++; @endphp
                    @else
                        @break
                    @endif
                @endforeach
                @if ($userCount > 4)
                <span class="av av-more">+{{ $userCount - 4 }}</span>
                @endif
            @endif
            <a href="javascript:void(0)" class="av av-add edit-task update-users-clients" data-id="{{ $task->id }}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('edit', 'Edit') }}"><i class="bx bx-plus"></i></a>
        </span>
        <div class="tcard-stats mono">
            <a href="javascript:void(0);" class="quick-view tcard-ic" data-id="{{ $task->id }}" data-type="task" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('quick_view', 'Quick View') }}"><i class='bx bx-info-circle'></i></a>
            @if (Auth::guard('web')->check() || $task->client_can_discuss)
            <a href="{{ route('tasks.info', ['id' => $task->id]) }}#navs-top-discussions" class="tcard-ic" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('discussions', 'Discussions') }}"><i class='bx bx-message-rounded-dots'></i></a>
            @endif
        </div>
    </div>
</div>
