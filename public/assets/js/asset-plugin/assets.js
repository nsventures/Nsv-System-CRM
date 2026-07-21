(function ($) {
    "use strict";
    // ajax for all forms
    $(document).on("submit", ".asset-form-submit-event", function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var currentForm = $(this);
        var submit_btn = $(this).find("[type='submit']");
        var btn_html = submit_btn.html();
        var btn_val = submit_btn.val();
        var redirect_url = currentForm.find('input[name="redirect_url"]').val();
        redirect_url = typeof redirect_url !== "undefined" && redirect_url ? redirect_url : "";
        var button_text = btn_html != "" || btn_html != "undefined" ? btn_html : btn_val;
        var tableInput = currentForm.find('input[name="table"]');
        var tableID = tableInput.length ? tableInput.val() : "table";
        $.ajax({
            type: "POST",
            url: $(this).attr("action"),
            data: formData,
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            beforeSend: function () {
                submit_btn.html(label_please_wait || "Please wait...");
                submit_btn.attr("disabled", true);
            },
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (result) {
                submit_btn.html(button_text);
                submit_btn.attr("disabled", false);
                if (result["error"] == true) {
                    toastr.error(result["message"]);
                } else {
                    // Handle offcanvas closing
                    var activeOffcanvas = currentForm.closest('.offcanvas');
                    if (activeOffcanvas.length > 0) {
                        var offcanvasInstance = bootstrap.Offcanvas.getInstance(activeOffcanvas[0]);
                        if (offcanvasInstance) {
                            offcanvasInstance.hide();
                        }
                    }
                    // Handle "Do Not Reload" forms
                    if (currentForm.find('input[name="dnr"]').length > 0) {
                        // Refresh the table
                        $("#" + tableID).bootstrapTable("refresh");
                        // Reset the form
                        currentForm[0].reset();
                        // Clear any error messages
                        currentForm.find(".error-message").html("");
                        // Reset any custom fields (like image previews, etc.)
                        resetAssetFormFields(currentForm);
                        // Handle dropdown additions for asset-specific modals
                        handleAssetDropdownAdditions(result, currentForm);
                        // Show success message
                        toastr.success(result["message"]);
                    } else {
                        // Handle redirection for forms that need it
                        if (result.hasOwnProperty("message")) {
                            toastr.success(result["message"]);
                            setTimeout(handleAssetRedirection, parseFloat(toastTimeOut || 2000));
                        } else {
                            handleAssetRedirection();
                        }
                    }
                }
            },
            error: function (xhr, status, error) {
                submit_btn.html(button_text);
                submit_btn.attr("disabled", false);
                if (xhr.status === 422) {
                    // Handle validation errors
                    var response = xhr.responseJSON;
                    var errors = response.errors;
                    // Handle country code errors (if applicable)
                    if (errors["country_code"]) {
                        errors["phone"] = errors["country_code"];
                        delete errors["country_code"];
                    }
                    toastr.error(label_please_correct_errors || "Please correct the errors");
                    // Display validation errors
                    displayAssetValidationErrors(currentForm, errors);
                } else {
                    // Handle other errors
                    var response = xhr.responseJSON;
                    if (response && response.message) {
                        var errorMessage = response.message;
                        // Check for database access errors
                        var match = errorMessage.match(/Access denied for user '([^']+)'@/);
                        if (match) {
                            var dbUser = match[1];
                            var customErrorMessage = "Please try changing the password for database user " + dbUser + " or recreate the database.";
                            toastr.error(customErrorMessage);
                        } else {
                            // Handle SQL errors
                            var sqlErrorPattern = /SQLSTATE\[[0-9]+\]: [^\(]+/;
                            if (sqlErrorPattern.test(errorMessage)) {
                                var shortErrorMessage = errorMessage.match(sqlErrorPattern)[0];
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
        function handleAssetRedirection() {
            if (redirect_url === "") {
                window.location.reload();
            } else {
                window.location.href = redirect_url;
            }
        }
    });
    // Function to reset asset-specific form fields
    function resetAssetFormFields(form) {
        // Reset image previews
        form.find('#create-current-picture-preview, #edit-current-picture-preview, #update-current-picture-preview').hide();
        form.find('#create-no-image-placeholder, #edit-no-image-placeholder, #update-no-image-placeholder').show();
        form.find('#create-image-actions, #edit-image-actions, #update-image-actions').hide();
        form.find('.asset-picture-input').val('');
        // Reset any select2 dropdowns
        form.find('.select2').trigger('change');
        // Reset any tomselect dropdowns
        form.find('.tom_static_select').each(function () {
            if (this.tomselect) {
                this.tomselect.clear();
            }
        });
        // Reset any other custom fields specific to assets
        form.find('input[type="date"]').val('');
        form.find('input[type="number"]').val('');
        form.find('textarea').val('');
    }
    // Function to handle dropdown additions for asset forms
    function handleAssetDropdownAdditions(result, currentForm) {
        var activeOffcanvas = currentForm.closest('.offcanvas');
        var offcanvasId = activeOffcanvas.attr('id');
        // Handle category additions
        if (result.category) {
            var categorySelectors = [
                '#createAssetOffcanvas .select-asset-category',
                '#editAssetOffcanvas .select-asset-category'
            ];
            categorySelectors.forEach(function (selector) {
                var dropdown = $(selector);
                if (dropdown.length) {
                    var newOption = $('<option></option>')
                        .attr('value', result.category.id)
                        .text(result.category.name);
                    // Select the option only in the current offcanvas
                    if (selector.includes(offcanvasId)) {
                        newOption.attr('selected', true);
                    }
                    dropdown.append(newOption);
                    dropdown.trigger('change');
                }
            });
        }
    }
    // Function to display validation errors for asset forms
    function displayAssetValidationErrors(form, errors) {
        // Clear existing error messages
        form.find('.error-message').remove();
        // Get all input fields
        var inputFields = form.find("input[name], select[name], textarea[name]");
        inputFields = $(inputFields.toArray().reverse());
        var firstErrorField = null;
        // Iterate through all input fields
        inputFields.each(function () {
            var inputField = $(this);
            var fieldName = inputField.attr("name");
            if (errors && errors[fieldName]) {
                if (!firstErrorField) {
                    firstErrorField = inputField;
                }
                var errorMessageElement = $('<span class="text-danger error-message"></span>');
                if (inputField.attr("type") !== "radio" && inputField.attr("type") !== "hidden") {
                    // Handle different input types
                    if (inputField.hasClass("select2-hidden-accessible")) {
                        inputField.parent().find(".text-danger.error-message").remove();
                        inputField.siblings(".select2").after(errorMessageElement);
                    } else if (inputField.hasClass("tomselected") || inputField.next().hasClass("ts-wrapper")) {
                        inputField.parent().find(".text-danger.error-message").remove();
                        inputField.siblings(".ts-wrapper").after(errorMessageElement);
                    } else if (inputField.closest(".input-group-merge").length > 0) {
                        var inputGroup = inputField.closest(".input-group-merge");
                        inputGroup.next(".text-danger.error-message").remove();
                        inputGroup.after(errorMessageElement);
                    } else if (inputField.closest(".input-group").length > 0) {
                        var inputGroup = inputField.closest(".input-group");
                        inputGroup.next(".text-danger.error-message").remove();
                        inputGroup.after(errorMessageElement);
                    } else {
                        inputField.next(".text-danger.error-message").remove();
                        inputField.after(errorMessageElement);
                    }
                    // Set error message text
                    if (errors[fieldName][0] && errors[fieldName][0].includes("required")) {
                        errorMessageElement.text("This field is required.");
                    } else {
                        errorMessageElement.text(Array.isArray(errors[fieldName]) ? errors[fieldName][0] : errors[fieldName]);
                    }
                }
            } else {
                // Clear existing error messages for fields without errors
                var existingErrorMessage = inputField.next(".text-danger.error-message");
                if (inputField.hasClass("select2-hidden-accessible")) {
                    existingErrorMessage = inputField.parent().find(".text-danger.error-message");
                } else if (inputField.hasClass("tomselected") || inputField.next().hasClass("ts-wrapper")) {
                    existingErrorMessage = inputField.parent().find(".text-danger.error-message");
                } else if (inputField.closest(".input-group-merge").length > 0) {
                    var inputGroup = inputField.closest(".input-group-merge");
                    existingErrorMessage = inputGroup.next(".text-danger.error-message");
                } else if (inputField.closest(".input-group").length > 0) {
                    var inputGroup = inputField.closest(".input-group");
                    existingErrorMessage = inputGroup.next(".text-danger.error-message");
                }
                if (existingErrorMessage.length > 0) {
                    existingErrorMessage.remove();
                }
            }
        });
        // Scroll to first error field
        if (firstErrorField) {
            firstErrorField[0].scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
            firstErrorField.focus();
        }
    }
    // For searching in table
    function queryParams(params) {
        const query = {
            limit: params.limit,
            offset: params.offset,
            search: params.search,
            sort: $('#sort').val(),
            order: params.order,
            categories: $('#select_categories').val(),
            assigned_to: $('#select_assigned_to').val(),
            asset_status: $('#asset_status').val(),
        };
        return query;
    }
    $(document).ready(function () {
        $('#select_categories').on('change', function () {
            $('#table').bootstrapTable('refresh');
        });
        $('#select_assigned_to').on('change', function () {
            $('#table').bootstrapTable('refresh');
        });
        $('#asset_status').on('change', function () {
            $('#table').bootstrapTable('refresh');
        });
    });
    // Modal event handlers
    $(document).on('click', '#createCategoryModalBtn', function () {
        $('#createCategoryModal').modal('show');
    });
    // For duplicating asset
    $(document).on('click', '.duplicateAsset', function () {
        const asset = $(this).data('asset');
        const actionUrl = `/assets/duplicate/${asset.id}`;
        $('#duplicateForm').attr('action', actionUrl);
        $('#duplicateAssetModal').modal('show');
    });
    // For showing and filling update asset category modal
    $(document).on('click', '.updateCategoryModal', function () {
        const assetCategory = $(this).data('asset-category');
        const color = assetCategory.color;
        const actionUrl = `/assets/category/update/${assetCategory.id}`;
        $('#updateCategoryForm').attr('action', actionUrl);
        $('#categoryName').val(assetCategory.name);
        $('#categoryDescription').val(assetCategory.description);
        $('#category_color').val(color).change();
        $('#category_color')
            .removeClass('select-bg-label-primary select-bg-label-secondary select-bg-label-success select-bg-label-danger select-bg-label-warning select-bg-label-info select-bg-label-dark')
            .addClass(`select-bg-label-${color}`);
        $('#updateCategoryModal').modal('show');
    });
    // Show update modal
    $(document).on('click', '.updateAssetOffcanvasBtn', function () {
        const asset = $(this).data('asset');
        const actionUrl = `/assets/update/${asset.id}`;
        $('#updateAssetForm').attr('action', actionUrl);
        $('#update-asset-name').val(asset.name);
        $('#update-asset-tag').val(asset.asset_tag);
        if ($('#update-asset-category')[0] && $('#update-asset-category')[0].tomselect) {
            $('#update-asset-category')[0].tomselect.setValue(asset.category_id);
        } else {
            $('#update-asset-category').val(asset.category_id).trigger('change');
        }
        if ($('#update-asset-assign-to')[0] && $('#update-asset-assign-to')[0].tomselect) {
            $('#update-asset-assign-to')[0].tomselect.setValue(asset.assigned_to);
        } else {
            $('#update-asset-assign-to').val(asset.assigned_to).trigger('change');
        }
        $('#update-asset-purchase-date').val(asset.purchase_date);
        $('#update-asset-purchase-cost').val(asset.purchase_cost);
        $('#update-asset-description').val(asset.description);
        $('#updateAssetForm').attr('action', `/assets/update/${asset.id}`);
        if (asset.picture_url) {
            showImagePreview('update', asset.picture_url);
        } else {
            resetImagePreview('update');
        }
        if (asset.status == 'lent') {
            $('#update_asset_status_field').hide();
            $('#update-lent-status').remove();
            $('#updateAssetForm').append(`<input type="hidden" id="update-lent-status" name="status" value="${asset.status}">`);
            $('#update-asset-status').removeAttr('name');
        } else {
            $('#update_asset_status_field').show();
            $('#update-lent-status').remove();
            if ($('#update-asset-status')[0] && $('#update-asset-status')[0].tomselect) {
                $('#update-asset-status')[0].tomselect.setValue(asset.status);
            } else {
                $('#update-asset-status').val(asset.status).trigger('change');
            }
        }
        $('#update-asset-picture').val('');
        $('#updateAssetOffcanvas').offcanvas('show');
    });
    // Handle file input change for both modals
    $(document).on('change', '.asset-picture-input', function () {
        const file = this.files[0];
        const modal = $(this).data('modal');
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                showImagePreview(modal, e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
    // Open full image in lightbox
    $(document).on('click', '.open-full-image-btn', function () {
        const targetImg = $(this).data('target');
        const imgSrc = $(`#${targetImg}`).attr('src');
        if (imgSrc) {
            $('#lightboxImage').attr('src', imgSrc);
            $('#imageLightboxModal').modal('show');
        }
    });
    // Also allow clicking on image to open lightbox
    $(document).on('click', '#create-preview-image, #update-preview-image', function () {
        const imgSrc = $(this).attr('src');
        if (imgSrc) {
            $('#lightboxImage').attr('src', imgSrc);
            $('#imageLightboxModal').modal('show');
        }
    });
    // Remove image functionality
    $(document).on('click', '.remove-image-btn', function () {
        const modal = $(this).data('modal');
        resetImagePreview(modal);
        $(`#${modal}-asset-picture`).val('');
        $('#update_remove_picture').val(1);
    });
    // Hover effect for image preview
    $(document).on('mouseenter', '#create-preview-image, #update-preview-image', function () {
        $(this).siblings('.hover-overlay').removeClass('opacity-0').addClass('opacity-100');
    });
    $(document).on('mouseleave', '#create-preview-image, #update-preview-image', function () {
        $(this).siblings('.hover-overlay').removeClass('opacity-100').addClass('opacity-0');
    });
    // Reset modal/offcanvas forms when they are hidden
    $(document).on('hidden.bs.offcanvas', '#createAssetOffcanvas', function () {
        $('#assetForm')[0].reset();
        resetImagePreview('create');
    });
    $(document).on('hidden.bs.offcanvas', '#updateAssetOffcanvas', function () {
        $('#updateAssetForm')[0].reset();
        resetImagePreview('update');
    });
    // Helper functions
    function showImagePreview(modal, src) {
        $(`#${modal}-preview-image`).attr('src', src);
        $(`#${modal}-current-picture-preview`).show();
        $(`#${modal}-image-actions`).removeClass('d-none');
        $(`#${modal}-no-image-placeholder`).hide();
    }
    function resetImagePreview(modal) {
        $(`#${modal}-current-picture-preview`).hide();
        $(`#${modal}-image-actions`).addClass('d-none');
        $(`#${modal}-no-image-placeholder`).show();
        $(`#${modal}-preview-image`).attr('src', '');
    }
    // Lend Asset Form Submission
    const lendAssetForm = document.getElementById('lendAssetForm');
    if (lendAssetForm) {
        lendAssetForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            try {
                const formData = new FormData(this);
                const assetId = formData.get('asset_id');
                const response = await fetch(`/assets/${assetId}/lend`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        lent_to: formData.get('assigned_to'),
                        estimated_return_date: formData.get('estimated_return_date'),
                        notes: formData.get('notes')
                    })
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'An error occurred while lending the asset.');
                }
                toastr.success(data.message);
                const modal = bootstrap.Modal.getInstance(document.getElementById('lendAssetModal'));
                modal.hide();
                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                toastr.error(error.message);
            }
        });
    }
    // Return Asset Form Submission
    const returnAssetForm = document.getElementById('returnAssetForm');
    if (returnAssetForm) {
        returnAssetForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            try {
                const formData = new FormData(this);
                const assetId = formData.get('asset_id');
                const response = await fetch(`/assets/${assetId}/return`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notes: formData.get('notes')
                    })
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'An error occurred while returning the asset.');
                }
                toastr.success(data.message);
                const modal = bootstrap.Modal.getInstance(document.getElementById('returnAssetModal'));
                modal.hide();
                setTimeout(() => { window.location.href = '/assets/index' }, 1500);
            } catch (error) {
                toastr.error(error.message);
            }
        });
    }
    // Set minimum date for estimated return date
    document.addEventListener('DOMContentLoaded', function () {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const estimatedReturnDateInput = document.getElementById('estimated_return_date');
        if (estimatedReturnDateInput) {
            estimatedReturnDateInput.min = tomorrow.toISOString().slice(0, 16);
        }
    });
    function initAssetTomSelectWithAjax(selector, type) {
        document.querySelectorAll(selector).forEach(function (el) {
            if (el.tomselect) {
                el.tomselect.destroy();
            }

            var allowClear = el.dataset.allowClear !== "false";

            new TomSelect(el, {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                plugins: allowClear ? ['remove_button', 'clear_button'] : ['remove_button'],
                preload: true,
                load: function (query, callback) {
                    var url = '/assets/search-assets?q=' + encodeURIComponent(query) + '&type=' + encodeURIComponent(type);
                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            callback(json.results);
                        }).catch(() => {
                            callback();
                        });
                }
            });
        });
    }

    $(document).ready(function () {
        // Initialize dynamic Tom Select filters
        initAssetTomSelectWithAjax('.select-asset-category', 'asset_category');
        initAssetTomSelectWithAjax('.select-asset-category_in_filter', 'asset_category');
        initAssetTomSelectWithAjax('.select-asset-assigned_to', 'users');
        initAssetTomSelectWithAjax('.select-asset-assigned_to_in_filter', 'users');
        initAssetTomSelectWithAjax('.select-assets', 'assets');

        if (typeof initTomSelectStatic === 'function') {
            initTomSelectStatic('.tom_static_select');
        }

        // Initialize TableFilterSync for assets
        if (typeof TableFilterSync === 'function') {
            new TableFilterSync({
                tableId: 'table',
                dataType: 'assets',
                filters: [
                    {
                        selector: '#select_categories',
                        type: 'tom-select',
                        name: 'categories',
                        ajaxType: null
                    },
                    {
                        selector: '#select_assigned_to',
                        type: 'tom-select',
                        name: 'assigned_to',
                        ajaxType: null
                    },
                    {
                        selector: '#asset_status',
                        type: 'tom-select',
                        name: 'asset_status',
                        ajaxType: null
                    }
                ],
                preserveParams: [''],
                queryParamsFn: queryParams
            });
        }
    });

    // Assets plugin main functionality
    $(document).ready(function () {
        // Ensure CSRF token is included in AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        // Handle form submission for bulkAssetsUploadModal
        $('#bulkAssetsUploadOffcanvas .form-submit-event').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $('#submit_btn');
            var $uploadErrors = $('#uploadErrors');
            var $uploadErrorsList = $('#uploadErrorsList');
            // Clear previous errors
            $uploadErrors.addClass('d-none').find('#uploadErrorsList').empty();
            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> {{ get_label("importing", "Importing...") }}');
            var formData = new FormData($form[0]);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.error) {
                        // Handle validation errors - keep canvas open and show errors
                        if (response.validation_errors && response.validation_errors.length > 0) {
                            let errorHtml = '';
                            response.validation_errors.forEach(function (error) {
                                errorHtml += `<li>Row ${error.row}: ${error.messages.join(', ')}</li>`;
                            });
                            $uploadErrorsList.html(errorHtml);
                        } else {
                            $uploadErrorsList.html(`<li>${response.message}</li>`);
                        }
                        $uploadErrors.removeClass('d-none');
                    } else {
                        // Success - close canvas and reload page
                        $('#bulkAssetsUploadOffcanvas').modal('hide');
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function (xhr) {
                    // Handle different error scenarios - keep canvas open and show errors
                    if (xhr.status === 422) {
                        // Validation errors
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.validation_errors && response.validation_errors.length > 0) {
                                let errorHtml = '';
                                response.validation_errors.forEach(function (error) {
                                    errorHtml += `<li>Row ${error.row}: ${error.messages.join(', ')}</li>`;
                                });
                                $uploadErrorsList.html(errorHtml);
                            } else {
                                $uploadErrorsList.html(`<li>${response.message || 'Validation failed.'}</li>`);
                            }
                        } catch (e) {
                            $uploadErrorsList.html('<li>An error occurred during validation. Please try again.</li>');
                        }
                        $uploadErrors.removeClass('d-none');
                    } else if (xhr.status === 413) {
                        // File too large
                        $uploadErrorsList.html('<li>The uploaded file is too large. Please try a smaller file.</li>');
                        $uploadErrors.removeClass('d-none');
                    } else if (xhr.status === 500) {
                        // Server error
                        try {
                            var response = JSON.parse(xhr.responseText);
                            $uploadErrorsList.html(`<li>${response.message || 'An internal server error occurred.'}</li>`);
                        } catch (e) {
                            $uploadErrorsList.html('<li>An internal server error occurred. Please try again.</li>');
                        }
                        $uploadErrors.removeClass('d-none');
                    } else {
                        // Generic error
                        $uploadErrorsList.html('<li>An error occurred during the upload. Please try again.</li>');
                        $uploadErrors.removeClass('d-none');
                    }
                },
                complete: function () {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).html('{{ get_label("import", "Import") }}');
                }
            });
        });
        // Client-side file validation
        $('#bulkAssetsUploadOffcanvas input[name="file"]').on('change', function () {
            var file = this.files[0];
            var $uploadErrors = $('#uploadErrors');
            var $uploadErrorsList = $('#uploadErrorsList');
            if (file) {
                var ext = file.name.split('.').pop().toLowerCase();
                if (!['xlsx', 'xls', 'csv'].includes(ext)) {
                    $uploadErrorsList.html('<li>Please upload a valid Excel or CSV file.</li>');
                    $uploadErrors.removeClass('d-none');
                    this.value = '';
                } else {
                    $uploadErrors.addClass('d-none').find('#uploadErrorsList').empty();
                }
            }
        });
        // Function to initialize Asset Analytics Chart
        function initAssetAnalyticsChart() {
            if (typeof window.assetAnalyticsData !== 'undefined' && $('#statusChart').length > 0) {
                var options = {
                    chart: {
                        type: 'pie',
                        height: 350
                    },
                    series: window.assetAnalyticsData.statusValues,
                    labels: window.assetAnalyticsData.statusLabels,
                    colors: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#343a40',
                        '#6c757d',
                        '#17a2b8'
                    ],
                    legend: {
                        position: 'bottom'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };
                var chart = new ApexCharts(document.querySelector("#statusChart"), options);
                chart.render();
            }
        }
        initAssetAnalyticsChart();
    });
})(jQuery);
