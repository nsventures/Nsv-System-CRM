'use strict';

$(document).ready(function () {
    // Pull config injected by blade
    const { entity, routes, dbFields, displayColumns, labels } = BULK_IMPORT_CONFIG;

    const SELECTORS = {
        STEPS: {
            CONTENT: ['#step1-content', '#step2-content', '#step3-content'],
            TABS: ['#step1-tab', '#step2-tab', '#step3-tab']
        },
        FORMS: { UPLOAD: '#upload-form', MAPPING: '#mapping-form' },
        ALERTS: {
            CONTAINER: '#alert-container',
            MAPPING_ERROR: '#mapping-error-alert',
            MAPPING_SUCCESS: '#mapping-success-alert'
        },
        PREVIEWS: { RAW: '#raw-preview', MAPPED: '#mapped-preview' },
        BUTTONS: {
            SUBMIT: '#submit-btn',
            PREVIEW: '#preview-mapped-leads',
            BACK: '#back-to-step1',
            NEW_IMPORT: '#start-new-import'
        },
        CONTENTS: {
            FILE_SUMMARY: '#file-summary',
            MAPPING_BODY: '#mapping-body',
            MAPPING_ERROR: '#mapping-error-content',
            MAPPING_SUCCESS: '#mapping-success-content',
            RESULTS_SUMMARY: '#results-summary',
            RESULTS_DETAILS: '#results-details'
        }
    };

    // ─── Notifications ────────────────────────────────────────────────────────

    const notifications = {
        showAlert(type, message) {
            $(SELECTORS.ALERTS.CONTAINER).html(`
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
        },
        showSectionError(elementId, contentId, errorData) {
            let html = '';
            if (typeof errorData === 'string') {
                html = `<p>${errorData}</p>`;
            } else {
                html = '<ul class="mb-0">';
                if (Array.isArray(errorData)) {
                    errorData.forEach(e => html += `<li>${e}</li>`);
                } else {
                    for (const [row, errors] of Object.entries(errorData)) {
                        html += `<li><strong>${row}:</strong><ul>`;
                        if (typeof errors === 'object') {
                            for (const [field, fieldErrors] of Object.entries(errors)) {
                                const msg = Array.isArray(fieldErrors) ? fieldErrors.join(', ') : fieldErrors;
                                html += `<li>${field}: ${msg}</li>`;
                            }
                        } else {
                            html += `<li>${errors}</li>`;
                        }
                        html += `</ul></li>`;
                    }
                }
                html += '</ul>';
            }
            $(`#${contentId}`).html(html);
            $(`#${elementId}`).removeClass('d-none');
        },
        showSectionSuccess(elementId, contentId, message) {
            $(`#${contentId}`).html(message);
            $(`#${elementId}`).removeClass('d-none');
        }
    };

    // ─── Navigation ───────────────────────────────────────────────────────────

    const navigation = {
        goToStep(stepNumber) {
            $(SELECTORS.STEPS.CONTENT.join(', ')).addClass('d-none');
            $(SELECTORS.STEPS.TABS.join(', ')).removeClass('active').addClass('disabled');
            $(`#step${stepNumber}-content`).removeClass('d-none');
            $(`#step${stepNumber}-tab`).removeClass('disabled').addClass('active');
            for (let i = 1; i < stepNumber; i++) {
                $(`#step${i}-tab`).removeClass('disabled');
            }
        },
        resetImport() {
            $(SELECTORS.FORMS.UPLOAD)[0].reset();
            $(SELECTORS.FORMS.MAPPING)[0].reset();
            $(SELECTORS.PREVIEWS.RAW + ', ' + SELECTORS.PREVIEWS.MAPPED + ', ' +
                SELECTORS.CONTENTS.RESULTS_SUMMARY + ', ' + SELECTORS.CONTENTS.RESULTS_DETAILS).html('');
            $(SELECTORS.ALERTS.MAPPING_ERROR + ', ' + SELECTORS.ALERTS.MAPPING_SUCCESS).addClass('d-none');
            $(SELECTORS.ALERTS.CONTAINER).html('');
            navigation.goToStep(1);
        }
    };

    // ─── Preview ──────────────────────────────────────────────────────────────

    const preview = {
        updateFileSummary(data) {
            const showing = labels.showing_preview
                .replace('{count}', data.rows.length)
                .replace('{total}', data.total_rows);
            $(SELECTORS.CONTENTS.FILE_SUMMARY).html(`
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${labels.file_processed}</strong><br>
                        ${labels.total_rows}: ${data.total_rows} | ${showing}
                    </div>
                </div>
            `);
        },

        generateFieldMappings(headers, fields) {
            const cleanedHeaders = headers.filter(
                h => typeof h === 'string' && h.trim() !== '' && h.toLowerCase() !== 'null'
            );
            const options = cleanedHeaders.map(h => `<option value="${h}">${h}</option>`).join('');

            let html = '';
            fields.forEach(field => {
                const required = field.required ? '<span class="text-danger">*</span>' : '';
                html += `
                    <tr>
                        <td class="fw-semibold">${field.name} ${required}</td>
                        <td>
                            <select class="form-select form-select-sm mapping-select"
                                name="mapping[${field.name}]" ${field.required ? 'required' : ''}>
                                <option value="">${labels.select_option}</option>
                                ${options}
                            </select>
                        </td>
                    </tr>
                `;
            });

            $(SELECTORS.CONTENTS.MAPPING_BODY).html(html);

            // ✅ Scope each select2 to its own row to avoid full-width overflow
            $('.mapping-select').each(function () {
                $(this).select2({
                    width: '100%',
                    dropdownAutoWidth: false,
                    dropdownParent: $(this).closest('td'),
                    containerCssClass: 'mapping-select2-container',
                    dropdownCssClass: 'mapping-select2-dropdown'
                });
            });

            preview.autoMatchFields(headers, fields);
        },

        autoMatchFields(headers, fields) {
            const fieldVariations = {
                'first_name': ['first name', 'firstname', 'fname', 'given name'],
                'last_name': ['last name', 'lastname', 'lname', 'surname', 'family name'],
                'email': ['email', 'email address', 'e-mail', 'mail'],
                'phone': ['phone', 'phone number', 'mobile', 'cell', 'telephone'],
                'company': ['company', 'organization', 'org', 'business'],
                'job_title': ['job title', 'jobtitle', 'title', 'position', 'role', 'designation'],
                'country_code': ['country code', 'countrycode', 'cc', 'dial code'],
                'country_iso_code': ['country iso', 'iso code', 'iso', 'country iso code'],
                'source': ['source', 'lead source'],
                'stage': ['stage', 'lead stage', 'status'],
                'industry': ['industry', 'sector', 'business type'],
                'website': ['website', 'web', 'url', 'site'],
                'linkedin': ['linkedin', 'linkedin url'],
                'instagram': ['instagram', 'instagram url'],
                'facebook': ['facebook', 'facebook url'],
                'pinterest': ['pinterest', 'pinterest url'],
                'city': ['city', 'town', 'locality'],
                'state': ['state', 'province', 'region'],
                'zip': ['zip', 'postal code', 'zipcode', 'postcode'],
                'country': ['country', 'nation'],
                // Users
                'name': ['name', 'full name', 'fullname'],
                'role': ['role', 'user role', 'position'],
                // Tasks/Projects
                'title': ['title', 'task title', 'project title', 'name'],
                'description': ['description', 'details', 'notes'],
                'start_date': ['start date', 'startdate', 'start'],
                'end_date': ['end date', 'enddate', 'due date', 'deadline'],
                'priority': ['priority', 'urgency'],
                'status': ['status', 'state'],
                // Clients
                'address': ['address', 'street', 'location'],
            };

            const validHeaders = headers.filter(h => typeof h === 'string' && h.trim() !== '');

            // Exact match first
            validHeaders.forEach(header => {
                const clean = header.trim().toLowerCase();
                fields.forEach(field => {
                    if (clean === field.name.toLowerCase()) {
                        const select = $(`select[name="mapping[${field.name}]"]`);
                        if (select.length && !select.val()) {
                            select.val(header).trigger('change');
                        }
                    }
                });
            });

            // Fuzzy match second
            validHeaders.forEach(header => {
                const clean = header.trim().toLowerCase();
                for (const [dbField, variations] of Object.entries(fieldVariations)) {
                    const select = $(`select[name="mapping[${dbField}]"]`);
                    if (!select.length || select.val() !== '') continue;
                    const matched = variations.some(v =>
                        clean === v || clean.includes(v) || v.includes(clean) ||
                        this._isSimilar(clean, v)
                    );
                    if (matched) {
                        select.val(header).trigger('change');
                        break;
                    }
                }
            });
        },

        _isSimilar(str1, str2) {
            const c1 = str1.replace(/[^a-z0-9]/g, '');
            const c2 = str2.replace(/[^a-z0-9]/g, '');
            return c1.length >= 3 && c2.length >= 3 && (c1.includes(c2) || c2.includes(c1));
        },

        showRawDataPreview(data) {
            let table = '<table class="table table-bordered table-sm"><thead><tr>';
            data.headers.forEach(h => table += `<th>${h}</th>`);
            table += '</tr></thead><tbody>';
            data.rows.forEach(row => {
                table += '<tr>';
                row.forEach(cell => table += `<td>${cell || '-'}</td>`);
                table += '</tr>';
            });
            table += '</tbody></table>';
            $(SELECTORS.PREVIEWS.RAW).html(table);
        },

        generatePreviewTable(data) {
            const showing = labels.showing_preview
                .replace('{count}', data.mapped_data.length)
                .replace('{total}', data.total_rows);

            let table = '<table class="table table-bordered table-sm"><thead><tr>';
            if (data.mapped_data.length > 0) {
                Object.keys(data.mapped_data[0]).forEach(k => table += `<th>${k}</th>`);
            }
            table += '</tr></thead><tbody>';
            data.mapped_data.forEach(row => {
                table += '<tr>';
                for (let k in row) table += `<td>${row[k] || '-'}</td>`;
                table += '</tr>';
            });
            table += '</tbody></table>';

            return `
                <div class="mb-2 small text-muted d-flex justify-content-between align-items-center">
                    <span>${showing}</span>
                    <button class="btn btn-link btn-sm p-0 toggle-preview"
                        data-showing="mapped">${labels.show_raw_data}</button>
                </div>
                ${table}
            `;
        },

        addRawPreviewToggle() {
            $(SELECTORS.PREVIEWS.RAW).prepend(`
                <div class="mb-2 small text-muted d-flex justify-content-between align-items-center">
                    <span></span>
                    <button class="btn btn-link btn-sm p-0 toggle-preview"
                        data-showing="raw">${labels.show_mapped_data}</button>
                </div>
            `);
        },

        setupPreviewToggle() {
            $(document).off('click', '.toggle-preview').on('click', '.toggle-preview', function (e) {
                e.preventDefault();
                const showing = $(this).data('showing');
                if (showing === 'mapped') {
                    $(SELECTORS.PREVIEWS.MAPPED).addClass('d-none');
                    $(SELECTORS.PREVIEWS.RAW).removeClass('d-none');
                } else {
                    $(SELECTORS.PREVIEWS.RAW).addClass('d-none');
                    $(SELECTORS.PREVIEWS.MAPPED).removeClass('d-none');
                }
            });
        }
    };

    // ─── Import Results ───────────────────────────────────────────────────────

    const importResults = {
        generateImportedTable(records) {
            let html = `<h6>${labels.imported_leads}</h6>
                <div class="table-responsive">
                <table class="table table-sm table-bordered"><thead><tr>`;
            displayColumns.forEach(col => {
                if (records[0].hasOwnProperty(col)) html += `<th>${col}</th>`;
            });
            html += '</tr></thead><tbody>';
            records.forEach(record => {
                html += '<tr>';
                displayColumns.forEach(col => {
                    if (record.hasOwnProperty(col)) html += `<td>${record[col] || '-'}</td>`;
                });
                html += '</tr>';
            });
            return html + '</tbody></table></div>';
        },

        generatePartialSummary(response) {
            return `
                <div class="alert alert-warning">
                    <h6 class="alert-heading">${labels.import_partially_completed}</h6>
                    <p>${response.message}</p><hr>
                    <p class="mb-0">
                        ${labels.successfully_imported}: ${response.data.successful}<br>
                        Failed: ${response.data.failed}<br>
                        Total: ${response.data.total}
                    </p>
                </div>
            `;
        },

        generateErrorDetails(failedRows) {
            if (!failedRows?.length) return `<p>${labels.no_detailed_error}</p>`;
            let html = `<h6 class="text-danger">${labels.import_errors}</h6><div class="error-list">`;
            failedRows.forEach(row => {
                html += `<div class="alert alert-danger mb-3">
                    <strong>Row ${row.row}</strong>
                    <ul class="list-unstyled mb-0 mt-2">
                        ${Object.entries(row.errors).map(([field, msgs]) =>
                            `<li>• ${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}</li>`
                        ).join('')}
                    </ul>
                </div>`;
            });
            return html + '</div>';
        }
    };

    // ─── Ajax Handlers ────────────────────────────────────────────────────────

    const ajaxHandlers = {
        handleParseSuccess(response) {
            $('#temp_path').val(response.data.temp_path);
            preview.updateFileSummary(response.data);
            preview.generateFieldMappings(response.data.headers, dbFields);
            preview.showRawDataPreview(response.data);
            navigation.goToStep(2);
        },
        handlePreviewSuccess(response) {
            $(SELECTORS.PREVIEWS.RAW).addClass('d-none');
            $(SELECTORS.PREVIEWS.MAPPED).removeClass('d-none');
            $(SELECTORS.PREVIEWS.MAPPED).html(preview.generatePreviewTable(response.data));
            if (!$(SELECTORS.PREVIEWS.RAW).find('.toggle-preview').length) {
                preview.addRawPreviewToggle();
            }
            $(SELECTORS.BUTTONS.SUBMIT).prop('disabled', false);
            notifications.showSectionSuccess('mapping-success-alert', 'mapping-success-content', 'Data mapped successfully.');
            preview.setupPreviewToggle();
        },
        handleImportSuccess(response) {
            $(SELECTORS.CONTENTS.RESULTS_SUMMARY).html(`
                <div class="alert alert-success">
                    <h6 class="alert-heading">${labels.import_success}</h6>
                    <p>${response.message}</p><hr>
                    <p class="mb-0">${labels.successfully_imported}: ${response.data.total}</p>
                </div>
            `);
            const records = response.data.imported_leads || response.data.imported_records || [];
            $(SELECTORS.CONTENTS.RESULTS_DETAILS).html(
                records.length > 0 ? importResults.generateImportedTable(records) : ''
            );
            navigation.goToStep(3);
            notifications.showAlert('success', response.message);
        },
        handleImportFailure(response) {
            if (response.data?.successful) {
                ajaxHandlers.handlePartialImport(response);
            } else {
                notifications.showSectionError('mapping-error-alert', 'mapping-error-content',
                    response.message || 'Failed to import.');
            }
        },
        handlePartialImport(response) {
            $(SELECTORS.CONTENTS.RESULTS_SUMMARY).html(importResults.generatePartialSummary(response));
            $(SELECTORS.CONTENTS.RESULTS_DETAILS).html(importResults.generateErrorDetails(response.data.failed_rows));
            navigation.goToStep(3);
            notifications.showAlert('warning', response.message);
        },
        handleImportError(response) {
            if (response.data?.failed_rows) {
                ajaxHandlers.handlePartialImport(response);
            } else {
                notifications.showAlert('danger', response.message || 'Error importing. Please try again.');
            }
        }
    };

    // ─── Event Handlers ───────────────────────────────────────────────────────

    $(document).on('submit', SELECTORS.FORMS.UPLOAD, function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.html(`<i class="bx bx-loader bx-spin me-1"></i>${labels.uploading}`).prop('disabled', true);
        $(SELECTORS.ALERTS.CONTAINER).html('');

        $.ajax({
            type: 'POST',
            url: routes.parse,
            data: new FormData(this),
            processData: false,
            contentType: false,
            success(response) {
                if (response.success) ajaxHandlers.handleParseSuccess(response);
                else notifications.showAlert('danger', response.message || 'Failed to parse file.');
            },
            error(xhr) {
                notifications.showAlert('danger', (xhr.responseJSON || {}).message || 'Error uploading file.');
            },
            complete() {
                $btn.html(`<i class="bx bx-upload me-1"></i>${labels.upload_and_continue}`).prop('disabled', false);
            }
        });
    });

    $(document).on('click', SELECTORS.BUTTONS.PREVIEW, function (e) {
        e.preventDefault();
        $(this).html(`<i class="bx bx-loader bx-spin me-1"></i>${labels.processing}`).prop('disabled', true);
        $(SELECTORS.ALERTS.MAPPING_ERROR + ', ' + SELECTORS.ALERTS.MAPPING_SUCCESS).addClass('d-none');

        const mappings = {};
        $('select[name^="mapping"]').each(function () {
            const field = $(this).attr('name').split('[')[1].split(']')[0];
            mappings[field] = $(this).val();
        });

        $.ajax({
            type: 'POST',
            url: routes.previewMappedLeads,
            data: {
                mapping: mappings,
                temp_path: $('#temp_path').val(),
                _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
            },
            success(response) {
                if (response.success) ajaxHandlers.handlePreviewSuccess(response);
                else notifications.showSectionError('mapping-error-alert', 'mapping-error-content',
                    response.message || 'Failed to map data.');
            },
            error(xhr) {
                notifications.showSectionError('mapping-error-alert', 'mapping-error-content',
                    (xhr.responseJSON || {}).message || 'Error generating preview.');
            },
            complete() {
                $(SELECTORS.BUTTONS.PREVIEW)
                    .html(`<i class="bx bx-search me-1"></i>${labels.preview_mapped_data}`)
                    .prop('disabled', false);
            }
        });
    });

    $(document).on('submit', SELECTORS.FORMS.MAPPING, function (e) {
        e.preventDefault();
        const $btn = $(SELECTORS.BUTTONS.SUBMIT);
        $btn.html(`<i class="bx bx-loader bx-spin me-1"></i>${labels.importing}`).prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: routes.import,
            data: $(this).serialize(),
            success(response) {
                if (response.success) ajaxHandlers.handleImportSuccess(response);
                else ajaxHandlers.handleImportFailure(response);
            },
            error(xhr) {
                ajaxHandlers.handleImportError(xhr.responseJSON || {});
            },
            complete() {
                $btn.html(`<i class="bx bx-import me-1"></i>${labels.import_data}`).prop('disabled', false);
            }
        });
    });

    $(document).on('click', SELECTORS.BUTTONS.BACK, function (e) {
        e.preventDefault();
        navigation.goToStep(1);
    });

    $(document).on('click', SELECTORS.BUTTONS.NEW_IMPORT, function (e) {
        e.preventDefault();
        navigation.resetImport();
    });

    // Initialize
    navigation.goToStep(1);
});