'use strict';
let currentPage = 1,
    lastPage = false,
    totalCount = 0;

// Initialize daterangepicker with enhanced styling
$('#dateRange').daterangepicker({
    opens: 'right',
    autoUpdateInput: true,
    locale: {
        format: window.js_date_format || 'YYYY-MM-DD'
    },
    ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'This Week': [moment().startOf('week'), moment().endOf('week')],
        'Last Week': [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
    }
});

// Utility to format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024,
        sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Utility to format metadata
function formatMetadata(metadata) {
    if (!metadata || Object.keys(metadata).length === 0) return '';
    let html = '';
    for (const [key, value] of Object.entries(metadata)) {
        if (value && value.toString().trim()) {
            html += `<span class="metadata-pill">${key}: ${value}</span>`;
        }
    }
    return html;
}

// Load screenshots with enhanced UI feedback
function loadScreenshots(reset = false) {
    if (reset) {
        $('#gallery').empty();
        currentPage = 1;
        lastPage = false;
    }

    $('#loader').show();
    $('#loadMoreBtn').hide().addClass('d-none');
    $('#emptyState').hide();

    const [startDate, endDate] = $('#dateRange').val().split(' - ');

    $.get(screenshotDataUrl, {
        page: currentPage,
        start_date: startDate,
        end_date: endDate,
        user_ids: $('#filterUser').val()
    }, function (response) {
        if (reset) {
            totalCount = response.pagination.total;
            $('#totalCount').text(`${totalCount} screenshot${totalCount !== 1 ? 's' : ''}`);
        }

        if (response.data.length === 0 && currentPage === 1) {
            $('#emptyState').show();
        }

        response.data.forEach(item => {
            const metadataHtml = formatMetadata(item.metadata);
            const captured = new Date(item.captured_at);

            // Check if date is valid, if not, show fallback
            let capturedDate, capturedTime;
            if (isNaN(captured.getTime())) {
                capturedDate = 'Invalid Date';
                capturedTime = '';
            } else {
                // Use the system's js_date_format for consistent formatting
                capturedDate = moment(captured).format(window.js_date_format || 'YYYY-MM-DD');
                capturedTime = captured.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            $('#gallery').append(`
    <div class="col-6 col-md-4 col-lg-3 col-xl-2 gallery-item-wrapper">
        <div class="gallery-item">
            <a href="${item.image_url}" data-lightbox="screenshots" data-title="${item.user_name} | ${capturedDate} ${capturedTime ? capturedTime : ''} | ${item.file_size_kb}">
                <img src="${item.image_url}" alt="Screenshot" loading="lazy">
                    <div class="position-absolute top-0 end-0 p-2">
                        <span class="badge bg-dark bg-opacity-75">
                            <i class="bx bx-expand-alt"></i>
                        </span>
                    </div>
            </a>
            <div class="gallery-info">
                <div class="gallery-title">
                    <i class="bx bx-user me-1"></i>${item.user_name}
                </div>
                <div class="gallery-meta">
                    <i class="bx bx-time me-1"></i>${capturedDate}${capturedTime ? ' ' + capturedTime : ''}
                    <br>
                        <i class="bx bx-file me-1"></i>${item.file_size_kb}
                </div>
                <div class="mt-1">${metadataHtml}</div>
            </div>
        </div>
    </div>
    `);
        });

        // Add stagger animation to new items
        $('.gallery-item-wrapper').each(function (index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
        });

        currentPage++;
        lastPage = currentPage > response.pagination.last_page;

        $('#loader').hide();
        if (!lastPage) {
            $('#loadMoreBtn').removeClass('d-none').show().css('display', 'inline-block');
        } else {
            $('#loadMoreBtn').hide();
        }
    }).fail(() => {
        $('#loader').hide();
        // Enhanced error handling
        const errorHtml = `
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>
        <strong>Error!</strong> Unable to load screenshots. Please try again.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    `;
        $('#gallery').before(errorHtml);
    });
}

// Enhanced events
$(document).ready(() => {
    // Initialize Select2 with enhanced styling
    $('#filterUser').select2({
        placeholder: "Select users",
        allowClear: true,
        width: '100%'
    });

    // Set initial date range to today
    $('#dateRange').data('daterangepicker').setStartDate(moment());
    $('#dateRange').data('daterangepicker').setEndDate(moment());

    // Auto-fetch on date range change
    $('#dateRange').on('apply.daterangepicker', function (ev, picker) {
        loadScreenshots(true);
    });

    // Auto-fetch on user selection change
    $('#filterUser').on('change', function () {
        loadScreenshots(true);
    });

    // Initial load
    loadScreenshots(true);

    // Enhanced button events
    $('#filterBtn').on('click', () => {
        $(this).addClass('loading');
        loadScreenshots(true);
    });

    $('#loadMoreBtn').on('click', () => loadScreenshots());

    $('#refreshBtn').on('click', () => {
        loadScreenshots(true);
    });

    // Enhanced preset buttons
    $('.preset-btn').on('click', function () {
        $('.preset-btn').removeClass('active');
        $(this).addClass('active');

        const preset = $(this).data('preset');
        const ranges = {
            today: [moment(), moment()],
            yesterday: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            thisWeek: [moment().startOf('week'), moment().endOf('week')],
            lastWeek: [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')],
            thisMonth: [moment().startOf('month'), moment().endOf('month')],
        };

        $('#dateRange').data('daterangepicker').setStartDate(ranges[preset][0]);
        $('#dateRange').data('daterangepicker').setEndDate(ranges[preset][1]);

        loadScreenshots(true);
    });

    // Initialize lightbox if available
    if (typeof lightbox !== 'undefined') {
        lightbox.option({
            resizeDuration: 200,
            wrapAround: true,
            albumLabel: "Screenshot %1 of %2"
        });
    }

    // Add loading states for better UX
    $(document).ajaxStart(function () {
        $('#filterBtn').prop('disabled', true);
        $('#refreshBtn').prop('disabled', true);
    }).ajaxStop(function () {
        $('#filterBtn').prop('disabled', false);
        $('#refreshBtn').prop('disabled', false);
    });
});
