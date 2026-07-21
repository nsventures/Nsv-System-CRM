'use strict';


$('#leads-kanban-filter').click(function () {
    // Get the selected values from status select and other filters
    var statuses = $('#selected_sources').val(); // Array of selected statuses
    var sort = $('#sort').val();

    // Form the URL with the selected filters
    var url = baseUrl + "/leads/kanban-view";
    var params = [];

    if (statuses && statuses.length > 0) {
        params.push("sources[]=" + statuses.join("&sources[]="));
    }

    if (sort) {
        params.push("sort=" + sort);
    }
    if ($('#lead_kanban_start_date').val() != '') {
        params.push("start_date=" + $('#lead_kanban_start_date').val());
    }
    if ($('#lead_kanban_end_date').val() != '') {
        params.push("end_date=" + $('#lead_kanban_end_date').val());
    }


    if (params.length > 0) {
        url += "?" + params.join("&");
    }

    // Redirect to the URL
    window.location.href = url;
});
$("#lead_kanban_date_range").on(
    "apply.daterangepicker",
    function (ev, picker) {
        var startDate = picker.startDate.format("YYYY-MM-DD");
        var endDate = picker.endDate.format("YYYY-MM-DD");
        $('#lead_kanban_end_date').val(endDate);
        $('#lead_kanban_start_date').val(startDate);

    }
);
$("#lead_kanban_date_range").on(
    "cancel.daterangepicker",
    function (ev, picker) {
        $('#lead_kanban_end_date').val('');
        $('#lead_kanban_start_date').val('');
        $('#lead_kanban_date_range').val('');
    }
);

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

function userFormatter(value, row, index) {
    return '<div class="d-flex">' +
        row.profile +
        '</div>';

}


document.addEventListener('DOMContentLoaded', function () {
    const columns = Array.from(document.querySelectorAll('.kanban-column-body'));
    const drake = dragula(columns, {
        direction: 'vertical',
        moves: function (el, container, handle) {
            return el.classList.contains('kanban-card'); // Only drag actual cards
        },
        accepts: function (el, target) {
            return el.classList.contains('kanban-card'); // Only drop inside columns
        },
        invalid: function (el, handle) {
            // Do not drag if clicking form inputs, buttons, or links
            if (el.tagName === 'A' || el.tagName === 'BUTTON' || el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA' || el.isContentEditable) {
                return true;
            }
            // Do not drag if clicking anything inside the footer or settings dropdown
            if (el.closest('.kanban-footer') || el.closest('.dropdown-menu') || el.classList.contains('create-lead-btn')) {
                return true;
            }
            return false;
        }
    });
    var oldParent;
    // Event when dragging starts
    drake.on('drag', function (el, source) {
        oldParent = source;
        el.classList.add('dragging'); // Add visual style to the dragged element
    });

    // Event when dragging ends
    drake.on('dragend', function (el) {
        el.classList.remove('dragging'); // Remove visual style from the dragged element
        el.classList.add('dropped'); // Add dropped style
        document.querySelectorAll('.drop-target').forEach(target => {
            target.classList.remove('drop-target'); // Remove highlight from all columns
        });
    });

    // Event when dragging over a container
    drake.on('over', function (el, container) {
        container.classList.add('drop-target'); // Add highlight to the container
    });

    // Event when dragging out of a container
    drake.on('out', function (el, container) {
        container.classList.remove('drop-target'); // Remove highlight from the container
    });
    drake.on('drop', function (el, target, source, sibling) {
        // Drop inside the same column is a reorder, bypass AJAX status update
        if (target === source) {
            return;
        }

        // Get the new status based on the target column's data attribute
        const newStage = target.closest('.kanban-column').getAttribute('data-stage-id');

        // Extract card ID from the element
        const cardId = el.getAttribute('data-card-id');

        // Update project status in the backend using jQuery AJAX
        $.ajax({
            url: baseUrl + '/leads/stage-change',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                id: cardId,
                stage_id: newStage
            },
            success: function (response) {
                if (response.error === false) {
                    toastr.success(response.message);

                    // Optionally, update the frontend to reflect any changes
                    // For example, updating the count of items in the column headers
                    updateColumnCounts();
                } else {
                    toastr.error(response.message);
                    drake.cancel(true);
                    if (oldParent) $(oldParent).append(el);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                toastr.error('Failed to update stage.');
                drake.cancel(true);
                if (oldParent) $(oldParent).append(el);
            }
        });
    });

    function updateColumnCounts() {
        // Function to update the counts of items in each column header
        document.querySelectorAll('.kanban-column').forEach(column => {
            const stageId = column.dataset.stageId;
            const count = column.querySelectorAll('.kanban-card').length;
            column.querySelector('.column-count').textContent = `${count}/${totalLeadsCount}`;
        });
    }

    // Optionally, calculate the total number of leads if needed
    const totalLeadsCount = document.querySelectorAll('.kanban-card').length;
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

