@php
    $visibleColumns = getUserPreferences('priority');
@endphp
<!-- meetings -->
{{$slot}}
@if (is_countable($priorities) && count($priorities) > 0)
<div class="card border shadow-none">
    <div class="card-body p-0">
        <x-tk-table id="table" :url="url('/priority/list')"
            data-sort-name="id" data-sort-order="desc" data-query-params="queryParams"
            :columns="[
                ['checkbox' => true],
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true, 'visible' => (in_array('id', $visibleColumns) || empty($visibleColumns)) ? true : false],
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true, 'visible' => (in_array('title', $visibleColumns) || empty($visibleColumns)) ? true : false],
                ['field' => 'color', 'label' => get_label('preview', 'Preview'), 'sortable' => true, 'visible' => (in_array('color', $visibleColumns) || empty($visibleColumns)) ? true : false],
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => (in_array('created_at', $visibleColumns)) ? true : false],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => (in_array('updated_at', $visibleColumns)) ? true : false],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns)) ? true : false]
            ]">
            <x-slot:before>
                <input type="hidden" id="data_type" value="priority">
                <input type="hidden" id="save_column_visibility">
            </x-slot:before>
        </x-tk-table>
    </div>
</div>
@else
<?php
$type = 'Priorities'; ?>
<x-empty-state-card :type="$type" />
@endif