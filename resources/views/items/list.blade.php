@extends('layout')
@section('title')
<?= get_label('items', 'Items') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-2 mt-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{url('home')}}"><?= get_label('home', 'Home') ?></a>
                    </li>
                    <!-- <li class="breadcrumb-item">
                        <a href="{{url('payslips')}}"><?= get_label('payslips', 'Payslips') ?></a>
                    </li> -->
                    <li class="breadcrumb-item active">
                        <?= get_label('items', 'Items') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_item_modal"><button type="button" class="btn btn-sm btn-primary action_create_items" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title=" <?= get_label('create_item', 'Create item') ?>"><i class="bx bx-plus"></i></button></a>
        </div>
    </div>
    @if ($items > 0)
    @php
    $visibleColumns = getUserPreferences('items');
    @endphp
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <select class="form-select tom_static_select" id="unit_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_units', 'Select Units') ?>" data-allow-clear="true" multiple>
                        @foreach ($units as $unit)
                        <option value="{{$unit->id}}">{{$unit->title}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 d-flex justify-content-md-end">
                    <button type="button" class="btn btn-sm btn-secondary" id="clear_filters"
                        style="height: 38px;"><i class='bx bx-refresh'></i> <?= get_label('clear_filters', 'Clear filters') ?></button>
                </div>
            </div>
        </div>
    </div>
    <div class="card border shadow-none">
        <div class="card-body p-0">
            @php
            $columns = [
                ['checkbox' => true],
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true, 'visible' => (in_array('id', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true, 'visible' => (in_array('title', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'price', 'label' => get_label('price', 'Price'), 'sortable' => true, 'visible' => (in_array('price', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'unit_id', 'label' => get_label('unit_id', 'Unit ID'), 'sortable' => true, 'visible' => (in_array('unit_id', $visibleColumns))],
                ['field' => 'unit', 'label' => get_label('unit', 'Unit'), 'sortable' => true, 'visible' => (in_array('unit', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'description', 'label' => get_label('description', 'Description'), 'sortable' => true, 'visible' => (in_array('description', $visibleColumns))],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => (in_array('created_at', $visibleColumns))],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => (in_array('updated_at', $visibleColumns))],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns))]
            ];
            @endphp
            <x-tk-table 
                id="table"
                url="{{ url('/items/list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParams"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="items">
                    <input type="hidden" id="save_column_visibility">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Items'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
</script>
<script src="{{asset('assets/js/pages/items.js')}}">
                                </script>
                                @endsection