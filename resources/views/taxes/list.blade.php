@extends('layout')
@section('title')
<?= get_label('taxes', 'Taxes') ?>
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
                        <?= get_label('taxes', 'Taxes') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_tax_modal"><button type="button" class="btn btn-sm btn-primary action_create_taxes" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title=" <?= get_label('create_tax', 'Create tax') ?>"><i class="bx bx-plus"></i></button></a>
        </div>
    </div>
    @if ($taxes > 0)
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <select class="form-select tom_static_select" id="types_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_types', 'Select types') ?>" data-allow-clear="true" multiple>
                        <option value="percentage"><?= get_label('percentage', 'Percentage') ?></option>
                        <option value="amount"><?= get_label('amount', 'Amount') ?></option>
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
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true],
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true],
                ['field' => 'type', 'label' => get_label('type', 'Type'), 'sortable' => true],
                ['field' => 'amount', 'label' => get_label('amount', 'Amount'), 'sortable' => true],
                ['field' => 'percentage', 'label' => get_label('percentage', 'Percentage'), 'sortable' => true],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => false],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => false],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions')]
            ];
            @endphp
            <x-tk-table 
                id="table"
                url="{{ url('/taxes/list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParams"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="taxes">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Taxes'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
    var decimal_points = <?= intval($general_settings['decimal_points_in_currency'] ?? '2') ?>;
</script>
<script src="{{asset('assets/js/pages/taxes.js')}}"></script>
@endsection