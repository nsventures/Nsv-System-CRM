@extends('layout')
@section('title')
<?= get_label('deductions', 'Deductions') ?>
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
                    <li class="breadcrumb-item">
                        <a href="{{url('payslips')}}"><?= get_label('payslips', 'Payslips') ?></a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('deductions', 'Deductions') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_deduction_modal"><button type="button" class="btn btn-sm btn-primary action_create_deductions" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title=" <?= get_label('create_deduction', 'Create deduction') ?>"><i class="bx bx-plus"></i></button></a>
        </div>
    </div>
    @if ($deductions > 0)
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_static_select" id="types_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_types', 'Select types') ?>" data-allow-clear="true" multiple>
                        <option value="percentage"><?= get_label('percentage', 'Percentage') ?></option>
                        <option value="amount"><?= get_label('amount', 'Amount') ?></option>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button class="btn btn-secondary clear-deductions-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
                        <i class="bx bx-refresh"></i> {{ get_label('clear_filters', 'Clear Filters') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
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
    <div class="card border shadow-none">
        <div class="card-body p-0">
            <x-tk-table 
                id="table"
                url="{{ url('/deductions/list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParams"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="deductions">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Deductions'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
</script>
<script src="{{asset('assets/js/pages/deductions.js')}}"></script>
@endsection