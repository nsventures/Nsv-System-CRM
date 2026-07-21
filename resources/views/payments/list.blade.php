@extends('layout')
@section('title')
<?= get_label('payments', 'Payments') ?>
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
                    <li class="breadcrumb-item active">
                        <?= get_label('payments', 'Payments') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_payment_modal"><button type="button" class="btn btn-sm btn-primary action_create_payments" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title=" <?= get_label('create_payment', 'Create payment') ?>"><i class="bx bx-plus"></i></button></a>
            <a href="{{url('payment-methods')}}"><button type="button" class="btn btn-sm btn-primary action_manage_payment_methods" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('payment_methods', 'Payment methods') ?>"><i class='bx bx-list-ul'></i></button></a>
        </div>
    </div>
    @if ($payments > 0)
    @php
    $visibleColumns = getUserPreferences('payments');
    @endphp
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <x-advanced-date-filters prefix="payment" :filters="['date_between']" />
                @if(isAdminOrHasAllDataAccess())
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_users_select" id="user_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_users', 'Select Users') ?>" multiple>
                    </select>
                </div>
                @endif
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_invoices_select" id="invoice_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_invoices', 'Select Invoices') ?>" multiple>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_static_select" id="payment_method_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_payment_methods', 'Select payment methods') ?>" data-allow-clear="true" multiple>
                        @foreach ($payment_methods as $pm)
                        <option value="{{$pm->id}}">{{$pm->title}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button class="btn btn-secondary clear-payments-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
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
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true, 'visible' => (in_array('id', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'user_id', 'label' => get_label('user_id', 'User ID'), 'sortable' => true, 'visible' => in_array('user_id', $visibleColumns)],
                ['field' => 'user', 'label' => get_label('user', 'User'), 'sortable' => true, 'visible' => (in_array('user', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'invoice_id', 'label' => get_label('invoice_id', 'Invoice ID'), 'sortable' => true, 'visible' => in_array('invoice_id', $visibleColumns)],
                ['field' => 'invoice', 'label' => get_label('invoice', 'Invoice'), 'sortable' => true, 'visible' => (in_array('invoice', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'payment_method_id', 'label' => get_label('payment_method_id', 'Payment method ID'), 'sortable' => true, 'visible' => in_array('payment_method_id', $visibleColumns)],
                ['field' => 'payment_method', 'label' => get_label('payment_method', 'Payment method'), 'sortable' => true, 'visible' => (in_array('payment_method', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'amount', 'label' => get_label('amount', 'Amount'), 'sortable' => true, 'visible' => (in_array('amount', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'payment_date', 'label' => get_label('payment_date', 'Payment date'), 'sortable' => true, 'visible' => (in_array('payment_date', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'note', 'label' => get_label('note', 'Note'), 'sortable' => true, 'visible' => in_array('note', $visibleColumns)],
                ['field' => 'created_by', 'label' => get_label('created_by', 'Created by'), 'sortable' => false, 'visible' => in_array('created_by', $visibleColumns)],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => in_array('created_at', $visibleColumns)],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => in_array('updated_at', $visibleColumns)],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns))]
            ];
            @endphp
            <x-tk-table 
                id="table"
                url="{{ url('/payments/list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParams"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="payments">
                    <input type="hidden" id="save_column_visibility">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Payments'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
    var label_duplicate = '<?= get_label('duplicate', 'Duplicate') ?>';
</script>
<script src="{{asset('assets/js/pages/payments.js')}}"></script>
@endsection
