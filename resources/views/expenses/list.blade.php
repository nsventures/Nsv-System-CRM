@extends('layout')
@section('title')
<?= get_label('expenses', 'Expenses') ?>
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
                        <?= get_label('expenses', 'Expenses') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_expense_modal"><button type="button" class="btn btn-sm btn-primary action_create_expenses" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title=" <?= get_label('create_expense', 'Create expense') ?>"><i class="bx bx-plus"></i></button></a>
            <a href="{{url('expenses/expense-types')}}"><button type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('expense_types', 'Expense types') ?>"><i class='bx bx-list-ul'></i></button></a>
        </div>
    </div>
    @if ($expenses > 0)
    @php
    $visibleColumns = getUserPreferences('expenses');
    @endphp
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <x-advanced-date-filters prefix="expense" :filters="['date_between']" />
                @if(isAdminOrHasAllDataAccess())
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_users_select" id="user_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_users', 'Select Users') ?>" multiple>
                    </select>
                </div>
                @endif
                <div class="col-md-4 mb-3">
                    <select class="form-select tom_expense_types_select" id="type_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_types', 'Select Types') ?>" multiple>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button class="btn btn-secondary clear-expenses-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
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
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true, 'visible' => (in_array('title', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'expense_type_id', 'label' => get_label('expense_type_id', 'Expense type ID'), 'sortable' => true, 'visible' => in_array('expense_type_id', $visibleColumns)],
                ['field' => 'expense_type', 'label' => get_label('expense_type', 'Expense type'), 'sortable' => true, 'visible' => (in_array('expense_type', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'user_id', 'label' => get_label('user_id', 'User ID'), 'sortable' => true, 'visible' => in_array('user_id', $visibleColumns)],
                ['field' => 'user', 'label' => get_label('user', 'User'), 'sortable' => true, 'visible' => (in_array('user', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'amount', 'label' => get_label('amount', 'Amount'), 'sortable' => true, 'visible' => (in_array('amount', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'expense_date', 'label' => get_label('expense_date', 'Expense date'), 'sortable' => true, 'visible' => (in_array('expense_date', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'note', 'label' => get_label('note', 'Note'), 'sortable' => true, 'visible' => in_array('note', $visibleColumns)],
                ['field' => 'created_by', 'label' => get_label('created_by', 'Created by'), 'sortable' => false, 'visible' => in_array('created_by', $visibleColumns)],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => in_array('created_at', $visibleColumns)],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => in_array('updated_at', $visibleColumns)],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns))]
            ];
            @endphp
            <x-tk-table 
                id="table"
                url="{{ url('/expenses/list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParams"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="expenses">
                    <input type="hidden" id="save_column_visibility">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Expenses'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
    var label_duplicate = '<?= get_label('duplicate', 'Duplicate') ?>';
</script>
<script src="{{asset('assets/js/pages/expenses.js')}}">
                                </script>
                                @endsection
