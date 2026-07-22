let activeIdleTrendChart, utilizationChart, trendChart, userTimeBreakdownChart;
const attendanceFilterDefaults = {
    dateRange: null,
    quickRange: 'last7days'
};
const attendancePaginationState = {
    currentPage: 1,
    perPage: 10,
    totalPages: 1,
    totalRecords: 0
};


$(document).ready(function () {
    $('#date_range').daterangepicker({
        startDate: moment().subtract(6, 'days'),
        endDate: moment(),
        opens: 'right',
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
            format: 'YYYY-MM-DD'
        }
    }, function () {
        syncAttendanceDateFilterState();
        loadEmployeeOptions();
        loadAttendanceData();
    });

    const attendanceRangeInstance = $('#date_range').data('daterangepicker');
    if (attendanceRangeInstance && !attendanceFilterDefaults.dateRange) {
        attendanceFilterDefaults.dateRange = {
            start: attendanceRangeInstance.startDate.clone(),
            end: attendanceRangeInstance.endDate.clone()
        };
    }
    syncAttendanceDateFilterState(attendanceFilterDefaults.quickRange);

    initializeSelect2();
    updateAttendanceUserFilterHighlight();
    loadEmployeeOptions();
    loadAttendanceData();
    setupEventListeners();
});
// Quick date range selection
function setDateRange(period) {
    let startDate, endDate;

    switch (period) {
        case 'today':
            startDate = endDate = moment();
            break;
        case 'yesterday':
            startDate = endDate = moment().subtract(1, 'days');
            break;
        case 'last7days':
            startDate = moment().subtract(6, 'days');
            endDate = moment();
            break;
        case 'last30days':
            startDate = moment().subtract(29, 'days');
            endDate = moment();
            break;
        case 'thismonth':
            startDate = moment().startOf('month');
            endDate = moment().endOf('month');
            break;
        case 'lastmonth':
            startDate = moment().subtract(1, 'month').startOf('month');
            endDate = moment().subtract(1, 'month').endOf('month');
            break;
        default:
            console.warn('Unknown period:', period);
            return;
    }

    $('#date_range').data('daterangepicker').setStartDate(startDate);
    $('#date_range').data('daterangepicker').setEndDate(endDate);

    console.log('Quick date range set:', startDate.format('YYYY-MM-DD'), endDate.format('YYYY-MM-DD'));

    syncAttendanceDateFilterState(period);
    loadEmployeeOptions();
    loadAttendanceData();
}
$(document).on('click', '.quick-date-btn', function () {
    const period = $(this).data('range');
    setDateRange(period);
});

function setCurrentDate() {
    const now = new Date();
    $('#currentDate').text(now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }));
}




function initializeSelect2() {
    $('#employee_select').select2({
        placeholder: 'Select employees',
        allowClear: true
    });
}

function loadEmployeeOptions() {
    $.ajax({
        url: attendanceUsersUrl || (timeAndAttendanceDataUrl.replace('/data', '/users')),
        type: "GET",
        success: function (response) {
            if (response.users && Array.isArray(response.users)) {
                const $select = $('#employee_select');
                $select.html('<option value="">All Employees</option>');
                response.users.forEach((user) => {
                    $select.append(`<option value="${user.id}">${user.name}</option>`);
                });
                updateAttendanceUserFilterHighlight();
            }
        },
        error: function () {
            $('#employee_select').html('<option value="">No Employees Found</option>');
            updateAttendanceUserFilterHighlight();
        }
    });
}

function fetchTimelineData($chartContainer, userId, date) {
    if (!$chartContainer || $chartContainer.length === 0) {
        return;
    }

    $chartContainer.data('userId', userId);
    $chartContainer.data('date', date);

    if (!$chartContainer.data('loaded')) {
        $chartContainer.html('<p class="text-center text-muted mb-0">Loading timeline...</p>');
    }

    $.ajax({
        url: attendanceTimelineUrl,
        type: "GET",
        data: {
            user_id: userId,
            date: date
        },
        success: function (response) {
            const shifts = Array.isArray(response.shifts) ? response.shifts : [];
            renderTimelineChart($chartContainer[0], shifts);
            $chartContainer.data('loaded', true);
        },
        error: function () {
            if (!$chartContainer.data('loaded')) {
                $chartContainer.html('<p class="text-danger text-center mb-0">Failed to load timeline data.</p>');
            }
        }
    });
}

function setupEventListeners() {
    $('#searchInput').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('#attendance-body tr.clickable-row').each(function () {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(value));
        });
    });

    $(document).on('click', '.clickable-row', function () {
        const $row = $(this);
        const userId = $row.data('user-id');
        const date = $row.data('date');
        const employeeName = $row.data('employee-name') || '';

        const $modal = $('#timelineModal');
        const modalBody = $('#timelineModalBody');
        modalBody.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 text-muted">Loading timeline...</p></div>');
        modalBody.removeData('compactMode').removeData('apexchart');

        $modal.find('.modal-title').html(`<i class="bx bx-bar-chart-alt-2 me-2"></i>${employeeName} — ${date}`);

        const modal = new bootstrap.Modal(document.getElementById('timelineModal'));
        modal.show();

        fetchTimelineData(modalBody, userId, date);
    });

    $('#fetch_data').on('click', function () {
        attendancePaginationState.currentPage = 1;
        loadAttendanceData();
    });
    $('#employee_select').on('change', function () {
        updateAttendanceUserFilterHighlight();
        attendancePaginationState.currentPage = 1;
        loadAttendanceData();
    });

    $(document).on('click', '#attendancePagination .page-link', function (e) {
        e.preventDefault();
        const page = Number($(this).data('page'));
        if (!page || page < 1 || page > attendancePaginationState.totalPages || page === attendancePaginationState.currentPage) {
            return;
        }
        attendancePaginationState.currentPage = page;
        loadAttendanceData();
    });

    $(document).on('change', '#attendancePerPage', function () {
        const perPage = parseInt($(this).val(), 10) || 10;
        attendancePaginationState.perPage = perPage;
        attendancePaginationState.currentPage = 1;
        loadAttendanceData();
    });

    $('#timelineModal').on('hidden.bs.modal', function () {
        const modalBody = $('#timelineModalBody');
        const chart = modalBody.data('apexchart');
        if (chart) {
            chart.destroy();
            modalBody.removeData('apexchart');
        }
        modalBody.empty();
    });

    $(document).on('click', '.tt-compact-toggle', function () {
        const $btn = $(this);
        const targetId = $btn.data('target');
        if (!targetId) {
            return;
        }
        const $target = $('#' + targetId);
        if ($target.length === 0) {
            return;
        }
        const current = $target.data('compactMode') === true;
        $target.data('compactMode', !current);
        fetchTimelineData($target, $btn.data('user'), $btn.data('date'));
    });
}

function renderTimelineChart(container, shifts) {
    if (!container) return;
    const $container = $(container);

    const existingChart = $container.data('apexchart');
    if (existingChart) {
        existingChart.destroy();
        $container.removeData('apexchart');
    }

    if (!Array.isArray(shifts) || shifts.length === 0) {
        $container.html('<p class="text-center text-muted mb-0">No shift data available for this date.</p>');
        return;
    }

    const colorMap = {
        active: '#4CAF50',
        manual: '#2196F3',
        idle: '#FFC107',
        break: '#F44336',
        pending_manual: '#957df5',
        pending: '#FF9800',
        neutral: '#90A4AE'
    };

    const statusMetaMap = {
        completed: { class: 'tt-status-badge--completed', icon: 'bx bx-check-circle', label: 'Completed' },
        ongoing: { class: 'tt-status-badge--ongoing', icon: 'bx bx-time-five', label: 'Ongoing' },
        incomplete: { class: 'tt-status-badge--incomplete', icon: 'bx bx-error-circle', label: 'Incomplete' }
    };

    const compactMode = $container.data('compactMode') === true;

    const totals = shifts.reduce((acc, shift) => {
        const summary = shift.summary || {};
        acc.work += shift.duration_seconds || 0;
        acc.active += summary.active_seconds || 0;
        acc.manual += summary.manual_seconds || 0;
        acc.pending += summary.pending_manual_seconds || 0;
        acc.break += summary.break_seconds || 0;
        acc.idle += summary.idle_seconds || 0;
        return acc;
    }, { work: 0, active: 0, manual: 0, pending: 0, break: 0, idle: 0 });

    const totalWorkSeconds = totals.work;

    const daySummaryHtml = `
        <div class="tt-day-summary">
            <div class="tt-day-summary__totals">
                <span><i class="bx bx-time-five me-1"></i>Total Work: <strong>${secondsToHHMM(totalWorkSeconds)}</strong></span>
                <span><i class="bx bx-check-circle me-1"></i>Active: <strong>${secondsToHHMM(totals.active)}</strong></span>
                <span><i class="bx bx-moon me-1"></i>Idle: <strong>${secondsToHHMM(totals.idle)}</strong></span>
            </div>
        </div>
    `;

    const chartId = `timelineChart-${Date.now()}-${Math.round(Math.random() * 10000)}`;
    const chartContainerId = $container.attr('id') || `timeline-container-${chartId}`;
    $container.attr('id', chartContainerId);
    const toggleIcon = compactMode ? 'bx bx-expand-alt' : 'bx bx-collapse-alt';
    const toggleLabel = compactMode ? 'Expand details' : 'Compact view';
    const compactButtonHtml = `
        <button type="button"
                class="btn btn-sm btn-outline-secondary tt-compact-toggle"
                data-target="${chartContainerId}"
                data-user="${$container.data('userId')}"
                data-date="${$container.data('date')}">
            <i class="${toggleIcon} me-1"></i>${toggleLabel}
        </button>
    `;

    const toolbarHtml = `
        <div class="tt-shift-toolbar">
            <div class="tt-day-summary">
                <div class="tt-day-summary__totals">
                    <span><i class="bx bx-time-five me-1"></i>Total Work: <strong>${secondsToHHMM(totalWorkSeconds)}</strong></span>
                    <span><i class="bx bx-check-circle me-1"></i>Active: <strong>${secondsToHHMM(totals.active)}</strong></span>
                    <span><i class="bx bx-moon me-1"></i>Idle: <strong>${secondsToHHMM(totals.idle)}</strong></span>
                </div>
            </div>
            <div class="tt-shift-toolbar__actions">
                ${compactButtonHtml}
            </div>
        </div>
    `;

    const shiftCardsHtml = shifts.map((shift, index) => {
        const summary = shift.summary || {};
        const metrics = [
            { key: 'active', label: 'Active', value: summary.active_seconds || 0 },
            { key: 'manual', label: 'Manual', value: summary.manual_seconds || 0 },
            { key: 'pending', label: 'Pending Manual', value: summary.pending_manual_seconds || 0 },
            { key: 'break', label: 'Break', value: summary.break_seconds || 0 },
            { key: 'idle', label: 'Idle', value: summary.idle_seconds || 0 }
        ];

        const metricsHtml = `
            <div class="tt-shift-card__metrics">
                ${metrics.map(metric => `
                    <div class="tt-shift-metric tt-shift-metric--${metric.key}">
                        <span class="tt-shift-metric__label">${metric.label}</span>
                        <span class="tt-shift-metric__value">${secondsToHHMM(metric.value)}</span>
                    </div>
                `).join('')}
            </div>
        `;

        let compactMetrics = metrics.filter(metric => metric.value > 0);
        if (!compactMetrics.length) {
            compactMetrics = [metrics[0]];
        }
        const compactHtml = `
            <div class="tt-shift-card__compact">
                ${compactMetrics.map(metric => `
                    <span class="tt-compact-pill tt-compact-pill--${metric.key}">
                        <span>${metric.label}</span>
                        <strong>${secondsToHHMM(metric.value)}</strong>
                    </span>
                `).join('')}
            </div>
        `;

        const statusKey = (shift.status || '').toLowerCase();
        const statusMeta = statusMetaMap[statusKey] || statusMetaMap.completed;
        const cardClass = compactMode ? 'tt-shift-card tt-shift-card--compact' : 'tt-shift-card';

        return `
            <div class="${cardClass}">
                <div class="tt-shift-card__header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="tt-shift-card__title">Shift ${index + 1}</span>
                        <span class="tt-status-badge ${statusMeta.class}">
                            <i class="${statusMeta.icon}"></i>
                            ${statusMeta.label}
                        </span>
                    </div>
                    <div class="tt-shift-card__time-range">
                        <i class="bx bx-time-five me-1"></i>${shift.clock_in_display || '--'} &mdash; ${shift.clock_out_display || '--'}
                    </div>
                </div>
                ${compactMode ? compactHtml : metricsHtml}
            </div>
        `;
    }).join('');

    $container.html(`
        ${toolbarHtml}
        <div class="tt-shift-card-stack ${compactMode ? 'is-compact' : ''}">
            ${shiftCardsHtml}
        </div>
        <div class="tt-chart-wrapper">
            <div id="${chartId}" class="tt-chart"></div>
        </div>
    `);

    const seriesData = [];

    shifts.forEach((shift, index) => {
        const label = `Shift ${index + 1}`;
        const segments = Array.isArray(shift.segments) ? shift.segments : [];

        segments.forEach(segment => {
            let startTime = Date.parse(segment.start);
            let endTime = Date.parse(segment.end);

            if (isNaN(startTime) || isNaN(endTime) || endTime <= startTime) {
                return;
            }

            const type = segment.type || 'neutral';

            seriesData.push({
                x: label,
                y: [startTime, endTime],
                fillColor: colorMap[type] || colorMap.neutral,
                data: {
                    type,
                    shiftLabel: label,
                    durationSeconds: segment.duration_seconds || Math.round((endTime - startTime) / 1000),
                    start: startTime,
                    end: endTime
                }
            });
        });
    });

    if (!seriesData.length) {
        $container.find(`#${chartId}`).html('<p class="text-center text-muted mb-0">No segment data available for this date.</p>');
        return;
    }

    const times = seriesData.flatMap(item => item.y);
    const minTime = Math.min.apply(null, times);
    const maxTime = Math.max.apply(null, times);
    const hasOngoingShift = shifts.some(shift => (shift.status || '').toLowerCase() === 'ongoing');

    let annotations;
    if (hasOngoingShift) {
        const now = Date.now();
        if (now >= minTime && now <= maxTime) {
            annotations = {
                xaxis: [{
                    x: now,
                    strokeDashArray: 4,
                    borderColor: '#FFB300',
                    opacity: 0.85,
                    label: {
                        borderColor: '#FFB300',
                        style: {
                            color: '#8a6d1d',
                            background: '#FFECB3',
                            fontSize: '0.65rem'
                        },
                        text: 'Now'
                    }
                }]
            };
        }
    }

    const chartHeight = Math.max(compactMode ? 190 : 230, shifts.length * (compactMode ? 120 : 160));

    const options = {
        chart: {
            type: 'rangeBar',
            height: chartHeight,
            toolbar: { show: true },
            animations: { enabled: true }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '55%',
                borderRadius: 6
            }
        },
        series: [{
            data: seriesData
        }],
        xaxis: {
            type: 'datetime',
            labels: {
                datetimeUTC: false,
                format: 'hh:mm TT'
            },
            axisTicks: { show: true }
        },
        yaxis: {
            labels: {
                style: {
                    fontSize: '0.8rem',
                    fontWeight: 500,
                    colors: '#273244'
                }
            }
        },
        dataLabels: {
            enabled: !compactMode,
            formatter: function (val, opts) {
                const seriesIndex = opts.seriesIndex;
                const dataPointIndex = opts.dataPointIndex;
                const seriesItem = opts.w.config.series?.[seriesIndex]?.data?.[dataPointIndex];
                const meta = seriesItem?.data || {};
                const seconds = meta.durationSeconds || Math.round(((seriesItem?.y?.[1] || 0) - (seriesItem?.y?.[0] || 0)) / 1000);
                return secondsToHHMM(seconds);
            },
            style: {
                fontSize: '0.72rem',
                fontWeight: 600
            }
        },
        tooltip: {
            custom: ({ series, seriesIndex, dataPointIndex, w }) => {
                const data = w.config.series[seriesIndex].data[dataPointIndex];
                const meta = data.data || {};
                const start = new Date(meta.start || data.y[0]);
                const end = new Date(meta.end || data.y[1]);
                const typeLabel = meta.type ? meta.type.charAt(0).toUpperCase() + meta.type.slice(1).replace('_', ' ') : 'Segment';
                return `
                    <div class="p-2">
                        <div class="fw-semibold">${typeLabel}</div>
                        <div class="small text-muted">${start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} - ${end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                        <div class="small">Duration: ${secondsToHHMM(meta.durationSeconds || 0)}</div>
                        <div class="small text-muted">${meta.shiftLabel || ''}</div>
                    </div>
                `;
            }
        },
        colors: seriesData.map(item => item.fillColor),
        fill: {
            opacity: 0.95,
            colors: seriesData.map(item => item.fillColor)
        },
        grid: {
            borderColor: 'rgba(17, 24, 39, 0.08)',
            strokeDashArray: 3
        },
        legend: {
            show: false
        },
        annotations: annotations
    };

    const chartElement = document.getElementById(chartId);
    if (!chartElement) {
        return;
    }

    const chart = new ApexCharts(chartElement, options);
    chart.render();
    $container.data('apexchart', chart);
}

function loadAttendanceData() {
    const dateRange = $('#date_range').data('daterangepicker');
    const startDate = dateRange ? dateRange.startDate.format('YYYY-MM-DD') : '2025-06-10';
    const endDate = dateRange ? dateRange.endDate.format('YYYY-MM-DD') : '2025-06-26';
    const employees = $('#employee_select').val();

    $.ajax({
        url: timeAndAttendanceDataUrl,
        data: {
            start_date: startDate,
            end_date: endDate,
            user_id: employees ? employees : '',
            page: attendancePaginationState.currentPage,
            per_page: attendancePaginationState.perPage
        },
        type: "GET",
        beforeSend: function () {
            $('#attendance-body').html(
                `<tr><td colspan="12" class="text-center text-muted">Loading attendance data...</td></tr>`
            );
        },
        success: function (response) {
            if (response.data && Array.isArray(response.data)) {
                updateStatistics(response.summary || {});
                updateCharts(response.chart_data || response.data);
                updateTable(response.data);
                if (response.pagination) {
                    attendancePaginationState.currentPage = response.pagination.current_page || 1;
                    attendancePaginationState.perPage = response.pagination.per_page || attendancePaginationState.perPage;
                    attendancePaginationState.totalPages = response.pagination.total_pages || 1;
                    attendancePaginationState.totalRecords = response.pagination.total_records || response.data.length;
                } else {
                    attendancePaginationState.totalPages = 1;
                    attendancePaginationState.totalRecords = response.data.length;
                    attendancePaginationState.currentPage = 1;
                }
                renderAttendancePagination();
            } else {
                $('#attendance-body').html(
                    `<tr><td colspan="12" class="text-center text-danger">Invalid data received from server.</td></tr>`
                );
            }
        },
        error: function () {
            $('#attendance-body').html(
                `<tr><td colspan="12" class="text-center text-danger">Failed to load data. Please refresh.</td></tr>`
            );
        }
    });
}

function updateStatistics(summary) {
    $('#total_employees').text(summary.total_employees || 0);
    $('#total_records').text(summary.total_records || 0);
    $('#total_work_hours').text(summary.total_work_hours || '00:00');
    $('#total_break_time').text(summary.total_break_time || '00:00');
    $('#total_idle_time').text(summary.total_idle_time || '00:00');
    $('#avgUtilization').text(summary.average_utilization ? summary.average_utilization : '0%');
}

function updateActiveIdleTrendChart(data) {
    const dateMap = {};
    data.forEach(item => {
        if (!dateMap[item.date]) dateMap[item.date] = {
            active: 0,
            idle: 0
        };
        dateMap[item.date].active += toMinutes(item.active_time || '00:00');
        dateMap[item.date].idle += toMinutes(item.idle_time || '00:00');
    });

    const dates = Object.keys(dateMap).sort();
    const activeData = dates.map(date => +(dateMap[date].active / 60).toFixed(2));
    const idleData = dates.map(date => +(dateMap[date].idle / 60).toFixed(2));

    const options = {
        series: [{
            name: 'Active Hours',
            data: activeData
        },
        {
            name: 'Idle Hours',
            data: idleData
        }
        ],
        chart: {
            type: 'area',
            height: 350,
            toolbar: {
                show: false
            }
        },
        colors: ['#4CAF50', '#FFC107'],
        stroke: {
            curve: 'smooth',
            width: 3
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: dates.map(date => new Date(date).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            })),
            title: {
                text: 'Date'
            }
        },
        yaxis: {
            title: {
                text: 'Hours'
            },
            min: 0,
            labels: {
                formatter: val => decimalToHHMM(val)
            }
        },
        legend: {
            position: 'top'
        },
        tooltip: {
            y: {
                formatter: val => decimalToHHMM(val)
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        }
    };

    if (activeIdleTrendChart) activeIdleTrendChart.destroy();
    activeIdleTrendChart = new ApexCharts(document.querySelector("#activeIdleTrendChart"), options);
    activeIdleTrendChart.render();
}

function updateCharts(data) {
    updateActiveIdleTrendChart(data);

    const utilizationMap = {};
    data.forEach(item => {
        if (!utilizationMap[item.employee]) {
            utilizationMap[item.employee] = { productive: 0, idle: 0, break: 0, count: 0 };
        }
        utilizationMap[item.employee].productive += toMinutes(item.active_time || '00:00');
        utilizationMap[item.employee].idle += toMinutes(item.idle_time || '00:00');
        utilizationMap[item.employee].break += toMinutes(item.break_time || '00:00');
        utilizationMap[item.employee].count++;
    });

    const employees = Object.keys(utilizationMap);
    const productive = employees.map(u => {
        const avg = utilizationMap[u].count ? utilizationMap[u].productive / utilizationMap[u].count / 60 : 0;
        return +(avg.toFixed(2));
    });
    const idle = employees.map(u => {
        const avg = utilizationMap[u].count ? utilizationMap[u].idle / utilizationMap[u].count / 60 : 0;
        return +(avg.toFixed(2));
    });
    const breakT = employees.map(u => {
        const avg = utilizationMap[u].count ? utilizationMap[u].break / utilizationMap[u].count / 60 : 0;
        return +(avg.toFixed(2));
    });

    const utilizationSeries = [
        {
            name: 'Productive',
            data: employees.map((employee, idx) => ({ x: employee, y: productive[idx] }))
        },
        {
            name: 'Idle',
            data: employees.map((employee, idx) => ({ x: employee, y: idle[idx] }))
        },
        {
            name: 'Break',
            data: employees.map((employee, idx) => ({ x: employee, y: breakT[idx] }))
        }
    ];

    const utilOptions = {
        series: utilizationSeries,
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            toolbar: { show: false }
        },
        colors: ['#4CAF50', '#FFC107', '#F44336'],
        xaxis: {
            title: { text: 'Avg Hours/Day' },
            labels: {
                formatter: val => decimalToHHMM(val)
            }
        },
        yaxis: {
            title: { text: 'Employees' }
        },
        plotOptions: {
            bar: {
                borderRadius: 6,
                horizontal: true
            }
        },
        dataLabels: {
            enabled: true,
            formatter: val => decimalToHHMM(val)
        },
        legend: { position: 'top' },
        tooltip: {
            y: {
                formatter: val => decimalToHHMM(val)
            }
        }
    };

    if (utilizationChart) utilizationChart.destroy();
    utilizationChart = new ApexCharts(document.querySelector("#utilizationChart"), utilOptions);
    utilizationChart.render();

    const groupByDate = {};
    data.forEach(item => {
        if (!groupByDate[item.date]) groupByDate[item.date] = { present: 0, late: 0, absent: 0 };
        if (item.clock_in === '--') {
            groupByDate[item.date].absent++;
        } else {
            const ci = new Date(`${item.date} ${convertTo24Hour(item.clock_in)}`);
            const std = new Date(`${item.date} ` + WorkDayStartTime);
            ci > std ? groupByDate[item.date].late++ : groupByDate[item.date].present++;
        }
    });

    const dates = Object.keys(groupByDate).sort();
    const trendOptions = {
        series: [
            { name: 'Present', data: dates.map(d => groupByDate[d].present) },
            { name: 'Late', data: dates.map(d => groupByDate[d].late) },
            { name: 'Absent', data: dates.map(d => groupByDate[d].absent) }
        ],
        chart: {
            type: 'area',
            height: 350,
            toolbar: { show: false }
        },
        colors: ['#4CAF50', '#FFC107', '#F44336'],
        stroke: {
            curve: 'smooth',
            width: 3
        },
        dataLabels: { enabled: false },
        xaxis: {
            categories: dates.map(date => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }))
        },
        yaxis: {
            title: { text: 'Employees' }
        },
        legend: { position: 'top' }
    };

    if (trendChart) trendChart.destroy();
    trendChart = new ApexCharts(document.querySelector("#attendanceTrendChart"), trendOptions);
    trendChart.render();

    const breakdownOptions = {
        series: [
            { name: 'Productive', data: productive },
            { name: 'Idle', data: idle },
            { name: 'Break', data: breakT }
        ],
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            toolbar: { show: false }
        },
        colors: ['#4CAF50', '#FFC107', '#F44336'],
        xaxis: { categories: employees },
        yaxis: {
            title: { text: 'Avg Hours/Day' },
            labels: {
                formatter: val => decimalToHHMM(val)
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 6,
                horizontal: false,
                columnWidth: '55%',
                dataLabels: {
                    total: {
                        enabled: true,
                        formatter: val => decimalToHHMM(val)
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: { position: 'top' },
        tooltip: {
            y: {
                formatter: val => decimalToHHMM(val)
            }
        }
    };

    if (userTimeBreakdownChart) userTimeBreakdownChart.destroy();
    userTimeBreakdownChart = new ApexCharts(document.querySelector("#userTimeBreakdownChart"), breakdownOptions);
    userTimeBreakdownChart.render();
}

function toMinutes(timeStr) {
    if (!timeStr || timeStr === '--' || typeof timeStr !== 'string' || !timeStr.includes(':')) return 0;
    const [h, m] = timeStr.split(':').map(Number);
    return h * 60 + m;
}

function updateTable(data) {
    let html = '';
    if (data.length > 0) {
        // Debug: Log first entry to see all available fields
        if (data[0]) {
            console.log('First entry data:', data[0]);
            console.log('Available keys:', Object.keys(data[0]));
        }
        data.forEach(entry => {
            const status = getAttendanceStatus(entry);
            const statusClass = status === 'Present' ? 'bg-label-success' :
                status === 'Late' ? 'bg-label-warning' : 'bg-label-danger';

            let actionsTd = '';
            if (typeof isAdmin !== 'undefined' && isAdmin) {
                let forceBtn = '';
                if (entry.has_ongoing_shift || entry.status === 'Active' || entry.clock_out === '--') {
                    forceBtn = `<button type="button" class="btn btn-xs btn-danger force-clockout-btn" data-user-id="${entry.user_id}" data-date="${entry.date}" title="Force Clock-out"><i class="bx bx-power-off me-1"></i>Force Clock-out</button>`;
                } else {
                    forceBtn = `<span class="text-muted small">--</span>`;
                }
                actionsTd = `<td>${forceBtn}</td>`;
            }

            html += `
            <tr class="clickable-row" data-user-id="${entry.user_id}" data-date="${entry.date}" data-employee-name="${entry.employee}">
                <td><strong>${entry.employee}</strong></td>
                <td>${entry.date}</td>
                <td>${entry.clock_in}</td>
                <td>${entry.clock_out}</td>
                <td>${entry.work_time}</td>
                <td>${entry.active_time}</td>
                <td>${entry.manual_time}</td>
                <td>${entry.pending_manual_time || '--'}</td>
                <td>${entry.break_time}</td>
                <td>${entry.idle_time}</td>
                <td>${entry.utilization}</td>
                <td><span class="badge ${statusClass}">${status}</span></td>
                ${actionsTd}
            </tr>`;
        });
    } else {
        const colSpan = (typeof isAdmin !== 'undefined' && isAdmin) ? 13 : 12;
        html =
            `<tr><td colspan="${colSpan}" class="text-center">No attendance records found for the selected filters.</td></tr>`;
    }
    $('#attendance-body').html(html);
}

$(document).on('click', '.force-clockout-btn', function (e) {
    e.stopPropagation();
    const userId = $(this).data('user-id');
    const date = $(this).data('date');
    if (confirm('Are you sure you want to force clock-out this employee?')) {
        $.ajax({
            url: forceClockoutUrl,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                user_id: userId,
                date: date
            },
            success: function (res) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message || 'User clocked out successfully');
                } else {
                    alert(res.message || 'User clocked out successfully');
                }
                loadAttendanceData();
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Error performing force clock-out';
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                } else {
                    alert(msg);
                }
            }
        });
    }
});

function getAttendanceStatus(entry) {
    if (entry.clock_in === '--') return 'Absent';
    const ci = new Date(`${entry.date} ${convertTo24Hour(entry.clock_in)}`);
    const std = new Date(`${entry.date} ` + WorkDayStartTime);
    return ci > std ? 'Late' : 'Present';
}

function convertTo24Hour(timeStr) {
    if (!timeStr || timeStr === '--') return '00:00:00';
    const match = timeStr.match(/(\d{1,2}):(\d{2})\s*(AM|PM)?/i);
    if (!match) return timeStr;
    let [, hours, minutes, period] = match;
    hours = parseInt(hours, 10);
    if (period) {
        period = period.toUpperCase();
        if (period === 'PM' && hours < 12) hours += 12;
        if (period === 'AM' && hours === 12) hours = 0;
    }
    return `${hours.toString().padStart(2, '0')}:${minutes}:00`;
}

function exportToCSV() {
    $.ajax({
        url: timeAndAttendanceDataUrl,
        type: "GET",
        data: {
            start_date: $('#date_range').data('daterangepicker') ? $('#date_range').data('daterangepicker')
                .startDate.format('YYYY-MM-DD') : '',
            end_date: $('#date_range').data('daterangepicker') ? $('#date_range').data('daterangepicker')
                .endDate.format('YYYY-MM-DD') : '',
            employee_id: $('#employee_select').val() ? $('#employee_select').val() : ''
        },
        success: function (response) {
            const data = response.data;
            const csvContent = "data:text/csv;charset=utf-8," +
                "Employee,Date,Clock In,Clock Out,Work Time,Active Time,Break Time,Utilization,Status,Manual Time,Ideal Time\n" +
                data.map(row =>
                    `${row.employee},${row.date},${row.clock_in},${row.clock_out},${row.work_time},${row.active_time},${row.break_time},${row.utilization},${row.status},${row.manual_time},${row.ideal_time}`
                ).join("\n");
            const link = document.createElement("a");
            link.setAttribute("href", encodeURI(csvContent));
            link.setAttribute("download", "attendance_report.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
}

function decimalToHHMM(decimal) {
    const totalMinutes = Math.round(decimal * 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
}

function secondsToHHMM(totalSeconds) {
    const seconds = Math.max(0, Math.round(totalSeconds || 0));
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
}

function renderAttendancePagination() {
    const info = $('#attendancePaginationInfo');
    const pagination = $('#attendancePagination');
    const { currentPage, perPage, totalPages, totalRecords } = attendancePaginationState;

    if (totalRecords === 0) {
        info.text('No records to display');
        pagination.empty();
        return;
    }

    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(start + perPage - 1, totalRecords);
    info.text(`Showing ${start} - ${end} of ${totalRecords} records`);

    $('#attendancePerPage').val(perPage);

    const createPageItem = (label, page, disabled = false, active = false) => {
        const disabledClass = disabled ? ' disabled' : '';
        const activeClass = active ? ' active' : '';
        let display = label;
        let ariaLabel = `Page ${label}`;
        if (label === '&laquo;') {
            display = '<i class="bx bx-chevron-left"></i>';
            ariaLabel = 'Previous';
        } else if (label === '&raquo;') {
            display = '<i class="bx bx-chevron-right"></i>';
            ariaLabel = 'Next';
        }
        pagination.append(`<li class="page-item${disabledClass}${activeClass}"><a class="page-link" href="#" data-page="${page}" aria-label="${ariaLabel}">${display}</a></li>`);
    };

    pagination.empty();
    createPageItem('&laquo;', currentPage - 1, currentPage === 1);

    const windowSize = 2;
    let startPage = Math.max(1, currentPage - windowSize);
    let endPage = Math.min(totalPages, currentPage + windowSize);

    if (startPage > 1) {
        createPageItem(1, 1, false, currentPage === 1);
        if (startPage > 2) {
            pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        createPageItem(i, i, false, currentPage === i);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
        createPageItem(totalPages, totalPages, false, currentPage === totalPages);
    }

    createPageItem('&raquo;', currentPage + 1, currentPage === totalPages);
}

function syncAttendanceDateFilterState(quickKeyOverride) {
    const picker = $('#date_range').data('daterangepicker');
    if (!picker) {
        return;
    }

    const start = picker.startDate.clone();
    const end = picker.endDate.clone();

    if (!attendanceFilterDefaults.dateRange) {
        attendanceFilterDefaults.dateRange = {
            start: start.clone(),
            end: end.clone()
        };
    }

    const quickKey = typeof quickKeyOverride !== 'undefined'
        ? quickKeyOverride
        : detectAttendanceQuickKey(start, end);

    const isDefaultSelection = attendanceFilterDefaults.dateRange
        && start.isSame(attendanceFilterDefaults.dateRange.start, 'day')
        && end.isSame(attendanceFilterDefaults.dateRange.end, 'day');

    toggleFilterWrapper('attendance-date-range', !isDefaultSelection);
    setActiveQuickChip('attendance-date-range', quickKey);
}

function detectAttendanceQuickKey(start, end) {
    if (!start || !end) {
        return null;
    }

    const today = moment();
    const yesterday = moment().subtract(1, 'days');
    const lastSevenStart = moment().subtract(6, 'days');
    const lastThirtyStart = moment().subtract(29, 'days');
    const thisMonthStart = moment().startOf('month');
    const thisMonthEnd = moment().endOf('month');
    const lastMonthStart = moment().subtract(1, 'month').startOf('month');
    const lastMonthEnd = moment().subtract(1, 'month').endOf('month');

    if (start.isSame(today, 'day') && end.isSame(today, 'day')) {
        return 'today';
    }
    if (start.isSame(yesterday, 'day') && end.isSame(yesterday, 'day')) {
        return 'yesterday';
    }
    if (start.isSame(lastSevenStart, 'day') && end.isSame(today, 'day')) {
        return 'last7days';
    }
    if (start.isSame(lastThirtyStart, 'day') && end.isSame(today, 'day')) {
        return 'last30days';
    }
    if (start.isSame(thisMonthStart, 'day') && end.isSame(thisMonthEnd, 'day')) {
        return 'thismonth';
    }
    if (start.isSame(lastMonthStart, 'day') && end.isSame(lastMonthEnd, 'day')) {
        return 'lastmonth';
    }

    return null;
}

function updateAttendanceUserFilterHighlight() {
    const values = $('#employee_select').val();
    const selectedCount = Array.isArray(values) ? values.filter(Boolean).length : 0;
    const totalOptions = $('#employee_select option').filter(function () {
        return $(this).val();
    }).length;

    const isActive = selectedCount > 0 && (totalOptions === 0 || selectedCount < totalOptions);

    toggleFilterWrapper('attendance-users', isActive);
}

function toggleFilterWrapper(filterKey, isActive) {
    const $wrapper = $(`[data-hp-filter="${filterKey}"]`);
    if ($wrapper.length === 0) {
        return;
    }
    $wrapper.toggleClass('filter-active', Boolean(isActive));
}

function setActiveQuickChip(filterKey, identifier) {
    const $chips = $(`[data-hp-filter-chip="${filterKey}"]`);
    if ($chips.length === 0) {
        return;
    }

    $chips.removeClass('filter-active');

    if (!identifier) {
        return;
    }

    $chips.each(function () {
        const $chip = $(this);
        const rangeKey = $chip.data('range');
        const presetKey = $chip.data('preset');

        if ((rangeKey && rangeKey === identifier) || (presetKey && presetKey === identifier)) {
            $chip.addClass('filter-active');
        }
    });
}
