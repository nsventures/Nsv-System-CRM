@extends('layout')
@section('title', get_label('time_and_attendance', 'Time and Attendance'))
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
                            <?= get_label('time_and_attendance', 'Time and Attendance') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        {{-- Page Title --}}

        {{-- Filters --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header border-bottom-0 bg-white">
                <h5 class="fw-semibold mb-0">{{ get_label('filters', 'Filters') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Date Range -->
                    <div class="col-lg-5 col-md-6 col-12">
                        <div class="filter-control-wrapper" data-hp-filter="attendance-date-range">
                            <label for="date_range"
                                class="form-label fw-semibold ">{{ get_label('select_date_range', 'Select Date Range') }}</label>
                            <input type="text" class="form-control" id="date_range" autocomplete="off" data-hp-filter-input="attendance-date-range">
                            <div class="form-text mt-2">
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-light text-dark quick-date-btn cursor-pointer filter-chip" data-range="today"
                                        data-hp-filter-chip="attendance-date-range"
                                        data-bs-toggle="tooltip"
                                        title="{{ get_label('view_today_data', 'View today data') }}">{{ get_label('today', 'Today') }}</span>
                                    <span class="badge bg-light text-dark quick-date-btn cursor-pointer filter-chip" data-range="yesterday"
                                        data-hp-filter-chip="attendance-date-range"
                                        data-bs-toggle="tooltip"
                                        title="{{ get_label('view_yesterday_data', 'View yesterday data') }}">{{ get_label('yesterday', 'Yesterday') }}</span>
                                    <span class="badge bg-light text-dark quick-date-btn cursor-pointer filter-chip" data-range="last7days"
                                        data-hp-filter-chip="attendance-date-range"
                                        data-bs-toggle="tooltip"
                                        title="{{ get_label('view_last_7_days_data', 'View last 7 days data') }}">{{ get_label('last_7_days', 'Last 7 days') }}</span>
                                    <span class="badge bg-light text-dark quick-date-btn cursor-pointer filter-chip" data-range="last30days"
                                        data-hp-filter-chip="attendance-date-range"
                                        data-bs-toggle="tooltip"
                                        title="{{ get_label('view_last_30_days_data', 'View last 30 days data') }}">{{ get_label('last_30_days', 'Last 30 days') }}</span>
                                    <span class="badge bg-light text-dark quick-date-btn cursor-pointer filter-chip" data-range="thismonth"
                                        data-hp-filter-chip="attendance-date-range"
                                        data-bs-toggle="tooltip"
                                        title="{{ get_label('view_current_month_data', 'View current month data') }}">{{ get_label('current_month', 'Current Month') }}</span>
                                    <span class="badge bg-light text-dark quick-date-btn cursor-pointer filter-chip" data-range="lastmonth"
                                        data-hp-filter-chip="attendance-date-range"
                                        data-bs-toggle="tooltip"
                                        title="{{ get_label('view_last_month_data', 'View Last Month Data') }}">{{ get_label('last_month', 'Last Month') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Employees Select -->
                    <div class="col-lg-4 col-md-4 col-12">
                        <div class="filter-control-wrapper" data-hp-filter="attendance-users">
                            <label for="employee_select" class="form-label fw-semibold ">{{get_label('users','Users')}}</label>
                            <select class="form-select" id="employee_select" multiple data-hp-filter-input="attendance-users"></select>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="align-items-center col-12 col-lg-3 col-md-2 d-flex gap-2">
                        <button type="button" class="btn btn-primary w-100" id="fetch_data">{{ get_label('fetch_data','Fetch Data') }}</button>
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="exportToCSV()">{{get_label('export_to_csv','Export to CSV')}}</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Summary Cards -->
        <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-6 my-4">
            <!-- Total Employees -->
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="text-muted mb-1">{{ get_label('total_users','Total Users') }}</h6>
                            <h2 class="fw-bold mb-0" id="total_employees">0</h2>
                        </div>
                        <div class="avatar-initial bg-label-primary d-flex align-items-center justify-content-center rounded w-px-40 h-px-40">
                            <i class="bx bx-user fs-4 " ></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Present Today -->
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="text-muted mb-1">{{ get_label('total_records','Total Records') }}</h6>
                            <h2 class="fw-bold mb-0" id="total_records">0</h2>
                        </div>
                        <div class="avatar-initial bg-label-success d-flex align-items-center justify-content-center rounded w-px-40 h-px-40">
                            <i class="bx bx-check-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Late Arrivals -->
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="text-muted mb-1">{{ get_label('total_work_hours','Total Work Hours') }}</h6>
                            <h2 class="fw-bold mb-0" id="total_work_hours">0</h2>
                        </div>
                        <div class="avatar-initial bg-label-warning d-flex align-items-center justify-content-center rounded w-px-40 h-px-40">
                            <i class="bx bx-time fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Break Time -->
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="text-muted mb-1">{{ get_label('break_time','Break Time') }}</h6>
                            <h2 class="fw-bold mb-0" id="total_break_time">0</h2>
                        </div>
                        <div class="avatar-initial bg-label-danger d-flex align-items-center justify-content-center rounded w-px-40 h-px-40">
                            <i class="bx bx-coffee fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Idle Time -->
            <div class="col">
                <div class="card tt-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{ get_label('idle_time','Idle Time') }}</h6>
                            <h2 class="fw-bold mb-0" id="total_idle_time">0</h2>
                        </div>
                        <div class="avatar-initial bg-label-warning d-flex align-items-center justify-content-center rounded w-px-40 h-px-40">
                            <i class="bx bx-pause-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Avg Utilization -->
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="text-muted mb-1">{{ get_label('utilization','Utilization') }}</h6>
                            <h2 class="fw-bold mb-0" id="avgUtilization">0</h2>
                        </div>
                        <div class="avatar-initial bg-label-info d-flex align-items-center justify-content-center rounded w-px-40 h-px-40">
                            <i class="bx bx-pie-chart-alt fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Charts --}}
        <div class="row g-5 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-bottom bg-white py-3 px-3">
                        <h6 class="fw-semibold mb-0">{{ get_label('active_vs_idle_trend','Active vs Idle Trend') }}</h6>
                    </div>
                    <div class="card-body p-3">
                        <div id="activeIdleTrendChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-bottom bg-white py-3 px-3">
                        <h6 class="fw-semibold mb-0">{{ get_label('utilization_distribution','Utilization Distribution') }}</h6>
                    </div>
                    <div class="card-body p-3">
                        <div id="utilizationChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-bottom bg-white py-3 px-3">
                        <h6 class="fw-semibold mb-0">{{ get_label('attendance_trend','Attendance Trend') }}</h6>
                    </div>
                    <div class="card-body p-3">
                        <div id="attendanceTrendChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-bottom bg-white py-3 px-3">
                        <h6 class="fw-semibold mb-0">{{ get_label('time_breakdown_per_employee','Time Breakdown per Employee') }}</h6>
                    </div>
                    <div class="card-body p-3">
                        <div id="userTimeBreakdownChart"></div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Attendance Table --}}
        <div class="card border-0 shadow-sm mt-5">
            <div class="card-header border-bottom bg-white py-3 px-3">
                <h5 class="fw-semibold mb-0">{{ get_label('attendance_records','Attendance Records') }}</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <div class="row justify-content-end g-2 mb-3">
                        <div class="col-md-4 col-sm-6">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search...">
                        </div>
                    </div>


                    <table class="table-bordered table-hover mb-0 table">
                        <thead class="">
                            <tr>
                                <th>{{ get_label('user','User') }}</th>
                                <th>{{ get_label('date','Date') }}</th>
                                <th>{{ get_label('clock_in','Clock In') }}</th>
                                <th>{{ get_label('clock_out','Clock Out') }}</th>
                                <th>{{ get_label('work_time','Work Time') }}</th>
                                <th>{{ get_label('active_time','Active Time') }}</th>
                                <th>{{ get_label('manual_time','Manual Time') }}</th>
                                <th>{{ get_label('pending_manual_time','Pending Manual Time') }}</th>
                                <th>{{ get_label('break_time','Break Time') }}</th>
                                <th>{{ get_label('idle_time','Idle Time') }}</th>
                                <th>{{ get_label('utilization','Utilization') }}</th>
                                <th>{{ get_label('status','Status') }}</th>
                            </tr>
                        </thead>

                        <tbody id="attendance-body"></tbody>

                    </table>

                </div>
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mt-3 gap-3">
                    <div id="attendancePaginationInfo" class="text-muted small"></div>
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <label for="attendancePerPage" class="form-label form-label-sm mb-0 text-muted">Rows per page</label>
                            <select id="attendancePerPage" class="form-select form-select-sm w-auto">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <nav aria-label="Attendance pagination" class="ms-sm-2">
                            <ul class="pagination pagination-sm pagination-primary mb-0" id="attendancePagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="timelineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bx bx-bar-chart-alt-2 me-2"></i>{{ get_label('shift_timeline','Shift Timeline') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="timelineModalBody"></div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/daterangepicker.js') }}"></script>
    <script>
        const timeAndAttendanceDataUrl = "{{ route('time_and_attendance.data') }}";
        const attendanceTimelineUrl = "{{ route('attendance.timeline') }}";
        const attendanceUsersUrl = "{{ route('time_and_attendance.users') }}";
        const WorkDayStartTime = "{{ $workDayStartTime ?? '09:00:00' }}";
        var label_today = @json(get_label('today','Today'));
        var label_yesterday = @json(get_label('yesterday','Yesterday'));
        var label_last_7_days = @json(get_label('last_7_days','Last 7 Days'));
        var label_last_30_days = @json(get_label('last_30_days','Last 30 Days'));
        var label_this_month = @json(get_label('this_month','This Month'));
        var label_last_month = @json(get_label('last_month','Last Month'));
        var label_productive_time = @json(get_label('productive_time','Productive Time'));
        var label_idle_time = @json(get_label('idle_time','Idle Time'));
        var label_break_time = @json(get_label('break_time','Break Time'));
        var label_avg_hours_day = @json(get_label('avg_hours_days','Avg Hours/Day'));
        var label_present = @json(get_label('present','Present'));
        var label_late = @json(get_label('late','Late'));
        var label_absent = @json(get_label('absent','Absent'));
        var label_active_hours = @json(get_label('active_hours','Active Hours'));
        var label_idle_hours = @json(get_label('idle_hours','Idle Hours'));
        var label_select_users = @json(get_label('select_users','Select Users'));
    </script>
    <script src="{{ asset('assets/js/timetracker-plugin/time_and_attendance.js') }}?v={{rand()}}"></script>
@endsection
