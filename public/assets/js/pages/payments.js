
'use strict';

function queryParams(p) {
    return {
        "user_id": $('#user_filter').val(),
        "invoice_id": $('#invoice_filter').val(),
        "pm_id": $('#payment_method_filter').val(),
        "date_from": $('#payment_date_between_from').val(),
        "date_to": $('#payment_date_between_to').val(),
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}

window.icons = {
    refresh: 'bx-refresh',
    toggleOn: 'bx-toggle-right',
    toggleOff: 'bx-toggle-left'
}

function loadingTemplate(message) {
    return '<i class="bx bx-loader-alt bx-spin bx-flip-vertical" ></i>'
}

addDebouncedEventListener('#user_filter, #invoice_filter, #payment_method_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#table').bootstrapTable('refresh');
    }
});

$(document).on('click', '.clear-payments-filters', function (e) {
    e.preventDefault();
    $('#payment_date_between').val('');
    $('#payment_date_between_from').val('');
    $('#payment_date_between_to').val('');
    $('#user_filter').val('').trigger('change', [0]);
    $('#invoice_filter').val('').trigger('change', [0]);
    $('#payment_method_filter').val('').trigger('change', [0]);
    $('#table').bootstrapTable('refresh');
})

$(document).ready(function () {
    if (typeof initAdvancedDateRangePicker !== 'undefined') {
        initAdvancedDateRangePicker({
            configs: [
                {
                    target: '#payment_date_between',
                    hiddenFrom: '#payment_date_between_from',
                    hiddenTo: '#payment_date_between_to'
                }
            ]
        });
    }

    // Initialize TableFilterSync
    const paymentFilterSync = new TableFilterSync({
        tableId: 'table',
        dataType: 'payments',
        filters: [
            {
                selector: '#payment_date_between',
                type: 'daterangepicker',
                name: 'payment_date_between',
                hiddenFrom: '#payment_date_between_from',
                hiddenTo: '#payment_date_between_to'
            },
            {
                selector: '#user_filter',
                type: 'tomselect',
                name: 'user_id',
                ajaxType: 'users'
            },
            {
                selector: '#invoice_filter',
                type: 'tomselect',
                name: 'invoice_id',
                ajaxType: 'invoices'
            },
            {
                selector: '#payment_method_filter',
                type: 'tomselect',
                name: 'pm_id',
                ajaxType: null
            },
        ],
        preserveParams: [''],
        queryParamsFn: queryParams // Reuse existing function
    });
});
