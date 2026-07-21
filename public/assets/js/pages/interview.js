function queryParams(params) {
    const query = {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: $('#sort').val(),
        order: params.order,
        status: $('#interview_status').val(),
        start_date: $('#interview_date_between_from').val(),
        end_date: $('#interview_date_between_to').val(),
    };
    console.log(query);
    return query;
}
$(document).on('click', '.edit-interview-btn', function () {
    const interview = $(this).data('interview');
    if (!interview || !interview.id) {
        console.error('Invalid interview data:', interview);
        toastr.error(label_something_went_wrong);
        return;
    }
    // Construct the form action URL dynamically
    const actionUrl = `/interviews/update/${interview.id}`;
    $('#editInterviewForm').attr('action', actionUrl);
    if ($('#candidate_id')[0] && $('#candidate_id')[0].tomselect) {
        $('#candidate_id')[0].tomselect.setValue(interview.candidate_id);
    } else {
        $('#candidate_id').val(interview.candidate_id).trigger('change');
    }
    if ($('#interviewer_id')[0] && $('#interviewer_id')[0].tomselect) {
        $('#interviewer_id')[0].tomselect.setValue(interview.interviewer_id);
    } else {
        $('#interviewer_id').val(interview.interviewer_id).trigger('change');
    }
    $('#round').val(interview.round || '');
    $('#scheduled_at').val(interview.scheduled_at || '');
    $('#mode').val(interview.mode || '');
    $('#location').val(interview.location || '');
    $('#status').val(interview.status || '');
    // Open the modal
    $('#editInterviewModal').modal('show');
});
$(document).ready(function () {
    $("#sort").on("change", function () {
        $('#interviews_table').bootstrapTable('refresh');
    });
    $('#interview_status').on('change', function () {
        $('#interviews_table').bootstrapTable('refresh');
    });
    $("#interview_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $('#interview_date_between_to').val(endDate);
            $('#interview_date_between_from').val(startDate);
            console.log('Selected range:', startDate, endDate);
            $("#interviews_table").bootstrapTable('refresh');
        }
    );
    $("#interview_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $('#interview_date_between_to').val('');
            $('#interview_date_between_from').val('');
            $('#interview_date_between').val('');
            picker.setStartDate(moment());
            picker.setEndDate(moment());
            picker.updateElement();
            $("#interviews_table").bootstrapTable('refresh');
        }
    );
});

$(document).ready(function () {
    // Initialize TableFilterSync for interviews
    const interviewFilterSync = new TableFilterSync({
        tableId: 'interviews_table',
        dataType: 'interview',
        filters: [
            {
                selector: '#interview_date_between',
                type: 'daterangepicker',
                name: 'interview_date_between',
                hiddenFrom: '#interview_date_between_from',
                hiddenTo: '#interview_date_between_to'
            },
            {
                selector: '#sort',
                type: 'tom-select',
                name: 'sort',
                ajaxType: null
            },
            {
                selector: '#interview_status',
                type: 'tom-select',
                name: 'status',
                ajaxType: null
            },

        ],
        preserveParams: [''],
        queryParamsFn: queryParams // Reuse existing function
    });
});

$(document).on('click', '.clear-interview-filters', function (e) {
    e.preventDefault();
    const picker = $('#interview_date_between').data('daterangepicker');
    if (picker) {
        picker.setStartDate(moment());
        picker.setEndDate(moment());
        picker.updateElement();
    }
    $('#interview_date_between').val('');
    $('#interview_date_between_from').val('');
    $('#interview_date_between_to').val('');
    
    if ($('#sort')[0] && $('#sort')[0].tomselect) {
        $('#sort')[0].tomselect.setValue('', true);
    }
    if ($('#interview_status')[0] && $('#interview_status')[0].tomselect) {
        // clear multiple selection for tomselect
        $('#interview_status')[0].tomselect.clear(true);
    }
    
    $('#interviews_table').bootstrapTable('refresh');
});
