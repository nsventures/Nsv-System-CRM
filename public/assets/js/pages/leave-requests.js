
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
// Initialize advanced date range filters with presets
$(document).ready(function () {
    // Initialized via custom.js standardized loop for standard filter IDs
});

function queryParamsLr(p) {

    return {
        "statuses": $('#lr_status_filter').val(),
        "user_ids": $('#lr_user_filter').val(),
        "action_by_ids": $('#lr_action_by_filter').val(),
        "lr_date_between_from": $('#lr_date_between_from').val(),
        "lr_date_between_to": $('#lr_date_between_to').val(),
        "lr_start_date_from": $('#lr_start_date_from').val(),
        "lr_start_date_to": $('#lr_start_date_to').val(),
        "lr_end_date_from": $('#lr_end_date_from').val(),
        "lr_end_date_to": $('#lr_end_date_to').val(),
        "types": $('#lr_type_filter').val(),
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}
function debounce(func, delay) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(this, args), delay);
    };
}
// Attach change event with debounce
addDebouncedEventListener('#lr_status_filter, #lr_user_filter, #lr_action_by_filter, #lr_type_filter', 'change', function (e, refreshTable) {
    e.preventDefault();
    if (typeof refreshTable === 'undefined' || refreshTable) {
        $('#lr_table').bootstrapTable('refresh');
    }
});

$(document).on('click', '.clear-leave-requests-filters', function (e) {
    e.preventDefault();
    window.clearDateRangeFilters('lr');
    $('#lr_status_filter, #lr_user_filter, #lr_action_by_filter, #lr_type_filter').val(null).trigger('change');
})



window.icons = {
    refresh: 'bx-refresh',
    toggleOn: 'bx-toggle-right',
    toggleOff: 'bx-toggle-left'
}

function loadingTemplate(message) {
    return '<i class="bx bx-loader-alt bx-spin bx-flip-vertical" ></i>'
}


$(document).ready(function () {
    if(!isAdminOrLe){
        $('.delete-selected ').addClass('d-none');
    }

});

$(document).ready(function () {
    // Initialize TableFilterSync for users
    const lrFilterSync = new TableFilterSync({
        tableId: 'lr_table',
        dataType: 'leave-requests',
        filters: [
            {
                selector: '#lr_date_between',
                type: 'daterangepicker',
                name: 'lr_date_between',
                hiddenFrom: '#lr_date_between_from',
                hiddenTo: '#lr_date_between_to'
            },
            {
                selector: '#lr_start_date_between',
                type: 'daterangepicker',
                name: 'lr_start_date_between',
                hiddenFrom: '#lr_start_date_from',
                hiddenTo: '#lr_start_date_to'
            },
            {
                selector: '#lr_end_date_between',
                type: 'daterangepicker',
                name: 'lr_end_date_between',
                hiddenFrom: '#lr_end_date_from',
                hiddenTo: '#lr_end_date_to'
            },

            {
                selector: '#lr_user_filter',
                type: 'tom-select',
                name: 'user_ids',
                ajaxType: 'users'
            },
            {
                selector: '#lr_action_by_filter',
                type: 'tom-select',
                name: 'action_by_ids',
                ajaxType: 'users'
            },

            {
                selector: '#lr_status_filter',
                type: 'tom-select',
                name: 'statuses',
                ajaxType: null
            },
            {
                selector: '#lr_type_filter',
                type: 'tom-select',
                name: 'types',
                ajaxType: null
            }

        ],
        preserveParams: [''],
        queryParamsFn: queryParamsLr // Reuse existing function
    });
});

// Set default toggle to ON when modal opens
$('#edit_leave_request_modal').on('show.bs.modal', function (e) {
    // Set toggle to ON by default (assume paid unless admin explicitly marks unpaid)
    setTimeout(function () {
        var toggle = $('#is_paid_toggle');
        toggle.prop('disabled', false); // Ensure not disabled
        toggle.prop('checked', true);
        console.log('Default toggle set to ON, clickable:', !toggle.prop('disabled'));
    }, 100); // Small delay to ensure DOM is ready
});

// Ensure toggle responds to clicks (both create and edit modals)
$(document).on('change', '#is_paid_toggle, #create_is_paid_toggle', function () {
    var isChecked = $(this).is(':checked');
    console.log('Toggle changed to:', isChecked ? 'ON (Paid)' : 'OFF (Unpaid)');

    // No visual feedback needed - Bootstrap handles the toggle color automatically
});

// Fetch balance when create modal opens
$('#create_leave_request_modal').on('show.bs.modal', function (e) {
    console.log('Create modal opened');

    // Clear previous balance info
    $('#create_leave_balance_info').html('<span class="text-muted">' + label_please_wait + '</span>');

    // Use authenticated user ID as default (since modal pre-selects it)
    var userId = typeof authUserId !== 'undefined' ? authUserId : null;
    console.log('Using default authenticated user ID:', userId);

    if (userId) {
        // Fetch balance immediately for default user
        fetchAndDisplayBalance(userId, '#create_leave_balance_info');
    } else {
        console.error('authUserId not defined - cannot fetch balance');
        $('#create_leave_balance_info').html('<span class="text-warning">Unable to load balance</span>');
    }
});

// Update balance when user selection changes in create modal
$(document).on('change', '#create_leave_request_modal .tom_users_select[name="user_id"]', function () {
    var userId = $(this).val();
    if (userId) {
        fetchAndDisplayBalance(userId, '#create_leave_balance_info');
    }
});

// Helper function to fetch and display balance
function fetchAndDisplayBalance(userId, targetElement) {
    console.log('fetchAndDisplayBalance called - User ID:', userId, 'Target:', targetElement);

    // Check if element exists
    if ($(targetElement).length === 0) {
        console.error('Target element not found:', targetElement);
        return;
    }

    $.ajax({
        url: '/leave-requests/get-user-balance',
        method: 'GET',
        data: { user_id: userId },
        success: function (response) {
            console.log('Balance API response:', response);
            if (!response.error && response.balance) {
                var balance = response.balance;
                console.log('Balance object:', balance);

                var balanceHtml = renderRemainingLeavesSummary(balance, {
                    heading: label_balance_snapshot,
                    includeAccrualMeta: true
                });

                console.log('Setting balance summary to element:', targetElement, 'Content:', balanceHtml);
                $(targetElement).html(balanceHtml);
                console.log('Balance summary set successfully');
            } else {
                console.error('Balance response error or missing balance data:', response);
                $(targetElement).html('<span class="text-danger">' + label_err_try_again + '</span>');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error fetching balance:', status, error);
            $(targetElement).html('<span class="text-danger">' + label_err_try_again + '</span>');
        }
    });
}

function initPaidLeaveWorkflowGuide() {
    var $modal = $('#paidLeaveWorkflowModal');
    if (!$modal.length) {
        return;
    }

    var $steps = $modal.find('.paid-leave-step');
    var $navItems = $modal.find('[data-step-nav]');
    var $prevBtn = $modal.find('.paid-leave-prev');
    var $nextBtn = $modal.find('.paid-leave-next');
    var $finishBtn = $modal.find('.paid-leave-finish');
    var totalSteps = $steps.length;
    var currentStep = 1;

    function renderStep(step) {
        currentStep = Math.max(1, Math.min(step, totalSteps));

        $steps.addClass('d-none');
        $steps.filter('[data-step="' + currentStep + '"]').removeClass('d-none');

        $navItems.each(function () {
            var navStep = parseInt($(this).data('step-nav'), 10);
            var isDisabled = navStep > currentStep;
            $(this)
                .toggleClass('active', navStep === currentStep)
                .toggleClass('disabled', isDisabled)
                .prop('disabled', isDisabled);

            if (navStep === currentStep) {
                $(this).attr('aria-current', 'step');
            } else {
                $(this).removeAttr('aria-current');
            }
        });

        $prevBtn.prop('disabled', currentStep === 1);
        $nextBtn.toggleClass('d-none', currentStep === totalSteps);
        $finishBtn.toggleClass('d-none', currentStep !== totalSteps);
    }

    $navItems.on('click', function () {
        if ($(this).hasClass('disabled')) {
            return;
        }
        var targetStep = parseInt($(this).data('step-nav'), 10);
        if (!isNaN(targetStep)) {
            renderStep(targetStep);
        }
    });

    $nextBtn.on('click', function () {
        renderStep(currentStep + 1);
    });

    $prevBtn.on('click', function () {
        renderStep(currentStep - 1);
    });

    $modal.on('shown.bs.modal', function () {
        renderStep(1);
    });
}

$(document).ready(function () {
    initPaidLeaveWorkflowGuide();
});
