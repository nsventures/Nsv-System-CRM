
'use strict';
function queryParamsExpenseTypes(p) {
    return {
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}

function queryParams(p) {
    return {
        "user_ids": $('#user_filter').val(),
        "type_ids": $('#type_filter').val(),
        "date_from": $('#expense_date_between_from').val(),
        "date_to": $('#expense_date_between_to').val(),
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

addDebouncedEventListener('#user_filter, #type_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#table').bootstrapTable('refresh');
    }
});

$(document).on('click', '.clear-expenses-filters', function (e) {
    e.preventDefault();
    $('#expense_date_between').val('');
    $('#expense_date_between_from').val('');
    $('#expense_date_between_to').val('');
    $('#user_filter').val('').trigger('change', [0]);
    $('#type_filter').val('').trigger('change', [0]);
    $('#table').bootstrapTable('refresh');
})

$(document).ready(function () {
    if (typeof initAdvancedDateRangePicker !== 'undefined') {
        initAdvancedDateRangePicker({
            configs: [
                {
                    target: '#expense_date_between',
                    hiddenFrom: '#expense_date_between_from',
                    hiddenTo: '#expense_date_between_to'
                }
            ]
        });
    }

    // Initialize TableFilterSync for users
    const expenseFilterSync = new TableFilterSync({
        tableId: 'table',
        dataType: 'expenses',
        filters: [
            {
                selector: '#expense_date_between',
                type: 'daterangepicker',
                name: 'expense_date_between',
                hiddenFrom: '#expense_date_between_from',
                hiddenTo: '#expense_date_between_to'
            },
            {
                selector: '#user_filter',
                type: 'tomselect',
                name: 'user_ids',
                ajaxType: 'users'
            },

            {
                selector: '#type_filter',
                type: 'tomselect',
                name: 'type_ids',
                ajaxType: 'expense_types'
            }
        ],
        preserveParams: [''],
        queryParamsFn: queryParams // Reuse existing function
    });
});
