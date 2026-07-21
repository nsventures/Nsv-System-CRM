'use strict';
function queryParams(p) {
    return {
        "statuses": $('#user_status_filter').val(),
        "role_ids": $('#user_roles_filter').val(),
        "ev_statuses": $('#user_ev_status_filter').val(),
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
    toggleOff: 'bx-toggle-left',
    toggleOn: 'bx-toggle-right'
}

function loadingTemplate(message) {
    return '<i class="bx bx-loader-alt bx-spin bx-flip-vertical" ></i>'
}

addDebouncedEventListener('#user_status_filter, #user_roles_filter, #user_ev_status_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#user_table').bootstrapTable('refresh');
    }
});

$(document).on('click', '.clear-users-filters', function (e) {
    e.preventDefault();
    $('#user_status_filter').val('').trigger('change', [0]);
    $('#user_roles_filter').val('').trigger('change', [0]);
    $('#user_ev_status_filter').val('').trigger('change', [0]);
    $('#user_table').bootstrapTable('refresh');
})

// Include table-filter-sync.js before this
// Users table filter sync configuration
$(document).ready(function () {
    // Initialize TableFilterSync for users
    new TableFilterSync({
        tableId: 'user_table', // Your users table ID
        dataType: 'users',
        filters: [
            {
                selector: '#user_status_filter',
                type: 'tom-select',
                name: 'status',
                ajaxType: null // No AJAX needed for static options
            },
            {
                selector: '#user_roles_filter',
                type: 'tom-select',
                name: 'roles',
                ajaxType: null // No AJAX needed for static options
            },
            {
                selector: '#user_ev_status_filter',
                type: 'tom-select',
                name: 'ev_status',
                ajaxType: null // No AJAX needed for static options
            }
        ],
        preserveParams: [], // Add any URL params you want to preserve
        debounceMs: 300,
        debug: true // Set to false in production
    });
});
