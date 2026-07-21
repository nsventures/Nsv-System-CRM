@extends('layout')
@section('title')
{{ get_label('leaves_report', 'Leaves Report') }}
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mt-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home.index') }}">{{ get_label('home', 'Home') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        {{ get_label('reports', 'Reports') }}
                    </li>
                    <li class="breadcrumb-item active">
                        {{ get_label('leaves', 'Leaves') }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Summary Tiles -->
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;" class="mb-4 tk-stats-grid">
        <!-- Total -->
        <div class="tk-fact">
            <i class="bx bx-calendar" style="font-size:22px; color:var(--signal);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('total', 'Total') }}</span>
                <span class="tk-fact-v" id="total-leaves">—</span>
            </div>
        </div>
        <!-- Full -->
        <div class="tk-fact">
            <i class="bx bx-check-circle" style="font-size:22px; color:var(--err);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('full', 'Full') }}</span>
                <span class="tk-fact-v" id="full-leaves">—</span>
            </div>
        </div>
        <!-- Partial -->
        <div class="tk-fact">
            <i class="bx bx-minus-circle" style="font-size:22px; color:var(--warn);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('partial', 'Partial') }}</span>
                <span class="tk-fact-v" id="partial-leaves">—</span>
            </div>
        </div>
        <!-- Approved -->
        <div class="tk-fact">
            <i class="bx bx-check-circle" style="font-size:22px; color:var(--ok);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('approved', 'Approved') }}</span>
                <span class="tk-fact-v" id="approved-leaves">—</span>
            </div>
        </div>
        <!-- Pending -->
        <div class="tk-fact">
            <i class="bx bx-time" style="font-size:22px; color:var(--warn);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('pending', 'Pending') }}</span>
                <span class="tk-fact-v" id="pending-leaves">—</span>
            </div>
        </div>
        <!-- Rejected -->
        <div class="tk-fact">
            <i class="bx bx-x-circle" style="font-size:22px; color:var(--err);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('rejected', 'Rejected') }}</span>
                <span class="tk-fact-v" id="rejected-leaves">—</span>
            </div>
        </div>
        <!-- Paid -->
        <div class="tk-fact">
            <i class="bx bx-check-double" style="font-size:22px; color:var(--ok);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('paid_leaves', 'Paid Leaves') }}</span>
                <span class="tk-fact-v" id="paid-leaves">—</span>
            </div>
        </div>
        <!-- Unpaid -->
        <div class="tk-fact">
            <i class="bx bx-x" style="font-size:22px; color:var(--fg-3);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('unpaid_leaves', 'Unpaid Leaves') }}</span>
                <span class="tk-fact-v" id="unpaid-leaves">—</span>
            </div>
        </div>
        <!-- Avg. Utilization -->
        <div class="tk-fact">
            <i class="bx bx-pie-chart-alt" style="font-size:22px; color:var(--info);"></i>
            <div class="tk-fact-txt">
                <span class="tk-fact-k">{{ get_label('avg_utilization', 'Avg. Utilization') }}</span>
                <span class="tk-fact-v" id="avg-utilization">—</span>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <h5 class="card-title mb-1">{{ get_label('leaves_report', 'Leaves Report') }}</h5>
                    <p class="text-muted mb-0 small">{{ get_label('filter_leaves_report', 'Filter leaves report by date, user and status') }}</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-info" id="view_charts_button" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('view_charts', 'View Charts') }}">
                    <i class="bx bx-bar-chart"></i> {{ get_label('view_charts', 'View Charts') }}
                </button>
                <button class="btn btn-primary" id="export_button" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('export_leaves_report', 'Export Leaves Report') }}">
                    <i class="bx bx-export"></i> {{ get_label('export', 'Export') }}
                </button>
                <button class="btn btn-secondary clear-report-filters" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{ get_label('clear_filters', 'Clear Filters') }}">
                    <i class="bx bx-refresh"></i> {{ get_label('clear_filters', 'Clear Filters') }}
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <x-advanced-date-filters prefix="report" />
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('users', 'Users') }}</label>
                    <select class="form-select tom_users_select" id="user_filter" aria-label="{{ get_label('select_users', 'Select Users') }}" multiple data-placeholder="<?= get_label('select_users', 'Select Users') ?>">
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ get_label('status', 'Status') }}</label>
                    <select class="form-select tom_statuses_filter" id="status_filter" aria-label="{{ get_label('select_statuses', 'Select Statuses') }}" multiple data-placeholder="<?= get_label('select_statuses', 'Select Statuses') ?>">
                        <option value="approved">{{ get_label('approved', 'Approved') }}</option>
                        <option value="pending">{{ get_label('pending', 'Pending') }}</option>
                        <option value="rejected">{{ get_label('rejected', 'Rejected') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    @php
    $visibleColumns = getUserPreferences('leaves_report');
    @endphp
    <div class="card border shadow-sm">
        <div class="card-body p-0">
            <!-- Table -->
            <div class="table-responsive text-nowrap">
                <input type="hidden" id="multi_select">
                <input type="hidden" id="data_type" value="report">
                <input type="hidden" id="save_column_visibility" data-type="leaves_report" data-table="leaves_report_table">
                <table id="leaves_report_table" data-toggle="table"
                    data-url="{{ route('reports.leaves-report-data') }}" data-loading-template="loadingTemplate"
                    data-icons-prefix="bx" data-icons="icons" data-show-refresh="true" data-total-field="total"
                    data-trim-on-search="false" data-data-field="users" data-page-list="[5, 10, 20, 50, 100, 200]"
                    data-search="true" data-side-pagination="server" data-show-columns="true" data-pagination="true"
                    data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                    data-query-params="leaves_report_query_params">
                    <thead>
                        <tr>
                            <th data-field="id" data-visible="{{ (in_array('id', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('id', 'ID') }}</th>
                            <th data-field="user_name" data-visible="{{ (in_array('user_name', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('user', 'User') }}</th>
                            <th data-field="total_leaves" data-visible="{{ (in_array('total_leaves', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatTotalLeaves" data-sortable="true">{{ get_label('total', 'Total') }}</th>
                            <th data-field="full_leaves" data-visible="{{ (in_array('full_leaves', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-sortable="true">{{ get_label('full', 'Full') }}</th>
                            <th data-field="partial_leaves" data-visible="{{ (in_array('partial_leaves', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatPartialLeaves" data-sortable="true">{{ get_label('partial', 'Partial') }}</th>
                            <th data-field="approved_leaves" data-visible="{{ (in_array('approved_leaves', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatApprovedLeaves" data-sortable="true">{{ get_label('approved', 'Approved') }}</th>
                            <th data-field="pending_leaves" data-visible="{{ (in_array('pending_leaves', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatPendingLeaves" data-sortable="true">{{ get_label('pending', 'Pending') }}</th>
                            <th data-field="rejected_leaves" data-visible="{{ (in_array('rejected_leaves', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatRejectedLeaves" data-sortable="true">{{ get_label('rejected', 'Rejected') }}</th>
                            <th data-field="paid_leaves" data-visible="{{ (in_array('paid_leaves', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatPaidLeaves" data-sortable="true">{{ get_label('paid', 'Paid') }}</th>
                            <th data-field="unpaid_leaves" data-visible="{{ (in_array('unpaid_leaves', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatUnpaidLeaves" data-sortable="true">{{ get_label('unpaid', 'Unpaid') }}</th>
                            <th data-field="balance_total" data-visible="{{ (in_array('balance_total', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatBalanceTotal" data-sortable="true">{{ get_label('annual_balance', 'Annual Balance') }}</th>
                            <th data-field="balance_used" data-visible="{{ (in_array('balance_used', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatBalanceUsed" data-sortable="true">{{ get_label('used', 'Used') }}</th>
                            <th data-field="balance_remaining" data-visible="{{ (in_array('balance_remaining', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatBalanceRemaining" data-sortable="true">{{ get_label('remaining', 'Remaining') }}</th>
                            <th data-field="utilization" data-visible="{{ (in_array('utilization', $visibleColumns) || empty($visibleColumns)) ? 'true' : 'false' }}" data-formatter="formatUtilization" data-sortable="true">{{ get_label('utilization', 'Utilization %') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Charts Modal -->
<div class="modal fade" id="chartsModal" tabindex="-1" aria-labelledby="chartsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chartsModalLabel">
                    <i class="bx bx-chart"></i> {{ get_label('leave_analytics_charts', 'Leave Analytics Dashboard') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Summary Statistics (bordered boxes, not cards) -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-check-double fs-2 text-success me-3"></i>
                                <div>
                                    <h6 class="mb-1">{{ get_label('total_paid_leaves', 'Total Paid Leaves') }}</h6>
                                    <p class="mb-0"><strong id="chart-total-paid">0</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-x fs-2 text-warning me-3"></i>
                                <div>
                                    <h6 class="mb-1">{{ get_label('total_unpaid_leaves', 'Total Unpaid Leaves') }}</h6>
                                    <p class="mb-0"><strong id="chart-total-unpaid">0</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-pie-chart-alt fs-2 text-info me-3"></i>
                                <div>
                                    <h6 class="mb-1">{{ get_label('avg_utilization', 'Avg. Utilization') }}</h6>
                                    <p class="mb-0"><strong id="chart-avg-utilization">0%</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-user-check fs-2 text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-1">{{ get_label('total_users', 'Total Users') }}</h6>
                                    <p class="mb-0"><strong id="chart-total-users">0</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paid vs Unpaid Chart Section (bordered section) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="border rounded-3">
                            <div class="border-bottom pb-3 p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><i class="bx bx-pie-chart text-primary"></i> {{ get_label('leave_distribution', 'Leave Distribution') }}</h5>
                                        <p class="text-muted mb-0 small">{{ get_label('paid_vs_unpaid_overview', 'Paid vs Unpaid Leaves Overview') }}</p>
                                    </div>
                                    <span class="badge bg-label-success" id="chart-paid-percentage">0%</span>
                                </div>
                            </div>
                            <div class="p-3">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="chart-container-pie w-100">
                                            <div id="paidVsUnpaidChart"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="d-flex flex-column gap-3">
                                            <div class="border rounded p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-success">
                                                        <i class="bx bx-check-double"></i> Paid Leaves
                                                    </span>
                                                    <strong class="text-success" id="chart-insight-paid">0 days</strong>
                                                </div>
                                                <small class="text-muted">From total approved leaves</small>
                                            </div>
                                            <div class="border rounded p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-warning">
                                                        <i class="bx bx-x"></i> Unpaid Leaves
                                                    </span>
                                                    <strong class="text-warning" id="chart-insight-unpaid">0 days</strong>
                                                </div>
                                                <small class="text-muted">From total approved leaves</small>
                                            </div>
                                            <div class="alert alert-info mb-0">
                                                <i class="bx bx-info-circle"></i>
                                                <strong>{{ get_label('insight', 'Insight:') }}</strong>
                                                <div class="mt-2 small" id="chart-insight-distribution">{{ get_label('no_leave_data_available_yet', 'No leave data available yet.') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Utilization Chart Section (bordered, wider) -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="border rounded-3">
                            <div class="border-bottom pb-3 p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><i class="bx bx-bar-chart-alt text-success"></i> {{ get_label('team_utilization', 'Team Utilization') }}</h5>
                                        <p class="text-muted mb-0 small">{{ get_label('top_users_by_utilization', 'Top users by leave utilization rate') }}</p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-label-success" id="chart-utilization-safe">0 {{ get_label('safe_zone', 'Safe Zone') }}</span>
                                        <span class="badge bg-label-warning" id="chart-utilization-warning">0 {{ get_label('warning_zone', 'Warning Zone') }}</span>
                                        <span class="badge bg-label-danger" id="chart-utilization-critical">0 {{ get_label('critical_zone', 'Critical Zone') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <div class="chart-container-bar w-100">
                                    <div id="utilizationChart"></div>
                                </div>
                                <div class="mt-3">
                                    <div class="alert alert-light mb-0">
                                        <ul class="list-inline mb-0 d-flex flex-wrap align-items-center gap-2">
                                            <li class="list-inline-item me-2">
                                                <i class="bx bx-info-circle text-primary"></i>
                                                <strong>{{ get_label('legend', 'Legend:') }}</strong>
                                            </li>
                                            <li class="list-inline-item"><span class="badge bg-success">< 80%</span> {{ get_label('safe_zone', 'Safe Zone') }}</li>
                                            <li class="list-inline-item"><span class="badge bg-warning">80-95%</span> {{ get_label('warning_zone', 'Warning Zone') }}</li>
                                            <li class="list-inline-item"><span class="badge bg-danger">> 95%</span> {{ get_label('critical_zone', 'Critical Zone') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {{-- </div> --}}

                <!-- Trends Chart Section (bordered, wider) -->
                {{-- <div class="row mb-4"> --}}
                    <div class="col-md-6">
                        <div class="border rounded-3">
                            <div class="border-bottom pb-3 p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><i class="bx bx-line-chart text-info"></i> {{ get_label('monthly_trends', 'Monthly Trends') }}</h5>
                                        <p class="text-muted mb-0 small">{{ get_label('approved_leaves_over_time', 'Approved leaves consumption over time') }}</p>
                                    </div>
                                    <span class="badge bg-label-info" id="chart-trend-months">6 Months</span>
                                </div>
                            </div>
                            <div class="p-3">
                                <div class="chart-container-line w-100">
                                    <div id="trendChart"></div>
                                </div>
                                <div class="alert alert-light mt-3 mb-0">
                                    <i class="bx bx-info-circle text-info"></i>
                                    <strong>{{ get_label('insight', 'Insight:') }}</strong>
                                    <div class="mt-2 small" id="chart-insight-trends">{{ get_label('analyzing_leave_patterns_and_trends', 'Analyzing leave patterns and trends...') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ get_label('close', 'Close') }}</button>
                <button type="button" class="btn btn-primary" id="exportChartsButton">
                    <i class="bx bx-download"></i> {{ get_label('export_charts_report', 'Export Report') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var leaves_report_export_url = "{{ route('reports.export-leaves-report') }}";
</script>
<script src="{{ asset('assets/js/pages/leaves-report.js') }}?v={{ time() }}"></script>
@endsection

@push('page_scripts')
@endpush
