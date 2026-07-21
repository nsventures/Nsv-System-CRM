@if (is_countable($interviews) && count($interviews) > 0)
    <div class="card mb-4">
        <div class="card-body">
            {{$slot}}
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?= get_label('date_between', 'Date Between') ?></label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                        <input type="text" class="form-control" id="interview_date_between" placeholder="<?= get_label('date_between', 'Date Between') ?>" autocomplete="off">
                    </div>
                    <input type="hidden" id="interview_date_between_from" name="start_date" />
                    <input type="hidden" id="interview_date_between_to" name="end_date" />
                </div>
                
                <div class="col-md-3">
                    <label class="form-label"><?= get_label('sort_by', 'Sort By') ?></label>
                    <select class="form-select tom_static_select" id="sort" name="sort" aria-label="Default select example" data-placeholder="<?= get_label('select_sort_by', 'Select Sort By') ?>" data-allow-clear="true">
                        <option></option>
                        <option value="newest" <?= request()->sort && request()->sort == 'newest' ? 'selected' : '' ?>><?= get_label('newest', 'Newest') ?></option>
                        <option value="oldest" <?= request()->sort && request()->sort == 'oldest' ? 'selected' : '' ?>><?= get_label('oldest', 'Oldest') ?></option>
                        <option value="recently-updated" <?= request()->sort && request()->sort == 'recently-updated' ? 'selected' : '' ?>><?= get_label('most_recently_updated', 'Most recently updated') ?></option>
                        <option value="earliest-updated" <?= request()->sort && request()->sort == 'earliest-updated' ? 'selected' : '' ?>><?= get_label('least_recently_updated', 'Least recently updated') ?></option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= get_label('status', 'Status') ?></label>
                    <select class="form-select tom_static_select" id="interview_status" name="status" aria-label="Default select example" data-placeholder="{{ get_label('filter_by_statuses', 'Filter by statuses') }}" data-allow-clear="true" multiple>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-secondary clear-interview-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
                        <i class="bx bx-refresh"></i> {{ get_label('clear_filters', 'Clear Filters') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
    $columns = [
        ['checkbox' => true],
        ['field' => 'id', 'label' => get_label('id', 'ID'), 'sortable' => true],
        ['field' => 'candidate', 'label' => get_label('candidate', 'Candidate'), 'sortable' => true],
        ['field' => 'interviewer', 'label' => get_label('interviewer', 'Interviewer')],
        ['field' => 'round', 'label' => get_label('round', 'Round')],
        ['field' => 'scheduled_at', 'label' => get_label('scheduled_at', 'Scheduled At'), 'sortable' => true],
        ['field' => 'status', 'label' => get_label('status', 'Status'), 'sortable' => true],
        ['field' => 'location', 'label' => get_label('location', 'Location'), 'sortable' => true],
        ['field' => 'mode', 'label' => get_label('mode', 'Mode'), 'sortable' => true],
        ['field' => 'created_at', 'label' => get_label('created_at', 'Created at'), 'sortable' => true],
        ['field' => 'updated_at', 'label' => get_label('updated_at', 'Updated at'), 'sortable' => true],
        ['field' => 'actions', 'label' => get_label('actions', 'Actions')],
    ];
    @endphp

    <div class="card border shadow-none">
        <div class="card-body p-0">
            <x-tk-table 
                id="interviews_table" 
                :url="route('interviews.list')" 
                :columns="$columns" 
                data-sort-name="id" 
                data-sort-order="desc" 
                data-query-params="queryParams"
            >
                <x-slot:before>
                    <input type="hidden" id="data_type" value="interviews">
                    <input type="hidden" id="data_table" value="interviews_table">
                    <input type="hidden" id="save_column_visibility">
                </x-slot:before>
            </x-tk-table>
        </div>
    </div>
@else
    <?php $type = 'Interview'; ?>
    <x-empty-state-card :type="$type" />
@endif
