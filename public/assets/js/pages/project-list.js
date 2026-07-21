'use strict';
function queryParamsProjects(p) {
    return {
        "status_ids": $('#project_status_filter').val(),
        "priority_ids": $('#project_priority_filter').val(),
        "user_ids": $('#project_user_filter').val(),
        "client_ids": $('#project_client_filter').val(),
        "tag_ids": $('#project_tag_filter').val(),
        "project_date_between_from": $('#project_date_between_from').val(),
        "project_date_between_to": $('#project_date_between_to').val(),
        "project_start_date_from": $('#project_start_date_from').val(),
        "project_start_date_to": $('#project_start_date_to').val(),
        "project_end_date_from": $('#project_end_date_from').val(),
        "project_end_date_to": $('#project_end_date_to').val(),
        "is_favorites": $('#is_favorites').val() || 0,
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
function assignedFormatter(value, row, index) {
    return '<div class="d-flex justify-content-start align-items-center"><div class="text-center mx-4"><span class="badge rounded-pill bg-primary" >' + row.projects + '</span><div>' + label_projects + '</div></div>' +
        '<div class="text-center"><span class="badge rounded-pill bg-primary" >' + row.tasks + '</span><div>' + label_tasks + '</div></div></div>'
}
function queryParamsUsersClients(p) {
    return {
        type: $('#type').val(),
        typeId: $('#typeId').val(),
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}
// New custom field formatter for checkbox values
function customFieldFormatter(value, row) {
    if (value && typeof value === 'string' && value.startsWith('[') && value.endsWith(']')) {
        try {
            // Parse JSON string to array
            const options = JSON.parse(value);
            // Join array elements with comma and space
            return options.join(', ');
        } catch (e) {
            // Return original value if parsing fails
            return value;
        }
    }
    return value;
}
addDebouncedEventListener('#project_status_filter, #project_priority_filter, #project_user_filter, #project_client_filter, #project_tag_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#projects_table').bootstrapTable('refresh');
    }
});
$(document).on('click', '.clear-projects-filters', function (e) {
    e.preventDefault();
    window.clearDateRangeFilters('project');
    $('#project_user_filter').val('').trigger('change', [0]);
    $('#project_client_filter').val('').trigger('change', [0]);
    $('#project_status_filter').val('').trigger('change', [0]);
    $('#project_priority_filter').val('').trigger('change', [0]);
    $('#project_tag_filter').val('').trigger('change', [0]);
    $('#projects_table').bootstrapTable('refresh');
    // Clear request parameters in the URL
    const urlWithoutFilters = window.location.protocol + "//" + window.location.host + window.location.pathname; // Get the base URL
    window.history.pushState({}, document.title, urlWithoutFilters); // Update the URL without reloading the page
})
$('#viewAssignedModal').on('hidden.bs.modal', function (e) {
    e.preventDefault();
    $('.clear-projects-filters').trigger('click');
})
// Initialize advanced date range filters with presets
$(document).ready(function () {
    // Initialized via custom.js standardized loop for standard filter IDs
});
// Include table-filter-sync.js before this
$(document).ready(function () {
    const projectFilterSync = new TableFilterSync({
        tableId: 'projects_table',
        dataType: 'projects',
        filters: [
            {
                selector: '#project_status_filter',
                type: 'tom-select',
                name: 'status_ids',
                ajaxType: 'statuses'
            },
            {
                selector: '#project_priority_filter',
                type: 'tom-select',
                name: 'priority_ids',
                ajaxType: 'priorities'
            },
            {
                selector: '#project_user_filter',
                type: 'tom-select',
                name: 'user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#project_client_filter',
                type: 'tom-select',
                name: 'client_ids',
                ajaxType: 'clients'
            },
            {
                selector: '#project_tag_filter',
                type: 'tom-select',
                name: 'tag_ids',
                ajaxType: 'tags'
            },
            {
                selector: '#project_date_between',
                type: 'daterangepicker',
                name: 'project_date_between',
                hiddenFrom: '#project_date_between_from',
                hiddenTo: '#project_date_between_to'
            },
            {
                selector: '#project_start_date_between',
                type: 'daterangepicker',
                name: 'project_start_date_between',
                hiddenFrom: '#project_start_date_from',
                hiddenTo: '#project_start_date_to'
            },
            {
                selector: '#project_end_date_between',
                type: 'daterangepicker',
                name: 'project_end_date_between',
                hiddenFrom: '#project_end_date_from',
                hiddenTo: '#project_end_date_to'
            },
            {
                selector: '#is_favorites',
                type: 'hidden',
                name: 'is_favorites'
            }
        ],
        preserveParams: ['from_home', 'is_favorites'],
        queryParamsFn: queryParamsProjects  // Use your custom function

    });
});
