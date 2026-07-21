@if (is_countable($templates) && count($templates) > 0)
    <div class="card border shadow-none">
        <div class="card-body p-0">
            {{ $slot }}
            <x-tk-table id="table" data-url="{{ route('email.templates.list') }}"
                data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total"
                data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                data-show-columns="true" data-side-pagination="server" data-pagination="true"
                data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-query-params="queryParams"
                :columns="[
                    ['checkbox' => true],
                    ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true],
                    ['field' => 'name', 'label' => get_label('name', 'Name'), 'sortable' => true],
                    ['field' => 'subject', 'label' => get_label('subject', 'Subject')],
                    ['field' => 'placeholders', 'label' => get_label('placeholders', 'Placeholders')],
                    ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true],
                    ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true],
                    ['field' => 'actions', 'label' => get_label('actions', 'Actions')]
                ]">
                <x-slot:before>
                    <input type="hidden" id="data_type" value="email_templates">
                </x-slot:before>
            </x-tk-table>
        </div>
    </div>
@else
    <?php $type = 'Email Templates'; ?>
    <x-empty-state-card :type="$type" />
@endif
