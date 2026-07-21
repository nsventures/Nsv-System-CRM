@extends('layout')

@section('title')
    <?= get_label('leave_balances', 'Leave balances') ?>
@endsection

@section('content')
    @php
        $summary = array_merge([
            'member_count' => 0,
            'total_allocation' => 0,
            'total_accrued_allocation' => 0,
            'total_annual_allocation' => 0,
            'total_yearly_allocation' => 0,
            'total_used' => 0,
            'total_accrued_used' => 0,
            'total_yearly_used' => 0,
            'total_remaining' => 0,
            'total_accrued_remaining' => 0,
            'total_annual_remaining' => 0,
            'total_yearly_remaining' => 0,
            'overall_utilization' => 0,
            'overall_utilization_accrued' => 0,
            'low_balance_count' => 0,
            'exhausted_count' => 0,
            'accrual_enabled' => false,
            'total_unpaid_taken' => 0,
        ], $summary ?? []);

        $daysLabel = get_label('days', 'Days');
        $daysLabelLower = strtolower($daysLabel);
        $isAccrual = !empty($summary['accrual_enabled']);
        $leaveBalancesScriptVersion = is_file(public_path('assets/js/pages/leave-balances.js'))
            ? filemtime(public_path('assets/js/pages/leave-balances.js'))
            : time();

        $formatSummary = function (string $key, int $decimals = 2) use ($summary) {
            $value = $summary[$key] ?? 0;

            return number_format((float) $value, $decimals);
        };

        $metricCards = [
            [
                'icon_class' => 'bg-label-success text-success',
                'icon' => 'bx bx-shield-quarter',
                'label' => get_label('total_remaining_leaves', 'Remaining paid days'),
                'tooltip' => get_label('total_remaining_tooltip', 'Accrued availability contrasted with the remaining annual plan.'),
                'value' => [
                    'primary' => ['key' => 'total_accrued_remaining', 'decimals' => 2],
                    'secondary' => ['key' => 'total_annual_remaining', 'decimals' => 2],
                    'suffix' => $daysLabel,
                ],
                'meta' => array_values(array_filter([
                    $isAccrual ? [
                        'label' => get_label('accrued_available_label', 'Accrued now'),
                        'key' => 'total_accrued_remaining',
                        'decimals' => 2,
                        'suffix' => ' ' . $daysLabelLower,
                    ] : null,
                    [
                        'label' => get_label('annual_remaining_label', 'Annual plan remaining'),
                        'key' => 'total_annual_remaining',
                        'decimals' => 2,
                        'suffix' => ' ' . $daysLabelLower,
                    ],
                ], fn($meta) => $meta !== null)),
                'description' => get_label('total_remaining_description', 'Accrued paid days available right now.'),
                'progress_key' => null,
            ],
            [
                'icon_class' => 'bg-label-primary text-primary',
                'icon' => 'bx bx-time-five',
                'label' => get_label('total_used_leaves', 'Paid days used'),
                'tooltip' => get_label('total_used_leaves_tooltip', 'Paid days already approved this year. When accrual is enabled the comparison uses the accrued allocation so far.'),
                'value' => [
                    'primary' => ['key' => 'total_used', 'decimals' => 2],
                    'secondary' => [
                        'key' => $isAccrual ? 'total_accrued_allocation' : 'total_annual_allocation',
                        'decimals' => 2,
                    ],
                    'suffix' => $daysLabel,
                ],
                'meta' => array_values(array_filter([
                    $isAccrual ? [
                        'label' => get_label('accrued_allocation_used_label', 'Accrued allocated'),
                        'key' => 'total_accrued_allocation',
                        'decimals' => 2,
                        'suffix' => ' ' . $daysLabelLower,
                    ] : null,
                    [
                        'label' => get_label('annual_allocation_label', 'Annual plan'),
                        'key' => 'total_annual_allocation',
                        'decimals' => 2,
                        'suffix' => ' ' . $daysLabelLower,
                    ],
                    ($summary['total_advanced_paid_leaves'] ?? 0) > 0 ? [
                        'label' => get_label('includes_advance', 'Includes'),
                        'key' => 'total_advanced_paid_leaves',
                        'decimals' => 2,
                        'suffix' => ' ' . get_label('advanced_paid_leaves', 'Advanced Paid Leaves'),
                    ] : null,
                ], fn($meta) => $meta !== null)),
                'description' => get_label('total_used_leaves_description', 'Used against the annual plan.'),
                'progress_key' => null,
            ],
            [
                'icon_class' => 'bg-label-info text-info',
                'icon' => 'bx bx-briefcase-alt',
                'label' => get_label('total_allocation', 'Total allocation'),
                'tooltip' => get_label('total_allocation_tooltip', 'Annual paid-leave plan versus the portion accrued so far.'),
                'value' => [
                    'primary' => [
                        'key' => $isAccrual ? 'total_accrued_allocation' : 'total_annual_allocation',
                        'decimals' => 2,
                    ],
                    'secondary' => $isAccrual ? ['key' => 'total_annual_allocation', 'decimals' => 2] : null,
                    'suffix' => $daysLabel,
                ],
                'meta' => array_values(array_filter([
                    $isAccrual ? [
                        'label' => get_label('accrued_allocation_label', 'Accrued allocated'),
                        'key' => 'total_accrued_allocation',
                        'decimals' => 2,
                        'suffix' => ' ' . $daysLabelLower,
                    ] : null,
                    [
                        'label' => get_label('annual_allocation_label', 'Annual plan'),
                        'key' => 'total_annual_allocation',
                        'decimals' => 2,
                        'suffix' => ' ' . $daysLabelLower,
                    ],
                ], fn($meta) => $meta !== null)),
                'description' => get_label('total_allocation_description', 'Annual paid-leave pool assigned to the team.'),
                'progress_key' => null,
            ],
            [
                'icon_class' => 'bg-label-secondary text-secondary',
                'icon' => 'bx bx-group',
                'label' => get_label('total_members', 'Total Members'),
                'tooltip' => null,
                'value' => [
                    'primary' => ['key' => 'member_count', 'decimals' => 0],
                    'secondary' => null,
                    'suffix' => null,
                ],
                'meta' => [],
                'description' => get_label('total_members_description', 'People included in this view\'s calculations.'),
                'progress_key' => null,
            ],
            [
                'icon_class' => 'bg-label-danger text-danger',
                'icon' => 'bx bx-wallet',
                'label' => get_label('unpaid_leaves_taken', 'Unpaid leaves taken'),
                'tooltip' => get_label('unpaid_leaves_tooltip', 'Unpaid leave days recorded this year.'),
                'value' => [
                    'primary' => ['key' => 'total_unpaid_taken', 'decimals' => 2],
                    'secondary' => null,
                    'suffix' => $daysLabel,
                ],
                'meta' => [],
                'description' => get_label('unpaid_leaves_description', 'Unpaid time off converted from approvals.'),
                'progress_key' => null,
            ],
            [
                'icon_class' => 'bg-label-primary text-primary',
                'icon' => 'bx bx-pie-chart-alt',
                'label' => get_label('overall_utilization', 'Overall utilization'),
                'tooltip' => get_label('overall_utilization_tooltip', 'Share of the annual plan already used. Accrued progress compares against the accrued pool.'),
                'value' => [
                    'primary' => ['key' => 'overall_utilization', 'decimals' => 1],
                    'secondary' => null,
                    'suffix' => '%',
                ],
                'meta' => $isAccrual ? [[
                    'label' => get_label('overall_utilization_accrued_label', 'Accrued progress'),
                    'key' => 'overall_utilization_accrued',
                    'decimals' => 1,
                    'suffix' => '%',
                ]] : [],
                'description' => get_label('overall_utilization_description', 'Portion of the annual plan consumed.'),
                'progress_key' => 'overall_utilization',
                'progress_color' => 'bg-primary',
            ],
            [
                'icon_class' => 'bg-label-warning text-warning',
                'icon' => 'bx bx-bell',
                'label' => get_label('members_low_balance', 'Members with low balance'),
                'tooltip' => get_label('members_low_balance_tooltip', 'Counts of teammates whose remaining paid days sit below the configured low-balance threshold. Great for planning proactive top-ups or reminders.'),
                'value' => [
                    'primary' => ['key' => 'low_balance_count', 'decimals' => 0],
                    'secondary' => null,
                    'suffix' => null,
                ],
                'meta' => [],
                'description' => get_label('members_low_balance_description', 'Members below the low-balance threshold.'),
                'progress_key' => null,
            ],
            [
                'icon_class' => 'bg-label-secondary text-secondary',
                'icon' => 'bx bx-error-alt',
                'label' => get_label('members_exhausted_balance', 'Members with no paid days'),
                'tooltip' => get_label('members_exhausted_tooltip', 'Teammates who have exhausted their paid days (zero or negative balance). Further approvals for them convert to unpaid unless allocation is adjusted.'),
                'value' => [
                    'primary' => ['key' => 'exhausted_count', 'decimals' => 0],
                    'secondary' => null,
                    'suffix' => null,
                ],
                'meta' => [],
                'description' => get_label('members_exhausted_description', 'Members currently at zero paid days.'),
                'progress_key' => null,
            ],
        ];
    @endphp

    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-2 mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('leave_balances', 'Leave balances') ?>
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ url('leave-requests') }}" class="btn btn-sm btn-outline-primary">
                    <i class='bx bx-right-arrow-alt me-1'></i><?= get_label('leave_requests', 'Leave requests') ?>
                </a>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card mb-4 border shadow-none">
            <div class="card-body p-3">
                <div class="row g-3 align-items-end tk-filter-row">
                    <div class="col-lg-3 col-md-4 col-12">
                        <label for="lb_date_range" class="form-label"><?= get_label('date_range', 'Date Range') ?></label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                            <input
                                type="text"
                                class="form-control advanced-daterange-picker"
                                id="lb_date_range"
                                name="lb_date_range"
                                placeholder="<?= get_label('select_date_range', 'Select date range') ?>"
                                autocomplete="off"
                                @if(isset($dateFrom) && isset($dateTo))
                                    value="{{ format_date($dateFrom) }} - {{ format_date($dateTo) }}"
                                @endif
                            >
                        </div>
                        <input type="hidden" id="lb_date_range_from" name="lb_date_range_from" value="{{ $dateFrom ?? '' }}">
                        <input type="hidden" id="lb_date_range_to" name="lb_date_range_to" value="{{ $dateTo ?? '' }}">
                    </div>
                    <div class="col-lg-3 col-md-4 col-12">
                        <label for="lb_status_filter" class="form-label"><?= get_label('balance_status', 'Balance Status') ?></label>
                        <select id="lb_status_filter" class="form-select tom_static_select" data-allow-clear="true">
                            <option value=""><?= get_label('balance_status_all', 'All balance states') ?></option>
                            <option value="healthy"><?= get_label('balance_status_healthy', 'Healthy') ?></option>
                            <option value="low"><?= get_label('balance_status_low', 'Low') ?></option>
                            <option value="exhausted"><?= get_label('balance_status_exhausted', 'Exhausted') ?></option>
                        </select>
                    </div>
                    <div class="col-lg-6 col-md-4 col-12">
                        <label for="lb_member_filter" class="form-label"><?= get_label('select_members', 'Select Members') ?></label>
                        <select id="lb_member_filter" class="form-select tom_users_select" multiple="multiple"
                            data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Strips -->
        @foreach (array_chunk($metricCards, 4) as $cardGroup)
            <div class="tk-card mb-4">
                <div class="tk-card-body p-0">
                    <div class="tk-metric-strip">
                        @foreach ($cardGroup as $card)
                            <div class="tk-metric">
                                <div class="tk-metric-row">
                                    <span class="tk-metric-label">{{ $card['label'] }}</span>
                                    @if (!empty($card['tooltip']))
                                        <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="tooltip"
                                            title="{{ $card['tooltip'] }}">
                                            <i class="bx bx-help-circle"></i>
                                        </button>
                                    @endif
                                </div>
                                <div class="tk-metric-value count d-flex align-items-baseline gap-1 mt-1">
                                    <span data-summary-key="{{ $card['value']['primary']['key'] }}">
                                        {{ $formatSummary($card['value']['primary']['key'], $card['value']['primary']['decimals'] ?? 2) }}
                                    </span>
                                    @if (!empty($card['value']['secondary']))
                                        <small class="text-muted fs-5 fw-normal">/ <span data-summary-key="{{ $card['value']['secondary']['key'] }}">{{ $formatSummary($card['value']['secondary']['key'], $card['value']['secondary']['decimals'] ?? 2) }}</span></small>
                                    @endif
                                    @if (!empty($card['value']['suffix']))
                                        <small class="text-muted fs-5 fw-normal">{{ $card['value']['suffix'] }}</small>
                                    @endif
                                </div>
                                @if (!empty($card['meta']))
                                    <div class="d-flex flex-column gap-1 mt-2 small text-muted">
                                        @foreach ($card['meta'] as $meta)
                                            <span class="d-flex align-items-baseline gap-1">
                                                <span>{{ $meta['label'] }}:</span>
                                                <span class="fw-semibold text-body" data-summary-key="{{ $meta['key'] }}">
                                                    {{ $formatSummary($meta['key'], $meta['decimals'] ?? 2) }}
                                                </span>
                                                @if (!empty($meta['suffix']))
                                                    <span>{{ $meta['suffix'] }}</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if (!empty($card['description']))
                                    <p class="mb-0 mt-2 small text-muted">{{ $card['description'] }}</p>
                                @endif
                                @if (!empty($card['progress_key']))
                                    @php
                                        $progressValue = (float) ($summary[$card['progress_key']] ?? 0);
                                        $progressWidth = max(0, min(100, $progressValue));
                                    @endphp
                                    <div class="mt-3">
                                        <div class="progress" style="height: 0.4rem;">
                                            <div class="progress-bar {{ $card['progress_color'] ?? 'bg-primary' }}" role="progressbar"
                                                style="width: {{ $progressWidth }}%;"
                                                aria-valuenow="{{ $progressWidth }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Chart and Table section -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="tk-card mb-4">
                    <div class="tk-card-head">
                        <div class="tk-card-head-main">
                            <h3 class="tk-card-title"><i class='bx bx-pie-chart-alt me-2'></i>{{ get_label('leave_balances_chart_title', 'User leave utilization') }}</h3>
                            <p class="tk-card-title-sub mt-1">{{ get_label('leave_balances_chart_subtitle', 'Compare used versus remaining paid time off for the visible members.') }}</p>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary chart-legend-toggle active" data-series="used_paid_leaves">
                                <i class='bx bxs-circle text-primary me-2'></i>{{ get_label('used_paid_leaves', 'Used Paid Leaves') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success chart-legend-toggle active" data-series="remaining_paid_leaves">
                                <i class='bx bxs-circle text-success me-2'></i>{{ get_label('remaining_paid_leaves', 'Remaining Paid Leaves') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning chart-legend-toggle active" data-series="unpaid_leaves_taken">
                                <i class='bx bxs-circle text-warning me-2'></i>{{ get_label('unpaid_leaves', 'Unpaid Leaves') }}
                            </button>
                        </div>
                    </div>
                    <div class="tk-card-body">
                        <div id="leaveBalancesStackedChart" class="w-100"></div>
                        <div id="leaveBalancesChartEmptyState" class="text-muted small mt-3 d-none">
                            {{ get_label('leave_balances_chart_no_data', 'No leave balance data to display.') }}
                        </div>

                        <div class="mt-4">
                            <div class="row g-4">
                                <div class="col-xl-3 col-lg-4 col-md-6">
                                    <div class="card border shadow-none bg-light h-100">
                                        <div class="card-body py-3 px-4">
                                            <h6 class="text-muted text-uppercase small mb-3">{{ get_label('leave_balances_chart_snapshot', 'Snapshot') }}</h6>
                                            <ul class="list-unstyled mb-0 small">
                                                <li class="d-flex justify-content-between align-items-center mb-2">
                                                    <span>{{ get_label('members', 'Members') }}</span>
                                                    <span class="fw-semibold" data-chart-summary="member_count">--</span>
                                                </li>
                                                <li class="d-flex justify-content-between align-items-center mb-2">
                                                    <span>{{ get_label('overall_utilization', 'Overall utilization') }}</span>
                                                    <span class="fw-semibold" data-chart-summary="avg_utilization">--</span>
                                                </li>
                                                <li class="d-flex justify-content-between align-items-center">
                                                    <span>{{ get_label('unpaid_leaves', 'Unpaid Leaves') }}</span>
                                                    <span class="fw-semibold" data-chart-summary="total_unpaid">--</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-8 col-md-6">
                                    <div class="card border shadow-none h-100">
                                        <div class="card-body py-3 px-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="text-muted text-uppercase small mb-0">{{ get_label('leave_balances_chart_trend_title', 'Monthly trend') }}</h6>
                                                <span class="badge bg-label-success" data-trend-months>--</span>
                                            </div>
                                            <div id="leaveBalancesTrendSparkline" class="w-100"></div>
                                            <ul class="list-unstyled mb-0 mt-3 small">
                                                <li class="d-flex justify-content-between">
                                                    <span>{{ get_label('leave_balances_chart_trend_peak', 'Peak') }}</span>
                                                    <span data-trend-peak>--</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span>{{ get_label('leave_balances_chart_trend_dip', 'Dip') }}</span>
                                                    <span data-trend-dip>--</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span>{{ get_label('leave_balances_chart_trend_direction', 'Direction') }}</span>
                                                    <span data-trend-direction>--</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-3 col-lg-12">
                                    <div class="d-flex flex-column gap-3 h-100">
                                        <div class="alert alert-warning d-flex align-items-center py-2 px-3 m-0">
                                            <i class="bx bx-bell fs-4 me-2"></i>
                                            <div>
                                                <div class="fw-semibold small">{{ get_label('leave_balances_chart_low_balance_title', 'Approaching zero balance') }}</div>
                                                <div class="small text-muted" data-highlight-low>--</div>
                                            </div>
                                        </div>
                                        <div class="alert alert-info d-flex align-items-center py-2 px-3 m-0">
                                            <i class="bx bx-wallet fs-4 me-2"></i>
                                            <div>
                                                <div class="fw-semibold small">{{ get_label('leave_balances_chart_top_unpaid_title', 'Highest unpaid usage') }}</div>
                                                <div class="small text-muted" data-highlight-unpaid>--</div>
                                            </div>
                                        </div>
                                        <div class="alert alert-success d-flex align-items-center py-2 px-3 m-0">
                                            <i class="bx bx-check-circle fs-4 me-2"></i>
                                            <div>
                                                <div class="fw-semibold small">{{ get_label('leave_balances_chart_healthy_balance_title', 'Healthy balances') }}</div>
                                                <div class="small text-muted" data-highlight-healthy>--</div>
                                            </div>
                                        </div>
                                        <div class="alert alert-secondary d-flex align-items-center py-2 px-3 m-0">
                                            <i class="bx bx-calendar-check fs-4 me-2"></i>
                                            <div>
                                                <div class="fw-semibold small">{{ get_label('leave_balances_chart_latest_leave_title', 'Most recent leave') }}</div>
                                                <div class="small text-muted" data-highlight-latest>--</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-none">
                    <div class="card-body p-0">
                        <div class="table-responsive text-nowrap">
                            <table id="leave_balances_table"
                                   class="table table-hover table-nowrap "
                                   data-toggle="table"
                                   data-loading-template="loadingTemplate"
                                   data-url="{{ url('/leave-balances/list') }}"
                                   data-icons-prefix="bx"
                                   data-icons="icons"
                                   data-show-refresh="true"
                                   data-total-field="total"
                                   data-data-field="rows"
                                   data-page-list="[10, 25, 50, 100]"
                                   data-side-pagination="server"
                                   data-pagination="true"
                                   data-search="true"
                                   data-sort-name="member"
                                   data-sort-order="asc"
                                   data-mobile-responsive="true"
                                   data-query-params="queryParamsLeaveBalances">
                                <thead>
                                <tr>
                                    <th data-field="member" data-sortable="true"><?= get_label('member', 'Member') ?></th>
                                    <th data-field="role" data-sortable="false"><?= get_label('role', 'Role') ?></th>
                                    <th data-field="total_allocation" data-sortable="true"><?= get_label('total_allocation', 'Total allocation') ?></th>
                                    <th data-field="used_paid_leaves" data-sortable="true"><?= get_label('used_paid_leaves', 'Used Paid Leaves') ?></th>
                                    <th data-field="remaining_paid_leaves" data-sortable="true">{{ get_label('remaining_paid_leaves', 'Remaining Paid Leaves') }}</th>
                                    <th data-field="utilization" data-sortable="true"><?= get_label('overall_utilization', 'Overall utilization') ?></th>
                                    <th data-field="latest_leave" data-sortable="false"><?= get_label('latest_leave', 'Latest leave') ?></th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_scripts')
    <script src="{{ asset('assets/js/pages/leave-balances.js') }}?v={{ $leaveBalancesScriptVersion }}"></script>
@endsection


