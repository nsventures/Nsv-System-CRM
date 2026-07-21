@php
    $visibleColumns = getUserPreferences('status');
@endphp
<!-- status -->
{{$slot}}
@if (is_countable($statuses) && count($statuses) > 0)
<div class="card border shadow-none">
    <div class="card-body p-0">
        @php
            $columns = [
                ['checkbox' => true],
                ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true, 'visible' => (in_array('id', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'title', 'label' => get_label('title', 'Title'), 'sortable' => true, 'visible' => (in_array('title', $visibleColumns) || empty($visibleColumns))],
                ['field' => 'color', 'label' => get_label('preview', 'Preview'), 'sortable' => true, 'visible' => (in_array('color', $visibleColumns) || empty($visibleColumns))],
            ];

            if (isAdminOrHasAllDataAccess()) {
                $columns[] = ['field' => 'roles_has_access', 'label' => get_label('allowed_roles', 'Allowed Roles') . ' <i class="bx bx-info-circle text-primary" title="' . get_label('roles_can_set_status_info_1', 'Including Admin and Roles with All Data Access Permission, Roles That Can Set This Status.') . '"></i>', 'visible' => (in_array('roles_has_access', $visibleColumns) || empty($visibleColumns))];
            }

            $columns = array_merge($columns, [
                ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true, 'visible' => in_array('created_at', $visibleColumns)],
                ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true, 'visible' => in_array('updated_at', $visibleColumns)],
                ['field' => 'actions', 'label' => get_label('actions', 'Actions'), 'visible' => (in_array('actions', $visibleColumns) || empty($visibleColumns))],
            ]);
        @endphp
        <x-tk-table
            id="table"
            :url="url('/status/list')"
            :columns="$columns"
            data-sort-name="id"
            data-sort-order="desc"
            data-query-params="queryParams"
        >
            <x-slot:before>
                <input type="hidden" id="data_type" value="status">
                <input type="hidden" id="save_column_visibility">
            </x-slot:before>
        </x-tk-table>
    </div>
</div>
@else
<?php
$type = 'Status'; ?>
<x-empty-state-card :type="$type" />
@endif