<div class="card">
    <div class="card-body">
        <div class="alert alert-primary d-flex align-items-center">
            <i class="bx bx-move fs-4 me-2"></i>
            <span class="fw-semibold">
                {{ get_label('candidate_status_reorder_info', 'Drag and drop the rows below to change the order of your candidate status.') }}
            </span>
        </div>
        {{ $slot }}

        @php
        $columns = [
            ['checkbox' => true],
            ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true],
            ['field' => 'name', 'label' => get_label('name', 'Name'), 'sortable' => true],
            ['field' => 'order', 'label' => get_label('order', 'Order'), 'sortable' => true],
            ['field' => 'color', 'label' => get_label('color', 'Color'), 'sortable' => true],
            ['field' => 'created_at', 'label' => get_label('created_at', 'Created At'), 'sortable' => true],
            ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated At'), 'sortable' => true],
            ['field' => 'actions', 'label' => get_label('actions', 'Actions')],
        ];
        @endphp

        <div class="card border shadow-none">
            <div class="card-body p-0">
                <x-tk-table 
                    id="table" 
                    :url="route('candidate.status.list')" 
                    :columns="$columns" 
                    data-sort-name="id" 
                    data-sort-order="desc" 
                    data-query-params="queryParams"
                >
                    <x-slot:before>
                        <input type="hidden" id="data_type" value="candidate_status">
                        <input type="hidden" id="save_column_visibility">
                    </x-slot:before>
                </x-tk-table>
            </div>
        </div>
    </div>
</div>
