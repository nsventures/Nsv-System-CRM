window.actionEvents = {
    'click .approve-manual-time': function (e, value, row, index) {
        openApprovalModal(row.id);
    }
};

function queryParams(params) {
    const dateRange = $('#dateRange').data('daterangepicker');
    if (dateRange) {
        params.start_date = dateRange.startDate.format('YYYY-MM-DD');
        params.end_date = dateRange.endDate.format('YYYY-MM-DD');
    }
    if (isAdminOrHasAllDataAccess || canApprove) {
        params.user_id = $('#userFilter').val();
    }
    return params;
}

function statusFormatter(value) {
    let badgeClass = 'bg-secondary';
    if (value === 'Approved') badgeClass = 'bg-success';
    else if (value === 'Rejected') badgeClass = 'bg-danger';
    else if (value === 'Pending') badgeClass = 'bg-warning';
    return `<span class="badge ${badgeClass}">${value}</span>`;
}

function actionsFormatter(value) {
    return value !== '-' ? value : '-';
}

function openApprovalModal(id) {
    $('#approvalModal').modal('show');
    $('#approveManualTimeForm')[0].reset();
    $('#remarksDiv').addClass('d-none');
    $('#approvalBtnText').text('Approve');

    $.ajax({
        url: manualTimeFetch,
        method: 'GET',
        data: { id: id },
        success: function (data) {
            $('#manual_time_id').val(data.id);
            $('#employee_name').val(data.employee_name);
            $('#date').val(data.date);
            $('#start_time').val(data.start_time);
            $('#end_time').val(data.end_time);
            $('#reason').val(data.reason);
            $('#approval_status').val('').trigger('change');
        },
        error: function () {
            toastr.error('Failed to fetch manual time details.');
        }
    });
}

$(document).ready(function () {
    $('#dateRange').daterangepicker({
        startDate: moment().subtract(6, 'days'),
        endDate: moment().endOf('day'),
        maxDate: moment().endOf('day'),
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
    });

    $('#refreshTable, #applyFilters').on('click', function () {
        $('#manualTimeTable').bootstrapTable('refresh');
    });

    $('#addManualTimeBtn').on('click', function () {
        $('#addManualTimeModal').modal('show');
        $('#addManualTimeForm')[0].reset();
        $('.select2-employee').val(null).trigger('change');
        $('#addManualTimeForm .invalid-feedback').text('');
        $('#addManualTimeForm .is-invalid').removeClass('is-invalid');
    });

    // Real-time validation for date and time
    $('#addManualTimeForm [name="date"]').on('change', function () {
        const $this = $(this);
        const selectedDate = $this.val();
        const today = moment().format('YYYY-MM-DD');
        if (selectedDate > today) {
            $this.addClass('is-invalid');
            $this.next('.invalid-feedback').text('Future dates are not allowed.');
            $this.val('');
        } else {
            $this.removeClass('is-invalid');
            $this.next('.invalid-feedback').text('');
        }
        validateTimeInputs();
    });

    $('#addManualTimeForm [name="start_time"], #addManualTimeForm [name="end_time"]').on('change', function () {
        validateTimeInputs();
    });

    function validateTimeInputs() {
        const $date = $('#addManualTimeForm [name="date"]');
        const $startTime = $('#addManualTimeForm [name="start_time"]');
        const $endTime = $('#addManualTimeForm [name="end_time"]');
        const date = $date.val();
        const startTime = $startTime.val();
        const endTime = $endTime.val();
        const now = moment();
        const selectedDate = moment(date);

        $startTime.removeClass('is-invalid');
        $endTime.removeClass('is-invalid');
        $startTime.next('.invalid-feedback').text('');
        $endTime.next('.invalid-feedback').text('');

        if (date && startTime && endTime) {
            const startDateTime = moment(`${date} ${startTime}`);
            const endDateTime = moment(`${date} ${endTime}`);

            // Check if times are in the future
            if (selectedDate.isSame(now, 'day')) {
                if (startDateTime.isAfter(now)) {
                    $startTime.addClass('is-invalid');
                    $startTime.next('.invalid-feedback').text('Start time cannot be in the future.');
                    $startTime.val('');
                    return false;
                }
                if (endDateTime.isAfter(now)) {
                    $endTime.addClass('is-invalid');
                    $endTime.next('.invalid-feedback').text('End time cannot be in the future.');
                    $endTime.val('');
                    return false;
                }
            }

            // Check if end time is after start time
            if (endDateTime.isSameOrBefore(startDateTime)) {
                $endTime.addClass('is-invalid');
                $endTime.next('.invalid-feedback').text('End time must be after start time.');
                $endTime.val('');
                return false;
            }

            return true;
        }
        return false;
    }

    $('#addManualTimeForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');

        if (!validateTimeInputs()) {
            return;
        }

        if (!$form[0].checkValidity()) {
            $form.find(':invalid').each(function () {
                const $input = $(this);
                $input.addClass('is-invalid');
                if ($input.attr('name') === 'user_id') {
                    $input.next('.invalid-feedback').text('Please select an employee.');
                } else if ($input.attr('name') === 'reason') {
                    $input.next('.invalid-feedback').text('Please provide a reason.');
                } else {
                    $input.next('.invalid-feedback').text('This field is required.');
                }
            });
            return;
        }

        $.ajax({
            url: manualTimeStore,
            method: 'POST',
            data: $form.serialize(),
            success: function () {
                $('#addManualTimeModal').modal('hide');
                $form[0].reset();
                $('.select2-employee').val(null).trigger('change');
                $('#manualTimeTable').bootstrapTable('refresh');
                toastr.success('Manual time added successfully.');
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors || {};
                Object.entries(errors).forEach(([field, messages]) => {
                    const $input = $form.find(`[name="${field}"]`);
                    $input.addClass('is-invalid');
                    $input.next('.invalid-feedback').text(messages[0]);
                });
                toastr.error('Please correct the errors in the form.');
            }
        });
    });

    $('#approval_status').on('change', function () {
        $('#remarksDiv').toggleClass('d-none', $(this).val() !== 'rejected');
        $('#approvalBtnText').text($(this).val() === 'rejected' ? 'Reject' : 'Approve');
    });

    $('#approveManualTimeForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');

        if (!$form[0].checkValidity()) {
            $form.find(':invalid').each(function () {
                const $input = $(this);
                $input.addClass('is-invalid');
                $input.next('.invalid-feedback').text('Please select an action.');
            });
            return;
        }

        if ($('#approval_status').val() === 'rejected' && !$('[name="remarks"]').val().trim()) {
            $('[name="remarks"]').addClass('is-invalid');
            $('[name="remarks"]').next('.invalid-feedback').text('Remarks are required when rejecting.');
            return;
        }

        $.ajax({
            url: manualTimeApprove,
            method: 'POST',
            data: $form.serialize(),
            success: function (response) {
                $('#approvalModal').modal('hide');
                $('#manualTimeTable').bootstrapTable('refresh');
                toastr.success(response.message || 'Manual time entry processed.');
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors || {};
                Object.entries(errors).forEach(([field, messages]) => {
                    const $input = $form.find(`[name="${field}"]`);
                    $input.addClass('is-invalid');
                    $input.next('.invalid-feedback').text(messages[0]);
                });
                toastr.error('Please correct the errors in the form.');
            }
        });
    });

    $('.select2-employee').select2({
        dropdownParent: $('#addManualTimeModal'),
        width: '100%',
        placeholder: "Select Employee",
        allowClear: true
    });

    $('.select2-employee-filter').select2({
        width: '100%',
        placeholder: "All Employees",
        allowClear: true
    });
});
