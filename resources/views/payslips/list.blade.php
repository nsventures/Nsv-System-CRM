@extends('layout')
@section('title')
<?= get_label('payslips', 'Payslips') ?>
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
                        <?= get_label('payslips', 'Payslips') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{url('payslips/create')}}"><button type="button" class="btn btn-sm btn-primary action_create_payslips" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title=" <?= get_label('create_payslip', 'Create payslip') ?>"><i class="bx bx-plus"></i></button></a>
        </div>
    </div>
    @if ($payslips > 0)
    @php
    $visibleColumns = getUserPreferences('payslips');
    @endphp
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <input class="form-control" type="month" id="filter_payslip_month" name="month">
                </div>
                @if(isAdminOrHasAllDataAccess())
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_users_select" id="user_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_users', 'Select Users') ?>" multiple>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_users_select" id="user_creators_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_user_creators', 'Select User Creators') ?>" multiple>                                                
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_clients_select" id="client_creators_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_client_creators', 'Select Client Creators') ?>" multiple>                        
                    </select>
                </div>
                @endif
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_static_select" id="status_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_statuses', 'Select statuses') ?>" data-allow-clear="true" multiple>
                        <option value="1"><?= get_label('paid', 'Paid') ?></option>
                        <option value="0"><?= get_label('unpaid', 'Unpaid') ?></option>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button class="btn btn-secondary clear-payslips-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
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
                ['field' => 'user', 'label' => get_label('user', 'User'), 'sortable' => false, 'visible' => (in_array('user', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'month', 'label' => get_label('month', 'Month'), 'sortable' => true, 'visible' => (in_array('month', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'basic_salary', 'label' => get_label('basic_salary', 'Basic salary'), 'sortable' => true, 'visible' => (in_array('basic_salary', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'working_days', 'label' => get_label('working_days', 'Working days'), 'sortable' => true, 'visible' => in_array('working_days', $visibleColumns)],
                ['field' => 'lop_days', 'label' => get_label('lop_days', 'Loss of pay days'), 'sortable' => true, 'visible' => in_array('lop_days', $visibleColumns)],
                ['field' => 'paid_days', 'label' => get_label('paid_days', 'Paid days'), 'sortable' => true, 'visible' => in_array('paid_days', $visibleColumns)],
                ['field' => 'leave_deduction', 'label' => get_label('leave_deduction', 'Leave deduction'), 'sortable' => true, 'visible' => in_array('leave_deduction', $visibleColumns)],
                ['field' => 'ot_hours', 'label' => get_label('over_time_hours', 'Over time hours'), 'sortable' => true, 'visible' => in_array('ot_hours', $visibleColumns)],
                ['field' => 'ot_rate', 'label' => get_label('over_time_rate', 'Over time rate'), 'sortable' => true, 'visible' => in_array('ot_rate', $visibleColumns)],
                ['field' => 'ot_payment', 'label' => get_label('over_time_payment', 'Over time payment'), 'sortable' => true, 'visible' => in_array('ot_payment', $visibleColumns)],
                ['field' => 'incentives', 'label' => get_label('incentives', 'Incentives'), 'sortable' => true, 'visible' => in_array('incentives', $visibleColumns)],
                ['field' => 'bonus', 'label' => get_label('bonus', 'Bonus'), 'sortable' => true, 'visible' => in_array('bonus', $visibleColumns)],
                ['field' => 'total_allowance', 'label' => get_label('total_allowance', 'Total allowance'), 'sortable' => true, 'visible' => in_array('total_allowance', $visibleColumns)],
                ['field' => 'total_deductions', 'label' => get_label('total_deductions', 'Total deductions'), 'sortable' => true, 'visible' => in_array('total_deductions', $visibleColumns)],
                ['field' => 'net_pay', 'label' => get_label('net_pay', 'Net pay'), 'sortable' => true, 'visible' => (in_array('net_pay', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'payment_method', 'label' => get_label('payment_method', 'Payment method'), 'sortable' => true, 'visible' => in_array('payment_method', $visibleColumns)],
                ['field' => 'payment_date', 'label' => get_label('payment_date', 'Payment date'), 'sortable' => true, 'visible' => in_array('payment_date', $visibleColumns)],
                ['field' => 'status', 'label' => get_label('status', 'Status'), 'sortable' => true, 'visible' => (in_array('status', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'note', 'label' => get_label('note', 'Note'), 'sortable' => true, 'visible' => in_array('note', $visibleColumns)],
                ['field' => 'created_by', 'label' => get_label('created_by', 'Created by'), 'sortable' => false, 'visible' => in_array('created_by', $visibleColumns)],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => in_array('created_at', $visibleColumns)],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => in_array('updated_at', $visibleColumns)],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns))]
            ];
            @endphp
            <x-tk-table 
                id="payslips_table"
                url="{{ url('/payslips/list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParams"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="payslips">
                    <input type="hidden" id="data_table" value="payslips_table">
                    <input type="hidden" id="save_column_visibility">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Payslips'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
    var label_duplicate = '<?= get_label('duplicate', 'Duplicate') ?>';
    var label_payslip_id_prefix = '<?= get_label('payslip_id_prefix', 'PSL-') ?>';
    var decimal_points = <?= intval($general_settings['decimal_points_in_currency'] ?? '2') ?>;
</script>
<script src="{{asset('assets/js/pages/payslips.js')}}"></script>
@endsection