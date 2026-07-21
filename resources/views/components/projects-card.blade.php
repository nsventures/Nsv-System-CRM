<!-- projects card -->
@php
$flag = (Request::segment(1) == 'home' || Request::segment(1) == 'users' || Request::segment(1) == 'clients' || isset($viewAssigned) && $viewAssigned == 1) || isset($projects) && !is_countable($projects) || count($projects) == 0 ? 0 : 1;
$visibleColumns = getUserPreferences('projects');
$auth_user = getAuthenticatedUser();
@endphp
<div class="mt-2">
        {{$slot}}
        @if ((isset($projects) && is_countable($projects) && count($projects) > 0) || (isset($viewAssigned) && $viewAssigned == 1))
        @php
            // Get selected statuses and tags from the request
            $selectedStatuses = request()->input('statuses', []);
            $selectedTags = request()->input('tags', []);

            $statuses = \App\Models\Status::whereIn('id', $selectedStatuses)->get();
            $tags = \App\Models\Tag::whereIn('id', $selectedTags)->get();
        @endphp
        <div class="card mb-3 border shadow-none">
            <div class="card-body p-3">
                <div class="row g-3 align-items-end tk-filter-row">
                    {{-- Date Between --}}
                    <div class="col-md-3">
                        <label class="form-label" for="project_date_between"><?= get_label('date_between', 'Date Between') ?></label>
                        <input type="text" class="tk-input" id="project_date_between" name="project_date_between" placeholder="<?= get_label('date_between', 'Date Between') ?>" autocomplete="off">
                    </div>
                    {{-- Start Date Between --}}
                    <div class="col-md-3">
                        <label class="form-label" for="project_start_date_between"><?= get_label('start_date_between', 'Start Date Between') ?></label>
                        <input type="text" class="tk-input" id="project_start_date_between" name="project_start_date_between" placeholder="<?= get_label('start_date_between', 'Start Date Between') ?>" autocomplete="off">
                    </div>
                    {{-- End Date Between --}}
                    <div class="col-md-3">
                        <label class="form-label" for="project_end_date_between"><?= get_label('end_date_between', 'Date Between') ?></label>
                        <input type="text" class="tk-input" id="project_end_date_between" name="project_end_date_between" placeholder="<?= get_label('end_date_between', 'End Date Between') ?>" autocomplete="off">
                    </div>
                    {{-- Users --}}
                    @if(isAdminOrHasAllDataAccess() && !isset($viewAssigned))
                    @if(!isset($id) || (explode('_',$id)[0] !='client' && explode('_',$id)[0] !='user'))
                    <div class="col-md-3">
                        <label class="form-label" for="project_user_filter"><?= get_label('users', 'Users') ?></label>
                        <select class="tk-select tom_users_select" id="project_user_filter" name="user_ids[]" multiple data-placeholder="<?= get_label('select_users', 'Select Users') ?>">
                        </select>
                    </div>
                    {{-- Clients --}}
                    <div class="col-md-3">
                        <label class="form-label" for="project_client_filter"><?= get_label('clients', 'Clients') ?></label>
                        <select class="tk-select tom_clients_select" id="project_client_filter" name="client_ids[]" multiple data-placeholder="<?= get_label('select_clients', 'Select Clients') ?>">
                        </select>
                    </div>
                    @endif
                    @endif
                    {{-- Statuses --}}
                    <div class="col-md-3">
                        <label class="form-label" for="project_status_filter"><?= get_label('statuses', 'Statuses') ?></label>
                        <select class="tk-select tom_statuses_filter" id="project_status_filter" name="status_ids[]" multiple data-placeholder="<?= get_label('select_statuses', 'Select Statuses') ?>">
                            @foreach($statuses as $status)
                            <option value="{{ $status->id }}" selected>{{ $status->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Priorities --}}
                    <div class="col-md-3">
                        <label class="form-label" for="project_priority_filter"><?= get_label('priorities', 'Priorities') ?></label>
                        <select class="tk-select tom_priorities_filter" id="project_priority_filter" name="priority_ids[]" multiple data-placeholder="<?= get_label('select_priorities', 'Select Priorities') ?>">
                        </select>
                    </div>
                    {{-- Tags --}}
                    <div class="col-md-3">
                        <label class="form-label" for="project_tag_filter"><?= get_label('tags', 'Tags') ?></label>
                        <select class="tk-select tom_tags_select" id="project_tag_filter" name="tag_ids[]" multiple data-placeholder="<?= get_label('select_tags', 'Select Tags') ?>">
                            @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" selected>{{ $tag->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                {{-- Hidden fields for date range values --}}
                <input type="hidden" id="project_date_between_from" name="project_date_from">
                <input type="hidden" id="project_date_between_to" name="project_date_to">
                <input type="hidden" id="project_start_date_between_from" name="project_start_date_from">
                <input type="hidden" id="project_start_date_between_to" name="project_start_date_to">
                <input type="hidden" id="project_end_date_between_from" name="project_end_date_from">
                <input type="hidden" id="project_end_date_between_to" name="project_end_date_to">
            </div>
        </div>

        <input type="hidden" id="is_favorites" value="{{$favorites??''}}">
        @php
            $columns = [
                ['checkbox' => true],
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true, 'visible' => (in_array('id', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true, 'visible' => (in_array('title', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'users', 'label' => get_label('users', 'Users'), 'visible' => (in_array('users', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'clients', 'label' => get_label('clients', 'Clients'), 'visible' => (in_array('clients', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'status_id', 'label' => get_label('status', 'Status'), 'sortable' => true, 'visible' => (in_array('status_id', $visibleColumns) || empty($visibleColumns)), 'class' => 'status-column'],
                ['field' => 'priority_id', 'label' => get_label('priority', 'Priority'), 'sortable' => true, 'visible' => (in_array('priority_id', $visibleColumns) || empty($visibleColumns)), 'class' => 'priority-column'],
                ['field' => 'start_date', 'label' => get_label('starts_at', 'Starts at'), 'sortable' => true, 'visible' => (in_array('start_date', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'end_date', 'label' => get_label('ends_at', 'Ends at'), 'sortable' => true, 'visible' => (in_array('end_date', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'budget', 'label' => get_label('budget', 'Budget'), 'sortable' => true, 'visible' => (in_array('budget', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'tags', 'label' => get_label('tags', 'Tags'), 'visible' => (in_array('tags', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => in_array('created_at', $visibleColumns)],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => in_array('updated_at', $visibleColumns)],
            ];

            // Add custom fields dynamically
            if (isset($customFields) && $customFields->isNotEmpty()) {
                foreach ($customFields as $customField) {
                    if ($customField->visibility !== null) {
                        $col = [
                            'field' => 'custom_field_' . $customField->id,
                            'label' => $customField->field_label,
                            'visible' => (in_array('custom_field_' . $customField->id, $visibleColumns) || empty($visibleColumns)),
                            'class' => 'truncate-col',
                        ];
                        if ($customField->field_type === 'checkbox') {
                            $col['formatter'] = 'customFieldFormatter';
                        }
                        $columns[] = $col;
                    }
                }
            }

            $columns[] = ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns))];

            $projectUrl = isset($viewAssigned) && $viewAssigned == 1
                ? ''
                : (!empty($id)
                    ? url('/projects/listing/' . $id . '?from_home=' . (request()->is('home') ? '1' : '0'))
                    : url('/projects/listing?from_home=' . (request()->is('home') ? '1' : '0')));
        @endphp
        <div class="card border shadow-none">
            <div class="card-body p-0">
                <x-tk-table
                    id="projects_table"
                    :url="$projectUrl"
                    :columns="$columns"
                    data-sort-name="id"
                    data-sort-order="desc"
                    data-query-params="queryParamsProjects"
                >
                    <x-slot:before>
                        <input type="hidden" id="data_type" value="projects">
                        <input type="hidden" id="data_table" value="projects_table">
                        <input type="hidden" id="data_reload" value="{{request()->is('home') ? '1' : '0'}}">
                        <input type="hidden" id="save_column_visibility">
                        <input type="hidden" id="multi_select">
                    </x-slot:before>
                </x-tk-table>
            </div>
        </div>
        @else
        <?php
        $type = 'Projects'; ?>
        <x-empty-state-card :type="$type" />
        @endif
</div>
@section('page_scripts')
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
    var label_not_assigned = '<?= get_label('not_assigned', 'Not assigned') ?>';
    var add_favorite = '<?= get_label('add_favorite', 'Click to mark as favorite') ?>';
    var remove_favorite = '<?= get_label('remove_favorite', 'Click to remove from favorite') ?>';
    var label_duplicate = '<?= get_label('duplicate', 'Duplicate') ?>';
</script>
<script src="{{asset('assets/js/pages/project-list.js')}}"></script>
@endsection
