'use strict';

function queryParamsLeaveBalances(params) {
    return {
        search: params.search,
        limit: params.limit,
        offset: params.offset,
        order: params.order,
        sort: params.sort,
        date_from: $('#lb_date_range_from').val() || '',
        date_to: $('#lb_date_range_to').val() || '',
        balance_status: $('#lb_status_filter').val(),
        user_ids: $('#lb_member_filter').val(),
    };
}

$(document).ready(function () {
    var $table = $('#leave_balances_table');

    if (!$table.length) {
        return;
    }

    // Initialize date range picker
    if (typeof initAdvancedDateRangePicker !== 'undefined') {
        var currentYear = typeof get_current_company_year !== 'undefined' ? get_current_company_year() : new Date().getFullYear();
        var yearDates = typeof get_company_year_dates !== 'undefined' ? get_company_year_dates(currentYear) : null;

        var defaultStart, defaultEnd;
        if (yearDates) {
            defaultStart = moment(yearDates.start);
            defaultEnd = moment(yearDates.end);
        } else {
            // Fallback to calendar year
            defaultStart = moment().startOf('year');
            defaultEnd = moment().endOf('year');
        }

        initAdvancedDateRangePicker({
            selector: '#lb_date_range',
            hiddenFrom: '#lb_date_range_from',
            hiddenTo: '#lb_date_range_to',
            tableId: 'leave_balances_table',
            callback: function (start, end, label) {
                // Refresh table when date range changes
                $table.bootstrapTable('refresh', { silent: true });
            }
        });

        // Set default to current company year if fields are empty
        var existingFrom = $('#lb_date_range_from').val();
        var existingTo = $('#lb_date_range_to').val();

        if (existingFrom && existingTo) {
            // Use existing values from server
            var picker = $('#lb_date_range').data('daterangepicker');
            if (picker) {
                picker.setStartDate(moment(existingFrom));
                picker.setEndDate(moment(existingTo));
                var dateFormat = picker.locale.format || window.js_date_format || 'YYYY-MM-DD';
                $('#lb_date_range').val(
                    moment(existingFrom).format(dateFormat) +
                    ' - ' +
                    moment(existingTo).format(dateFormat)
                );
            }
        } else {
            // Set default to current company year
            $('#lb_date_range_from').val(defaultStart.format('YYYY-MM-DD'));
            $('#lb_date_range_to').val(defaultEnd.format('YYYY-MM-DD'));
            var dateFormat = window.js_date_format || 'YYYY-MM-DD';
            $('#lb_date_range').val(
                defaultStart.format(dateFormat) +
                ' - ' +
                defaultEnd.format(dateFormat)
            );

            // Update picker with default dates
            var picker = $('#lb_date_range').data('daterangepicker');
            if (picker) {
                picker.setStartDate(defaultStart);
                picker.setEndDate(defaultEnd);
            }
        }
    }

    var themeConfig = typeof config !== 'undefined' ? config : {
        colors: {
            primary: '#696cff',
            success: '#71dd37',
            warning: '#ffab00',
            axisColor: '#a1acb8',
            borderColor: '#e9ecef',
            white: '#fff'
        }
    };

    var leaveBalancesChartEl = document.querySelector('#leaveBalancesStackedChart');
    var $chartEmptyState = $('#leaveBalancesChartEmptyState');
    var trendChartEl = document.querySelector('#leaveBalancesTrendSparkline');

    var leaveBalancesChartInstance = null;
    var leaveBalancesTrendInstance = null;

    var seriesVisibility = {
        used_paid_leaves: true,
        remaining_paid_leaves: true,
        unpaid_leaves_taken: true
    };

    var seriesLabels = {
        used_paid_leaves: typeof label_used_paid_leaves !== 'undefined' ? label_used_paid_leaves : 'Used Paid Leaves',
        remaining_paid_leaves: typeof label_remaining_paid_leaves !== 'undefined' ? label_remaining_paid_leaves : 'Remaining Paid Leaves',
        unpaid_leaves_taken: typeof label_unpaid_leaves !== 'undefined' ? label_unpaid_leaves : 'Unpaid Leaves'
    };

    var debugEnabled = true;
    function logChart(event, details) {
        if (!debugEnabled || typeof console === 'undefined') {
            return;
        }
        var groupLabel = '[leave-balances] ' + event;
        if (typeof console.groupCollapsed === 'function' && typeof console.groupEnd === 'function') {
            console.groupCollapsed(groupLabel);
            if (details !== undefined) {
                console.log(details);
            }
            console.groupEnd();
        } else {
            if (details !== undefined) {
                console.log(groupLabel, details);
            } else {
                console.log(groupLabel);
            }
        }
    }

    function initTooltips() {
        $('[data-bs-toggle="tooltip"]').each(function () {
            var existing = bootstrap.Tooltip.getInstance(this);
            if (existing) {
                existing.dispose();
            }
            new bootstrap.Tooltip(this);
        });
    }

    function formatNumber(value, decimals) {
        var number = parseFloat(value);
        if (isNaN(number)) {
            number = 0;
        }
        var fractionDigits = typeof decimals === 'number' ? decimals : (number % 1 === 0 ? 0 : 2);
        return number.toLocaleString(undefined, {
            minimumFractionDigits: fractionDigits,
            maximumFractionDigits: fractionDigits
        });
    }

    function formatPercent(value, decimals) {
        return formatNumber(value, typeof decimals !== 'undefined' ? decimals : 1) + '%';
    }

    function formatDays(value, decimals) {
        return formatNumber(value, typeof decimals !== 'undefined' ? decimals : 2) + ' ' + (typeof label_days !== 'undefined' ? label_days : 'Days');
    }

    function formatDateTime(dateString) {
        if (!dateString) {
            return '--';
        }
        var date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return '--';
        }
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    function updateSummary(summary) {
        if (!summary) {
            return;
        }

        $('[data-summary-key="member_count"]').text(formatNumber(summary.member_count, 0));
        $('[data-summary-key="total_annual_allocation"]').text(formatNumber(summary.total_annual_allocation, 2));
        $('[data-summary-key="total_accrued_allocation"]').text(formatNumber(summary.total_accrued_allocation, 2));
        $('[data-summary-key="total_used"]').text(formatNumber(summary.total_used, 2));
        $('[data-summary-key="total_accrued_remaining"]').text(formatNumber(summary.total_accrued_remaining, 2));
        $('[data-summary-key="total_annual_remaining"]').text(formatNumber(summary.total_annual_remaining, 2));
        if (typeof summary.total_unpaid_taken !== 'undefined') {
            $('[data-summary-key="total_unpaid_taken"]').text(formatNumber(summary.total_unpaid_taken, 2));
        }
        $('[data-summary-key="overall_utilization"]').text(formatNumber(summary.overall_utilization, 1) + '%');
        if (typeof summary.overall_utilization_accrued !== 'undefined') {
            $('[data-summary-key="overall_utilization_accrued"]').text(formatNumber(summary.overall_utilization_accrued, 1));
        }
        $('[data-summary-key="low_balance_count"]').text(formatNumber(summary.low_balance_count, 0));
        $('[data-summary-key="exhausted_count"]').text(formatNumber(summary.exhausted_count, 0));
    }

    function renderRecentRequests(requests) {
        var $container = $('#lb_recent_requests');
        if (!$container.length) {
            return;
        }

        $container.empty();

        if (!requests || !requests.length) {
            $container.append('<li class="list-group-item text-muted">' + label_no_recent_leave_activity + '</li>');
            return;
        }

        requests.forEach(function (item) {
            var listItem = $('<li class="list-group-item d-flex justify-content-between align-items-start"></li>');
            var userBlock = $('<div></div>');
            userBlock.append('<div class="fw-semibold">' + item.user_name + '</div>');
            userBlock.append('<small class="text-muted">' + item.date_range + '</small>');

            var statusBlock = $('<div class="text-end"></div>');
            statusBlock.append(item.status_badge || '');
            statusBlock.append('<div class="small text-muted">' + item.balance_impact + '</div>');

            listItem.append(userBlock).append(statusBlock);
            $container.append(listItem);
        });
    }

    $('#lb_date_range, #lb_status_filter').on('change daterange:applied', function () {
        $table.bootstrapTable('refresh', { silent: true });
    });

    $('#lb_member_filter').on('change', function () {
        $table.bootstrapTable('refresh', { silent: true });
    });

    initTooltips();

    $table.on('load-success.bs.table', function (event, data) {
        logChart('bootstrap-table load-success raw payload', data);
        var payloadPreview = {
            rows: Array.isArray(data.rows) ? data.rows.length : 0,
            chart_data: Array.isArray(data.chart_data) ? data.chart_data.length : (data.chart_data === undefined ? 'undefined' : typeof data.chart_data),
            chart_summary_keys: data.chart_summary ? Object.keys(data.chart_summary) : [],
            trend_points: data.chart_trend && Array.isArray(data.chart_trend.data) ? data.chart_trend.data.length : 0
        };
        logChart('bootstrap-table load-success summary', payloadPreview);
        updateSummary(data.summary);
        renderRecentRequests(data.recent_requests);
        updateStackedChart(data.chart_data || []);
        var summaryPayload = data.chart_summary || data.summary || null;
        updateChartSummary(summaryPayload, data.latest_workspace_leave_at || null);
        updateTrendChart(data.chart_trend || { labels: [], data: [] });
        initTooltips();
    });

    $table.on('load-error.bs.table', function (event, status, res) {
        logChart('bootstrap-table load-error', { status: status, response: res });
    });

    new TableFilterSync({
        tableId: 'leave_balances_table',
        dataType: 'leave-balances',
        filters: [
            {
                selector: '#lb_status_filter',
                type: 'tom-select',
                name: 'balance_status'
            },
            {
                selector: '#lb_member_filter',
                type: 'tom-select',
                name: 'user_ids',
                ajaxType: 'users'
            }
        ],
        queryParamsFn: queryParamsLeaveBalances
    });

    function toggleChartVisibility(hasData) {
        if (!leaveBalancesChartEl) {
            return;
        }

        if (hasData) {
            leaveBalancesChartEl.classList.remove('d-none');
            $chartEmptyState.addClass('d-none');
        } else {
            leaveBalancesChartEl.classList.add('d-none');
            $chartEmptyState.removeClass('d-none');
        }
        logChart('toggleChartVisibility', { visible: hasData });
    }

    function buildChartPayload(chartData) {
        var categories = [];
        var used = [];
        var remaining = [];
        var unpaid = [];
        var minValue = 0; // Track minimum value for y-axis

        chartData.forEach(function (row) {
            categories.push(row.member);
            var usedVal = parseFloat(row.used_paid_leaves) || 0;
            var remVal = parseFloat(row.remaining_paid_leaves) || 0;
            var unpaidVal = parseFloat(row.unpaid_leaves_taken) || 0;

            used.push(usedVal);
            remaining.push(remVal);
            unpaid.push(unpaidVal);

            // Calculate minimum value (for negative remaining)
            var totalForUser = usedVal + remVal + unpaidVal;
            if (remVal < 0) {
                // If remaining is negative, the total will be less than used + unpaid
                minValue = Math.min(minValue, remVal);
            }
        });

        return {
            categories: categories,
            series: [
                { key: 'used_paid_leaves', name: seriesLabels.used_paid_leaves, data: used },
                { key: 'remaining_paid_leaves', name: seriesLabels.remaining_paid_leaves, data: remaining },
                { key: 'unpaid_leaves_taken', name: seriesLabels.unpaid_leaves_taken, data: unpaid }
            ],
            minValue: minValue // Pass min value for y-axis configuration
        };
    }

    function updateStackedChart(chartData) {
        if (!leaveBalancesChartEl) {
            logChart('updateStackedChart skipped (no chart element)');
            return;
        }

        if (!Array.isArray(chartData)) {
            logChart('updateStackedChart received non-array data', chartData);
            chartData = [];
        }

        if (!chartData.length) {
            logChart('updateStackedChart no data rows');
            toggleChartVisibility(false);
            if (leaveBalancesChartInstance) {
                leaveBalancesChartInstance.updateSeries([
                    { name: seriesLabels.used_paid_leaves, data: [] },
                    { name: seriesLabels.remaining_paid_leaves, data: [] },
                    { name: seriesLabels.unpaid_leaves_taken, data: [] }
                ]);
            }
            return;
        }

        var payload = buildChartPayload(chartData);
        var hasPositiveValue = payload.series.some(function (series) {
            return series.data.some(function (value) {
                return value > 0;
            });
        });

        logChart('updateStackedChart payload prepared', {
            categoriesCount: payload.categories.length,
            sampleCategories: payload.categories.slice(0, 6),
            series: payload.series.map(function (series) {
                return {
                    key: series.key,
                    name: series.name,
                    dataPreview: series.data.slice(0, 6)
                };
            })
        });

        if (!payload.categories.length || !hasPositiveValue) {
            logChart('updateStackedChart all series zero or empty');
            toggleChartVisibility(false);
            if (leaveBalancesChartInstance) {
                leaveBalancesChartInstance.updateSeries([
                    { name: seriesLabels.used_paid_leaves, data: [] },
                    { name: seriesLabels.remaining_paid_leaves, data: [] },
                    { name: seriesLabels.unpaid_leaves_taken, data: [] }
                ]);
            }
            return;
        }

        toggleChartVisibility(true);

        if (!leaveBalancesChartInstance) {
            logChart('updateStackedChart creating new ApexCharts instance');
            var chartOptions = {
                chart: {
                    type: 'bar',
                    stacked: true,
                    height: 300,
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '45%',
                        borderRadius: 6,
                        borderRadiusApplication: 'end'
                    }
                },
                colors: [
                    themeConfig.colors.primary,
                    themeConfig.colors.success,
                    themeConfig.colors.warning
                ],
                dataLabels: { enabled: false },
                stroke: { show: true, width: 1, colors: [themeConfig.colors.white || '#fff'] },
                legend: { show: false },
                grid: {
                    borderColor: themeConfig.colors.borderColor,
                    strokeDashArray: 3,
                    padding: { top: 12, left: 16, right: 16, bottom: 0 }
                },
                xaxis: {
                    categories: payload.categories,
                    labels: {
                        style: { colors: themeConfig.colors.axisColor },
                        rotate: -35,
                        rotateAlways: true
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: themeConfig.colors.axisColor },
                        formatter: function (value) {
                            return value.toFixed(1);
                        }
                    },
                    min: payload.minValue !== undefined && payload.minValue < 0 ? Math.floor(payload.minValue * 1.1) : 0,
                    title: {
                        text: typeof label_days !== 'undefined' ? label_days : 'Days',
                        style: { color: themeConfig.colors.axisColor }
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (value) {
                            return formatDays(value);
                        }
                    }
                },
                series: payload.series
            };

            leaveBalancesChartInstance = new ApexCharts(leaveBalancesChartEl, chartOptions);
            leaveBalancesChartInstance.render();
        } else {
            logChart('updateStackedChart updating existing chart');
            var yAxisMin = payload.minValue !== undefined && payload.minValue < 0 ? Math.floor(payload.minValue * 1.1) : 0;
            leaveBalancesChartInstance.updateOptions({
                xaxis: { categories: payload.categories },
                yaxis: { min: yAxisMin }
            }, false, true);
            leaveBalancesChartInstance.updateSeries(payload.series, true);
        }
    }

    function applySeriesVisibility() {
        if (!leaveBalancesChartInstance) {
            return;
        }

        Object.keys(seriesVisibility).forEach(function (key) {
            var action = seriesVisibility[key] ? 'showSeries' : 'hideSeries';
            var name = seriesLabels[key];

            if (typeof leaveBalancesChartInstance[action] === 'function') {
                leaveBalancesChartInstance[action](name);
            }
        });
        logChart('applySeriesVisibility', seriesVisibility);
    }

    $('.chart-legend-toggle').on('click', function () {
        var seriesKey = $(this).data('series');
        if (!seriesKey || !seriesLabels[seriesKey] || !leaveBalancesChartInstance) {
            return;
        }

        if (seriesVisibility[seriesKey]) {
            leaveBalancesChartInstance.hideSeries(seriesLabels[seriesKey]);
            $(this).removeClass('active');
            seriesVisibility[seriesKey] = false;
        } else {
            leaveBalancesChartInstance.showSeries(seriesLabels[seriesKey]);
            $(this).addClass('active');
            seriesVisibility[seriesKey] = true;
        }
    });

    function updateChartSummary(summary, latestAt) {
        if (!summary) {
            $('[data-chart-summary]').text('--');
            $('[data-highlight-low]').text('--');
            $('[data-highlight-unpaid]').text('--');
            $('[data-highlight-healthy]').text('--');
            $('[data-highlight-latest]').text('--');
            logChart('updateChartSummary skipped (no summary)');
            return;
        }

        logChart('updateChartSummary payload', summary);

        $('[data-chart-summary="member_count"]').text(formatNumber(summary.member_count || 0, 0));
        $('[data-chart-summary="avg_utilization"]').text(formatPercent(summary.avg_utilization || 0));
        $('[data-chart-summary="total_unpaid"]').text(formatDays(summary.total_unpaid || 0));

        var lowThreshold = summary.low_threshold || 0;
        var lowCount = summary.low_balance_count || 0;
        var exhaustedCount = summary.exhausted_count || 0;
        var lowMessage = formatNumber(lowCount + exhaustedCount, 0) + ' ' + (typeof label_members_text !== 'undefined' ? label_members_text : 'Members');
        if (lowThreshold) {
            lowMessage += ' (< ' + formatNumber(lowThreshold, 0) + ' ' + (typeof label_days !== 'undefined' ? label_days.toLowerCase() : 'days') + ')';
        }
        $('[data-highlight-low]').text(lowMessage);

        if (summary.top_unpaid_member) {
            $('[data-highlight-unpaid]').text(
                summary.top_unpaid_member.name + ' · ' + formatDays(summary.top_unpaid_member.unpaid_leaves || 0)
            );
        } else {
            $('[data-highlight-unpaid]').text('--');
        }

        var healthyThreshold = summary.healthy_threshold || 0;
        var healthyCount = summary.healthy_count || 0;
        var healthyMessage = formatNumber(healthyCount, 0) + ' ';
        healthyMessage += (typeof label_members_text !== 'undefined' ? label_members_text : 'Members');
        if (healthyThreshold) {
            healthyMessage += ' (≥ ' + formatNumber(healthyThreshold, 0) + ' ' + (typeof label_days !== 'undefined' ? label_days.toLowerCase() : 'days') + ')';
        }
        $('[data-highlight-healthy]').text(healthyMessage);

        var latestText = latestAt ? formatDateTime(latestAt) : '--';
        $('[data-highlight-latest]').text(latestText);
    }

    function updateTrendChart(trendData) {
        if (!trendChartEl) {
            logChart('updateTrendChart skipped (no trend element)');
            return;
        }

        var labels = Array.isArray(trendData.labels) ? trendData.labels : [];
        var seriesData = Array.isArray(trendData.data) ? trendData.data : [];

        var hasData = seriesData.length > 0 && seriesData.some(function (value) { return value > 0; });

        logChart('updateTrendChart payload', {
            labelsCount: labels.length,
            labelsPreview: labels.slice(0, 12),
            dataPreview: seriesData.slice(0, 12),
            hasData: hasData
        });

        if (!leaveBalancesTrendInstance) {
            var trendOptions = {
                chart: {
                    type: 'area',
                    height: 160,
                    sparkline: { enabled: true },
                    toolbar: { show: false }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2,
                    colors: [themeConfig.colors.primary]
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0.3,
                        opacityFrom: 0.7,
                        opacityTo: 0.1,
                        stops: [0, 90, 100],
                        colorStops: []
                    }
                },
                tooltip: {
                    custom: function ({ series, seriesIndex, dataPointIndex }) {
                        var monthLabel = labels[dataPointIndex] || '--';
                        var value = series[seriesIndex][dataPointIndex] || 0;
                        var title = typeof label_leaves_taken !== 'undefined' ? label_leaves_taken : 'Leaves Taken';
                        return (
                            '<div class="px-3 py-2">' +
                            '<div class="text-muted small mb-1">' + monthLabel + '</div>' +
                            '<div class="d-flex align-items-center">' +
                            '<i class="bx bxs-circle text-primary me-2"></i>' +
                            '<span class="fw-semibold">' + title + ': ' + formatDays(value) + '</span>' +
                            '</div>' +
                            '</div>'
                        );
                    }
                },
                series: [{
                    name: typeof label_leaves_taken !== 'undefined' ? label_leaves_taken : 'Leaves Taken',
                    data: seriesData
                }]
            };

            leaveBalancesTrendInstance = new ApexCharts(trendChartEl, trendOptions);
            leaveBalancesTrendInstance.render();
        } else {
            leaveBalancesTrendInstance.updateSeries([{
                name: typeof label_leaves_taken !== 'undefined' ? label_leaves_taken : 'Leaves Taken',
                data: seriesData
            }], true);
            leaveBalancesTrendInstance.updateOptions({
                tooltip: {
                    custom: function ({ series, seriesIndex, dataPointIndex }) {
                        var monthLabel = labels[dataPointIndex] || '--';
                        var value = series[seriesIndex][dataPointIndex] || 0;
                        var title = typeof label_leaves_taken !== 'undefined' ? label_leaves_taken : 'Leaves Taken';
                        return (
                            '<div class="px-3 py-2">' +
                            '<div class="text-muted small mb-1">' + monthLabel + '</div>' +
                            '<div class="d-flex align-items-center">' +
                            '<i class="bx bxs-circle text-primary me-2"></i>' +
                            '<span class="fw-semibold">' + title + ': ' + formatDays(value) + '</span>' +
                            '</div>' +
                            '</div>'
                        );
                    }
                }
            }, true, true);
        }

        updateTrendMeta(labels, seriesData, hasData);
    }

    function updateTrendMeta(labels, data, hasData) {
        var $monthsBadge = $('[data-trend-months]');
        var $peak = $('[data-trend-peak]');
        var $dip = $('[data-trend-dip]');
        var $direction = $('[data-trend-direction]');

        if (!hasData) {
            $monthsBadge.text('--');
            $peak.text('--');
            $dip.text('--');
            $direction.text('--');
            logChart('updateTrendMeta no data');
            return;
        }

        $monthsBadge.text(labels.length + ' ' + (typeof label_months !== 'undefined' ? label_months : 'Months'));

        var max = Math.max.apply(null, data);
        var min = Math.min.apply(null, data);
        var maxIndex = data.indexOf(max);
        var minIndex = data.indexOf(min);

        $peak.text((labels[maxIndex] || '--') + ' · ' + formatDays(max));
        $dip.text((labels[minIndex] || '--') + ' · ' + formatDays(min));

        if (data.length >= 2) {
            var first = data[0];
            var last = data[data.length - 1];
            var change = first === 0 ? 0 : ((last - first) / Math.max(first, 1)) * 100;
            var directionText = (change > 0 ? '+' : '') + formatNumber(change, 1) + '%';
            $direction.text(directionText);
        } else {
            $direction.text('--');
        }

        logChart('updateTrendMeta summary', {
            months: labels.length,
            peak: { month: labels[maxIndex] || '--', value: max },
            dip: { month: labels[minIndex] || '--', value: min },
            change: data.length >= 2 ? { first: first, last: last } : null
        });
    }
});


