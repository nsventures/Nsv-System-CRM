$(document).ready(function () {
    $("#sort").on("change", function () {
        $('#leads_table').bootstrapTable('refresh');
    });
    $("#selected_sources").on("change", function () {
        $('#leads_table').bootstrapTable('refresh');
    });
    $("#selected_stages").on("change", function () {
        $('#leads_table').bootstrapTable('refresh');
    });

    $(document).on('click', '.clear-leads-filters', function (e) {
        e.preventDefault();
        $('#sort').val('').trigger('change', [0]);
        $('#selected_sources').val('').trigger('change', [0]);
        $('#selected_stages').val('').trigger('change', [0]);
        $('#lead_date_range').val('');
        $('#lead_date_range_from').val('');
        $('#lead_date_range_to').val('');
        $('#leads_table').bootstrapTable('refresh');
    })

});
function queryParamsLead(p) {
    return {
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        sort: $('#sort').val(),
        source_ids: $('#selected_sources').val(),
        start_date: $('#lead_date_range_from').val(),
        end_date: $('#lead_date_range_to').val(),
        stage_ids: $('#selected_stages').val(),
    };
}

$(document).ready(function () {
    // Initialize TableFilterSync for users
    const leadFilterSync = new TableFilterSync({
        tableId: 'leads_table',
        dataType: 'leads',
        filters: [
            {
                selector: '#sort',
                type: 'tom-select',
                name: 'sort',
                ajaxType: null
            },
            {
                selector: '#selected_sources',
                type: 'tom-select',
                name: 'source_ids',
                ajaxType: 'lead_sources'
            },
            {
                selector: '#selected_stages',
                type: 'tom-select',
                name: 'stage_ids',
                ajaxType: 'lead_stages'
            },
            {
                selector: '#lead_date_range',
                type: 'daterangepicker',
                name: 'lead_date_range',
                hiddenFrom: '#lead_date_range_from',
                hiddenTo: '#lead_date_range_to'
            }
        ],
        preserveParams: [''],
        queryParamsFn: queryParamsLead // Reuse existing function
    });
});


