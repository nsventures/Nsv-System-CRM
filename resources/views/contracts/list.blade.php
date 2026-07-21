@extends('layout')
@section('title')
<?= get_label('contracts', 'Contracts') ?>
@endsection
@php
$visibleColumns = getUserPreferences('contracts');
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
                        <?= get_label('contracts', 'Contracts') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_contract_modal"><button type="button" class="btn btn-sm btn-primary action_create_contracts" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title=" <?= get_label('create_contract', 'Create contract') ?>"><i class="bx bx-plus"></i></button></a>
            <a href="{{url('contracts/contract-types')}}"><button type="button" class="btn btn-sm btn-primary action_manage_contract_types" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('contract_types', 'Contract types') ?>"><i class='bx bx-list-ul'></i></button></a>
        </div>
    </div>
    @if ($contracts > 0)
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <x-advanced-date-filters prefix="contract" />
                <div class="col-md-4">
                    <label class="form-label"><?= get_label('projects', 'Projects') ?></label>
                    <select class="form-select tom_projects_select" id="project_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_projects', 'Select Projects') ?>" multiple>
                    </select>
                </div>
                @if (!isClient() || isAdminOrHasAllDataAccess())
                <div class="col-md-4">
                    <label class="form-label"><?= get_label('clients', 'Clients') ?></label>
                    <select class="form-select tom_clients_select" id="client_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_clients', 'Select Clients') ?>" multiple>
                    </select>
                </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label"><?= get_label('types', 'Types') ?></label>
                    <select class="form-select tom_contract_types_select" id="type_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_types', 'Select Types') ?>" multiple>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= get_label('status', 'Status') ?></label>
                    <select class="form-select tom_static_select" id="status_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_statuses', 'Select statuses') ?>" data-allow-clear="true" multiple>
                        <option value="signed"><?= get_label('signed', 'Signed') ?></option>
                        <option value="not_signed"><?= get_label('not_signed', 'Not signed') ?></option>
                        <option value="partially_signed"><?= get_label('partially_signed', 'Partially signed') ?></option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-secondary clear-contracts-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
                        <i class="bx bx-refresh"></i> {{ get_label('clear_filters', 'Clear Filters') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card border shadow-none">
        <div class="card-body p-0">
            @php
            $columns = [
                ['checkbox' => true],
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true, 'visible' => (in_array('id', $visibleColumns) || empty($visibleColumns)), 'formatter' => 'idFormatter'],
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true, 'visible' => (in_array('title', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'client', 'label' => get_label('client', 'Client'), 'sortable' => false, 'visible' => (in_array('client', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'project', 'label' => get_label('project', 'Project'), 'sortable' => false, 'visible' => (in_array('project', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'contract_type', 'label' => get_label('type', 'Type'), 'sortable' => false, 'visible' => (in_array('contract_type', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'start_date', 'label' => get_label('starts_at', 'Starts at'), 'sortable' => true, 'visible' => (in_array('start_date', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'end_date', 'label' => get_label('ends_at', 'Ends at'), 'sortable' => true, 'visible' => (in_array('end_date', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'duration', 'label' => get_label('duration', 'Duration'), 'sortable' => false, 'visible' => in_array('duration', $visibleColumns)],
                ['field' => 'value', 'label' => get_label('value', 'Value'), 'sortable' => true, 'visible' => (in_array('value', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'promisor_sign', 'label' => get_label('promisor_sign_status', 'Promisor sign status'), 'sortable' => true, 'visible' => in_array('promisor_sign', $visibleColumns)],
                ['field' => 'promisee_sign', 'label' => get_label('promisee_sign_status', 'Promisee sign status'), 'sortable' => true, 'visible' => in_array('promisee_sign', $visibleColumns)],
                ['field' => 'status', 'label' => get_label('status', 'Status'), 'visible' => (in_array('status', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'description', 'label' => get_label('description', 'Description'), 'sortable' => true, 'visible' => in_array('description', $visibleColumns)],
                ['field' => 'created_by', 'label' => get_label('created_by', 'Created by'), 'sortable' => false, 'visible' => (in_array('created_by', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => in_array('created_at', $visibleColumns)],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => in_array('updated_at', $visibleColumns)],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns))]
            ];
            @endphp
            <x-tk-table 
                id="contracts_table"
                url="{{ url('/contracts/list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParams"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="contracts">
                    <input type="hidden" id="data_table" value="contracts_table">
                    <input type="hidden" id="save_column_visibility">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Contracts'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
    var label_duplicate = '<?= get_label('duplicate', 'Duplicate') ?>';
    var label_contract_id_prefix = '<?= get_label('contract_id_prefix', 'CTR-') ?>';
</script>
<script src="{{asset('assets/js/pages/contracts.js')}}">
</script>
@endsection
