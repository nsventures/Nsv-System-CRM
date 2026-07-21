function queryParams(params) {
    return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
        status: $('#select_social_stastuses').val(),
        platform: $('#select_social_platforms').val()
    };
}



$(document).ready(function () {
    if (typeof TableFilterSync === 'function') {
        TableFilterSync('table', ['select_social_platforms', 'select_social_stastuses']);
    } else {
        $("#select_social_platforms").on("change", function () {
            $('#table').bootstrapTable('refresh');
        });

        $('#select_social_stastuses').on('change', function () {
            $('#table').bootstrapTable('refresh');
        });
    }
});


$(document).ready(function () {
    // Wait for TinyMCE to initialize
    if (typeof tinymce !== 'undefined') {
        function applyTinyMCETheme(editor) {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const body = editor.getBody();
            if (body) {
                if (isDark) {
                    body.style.backgroundColor = '#2b2b32';
                    body.style.color = '#f5f5f9';
                } else {
                    body.style.backgroundColor = '#ffffff';
                    body.style.color = '#435971';
                }
            }
        }

        tinymce.init({
            height: 250,
            selector: '.caption',
            plugins: 'advlist autolink lists link image charmap preview',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image',
            menubar: false,
            setup: function (editor) {
                editor.on('init', function () {
                    console.log('TinyMCE initialized successfully for #' + editor.id);
                    updateCharacterCounter();
                    updatePostPreview();
                    
                    // Apply initial theme
                    applyTinyMCETheme(editor);

                    // Observe theme change
                    const observer = new MutationObserver(function (mutations) {
                        mutations.forEach(function (mutation) {
                            if (mutation.attributeName === "data-theme") {
                                applyTinyMCETheme(editor);
                            }
                        });
                    });
                    observer.observe(document.documentElement, { attributes: true });
                });
                editor.on('change keyup input', function () {
                    updateCharacterCounter();
                    updatePostPreview();
                });
            }
        });
    } else {
        console.warn('TinyMCE is not loaded. Falling back to textarea events.');
        $('#social-media-caption').on('input', function () {
            updateCharacterCounter();
            updatePostPreview();
        });
    }

    // Initialize the page
    initializePage();

    // Event listeners
    setupEventListeners();

    // Initialize ONLY Social Media Caption AI with unique classes
    initializeSocialCaptionAI();

    // Load existing data if editing
    loadExistingData();

    // EDIT: Initialize existing media deletion modal
    if (window.isEditMode) {
        initializeEditModeFeatures();
    }
});

function initializeSocialCaptionAI() {
    // Toggle custom prompt visibility - ONLY for social caption with unique classes
    $(document).on('change', '.social-caption-enable-custom-prompt', function () {
        const isChecked = $(this).is(':checked');
        const $container = $('.social-caption-custom-prompt-container');
        $container.toggleClass('d-none', !isChecked);
    });

    // Handle AI caption generation - ONLY for social caption with unique classes
    $(document).on('click', '.social-caption-generate-ai', function () {
        const $btn = $(this);
        const $scope = $btn.closest('.social-caption-ai-wrapper');

        // Double check this is the social media caption AI
        if (!$scope.find('#social-media-caption').length) {
            console.log('Not social caption AI, ignoring click');
            return; // Not our handler
        }

        const useCustomPrompt = $scope.find('.social-caption-enable-custom-prompt').is(':checked');
        let prompt = '';

        if (useCustomPrompt) {
            prompt = $scope.find('.social-caption-ai-custom-prompt').val().trim();
            if (!prompt) {
                toastr.error(window.labels.enter_custom_prompt_first || 'Please enter a custom prompt first.');
                return;
            }
        }

        // Get existing caption content
        const existingCaption = tinymce.get('social-media-caption') ?
            tinymce.get('social-media-caption').getContent({ format: 'html' }) :
            $('#social-media-caption').val();

        // Get selected platforms for context
        const selectedPlatforms = [];
        $('.platform-card.selected').each(function () {
            selectedPlatforms.push($(this).data('platform'));
        });

        // Show loading state
        const $loader = $scope.find('.social-caption-ai-loader');
        $btn.prop('disabled', true);
        if ($loader.length) $loader.removeClass('d-none');

        console.log('Making AJAX request for social caption generation');

        // Make AJAX request to SOCIAL-SPECIFIC endpoint
        $.ajax({
            url: '/social-media-scheduler/ai/generate-caption',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                prompt: prompt,
                isCustomPrompt: useCustomPrompt,
                existingCaption: existingCaption,
                platforms: selectedPlatforms
            },
            success: function (response) {
                console.log('Social caption generation success:', response);
                if (response.error) {
                    toastr.error(response.message);
                } else {
                    toastr.success(response.message || 'Caption generated successfully!');

                    // Set the generated caption
                    if (tinymce.get('social-media-caption')) {
                        tinymce.get('social-media-caption').setContent(response.caption || response.description);
                    } else {
                        $('#social-media-caption').val(response.caption || response.description);
                    }

                    // Update UI
                    updateCharacterCounter();
                    updatePostPreview();
                }
            },
            error: function (xhr) {
                console.log('Social caption generation error:', xhr);
                let errorMessage = window.labels.something_went_wrong || 'Something went wrong. Please try again.';

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('\n');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                toastr.error(errorMessage);
            },
            complete: function () {
                $btn.prop('disabled', false);
                if ($loader.length) $loader.addClass('d-none');
            }
        });
    });
}

function initializePage() {
    // Update selected platforms display
    updateSelectedPlatforms();
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
}

function setupEventListeners() {
    // Platform selection
    $('.platform-card').on('click', function () {
        const platform = $(this).data('platform');
        const checkbox = $(this).find('input[type="checkbox"]');

        // Toggle selection
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
            checkbox.prop('checked', false);
        } else {
            $(this).addClass('selected');
            checkbox.prop('checked', true);
        }

        updateSelectedPlatforms();
        updatePostPreview();
    });

    // Post type change (now vs schedule)
    $('input[name="post_type"]').on('change', function () {
        const postType = $(this).val();
        if (postType === 'schedule') {
            $('#schedule-section').show();
            $('#submit-text').text(window.labels.schedule_post || 'Schedule Post');
        } else {
            $('#schedule-section').hide();
            // EDIT: Different text for edit vs create
            const submitText = window.isEditMode ?
                (window.labels.update_post || 'Update Post') :
                (window.labels.post_now || 'Post Now');
            $('#submit-text').text(submitText);
        }
    });

    // Media upload preview
    $('#media-upload').on('change', function () {
        handleMediaUpload(this.files);
    });

    // Remove new media preview
    $(document).on('click', '.remove-new-media', function () {
        removeNewMedia($(this));
    });
}

// EDIT: Initialize edit mode specific features
function initializeEditModeFeatures() {
    // Handle existing media removal with confirmation modal
    $(document).on('click', '.remove-media', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(this);
        const mediaId = $btn.data('media-id');

        // Store reference for modal confirmation
        window.mediaToRemove = {
            element: $btn,
            mediaId: mediaId
        };

        // Show confirmation modal
        $('#confirmDeleteModal').modal('show');
    });

    // Handle modal confirmation
    $('#confirmDeleteBtn').on('click', function () {
        if (window.mediaToRemove) {
            const { element, mediaId } = window.mediaToRemove;
            removeExistingMediaWithAjax(element, mediaId);
            $('#confirmDeleteModal').modal('hide');
            window.mediaToRemove = null;
        }
    });

    // Set initial submit button text based on post type
    const currentPostType = $('input[name="post_type"]:checked').val();
    if (currentPostType === 'schedule') {
        $('#submit-text').text(window.labels.schedule_post || 'Schedule Post');
    } else {
        $('#submit-text').text(window.labels.update_post || 'Update Post');
    }
}

function loadExistingData() {
    // Load existing media if available
    if (typeof window.existingMedia !== 'undefined' && window.existingMedia.length > 0) {
        displayExistingMediaInPreview();
    }

    // Load existing caption if available
    if (typeof window.existingCaption !== 'undefined' && window.existingCaption) {
        if (tinymce.get('social-media-caption')) {
            tinymce.get('social-media-caption').setContent(window.existingCaption);
        } else {
            $('#social-media-caption').val(window.existingCaption);
        }
        updateCharacterCounter();
        updatePostPreview();
    }
}

function updateCharacterCounter() {
    const editor = tinymce.get('social-media-caption');
    const caption = editor ? editor.getContent({ format: 'text' }) : $('#social-media-caption').val();
    const maxLength = 2000;
    const currentLength = caption.length;
    const percentage = (currentLength / maxLength) * 100;

    $('#caption-count').text(`${currentLength}/${maxLength}`);
    $('#charProgress').css('width', `${percentage}%`);

    // Change color based on usage
    if (percentage > 90) {
        $('#charProgress').removeClass('bg-success bg-warning').addClass('bg-danger');
    } else if (percentage > 75) {
        $('#charProgress').removeClass('bg-success bg-danger').addClass('bg-warning');
    } else {
        $('#charProgress').removeClass('bg-warning bg-danger').addClass('bg-success');
    }
}

function updatePostPreview() {
    const editor = tinymce.get('social-media-caption');
    const caption = editor ? editor.getContent({ format: 'html' }) : $('#social-media-caption').val();

    let previewHtml = '';

    if (caption.trim()) {
        previewHtml += `<div class="mb-2">${caption}</div>`;
    }

    // Add media preview with carousel
    const mediaItems = getMediaPreviewItems();
    if (mediaItems.length > 0) {
        previewHtml += `
            <div id="postPreviewCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    ${mediaItems.map((item, index) => `
                        <div class="carousel-item ${index === 0 ? 'active' : ''}">
                            ${item}
                        </div>
                    `).join('')}
                </div>
                ${mediaItems.length > 1 ? `
                    <button class="carousel-control-prev" type="button" data-bs-target="#postPreviewCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#postPreviewCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                ` : ''}
            </div>
        `;
    }

    if (!previewHtml) {
        previewHtml = `<div class="text-center text-muted py-3">${window.labels.preview_will_appear_here || 'Post preview will appear here...'}</div>`;
    }

    $('#post-preview').html(previewHtml);
}

function getMediaPreviewItems() {
    let mediaItems = [];

    // Existing media (for edit mode)
    if (typeof window.existingMedia !== 'undefined' && window.existingMedia.length > 0) {
        window.existingMedia.forEach(media => {
            console.log('Processing existing media:', media);
            if (media.type === 'image') {
                mediaItems.push(`<img src="${media.path}" class="img-fluid" alt="Media">`);
            } else if (media.type === 'video') {
                mediaItems.push(`
                    <video class="img-fluid" controls muted>
                        <source src="${media.path}" type="${media.mime_type}">
                    </video>
                `);
            }
        });
    }

    // New media files
    if (window.newMediaFiles && window.newMediaFiles.length > 0) {
        window.newMediaFiles.forEach(file => {
            if (file.type.startsWith('image/')) {
                mediaItems.push(`<img src="${file.url}" class="img-fluid" alt="New Media">`);
            } else if (file.type.startsWith('video/')) {
                mediaItems.push(`
                    <video class="img-fluid" controls muted>
                        <source src="${file.url}" type="${file.type}">
                    </video>
                `);
            }
        });
    }

    return mediaItems;
}

function handleMediaUpload(files) {
    if (!window.newMediaFiles) {
        window.newMediaFiles = [];
    }

    window.newMediaFiles = [];

    if (files.length === 0) {
        updatePostPreview();
        clearNewMediaPreview();
        return;
    }

    Array.from(files).forEach((file, index) => {
        if (!file.type.match(/^(image|video)\//)) {
            console.warn('Unsupported file type:', file.type);
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            toastr.error('File size must be less than 10MB: ' + file.name);
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            window.newMediaFiles.push({
                file: file,
                url: e.target.result,
                type: file.type,
                name: file.name
            });

            if (window.newMediaFiles.length === files.length ||
                window.newMediaFiles.length === Array.from(files).filter(f =>
                    f.type.match(/^(image|video)\//) && f.size <= 10 * 1024 * 1024
                ).length) {
                updatePostPreview();
                displayNewMediaPreview();
            }
        };
        reader.readAsDataURL(file);
    });
}

function displayNewMediaPreview() {
    if (!window.newMediaFiles || window.newMediaFiles.length === 0) {
        return;
    }

    if ($('#new-media-preview').length === 0) {
        const newMediaSection = `
            <div class="mb-3" id="new-media-preview-section">
                <label class="form-label">New Media Preview</label>
                <div class="row g-2" id="new-media-preview"></div>
            </div>
        `;
        $('#media-upload').closest('.mb-3').after(newMediaSection);
    }

    let previewHtml = '';
    window.newMediaFiles.forEach((mediaFile, index) => {
        previewHtml += `
            <div class="col-6 col-md-3">
                <div class="media-item position-relative">
        `;

        if (mediaFile.type.startsWith('image/')) {
            previewHtml += `<img src="${mediaFile.url}" class="media-thumb img-fluid rounded" alt="New Media">`;
        } else if (mediaFile.type.startsWith('video/')) {
            previewHtml += `
                <video class="media-thumb img-fluid rounded" muted>
                    <source src="${mediaFile.url}" type="${mediaFile.type}">
                </video>
                <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-dark"><i class="bx bx-play-circle me-1"></i></span>
                </div>
            `;
        }

        previewHtml += `
                    <button type="button" class="remove-new-media" data-index="${index}">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            </div>
        `;
    });

    $('#new-media-preview').html(previewHtml);
}

function displayExistingMediaInPreview() {
    if (!window.existingMedia || window.existingMedia.length === 0) {
        return;
    }

    updatePostPreview();
}

// EDIT: Handle existing media removal with AJAX (for edit mode)
function removeExistingMediaWithAjax(element, mediaId) {
    // Show loading state
    const $mediaItem = element.closest('.col-6, .col-md-3');
    $mediaItem.addClass('opacity-50');
    element.prop('disabled', true);

    $.ajax({
        url: `/social-media-scheduler/destroy-media/${mediaId}`,
        method: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {

            if (response.error === false) {
                toastr.success(window.labels.media_deleted_successfully || 'Media deleted successfully.');

                // Remove from UI
                $mediaItem.fadeOut(300, function () {
                    $(this).remove();
                });

                // Remove from window.existingMedia array
                if (window.existingMedia) {
                    window.existingMedia = window.existingMedia.filter(media => media.id !== mediaId);
                }

                // Update preview
                updatePostPreview();

                // Hide existing media section if no media left
                if (window.existingMedia && window.existingMedia.length === 0) {
                    $('#existing-media-section').fadeOut(300);
                }
            } else {
                toastr.error(response.message || window.labels.error_deleting_media || 'Error deleting media. Please try again.');
                $mediaItem.removeClass('opacity-50');
                element.prop('disabled', false);
            }
        },
        error: function (xhr) {
            let errorMessage = window.labels.error_deleting_media || 'Error deleting media. Please try again.';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage);
            $mediaItem.removeClass('opacity-50');
            element.prop('disabled', false);
        }
    });
}

// CREATE: Original media removal function (for create mode - local only)
function removeExistingMedia(element, mediaId) {
    element.closest('.col-6, .col-md-3').remove();

    if (window.existingMedia) {
        window.existingMedia = window.existingMedia.filter(media => media.id !== mediaId);
    }

    if (!window.mediaToDelete) {
        window.mediaToDelete = [];
    }
    window.mediaToDelete.push(mediaId);

    $('#edit-post-form, #create-post-form').append(`<input type="hidden" name="delete_media[]" value="${mediaId}">`);

    updatePostPreview();

    if (window.existingMedia && window.existingMedia.length === 0) {
        $('#existing-media-section').hide();
    }
}

function removeNewMedia(element) {
    const index = parseInt(element.data('index'));

    if (window.newMediaFiles && window.newMediaFiles[index]) {
        window.newMediaFiles.splice(index, 1);
    }

    element.closest('.col-6, .col-md-3').remove();

    $('#media-upload').val('');

    updatePostPreview();

    if (!window.newMediaFiles || window.newMediaFiles.length === 0) {
        $('#new-media-preview-section').remove();
    }
}

function clearNewMediaPreview() {
    $('#new-media-preview-section').remove();
    window.newMediaFiles = [];
}

$(document).on('submit', '#create-post-form, #edit-post-form', function (e) {
    // Disable submit button to prevent double submission
    const $submitBtn = $('#submit_btn');
    $submitBtn.prop('disabled', true);

    // Restore button if validation fails
    const restoreButton = () => {
        setTimeout(() => {
            $submitBtn.prop('disabled', false);
        }, 1000);
    };

    if ($('.platform-card.selected').length === 0) {
        e.preventDefault();
        toastr.error('Please select at least one platform to post to.');
        restoreButton();
        return false;
    }

    const issues = validatePlatformRequirements();
    if (issues.length > 0) {
        e.preventDefault();
        toastr.error(issues.join('<br>'), '', {
            timeOut: 5000,
            extendedTimeOut: 2000
        });
        restoreButton();
        return false;
    }

    // Save TinyMCE content
    if (tinymce.get('social-media-caption')) {
        tinymce.get('social-media-caption').save();
    }

    return true;
});

function validatePlatformRequirements() {
    const editor = tinymce.get('social-media-caption');
    const caption = editor ? editor.getContent({ format: 'text' }) : $('#social-media-caption').val();
    const selectedPlatforms = [];

    $('.platform-card.selected').each(function () {
        selectedPlatforms.push($(this).data('platform'));
    });

    const issues = [];

    selectedPlatforms.forEach(platform => {
        switch (platform) {
            case 'instagram':
                if (caption.length > 2200) {
                    issues.push('Instagram posts must be 2,200 characters or less');
                }
                if (!hasMedia()) {
                    issues.push('Instagram posts require at least one image or video');
                }
                break;
            case 'pinterest':
                if (caption.length > 500) {
                    issues.push('Pinterest posts must be 500 characters or less');
                }
                if (!hasImages()) {
                    issues.push('Pinterest posts require at least one image');
                }
                break;
            case 'linkedin':
                if (caption.length > 3000) {
                    issues.push('LinkedIn posts must be 3,000 characters or less');
                }
                break;
        }
    });

    return issues;
}

function hasMedia() {
    return (window.existingMedia && window.existingMedia.length > 0) ||
        (window.newMediaFiles && window.newMediaFiles.length > 0);
}

function hasImages() {
    let hasImages = false;

    if (window.existingMedia) {
        hasImages = window.existingMedia.some(media => media.type === 'image');
    }

    if (!hasImages && window.newMediaFiles) {
        hasImages = window.newMediaFiles.some(media => media.type.startsWith('image/'));
    }

    return hasImages;
}

function updateSelectedPlatforms() {
    const selectedPlatforms = [];
    $('.platform-card.selected').each(function () {
        selectedPlatforms.push($(this).data('platform'));
    });

    if (selectedPlatforms.length === 0) {
        $('#selectedPlatforms').html(`<small class="text-muted">${window.labels.no_platforms_selected}</small>`);
    } else {
        const platformLabels = {
            facebook: 'Facebook',
            instagram: 'Instagram',
            linkedin: 'LinkedIn',
            pinterest: 'Pinterest'
        };
        const badges = selectedPlatforms.map(platform =>
            `<span class="platform-badge ${platform}">${platformLabels[platform]}</span>`
        ).join('');
        $('#selectedPlatforms').html(badges);
    }
}



// Js for quick view
/**
 * Shared Quick View functionality for Social Media Scheduler
 * This file can be used by both calendar and list views
 */

function showPostQuickView(postId) {
    // Show loading state
    const $modalContent = $('#quickViewContent');
    $modalContent.html(`
    <div class="tk-empty" style="padding:40px 16px">
        <div class="tk-skel" style="width:32px;height:32px;border-radius:50%;margin:0 auto 12px"></div>
        <p class="tk-muted">Loading post details…</p>
    </div>
    `);

    // Determine the API endpoint - use calendarConfig if available, otherwise construct URL
    let baseUrl;
    if (typeof calendarConfig !== 'undefined' && calendarConfig.routes && calendarConfig.routes.quickView) {
        baseUrl = calendarConfig.routes.quickView.replace('{id}', postId);
    } else {
        baseUrl = `/social-media-scheduler/posts/${postId}`;
    }

    // Fetch post details via AJAX
    $.ajax({
        url: baseUrl,
        method: 'GET',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .done(function (data) {
            if (data.error) {
                throw new Error(data.message || 'Failed to load post details');
            }
            showQuickView(data.post || data);
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            const errorMessage = jqXHR.responseJSON?.message || errorThrown || 'Failed to load post details';
            $modalContent.html(`
            <div class="tk-empty" style="padding:32px 16px">
                <i class="bx bx-error-circle" style="font-size:2rem;color:var(--err)"></i>
                <p style="color:var(--err);margin:0;font-size:var(--fs-base)">Error loading post details: ${errorMessage}</p>
            </div>
        `);
        });
}

function showQuickView(postData) {
    const $modalContent = $('#quickViewContent');

    // Platform icons mapping
    const platformConfig = {
        'facebook': { icon: 'bxl-facebook-circle', color: '#4267B2' },
        'instagram': { icon: 'bxl-instagram', color: '#E4405F' },
        'twitter': { icon: 'bxl-twitter', color: '#1DA1F2' },
        'linkedin': { icon: 'bxl-linkedin', color: '#0077B5' },
        'pinterest': { icon: 'bxl-pinterest', color: '#E60023' }
    };

    // Status → tk-badge variant mapping
    const statusConfig = {
        'published':            { badge: 'tk-badge-success', text: 'Published' },
        'scheduled':            { badge: 'tk-badge-warning', text: 'Scheduled' },
        'failed':               { badge: 'tk-badge-danger',  text: 'Failed' },
        'pending':              { badge: 'tk-badge-info',    text: 'Pending' },
        'partially_published':  { badge: 'tk-badge-primary', text: 'Partially Published' }
    };

    const sts = statusConfig[postData.status] || { badge: '', text: postData.status.replace('_', ' ') };

    let content = `
    <div class="tk-stack" style="gap:16px">

        <!-- Post header tile -->
        <div class="tk-tile">
            <div class="tk-between" style="margin-bottom:8px">
                <div class="tk-cluster">
                    <i class="bx bx-file-blank" style="font-size:18px;color:var(--signal)"></i>
                    <span style="font-size:14px;font-weight:600;color:var(--fg-0)">Post #${postData.id}</span>
                </div>
                <span class="tk-badge ${sts.badge}">${sts.text}</span>
            </div>
            <div class="tk-meta" style="grid-template-columns:90px 1fr">
                <dt>Created</dt>
                <dd>${formatQuickViewDate(postData.created_at)}</dd>
                ${postData.scheduled_at ? `
                <dt>Scheduled</dt>
                <dd>${formatQuickViewDate(postData.scheduled_at)}</dd>
                ` : ''}
            </div>
        </div>

        <!-- Caption section -->
        <div class="tk-card">
            <div class="tk-card-head">
                <h6 class="tk-card-title" style="display:flex;align-items:center;gap:6px">
                    <i class="bx bx-message-square-detail" style="color:var(--signal)"></i>
                    Caption
                </h6>
            </div>
            <div class="tk-card-body">
                ${postData.caption
                    ? `<div class="tk-tile" style="max-height:180px;overflow-y:auto"><p style="margin:0;white-space:pre-line;color:var(--fg-1);font-size:var(--fs-base);line-height:1.6">${postData.caption}</p></div>`
                    : `<p class="tk-muted" style="margin:0;font-style:italic;font-size:var(--fs-base)">No caption provided</p>`
                }
            </div>
        </div>

        <!-- Platform status section -->
        <div class="tk-card">
            <div class="tk-card-head">
                <h6 class="tk-card-title" style="display:flex;align-items:center;gap:6px">
                    <i class="bx bx-globe" style="color:var(--signal)"></i>
                    Platform Status
                </h6>
            </div>
            <div class="tk-card-body">
                <div class="tk-stack" style="gap:8px">`;

    // Generate platform status tiles
    const platforms = postData.platforms || [];
    $.each(platforms, function (index, platform) {
        const log = postData.response_logs ? postData.response_logs[platform] : null;
        const config = platformConfig[platform] || { icon: 'bx-circle', color: '#6c757d' };

        const isSuccess = log && log.success === true;
        const isFailed  = log && log.success === false;

        let badgeClass, statusIcon, statusText, statusDetail;

        if (isSuccess) {
            badgeClass  = 'tk-badge-success';
            statusIcon  = 'bx-check-circle';
            statusText  = 'Published';
            statusDetail = `Published: ${formatQuickViewDateTime(log.published_at)}`;
        } else if (isFailed) {
            badgeClass  = 'tk-badge-danger';
            statusIcon  = 'bx-x-circle';
            statusText  = 'Failed';
            statusDetail = `Failed: ${log.failed_at ? formatQuickViewDateTime(log.failed_at) : 'Unknown'}`;
        } else {
            badgeClass  = '';
            statusIcon  = 'bx-time-five';
            statusText  = 'Pending';
            statusDetail = 'Not processed yet';
        }

        content += `
                    <div class="tk-tile">
                        <div class="tk-between" style="margin-bottom:4px">
                            <div class="tk-cluster">
                                <i class="bx ${config.icon}" style="font-size:20px;color:${config.color}"></i>
                                <span style="font-weight:600;color:var(--fg-0);font-size:var(--fs-md)">${platform.charAt(0).toUpperCase() + platform.slice(1)}</span>
                            </div>
                            <span class="tk-badge ${badgeClass}">
                                <i class="bx ${statusIcon}" style="font-size:12px"></i>
                                ${statusText}
                            </span>
                        </div>
                        <span class="tk-muted" style="font-size:var(--fs-sm)">${statusDetail}</span>`;

        // Show error details if failed
        if (isFailed && log.error) {
            const errorMsg = log.error.length > 100 ? log.error.substring(0, 100) + '...' : log.error;
            content += `
                        <div style="margin-top:8px;padding:8px 10px;background:oklch(from var(--err) l c h / 0.08);border:1px solid oklch(from var(--err) l c h / 0.18);border-radius:var(--r-2)">
                            <strong style="color:var(--err);font-size:var(--fs-sm)">${log.error_code || 'Error'}</strong>
                            <p style="margin:2px 0 0;color:var(--fg-2);font-size:var(--fs-sm);line-height:1.4">${errorMsg}</p>
                        </div>`;
        }

        content += `</div>`;
    });

    content += `
                </div>
            </div>
        </div>

        <!-- Summary statistics -->
        <div class="tk-facts" style="grid-template-columns:repeat(3,1fr)">`;

    const successCount = postData.successful_platforms ? postData.successful_platforms.length : 0;
    const failedCount  = postData.failed_platforms ? postData.failed_platforms.length : 0;
    const pendingCount = platforms.length - successCount - failedCount;

    const stats = [
        { label: 'Published', count: successCount, icon: 'bx-check-circle', color: 'var(--ok)' },
        { label: 'Failed',    count: failedCount,  icon: 'bx-x-circle',     color: 'var(--err)' },
        { label: 'Pending',   count: pendingCount,  icon: 'bx-time-five',    color: 'var(--fg-3)' }
    ];

    $.each(stats, function (index, stat) {
        content += `
            <div class="tk-fact" style="flex-direction:column;align-items:center;text-align:center;padding:14px 11px">
                <i class="bx ${stat.icon}" style="font-size:22px;color:${stat.color};margin-bottom:4px"></i>
                <span class="tk-fact-v" style="font-size:20px">${stat.count}</span>
                <span class="tk-fact-k">${stat.label}</span>
            </div>`;
    });

    content += `
        </div>
    </div>`;

    $modalContent.html(content);

    // Update the modal title
    $('#quickViewModalLabel').text(`Post #${postData.id} — Publishing Details`);

    // Update action buttons with post data
    updateQuickViewActionButtons(postData);
}

function updateQuickViewActionButtons(postData) {
    // Show/hide publish now button based on status
    const publishBtn = $('#publishNowBtn');
    if (['pending', 'scheduled', 'failed'].includes(postData.status)) {
        publishBtn.show();
    } else {
        publishBtn.hide();
    }

    // Store post ID in buttons for the action handlers
    $('#editPostBtn').data('post-id', postData.id);
    $('#deletePostBtn').data('post-id', postData.id);
    $('#publishNowBtn').data('post-id', postData.id);
}

// Helper functions for date formatting
function formatQuickViewDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatQuickViewDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Generic function to show quick view modal (can be called from anywhere)
function openQuickViewModal(postId) {
    showPostQuickView(postId);
    $('#quickViewModal').modal('show');
}


