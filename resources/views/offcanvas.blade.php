@php
    use App\Models\Workspace;
    use Spatie\Permission\Models\Role;
    $auth_user = getAuthenticatedUser();
    $roles = Role::where('name', '!=', 'admin')->get();
    $isAdminOrHasAllDataAccess = isAdminOrHasAllDataAccess();
    $guard = getGuardName();
@endphp
@if (
    (Request::is('projects*') && !Request::is('projects/information/*')) ||
        Request::is('home') ||
        Request::is('users/profile/*') ||
        Request::is('clients/profile/*'))
    <x-ui.offcanvas id="create_project_offcanvas" title="{{ get_label('create_project', 'Create Project') }}"
        size="offcanvas-responsive" icon="bx bx-plus" :form-id="'create_project_form'" :form-action="url('projects/store')" form-method="POST"
        :submit-label="get_label('create', 'Create')" submit-icon="bx bx-check" :close-label="get_label('close', 'Close')" close-icon="bx bx-x">
        @if (
            !Request::is('projects') &&
                !Request::is('projects/kanban') &&
                !Request::is('projects/favorite') &&
                !Request::is('projects/kanban/favorite') &&
                !Request::is('projects/gantt-chart') &&
                !Request::is('projects/gantt-chart/favorite'))
            <input type="hidden" name="dnr">
            <input type="hidden" name="table" value="projects_table">
        @endif
        <input type="hidden" name="is_favorite" id="is_favorite" value="0">
        <div class="ai-wrapper">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="title" class="form-label"><?= get_label('title', 'Title') ?> <span
                            class="asterisk">*</span></label>
                    <input
                        type="text"
                        name="title"
                        placeholder="Please Enter Title"
                        class="tk-input"
                    >
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0" for="status"><?= get_label('status', 'Status') ?> <span
                                class="asterisk">*</span></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreateStatusModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_status', 'Create status') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('status/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_statuses', 'Manage statuses') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="tk-select statusDropdown" name="status_id" data-placeholder="<?= get_label('please_select', 'Please select') ?>">
                        <option></option>
                        @isset($statuses)
                            @foreach ($statuses as $status)
                                @if (canSetStatus($status))
                                    <option value="{{ $status->id }}" data-color="{{ $status->color }}"
                                         {{ old('status') == $status->id ? 'selected' : '' }}>
                                        {{ $status->title }}</option>
                                @endif
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0"><?= get_label('priority', 'Priority') ?></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreatePriorityModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_priority', 'Create Priority') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('priority/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_priorities', 'Manage Priorities') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="tk-select priorityDropdown" name="priority_id"
                        data-placeholder="<?= get_label('please_select', 'Please select') ?>">
                        <option></option>
                        @isset($priorities)
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->id }}" data-color="{{ $priority->color }}">
                                    {{ $priority->title }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="start_date"><?= get_label('starts_at', 'Starts at') ?></label>
                    <input type="text" id="start_date" name="start_date" class="tk-input"
                        placeholder="{{ get_label('please_select', 'Please Select') }}" autocomplete="off">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="due_date"><?= get_label('ends_at', 'Ends at') ?></label>
                    <input type="text" id="end_date" name="end_date" class="tk-input"
                        placeholder="{{ get_label('please_select', 'Please Select') }}" autocomplete="off">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="budget" class="form-label"><?= get_label('budget', 'Budget') ?></label>
                    <div class="tk-inputgroup">
                        <span class="tk-ic">{{ $general_settings['currency_symbol'] }}</span>
                        <input class="currency" type="text" id="budget" name="budget"
                            placeholder="<?= get_label('please_enter_budget', 'Please enter budget') ?>"
                            value="{{ old('budget') }}">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label mb-1" for="">
                        <?= get_label('task_accessibility', 'Task Accessibility') ?>
                        <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip" data-bs-offset="0,4"
                            data-bs-placement="top" data-bs-html="true" title=""
                            data-bs-original-title="{{ get_label('assigned_users', 'Assigned Users') }}: {{ get_label('assigned_users_info', 'You Will Need to Manually Select Task Users When Creating Tasks Under This Project.') }}<br/>{{ get_label('project_users', 'Project Users') }}: {{ get_label('project_users_info', 'When Creating Tasks Under This Project, the Task Users Selection Will Be Automatically Filled With Project Users.') }}"></i>
                    </label>
                    <select class="tk-select" name="task_accessibility">
                        <option value="assigned_users"><?= get_label('assigned_users', 'Assigned Users') ?></option>
                        <option value="project_users"><?= get_label('project_users', 'Project Users') ?></option>
                    </select>
                </div>
            </div>
            
            @if ($isAdminOrHasAllDataAccess)
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <label class="form-label mb-0" for="clientCanDiscussProject">{{ get_label('client_can_discuss', 'Client Can Discuss') }}?</label>
                            <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('client_can_discuss_info', 'Allows the client to participate in project discussions.') }}"></i>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="clientCanDiscussProject" name="clientCanDiscuss">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <label class="form-label mb-0" for="edit_tasks_time_entries">
                                <?= get_label('tasks_time_entries', 'Tasks Time Entries') ?>
                            </label>
                            <i class="bx bx-info-circle text-primary" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" data-bs-html="true" title="" data-bs-original-title="<b>{{ get_label('tasks_time_entries', 'Tasks Time Entries') }}:</b> {{ get_label('tasks_time_entries_info', 'To use Time Entries in tasks, you need to enable this option. It allows time tracking and entry management for tasks under this project.') }}"></i>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="enable_tasks_time_entries" value="0">
                            <input class="form-check-input" type="checkbox" name="enable_tasks_time_entries" id="edit_tasks_time_entries" value="1">
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label" for="user_id"><?= get_label('select_users', 'Select users') ?></label>
                    <select class="tk-select tom_users_select" name="user_id[]" multiple data-allow-clear="true"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                        @if ($guard == 'web')
                            <option value="{{ $auth_user->id }}" selected>{{ $auth_user->first_name }}
                                {{ $auth_user->last_name }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label"
                        for="client_id"><?= get_label('select_clients', 'Select clients') ?></label>
                    <select class="tk-select tom_clients_select" name="client_id[]" multiple data-allow-clear="true"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                        @if ($guard == 'client')
                            <option value="{{ $auth_user->id }}" selected>{{ $auth_user->first_name }}
                                {{ $auth_user->last_name }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0" for=""><?= get_label('select_tags', 'Select tags') ?></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreateTagModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_tag', 'Create tag') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('tags/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_tags', 'Manage tags') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="tk-select tom_tags_select" name="tag_ids[]" multiple data-allow-clear="true"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="description" class="form-label mb-0">
                            <?= get_label('description', 'Description') ?>
                        </label>
                        <div class="d-inline-flex align-items-center">
                            <div class="form-check form-switch me-3 mb-0">
                                <input class="form-check-input enableCustomPrompt" type="checkbox" id="enableCustomPrompt_create">
                                <label class="form-check-label" for="enableCustomPrompt_create">
                                    <small><?= get_label('use_custom_prompt', 'Use Custom Prompt') ?></small>
                                </label>
                            </div>
                            <button type="button" class="btn btn-outline-primary generate-ai btn-sm">
                                <i class="fas fa-magic me-1"></i>
                                <?= get_label('generate_with_ai', 'Generate with AI') ?>
                            </button>
                            <i class="bx bx-info-circle text-primary ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<b>{{ get_label('generate_with_ai', 'Generate with AI') }}:</b> {{ get_label('generate_with_ai_info', 'Enable custom prompt to write your own AI prompt. If disabled, the AI will use the title to generate the description. Max 255 characters will be used.') }}" data-bs-html="true"></i>
                            <div class="spinner-border text-primary ai-loader d-none w-px-20 h-px-20 ms-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <!-- Custom Prompt Input (initially hidden) -->
                    <div class="customPromptContainer d-none mb-2">
                        <textarea class="tk-textarea ai-custom-prompt" rows="2"
                            placeholder="<?= get_label('enter_custom_prompt', 'Enter custom prompt for AI generation') ?>"></textarea>
                    </div>
                    <!-- Description Textarea -->
                    <textarea class="tk-textarea description ai-output" rows="5" name="description"
                        placeholder="<?= get_label('please_enter_description', 'Please enter description') ?>"><?= old('description') ?></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label"><?= get_label('note', 'Note') ?></label>
                    <textarea class="tk-textarea" name="note" rows="3"
                        placeholder="<?= get_label('optional_note', 'Optional Note') ?>"></textarea>
                </div>
            </div>

            @php
                $isEdit = true;
            @endphp
            <!-- Custom Fields Section -->
            <x-custom-fields :isEdit="$isEdit" :fields="$projectCustomFields" />

            @if (!$isAdminOrHasAllDataAccess)
                <div class="alert alert-primary mt-2" role="alert">
                    <?= get_label('you_will_be_project_participant_automatically', 'You will be project participant automatically.') ?>
                </div>
            @endif
        </div>
    </x-ui.offcanvas>
@endif
@if (Request::is('projects*') ||
        Request::is('home') ||
        Request::is('users/profile/*') ||
        Request::is('clients/profile/*') ||
        Request::is('users') ||
        Request::is('clients'))
    <x-ui.offcanvas id="edit_project_offcanvas" title="{{ get_label('update_project', 'Update Project') }}"
        size="offcanvas-responsive" icon="bx bx-edit" :form-id="'edit_project_form'" :form-action="url('projects/update')" form-method="POST"
        :submit-label="get_label('update', 'Update')" submit-icon="bx bx-check" :close-label="get_label('close', 'Close')" close-icon="bx bx-x">
        <input type="hidden" name="id" id="project_id">
        @if (
            !Request::is([
                'projects',
                'projects/information/*',
                'projects/kanban',
                'projects/favorite',
                'projects/kanban/favorite',
                'projects/gantt-chart/favorite',
            ]))
            <input type="hidden" name="dnr">
            <input type="hidden" name="table" value="projects_table">
        @endif
        @csrf
        <div class="ai-wrapper">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="title" class="form-label"><?= get_label('title', 'Title') ?> <span
                            class="asterisk">*</span></label>
                    <input class="form-control ai-title w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" type="text" name="title" id="project_title"
                        placeholder="<?= get_label('please_enter_title', 'Please enter title') ?>"
                        value="{{ old('title') }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0" for="status"><?= get_label('status', 'Status') ?> <span
                                class="asterisk">*</span></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreateStatusModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_status', 'Create status') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('status/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_statuses', 'Manage statuses') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="form-select statusDropdown w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" name="status_id" id="project_status_id">
                        @isset($statuses)
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" data-color="{{ $status->color }}"
                                    {{ old('status') == $status->id ? 'selected' : '' }}>{{ $status->title }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0"><?= get_label('priority', 'Priority') ?></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreatePriorityModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_priority', 'Create Priority') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('priority/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_priorities', 'Manage Priorities') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="form-select priorityDropdown w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" name="priority_id" id="project_priority_id"
                        data-placeholder="<?= get_label('please_select', 'Please select') ?>">
                        <option></option>
                        @isset($priorities)
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->id }}" data-color="{{ $priority->color }}">
                                    {{ $priority->title }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="start_date"><?= get_label('starts_at', 'Starts at') ?></label>
                    <input type="text" id="update_start_date" name="start_date" class="form-control w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent"
                        placeholder="{{ get_label('please_select', 'Please Select') }}" autocomplete="off">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="due_date"><?= get_label('ends_at', 'Ends at') ?></label>
                    <input type="text" id="update_end_date" name="end_date" class="form-control w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent"
                        placeholder="{{ get_label('please_select', 'Please Select') }}" autocomplete="off">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="budget" class="form-label"><?= get_label('budget', 'Budget') ?></label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text">{{ $general_settings['currency_symbol'] }}</span>
                        <input class="form-control currency w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" type="text" id="project_budget" name="budget"
                            placeholder="<?= get_label('please_enter_budget', 'Please enter budget') ?>"
                            value="{{ old('budget') }}">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label mb-1" for="">
                        <?= get_label('task_accessibility', 'Task Accessibility') ?>
                        <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip" data-bs-offset="0,4"
                            data-bs-placement="top" data-bs-html="true" title=""
                            data-bs-original-title="{{ get_label('assigned_users', 'Assigned Users') }}: {{ get_label('assigned_users_info', 'You Will Need to Manually Select Task Users When Creating Tasks Under This Project.') }}<br/>{{ get_label('project_users', 'Project Users') }}: {{ get_label('project_users_info', 'When Creating Tasks Under This Project, the Task Users Selection Will Be Automatically Filled With Project Users.') }}"></i>
                    </label>
                    <select class="form-select w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" name="task_accessibility" id="task_accessibility">
                        <option value="assigned_users"><?= get_label('assigned_users', 'Assigned Users') ?></option>
                        <option value="project_users"><?= get_label('project_users', 'Project Users') ?></option>
                    </select>
                </div>
            </div>

            @if ($isAdminOrHasAllDataAccess)
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <label class="form-label mb-0" for="updateClientCanDiscussProject">{{ get_label('client_can_discuss', 'Client Can Discuss') }}?</label>
                            <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('client_can_discuss_info', 'Allows the client to participate in project discussions.') }}"></i>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="updateClientCanDiscussProject" name="clientCanDiscuss">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <label class="form-label mb-0" for="tasks_time_entries">
                                <?= get_label('tasks_time_entries', 'Tasks Time Entries') ?>
                            </label>
                            <i class="bx bx-info-circle text-primary" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" data-bs-html="true" title="" data-bs-original-title="<b>{{ get_label('tasks_time_entries', 'Tasks Time Entries') }}:</b> {{ get_label('tasks_time_entries_info', 'To use Time Entries in tasks, you need to enable this option. It allows time tracking and entry management for tasks under this project.') }}"></i>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="enable_tasks_time_entries" value="0">
                            <input class="form-check-input" type="checkbox" name="enable_tasks_time_entries" id="tasks_time_entries" value="1" {{ old('tasks_time_entries') ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label" for="user_id"><?= get_label('select_users', 'Select users') ?></label>
                    <select class="form-select tom_users_select w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" name="user_id[]" multiple data-allow-clear="true"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label"
                        for="client_id"><?= get_label('select_clients', 'Select clients') ?></label>
                    <select class="form-select tom_clients_select w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" name="client_id[]" multiple data-allow-clear="true"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0" for=""><?= get_label('select_tags', 'Select tags') ?></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreateTagModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_tag', 'Create tag') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('tags/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_tags', 'Manage tags') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="form-select tom_tags_select w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" name="tag_ids[]" multiple data-allow-clear="true"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="description" class="form-label mb-0">
                            <?= get_label('description', 'Description') ?>
                        </label>
                        <div class="d-inline-flex align-items-center">
                            <div class="form-check form-switch me-3 mb-0">
                                <input class="form-check-input enableCustomPrompt" type="checkbox" id="enableCustomPrompt_edit">
                                <label class="form-check-label" for="enableCustomPrompt_edit">
                                    <small><?= get_label('use_custom_prompt', 'Use Custom Prompt') ?></small>
                                </label>
                            </div>
                            <button type="button" class="btn btn-outline-primary generate-ai btn-sm">
                                <i class="fas fa-magic me-1"></i>
                                <?= get_label('generate_with_ai', 'Generate with AI') ?>
                            </button>
                            <i class="bx bx-info-circle text-primary ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<b>{{ get_label('generate_with_ai', 'Generate with AI') }}:</b> {{ get_label('generate_with_ai_info', 'Enable custom prompt to write your own AI prompt. If disabled, the AI will use the title to generate the description. Max 255 characters will be used.') }}" data-bs-html="true"></i>
                            <div class="spinner-border text-primary ai-loader d-none w-px-20 h-px-20 ms-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <!-- Custom Prompt Input (initially hidden) -->
                    <div class="customPromptContainer d-none mb-2">
                        <textarea class="form-control ai-custom-prompt w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" rows="2"
                            placeholder="<?= get_label('enter_custom_prompt', 'Enter custom prompt for AI generation') ?>"></textarea>
                    </div>
                    <!-- Description Textarea -->
                    <textarea class="form-control description ai-output w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" rows="5" name="description" id="project_description"
                        placeholder="<?= get_label('please_enter_description', 'Please enter description') ?>"><?= old('description') ?></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label"><?= get_label('note', 'Note') ?></label>
                    <textarea class="form-control w-full px-3 py-2 bg-[#f5f5f9] border border-[#d9dee3] rounded-md text-sm text-[#566a7f] placeholder-[#a1b0cb] transition-all duration-150 hover:bg-[#eceef1] focus:!bg-[#f5f5f9] focus:!border-[#566a7f] focus:!ring-0 focus:!outline-none focus:placeholder-transparent" name="note" id="project_note" rows="3"
                        placeholder="<?= get_label('optional_note', 'Optional Note') ?>"></textarea>
                </div>
            </div>

            @php
                $isEdit = true;
            @endphp
            <!-- Custom Fields Section -->
            <x-custom-fields :isEdit="$isEdit" :fields="$projectCustomFields" />
        </div>
    </x-ui.offcanvas>
@endif
@if (Request::is('tasks') ||
        Request::is('tasks/draggable') ||
        Request::is('tasks/calendar') ||
        Request::is('tasks/group-by-task-list') ||
        Request::is('projects/tasks/calendar/*') ||
        Request::is('projects/information/*') ||
        Request::is('projects/tasks/draggable/*') ||
        Request::is('projects/tasks/list/*') ||
        Request::is('home') ||
        Request::is('users/profile/*') ||
        Request::is('clients/profile/*') ||
        Request::is('tasks/information/*'))
    <x-ui.offcanvas id="create_task_offcanvas" title="{{ get_label('create_task', 'Create Task') }}"
        size="offcanvas-responsive" icon="bx bx-plus" :form-id="'create_task_form'" :form-action="url('tasks/store')" form-method="POST"
        :submit-label="get_label('create', 'Create')" submit-icon="bx bx-check" :close-label="get_label('close', 'Close')" close-icon="bx bx-x">

        @if (
            !Request::is('projects/tasks/draggable/*') &&
                !Request::is('tasks/draggable') &&
                !Request::is('tasks/calendar') &&
                !Request::is('projects/tasks/calendar/*') &&
                !Request::is('projects/information/*'))
            <input type="hidden" name="dnr">
            <input type="hidden" name="table" value="task_table">
        @endif
        @if (Request::is('tasks/information/*'))
            <input type="hidden" name="parent_id" value="{{ $task->id }}">
        @endif
        <input type="hidden" name="is_favorite" id="is_favorite" value="0">

        @csrf
        <div class="modal-body ai-wrapper">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="title" class="form-label"><?= get_label('title', 'Title') ?> <span
                            class="asterisk">*</span></label>
                    <input class="tk-input ai-title" type="text" name="title"
                        placeholder="<?= get_label('please_enter_title', 'Please enter title') ?>"
                        value="{{ old('title') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0" for="status"><?= get_label('status', 'Status') ?> <span
                                class="asterisk">*</span></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreateStatusModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_status', 'Create status') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('status/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_statuses', 'Manage statuses') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="tk-select statusDropdown" name="status_id" data-placeholder="<?= get_label('please_select', 'Please select') ?>">
                        <option></option>
                        @isset($statuses)
                            @foreach ($statuses as $status)
                                @if (canSetStatus($status))
                                    <option value="{{ $status->id }}" data-color="{{ $status->color }}"
                                        {{ old('status') == $status->id ? 'selected' : '' }}>
                                        {{ $status->title }}</option>
                                @endif
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0"><?= get_label('priority', 'Priority') ?></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreatePriorityModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_priority', 'Create Priority') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('priority/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_priorities', 'Manage Priorities') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="tk-select priorityDropdown" name="priority_id"
                        data-placeholder="<?= get_label('please_select', 'Please select') ?>">
                        <option></option>
                        @isset($priorities)
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->id }}" data-color="{{ $priority->color }}">
                                    {{ $priority->title }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
            </div>
            <div class="row">
                <?php $project_id = 0;
                                                              if (!isset($project->id)) {
                                                        ?>
                <div class="mb-3">
                    <label class="form-label" for="user_id"><?= get_label('select_project', 'Select project') ?>
                        <span class="asterisk">*</span></label>
                    <select class="tk-select selectTaskProject tom_projects_select" name="project"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>"
                        data-single-select="true" data-allow-clear="false">
                    </select>
                </div>
                <?php } else {$project_id=$project->id ?>
                <input type="hidden" name="project" value="{{ $project_id }}">
                <div class="mb-3">
                    <label for="project_title" class="form-label"><?= get_label('project', 'Project') ?>
                        <span class="asterisk">*</span></label>
                    <input class="tk-input" type="text" value="{{ $project->title }}" readonly>
                </div>
                <?php } ?>
            </div>
            <div class="row" id="selectTaskUsers">
                <div class="mb-3">
                    <label class="form-label" for="user_id"><?= get_label('select_users', 'Select users') ?>
                        <span id="users_associated_with_project"></span><?php if (!empty($project_id)) { ?>
                        (<?= get_label('users_associated_with_project', 'Users associated with project') ?>
                        <b>{{ $project->title }}</b>)
                        <?php } ?></label>
                    <select class="tk-select tom_users_select" name="user_id[]" multiple="multiple"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="start_date"><?= get_label('starts_at', 'Starts at') ?></label>
                    <input type="text" id="task_start_date" name="start_date" class="tk-input"
                        placeholder="{{ get_label('please_select', 'Please Select') }}" autocomplete="off">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="due_date"><?= get_label('ends_at', 'Ends at') ?></label>
                    <input type="text" id="task_end_date" name="due_date" class="tk-input"
                        placeholder="{{ get_label('please_select', 'Please Select') }}" autocomplete="off">
                </div>
            </div>
            <div class="row">
                <div class="mb-3">
                    <label for="task_list" class="form-label">
                        {{ get_label('task_list', 'Task List') }}
                    </label>
                    <select class="tk-select tom_task_list_select" name="task_list_id" id="task_list"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>"
                        data-single-select="true" data-allow-clear="false">
                    </select>
                </div>
            </div>
            @if ($isAdminOrHasAllDataAccess)
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-check-label"
                            for="clientCanDiscussTask">{{ get_label('client_can_discuss', 'Client Can Discuss') }}?</label>
                        <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip" data-bs-placement="top"
                            title="{{ get_label('client_can_discuss_info_task', 'Allows the client to participate in task discussions.') }}"></i>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="clientCanDiscussTask"
                                name="clientCanDiscuss">
                        </div>
                    </div>
                </div>
            @endif
            <div class="row align-items-center mb-2">
                <!-- Description Label -->
                <div class="col-md-6">
                    <label for="description" class="form-label mb-0">
                        <?= get_label('description', 'Description') ?>
                    </label>
                </div>
                <!-- Custom Prompt Switch + Generate Button -->
                <div class="col-md-6 text-md-end mt-md-0 mt-2">
                    <div class="d-inline-flex align-items-center">
                        <div class="form-check form-switch me-3">
                            <input class="form-check-input enableCustomPrompt" type="checkbox">
                            <label class="form-check-label" for="enableCustomPrompt">
                                <?= get_label('use_custom_prompt', 'Use Custom Prompt') ?>
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline-primary generate-ai btn-sm">
                            <i class="fas fa-magic me-1"></i>
                            <?= get_label('generate_with_ai', 'Generate with AI') ?>
                        </button>
                        <i class="bx bx-info-circle text-primary ms-2" data-bs-toggle="tooltip" data-bs-offset="0,4"
                            data-bs-placement="top" data-bs-html="true" title=""
                            data-bs-original-title="<b>{{ get_label('generate_with_ai', 'Generate with AI') }}:</b> {{ get_label('generate_with_ai_info', 'Enable custom prompt to write your own AI prompt. If disabled, the AI will use the title to generate the description. Max 255 characters will be used.') }}">
                        </i>
                        <div class="spinner-border text-primary ai-loader d-none w-px-20 h-px-20 ms-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Custom Prompt Input (initially hidden) -->
            <div class="customPromptContainer d-none mb-2 mt-2">
                <textarea class="tk-textarea ai-custom-prompt" rows="2"
                    placeholder="<?= get_label('enter_custom_prompt', 'Enter custom prompt for AI generation') ?>"></textarea>
            </div>
            <!-- Description Textarea -->
            <div class="mb-3">
                <textarea class="tk-textarea description ai-output" rows="5" name="description"
                    placeholder="<?= get_label('please_enter_description', 'Please enter description') ?>"><?= old('description') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"
                        for="billing_type">{{ get_label('billing_type', 'Billing Type') }}</label>
                    <select class="tk-select" name="billing_type" id="billing_type">
                        <option value="none">{{ get_label('none', 'None') }}</option>
                        <option value="billable">{{ get_label('billable', 'Billable') }}</option>
                        <option value="non-billable">{{ get_label('non_billable', 'Non Billable') }}
                        </option>
                    </select>
                    @error('billing_type')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"
                        for="completion_percentage">{{ get_label('completion_percentage', 'Completion Percentage (%)') }}</label>
                    <select class="tk-select" name="completion_percentage" id="completion_percentage">
                        @foreach (range(0, 100, 10) as $percentage)
                            <option value="{{ $percentage }}">{{ $percentage }}%</option>
                        @endforeach
                    </select>
                    @error('completion_percentage')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="row">
                <div class="mb-3">
                    <label class="form-label"><?= get_label('note', 'Note') ?></label>
                    <textarea class="tk-textarea" name="note" rows="3"
                        placeholder="<?= get_label('optional_note', 'Optional Note') ?>"></textarea>
                </div>
            </div>
            @if (!$isAdminOrHasAllDataAccess && $guard != 'client')
                <div class="alert alert-primary" role="alert">
                    <?= get_label('you_will_be_task_participant_automatically', 'You will be task participant automatically.') ?>
                </div>
            @endif
            <!-- Remider Task -->
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="reminder-switch"
                        class="form-label">{{ get_label('enable_reminder', 'Enable Reminder') }}</label>
                    <i class="bx bx-info-circle text-primary" data-bs-toggle="tooltip"data-bs-offset="0,4"
                        data-bs-placement="top" data-bs-html="true"title=""
                        data-bs-original-title="<b>{{ get_label('task_reminder', 'Task Reminder') }}:</b> {{ get_label('task_reminder_info', 'Enable this option to set reminders for tasks. You can configure reminder frequencies (daily, weekly, or monthly), specific times, and customize alerts to ensure you stay on track with task deadlines.') }}"></i>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="reminder-switch" name="enable_reminder">
                        <label class="form-check-label"
                            for="reminder-switch">{{ get_label('enable_task_reminder', 'Enable Task Reminder') }}</label>
                    </div>
                </div>
                <div id="reminder-settings" class="d-none">
                    <!-- Frequency Type -->
                    <div class="mb-3">
                        <label for="frequency-type"
                            class="form-label">{{ get_label('frequency_type', 'Frequency Type') }}</label>
                        <select class="tk-select" id="frequency-type" name="frequency_type" required>
                            <option value="daily">{{ get_label('daily', 'Daily') }}</option>
                            <option value="weekly">{{ get_label('weekly', 'Weekly') }}</option>
                            <option value="monthly">{{ get_label('monthly', 'Monthly') }}</option>
                        </select>
                    </div>
                    <!-- Day of Week (Weekly Only) -->
                    <div class="d-none mb-3" id="day-of-week-group">
                        <label
                            for="day-of-week"class="form-label">{{ get_label('day_of_the_week', 'Day of the Week') }}</label>
                        <select class="tk-select" id="day-of-week" name="day_of_week">
                            <option value="">{{ get_label('any_day', 'Any Day') }}</option>
                            <option value="1">{{ get_label('monday', 'Monday') }}</option>
                            <option value="2">{{ get_label('tuesday', 'Tuesday') }}</option>
                            <option value="3">{{ get_label('wednesday', 'Wednesday') }}</option>
                            <option value="4">{{ get_label('thursday', 'Thursday') }}</option>
                            <option value="5">{{ get_label('friday', 'Friday') }}</option>
                            <option value="6">{{ get_label('saturday', 'Saturday') }}</option>
                            <option value="7">{{ get_label('sunday', 'Sunday') }}</option>
                        </select>
                    </div>
                    <!-- Day of Month (Monthly Only) -->
                    <div class="d-none mb-3" id="day-of-month-group">
                        <label for="day-of-month"
                            class="form-label">{{ get_label('day_of_the_month', 'Day of the Month') }}</label>
                        <select class="tk-select" id="day-of-month" name="day_of_month">
                            <option value="">{{ get_label('any_day', 'Any Day') }}</option>
                            @foreach (range(1, 31) as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Time of Day -->
                    <div class="mb-3">
                        <label for="time-of-day"
                            class="form-label">{{ get_label('time_of_day', 'Time of Day') }}</label>
                        <input type="time" class="tk-input" id="time-of-day" name="time_of_day">
                    </div>
                </div>
            </div>
            <!-- Recuring Task -->
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="recurring-task-switch" class="form-label">
                        {{ get_label('enable_recurring_task', 'Enable Recurring Task') }}
                    </label>
                    <i class="bx bx-info-circle text-primary" data-bs-toggle="tooltip" data-bs-offset="0,4"
                        data-bs-placement="top" data-bs-html="true" title=""
                        data-bs-original-title="<b>{{ get_label('recurring_tasks', 'Recurring Tasks') }}:</b> {{ get_label('recurring_tasks_info', 'This option enables the creation of recurring tasks. You can set the frequency (daily, weekly, monthly, yearly), specific days, and manage the recurrence schedule efficiently.') }}">
                    </i>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="recurring-task-switch"
                            name="enable_recurring_task">
                        <label class="form-check-label" for="recurring-task-switch">
                            {{ get_label('enable_recurring_task', 'Enable Recurring Task') }}
                        </label>
                    </div>
                </div>
                <div id="recurring-task-settings" class="d-none">
                    <!-- Frequency Type -->
                    <div class="mb-3">
                        <label for="recurrence-frequency" class="form-label">
                            {{ get_label('recurrence_frequency', 'Recurrence Frequency') }}
                        </label>
                        <select class="tk-select" id="recurrence-frequency" name="recurrence_frequency" required>
                            <option value="daily">{{ get_label('daily', 'Daily') }}</option>
                            <option value="weekly">{{ get_label('weekly', 'Weekly') }}</option>
                            <option value="monthly">{{ get_label('monthly', 'Monthly') }}</option>
                            <option value="yearly">{{ get_label('yearly', 'Yearly') }}</option>
                        </select>
                    </div>
                    <!-- Day of Week (Weekly Only) -->
                    <div class="d-none mb-3" id="recurrence-day-of-week-group">
                        <label for="recurrence-day-of-week" class="form-label">
                            {{ get_label('day_of_the_week', 'Day of the Week') }}
                        </label>
                        <select class="tk-select" id="recurrence-day-of-week" name="recurrence_day_of_week">
                            <option value="">{{ get_label('any_day', 'Any Day') }}</option>
                            <option value="1">{{ get_label('monday', 'Monday') }}</option>
                            <option value="2">{{ get_label('tuesday', 'Tuesday') }}</option>
                            <option value="3">{{ get_label('wednesday', 'Wednesday') }}</option>
                            <option value="4">{{ get_label('thursday', 'Thursday') }}</option>
                            <option value="5">{{ get_label('friday', 'Friday') }}</option>
                            <option value="6">{{ get_label('saturday', 'Saturday') }}</option>
                            <option value="7">{{ get_label('sunday', 'Sunday') }}</option>
                        </select>
                    </div>
                    <!-- Day of Month (Monthly and Yearly Only) -->
                    <div class="d-none mb-3" id="recurrence-day-of-month-group">
                        <label for="recurrence-day-of-month" class="form-label">
                            {{ get_label('day_of_the_month', 'Day of the Month') }}
                        </label>
                        <select class="tk-select" id="recurrence-day-of-month" name="recurrence_day_of_month">
                            <option value="">{{ get_label('any_day', 'Any Day') }}</option>
                            @foreach (range(1, 31) as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Month of Year (Yearly Only) -->
                    <div class="d-none mb-3" id="recurrence-month-of-year-group">
                        <label for="recurrence-month-of-year" class="form-label">
                            {{ get_label('month_of_the_year', 'Month of the Year') }}
                        </label>
                        <select class="tk-select" id="recurrence-month-of-year" name="recurrence_month_of_year">
                            <option value="">{{ get_label('any_month', 'Any Month') }}</option>
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}">
                                    {{ \Carbon\Carbon::create()->month($month)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Starts From -->
                    <div class="mb-3">
                        <label for="recurrence-starts-from" class="form-label">
                            {{ get_label('starts_from', 'Starts From') }}
                        </label>
                        <input type="date" class="tk-input" id="recurrence-starts-from"
                            name="recurrence_starts_from">
                    </div>
                    <!-- Number of Occurrences -->
                    <div class="mb-3">
                        <label for="recurrence-occurrences" class="form-label">
                            {{ get_label('number_of_occurrences', 'Number of Occurrences') }}
                        </label>
                        <input type="number" class="tk-input" id="recurrence-occurrences"
                            name="recurrence_occurrences" min="1">
                    </div>
                </div>
            </div>
            @php
                $isEdit = false;
            @endphp
            <x-custom-fields :isEdit="$isEdit" :fields="$taskCustomFields" />
        </div>

    </x-ui.offcanvas>
@endif
@if (Request::is('tasks') ||
        Request::is('tasks/draggable') ||
        Request::is('tasks/calendar') ||
        Request::is('tasks/group-by-task-list') ||
        Request::is('projects/tasks/calendar/*') ||
        Request::is('projects/tasks/draggable/*') ||
        Request::is('projects/tasks/list/*') ||
        Request::is('tasks/information/*') ||
        Request::is('home') ||
        Request::is('users/profile/*') ||
        Request::is('clients/profile/*') ||
        Request::is('projects/information/*') ||
        Request::is('users') ||
        Request::is('clients'))

    <x-ui.offcanvas id="edit_task_offcanvas" title="{{ get_label('edit_task', 'Edit Task') }}"
        size="offcanvas-responsive" icon="bx bx-edit" :form-id="'edit_task_form'" :form-action="url('tasks/update')" form-method="POST"
        :submit-label="get_label('update', 'Update')" submit-icon="bx bx-check" :close-label="get_label('close', 'Close')" close-icon="bx bx-x">

        <input type="hidden" name="id" id="id">
        @if (
            !Request::is('projects/tasks/draggable/*') &&
                !Request::is('tasks/draggable') &&
                !Request::is('tasks/calendar') &&
                !Request::is('projects/tasks/calendar/*') &&
                !Request::is('tasks/information/*'))
            <input type="hidden" name="dnr">
            <input type="hidden" name="table" value="task_table">
        @endif

        @csrf
        <div class="modal-body ai-wrapper">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="title" class="form-label"><?= get_label('title', 'Title') ?> <span
                            class="asterisk">*</span></label>
                    <input class="form-control ai-title" type="text" id="title" name="title"
                        placeholder="<?= get_label('please_enter_title', 'Please enter title') ?>"
                        value="{{ old('title') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0" for="status"><?= get_label('status', 'Status') ?> <span
                                class="asterisk">*</span></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreateStatusModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_status', 'Create status') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('status/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_statuses', 'Manage statuses') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="form-select statusDropdown" name="status_id" id="task_status_id">
                        @isset($statuses)
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" data-color="{{ $status->color }}"
                                    {{ old('status') == $status->id ? 'selected' : '' }}>{{ $status->title }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <label class="form-label mb-0"><?= get_label('priority', 'Priority') ?></label>
                        <div class="ms-2 small">
                            <a href="javascript:void(0);" class="openCreatePriorityModal text-muted" data-bs-toggle="tooltip" title="<?= get_label('create_priority', 'Create Priority') ?>"><i class="bx bx-plus"></i></a>
                            <a href="{{ url('priority/manage') }}" class="text-muted ms-1" data-bs-toggle="tooltip" title="<?= get_label('manage_priorities', 'Manage Priorities') ?>"><i class="bx bx-list-ul"></i></a>
                        </div>
                    </div>
                    <select class="form-select priorityDropdown" name="priority_id" id="priority_id"
                        data-placeholder="<?= get_label('please_select', 'Please select') ?>">
                        <option></option>
                        @isset($priorities)
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->id }}" data-color="{{ $priority->color }}">
                                    {{ $priority->title }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="project_title" class="form-label"><?= get_label('project', 'Project') ?>
                        <span class="asterisk">*</span></label>
                    <input class="form-control" type="text" id="update_project_title" readonly>
                </div>
            </div>
            <div class="row">
                <div class="mb-3">
                    <label class="form-label" for="user_id"><?= get_label('select_users', 'Select users') ?> <span
                            id="task_update_users_associated_with_project"></span></label>
                    <select class="form-select tom_users_select" name="user_id[]" multiple="multiple"
                        data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="start_date"><?= get_label('starts_at', 'Starts at') ?></label>
                    <input type="text" id="update_start_date" name="start_date" class="form-control"
                        placeholder="{{ get_label('please_select', 'Please Select') }}" autocomplete="off">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="due_date"><?= get_label('ends_at', 'Ends at') ?></label>
                    <input type="text" id="update_end_date" name="due_date" class="form-control"
                        placeholder="{{ get_label('please_select', 'Please Select') }}" autocomplete="off">
                </div>
            </div>
            @if ($isAdminOrHasAllDataAccess)
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-check-label"
                            for="updateClientCanDiscussTask">{{ get_label('client_can_discuss', 'Client Can Discuss') }}?</label>
                        <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip" data-bs-placement="top"
                            title="{{ get_label('client_can_discuss_info_task', 'Allows the client to participate in task discussions.') }}"></i>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="updateClientCanDiscussTask"
                                name="clientCanDiscuss">
                        </div>
                    </div>
                </div>
            @endif
            <div class="mb-3">
                <label for="edit_task_list" class="form-label">
                    {{ get_label('task_list', 'Task List') }}
                </label>
                <select class="form-select tom_task_list_select" name="task_list_id" id="edit_task_list">
                    <option value="">Select a task list</option>
                </select>
            </div>
            <div class="row align-items-center mb-2">
                <!-- Description Label -->
                <div class="col-md-6">
                    <label for="description" class="form-label mb-0">
                        <?= get_label('description', 'Description') ?>
                    </label>
                </div>
                <!-- Custom Prompt Switch + Generate Button -->
                <div class="col-md-6 text-md-end mt-md-0 mt-2">
                    <div class="d-inline-flex align-items-center">
                        <div class="form-check form-switch me-3">
                            <input class="form-check-input enableCustomPrompt" type="checkbox">
                            <label class="form-check-label" for="enableCustomPrompt">
                                <?= get_label('use_custom_prompt', 'Use Custom Prompt') ?>
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline-primary generate-ai btn-sm">
                            <i class="fas fa-magic me-1"></i>
                            <?= get_label('generate_with_ai', 'Generate with AI') ?>
                        </button>
                        <i class="bx bx-info-circle text-primary ms-2" data-bs-toggle="tooltip" data-bs-offset="0,4"
                            data-bs-placement="top" data-bs-html="true" title=""
                            data-bs-original-title="<b>{{ get_label('generate_with_ai', 'Generate with AI') }}:</b> {{ get_label('generate_with_ai_info', 'Enable custom prompt to write your own AI prompt. If disabled, the AI will use the title to generate the description. Max 255 characters will be used.') }}">
                        </i>
                        <div class="spinner-border text-primary ai-loader d-none w-px-20 h-px-20 ms-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Custom Prompt Input (initially hidden) -->
            <div class="customPromptContainer d-none mb-2 mt-2">
                <textarea class="form-control ai-custom-prompt" rows="2"
                    placeholder="<?= get_label('enter_custom_prompt', 'Enter custom prompt for AI generation') ?>"></textarea>
            </div>
            <!-- Description Textarea -->
            <div class="mb-3">
                <textarea class="form-control description ai-output" rows="5" name="description" id="task_description"
                    placeholder="<?= get_label('please_enter_description', 'Please enter description') ?>"><?= old('description') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"
                        for="billing_type">{{ get_label('billing_type', 'Billing Type') }}</label>
                    <select class="form-select" name="billing_type" id="edit_billing_type">
                        <option value="none">{{ get_label('none', 'None') }}</option>
                        <option value="billable">{{ get_label('billable', 'Billable') }}</option>
                        <option value="non-billable">{{ get_label('non_billable', 'Non Billable') }}
                        </option>
                    </select>
                    @error('billing_type')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"
                        for="completion_percentage">{{ get_label('completion_percentage', 'Completion Percentage (%)') }}</label>
                    <select class="form-select" name="completion_percentage" id="edit_completion_percentage">
                        @foreach (range(0, 100, 10) as $percentage)
                            <option value="{{ $percentage }}">{{ $percentage }}%</option>
                        @endforeach
                    </select>
                    @error('completion_percentage')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="row">
                <div class="mb-3">
                    <label class="form-label"><?= get_label('note', 'Note') ?></label>
                    <textarea class="form-control" name="note" rows="3" id="taskNote"
                        placeholder="<?= get_label('optional_note', 'Optional Note') ?>"></textarea>
                </div>
            </div>
            <!-- edit Remider Task -->
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="reminder-switch"
                        class="form-label">{{ get_label('enable_reminder', 'Enable Reminder') }}</label>
                    <i class="bx bx-info-circle text-primary" data-bs-toggle="tooltip"data-bs-offset="0,4"
                        data-bs-placement="top" data-bs-html="true"title=""
                        data-bs-original-title="<b>{{ get_label('task_reminder', 'Task Reminder') }}:</b> {{ get_label('task_reminder_info', 'Enable this option to set reminders for tasks. You can configure reminder frequencies (daily, weekly, or monthly), specific times, and customize alerts to ensure you stay on track with task deadlines.') }}"></i>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="edit-reminder-switch"
                            name="enable_reminder">
                        <label class="form-check-label"
                            for="reminder-switch">{{ get_label('enable_task_reminder', 'Enable Task Reminder') }}</label>
                    </div>
                </div>
            </div>
            <div id="edit-reminder-settings" class="d-none">
                <!-- Frequency Type -->
                <div class="mb-3">
                    <label for="frequency-type"
                        class="form-label">{{ get_label('frequency_type', 'Frequency Type') }}</label>
                    <select class="form-select" id="edit-frequency-type" name="frequency_type" required>
                        <option value="daily">{{ get_label('daily', 'Daily') }}</option>
                        <option value="weekly">{{ get_label('weekly', 'Weekly') }}</option>
                        <option value="monthly">{{ get_label('monthly', 'Monthly') }}</option>
                    </select>
                </div>
                <!-- Day of Week (Weekly Only) -->
                <div class="d-none mb-3" id="edit-day-of-week-group">
                    <label for="day-of-week"
                        class="form-label">{{ get_label('day_of_the_week', 'Day of the Week') }}</label>
                    <select class="form-select" id="edit-day-of-week" name="day_of_week">
                        <option value="">{{ get_label('any_day', 'Any Day') }}</option>
                        <option value="1">{{ get_label('monday', 'Monday') }}</option>
                        <option value="2">{{ get_label('tuesday', 'Tuesday') }}</option>
                        <option value="3">{{ get_label('wednesday', 'Wednesday') }}</option>
                        <option value="4">{{ get_label('thursday', 'Thursday') }}</option>
                        <option value="5">{{ get_label('friday', 'Friday') }}</option>
                        <option value="6">{{ get_label('saturday', 'Saturday') }}</option>
                        <option value="7">{{ get_label('sunday', 'Sunday') }}</option>
                    </select>
                </div>
                <!-- Day of Month (Monthly Only) -->
                <div class="d-none mb-3" id="edit-day-of-month-group">
                    <label for="day-of-month"
                        class="form-label">{{ get_label('day_of_the_month', 'Day of the Month') }}</label>
                    <select class="form-select" id="edit-day-of-month" name="day_of_month">
                        <option value="">{{ get_label('any_day', 'Any Day') }}</option>
                        @foreach (range(1, 31) as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Time of Day -->
                <div class="mb-3">
                    <label for="time-of-day"
                        class="form-label">{{ get_label('time_of_day', 'Time of Day') }}</label>
                    <input type="time" class="form-control" id="edit-time-of-day" name="time_of_day">
                </div>
            </div>
            <!--edit Recursion Task -->
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="edit-recurring-task-switch" class="form-label">
                        {{ get_label('enable_recurring_task', 'Enable Recurring Task') }}
                    </label>
                    <i class="bx bx-info-circle text-primary" data-bs-toggle="tooltip" data-bs-offset="0,4"
                        data-bs-placement="top" data-bs-html="true" title=""
                        data-bs-original-title="<b>{{ get_label('recurring_tasks', 'Recurring Tasks') }}:</b> {{ get_label('recurring_tasks_info', 'This option enables the creation of recurring tasks. You can set the frequency (daily, weekly, monthly, yearly), specific days, and manage the recurrence schedule efficiently.') }}">
                    </i>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="edit-recurring-task-switch"
                            name="enable_recurring_task"
                            {{ isset($task) && $task->recurringTask ? 'checked' : '' }}>
                        <label class="form-check-label" for="edit-recurring-task-switch">
                            {{ get_label('enable_recurring_task', 'Enable Recurring Task') }}
                        </label>
                    </div>
                </div>
                <div id="edit-recurring-task-settings"
                    class="{{ isset($task) && $task->recurringTask ? '' : 'd-none' }}">
                    <!-- Frequency Type -->
                    <div class="mb-3">
                        <label for="edit-recurrence-frequency" class="form-label">
                            {{ get_label('recurrence_frequency', 'Recurrence Frequency') }}
                        </label>
                        <select class="form-select" id="edit-recurrence-frequency" name="recurrence_frequency"
                            required>
                            <option value="daily"
                                {{ isset($task) && optional($task->recurringTask)->frequency == 'daily' ? 'selected' : '' }}>
                                {{ get_label('daily', 'Daily') }}
                            </option>
                            <option value="weekly"
                                {{ isset($task) && optional($task->recurringTask)->frequency == 'weekly' ? 'selected' : '' }}>
                                {{ get_label('weekly', 'Weekly') }}
                            </option>
                            <option value="monthly"
                                {{ isset($task) && optional($task->recurringTask)->frequency == 'monthly' ? 'selected' : '' }}>
                                {{ get_label('monthly', 'Monthly') }}
                            </option>
                            <option value="yearly"
                                {{ isset($task) && optional($task->recurringTask)->frequency == 'yearly' ? 'selected' : '' }}>
                                {{ get_label('yearly', 'Yearly') }}
                            </option>
                        </select>
                    </div>
                    <!-- Day of Week (Weekly Only) -->
                    <div class="{{ isset($task) && optional($task->recurringTask)->frequency == 'weekly' ? '' : 'd-none' }} mb-3"
                        id="edit-recurrence-day-of-week-group">
                        <label for="edit-recurrence-day-of-week" class="form-label">
                            {{ get_label('day_of_the_week', 'Day of the Week') }}
                        </label>
                        <select class="form-select" id="edit-recurrence-day-of-week"
                            name="recurrence_day_of_week">
                            <option value="">{{ get_label('any_day', 'Any Day') }}</option>
                            <option value="1"
                                {{ isset($task) && optional($task->recurringTask)->day_of_week == '1' ? 'selected' : '' }}>
                                {{ get_label('monday', 'Monday') }}
                            </option>
                            <option value="2"
                                {{ isset($task) && optional($task->recurringTask)->day_of_week == '2' ? 'selected' : '' }}>
                                {{ get_label('tuesday', 'Tuesday') }}
                            </option>
                            <option value="3"
                                {{ isset($task) && optional($task->recurringTask)->day_of_week == '3' ? 'selected' : '' }}>
                                {{ get_label('wednesday', 'Wednesday') }}
                            </option>
                            <option value="4"
                                {{ isset($task) && optional($task->recurringTask)->day_of_week == '4' ? 'selected' : '' }}>
                                {{ get_label('thursday', 'Thursday') }}
                            </option>
                            <option value="5"
                                {{ isset($task) && optional($task->recurringTask)->day_of_week == '5' ? 'selected' : '' }}>
                                {{ get_label('friday', 'Friday') }}
                            </option>
                            <option value="6"
                                {{ isset($task) && optional($task->recurringTask)->day_of_week == '6' ? 'selected' : '' }}>
                                {{ get_label('saturday', 'Saturday') }}
                            </option>
                            <option value="7"
                                {{ isset($task) && optional($task->recurringTask)->day_of_week == '7' ? 'selected' : '' }}>
                                {{ get_label('sunday', 'Sunday') }}
                            </option>
                        </select>
                    </div>
                    <!-- Day of Month (Monthly and Yearly Only) -->
                    <div class="{{ isset($task) && in_array(optional($task->recurringTask)->frequency, ['monthly', 'yearly']) ? '' : 'd-none' }} mb-3"
                        id="edit-recurrence-day-of-month-group">
                        <label for="edit-recurrence-day-of-month" class="form-label">
                            {{ get_label('day_of_the_month', 'Day of the Month') }}
                        </label>
                        <select class="form-select" id="edit-recurrence-day-of-month"
                            name="recurrence_day_of_month">
                            <option value="">{{ get_label('any_day', 'Any Day') }}</option>
                            @foreach (range(1, 31) as $day)
                                <option value="{{ $day }}"
                                    {{ isset($task) && optional($task->recurringTask)->day_of_month == $day ? 'selected' : '' }}>
                                    {{ $day }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Month of Year (Yearly Only) -->
                    <div class="{{ isset($task) && optional($task->recurringTask)->frequency == 'yearly' ? '' : 'd-none' }} mb-3"
                        id="edit-recurrence-month-of-year-group">
                        <label for="edit-recurrence-month-of-year" class="form-label">
                            {{ get_label('month_of_the_year', 'Month of the Year') }}
                        </label>
                        <select class="form-select" id="edit-recurrence-month-of-year"
                            name="recurrence_month_of_year">
                            <option value="">{{ get_label('any_month', 'Any Month') }}</option>
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}"
                                    {{ isset($task) && optional($task->recurringTask)->month_of_year == $month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Starts From -->
                    <div class="mb-3">
                        <label for="edit-recurrence-starts-from" class="form-label">
                            {{ get_label('starts_from', 'Starts From') }}
                        </label>
                        <input type="date" class="form-control" id="edit-recurrence-starts-from"
                            name="recurrence_starts_from"
                            value="{{ isset($task) && optional($task->recurringTask)->starts_from ? \Carbon\Carbon::parse($task->recurringTask->starts_from)->format('Y-m-d') : '' }}">
                    </div>
                    <!-- Number of Occurrences -->
                    <div class="mb-3">
                        <label for="edit-recurrence-occurrences" class="form-label">
                            {{ get_label('number_of_occurrences', 'Number of Occurrences') }}
                        </label>
                        <input type="number" class="form-control" id="edit-recurrence-occurrences"
                            name="recurrence_occurrences" min="1"
                            value="{{ isset($task) ? optional($task->recurringTask)->number_of_occurrences : '' }}">
                    </div>
                </div>
            </div>
            @php
                $isEdit = true;
            @endphp
            <x-custom-fields :isEdit="$isEdit" :fields="$taskCustomFields" />
        </div>

    </x-ui.offcanvas>
@endif
