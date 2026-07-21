$(function () {

    $('#invoices_report_table').on('load-success.bs.table', function (e, data) {
        $('#average-invoice-value').text(data.summary.average_invoice_value);
        $('#total-final').text(data.summary.total_final);
        $('#total-tax').text(data.summary.total_tax);
        $('#total-amount').text((data.summary.total_amount));
        $('#total-invoices').text(data.summary.total_invoices);
    });
});
$(document).ready(function () {
    $('#export_button').click(function () {
        var $exportButton = $(this);
        $exportButton.attr('disabled', true);
        // Prepare query parameters
        const queryParams = invoices_report_query_params({ offset: 0, limit: 1000, sort: 'id', order: 'desc', search: '' });
        // Construct the export URL
        const exportUrl = invoices_report_export_url + '?' + $.param(queryParams);
        // Open the export URL in a new tab or window
        $exportButton.attr('disabled', false);
        window.open(exportUrl, '_blank');
    });
});
function invoices_report_query_params(p) {
    return {
        "types": $('#type_filter').val(),
        "statuses": $('#status_filter').val(),
        "client_ids": $('#client_filter').val(),
        "created_by_user_ids": $('#user_creators_filter').val(),
        "created_by_client_ids": $('#client_creators_filter').val(),
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

addDebouncedEventListener('#type_filter, #client_filter, #status_filter, #user_creators_filter, #client_creators_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#invoices_report_table').bootstrapTable('refresh');
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
    $('#type_filter').val('').trigger('change', [0]);
    $('#client_filter').val('').trigger('change', [0]);
    $('#status_filter').val('').trigger('change', [0]);
    $('#user_creators_filter').val('').trigger('change', [0]);
    $('#client_creators_filter').val('').trigger('change', [0]);
    $('#invoices_report_table').bootstrapTable('refresh');
})


$(document).ready(function () {
    // Initialize TableFilterSync for users
    const invoiceReportFilterSync = new TableFilterSync({
        tableId: 'invoices_report_table',
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
                selector: '#type_filter',
                type: 'tom-select',
                name: 'type_filter',
                ajaxType: null,
            },
            {
                selector: '#user_creators_filter',
                type: 'tom-select',
                name: 'created_by_user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#client_filter',
                type: 'tom-select',
                name: 'client_ids',
                ajaxType: 'clients'
            },
            {
                selector: '#client_creators_filter',
                type: 'tom-select',
                name: 'created_by_client_ids',
                ajaxType: 'clients'
            },
        ],
        preserveParams: [''],
        queryParamsFn: invoices_report_query_params // Reuse existing function
    });
});
