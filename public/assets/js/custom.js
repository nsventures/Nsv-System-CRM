"use strict";

if ($.fn.bootstrapTable) {
    $.extend($.fn.bootstrapTable.defaults, {
        formatNoMatches: function () {
            // Using the global label if available, otherwise fallback
            var msg =
                typeof label_data_does_not_exists !== "undefined"
                    ? label_data_does_not_exists
                    : "No matching records found";
            var title =
                typeof label_not_found !== "undefined"
                    ? label_not_found
                    : "Nothing found";
            return `
            <div class="empty">
                <div class="empty-icon">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h4l2 3h6l2-3h4M3 13l3-7h12l3 7M3 13v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6"/></svg>
                </div>
                <div class="empty-title">${title}</div>
                <div class="empty-sub">${msg}</div>
            </div>`;
        },
    });
}

toastr.options = {
    positionClass: toastPosition,
    timeOut: parseFloat(toastTimeOut) * 1000,
    showDuration: "300",
    hideDuration: "1000",
    extendedTimeOut: "1000",
    progressBar: true,
    closeButton: true,
};

function renderRemainingLeavesSummary(balance, options) {
    if (!balance) {
        return "";
    }

    var settings = $.extend(
        {
            heading: label_remaining_leaves,
            lowThreshold: 3,
            includeAccrualMeta: true,
            includeWarnings: true,
        },
        options || {},
    );

    var remaining = parseFloat(balance.remaining_paid_leaves || 0);
    var effectiveTotal =
        balance.accrued_leaves && balance.accrued_leaves > 0
            ? parseFloat(balance.accrued_leaves)
            : parseFloat(balance.total_annual_leaves || 0);

    function formatNumber(value) {
        return value % 1 === 0
            ? value.toString()
            : value.toFixed(2).replace(/0+$/, "").replace(/\.$/, "");
    }

    var status = "healthy";
    if (remaining <= 0) {
        status = "exhausted";
    } else if (remaining < settings.lowThreshold) {
        status = "low";
    }

    var badgeClasses = {
        healthy: "bg-label-success text-success",
        low: "bg-label-warning text-warning",
        exhausted: "bg-label-danger text-danger",
    };

    var iconClasses = {
        healthy: "bx bx-check-circle",
        low: "bx bx-bell",
        exhausted: "bx bx-error-circle",
    };

    var hintTexts = {
        healthy: label_remaining_leaves_good_hint,
        low: label_remaining_leaves_low_hint,
        exhausted: label_remaining_leaves_exhausted_hint,
    };

    var html = '<div class="d-flex flex-column gap-1 leave-remaining-pill">';

    if (settings.heading) {
        html += '<small class="text-muted">' + settings.heading + "</small>";
    }

    html +=
        '<span class="badge d-inline-flex align-items-center gap-2 ' +
        badgeClasses[status] +
        '">';
    html += '<i class="' + iconClasses[status] + '"></i>';
    html += '<span class="text-body">' + label_remaining_leaves + ":</span>";
    html += "<strong>" + formatNumber(remaining) + "</strong>";
    html += "</span>";

    html +=
        '<small class="text-muted">' +
        label_of_text +
        " " +
        formatNumber(effectiveTotal) +
        " " +
        label_days +
        "</small>";

    if (settings.includeWarnings) {
        var hintClass =
            status === "healthy"
                ? "text-muted"
                : status === "low"
                  ? "text-warning"
                  : "text-danger";
        html +=
            '<small class="' +
            hintClass +
            '">' +
            hintTexts[status] +
            "</small>";
    }

    if (
        settings.includeAccrualMeta &&
        balance.accrued_leaves &&
        balance.monthly_accrual_rate
    ) {
        html +=
            '<small class="text-muted">' +
            label_earning +
            " " +
            balance.monthly_accrual_rate +
            " " +
            label_days_per_month +
            " · " +
            balance.months_worked +
            " " +
            label_months_worked +
            " · " +
            label_annual +
            ": " +
            balance.total_annual_leaves +
            "</small>";
    }

    if (status === "exhausted") {
        html +=
            '<small class="text-danger">⚠️ ' +
            label_marked_unpaid_if_approved +
            "</small>";
    } else if (status === "low") {
        html +=
            '<small class="text-warning">⚠️ ' +
            label_marked_unpaid +
            "</small>";
    }

    html += "</div>";

    return html;
}

window.renderRemainingLeavesSummary = renderRemainingLeavesSummary;
$(document).on("click", ".delete", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var type = $(this).data("type");
    var reload = $(this).data("reload"); // Get the value of data-reload attribute
    if (!id || !type) {
        toastr.error(label_something_went_wrong);
        return;
    }
    if (typeof reload !== "undefined" && reload === true) {
        reload = true;
    } else {
        reload = false;
    }
    var tableID = $(this).data("table") || "table";
    var destroy =
        type == "users"
            ? "delete_user"
            : type == "contract-type"
              ? "delete-contract-type"
              : type == "project-media" || type == "task-media"
                ? "delete-media"
                : type == "expense-type"
                  ? "delete-expense-type"
                  : type == "milestone"
                    ? "delete-milestone"
                    : "destroy";
    type =
        type == "contract-type"
            ? "contracts"
            : type == "project-media"
              ? "projects"
              : type == "task-media"
                ? "tasks"
                : type == "expense-type"
                  ? "expenses"
                  : type == "milestone"
                    ? "projects"
                    : type;
    $("#deleteModal").modal("show"); // show the confirmation modal
    $("#deleteModal").off("click", "#confirmDelete");
    $("#deleteModal").on("click", "#confirmDelete", function (e) {
        $("#confirmDelete").html(label_please_wait).attr("disabled", true);
        $.ajax({
            url: baseUrl + "/" + type + "/" + destroy + "/" + id,
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            success: function (response) {
                $("#confirmDelete").html(label_yes).attr("disabled", false);
                $("#deleteModal").modal("hide");
                if (response.error == false) {
                    if (reload) {
                        location.reload();
                    } else {
                        toastr.success(response.message);
                        if (tableID) {
                            $("#" + tableID).bootstrapTable("refresh");
                        }
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (data) {
                $("#confirmDelete").html(label_yes).attr("disabled", false);
                $("#deleteModal").modal("hide");
                toastr.error(label_something_went_wrong);
            },
        });
    });
});
$(document).on("click", ".delete-selected", function (e) {
    e.preventDefault();
    var table = $(this).data("table");
    var type = $(this).data("type");
    var reload = $(this).data("reload");
    var destroy =
        type == "users"
            ? "delete_multiple_user"
            : type == "contract-types"
              ? "delete-multiple-contract-type"
              : type == "project-media" || type == "task-media"
                ? "delete-multiple-media"
                : type == "expense-types"
                  ? "delete-multiple-expense-type"
                  : type == "milestones"
                    ? "delete-multiple-milestone"
                    : "destroy_multiple";
    type =
        type == "contract-types"
            ? "contracts"
            : type == "project-media"
              ? "projects"
              : type == "task-media"
                ? "tasks"
                : type == "expense-types"
                  ? "expenses"
                  : type == "milestones"
                    ? "projects"
                    : type;
    var selections = $("#" + table).bootstrapTable("getSelections");
    var selectedIds = selections.map(function (row) {
        return row.id; // Replace 'id' with the field containing the unique ID
    });
    if (selectedIds.length > 0) {
        $("#confirmDeleteSelectedModal").modal("show"); // show the confirmation modal
        $("#confirmDeleteSelectedModal").off(
            "click",
            "#confirmDeleteSelections",
        );
        $("#confirmDeleteSelectedModal").on(
            "click",
            "#confirmDeleteSelections",
            function (e) {
                $("#confirmDeleteSelections")
                    .html(label_please_wait)
                    .attr("disabled", true);
                $.ajax({
                    url: baseUrl + "/" + type + "/" + destroy,
                    data: {
                        ids: selectedIds,
                    },
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
                    },
                    success: function (response) {
                        $("#confirmDeleteSelections")
                            .html(label_yes)
                            .attr("disabled", false);
                        $("#confirmDeleteSelectedModal").modal("hide");
                        $("#" + table).bootstrapTable("refresh");
                        if (type == "settings/languages") {
                            location.reload();
                        } else {
                            if (reload) {
                                if (response.hasOwnProperty("message")) {
                                    if (response.error == false) {
                                        toastr.success(response["message"]);
                                        setTimeout(
                                            function () {
                                                location.reload();
                                            },
                                            parseFloat(toastTimeOut) * 1000,
                                        );
                                    } else {
                                        toastr.error(response["message"]);
                                    }
                                } else {
                                    location.reload();
                                }
                            } else {
                                if (response.error == false) {
                                    toastr.success(response.message);
                                } else {
                                    toastr.error(response.message);
                                }
                            }
                        }
                    },
                    error: function (data) {
                        $("#confirmDeleteSelections")
                            .html(label_yes)
                            .attr("disabled", false);
                        $("#confirmDeleteSelectedModal").modal("hide");
                        toastr.error(label_something_went_wrong);
                    },
                });
            },
        );
    } else {
        toastr.error(label_please_select_records_to_delete);
    }
});
$(document).ready(function () {
    // Handle delete selected notes or todos
    $("#delete-selected").on("click", function () {
        const itemType = $(this).data("type");
        const selectedIds = $(".selected-items:checked")
            .map(function () {
                return $(this).val();
            })
            .get();
        if (selectedIds.length > 0) {
            $("#confirmDeleteSelectedModal").modal("show"); // show the confirmation modal
            $("#confirmDeleteSelectedModal").off(
                "click",
                "#confirmDeleteSelections",
            );
            $("#confirmDeleteSelectedModal").on(
                "click",
                "#confirmDeleteSelections",
                function (e) {
                    $("#confirmDeleteSelections")
                        .html(label_please_wait)
                        .attr("disabled", true);
                    $.ajax({
                        url: baseUrl + "/" + itemType + "/destroy_multiple", // Adjust URL based on item type
                        data: {
                            ids: selectedIds,
                        },
                        type: "POST",
                        headers: {
                            "X-CSRF-TOKEN": $('input[name="_token"]').attr(
                                "value",
                            ),
                        },
                        success: function (response) {
                            $("#confirmDeleteSelections")
                                .html(label_yes)
                                .attr("disabled", false);
                            $("#confirmDeleteSelectedModal").modal("hide");
                            location.reload();
                        },
                        error: function (data) {
                            $("#confirmDeleteSelections")
                                .html(label_yes)
                                .attr("disabled", false);
                            $("#confirmDeleteSelectedModal").modal("hide");
                            toastr.error(label_something_went_wrong);
                        },
                    });
                },
            );
        } else {
            toastr.error(label_please_select_records_to_delete);
        }
    });
});

/* ===== Modal / Offcanvas backdrop stacking fixes =====
   Ensure proper Bootstrap class usage and avoid stray backdrop overlay.
*/
// When a modal is about to be shown, compute z-index based on any visible offcanvas
$(document).on("show.bs.modal", ".modal", function () {
    try {
        var $modal = $(this);
        // Find highest z-index among visible offcanvas elements
        var highestOffcanvasZ = 0;
        $(".offcanvas.show").each(function () {
            var z = parseInt($(this).css("z-index")) || 0;
            if (z > highestOffcanvasZ) highestOffcanvasZ = z;
        });
        // Base modal/backdrop z-index values
        var modalZ = highestOffcanvasZ ? highestOffcanvasZ + 20 : 1060;
        var backdropZ = modalZ - 10;
        $modal.data("stacking-modal-z", modalZ);
        $modal.data("stacking-backdrop-z", backdropZ);
        // Ensure modal is a direct child of <body> to avoid ancestor stacking contexts
        try {
            if (!$modal.parent().is("body")) $modal.appendTo(document.body);
        } catch (e) {}
        // Apply inline z-index/position to the modal itself immediately so any appended backdrop sits behind
        $modal.css({ "z-index": modalZ, position: "fixed" });
    } catch (e) {
        // ignore
    }
});

// After modal is shown, adjust the newly added backdrop z-index
$(document).on("shown.bs.modal", ".modal", function () {
    try {
        var $modal = $(this);
        // Compute the highest z-index among visible overlays (offcanvas, backdrops, other modals)
        var highest = 0;
        $(".offcanvas.show, .modal-backdrop, .modal.show").each(function () {
            // skip the current modal when checking .modal.show
            if ($(this).is($modal)) return;
            var z = parseInt($(this).css("z-index")) || 0;
            if (z > highest) highest = z;
        });
        var backdropZ =
            highest && highest >= 0
                ? highest + 10
                : $modal.data("stacking-backdrop-z") || 1050;
        var modalZ = backdropZ
            ? backdropZ + 10
            : $modal.data("stacking-modal-z") || 1060;
        // The backdrop inserted by Bootstrap is usually the last .modal-backdrop element
        var $backdrops = $(".modal-backdrop");
        if ($backdrops.length) {
            // Mark all backdrops as stacked so CSS can enforce proper z-index
            $backdrops.addClass("stacked");
        }
        // Add a strong class to modal so CSS !important rules ensure it stays on top
        $modal.addClass("stacked-active");
        // Ensure backdrop inline z-index is set after insertion (small delay to account for timing)
        setTimeout(function () {
            try {
                var modalZ_inline =
                    parseInt($modal.css("z-index")) ||
                    $modal.data("stacking-modal-z") ||
                    2000;
                var backdropZ_inline =
                    modalZ_inline - 10 ||
                    $modal.data("stacking-backdrop-z") ||
                    1990;
                var $lastBackdrop = $(".modal-backdrop").last();
                if ($lastBackdrop.length) {
                    $lastBackdrop.css("z-index", backdropZ_inline);
                    $lastBackdrop.addClass("stacked");
                }
                // Reinforce modal inline z-index and position
                $modal.css({ "z-index": modalZ_inline, position: "fixed" });
            } catch (e) {}
        }, 10);
        // Ensure body has modal-open
        if (!$("body").hasClass("modal-open")) $("body").addClass("modal-open");
    } catch (e) {
        // ignore
    }
});

// When modal hidden, remove its backdrop only if there are no other visible modals
$(document).on("hidden.bs.modal", ".modal", function () {
    try {
        // Allow Bootstrap to remove backdrop; if stray backdrops remain, clean them safely
        setTimeout(function () {
            // Remove backdrop elements that are not associated with any visible modal
            if ($(".modal.show").length === 0) {
                // No modals open — remove any leftover backdrops and modal-open body class
                $(".modal-backdrop").remove();
                $("body").removeClass("modal-open");
            } else {
                // There are other modals open — ensure only one backdrop exists and body class stays
                $(".modal-backdrop").not(".stacked").remove();
                if (!$("body").hasClass("modal-open"))
                    $("body").addClass("modal-open");
            }
            // cleanup stacking classes on the modal that was hidden
            $(this).removeClass("stacked-active");
            // if no modals remain, remove stacked class from backdrops too (they were removed above)
            if ($(".modal.show").length === 0)
                $(".modal-backdrop").removeClass("stacked");
        }, 200);
    } catch (e) {
        // ignore
    }
});

// When an offcanvas is shown, ensure its backdrop z-index is under modals
$(document).on("show.bs.offcanvas", ".offcanvas", function () {
    try {
        var $off = $(this);
        // default offcanvas z-index
        var offZ = parseInt($off.css("z-index")) || 1045;
        // if any modal is visible, push offcanvas under modal
        if ($(".modal.show").length) {
            var topModalZ =
                parseInt($(".modal.show").last().css("z-index")) || 1060;
            if (topModalZ && offZ >= topModalZ) {
                $off.css("z-index", topModalZ - 20);
            }
        }
    } catch (e) {}
});

$("#select-all").on("click", function () {
    $(".selected-items").prop("checked", this.checked);
});
$(document).on("click", "#deleteAccount", function (e) {
    e.preventDefault();
    $("#deleteAccountModal").modal("show"); // show the confirmation modal
    $("#deleteAccountModal").off("click", "#confirmDeleteAccount");
    $("#deleteAccountModal").on("click", "#confirmDeleteAccount", function (e) {
        $("#confirmDeleteAccount")
            .html(label_please_wait)
            .attr("disabled", true);
        $.ajax({
            url: baseUrl + "/account/destroy",
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            success: function (response) {
                $("#confirmDeleteAccount")
                    .html(label_yes)
                    .attr("disabled", false);
                $("#deleteAccountModal").modal("hide");
                if (!response.error) {
                    toastr.success(response["message"]);
                    setTimeout(
                        function () {
                            location.reload();
                        },
                        parseFloat(toastTimeOut) * 1000,
                    );
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (data) {
                $("#confirmDeleteAccount")
                    .html(label_yes)
                    .attr("disabled", false);
                $("#deleteAccountModal").modal("hide");
                toastr.error(label_something_went_wrong);
            },
        });
    });
});
function update_status(e) {
    var id = e["id"];
    var name = e["name"];
    var status;
    var is_checked = $("input[name=" + name + "]:checked");
    if (is_checked.length >= 1) {
        status = 1;
    } else {
        status = 0;
    }
    $.ajax({
        url: baseUrl + "/todos/update_status",
        type: "POST", // Use POST method
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        data: {
            _method: "PUT", // Specify the desired method
            id: id,
            status: status,
        },
        success: function (response) {
            if (response.error == false) {
                location.reload();
            } else {
                toastr.error(response.message);
            }
        },
    });
}
$(document).on("click", ".edit-todo", function () {
    var id = $(this).data("id");
    $("#edit_todo_modal").modal("show");
    $.ajax({
        url: baseUrl + "/todos/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#todo_id").val(response.todo.id);
            $("#todo_title").val(response.todo.title);
            $("#todo_priority").val(response.todo.priority);
            $("#todo_description").val(response.todo.description);
            if (response.todo?.reminders?.length > 0) {
                const reminder = response.todo.reminders[0];
                $("#edit-todo-reminder-switch")
                    .prop("checked", reminder.is_active === 1)
                    .trigger("change"); // trigger change event
                $("#edit-todo-frequency-type")
                    .val(reminder.frequency_type)
                    .trigger("change");
                switch (reminder.frequency_type) {
                    case "weekly":
                        $("#edit-todo-day-of-week").val(
                            reminder.day_of_week || "",
                        );
                        break;
                    case "monthly":
                        $("#edit-todo-day-of-month").val(
                            reminder.day_of_month || "",
                        );
                        break;
                }
                if (reminder.time_of_day) {
                    const timeOfDay = reminder.time_of_day.slice(0, 5);
                    $("#edit-todo-time-of-day").val(timeOfDay);
                }
            }
        },
    });
});
$(document).on("click", ".edit-note", function () {
    var id = $(this).data("id");
    $("#edit_note_modal").modal("show");
    // Get the current color class
    var classes = $("#note_color").attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    const noteTypeLabels = {
        text: "Text Note",
        drawing: "Drawing Note",
    };
    $.ajax({
        url: baseUrl + "/notes/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#note_id").val(response.note.id);
            $("#note_title").val(response.note.title);
            $("#note_color")
                .val(response.note.color)
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + response.note.color);
            let noteType = response.note.note_type;
            // Set the note type select value and trigger change
            $("#editNoteType").val(noteType);
            $("#editNoteTypeDisplay").val(noteTypeLabels[noteType]);
            if (noteType === "text") {
                $("#edit-text-note-section").removeClass("d-none");
                $("#edit-drawing-note-section").addClass("d-none");
                $("#note_description").val(response.note.description || "");
            } else if (noteType === "drawing") {
                $("#edit-text-note-section").addClass("d-none");
                $("#edit-drawing-note-section").removeClass("d-none");
                // Set the drawing data
                $("#edit_drawing_data").val(response.note.drawing_data || "");
                // Initialize the drawing editor
                setTimeout(function () {
                    let drawingContainer = document.getElementById(
                        "edit_drawing-container",
                    );
                    if (drawingContainer) {
                        try {
                            // Clear any existing content
                            drawingContainer.innerHTML = "";
                            window.editNoteEditor = new jsdraw.Editor(drawingContainer);
                            window.editNoteEditor.getRootElement().style.height = "260px";
                            const toolbar = window.editNoteEditor.addToolbar();
                            $(
                                ".toolbar-internalWidgetId--selection-tool-widget, .toolbar-internalWidgetId--text-tool-widget, .toolbar-internalWidgetId--document-properties-widget, .pipetteButton,.toolbar-internalWidgetId--insert-image-widget",
                            ).hide();
                            setTimeout(() => {
                                $(".toolbar--pen-tool-toggle-buttons").hide();
                            }, 500);
                            // Try to load the image using jsdraw's API
                            if (response.note.drawing_data) {
                                var svgSavedData = response.note.drawing_data;
                                try {
                                    window.editNoteEditor.loadFromSVG(svgSavedData);
                                } catch (error) {
                                    console.error(
                                        "Error loading drawing data:",
                                        error,
                                    );
                                }
                            }
                        } catch (e) {
                            console.error(
                                "Error initializing jsDraw for edit:",
                                e,
                            );
                        }
                    }
                }, 300);
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching note data:", error);
        },
    });
});
$(document).on("submit", "#edit_note_modal form", function (e) {
    if ($("#editNoteType").val() === "drawing" && window.editNoteEditor) {
        console.log("Saving edit drawing data...");
        let drawingData = window.editNoteEditor.toSVG().outerHTML;
        let encodedDrawingData = btoa(
            unescape(
                encodeURIComponent(drawingData),
            ),
        );
        $("#edit_drawing_data").val(encodedDrawingData);
    }
});
$(document).on("click", ".edit-status", function () {
    var id = $(this).data("id");
    $("#edit_status_modal").modal("show");
    var classes = $("#status_color").attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    $.ajax({
        url: baseUrl + "/status/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#status_id").val(response.status.id);
            $("#status_title").val(response.status.title);
            $("#status_color")
                .val(response.status.color)
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + response.status.color);
            var modalForm = $("#edit_status_modal").find("form");
            var usersSelect = modalForm.find(
                '.tom_static_select[name="role_ids[]"]',
            );
            if (usersSelect.length && usersSelect[0].tomselect) {
                usersSelect[0].tomselect.setValue(response.roles);
            } else {
                usersSelect.val(response.roles);
                usersSelect.trigger("change");
            }
        },
    });
});
$(document).on("click", ".edit-tag", function () {
    var id = $(this).data("id");
    $("#edit_tag_modal").modal("show");
    var classes = $("#tag_color").attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    $.ajax({
        url: baseUrl + "/tags/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#tag_id").val(response.tag.id);
            $("#tag_title").val(response.tag.title);
            $("#tag_color")
                .val(response.tag.color)
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + response.tag.color);
        },
    });
});
$(document).on("click", ".edit-leave-request", function () {
    var id = $(this).data("id");
    $("#edit_leave_request_modal").modal("show");
    $.ajax({
        url: baseUrl + "/leave-requests/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            var formattedFromDate = moment(response.lr.from_date).format(
                js_date_format,
            );
            var formattedToDate = moment(response.lr.to_date).format(
                js_date_format,
            );
            var fromDateSelect = $("#edit_leave_request_modal").find(
                "#update_start_date",
            );
            var toDateSelect = $("#edit_leave_request_modal").find(
                "#update_end_date",
            );
            var reasonSelect = $("#edit_leave_request_modal").find(
                '[name="reason"]',
            );
            var commentSelect = $("#edit_leave_request_modal").find(
                '[name="comment"]',
            );
            var totalDaysSelect = $("#edit_leave_request_modal").find(
                "#update_total_days",
            );
            $("#lr_id").val(response.lr.id);
            $("#leaveUser").val(
                response.lr.user.first_name + " " + response.lr.user.last_name,
            );
            fromDateSelect.val(formattedFromDate);
            toDateSelect.val(formattedToDate);
            initializeDateRangePicker("#update_start_date,#update_end_date");
            var start_date = moment(fromDateSelect.val(), js_date_format);
            var end_date = moment(toDateSelect.val(), js_date_format);
            var total_days = end_date.diff(start_date, "days") + 1;
            totalDaysSelect.val(total_days);
            if (response.lr.from_time && response.lr.to_time) {
                $("#updatePartialLeave")
                    .prop("checked", true)
                    .trigger("change");
                var fromTimeSelect = $("#edit_leave_request_modal").find(
                    '[name="from_time"]',
                );
                var toTimeSelect = $("#edit_leave_request_modal").find(
                    '[name="to_time"]',
                );
                fromTimeSelect.val(response.lr.from_time);
                toTimeSelect.val(response.lr.to_time);
            } else {
                $("#updatePartialLeave")
                    .prop("checked", false)
                    .trigger("change");
            }
            if (response.lr.visible_to_all) {
                $("#edit_leave_request_modal")
                    .find(".leaveVisibleToAll")
                    .prop("checked", true)
                    .trigger("change");
            } else {
                $("#edit_leave_request_modal")
                    .find(".leaveVisibleToAll")
                    .prop("checked", false)
                    .trigger("change");
                var visibleToSelect = $("#edit_leave_request_modal").find(
                    '.users_select[name="visible_to_ids[]"]',
                );
                if (
                    response.lr.visible_to_users &&
                    response.lr.visible_to_users.length > 0
                ) {
                    // Iterate through the users and add them to the select element
                    response.lr.visible_to_users.forEach(function (user) {
                        var userOption = new Option(
                            user.first_name + " " + user.last_name,
                            user.id,
                            true,
                            true,
                        );
                        visibleToSelect.append(userOption);
                    });
                    // Trigger select2 to update the selected values
                    visibleToSelect.trigger("change");
                }
            }
            reasonSelect.val(response.lr.reason);
            commentSelect.val(response.lr.comment);
            $("input[name=status][value=" + response.lr.status + "]").prop(
                "checked",
                true,
            );

            // Set the "Mark as Paid Leave" toggle based on database value
            var isPaidToggle = $("#edit_leave_request_modal").find(
                "#is_paid_toggle",
            );
            console.log("is_paid value from DB:", response.lr.is_paid);

            // Remove any disabled attribute to ensure it's clickable
            isPaidToggle.prop("disabled", false);

            if (response.lr.is_paid === true || response.lr.is_paid === 1) {
                isPaidToggle.prop("checked", true);
                console.log("Toggle set to ON (paid)");
            } else if (
                response.lr.is_paid === false ||
                response.lr.is_paid === 0
            ) {
                isPaidToggle.prop("checked", false);
                console.log("Toggle set to OFF (unpaid)");
            } else {
                // If null/undefined, default to ON
                isPaidToggle.prop("checked", true);
                console.log("Toggle set to ON (default - is_paid is null)");
            }

            // Fetch and display leave balance
            if (response.lr.user && response.lr.user.id) {
                console.log(
                    "Fetching balance for user:",
                    response.lr.user.id,
                    "excluding leave:",
                    response.lr.id,
                );
                $.ajax({
                    url: baseUrl + "/leave-requests/get-user-balance",
                    method: "GET",
                    data: {
                        user_id: response.lr.user.id,
                        exclude_leave_id: response.lr.id,
                    },
                    success: function (balanceResponse) {
                        console.log("Balance response:", balanceResponse);
                        if (!balanceResponse.error && balanceResponse.balance) {
                            var balance = balanceResponse.balance;

                            var balanceHtml = renderRemainingLeavesSummary(
                                balance,
                                {
                                    heading: label_balance_snapshot,
                                    includeAccrualMeta: true,
                                },
                            );
                            $("#leave_balance_info").html(balanceHtml);
                        }
                    },
                    error: function () {
                        $("#leave_balance_info").html(
                            '<span class="text-danger">' +
                                label_err_try_again +
                                "</span>",
                        );
                    },
                });
            }
        },
    });
});
$(document).on("click", ".edit-contract-type", function () {
    var id = $(this).data("id");
    $("#edit_contract_type_modal").modal("show");
    $.ajax({
        url: baseUrl + "/contracts/get-contract-type/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#update_contract_type_id").val(response.ct.id);
            $("#contract_type").val(response.ct.type);
        },
    });
});
$(document).on("click", ".edit-contract", function () {
    var id = $(this).data("id");
    $("#edit_contract_modal").modal("show");
    $.ajax({
        url: baseUrl + "/contracts/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            if (response.error == false) {
                var formattedStartDate = moment(
                    response.contract.start_date,
                ).format(js_date_format);
                var formattedEndDate = moment(
                    response.contract.end_date,
                ).format(js_date_format);
                $("#contract_id").val(response.contract.id);
                $("#edit_contract_title").val(response.contract.title);
                $("#value").val(response.contract.value);
                if ($("#client_id")[0].tomselect) {
                    $("#client_id")[0].tomselect.addOption({
                        id: response.contract.client.id,
                        text:
                            response.contract.client.first_name +
                            " " +
                            response.contract.client.last_name,
                    });
                    $("#client_id")[0].tomselect.setValue(
                        response.contract.client.id,
                    );
                } else {
                    var clientOption = new Option(
                        response.contract.client.first_name +
                            " " +
                            response.contract.client.last_name,
                        response.contract.client.id,
                        true,
                        true,
                    );
                    $("#client_id").append(clientOption).trigger("change");
                }

                if ($("#project_id")[0].tomselect) {
                    $("#project_id")[0].tomselect.addOption({
                        id: response.contract.project.id,
                        text: response.contract.project.title,
                    });
                    $("#project_id")[0].tomselect.setValue(
                        response.contract.project.id,
                    );
                } else {
                    var projectOption = new Option(
                        response.contract.project.title,
                        response.contract.project.id,
                        true,
                        true,
                    );
                    $("#project_id").append(projectOption).trigger("change");
                }

                if ($("#contract_type_id")[0].tomselect) {
                    $("#contract_type_id")[0].tomselect.addOption({
                        id: response.contract.contract_type.id,
                        text: response.contract.contract_type.type,
                    });
                    $("#contract_type_id")[0].tomselect.setValue(
                        response.contract.contract_type.id,
                    );
                } else {
                    var contractTypeOption = new Option(
                        response.contract.contract_type.type,
                        response.contract.contract_type.id,
                        true,
                        true,
                    );
                    $("#contract_type_id")
                        .append(contractTypeOption)
                        .trigger("change");
                }
                var description =
                    response.contract.description !== null
                        ? response.contract.description
                        : "";
                $("#update_contract_description").val(description);
                $("#update_start_date").val(formattedStartDate);
                $("#update_end_date").val(formattedEndDate);
                initializeDateRangePicker(
                    "#update_start_date, #update_end_date",
                );
            } else {
                location.reload();
            }
        },
    });
});
$(document).on("click", ".edit-expense-type", function () {
    var id = $(this).data("id");
    $("#edit_expense_type_modal").modal("show");
    $.ajax({
        url: baseUrl + "/expenses/get-expense-type/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#update_expense_type_id").val(response.et.id);
            $("#expense_type_title").val(response.et.title);
            $("#expense_type_description").val(response.et.description);
        },
    });
});
$(document).on("click", ".edit-expense", function () {
    var id = $(this).data("id");
    $("#edit_expense_modal").modal("show");
    $.ajax({
        url: baseUrl + "/expenses/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            var formattedExpDate = moment(response.exp.expense_date).format(
                js_date_format,
            );
            $("#update_expense_id").val(response.exp.id);
            $("#expense_title").val(response.exp.title);
            if (response.exp.expense_type && response.exp.expense_type.title) {
                if ($("#expense_type_id")[0].tomselect) {
                    $("#expense_type_id")[0].tomselect.addOption({
                        id: response.exp.expense_type.id,
                        text: response.exp.expense_type.title,
                    });
                    $("#expense_type_id")[0].tomselect.setValue(
                        response.exp.expense_type.id,
                    );
                }
            }
            if (response.exp.user && response.exp.user.id) {
                if ($("#expense_user_id")[0].tomselect) {
                    $("#expense_user_id")[0].tomselect.addOption({
                        id: response.exp.user.id,
                        text:
                            response.exp.user.first_name +
                            " " +
                            response.exp.user.last_name,
                    });
                    $("#expense_user_id")[0].tomselect.setValue(
                        response.exp.user.id,
                    );
                }
            }
            $("#expense_amount").val(response.exp.amount);
            $("#update_expense_date").val(formattedExpDate);
            $("#expense_note").val(response.exp.note);
        },
    });
});
$(document).on("click", ".edit-language", function () {
    var id = $(this).data("id");
    $("#edit_language_modal").modal("show");
    $.ajax({
        url: baseUrl + "/settings/languages/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#language_id").val(response.language.id);
            $("#language_title").val(response.language.name);
        },
    });
});
$(document).on("click", ".edit-payment", function () {
    var id = $(this).data("id");
    $("#edit_payment_modal").modal("show");
    $.ajax({
        url: baseUrl + "/payments/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            var formattedExpDate = moment(response.payment.payment_date).format(
                js_date_format,
            );
            $("#update_payment_id").val(response.payment.id);
            // Update payment_user_id with user details
            if (response.payment.user && response.payment.user.id) {
                var ts = document.querySelector("#payment_user_id").tomselect;
                ts.addOption({
                    value: response.payment.user.id,
                    text:
                        response.payment.user.first_name +
                        " " +
                        response.payment.user.last_name,
                });
                ts.setValue(response.payment.user.id);
            } else {
                if (document.querySelector("#payment_user_id").tomselect) {
                    document
                        .querySelector("#payment_user_id")
                        .tomselect.clear();
                }
            }
            // Update payment_invoice_id with invoice details
            if (response.payment.invoice && response.payment.invoice.id) {
                var ts = document.querySelector(
                    "#payment_invoice_id",
                ).tomselect;
                ts.addOption({
                    value: response.payment.invoice.id,
                    text:
                        label_invoice_id_prefix +
                        "" +
                        response.payment.invoice.id,
                });
                ts.setValue(response.payment.invoice.id);
            } else {
                if (document.querySelector("#payment_invoice_id").tomselect) {
                    document
                        .querySelector("#payment_invoice_id")
                        .tomselect.clear();
                }
            }
            // Update payment_pm_id with payment method details
            if (
                response.payment.payment_method &&
                response.payment.payment_method.title
            ) {
                var ts = document.querySelector("#payment_pm_id").tomselect;
                ts.setValue(response.payment.payment_method_id);
            } else {
                if (document.querySelector("#payment_pm_id").tomselect) {
                    document.querySelector("#payment_pm_id").tomselect.clear();
                }
            }
            $("#payment_amount").val(response.payment.amount);
            $("#update_payment_date").val(formattedExpDate);
            $("#payment_note").val(response.payment.note);
        },
    });
});
/**
 * Initializes DateRangePicker for specified input elements, supporting both modal and offcanvas contexts.
 * Configures single-date pickers with custom formatting, dynamic parent anchoring, and conditional start dates.
 *
 * @param {string} inputSelector - jQuery selector for the date input elements to initialize.
 * @returns {void}
 */
function initializeDateRangePicker(inputSelector) {
    /**
     * List of modal and offcanvas IDs to check for parent context.
     * @type {string[]}
     */
    var modalsToCheck = [
        "#create_project_modal",
        "#edit_project_modal",
        "#create_milestone_modal",
        "#edit_milestone_modal",
        "#create_project_offcanvas",
        "#edit_project_offcanvas",
        "#create_task_offcanvas",
        "#edit_task_offcanvas",
        "#create_milestone_offcanvas",
        "#edit_milestone_offcanvas",
    ];
    $(inputSelector).each(function () {
        var $input = $(this);
        var isEmpty = $input.val() === ""; // Check if the input is empty
        // Check for closest modal or offcanvas
        var parentOverlay = $input.closest(".modal, .offcanvas");
        var parentOverlayId = parentOverlay.length
            ? parentOverlay.attr("id")
            : "";
        // Check if input is inside any of the specified modals or offcanvas
        var isInsideOverlay = modalsToCheck.some(function (overlayId) {
            var isInOverlay = $input.closest(overlayId).length > 0;
            if (isInOverlay) {
            }
            return isInOverlay;
        });
        /**
         * Configuration for DateRangePicker.
         * @type {Object}
         */
        var daterangepickerOptions = {
            alwaysShowCalendars: true,
            showCustomRangeLabel: true,
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: !isInsideOverlay,
            locale: {
                cancelLabel: "Clear",
                format: js_date_format,
            },
        };
        // Set parentEl to the closest modal or offcanvas, or body if none found
        if (parentOverlayId) {
            daterangepickerOptions.parentEl = `#${parentOverlayId}`;
        } else {
            daterangepickerOptions.parentEl = $(document.body);
        }
        // Conditionally add startDate if input is not empty
        if (!isEmpty) {
            daterangepickerOptions.startDate = moment(
                $input.val(),
                js_date_format,
            );
        }
        // Initialize DateRangePicker
        $input.daterangepicker(daterangepickerOptions);
        // Handle autoUpdateInput behavior
        if (isEmpty) {
            $input.val(""); // Ensure input remains empty if initially empty
        }
        // Manually update input value on date selection
        $input.on("apply.daterangepicker", function (ev, picker) {
            $(this).val(picker.startDate.format(js_date_format));
        });
    });
}
$(document).on("click", "#set-as-default", function (e) {
    e.preventDefault();
    var lang = $(this).data("lang");
    $("#default_language_modal").modal("show"); // show the confirmation modal
    $("#default_language_modal").on("click", "#confirm", function () {
        $("#default_language_modal")
            .find("#confirm")
            .html(label_please_wait)
            .attr("disabled", true);
        $.ajax({
            url: baseUrl + "/settings/languages/set-default",
            type: "PUT",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            data: {
                lang: lang,
            },
            success: function (response) {
                $("#default_language_modal")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                if (response.error == false) {
                    location.reload();
                } else {
                    toastr.error(response.message);
                    $("#default_language_modal").modal("hide");
                }
            },
        });
    });
});
$(document).on("click", "#set-default-view", function (e) {
    e.preventDefault();
    var type = $(this).data("type");
    var view = $(this).data("view");
    var url = baseUrl + "/save-" + type + "-view-preference";
    $("#set_default_view_modal").modal("show");
    $("#set_default_view_modal").off("click", "#confirm");
    $("#set_default_view_modal").on("click", "#confirm", function () {
        $("#set_default_view_modal")
            .find("#confirm")
            .html(label_please_wait)
            .attr("disabled", true);
        $.ajax({
            url: url,
            type: "PUT",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            data: {
                type: type,
                view: view,
            },
            success: function (response) {
                $("#set_default_view_modal")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                if (response.error == false) {
                    $("#set-default-view")
                        .text(label_default_view)
                        .removeClass("bg-secondary")
                        .addClass("bg-primary");
                    $("#set_default_view_modal").modal("hide");
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
        });
    });
});
$(document).on("click", "#remove-participant", function (e) {
    e.preventDefault();
    $("#leaveWorkspaceModal").modal("show"); // show the confirmation modal
    $("#leaveWorkspaceModal").on("click", "#confirm", function () {
        $("#leaveWorkspaceModal")
            .find("#confirm")
            .html(label_please_wait)
            .attr("disabled", true);
        $.ajax({
            url: baseUrl + "/workspaces/remove_participant",
            type: "GET",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            success: function (response) {
                $("#leaveWorkspaceModal")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                location.reload();
            },
            error: function (data) {
                $("#leaveWorkspaceModal")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                location.reload();
            },
        });
    });
});
/**
 * Resets and reinitializes DateRangePicker fields within a form, supporting both modal and offcanvas contexts.
 * Clears or sets default dates based on whether the form is inside an overlay, and reinitializes DateRangePicker instances.
 *
 * @param {jQuery} $form - The jQuery object representing the form containing date inputs.
 * @returns {void}
 */
function resetDateFields($form) {
    $form.find("input").each(function () {
        var $this = $(this);
        if ($this.data("daterangepicker")) {
            window.initSingleDatePicker({ selector: this });
        }
    });
}
/**
 * Initializes DateRangePicker for specified date input fields, supporting both modal and offcanvas contexts.
 * Configures single-date pickers with custom formatting, default values, and dynamic parent anchoring.
 * Handles specific date fields (#dob, #doj) with restricted date ranges.
 *
 * @listens document.ready - Executes when the DOM is fully loaded.
 * @returns {void}
 */
$(document).ready(function () {
    /**
     * List of input IDs to initialize with standardized Date Picker.
     * @type {string[]}
     */
    var idsToProcess = [
        "#start_date",
        "#end_date",
        "#update_start_date",
        "#update_end_date",
        "#lr_end_date",
        "#meeting_end_date",
        "#expense_date",
        "#update_expense_date",
        "#payment_date",
        "#update_payment_date",
        "#update_milestone_start_date",
        "#update_milestone_end_date",
        "#task_start_date",
        "#task_end_date",
        "#dob",
        "#doj",
    ];

    idsToProcess.forEach(function (id) {
        var config = { selector: id };
        if (id === "#dob" || id === "#doj") {
            config.minDate = moment("01/01/1950", "DD/MM/YYYY");
            config.maxDate = moment();
        }
        window.initSingleDatePicker(config);
    });
});
$(document).ready(function () {
    var filterIds = [
        "#report_start_date_between",
        "#report_end_date_between",
        "#filter_date_range",
        "#ie_date_between",
        "#ms_date_between",
        "#start_date_between",
        "#end_date_between",
        "#project_date_between",
        "#project_start_date_between",
        "#project_end_date_between",
        "#task_date_between",
        "#task_start_date_between",
        "#task_end_date_between",
        "#lr_date_between",
        "#lr_start_date_between",
        "#lr_end_date_between",
        "#contract_date_between",
        "#contract_start_date_between",
        "#contract_end_date_between",
        "#timesheet_date_between",
        "#timesheet_start_date_between",
        "#timesheet_end_date_between",
        "#meeting_date_between",
        "#meeting_start_date_between",
        "#meeting_end_date_between",
        "#activity_log_between_date",
        "#notification_between_date",
        "#expense_from_date_between",
        "#payment_date_between",
        "#lead_kanban_date_range",
        "#lead_date_range",
        "#candidate_date_between",
        "#interview_date_between",
        "#report_date_between",
    ];

    filterIds.forEach(function (id) {
        var baseId = id.replace("#", "");
        var tableIdMap = {
            project: "projects_table",
            task: "task_table",
            lr: "lr_table",
            contract: "contract_table",
            timesheet: "timesheet_table",
            meeting: "meetings_table",
            expense_from: "expense_table",
            payment: "payment_table",
        };

        var tableId = null;
        for (var prefix in tableIdMap) {
            if (baseId.startsWith(prefix)) {
                tableId = tableIdMap[prefix];
                break;
            }
        }

        var hiddenFrom = id + "_from";
        var hiddenTo = id + "_to";

        // Robust fallback logic for hidden field IDs
        if (!$(hiddenFrom).length) {
            var fallbacks = ["_between", "_range", "_date"];
            for (var i = 0; i < fallbacks.length; i++) {
                var fallbackFrom = id.replace(fallbacks[i], "_from");
                if ($(fallbackFrom).length) {
                    hiddenFrom = fallbackFrom;
                    break;
                }
            }
        }
        if (!$(hiddenTo).length) {
            var fallbacks = ["_between", "_range", "_date"];
            for (var i = 0; i < fallbacks.length; i++) {
                var fallbackTo = id.replace(fallbacks[i], "_to");
                if ($(fallbackTo).length) {
                    hiddenTo = fallbackTo;
                    break;
                }
            }
        }

        window.initAdvancedDateRangePicker({
            selector: id,
            hiddenFrom: hiddenFrom,
            hiddenTo: hiddenTo,
            tableId: tableId,
        });
    });
});
// Redundant manual handlers removed as they are now handled by initAdvancedDateRangePicker
$(
    "textarea#footer_text,textarea#contract_description,textarea#update_contract_description,textarea.description",
).tinymce({
    height: 250,
    menubar: false,
    plugins: [
        "link",
        "a11ychecker",
        "advlist",
        "advcode",
        "advtable",
        "autolink",
        "checklist",
        "export",
        "lists",
        "link",
        "image",
        "charmap",
        "preview",
        "anchor",
        "searchreplace",
        "visualblocks",
        "powerpaste",
        "fullscreen",
        "formatpainter",
        "insertdatetime",
        "media",
        "table",
        "help",
        "wordcount",
        "emoticons",
        "code",
    ],
    toolbar:
        "link | undo redo | a11ycheck casechange blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | removeformat | code blockquote emoticons table help",
});
$(
    "textarea#privacy_policy,textarea#terms_conditions,textarea#about_us",
).tinymce({
    height: 400,
    menubar: false,
    plugins: [
        "link",
        "a11ychecker",
        "advlist",
        "advcode",
        "advtable",
        "autolink",
        "checklist",
        "export",
        "lists",
        "link",
        "image",
        "charmap",
        "preview",
        "anchor",
        "searchreplace",
        "visualblocks",
        "powerpaste",
        "fullscreen",
        "formatpainter",
        "insertdatetime",
        "media",
        "table",
        "help",
        "wordcount",
        "emoticons",
        "code",
    ],
    toolbar:
        "link | undo redo | a11ycheck casechange blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | removeformat | code blockquote emoticons table help",
});
document.addEventListener("focusin", function (e) {
    if (
        e.target.closest(
            ".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root",
        ) !== null
    ) {
        e.stopImmediatePropagation();
    }
});
$(document).on("submit", ".form-submit-event", function (e) {
    e.preventDefault();
    if ($("#net_payable").length > 0) {
        var net_payable = $("#net_payable").text();
        $("#net_pay").val(net_payable);
    }
    var formData = new FormData(this);
    // NEW CODE: Check if this is an HTML template and encode it if needed
    if (
        $(this).attr("action").includes("store_template") ||
        $(this).attr("action").includes("/email-templates/store") ||
        $(this).attr("action").includes("email-templates/update") ||
        $(this).attr("action").includes("/emails/store") ||
        $(this).attr("action").includes("/emails/preview")
    ) {
        // Find the HTML content field - adjust the selector as needed
        var contentField = $(this).find(
            'textarea[name="content"] , input[name="content"]',
        );
        if (contentField.length > 0) {
            // Remove the original content from FormData
            formData.delete("content");
            // Add the content as base64 encoded to bypass ModSecurity filters
            var encodedContent = btoa(contentField.val());
            formData.append("content", encodedContent);
            formData.append("is_encoded", "1");
        }
    }
    // END OF NEW CODE
    var currentForm = $(this);
    var submit_btn = $(this).find("#submit_btn");
    var btn_html = submit_btn.html();
    var btn_val = submit_btn.val();
    var redirect_url = currentForm.find('input[name="redirect_url"]').val();
    redirect_url =
        typeof redirect_url !== "undefined" && redirect_url ? redirect_url : "";
    var button_text =
        btn_html != "" || btn_html != "undefined" ? btn_html : btn_val;
    var tableInput = currentForm.find('input[name="table"]');
    var tableID = tableInput.length ? tableInput.val() : "table";
    if (currentForm.closest("#edit_contract_modal").length > 0) {
        // Ensure Dropzone is initialized for #contract-dropzone
        if (Dropzone.instances.length > 0) {
            var dropzoneInstance = Dropzone.forElement("#contract-dropzone");
            if (dropzoneInstance.getAcceptedFiles().length > 0) {
                dropzoneInstance.getAcceptedFiles().forEach(function (file) {
                    formData.append("signed_pdf", file);
                });
            }
        }
    }
    $.ajax({
        type: "POST",
        url: $(this).attr("action"),
        data: formData,
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        beforeSend: function () {
            submit_btn.html(label_please_wait);
            submit_btn.attr("disabled", true);
        },
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (result) {
            submit_btn.html(button_text);
            submit_btn.attr("disabled", false);

            // Check if override confirmation is required (for payslip forms)
            if (result["override_required"] === true) {
                console.log(
                    "[Override Check] Override required detected",
                    result.override_data,
                );

                // Check for function in global scope or window object
                var showModalFunc =
                    typeof showOverrideConfirmationModal !== "undefined"
                        ? showOverrideConfirmationModal
                        : typeof window.showOverrideConfirmationModal !==
                            "undefined"
                          ? window.showOverrideConfirmationModal
                          : null;

                console.log("[Override Check] Function check:", {
                    "showOverrideConfirmationModal exists":
                        typeof showOverrideConfirmationModal,
                    "window.showOverrideConfirmationModal exists":
                        typeof window.showOverrideConfirmationModal,
                    "showModalFunc found": showModalFunc !== null,
                    "showModalFunc is function":
                        showModalFunc && typeof showModalFunc === "function",
                });

                if (showModalFunc && typeof showModalFunc === "function") {
                    try {
                        // Show override confirmation modal
                        var formData = new FormData(currentForm[0]);
                        console.log(
                            "[Override Check] Calling showOverrideConfirmationModal",
                        );
                        showModalFunc(
                            result.override_data,
                            currentForm,
                            formData,
                        );
                        console.log(
                            "[Override Check] Modal should be shown now",
                        );
                        return; // Don't proceed with normal success handling
                    } catch (error) {
                        console.error(
                            "[Override Check] Error showing modal:",
                            error,
                        );
                        // Fallback: show alert
                        alert(
                            (APP_LABELS && APP_LABELS["override_required"]
                                ? APP_LABELS["override_required"]
                                : "Override Required!") +
                                "\n\n" +
                                "Available Balance: " +
                                (result.override_data?.available_balance || 0) +
                                "\n" +
                                "Excess Paid Leave: " +
                                (result.override_data?.excess_paid_leave || 0) +
                                "\n" +
                                "Delta Paid Leave: " +
                                (result.override_data?.delta_paid_leave || 0),
                        );
                        return; // Don't proceed with normal success handling
                    }
                } else {
                    // Function not found - this shouldn't happen, but log for debugging
                    console.error(
                        "[Override Check] showOverrideConfirmationModal function not found. Override required but modal cannot be shown.",
                    );
                    console.log(
                        "[Override Check] Override data:",
                        result.override_data,
                    );
                    // Fallback: show alert
                    alert(
                        (APP_LABELS && APP_LABELS["override_required"]
                            ? APP_LABELS["override_required"]
                            : "Override Required!") +
                            "\n\n" +
                            "Available Balance: " +
                            (result.override_data?.available_balance || 0) +
                            "\n" +
                            "Excess Paid Leave: " +
                            (result.override_data?.excess_paid_leave || 0) +
                            "\n" +
                            "Delta Paid Leave: " +
                            (result.override_data?.delta_paid_leave || 0) +
                            "\n\n" +
                            "Please refresh the page and try again.",
                    );
                    return; // Don't proceed with normal success handling
                }
            }

            if (result["error"] == true) {
                toastr.error(result["message"]);
            } else {
                var modalWithClass = $(".modal.fade.show");
                var idOfModal = modalWithClass.attr("id");
                $("#" + idOfModal).modal("hide");
                if ($(".empty-state").length > 0) {
                    if (result.hasOwnProperty("message")) {
                        toastr.success(result["message"]);
                        setTimeout(
                            handleRedirection,
                            parseFloat(toastTimeOut) * 1000,
                        );
                    } else {
                        handleRedirection();
                    }
                } else {
                    if (currentForm.find('input[name="dnr"]').length > 0) {
                        if (modalWithClass.length > 0) {
                            $("#" + tableID).bootstrapTable("refresh");
                            currentForm[0].reset();
                            var partialLeaveCheckbox = $("#partialLeave");
                            if (partialLeaveCheckbox.length) {
                                partialLeaveCheckbox.trigger("change");
                            }
                            resetDateFields(currentForm);
                            if (idOfModal == "create_status_modal") {
                                var dropdownSelector = modalWithClass.find(
                                    'select[name="status_id"]',
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.status;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("data-color", newItem.color)
                                        .attr("selected", true)
                                        .text(
                                            newItem.title +
                                                " (" +
                                                newItem.color +
                                                ")",
                                        );
                                    $(dropdownSelector).append(newOption);
                                    var openModalId = dropdownSelector
                                        .closest(".modal.fade.show")
                                        .attr("id");
                                    // List of all possible modal IDs
                                    var modalIds = [
                                        "#create_project_modal",
                                        "#edit_project_modal",
                                        "#create_task_offcanvas",
                                        "#edit_task_offcanvas",
                                    ];
                                    // Iterate through each modal ID
                                    modalIds.forEach(function (modalId) {
                                        // If the modal ID is not the open one
                                        if (modalId !== "#" + openModalId) {
                                            // Find the select element within the modal
                                            var otherModalSelector = $(
                                                modalId,
                                            ).find('select[name="status_id"]');
                                            // Create a new option without 'selected' attribute
                                            var otherOption = $(
                                                "<option></option>",
                                            )
                                                .attr("value", newItem.id)
                                                .attr(
                                                    "data-color",
                                                    newItem.color,
                                                )
                                                .text(
                                                    newItem.title +
                                                        " (" +
                                                        newItem.color +
                                                        ")",
                                                );
                                            // Append the option to the select element in the modal
                                            otherModalSelector.append(
                                                otherOption,
                                            );
                                        }
                                    });
                                }
                            }
                            if (idOfModal == "create_priority_modal") {
                                var dropdownSelector = modalWithClass.find(
                                    'select[name="priority_id"]',
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.priority;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr(
                                            "class",
                                            "badge bg-label-" + newItem.color,
                                        )
                                        .attr("selected", true)
                                        .text(
                                            newItem.title +
                                                " (" +
                                                newItem.color +
                                                ")",
                                        );
                                    $(dropdownSelector).append(newOption);
                                    var openModalId = dropdownSelector
                                        .closest(".modal.fade.show")
                                        .attr("id");
                                    // List of all possible modal IDs
                                    var modalIds = [
                                        "#create_project_modal",
                                        "#edit_project_modal",
                                        "#create_task_offcanvas",
                                        "#edit_task_offcanvas",
                                    ];
                                    // Iterate through each modal ID
                                    modalIds.forEach(function (modalId) {
                                        // If the modal ID is not the open one
                                        if (modalId !== "#" + openModalId) {
                                            // Find the select element within the modal
                                            var otherModalSelector = $(
                                                modalId,
                                            ).find(
                                                'select[name="priority_id"]',
                                            );
                                            // Create a new option without 'selected' attribute
                                            var otherOption = $(
                                                "<option></option>",
                                            )
                                                .attr("value", newItem.id)
                                                .attr(
                                                    "class",
                                                    "badge bg-label-" +
                                                        newItem.color,
                                                )
                                                .text(
                                                    newItem.title +
                                                        " (" +
                                                        newItem.color +
                                                        ")",
                                                );
                                            // Append the option to the select element in the modal
                                            otherModalSelector.append(
                                                otherOption,
                                            );
                                        }
                                    });
                                }
                            }
                            if (idOfModal == "create_tag_modal") {
                                var dropdownSelector = modalWithClass.find(
                                    'select[name="tag_ids[]"]',
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.tag;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("data-color", newItem.color)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector).append(newOption);
                                    $(dropdownSelector).trigger("change");
                                    var openModalId = dropdownSelector
                                        .closest(".modal.fade.show")
                                        .attr("id");
                                    // List of all possible modal IDs
                                    var modalIds = [
                                        "#create_project_modal",
                                        "#edit_project_modal",
                                    ];
                                    // Iterate through each modal ID
                                    modalIds.forEach(function (modalId) {
                                        // If the modal ID is not the open one
                                        if (modalId !== "#" + openModalId) {
                                            // Find the select element within the modal
                                            var otherModalSelector = $(
                                                modalId,
                                            ).find('select[name="tag_ids[]"]');
                                            // Create a new option without 'selected' attribute
                                            var otherOption = $(
                                                "<option></option>",
                                            )
                                                .attr("value", newItem.id)
                                                .attr(
                                                    "data-color",
                                                    newItem.color,
                                                )
                                                .text(newItem.title);
                                            // Append the option to the select element in the modal
                                            otherModalSelector.append(
                                                otherOption,
                                            );
                                        }
                                    });
                                }
                            }
                            if (idOfModal == "create_item_modal") {
                                var dropdownSelector = $("#item_id");
                                if (dropdownSelector.length) {
                                    var newItem = result.item;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector).append(newOption);
                                    $(dropdownSelector).trigger("change");
                                }
                            }
                            if (idOfModal === "create_contract_type_modal") {
                                var newItem = result.ct;
                                // Find the currently open contract modal
                                var contractModal = $(
                                    "#create_contract_modal.show, #edit_contract_modal.show",
                                );
                                if (contractModal.length) {
                                    var dropdownSelector = contractModal.find(
                                        'select[name="contract_type_id"]',
                                    );
                                    if (dropdownSelector.length) {
                                        // Append and select the new option
                                        var newOption = $("<option></option>")
                                            .attr("value", newItem.id)
                                            .text(newItem.type)
                                            .attr("selected", true);
                                        dropdownSelector
                                            .append(newOption)
                                            .val(newItem.id)
                                            .trigger("change");
                                    }
                                }
                                // Append to the *other* contract modal (to keep both in sync)
                                var otherContractModal = $(
                                    "#create_contract_modal, #edit_contract_modal",
                                )
                                    .not(contractModal)
                                    .find('select[name="contract_type_id"]');
                                if (otherContractModal.length) {
                                    var otherOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .text(newItem.type);
                                    otherContractModal.append(otherOption);
                                }
                                // Close only the contract type modal
                                $("#create_contract_type_modal").modal("hide");
                                // Show success message
                                toastr.success(result["message"]);
                                currentForm.find(".error-message").html("");
                                // Stop further processing to prevent handleRedirection
                                return false;
                            }
                            if (idOfModal == "create_pm_modal") {
                                var dropdownSelector = $(
                                    'select[name="payment_method_id"]',
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.pm;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector).append(newOption);
                                }
                            }
                            if (idOfModal == "create_allowance_modal") {
                                var dropdownSelector = $(
                                    'select[name="allowance_id"]',
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.allowance;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector)
                                        .append(newOption)
                                        .trigger("change");
                                }
                            }
                            if (idOfModal == "create_deduction_modal") {
                                var dropdownSelector = $(
                                    'select[name="deduction_id"]',
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.deduction;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector)
                                        .append(newOption)
                                        .trigger("change");
                                }
                            }
                        }
                        toastr.success(result["message"]);
                        currentForm.find(".error-message").html("");
                    } else {
                        if (result.hasOwnProperty("message")) {
                            toastr.success(result["message"]);
                            setTimeout(
                                handleRedirection,
                                parseFloat(toastTimeOut) * 1000,
                            );
                        } else {
                            handleRedirection();
                        }
                    }
                }
            }
        },
        error: function (xhr, status, error) {
            submit_btn.html(button_text);
            submit_btn.attr("disabled", false);
            if (xhr.status === 422) {
                // Handle validation errors here
                var response = xhr.responseJSON; // Assuming you're returning JSON
                // You can access validation errors from the response object
                var errors = response.errors;
                if (errors["country_code"]) {
                    errors["phone"] = errors["country_code"];
                    delete errors["country_code"];
                }
                // Example: Display the first validation error message
                toastr.error(label_please_correct_errors);
                var showInModal = response.showInModal; // Flag to decide if errors should be shown in the modal
                if (showInModal) {
                    // Get validation errors from the response
                    var errorHtmlBody = "";
                    // Loop through the validation errors
                    $.each(errors, function (row, fields) {
                        errorHtmlBody += `<div><strong>${row}</strong><ul>`;
                        $.each(fields, function (field, messages) {
                            messages.forEach(function (msg) {
                                errorHtmlBody += `<li>${msg}</li>`;
                            });
                        });
                        errorHtmlBody += `</ul></div>`;
                    });
                    // Inject error HTML into the modal
                    $("#errorModalContent").html(errorHtmlBody);
                    $("#errorModalBody").removeClass("d-none");
                }
                // Assuming you have a list of all input fields with error messages
                var inputFields = currentForm.find(
                    "input[name], select[name], textarea[name]",
                );
                inputFields = $(inputFields.toArray().reverse());
                // Iterate through all input fields
                inputFields.each(function () {
                    var inputField = $(this);
                    var fieldName = inputField.attr("name");
                    var errorMessageElement = $(
                        '<span class="text-danger error-message"></span>',
                    );
                    if (errors && errors[fieldName]) {
                        if (
                            inputField.attr("type") !== "radio" &&
                            inputField.attr("type") !== "hidden"
                        ) {
                            // Remove existing error messages
                            if (
                                inputField.hasClass("select2-hidden-accessible")
                            ) {
                                inputField
                                    .parent()
                                    .find(".text-danger.error-message")
                                    .remove();
                                inputField
                                    .siblings(".select2")
                                    .after(errorMessageElement);
                            } else if (
                                inputField.closest(".input-group-merge")
                                    .length > 0
                            ) {
                                var inputGroup =
                                    inputField.closest(".input-group-merge");
                                inputGroup
                                    .next(".text-danger.error-message")
                                    .remove();
                                inputGroup.after(errorMessageElement);
                            } else if (
                                inputField.closest(".input-group").length > 0
                            ) {
                                var inputGroup =
                                    inputField.closest(".input-group");
                                inputGroup
                                    .next(".text-danger.error-message")
                                    .remove();
                                inputGroup.after(errorMessageElement);
                            } else if (
                                inputField.is("textarea#privacy_policy")
                            ) {
                                // Handle textarea with id privacy_policy
                                inputField
                                    .next(".text-danger.error-message")
                                    .remove();
                                inputField
                                    .parent()
                                    .find(".mt-2")
                                    .first()
                                    .before(errorMessageElement);
                            } else {
                                inputField
                                    .next(".text-danger.error-message")
                                    .remove();
                                inputField.after(errorMessageElement);
                            }
                        }
                        // If there is a validation error message for this field, display it
                        if (errors[fieldName][0].includes("required")) {
                            errorMessageElement.text(
                                APP_LABELS &&
                                    APP_LABELS["this_field_is_required"]
                                    ? APP_LABELS["this_field_is_required"]
                                    : "This field is required.",
                            );
                        } else {
                            errorMessageElement.text(errors[fieldName]);
                        }
                        inputField[0].scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                        inputField.focus();
                    } else {
                        // If there is no validation error message, clear the existing message
                        var existingErrorMessage = inputField.next(
                            ".text-danger.error-message",
                        );
                        if (inputField.hasClass("select2-hidden-accessible")) {
                            existingErrorMessage = inputField
                                .parent()
                                .find(".text-danger.error-message");
                        } else if (
                            inputField.closest(".input-group-merge").length > 0
                        ) {
                            var inputGroup =
                                inputField.closest(".input-group-merge");
                            existingErrorMessage = inputGroup.next(
                                ".text-danger.error-message",
                            );
                        } else if (
                            inputField.closest(".input-group").length > 0
                        ) {
                            var inputGroup = inputField.closest(".input-group");
                            existingErrorMessage = inputGroup.next(
                                ".text-danger.error-message",
                            );
                        }
                        if (existingErrorMessage.length > 0) {
                            existingErrorMessage.remove();
                        }
                    }
                });
            } else {
                var response = xhr.responseJSON;
                if (response && response.message && response.exception) {
                    var errorMessage = response.message;
                    var match = errorMessage.match(
                        /Access denied for user '([^']+)'@/,
                    );
                    if (match) {
                        var dbUser = match[1];
                        var customErrorMessage =
                            "Please try changing the password for database user " +
                            dbUser +
                            " or recreate the database.";
                        toastr.error(customErrorMessage);
                    } else {
                        // Check if it's an SQL error and extract relevant part
                        var sqlErrorPattern = /SQLSTATE\[[0-9]+\]: [^\(]+/;
                        var nonSqlErrorPattern =
                            /\b(?!SQLSTATE\[[0-9]+\]): [^\r\n]+/;
                        if (sqlErrorPattern.test(errorMessage)) {
                            var shortErrorMessage =
                                errorMessage.match(sqlErrorPattern)[0];
                            toastr.error(shortErrorMessage);
                        } else if (nonSqlErrorPattern.test(errorMessage)) {
                            var shortErrorMessage =
                                errorMessage.match(nonSqlErrorPattern)[0];
                            toastr.error(shortErrorMessage);
                        } else {
                            toastr.error("An unexpected error occurred.");
                        }
                    }
                } else {
                    toastr.error("An unexpected error occurred.");
                }
            }
        },
    });
    function handleRedirection() {
        if (redirect_url === "") {
            window.location.reload(); // Reload the current page
        } else {
            window.location.href = redirect_url; // Redirect to specified URL
        }
    }
});
// Click event handler for the favorite icon
$(document).on("click", ".favorite-icon", function () {
    var icon = $(this);
    var entityId = icon.data("id"); // ID of the entity (e.g., project, task)
    var entityType = icon.data("type") || "projects"; // Default to 'projects' if no type is provided
    var isFavorite = icon.attr("data-favorite");
    isFavorite = isFavorite == 1 ? 0 : 1;
    var dataTitle = icon.data("bs-original-title");
    var temp = dataTitle !== undefined ? "data-bs-original-title" : "title";
    // Send an AJAX request to update the favorite status
    $.ajax({
        url: baseUrl + "/" + entityType + "/update-favorite/" + entityId,
        type: "PATCH",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            is_favorite: isFavorite,
        },
        success: function (response) {
            if (response.error == false) {
                if (isFavorite == 0) {
                    icon.removeClass("bxs-star");
                    icon.addClass("bx-star");
                    icon.attr(temp, add_favorite); // Update the tooltip text
                    icon.find("i")
                        .removeClass("bxs-star text-warning")
                        .addClass("bx-star text-muted");
                } else {
                    icon.removeClass("bx-star");
                    icon.addClass("bxs-star");
                    icon.attr(temp, remove_favorite); // Update the tooltip text
                    icon.find("i")
                        .removeClass("bx-star text-muted")
                        .addClass("bxs-star text-warning");
                }
                icon.attr("data-favorite", isFavorite);
                var textNode = icon.contents().filter(function () {
                    return this.nodeType === 3 && this.nodeValue.trim() !== "";
                });
                if (textNode.length) {
                    if (!icon.hasClass("dropdown-item")) {
                        textNode[0].nodeValue =
                            isFavorite == 0
                                ? " " + add_favorite
                                : " " + remove_favorite;
                    }
                }
                if (isFavorite == 0) {
                    toastr.success(label_removed_from_favorite_successfully);
                } else {
                    toastr.success(label_marked_as_favorite_successfully);
                }
            } else {
                toastr.error(response.message);
            }
        },
        error: function () {
            // Handle errors if necessary
            toastr.error(label_err_try_again);
        },
    });
});
// Click event handler for the pinned icon
$(document).on("click", ".pinned-icon", function () {
    var icon = $(this);
    var entityId = icon.data("id"); // ID of the entity (e.g., project, task)
    var entityType = icon.data("type") || "projects"; // Default to 'projects' if no type is provided
    var isPinned = icon.attr("data-pinned");
    isPinned = isPinned == 1 ? 0 : 1;
    var requireReload =
        icon.data("require_reload") !== undefined
            ? icon.data("require_reload")
            : 1;
    var dataTitle = icon.data("bs-original-title");
    var temp = dataTitle !== undefined ? "data-bs-original-title" : "title";
    // Send an AJAX request to update the pinned status
    $.ajax({
        url: baseUrl + "/" + entityType + "/update-pinned/" + entityId,
        type: "PATCH",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            is_pinned: isPinned,
        },
        success: function (response) {
            if (response.error == false) {
                if (isPinned == 0) {
                    icon.removeClass("bxs-pin");
                    icon.addClass("bx-pin");
                    icon.attr(temp, label_click_pin); // Update the tooltip text
                    icon.find("i")
                        .removeClass("bxs-pin text-success")
                        .addClass("bx-pin text-muted");
                } else {
                    icon.removeClass("bx-pin");
                    icon.addClass("bxs-pin");
                    icon.attr(temp, label_click_unpin); // Update the tooltip text
                    icon.find("i")
                        .removeClass("bx-pin text-muted")
                        .addClass("bxs-pin text-success");
                }
                icon.attr("data-pinned", isPinned);
                var textNode = icon.contents().filter(function () {
                    return this.nodeType === 3 && this.nodeValue.trim() !== "";
                });
                if (textNode.length) {
                    if (!icon.hasClass("dropdown-item")) {
                        textNode[0].nodeValue =
                            isPinned == 0
                                ? " " + label_click_pin
                                : " " + label_click_unpin;
                    }
                }
                if (requireReload) {
                    // Show success message
                    toastr.success(response.message);
                    setTimeout(
                        function () {
                            location.reload();
                        },
                        parseFloat(toastTimeOut) * 1000,
                    );
                } else {
                    icon.attr("data-pinned", isPinned);
                    if (isPinned == 0) {
                        toastr.success(label_unpinned_successfully);
                    } else {
                        toastr.success(label_pinned_successfully);
                    }
                    // Check if 'data-table' attribute is provided, otherwise default to 'projects_table'
                    var tableId = icon.data("table") || entityType + "_table";
                    if ($("#" + tableId).length) {
                        // Check if the table exists
                        $("#" + tableId).bootstrapTable("refresh"); // Refresh the table
                    }
                }
            } else {
                toastr.error(response.message);
            }
        },
        error: function () {
            // Handle errors if necessary
            toastr.error(label_err_try_again);
        },
    });
});
$(document).on("click", ".duplicate", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var type = $(this).data("type");
    var reload = $(this).data("reload"); // Get the value of data-reload attribute
    if (typeof reload !== "undefined" && reload === true) {
        reload = true;
    } else {
        reload = false;
    }
    var tableID = $(this).data("table") || "table";
    $("#duplicateModal").modal("show"); // show the confirmation modal
    $("#duplicateModal").off("click", "#confirmDuplicate");
    if (type != "estimates-invoices" && type != "payslips") {
        $("#duplicateModal").find("#titleDiv").removeClass("d-none");
        var title = $(this).data("title");
        $("#duplicateModal").find("#updateTitle").val(title);
    } else {
        $("#duplicateModal").find("#titleDiv").addClass("d-none");
    }
    // Show or hide selection div based on data-type being 'workspaces'
    if (type === "workspaces") {
        $("#duplicateModal").find("#selectionDiv").removeClass("d-none"); // Show the selection div
    } else {
        $("#duplicateModal").find("#selectionDiv").addClass("d-none"); // Hide the selection div
    }
    $("#duplicateModal").on("click", "#confirmDuplicate", function (e) {
        e.preventDefault();
        var title = $("#duplicateModal").find("#updateTitle").val();
        const selectedOptions = $(".duplicate-option:checked")
            .map(function () {
                return $(this).val();
            })
            .get();
        $("#confirmDuplicate").html(label_please_wait).attr("disabled", true);
        $.ajax({
            url:
                baseUrl +
                "/" +
                type +
                "/duplicate/" +
                id +
                "?reload=" +
                reload +
                "&title=" +
                title +
                "&options=" +
                selectedOptions,
            type: "GET",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                $("#confirmDuplicate").html(label_yes).attr("disabled", false);
                $("#duplicateModal").modal("hide");
                if (response.error == false) {
                    if (reload) {
                        if (response.message) {
                            // Show success message
                            toastr.success(response.message);
                            setTimeout(
                                function () {
                                    location.reload();
                                },
                                parseFloat(toastTimeOut) * 1000,
                            );
                        } else {
                            location.reload();
                        }
                    } else {
                        toastr.success(response.message);
                        if (tableID) {
                            $("#" + tableID).bootstrapTable("refresh");
                        }
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (data) {
                $("#confirmDuplicate").html(label_yes).attr("disabled", false);
                $("#duplicateModal").modal("hide");
                toastr.error(label_something_went_wrong);
            },
        });
    });
});
$("#duplicateProjects").on("change", function () {
    const projectTasksCheckbox = $("#duplicateProjectTasks");
    if ($(this).is(":checked")) {
        projectTasksCheckbox.prop("disabled", false);
    } else {
        projectTasksCheckbox.prop("disabled", true).prop("checked", false);
    }
});
$("#deduction_type").on("change", function (e) {
    if ($("#deduction_type").val() == "amount") {
        $("#amount_div").removeClass("d-none");
        $("#percentage_div").addClass("d-none");
    } else if ($("#deduction_type").val() == "percentage") {
        $("#amount_div").addClass("d-none");
        $("#percentage_div").removeClass("d-none");
    } else {
        $("#amount_div").addClass("d-none");
        $("#percentage_div").addClass("d-none");
    }
});
$("#update_deduction_type").on("change", function (e) {
    if ($("#update_deduction_type").val() == "amount") {
        $("#update_amount_div").removeClass("d-none");
        $("#update_percentage_div").addClass("d-none");
    } else if ($("#update_deduction_type").val() == "percentage") {
        $("#update_amount_div").addClass("d-none");
        $("#update_percentage_div").removeClass("d-none");
    } else {
        $("#update_amount_div").addClass("d-none");
        $("#update_percentage_div").addClass("d-none");
    }
});
$("#tax_type").on("change", function (e) {
    if ($("#tax_type").val() == "amount") {
        $("#amount_div").removeClass("d-none");
        $("#percentage_div").addClass("d-none");
    } else if ($("#tax_type").val() == "percentage") {
        $("#amount_div").addClass("d-none");
        $("#percentage_div").removeClass("d-none");
    } else {
        $("#amount_div").addClass("d-none");
        $("#percentage_div").addClass("d-none");
    }
});
$("#update_tax_type").on("change", function (e) {
    if ($("#update_tax_type").val() == "amount") {
        $("#update_amount_div").removeClass("d-none");
        $("#update_percentage_div").addClass("d-none");
    } else if ($("#update_tax_type").val() == "percentage") {
        $("#update_amount_div").addClass("d-none");
        $("#update_percentage_div").removeClass("d-none");
    } else {
        $("#update_amount_div").addClass("d-none");
        $("#update_percentage_div").addClass("d-none");
    }
});
if (document.getElementById("system-update-dropzone")) {
    if (!$("#system-update").hasClass("dropzone")) {
        var systemDropzone = new Dropzone("#system-update-dropzone", {
            url: $("#system-update").attr("action"),
            paramName: "update_file",
            autoProcessQueue: false,
            parallelUploads: 1,
            maxFiles: 1,
            acceptedFiles: ".zip",
            timeout: 360000,
            autoDiscover: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), // Pass the CSRF token as a header
            },
            addRemoveLinks: true,
            dictRemoveFile: "x",
            dictMaxFilesExceeded: label_only_one_file_can_be_uploaded_at_a_time,
            dictResponseError: "Error",
            uploadMultiple: true,
            dictDefaultMessage:
                '<p><input type="button" value="' +
                label_select +
                '" class="btn btn-primary" /><br> ' +
                label_or +
                " <br> " +
                label_drag_and_drop_update_zip_file_here +
                "</p>",
        });
        systemDropzone.on("addedfile", function (file) {
            var i = 0;
            if (this.files.length) {
                var _i, _len;
                for (_i = 0, _len = this.files.length; _i < _len - 1; _i++) {
                    if (
                        this.files[_i].name === file.name &&
                        this.files[_i].size === file.size &&
                        this.files[_i].lastModifiedDate.toString() ===
                            file.lastModifiedDate.toString()
                    ) {
                        this.removeFile(file);
                        i++;
                    }
                }
            }
        });
        systemDropzone.on("error", function (file, response) {
            // Remove the file
            systemDropzone.removeFile(file);
            // Re-enable the submit button and reset its text
            $("#system_update_btn")
                .attr("disabled", false)
                .text(label_update_the_system);
            var errorMessage = label_err_try_again;
            if (typeof response === "string") {
                errorMessage = response; // Use the response text if it's a string
            } else if (response.message) {
                errorMessage = response.message; // Use response.message if it exists
            }
            toastr.error(errorMessage);
        });
        systemDropzone.on("success", function (file, response) {
            $("#system_update_btn")
                .attr("disabled", false)
                .text(label_update_the_system);
            if (response.error) {
                // Remove the file
                systemDropzone.removeFile(file);
                // Re-enable the submit button and reset its text
                // Show the error message
                toastr.error(response.message);
            } else {
                // Show success message
                toastr.success(response.message);
                setTimeout(
                    function () {
                        location.reload();
                    },
                    parseFloat(toastTimeOut) * 1000,
                );
            }
        });
        $("#system_update_btn").on("click", function (e) {
            e.preventDefault();
            var queuedFiles = systemDropzone.getQueuedFiles();
            if (queuedFiles.length > 0) {
                $("#system_update_btn")
                    .attr("disabled", true)
                    .text(label_please_wait);
                systemDropzone.processQueue();
            } else {
                toastr.error(label_no_files_chosen);
            }
        });
    }
}
if (document.getElementById("media-upload-dropzone")) {
    var is_error = false;
    var mediaDropzone = new Dropzone("#media-upload-dropzone", {
        url: $("#media-upload").attr("action"),
        paramName: "media_files",
        autoProcessQueue: false,
        timeout: 0,
        autoDiscover: false,
        maxFilesize: allowedMaxFilesize,
        maxFiles: maxFilesAllowed,
        parallelUploads: maxFilesAllowed,
        acceptedFiles: allowedFileTypes,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), // Pass the CSRF token as a header
        },
        addRemoveLinks: true,
        dictRemoveFile: "x",
        dictResponseError: "Error",
        uploadMultiple: true,
        dictDefaultMessage:
            '<p><input type="button" value="' +
            label_select +
            '" class="btn btn-primary" /><br> ' +
            label_or +
            " <br> " +
            label_drag_and_drop_files_here +
            " <br> " +
            label_allowed_max_upload_size +
            ": " +
            allowedMaxFilesizeFormatted +
            "<br> " +
            label_max_files_allowed +
            ": " +
            maxFilesAllowed +
            "</p>",
    });
    allowedFileTypes = allowedFileTypes.split(",");
    mediaDropzone.on("addedfile", function (file) {
        var removedFiles = 0;
        // Check if the number of files exceeds the maxFiles limit
        if (this.files.length > maxFilesAllowed) {
            this.removeFile(file); // Remove the extra file
            toastr.error(
                label_max_files_count_allowed.replace(
                    ":count",
                    maxFilesAllowed,
                ),
            );
            return; // Exit to prevent further processing
        }
        // Check if file type is allowed
        var fileExtension = getFileExtension(file.name);
        if (!allowedFileTypes.includes(fileExtension)) {
            mediaDropzone.removeFile(file);
            removedFiles++;
        }
        // Show a message if a file was removed
        if (removedFiles > 0) {
            toastr.error(label_file_type_not_allowed + ": " + file.name);
        }
    });
    mediaDropzone.on("error", function (file, response) {
        // console.log(response);
    });
    mediaDropzone.on("sending", function (file, xhr, formData) {
        var id = $("#media_type_id").val();
        formData.append("id", id);
    });
    mediaDropzone.on("queuecomplete", function () {
        $("#upload_media_btn").attr("disabled", false).text(label_upload);
        if (mediaDropzone.files.length > 0) {
            var lastFileResponse =
                mediaDropzone.files[mediaDropzone.files.length - 1].xhr
                    .responseText;
            var response = JSON.parse(lastFileResponse);
            if (!response.error) {
                if ($("#add_media_modal").length) {
                    $("#add_media_modal").modal("hide");
                }
                if ($("#project_media_table").length) {
                    $("#project_media_table").bootstrapTable("refresh");
                }
                if ($("#task_media_table").length) {
                    $("#task_media_table").bootstrapTable("refresh");
                }
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        }
        mediaDropzone.removeAllFiles();
    });
    $("#upload_media_btn").on("click", function (e) {
        e.preventDefault();
        if (mediaDropzone.getQueuedFiles().length > 0) {
            if (is_error == false) {
                $("#upload_media_btn")
                    .attr("disabled", true)
                    .text(label_please_wait);
                mediaDropzone.processQueue();
            }
        } else {
            toastr.error(label_no_files_chosen);
        }
    });
    // Clear Dropzone files when the modal is closed
    $("#add_media_modal").on("hide.bs.modal", function () {
        mediaDropzone.removeAllFiles();
        $("#upload_media_btn").attr("disabled", false).text(label_upload);
    });
}
if (document.getElementById("contract-dropzone")) {
    var contractDropzone = new Dropzone("#contract-dropzone", {
        url: $("#edit_contract_modal")
            .find(".form-submit-event")
            .attr("action"),
        paramName: "signed_pdf",
        autoProcessQueue: false,
        parallelUploads: 1,
        maxFilesize: 10, // Maximum file size in MB
        maxFiles: 1, // Only allow one file to be uploaded
        acceptedFiles: ".pdf", // Only accept PDF files
        timeout: 360000,
        autoDiscover: false,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), // Pass CSRF token as header
        },
        addRemoveLinks: true,
        dictRemoveFile: "x",
        dictMaxFilesExceeded: label_only_one_file_can_be_uploaded_at_a_time,
        dictResponseError: "Error",
        uploadMultiple: false, // Allow only one file upload at a time
        dictDefaultMessage:
            '<p><input type="button" value="' +
            label_select +
            '" class="btn btn-primary" /><br> ' +
            label_or +
            " <br> " +
            label_drag_and_drop_file_here +
            "</p>",
    });
}
if (document.getElementById("bulk-upload-dropzone")) {
    var bulkUploadDropzone = new Dropzone("#bulk-upload-dropzone", {
        url: $("#bulk-upload-dropzone").closest("form").attr("action"), // Uses the form's action URL
        paramName: "bulk_file", // The name of the file input field
        autoProcessQueue: false, // Don't auto-submit
        parallelUploads: 1, // Only upload one file at a time
        maxFiles: 1, // Allow only one file at a time
        acceptedFiles: ".csv,.xlsx,.xls", // Only accept CSV or Excel files
        timeout: 360000,
        autoDiscover: false,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), // Pass the CSRF token as a header
        },
        addRemoveLinks: true,
        dictRemoveFile: "x",
        dictMaxFilesExceeded: label_only_one_file_can_be_uploaded_at_a_time,
        dictResponseError: "Error",
        uploadMultiple: false, // Don't allow multiple files
        dictDefaultMessage:
            '<p><input type="button" value="' +
            label_select +
            '" class="btn btn-primary" /><br>' +
            label_or +
            "<br>" +
            label_drag_and_drop_file_here +
            "</p>",
    });
    // On added file
    bulkUploadDropzone.on("addedfile", function (file) {
        var i = 0;
        if (this.files.length) {
            var _i, _len;
            for (_i = 0, _len = this.files.length; _i < _len - 1; _i++) {
                if (
                    this.files[_i].name === file.name &&
                    this.files[_i].size === file.size &&
                    this.files[_i].lastModifiedDate.toString() ===
                        file.lastModifiedDate.toString()
                ) {
                    this.removeFile(file);
                    i++;
                }
            }
        }
    });
    // On error
    bulkUploadDropzone.on("error", function (file, response) {
        bulkUploadDropzone.removeFile(file);
        $("#submit_btn").attr("disabled", false).text(label_upload); // Re-enable the button
        $("#validation-errors").html("");
        if (
            response.validation_errors &&
            Object.keys(response.validation_errors).length > 0
        ) {
            toastr.error(label_please_correct_errors); // Show error message
            // Loop through the validation errors
            Object.values(response.validation_errors).forEach(function (error) {
                if (error.trim() !== "") {
                    // Ignore empty strings
                    $("#validation-errors").append("<p>" + error + "</p>");
                }
            });
        } else {
            if (response.message) {
                toastr.error(response.message); // Show error message
            } else {
                toastr.error(label_something_went_wrong); // Show error message
            }
        }
    });
    // On success
    bulkUploadDropzone.on("success", function (file, response) {
        $("#submit_btn").attr("disabled", false).text(label_upload); // Re-enable the button
        $("#validation-errors").html("");
        bulkUploadDropzone.removeFile(file); // Remove the file
        if (response.error) {
            toastr.error(response.message); // Show error message
        } else {
            toastr.success(response.message); // Show success message
        }
    });
    // On submit button click
    $("#submit_btn").on("click", function (e) {
        e.preventDefault();
        var queuedFiles = bulkUploadDropzone.getQueuedFiles();
        if (queuedFiles.length > 0) {
            $("#submit_btn").attr("disabled", true).text(label_please_wait); // Disable the button
            bulkUploadDropzone.processQueue(); // Start uploading
        } else {
            toastr.error(label_no_file_chosen); // Error message if no file is added
        }
    });
}
function getFileExtension(filename) {
    return "." + filename.split(".").pop().toLowerCase();
}
$(document).on("click", ".admin-login", function (e) {
    e.preventDefault();
    $("#email").val("admin@gmail.com");
    $("#password").val("123456");
});
$(document).on("click", ".member-login", function (e) {
    e.preventDefault();
    $("#email").val("member@gmail.com");
    $("#password").val("123456");
});
$(document).on("click", ".client-login", function (e) {
    e.preventDefault();
    $("#email").val("client@gmail.com");
    $("#password").val("123456");
});
// Row-wise Select/Deselect All
$(".row-permission-checkbox").change(function () {
    var module = $(this).data("module");
    var isChecked = $(this).prop("checked");
    $(`.permission-checkbox[data-module="${module}"]`).prop(
        "checked",
        isChecked,
    );
});
$("#selectAllColumnPermissions").change(function () {
    var isChecked = $(this).prop("checked");
    $(".permission-checkbox").prop("checked", isChecked);
    if (isChecked) {
        $(".row-permission-checkbox").prop("checked", true).trigger("change"); // Check all row permissions when select all is checked
    } else {
        $(".row-permission-checkbox").prop("checked", false).trigger("change"); // Uncheck all row permissions when select all is unchecked
    }
    checkAllPermissions(); // Check all permissions
});
// Select/Deselect All for Rows
$("#selectAllPermissions").change(function () {
    var isChecked = $(this).prop("checked");
    $(".row-permission-checkbox").prop("checked", isChecked).trigger("change");
});
// Function to check/uncheck all permissions for a module
function checkModulePermissions(module) {
    var allChecked = true;
    $('.permission-checkbox[data-module="' + module + '"]').each(function () {
        if (!$(this).prop("checked")) {
            allChecked = false;
        }
    });
    $("#selectRow" + module).prop("checked", allChecked);
}
// Function to check if all permissions are checked and select/deselect "Select all" checkbox
function checkAllPermissions() {
    var allPermissionsChecked = true;
    $(".permission-checkbox").each(function () {
        if (!$(this).prop("checked")) {
            allPermissionsChecked = false;
        }
    });
    $("#selectAllColumnPermissions").prop("checked", allPermissionsChecked);
}
// Event handler for individual permission checkboxes
$(".permission-checkbox").on("change", function () {
    var module = $(this).data("module");
    checkModulePermissions(module);
    checkAllPermissions();
});
// Event handler for "Select all" checkbox
$("#selectAllColumnPermissions").on("change", function () {
    var isChecked = $(this).prop("checked");
    $(".permission-checkbox").prop("checked", isChecked);
});
// Initial check for permissions on page load
$(".row-permission-checkbox").each(function () {
    var module = $(this).data("module");
    checkModulePermissions(module);
});
checkAllPermissions();
$(document).ready(function () {
    $(".fixed-table-toolbar").each(function () {
        var $toolbar = $(this);
        var $data_type = $toolbar
            .closest(".table-responsive")
            .find("#data_type");
        var $data_table = $toolbar
            .closest(".table-responsive")
            .find("#data_table");
        var $multi_select = $toolbar
            .closest(".table-responsive")
            .find("#multi_select");
        var $save_column_visibility = $toolbar
            .closest(".table-responsive")
            .find("#save_column_visibility");
        if ($data_type.length > 0) {
            var data_type = $data_type.val();
            var data_table = $data_table.val() || "table";
            var multi_select = $multi_select.length > 0 ? 1 : 0;
            var multi_select_value = $multi_select.val() || null;
            var data_reload =
                $toolbar
                    .closest(".table-responsive")
                    .find("#data_reload")
                    .val() || 0;
            var action_class =
                "action_delete_" +
                (["project-media", "task-media"].includes(data_type)
                    ? "media"
                    : data_type.replace("-", "_"));
            var showDelete =
                data_type !== "report" &&
                data_table !== "birthdays_table" &&
                data_table !== "wa_table"
                    ? 1
                    : 0;
            var $actionBar = $toolbar.find(".custom-action-bar");
            if ($actionBar.length === 0) {
                $actionBar = $(
                    '<div class="custom-action-bar bs-bars float-none float-md-start d-flex flex-column flex-md-row gap-2 mb-3"></div>',
                );
                $toolbar.prepend($actionBar);
            } else {
                $actionBar.empty();
            }

            // Create the "Delete selected" button
            if (showDelete) {
                var $deleteButton = $(
                    '<button type="button" class="btn btn-outline-danger delete-selected ' +
                        action_class +
                        '" data-type="' +
                        data_type +
                        '" data-table="' +
                        data_table +
                        '" data-reload="' +
                        data_reload +
                        '">' +
                        '<i class="bx bx-trash me-1"></i>' +
                        label_delete_selected +
                        "</button>",
                );
                $actionBar.append($deleteButton);
            }
            if (multi_select) {
                // Use multi_select value for clear button class if available, else use data_type
                var clearButtonClass = multi_select_value
                    ? "clear-" + multi_select_value + "-filters"
                    : "clear-" + data_type + "-filters";
                // Create the "Clear Filters" button
                var $clearFiltersButton = $(
                    '<button type="button" class="btn btn-secondary w-100 ' +
                        clearButtonClass +
                        '">' +
                        '<i class="bx bx-refresh me-1"></i>' +
                        label_clear_filters +
                        "</button>",
                );

                // Try to find the filter row by checking parents from closest to furthest
                var $filterRow = $();
                $toolbar
                    .parents(".card-body, .card, .mt-2, .container-fluid")
                    .each(function () {
                        var $row = $(this)
                            .find(".row")
                            .filter(function () {
                                return (
                                    $(this).find('select[multiple="multiple"]')
                                        .length > 0 ||
                                    $(this).hasClass("tk-filter-row")
                                );
                            })
                            .last();
                        if ($row.length > 0) {
                            $filterRow = $row;
                            return false; // Break the loop once we find the filter row
                        }
                    });

                if ($filterRow.length > 0) {
                    // Remove old clear filter button if already exists
                    $filterRow.find(".clear-filters-container").remove();
                    var $filterCol = $(
                        '<div class="col-md-auto d-flex align-items-end clear-filters-container"></div>',
                    );
                    $filterCol.append($clearFiltersButton);
                    $filterRow.append($filterCol);
                } else {
                    // Prevent duplicate append in action bar
                    $actionBar.find("." + clearButtonClass).remove();
                    $actionBar.append($clearFiltersButton);
                }
            }
            if ($save_column_visibility.length > 0) {
                // Extract data-type and data-table from $save_column_visibility if they exist
                var saveType =
                    $save_column_visibility.data("type") || data_type;
                var saveTable =
                    $save_column_visibility.data("table") || data_table;
                var $savePreferencesButton = $(
                    '<button type="button" class="btn btn-outline-primary save-column-visibility" data-type="' +
                        saveType +
                        '" data-table="' +
                        saveTable +
                        '">' +
                        '<i class="bx bx-save me-1"></i>' +
                        label_save_column_visibility +
                        "</button>",
                );
                $actionBar.append($savePreferencesButton);
            }
        }
    });
});
$("#media_storage_type").on("change", function (e) {
    if ($("#media_storage_type").val() == "s3") {
        $(".aws-s3-fields").removeClass("d-none");
    } else {
        $(".aws-s3-fields").addClass("d-none");
    }
});
$(document).on("click", ".edit-milestone", function () {
    var id = $(this).data("id");
    $.ajax({
        url: baseUrl + "/projects/get-milestone/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            var formattedStartDate = response.ms.start_date
                ? moment(response.ms.start_date).format(js_date_format)
                : "";
            var formattedEndDate = response.ms.end_date
                ? moment(response.ms.end_date).format(js_date_format)
                : "";
            $("#milestone_id").val(response.ms.id);
            $("#milestone_title").val(response.ms.title);
            if (formattedStartDate) {
                $("#update_milestone_start_date").val(formattedStartDate);
            }
            if (formattedEndDate) {
                $("#update_milestone_end_date").val(formattedEndDate);
            }
            $("#milestone_status").val(response.ms.status);
            $("#milestone_cost").val(response.ms.cost);
            var description =
                response.ms.description !== null ? response.ms.description : "";
            $("#edit_milestone_modal")
                .find("#milestone_description")
                .val(description);
            $("#milestone_progress").val(response.ms.progress);
            $(".milestone-progress").text(response.ms.progress + "%");
        },
    });
});
$(document).on("click", "#mark-all-notifications-as-read", function (e) {
    e.preventDefault();
    $("#mark_all_notifications_as_read_modal").modal("show"); // show the confirmation modal
    $("#mark_all_notifications_as_read_modal").on(
        "click",
        "#confirmMarkAllAsRead",
        function () {
            $("#confirmMarkAllAsRead")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                url: baseUrl + "/notifications/mark-all-as-read",
                type: "PUT",
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
                },
                success: function (response) {
                    location.reload();
                    // $('#confirmMarkAllAsRead').html(label_yes).attr('disabled', false);
                },
            });
        },
    );
});

// Global Table Style Normalizer
// Applies the design system badge styles (bg-label-*) and uncolored action icons to ALL tables automatically
$(document).on("post-body.bs.table", 'table[data-toggle="table"]', function () {
    var $table = $(this);

    // 1. Convert all standard bg-* badges to bg-label-*
    $table.find(".badge").each(function () {
        var $badge = $(this);
        var classes = $badge.attr("class").split(" ");
        var originalBgClass = "";
        var hasLabelClass = false;

        for (var i = 0; i < classes.length; i++) {
            if (classes[i].startsWith("bg-label-")) {
                hasLabelClass = true;
                break;
            }
            if (
                classes[i].startsWith("bg-") &&
                classes[i] !== "bg-transparent" &&
                classes[i] !== "bg-white"
            ) {
                originalBgClass = classes[i];
            }
        }

        if (!hasLabelClass && originalBgClass) {
            var color = originalBgClass.substring(3); // extracts 'primary', 'danger', etc.
            $badge.removeClass(originalBgClass).addClass("bg-label-" + color);
        }
    });

    // 2. Normalize Action Icons (remove hardcoded colors like text-danger, text-primary from action columns)
    $table.find("a i.bx, button i.bx").each(function () {
        $(this).removeClass(
            "text-danger text-primary text-success text-warning text-info text-dark text-secondary text-muted",
        );
    });
});
$(document).on("click", ".update-notification-status", function (e) {
    var notificationId = $(this).data("id");
    var needConfirm = $(this).data("needconfirm") || false;
    if (needConfirm) {
        // Show the confirmation modal
        $("#update_notification_status_modal").modal("show");
        // Attach click event handler to the confirmation button
        $("#update_notification_status_modal").off(
            "click",
            "#confirmNotificationStatus",
        );
        $("#update_notification_status_modal").on(
            "click",
            "#confirmNotificationStatus",
            function () {
                $("#confirmNotificationStatus")
                    .html(label_please_wait)
                    .attr("disabled", true);
                performUpdate(notificationId, needConfirm);
            },
        );
    } else {
        // If confirmation is not needed, directly perform the update and handle response
        performUpdate(notificationId);
    }
});
function performUpdate(notificationId, needConfirm = "") {
    $.ajax({
        url: baseUrl + "/notifications/update-status",
        type: "PUT",
        data: { id: notificationId, needConfirm: needConfirm },
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        success: function (response) {
            if (needConfirm) {
                $("#confirmNotificationStatus")
                    .html(label_yes)
                    .attr("disabled", false);
                if (response.error == false) {
                    toastr.success(response.message);
                    $("#table").bootstrapTable("refresh");
                    // Redirect after successful update
                } else {
                    toastr.error(response.message);
                }
                $("#update_notification_status_modal").modal("hide");
            } else {
                var redirectUrl = determineRedirectUrl(
                    response.notification.type,
                    response.notification.type_id,
                    response.notification.action,
                );
                window.location.href = redirectUrl;
            }
        },
    });
}
function determineRedirectUrl(type, typeId, action) {
    var redirectUrl = "";
    switch (type) {
        case "project":
            redirectUrl = baseUrl + "/projects/information/" + typeId;
            break;
        case "task":
            redirectUrl = baseUrl + "/tasks/information/" + typeId;
            break;
        case "project_comment_mention":
            redirectUrl =
                baseUrl +
                "/projects/information/" +
                typeId +
                "#navs-top-discussions";
            break;
        case "task_comment_mention":
            redirectUrl =
                baseUrl +
                "/tasks/information/" +
                typeId +
                "#navs-top-discussions";
            break;
        case "workspace":
            redirectUrl = baseUrl + "/workspaces";
            break;
        case "leave_request":
            if (action === "team_member_on_leave_alert") {
                redirectUrl = baseUrl + "/notifications";
            } else {
                redirectUrl = baseUrl + "/leave-requests";
            }
            break;
        case "meeting":
            redirectUrl = baseUrl + "/meetings";
            break;
        case "todo_reminder":
            redirectUrl = baseUrl + "/todos";
            break;
        default:
            redirectUrl = baseUrl + "/";
    }
    return redirectUrl;
}
if (
    typeof manage_notifications !== "undefined" &&
    manage_notifications == "true"
) {
    function updateUnreadNotifications() {
        // Make an AJAX request to fetch the count and HTML of unread notifications
        $.ajax({
            url: baseUrl + "/notifications/get-unread-notifications",
            type: "GET",
            dataType: "json",
            success: function (data) {
                const unreadNotificationsCount = data.count;
                const unreadNotificationsHtml = data.html;
                $("#unreadNotificationsCount").text(unreadNotificationsCount);
                $("#unreadNotificationsCount").toggleClass(
                    "d-none",
                    unreadNotificationsCount === 0,
                );
                // Update the notifications list with the new HTML
                $("#unreadNotificationsContainer").html(
                    unreadNotificationsHtml,
                );
            },
            error: function (xhr, status, error) {
                console.error("Error fetching unread notifications:", error);
            },
        });
    }
    // Call the updateUnreadNotifications function initially
    // updateUnreadNotifications();
    // Update the unread notifications every 30 seconds
    // setInterval(updateUnreadNotifications, 30000);
}
$(
    "textarea#email_verify_email,textarea#email_account_creation,textarea#email_forgot_password,textarea#email_project_assignment,textarea#email_task_assignment,textarea#email_workspace_assignment,textarea#email_meeting_assignment,textarea#email_leave_request_creation,textarea#email_leave_request_status_updation,textarea#email_project_status_updation,textarea#email_task_status_updation,textarea#email_team_member_on_leave_alert,textarea#email_birthday_wish,#email_work_anniversary_wish,textarea#email_task_reminder,textarea#email_recurring_task,textarea#template-body,textarea#editBody,textarea#email_interview_assignment,textarea#email_interview_status_update",
).tinymce({
    height: 821,
    menubar: true,
    plugins: [
        "link",
        "a11ychecker",
        "advlist",
        "advcode",
        "advtable",
        "autolink",
        "checklist",
        "export",
        "lists",
        "link",
        "image",
        "charmap",
        "preview",
        "anchor",
        "searchreplace",
        "visualblocks",
        "powerpaste",
        "fullscreen",
        "formatpainter",
        "insertdatetime",
        "media",
        "table",
        "help",
        "wordcount",
        "emoticons",
        "code",
    ],
    toolbar: false,
    // toolbar: 'link | undo redo | a11ycheck casechange blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | removeformat | code blockquote emoticons table help'
});
// Handle click event on toolbar items
$(".tox-tbtn").click(function () {
    // Get the current editor instance
    var editor = tinyMCE.activeEditor;
    // Close any open toolbar dropdowns
    tinymce.ui.Factory.each(function (ctrl) {
        if (ctrl.type === "toolbarbutton" && ctrl.settings.toolbar) {
            if (ctrl !== this && ctrl.settings.toolbar === "toolbox") {
                ctrl.panel.hide();
            }
        }
    }, editor);
    // Execute the action associated with the clicked toolbar item
    editor.execCommand("mceInsertContent", false, "Clicked!");
});
$(document).on("click", ".restore-default", function (e) {
    e.preventDefault();
    var form = $(this).closest("form");
    var type = form.find('input[name="type"]').val();
    var name = form.find('input[name="name"]').val();
    var textarea = type + "_" + name;
    $("#restore_default_modal").modal("show"); // show the confirmation modal
    $("#restore_default_modal").off("click", "#confirmRestoreDefault");
    $("#restore_default_modal").on(
        "click",
        "#confirmRestoreDefault",
        function () {
            $("#confirmRestoreDefault")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                url: baseUrl + "/settings/get-default-template",
                type: "POST",
                data: { type: type, name: name },
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
                },
                dataType: "json",
                success: function (response) {
                    $("#confirmRestoreDefault")
                        .html(label_yes)
                        .attr("disabled", false);
                    $("#restore_default_modal").modal("hide");
                    if (response.error == false) {
                        tinymce.get(textarea).setContent(response.content);
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
            });
        },
    );
});
$(document).on("click", ".sms-restore-default", function (e) {
    e.preventDefault();
    var form = $(this).closest("form");
    var type = form.find('input[name="type"]').val();
    var name = form.find('input[name="name"]').val();
    var textarea = type + "_" + name;
    $("#restore_default_modal").modal("show"); // show the confirmation modal
    $("#restore_default_modal").off("click", "#confirmRestoreDefault");
    $("#restore_default_modal").on(
        "click",
        "#confirmRestoreDefault",
        function () {
            $("#confirmRestoreDefault")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                url: baseUrl + "/settings/get-default-template",
                type: "POST",
                data: { type: type, name: name },
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
                },
                dataType: "json",
                success: function (response) {
                    $("#confirmRestoreDefault")
                        .html(label_yes)
                        .attr("disabled", false);
                    $("#restore_default_modal").modal("hide");
                    if (response.error == false) {
                        $("#" + textarea).val(response.content);
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
            });
        },
    );
});
$(document).ready(function () {
    // Shared function to calculate total days
    function calculateTotalDays(
        startDateSelector,
        endDateSelector,
        totalDaysSelector,
    ) {
        var start_date = moment($(startDateSelector).val(), js_date_format);
        var end_date = moment($(endDateSelector).val(), js_date_format);
        if (start_date.isValid() && end_date.isValid()) {
            var total_days = end_date.diff(start_date, "days") + 1;
            $(totalDaysSelector).val(total_days);
        }
    }
    // Function to bind event listeners for date inputs
    function bindDateChangeListeners(
        startDateSelector,
        endDateSelector,
        totalDaysSelector,
    ) {
        $(startDateSelector + ", " + endDateSelector)
            .off("change")
            .on("change", function () {
                calculateTotalDays(
                    startDateSelector,
                    endDateSelector,
                    totalDaysSelector,
                );
            });
        $(startDateSelector).on("apply.daterangepicker", function () {
            calculateTotalDays(
                startDateSelector,
                endDateSelector,
                totalDaysSelector,
            );
        });
        $(endDateSelector).on("apply.daterangepicker", function () {
            calculateTotalDays(
                startDateSelector,
                endDateSelector,
                totalDaysSelector,
            );
        });
    }
    // Initial binding for create modal
    if ($("#total_days").length) {
        bindDateChangeListeners("#start_date", "#lr_end_date", "#total_days");
    }
    // Initial binding for update modal
    if ($("#update_total_days").length) {
        bindDateChangeListeners(
            "#update_start_date",
            "#update_end_date",
            "#update_total_days",
        );
    }
    // Reset form logic for both modal and offcanvas
    function resetModalForm(container) {
        var containerId = $(container).attr("id");
        var $form = $(container).find("form");
        $form.trigger("reset");
        if ($form.find("#total_days").length) {
            bindDateChangeListeners(
                "#start_date",
                "#lr_end_date",
                "#total_days",
            );
        }
        if ($form.find("#update_total_days").length) {
            bindDateChangeListeners(
                "#update_start_date",
                "#update_end_date",
                "#update_total_days",
            );
        }
        $form.find(".error-message").html("");
        var partialLeaveCheckbox = $("#partialLeave");
        if (partialLeaveCheckbox.length) {
            partialLeaveCheckbox.trigger("change");
        }
        var leaveVisibleToAllCheckbox = $form.find(".leaveVisibleToAll");
        if (leaveVisibleToAllCheckbox.length) {
            leaveVisibleToAllCheckbox.trigger("change");
        }
        var defaultColor =
            containerId == "create_note_modal" ||
            containerId == "edit_note_modal"
                ? "success"
                : "primary";
        var colorSelect = $form.find('select[name="color"]');
        if (colorSelect.length) {
            var classes = colorSelect.attr("class").split(" ");
            var currentColorClass = classes.find((c) =>
                c.startsWith("select-"),
            );
            colorSelect
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + defaultColor);
        }
        var selectPriority = $form.find('select[name="priority_id"]');
        if (selectPriority.length) {
            var classes = selectPriority.attr("class").split(" ");
            var currentClass = classes.find((c) => c.startsWith("bg-label"));
            selectPriority
                .removeClass(currentClass)
                .addClass("bg-label-secondary");
        }
        $form
            .find(
                ".js-example-basic-multiple, .users_select, .clients_select, .projects_select, .contract_types_select, .invoices_select",
            )
            .val(null)
            .trigger("change");
        $("#create_task_offcanvas, #edit_task_offcanvas")
            .find('select[name="user_id[]"]')
            .val(null)
            .trigger("change");
        if ($('.selectTaskProject[name="project"]').length) {
            $form
                .find($('.selectTaskProject[name="project"]'))
                .trigger("change");
        }
        if ($('.statusDropdown[name="status_id"]').length) {
            var $s = $form.find($('.statusDropdown[name="status_id"]'));
            if ($s.length && $s[0].tomselect) {
                $s[0].tomselect.sync();
            } else {
                $s.trigger("change");
            }
        }
        if ($('.priorityDropdown[name="priority_id"]').length) {
            var $p = $form.find($('.priorityDropdown[name="priority_id"]'));
            if ($p.length && $p[0].tomselect) {
                $p[0].tomselect.sync();
            } else {
                $p.trigger("change");
            }
        }
        $(
            "#users_associated_with_project, #task_update_users_associated_with_project",
        ).text("");
        $(container)
            .find('input[type="checkbox"]')
            .each(function () {
                $(this).prop("checked", false).trigger("change");
            });
        if (Dropzone.instances.length > 0) {
            Dropzone.instances.forEach(function (dz) {
                dz.removeAllFiles(true);
            });
        }
        resetDateFields($form);
    }
    // Reset when modal is closed
    $(".modal").on("hidden.bs.modal", function () {
        resetModalForm(this);
    });
    // ✅ Reset when offcanvas is closed
    $(".offcanvas").on("hidden.bs.offcanvas", function () {
        resetModalForm(this);
    });
});
$(document).ready(function () {
    // Listen for changes on the project select element within the modal
    $('.selectTaskProject[name="project"]').on("change", function (e) {
        var projectId = $(this).val();
        var currentModal = $(this).closest(".offcanvas"); // Adjust the selector to match your modal structure
        var usersSelect = currentModal.find('select[name="user_id[]"]');
        var modalId = currentModal.attr("id");
        if (projectId) {
            $.ajax({
                url: baseUrl + "/projects/get/" + projectId,
                type: "GET",
                success: function (response) {
                    currentModal
                        .find("#users_associated_with_project")
                        .html(
                            "(" +
                                label_users_associated_with_project +
                                " <strong>" +
                                response.project.title +
                                "</strong>)",
                        );
                    if (usersSelect.length && usersSelect[0].tomselect) {
                        var ts = usersSelect[0].tomselect;
                        ts.clear(true);
                        ts.clearOptions();
                        if (response.users && response.users.length > 0) {
                            response.users.forEach(function (user) {
                                ts.addOption({
                                    id: user.id,
                                    text: user.first_name + " " + user.last_name
                                });
                            });
                            if (response.project.task_accessibility == "project_users") {
                                var selectedIds = response.users.map(function (user) { return user.id; });
                                ts.setValue(selectedIds, true);
                            } else {
                                if (guard != "client" && modalId == "create_task_offcanvas") {
                                    ts.setValue([authUserId], true);
                                }
                            }
                        } else {
                            if (guard != "client" && modalId == "create_task_offcanvas") {
                                ts.setValue([authUserId], true);
                            }
                        }
                    } else {
                        usersSelect.empty(); // Clear existing options
                        if (response.users && response.users.length > 0) {
                            // Iterate through users and append options
                            response.users.forEach(function (user) {
                                var userOption = new Option(
                                    user.first_name + " " + user.last_name,
                                    user.id,
                                    false,
                                    false,
                                ); // Unselected initially
                                usersSelect.append(userOption);
                            });
                            // Set task users or default to authUserId based on task accessibility
                            if (
                                response.project.task_accessibility ==
                                "project_users"
                            ) {
                                var taskUsers = response.users.map(
                                    (user) => user.id,
                                );
                                usersSelect.val(taskUsers);
                            } else {
                                if (
                                    guard != "client" &&
                                    modalId == "create_task_offcanvas"
                                ) {
                                    usersSelect.val(authUserId);
                                }
                            }
                        } else {
                            // Handle case when no users are returned
                            if (
                                guard != "client" &&
                                modalId == "create_task_offcanvas"
                            ) {
                                usersSelect.val(authUserId);
                            }
                        }
                        usersSelect.trigger("change");
                    }
                },
                error: function (xhr, status, error) {
                    console.error(error);
                },
            });
        }
    });
});
/**
 * Handles the click event on elements with class 'edit-task' to open and populate an edit task form
 * in either a modal or offcanvas, fetching task data via AJAX and initializing form fields.
 *
 * @param {number} taskId - The ID of the task to edit.
 * @param {boolean} isOffcanvas - Whether to use offcanvas (true) or modal (false).
 * @param {string} baseUrl - The base URL for AJAX requests.
 * @param {string} js_date_format - The date format for Moment.js.
 * @returns {void}
 */
function editTask(taskId, isOffcanvas = true, baseUrl, js_date_format) {
    const overlayId = isOffcanvas ? "#edit_task_offcanvas" : "#edit_task_modal";
    const overlayType = isOffcanvas ? "offcanvas" : "modal";
    const $overlay = $(overlayId);

    // Open the overlay
    if (isOffcanvas) {
        $overlay.offcanvas("show");
    } else {
        $overlay.modal("show");
    }
    $.ajax({
        url: `/tasks/get/${taskId}`,
        type: "GET",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        dataType: "json",
        success: function (response) {
            if (!$overlay.length) {
                console.warn(`${overlayId} not found in DOM`);
                return;
            }

            // Format dates
            const formattedStartDate = response.task.start_date
                ? moment(response.task.start_date).format(js_date_format)
                : "";
            const formattedEndDate = response.task.due_date
                ? moment(response.task.due_date).format(js_date_format)
                : "";

            // Populate form fields
            $overlay
                .find("#task_update_users_associated_with_project")
                .html(
                    `(${label_users_associated_with_project} <strong>${response.project.title}</strong>)`,
                );
            $overlay.find("#id").val(response.task.id);
            $overlay.find("#title").val(response.task.title);
            $overlay
                .find("#task_status_id")
                .val(response.task.status_id)
                .trigger("change");
            $overlay
                .find("#priority_id")
                .val(response.task.priority_id)
                .trigger("change");
            $overlay.find("#update_start_date").val(formattedStartDate);
            $overlay.find("#update_end_date").val(formattedEndDate);
            $overlay.find("#update_project_title").val(response.project.title);
            $overlay
                .find("#task_description")
                .val(response.task.description || "");
            $overlay.find("#taskNote").val(response.task.note);
            $overlay
                .find("#edit_billing_type")
                .val(response.task.billing_type)
                .trigger("change");
            $overlay
                .find("#edit_completion_percentage")
                .val(response.task.completion_percentage)
                .trigger("change");
            $overlay
                .find("#updateClientCanDiscussTask")
                .prop("checked", response.task.client_can_discuss === 1);

            // Initialize DateRangePicker
            initializeDateRangePicker(
                $overlay.find("#update_start_date, #update_end_date"),
            );

            // Initialize task list Tom Select
            const editTaskList = $overlay.find("#edit_task_list");
            if (editTaskList.length) {
                if (editTaskList[0].tomselect) {
                    editTaskList[0].tomselect.destroy();
                }

                new TomSelect(editTaskList[0], {
                    valueField: "id",
                    labelField: "text",
                    searchField: "text",
                    placeholder: "Select a task list",
                    plugins: ["clear_button"],
                    preload: true,
                    load: function (query, callback) {
                        fetch(
                            `${baseUrl}/task-lists/search?search=${encodeURIComponent(query)}&project_id=${response.project.id}`,
                        )
                            .then((res) => res.json())
                            .then((data) => {
                                callback(
                                    data.map((item) => ({
                                        id: item.id,
                                        text: item.name,
                                    })),
                                );
                            })
                            .catch(() => {
                                callback();
                            });
                    },
                });

                // Prefill task list if it exists
                if (response.task.task_list_id) {
                    $.ajax({
                        url: `${baseUrl}/task-lists/search`,
                        data: { id: response.task.task_list_id },
                        dataType: "json",
                        success: function (data) {
                            const taskList = data.find(
                                (item) =>
                                    item.id === response.task.task_list_id,
                            );
                            if (taskList && editTaskList[0].tomselect) {
                                editTaskList[0].tomselect.addOption({
                                    id: taskList.id,
                                    text: taskList.name,
                                });
                                editTaskList[0].tomselect.setValue(
                                    taskList.id,
                                    true,
                                );
                            }
                        },
                    });
                } else {
                    editTaskList[0].tomselect.clear(true);
                }
            }

            // Populate users multi-select
            const usersSelect = $overlay.find('select[name="user_id[]"]');

            if (usersSelect.length && usersSelect[0].tomselect) {
                usersSelect[0].tomselect.clear(true);
                usersSelect[0].tomselect.clearOptions();
                if (response.project?.users?.length > 0) {
                    response.project.users.forEach((user) => {
                        usersSelect[0].tomselect.addOption({
                            id: user.id,
                            text: `${user.first_name} ${user.last_name}`,
                        });
                    });
                    const selectedTaskUsers =
                        response.task?.users?.length > 0
                            ? response.task.users.map((user) => user.id)
                            : [];
                    usersSelect[0].tomselect.setValue(selectedTaskUsers, true);
                    console.log(
                        "Users associated with the project:",
                        response.project.users,
                    );
                } else {
                    console.log("No users associated with the project");
                }
            } else {
                usersSelect.empty();
                if (response.project?.users?.length > 0) {
                    response.project.users.forEach((user) => {
                        const userOption = new Option(
                            `${user.first_name} ${user.last_name}`,
                            user.id,
                            false,
                            false,
                        );
                        usersSelect.append(userOption);
                    });
                    const selectedTaskUsers =
                        response.task?.users?.length > 0
                            ? response.task.users.map((user) => user.id)
                            : [];
                    usersSelect.val(selectedTaskUsers).trigger("change");
                    console.log(
                        "Users associated with the project:",
                        response.project.users,
                    );
                } else {
                    console.log("No users associated with the project");
                    usersSelect.val(null).trigger("change");
                }
            }

            // Handle recurring task settings
            if (response.task.recurring_task) {
                $overlay
                    .find("#edit-recurring-task-switch")
                    .prop("checked", true);
                $overlay
                    .find("#edit-recurring-task-settings")
                    .removeClass("d-none");
                $overlay
                    .find("#edit-recurrence-frequency")
                    .val(response.task.recurring_task.frequency)
                    .trigger("change");
                switch (response.task.recurring_task.frequency) {
                    case "weekly":
                        $overlay
                            .find("#edit-recurrence-day-of-week")
                            .val(
                                response.task.recurring_task.day_of_week || "",
                            );
                        break;
                    case "monthly":
                    case "yearly":
                        $overlay
                            .find("#edit-recurrence-day-of-month")
                            .val(
                                response.task.recurring_task.day_of_month || "",
                            );
                        break;
                    case "yearly":
                        $overlay
                            .find("#edit-recurrence-month-of-year")
                            .val(
                                response.task.recurring_task.month_of_year ||
                                    "",
                            );
                        break;
                }
                $overlay
                    .find("#edit-recurrence-starts-from")
                    .val(
                        response.task.recurring_task.starts_from
                            ? moment(
                                  response.task.recurring_task.starts_from,
                              ).format("YYYY-MM-DD")
                            : "",
                    );
                $overlay
                    .find("#edit-recurrence-occurrences")
                    .val(
                        response.task.recurring_task.number_of_occurrences ||
                            "",
                    );
            } else {
                $overlay
                    .find("#edit-recurring-task-switch")
                    .prop("checked", false);
                $overlay
                    .find("#edit-recurring-task-settings")
                    .addClass("d-none");
            }

            // Handle reminder settings
            if (response.task?.reminders?.length > 0) {
                const reminder = response.task.reminders[0];
                $overlay
                    .find("#edit-reminder-switch")
                    .prop("checked", reminder.is_active === 1);
                $overlay
                    .find("#edit-reminder-settings")
                    .toggleClass("d-none", reminder.is_active !== 1);
                $overlay
                    .find("#edit-frequency-type")
                    .val(reminder.frequency_type)
                    .trigger("change");
                $overlay
                    .find("#edit-day-of-week-group")
                    .toggleClass(
                        "d-none",
                        reminder.frequency_type !== "weekly",
                    );
                $overlay
                    .find("#edit-day-of-month-group")
                    .toggleClass(
                        "d-none",
                        reminder.frequency_type !== "monthly",
                    );
                $overlay
                    .find("#edit-day-of-week")
                    .val(reminder.day_of_week || "");
                $overlay
                    .find("#edit-day-of-month")
                    .val(reminder.day_of_month || "");
                $overlay
                    .find("#edit-time-of-day")
                    .val(reminder.time_of_day?.slice(0, 5) || "");
            } else {
                $overlay.find("#edit-reminder-switch").prop("checked", false);
                $overlay.find("#edit-reminder-settings").addClass("d-none");
            }

            // Handle custom fields
            if (response.task.formatted_custom_fields) {
                setTimeout(() => {
                    $.each(
                        response.task.formatted_custom_fields,
                        (fieldId, field) => {
                            const fieldName = `custom_fields[${field.field_id}]`;
                            const fieldSelector = `#edit_cf_${field.field_id}`;
                            const fieldType = field.field_type.toLowerCase();
                            switch (fieldType) {
                                case "checkbox":
                                    const values = field.value
                                        ? JSON.parse(field.value)
                                        : [];
                                    $(`input[name="${fieldName}[]"]`).each(
                                        function () {
                                            $(this).prop(
                                                "checked",
                                                values.includes($(this).val()),
                                            );
                                        },
                                    );
                                    break;
                                case "radio":
                                    $(`input[name="${fieldName}"]`).each(
                                        function () {
                                            $(this).prop(
                                                "checked",
                                                $(this).val() === field.value,
                                            );
                                        },
                                    );
                                    break;
                                case "select":
                                    $(fieldSelector)
                                        .val(field.value)
                                        .trigger("change");
                                    break;
                                case "textarea":
                                case "text":
                                case "password":
                                case "number":
                                    $(fieldSelector).val(field.value);
                                    break;
                                case "date":
                                    const formattedDate = field.value
                                        ? moment(field.value).format(
                                              js_date_format,
                                          )
                                        : "";
                                    $(fieldSelector).val(formattedDate);
                                    if (
                                        $(fieldSelector).data("daterangepicker")
                                    ) {
                                        $(fieldSelector)
                                            .data("daterangepicker")
                                            .remove();
                                    }
                                    $(fieldSelector)
                                        .daterangepicker({
                                            singleDatePicker: true,
                                            showDropdowns: true,
                                            autoUpdateInput: true,
                                            locale: {
                                                cancelLabel: "Clear",
                                                format: js_date_format,
                                            },
                                            startDate:
                                                formattedDate || moment(),
                                        })
                                        .on(
                                            "cancel.daterangepicker",
                                            function () {
                                                $(this).val("");
                                            },
                                        );
                                    break;
                            }
                        },
                    );
                }, 500);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            toastr.error("Failed to load task data");
        },
    });
}

$(document).on("click", ".edit-task", function () {
    editTask($(this).data("id"), true, baseUrl, js_date_format);
});
/**
 * Handles the click event on elements with class 'edit-project' to open and populate an edit project form
 * in either a modal or offcanvas, fetching project data via AJAX and initializing form fields.
 *
 * @param {Event} e - The click event.
 * @listens click - Binds to the 'click' event of elements with class 'edit-project'.
 * @returns {void}
 */
function editProject(projectId, isOffcanvas = true, baseUrl, js_date_format) {
    const overlayId = isOffcanvas
        ? "#edit_project_offcanvas"
        : "#edit_project_modal";
    const overlayType = isOffcanvas ? "offcanvas" : "modal";
    // Open the overlay
    const $overlay = $(overlayId);
    if (isOffcanvas) {
        $overlay.offcanvas("show");
    } else {
        $overlay.modal("show");
    }
    $.ajax({
        url: `${baseUrl}/projects/get/${projectId}`,
        type: "GET",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        dataType: "json",
        success: function (response) {
            if (!$overlay.length) {
                // console.warn(`${overlayId} not found in DOM`);
                return;
            }
            // Format dates
            const formattedStartDate = response.project.start_date
                ? moment(response.project.start_date).format(js_date_format)
                : "";
            const formattedEndDate = response.project.end_date
                ? moment(response.project.end_date).format(js_date_format)
                : "";
            // Populate form fields
            $overlay.find("#project_id").val(response.project.id);
            $overlay.find("#project_title").val(response.project.title);
            $overlay
                .find("#project_status_id")
                .val(response.project.status_id)
                .trigger("change");
            $overlay
                .find("#project_priority_id")
                .val(response.project.priority_id)
                .trigger("change");
            $overlay.find("#project_budget").val(response.project.budget);
            $overlay.find("#update_start_date").val(formattedStartDate);
            $overlay.find("#update_end_date").val(formattedEndDate);
            $overlay
                .find("#task_accessibility")
                .val(response.project.task_accessibility);
            $overlay.find("#project_note").val(response.project.note);
            $overlay
                .find("#project_description")
                .val(response.project.description || "");
            // Initialize DateRangePicker
            initializeDateRangePicker(
                $overlay.find("#update_start_date, #update_end_date"),
            );

            // Populate users multi-select
            const usersSelect = $overlay.find(".tom_users_select");
            if (usersSelect.length && usersSelect[0].tomselect) {
                usersSelect[0].tomselect.clear(true);
                usersSelect[0].tomselect.clearOptions();
                if (response.users && response.users.length > 0) {
                    const selectedIds = [];
                    response.users.forEach((user) => {
                        usersSelect[0].tomselect.addOption({
                            id: user.id,
                            text: `${user.first_name} ${user.last_name}`,
                        });
                        selectedIds.push(user.id);
                    });
                    usersSelect[0].tomselect.setValue(selectedIds, true);
                }
            } else {
                const legacyUsers = $overlay.find(".users_select");
                legacyUsers.empty();
                if (response.users && response.users.length > 0) {
                    response.users.forEach((user) => {
                        legacyUsers.append(
                            new Option(
                                `${user.first_name} ${user.last_name}`,
                                user.id,
                                true,
                                true,
                            ),
                        );
                    });
                }
                legacyUsers.trigger("change");
            }

            // Populate clients multi-select
            const clientsSelect = $overlay.find(".tom_clients_select");
            if (clientsSelect.length && clientsSelect[0].tomselect) {
                clientsSelect[0].tomselect.clear(true);
                clientsSelect[0].tomselect.clearOptions();
                if (response.clients && response.clients.length > 0) {
                    const selectedIds = [];
                    response.clients.forEach((client) => {
                        clientsSelect[0].tomselect.addOption({
                            id: client.id,
                            text: `${client.first_name} ${client.last_name}`,
                        });
                        selectedIds.push(client.id);
                    });
                    clientsSelect[0].tomselect.setValue(selectedIds, true);
                }
            } else {
                const legacyClients = $overlay.find(".clients_select");
                legacyClients.empty();
                if (response.clients && response.clients.length > 0) {
                    response.clients.forEach((client) => {
                        legacyClients.append(
                            new Option(
                                `${client.first_name} ${client.last_name}`,
                                client.id,
                                true,
                                true,
                            ),
                        );
                    });
                }
                legacyClients.trigger("change");
            }

            // Populate tags multi-select
            const tagsSelect = $overlay.find(
                ".tom_tags_select, [name='tag_ids[]']",
            );
            if (tagsSelect.length && tagsSelect[0].tomselect) {
                tagsSelect[0].tomselect.clear(true);
                tagsSelect[0].tomselect.clearOptions();
                if (response.tags && response.tags.length > 0) {
                    const selectedIds = [];
                    response.tags.forEach((tag) => {
                        tagsSelect[0].tomselect.addOption({
                            id: tag.id,
                            text: tag.title,
                        });
                        selectedIds.push(tag.id);
                    });
                    tagsSelect[0].tomselect.setValue(selectedIds, true);
                }
            } else {
                tagsSelect.empty();
                if (response.tags && response.tags.length > 0) {
                    response.tags.forEach((tag) => {
                        tagsSelect.append(
                            new Option(tag.title, tag.id, true, true),
                        );
                    });
                }
                tagsSelect.trigger("change");
            }

            // Handle checkboxes
            $overlay
                .find("#updateClientCanDiscussProject")
                .prop("checked", response.project.client_can_discuss === 1);
            $overlay
                .find("#tasks_time_entries")
                .prop(
                    "checked",
                    response.project.enable_tasks_time_entries === 1,
                );
            // Handle custom fields
            if (response.customFieldValues) {
                $.each(response.customFieldValues, function (fieldId, value) {
                    const inputField = $overlay.find(`#edit_cf_${fieldId}`);
                    if (inputField.length) {
                        if (inputField.is("select")) {
                            inputField.val(value).trigger("change");
                        } else if (inputField.hasClass("custom-datepicker")) {
                            inputField.val(
                                value
                                    ? moment(value).format(js_date_format)
                                    : "",
                            );
                        } else {
                            inputField.val(value);
                        }
                    } else if (
                        $overlay.find(
                            `input[type="radio"][name="custom_fields[${fieldId}]"]`,
                        ).length
                    ) {
                        $overlay
                            .find(
                                `input[type="radio"][name="custom_fields[${fieldId}]"][value="${value}"]`,
                            )
                            .prop("checked", true);
                    } else if (
                        $overlay.find(
                            `input[type="checkbox"][name="custom_fields[${fieldId}][]"]`,
                        ).length
                    ) {
                        try {
                            const checkboxValues =
                                typeof value === "string" && value.includes("[")
                                    ? JSON.parse(value)
                                    : [value];
                            $overlay
                                .find(
                                    `input[type="checkbox"][name="custom_fields[${fieldId}][]"]`,
                                )
                                .prop("checked", false);
                            checkboxValues.forEach((val) => {
                                $overlay
                                    .find(
                                        `input[type="checkbox"][name="custom_fields[${fieldId}][]"][value="${val}"]`,
                                    )
                                    .prop("checked", true);
                            });
                        } catch (e) {
                            console.error(
                                `Error parsing checkbox values for field ${fieldId}:`,
                                e,
                            );
                        }
                    }
                });
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            toastr.error("Failed to load project data");
        },
    });
}
$(document).on("click", ".edit-project", function () {
    editProject(
        $(this).data("id"),
        $(this).data("offcanvas") === true,
        baseUrl,
        js_date_format,
    );
});
$(document).on("click", ".edit-priority", function () {
    var id = $(this).data("id");
    $("#edit_priority_modal").modal("show");
    var classes = $("#priority_color").attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    $.ajax({
        url: baseUrl + "/priority/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#priority_id").val(response.priority.id);
            $("#priority_title").val(response.priority.title);
            $("#priority_color")
                .val(response.priority.color)
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + response.priority.color);
        },
    });
});
$(document).on("click", ".edit-workspace", function () {
    var id = $(this).data("id");
    $("#editWorkspaceModal").modal("show");
    var $modal = $("#editWorkspaceModal");
    $.ajax({
        url: baseUrl + "/workspaces/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#workspace_id").val(response.workspace.id);
            $("#workspace_title").val(response.workspace.title);
            var usersSelect = $modal.find(".tom_users_select");
            var clientsSelect = $modal.find(".tom_clients_select");

            // Handle multi-select for users
            if (usersSelect.length && usersSelect[0].tomselect) {
                usersSelect[0].tomselect.clear(true);
                usersSelect[0].tomselect.clearOptions();
                if (
                    response.workspace.users &&
                    response.workspace.users.length > 0
                ) {
                    var selectedUsers = [];
                    response.workspace.users.forEach(function (user) {
                        var userIdStr = user.id.toString();
                        usersSelect[0].tomselect.addOption({
                            id: userIdStr,
                            text: user.first_name + " " + user.last_name,
                        });
                        selectedUsers.push(userIdStr);
                    });
                    usersSelect[0].tomselect.setValue(selectedUsers, true);
                }
            } else {
                usersSelect.empty();
                if (
                    response.workspace.users &&
                    response.workspace.users.length > 0
                ) {
                    response.workspace.users.forEach(function (user) {
                        var userOption = new Option(
                            user.first_name + " " + user.last_name,
                            user.id,
                            true,
                            true,
                        );
                        usersSelect.append(userOption);
                    });
                    usersSelect.trigger("change");
                } else {
                    usersSelect.val(null).trigger("change");
                }
            }

            // Handle multi-select for clients
            if (clientsSelect.length && clientsSelect[0].tomselect) {
                clientsSelect[0].tomselect.clear(true);
                clientsSelect[0].tomselect.clearOptions();
                if (
                    response.workspace.clients &&
                    response.workspace.clients.length > 0
                ) {
                    var selectedClients = [];
                    response.workspace.clients.forEach(function (client) {
                        var clientIdStr = client.id.toString();
                        clientsSelect[0].tomselect.addOption({
                            id: clientIdStr,
                            text: client.first_name + " " + client.last_name,
                        });
                        selectedClients.push(clientIdStr);
                    });
                    clientsSelect[0].tomselect.setValue(selectedClients, true);
                }
            } else {
                clientsSelect.empty();
                if (
                    response.workspace.clients &&
                    response.workspace.clients.length > 0
                ) {
                    response.workspace.clients.forEach(function (client) {
                        var clientOption = new Option(
                            client.first_name + " " + client.last_name,
                            client.id,
                            true,
                            true,
                        );
                        clientsSelect.append(clientOption);
                    });
                    clientsSelect.trigger("change");
                } else {
                    clientsSelect.val(null).trigger("change");
                }
            }
            if (response.workspace.is_primary == 1) {
                $("#editWorkspaceModal")
                    .find("#updatePrimaryWorkspace")
                    .prop("checked", true)
                    .prop("disabled", true);
            } else {
                $("#editWorkspaceModal")
                    .find("#updatePrimaryWorkspace")
                    .prop("checked", false)
                    .prop("disabled", false);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
    });
});
function setDefaultWorkspace(workspaceId, isDefault) {
    const isDefaultNumeric = isDefault ? 1 : 0;
    $.ajax({
        url: baseUrl + "/workspaces/" + workspaceId + "/default",
        type: "patch",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        data: {
            is_default: isDefaultNumeric,
        },
        success: function (response) {
            if (response.error == false) {
                toastr.success(response.message);
                $("#table").bootstrapTable("refresh");
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr, status, error) {
            toastr.error(
                "An error occurred while updating the default workspace.",
            );
        },
    });
}
$(document).on("click", ".edit-meeting", function () {
    var id = $(this).data("id");
    $("#editMeetingModal").modal("show");
    $.ajax({
        url: baseUrl + "/meetings/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            var formattedStartDate = moment(response.meeting.start_date).format(
                js_date_format,
            );
            var formattedEndDate = moment(response.meeting.end_date).format(
                js_date_format,
            );
            var startDateInput = $("#editMeetingModal").find(
                '[name="start_date"]',
            );
            var endDateInput = $("#editMeetingModal").find('[name="end_date"]');
            $("#meeting_id").val(response.meeting.id);
            $("#meeting_title").val(response.meeting.title);
            startDateInput.val(formattedStartDate);
            endDateInput.val(formattedEndDate);
            $("#meeting_start_time").val(response.meeting.start_time);
            $("#meeting_end_time").val(response.meeting.end_time);
            var usersSelect = $("#editMeetingModal").find(".users_select");
            var clientsSelect = $("#editMeetingModal").find(".clients_select");
            // Clear existing options
            usersSelect.empty();
            clientsSelect.empty();
            // Handle multi-select for users
            if (response.meeting.users && response.meeting.users.length > 0) {
                response.meeting.users.forEach(function (user) {
                    var userOption = new Option(
                        user.first_name + " " + user.last_name,
                        user.id,
                        true,
                        true,
                    );
                    usersSelect.append(userOption);
                });
                usersSelect.trigger("change");
            } else {
                usersSelect.val(null).trigger("change"); // Handle case when no users are present
            }
            // Handle multi-select for clients
            if (
                response.meeting.clients &&
                response.meeting.clients.length > 0
            ) {
                response.meeting.clients.forEach(function (client) {
                    var clientOption = new Option(
                        client.first_name + " " + client.last_name,
                        client.id,
                        true,
                        true,
                    );
                    clientsSelect.append(clientOption);
                });
                clientsSelect.trigger("change");
            } else {
                clientsSelect.val(null).trigger("change"); // Handle case when no clients are present
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
    });
});
$(document).on("change", "#statusSelect", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var statusId = this.value;
    var type = $(this).data("type") || "project";
    var reload = $(this).data("reload") || false;
    var select = $(this);
    var originalStatusId = select.data("original-status-id");
    var originalColorClass = select.data("original-color-class");
    var classes = select.attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    var selectedOption = select.find("option:selected");
    var selectedOptionClasses = selectedOption.attr("class").split(" ");
    var newColorClass = "select-" + selectedOptionClasses[1];
    select.removeClass(currentColorClass).addClass(newColorClass);
    $.ajax({
        url: baseUrl + "/" + type + "s/get/" + id,
        type: "GET",
        success: function (response) {
            if (response.error == false) {
                $("#confirmUpdateStatusModal").modal("show");
                $("#confirmUpdateStatusModal").off(
                    "click",
                    "#confirmUpdateStatus",
                );
                if (type == "task" && response.task) {
                    $("#statusNote").val(response.task.note);
                    originalStatusId = response.task.status_id;
                } else if (type == "project" && response.project) {
                    $("#statusNote").val(response.project.note);
                    originalStatusId = response.project.status_id;
                }
                $("#confirmUpdateStatusModal").on(
                    "click",
                    "#confirmUpdateStatus",
                    function (e) {
                        $("#confirmUpdateStatus")
                            .html(label_please_wait)
                            .attr("disabled", true);
                        $.ajax({
                            type: "POST",
                            url: baseUrl + "/update-" + type + "-status",
                            headers: {
                                "X-CSRF-TOKEN": $('input[name="_token"]').val(), // Use .val() instead of .attr('value')
                            },
                            data: {
                                id: id,
                                statusId: statusId,
                                note: $("#statusNote").val(),
                            },
                            success: function (response) {
                                $("#confirmUpdateStatus")
                                    .html(label_yes)
                                    .attr("disabled", false);
                                if (response.error == false) {
                                    setTimeout(
                                        function () {
                                            if (reload) {
                                                window.location.reload();
                                            }
                                        },
                                        parseFloat(toastTimeOut) * 1000,
                                    );
                                    $("#confirmUpdateStatusModal").modal(
                                        "hide",
                                    );
                                    var tableSelector =
                                        type == "project"
                                            ? "projects_table"
                                            : "task_table";
                                    var $table = $("#" + tableSelector);
                                    if ($table.length) {
                                        $table.bootstrapTable("refresh");
                                    }
                                    if ($("#activity_log_table").length) {
                                        $("#activity_log_table").bootstrapTable(
                                            "refresh",
                                        );
                                    }
                                    select.attr(
                                        "data-original-status-id",
                                        statusId,
                                    );
                                    toastr.success(response.message);
                                } else {
                                    select
                                        .removeClass(newColorClass)
                                        .addClass(originalColorClass);
                                    select.val(originalStatusId);
                                    toastr.error(response.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                $("#confirmUpdateStatus")
                                    .html(label_yes)
                                    .attr("disabled", false);
                                select
                                    .removeClass(newColorClass)
                                    .addClass(originalColorClass);
                                select.val(originalStatusId);
                                toastr.error("Something Went Wrong");
                            },
                        });
                    },
                );
            } else {
                $("#confirmUpdateStatus")
                    .html(label_yes)
                    .attr("disabled", false);
                select.val(originalStatusId);
                toastr.error(response.message);
            }
        },
        error: function (xhr, status, error) {
            toastr.error("Something Went Wrong");
        },
    });
    $("#confirmUpdateStatusModal").off(
        "click",
        ".btn-close, #declineUpdateStatus",
    );
    $("#confirmUpdateStatusModal").on(
        "click",
        ".btn-close, #declineUpdateStatus",
        function (e) {
            select.val(originalStatusId);
            select.removeClass(newColorClass).addClass(currentColorClass);
        },
    );
});
$(document).on("change", "#prioritySelect", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var priorityId = this.value;
    var type = $(this).data("type") || "project";
    var reload = $(this).data("reload") || false;
    var select = $(this);
    var originalPriorityId = select.data("original-priority-id") || "";
    var originalColorClass = select.data("original-color-class");
    var classes = select.attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    var selectedOption = select.find("option:selected");
    var selectedOptionClasses = selectedOption.attr("class").split(" ");
    var newColorClass = "select-" + selectedOptionClasses[1];
    select.removeClass(currentColorClass).addClass(newColorClass);
    $("#confirmUpdatePriorityModal").modal("show"); // show the confirmation modal
    $("#confirmUpdatePriorityModal").off("click", "#confirmUpdatePriority");
    $("#confirmUpdatePriorityModal").on(
        "click",
        "#confirmUpdatePriority",
        function (e) {
            $("#confirmUpdatePriority")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                type: "POST",
                url: baseUrl + "/update-" + type + "-priority",
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').val(),
                },
                data: {
                    id: id,
                    priorityId: priorityId,
                },
                success: function (response) {
                    $("#confirmUpdatePriority")
                        .html(label_yes)
                        .attr("disabled", false);
                    if (response.error == false) {
                        setTimeout(
                            function () {
                                if (reload) {
                                    window.location.reload(); // Reload the current page
                                }
                            },
                            parseFloat(toastTimeOut) * 1000,
                        );
                        $("#confirmUpdatePriorityModal").modal("hide");
                        var tableSelector =
                            type == "project" ? "projects_table" : "task_table";
                        var $table = $("#" + tableSelector);
                        if ($table.length) {
                            $table.bootstrapTable("refresh");
                        }
                        if ($("#activity_log_table").length) {
                            $("#activity_log_table").bootstrapTable("refresh");
                        }
                        select.data("original-priority-id", priorityId);
                        toastr.success(response.message);
                    } else {
                        select
                            .removeClass(newColorClass)
                            .addClass(originalColorClass);
                        select.val(originalPriorityId);
                        toastr.error(response.message);
                    }
                },
                error: function (xhr, status, error) {
                    $("#confirmUpdatePriority")
                        .html(label_yes)
                        .attr("disabled", false);
                    // Handle error
                    select
                        .removeClass(newColorClass)
                        .addClass(originalColorClass);
                    select.val(originalPriorityId);
                    toastr.error("Something Went Wrong");
                },
            });
        },
    );
    // Handle modal close event
    $("#confirmUpdatePriorityModal").off(
        "click",
        ".btn-close, #declineUpdatePriority",
    );
    $("#confirmUpdatePriorityModal").on(
        "click",
        ".btn-close, #declineUpdatePriority",
        function (e) {
            // Set original priority when modal is closed without confirmation
            select.val(originalPriorityId);
            select.removeClass(newColorClass).addClass(currentColorClass);
        },
    );
});
$("#partialLeave, #updatePartialLeave").on("change", function () {
    var $form = $(this).closest("form"); // Get the closest form element
    var isChecked = $(this).prop("checked");
    if (isChecked) {
        // If the checkbox is checked
        $form
            .find(".leave-from-date-div")
            .removeClass("col-5")
            .addClass("col-3");
        $form.find(".leave-to-date-div").removeClass("col-5").addClass("col-3");
        $form
            .find(".leave-from-time-div, .leave-to-time-div")
            .removeClass("d-none");
    } else {
        // If the checkbox is unchecked, revert the changes
        $form.find('input[name="from_time"]').val("");
        $form.find('input[name="to_time"]').val("");
        $form
            .find(".leave-from-date-div")
            .removeClass("col-3")
            .addClass("col-5");
        $form.find(".leave-to-date-div").removeClass("col-3").addClass("col-5");
        $form
            .find(".leave-from-time-div, .leave-to-time-div")
            .addClass("d-none");
    }
});
$(".leaveVisibleToAll").on("change", function () {
    var $form = $(this).closest("form"); // Get the closest form element
    var isChecked = $(this).prop("checked");
    if (isChecked) {
        // If the checkbox is checked
        $form.find(".leaveVisibleToDiv").addClass("d-none");
        var visibleToSelect = $form.find(
            '.js-example-basic-multiple[name="visible_to_ids[]"]',
        );
        visibleToSelect.val(null).trigger("change");
    } else {
        // If the checkbox is unchecked, revert the changes
        $form.find(".leaveVisibleToDiv").removeClass("d-none");
    }
});
$(document).ready(function () {
    var upcomingBDCalendarInitialized = false;
    var upcomingWACalendarInitialized = false;
    var membersOnLeaveCalendarInitialized = false;
    // Listen for the inner calendar tab click
    $(document).on("shown.bs.tab", ".calendar-button", function (event) {
        var tabId = $(event.target).attr("data-bs-target");
        if (
            tabId === "#upcomingBirthdaysCalendar-calendar" &&
            !upcomingBDCalendarInitialized
        ) {
            initializeUpcomingBDCalendar();
            upcomingBDCalendarInitialized = true;
        } else if (
            tabId === "#upcomingWorkAnniversariesCalendar-calendar" &&
            !upcomingWACalendarInitialized
        ) {
            initializeUpcomingWACalendar();
            upcomingWACalendarInitialized = true;
        } else if (
            tabId === "#membersOnLeaveCalendar-calendar" &&
            !membersOnLeaveCalendarInitialized
        ) {
            initializeMembersOnLeaveCalendar();
            membersOnLeaveCalendarInitialized = true;
        }
    });
});
function initializeUpcomingBDCalendar() {
    var upcomingBDCalendar = document.getElementById(
        "upcomingBirthdaysCalendar",
    );
    // Check if the calendar element exists
    if (upcomingBDCalendar) {
        var BDcalendar = new FullCalendar.Calendar(upcomingBDCalendar, {
            plugins: ["interaction", "dayGrid", "list"],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listYear",
            },
            editable: true,
            eventLimit: 4, // Show max 4 events per day
            events: function (fetchInfo, successCallback, failureCallback) {
                // Make AJAX request to fetch dynamic data
                $.ajax({
                    url: baseUrl + "/home/upcoming-birthdays-calendar",
                    type: "GET",
                    data: {
                        startDate: fetchInfo.startStr,
                        endDate: fetchInfo.endStr,
                    },
                    success: function (response) {
                        // Parse and format dynamic data for FullCalendar
                        var events = response.map(function (event) {
                            return {
                                title: event.title,
                                start: event.start,
                                end: event.start,
                                backgroundColor: event.backgroundColor,
                                borderColor: event.borderColor,
                                textColor: event.textColor,
                                userId: event.userId,
                                type: event.type,
                            };
                        });
                        // Invoke success callback with dynamic data
                        successCallback(events);
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        // Invoke failure callback if there's an error
                        failureCallback(error);
                    },
                });
            },
            eventClick: function (info) {
                if (
                    info.event.extendedProps &&
                    info.event.extendedProps.userId
                ) {
                    var userId = info.event.extendedProps.userId;
                    // Check if the type is 'client'
                    if (info.event.extendedProps.type === "client") {
                        var url = baseUrl + "/clients/profile/" + userId; // Redirect to client's profile
                    } else {
                        var url = baseUrl + "/users/profile/" + userId; // Redirect to user's profile
                    }
                    window.location.href = url;
                }
            },
            eventMouseEnter: function (info) {
                // Create a tooltip element
                var tooltip = document.createElement("div");
                tooltip.innerHTML = info.event.title; // Set the tooltip content
                tooltip.style.position = "absolute";
                tooltip.style.background = "rgba(0, 0, 0, 0.8)";
                tooltip.style.color = "#fff";
                tooltip.style.padding = "5px";
                tooltip.style.borderRadius = "5px";
                tooltip.style.zIndex = "1000";
                tooltip.style.pointerEvents = "none"; // Prevent mouse events
                // Append the tooltip to the body
                document.body.appendChild(tooltip);
                // Position the tooltip
                var rect = info.el.getBoundingClientRect();
                tooltip.style.left = rect.left + window.scrollX + "px";
                tooltip.style.top = rect.bottom + window.scrollY + "px";
                // Remove tooltip on mouse leave
                info.el.addEventListener(
                    "mouseleave",
                    function () {
                        document.body.removeChild(tooltip);
                    },
                    { once: true },
                );
            },
        });
        BDcalendar.render();
    }
}
function initializeUpcomingWACalendar() {
    var upcomingWACalendar = document.getElementById(
        "upcomingWorkAnniversariesCalendar",
    );
    // Check if the calendar element exists
    if (upcomingWACalendar) {
        var WAcalendar = new FullCalendar.Calendar(upcomingWACalendar, {
            plugins: ["interaction", "dayGrid", "list"],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listYear",
            },
            editable: true,
            height: "auto",
            eventLimit: 4, // Show max 4 events per day
            events: function (fetchInfo, successCallback, failureCallback) {
                // Make AJAX request to fetch dynamic data
                $.ajax({
                    url: baseUrl + "/home/upcoming-work-anniversaries-calendar",
                    type: "GET",
                    data: {
                        startDate: fetchInfo.startStr,
                        endDate: fetchInfo.endStr,
                    },
                    success: function (response) {
                        // Parse and format dynamic data for FullCalendar
                        var events = response.map(function (event) {
                            return {
                                title: event.title,
                                start: event.start,
                                end: event.start,
                                backgroundColor: event.backgroundColor,
                                borderColor: event.borderColor,
                                textColor: event.textColor,
                                userId: event.userId,
                                type: event.type,
                            };
                        });
                        // Invoke success callback with dynamic data
                        successCallback(events);
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        // Invoke failure callback if there's an error
                        failureCallback(error);
                    },
                });
            },
            eventClick: function (info) {
                if (
                    info.event.extendedProps &&
                    info.event.extendedProps.userId
                ) {
                    var userId = info.event.extendedProps.userId;
                    // Check if the type is 'client'
                    if (info.event.extendedProps.type === "client") {
                        var url = baseUrl + "/clients/profile/" + userId; // Redirect to client's profile
                    } else {
                        var url = baseUrl + "/users/profile/" + userId; // Redirect to user's profile
                    }
                    window.location.href = url;
                }
            },
            eventMouseEnter: function (info) {
                // Create a tooltip element
                var tooltip = document.createElement("div");
                tooltip.innerHTML = info.event.title; // Set the tooltip content
                tooltip.style.position = "absolute";
                tooltip.style.background = "rgba(0, 0, 0, 0.8)";
                tooltip.style.color = "#fff";
                tooltip.style.padding = "5px";
                tooltip.style.borderRadius = "5px";
                tooltip.style.zIndex = "1000";
                tooltip.style.pointerEvents = "none"; // Prevent mouse events
                // Append the tooltip to the body
                document.body.appendChild(tooltip);
                // Position the tooltip
                var rect = info.el.getBoundingClientRect();
                tooltip.style.left = rect.left + window.scrollX + "px";
                tooltip.style.top = rect.bottom + window.scrollY + "px";
                // Remove tooltip on mouse leave
                info.el.addEventListener(
                    "mouseleave",
                    function () {
                        document.body.removeChild(tooltip);
                    },
                    { once: true },
                );
            },
        });
        WAcalendar.render();
    }
}
function initializeMembersOnLeaveCalendar() {
    var membersOnLeaveCalendar = document.getElementById(
        "membersOnLeaveCalendar",
    );
    // Check if the calendar element exists
    if (membersOnLeaveCalendar) {
        var MOLcalendar = new FullCalendar.Calendar(membersOnLeaveCalendar, {
            plugins: ["interaction", "dayGrid", "list"],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listYear",
            },
            editable: true,
            displayEventTime: true,
            eventLimit: 4, // Show max 4 events per day
            events: function (fetchInfo, successCallback, failureCallback) {
                // Make AJAX request to fetch dynamic data
                $.ajax({
                    url: baseUrl + "/home/members-on-leave-calendar",
                    type: "GET",
                    data: {
                        startDate: fetchInfo.startStr,
                        endDate: fetchInfo.endStr,
                    },
                    success: function (response) {
                        // Parse and format dynamic data for FullCalendar
                        var events = response.map(function (event) {
                            var eventData = {
                                title: event.title,
                                start: event.start,
                                end: moment(event.end)
                                    .add(1, "days")
                                    .format("YYYY-MM-DD"),
                                backgroundColor: event.backgroundColor,
                                borderColor: event.borderColor,
                                textColor: event.textColor,
                                userId: event.userId,
                            };
                            // Check if the event is partial and has start and end times
                            if (event.startTime && event.endTime) {
                                // Include start and end times directly in the event data
                                eventData.extendedProps = {
                                    startTime: event.startTime,
                                    endTime: event.endTime,
                                };
                            }
                            return eventData;
                        });
                        // Invoke success callback with dynamic data
                        successCallback(events);
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        // Invoke failure callback if there's an error
                        failureCallback(error);
                    },
                });
            },
            eventClick: function (info) {
                if (
                    info.event.extendedProps &&
                    info.event.extendedProps.userId
                ) {
                    var userId = info.event.extendedProps.userId;
                    var url = baseUrl + "/users/profile/" + userId;
                    window.location.href = url;
                }
            },
            eventMouseEnter: function (info) {
                // Create a tooltip element
                var tooltip = document.createElement("div");
                tooltip.innerHTML = info.event.title; // Set the tooltip content
                tooltip.style.position = "absolute";
                tooltip.style.background = "rgba(0, 0, 0, 0.8)";
                tooltip.style.color = "#fff";
                tooltip.style.padding = "5px";
                tooltip.style.borderRadius = "5px";
                tooltip.style.zIndex = "1000";
                tooltip.style.pointerEvents = "none"; // Prevent mouse events
                // Append the tooltip to the body
                document.body.appendChild(tooltip);
                // Position the tooltip
                var rect = info.el.getBoundingClientRect();
                tooltip.style.left = rect.left + window.scrollX + "px";
                tooltip.style.top = rect.bottom + window.scrollY + "px";
                // Remove tooltip on mouse leave
                info.el.addEventListener(
                    "mouseleave",
                    function () {
                        document.body.removeChild(tooltip);
                    },
                    { once: true },
                );
            },
        });
        MOLcalendar.render();
    }
}
// Preprocess permissions to avoid redundant checks
var permissionSet = new Set(permissions);
$(document).ready(function () {
    // Loop through classes starting with 'action-'
    $('[class*="action_"]').each(function () {
        // Extract the part of class name after "action-"
        var className = $(this).attr("class");
        var permission = className.substring(
            className.indexOf("action_") + "action_".length,
        );
        // Check if the user is not an admin and if the permission does not exist
        if (
            (typeof isAdmin == "undefined" || !isAdmin) &&
            !permissionSet.has(permission)
        ) {
            $(this).addClass("d-none");
        }
    });
});
$(document).on("click", ".save-column-visibility", function (e) {
    e.preventDefault();
    var tableName = $(this).data("table");
    var type = $(this).data("type");
    type = type.replace("-", "_");
    $("#confirmSaveColumnVisibility").modal("show");
    $("#confirmSaveColumnVisibility").off("click", "#confirm");
    $("#confirmSaveColumnVisibility").on("click", "#confirm", function () {
        $("#confirmSaveColumnVisibility")
            .find("#confirm")
            .html(label_please_wait)
            .attr("disabled", true);
        var visibleColumns = [];
        $("#" + tableName)
            .bootstrapTable("getVisibleColumns")
            .forEach((column) => {
                if (!column.checkbox) {
                    visibleColumns.push(column.field);
                }
            });
        // Send preferences to the server
        $.ajax({
            url: baseUrl + "/save-column-visibility",
            type: "POST",
            data: {
                type: type,
                visible_columns: JSON.stringify(visibleColumns),
            },
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            success: function (response) {
                $("#confirmSaveColumnVisibility")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                if (response.error == false) {
                    $("#confirmSaveColumnVisibility").modal("hide");
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (data) {
                $("#confirmSaveColumnVisibility")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                $("#confirmSaveColumnVisibility").modal("hide");
                toastr.error(label_something_went_wrong);
            },
        });
    });
});
$(document).on("click", ".viewAssigned", function (e) {
    e.preventDefault();
    var projectsUrl = baseUrl + "/projects/listing";
    var tasksUrl = baseUrl + "/tasks/list";
    var id = $(this).data("id");
    var type = $(this).data("type");
    var user = $(this).data("user");
    projectsUrl = projectsUrl + (id ? "/" + id : "");
    tasksUrl = tasksUrl + (id ? "/" + id : "");
    $("#viewAssignedModal").modal("show");
    var projectsTable = $("#viewAssignedModal").find("#projects_table");
    var tasksTable = $("#viewAssignedModal").find("#task_table");
    if (type === "tasks") {
        $('.nav-link[data-bs-target="#navs-top-view-assigned-tasks"]').tab(
            "show",
        );
        $(
            '.nav-link[data-bs-target="#navs-top-view-assigned-projects"]',
        ).removeClass("active");
        $("#navs-top-view-assigned-projects").removeClass("show active");
        $("#navs-top-view-assigned-tasks").addClass("show active");
    } else {
        $('.nav-link[data-bs-target="#navs-top-view-assigned-projects"]').tab(
            "show",
        );
        $(
            '.nav-link[data-bs-target="#navs-top-view-assigned-tasks"]',
        ).removeClass("active");
        $("#navs-top-view-assigned-tasks").removeClass("show active");
        $("#navs-top-view-assigned-projects").addClass("show active");
    }
    $("#userPlaceholder").text(user);
    $(projectsTable).bootstrapTable("refresh", {
        url: projectsUrl,
    });
    $(tasksTable).bootstrapTable("refresh", {
        url: tasksUrl,
    });
});
$(document).on("click", ".openCreateStatusModal", function (e) {
    e.preventDefault();
    $("#create_status_modal").modal("show");
});
$(document).on("click", ".openCreatePriorityModal", function (e) {
    e.preventDefault();
    $("#create_priority_modal").modal("show");
});
$(document).on("click", ".openCreateTagModal", function (e) {
    e.preventDefault();
    $("#create_tag_modal").modal("show");
});
$(document).on("click", ".openCreateContractTypeModal", function (e) {
    e.preventDefault();
    $("#create_contract_type_modal").modal("show");
});
$(document).on("click", ".openCreatePmModal", function (e) {
    e.preventDefault();
    $("#create_pm_modal").modal("show");
});
$(document).on("click", ".openCreateAllowanceModal", function (e) {
    e.preventDefault();
    $("#create_allowance_modal").modal("show");
});
$(document).on("click", ".openCreateDeductionModal", function (e) {
    e.preventDefault();
    $("#create_deduction_modal").modal("show");
});
function formatTag(tag) {
    if (!tag.id) {
        return tag.text;
    }
    var color = tag.color;
    return $(
        '<span class="badge bg-label-' + color + '">' + tag.text + "</span>",
    );
}
/**
 * Initializes Select2 dropdowns for status and priority fields with dynamic parent detection for modals and offcanvas.
 * Formats dropdown options with colored badges and handles clearing behavior for priority dropdowns.
 *
 * @listens document.ready - Executes when the DOM is fully loaded.
 * @returns {void}
 */
$(document).ready(function () {
    /**
     * Initializes TomSelect for elements with class 'statusDropdown'.
     */
    $(".statusDropdown").each(function () {
        var $this = $(this);
        if ($this[0].tomselect) {
            $this[0].tomselect.destroy();
        }
        new TomSelect($this[0], {
            allowEmptyOption: true,
            render: {
                option: function (data, escape) {
                    if (!data.value) {
                        return (
                            "<div>" +
                            escape(data.text || "Please select") +
                            "</div>"
                        );
                    }
                    var color = data.$option
                        ? data.$option.getAttribute("data-color")
                        : data.color || "primary";
                    if (!color) color = "primary";
                    return (
                        '<div><span class="badge bg-' +
                        escape(color) +
                        '">' +
                        escape(data.text) +
                        "</span></div>"
                    );
                },
                item: function (data, escape) {
                    if (!data.value) {
                        return (
                            "<div>" +
                            escape(data.text || "Please select") +
                            "</div>"
                        );
                    }
                    var color = data.$option
                        ? data.$option.getAttribute("data-color")
                        : data.color || "primary";
                    if (!color) color = "primary";
                    return (
                        '<div><span class="badge bg-' +
                        escape(color) +
                        '">' +
                        escape(data.text) +
                        "</span></div>"
                    );
                },
            },
        });
    });

    /**
     * Initializes TomSelect for elements with class 'priorityDropdown'.
     */
    $(".priorityDropdown").each(function () {
        var $this = $(this);
        if ($this[0].tomselect) {
            $this[0].tomselect.destroy();
        }
        new TomSelect($this[0], {
            allowEmptyOption: true,
            render: {
                option: function (data, escape) {
                    if (!data.value) {
                        return (
                            "<div>" +
                            escape(data.text || "Please select") +
                            "</div>"
                        );
                    }
                    var color = data.$option
                        ? data.$option.getAttribute("data-color")
                        : data.color || "primary";
                    if (!color) color = "primary";
                    return (
                        '<div><span class="badge bg-' +
                        escape(color) +
                        '">' +
                        escape(data.text) +
                        "</span></div>"
                    );
                },
                item: function (data, escape) {
                    if (!data.value) {
                        return (
                            "<div>" +
                            escape(data.text || "Please select") +
                            "</div>"
                        );
                    }
                    var color = data.$option
                        ? data.$option.getAttribute("data-color")
                        : data.color || "primary";
                    if (!color) color = "primary";
                    return (
                        '<div><span class="badge bg-' +
                        escape(color) +
                        '">' +
                        escape(data.text) +
                        "</span></div>"
                    );
                },
            },
        });
    });
});
$(document).on("change", 'select[name="color"]', function (e) {
    e.preventDefault();
    var select = $(this);
    var classes = $(this).attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    var selectedOption = $(this).find("option:selected");
    var selectedOptionClasses = selectedOption.attr("class").split(" ");
    var newColorClass = "select-" + selectedOptionClasses[1];
    select.removeClass(currentColorClass).addClass(newColorClass);
});
function toggleChatIframe() {
    var iframeContainer = document.getElementById("chatIframeContainer");
    if (
        iframeContainer.style.display === "none" ||
        iframeContainer.style.display === ""
    ) {
        iframeContainer.style.display = "block";
    } else {
        iframeContainer.style.display = "none";
    }
}
$(document).ready(function () {
    if ($("#selectAllPreferences").length) {
        // Check initial state of checkboxes and update selectAllPreferences checkbox
        updateSelectAll();
        // Select/deselect all checkboxes when the selectAllPreferences checkbox is clicked
        $("#selectAllPreferences").click(function () {
            var isChecked = $(this).prop("checked");
            $('input[name="enabled_notifications[]"]:not(:disabled)').prop(
                "checked",
                isChecked,
            );
        });
        // Update the selectAllPreferences checkbox state based on the checkboxes' status
        $('input[name="enabled_notifications[]"]').change(function () {
            updateSelectAll();
        });
        // Function to update selectAllPreferences checkbox based on checkboxes' status
        function updateSelectAll() {
            var allChecked =
                $('input[name="enabled_notifications[]"]:not(:disabled)')
                    .length ===
                $(
                    'input[name="enabled_notifications[]"]:not(:disabled):checked',
                ).length;
            $("#selectAllPreferences").prop("checked", allChecked);
        }
    }
});
$("#internal_client").change(function () {
    var isChecked = $(this).prop("checked");
    $("#password, #password_confirmation").val("");
    $("#passDiv, #confirmPassDiv, #statusDiv, #requireEvDiv").toggleClass(
        "d-none",
        isChecked,
    );
    $("#client_deactive").prop("checked", true);
    $("#require_ev_" + (isChecked ? "no" : "yes")).prop("checked", true);
    $("#password").next(".error-message").remove();
    $("#password_confirmation").next(".error-message").remove();
});
$("#update_internal_client").change(function () {
    var isChecked = $(this).prop("checked");
    $("#password, #password_confirmation").val("");
    $("#passDiv, #confirmPassDiv, #statusDiv, #requireEvDiv").toggleClass(
        "d-none",
        isChecked,
    );
    // Remove .error-message elements next to #password and #password_confirmation
    $("#password").next(".error-message").remove();
    $("#password_confirmation").next(".error-message").remove();
});
$(document).ready(function () {
    $("#previewToast").click(function () {
        var previewToastPosition = $("#toastPosition").val();
        var toastTimeoutInput = $("#toastTimeout");
        var previewToastTimeout = parseFloat(toastTimeoutInput.val());
        // Validate toast timeout is not blank and is a positive number
        if (isNaN(previewToastTimeout) || previewToastTimeout <= 0) {
            toastr.options = {
                positionClass: toastPosition,
                timeOut: parseFloat(toastTimeOut) * 1000,
                showDuration: "300",
                hideDuration: "1000",
                extendedTimeOut: "1000",
                progressBar: true,
                closeButton: true,
            };
            toastr.error("Please enter a valid timeout value in seconds.");
            toastTimeoutInput.focus();
            return;
        }
        // Convert timeout to milliseconds
        previewToastTimeout *= 1000;
        toastr.options = {
            positionClass: previewToastPosition,
            timeOut: previewToastTimeout,
            showDuration: "300",
            hideDuration: "1000",
            extendedTimeOut: "1000",
            progressBar: true,
            closeButton: true,
        };
        toastr.success(
            "This is a preview of your toast message!",
            "Toast Preview",
        );
    });
});
$(document).ready(function () {
    var $canvas = $("#promisor_sign");
    var $resetButton = $("#reset_promisor_sign");
    // Function to resize canvas
    function resizeCanvas() {
        var $modalBody = $canvas.closest(".modal-body");
        var maxWidth = $modalBody.width() - 32; // Subtract padding
        var aspectRatio = $canvas[0].width / $canvas[0].height;
        $canvas.attr("width", maxWidth);
        $canvas.attr("height", maxWidth / aspectRatio);
    }
    // Resize canvas when the modal is shown
    $("#create_contract_sign_modal").on("shown.bs.modal", function () {
        resizeCanvas();
    });
    // Handle canvas reset
    $resetButton.on("click", function () {
        var context = $canvas[0].getContext("2d");
        context.clearRect(0, 0, $canvas[0].width, $canvas[0].height);
    });
});
$(document).on("click", "#testSmsSettingsButton", function (e) {
    e.preventDefault();
    $("#testSmsSettingsModal").modal("show");
});
$("#testSmsSettingsForm").on("submit", function (event) {
    event.preventDefault();
    var recipientNumber = $("#testSmsRecipientNumber").val();
    var recipientCountryCode = $("#testSmsRecipientCountryCode").val();
    var message = $("#testSmsMessage").val();
    // AJAX request
    $.ajax({
        url: baseUrl + "/settings/notifications/test",
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        data: {
            type: "sms",
            recipientCountryCode: recipientCountryCode,
            recipientNumber: recipientNumber,
            message: message,
        },
        dataType: "json",
        beforeSend: function () {
            $("#performTestSmsSettingsButton")
                .prop("disabled", true)
                .html(label_sending);
        },
        success: function (response) {
            // Handle successful response
            $("#performTestSmsSettingsButton")
                .prop("disabled", false)
                .html(label_submit);
            $("#smsTestResponse").removeClass("d-none");
            $("#smsResponseText").text(JSON.stringify(response, null, 2));
        },
        error: function (xhr, status, error) {
            // Handle error
            $("#performTestSmsSettingsButton")
                .prop("disabled", false)
                .html(label_submit);
            $("#smsTestResponse").removeClass("d-none");
            $("#smsResponseText").text(
                (APP_LABELS && APP_LABELS["error_prefix"]
                    ? APP_LABELS["error_prefix"]
                    : "Error: ") + xhr.responseText,
            );
        },
    });
});
$("#testSmsSettingsModal").on("hidden.bs.modal", function () {
    $("#smsTestResponse").addClass("d-none");
    $("#smsResponseText").text("");
});
$(document).on("click", "#testWhatsappSettingsButton", function (e) {
    e.preventDefault();
    $("#testWhatsappSettingsModal").modal("show");
});
$("#testWhatsappSettingsForm").on("submit", function (event) {
    event.preventDefault();
    var recipientNumber = $("#testWhatsappRecipientNumber").val();
    var recipientCountryCode = $("#testWhatsappRecipientCountryCode").val();
    var message = $("#testWhatsappMessage").val();
    // AJAX request
    $.ajax({
        url: baseUrl + "/settings/notifications/test",
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        data: {
            type: "whatsapp",
            recipientCountryCode: recipientCountryCode,
            recipientNumber: recipientNumber,
            message: message,
        },
        dataType: "json",
        beforeSend: function () {
            $("#performTestWhatsappSettingsButton")
                .prop("disabled", true)
                .html(label_sending);
        },
        success: function (response) {
            // Handle successful response
            $("#performTestWhatsappSettingsButton")
                .prop("disabled", false)
                .html(label_submit);
            $("#whatsappTestResponse").removeClass("d-none");
            $("#whatsappResponseText").text(JSON.stringify(response, null, 2));
        },
        error: function (xhr, status, error) {
            // Handle error
            $("#performTestWhatsappSettingsButton")
                .prop("disabled", false)
                .html(label_submit);
            $("#whatsappTestResponse").removeClass("d-none");
            $("#whatsappResponseText").text(
                (APP_LABELS && APP_LABELS["error_prefix"]
                    ? APP_LABELS["error_prefix"]
                    : "Error: ") + xhr.responseText,
            );
        },
    });
});
$("#testWhatsappSettingsModal").on("hidden.bs.modal", function () {
    $("#whatsappTestResponse").addClass("d-none");
    $("#whatsappResponseText").text("");
});
$(document).on("click", "#testSlackSettingsButton", function (e) {
    e.preventDefault();
    $("#testSlackSettingsModal").modal("show");
});
$("#testSlackSettingsForm").on("submit", function (event) {
    event.preventDefault();
    var recipientEmail = $("#testSlackRecipientEmail").val();
    var message = $("#testSlackMessage").val();
    // AJAX request
    $.ajax({
        url: baseUrl + "/settings/notifications/test",
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        data: {
            type: "slack",
            recipientEmail: recipientEmail,
            message: message,
        },
        dataType: "json",
        beforeSend: function () {
            $("#performTestSlackSettingsButton")
                .prop("disabled", true)
                .html(label_sending);
        },
        success: function (response) {
            // Handle successful response
            $("#performTestSlackSettingsButton")
                .prop("disabled", false)
                .html(label_submit);
            $("#slackTestResponse").removeClass("d-none");
            $("#slackResponseText").text(JSON.stringify(response, null, 2));
        },
        error: function (xhr, status, error) {
            // Handle error
            $("#performTestSlackSettingsButton")
                .prop("disabled", false)
                .html(label_submit);
            $("#slackTestResponse").removeClass("d-none");
            $("#slackResponseText").text(
                (APP_LABELS && APP_LABELS["error_prefix"]
                    ? APP_LABELS["error_prefix"]
                    : "Error: ") + xhr.responseText,
            );
        },
    });
});
$("#testSlackSettingsModal").on("hidden.bs.modal", function () {
    $("#slackTestResponse").addClass("d-none");
    $("#slackResponseText").text("");
});
$(document).ready(function () {
    // Function to validate input
    function validateCurrencyInput() {
        var input = $(this);
        var value = input.val();
        // Check for disallowed characters
        if (/[^0-9.,]/.test(value)) {
            toastr.error(label_currency_restriction);
            value = value.replace(/[^0-9.,]/g, "");
        }
        // Check for multiple decimal points
        var multipleDecimalPoints = value.split(".").length - 1;
        if (multipleDecimalPoints > 1) {
            toastr.error(label_currency_restriction_1);
            // Keep only the first decimal point
            value = value.replace(/(\..*)\./g, "$1");
        }
        input.val(value);
    }
    // Apply validation to all inputs with class "currency"
    $(document).on("input", ".currency", validateCurrencyInput);
    function validateDecimalInput() {
        var input = $(this);
        var value = input.val();
        // Remove any commas
        value = value.replace(/,/g, "");
        // Check for disallowed characters (anything other than digits and decimal point)
        if (/[^0-9.]/.test(value)) {
            toastr.error(label_currency_restriction_2);
            value = value.replace(/[^0-9.]/g, "");
        }
        // Check for multiple decimal points
        var multipleDecimalPoints = value.split(".").length - 1;
        if (multipleDecimalPoints > 1) {
            toastr.error(label_currency_restriction_1);
            // Keep only the first decimal point
            value = value.replace(/(\..*)\./g, "$1");
        }
        input.val(value);
    }
    $(document).on("input", ".decimal-currency", validateDecimalInput);
});
$(document).ready(function () {
    const input = $("#phone")[0]; // Get the actual DOM element for intlTelInput
    // Check if the input element exists and has the data-type="create" attribute
    if (input) {
        var $countryCodeIsoInput = $("#country_iso_code");
        var $countryCodeNumInput = $("#country_code");
        var initialCountryCode = "";
        // Check if the hidden input exists and has a value
        if ($countryCodeIsoInput.length && $countryCodeIsoInput.val()) {
            initialCountryCode = $countryCodeIsoInput.val();
        }
        // Determine whether to set initial country to 'auto' or leave it unset
        var auto = $(input).data("type") === "create" ? "auto" : "";
        // Initialize intlTelInput with the appropriate initial country setting
        const iti = window.intlTelInput(input, {
            initialCountry: initialCountryCode || auto, // Set to 'auto' or initialCountryCode if available
            geoIpLookup: (callback) => {
                fetch("https://ipapi.co/json")
                    .then((res) => res.json())
                    .then((data) => callback(data.country_code))
                    .catch(() => callback("us"));
            },
            utilsScript:
                "https://cdn.jsdelivr.net/npm/intl-tel-input@24.3.4/build/js/utils.js",
            separateDialCode: true,
        });
        $(input).on("countrychange", () => {
            const countryData = iti.getSelectedCountryData();
            if (countryData && countryData.iso2 && countryData.dialCode) {
                // Update the hidden input with the selected country code
                $countryCodeIsoInput.val(countryData.iso2);
                $countryCodeNumInput.val("+" + countryData.dialCode);
            } else {
                // Clear the hidden inputs if the country data is not valid
                $countryCodeIsoInput.val("");
                $countryCodeNumInput.val("");
                iti.setCountry("");
            }
        });
        // Clear and reset country selection when the phone input is cleared
        $(input).on("input", function () {
            if ($(this).val() === "") {
                $countryCodeIsoInput.val("");
                $countryCodeNumInput.val("");
                iti.setCountry("");
            }
        });
        // Add functionality to clear the phone input and reset the country code
        $(".clear-input").on("click", function () {
            $(input).val(""); // Clear the phone input
            $countryCodeIsoInput.val(""); // Clear the hidden country code fields
            $countryCodeNumInput.val("");
            iti.setCountry(""); // Clear the country flag
        });
    }
});
function initSelect2WithAjax(selector, type) {
    $(selector).each(function () {
        if ($(this).length) {
            var $this = $(this);
            var allowClear =
                $this.data("allow-clear") === "false" ? false : true;
            var leaveVisibleToUsers = $this.data("leave-visible-to-users");
            leaveVisibleToUsers =
                leaveVisibleToUsers == undefined
                    ? false
                    : leaveVisibleToUsers === false
                      ? false
                      : true;
            var ignoreAdmins = $this.data("ignore-admins");
            ignoreAdmins =
                ignoreAdmins == undefined
                    ? false
                    : ignoreAdmins === false
                      ? false
                      : true;
            // Check if the 'data-consider-workspace' attribute is defined
            var considerWorkspace = $this.data("consider-workspace");
            // If 'considerWorkspace' is undefined, default to true
            considerWorkspace =
                considerWorkspace == undefined
                    ? true
                    : considerWorkspace === false
                      ? false
                      : true;
            var singleSelect =
                $this.data("single-select") === undefined ||
                $this.data("single-select") === false
                    ? false
                    : true;
            // New: Check if initial values should be loaded
            var loadInitialValues = $this.data("load-initial") !== false; // Default to true unless explicitly set to false
            var initialLimit = $this.data("initial-limit") || 10; // Default to 10 initial items
            var ajaxOptions = {
                ajax: {
                    url: "/search", // API endpoint to fetch data dynamically
                    dataType: "json",
                    delay: 250,
                    data: function (params) {
                        var requestData = {
                            q: params.term, // search term
                            type: type, // dynamic type: 'tags', 'statuses', 'priorities'
                            considerWorkspace: considerWorkspace,
                            leaveVisibleToUsers: leaveVisibleToUsers,
                            ignoreAdmins: ignoreAdmins,
                        };
                        // If no search term and initial values should be loaded
                        if (!params.term && loadInitialValues) {
                            requestData.initial = true;
                            requestData.limit = initialLimit;
                        }
                        return requestData;
                    },
                    processResults: function (data) {
                        return {
                            results: data.results.map(function (item) {
                                // Handle 'color' only for 'tags'
                                // if (type === 'tags') {
                                //     return {
                                //         id: item.id,
                                //         text: item.text,
                                //         color: item.color
                                //     };
                                // }
                                // Default handling for other types
                                return {
                                    id: item.id,
                                    text: item.text,
                                };
                            }),
                        };
                    },
                    cache: true,
                },
                minimumInputLength: loadInitialValues ? 0 : 1, // Allow opening without typing if initial values are enabled
                allowClear: allowClear,
                closeOnSelect: singleSelect,
                language: {
                    inputTooShort: function () {
                        return label_please_type_at_least_1_character;
                    },
                    searching: function () {
                        return label_searching;
                    },
                    noResults: function () {
                        return label_no_results_found;
                    },
                },
            };
            // Apply specific templates if type is 'tags'
            // if (type === 'tags') {
            //     ajaxOptions.templateResult = formatTag;
            //     ajaxOptions.templateSelection = formatTag;
            //     ajaxOptions.escapeMarkup = function (markup) {
            //         return markup; // Prevent escaping of markup
            //     };
            // }
            // Check if the element is inside a modal
            if (
                $this.closest(".modal").length &&
                $this.data("single-select") == true
            ) {
                var modalId = $this.closest(".modal").attr("id"); // Get the ID of the closest .modal
                if (modalId) {
                    ajaxOptions.dropdownParent = $("#" + modalId); // Use the ID to reference the modal
                }
            }
            $this.select2(ajaxOptions);
            $(".cancel-button").on("click", function () {
                $this.select2("close"); // Close the dropdown
            });
        }
    });
}

function initTomSelectWithAjax(selector, type) {
    document.querySelectorAll(selector).forEach(function (el) {
        if (el.tomselect) {
            el.tomselect.destroy();
        }

        var allowClear = el.dataset.allowClear !== "false";
        var considerWorkspace = el.dataset.considerWorkspace !== "false";
        var leaveVisibleToUsers = el.dataset.leaveVisibleToUsers !== "false";
        var ignoreAdmins = el.dataset.ignoreAdmins !== "false";
        var isMultiple = el.hasAttribute("multiple");

        var plugins = [];
        if (allowClear) {
            plugins.push("clear_button");
        }
        if (isMultiple) {
            plugins.push("remove_button");
        }

        new TomSelect(el, {
            valueField: "id",
            labelField: "text",
            searchField: "text",
            plugins: plugins,
            preload: true,
            load: function (query, callback) {
                var url =
                    "/search?q=" +
                    encodeURIComponent(query) +
                    "&type=" +
                    encodeURIComponent(type) +
                    "&considerWorkspace=" +
                    considerWorkspace +
                    "&leaveVisibleToUsers=" +
                    leaveVisibleToUsers +
                    "&ignoreAdmins=" +
                    ignoreAdmins;

                if (!query) {
                    url +=
                        "&initial=true&limit=" +
                        (el.dataset.initialLimit || 10);
                }

                fetch(url)
                    .then((response) => response.json())
                    .then((json) => {
                        callback(json.results);
                    })
                    .catch(() => {
                        callback();
                    });
            },
        });
    });
}
// Tom Select for a <select> that already has static <option>s (no AJAX).
function initTomSelectStatic(selector) {
    document.querySelectorAll(selector).forEach(function (el) {
        if (el.tomselect) {
            el.tomselect.destroy();
        }
        var allowClear = el.dataset.allowClear !== "false";
        var isMultiple = el.hasAttribute("multiple");
        var plugins = [];
        if (allowClear) {
            plugins.push("clear_button");
        }
        if (isMultiple) {
            plugins.push("remove_button");
        }

        new TomSelect(el, {
            plugins: plugins,
            persist: false,
            placeholder: el.dataset.placeholder || "",
        });
    });
}

// When a "Clear filters" button is clicked, also clear any Tom Select instances
// in the same card (the page JS only resets the underlying <select>, which would
// leave the Tom Select chips visible). Additive — no page JS changed.
$(document).on("click", '[class*="clear-"][class*="-filters"]', function () {
    var scope =
        this.closest(".tk-filter-panel, .card, .container-fluid") || document;
    scope.querySelectorAll("select").forEach(function (s) {
        if (s.tomselect) {
            s.tomselect.clear(true); // silent: page handler already triggers the refresh
        }
    });
});

function initTagifyFromSelect(selector, type) {
    document.querySelectorAll(selector).forEach(function (selectEl) {
        // Hide the original select
        selectEl.style.display = "none";

        // Create an input for Tagify
        var input = document.createElement("input");
        input.setAttribute("placeholder", "Select users...");
        selectEl.parentNode.insertBefore(input, selectEl.nextSibling);

        // Detect if inside modal
        var modalEl = selectEl.closest(".modal");
        var dropdownContainer = modalEl ? modalEl : document.body;

        // Initialize Tagify
        var tagify = new Tagify(input, {
            enforceWhitelist: true,
            whitelist: [],
            dropdown: {
                enabled: 0,
                maxItems: 10,
                closeOnSelect: true,
                searchKeys: ["text"],
                // ✅ Ensure dropdown renders inside modal if present
                appendTarget: dropdownContainer,
            },
        });

        // Fix z-index if in modal
        if (modalEl) {
            tagify.DOM.dropdown.style.zIndex = "2000"; // higher than Bootstrap modal (1055)
        }

        // Preload already selected <option> as Tagify values
        var selected = Array.from(selectEl.options)
            .filter((o) => o.selected)
            .map((o) => ({ value: o.value, text: o.text }));
        tagify.addTags(selected);

        // Sync Tagify back to <select>
        tagify.on("change", function () {
            // Clear all selected options
            Array.from(selectEl.options).forEach((o) => (o.selected = false));

            // Set selected ones from tagify
            var values = tagify.value.map((v) => v.value);
            values.forEach((val) => {
                var option = Array.from(selectEl.options).find(
                    (o) => o.value == val,
                );
                if (option) option.selected = true;
                else {
                    // If not exists, create dynamically
                    var newOpt = new Option(val, val, true, true);
                    selectEl.add(newOpt);
                }
            });
        });

        // Load suggestions dynamically (AJAX)
        tagify.on("input", function (e) {
            var value = e.detail.value;

            if (tagify.loadingXHR) tagify.loadingXHR.abort();

            tagify.loading(true).dropdown.hide.call(tagify);

            tagify.loadingXHR = $.ajax({
                url: "/search",
                data: {
                    q: value,
                    type: type,
                    initial: !value,
                    limit: $(selectEl).data("initial-limit") ?? 10,
                },
                dataType: "json",
                success: function (data) {
                    tagify.settings.whitelist = data.results.map((item) => ({
                        value: item.id,
                        text: item.text,
                    }));
                    tagify.loading(false).dropdown.show.call(tagify, value);
                },
            });
        });
    });
}

$(document).ready(function () {
    initSelect2WithAjax(".projects_select", "projects");
    initSelect2WithAjax(".users_select", "users");
    // initTagifyFromSelect(".users_select", "users");

    initTomSelectWithAjax(".tom_users_select", "users");
    initTomSelectWithAjax(".tom_clients_select", "clients");
    initTomSelectWithAjax(".tom_projects_select", "projects");
    initTomSelectWithAjax(".tom_contract_types_select", "contract_types");
    initTomSelectWithAjax(".tom_statuses_filter", "statuses");
    initTomSelectWithAjax(".tom_priorities_filter", "priorities");
    initTomSelectWithAjax(".tom_tags_filter", "tags");
    initTomSelectWithAjax(".tom_tags_select", "tags");
    initTomSelectWithAjax(".tom_allowances_select", "allowances");
    initTomSelectWithAjax(".tom_deductions_select", "deductions");
    initTomSelectWithAjax(".tom_expense_types_select", "expense_types");
    initTomSelectWithAjax(".tom_invoices_select", "invoices");
    initTomSelectWithAjax(
        ".tom_candidate_statuses_select",
        "candidate_statuses",
    );
    initTomSelectStatic(".tom_static_select");

    initSelect2WithAjax(".clients_select", "clients");
    initSelect2WithAjax(".tags_select", "tags");
    initSelect2WithAjax(".contract_types_select", "contract_types");
    initSelect2WithAjax(".expense_types_select", "expense_types");
    initSelect2WithAjax(".allowances_select", "allowances");
    initSelect2WithAjax(".deductions_select", "deductions");
    initSelect2WithAjax(".items_select", "items");
    initSelect2WithAjax(".invoices_select", "invoices");
    initSelect2WithAjax(".statuses_filter", "statuses");
    initSelect2WithAjax(".priorities_filter", "priorities");
    initTomSelectWithAjax("#select_lead_source", "lead_sources");
    initTomSelectWithAjax("#select_lead_stage", "lead_stages");
    initTomSelectWithAjax("#select_lead_assignee", "users");
    initSelect2WithAjax("#create_follow_up_assigned_to", "users");
    initSelect2WithAjax("#edit_follow_up_assigned_to", "users");
    initTomSelectWithAjax("#selected_sources", "lead_sources");
    initTomSelectWithAjax("#selected_stages", "lead_stages");
    initTomSelectWithAjax(
        ".select-interview-candidate",
        "interview_candidates",
    );
    initTomSelectWithAjax(
        ".select-interview-interviewer",
        "interview_interviewer",
    );
    $("#edit_task_offcanvas")
        .find('select[name="user_id[]"]')
        .each(function () {
            if ($(this).length) {
                $(this).select2({
                    minimumInputLength: 1,
                    allowClear: true,
                    language: {
                        inputTooShort: function () {
                            return label_please_type_at_least_1_character;
                        },
                        searching: function () {
                            return label_searching;
                        },
                        noResults: function () {
                            return label_no_results_found;
                        },
                    },
                });
            }
        });
});
$(document).ready(function () {
    // Function to load users for a specific project
    function loadProjectUsers(projectId) {
        var usersSelect = $("#create_task_offcanvas").find(
            'select[name="user_id[]"]',
        );
        usersSelect.empty(); // Clear any previous options
        if (projectId) {
            $.ajax({
                url: baseUrl + "/projects/get/" + projectId, // Endpoint to get users based on project
                type: "GET",
                success: function (response) {
                    // Add the project users as options
                    if (response.users && response.users.length > 0) {
                        // Iterate through the users and add them to the select element
                        response.users.forEach(function (user) {
                            var userOption = new Option(
                                user.first_name + " " + user.last_name,
                                user.id,
                                false,
                                false,
                            );
                            usersSelect.append(userOption);
                        });
                        // If task_accessibility is 'project_users', select the users automatically
                        if (
                            response.project.task_accessibility ===
                            "project_users"
                        ) {
                            var projectUserIds = response.users.map(
                                function (user) {
                                    return user.id;
                                },
                            );
                            // Set selected users
                            usersSelect.val(projectUserIds);
                        }
                        // Trigger select2 to update the selected values
                        usersSelect.trigger("change");
                    } else {
                        // Handle case when there are no users
                        usersSelect.val(null).trigger("change");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error loading project users:", error);
                },
            });
        }
    }
    // Check if the project is set via a hidden input (when project is not selectable)
    var projectInput = $('input[name="project"]'); // Cache the selector

    if (projectInput.length) {
        var projectId = projectInput.val();

        console.log(projectInput);
        if (projectId) {
            loadProjectUsers(projectId); // Load users if the project is pre-selected and not selectable
        }
    }
});
$(document).ready(function () {
    $("#generate-password").on("click", function () {
        function generatePassword(length) {
            var charset =
                "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
            var password = "";
            for (var i = 0, n = charset.length; i < length; ++i) {
                password += charset.charAt(Math.floor(Math.random() * n));
            }
            return password;
        }
        // Generate a new random password
        var newPassword = generatePassword(12);
        // Set the generated password in both password and confirm password fields
        $("#password").val(newPassword);
        $("#password_confirmation").val(newPassword);
        // Ensure password is visible after generation
        var passwordField = $("#password");
        var toggleIcon = $(".toggle-password i");
        // Explicitly set the password field type to 'text'
        if (passwordField.attr("type") === "password") {
            passwordField.attr("type", "text"); // Show password
            // Ensure the toggle icon is in 'show' state
            toggleIcon.removeClass("bx-hide").addClass("bx-show");
        }
    });
});
$("#create_project_modal ").on("shown.bs.modal", function (event) {
    var currentUrl = window.location.pathname;
    // Check if the current URL contains one of the favorite project routes
    if (
        currentUrl.includes("/kanban/favorite") ||
        currentUrl.includes("/list/favorite") ||
        currentUrl.includes("/favorite")
    ) {
        $("#create_project_modal #is_favorite").val(1); // Set is_favorite to 1 if on a favorite page
    } else {
        $("#create_project_modal #is_favorite").val(0); // Set is_favorite to 0 if not on a favorite page
    }
    var button = $(event.relatedTarget); // Button that triggered the modal
    var statusId = button.data("status-id"); // Extract status ID from data attribute
    // Find the status dropdown
    var $statusDropdown = $(this).find('select[name="status_id"]');
    // Check if the status ID is defined
    if (statusId) {
        // Check if the dropdown contains the option with the given status ID
        if ($statusDropdown.find(`option[value="${statusId}"]`).length) {
            // Set the selected status in the dropdown
            $statusDropdown.val(statusId).trigger("change");
        }
    }
});
$("#create_project_offcanvas").on("shown.bs.offcanvas", function (event) {
    var currentUrl = window.location.pathname;
    // Check if the current URL contains one of the favorite project routes
    if (
        currentUrl.includes("/kanban/favorite") ||
        currentUrl.includes("/list/favorite") ||
        currentUrl.includes("/favorite")
    ) {
        $("#create_project_offcanvas #is_favorite").val(1); // Set is_favorite to 1 if on a favorite page
    } else {
        $("#create_project_offcanvas #is_favorite").val(0); // Set is_favorite to 0 if not on a favorite page
    }
    var button = $(event.relatedTarget); // Button that triggered the offcanvas
    var statusId = button.data("status-id"); // Extract status ID from data attribute
    // Find the status dropdown
    var $statusDropdown = $(this).find('select[name="status_id"]');
    // Check if the status ID is defined
    if (statusId) {
        // Check if the dropdown contains the option with the given status ID
        if ($statusDropdown.find(`option[value="${statusId}"]`).length) {
            // Set the selected status in the dropdown
            if ($statusDropdown[0].tomselect) {
                $statusDropdown[0].tomselect.setValue(statusId);
            } else {
                $statusDropdown.val(statusId).trigger("change");
            }
        }
    }
});
$("#create_task_offcanvas").on("shown.bs.offcanvas", function (event) {
    if (
        window.location.search.includes("favorite=1") ||
        window.location.search.includes("is_favorite=1")
    ) {
        $("#create_task_offcanvas #is_favorite").val(1); // Set is_favorite to 1 if the query parameter favorite=1 is present
    } else {
        $("#create_task_offcanvas #is_favorite").val(0); // Set is_favorite to 0 if the query parameter favorite is not present
    }
});

// Legacy menu search logic removed
function addClearButtonFunctionality() {
    $(".custom-search-input").each(function () {
        const $inputField = $(this); // Current input field
        // Listen for input changes on the input field
        $inputField.on("input", function () {
            var searchQuery = $(this).val().toLowerCase();
            // Check if the search query is not empty
            if (searchQuery) {
                // Add the clear button if it doesn't already exist
                if ($inputField.siblings(".clear-search").length === 0) {
                    // Create and append the clear button
                    const clearButtonHtml =
                        '<span class="input-group-text cursor-pointer clear-search"><i class="bx bx-x"></i></span>';
                    $inputField.after(clearButtonHtml);
                }
            } else {
                // Remove the clear button if the input is empty
                $inputField.siblings(".clear-search").remove();
            }
        });
    });
    // Clear button click functionality
    $(document).on("click", ".clear-search", function () {
        const $inputField = $(this).prev(".custom-search-input"); // Get the associated input field
        $inputField.val("").trigger("input"); // Clear the input and trigger the input event
        $(this).remove(); // Remove the clear button
    });
}
$(document).ready(function () {
    // Apply clear button functionality to all search inputs
    addClearButtonFunctionality();
});
// Mention in the text area
function initializeMentionTextarea($textarea) {
    // Extract mention id and type from the data attributes of the textarea
    const mentionID = $textarea.data("mention-id");
    const mentionType = $textarea.data("mention-type");
    // Check if the textarea element exists
    if ($textarea.length === 0) {
        console.error("Textarea not found.");
        return;
    }
    // Initialize Tribute.js with the provided textarea
    const tribute = new Tribute({
        values: function (text, cb) {
            // Fetch users based on the search term and mention info
            $.ajax({
                url: baseUrl + "/users/get-mentions",
                method: "GET",
                data: {
                    search: text,
                    mention_id: mentionID,
                    mention_type: mentionType,
                },
                success: function (response) {
                    const mappedUsers = response.map((user) => ({
                        key: user.id, // Use 'id' as key
                        value: user.first_name + " " + user.last_name,
                    }));
                    cb(mappedUsers); // Provide the data to Tribute.js callback
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching users:", error);
                },
            });
        },
        selectTemplate: function (item) {
            return `@${item.original.value}`; // What gets inserted when selected
        },
        lookup: "value", // Attribute used for lookup
        menuItemTemplate: function (item) {
            return `${item.original.value}`; // How items appear in the dropdown
        },
    });
    // Attach Tribute.js to the textarea
    tribute.attach($textarea[0]);
}
function stripHtml(content) {
    // Replace <a> tags with the inner text, but only add '@' if the inner text doesn't already start with it
    return content.replace(
        /<a [^>]*class=["'][^"']*mention[^"']*["'][^>]*>([^<]+)<\/a>/g,
        function (match, innerText) {
            // Check if the innerText already starts with @
            return innerText.startsWith("@") ? innerText : "@" + innerText;
        },
    );
}
// Recuring Task Settings
$(document).ready(function () {
    // Toggle Recurring Task Settings Visibility
    $("#recurring-task-switch").on("change", function () {
        const isChecked = $(this).is(":checked");
        $("#recurring-task-settings").toggleClass("d-none", !isChecked);
        // Toggle required attributes based on switch state
        $(
            "#recurrence-frequency, #recurrence-starts-from, #recurrence-occurrences",
        ).prop("required", isChecked);
        // Trigger change event to update dependent fields
        if (isChecked) {
            $("#recurrence-frequency").trigger("change");
        }
    });
    // Dynamic Display Based on Recurrence Frequency Type
    $("#recurrence-frequency").on("change", function () {
        const value = $(this).val();
        // Hide all frequency-specific groups
        $(
            "#recurrence-day-of-week-group, #recurrence-day-of-month-group, #recurrence-month-of-year-group",
        ).addClass("d-none");
        // Show appropriate groups based on selected frequency
        switch (value) {
            case "weekly":
                $("#recurrence-day-of-week-group").removeClass("d-none");
                break;
            case "monthly":
                $("#recurrence-day-of-month-group").removeClass("d-none");
                break;
            case "yearly":
                $(
                    "#recurrence-day-of-month-group, #recurrence-month-of-year-group",
                ).removeClass("d-none");
                break;
        }
    });
    // Initialize Settings on Page Load
    function initializeSettings() {
        const isRecurring = $("#recurring-task-switch").is(":checked");
        // Toggle visibility of recurring task settings
        $("#recurring-task-settings").toggleClass("d-none", !isRecurring);
        // If recurring is enabled, show appropriate fields based on frequency
        if (isRecurring) {
            $("#recurrence-frequency").trigger("change");
        }
    }
    // Run initialization on page load
    initializeSettings();
});
//Edit Recuring Task Settings
$(document).ready(function () {
    // Toggle Recurring Task Settings Visibility
    $("#edit-recurring-task-switch").on("change", function () {
        const isChecked = $(this).is(":checked");
        $("#edit-recurring-task-settings").toggleClass("d-none", !isChecked);
        // Toggle required attributes based on switch state
        $(
            "#edit-recurrence-frequency, #edit-recurrence-starts-from, #edit-recurrence-occurrences",
        ).prop("required", isChecked);
        // Trigger change event to update dependent fields
        if (isChecked) {
            $("#edit-recurrence-frequency").trigger("change");
        }
    });
    // Dynamic Display Based on Recurrence Frequency Type
    $("#edit-recurrence-frequency").on("change", function () {
        const value = $(this).val();
        // Hide all frequency-specific groups
        $(
            "#edit-recurrence-day-of-week-group, #edit-recurrence-day-of-month-group, #edit-recurrence-month-of-year-group",
        ).addClass("d-none");
        // Show appropriate groups based on selected frequency
        switch (value) {
            case "weekly":
                $("#edit-recurrence-day-of-week-group").removeClass("d-none");
                break;
            case "monthly":
                $("#edit-recurrence-day-of-month-group").removeClass("d-none");
                break;
            case "yearly":
                $(
                    "#edit-recurrence-day-of-month-group, #edit-recurrence-month-of-year-group",
                ).removeClass("d-none");
                break;
        }
    });
    // Initialize Settings on Page Load
    function initializeSettings() {
        const isRecurring = $("#edit-recurring-task-switch").is(":checked");
        // Toggle visibility of recurring task settings
        $("#edit-recurring-task-settings").toggleClass("d-none", !isRecurring);
        // If recurring is enabled, show appropriate fields based on frequency
        if (isRecurring) {
            $("#edit-recurrence-frequency").trigger("change");
        }
    }
    // Run initialization on page load
    initializeSettings();
});
// Task Reminder Settings
$(document).ready(function () {
    // Toggle Reminder Settings Visibility
    $("#reminder-switch").on("change", function () {
        if ($(this).is(":checked")) {
            $("#reminder-settings").removeClass("d-none");
            $("#time-of-day").prop("required", true);
        } else {
            $("#reminder-settings").addClass("d-none");
            $("#time-of-day").prop("required", false);
        }
    });
    // Dynamic Display Based on Frequency Type
    $("#frequency-type").on("change", function () {
        const value = $(this).val();
        $("#day-of-week-group").addClass("d-none");
        $("#day-of-month-group").addClass("d-none");
        if (value === "weekly") {
            $("#day-of-week-group").removeClass("d-none");
        } else if (value === "monthly") {
            $("#day-of-month-group").removeClass("d-none");
        }
    });
    $("#edit-reminder-switch").on("change", function () {
        if ($(this).is(":checked")) {
            $("#edit-reminder-settings").removeClass("d-none");
        } else {
            $("#edit-reminder-settings").addClass("d-none");
        }
    });
    $("#edit-frequency-type").on("change", function () {
        const value = $(this).val();
        $("#edit-day-of-week-group").addClass("d-none");
        $("#edit-day-of-month-group").addClass("d-none");
        if (value === "weekly") {
            $("#edit-day-of-week-group").removeClass("d-none");
        } else if (value === "monthly") {
            $("#edit-day-of-month-group").removeClass("d-none");
        }
    });
    $("#edit-todo-reminder-switch").on("change", function () {
        if ($(this).is(":checked")) {
            $("#edit-todo-reminder-settings").removeClass("d-none");
        } else {
            $("#edit-todo-reminder-settings").addClass("d-none");
        }
    });
    $("#edit-todo-frequency-type").on("change", function () {
        const value = $(this).val();
        $("#edit-todo-day-of-week-group").addClass("d-none");
        $("#edit-todo-day-of-month-group").addClass("d-none");
        if (value === "weekly") {
            $("#edit-todo-day-of-week-group").removeClass("d-none");
        } else if (value === "monthly") {
            $("#edit-todo-day-of-month-group").removeClass("d-none");
        }
    });
});
// Taks List Selection
$(document).ready(function () {
    var taskListSelect = document.getElementById("task_list");
    var taskListTomSelect = null;
    if (taskListSelect) {
        taskListTomSelect = new TomSelect(taskListSelect, {
            valueField: "id",
            labelField: "text",
            searchField: "text",
            placeholder: "Select a task list",
            plugins: ["clear_button"],
            preload: true,
            load: function (query, callback) {
                var projectId = $('.selectTaskProject[name="project"]').val() || $('.selectTaskProject[name="project_id"]').val();
                if (!projectId) {
                    callback([]);
                    return;
                }
                fetch(`${baseUrl}/task-lists/search?search=${encodeURIComponent(query)}&project_id=${projectId}`)
                    .then((res) => res.json())
                    .then((data) => {
                        callback(
                            data.map((item) => ({
                                id: item.id,
                                text: item.name,
                            })),
                        );
                    })
                    .catch(() => callback([]));
            }
        });
        
        if (!$('.selectTaskProject[name="project"]').val() && !$('.selectTaskProject[name="project_id"]').val()) {
            taskListTomSelect.disable();
        }
        
        $('.selectTaskProject[name="project"], .selectTaskProject[name="project_id"]').on("change", function () {
            var projectId = $(this).val();
            if (projectId) {
                taskListTomSelect.enable();
                taskListTomSelect.clear();
                taskListTomSelect.clearOptions();
                taskListTomSelect.load("");
            } else {
                taskListTomSelect.disable();
                taskListTomSelect.clear();
                taskListTomSelect.clearOptions();
            }
        });
    }
});
// Show the Active Tab
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (button) {
    button.addEventListener("click", function () {
        // Check if the clicked button has the "list-button" or "calendar-button" class
        if (
            this.classList.contains("list-button") ||
            this.classList.contains("calendar-button")
        ) {
            // Remove bg-primary and text-white from "List" and "Calendar" buttons only
            document
                .querySelectorAll(".list-button, .calendar-button")
                .forEach(function (specificButton) {
                    specificButton.classList.remove("bg-primary", "text-white");
                });
            // Add bg-primary and text-white to the clicked button
            this.classList.add("bg-primary", "text-white");
        }
        // Handle nested tabs
        const parentPane = document.querySelector(this.dataset.bsTarget);
        if (parentPane) {
            parentPane
                .querySelectorAll(".list-button, .calendar-button")
                .forEach(function (subTab) {
                    subTab.classList.remove("bg-primary", "text-white");
                });
            const activeSubTab = parentPane.querySelector(
                ".list-button.active, .calendar-button.active",
            );
            if (activeSubTab) {
                activeSubTab.classList.add("bg-primary", "text-white");
            }
        }
    });
});

$(document).on("click", ".edit-task-list", function () {
    var id = $(this).data("id");
    var routePrefix = $("#table").data("routePrefix");
    $("#edit_task_list_modal").modal("show");
    $.ajax({
        url: "/task-lists/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            $("#task_list_id").val(response.task_list.id);
            $("#task_list_project").val(response.task_list.project.title);
            $("#task_list_name").val(response.task_list.name);
            var projectData = {
                id: response.task_list.project.id, // Ensure this matches your project's ID field
                text: response.task_list.project.title, // Ensure this matches your project's title field
            };
            var $projectSelect = $("#task_list_project_id");
            $projectSelect
                .empty()
                .append(
                    new Option(projectData.text, projectData.id, true, true),
                )
                .trigger("change");
        },
    });
});
// Edit Lead Sources
$(document).on("click", ".edit-lead-source", function () {
    var id = $(this).data("id");
    $("#edit_lead_source_modal").modal("show");
    $.ajax({
        url: "/lead-sources/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            $("#lead_source_id").val(response.lead_source.id);
            $("#lead_source_name").val(response.lead_source.name);
        },
    });
});
$(document).on("click", ".edit-lead-follow-up", function () {
    var id = $(this).data("id");
    $("#edit_lead_follow_up_modal").modal("show");
    $.ajax({
        url: "/leads/follow-up/get/" + id,
        type: "GET",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').val(),
        },
        dataType: "json",
        success: function (response) {
            // Prefill the form with data from the response
            var followUp = response.follow_up;
            var lead = response.follow_up.lead;
            // Prefill ID (hidden field)
            $('input[name="id"]').val(followUp.id);
            // Prefill Assigned To field (select)
            var dropdownSelector = $('select[name="assigned_to"]');
            if (dropdownSelector.length) {
                var newItem = response.follow_up.assigned_to;
                var newOption = $("<option></option>")
                    .attr("value", newItem.id)
                    .attr("selected", true)
                    .text(newItem.first_name + " " + newItem.last_name);
                dropdownSelector.append(newOption).trigger("change");
            }
            // Prefill Follow Up Date
            var formatted = moment(followUp.follow_up_at).format(
                "YYYY-MM-DDTHH:mm",
            );
            $('input[name="follow_up_at"]').val(formatted);
            // Prefill Follow Up Type
            $('select[name="type"]').val(followUp.type);
            // Prefill Status field
            $('select[name="status"]').val(followUp.status);
            // Prefill Note field (make sure to decode HTML entities if needed)
            $("#edit_follow_up_note").val(followUp.note);
            // Optionally, you can populate any additional lead-related information if needed
            // Example: Pre-fill any lead-specific info in the form, if required
        },
        error: function (xhr, status, error) {
            console.error("Error:", error);
        },
    });
});
$(document).ready(function () {
    if ($("textarea#follow_up_note,textarea#edit_follow_up_note").length > 0) {
        $("textarea#follow_up_note,textarea#edit_follow_up_note").tinymce({
            height: 300,
            menubar: true,
        });
    }
});
$(document).ready(function () {
    // Use event delegation to handle clicks on dynamically loaded buttons
    $(document).on("click", ".convert-to-client", function (e) {
        e.preventDefault();
        var leadId = $(this).data("id");
        var csrfToken = $('meta[name="csrf-token"]').attr("content");
        if (!leadId) {
            toastr.error("Invalid lead ID.");
            return;
        }
        $.ajax({
            url: "/leads/" + leadId + "/convert-to-client",
            type: "POST",
            data: {
                _token: csrfToken,
                lead_id: leadId,
            },
            success: handleConvertSuccess,
            error: handleConvertError,
        });
    });
    function handleConvertSuccess(response) {
        if (response.error || response.status === false) {
            toastr.error(response.message || "Conversion failed.");
        } else {
            toastr.success(response.message || "Lead successfully converted.");
            setTimeout(
                () => {
                    location.reload();
                },
                parseFloat(toastTimeOut) * 1000,
            );
        }
    }
    function handleConvertError(xhr) {
        let message = "Something went wrong.";
        if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }
        toastr.error(message);
        setTimeout(
            () => {
                location.reload();
            },
            parseFloat(toastTimeOut) * 1000,
        );
    }
});
function setupScopedAIGenerator(generateBtnSelector, options = {}) {
    const defaultOptions = {
        promptSelector: ".ai-title",
        customPromptSelector: ".ai-custom-prompt",
        outputSelector: ".ai-output",
        loaderSelector: ".ai-loader",
        customPromptSwitchSelector: ".enableCustomPrompt",
        customPromptContainerSelector: ".customPromptContainer",
        endpoint: "/ai/generate-description",
    };
    const settings = { ...defaultOptions, ...options };
    // Toggle custom prompt textarea visibility using Bootstrap's d-none
    $(document).on("change", settings.customPromptSwitchSelector, function () {
        const isChecked = $(this).is(":checked");
        const $container = $(settings.customPromptContainerSelector);
        if (isChecked) {
            $container.removeClass("d-none");
        } else {
            $container.addClass("d-none");
        }
    });
    $(document).on("click", generateBtnSelector, function () {
        const $btn = $(this);
        const $scope = $btn.closest(".ai-wrapper");
        const useCustomPrompt = $scope
            .find(settings.customPromptSwitchSelector)
            .is(":checked");
        let prompt;
        if (useCustomPrompt) {
            prompt = $scope.find(settings.customPromptSelector).val();
            if (!prompt) {
                toastr.error(label_enter_custom_prompt_first);
                return;
            }
        } else {
            prompt = $scope.find(settings.promptSelector).val();
            if (!prompt) {
                toastr.error(label_enter_project_title_first);
                return;
            }
        }
        const $output = $scope.find(settings.outputSelector);
        const existingDescription = $output.val(); // Get the existing description
        const $loader = $scope.find(settings.loaderSelector);
        $btn.prop("disabled", true);
        if ($loader.length) $loader.removeClass("d-none");
        $.ajax({
            url: settings.endpoint,
            method: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                prompt: prompt,
                isCustomPrompt: useCustomPrompt,
                existingDescription: existingDescription, // Send existing description to backend
            },
            success: function (response) {
                if (response.error) {
                    toastr.error(response.message);
                } else {
                    toastr.success(response.message);
                    $output.val(response.description);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function (key, value) {
                        toastr.error(value[0]);
                    });
                } else {
                    toastr.error(label_something_went_wrong);
                }
            },
            complete: function () {
                $btn.prop("disabled", false);
                if ($loader.length) $loader.addClass("d-none");
            },
        });
    });
}
// Initialize
$(document).ready(function () {
    setupScopedAIGenerator(".generate-ai");
});
$(document).ready(function () {
    // Listen for change events on the radio buttons with the class 'is_active_ai_model'
    $(".is_active_ai_model").on("change", function () {
        // When a radio button is selected, uncheck all others
        $(".is_active_ai_model").not(this).prop("checked", false);
    });
});
$(document).ready(function () {
    // Update temperature value displays when sliders change
    $("#openrouter_temperature").on("input", function () {
        $("#openrouter_temperature_value").text($(this).val());
    });
    $("#gemini_temperature").on("input", function () {
        $("#gemini_temperature_value").text($(this).val());
    });
    // Initialize Bootstrap tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
if (document.getElementById("install-plugin-dropzone")) {
    // Initialize Dropzone for plugin installation
    if (!$("#install-plugin").hasClass("dropzone")) {
        var systemDropzone = new Dropzone("#install-plugin-dropzone", {
            url: $("#install-plugin").attr("action"),
            paramName: "plugin_zip",
            autoProcessQueue: false,
            parallelUploads: 1,
            maxFiles: 1,
            acceptedFiles: ".zip",
            timeout: 360000,
            autoDiscover: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), // Pass the CSRF token as a header
            },
            addRemoveLinks: true,
            dictRemoveFile: "x",
            dictMaxFilesExceeded: label_only_one_file_can_be_uploaded_at_a_time,
            dictResponseError: "Error",
            uploadMultiple: true,
            dictDefaultMessage:
                '<p><input type="button" value="' +
                label_select +
                '" class="btn btn-primary" /><br> ' +
                label_or +
                " <br> " +
                "Drag and drop the ZIP file here" +
                "</p>",
        });
        systemDropzone.on("addedfile", function (file) {
            var i = 0;
            if (this.files.length) {
                var _i, _len;
                for (_i = 0, _len = this.files.length; _i < _len - 1; _i++) {
                    if (
                        this.files[_i].name === file.name &&
                        this.files[_i].size === file.size &&
                        this.files[_i].lastModifiedDate.toString() ===
                            file.lastModifiedDate.toString()
                    ) {
                        this.removeFile(file);
                        i++;
                    }
                }
            }
        });
        systemDropzone.on("error", function (file, response) {
            // Remove the file
            systemDropzone.removeFile(file);
            // Re-enable the submit button and reset its text
            $("#install_plugin_btn")
                .attr("disabled", false)
                .text(label_update_the_system);
            var errorMessage = label_err_try_again;
            if (typeof response === "string") {
                errorMessage = response; // Use the response text if it's a string
            } else if (response.message) {
                errorMessage = response.message; // Use response.message if it exists
            }
            toastr.error(errorMessage);
        });
        systemDropzone.on("success", function (file, response) {
            $("#install_plugin_btn")
                .attr("disabled", false)
                .text(label_update_the_system);
            if (response.error) {
                // Remove the file
                systemDropzone.removeFile(file);
                // Re-enable the submit button and reset its text
                // Show the error message
                toastr.error(response.message);
            } else {
                // Show success message
                toastr.success(response.message);
                setTimeout(
                    function () {
                        location.reload();
                    },
                    parseFloat(toastTimeOut) * 1000,
                );
            }
        });
        $("#install_plugin_btn").on("click", function (e) {
            e.preventDefault();
            var queuedFiles = systemDropzone.getQueuedFiles();
            if (queuedFiles.length > 0) {
                $("#install_plugin_btn")
                    .attr("disabled", true)
                    .text(label_please_wait);
                systemDropzone.processQueue();
            } else {
                toastr.error(label_no_files_chosen);
            }
        });
    }
}
/**
 * Enhanced Form Submission Handler with Smart Modal/Offcanvas Management
 *
 * This handler manages form submissions across the application with intelligent overlay closing,
 * error handling, and dependent property management. Designed for systems transitioning from
 * modals to offcanvas for better UX while maintaining backward compatibility.
 *
 *
 * FEATURES:
 * ========
 * • Smart Context Detection: Automatically detects modal vs offcanvas forms
 * • Nested Overlay Support: Handles Project (offcanvas) + Dependencies (modal) scenarios
 * • Intelligent Closing: Only closes relevant overlays based on form context
 * • Enhanced Error Handling: Context-aware error display and robust server error parsing
 * • Dependent Property Management: Seamless dropdown refresh for related entities
 * • Accessibility Compliant: Proper ARIA attributes and focus management
 * • Multi-Entity Support: Handles various entity types (projects, status, priority, tags, etc.)
 *
 * SUPPORTED SCENARIOS:
 * ==================
 * 1. Main Entity Forms (Projects, Users, etc.):
 *    - Opens in offcanvas
 *    - Closes offcanvas + any modals on success
 *    - Redirects or reloads page
 *
 * 2. Dependent Property Forms (Status, Priority, Tags):
 *    - Opens in modal (even when parent is offcanvas)
 *    - Only closes the modal on success
 *    - Keeps parent offcanvas open
 *    - Auto-refreshes parent form dropdowns
 *
 * 3. Independent Entity Forms:
 *    - Can be in modal or offcanvas
 *    - Closes only its container
 *
 * FORM MARKUP REQUIREMENTS:
 * ========================
 * 1. All forms must have class: "new-form-submit-event"
 * 2. Submit button must have id: "submit_btn"
 * 3. CSRF token via meta tag: <meta name="csrf-token" content="...">
 *
 * DEPENDENT PROPERTY DETECTION:
 * ============================
 * Mark dependent properties using ANY of these methods:
 * • Form class: <form class="new-form-submit-event dependent-property-form">
 * • Hidden input: <input type="hidden" name="is_dependent_property" value="1">
 * • Modal class: <div class="modal dependent-property-modal">
 * • Offcanvas class: <div class="offcanvas dependent-property-offcanvas">
 *
 * SERVER RESPONSE FORMAT:
 * ======================
 * Success Response:
 * {
 *   "error": false,
 *   "message": "Success message",
 *   "type": "status|priority|tag|etc", // For dropdown refresh
 *   "data": {
 *     "id": 123,
 *     "name": "New Item Name"
 *   }
 * }
 *
 * Validation Error Response (422):
 * {
 *   "errors": {
 *     "field_name": ["Error message 1", "Error message 2"]
 *   },
 *   "showInModal": true // Optional: show errors in modal/offcanvas container
 * }
 *
 * SPECIAL FORM INPUTS:
 * ===================
 * • redirect_url: Custom redirect after success
 * • table: Table ID for bootstrap-table refresh (default: "table")
 * • dnr: "Do Not Redirect" - refreshes table instead of redirecting
 * • is_dependent_property: Marks form as dependent property
 * • is_encoded: Indicates content field is base64 encoded
 *
 * SUPPORTED OVERLAYS:
 * ==================
 * • Bootstrap 5 Modals (.modal)
 * • Bootstrap 5 Offcanvas (.offcanvas)
 * • Backward compatibility with Bootstrap 4 modals
 *
 * SUPPORTED PLUGINS:
 * =================
 * • Select2 dropdowns
 * • Tinymce editors
 * • Dropzone file uploads
 * • Bootstrap Table
 * • Toastr notifications
 *
 * ERROR HANDLING:
 * ==============
 * • Field-level validation errors with visual indicators
 * • Database connection and SQL error parsing
 * • Graceful fallbacks for plugin failures
 * • Automatic focus on first error field
 * • Enhanced error messages for common database issues
 *
 * DROPDOWN REFRESH:
 * ================
 * Automatically refreshes parent form dropdowns when dependent properties are created.
 * Supports: status, priority, tags, categories, departments, clients, etc.
 *
 * ACCESSIBILITY:
 * =============
 * • Proper ARIA attributes management
 * • Keyboard navigation support
 * • Screen reader friendly error messages
 * • Focus management for error fields
 *
 * BROWSER SUPPORT:
 * ===============
 * • Modern browsers with ES6+ support
 * • jQuery 3.x required
 * • Bootstrap 5.x recommended (Bootstrap 4.x compatible)
 *
 * TROUBLESHOOTING:
 * ===============
 * • Check browser console for detailed error logs
 * • Ensure CSRF token is properly set
 * • Verify form has required classes and IDs
 * • Check server response format matches expected structure
 *
 * @example
 * // Basic form setup
 * <form class="new-form-submit-event" action="/projects/store" method="POST">
 *   <input type="hidden" name="_token" value="...">
 *   <input type="hidden" name="table" value="projects-table">
 *   <!-- form fields -->
 *   <button type="submit" id="submit_btn">Save Project</button>
 * </form>
 *
 * @example
 * // Dependent property form
 * <form class="new-form-submit-event dependent-property-form" action="/status/store" method="POST">
 *   <input type="hidden" name="_token" value="...">
 *   <input type="hidden" name="dnr" value="1">
 *   <!-- form fields -->
 *   <button type="submit" id="submit_btn">Save Status</button>
 * </form>
 */
$(document).on("submit", ".new-form-submit-event", function (e) {
    e.preventDefault();
    if ($("#net_payable").length > 0) {
        $("#net_pay").val($("#net_payable").text());
    }
    var formData = new FormData(this);
    // Encode HTML content for template saving
    if (
        $(this).attr("action").includes("store_template") ||
        $(this).attr("action").includes("/email-templates/store") ||
        $(this).attr("action").includes("email-templates/update") ||
        $(this).attr("action").includes("/emails/store") ||
        $(this).attr("action").includes("/emails/preview")
    ) {
        var contentField = $(this).find(
            'textarea[name="content"], input[name="content"]',
        );
        if (contentField.length > 0) {
            formData.delete("content");
            formData.append("content", btoa(contentField.val()));
            formData.append("is_encoded", "1");
        }
    }
    var currentForm = $(this);
    var submit_btn = currentForm.find("#submit_btn");
    var button_text = submit_btn.html() || submit_btn.val();
    var redirect_url =
        currentForm.find('input[name="redirect_url"]').val() || "";
    var tableID = currentForm.find('input[name="table"]').val() || "table";
    // Enhanced overlay type detection
    var isInModal = currentForm.closest(".modal").length > 0;
    var isInOffcanvas = currentForm.closest(".offcanvas").length > 0;
    var parentModal = isInModal ? currentForm.closest(".modal") : null;
    var parentOffcanvas = isInOffcanvas
        ? currentForm.closest(".offcanvas")
        : null;
    // Enhanced dependent property detection
    var isDependentProperty =
        currentForm.hasClass("dependent-property-form") ||
        currentForm.find('input[name="is_dependent_property"]').length > 0 ||
        currentForm.closest(".modal").hasClass("dependent-property-modal") ||
        currentForm
            .closest(".offcanvas")
            .hasClass("dependent-property-offcanvas");
    // Enhanced contract dropzone handling for both modal and offcanvas
    if (
        currentForm.closest("#edit_contract_offcanvas, #edit_contract_modal")
            .length > 0 &&
        typeof Dropzone !== "undefined" &&
        Dropzone.instances.length > 0
    ) {
        try {
            var dropzoneInstance = Dropzone.forElement("#contract-dropzone");
            if (dropzoneInstance) {
                dropzoneInstance.getAcceptedFiles().forEach(function (file) {
                    formData.append("signed_pdf", file);
                });
            }
        } catch (error) {
            // console.warn('Dropzone error:', error);
        }
    }
    $.ajax({
        type: "POST",
        url: currentForm.attr("action"),
        data: formData,
        headers: {
            "X-CSRF-TOKEN":
                $('meta[name="csrf-token"]').attr("content") ||
                $('input[name="_token"]').val(),
        },
        beforeSend: function () {
            submit_btn.html(label_please_wait).prop("disabled", true);
        },
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (result) {
            submit_btn.html(button_text).prop("disabled", false);
            if (result.error) {
                toastr.error(result.message);
                return;
            }
            // Smart overlay closing
            handleOverlayClosing(
                isDependentProperty,
                parentModal,
                parentOffcanvas,
            );
            // Handle success scenarios - REORDERED to check DNR first
            if (currentForm.find('input[name="dnr"]').length > 0) {
                // DNR scenario - refresh table and reset form
                if ($("#" + tableID).length) {
                    $("#" + tableID).bootstrapTable("refresh");
                }
                resetForm(currentForm);
                toastr.success(result.message || "Success");
                // Always try to refresh parent dropdowns for DNR forms in modal/offcanvas
                if (
                    (isInModal || isInOffcanvas) &&
                    result.type &&
                    result.data
                ) {
                    refreshParentFormDropdowns(result);
                }
                // Also refresh for dependent properties
                if (isDependentProperty) {
                    refreshParentFormDropdowns(result);
                }
            } else if ($(".empty-state").length > 0) {
                toastr.success(result.message || "Success");
                setTimeout(handleRedirection, parseFloat(toastTimeOut) * 1000);
            } else {
                toastr.success(result.message || "Success");
                // Always try to refresh parent dropdowns if we're in a modal/offcanvas
                if (
                    (isInModal || isInOffcanvas) &&
                    result.type &&
                    result.data
                ) {
                    refreshParentFormDropdowns(result);
                }
                setTimeout(handleRedirection, parseFloat(toastTimeOut) * 1000);
            }
            // Clear all error messages
            currentForm.find(".error-message").remove();
        },
        error: function (xhr) {
            submit_btn.html(button_text).prop("disabled", false);
            if (xhr.status === 422) {
                handleValidationErrors(xhr, currentForm, isInOffcanvas);
            } else {
                handleServerErrors(xhr);
            }
        },
    });
    // ✅ ENHANCED OVERLAY CLOSING LOGIC
    function handleOverlayClosing(
        isDependentProperty,
        parentModal,
        parentOffcanvas,
    ) {
        try {
            if (isDependentProperty) {
                // For dependent properties: only close the immediate modal
                if (parentModal) {
                    closeSpecificModal(parentModal);
                }
                // Keep offcanvas open for dependent properties
            } else if (parentOffcanvas) {
                // For main entities in offcanvas: close offcanvas and any modals
                closeSpecificOffcanvas(parentOffcanvas);
                closeAllModals();
            } else if (parentModal) {
                // For independent entities in modals: close only the modal
                closeSpecificModal(parentModal);
            } else {
                // Fallback: close everything
                closeAllOverlays();
            }
        } catch (error) {
            // console.warn('Error in smart overlay closing:', error);
            closeAllOverlays();
        }
    }
    // ✅ ENHANCED MODAL CLOSING
    function closeSpecificModal(modalElement) {
        try {
            let modalInstance = bootstrap.Modal.getInstance(modalElement[0]);
            if (modalInstance) {
                modalInstance.hide();
            } else {
                modalElement.modal("hide");
            }
        } catch (error) {
            // console.warn('Error closing specific modal:', error);
            modalElement.removeClass("show").css("display", "none");
            modalElement.attr("aria-hidden", "true").removeAttr("aria-modal");
        }
    }
    // ✅ ENHANCED OFFCANVAS CLOSING
    function closeSpecificOffcanvas(offcanvasElement) {
        try {
            let offcanvasInstance = bootstrap.Offcanvas.getInstance(
                offcanvasElement[0],
            );
            if (offcanvasInstance) {
                offcanvasInstance.hide();
            } else {
                offcanvasElement
                    .removeClass("show")
                    .css("visibility", "hidden");
                $("body").removeClass("offcanvas-open");
            }
        } catch (error) {
            // console.warn('Error closing specific offcanvas:', error);
            offcanvasElement.removeClass("show").css("visibility", "hidden");
            offcanvasElement.attr("aria-hidden", "true");
            $("body").removeClass("offcanvas-open");
        }
    }
    // ✅ CLOSE ALL MODALS
    function closeAllModals() {
        try {
            $(".modal.show").each(function () {
                let modalInstance = bootstrap.Modal.getInstance(this);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    $(this).modal("hide");
                }
            });
        } catch (error) {
            // console.warn('Error closing all modals:', error);
            $(".modal").removeClass("show").css("display", "none");
        }
    }
    // ✅ ENHANCED CLOSE ALL OVERLAYS
    function closeAllOverlays() {
        try {
            // Close modals
            $(".modal.show").each(function () {
                let modalInstance = bootstrap.Modal.getInstance(this);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    $(this).modal("hide");
                }
            });
            // Close offcanvas
            $(".offcanvas.show").each(function () {
                let offcanvasInstance = bootstrap.Offcanvas.getInstance(this);
                if (offcanvasInstance) {
                    offcanvasInstance.hide();
                } else {
                    $(this).removeClass("show").css("visibility", "hidden");
                }
            });
            // Cleanup backdrops and body classes after animation
            setTimeout(function () {
                $(".modal-backdrop, .offcanvas-backdrop").remove();
                $("body").removeClass("modal-open offcanvas-open");
            }, 300);
        } catch (error) {
            // console.warn('Error in closeAllOverlays:', error);
            // Force cleanup
            $(".modal, .offcanvas").removeClass("show");
            $(".modal-backdrop, .offcanvas-backdrop").remove();
            $("body").removeClass("modal-open offcanvas-open");
        }
    }
    // ✅ ENHANCED FORM RESET
    function resetForm(form) {
        try {
            form[0].reset();
            form.find("select").val(null).trigger("change");
            // Handle Select2 dropdowns
            form.find(".select2").each(function () {
                $(this).val(null).trigger("change");
            });
            // Handle specific elements
            if ($("#partialLeave").length) {
                $("#partialLeave").trigger("change");
            }
            if (typeof resetDateFields === "function") {
                resetDateFields(form);
            }
            // Clear any summernote editors
            form.find(".summernote").each(function () {
                if ($(this).summernote) {
                    $(this).summernote("code", "");
                }
            });
        } catch (error) {
            // console.warn('Error resetting form:', error);
        }
    }
    // ✅ ENHANCED VALIDATION ERROR HANDLING
    function handleValidationErrors(xhr, currentForm, isInOffcanvas) {
        toastr.error(label_please_correct_errors);
        var errors = xhr.responseJSON.errors || {};
        // Show errors in overlay-specific container
        if (xhr.responseJSON.showInModal) {
            var errorContainerId = isInOffcanvas
                ? "#errorOffcanvasContent"
                : "#errorModalContent";
            var errorBodyId = isInOffcanvas
                ? "#errorOffcanvasBody"
                : "#errorModalBody";
            let errorHtmlBody = "";
            $.each(errors, function (field, messages) {
                errorHtmlBody += `<div class="mb-2"><strong class="text-capitalize">${field.replace(/_/g, " ")}</strong><ul class="mb-0 mt-1">`;
                $.each(messages, function (_, msg) {
                    errorHtmlBody += `<li>${msg}</li>`;
                });
                errorHtmlBody += `</ul></div>`;
            });
            $(errorContainerId).html(errorHtmlBody);
            $(errorBodyId).removeClass("d-none");
        }
        // Render field-specific errors with enhanced targeting
        var inputFields = $(
            currentForm
                .find("input[name], select[name], textarea[name]")
                .get()
                .reverse(),
        );
        var firstErrorField = null;
        inputFields.each(function () {
            var input = $(this);
            var fieldName = input.attr("name");
            var errorMessage = errors[fieldName];
            var parent = input.closest(
                ".form-group, .input-group, .form-control, .form-select, .mb-3, .mb-2",
            );
            // Remove existing error messages
            parent.find(".text-danger.error-message").remove();
            if (errorMessage) {
                if (!firstErrorField) firstErrorField = input;
                var msg = Array.isArray(errorMessage)
                    ? errorMessage[0]
                    : errorMessage;
                var errorEl = $(
                    '<span class="text-danger error-message d-block mt-1 small"></span>',
                ).text(msg);
                // Enhanced error placement logic
                if (input.hasClass("select2-hidden-accessible")) {
                    input.siblings(".select2").after(errorEl);
                } else if (input.is("textarea#privacy_policy")) {
                    input.parent().find(".mt-2").first().before(errorEl);
                } else if (input.closest(".input-group").length) {
                    input.closest(".input-group").after(errorEl);
                } else {
                    input.after(errorEl);
                }
            }
        });
        // Scroll to first error field
        if (firstErrorField) {
            setTimeout(function () {
                firstErrorField[0].scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
                firstErrorField.focus();
            }, 100);
        }
    }
    // ✅ ENHANCED SERVER ERROR HANDLING
    function handleServerErrors(xhr) {
        let msg = xhr.responseJSON?.message || "An unexpected error occurred.";
        // Enhanced error message parsing
        if (xhr.responseJSON?.exception) {
            // Database connection errors
            let match = msg.match(/Access denied for user '([^']+)'@/);
            if (match) {
                msg = `Database access denied for user '${match[1]}'. Please check your database credentials.`;
            }
            // SQL State errors
            else if (/SQLSTATE\[(\w+)\]/.test(msg)) {
                let sqlMatch = msg.match(
                    /SQLSTATE\[(\w+)\]: (.+?)(?:\s\(SQL:|$)/,
                );
                if (sqlMatch) {
                    msg = `Database Error [${sqlMatch[1]}]: ${sqlMatch[2]}`;
                }
            }
            // Connection timeout errors
            else if (msg.includes("Connection timed out")) {
                msg = "Database connection timed out. Please try again.";
            }
            // General SQL errors
            else if (msg.includes("Query Exception")) {
                msg =
                    "Database query error. Please contact support if this persists.";
            }
        }
        toastr.error(msg);
    }
    // ✅ ENHANCED DROPDOWN REFRESH WITH MORE ENTITY TYPES AND COMPREHENSIVE DEBUG
    function refreshParentFormDropdowns(result) {
        if (
            !result.data ||
            !result.data.id ||
            !result.data.name ||
            !result.type
        ) {
            return;
        }
        // Enhanced mapping for more entity types
        let selectMap = {
            status: [
                "status_id",
                "project_status_id",
                "task_status_id",
                "status",
            ],
            priority: [
                "priority_id",
                "project_priority_id",
                "task_priority_id",
                "priority",
            ],
            tag: ["tag_ids[]", "tags[]", "tag_id"],
            contract_type: ["contract_type_id", "contract_type"],
            payment_method: ["payment_method_id"],
            allowance: ["allowance_id", "allowance_ids[]"],
            deduction: ["deduction_id", "deduction_ids[]"],
            item: ["item_id", "product_id"],
            category: ["category_id"],
            department: ["department_id"],
            designation: ["designation_id"],
            client: ["client_id", "customer_id"],
            project: ["project_id"],
            workspace: ["workspace_id"],
        };
        let targetSelects = selectMap[result.type] || [];
        if (!targetSelects.length) {
            return;
        }
        // Look for selects in the active offcanvas first, then modal
        let activeContainer = $(".offcanvas.show");
        if (!activeContainer.length) {
            activeContainer = $(".modal.show").not("#create_status_modal"); // Exclude the status creation modal itself
        }
        if (activeContainer.length) {
            console.log(
                "All selects in container:",
                activeContainer
                    .find("select")
                    .map(function () {
                        return {
                            name: this.name || "NO_NAME",
                            id: this.id || "NO_ID",
                            classes: this.className,
                        };
                    })
                    .get(),
            );
            let foundAndUpdated = false;
            targetSelects.forEach(function (selectName) {
                let selector = activeContainer.find(
                    `select[name="${selectName}"]`,
                );
                if (selector.length) {
                    foundAndUpdated = true;
                    // Check if option already exists
                    if (
                        selector.find(`option[value="${result.data.id}"]`)
                            .length === 0
                    ) {
                        let newOption = new Option(
                            result.data.name,
                            result.data.id,
                            true,
                            true,
                        );

                        // Attach color data to the option
                        if (result.priority?.color || result.status?.color) {
                            $(newOption).data(
                                "color",
                                result.priority?.color || result.status?.color,
                            );
                            $(newOption).attr(
                                "data-color",
                                result.priority?.color || result.status?.color,
                            ); // Ensure data attribute is set
                        }
                        selector.append(newOption);
                    } else {
                        selector.val(result.data.id);
                    }
                    // Trigger change event for Select2 and other plugins
                    selector.trigger("change");
                    // Handle Select2 specifically
                    if (selector.hasClass("select2-hidden-accessible")) {
                        selector.trigger("change.select2");
                        // Force Select2 to recognize the new option
                        setTimeout(function () {
                            selector
                                .val(result.data.id)
                                .trigger("change.select2");
                        }, 100);
                    }
                    // Handle Ajax Select2 dropdowns
                    if (
                        selector.data("select2") &&
                        selector.data("select2").options &&
                        selector.data("select2").options.ajax
                    ) {
                        selector.trigger({
                            type: "select2:select",
                            params: {
                                data: {
                                    id: result.data.id,
                                    text: result.data.name,
                                    color:
                                        result.priority?.color ||
                                        result.status?.color ||
                                        "primary", // Ensure color is passed
                                },
                            },
                        });
                    }
                    // Handle TomSelect specifically
                    if (selector[0].tomselect) {
                        selector[0].tomselect.addOption({
                            value: result.data.id,
                            text: result.data.name,
                            color:
                                result.priority?.color ||
                                result.status?.color ||
                                "primary",
                        });
                        selector[0].tomselect.addItem(result.data.id);
                    }
                } else {
                }
            });
            if (!foundAndUpdated) {
            }
        } else {
        }
    }
    // ✅ ENHANCED REDIRECTION
    function handleRedirection() {
        try {
            if (redirect_url) {
                window.location.href = redirect_url;
            } else {
                window.location.reload();
            }
        } catch (error) {
            // console.warn('Redirection error:', error);
            window.location.reload();
        }
    }
});
// Opening Quick View Modal from URL Also
$(document).ready(function () {
    // Base URL for AJAX requests (replace with your actual base URL)
    const baseUrl = window.location.origin;
    // Function to open the quick view modal
    function openQuickViewModal(type, id, updateUrl = true) {
        $("#type").val(type);
        $("#typeId").val(id);
        $.ajax({
            url: `${baseUrl}/${type}s/get/${id}`,
            type: "GET",
            success: function (response) {
                if (response.error === false) {
                    $("#quickViewOffcanvas").offcanvas("show");
                    if (type === "task" && response.task) {
                        $("#quickViewTitlePlaceholder").text(
                            response.task.title,
                        );
                        $("#quickViewDescPlaceholder").html(
                            response.task.description,
                        );
                    } else if (type === "project" && response.project) {
                        $("#quickViewTitlePlaceholder").text(
                            response.project.title,
                        );
                        $("#quickViewDescPlaceholder").html(
                            response.project.description,
                        );
                    }
                    $("#typePlaceholder").text(
                        type === "task" ? "Task" : "Project",
                    );
                    $("#usersTable").bootstrapTable("refresh");
                    $("#clientsTable").bootstrapTable("refresh");
                    // Update URL with modal parameters
                    if (updateUrl) {
                        const urlParams = new URLSearchParams(
                            window.location.search,
                        );
                        urlParams.set("modal", "quickview");
                        urlParams.set("type", type);
                        urlParams.set("id", id);
                        const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
                        window.history.pushState(
                            { modal: "quickview", type, id },
                            "",
                            newUrl,
                        );
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr, status, error) {
                toastr.error("Something Went Wrong");
            },
        });
    }
    // Handle click on quick-view elements
    $(document).on("click", ".quick-view", function (e) {
        e.preventDefault();
        const id = $(this).data("id");
        const type = $(this).data("type") || "task";
        openQuickViewModal(type, id);
    });
    // Check URL on page load to open modal if parameters exist
    function checkUrlForModal() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get("modal") === "quickview") {
            const type = urlParams.get("type");
            const id = urlParams.get("id");
            if (type && id) {
                openQuickViewModal(type, id, false); // Don't update URL to avoid redundant pushState
            }
        }
    }
    // Handle modal close to clear URL parameters (optional)
    $("#quickViewOffcanvas").on("hidden.bs.offcanvas", function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get("modal") === "quickview") {
            urlParams.delete("modal");
            urlParams.delete("type");
            urlParams.delete("id");
            const newUrl = urlParams.toString()
                ? `${window.location.pathname}?${urlParams.toString()}`
                : window.location.pathname;
            window.history.pushState({}, "", newUrl);
        }
    });
    // Handle popstate to restore modal state
    window.addEventListener("popstate", function (event) {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get("modal") === "quickview") {
            const type = urlParams.get("type");
            const id = urlParams.get("id");
            if (type && id) {
                openQuickViewModal(type, id, false);
            }
        } else {
            $("#quickViewOffcanvas").offcanvas("hide");
        }
    });
    // Initialize by checking URL for modal parameters
    checkUrlForModal();
});

/* =====================================================================
   TASKIFY v2 "Graphite Studio" — app shell behaviour (rail + panel).
   - Hovering a rail icon previews that category's pane; leaving the rail
     restores the active pane.
   - The menu search filters items within the currently visible pane.
   - On <xl screens the rail toggles the collapsed context panel.
   ===================================================================== */
(function () {
    "use strict";

    function initTaskifyShell() {
        var rail = document.querySelector(".tk-rail");
        var panel = document.querySelector(".tk-panel");
        if (!rail || !panel) return;

        var panes = panel.querySelectorAll(".tk-panel-pane");
        var activePane = panel.querySelector(".tk-panel-pane:not([hidden])");
        var activeKey = activePane
            ? activePane.getAttribute("data-panel")
            : null;

        function showPane(key) {
            panes.forEach(function (p) {
                if (p.getAttribute("data-panel") === key) {
                    p.removeAttribute("hidden");
                } else {
                    p.setAttribute("hidden", "");
                }
            });
        }

        var railButtons = rail.querySelectorAll(".tk-rail-btn[data-panel]");
        var isCompact = function () {
            return window.matchMedia("(max-width: 1199.98px)").matches;
        };

        railButtons.forEach(function (btn) {
            var key = btn.getAttribute("data-panel");

            // Desktop: preview pane on hover.
            btn.addEventListener("mouseenter", function () {
                if (!isCompact()) showPane(key);
            });

            // Compact: tapping the rail opens that pane in the slide-over panel
            // (instead of navigating) so the submenu is reachable on phones.
            btn.addEventListener("click", function (e) {
                if (isCompact()) {
                    e.preventDefault();
                    showPane(key);
                    document.body.classList.add("tk-panel-open");
                }
            });
        });

        // Restore the route's active pane when the pointer leaves the rail.
        rail.addEventListener("mouseleave", function () {
            if (!isCompact() && activeKey) showPane(activeKey);
        });

        // Close the slide-over panel when clicking the page content / scrim
        // (compact only). The burger is excluded so its own click can open it.
        document.addEventListener("click", function (e) {
            if (!isCompact()) return;
            if (
                !e.target.closest(".tk-panel") &&
                !e.target.closest(".tk-rail") &&
                !e.target.closest(".tk-cbar-burger")
            ) {
                document.body.classList.remove("tk-panel-open");
            }
        });

        // Menu search: filter items inside the visible pane.
        var search = document.getElementById("menu-search");
        var searchClear = document.getElementById("menu-search-clear");
        if (search) {
            search.addEventListener("input", function () {
                var q = this.value.trim().toLowerCase();
                if (searchClear)
                    searchClear.style.display = q.length > 0 ? "block" : "none";
                var pane = panel.querySelector(".tk-panel-pane:not([hidden])");
                if (!pane) return;
                pane.querySelectorAll(".tk-panel-item").forEach(
                    function (item) {
                        var span = item.querySelector("span");
                        if (!span) return;

                        if (!span.hasAttribute("data-original-text")) {
                            span.setAttribute(
                                "data-original-text",
                                span.textContent,
                            );
                        }
                        var text = span.getAttribute("data-original-text");
                        var lowerText = text.toLowerCase();

                        if (!q) {
                            span.innerHTML = text;
                            item.style.display = "";
                        } else if (lowerText.indexOf(q) !== -1) {
                            item.style.display = "";
                            var startIndex = lowerText.indexOf(q);
                            var endIndex = startIndex + q.length;
                            span.innerHTML =
                                text.substring(0, startIndex) +
                                '<span style="color: var(--signal); font-weight: 600;">' +
                                text.substring(startIndex, endIndex) +
                                "</span>" +
                                text.substring(endIndex);
                        } else {
                            item.style.display = "none";
                            span.innerHTML = text;
                        }
                    },
                );
                pane.querySelectorAll(".tk-panel-group").forEach(
                    function (group) {
                        var anyVisible = Array.prototype.some.call(
                            group.querySelectorAll(".tk-panel-item"),
                            function (it) {
                                return it.style.display !== "none";
                            },
                        );
                        group.style.display = anyVisible ? "" : "none";
                    },
                );
            });

            if (searchClear) {
                searchClear.addEventListener("click", function () {
                    search.value = "";
                    search.dispatchEvent(new Event("input"));
                    search.focus();
                });
            }
        }

        // Keyboard shortcut '/' to focus menu search
        document.addEventListener("keydown", function (e) {
            if (
                e.key === "/" &&
                document.activeElement.tagName !== "INPUT" &&
                document.activeElement.tagName !== "TEXTAREA"
            ) {
                var searchInput = document.getElementById("menu-search");
                if (searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initTaskifyShell);
    } else {
        initTaskifyShell();
    }
})();

/* =====================================================================
   TASKIFY v2 — command bar behaviour: light/dark theme toggle and the
   mobile burger that opens the context panel. Theme is stored in
   localStorage and applied to both the design system (data-theme) and
   Bootstrap 5.3 components (data-bs-theme).
   ===================================================================== */
(function () {
    "use strict";

    var STORAGE_KEY = "taskify.theme";

    function applyTheme(theme) {
        var el = document.documentElement;
        el.setAttribute("data-theme", theme);
        el.setAttribute("data-bs-theme", theme);
    }

    function currentTheme() {
        return document.documentElement.getAttribute("data-theme") === "dark"
            ? "dark"
            : "light";
    }

    function initCbar() {
        var toggle = document.getElementById("tk-theme-toggle");
        if (toggle) {
            toggle.addEventListener("click", function () {
                var next = currentTheme() === "dark" ? "light" : "dark";
                applyTheme(next);
                try {
                    localStorage.setItem(STORAGE_KEY, next);
                } catch (e) {}
            });
        }

        // Mobile burger opens / closes the context panel.
        document.querySelectorAll(".tk-cbar-burger").forEach(function (btn) {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                document.body.classList.toggle("tk-panel-open");
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initCbar);
    } else {
        initCbar();
    }
})();

/* =====================================================================
   TASKIFY v2 — Global search command palette (#globalSearchModal #tk-pal)
   Lists every permission-gated menu. Typing filters the list; Arrow keys
   move the highlight; Enter navigates. Mirrors the kit palette behaviour
   and runs alongside the existing AJAX content search.
   ===================================================================== */
(function () {
    "use strict";

    function initPalette() {
        var modal = document.getElementById("globalSearchModal");
        if (!modal) return;
        var input = modal.querySelector("#modalSearchInput");
        var pal = modal.querySelector("#tk-pal");
        if (!input || !pal) return;
        var empty = modal.querySelector("#tk-pal-empty");

        function items() {
            return Array.prototype.slice.call(
                pal.querySelectorAll(".tk-pal-item"),
            );
        }
        function visibleItems() {
            return items().filter(function (it) {
                return it.style.display !== "none";
            });
        }
        function clearActive() {
            items().forEach(function (it) {
                it.removeAttribute("data-active");
            });
        }
        function setActive(it) {
            clearActive();
            if (it) {
                it.setAttribute("data-active", "true");
                it.scrollIntoView({ block: "nearest" });
            }
        }

        function filter() {
            var q = (input.value || "").trim().toLowerCase();
            var anyVisible = false;
            items().forEach(function (it) {
                var text =
                    it.getAttribute("data-text") ||
                    it.textContent.toLowerCase();
                var match = !q || text.indexOf(q) !== -1;
                it.style.display = match ? "" : "none";
                if (match) anyVisible = true;
            });
            pal.querySelectorAll(".tk-pal-group").forEach(function (group) {
                var groupVisible = Array.prototype.some.call(
                    group.querySelectorAll(".tk-pal-item"),
                    function (it) {
                        return it.style.display !== "none";
                    },
                );
                group.style.display = groupVisible ? "" : "none";
            });
            if (empty) empty.hidden = true;
            // Collapse the palette entirely when no menu matches, so the AJAX
            // content results below can speak for themselves.
            pal.style.display = anyVisible ? "" : "none";
            setActive(visibleItems()[0] || null);
        }

        input.addEventListener("input", filter);

        input.addEventListener("keydown", function (e) {
            var vis = visibleItems();
            if (!vis.length) return;
            var idx = vis.findIndex(function (it) {
                return it.getAttribute("data-active") === "true";
            });
            if (e.key === "ArrowDown") {
                e.preventDefault();
                setActive(vis[Math.min(idx + 1, vis.length - 1)] || vis[0]);
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                setActive(vis[Math.max(idx - 1, 0)] || vis[vis.length - 1]);
            } else if (e.key === "Enter") {
                var target = vis[idx] || vis[0];
                if (target && target.getAttribute("href")) {
                    e.preventDefault();
                    window.location.href = target.getAttribute("href");
                }
            }
        });

        // Reset filter + highlight each time the modal opens.
        if (window.jQuery) {
            window.jQuery(modal).on("shown.bs.modal", function () {
                filter();
                input.focus();
            });
        }
        filter();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initPalette);
    } else {
        initPalette();
    }
})();

/* =====================================================================
   Taskify v2 — Dashboard KPI sparklines + trend badges
   ---------------------------------------------------------------------
   The KPI value (.tk-metric-value.count) is the real, live number filled
   by dashboard.js. This module only renders the sparkline + signed delta
   badge from REAL data — the per-metric daily series in response.trends,
   added (read-only) by DashboardService::buildTrends().

   No random, no static: each series is the cumulative count of records
   created up to each day in the selected range, so the line is the true
   growth of that metric and the badge delta (last − first) is the net new
   in the period. We hook jQuery's global ajaxSuccess for /dashboard/data,
   so dashboard.js is NOT modified; when filters change, dashboard.js
   re-fetches and the sparklines update with it.
   ===================================================================== */
(function () {
    "use strict";

    var VB_W = 100,
        VB_H = 28; // svg viewBox
    var PAD_Y = 3;

    // tile id -> trends key returned by the backend.
    var TREND_KEY = {
        "projects-tile": "projects",
        "tasks-tile": "tasks",
        "users-tile": "users",
        "clients-tile": "clients",
        "meetings-tile": "meetings",
        "todos-tile": "todos",
    };

    function buildLinePath(series) {
        var data = series.slice();
        if (data.length === 1) {
            data = [data[0], data[0]];
        }
        var n = data.length;
        var min = Math.min.apply(null, data);
        var max = Math.max.apply(null, data);
        var range = max - min || 1;
        var usableH = VB_H - PAD_Y * 2;
        var d = "";
        for (var i = 0; i < n; i++) {
            var x = (i / (n - 1)) * VB_W;
            var y = PAD_Y + (1 - (data[i] - min) / range) * usableH;
            d +=
                (i === 0 ? "M" : " L") +
                Math.round(x * 100) / 100 +
                " " +
                Math.round(y * 100) / 100;
        }
        return d;
    }

    function formatDelta(delta) {
        var sign = delta > 0 ? "+" : delta < 0 ? "−" : "";
        return sign + Math.abs(Math.round(delta));
    }

    function render(metric, series) {
        var sparkEl = metric.querySelector(".tk-metric-spark");
        var trendEl = metric.querySelector(".tk-metric-trend");

        // No real data for this metric -> show nothing (cells stay clean).
        if (!Array.isArray(series) || series.length === 0) {
            if (sparkEl) {
                sparkEl.innerHTML = "";
            }
            if (trendEl) {
                trendEl.innerHTML = "";
            }
            return;
        }

        var delta = series[series.length - 1] - series[0];
        var dir = delta > 0 ? "up" : delta < 0 ? "down" : "flat";

        if (trendEl) {
            trendEl.classList.remove("is-up", "is-down", "is-flat");
            trendEl.classList.add("is-" + dir);
            var arrow = dir === "down" ? "↓" : dir === "up" ? "↑" : "→";
            trendEl.innerHTML =
                '<span class="tk-trend-arrow">' +
                arrow +
                "</span>" +
                "<span>" +
                formatDelta(delta) +
                "</span>";
        }

        if (sparkEl) {
            var d = buildLinePath(series);
            sparkEl.innerHTML =
                '<svg viewBox="0 0 ' +
                VB_W +
                " " +
                VB_H +
                '" preserveAspectRatio="none" focusable="false">' +
                '<path class="tk-spark-line" d="' +
                d +
                '"></path>' +
                "</svg>";
        }
    }

    function applyTrends(trends) {
        if (!trends) {
            return;
        }
        var metrics = document.querySelectorAll(".tk-metric");
        for (var i = 0; i < metrics.length; i++) {
            var metric = metrics[i];
            var key = TREND_KEY[metric.id];
            if (key && Object.prototype.hasOwnProperty.call(trends, key)) {
                render(metric, trends[key]);
            }
        }
    }

    function initMetricSparklines() {
        if (!document.querySelector(".tk-metric")) {
            return;
        }
        if (!window.jQuery) {
            return;
        }

        // Read the real trend series from the dashboard AJAX response without
        // touching dashboard.js — jQuery fires ajaxSuccess globally.
        window.jQuery(document).ajaxSuccess(function (evt, xhr, settings) {
            if (
                !settings ||
                !settings.url ||
                settings.url.indexOf("/dashboard/data") < 0
            ) {
                return;
            }
            var resp = xhr.responseJSON;
            if (!resp && xhr.responseText) {
                try {
                    resp = JSON.parse(xhr.responseText);
                } catch (e) {
                    resp = null;
                }
            }
            if (resp && resp.trends) {
                applyTrends(resp.trends);
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initMetricSparklines);
    } else {
        initMetricSparklines();
    }
})();

/* =====================================================================
   Taskify v2 — Dashboard hero area chart (Income vs Expense)
   ---------------------------------------------------------------------
   Draws the kit <x-data.area-chart> as a vanilla SVG into #tk-hero-chart
   from the REAL /reports/income-vs-expense-report-data response. We hook
   jQuery's global ajaxSuccess (the same request dashboard.js already
   fires), so dashboard.js is NOT modified. Two series (income/expense)
   share one Y scale; kit grid/axis/gradients + a singleton tooltip and a
   hover cursor. Re-renders on resize and on every filter-driven refetch.
   ===================================================================== */
(function () {
    "use strict";

    var ENDPOINT_HINT = "income-vs-expense-report-data";
    var H = 240,
        PT = 16,
        PB = 26,
        PL = 48,
        PR = 14;
    var CUR =
        (typeof window.label_currency_symbol === "string" &&
            window.label_currency_symbol) ||
        "₹";

    var lastData = null; // {categories, income, expense, labels}
    var tooltip = null;
    var mode = "both"; // income | expense | both (driven by the segmented selector)

    function el() {
        return document.getElementById("tk-hero-chart");
    }

    function fmtMoney(v) {
        var n = Math.abs(Math.round(Number(v) || 0));
        return CUR + n.toLocaleString();
    }

    // dd-mm-yyyy -> Date (mirrors dashboard.js parseDMY).
    function parseDMY(d) {
        if (!d) {
            return new Date(0);
        }
        var parts = String(d).split("-");
        if (parts.length !== 3) {
            return new Date(d);
        }
        return new Date(
            Number(parts[2]),
            Number(parts[1]) - 1,
            Number(parts[0]),
        );
    }

    function groupByDate(rows, dateField, amountField) {
        var acc = {};
        (rows || []).forEach(function (item) {
            var date = String(item[dateField] || "").split(" ")[0] || "";
            if (!date) {
                return;
            }
            var amount =
                parseFloat(
                    String(item[amountField] || "").replace(/[^0-9.\-]+/g, ""),
                ) || 0;
            acc[date] = (acc[date] || 0) + amount;
        });
        return acc;
    }

    // Build the chart model from the raw AJAX response.
    function buildModel(resp) {
        var invoices = (resp && resp.invoices) || [];
        var expenses = (resp && resp.expenses) || [];
        var inc = groupByDate(invoices, "from_date", "amount");
        var exp = groupByDate(expenses, "expense_date", "amount");
        var dates = Object.keys(inc).concat(Object.keys(exp));
        var seen = {};
        var all = [];
        dates.forEach(function (d) {
            if (!seen[d]) {
                seen[d] = 1;
                all.push(d);
            }
        });
        all.sort(function (a, b) {
            return parseDMY(a) - parseDMY(b);
        });
        return {
            categories: all,
            income: all.map(function (d) {
                return inc[d] || 0;
            }),
            expense: all.map(function (d) {
                return exp[d] || 0;
            }),
            labels: all.map(function (d) {
                var dt = parseDMY(d);
                return isNaN(dt)
                    ? d
                    : dt.getDate() +
                          " " +
                          dt.toLocaleString(undefined, { month: "short" });
            }),
        };
    }

    function ensureTooltip() {
        if (tooltip) {
            return tooltip;
        }
        tooltip = document.createElement("div");
        tooltip.className = "tk-chart-tt";
        document.body.appendChild(tooltip);
        return tooltip;
    }

    function svgNS(tag, attrs) {
        var node = document.createElementNS("http://www.w3.org/2000/svg", tag);
        for (var k in attrs) {
            if (Object.prototype.hasOwnProperty.call(attrs, k)) {
                node.setAttribute(k, attrs[k]);
            }
        }
        return node;
    }

    function round(n) {
        return Math.round(n * 100) / 100;
    }

    function linePath(pts) {
        return pts
            .map(function (p, i) {
                return (i === 0 ? "M" : "L") + round(p[0]) + " " + round(p[1]);
            })
            .join(" ");
    }

    function render() {
        var host = el();
        if (!host || !lastData) {
            return;
        }

        var data = lastData;
        var n = data.categories.length;
        host.innerHTML = "";

        if (n === 0) {
            var empty = document.createElement("div");
            empty.className = "tk-area-empty";
            empty.textContent =
                host.getAttribute("data-empty-label") || "No data available";
            host.appendChild(empty);
            return;
        }

        var showInc = mode === "both" || mode === "income";
        var showExp = mode === "both" || mode === "expense";

        var W = Math.max(host.clientWidth || host.offsetWidth || 700, 320);
        var plotW = W - PL - PR;
        var plotH = H - PT - PB;
        var visible = [];
        if (showInc) {
            visible = visible.concat(data.income);
        }
        if (showExp) {
            visible = visible.concat(data.expense);
        }
        var maxV = visible.length ? Math.max.apply(null, visible) : 0;
        maxV = maxV > 0 ? maxV * 1.15 : 1;

        var x = function (i) {
            return n === 1 ? PL + plotW / 2 : PL + (i / (n - 1)) * plotW;
        };
        var y = function (v) {
            return PT + (1 - v / maxV) * plotH;
        };

        var incPts = data.income.map(function (v, i) {
            return [x(i), y(v)];
        });
        var expPts = data.expense.map(function (v, i) {
            return [x(i), y(v)];
        });

        var svg = svgNS("svg", {
            viewBox: "0 0 " + W + " " + H,
            preserveAspectRatio: "none",
            focusable: "false",
        });

        // gradients
        var defs = svgNS("defs", {});
        [
            ["tkHeroIncomeGrad", "tk-grad--income"],
            ["tkHeroExpenseGrad", "tk-grad--expense"],
        ].forEach(function (g) {
            var grad = svgNS("linearGradient", {
                id: g[0],
                class: "tk-grad " + g[1],
                x1: 0,
                y1: 0,
                x2: 0,
                y2: 1,
            });
            grad.appendChild(svgNS("stop", { class: "tk-g0", offset: "0%" }));
            grad.appendChild(svgNS("stop", { class: "tk-g1", offset: "100%" }));
            defs.appendChild(grad);
        });
        svg.appendChild(defs);

        // grid + y axis labels
        var grid = svgNS("g", { class: "tk-area-grid" });
        var axis = svgNS("g", { class: "tk-area-axis" });
        [0, 0.25, 0.5, 0.75, 1].forEach(function (t) {
            var gy = PT + t * plotH;
            grid.appendChild(
                svgNS("line", { x1: PL, x2: W - PR, y1: gy, y2: gy }),
            );
            var label = svgNS("text", {
                x: PL - 8,
                y: gy + 3,
                "text-anchor": "end",
            });
            label.textContent = Math.round(maxV * (1 - t)).toLocaleString();
            axis.appendChild(label);
        });
        // x axis labels (~8 max)
        var stepLabel = Math.max(1, Math.floor(n / 8));
        for (var i = 0; i < n; i++) {
            if (i % stepLabel === 0 || i === n - 1) {
                var tx = svgNS("text", {
                    x: x(i),
                    y: H - 8,
                    "text-anchor": "middle",
                });
                tx.textContent = data.labels[i];
                axis.appendChild(tx);
            }
        }
        svg.appendChild(grid);
        svg.appendChild(axis);

        // areas (closed to baseline)
        var baseY = PT + plotH;
        function areaPath(pts) {
            return (
                linePath(pts) +
                " L" +
                round(pts[pts.length - 1][0]) +
                " " +
                baseY +
                " L" +
                round(pts[0][0]) +
                " " +
                baseY +
                " Z"
            );
        }
        if (showInc) {
            svg.appendChild(
                svgNS("path", {
                    d: areaPath(incPts),
                    fill: "url(#tkHeroIncomeGrad)",
                }),
            );
        }
        if (showExp) {
            svg.appendChild(
                svgNS("path", {
                    d: areaPath(expPts),
                    fill: "url(#tkHeroExpenseGrad)",
                }),
            );
        }

        // hover cursor line
        var cursor = svgNS("line", {
            class: "tk-area-cursor",
            x1: 0,
            x2: 0,
            y1: PT,
            y2: baseY,
        });
        svg.appendChild(cursor);

        // lines
        if (showInc) {
            svg.appendChild(
                svgNS("path", {
                    class: "tk-area-line tk-area-line--income",
                    d: linePath(incPts),
                }),
            );
        }
        if (showExp) {
            svg.appendChild(
                svgNS("path", {
                    class: "tk-area-line tk-area-line--expense",
                    d: linePath(expPts),
                }),
            );
        }

        // dots (skip when too dense)
        var showDots = n <= 24;
        var incDots = [],
            expDots = [];
        if (showDots) {
            if (showInc) {
                incPts.forEach(function (p) {
                    var c = svgNS("circle", {
                        class: "tk-area-dot tk-area-dot--income",
                        cx: round(p[0]),
                        cy: round(p[1]),
                        r: 2.5,
                    });
                    svg.appendChild(c);
                    incDots.push(c);
                });
            }
            if (showExp) {
                expPts.forEach(function (p) {
                    var c = svgNS("circle", {
                        class: "tk-area-dot tk-area-dot--expense",
                        cx: round(p[0]),
                        cy: round(p[1]),
                        r: 2.5,
                    });
                    svg.appendChild(c);
                    expDots.push(c);
                });
            }
        }

        host.appendChild(svg);

        // ---- interaction: nearest-point tooltip + cursor ----
        var incLabel = host.getAttribute("data-label-income") || "Income";
        var expLabel = host.getAttribute("data-label-expense") || "Expenses";

        function indexFromEvent(evt) {
            var rect = svg.getBoundingClientRect();
            var px = ((evt.clientX - rect.left) / rect.width) * W; // map back to viewBox units
            var idx = n === 1 ? 0 : Math.round(((px - PL) / plotW) * (n - 1));
            return Math.max(0, Math.min(n - 1, idx));
        }

        function showAt(evt) {
            var idx = indexFromEvent(evt);
            var cx = x(idx);
            cursor.setAttribute("x1", cx);
            cursor.setAttribute("x2", cx);
            host.classList.add("is-hover");
            if (showDots) {
                incDots.forEach(function (c, i) {
                    c.setAttribute("r", i === idx ? 3.6 : 2.5);
                });
                expDots.forEach(function (c, i) {
                    c.setAttribute("r", i === idx ? 3.6 : 2.5);
                });
            }
            var tt = ensureTooltip();
            var rows =
                '<div class="tk-chart-tt-date">' + data.labels[idx] + "</div>";
            if (showInc) {
                rows +=
                    '<div class="tk-chart-tt-row"><span class="tk-chart-tt-sw" style="background:var(--signal)"></span>' +
                    incLabel +
                    '<span class="tk-chart-tt-val">' +
                    fmtMoney(data.income[idx]) +
                    "</span></div>";
            }
            if (showExp) {
                rows +=
                    '<div class="tk-chart-tt-row"><span class="tk-chart-tt-sw" style="background:var(--fg-2)"></span>' +
                    expLabel +
                    '<span class="tk-chart-tt-val">' +
                    fmtMoney(data.expense[idx]) +
                    "</span></div>";
            }
            tt.innerHTML = rows;
            tt.classList.add("is-on");
            tt.style.left = evt.clientX + "px";
            tt.style.top = evt.clientY + "px";
        }

        function hide() {
            host.classList.remove("is-hover");
            if (tooltip) {
                tooltip.classList.remove("is-on");
            }
            if (showDots) {
                incDots.forEach(function (c) {
                    c.setAttribute("r", 2.5);
                });
                expDots.forEach(function (c) {
                    c.setAttribute("r", 2.5);
                });
            }
        }

        svg.addEventListener("mousemove", showAt);
        svg.addEventListener("mouseleave", hide);
    }

    var resizeTimer = null;
    function onResize() {
        if (resizeTimer) {
            clearTimeout(resizeTimer);
        }
        resizeTimer = setTimeout(render, 120);
    }

    function initHeroAreaChart() {
        if (!el() || !window.jQuery) {
            return;
        }

        window.jQuery(document).ajaxSuccess(function (evt, xhr, settings) {
            if (
                !settings ||
                !settings.url ||
                settings.url.indexOf(ENDPOINT_HINT) < 0
            ) {
                return;
            }
            var resp = xhr.responseJSON;
            if (!resp && xhr.responseText) {
                try {
                    resp = JSON.parse(xhr.responseText);
                } catch (e) {
                    resp = null;
                }
            }
            if (!resp) {
                return;
            }
            lastData = buildModel(resp);
            render();
        });

        window.addEventListener("resize", onResize);

        // Income | Expense | Both segmented selector (kit .seg control).
        var seg = document.querySelector('.tk-seg[data-chart="hero"]');
        if (seg) {
            var onBtn = seg.querySelector(".tk-seg-btn.on");
            if (onBtn) {
                mode = onBtn.getAttribute("data-value") || "both";
            }
            seg.addEventListener("click", function (e) {
                var btn = e.target.closest(".tk-seg-btn");
                if (!btn) {
                    return;
                }
                mode = btn.getAttribute("data-value") || "both";
                var btns = seg.querySelectorAll(".tk-seg-btn");
                for (var i = 0; i < btns.length; i++) {
                    var isOn = btns[i] === btn;
                    btns[i].classList.toggle("on", isOn);
                    btns[i].setAttribute(
                        "aria-checked",
                        isOn ? "true" : "false",
                    );
                }
                render();
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initHeroAreaChart);
    } else {
        initHeroAreaChart();
    }
})();

/* =====================================================================
   Taskify v2 — Right-column status donuts (Project / Task / Todo) +
   left-column Recent Activity feed.
   ---------------------------------------------------------------------
   Vanilla SVG donuts matching the kit <x-data.donut> (size 132, thickness
   14, bg-3 track, rotate -90 segments). Segment colors come from the
   design tokens (status colour -> token). All fed by the real
   /dashboard/data response via jQuery ajaxSuccess — dashboard.js is NOT
   modified; the equivalent old statistics cards are hidden via CSS.
   ===================================================================== */
(function () {
    "use strict";

    var SIZE = 132,
        TH = 14;
    // App status colour name -> design-system token.
    var STATUS_TOKENS = {
        primary: "var(--signal)",
        secondary: "var(--fg-3)",
        success: "var(--ok)",
        danger: "var(--err)",
        warning: "var(--warn)",
        info: "var(--info)",
    };
    var FALLBACK = [
        "var(--signal)",
        "var(--info)",
        "var(--warn)",
        "var(--ok)",
        "var(--err)",
        "var(--fg-3)",
    ];
    var tt = null;

    function tooltip() {
        if (tt) {
            return tt;
        }
        tt = document.querySelector(".tk-chart-tt");
        if (!tt) {
            tt = document.createElement("div");
            tt.className = "tk-chart-tt";
            document.body.appendChild(tt);
        }
        return tt;
    }

    function svgNS(tag, attrs) {
        var node = document.createElementNS("http://www.w3.org/2000/svg", tag);
        for (var k in attrs) {
            if (Object.prototype.hasOwnProperty.call(attrs, k)) {
                node.setAttribute(k, attrs[k]);
            }
        }
        return node;
    }

    function colorFor(name, i) {
        return STATUS_TOKENS[name] || FALLBACK[i % FALLBACK.length];
    }

    function drawDonut(host, legendHost, totalEl, items, centerLabel) {
        if (!host) {
            return;
        }
        var total = items.reduce(function (a, b) {
            return a + b.value;
        }, 0);
        if (totalEl) {
            totalEl.textContent = total.toLocaleString();
        }

        var r = SIZE / 2 - TH / 2,
            cx = SIZE / 2,
            cy = SIZE / 2,
            circ = 2 * Math.PI * r;
        var svg = svgNS("svg", {
            width: SIZE,
            height: SIZE,
            viewBox: "0 0 " + SIZE + " " + SIZE,
        });
        svg.appendChild(
            svgNS("circle", {
                cx: cx,
                cy: cy,
                r: r,
                fill: "none",
                stroke: "var(--bg-3)",
                "stroke-width": TH,
            }),
        );

        var acc = 0;
        var segNodes = [];
        items.forEach(function (it) {
            var frac = total ? it.value / total : 0;
            if (it.value > 0) {
                var len = frac * circ;
                var seg = svgNS("circle", {
                    cx: cx,
                    cy: cy,
                    r: r,
                    fill: "none",
                    stroke: it.color,
                    "stroke-width": TH,
                    "stroke-dasharray": Math.max(len - 2, 0) + " " + circ,
                    "stroke-dashoffset": -acc * circ,
                    transform: "rotate(-90 " + cx + " " + cy + ")",
                    class: "donut-segment",
                });
                svg.appendChild(seg);
                segNodes.push(seg);
            } else {
                segNodes.push(null);
            }
            acc += frac;
        });

        var t1 = svgNS("text", {
            x: cx,
            y: cy - 1,
            "text-anchor": "middle",
            "font-size": 22,
            "font-weight": 700,
            fill: "var(--fg-0)",
            "letter-spacing": "-0.03em",
            "font-family": "var(--font-sans)",
        });
        t1.textContent = total.toLocaleString();
        var t2 = svgNS("text", {
            x: cx,
            y: cy + 14,
            "text-anchor": "middle",
            "font-size": 9,
            fill: "var(--fg-3)",
            "font-family": "var(--font-mono)",
            "letter-spacing": "0.06em",
        });
        t2.textContent = centerLabel || "TOTAL";
        svg.appendChild(t1);
        svg.appendChild(t2);

        host.innerHTML = "";
        host.appendChild(svg);

        function fadeExcept(idx) {
            segNodes.forEach(function (s, j) {
                if (s) {
                    s.classList.toggle("is-faded", j !== idx);
                }
            });
        }
        function clearFade() {
            segNodes.forEach(function (s) {
                if (s) {
                    s.classList.remove("is-faded");
                }
            });
        }

        if (legendHost) {
            legendHost.innerHTML = "";
            if (!items.length) {
                legendHost.innerHTML =
                    '<div class="tk-donut-empty">' +
                    (legendHost.getAttribute("data-empty-label") || "No data") +
                    "</div>";
            }
            items.forEach(function (it, i) {
                var pct = total ? Math.round((it.value / total) * 100) : 0;
                var row = document.createElement("div");
                row.className = "tk-donut-legend-row";
                row.innerHTML =
                    '<span class="tk-ld" style="background:' +
                    it.color +
                    '"></span>' +
                    '<span class="tk-ll">' +
                    it.label +
                    "</span>" +
                    '<span class="tk-lv">' +
                    it.value.toLocaleString() +
                    "</span>";
                legendHost.appendChild(row);
                row.addEventListener("mouseenter", function () {
                    fadeExcept(i);
                });
                row.addEventListener("mouseleave", clearFade);
            });
        }

        segNodes.forEach(function (seg, i) {
            if (!seg) {
                return;
            }
            var it = items[i];
            var pct = total ? Math.round((it.value / total) * 100) : 0;
            seg.addEventListener("mousemove", function (e) {
                fadeExcept(i);
                var el = tooltip();
                el.innerHTML =
                    '<div class="tk-chart-tt-row"><span class="tk-chart-tt-sw" style="background:' +
                    it.color +
                    '"></span>' +
                    it.label +
                    '<span class="tk-chart-tt-val">' +
                    it.value.toLocaleString() +
                    " (" +
                    pct +
                    "%)</span></div>";
                el.classList.add("is-on");
                el.style.left = e.clientX + "px";
                el.style.top = e.clientY + "px";
            });
            seg.addEventListener("mouseleave", function () {
                clearFade();
                var el = tooltip();
                el.classList.remove("is-on");
            });
        });
    }

    // Build segments from the workspace statuses + a status_id -> count map.
    function statusItems(statuses, counts) {
        return (statuses || [])
            .map(function (s, i) {
                return {
                    label: s.title,
                    value: Math.max(0, Number((counts || {})[s.id]) || 0),
                    color: colorFor(s.color, i),
                };
            })
            .filter(function (x) {
                return x.value > 0;
            });
    }

    function renderCharts(resp) {
        var pHost = document.getElementById("tk-project-donut");
        if (pHost) {
            drawDonut(
                pHost,
                document.getElementById("tk-project-legend"),
                document.getElementById("tk-project-total"),
                statusItems(resp.statuses, resp.project_status_counts),
                pHost.getAttribute("data-center-label") || "PROJECTS",
            );
        }
        var kHost = document.getElementById("tk-task-donut");
        if (kHost) {
            drawDonut(
                kHost,
                document.getElementById("tk-task-legend"),
                document.getElementById("tk-task-total"),
                statusItems(resp.statuses, resp.task_status_counts),
                kHost.getAttribute("data-center-label") || "TASKS",
            );
        }
        var dHost = document.getElementById("tk-todo-donut");
        if (dHost) {
            var td = resp.todo_data || [0, 0];
            var todoItems = [
                {
                    label: dHost.getAttribute("data-label-done") || "Completed",
                    value: Math.max(0, Number(td[0]) || 0),
                    color: "var(--ok)",
                },
                {
                    label:
                        dHost.getAttribute("data-label-pending") || "Pending",
                    value: Math.max(0, Number(td[1]) || 0),
                    color: "var(--warn)",
                },
            ].filter(function (x) {
                return x.value > 0;
            });
            drawDonut(
                dHost,
                document.getElementById("tk-todo-legend"),
                document.getElementById("tk-todo-total"),
                todoItems,
                dHost.getAttribute("data-center-label") || "TODOS",
            );
        }
    }

    // ---- Recent Activity feed ----
    var ACT_CLASS = {
        created: "is-created",
        updated: "is-updated",
        deleted: "is-deleted",
        "updated status": "is-status",
    };
    function escTime(s) {
        return String(s == null ? "" : s).replace(/[&<>"]/g, function (c) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c];
        });
    }
    function renderActivity(resp) {
        var host = document.getElementById("tk-activity-list");
        if (!host) {
            return;
        }
        var acts = resp.activities || [];
        if (!acts.length) {
            host.innerHTML =
                '<div class="tk-act-empty">' +
                escTime(
                    host.getAttribute("data-empty-label") ||
                        "No recent activities",
                ) +
                "</div>";
            return;
        }
        // message is server-rendered (same as dashboard.js timeline) -> inserted as-is.
        host.innerHTML = acts
            .map(function (a) {
                var cls = ACT_CLASS[a.activity] || "is-default";
                return (
                    '<div class="tk-act-row"><span class="tk-act-dot ' +
                    cls +
                    '"></span>' +
                    '<div class="tk-act-main"><div class="tk-act-msg">' +
                    (a.message || "") +
                    "</div>" +
                    '<div class="tk-act-time">' +
                    escTime(a.created_at_diff || a.created_at_formatted || "") +
                    "</div></div></div>"
                );
            })
            .join("");
    }

    function apply(resp) {
        renderCharts(resp);
        renderActivity(resp);
    }

    function initDashboardCharts() {
        if (!window.jQuery) {
            return;
        }
        var anchor =
            document.getElementById("tk-project-donut") ||
            document.getElementById("tk-task-donut") ||
            document.getElementById("tk-todo-donut") ||
            document.getElementById("tk-activity-list");
        if (!anchor) {
            return;
        }
        window.jQuery(document).ajaxSuccess(function (evt, xhr, settings) {
            if (
                !settings ||
                !settings.url ||
                settings.url.indexOf("/dashboard/data") < 0
            ) {
                return;
            }
            var resp = xhr.responseJSON;
            if (!resp && xhr.responseText) {
                try {
                    resp = JSON.parse(xhr.responseText);
                } catch (e) {
                    resp = null;
                }
            }
            if (resp) {
                apply(resp);
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDashboardCharts);
    } else {
        initDashboardCharts();
    }
})();

/* =====================================================================
   Taskify v2 — Dashboard birthday / anniversary / leave table filtering
   ---------------------------------------------------------------------
   The bootstrap-table data-query-params="queryParamsUpcoming*" referenced
   functions that were not defined anywhere, so the member/client/days
   filters were never sent to the server (filtering did nothing). This
   defines them (sending the real filter values + page/limit the
   controllers expect) AND auto-filters on select/input change so the
   explicit "Filter" button is no longer needed. No backend changes.
   ===================================================================== */
(function () {
    "use strict";

    function $j() {
        return window.jQuery;
    }
    function val(sel) {
        var $ = $j();
        return $ && $(sel).length ? $(sel).val() || "" : "";
    }
    function arr(sel) {
        var $ = $j();
        if (!$ || !$(sel).length) {
            return [];
        }
        var v = $(sel).val() || [];
        return Array.isArray(v) ? v : v ? [v] : [];
    }
    // Drop empty values so the server defaults (e.g. upcoming_days = 30) apply.
    function clean(extra) {
        Object.keys(extra).forEach(function (k) {
            var v = extra[k];
            if (
                v === "" ||
                v === null ||
                typeof v === "undefined" ||
                (Array.isArray(v) && v.length === 0)
            ) {
                delete extra[k];
            }
        });
        return extra;
    }
    function base(p, extra) {
        var out = {
            search: p.search,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            limit: p.limit,
            page: p.limit ? p.offset / p.limit + 1 : 1,
        };
        return Object.assign(out, clean(extra));
    }

    // Functions referenced by data-query-params in the three card tables.
    window.queryParamsUpcomingBirthdays = function (p) {
        return base(p, {
            upcoming_days: val("#upcoming_days_bd"),
            user_ids: arr("#birthday_user_filter"),
            client_ids: arr("#birthday_client_filter"),
        });
    };
    window.queryParamsUpcomingWa = function (p) {
        return base(p, {
            upcoming_days: val("#upcoming_days_wa"),
            user_ids: arr("#wa_user_filter"),
            client_ids: arr("#wa_client_filter"),
        });
    };
    window.queryParamsMol = function (p) {
        return base(p, {
            upcoming_days: val("#upcoming_days_mol"),
            user_ids: arr("#mol_user_filter"),
        });
    };

    function initDashboardTableFilters() {
        var $ = $j();
        if (!$) {
            return;
        }

        var groups = [
            {
                table: "#birthdays_table",
                filters: [
                    "#birthday_user_filter",
                    "#birthday_client_filter",
                    "#upcoming_days_bd",
                ],
                btn: "#upcoming_days_birthday_filter",
            },
            {
                table: "#wa_table",
                filters: [
                    "#wa_user_filter",
                    "#wa_client_filter",
                    "#upcoming_days_wa",
                ],
                btn: "#upcoming_days_wa_filter",
            },
            {
                table: "#mol_table",
                filters: ["#mol_user_filter", "#upcoming_days_mol"],
                btn: "#upcoming_days_mol_filter",
            },
        ];

        groups.forEach(function (g) {
            var timer = null;
            function refresh() {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    var $t = $(g.table);
                    if ($t.length && $t.data("bootstrap.table")) {
                        try {
                            $t.bootstrapTable("refresh");
                        } catch (e) {
                            /* table not ready */
                        }
                    }
                }, 350);
            }
            // Auto-filter: select2 fires "change"; the days field fires input/change.
            g.filters.forEach(function (sel) {
                $(document).on("change", sel, refresh);
                $(document).on("input", sel, refresh);
            });
            // Clear filters button logic
            $(document).on("click", g.btn, function (e) {
                e.preventDefault();
                g.filters.forEach(function (sel) {
                    var el = document.querySelector(sel);
                    if (el) {
                        if (el.tomselect) {
                            el.tomselect.clear(true);
                            $(el).trigger("change");
                        } else {
                            // Fallback for native inputs
                            $(el).val("");
                            $(el).trigger("change");
                        }
                    }
                });
                refresh();
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            initDashboardTableFilters,
        );
    } else {
        initDashboardTableFilters();
        $(function () {
            if (
                typeof window.initAdvancedDateRangePicker === "function" &&
                $("#task_date_between").length
            ) {
                window.initAdvancedDateRangePicker(
                    "#task_date_between",
                    "#task_date_between_from",
                    "#task_date_between_to",
                );
            }
        });
    }
})();

/* ============================================================
   Docked project detail panel — presentational helpers.
   No business logic: only fixes width-dependent rendering of the
   bootstrap-tables in the module tabs (they initialise at 0-width
   while their tab is hidden) by recomputing on tab-show.
   ============================================================ */
(function () {
    var SEL = "#project_detail_panel";

    // Re-fit the table of whichever module tab the user switches to.
    if (window.jQuery) {
        jQuery(document).on(
            "shown.bs.tab",
            SEL + ' [data-bs-toggle="tab"]',
            function (e) {
                var target = jQuery(e.target).attr("data-bs-target");
                if (target) {
                    jQuery(target)
                        .find('table[data-toggle="table"]')
                        .each(function () {
                            try {
                                jQuery(this).bootstrapTable("resetView");
                            } catch (err) {}
                        });
                }
            },
        );

        // The panel is visible from the start; nudge the active tab's table +
        // the statistics chart once the layout has settled.
        jQuery(function () {
            setTimeout(function () {
                try {
                    window.dispatchEvent(new Event("resize"));
                } catch (e) {}
                jQuery(
                    SEL + ' .tab-pane.active table[data-toggle="table"]',
                ).each(function () {
                    try {
                        jQuery(this).bootstrapTable("resetView");
                    } catch (err) {}
                });
            }, 300);
        });
    }
})();

/* ============================================================
   DOCKED TASK INSPECTOR (project board / list) — always-visible
   right column. Opens a task on card/row click; loads its modules
   lazily from existing endpoints (tasks.get / tasks.list /
   tasks.info / tasks.get-media). Tabs: Subtask · Comments ·
   Timeline · Media (embedded) + Activity (deep-link). No
   controller/route changes.
   ============================================================ */
(function () {
    if (!window.jQuery) return;
    var $ = jQuery;
    var $dock = $("#task_inspector");
    if (!$dock.length) return;

    var listUrl = $dock.data("tasks-list-url");
    var infoUrl = $dock.data("task-info-url");
    var mediaUrl = $dock.data("task-media-url");
    var getUrl = $dock.data("task-get-url");
    var commentBase = $dock.data("comment-url");
    var storageUrl = $dock.data("storage-url");
    var noImage = $dock.data("no-image");
    var tasksBase = String(listUrl || "").replace(/\/list$/, "");

    var currentTaskId = null;
    var loaded = {};
    var taskPageDoc = null;

    function csrf() {
        return (
            $('meta[name="csrf-token"]').attr("content") ||
            $dock.find('input[name="_token"]').val() ||
            ""
        );
    }
    function statuses() {
        return window.statusArray || [];
    }
    function priorities() {
        return window.priorityArray || [];
    }
    function byId(arr, id) {
        for (var i = 0; i < arr.length; i++) {
            if (String(arr[i].id) === String(id)) return arr[i];
        }
        return null;
    }
    function statusTitleById(id) {
        var s = byId(statuses(), id);
        return s ? s.title : "";
    }
    function doneStatusId() {
        var s = statuses();
        return s.length ? s[s.length - 1].id : null;
    }
    function todoStatusId() {
        var s = statuses();
        return s.length ? s[0].id : null;
    }
    function esc(t) {
        return $("<div>")
            .text(t == null ? "" : t)
            .html();
    }
    var COLORVAR = {
        success: "var(--ok)",
        danger: "var(--err)",
        warning: "var(--warn)",
        info: "var(--info)",
        primary: "var(--signal)",
        secondary: "var(--fg-3)",
    };
    function colorVar(c) {
        return COLORVAR[c] || "var(--fg-3)";
    }
    function parseStatusId(html) {
        var m = /data-original-status-id=['"]?(\d+)/.exec(html || "");
        return m ? m[1] : null;
    }
    function parseTitle(html) {
        var $h = $("<div>").html(html || "");
        var t = $h.find("strong").first().text();
        return t || $.trim($h.text());
    }
    function row(lbl, val) {
        return (
            '<div class="tk-insp-lbl">' +
            esc(lbl) +
            '</div><div class="tk-insp-val">' +
            val +
            "</div>"
        );
    }

    function photoSrc(p) {
        return p ? storageUrl + "/" + p : noImage;
    }
    function avatarsFromUsers(users) {
        users = users || [];
        if (!users.length)
            return '<span style="font-size:12px;color:var(--fg-3)">—</span>';
        var h = '<span class="av-stack tk-av-stack">',
            n = 0;
        users.forEach(function (u) {
            if (n < 5) {
                h +=
                    '<span class="av" title="' +
                    esc((u.first_name || "") + " " + (u.last_name || "")) +
                    '"><img src="' +
                    esc(photoSrc(u.photo)) +
                    '" onerror="this.onerror=null;this.src=DEFAULT_IMG" alt=""></span>';
                n++;
            }
        });
        if (users.length > 5)
            h += '<span class="av av-more">+' + (users.length - 5) + "</span>";
        h = h.replace(
            /DEFAULT_IMG/g,
            "'" + String(noImage).replace(/'/g, "%27") + "'",
        );
        return h + "</span>";
    }

    function openTask(taskId, $card) {
        if (!taskId) return;
        currentTaskId = taskId;
        loaded = {};
        taskPageDoc = null;
        $("#tk_insp_empty").hide();
        $("#tk_insp_content").show();
        $("#tk_insp_foot").show();
        $("#tk_insp_task_id").val(taskId);
        $("#tk_insp_open").attr("href", infoUrl + "/" + taskId);
        $("#tk_insp_scroll").scrollTop(0);
        activateTab("subtask");
        if ($card && $card.length) {
            metaFromCard($card, taskId);
        } else {
            metaFromJson(taskId);
        }
        loadTab("subtask");
    }

    function metaFromCard($card, taskId) {
        var code = $.trim($card.find(".tcard-code").first().text());
        var title = $.trim($card.find(".tcard-title").first().text());
        var statusId = $card.closest(".kanban-tasks").data("status");
        var stTitle = statusTitleById(statusId);
        var $prio = $card.find(".tag-priority").first();
        var prioTxt = $.trim($prio.text());
        var prioColor = $prio.length ? $prio.css("color") : "";
        var dueTxt = $.trim($card.find(".tag-due").first().text());
        var $assignees = $card.find(".tcard-foot .av-stack").first().clone();
        $assignees.find(".av-add").remove();
        $("#tk_insp_code").text(code || "#" + taskId);
        $("#tk_insp_status").html('<span class="dot"></span>' + esc(stTitle));
        $("#tk_insp_title").text(title);
        var html = "";
        if (prioTxt)
            html += row(
                "Priority",
                '<span class="mono" style="color:' +
                    prioColor +
                    '">' +
                    esc(prioTxt) +
                    "</span>",
            );
        html += row("Assignees", '<span id="tk_insp_assignees"></span>');
        if (dueTxt)
            html += row("Due", '<span class="mono">' + esc(dueTxt) + "</span>");
        if (stTitle) html += row("Status", esc(stTitle));
        $("#tk_insp_meta").html(html);
        if ($assignees.length) $("#tk_insp_assignees").replaceWith($assignees);
        else $("#tk_insp_assignees").text("—");
    }

    function metaFromJson(taskId) {
        $("#tk_insp_code").text("#" + taskId);
        $("#tk_insp_title").text("");
        $("#tk_insp_meta").html(
            '<div class="tk-insp-skel"></div><div class="tk-insp-skel" style="width:60%"></div>',
        );
        if (!getUrl) return;
        $.getJSON(getUrl + "/" + taskId)
            .done(function (res) {
                if (!res || res.error || !res.task) {
                    $("#tk_insp_meta").html("");
                    return;
                }
                var t = res.task;
                var stTitle = statusTitleById(t.status_id);
                $("#tk_insp_code").text("#" + t.id);
                $("#tk_insp_status").html(
                    '<span class="dot"></span>' + esc(stTitle),
                );
                $("#tk_insp_title").text(t.title || "");
                var html = "";
                var pr = byId(priorities(), t.priority_id);
                if (pr)
                    html += row(
                        "Priority",
                        '<span class="mono" style="color:' +
                            colorVar(pr.color) +
                            '">● ' +
                            esc(pr.title) +
                            "</span>",
                    );
                html += row("Assignees", avatarsFromUsers(t.users));
                if (t.due_date)
                    html += row(
                        "Due",
                        '<span class="mono">' + esc(t.due_date) + "</span>",
                    );
                if (stTitle) html += row("Status", esc(stTitle));
                $("#tk_insp_meta").html(html);
            })
            .fail(function () {
                $("#tk_insp_meta").html("");
            });
    }

    function activateTab(name) {
        $("#tk_insp_tabs .tk-insp-tab")
            .removeClass("active")
            .filter('[data-insp-tab="' + name + '"]')
            .addClass("active");
        $("#tk_insp_panes .tk-insp-pane")
            .removeClass("active")
            .filter('[data-insp-pane="' + name + '"]')
            .addClass("active");
    }
    $dock.on("click", ".tk-insp-tab", function () {
        var name = $(this).data("insp-tab");
        activateTab(name);
        loadTab(name);
    });

    function loadTab(name) {
        if (!currentTaskId) return;
        if (loaded[name]) return;
        loaded[name] = true;
        if (name === "subtask") return loadSubtasks(currentTaskId);
        if (name === "media") return loadMedia(currentTaskId);
        if (name === "activity") return fillActivity(currentTaskId);
        if (name === "comments" || name === "timeline")
            return loadTaskPage(currentTaskId);
    }

    function loadSubtasks(taskId) {
        var $p = $("#tk_insp_pane_subtask");
        $p.html(
            '<div class="tk-insp-skel"></div><div class="tk-insp-skel" style="width:80%"></div>',
        );
        if (!listUrl) {
            $p.html('<div class="tk-insp-emptyline">—</div>');
            return;
        }
        $.ajax({
            url: listUrl,
            data: { task_parent_id: taskId, limit: 100 },
            dataType: "json",
        })
            .done(function (res) {
                renderSubtasks((res && res.rows) || []);
            })
            .fail(function () {
                $p.html(
                    '<div class="tk-insp-emptyline">Could not load subtasks.</div>',
                );
            });
    }
    function renderSubtasks(rows) {
        var $p = $("#tk_insp_pane_subtask");
        var total = rows.length,
            done = 0,
            dId = doneStatusId();
        if (!total) {
            $p.html('<div class="tk-insp-emptyline">No subtasks.</div>');
            return;
        }
        var items = "";
        rows.forEach(function (r) {
            var sid = parseStatusId(r.status_id);
            var isDone = dId && String(sid) === String(dId);
            if (isDone) done++;
            items +=
                '<label class="tk-subtask' +
                (isDone ? " is-done" : "") +
                '" data-sub-id="' +
                r.id +
                '">';
            items +=
                '<input type="checkbox" ' + (isDone ? "checked" : "") + ">";
            items +=
                '<span class="tk-subtask-title">' +
                esc(parseTitle(r.title)) +
                "</span></label>";
        });
        var pct = total ? Math.round((done / total) * 100) : 0;
        $p.html(
            '<div class="tk-insp-sechead"><strong>Subtasks</strong><span class="tk-insp-count" id="tk_insp_subcount">' +
                done +
                "/" +
                total +
                "</span></div>" +
                '<div class="tk-insp-progress"><span id="tk_insp_progress" style="width:' +
                pct +
                '%"></span></div>' +
                '<div class="tk-subtask-list">' +
                items +
                "</div>",
        );
    }
    function updateSubCount() {
        var $list = $("#tk_insp_pane_subtask");
        var total = $list.find(".tk-subtask").length;
        var done = $list.find(".tk-subtask input:checked").length;
        $("#tk_insp_subcount").text(done + "/" + total);
        $("#tk_insp_progress").css(
            "width",
            (total ? Math.round((done / total) * 100) : 0) + "%",
        );
    }
    $dock.on("change", ".tk-subtask input[type=checkbox]", function () {
        var $r = $(this).closest(".tk-subtask");
        var sid = $r.data("sub-id");
        var checked = this.checked,
            self = this;
        var target = checked ? doneStatusId() : todoStatusId();
        if (!target || !tasksBase) return;
        $r.toggleClass("is-done", checked);
        updateSubCount();
        $.ajax({
            url: tasksBase + "/" + sid + "/update-status/" + target,
            method: "PUT",
            headers: { "X-CSRF-TOKEN": csrf() },
        }).fail(function () {
            self.checked = !checked;
            $r.toggleClass("is-done", self.checked);
            updateSubCount();
        });
    });

    function loadTaskPage(taskId) {
        $("#tk_insp_pane_comments").html(
            '<div class="tk-insp-skel"></div><div class="tk-insp-skel" style="width:70%"></div>',
        );
        $("#tk_insp_pane_timeline").html('<div class="tk-insp-skel"></div>');
        if (!infoUrl) return;
        $.ajax({ url: infoUrl + "/" + taskId, dataType: "html" })
            .done(function (htmlStr) {
                // Full HTML document — parse with DOMParser (jQuery .html() drops
                // <html>/<head>/<body> and mangles the panes).
                try {
                    taskPageDoc = new DOMParser().parseFromString(
                        htmlStr,
                        "text/html",
                    );
                } catch (e) {
                    taskPageDoc = null;
                }
                fillComments();
                fillTimeline();
            })
            .fail(function () {
                $("#tk_insp_pane_comments").html(
                    '<div class="tk-insp-emptyline">Could not load comments.</div>',
                );
                $("#tk_insp_pane_timeline").html(
                    '<div class="tk-insp-emptyline">Could not load timeline.</div>',
                );
            });
    }
    function paneHtml(sel) {
        if (!taskPageDoc || !taskPageDoc.querySelector) return null;
        var el = taskPageDoc.querySelector(sel);
        return el ? el.innerHTML : null;
    }
    function hasText(html) {
        return html && $.trim($("<div>").html(html).text()) !== "";
    }
    function fillComments() {
        var $p = $("#tk_insp_pane_comments");
        var html = paneHtml("#navs-top-discussions");
        if (hasText(html)) {
            $p.html('<div class="tk-insp-activity">' + html + "</div>");
        } else {
            $p.html('<div class="tk-insp-emptyline">No comments yet.</div>');
        }
    }
    function fillTimeline() {
        var $p = $("#tk_insp_pane_timeline");
        var html = paneHtml("#navs-top-status_timeline");
        if (hasText(html)) {
            $p.html('<div class="tk-insp-timeline">' + html + "</div>");
        } else {
            $p.html(
                '<div class="tk-insp-emptyline">No timeline entries.</div>',
            );
        }
    }

    function loadMedia(taskId) {
        var $p = $("#tk_insp_pane_media");
        $p.html('<div class="tk-insp-skel"></div>');
        if (!mediaUrl) {
            $p.html('<div class="tk-insp-emptyline">—</div>');
            return;
        }
        $.getJSON(mediaUrl + "/" + taskId)
            .done(function (res) {
                var rows = (res && res.rows) || [];
                if (!rows.length) {
                    $p.html('<div class="tk-insp-emptyline">No media.</div>');
                    return;
                }
                // `file` is server-rendered HTML (image thumbnail link or download link).
                var h = '<div class="tk-insp-media-grid">';
                rows.forEach(function (m) {
                    h +=
                        '<div class="tk-insp-media">' +
                        (m.file || "") +
                        '<span class="tk-insp-media-name">' +
                        esc(m.file_name || "") +
                        "</span></div>";
                });
                $p.html(h + "</div>");
            })
            .fail(function () {
                $p.html(
                    '<div class="tk-insp-emptyline">Could not load media.</div>',
                );
            });
    }

    function fillActivity(taskId) {
        $("#tk_insp_pane_activity").html(
            '<div class="tk-insp-emptyline" style="text-align:center;padding:18px 8px;">' +
                "Activity log opens in the full task view.<br>" +
                '<a class="btn btn-sm btn-outline-secondary mt-2" target="_blank" href="' +
                esc(infoUrl + "/" + taskId) +
                '#navs-top-activity-log">Open activity</a>' +
                "</div>",
        );
    }

    $("#tk_insp_comment_form").on("submit", function (e) {
        e.preventDefault();
        var taskId = $("#tk_insp_task_id").val();
        var content = $.trim($("#tk_insp_comment_input").val());
        if (!taskId || !content || !commentBase) return;
        var fd = new FormData();
        fd.append("model_type", "App\\Models\\Task");
        fd.append("model_id", taskId);
        fd.append("parent_id", "");
        fd.append("content", content);
        fd.append("_token", csrf());
        var $btn = $("#tk_insp_comment_submit");
        $btn.prop("disabled", true);
        $.ajax({
            url: commentBase + "/" + taskId + "/comments",
            method: "POST",
            data: fd,
            processData: false,
            contentType: false,
        })
            .done(function () {
                $("#tk_insp_comment_input").val("");
                activateTab("comments");
                taskPageDoc = null;
                loaded["comments"] = false;
                loaded["timeline"] = false;
                loadTab("comments");
            })
            .always(function () {
                $btn.prop("disabled", false);
            });
    });

    $(document).on("click", "#project_task_board .tcard", function (e) {
        if (
            $(e.target).closest(
                ".tcard-actions, .tcard-stats, .av-stack, .dropdown, [data-bs-toggle]",
            ).length
        )
            return;
        if ($(this).hasClass("gu-transit") || $(this).hasClass("gu-mirror"))
            return;
        e.preventDefault();
        $("#project_task_board .tcard").removeClass("tk-card-active");
        $(this).addClass("tk-card-active");
        openTask($(this).data("task-id"), $(this));
    });

    $(document).on(
        "click",
        'table[data-toggle="table"] tbody tr',
        function (e) {
            if (!$("#task_inspector").length) return;
            if (
                $(e.target).closest(
                    "a, button, input, select, .dropdown, label, .form-check, [data-bs-toggle]",
                ).length
            )
                return;
            var id = $(this).find("[data-task-row-id]").data("task-row-id");
            if (!id) {
                var href =
                    $(this)
                        .find('a[href*="/tasks/information/"]')
                        .attr("href") || "";
                var m = /\/tasks\/information\/(\d+)/.exec(href);
                id = m ? m[1] : null;
            }
            if (!id) return;
            $('table[data-toggle="table"] tbody tr').removeClass(
                "tk-row-active",
            );
            $(this).addClass("tk-row-active");
            openTask(id, null);
        },
    );

    $(document).on("input", "#tk_board_search", function () {
        var q = $.trim(this.value).toLowerCase();
        $("#project_task_board .tcard").each(function () {
            var t = $(this).find(".tcard-title").text().toLowerCase();
            $(this).toggleClass("tk-hide", !!q && t.indexOf(q) === -1);
        });
        $("#project_task_board .kcol").each(function () {
            $(this)
                .find(".kcol-count")
                .text($(this).find(".tcard:not(.tk-hide)").length);
        });
    });
})();

/* Board search (standalone) — filters kanban cards by title and updates
   column counts. Kept independent of the (now optional) task inspector. */
(function () {
    if (!window.jQuery) return;
    jQuery(document).on("input", "#tk_board_search", function () {
        var q = jQuery.trim(this.value).toLowerCase();
        jQuery("#project_task_board .tcard").each(function () {
            var t = jQuery(this).find(".tcard-title").text().toLowerCase();
            jQuery(this).toggleClass("tk-hide", !!q && t.indexOf(q) === -1);
        });
        jQuery("#project_task_board .kcol").each(function () {
            jQuery(this)
                .find(".kcol-count")
                .text(jQuery(this).find(".tcard:not(.tk-hide)").length);
        });
    });
})();

/* Project detail panel — expand / collapse toggle (toolbar button opens,
   the panel's × closes; last state remembered). */
(function () {
    if (!window.jQuery) return;
    var $ = jQuery;
    function setCollapsed(collapsed) {
        $("#tk_workspace").toggleClass("tk-detail-collapsed", collapsed);
        $("#tk_detail_toggle").toggleClass("active", !collapsed);
        try {
            localStorage.setItem(
                "tkProjectDetailCollapsed",
                collapsed ? "1" : "0",
            );
        } catch (e) {}
    }
    $(document).on("click", "#tk_detail_toggle", function () {
        setCollapsed(!$("#tk_workspace").hasClass("tk-detail-collapsed"));
    });
    $(document).on("click", "#tk_detail_close", function () {
        setCollapsed(true);
    });
    $(function () {
        if (!$("#tk_workspace").length) return;
        var saved = null;
        try {
            saved = localStorage.getItem("tkProjectDetailCollapsed");
        } catch (e) {}
        if (saved === "1") setCollapsed(true);
    });
})();

/**
 * Fix: action dropdowns inside scrollable tables (.tk-table / bootstrap-table's
 * .fixed-table-body) get clipped by the container's overflow, so the menu appears
 * empty or cut off — most visible on short tables with few rows. While a dropdown
 * is open, allow the scroll containers to overflow; restore on close so horizontal
 * scrolling still works normally.
 */
(function ($) {
    if (typeof $ === "undefined") return;

    var CLIP_SELECTOR = ".tk-table, .tk-table .fixed-table-container, .tk-table .fixed-table-body";

    $(document).on("show.bs.dropdown", ".tk-table .dropdown", function () {
        $(this)
            .closest(".tk-table")
            .find(".fixed-table-container, .fixed-table-body")
            .addBack()
            .each(function () {
                // Remember the inline value (if any) so we can restore it exactly.
                if (this.getAttribute("data-prev-overflow") === null) {
                    this.setAttribute("data-prev-overflow", this.style.overflow || "");
                }
                this.style.overflow = "visible";
            });
    });

    $(document).on("hide.bs.dropdown", ".tk-table .dropdown", function () {
        $(this)
            .closest(".tk-table")
            .find(".fixed-table-container, .fixed-table-body")
            .addBack()
            .each(function () {
                var prev = this.getAttribute("data-prev-overflow");
                this.style.overflow = prev === null ? "" : prev;
                this.removeAttribute("data-prev-overflow");
            });
    });
})(window.jQuery || window.$);
