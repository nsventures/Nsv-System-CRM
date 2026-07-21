
function queryParams(p) {
    return {
        "user_ids": $('#workspace_user_filter').val(),
        "client_ids": $('#workspace_client_filter').val(),
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
addDebouncedEventListener('#workspace_user_filter, #workspace_client_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#table').bootstrapTable('refresh');
    }
});
$(document).on('click', '.clear-workspaces-filters', function (e) {
    e.preventDefault();
    $('#workspace_user_filter').val('').trigger('change', [0]);
    $('#workspace_client_filter').val('').trigger('change', [0]);
    $('#table').bootstrapTable('refresh');
})
// Include table-filter-sync.js before this
$(document).ready(function () {
    const workspaceFilterSync = new TableFilterSync({
        tableId: 'table',
        dataType: 'workspaces',
        filters: [
            {
                selector: '#workspace_user_filter',
                type: 'tom-select',
                name: 'user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#workspace_client_filter',
                type: 'tom-select',
                name: 'client_ids',
                ajaxType: 'clients'
            },
        ],
        preserveParams: [''],
        queryParamsFn: queryParams // Reuse existing function
    });
});
