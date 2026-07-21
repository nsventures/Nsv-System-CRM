if ($('.tom-select-sort').length > 0) {
    var placeholderText = $('.tom-select-sort').data('placeholder') || 'Sort By';
    new TomSelect('.tom-select-sort', {
        placeholder: placeholderText
    });
}

$('#sort').on('change', function (e) {
    var sort = $(this).val();
    location.href = setUrlParameter(location.href, 'sort', sort);
});


function setUrlParameter(url, paramName, paramValue) {
    paramName = paramName.replace(/\s+/g, '-');
    if (paramValue == null || paramValue == '') {
        return url.replace(new RegExp('[?&]' + paramName + '=[^&#]*(#.*)?$'), '$1')
            .replace(new RegExp('([?&])' + paramName + '=[^&]*&'), '$1');
    }
    var pattern = new RegExp('\\b(' + paramName + '=).*?(&|#|$)');
    if (url.search(pattern) >= 0) {
        return url.replace(pattern, '$1' + paramValue + '$2');
    }
    url = url.replace(/[?#]$/, '');
    return url + (url.indexOf('?') > 0 ? '&' : '?') + paramName + '=' + paramValue;
}

$('#selected_statuses, #selected_tags').on('change', function () {
    // Get the selected values from status select and other filters
    var statuses = $('#selected_statuses').val(); // Array of selected statuses
    var sort = $('#sort').val();
    // Get selected tags using Select2 / TomSelect
    var selectedTags = $('#selected_tags').val(); // Array of selected tags

    // Form the URL with the selected filters
    var url = baseUrl + "/projects";
    var params = [];

    if (statuses && statuses.length > 0) {
        params.push("statuses[]=" + statuses.join("&statuses[]="));
    }

    if (sort) {
        params.push("sort=" + sort);
    }

    if (selectedTags && selectedTags.length > 0) {
        params.push("tags[]=" + selectedTags.join("&tags[]="));
    }

    if (params.length > 0) {
        url += "?" + params.join("&");
    }

    // Redirect to the URL
    window.location.href = url;
});


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
