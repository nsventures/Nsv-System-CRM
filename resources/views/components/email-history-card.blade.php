@if ($emails->count() > 0)
    <div class="card border shadow-none">
        <div class="card-body p-0">
            {{ $slot }}
            <x-tk-table id="table" data-url="{{ route('emails.historyList') }}"
                data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total"
                data-data-field="rows" data-page-list="[5, 10, 20, 50, 100]" data-search="true" data-show-columns="true"
                data-side-pagination="server" data-pagination="true" data-sort-name="id" data-sort-order="desc"
                data-mobile-responsive="true" data-query-params="queryParamsEmailHistory"
                :columns="[
                    ['checkbox' => true],
                    ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true],
                    ['field' => 'to_email', 'label' => get_label('recipient_email', 'Recipient Email'), 'sortable' => true],
                    ['field' => 'subject', 'label' => get_label('subject', 'Subject')],
                    ['field' => 'status', 'label' => get_label('status', 'Status')],
                    ['field' => 'scheduled_at', 'label' => get_label('scheduled_at', 'Scheduled At')],
                    ['field' => 'user_name', 'label' => get_label('created_by', 'Created By')],
                    ['field' => 'view', 'label' => get_label('view', 'View'), 'formatter' => 'emailHistoryActionsFormatter'],
                    ['field' => 'created_at', 'label' => get_label('created_at', 'Created At'), 'sortable' => true],
                    ['field' => 'updated_at', 'label' => get_label('upadted_at', 'Updated At'), 'sortable' => true],
                    ['field' => 'actions', 'label' => get_label('actions', 'Actions')]
                ]">
                <x-slot:before>
                    <input type="hidden" id="data_type" value="emails/history">
                    <input type="hidden" id="data_reload" value="1">
                </x-slot:before>
            </x-tk-table>
        </div>
    </div>

    <script>
        var label_update = '<?= get_label('update', 'Update') ?>';
        var label_delete = '<?= get_label('delete', 'Delete') ?>';
        var label_duplicate = '<?= get_label('duplicate', 'Duplicate') ?>';
        const previewUrl = "{{ route('emails.preview') }}";
    </script>
@else
    <?php $type = 'Emails'; ?>
    <x-empty-state-card :type="$type" />
@endif
