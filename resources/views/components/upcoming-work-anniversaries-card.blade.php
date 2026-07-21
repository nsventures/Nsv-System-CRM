<!-- projects card -->
<div class="d-flex flex-wrap gap-3 mb-3 tk-filter-bar">
    <div class="flex-grow-1" style="min-width: 250px; max-width: 300px;">
        <select class="tk-select tom_users_select" id="wa_user_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_members', 'Select Members') ?>" multiple>
        </select>
    </div>
    <div class="flex-grow-1" style="min-width: 250px; max-width: 300px;">
        <select class="tk-select tom_clients_select" id="wa_client_filter" aria-label="Default select example" data-placeholder="<?= get_label('select_clients', 'Select Clients') ?>" multiple>
        </select>
    </div>
    <div class="flex-grow-1" style="min-width: 250px; max-width: 300px;">
        <input type="number" id="upcoming_days_wa" name="upcoming_days" class="tk-input" min="0" placeholder="<?= get_label('till_upcoming_days_def_30', 'Till upcoming days : default 30') ?>" autocomplete="off">
    </div>
    <div class="d-flex align-items-center ms-auto">
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1" id="upcoming_days_wa_filter" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?= get_label('clear_filters', 'Clear Filters') ?>">
            <i class='bx bx-x-circle'></i> <?= get_label('clear_filters', 'Clear Filters') ?>
        </button>
    </div>
</div>
<x-tk-table id="wa_table" :url="url('/home/upcoming-work-anniversaries')"
    data-sort-name="doj" data-sort-order="asc" data-query-params="queryParamsUpcomingWa"
    :columns="[
        ['field' => 'id', 'label' => get_label('id', 'ID')],
        ['field' => 'member', 'label' => get_label('whose', 'Whose')],
        ['field' => 'type', 'label' => get_label('type', 'Type')],
        ['field' => 'wa_date', 'label' => get_label('work_anniversary_date', 'Work anniversary date'), 'sortable' => true],
        ['field' => 'days_left', 'label' => get_label('days_left', 'Days left')],
    ]">
    <x-slot:before>
        <input type="hidden" id="data_type" value="users">
        <input type="hidden" id="data_table" value="wa_table">
        <input type="hidden" id="data_reload" value="1">
        <input type="hidden" id="multi_select" value="upcoming-wa">
    </x-slot:before>
</x-tk-table>