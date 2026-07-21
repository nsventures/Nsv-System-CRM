<div class="kanban-board tk-kanban">
    @foreach ($statuses as $status)
    <div class="kcol kanban-column" data-status-id="{{ $status->id }}">
        <div class="kcol-head">
            <span class="kcol-dot kcol-dot-{{ $status->color }}"></span>
            <span class="kcol-name">{{ $status->title }}</span>
            <span class="kcol-count column-count">{{ $projects->where('status_id', $status->id)->count() }}/{{ $projects->count() }}</span>
        </div>
        <div class="kanban-tasks kcol-body kanban-column-body" id="{{ $status->slug }}" data-status="{{ $status->id }}">
            @foreach ($projects->where('status_id', $status->id) as $project)
            <div class="tcard kanban-card" data-card-id="{{ $project->id }}">
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

                <div class="d-flex align-items-center mt-2 mb-2" style="font-size: 13px; color: var(--fg-2);">
                    <x-tk-icon name="task" class="me-1 tk-ic-muted" />
                    <b style="font-family: monospace; font-size: 14px; margin-right: 4px;"><?= isAdminOrHasAllDataAccess() ? count($project->tasks) : $auth_user->project_tasks($project->id)->count() ?></b>
                    <?= get_label('tasks', 'Tasks') ?>
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
            @endforeach
            @if (canSetStatus($status))
            <div class="kanban-footer mt-auto p-2">
                <a href="javascript:void(0);" class="btn btn-sm d-block create-project-btn" data-bs-toggle="offcanvas" data-bs-target="#create_project_offcanvas" data-status-id="{{ $status->id }}">
                    <x-tk-icon name="plus" class="me-1" />{{ get_label('create_project', 'Create project') }}
                </a>
            </div>
            @endif
        </div>
    </div>
    @endforeach
    <div class="kcol kanban-column">
        <div class="kcol-head">
            <a href="javascript:void(0);" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#create_status_modal">
                <x-tk-icon name="plus" class="me-1" />{{ get_label('add_status', 'Add Status') }}
            </a>
        </div>
    </div>
</div>
