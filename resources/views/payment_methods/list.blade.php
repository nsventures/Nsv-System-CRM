@extends('layout')
@section('title')
<?= get_label('payment_methods', 'Payment methods') ?>
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
                        <?= get_label('payment_methods', 'Payment methods') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_pm_modal"><button type="button" class="btn btn-sm btn-primary action_create_payment_methods" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title=" <?= get_label('create_payment_method', 'Create payment method') ?>"><i class="bx bx-plus"></i></button></a>
        </div>
    </div>
    @if ($payment_methods > 0)
    <div class="card border shadow-none">
        <div class="card-body p-0">
            @php
            $columns = [
                ['checkbox' => true],
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true],
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => false],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => false],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions')]
            ];
            @endphp
            <x-tk-table 
                id="table"
                url="{{ url('/payment-methods/list') }}"
                :columns="$columns"
                data-sort-name="id"
                data-sort-order="desc"
                data-query-params="queryParams"
            >
                <x-slot name="before">
                    <input type="hidden" id="data_type" value="payment-methods">
                </x-slot>
            </x-tk-table>
        </div>
    </div>
    @else
    <?php
    $type = 'Payment methods'; ?>
    <x-empty-state-card :type="$type" />
    @endif
</div>
<script>
    var label_update = '<?= get_label('update', 'Update') ?>';
    var label_delete = '<?= get_label('delete', 'Delete') ?>';
</script>
<script src="{{asset('assets/js/pages/payment-methods.js')}}"></script>
@endsection