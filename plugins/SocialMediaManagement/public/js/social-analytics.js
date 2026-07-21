'use strict';

$(document).ready(function () {
    let analyticsData = {};
    let charts = {};
    let currentAccountId = 'all';

    // Initialize
    initializeDatePickers();
    loadActiveAccounts();
    loadAnalyticsData();

    function initializeDatePickers() {
        $('#social_date_between').daterangepicker({
            alwaysShowCalendars: true,
            showCustomRangeLabel: true,
            singleDatePicker: false,
            showDropdowns: true,
            autoUpdateInput: false,
            locale: {
                cancelLabel: "Clear",
                format: js_date_format,
            },
        });
    }

    // Load active accounts for dropdown
    function loadActiveAccounts() {

           // Initialize Select2 on account dropdown
        $('#accountSelect').select2({
            placeholder:  'Select an account',
            allowClear: false,
            width: '100%'
        });
        
        $.ajax({
            url: analyticConfig.routes.getActiveAccounts,
            type: 'GET',
            dataType: 'json'
        })
            .done(function (response) {
                if (!response.error && response.data) {
                    const $select = $('#accountSelect');
                    // Keep "All Accounts" option
                    $select.find('option:not([value="all"])').remove();
                    
                    // Add account options
                    response.data.forEach(account => {
                        $select.append(
                            $('<option>', {
                                value: account.id,
                                text: account.name
                            })
                        );
                    });
                }
            })
            .fail(function () {
                console.error('Failed to load accounts');
            });
    }

    // Event: Account selection change
    $('#accountSelect').on('change', function () {
        currentAccountId = $(this).val();
        loadAnalyticsData();
    });

    // Event: Date range picker
    $("#social_date_between").on("apply.daterangepicker", function (ev, picker) {
        const startDate = picker.startDate.format("YYYY-MM-DD");
        const endDate = picker.endDate.format("YYYY-MM-DD");
        $('#social_end_date').val(endDate);
        $('#social_start_date').val(startDate);
        $("#social_date_between").val(startDate + ' To ' + endDate);
        loadAnalyticsData(startDate, endDate);
    });

    $("#social_date_between").on("cancel.daterangepicker", function (ev, picker) {
        $('#social_end_date').val('');
        $('#social_start_date').val('');
        $('#social_date_between').val('');
        picker.setStartDate(moment());
        picker.setEndDate(moment());
        picker.updateElement();
    });

    // Event: Refresh button
    $('#refreshSocialAnalytics').on('click', function () {
        loadAnalyticsData();
    });

    // Event: Date range select
    $('#dateRangeSelect').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#customDateRange').css('display', 'flex');
        } else {
            $('#customDateRange').css('display', 'none');
            loadAnalyticsData();
        }
    });

    // Event: Trend period change
    $('.trend-period').on('click', function (e) {
        e.preventDefault();
        const period = $(this).data('period');
        loadPostingTrends(period);
    });

    function loadAnalyticsData(startDate = null, endDate = null) {
        showLoading(true);

        const dateRange = $('#dateRangeSelect').val();
        const params = new URLSearchParams();

        // Add account filter
        params.append('account_id', currentAccountId);

        if (startDate && endDate) {
            params.append('start_date', startDate);
            params.append('end_date', endDate);
        } else if (dateRange !== 'custom') {
            params.append('date_range', dateRange);
        }

        $.ajax({
            url: `${analyticConfig.routes.getAnalyticsData}?${params}`,
            type: 'GET',
            dataType: 'json'
        })
            .done(function (data) {
                if (data.error) {
                    showAlert('error', data.message);
                    return;
                }

                analyticsData = data.data;
                updateAllComponents();
            })
            .fail(function (xhr, status, error) {
                showAlert('error', 'Failed to load analytics data');
            })
            .always(function () {
                showLoading(false);
            });
    }

    function updateAllComponents() {
        updateOverallStats();
        updateAccountPerformance(); // NEW
        updateDailyActivityChart();
        updateStatusDistributionChart();
        updatePlatformPerformanceChart();
        updatePeakHoursChart();
        updateSchedulingChart();
        updatePlatformStatsTable();
        updateMediaStats();
    }

    function updateOverallStats() {
        const stats = analyticsData.overall_stats;

        $('#totalPosts').text(stats.total_posts);
        $('#publishedPosts').text(stats.published + stats.partially_published);
        $('#successRate').text(`(${stats.success_rate}%)`);
        $('#scheduledPosts').text(stats.scheduled);
        $('#totalMediaFiles').text(stats.total_media_files);
    }

    // NEW: Account Performance Chart
    function updateAccountPerformance() {
        const accountStats = analyticsData.account_stats;

        // Show/hide section based on whether viewing all accounts
        if (!accountStats || accountStats.length === 0 || currentAccountId !== 'all') {
            $('#accountPerformanceSection').hide();
            return;
        }

        $('#accountPerformanceSection').show();

        // Destroy existing chart
        if (charts.accountPerformance) {
            charts.accountPerformance.destroy();
        }

        const options = {
            series: [
                {
                    name: 'Total Posts',
                    data: accountStats.map(a => a.total_posts),
                    color: '#54a0ff'
                },
                {
                    name: 'Published',
                    data: accountStats.map(a => a.published + a.partially_published),
                    color: '#5f27cd'
                },
                {
                    name: 'Scheduled',
                    data: accountStats.map(a => a.scheduled),
                    color: '#ffc107'
                },
                {
                    name: 'Failed',
                    data: accountStats.map(a => a.failed),
                    color: '#ff6b6b'
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: true }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: accountStats.map(a => a.name),
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                min: 0,
                labels: {
                    formatter: function (val) {
                        return Math.floor(val);
                    }
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val, opts) {
                        const accountIndex = opts.dataPointIndex;
                        const account = accountStats[accountIndex];
                        return val + ' posts (Success: ' + account.success_rate + '%)';
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            }
        };

        charts.accountPerformance = new ApexCharts($("#accountPerformanceChart")[0], options);
        charts.accountPerformance.render();
    }

    function updateDailyActivityChart() {
        if (charts.dailyActivity) {
            charts.dailyActivity.destroy();
        }

        const dailyData = analyticsData.daily_activity;

        const options = {
            series: [
                {
                    name: 'Published',
                    data: dailyData.map(d => ({ x: d.date, y: d.published })),
                    color: getStatusColor('published')
                },
                {
                    name: 'Partially Published',
                    data: dailyData.map(d => ({ x: d.date, y: d.partially_published || 0 })),
                    color: getStatusColor('partially_published')
                },
                {
                    name: 'Scheduled',
                    data: dailyData.map(d => ({ x: d.date, y: d.scheduled })),
                    color: getStatusColor('scheduled')
                },
                {
                    name: 'Failed',
                    data: dailyData.map(d => ({ x: d.date, y: d.failed })),
                    color: getStatusColor('failed')
                },
                {
                    name: 'Pending',
                    data: dailyData.map(d => ({ x: d.date, y: d.pending || 0 })),
                    color: getStatusColor('pending')
                }
            ],
            chart: {
                type: 'area',
                height: 300,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.6,
                    opacityTo: 0.1,
                }
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    format: 'MMM dd',
                    rotate: -30,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                min: 0,
                labels: {
                    formatter: function (val) {
                        return Math.floor(val);
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            tooltip: {
                shared: true,
                intersect: false,
                x: {
                    format: 'MMM dd, yyyy'
                }
            }
        };

        charts.dailyActivity = new ApexCharts($("#dailyActivityChart")[0], options);
        charts.dailyActivity.render();
    }

    function updateStatusDistributionChart() {
        if (charts.statusDistribution) {
            charts.statusDistribution.destroy();
        }

        const statusData = analyticsData.status_distribution.filter(s => s.count > 0);

        if (statusData.length === 0) {
            $('#statusDistributionChart').html(
                '<div class="d-flex justify-content-center align-items-center h-100"><span class="text-muted">No data available</span></div>'
            );
            return;
        }

        const options = {
            series: statusData.map(s => s.count),
            chart: {
                type: 'donut',
                height: 300
            },
            labels: statusData.map(s => s.status),
            colors: statusData.map(s => s.color),
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Posts',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                position: 'bottom'
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        charts.statusDistribution = new ApexCharts($("#statusDistributionChart")[0], options);
        charts.statusDistribution.render();
    }

    function updatePlatformPerformanceChart() {
        if (charts.platformPerformance) {
            charts.platformPerformance.destroy();
        }

        const platformData = Object.entries(analyticsData.platform_stats)
            .filter(([platform, stats]) => stats.total_posts > 0)
            .map(([platform, stats]) => ({
                platform: platform.charAt(0).toUpperCase() + platform.slice(1),
                ...stats
            }));

        if (platformData.length === 0) {
            $('#platformPerformanceChart').html(
                '<div class="d-flex justify-content-center align-items-center h-100"><span class="text-muted">No platform data available</span></div>'
            );
            return;
        }

        const options = {
            series: [
                {
                    name: 'Total Posts',
                    data: platformData.map(p => p.total_posts),
                    color: '#54a0ff'
                },
                {
                    name: 'Successful',
                    data: platformData.map(p => p.successful),
                    color: '#5f27cd'
                },
                {
                    name: 'Failed',
                    data: platformData.map(p => p.failed),
                    color: '#ff6b6b'
                }
            ],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: platformData.map(p => p.platform)
            },
            yaxis: {
                min: 0,
                labels: {
                    formatter: function (val) {
                        return Math.floor(val);
                    }
                }
            },
            legend: {
                position: 'top'
            }
        };

        charts.platformPerformance = new ApexCharts($("#platformPerformanceChart")[0], options);
        charts.platformPerformance.render();
    }

    function updatePeakHoursChart() {
        if (charts.peakHours) {
            charts.peakHours.destroy();
        }

        const peakData = analyticsData.peak_hours;

        const options = {
            series: [{
                name: 'Posts',
                data: peakData.map(p => p.count),
                color: '#4e73df'
            }],
            chart: {
                type: 'line',
                height: 300,
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            markers: {
                size: 4,
                colors: ['#ffffff'],
                strokeColors: '#4e73df',
                strokeWidth: 2,
                hover: {
                    size: 6
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: peakData.map(p => formatHour(p.hour)),
                labels: {
                    style: {
                        fontSize: '11px'
                    },
                    rotate: 0,
                    rotateAlways: false,
                    hideOverlappingLabels: true,
                    showDuplicates: false,
                    trim: false
                },
                title: {
                    text: 'Hour of Day',
                    style: {
                        fontSize: '13px',
                        fontWeight: 500
                    }
                },
                tickAmount: 12,
                tickPlacement: 'between'
            },
            yaxis: {
                min: 0,
                labels: {
                    formatter: function (val) {
                        return Math.floor(val);
                    }
                },
                title: {
                    text: 'Posts Count',
                    style: {
                        fontSize: '13px',
                        fontWeight: 500
                    }
                }
            },
            grid: {
                strokeDashArray: 4,
                borderColor: '#e0e0e0',
                xaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: val => `${val} posts`
                }
            },
            legend: {
                show: false
            }
        };

        charts.peakHours = new ApexCharts($("#peakHoursChart")[0], options);
        charts.peakHours.render();
    }

    function formatHour(hour) {
        const h = parseInt(hour, 10);
        const suffix = h >= 12 ? 'PM' : 'AM';
        const displayHour = ((h + 11) % 12 + 1);
        return `${displayHour} ${suffix}`;
    }

    function updateSchedulingChart() {
        if (charts.scheduling) {
            charts.scheduling.destroy();
        }

        const schedulingData = analyticsData.scheduling_stats;
        const total = schedulingData.immediate + schedulingData.scheduled;

        if (total === 0) {
            $('#schedulingChart').html(
                '<div class="d-flex justify-content-center align-items-center h-100"><span class="text-muted">No scheduling data</span></div>'
            );
            return;
        }

        const options = {
            series: [schedulingData.immediate, schedulingData.scheduled],
            chart: {
                type: 'donut',
                height: 250
            },
            labels: ['Immediate', 'Scheduled'],
            colors: ['#17a2b8', '#ffc107'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%'
                    }
                }
            },
            legend: {
                position: 'bottom'
            }
        };

        charts.scheduling = new ApexCharts($("#schedulingChart")[0], options);
        charts.scheduling.render();
    }

    function updatePlatformStatsTable() {
        if (charts.platformStats) {
            charts.platformStats.destroy();
        }

        const platforms = analyticsData.platform_stats;

        const platformData = Object.entries(platforms)
            .filter(([platform, stats]) => stats.total_posts > 0)
            .map(([platform, stats]) => ({
                platform: platform.charAt(0).toUpperCase() + platform.slice(1),
                total_posts: stats.total_posts,
                successful: stats.successful,
                failed: stats.failed,
                success_rate: stats.success_rate,
                color: getPlatformColor(platform)
            }));

        const $container = $('#platformStatsList').parent();
        $container.empty();

        if (platformData.length === 0) {
            $container.html(
                '<div class="d-flex justify-content-center align-items-center h-100"><span class="text-muted">No platform data available</span></div>'
            );
            return;
        }

        $container.html('<div id="platformStatsChart"></div>');

        const options = {
            series: [
                {
                    name: 'Successful',
                    data: platformData.map(p => p.successful),
                    color: '#4CAF50'
                },
                {
                    name: 'Failed',
                    data: platformData.map(p => p.failed),
                    color: '#F44336'
                }
            ],
            chart: {
                type: 'bar',
                height: platformData.length * 45 + 120,
                stacked: true,
                stackType: '100%',
                toolbar: { show: false },
                fontFamily: 'Inter, system-ui, sans-serif'
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    barHeight: '60%',
                }
            },
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '12px',
                    fontWeight: 500,
                    colors: ['#fff']
                }
            },
            stroke: {
                show: false
            },
            xaxis: {
                categories: platformData.map(p => p.platform),
                labels: {
                    show: false
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '13px',
                        fontWeight: 500,
                        color: '#333'
                    }
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: false } }
            },
            tooltip: {
                theme: 'light',
                style: { fontSize: '13px' },
                custom: function ({ series, seriesIndex, dataPointIndex }) {
                    const platform = platformData[dataPointIndex];
                    return `
                        <div style="padding:10px; font-family:Inter, sans-serif;">
                            <div class="fw-bold mb-1" style="font-size:14px;">${platform.platform}</div>
                            <div style="color:#4CAF50;">✓ Successful: ${platform.successful}</div>
                            <div style="color:#F44336;">✗ Failed: ${platform.failed}</div>
                            <div class="text-muted">Total: ${platform.total_posts}</div>
                            <div class="fw-bold" style="color:#2196F3;">Success Rate: ${platform.success_rate}%</div>
                        </div>
                    `;
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                markers: {
                    width: 12,
                    height: 12,
                    radius: 12
                },
                itemMargin: {
                    horizontal: 8
                },
                labels: {
                    colors: '#555',
                    useSeriesColors: false
                }
            },
            fill: { opacity: 1 }
        };

        charts.platformStats = new ApexCharts($("#platformStatsChart")[0], options);
        charts.platformStats.render();
    }

    function updateMediaStats() {
        const mediaStats = analyticsData.media_stats;

        $('#postsWithMedia').text(mediaStats.posts_with_media);
        $('#avgMediaPerPost').text(mediaStats.avg_media_per_post);
    }

    function loadPostingTrends(period) {
        const dateRange = $('#dateRangeSelect').val();
        const params = new URLSearchParams({
            period: period,
            date_range: dateRange,
            account_id: currentAccountId
        });

        $.ajax({
            url: `${analyticConfig.routes.getPostingTrends}?${params}`,
            type: 'GET',
            dataType: 'json'
        })
            .done(function (data) {
                if (!data.error) {
                    analyticsData.daily_activity = data.data;
                    updateDailyActivityChart();
                }
            });
    }

    function showLoading(show) {
        $('#loadingSpinner').css('display', show ? 'block' : 'none');
        $('#analyticsContent').css('display', show ? 'none' : 'block');
    }

    function showAlert(type, message) {
        if (type === 'error') {
            toastr.error('Error: ' + message);
        }
    }

    function getStatusColor(status) {
        const colors = {
            'published': '#28a745',
            'partially_published': '#007bff',
            'scheduled': '#ffc107',
            'failed': '#dc3545',
            'pending': '#6c757d'
        };
        return colors[status] || '#6c757d';
    }

    function getPlatformColor(platform) {
        const colors = {
            'facebook': '#4267B2',
            'instagram': '#E4405F',
            'twitter': '#1DA1F2',
            'linkedin': '#0077B5',
            'pinterest': '#E60023',
            'youtube': '#FF0000'
        };
        return colors[platform.toLowerCase()] || '#6c757d';
    }
});