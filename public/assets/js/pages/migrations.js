// Migration Management JavaScript
$(document).ready(function () {
    // Show alert function
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#alertContainer').html(alertHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Run All Migrations
    $('#runAllMigrations').on('click', function() {
        if (!confirm('Are you sure you want to run all pending migrations?')) {
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Running...');

        $.ajax({
            url: '/migrate/run-all',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    let message = response.message || 'Migrations run successfully.';
                    if (response.fixed && response.fixed.length > 0) {
                        message += '<br><small>Fixed ' + response.fixed.length + ' migration issue(s).</small>';
                    }
                    showAlert('success', message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert('danger', response.message || 'Failed to run migrations.');
                    btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                let message = response.message || 'An error occurred while running migrations.';

                // Check if it's a table exists error with fix suggestion
                if (response.suggest_fix && response.issues && response.issues.length > 0) {
                    message += '<br><br><strong>Detected Issues:</strong><ul class="mb-0 mt-2">';
                    response.issues.forEach(function(issue) {
                        message += '<li>' + issue.message + '</li>';
                    });
                    message += '</ul><br><button class="btn btn-sm btn-warning mt-2" onclick="$(\'#fixIssues\').click();">Fix Issues with Auto-Fix</button>';
                }

                showAlert('danger', message);
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Check Migration Sequence
    $('#checkSequence').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Checking...');

        $.ajax({
            url: '/migrate/check-sequence',
            type: 'GET',
            success: function(response) {
                btn.prop('disabled', false).html(originalHtml);

                if (response.success && response.report) {
                    const report = response.report;
                    let html = '';

                    if (report.valid) {
                        html += '<div class="alert alert-success"><i class="bx bx-check-circle me-1"></i>Migration sequence is valid.</div>';
                    } else {
                        html += '<div class="alert alert-warning"><i class="bx bx-error-circle me-1"></i>Migration sequence issues detected.</div>';
                    }

                    if (report.out_of_order && report.out_of_order.length > 0) {
                        html += '<h6 class="mt-3">Out of Order Migrations:</h6><ul class="list-unstyled">';
                        report.out_of_order.forEach(function(item) {
                            html += `<li class="text-warning"><i class="bx bx-error-circle me-1"></i>${item.migration} (ran before ${item.ran_before})</li>`;
                        });
                        html += '</ul>';
                    }

                    if (report.dependency_errors && report.dependency_errors.length > 0) {
                        html += '<h6 class="mt-3">Dependency Errors:</h6><ul class="list-unstyled">';
                        report.dependency_errors.forEach(function(item) {
                            html += `<li class="text-danger"><i class="bx bx-x-circle me-1"></i>${item.migration} modifies table "${item.table}" which is created in ${item.created_in}</li>`;
                        });
                        html += '</ul>';
                    }

                    if (report.recommendations && report.recommendations.length > 0) {
                        html += '<h6 class="mt-3">Recommendations:</h6><ul class="list-unstyled">';
                        report.recommendations.forEach(function(rec) {
                            html += `<li class="text-info"><i class="bx bx-info-circle me-1"></i>${rec}</li>`;
                        });
                        html += '</ul>';
                    }

                    $('#sequenceContent').html(html);
                    $('#sequenceResults').removeClass('d-none');
                } else {
                    showAlert('danger', 'Failed to check migration sequence.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                const message = xhr.responseJSON?.message || 'An error occurred while checking sequence.';
                showAlert('danger', message);
            }
        });
    });

    // Validate Migrations
    $('#validateMigrations').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Validating...');

        $.ajax({
            url: '/migrate/validate',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                btn.prop('disabled', false).html(originalHtml);

                if (response.success && response.validation) {
                    const validation = response.validation;
                    let message = '';

                    if (validation.valid) {
                        message = '<i class="bx bx-check-circle me-1"></i>All migrations are valid.';
                        showAlert('success', message);
                    } else {
                        message = '<i class="bx bx-error-circle me-1"></i>Validation failed.';
                        if (validation.errors && validation.errors.length > 0) {
                            message += '<ul class="mb-0 mt-2">';
                            validation.errors.forEach(function(error) {
                                message += `<li>${error}</li>`;
                            });
                            message += '</ul>';
                        }
                        if (validation.warnings && validation.warnings.length > 0) {
                            message += '<ul class="mb-0 mt-2">';
                            validation.warnings.forEach(function(warning) {
                                message += `<li class="text-warning">${warning}</li>`;
                            });
                            message += '</ul>';
                        }
                        showAlert('danger', message);
                    }
                } else {
                    showAlert('danger', 'Failed to validate migrations.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                const message = xhr.responseJSON?.message || 'An error occurred while validating migrations.';
                showAlert('danger', message);
            }
        });
    });

    // Fix Issues
    $('#fixIssues').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();

        // Ask if user wants to enable auto-fix
        const enableAutoFix = confirm('Enable auto-fix? This will automatically mark migrations as run if their tables already exist.');

        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Fixing...');

        $.ajax({
            url: '/migrate/fix-issues',
            type: 'POST',
            data: {
                auto_fix: enableAutoFix ? 1 : 0
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                btn.prop('disabled', false).html(originalHtml);

                if (response.success) {
                    let message = response.message || 'Migration issues checked.';

                    // Show table exists issues
                    if (response.fixes && response.fixes.table_exists && response.fixes.table_exists.length > 0) {
                        message += '<br><br><strong>Table Exists Issues Found:</strong><ul class="mb-0 mt-2">';
                        response.fixes.table_exists.forEach(function(issue) {
                            message += `<li class="text-warning"><i class="bx bx-error-circle me-1"></i>${issue.message}</li>`;
                        });
                        message += '</ul>';

                        if (!enableAutoFix) {
                            message += '<br><small class="text-info">Enable auto-fix to automatically mark these migrations as run.</small>';
                        }
                    }

                    // Show fixed issues
                    if (response.fixes && response.fixes.table_exists_fixed && response.fixes.table_exists_fixed.length > 0) {
                        message += '<br><br><strong>Fixed Issues:</strong><ul class="mb-0 mt-2">';
                        response.fixes.table_exists_fixed.forEach(function(fixed) {
                            message += `<li class="text-success"><i class="bx bx-check-circle me-1"></i>${fixed.message}</li>`;
                        });
                        message += '</ul>';

                        // Reload page after 2 seconds if fixes were applied
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    }

                    if (response.errors && response.errors.length > 0) {
                        message += '<br><br><strong>Errors:</strong><ul class="mb-0 mt-2">';
                        response.errors.forEach(function(error) {
                            message += `<li class="text-danger">${error}</li>`;
                        });
                        message += '</ul>';
                    }

                    if (response.warnings && response.warnings.length > 0) {
                        message += '<br><br><strong>Warnings:</strong><ul class="mb-0 mt-2">';
                        response.warnings.forEach(function(warning) {
                            message += `<li class="text-warning">${warning}</li>`;
                        });
                        message += '</ul>';
                    }

                    const alertType = (response.fixes && response.fixes.table_exists_fixed && response.fixes.table_exists_fixed.length > 0) ? 'success' : 'info';
                    showAlert(alertType, message);
                } else {
                    showAlert('danger', 'Failed to fix migration issues.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                const message = xhr.responseJSON?.message || 'An error occurred while fixing issues.';
                showAlert('danger', message);
            }
        });
    });

    // Refresh Status
    $('#refreshStatus').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Refreshing...');

        window.location.reload();
    });
});

