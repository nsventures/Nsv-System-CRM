window.icons = {
    refresh: "bx-refresh",
};

function loadingTemplate(message) {
    return '<i class="bx bx-loader-alt bx-spin bx-flip-vertical" ></i>';
}

function queryParamsTasks(p) {
    let task_parent_id2 =
        typeof task_parent_id !== "undefined" && task_parent_id !== null
            ? task_parent_id
            : null;
    return {
        status_ids: $("#task_status_filter").val(),
        priority_ids: $("#task_priority_filter").val(),
        user_ids: $("#task_user_filter").val(),
        client_ids: $("#task_client_filter").val(),
        project_ids: $("#task_project_filter").val(),
        task_date_between_from: $("#task_date_between_from").val(),
        task_date_between_to: $("#task_date_between_to").val(),
        task_start_date_from: $("#task_start_date_from").val(),
        task_start_date_to: $("#task_start_date_to").val(),
        task_end_date_from: $("#task_end_date_from").val(),
        task_end_date_to: $("#task_end_date_to").val(),
        is_favorites: $("#is_favorites").val(),
        task_parent_id: task_parent_id2,
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
    };
}
addDebouncedEventListener(
    "#task_status_filter, #task_priority_filter, #task_user_filter, #task_client_filter, #task_project_filter",
    "change",
    function (e, refreshTable) {
        e.preventDefault();
        if (typeof refreshTable === "undefined" || refreshTable) {
            $("#task_table").bootstrapTable("refresh");
        }
    }
);

function assignedFormatter(value, row, index) {
    return (
        '<div class="d-flex justify-content-start align-items-center"><div class="text-center mx-4"><span class="badge rounded-pill bg-primary" >' +
        row.projects +
        "</span><div>" +
        label_projects +
        "</div></div>" +
        '<div class="text-center"><span class="badge rounded-pill bg-primary" >' +
        row.tasks +
        "</span><div>" +
        label_tasks +
        "</div></div></div>"
    );
}

function queryParamsUsersClients(p) {
    return {
        type: $("#type").val(),
        typeId: $("#typeId").val(),
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
    };
}

$(document).on("click", ".clear-tasks-filters", function (e) {
    e.preventDefault();
    $("#task_date_between").val("");
    $("#task_date_between_from").val("");
    $("#task_date_between_to").val("");
    $("#task_start_date_between").val("");
    $("#task_end_date_between").val("");
    $("#task_start_date_from").val("");
    $("#task_start_date_to").val("");
    $("#task_end_date_from").val("");
    $("#task_end_date_to").val("");
    $("#task_project_filter").val("").trigger("change", [0]);
    $("#task_user_filter").val("").trigger("change", [0]);
    $("#task_client_filter").val("").trigger("change", [0]);
    $("#task_status_filter").val("").trigger("change", [0]);
    $("#task_priority_filter").val("").trigger("change", [0]);
    $("#task_table").bootstrapTable("refresh");
});

$("#viewAssignedModal").on("hidden.bs.modal", function (e) {
    e.preventDefault();
    $(".clear-tasks-filters").trigger("click");
});

// Initialize advanced date range filters with presets
$(document).ready(function () {
    // Initialized via custom.js standardized loop for standard filter IDs
});

// Include table-filter-sync.js before this
$(document).ready(function () {
    const FilterSync = new TableFilterSync({
        tableId: 'task_table',
        dataType: 'tasks',
        filters: [
            {
                selector: '#task_status_filter',
                type: 'select2',
                name: 'status_ids',
                ajaxType: 'statuses'
            },
            {
                selector: '#task_project_filter',
                type: 'select2',
                name: 'project_ids',
                ajaxType: 'projects'
            },
            {
                selector: '#task_priority_filter',
                type: 'select2',
                name: 'priority_ids',
                ajaxType: 'priorities'
            },
            {
                selector: '#task_user_filter',
                type: 'select2',
                name: 'user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#task_client_filter',
                type: 'select2',
                name: 'client_ids',
                ajaxType: 'clients'
            },
            {
                selector: '#task_date_between',
                type: 'daterangepicker',
                name: 'task_date_between',
                hiddenFrom: '#task_date_between_from',
                hiddenTo: '#task_date_between_to'
            },
            {
                selector: '#task_start_date_between',
                type: 'daterangepicker',
                name: 'task_start_date_between',
                hiddenFrom: '#task_start_date_from',
                hiddenTo: '#task_start_date_to'
            },
            {
                selector: '#task_end_date_between',
                type: 'daterangepicker',
                name: 'task_end_date_between',
                hiddenFrom: '#task_end_date_from',
                hiddenTo: '#task_end_date_to'
            },
        ],
        preserveParams: ['from_home'],
        queryParamsFn: queryParamsTasks // Reuse existing function
    });
});
