'use strict';
var elements = [];

// Loop through the statusArray and generate elements
for (var i = 0; i < statusArray.length; i++) {
    var sts = statusArray[i];    
    var element = document.getElementById(sts.slug);

    // Check if the element exists before adding it to the elements array
    if (element) {
        elements.push(element);
    }
}

$(function () {
    var drake = dragula(elements, {
        revertOnSpill: true,
        moves: function (el, container, handle) {
            return el.classList.contains('tcard'); // Only drag task cards
        },
        accepts: function (el, target) {
            return el.classList.contains('tcard'); // Only drop inside columns
        },
        invalid: function (el, handle) {
            // Do not drag if clicking form inputs, buttons, or links
            if (el.tagName === 'A' || el.tagName === 'BUTTON' || el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA' || el.isContentEditable) {
                return true;
            }
            // Do not drag if clicking anything inside settings dropdown
            if (el.closest('.dropdown-menu')) {
                return true;
            }
            return false;
        }
    });

    if (!drake) {
        console.error("Dragula initialization failed");
        return;
    }

    var oldParent; // Variable to store the old parent element

    drake.on('drag', function (el, source) {
        // Store the old parent element when dragging starts
        oldParent = source;
    });

    drake.on('drop', function (el, target) {
        // Get the task ID and new status
        var taskId = el.getAttribute('data-task-id');
        var newStatus = target.getAttribute('data-status');

        // Make an AJAX call to update the task status
        $.ajax({
            method: "POST",
            url: baseUrl + '/tasks/' + taskId + '/update-status/' + newStatus,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                _method: "PUT",
                'flash_message_only': 1,
            },
            success: function (response) {
                if (response.error == false) {
                    toastr.success(response.message); // show a success message
                } else {
                    toastr.error(response.message);
                    // Revert back to the old status
                    drake.cancel(true); // Cancel the drop operation
                    // Manually revert the element to the old status
                    $(oldParent).append(el); // Append the element back to the old parent
                }
            },
            error: function () {
                toastr.error("An error occurred during the AJAX request");
                // Revert back to the old status
                drake.cancel(true); // Cancel the drop operation
                // Manually revert the element to the old status
                $(oldParent).append(el); // Append the element back to the old parent
            }
        });
    });
});

