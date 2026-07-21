'use strict';
function queryParams(p) {
    return {
        "statuses": $('#status_filter').val(),
        "user_ids": $('#user_filter').val(),
        "created_by_user_ids": $('#user_creators_filter').val(),
        "created_by_client_ids": $('#client_creators_filter').val(),
        "month": $('#filter_payslip_month').val(),
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}
addDebouncedEventListener('#status_filter, #user_filter, #user_creators_filter, #client_creators_filter, #filter_payslip_month', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#payslips_table').bootstrapTable('refresh');
    }
});
$(document).on('click', '.clear-payslips-filters', function (e) {
    e.preventDefault();
    $('#filter_payslip_month').val('');
    $('#status_filter').val('').trigger('change', [0]);
    $('#user_filter').val('').trigger('change', [0]);
    $('#user_creators_filter').val('').trigger('change', [0]);
    $('#client_creators_filter').val('').trigger('change', [0]);
    $('#payslips_table').bootstrapTable('refresh');
})
window.icons = {
    refresh: 'bx-refresh',
    toggleOn: 'bx-toggle-right',
    toggleOff: 'bx-toggle-left'
}
function loadingTemplate(message) {
    return '<i class="bx bx-loader-alt bx-spin bx-flip-vertical" ></i>'
}
function idFormatter(value, row, index) {
    return [
        '<a href="' + baseUrl + '/payslips/view/' + row.id + '" target="_blank">' + label_payslip_id_prefix + row.id + '</a>'
    ];
}
var currentDate = new Date();
// Get the year and month of the previous month
var previousMonth = new Date(currentDate);
previousMonth.setMonth(currentDate.getMonth() - 1);
var previousYear = previousMonth.getFullYear();
var previousMonthNumber = previousMonth.getMonth() + 1; // Month is zero-based, so add 1
// Calculate the last day of the previous month
var lastDayOfPreviousMonth = new Date(previousYear, previousMonthNumber, 0).getDate();
// Format the date as "YYYY-MM"
var formattedDate = previousYear + '-' + (previousMonthNumber < 10 ? '0' : '') + previousMonthNumber;
// Set the formatted date and last day as input values
$('#payslip_month').val(formattedDate);
$('#working_days').val(lastDayOfPreviousMonth);
$('#payslip_month').on('change', function () {
    var selectedValue = $(this).val();
    if (selectedValue) {
        // Split the selected value into year and month
        var selectedYear = parseInt(selectedValue.split("-")[0]);
        var selectedMonth = parseInt(selectedValue.split("-")[1]);
        // Calculate the last day of the selected month
        var lastDay = new Date(selectedYear, selectedMonth, 0).getDate();
        // Display the total days in a paragraph
        $('#working_days').val(lastDay);
    } else {
        // If no value is selected, clear the total days display
        $('#working_days').val("");
    }
});
// $(document).on('ready', function () {
// Get references to the input fields
var basicSalaryInput = $('#basic_salary');
var workingDaysInput = $('#working_days');
var lopDaysInput = $('#lop_days');
var paidDaysInput = $('#paid_days');
var leaveDeductionInput = $('#leave_deduction');
var overTimeHoursInput = $('#over_time_hours');
var overTimeRateInput = $('#over_time_rate');
var overTimePaymentInput = $('#over_time_payment');
var bonusInput = $('#bonus');
var incentivesInput = $('#incentives');
var perDayPayment = 0;
var leaveSummaryState = {
    workingDays: parseFloat(workingDaysInput.val()) || 0,
    paidDays: parseFloat(paidDaysInput.val()) || 0,
    totalLeaveDays: 0,
    paidLeaveDays: 0,
    lopDays: parseFloat(lopDaysInput.val()) || 0,
    baseline: {
        totalLeaveDays: 0,
        paidLeaveDays: 0,
        lopDays: 0,
        unpaidLeavesTaken: 0
    },
    annual: {
        totalAnnualLeaves: 0,
        accruedLeaves: null,
        usedPaidLeaves: 0,
        remainingPaidLeaves: 0,
        unpaidLeavesTaken: 0,
        utilizationPercentage: 0,
        accrualUtilizationPercentage: null
    },
    initialAnnual: null
};

function formatLeaveValue(value, decimals = 2) {
    var num = parseFloat(value);
    if (isNaN(num)) {
        num = 0;
    }
    var fixed = num.toFixed(decimals);
    if (decimals > 0) {
        fixed = fixed.replace(/\.?0+$/, '');
    }
    return fixed;
}

function formatPercentage(value) {
    return formatLeaveValue(value, 1);
}

function renderMonthlyBreakdown() {
    if (!$('#leave-summary').length) {
        return;
    }
    var workingDays = leaveSummaryState.workingDays;
    var lopDays = leaveSummaryState.lopDays;
    var paidDays = leaveSummaryState.paidDays;
    var baselineTotal = leaveSummaryState.baseline.totalLeaveDays;
    var baselinePaid = leaveSummaryState.baseline.paidLeaveDays;
    var paidLeaveDays = baselinePaid || 0;
    if (!baselinePaid && baselineTotal === 0) {
        paidLeaveDays = leaveSummaryState.paidLeaveDays || 0;
    } else if (!baselinePaid && baselineTotal > 0) {
        paidLeaveDays = Math.max(baselineTotal - lopDays, 0);
    }
    leaveSummaryState.paidLeaveDays = paidLeaveDays;
    var totalLeaveDays = baselineTotal || (paidLeaveDays + lopDays);
    leaveSummaryState.totalLeaveDays = totalLeaveDays;

    $('#monthly_paid_leave_days').text(formatLeaveValue(paidLeaveDays));
    $('#monthly_lop_days').text(formatLeaveValue(lopDays));
    $('#monthly_working_days').text(formatLeaveValue(workingDays));
    $('#monthly_paid_days_display').text(formatLeaveValue(paidDays));
    $('#monthly_total_leave_days').text(formatLeaveValue(totalLeaveDays));
}

function renderLeaveSummary() {
    if (!$('#leave-summary').length) {
        return;
    }
    var baseline = leaveSummaryState.baseline || {};
    var baselineTotalLeaveDays = baseline.totalLeaveDays || 0;
    var baselinePaidLeaves = baseline.paidLeaveDays || 0;
    var baselineLopDays = baseline.lopDays || 0;

    var currentLopDays = leaveSummaryState.lopDays || 0;
    var currentPaidDays = leaveSummaryState.paidDays || 0;

    var currentPaidLeaveDays = baselineTotalLeaveDays > 0
        ? Math.max(baselineTotalLeaveDays - currentLopDays, 0)
        : leaveSummaryState.paidLeaveDays || 0;

    leaveSummaryState.paidLeaveDays = currentPaidLeaveDays;
    leaveSummaryState.totalLeaveDays = baselineTotalLeaveDays > 0
        ? baselineTotalLeaveDays
        : currentPaidLeaveDays + currentLopDays;

    var initialAnnual = leaveSummaryState.initialAnnual || leaveSummaryState.annual;
    var totalAnnual = initialAnnual.totalAnnualLeaves || 0;
    var accruedLeaves = initialAnnual.accruedLeaves;
    if (accruedLeaves === null || typeof accruedLeaves === 'undefined') {
        accruedLeaves = totalAnnual;
    }

    var paidDelta = currentPaidLeaveDays - baselinePaidLeaves;
    var lopDelta = currentLopDays - baselineLopDays;
    var usedPaidLeaves = (initialAnnual.usedPaidLeaves || 0) + paidDelta;
    var remainingPaidLeaves = (initialAnnual.remainingPaidLeaves || 0) - paidDelta;
    var unpaidLeaves = (initialAnnual.unpaidLeavesTaken || 0) + lopDelta;

    if (remainingPaidLeaves < 0) {
        remainingPaidLeaves = 0;
    }
    if (unpaidLeaves < 0) {
        unpaidLeaves = 0;
    }
    if (usedPaidLeaves < 0) {
        usedPaidLeaves = 0;
    }

    leaveSummaryState.annual.usedPaidLeaves = usedPaidLeaves;
    leaveSummaryState.annual.remainingPaidLeaves = remainingPaidLeaves;
    leaveSummaryState.annual.unpaidLeavesTaken = unpaidLeaves;

    $('.annual-total-leaves').text(formatLeaveValue(totalAnnual));
    $('.annual-accrued-leaves').text(formatLeaveValue(accruedLeaves));
    $('#annual_used_paid_leaves').text(formatLeaveValue(usedPaidLeaves));
    $('#annual_remaining_paid_leaves').text(formatLeaveValue(remainingPaidLeaves));
    $('#annual_unpaid_leaves').text(formatLeaveValue(unpaidLeaves));

    // Calculate and display total leaves taken (paid + unpaid)
    var totalLeavesTaken = usedPaidLeaves + unpaidLeaves;
    $('#annual_total_leaves_taken').text(formatLeaveValue(totalLeavesTaken));
    $('#annual_used_paid_leaves_breakdown').text(formatLeaveValue(usedPaidLeaves));
    $('#annual_unpaid_leaves_breakdown').text(formatLeaveValue(unpaidLeaves));

    $('#monthly_total_leave_days').text(formatLeaveValue(leaveSummaryState.totalLeaveDays));
}

function refreshLeaveSummaryState(options) {
    options = options || {};
    leaveSummaryState.workingDays = parseFloat(workingDaysInput.val()) || 0;
    leaveSummaryState.lopDays = parseFloat(lopDaysInput.val()) || 0;

    var baselineTotal = leaveSummaryState.baseline.totalLeaveDays;
    var totalLeaveDays = baselineTotal > 0 ? baselineTotal : leaveSummaryState.totalLeaveDays;

    if (!options.preservePaidLeave) {
        if (baselineTotal > 0) {
            leaveSummaryState.paidLeaveDays = Math.max(baselineTotal - leaveSummaryState.lopDays, 0);
        } else {
            leaveSummaryState.paidLeaveDays = Math.max(leaveSummaryState.paidLeaveDays, 0);
        }
    } else {
        totalLeaveDays = leaveSummaryState.paidLeaveDays + leaveSummaryState.lopDays;
    }

    leaveSummaryState.totalLeaveDays = leaveSummaryState.paidLeaveDays + leaveSummaryState.lopDays;
    if (leaveSummaryState.totalLeaveDays === 0 && totalLeaveDays > 0) {
        leaveSummaryState.totalLeaveDays = totalLeaveDays;
    }
    leaveSummaryState.paidDays = parseFloat(paidDaysInput.val()) || Math.max(leaveSummaryState.workingDays - leaveSummaryState.lopDays, 0);

    if (!options.skipRender) {
        renderMonthlyBreakdown();
        renderLeaveSummary();
    }
}

function applyLeaveSummaryData(summaryData) {
    var breakdown = summaryData.breakdown || {};
    var annualSummary = summaryData.annual_summary || {};

    leaveSummaryState.baseline = {
        totalLeaveDays: parseFloat(breakdown.total_leave_days) || ((parseFloat(breakdown.paid_leave_days) || 0) + (parseFloat(breakdown.unpaid_leave_days) || 0)),
        paidLeaveDays: parseFloat(breakdown.paid_leave_days) || 0,
        lopDays: parseFloat(breakdown.unpaid_leave_days) || 0,
        unpaidLeavesTaken: typeof annualSummary.unpaid_leaves_taken !== 'undefined'
            ? parseFloat(annualSummary.unpaid_leaves_taken) || 0
            : 0
    };

    leaveSummaryState.totalLeaveDays = leaveSummaryState.baseline.totalLeaveDays;
    leaveSummaryState.paidLeaveDays = leaveSummaryState.baseline.paidLeaveDays;
    leaveSummaryState.lopDays = parseFloat(summaryData.lop_days) || leaveSummaryState.baseline.lopDays;
    leaveSummaryState.workingDays = parseFloat(summaryData.working_days) || leaveSummaryState.workingDays;
    leaveSummaryState.paidDays = parseFloat(summaryData.paid_days) || Math.max(leaveSummaryState.workingDays - leaveSummaryState.lopDays, 0);

    leaveSummaryState.annual = {
        totalAnnualLeaves: parseFloat(annualSummary.total_annual_leaves) || 0,
        accruedLeaves: typeof annualSummary.accrued_leaves !== 'undefined' && annualSummary.accrued_leaves !== null
            ? parseFloat(annualSummary.accrued_leaves)
            : null,
        usedPaidLeaves: parseFloat(annualSummary.used_paid_leaves) || 0,
        remainingPaidLeaves: parseFloat(annualSummary.remaining_paid_leaves) || 0,
        unpaidLeavesTaken: parseFloat(annualSummary.unpaid_leaves_taken) || 0,
        utilizationPercentage: parseFloat(annualSummary.utilization_percentage) || 0,
        accrualUtilizationPercentage: typeof annualSummary.accrual_utilization_percentage !== 'undefined' && annualSummary.accrual_utilization_percentage !== null
            ? parseFloat(annualSummary.accrual_utilization_percentage)
            : null
    };
    leaveSummaryState.initialAnnual = Object.assign({}, leaveSummaryState.annual);

    $('#total_leave_days_value').val(formatLeaveValue(leaveSummaryState.totalLeaveDays));
    refreshLeaveSummaryState({ preserveBaseline: true, preservePaidLeave: true });
}
// Function to calculate over time payment
function calculateOverTimePayment() {
    var overtimeHours = parseFloat(overTimeHoursInput.val()) || 0;
    var overtimeRate = parseFloat(overTimeRateInput.val()) || 0;
    var overTimePayment = overtimeHours * overtimeRate;
    overTimePaymentInput.val(overTimePayment.toFixed(decimal_points));
    calculateTotalEarning();
}
// Calculate over time payment on change of hours or rate
overTimeHoursInput.on('change', calculateOverTimePayment);
overTimeRateInput.on('change', calculateOverTimePayment);
// Function to calculate per-day payment
function calculatePerDayPayment() {
    var basicSalary = parseFloat(basicSalaryInput.val()) || 0;
    var workingDays = parseFloat(workingDaysInput.val()) || 0;
    if (workingDays > 0) {
        perDayPayment = parseFloat((basicSalary / workingDays).toFixed(decimal_points));
    } else {
        perDayPayment = 0;
    }
    calculateLeaveDeduction();
    calculatePaidDays();
    refreshLeaveSummaryState();
}
// Calculate per-day payment on change of basic salary or working days
basicSalaryInput.on('change', calculatePerDayPayment);
workingDaysInput.on('change', calculatePerDayPayment);
workingDaysInput.on('input', calculatePerDayPayment);
lopDaysInput.on('input', calculatePerDayPayment);
// Function to calculate leave deduction
function calculateLeaveDeduction() {
    var lopDays = parseFloat(lopDaysInput.val()) || 0;
    var leaveDeduction = perDayPayment * lopDays;
    leaveDeductionInput.val(leaveDeduction.toFixed(decimal_points));
    calculateTotalEarning();
}
// Function to calculate paid days (working days minus LOP days)
function calculatePaidDays() {
    var workingDays = parseFloat(workingDaysInput.val()) || 0;
    var lopDays = parseFloat(lopDaysInput.val()) || 0;
    var paidDays = workingDays - lopDays;
    if (paidDays < 0) {
        paidDays = 0;
    }
    paidDaysInput.val(paidDays);
    leaveSummaryState.paidDays = paidDays;
}
// Function to calculate total earnings based on inputs
function calculateTotalEarning() {
    var basicSalary = parseFloat(basicSalaryInput.val()) || 0;
    var bonus = parseFloat(bonusInput.val()) || 0;
    var incentives = parseFloat(incentivesInput.val()) || 0;
    var overTimePayment = parseFloat(overTimePaymentInput.val()) || 0;
    var totalEarning = basicSalary + bonus + incentives + overTimePayment;
    // Update the Total Earnings field
    $('#total_earning').text(totalEarning.toFixed(decimal_points));
    // Calculate Net Payable
    calculateNetPayable(totalEarning);
}
// Function to calculate net payable based on inputs
function calculateNetPayable(totalEarning) {
    var leaveDeduction = parseFloat(leaveDeductionInput.val()) || 0;
    var netPayable = totalEarning - leaveDeduction;
    $('#net_payable').text(netPayable.toFixed(decimal_points));
}
// Attach change event listeners to all related input fields
$('#basic_salary, #working_days, #lop_days, #bonus, #incentives').on('change', function () {
    calculatePerDayPayment();
});
// Initially, calculate the values based on the default values
calculateOverTimePayment();
calculatePerDayPayment();
calculateTotalEarning();
refreshLeaveSummaryState();
// });
$('#allowance_id').on('change', function () {
    var id = $(this).val();
    if (id !== null && id !== '') {
        $.ajax({
            url: baseUrl + '/allowances/get/' + id,
            type: 'get',
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').attr('value') // Replace with your method of getting the CSRF token
            },
            dataType: 'json',
            success: function (response) {
                $('#allowance_0_title').val(response.allowance.title);
                $('#allowance_0_amount').val(parseFloat(response.allowance.amount).toFixed(decimal_points));
            },
        });
    } else {
        $('#allowance_0_title').val('');
        $('#allowance_0_amount').val('');
    }
});
$('#allowance_id').on('select2:clear', function () {
    $('#allowance_0_title').val('');
    $('#allowance_0_amount').val('');
});
$('.add-allowance').on('click', function (e) {
    e.preventDefault();
    var html = '';
    var title = $("#allowance_0_title").val();
    if (title != '') {
        var allowance_id = $("#allowance_id").val();
        var allowance_ids = $("#allowance_ids").val();
        allowance_ids = allowance_ids.split(',');
        var exists = allowance_ids.includes(allowance_id);
        if (!exists) {
            allowance_count++
            allowance_ids = allowance_ids.toString();
            if (allowance_ids != '') {
                allowance_ids = allowance_ids + ',' + allowance_id;
            } else {
                allowance_ids = allowance_id;
            }
            $("#allowance_ids").val(allowance_ids)
            var amount = $("#allowance_0_amount").val();
            html = '<div class="payslip-allowance"><div class="d-flex">' +
                '<input type="hidden" id=allowance_' + allowance_count + ' name="allowances[]">' +
                '<div class="mb-3 col-md-6 mx-1">' +
                '<input type="text" id="allowance_' + allowance_count + '_title" class="form-control" placeholder="Allowance" readonly>' +
                '</div>' +
                '<div class="mb-3 col-md-4 mx-1">' +
                '<input type="number" id="allowance_' + allowance_count + '_amount" class="form-control" placeholder="Amount" disabled>' +
                '</div>' +
                '<div class="mb-3 col-md-1 mx-1">' +
                '<button type="button" class="btn btn-sm btn-danger remove-allowance" data-count="' + allowance_count + '"><i class="bx bx-trash"></i></button>' +
                '</div>' +
                '</div></div>';
            $('#payslip-allowances').append(html);
            $('#allowance_' + allowance_count).val(allowance_id);
            $('#allowance_' + allowance_count + '_title').val(title);
            $('#allowance_' + allowance_count + '_amount').val(parseFloat(amount).toFixed(decimal_points));
            // Update Total Earnings and Net Pay when an allowance is added
            var total_allowance = parseFloat($('#total_allowance').text());
            var total_earning = parseFloat($('#total_earning').text());
            var net_pay = parseFloat($('#net_payable').text());
            if (!isNaN(total_allowance) && !isNaN(total_earning) && !isNaN(net_pay)) {
                total_allowance += parseFloat(amount);
                total_earning += parseFloat(amount);
                net_pay += parseFloat(amount);
                $('#total_allowance').text(total_allowance.toFixed(decimal_points));
                $('#hidden_total_allowance').val(total_allowance.toFixed(decimal_points));
                $('#total_earning').text(total_earning.toFixed(decimal_points));
                $('#total_earnings').val(total_earning.toFixed(decimal_points));
                $('#net_payable').text(net_pay.toFixed(decimal_points));
            }
            $("#allowance_0_title").val('');
            $("#allowance_0_amount").val('');
            $('#allowance_id').val('');
        } else {
            toastr.error('Allowance already added.');
        }
    } else {
        toastr.error('Please choose allowance.');
    }
});
$(document).on('click', '.remove-allowance', function (e) {
    e.preventDefault();
    var count = $(this).data('count');
    var allowance_id = $("#allowance_" + count).val();
    var amount = parseFloat($("#allowance_" + count + "_amount").val());
    var allowance_ids = $("#allowance_ids").val().split(','); // Split the string into an array
    var index = $.inArray(allowance_id.toString(), allowance_ids);
    if (index !== -1) {
        // Remove the allowance_id from the array
        allowance_ids.splice(index, 1);
        // Update the #allowance_ids input value with the modified string
        $("#allowance_ids").val(allowance_ids.join(',')); // Join the array back into a string
    }
    var total_allowance = parseFloat($('#total_allowance').text());
    total_allowance = total_allowance - amount;
    if (isNaN(total_allowance) || total_allowance < 0) {
        total_allowance = 0;
    }
    total_allowance = total_allowance.toFixed(decimal_points);
    $('#total_allowance').text(total_allowance);
    $('#hidden_total_allowance').val(total_allowance);
    var total_earning = parseFloat($('#total_earning').text());
    total_earning = total_earning - amount;
    total_earning = total_earning.toFixed(decimal_points);
    $('#total_earning').text(total_earning);
    $('#total_earnings').val(total_earning);
    var net_pay = parseFloat($('#net_payable').text());
    net_pay = net_pay - amount;
    net_pay = net_pay.toFixed(decimal_points);
    $('#net_payable').text(net_pay);
    $(this).closest('.payslip-allowance').remove();
});
$('#deduction_id').on('change', function () {
    var id = $(this).val();
    if (id !== null && id !== '') {
        $.ajax({
            url: baseUrl + '/deductions/get/' + id,
            type: 'get',
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').attr('value') // Replace with your method of getting the CSRF token
            },
            dataType: 'json',
            success: function (response) {
                $('#deduction_0_title').val(response.deduction.title);
                $('#deduction_0_type').val(response.deduction.type);
                $('#deduction_0_amount').val(parseFloat(response.deduction.amount).toFixed(decimal_points));
                $('#deduction_0_percentage').val(response.deduction.percentage);
            },
        });
    } else {
        $('#deduction_0_title').val('');
        $('#deduction_0_amount').val('');
        $('#deduction_0_percentage').val('');
        $('#deduction_0_type').val('');
    }
});
$('#deduction_id').on('select2:clear', function () {
    $('#deduction_0_title').val('');
    $('#deduction_0_amount').val('');
    $('#deduction_0_percentage').val('');
    $('#deduction_0_type').val('');
});
$(document).on('click', '.add-deduction', function (e) {
    e.preventDefault();
    var html = '';
    var title = $("#deduction_0_title").val();
    if (title != '') {
        var deduction_id = $("#deduction_id").val();
        var deduction_ids = $("#deduction_ids").val();
        deduction_ids = deduction_ids.split(',');
        var exists = deduction_ids.includes(deduction_id);
        if (!exists) {
            deduction_count++;
            deduction_ids = deduction_ids.toString();
            if (deduction_ids != '') {
                deduction_ids = deduction_ids + ',' + deduction_id;
            }
            else {
                deduction_ids = deduction_id;
            }
            $("#deduction_ids").val(deduction_ids); // Update the hidden input value
            var amount = $("#deduction_0_amount").val();
            var percentage = $("#deduction_0_percentage").val();
            var type = $("#deduction_0_type").val();
            html = '<div class="payslip-deduction"><div class="d-flex">' +
                '<input type="hidden" id="deduction_' + deduction_count + '" name="deductions[]">' +
                '<div class="mb-3 col-md-5 mx-1">' +
                '<input type="text" id="deduction_' + deduction_count + '_title" class="form-control" placeholder="Deduction" readonly>' +
                '</div>' +
                '<input type="hidden" id="deduction_' + deduction_count + '_type"></input>' +
                '<div class="mb-3 col-md-3 mx-1">' +
                '<input type="number" id="deduction_' + deduction_count + '_amount" class="form-control" placeholder="Amount" disabled>' +
                '</div>' +
                '<div class="mb-3 col-md-3 mx-1">' +
                '<input type="number" id="deduction_' + deduction_count + '_percentage" class="form-control" placeholder="Percentage" disabled>' +
                '</div>' +
                '<div class="mb-3 col-md-1 mx-1">' +
                '<button type="button" class="btn btn-sm btn-danger remove-deduction" data-count="' + deduction_count + '" data-bs-toggle="tooltip" data-bs-placement="right"><i class="bx bx-trash"></i></button>' +
                '</div>' +
                '</div></div>';
            $('#payslip-deductions').append(html);
            $('#deduction_' + deduction_count).val(deduction_id);
            $('#deduction_' + deduction_count + '_title').val(title);
            $('#deduction_' + deduction_count + '_type').val(type);
            var total_deduction = parseFloat($('#total_deduction').text());
            var total_earning = parseFloat($('#total_earning').text());
            var net_pay = parseFloat($('#net_payable').text());
            if (type == 'amount') {
                total_deduction = +total_deduction + +amount;
                total_earning = total_earning - parseFloat(amount);
                net_pay = net_pay - parseFloat(amount);
                $('#deduction_' + deduction_count + '_amount').val(parseFloat(amount).toFixed(decimal_points));
                var deduction_amount = $('#deduction_0_amount').val();
            } else {
                $('#deduction_' + deduction_count + '_percentage').val(percentage);
                var net_payable = parseFloat($('#net_payable').text()) || 0;
                var deduction_amount = (percentage / 100) * net_payable;
                total_deduction = +total_deduction + +deduction_amount;
            }
            total_deduction = parseFloat(total_deduction).toFixed(decimal_points);
            $('#total_deduction').text(total_deduction);
            $('#hidden_total_deductions').val(total_deduction);
            var total_earning = parseFloat($('#total_earning').text());
            total_earning = total_earning - deduction_amount;
            total_earning = total_earning.toFixed(decimal_points);
            $('#total_earning').text(total_earning);
            $('#total_earnings').val(total_earning);
            var net_pay = parseFloat($('#net_payable').text());
            net_pay = net_pay - deduction_amount;
            net_pay = net_pay.toFixed(decimal_points);
            $('#net_payable').text(net_pay);
            $("#deduction_0_title").val('');
            $("#deduction_0_amount").val('');
            $("#deduction_0_percentage").val('');
            $("#deduction_0_type").val('');
            $('#deduction_id').val('');
        } else {
            toastr.error('Deduction already added.');
        }
    } else {
        toastr.error('Please choose deduction.');
    }
});
$(document).on('click', '.remove-deduction', function (e) {
    e.preventDefault();
    var count = $(this).data('count');
    var deduction_ids = $("#deduction_ids").val();
    var deduction_id = $("#deduction_" + count).val();
    var type = $("#deduction_" + count + "_type").val();
    var deduction_amount = 0;
    if (type == 'amount') {
        deduction_amount = parseFloat($("#deduction_" + count + "_amount").val()) || 0;
    } else {
        var percentage = parseFloat($("#deduction_" + count + "_percentage").val()) || 0;
        var net_payable = parseFloat($('#net_payable').text()) || 0;
        deduction_amount = (percentage / 100) * net_payable;
    }
    var total_deduction = parseFloat($('#total_deduction').text()) || 0;
    total_deduction = total_deduction - deduction_amount;
    total_deduction = total_deduction.toFixed(decimal_points);
    $('#total_deduction').text(total_deduction);
    $('#hidden_total_deductions').val(total_deduction);
    var total_earning = parseFloat($('#total_earning').text()) || 0;
    total_earning = total_earning + deduction_amount;
    total_earning = total_earning.toFixed(decimal_points);
    $('#total_earning').text(total_earning);
    $('#total_earnings').val(total_earning);
    var net_pay = parseFloat($('#net_payable').text()) || 0;
    net_pay = net_pay + deduction_amount;
    net_pay = net_pay.toFixed(decimal_points);
    $('#net_payable').text(net_pay);
    // Remove the deduction from the hidden input
    var deduction_ids = $("#deduction_ids").val().split(','); // Split the string into an array
    var index = $.inArray(deduction_id.toString(), deduction_ids);
    if (index !== -1) {
        // Remove the allowance_id from the array
        deduction_ids.splice(index, 1);
        // Update the #allowance_ids input value with the modified string
        $("#deduction_ids").val(deduction_ids.join(',')); // Join the array back into a string
    }
    // Remove the deduction element from the DOM
    $(this).closest('.payslip-deduction').remove();
});
$(document).ready(function () {
    $('input[name="status"]').change(function () {
        if ($(this).val() == '0') { // If "unpaid" is selected
            $('#payment_date').val(''); // Clear the payment date
            $('select[name="payment_method_id"]').val('');
        }
    });
});

$(document).ready(function () {
    // Initialize TableFilterSync for users
    const payslipFilterSync = new TableFilterSync({
        tableId: 'payslips_table',
        dataType: 'payslips',
        filters: [
            {
                selector: '#filter_payslip_month',
                type: 'month',
                name: 'month',
            },
            {
                selector: '#user_creators_filter',
                type: 'select2',
                name: 'created_by_user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#user_filter',
                type: 'select2',
                name: 'user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#client_creators_filter',
                type: 'select2',
                name: 'created_by_client_ids',
                ajaxType: 'clients'
            },
            {
                selector: '#status_filter',
                type: 'select2',
                name: 'statuses',
                ajaxType: null
            }
        ],
        preserveParams: [''],
        queryParamsFn: queryParams // Reuse existing function
    });
});

// Auto-calc from leave integration
function fetchLeaveSummary() {
    var userId = $('select[name="user_id"]').val();
    var month = $('#payslip_month').val();
    var basic = $('#basic_salary').val();

    if (!userId || !month || !basic) {
        if (!userId) {
            toastr.warning('Please select a user first.');
        } else if (!month) {
            toastr.warning('Please select a payslip month first.');
        } else if (!basic) {
            toastr.warning('Please enter basic salary first.');
        }
        return;
    }

    // Save original button text and show loading state
    var $btn = $('#recalc_from_leave_btn');
    var originalBtnText = $btn.html();
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Loading...');

    $.ajax({
        url: baseUrl + '/payslips/leave-summary',
        type: 'GET',
        data: {
            user_id: userId,
            month: month,
            basic_salary: basic
        },
        success: function (res) {
            if (res && res.data) {
                // Update all fields first without triggering change events
                $('#working_days').val(res.data.working_days);
                $('#lop_days').val(res.data.lop_days);
                $('#paid_days').val(res.data.paid_days);
                $('#leave_deduction').val(parseFloat(res.data.leave_deduction).toFixed(decimal_points));

                applyLeaveSummaryData(res.data);
                calculatePerDayPayment();
                calculateTotalEarning();
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching leave summary:', error);
            var errorMsg = 'Failed to fetch leave summary.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 422) {
                errorMsg = 'Validation error. Please check your inputs.';
            }
            toastr.error(errorMsg);
        },
        complete: function () {
            // Restore button state
            $btn.prop('disabled', false).html(originalBtnText);
        }
    });
}

// Toggle and button wiring
$(document).on('change', '#auto_from_leave_toggle', function () {
    var on = $(this).is(':checked');
    $('#auto_from_leave').val(on ? '1' : '0');
    if (on) {
        fetchLeaveSummary();
    }
});

$(document).on('click', '#recalc_from_leave_btn', function () {
    fetchLeaveSummary();
});

// Trigger recalculation on key changes if auto toggle is on
$(document).on('change', 'select[name="user_id"], #payslip_month, #basic_salary', function () {
    if ($('#auto_from_leave').val() === '1') {
        fetchLeaveSummary();
    }
});

// Override confirmation handling for payslip form
var pendingPayslipFormData = null;
var pendingPayslipForm = null;

// Define function and attach to window for global access
function showOverrideConfirmationModal(overrideData, form, formData) {
    console.log('[showOverrideConfirmationModal] Called with data:', overrideData);

    // Check if modal element exists
    var modalElement = document.getElementById('overrideConfirmationModal');
    if (!modalElement) {
        console.error('[showOverrideConfirmationModal] Modal element #overrideConfirmationModal not found!');
        alert((APP_LABELS && APP_LABELS['override_required'] ? APP_LABELS['override_required'] : 'Override Required!') + '\n\n' +
            'Available Balance: ' + (overrideData?.available_balance || 0) + '\n' +
            'Excess Paid Leave: ' + (overrideData?.excess_paid_leave || 0) + '\n' +
            'Delta Paid Leave: ' + (overrideData?.delta_paid_leave || 0) + '\n\n' +
            'Modal element not found. Please refresh the page.');
        return;
    }

    // Store form data for resubmission
    pendingPayslipFormData = formData;
    pendingPayslipForm = form;

    // Populate modal with data
    var availableBalanceEl = $('#override_available_balance');
    var deltaPaidLeaveEl = $('#override_delta_paid_leave');
    var excessPaidLeaveEl = $('#override_excess_paid_leave');

    if (availableBalanceEl.length) {
        availableBalanceEl.text(parseFloat(overrideData.available_balance || 0).toFixed(2));
    }
    if (deltaPaidLeaveEl.length) {
        deltaPaidLeaveEl.text(parseFloat(overrideData.delta_paid_leave || 0).toFixed(2));
    }
    if (excessPaidLeaveEl.length) {
        excessPaidLeaveEl.text(parseFloat(overrideData.excess_paid_leave || 0).toFixed(2));
    }

    console.log('[showOverrideConfirmationModal] Modal elements populated');

    // Show modal using Bootstrap 5
    try {
        var modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('[showOverrideConfirmationModal] Modal shown successfully');
    } catch (error) {
        console.error('[showOverrideConfirmationModal] Error showing modal:', error);
        // Fallback to jQuery if Bootstrap fails
        $('#overrideConfirmationModal').modal('show');
    }
}

// Attach to window object to ensure global accessibility
window.showOverrideConfirmationModal = showOverrideConfirmationModal;

// Confirm override and resubmit
$(document).on('click', '#confirmOverride', function () {
    if (pendingPayslipFormData && pendingPayslipForm) {
        // Add override_confirmed flag
        pendingPayslipFormData.append('override_confirmed', '1');

        // Hide modal
        var modal = bootstrap.Modal.getInstance(document.getElementById('overrideConfirmationModal'));
        if (modal) {
            modal.hide();
        }

        // Resubmit form
        var form = pendingPayslipForm;
        var submit_btn = form.find("#submit_btn");
        var btn_html = submit_btn.html();
        var redirect_url = form.find('input[name="redirect_url"]').val();

        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: pendingPayslipFormData,
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            beforeSend: function () {
                submit_btn.html(label_please_wait || "Please wait...");
                submit_btn.attr("disabled", true);
            },
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (result) {
                submit_btn.html(btn_html);
                submit_btn.attr("disabled", false);

                // Check again for override (shouldn't happen, but handle gracefully)
                if (result["override_required"] === true) {
                    showOverrideConfirmationModal(result.override_data, form, pendingPayslipFormData);
                    return;
                }

                if (result["error"] == true) {
                    toastr.error(result["message"]);
                } else {
                    toastr.success(result["message"] || "Payslip saved successfully.");
                    setTimeout(function () {
                        if (redirect_url) {
                            window.location.href = redirect_url;
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                }

                // Clear pending data
                pendingPayslipFormData = null;
                pendingPayslipForm = null;
            },
            error: function (xhr) {
                submit_btn.html(btn_html);
                submit_btn.attr("disabled", false);

                var errorMsg = "An error occurred.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);

                // Clear pending data
                pendingPayslipFormData = null;
                pendingPayslipForm = null;
            }
        });
    }
});
