function queryParamsEmailHistory(params) {
    console.log('Query Params:', params);
    return {
        search: params.search,
        limit: params.limit,
        offset: params.offset,
        sort: params.sort,
        order: params.order
    };
}



function emailHistoryActionsFormatter(value, row) {
    return `
    <div class="d-flex justify-content-center">
        <button class="btn btn-sm btn-outline-secondary preview-history-btn"
            data-body="${encodeURIComponent(row.body)}"
            data-bs-toggle="tooltip" data-bs-placement="top" title="Preview">
            <i class="bx bx-envelope-open mx-1"></i>
        </button>
    </div>`;
}

// CSRF setup if not already done
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
});

// Email History Preview Button click
$(document).on('click', '.preview-history-btn', function () {
    let body = decodeURIComponent($(this).data('body'));

    // Optional cleanup (remove background-color inline styles)
    body = body.replace(/background-color:\s*[^;]+;?/gi, '');

    $('#previewContent').html(`<div>${body}</div>`);
    $('#previewModal').modal('show');
});



