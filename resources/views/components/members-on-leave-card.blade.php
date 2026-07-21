<!-- projects card -->
<div class="d-flex flex-wrap gap-3 mb-3 tk-filter-bar">
    <div class="flex-grow-1" style="min-width: 250px; max-width: 300px;">
        <select class="form-select tom_users_select" id="mol_user_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_members', 'Select Members') ?>" multiple>
        </select>
    </div>
    <div class="flex-grow-1" style="min-width: 250px; max-width: 300px;">
        <div class="input-group input-group-merge">
            <input type="number" id="upcoming_days_mol" name="upcoming_days" class="form-control" min="0" placeholder="<?= get_label('till_upcoming_days_def_30', 'Till upcoming days : default 30') ?>" autocomplete="off">
        </div>
    </div>
    <div class="d-flex align-items-center ms-auto">
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1" id="upcoming_days_mol_filter" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('clear_filters', 'Clear Filters') ?>">
            <i class='bx bx-x-circle'></i> <?= get_label('clear_filters', 'Clear Filters') ?>
        </button>
    </div>
</div>
<x-tk-table id="mol_table" :url="url('/home/members-on-leave')"
    data-sort-name="leave_requests.from_date" data-sort-order="asc" data-query-params="queryParamsMol"
    :columns="[
        ['checkbox' => true],
        ['field' => 'id', 'label' => get_label('id', 'ID')],
        ['field' => 'member', 'label' => get_label('member', 'Member')],
        ['field' => 'from_date', 'label' => get_label('from', 'From'), 'sortable' => true],
        ['field' => 'to_date', 'label' => get_label('to', 'To'), 'sortable' => true],
        ['field' => 'type', 'label' => get_label('type', 'type')],
        ['field' => 'duration', 'label' => get_label('duration', 'Duration')],
        ['field' => 'days_left', 'label' => get_label('days_left', 'Days left')],
    ]">
    <x-slot:before>
        <input type="hidden" id="data_type" value="users">
        <input type="hidden" id="data_table" value="mol_table">
        <input type="hidden" id="data_reload" value="1">
        <input type="hidden" id="multi_select" value="upcoming-mol">
    </x-slot:before>
    <x-slot:prepend>
        <div class="alert alert-info alert-dismissible" role="alert">{{ get_label('delete_selected_will_delete_selected_team_members_alert', 'Delete selected will delete selected team members.') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    </x-slot:prepend>
</x-tk-table>