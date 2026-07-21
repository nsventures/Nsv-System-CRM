@extends('layout')
@section('title')
<?= get_label('expense_types', 'Expense types') ?>
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
                        <a href="{{url('expenses')}}"><?= get_label('expenses', 'Expenses') ?></a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('expenses_types', 'Expense types') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_expense_type_modal"><button type="button" class="btn btn-sm btn-primary action_create_expense_types" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title=" <?= get_label('create_expense_type', 'Create expense type') ?>"><i class="bx bx-plus"></i></button></a>
            <a href="{{url('expenses')}}"><button type="button" class="btn btn-sm btn-primary action_manage_expenses" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('expenses', 'Expenses') ?>"><i class='bx bx-list-ul'></i></button></a>
        </div>
    </div>
    @if ($expense_types > 0)
    <div class="card border shadow-none">
        <div class="card-body p-0">
            @php
            $columns = [
                ['checkbox' => true],
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true],
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true],
                ['field' => 'description', 'label' => get_label('description', 'Description'), 'sortable' => true, 'visible' => false],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => false],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => false],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions')]
            ];
            @endphp
            <x-tk-table 
                id="table"
                url="{{ url('/expenses/expense-types-list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParamsExpenseTypes"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="expense-types">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Expense types'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
</script>
<script src="{{asset('assets/js/pages/expenses.js')}}"></script>
@endsection