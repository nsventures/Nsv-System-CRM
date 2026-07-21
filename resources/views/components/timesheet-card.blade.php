@if (is_countable($timesheet) && count($timesheet) > 0)
@php
$visibleColumns = getUserPreferences('time_tracker');
@endphp
<div class="col-12">
    <div class="card mb-3 border shadow-none">
        <div class="card-body p-3">
            {{$slot}}
            <div class="row g-3 align-items-end tk-filter-row">
                <x-advanced-date-filters prefix="timesheet" />
                @if(isAdminOrHasAllDataAccess())
                <div class="col-md-3">
                    <label class="form-label" for="timesheet_user_filter"><?= get_label('users', 'Users') ?></label>
                    <select class="form-select tom_users_select" id="timesheet_user_filter" data-placeholder="<?= get_label('select_users', 'Select Users') ?>" multiple>
                    </select>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="card border shadow-none">
        <div class="card-body p-0">
            <div class="table-responsive text-nowrap">
                <input type="hidden" id="data_type" value="time-tracker">
                <input type="hidden" id="data_table" value="timesheet_table">
                <input type="hidden" id="save_column_visibility">
                <input type="hidden" id="multi_select">
                <table id="timesheet_table" data-toggle="table" data-loading-template="loadingTemplate" data-url="{{ url('/time-tracker/list') }}" data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total" data-trim-on-search="false" data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-side-pagination="server" data-show-columns="true" data-pagination="true" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-query-params="time_tracker_query_params">
                    <thead>
                        <tr>
                            <th data-checkbox="true"></th>
                            <th data-field="id" data-visible="{{ (in_array('id', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('id', 'ID') ?></th>
                            <th data-field="user" data-visible="{{ (in_array('user', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}"><?= get_label('user', 'User') ?></th>
                            <th data-field="start_date_time" data-visible="{{ (in_array('start_date_time', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('started_at', 'Started at') ?></th>
                            <th data-field="end_date_time" data-visible="{{ (in_array('end_date_time', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('ended_at', 'Ended at') ?></th>
                            <th data-field="duration" data-visible="{{ (in_array('duration', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="false"><?= get_label('duration', 'Duration') ?></th>
                            <th data-field="message" data-visible="{{ (in_array('message', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('message', 'Message') ?></th>
                            <th data-field="created_at" data-visible="{{ (in_array('created_at', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('created_at', 'Created at') ?></th>
                            <th data-field="updated_at" data-visible="{{ (in_array('updated_at', $visibleColumns)) ? 'true' : 'false' }}" data-sortable="true"><?= get_label('updated_at', 'Updated at') ?></th>
                            @if (getAuthenticatedUser()->can('delete_timesheet'))
                            <th data-field="actions" data-visible="{{ (in_array('actions', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="timeSheetActionsFormatter"><?= get_label('actions', 'Actions') ?></th>
                            @endif
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@else
<?php
$type = 'Timesheet'; ?>
<x-empty-state-card :type="$type" />
@endif
