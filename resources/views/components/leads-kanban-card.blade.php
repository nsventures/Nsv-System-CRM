<div class="kanban-board tk-kanban">
    @foreach ($stages as $stage)
    <div class="kcol kanban-column" data-stage-id="{{ $stage->id }}">
        <div class="kcol-head">
            <span class="kcol-dot kcol-dot-{{ $stage->color }}"></span>
            <span class="kcol-name">{{ $stage->name }}</span>
            <span class="kcol-count column-count">{{ $leads->where('stage_id', $stage->id)->count() }}/{{ $leads->count() }}</span>
        </div>
        <div class="kanban-tasks kcol-body kanban-column-body" id="{{ \Str::slug($stage->name) }}" data-status="{{ $stage->id }}">
            @foreach ($leads->where('stage_id', $stage->id) as $lead)
            <div class="tcard kanban-card" data-card-id="{{ $lead->id }}">
                <div class="tcard-meta">
                    <a href="{{ route('leads.show', ['id' => $lead->id]) }}" class="tcard-code mono" target="_blank">#{{ $lead->id }}</a>
                    <div class="tcard-actions">
                        @if ($showSettings)
                        <div class="dropdown tcard-ic">
                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"><x-tk-icon name="moreV" /></a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item text-info" href="{{ route('leads.show', ['id' => $lead->id]) }}" data-id="{{ $lead->id }}">
                                        <i class="bx bx-show-alt"></i> {{ get_label('quick_view', 'Quick View') }}
                                    </a>
                                </li>
                                @if ($canEditLeads)
                                <li>
                                    <a class="dropdown-item text-primary edit-lead" href="{{ route('leads.edit', ['id' => $lead->id]) }}" data-id="{{ $lead->id }}">
                                        <i class="bx bx-edit"></i> {{ get_label('edit', 'Edit') }}
                                    </a>
                                </li>
                                @endif
                                @if ($canDeleteLeads)
                                <li>
                                    <a class="dropdown-item text-danger delete" href="javascript:void(0);" data-reload="true" data-type="leads" data-id="{{ $lead->id }}">
                                        <i class="bx bx-trash"></i> {{ get_label('delete', 'Delete') }}
                                    </a>
                                </li>
                                @endif
                                @if ($lead->is_converted == 0)
                                <li>
                                    <a class="dropdown-item text-primary convert-to-client" href="javascript:void(0);" data-id="{{ $lead->id }}">
                                        <i class="bx bxs-analyse me-1"></i>{{ get_label('convert_to_client', 'Convert To Client') }}
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>

                <h4 class="tcard-title">
                    <a href="{{ route('leads.show', ['id' => $lead->id]) }}" target="_blank">
                        {{ ucfirst($lead->first_name) }} {{ ucfirst($lead->last_name) }}
                    </a>
                </h4>

                <div class="tcard-tags">
                    @if ($lead->source)
                    <span class="tag">
                        <span class="text-{{ $stage->color }}">●</span> {{ Str::limit($lead->source->name, 10, '...') }}
                    </span>
                    @endif
                    @if ($lead->company)
                    <span class="tag tag-note">
                        <strong class="text-muted fw-normal me-1">{{ get_label('company', 'Company') }}:</strong>
                        {{ $lead->job_title ? $lead->job_title . ' @ ' : '' }}{{ $lead->company }}
                    </span>
                    @endif
                    @if ($lead->phone)
                    <span class="tag tag-due" style="color: var(--signal);">
                        <i class="bx bx-phone" style="font-size: 13px; transform: translateY(1px); margin-right: 2px;"></i> <a href="tel:{{ $lead->country_code }}{{ $lead->phone }}">{{ $lead->country_code }} {{ $lead->phone }}</a>
                    </span>
                    @endif
                    @if ($lead->email)
                    <span class="tag tag-due">
                        <i class="bx bx-envelope" style="font-size: 13px; transform: translateY(1px); margin-right: 2px;"></i> <a href="mailto:{{ $lead->email }}">{{ Str::limit($lead->email, 20, '...') }}</a>
                    </span>
                    @endif
                </div>

                <div class="tcard-foot tk-pfoot mt-1">
                    <div class="tk-pfoot-people">
                        <div class="tk-pgroup">
                            <span class="tk-pgroup-lbl">{{ get_label('assigned_to', 'Assigned To') }}</span>
                            <span class="av-stack tk-av-stack">
                                @if ($lead->assigned_user)
                                    <a href="{{ url('/users/profile/' . $lead->assigned_user->id) }}" target="_blank" class="av">
                                        <img src="{{ $lead->assigned_user->photo ? asset('storage/' . $lead->assigned_user->photo) : asset('storage/photos/no-image.jpg') }}" loading="lazy" alt="{{ $lead->assigned_user->first_name }}" class="rounded-circle" title="{{ $lead->assigned_user->first_name }} {{ $lead->assigned_user->last_name }}">
                                    </a>
                                @else
                                    <span class="text-muted small">{{ get_label('not_assigned', 'Not assigned') }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="tcard-stats mono">
                        @if ($lead->linkedin)
                            <a href="{{ $lead->linkedin }}" target="_blank" class="tcard-ic" title="LinkedIn"><i class="bx bxl-linkedin-square"></i></a>
                        @endif
                        @if ($lead->facebook)
                            <a href="{{ $lead->facebook }}" target="_blank" class="tcard-ic" title="Facebook"><i class="bx bxl-facebook-circle"></i></a>
                        @endif
                        @if ($lead->instagram)
                            <a href="{{ $lead->instagram }}" target="_blank" class="tcard-ic" title="Instagram"><i class="bx bxl-instagram"></i></a>
                        @endif
                        @if ($lead->is_converted == 1)
                            <span class="badge bg-label-success" style="font-size: 0.7rem;">Converted</span>
                        @endif
                        <a href="{{ route('leads.show', ['id' => $lead->id]) }}" class="tcard-ic" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('quick_view', 'Quick View') }}"><x-tk-icon name="info" /></a>
                    </div>
                </div>
            </div>
            @endforeach
            <div class="kanban-footer mt-auto p-2">
                <a href="{{ route('leads.create') }}" class="btn btn-sm d-block create-lead-btn" data-stage-id="{{ $stage->id }}">
                    <x-tk-icon name="plus" class="me-1" />{{ get_label('create_lead', 'Create Lead') }}
                </a>
            </div>
        </div>
    </div>
    @endforeach
    <div class="kcol kanban-column">
        <div class="kcol-head">
            <a href="{{ route('lead-stages.index') }}" class="btn btn-sm btn-primary">
                <x-tk-icon name="plus" class="me-1" />{{ get_label('add_stage', 'Add Stage') }}
            </a>
        </div>
    </div>
</div>
