    @extends('layout')
@section('title')
    <?= get_label('leave_requests', 'Leave requests') ?>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('leave_requests', 'Leave requests') ?>
                        </li>
                    </ol>
                </nav>
                @php
                    $meetingsDefaultView = getUserPreferences('leave_requests', 'default_view');
                @endphp
                @if ($meetingsDefaultView === 'list')
                    <span class="badge bg-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);"><span class="badge bg-secondary" id="set-default-view"
                            data-type="leave-requests"
                            data-view="list"><?= get_label('set_as_default_view', 'Set as Default View') ?></span></a>
                @endif
            </div>
            <div class="d-flex align-items-center flex-wrap gap-1">
                @if ($auth_user->hasRole('admin') || is_admin_or_leave_editor())
                    <a href="{{ url('leave-balances') }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" data-bs-original-title="<?= get_label('view_leave_balances', 'View leave balance dashboard') ?>">
                        <i class='bx bx-bar-chart'></i>
                    </a>
                @endif
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                    data-bs-target="#paidLeaveWorkflowModal"
                    title="<?= get_label('view_paid_leave_flow', 'View paid leave flow') ?>">
                    <i class='bx bx-info-circle'></i>
                </button>
                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_leave_request_modal"><button
                        type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="right"
                        data-bs-original-title=" <?= get_label('create_leave_request', 'Create leave request') ?>"><i
                            class="bx bx-plus"></i></button></a>
                <a href="{{ route('leave-requests.calendar') }}"><button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="<?= get_label('calendar_view', 'Calendar view') ?>"><i
                            class='bx bx-calendar'></i></button></a>
            </div>
        </div>
        @php
            $isLeaveEditor = \App\Models\LeaveEditor::where('user_id', $auth_user->id)->exists();
            $leaveBalanceService = new \App\Services\LeaveBalanceService();
            $leaveBalance = $leaveBalanceService->getBalanceSummary($auth_user->id, getWorkspaceId());
            $companyYearText = format_company_year(null, true); // e.g., "Apr 2024 - Mar 2025"
        @endphp

        @php
            $formatLeaveNumber = static function ($value) {
                return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
            };
        @endphp

        <!-- Leave Balance Widget Overrides -->
        <style>
            .leave-balance-card .tk-metric-strip {
                grid-template-columns: repeat(5, 1fr) !important;
            }
            @media (max-width: 1100px) {
                .leave-balance-card .tk-metric-strip {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
            }
            @media (max-width: 560px) {
                .leave-balance-card .tk-metric-strip {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>

        <!-- Leave Balance Widget -->
        <div class="tk-card mb-4 leave-balance-card">
            <div class="tk-card-head">
                <div class="tk-card-head-main">
                    <div class="tk-card-eyebrow">{{ $companyYearText }}</div>
                    <h3 class="tk-card-title"><i class='bx bx-wallet me-2'></i><?= get_label('my_leave_balance', 'My Leave Balance') ?></h3>
                    <p class="tk-card-title-sub mt-1">{{ get_label('leave_balance_subheadline', 'Track your annual paid leave at a glance.') }}</p>
                </div>
            </div>
            <div class="tk-card-body">
                <div class="tk-metric-strip">
                    <div class="tk-metric">
                        <div class="tk-metric-row"><span class="tk-metric-label"><?= get_label('total_annual_leaves', 'Total Annual Leaves') ?></span></div>
                        <div class="tk-metric-value count">{{ $formatLeaveNumber($leaveBalance['total_annual_leaves']) }}</div>
                        @if(isset($leaveBalance['accrued_leaves']))
                            <div class="tk-metric-row mt-1"><span class="tk-metric-label text-info"><i class="bx bx-info-circle"></i> <?= get_label('accrued', 'Accrued') ?>: {{ $formatLeaveNumber($leaveBalance['accrued_leaves']) }}</span></div>
                        @endif
                    </div>

                    <div class="tk-metric">
                        <div class="tk-metric-row"><span class="tk-metric-label"><?= get_label('used_paid_leaves', 'Used Paid Leaves') ?></span></div>
                        <div class="tk-metric-value count d-flex align-items-baseline gap-1">
                            <span>{{ $formatLeaveNumber($leaveBalance['used_paid_leaves']) }}</span>
                            <small class="text-muted fs-5 fw-normal">/ {{ $formatLeaveNumber($leaveBalance['total_annual_leaves']) }}</small>
                        </div>
                        @if(isset($leaveBalance['advanced_paid_leaves']) && $leaveBalance['advanced_paid_leaves'] > 0)
                            <div class="tk-metric-row mt-1"><span class="tk-metric-label text-info"><i class="bx bx-info-circle"></i> <?= get_label('includes_advance', 'Includes') ?> {{ $formatLeaveNumber($leaveBalance['advanced_paid_leaves']) }} <?= get_label('advanced_paid_leaves', 'Advanced Paid Leaves') ?></span></div>
                        @endif
                    </div>

                    <div class="tk-metric">
                        @php
                            $remainingPaidLeaves = $leaveBalance['remaining_paid_leaves'] ?? 0;
                            $totalAnnualLeaves = $leaveBalance['total_annual_leaves'] ?? 0;
                            $accruedLeaves = $leaveBalance['accrued_leaves'] ?? null;
                            $advancedPaidLeaves = $leaveBalance['advanced_paid_leaves'] ?? 0;
                            $displayRemaining = $leaveBalance['display_remaining_paid_leaves'] ?? ($remainingPaidLeaves - $advancedPaidLeaves);
                            $annualRemaining = max($totalAnnualLeaves - ($leaveBalance['used_paid_leaves'] ?? 0), 0);
                        @endphp
                        <x-leave.remaining-leaves-pill
                            :remaining="$displayRemaining"
                            :total="$totalAnnualLeaves"
                            :accrued="$accruedLeaves"
                            :advanced_paid_leaves="$advancedPaidLeaves"
                            :annual="$totalAnnualLeaves"
                            :annual-remaining="$annualRemaining"
                            heading="{{ get_label('remaining_paid_leaves', 'Remaining Paid Leaves') }}"
                        />
                    </div>

                    <div class="tk-metric">
                        <div class="tk-metric-row"><span class="tk-metric-label"><?= get_label('unpaid_leaves_taken', 'Unpaid Leaves Taken') ?></span></div>
                        <div class="tk-metric-value count">{{ $formatLeaveNumber($leaveBalance['unpaid_leaves_taken']) }}</div>
                    </div>

                    <div class="tk-metric">
                        @php
                            $totalLeavesTaken = ($leaveBalance['used_paid_leaves'] ?? 0) + ($leaveBalance['unpaid_leaves_taken'] ?? 0);
                        @endphp
                        <div class="tk-metric-row"><span class="tk-metric-label"><?= get_label('total_leaves_taken', 'Total Leaves Taken') ?></span></div>
                        <div class="tk-metric-value count">{{ $formatLeaveNumber($totalLeavesTaken) }}</div>
                        <div class="tk-metric-row mt-1"><span class="tk-metric-label text-muted"><?= get_label('paid', 'Paid') ?>: {{ $formatLeaveNumber($leaveBalance['used_paid_leaves'] ?? 0) }} · <?= get_label('unpaid', 'Unpaid') ?>: {{ $formatLeaveNumber($leaveBalance['unpaid_leaves_taken'] ?? 0) }}</span></div>
                    </div>
                        </div>

                        @if(isset($leaveBalance['monthly_accrual_rate']))
                            <div class="alert alert-info leave-accrual-banner d-flex align-items-start gap-3 mt-4" role="alert">
                                <div class="leave-accrual-icon text-info">
                                    <i class='bx bx-info-circle'></i>
                                </div>
                                <div>
                                    <h6 class="alert-title mb-1"><?= get_label('monthly_accrual_info', 'Monthly Accrual System') ?></h6>
                                    <p class="mb-0 small text-info">
                                        <?= get_label('you_earn', 'You earn') ?> <strong>{{ $leaveBalance['monthly_accrual_rate'] }}</strong> <?= get_label('days_per_month', 'days per month') ?>.
                                        <?= get_label('worked_months', 'Worked') ?>: <strong>{{ $leaveBalance['months_worked'] }} <?= get_label('months', 'months') ?></strong>.
                                        <?= get_label('accrued_so_far', 'Accrued so far') ?>: <strong>{{ $formatLeaveNumber($leaveBalance['accrued_leaves']) }} <?= get_label('days', 'days') ?></strong>.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small">{{ get_label('leave_utilization', 'Leave utilization') }}</span>
                                <span class="text-muted small fw-semibold">{{ number_format($leaveBalance['utilization_percentage'], 1) }}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" role="progressbar"
                                    style="width: {{ number_format($leaveBalance['utilization_percentage'], 1) }}%;"
                                    aria-valuenow="{{ $leaveBalance['utilization_percentage'] }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"></div>
                            </div>
                        </div>
            </div>
        </div>

        @if ($auth_user->hasRole('admin'))
            <div class="tk-card mb-4" style="overflow: visible; position: relative; z-index: 10;">
                <div class="tk-card-head">
                    <div class="tk-card-head-main">
                        <h3 class="tk-card-title"><i class='bx bx-user-check me-2'></i><?= get_label('select_leave_editors', 'Select Leave Editors') ?></h3>
                        <p class="tk-card-title-sub mt-1">{{ get_label('leave_editor_access_info', 'Like Admin, Selected Users Will Be Able to Update and Create Leaves for Other Members.') }}</p>
                    </div>
                </div>
                <div class="tk-card-body">
                    <form action="{{ url('leave-requests/update-editors') }}" class="form-submit-event" method="POST">
                        <input type="hidden" name="redirect_url" value="{{ url('leave-requests') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <select id="leave_editors_select" class="form-select tom_users_select" name="user_ids[]" multiple="multiple"
                                    data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>"
                                    data-ignore-admins="true" data-allow-clear="true">
                                    @foreach ($leaveEditors as $leaveEditor)
                                        <option value="{{ $leaveEditor->id }}" selected>{{ $leaveEditor->first_name }} {{ $leaveEditor->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" id="submit_btn" class="btn btn-primary w-100"><?= get_label('update', 'Update') ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
        @if ($isLeaveEditor)
            <div class="d-flex justify-content-center mb-3">
                <span class="badge bg-primary"><?= get_label('leave_editor_info', 'You are leave editor') ?></span>
            </div>
        @endif
        @if ($leave_requests > 0)
            @php
                $visibleColumns = getUserPreferences('leave_requests');
            @endphp
            <div class="card mb-4 border shadow-none">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-end tk-filter-row">
                        <x-advanced-date-filters prefix="lr" />
                        @if (is_admin_or_leave_editor())
                            <div class="col-md-4">
                                <label class="form-label" for="lr_user_filter"><?= get_label('select_users', 'Select Users') ?></label>
                                <select class="form-select tom_users_select" id="lr_user_filter"
                                    data-placeholder="<?= get_label('select_users', 'Select Users') ?>" multiple>
                                </select>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label" for="lr_action_by_filter"><?= get_label('select_actions_by', 'Select Actions By') ?></label>
                            <select class="form-select tom_users_select" id="lr_action_by_filter"
                                data-placeholder="<?= get_label('select_actions_by', 'Select Actions By') ?>" multiple>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="lr_status_filter"><?= get_label('select_statuses', 'Select statuses') ?></label>
                            <select class="form-select tom_static_select" id="lr_status_filter"
                                data-placeholder="<?= get_label('select_statuses', 'Select statuses') ?>"
                                data-allow-clear="true" multiple>
                                <option value="pending"><?= get_label('pending', 'Pending') ?></option>
                                <option value="approved"><?= get_label('approved', 'Approved') ?></option>
                                <option value="rejected"><?= get_label('rejected', 'Rejected') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="lr_type_filter"><?= get_label('select_types', 'Select Types') ?></label>
                            <select class="form-select tom_static_select" id="lr_type_filter"
                                data-placeholder="<?= get_label('select_types', 'Select Types') ?>"
                                data-allow-clear="true" multiple>
                                <option value="full"><?= get_label('full', 'Full') ?></option>
                                <option value="partial"><?= get_label('partial', 'Partial') ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card border shadow-none">
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <input type="hidden" id="data_type" value="leave-requests">
                        <input type="hidden" id="data_table" value="lr_table">
                        <input type="hidden" id="save_column_visibility">
                        <input type="hidden" id="multi_select">
                        <table id="lr_table" data-toggle="table" data-loading-template="loadingTemplate"
                            data-url="{{ url('/leave-requests/list') }}" data-icons-prefix="bx" data-icons="icons"
                            data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                            data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                            data-query-params="queryParamsLr">
                            <thead>
                                <tr>
                                    <th data-checkbox="true"></th>
                                    <th data-field="id"
                                        data-visible="{{ in_array('id', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true"><?= get_label('id', 'ID') ?></th>
                                    <th data-field="user_name"
                                        data-visible="{{ in_array('user_name', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="false"><?= get_label('member', 'Member') ?></th>
                                    <th data-field="from_date"
                                        data-visible="{{ in_array('from_date', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true"><?= get_label('from', 'From') ?></th>
                                    <th data-field="to_date"
                                        data-visible="{{ in_array('to_date', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true"><?= get_label('to', 'To') ?></th>
                                    <th data-field="type"
                                        data-visible="{{ in_array('type', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">
                                        <?= get_label('type', 'Type') ?>
                                    </th>
                                    <th data-field="duration"
                                        data-visible="{{ in_array('duration', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="false"><?= get_label('duration', 'Duration') ?></th>
                                    <th data-field="reason"
                                        data-visible="{{ in_array('reason', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true"><?= get_label('reason', 'Reason') ?></th>
                                    <th data-field="visible_to"
                                        data-visible="{{ in_array('visible_to', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">
                                        <?= get_label('visible_to', 'Visible To') ?><i
                                            class='bx bx-info-circle text-primary'
                                            title="{{ get_label('leave_visible_to_info_1', 'Including the requestee, admin, and leave editors, users who will be able to know when the requestee is on leave (not applicable if visible to all).') }}"></i>
                                    </th>
                                    <th data-field="status"
                                        data-visible="{{ in_array('status', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true"><?= get_label('status', 'Status') ?></th>
                                    <th data-field="action_by"
                                        data-visible="{{ in_array('action_by', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true"><?= get_label('action_by', 'Action by') ?></th>
                                    @if (is_admin_or_leave_editor())
                                        <th data-field="comment"
                                            data-visible="{{ in_array('comment', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">
                                            <?= get_label('comment', 'Comment') ?>
                                        </th>
                                    @endif
                                    <th data-field="created_at"
                                        data-visible="{{ in_array('created_at', $visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true"><?= get_label('created_at', 'Created at') ?></th>
                                    <th data-field="updated_at"
                                        data-visible="{{ in_array('updated_at', $visibleColumns) ? 'true' : 'false' }}"
                                        data-sortable="true"><?= get_label('updated_at', 'Updated at') ?></th>
                                    <th data-field="actions"
                                        data-visible="{{ in_array('actions', $visibleColumns) || empty($visibleColumns) ? 'true' : 'false' }}">
                                        <?= get_label('actions', 'Actions') ?>
                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <?php
            $type = 'Leave requests'; ?>
            <x-empty-state-card :type="$type" />
        @endif
    </div>
@endsection

@section('page_scripts')
    <script>
        var label_update = '<?= get_label('update', 'Update') ?>';
        var label_delete = '<?= get_label('delete', 'Delete') ?>';
        var isAdminOrLe = '<?= is_admin_or_leave_editor() ?>';
        var authUserId = {{ $auth_user->id }}; // Logged-in user ID for balance fetching
    </script>
    <script src="{{ asset('assets/js/pages/leave-requests.js') }}"></script>
@endsection
