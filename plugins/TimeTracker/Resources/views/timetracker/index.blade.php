@extends('layout')
@section('title', get_label('productivity_dashboard','Productivity Dashboard'))
@section('page_styles')
    <link rel="stylesheet" href="{{ asset('assets/css/timetracker/timetracker.css') }}">
@endsection
@section('content')

    <div class="container-fluid">
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
                            <?= get_label('productivity_dashboard','Productivity Dashboard') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- Welcome Guide Card -->
        <div class="card mb-3 shadow-sm border-0" id="welcomeGuide">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="text-primary fw-semibold mb-1">
                            <i class="bx bx-bulb me-1"></i> {{ get_label('welcome_to_your_productivity_dashboard','Welcome to Your Productivity Dashboard') }}
                        </h6>
                        <p class="text-muted small mb-2">
                            {{ get_label('get_started_by_selecting_a_date_range_and_team_members_to_analyze_hover_over_charts_or_metrics_for_insights','Get started by selecting a date range and team members to analyze. Hover over charts or metrics for insights.') }}
                        </p>
                        <small class="text-muted d-flex align-items-center">
                            <i class="bx bx-info-circle me-1"></i>
                            {{ get_label('tip','Tip') }} : {{ get_label('use_the_date_picker_to_compare_periods_and_track_productivity_trends','Use the date picker to compare periods and track productivity trends.') }}
                        </small>
                    </div>
                    <div class="ms-2 d-flex flex-column align-items-end">
                        <button type="button" class="btn btn-sm btn-primary rounded-circle mb-2 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#helpModal"
                            data-bs-toggle="tooltip" data-bs-placement="left"
                            title="Dashboard guide">
                            <i class="bx bx-help-circle"></i>
                        </button>
                        <button type="button" class="btn-close btn-sm dismiss-guide-btn" aria-label="Dismiss guide"></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header and Filters -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="fw-bold mb-1">
                            <i class="bx bx-bar-chart-alt-2 me-2"></i>
                            {{ get_label('productivity_analytics_dashboard','Productivity Analytics Dashboard') }}
                        </h3>
                        <p class="text-muted small mb-3">
                            {{ get_label('monitor_team_performance_and_gain_actionable_insights_in_real_time','Monitor team performance and gain actionable insights in real-time.') }}
                            <i class="bx bx-help-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                               title="{{ get_label('this_dashboard_tracks_active_work_time_manual_entries_breaks_and_productivity_patterns_to_help_optimize_team_performance','This dashboard tracks active work time, manual entries, breaks, and productivity patterns to help optimize team performance.') }}"></i>
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="filter-control-wrapper" data-hp-filter="dashboard-date-range">
                                    <label for="daterange" class="form-label fw-semibold ">
                                        {{ get_label('select_period','Select Period') }}
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip"
                                           data-bs-placement="top" title="{{ get_label('choose_a_date_range_to_analyze_productivity_trends_default_shows_last_7_days','Choose a date range to analyze productivity trends. Default shows last 7 days.') }}"></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bx bx-calendar"></i>
                                        </span>
                                        <input type="text" id="daterange" class="form-control" readonly
                                               placeholder="{{ get_label('select_date_range','Select date range') }}..." data-bs-toggle="tooltip"
                                               data-bs-placement="bottom" title="Click to open date picker" data-hp-filter-input="dashboard-date-range">
                                    </div>
                                    <div class="form-text">
                                        <i class="bx bx-time-five me-1"></i>
                                        {{ get_label('quick_select','Quick select') }}:
                                        <span class="badge bg-light text-dark ms-1 cursor-pointer quick-date-btn filter-chip" data-range="today"
                                              data-hp-filter-chip="dashboard-date-range" data-bs-toggle="tooltip" title="{{ get_label('view_today_data', 'View today data')  }}">{{ get_label('today','Today') }}</span>
                                        <span class="badge bg-light text-dark ms-1 cursor-pointer quick-date-btn filter-chip" data-range="yesterday"
                                              data-hp-filter-chip="dashboard-date-range" data-bs-toggle="tooltip" title="{{ get_label('view_yesterday_data','View yesterday data') }}">{{ get_label('yesterday','Yesterday') }}</span>
                                        <span class="badge bg-light text-dark ms-1 cursor-pointer quick-date-btn filter-chip" data-range="last7days"
                                              data-hp-filter-chip="dashboard-date-range" data-bs-toggle="tooltip" title="{{ get_label('view_last_7_days_data','View last 7 days data') }}">{{get_label('last_7_days','Last 7 days')}}</span>
                                        <span class="badge bg-light text-dark ms-1 cursor-pointer quick-date-btn filter-chip" data-range="last30days"
                                              data-hp-filter-chip="dashboard-date-range" data-bs-toggle="tooltip" title="{{ get_label('view_last_30_days_data','View last 30 days data') }}">{{ get_label('last_30_days','Last 30 days') }}</span>
                                        <span class="badge bg-light text-dark ms-1 cursor-pointer quick-date-btn filter-chip" data-range="thismonth"
                                              data-hp-filter-chip="dashboard-date-range" data-bs-toggle="tooltip" title="{{ get_label('view_current_month_data','View current month data') }}">{{ get_label('current_month','Current Month') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-control-wrapper" data-hp-filter="dashboard-users">
                                    <label for="userFilter" class="form-label fw-semibold ">
                                        {{ get_label('select_team_members','Select Team Member(s)') }}
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip"
                                           data-bs-placement="top" title="{{ get_label('filter_by_specific_team_members_or_leave_empty_to_view_all_users','Filter by specific team members or leave empty to view all users.') }}"></i>
                                    </label>
                                    <select id="userFilter" class="form-select js-example-basic-multiple" multiple
                                            data-hp-filter-input="dashboard-users"
                                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                                            title="{{ get_label('start_typing_to_search_for_team_members','Start typing to search for team members') }}">
                                        <option value="">Loading team members...</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="bx bx-group me-1"></i>
                                        <span id="selectedUsersCount">{{ get_label('all_team_members_selected','All team members selected') }}</span>
                                        <span class="ms-2">
                                            <a class="text-decoration-none small select-all-users-btn" href="javascript:void(0)">Select All</a> |
                                            <a class="text-decoration-none small clear-user-selection-btn" href="javascript:void(0)">Clear</a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-none d-md-block text-center">
                        <img src="{{ asset('assets/img/illustrations/man-with-laptop-light.png') }}"
                            alt="Dashboard illustration" class="h-px-200 img-fluid"
                            data-bs-toggle="tooltip" data-bs-placement="left"
                            title="Your productivity insights at a glance">
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5 d-none" >
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">{{ get_label('loading_your_productivity_insights','Loading your productivity insights') }}...</p>
        </div>

        <!-- Metrics Section -->
        <div id="dashboardMetrics" class="row  mb-6 mt-4">
            <!-- Metrics will be populated by JavaScript -->
        </div>

        <!-- No Data State -->
        <div id="noDataState" class="text-center py-5 d-none" >
            <div class="mb-3">
                <i class="bx bx-data fs-1 text-secondary"></i>
            </div>
            <h5 class="text-muted">{{ get_label('no_data_available','No Data Available') }}</h5>
            <p class="text-muted">
                {{ get_label('no_productivity_data_found_for_the_selected_period_and_team_members','No productivity data found for the selected period and team members') }}.<br>
                {{ get_label('try_adjusting_your_date_range_or_user_selection','Try adjusting your date range or user selection') }}.
            </p>
            <button class="btn btn-outline-primary reset-filters-btn">
                <i class="bx bx-refresh me-1"></i>{{ get_label('reset_filters','Reset Filters') }}
            </button>
        </div>

        <!-- Charts Section -->
        <div class="row g-4" id="chartsSection">
            <div class="col-lg-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-chart-area text-primary me-2"></i>
                            <h6 class="mb-0">{{ get_label('daily_working_hours_trend','Daily Working Hours Trend') }}</h6>
                            <i class="bx bx-help-circle text-muted ms-2" data-bs-toggle="tooltip"
                               data-bs-placement="top" title="{{ get_label('shows_daily_active_work_hours_manual_entries_and_total_productive_time_hover_over_points_for_detailed_breakdown','Shows daily active work hours, manual entries, and total productive time. Hover over points for detailed breakdown.') }}"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item export-chart-btn" data-chart="workingHoursTrend">
                                    <i class="bx bx-download me-2"></i>{{ get_label('export_chart','Export Chart') }}</a></li>
                                <li><a class="dropdown-item toggle-chart-view-btn" data-chart="workingHoursTrend">
                                    <i class="bx bx-bar-chart me-2"></i>{{ get_label('toggle_view','Toggle View') }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <small class="text-muted">
                                <i class="bx bx-info-circle me-1"></i>
                                {{ get_label('drag_to_zoom_and_double_click_to_reset','Drag to zoom, double-click to reset') }}
                            </small>
                        </div>
                        <div id="workingHoursTrendChart" class="h-350"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-layer-group text-success me-2"></i>
                            <h6 class="mb-0">{{ get_label('daily_breakdown','Daily Breakdown') }}</h6>
                            <i class="bx bx-help-circle text-muted ms-2" data-bs-toggle="tooltip"
                               data-bs-placement="top" title="{{ get_label('stacked_chart_showing_distribution_of_active_time_manual_time_and_breaks_throughout_the_selected_period','Stacked chart showing distribution of active time, manual time, and breaks throughout the selected period.') }}"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item export-chart-btn" data-chart="dailyBreakdown">
                                    <i class="bx bx-download me-2"></i>{{ get_label('export_chart','Export Chart') }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bx bx-mouse me-1"></i>
                                {{ get_label('click_lengend_items_to_show_hide_data_series','Click legend items to show/hide data series') }}
                            </small>
                        </div>
                        <div id="dailyBreakdownChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">
                                <i class="bx bx-user-check text-success me-2"></i>{{ get_label('top_productive_users','Top Productive Users') }}
                                <i class="bx bx-help-circle text-muted ms-2" data-bs-toggle="tooltip"
                                   data-bs-placement="top" title="{{ get_label('ranked_by_total_productive_hours_active_plus_manual_time_click_on_any_user_for_detailed_insights','Ranked by total productive hours (active + manual time). Click on any user for detailed insights.') }}"></i>
                            </h5>
                            <small class="text-muted">{{ get_label('identify_your_most_productive_team_members_based_on_active_and_manual_work_time','Identify your most productive team members based on active and manual work time') }}</small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                {{ get_label('view','View') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item sort-users-btn" data-sort="productive_time">
                                    <i class="bx bx-sort-down me-2"></i>{{ get_label('sort_by_productive_time','Sort by Productive Time') }}</a></li>
                                <li><a class="dropdown-item sort-users-btn" data-sort="utilization">
                                    <i class="bx bx-trending-up me-2"></i>{{ get_label('sort_by_utilization','Sort by Utilization') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item export-user-data-btn" href="#">
                                    <i class="bx bx-export me-2"></i>{{ get_label('export_data','Export Data') }}</a></li>
                            </ul>
                        </div>

                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bx bx-trophy me-1"></i>
                                {{ get_label('rankings_based_on_selected_date_range','Rankings based on selected date range') }}
                            </small>
                        </div>
                        <div id="topProductiveUsers" class="row g-3">
                            <!-- Will be populated by JavaScript -->
                        </div>
                        <div class="text-center mt-3 d-none" id="viewAllUsersBtn">
                            <button class="btn btn-outline-primary btn-sm show-all-users-btn">
                                <i class="bx bx-show-alt me-1"></i>{{ get_label('view_all_users','View All Users') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">
                                <i class="bx bx-time text-success me-2"></i>{{ get_label('average_productive_hours','Average Productive Hours') }}
                                <i class="bx bx-help-circle text-muted ms-2" data-bs-toggle="tooltip"
                                   data-bs-placement="top" title="{{ get_label('daily_average_productive_hours_per_team_member_click_any_row_to_see_detailed_user_insights_and_trends','Daily average productive hours per team member. Click any row to see detailed user insights and trends.') }}"></i>
                            </h5>
                            <small class="text-muted">{{ get_label('track_the_average_productive_hours_of_your_team_members_click_user_row_for_more_insights','Track the average productive hours of your team members. Click user row for more insights.') }}</small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                {{ get_label('view','View') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item export-chart-btn" data-chart="averageProductiveHours">
                                    <i class="bx bx-export me-2"></i>{{ get_label('export_data','Export Data') }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">{{ get_label('team_average','Team Average') }}</small>
                                    <strong id="teamAverage" class="text-primary">--</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">{{ get_label('highest','Highest') }}</small>
                                    <strong id="highestAverage" class="text-success">--</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">{{ get_label('lowest','Lowest') }}</small>
                                    <strong id="lowestAverage" class="text-warning">--</strong>
                                </div>
                            </div>
                        </div>
                        <div id="avgProductiveHoursChart" class="row g-3">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Insights Modal -->
        <div class="modal fade" id="userInsightsModal" tabindex="-1" aria-labelledby="userInsightsModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userInsightsModalLabel">
                            <i class="bx bx-user-circle me-2"></i>{{ get_label('user_insights','User Insights') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center py-3" id="modalLoading">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">{{ get_label('loading_user_insights','Loading user insights') }}...</p>
                        </div>
                        <div id="userInsightsContent"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary export-user-insights-btn">
                            <i class="bx bx-download me-1"></i>{{ get_label('export_report','Export Report') }}
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Modal -->
        <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="helpModalLabel">
                            <i class="bx bx-help-circle me-2"></i>{{ get_label('dashboard_help_guide','Dashboard Help Guide') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="accordion" id="helpAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#help1" aria-expanded="true">
                                        {{ get_label('getting_started','Getting Started') }}
                                    </button>
                                </h2>
                                <div id="help1" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <p>{{ get_label('use_the_date_picker_and_user_filter_to_customize_your_view_the_dashboard_updates_automatically_when_you_change_these_settings','Use the date picker and user filter to customize your view. The dashboard updates automatically when you change these settings') }}.</p>
                                        <ul>
                                            <li>{{ get_label('select_a_date_range_to_analyze_specific_periods','Select a date range to analyze specific periods') }}</li>
                                            <li>{{ get_label('filter_by_team_members_to_focus_on_individual_or_group_performance','Filter by team members to focus on individual or group performance') }}</li>
                                            <li>{{ get_label('use_quick_select_buttons_for_common_time_periods','Use quick select buttons for common time periods') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#help2">
                                        {{ get_label('understanding_metrics','Understanding Metrics') }}
                                    </button>
                                </h2>
                                <div id="help2" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul>
                                            <li><strong>{{ get_label('active_hours','Active Hours') }}:</strong> {{ get_label('time_tracked_automatically_by_the_system','Time tracked automatically by the system') }}</li>
                                            <li><strong>{{ get_label('manual_hours','Manual Hours') }}:</strong> {{ get_label('time_entered_manually_by_users','Time entered manually by users') }}</li>
                                            <li><strong>{{ get_label('break_time','Break Time') }}:</strong> {{ get_label('recorded_break_periods','Recorded break periods') }}</li>
                                            <li><strong>{{ get_label('productivity_score','Productivity Score') }}:</strong> {{ get_label('calculated_based_on_active_vs_total_time','Calculated based on active vs. total time') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#help3">
                                        {{ get_label('chart_interactions','Chart Interactions') }}
                                    </button>
                                </h2>
                                <div id="help3" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul>
                                            <li>{{ get_label('hover_over_chart_elements_for_detailed_information','Hover over chart elements for detailed information') }}</li>
                                            <li>{{ get_label('click_legend_items_to_show_hide_data_series','Click legend items to show/hide data series') }}</li>
                                            <li>{{ get_label('drag_on_line_charts_to_zoon_into_specific_periods','Drag on line charts to zoom into specific periods') }}</li>
                                            <li>{{ get_label('double_click_to_reset_zoom_level','Double-click to reset zoom level') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <script>
        var dashboardDataRoute = "{{ route('timetracker.dashboard.data') }}";
        var label_select_team_members = @json(get_label('select_team_members','Select Team Member(s)'));
        var label_all_team_members = @json(get_label('all_team_members','All Team Members'));
        var label_work_time = @json(get_label('work_time','Work Time'));
        var label_active_time = @json(get_label('active_time','Active Time'));
        var label_manual_time = @json(get_label('manual_time','Active Time'));
        var label_break_time = @json(get_label('break_time','Break Time'));
        var label_idle_time = @json(get_label('idle_time','Idle Time'));
        var label_productive_time = @json(get_label('productive_time','Productive Time'));
        var label_unproductive_time = @json(get_label('unproductive_time','Unproductive Time'));
        var label_utilization = @json(get_label('utilization','Utilization'));
        var label_neutral_time = @json(get_label('neutral_time','Neutral Time'));
        var label_manual_processing_time = @json(get_label('manual_processing_time','Manual Processing Time'));
        var label_work_time_description = @json(get_label('combined_active_and_manual_time','Combined active and manual time'));
        var label_active_time_description = @json(get_label('automatically_tracked_time','Automatically tracked time'));
        var label_manual_time_description = @json(get_label('manually_entered_time','Manually entered time'));
        var label_break_time_description = @json(get_label('recorded_break_periods','Recorded break periods'));
        var label_idle_time_description = @json(get_label('periods_of_inactivity','Periods of inactivity'));
        var label_productive_time_description = @json(get_label('active_work_time','Active work time'));
        var label_unproductive_time_description = @json(get_label('non_productive_activities','Non-productive activities'));
        var label_utilization_description = @json(get_label('productivity_efficiency','Productivity efficiency'));
        var label_neutral_time_description = @json(get_label('uncategorized_time','Uncategorized time'));
        var label_manual_processing_time_description = @json(get_label('time_spent_on_manual_processing','Time spent on manual processing'));
        var label_no_user_data_available = @json(get_label('no_user_data_available','No user data available'));
        var label_user = @json(get_label('user','User'));
        var label_total_productive_hours_active_manual_time = @json(get_label('total_productive_hours_active_manual_time','Total productive hours (active + manual time)'));
        var label_7_day_productivity_trend = @json(get_label('7_day_productivity_trend','7-day productivity trend'));
        var label_trend = @json(get_label('trend','Trend'));
        var label_click_to_view_detailed_insights_for = @json(get_label('click_to_view_detailed_insights_for','Click to view detailed insights for'));
        var label_daily_minutes = @json(get_label('daily_minutes','Daily Minutes'));
        var label_total_work_hours = @json(get_label('total_work_hours','Total Work Hours'));
        var label_active_hours = @json(get_label('active_hours','Active Hours'));
        var label_manual_hours = @json(get_label('manual_hours','Manual Hours'));
        var label_average_hours_per_day = @json(get_label('average_hours_per_day','Average Hours Per Day'));
        var label_user_insights = @json(get_label('user_insights','User Insights'));
        var label_total_work_time = @json(get_label('total_work_time','Total Work Time'));
        var label_daily_work_hours_trend = @json(get_label('daily_work_hours_trend','Daily Work Hours Trend'));
    </script>
    <script src="{{ asset('assets/js/timetracker-plugin/dashboard.js') }}"></script>

@endsection
