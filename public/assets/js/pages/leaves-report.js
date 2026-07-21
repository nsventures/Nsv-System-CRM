$(function () {

    $('#leaves_report_table').on('load-success.bs.table', function (e, data) {
        $('#rejected-leaves').text(data.summary.formatted_rejected_leaves);
        $('#pending-leaves').text(data.summary.formatted_pending_leaves);
        $('#approved-leaves').text(data.summary.formatted_approved_leaves);
        $('#total-leaves').text((data.summary.formatted_total_leaves));
        $('#full-leaves').text((data.summary.total_full_leaves));
        $('#partial-leaves').text((data.summary.formatted_partial_leaves));

        // Update new cards
        $('#paid-leaves').text(data.summary.formatted_paid_leaves || '0');
        $('#unpaid-leaves').text(data.summary.formatted_unpaid_leaves || '0');
        $('#avg-utilization').text((data.summary.avg_utilization_percentage || 0) + '%');
    });
});
$(document).ready(function () {
    $('#export_button, #exportChartsButton').click(function () {
        var $exportButton = $(this);
        $exportButton.attr('disabled', true);
        // Prepare query parameters
        const queryParams = leaves_report_query_params({ offset: 0, limit: 1000, sort: 'id', order: 'desc', search: '' });
        // Construct the export URL
        const exportUrl = leaves_report_export_url + '?' + $.param(queryParams);
        // Open the export URL in a new tab or window
        $exportButton.attr('disabled', false);
        window.open(exportUrl, '_blank');
    });
});
function leaves_report_query_params(p) {
    return {
        user_ids: $('#user_filter').val(),
        statuses: $('#status_filter').val(),
        date_between_from: $('#report_date_between_from').val(),
        date_between_to: $('#report_date_between_to').val(),
        start_date_from: $('#report_start_date_from').val(),
        start_date_to: $('#report_start_date_to').val(),
        end_date_from: $('#report_end_date_from').val(),
        end_date_to: $('#report_end_date_to').val(),
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}
addDebouncedEventListener('#user_filter, #status_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#leaves_report_table').bootstrapTable('refresh');
    }
});


$(document).on('click', '.clear-report-filters', function (e) {
    e.preventDefault();
    $('#report_date_between').val('');
    $('#report_date_between_from').val('');
    $('#report_date_between_to').val('');
    $('#report_start_date_between').val('');
    $('#report_start_date_from').val('');
    $('#report_start_date_to').val('');
    $('#report_end_date_between').val('');
    $('#report_end_date_from').val('');
    $('#report_end_date_to').val('');
    $('#user_filter').val('').trigger('change', [0]);
    $('#status_filter').val('').trigger('change', [0]);
    $('#leaves_report_table').bootstrapTable('refresh');
})
// Function to format the Total Leaves column
function formatTotalLeaves(value, row, index) {
    return formatLeaveDuration(row.total_leaves, row.total_days, row.total_hours);
}

// Function to format the Partial Leaves column
function formatPartialLeaves(value, row, index) {
    return formatLeaveDuration(row.partial_leaves, '', row.total_hours);
}

// Function to format the Approved Leaves column
function formatApprovedLeaves(value, row, index) {
    return formatLeaveDuration(row.approved_leaves, row.approved_days, row.approved_hours);
}

// Function to format the Pending Leaves column
function formatPendingLeaves(value, row, index) {
    return formatLeaveDuration(row.pending_leaves, row.pending_days, row.pending_hours);
}

// Function to format the Rejected Leaves column
function formatRejectedLeaves(value, row, index) {
    return formatLeaveDuration(row.rejected_leaves, row.rejected_days, row.rejected_hours);
}

// Function to format Paid Leaves column
function formatPaidLeaves(value, row, index) {
    if (!row.paid_breakdown) {
        return '-';
    }
    // Use formatted_paid if available, otherwise fallback to paid key
    const formatted = row.paid_breakdown.formatted_paid || row.paid_breakdown.paid;
    // Show '-' if no formatted value or if it's effectively zero
    if (!formatted || formatted === '0' || formatted === '0 (0 Days)') {
        // Check if there are actual paid days - if yes, show the formatted value even if it's '0'
        const paidDays = parseFloat(row.paid_breakdown.paid_days) || 0;
        if (paidDays > 0) {
            return formatted || '-';
        }
        return '-';
    }
    return formatted;
}

// Function to format Unpaid Leaves column
function formatUnpaidLeaves(value, row, index) {
    if (!row.paid_breakdown) {
        return '-';
    }
    // Use formatted_unpaid if available, otherwise fallback to unpaid key
    const formatted = row.paid_breakdown.formatted_unpaid || row.paid_breakdown.unpaid;
    // Show '-' if no formatted value or if it's effectively zero
    if (!formatted || formatted === '0' || formatted === '0 (0 Days)') {
        // Check if there are actual unpaid days - if yes, show the formatted value even if it's '0'
        const unpaidDays = parseFloat(row.paid_breakdown.unpaid_days) || 0;
        if (unpaidDays > 0) {
            return formatted || '-';
        }
        return '-';
    }
    return formatted;
}

// Function to format Balance Total column
function formatBalanceTotal(value, row, index) {
    return row.balance_info ? row.balance_info.total_annual_leaves : '0';
}

// Function to format Balance Used column
function formatBalanceUsed(value, row, index) {
    return row.balance_info ? row.balance_info.used_paid_leaves : '0';
}

// Function to format Balance Remaining column
function formatBalanceRemaining(value, row, index) {
    return row.balance_info ? row.balance_info.remaining_paid_leaves : '0';
}

// Function to format Utilization column
function formatUtilization(value, row, index) {
    const util = row.balance_info ? row.balance_info.utilization_percentage : 0;
    let colorClass = 'text-success';
    if (util > 80) colorClass = 'text-warning';
    if (util > 95) colorClass = 'text-danger';
    return `<span class="${colorClass}">${util}%</span>`;
}

// General function to format leave duration in a "X Days and Y Hours" format
function formatLeaveDuration(totalLeaves, days, hours) {
    const dayLabel = 'Day';
    const daysLabel = 'Days';
    const hourLabel = 'Hour';
    const hoursLabel = 'Hours';

    // If there are no days or hours, return just the total leaves
    if (days === 0 && hours === 0) {
        return `${totalLeaves}`;
    }

    // Initialize the formatted string
    let formatted = `${totalLeaves}`;

    // Array to hold the duration strings
    let leaveDuration = [];

    // If there are days, format and add them
    if (days > 0) {
        leaveDuration.push(`${days} ${days > 1 ? daysLabel : dayLabel}`);
    }

    // If there are hours, format and add them
    if (hours > 0) {
        leaveDuration.push(`${hours} ${hours > 1 ? hoursLabel : hourLabel}`);
    }

    // If we have any leave duration to display, append it inside parentheses
    if (leaveDuration.length > 0) {
        formatted += ` (${leaveDuration.join(' and ')})`;
    }

    return formatted;
}

$(document).ready(function () {
    // Initialize TableFilterSync for leaves report filters
    const leaveReportFilterSync = new TableFilterSync({
        tableId: 'leaves_report_table',
        dataType: 'report',
        filters: [
            {
                selector: '#report_date_between',
                type: 'daterangepicker',
                name: 'report_date_between',
                hiddenFrom: '#report_date_between_from',
                hiddenTo: '#report_date_between_to'
            },
            {
                selector: '#report_start_date_between',
                type: 'daterangepicker',
                name: 'report_start_date_between',
                hiddenFrom: '#report_start_date_from',
                hiddenTo: '#report_start_date_to'
            },
            {
                selector: '#report_end_date_between',
                type: 'daterangepicker',
                name: 'report_end_date_between',
                hiddenFrom: '#report_end_date_from',
                hiddenTo: '#report_end_date_to'
            },
            {
                selector: '#user_filter',
                type: 'tom-select',
                name: 'user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#status_filter',
                type: 'tom-select',
                name: 'statuses',
                ajaxType: null
            }
        ],
        preserveParams: [''],
        queryParamsFn: leaves_report_query_params // Reuse existing function
    });
});

// Charts functionality
let paidVsUnpaidChart = null;
let utilizationChart = null;
let trendChart = null;

$('#view_charts_button').on('click', function () {
    // Fetch full data including trends from API
    const queryParams = leaves_report_query_params({ offset: 0, limit: 1000, sort: 'id', order: 'desc', search: '' });
    $.ajax({
        url: $('#leaves_report_table').data('url'),
        data: queryParams,
        success: function (response) {
            if (response.users && response.monthly_trends) {
                renderCharts(response.users, response.monthly_trends, response.summary);
                $('#chartsModal').modal('show');
            } else {
                // Fallback to table data only
                const tableData = $('#leaves_report_table').bootstrapTable('getData');
                renderCharts(tableData, null, null);
                $('#chartsModal').modal('show');
            }
        },
        error: function () {
            // Fallback to table data only
            const tableData = $('#leaves_report_table').bootstrapTable('getData');
            renderCharts(tableData, null, null);
            $('#chartsModal').modal('show');
        }
    });
});

// Ensure charts expand to full modal width when the modal becomes visible
$('#chartsModal').on('shown.bs.modal', function () {
    // Give layout a moment to settle, then force a resize on the window and each chart instance
    setTimeout(function () {
        try { window.dispatchEvent(new Event('resize')); } catch (e) { }
        if (typeof utilizationChart !== 'undefined' && utilizationChart && typeof utilizationChart.resize === 'function') {
            utilizationChart.resize();
        }
        if (typeof paidVsUnpaidChart !== 'undefined' && paidVsUnpaidChart && typeof paidVsUnpaidChart.resize === 'function') {
            paidVsUnpaidChart.resize();
        }
        if (typeof trendChart !== 'undefined' && trendChart && typeof trendChart.resize === 'function') {
            trendChart.resize();
        }
    }, 150);
});

function renderCharts(data, monthlyTrends, summary) {
    // Destroy existing charts if they exist
    if (paidVsUnpaidChart && typeof paidVsUnpaidChart.destroy === 'function') {
        paidVsUnpaidChart.destroy();
    }
    if (utilizationChart && typeof utilizationChart.destroy === 'function') {
        utilizationChart.destroy();
    }
    if (trendChart && typeof trendChart.destroy === 'function') {
        trendChart.destroy();
    }

    // Calculate totals for summary cards
    const paidDays = data.reduce((sum, row) => sum + (parseFloat(row.paid_breakdown?.paid_days) || 0), 0);
    const unpaidDays = data.reduce((sum, row) => sum + (parseFloat(row.paid_breakdown?.unpaid_days) || 0), 0);
    const totalDays = paidDays + unpaidDays;
    const paidPercentage = totalDays > 0 ? ((paidDays / totalDays) * 100).toFixed(1) : 0;

    const allUtilizations = data.map(row => parseFloat(row.balance_info?.utilization_percentage) || 0);
    const avgUtilization = allUtilizations.length > 0
        ? (allUtilizations.reduce((a, b) => a + b, 0) / allUtilizations.length).toFixed(1)
        : 0;

    // Calculate utilization zones
    const safeUsers = allUtilizations.filter(u => u < 80).length;
    const warningUsers = allUtilizations.filter(u => u >= 80 && u <= 95).length;
    const criticalUsers = allUtilizations.filter(u => u > 95).length;

    // Update summary cards
    $('#chart-total-paid').text(paidDays.toFixed(1));
    $('#chart-total-unpaid').text(unpaidDays.toFixed(1));
    $('#chart-avg-utilization').text(avgUtilization + '%');
    $('#chart-total-users').text(data.length);

    // Update insights
    $('#chart-paid-percentage').text(paidPercentage + '%');
    $('#chart-insight-paid').text(paidDays.toFixed(1) + ' days');
    $('#chart-insight-unpaid').text(unpaidDays.toFixed(1) + ' days');
    $('#chart-utilization-safe').text(safeUsers + ' Safe');
    $('#chart-utilization-warning').text(warningUsers + ' Warning');
    $('#chart-utilization-critical').text(criticalUsers + ' Critical');

    // Update distribution insight
    if (totalDays > 0) {
        const insight = `${paidPercentage}% of leaves are paid. `;
        let recommendation = '';
        if (paidPercentage < 50) {
            recommendation = 'Consider reviewing unpaid leave policies.';
        } else if (paidPercentage >= 90) {
            recommendation = 'Most leaves are covered with pay - excellent!';
        } else {
            recommendation = 'Balanced paid/unpaid distribution.';
        }
        $('#chart-insight-distribution').html(insight + '<strong>' + recommendation + '</strong>');
    } else {
        $('#chart-insight-distribution').text(APP_LABELS && APP_LABELS['no_leave_data_available_yet'] ? APP_LABELS['no_leave_data_available_yet'] : 'No leave data available yet.');
    }

    const pieChartOptions = {
        chart: {
            type: 'donut',
            height: 300
        },
        series: [paidDays, unpaidDays],
        labels: ['Paid Leaves', 'Unpaid Leaves'],
        colors: ['#71dd37', '#ffab00'],
        legend: {
            position: 'bottom',
            fontSize: '14px'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: {
                            show: true
                        },
                        value: {
                            show: true,
                            formatter: function (val) {
                                return val.toFixed(1) + ' days';
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val.toFixed(1) + ' days';
                }
            }
        }
    };

    paidVsUnpaidChart = new ApexCharts(document.querySelector("#paidVsUnpaidChart"), pieChartOptions);
    paidVsUnpaidChart.render();

    // Chart 2: Utilization Bar Chart (Horizontal for better readability)
    const sortedData = data.slice().sort((a, b) => {
        return (parseFloat(b.balance_info?.utilization_percentage) || 0) - (parseFloat(a.balance_info?.utilization_percentage) || 0);
    }).slice(0, 10);

    // Truncate long names for better display (avoid returning just ellipsis)
    const getShortName = (fullName) => {
        const text = (fullName || '').replace(/<[^>]*>/g, '').trim();
        if (!text) {
            return 'User';
        }
        // Check if on mobile/tablet (screen width <= 768px)
        const isMobile = window.innerWidth <= 768;
        const maxLength = isMobile ? 30 : 22;
        return text.length > maxLength ? text.substring(0, maxLength) + '…' : text;
    };

    const barChartOptions = {
        chart: {
            type: 'bar',
            height: Math.max(400, sortedData.length * 50),
            width: '100%',
            toolbar: {
                show: true
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        width: '100%',
                        height: Math.max(350, sortedData.length * 45)
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true
                        }
                    },
                    dataLabels: {
                        offsetY: -10,
                        offsetX: 0
                    },
                    yaxis: {
                        title: {
                            text: 'Users',
                            offsetX: -10
                        },
                        labels: {
                            style: {
                                fontSize: '11px'
                            }
                        }
                    },
                    xaxis: {
                        title: {
                            text: 'Utilization (%)'
                        },
                        labels: {
                            style: {
                                fontSize: '10px'
                            }
                        }
                    }
                }
            }]
        },
        series: [{
            name: 'Utilization %',
            data: sortedData.map(row => parseFloat(row.balance_info?.utilization_percentage) || 0)
        }],
        xaxis: {
            categories: sortedData.map(row => getShortName(row.user_name)),
            labels: {
                style: {
                    fontSize: '12px'
                }
            },
            title: {
                text: 'Users',
                style: {
                    fontSize: '13px',
                    fontWeight: 600
                }
            }
        },
        yaxis: {
            min: 0,
            max: 100,
            title: {
                text: 'Utilization (%)',
                style: {
                    fontSize: '13px',
                    fontWeight: 600
                }
            }
        },
        colors: sortedData.map(row => {
            const util = parseFloat(row.balance_info?.utilization_percentage) || 0;
            if (util > 95) return '#ff3e1d';
            if (util > 80) return '#ffab00';
            return '#71dd37';
        }),
        plotOptions: {
            bar: {
                distributed: true,
                horizontal: false,
                borderRadius: 8,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(1) + '%';
            },
            offsetY: -20,
            style: {
                fontSize: '12px',
                fontWeight: 600
            }
        },
        grid: {
            borderColor: '#e0e0e0',
            strokeDashArray: 3
        },
        legend: {
            show: false
        }
    };

    utilizationChart = new ApexCharts(document.querySelector("#utilizationChart"), barChartOptions);
    utilizationChart.render();

    // Chart 3: Trend Line Chart
    const trendLabels = monthlyTrends?.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const trendData = monthlyTrends?.data || [5, 8, 12, 7, 15, 10];

    // Calculate trend insights
    if (trendData && trendData.length > 0) {
        const maxLeaves = Math.max(...trendData);
        const minLeaves = Math.min(...trendData);
        const avgLeaves = (trendData.reduce((a, b) => a + b, 0) / trendData.length).toFixed(1);
        const maxMonth = trendLabels[trendData.indexOf(maxLeaves)];

        let trendInsight = `Average: ${avgLeaves} days/month. Peak: ${maxMonth} with ${maxLeaves.toFixed(1)} days.`;

        if (maxLeaves > avgLeaves * 1.5) {
            trendInsight += ' <strong>High concentration in ' + maxMonth + ' - plan coverage accordingly.</strong>';
        } else {
            trendInsight += ' <strong>Relatively balanced distribution across months.</strong>';
        }

        $('#chart-insight-trends').html(trendInsight);
        $('#chart-trend-months').text(trendLabels.length + ' Months');
    } else {
        $('#chart-insight-trends').html('Insufficient data for trend analysis.');
        $('#chart-trend-months').text(APP_LABELS && APP_LABELS['na_text'] ? APP_LABELS['na_text'] : 'N/A');
    }

    const lineChartOptions = {
        chart: {
            type: 'line',
            height: 300,
            toolbar: {
                show: false
            }
        },
        series: [{
            name: 'Leaves Taken',
            data: trendData
        }],
        xaxis: {
            categories: trendLabels,
            title: {
                text: 'Months',
                style: {
                    fontSize: '13px',
                    fontWeight: 600
                }
            }
        },
        yaxis: {
            title: {
                text: 'Number of Days',
                style: {
                    fontSize: '13px',
                    fontWeight: 600
                }
            }
        },
        colors: ['#696cff'],
        stroke: {
            curve: 'smooth',
            width: 3
        },
        markers: {
            size: 5,
            hover: {
                size: 7
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.3,
                opacityFrom: 0.4,
                opacityTo: 0.1
            }
        },
        grid: {
            borderColor: '#e0e0e0',
            strokeDashArray: 3
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val.toFixed(1) + ' days';
                }
            }
        }
    };

    trendChart = new ApexCharts(document.querySelector("#trendChart"), lineChartOptions);
    trendChart.render();
}
