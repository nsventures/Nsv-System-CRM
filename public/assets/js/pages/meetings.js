
'use strict';
function queryParams(p) {
    return {
        "statuses": $('#status_filter').val(),
        "user_ids": $('#meeting_user_filter').val(),
        "client_ids": $('#meeting_client_filter').val(),
        "date_between_from": $('#meeting_date_between_from').val(),
        "date_between_to": $('#meeting_date_between_to').val(),
        "start_date_from": $('#meeting_start_date_from').val(),
        "start_date_to": $('#meeting_start_date_to').val(),
        "end_date_from": $('#meeting_end_date_from').val(),
        "end_date_to": $('#meeting_end_date_to').val(),
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

addDebouncedEventListener('#status_filter, #meeting_user_filter, #meeting_client_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#meetings_table').bootstrapTable('refresh');
    }
});

// Initialize advanced date range filters with presets
$(document).ready(function () {
    // Initialize date range filters with preset ranges
    initAdvancedDateRangePicker({
        selector: '#meeting_date_between',
        hiddenFrom: '#meeting_date_between_from',
        hiddenTo: '#meeting_date_between_to',
        tableId: 'meetings_table'
    });

    initAdvancedDateRangePicker({
        selector: '#meeting_start_date_between',
        hiddenFrom: '#meeting_start_date_from',
        hiddenTo: '#meeting_start_date_to',
        tableId: 'meetings_table'
    });

    initAdvancedDateRangePicker({
        selector: '#meeting_end_date_between',
        hiddenFrom: '#meeting_end_date_from',
        hiddenTo: '#meeting_end_date_to',
        tableId: 'meetings_table'
    });
});

$(document).on('click', '.clear-meetings-filters', function (e) {
    e.preventDefault();
    $('#meeting_date_between').val('');
    $('#meeting_date_between_from').val('');
    $('#meeting_date_between_to').val('');
    $('#meeting_start_date_between').val('');
    $('#meeting_end_date_between').val('');
    $('#meeting_start_date_from').val('');
    $('#meeting_start_date_to').val('');
    $('#meeting_end_date_from').val('');
    $('#meeting_end_date_to').val('');
    $('#status_filter').val('').trigger('change', [0]);
    $('#meeting_user_filter').val('').trigger('change', [0]);
    $('#meeting_client_filter').val('').trigger('change', [0]);
    $('#meetings_table').bootstrapTable('refresh');
})

$(document).ready(function () {
    // Initialize TableFilterSync for users
    const leadFilterSync = new TableFilterSync({
        tableId: 'meetings_table',
        dataType: 'meetings',
        filters: [
            {
                selector: '#meeting_date_between',
                type: 'daterangepicker',
                name: 'meeting_date_between',
                hiddenFrom: '#meeting_date_between_from',
                hiddenTo: '#meeting_date_between_to'
            },
            {
                selector: '#meeting_start_date_between',
                type: 'daterangepicker',
                name: 'meeting_start_date_between',
                hiddenFrom: '#meeting_start_date_from',
                hiddenTo: '#meeting_start_date_to'
            },
            {
                selector: '#meeting_end_date_between',
                type: 'daterangepicker',
                name: 'meeting_end_date_between',
                hiddenFrom: '#meeting_end_date_from',
                hiddenTo: '#meeting_end_date_to'
            },
            {
                selector: '#meeting_user_filter',
                type: 'tom-select',
                name: 'user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#meeting_client_filter',
                type: 'tom-select',
                name: 'client_ids',
                ajaxType: 'clients'
            },
            {
                selector: '#status_filter',
                type: 'tom-select',
                name: 'statuses',
                ajaxType: null
            }

        ],
        preserveParams: [''],
        queryParamsFn: queryParams // Reuse existing function
    });
});
