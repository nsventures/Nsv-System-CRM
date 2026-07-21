@extends('layout')

@section('title', get_label('manual_time_tracker','Manual Time Tracker'))

@section('content')
    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between mb-2 mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <?= get_label('team_monitoring_and_productivity_tracker', 'Team Monitoring and Productivity Tracker') ?>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('manual_time_tracker','Manual Time Tracker') ?>
                        </li>
                    </ol>
                </nav>
            </div>

        </div>
        <div class="card border-0 shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white">
                <h5 class="fw-semibold mb-0">{{ get_label('manual_time_entries','Manual Time Entries') }}</h5>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-sm btn-primary" id="addManualTimeBtn">
                        <i class="bx bx-plus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="refreshTable">
                        <i class="bx bx-refresh"></i>
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">{{ get_label('date_range','Date Range') }}</label>
                        <input type="text" id="dateRange" class="form-control" placeholder="{{ get_label('select_date_range','Select Date Range') }}" autocomplete="off">
                    </div>

                    @if (isAdminOrHasAllDataAccess() || $canApprove)
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{get_label('users','Users')}}</label>
                            <select name="user_id[]" id="userFilter" class="form-select select2-employee-filter" multiple>
                                <option value="">{{ get_label('select_users','Select Users') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif


                </div>

                <div class="table-responsive">
                    <table id="manualTimeTable" data-toggle="table" data-url="{{ route('timetracker.manual_time.data') }}"
                        data-pagination="true" data-side-pagination="server" data-page-list="[10, 20, 50, 100]"
                        data-search="true" data-show-refresh="true" data-show-columns="true" data-sort-name="timestamp"
                        data-sort-order="desc" data-icons-prefix="bx" data-icons="icons" data-mobile-responsive="true"
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="employee_name" data-sortable="true">{{get_label('user','User')}}</th>
                                <th data-field="date" data-sortable="true">{{ get_label('date','Date') }}</th>
                                <th data-field="start_time" data-sortable="true">{{ get_label('start_time','Start Time') }}</th>
                                <th data-field="end_time" data-sortable="true">{{ get_label('end_time','End Time') }}</th>
                                <th data-field="duration" data-sortable="true">{{ get_label('duration','Duration') }}</th>
                                <th data-field="reason" data-sortable="true">{{ get_label('reason','Reason') }}</th>
                                <th data-field="status" data-sortable="true" data-formatter="statusFormatter">{{ get_label('status','Status') }}</th>
                                <th data-field="approved_at" data-sortable="true">{{ get_label('approved_at','Approved At') }}</th>
                                <th data-field="approver" data-sortable="true">{{ get_label('approver','Approver') }}</th>
                                <th data-field="actions" data-formatter="actionsFormatter" data-events="actionEvents">{{ get_label('actions','Actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Manual Time Modal -->
    <div class="modal fade" id="addManualTimeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="addManualTimeForm" novalidate>
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ get_label('add_manual_time','Add Manual Time') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        @if (isAdminOrHasAllDataAccess() || $canApprove)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">{{get_label('user','User')}}</label>
                                <select name="user_id" class="form-select select2-employee" required>
                                    <option value="">{{ get_label('select_user','Select User') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">{{get_label('please_select_an_user','Please select an user')}}</div>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ get_label('date','Date') }}</label>
                            <input type="date" name="date" class="form-control" max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ get_label('start_time','Start Time') }}</label>
                            <input type="time" name="start_time" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ get_label('end_time','End Time') }}</label>
                            <input type="time" name="end_time" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ get_label('reason','Reason') }}</label>
                            <textarea name="reason" class="form-control" placeholder="{{ get_label('enter_reason_for_manual_time','Enter reason for manual time') }}" required></textarea>
                            <div class="invalid-feedback">{{ get_label('please_provide_a_reason','Please provide a reason.') }}</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bx bx-save"></i> {{ get_label('save_manual_time','Save Manual Time') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Approval Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1 Thoracic Event" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="approveManualTimeForm" novalidate>
                @csrf
                <input type="hidden" name="manual_time_id" id="manual_time_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">{{ get_label('approve_or_reject_manual_time_entry','Approve / Reject Manual Time Entry') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label fw-semibold">{{get_label('user','User')}}</label>
                            <input type="text" name="employee_name" id="employee_name" class="form-control" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">{{ get_label('date','Date') }}</label>
                            <input type="text" name="date" id="date" class="form-control" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">{{ get_label('start_time','Start Time') }}</label>
                            <input type="text" name="start_time" id="start_time" class="form-control" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">{{ get_label('end_time','End Time') }}</label>
                            <input type="text" name="end_time" id="end_time" class="form-control" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">{{ get_label('reason','Reason') }}</label>
                            <textarea name="reason" id="reason" class="form-control" rows="2" readonly></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">{{ get_label('action','Action') }}</label>
                            <select name="status" id="approval_status" class="form-select" required>
                                <option value="">{{ get_label('select_action','Select Action') }}</option>
                                <option value="approved">{{ get_label('approve','Approve') }}</option>
                                <option value="rejected">{{ get_label('reject','Reject') }}</option>
                            </select>
                            <div class="invalid-feedback">{{ get_label('please_select_an_action','Please select an action.') }}</div>
                        </div>
                        <div class="d-none mb-2" id="remarksDiv">
                            <label class="form-label fw-semibold">{{ get_label('remarks','Remarks (if rejecting)') }}</label>
                            <textarea name="remarks" class="form-control" placeholder="{{ get_label('add_remarks_if_rejecting','Add remarks if rejecting') }}"></textarea>
                            <div class="invalid-feedback">{{ get_label('remarks_are_required_when_rejecting','Remarks are required when rejecting') }}.</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ get_label('close','Close') }}</button>
                        <button type="submit" class="btn btn-primary" id="submit_btn">
                            <i class="bx bx-check-double"></i> <span id="approvalBtnText">{{ get_label('approve','Approve') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/daterangepicker.js') }}"></script>
    <script>
        var canApprove = @json($canApprove);
        var isAdminOrHasAllDataAccess = @json(isAdminOrHasAllDataAccess());
        var canApprove = @json($canApprove);
        var manualTimeFetch = @json(route('timetracker.manual_time.fetch'));
        var manualTimeStore = @json(route('timetracker.manual_time.store'));
        var manualTimeApprove = @json(route('timetracker.manual_time.approve'));
        var label_select_user = @json(get_label('select_user','Select User'));
        var label_select_users = @json(get_label('select_users','Select Users'));
        var label_please_select_an_action = @json(get_label('please_select_an_action','Please select an action.'));
        var label_remarks_are_required_when_rejecting  = @json(get_label('remarks_are_required_when_rejecting','Remarks are required when rejecting'));
        var label_manual_time_added_successfully = @json(get_label('manual_time_added_successfully','Manual time added successfully.'));
        var label_please_select_an_user = @json(get_label('please_select_an_user','Please select an user'));
        var label_please_provide_a_reason = @json(get_label('please_provide_a_reason','Please provide a reason.'));
        var label_end_time_must_be_after_start_time = @json(get_label('end_time_must_be_after_start_time','End time must be after start time.'));
        var label_end_time_cannot_be_in_the_future = @json(get_label('end_time_cannot_be_in_the_future','End time cannot be in the future.'));
        var label_start_time_cannot_be_in_the_future = @json(get_label('start_time_cannot_be_in_the_future','Start time cannot be in the future.'));
        var label_future_dates_are_not_allowed = @json(get_label('future_dates_are_not_allowed','Future dates are not allowed.'))
    </script>
    <script src="{{ asset('assets/js/timetracker-plugin/manual_time.js') }}"></script>
@endsection
