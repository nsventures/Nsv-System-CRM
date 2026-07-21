@extends('layout')
@section('title')
<?= get_label('activity_log', 'Activity log') ?>
@endsection
@php
$visibleColumns = getUserPreferences('activity_log');
@endphp
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-2 mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{url('home')}}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('activity_log', 'Activity log') ?>
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                @php
                    $meetingsDefaultView = getUserPreferences('meetings', 'default_view');
                @endphp
                @if ($meetingsDefaultView === 'list')
                    <span class="badge bg-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);"><span class="badge bg-secondary" id="set-default-view" data-type="meetings"
                            data-view="list"><?= get_label('set_as_default_view', 'Set as Default View') ?></span></a>
                @endif
            </div>
            <div>

                <a href="{{ route('activity_log.calendar_view') }}"><button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="<?= get_label('calendar_view', 'Calendar view') ?>"><i
                            class='bx bx-calendar'></i></button></a>
            </div>
        </div>
        <div class="card border shadow-none">
            <div class="card-body">
                <div class="row g-3 align-items-end tk-filter-row mb-4">
                    <div class="col-lg-3 col-md-4 col-12">
                        <label for="activity_log_between_date" class="form-label"><?= get_label('date_between', 'Date between') ?></label>
                        <div class="input-group input-group-merge">
                            <input type="text" id="activity_log_between_date" class="form-control" placeholder="<?= get_label('date_between', 'Date between') ?>" autocomplete="off">
                        </div>
                    </div>
                    @if(isAdminOrHasAllDataAccess())
                    <div class="col-lg-3 col-md-4 col-12">
                        <label for="user_filter" class="form-label"><?= get_label('select_actioned_by_users', 'Select Actioned By Users') ?></label>
                        <select class="form-select tom_users_select" id="user_filter" data-placeholder="<?= get_label('select_actioned_by_users', 'Select Actioned By Users') ?>" multiple>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-12">
                        <label for="client_filter" class="form-label"><?= get_label('select_actioned_by_clients', 'Select Actioned By Clients') ?></label>
                        <select class="form-select tom_clients_select" id="client_filter" data-placeholder="<?= get_label('select_actioned_by_clients', 'Select Actioned By Clients') ?>" multiple>
                        </select>
                    </div>
                    @endif
                    <div class="col-lg-3 col-md-4 col-12">
                        <label for="activity_filter" class="form-label"><?= get_label('select_activities', 'Select Activities') ?></label>
                        <select class="form-select tom_static_select" id="activity_filter" data-placeholder="<?= get_label('select_activities', 'Select Activities') ?>" data-allow-clear="true" multiple>
                            <option value="created"><?= get_label('created', 'Created') ?></option>
                            <option value="updated"><?= get_label('updated', 'Updated') ?></option>
                            <option value="duplicated"><?= get_label('duplicated', 'Duplicated') ?></option>
                            <option value="uploaded"><?= get_label('uploaded', 'Uploaded') ?></option>
                            <option value="deleted"><?= get_label('deleted', 'Deleted') ?></option>
                            <option value="updated_status"><?= get_label('updated_status', 'Updated status') ?></option>
                            <option value="updated_priority"><?= get_label('updated_priority', 'Updated priority') ?></option>
                            <option value="signed"><?= get_label('signed', 'Signed') ?></option>
                            <option value="unsigned"><?= get_label('unsigned', 'Unsigned') ?></option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-12">
                        <label for="type_filter" class="form-label"><?= get_label('select_types', 'Select types') ?></label>
                        <select class="form-select tom_static_select" id="type_filter" data-placeholder="<?= get_label('select_types', 'Select types') ?>" data-allow-clear="true" multiple>
                            @foreach ($types as $type)
                            <option value="{{$type}}">{{ Str::title(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-12">
                        <button type="button" class="btn btn-secondary clear-activity-log-filters w-100">
                            <i class="bx bx-refresh me-1"></i><?= get_label('clear_filters', 'Clear Filters') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @php
        $columns = [
            ['checkbox' => true],
            ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true, 'visible' => (in_array('id', $visibleColumns) || empty($visibleColumns))],
            ['field' => 'actor_id', 'label' => get_label('actioned_by_id', 'Actioned By ID'), 'sortable' => true, 'visible' => (in_array('actor_id', $visibleColumns))],
            ['field' => 'actor_name', 'label' => get_label('actioned_by', 'Actioned By'), 'sortable' => true, 'visible' => (in_array('actor_name', $visibleColumns) || empty($visibleColumns))],
            ['field' => 'actor_type', 'label' => get_label('actioned_by_type', 'Actioned By Type'), 'sortable' => true, 'visible' => (in_array('actor_type', $visibleColumns))],
            ['field' => 'type_id', 'label' => get_label('type_id', 'Type ID'), 'sortable' => true, 'visible' => (in_array('type_id', $visibleColumns))],
            ['field' => 'parent_type_id', 'label' => get_label('parent_type_id', 'Parent type ID'), 'sortable' => true, 'visible' => (in_array('parent_type_id', $visibleColumns))],
            ['field' => 'activity', 'label' => get_label('activity', 'Activity'), 'sortable' => true, 'visible' => (in_array('activity', $visibleColumns) || empty($visibleColumns))],
            ['field' => 'type', 'label' => get_label('type', 'Type'), 'sortable' => true, 'visible' => (in_array('type', $visibleColumns) || empty($visibleColumns))],
            ['field' => 'parent_type', 'label' => get_label('parent_type', 'Parent type'), 'sortable' => true, 'visible' => (in_array('parent_type', $visibleColumns))],
            ['field' => 'type_title', 'label' => get_label('type_title', 'Type title'), 'sortable' => true, 'visible' => (in_array('type_title', $visibleColumns) || empty($visibleColumns))],
            ['field' => 'parent_type_title', 'label' => get_label('parent_type_title', 'Parent type title'), 'sortable' => true, 'visible' => (in_array('parent_type_title', $visibleColumns))],
            ['field' => 'message', 'label' => get_label('message', 'Message'), 'sortable' => true, 'visible' => (in_array('message', $visibleColumns))],
            ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => (in_array('created_at', $visibleColumns))],
            ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => (in_array('updated_at', $visibleColumns))],
            ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns))]
        ];
        @endphp
        <div class="card border shadow-none">
            <div class="card-body p-0">
                <x-tk-table
                    id="activity_log_table"
                    :url="url('/activity-log/list')"
                    :columns="$columns"
                    data-sort-name="id"
                    data-sort-order="desc"
                    data-query-params="queryParams"
                >
                    <x-slot:before>
                        <input type="hidden" id="activity_log_between_date_from">
                        <input type="hidden" id="activity_log_between_date_to">
                        <input type="hidden" id="data_type" value="activity-log">
                        <input type="hidden" id="data_table" value="activity_log_table">
                        <input type="hidden" id="save_column_visibility">
                        <input type="hidden" id="multi_select">
                    </x-slot:before>
                </x-tk-table>
            </div>
        </div>
    </div>
    <script>
        var label_delete = '<?= get_label('delete', 'Delete') ?>';
    </script>
    <script src="{{asset('assets/js/pages/activity-log.js')}}">
                                    </script>
@endsection
