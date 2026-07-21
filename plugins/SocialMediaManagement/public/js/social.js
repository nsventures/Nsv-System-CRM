/**
 * Social Media Scheduler - Refactored & Extensible
 * Platform Configuration Approach
 */

'use strict';

// ============================================================================
// PLATFORM CONFIGURATION - Single Source of Truth
// ============================================================================

const PLATFORM_CONFIG = {
    facebook: {
        name: 'Facebook',
        icon: 'bxl-facebook-circle',
        color: '#1877f2',
        requirements: {
            maxCaptionLength: 63206,
            mediaRequired: false,
            acceptedFormats: ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/mov', 'video/avi'],
            mediaTypes: ['image', 'video']
        },
        validation: {
            caption: (length) => length <= 63206,
            media: () => true // No media requirement
        }
    },
    instagram: {
        name: 'Instagram',
        icon: 'bxl-instagram',
        color: '#e4405f',
        requirements: {
            maxCaptionLength: 2200,
            mediaRequired: true,
            acceptedFormats: ['image/jpeg', 'image/png', 'video/mp4'],
            mediaTypes: ['image', 'video']
        },
        validation: {
            caption: (length) => length <= 2200,
            media: (hasMedia) => hasMedia
        }
    },
    linkedin: {
        name: 'LinkedIn',
        icon: 'bxl-linkedin',
        color: '#0077b5',
        requirements: {
            maxCaptionLength: 3000,
            mediaRequired: false,
            acceptedFormats: ['image/jpeg', 'image/png', 'video/mp4'],
            mediaTypes: ['image', 'video']
        },
        validation: {
            caption: (length) => length <= 3000,
            media: () => true
        }
    },
    pinterest: {
        name: 'Pinterest',
        icon: 'bxl-pinterest',
        color: '#e60023',
        requirements: {
            maxCaptionLength: 500,
            mediaRequired: true,
            acceptedFormats: ['image/jpeg', 'image/png'],
            mediaTypes: ['image']
        },
        validation: {
            caption: (length) => length <= 500,
            media: (hasMedia, mediaType) => hasMedia && mediaType === 'image'
        }
    },
    youtube: {
        name: 'YouTube',
        icon: 'bxl-youtube',
        color: '#ff0000',
        requirements: {
            maxCaptionLength: 5000,
            mediaRequired: true,
            acceptedFormats: ['video/mp4', 'video/mov', 'video/avi'],
            mediaTypes: ['video']
        },
        validation: {
            caption: (length) => length <= 5000,
            media: (hasMedia, mediaType) => hasMedia && mediaType === 'video'
        }
    },
    tiktok: {
        name: 'TikTok',
        icon: 'bxl-tiktok',
        color: '#ff0000',
        requirements: {
            maxCaptionLength: 5000,
            mediaRequired: true,
            acceptedFormats: ['video/mp4', 'video/mov', 'video/avi'],
            mediaTypes: ['video']
        },
        validation: {
            caption: (length) => length <= 5000,
            media: (hasMedia, mediaType) => hasMedia && mediaType === 'video'
        }
    }
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

const SocialUtils = {
    /**
     * Get platform configuration
     */
    getPlatformConfig(platformKey) {
        return PLATFORM_CONFIG[platformKey] || null;
    },

    /**
     * Get all platforms
     */
    getAllPlatforms() {
        return Object.keys(PLATFORM_CONFIG);
    },

    /**
     * Get selected platforms
     */
    getSelectedPlatforms() {
        const selected = [];
        $('.platform-card.selected').each(function () {
            selected.push($(this).data('platform'));
        });
        return selected;
    },

    /**
     * Check if media exists
     */
    hasMedia() {
        return (window.existingMedia && window.existingMedia.length > 0) ||
            (window.newMediaFiles && window.newMediaFiles.length > 0);
    },

    /**
     * Get media types present
     */
    getMediaTypes() {
        const types = new Set();

        if (window.existingMedia) {
            window.existingMedia.forEach(media => types.add(media.type));
        }

        if (window.newMediaFiles) {
            window.newMediaFiles.forEach(media => {
                if (media.type.startsWith('image/')) types.add('image');
                if (media.type.startsWith('video/')) types.add('video');
            });
        }

        return Array.from(types);
    },

    /**
     * Format platform name for display
     */
    formatPlatformName(platformKey) {
        const config = this.getPlatformConfig(platformKey);
        return config ? config.name : platformKey.charAt(0).toUpperCase() + platformKey.slice(1);
    }
};

// ============================================================================
// PLATFORM VALIDATOR
// ============================================================================

const PlatformValidator = {
    /**
     * Validate single platform requirements
     */
    validatePlatform(platformKey, caption, hasMedia, mediaTypes) {
        const config = PLATFORM_CONFIG[platformKey];
        if (!config) return null;

        const issues = [];
        const captionLength = caption.length;

        // Validate caption length
        if (!config.validation.caption(captionLength)) {
            issues.push(`${config.name} posts must be ${config.requirements.maxCaptionLength} characters or less`);
        }

        // Validate media requirements
        if (config.requirements.mediaRequired) {
            if (!hasMedia) {
                const mediaTypeStr = config.requirements.mediaTypes.join(' or ');
                issues.push(`${config.name} posts require at least one ${mediaTypeStr}`);
            } else if (config.requirements.mediaTypes.length > 0) {
                // Check if media type matches platform requirements
                const hasValidMediaType = mediaTypes.some(type =>
                    config.requirements.mediaTypes.includes(type)
                );

                if (!hasValidMediaType) {
                    const requiredTypes = config.requirements.mediaTypes.join(' or ');
                    issues.push(`${config.name} requires ${requiredTypes}`);
                }
            }
        }

        return issues;
    },

    /**
     * Validate all selected platforms
     */
    validateAllPlatforms() {
        const editor = tinymce.get('social-media-caption');
        const caption = editor ? editor.getContent({ format: 'text' }) : $('#social-media-caption').val();
        const selectedPlatforms = SocialUtils.getSelectedPlatforms();
        const hasMedia = SocialUtils.hasMedia();
        const mediaTypes = SocialUtils.getMediaTypes();

        let allIssues = [];

        selectedPlatforms.forEach(platform => {
            const platformIssues = this.validatePlatform(platform, caption, hasMedia, mediaTypes);
            if (platformIssues && platformIssues.length > 0) {
                allIssues = allIssues.concat(platformIssues);
            }
        });

        return allIssues;
    }
};

// ============================================================================
// UI MANAGER
// ============================================================================

const UIManager = {
    /**
     * Update selected platforms display
     */
    updateSelectedPlatforms() {
        const selectedPlatforms = SocialUtils.getSelectedPlatforms();

        if (selectedPlatforms.length === 0) {
            $('#selectedPlatforms').html(
                `<small class="text-muted">${window.labels.no_platforms_selected}</small>`
            );
        } else {
            const badges = selectedPlatforms.map(platformKey => {

                const config = PLATFORM_CONFIG[platformKey];
                return `<span class="platform-badge ${platformKey}">${config.name}</span>`;
            }).join('');
            console.log(badges);
            $('#selectedPlatforms').html(badges);
        }
    },

    /**
     * Update character counter
     */
    updateCharacterCounter() {
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
    },

    /**
     * Update post preview
     */
    updatePostPreview() {
        const editor = tinymce.get('social-media-caption');
        const caption = editor ? editor.getContent({ format: 'html' }) : $('#social-media-caption').val();

        let previewHtml = '';

        if (caption.trim()) {
            previewHtml += `<div class="mb-2">${caption}</div>`;
        }

        // Add media preview with carousel
        const mediaItems = this.getMediaPreviewItems();
        if (mediaItems.length > 0) {
            previewHtml += this.buildCarousel(mediaItems);
        }

        if (!previewHtml) {
            previewHtml = `<div class="text-center text-muted py-3">${window.labels.preview_will_appear_here || 'Post preview will appear here...'}</div>`;
        }

        $('#post-preview').html(previewHtml);
    },

    /**
     * Build carousel HTML
     */
    buildCarousel(mediaItems) {
        return `
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
    },

    /**
     * Get media preview items
     */
    getMediaPreviewItems() {
        let mediaItems = [];

        // Existing media (for edit mode)
        if (typeof window.existingMedia !== 'undefined' && window.existingMedia.length > 0) {
            window.existingMedia.forEach(media => {
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
};

// ============================================================================
// MEDIA MANAGER
// ============================================================================

const MediaManager = {

    /**
  * Handle media upload
  */
    handleMediaUpload(files) {
        if (!window.newMediaFiles) {
            window.newMediaFiles = [];
        }

        window.newMediaFiles = [];

        if (files.length === 0) {
            UIManager.updatePostPreview();
            this.clearNewMediaPreview();
            return;
        }

        // Always allow large files - just warn about platform compatibility
        Array.from(files).forEach((file, index) => {
            if (!file.type.match(/^(image|video)\//)) {
                toastr.warning(`Skipping ${file.name}: Only images and videos are supported`);
                return;
            }

            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);

            // Show info for large files
            if (file.size > 10 * 1024 * 1024 && file.size <= 2 * 1024 * 1024 * 1024) {
                toastr.info(`Large file detected: ${file.name} (${fileSizeMB}MB). Make sure to select YouTube if uploading videos over 10MB.`, '', {
                    timeOut: 5000
                });
            }

            // Block files over 2GB
            if (file.size > 2 * 1024 * 1024 * 1024) {
                toastr.error(`File too large: ${file.name} (${fileSizeMB}MB). Maximum allowed: 2GB`);
                return;
            }

            // Skip preview for large files (over 50MB) - just store the file reference
            if (file.size > 50 * 1024 * 1024) {
                window.newMediaFiles.push({
                    file: file,
                    url: null,
                    type: file.type,
                    name: file.name,
                    size: file.size,
                    large: true
                });

                // Update UI after processing all files
                if (window.newMediaFiles.length === files.length) {
                    UIManager.updatePostPreview();
                    this.displayNewMediaPreview();
                }
                return;
            }

            // Create preview for smaller files
            const reader = new FileReader();
            reader.onload = (e) => {
                window.newMediaFiles.push({
                    file: file,
                    url: e.target.result,
                    type: file.type,
                    name: file.name,
                    size: file.size,
                    large: false
                });

                if (window.newMediaFiles.length === files.length) {
                    UIManager.updatePostPreview();
                    this.displayNewMediaPreview();
                }
            };

            reader.readAsDataURL(file);
        });
    },

    /**
     * Display new media preview with compatibility warnings
     */
    displayNewMediaPreview() {
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

        window.newMediaFiles.forEach((media, index) => {
            const fileSize = (media.size / (1024 * 1024)).toFixed(2);
            const isLargeFile = media.size > 10 * 1024 * 1024;

            if (media.large) {
                const icon = media.type.startsWith('video/') ? 'bx-movie-play' : 'bx-image';
                previewHtml += `
                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 d-flex flex-column align-items-center justify-content-center text-center position-relative" 
                         style="height: 180px; background: #f8f9fa;">
                        ${isLargeFile ? '<span class="badge bg-warning position-absolute top-0 end-0 m-2"><i class="bx bx-info-circle"></i> Large File</span>' : ''}
                        <i class="bx ${icon} fs-1 text-primary"></i>
                        <p class="mb-1 fw-semibold small">${media.name}</p>
                        <small class="text-muted">${fileSize} MB</small>
                        <small class="text-muted">Ready to upload</small>
                    </div>
                </div>
            `;
            } else if (media.type.startsWith("image/")) {
                previewHtml += `
                <div class="col-12 col-md-4">
                    <div class="border rounded overflow-hidden position-relative" style="height: 180px;">
                        ${isLargeFile ? '<span class="badge bg-warning position-absolute top-0 end-0 m-2"><i class="bx bx-info-circle"></i> Large File</span>' : ''}
                        <img src="${media.url}" alt="${media.name}" 
                             class="w-100 h-100" style="object-fit: cover;">
                    </div>
                </div>
            `;
            } else if (media.type.startsWith("video/")) {
                previewHtml += `
                <div class="col-12 col-md-4">
                    <div class="border rounded overflow-hidden position-relative" style="height: 180px;">
                        ${isLargeFile ? '<span class="badge bg-warning position-absolute top-0 end-0 m-2"><i class="bx bx-info-circle"></i> Large File</span>' : ''}
                        <video controls class="w-100 h-100" style="object-fit: cover;">
                            <source src="${media.url}" type="${media.type}">
                        </video>
                    </div>
                </div>
            `;
            }
        });

        $('#new-media-preview').html(previewHtml);
    },

    /**
     * Build single media preview item
     */
    buildMediaPreviewItem(mediaFile, index) {
        let mediaHtml = '';

        if (mediaFile.type.startsWith('image/')) {
            mediaHtml = `<img src="${mediaFile.url}" class="media-thumb img-fluid rounded" alt="New Media">`;
        } else if (mediaFile.type.startsWith('video/')) {
            mediaHtml = `
                <video class="media-thumb img-fluid rounded" muted>
                    <source src="${mediaFile.url}" type="${mediaFile.type}">
                </video>
                <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-dark"><i class="bx bx-play-circle me-1"></i></span>
                </div>
            `;
        }

        return `
            <div class="col-6 col-md-3">
                <div class="media-item position-relative">
                    ${mediaHtml}
                    <button type="button" class="remove-new-media" data-index="${index}">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Remove new media
     */
    removeNewMedia(element) {
        const index = parseInt(element.data('index'));

        if (window.newMediaFiles && window.newMediaFiles[index]) {
            window.newMediaFiles.splice(index, 1);
        }

        element.closest('.col-6, .col-md-3').remove();
        $('#media-upload').val('');
        UIManager.updatePostPreview();

        if (!window.newMediaFiles || window.newMediaFiles.length === 0) {
            $('#new-media-preview-section').remove();
        }
    },

    /**
     * Clear new media preview
     */
    clearNewMediaPreview() {
        $('#new-media-preview-section').remove();
        window.newMediaFiles = [];
    },

    /**
     * Remove existing media with AJAX (edit mode)
     */
    removeExistingMediaWithAjax(element, mediaId) {
        const $mediaItem = element.closest('.col-6, .col-md-3');
        $mediaItem.addClass('opacity-50');
        element.prop('disabled', true);

        $.ajax({
            url: `/social-media-scheduler/destroy-media/${mediaId}`,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.error === false) {
                    toastr.success(window.labels.media_deleted_successfully || 'Media deleted successfully.');

                    $mediaItem.fadeOut(300, function () {
                        $(this).remove();
                    });

                    if (window.existingMedia) {
                        window.existingMedia = window.existingMedia.filter(media => media.id !== mediaId);
                    }

                    UIManager.updatePostPreview();

                    if (window.existingMedia && window.existingMedia.length === 0) {
                        $('#existing-media-section').fadeOut(300);
                    }
                } else {
                    toastr.error(response.message || window.labels.error_deleting_media || 'Error deleting media.');
                    $mediaItem.removeClass('opacity-50');
                    element.prop('disabled', false);
                }
            },
            error: (xhr) => {
                let errorMessage = window.labels.error_deleting_media || 'Error deleting media.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
                $mediaItem.removeClass('opacity-50');
                element.prop('disabled', false);
            }
        });
    }
};

// ============================================================================
// AI CAPTION GENERATOR
// ============================================================================

const AICaptionGenerator = {
    /**
     * Initialize AI caption functionality
     */
    initialize() {
        // Toggle custom prompt visibility
        $(document).on('change', '.social-caption-enable-custom-prompt', function () {
            const isChecked = $(this).is(':checked');
            $('.social-caption-custom-prompt-container').toggleClass('d-none', !isChecked);
        });

        // Handle AI caption generation
        $(document).on('click', '.social-caption-generate-ai', function () {
            AICaptionGenerator.generateCaption($(this));
        });
    },

    /**
     * Generate AI caption
     */
    generateCaption($btn) {
        const $scope = $btn.closest('.social-caption-ai-wrapper');

        if (!$scope.find('#social-media-caption').length) {
            return;
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

        const existingCaption = tinymce.get('social-media-caption') ?
            tinymce.get('social-media-caption').getContent({ format: 'html' }) :
            $('#social-media-caption').val();

        const selectedPlatforms = SocialUtils.getSelectedPlatforms();

        // Show loading state
        const $loader = $scope.find('.social-caption-ai-loader');
        $btn.prop('disabled', true);
        if ($loader.length) $loader.removeClass('d-none');

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
            success: (response) => {
                if (response.error) {
                    toastr.error(response.message);
                } else {
                    toastr.success(response.message || 'Caption generated successfully!');

                    if (tinymce.get('social-media-caption')) {
                        tinymce.get('social-media-caption').setContent(response.caption || response.description);
                    } else {
                        $('#social-media-caption').val(response.caption || response.description);
                    }

                    UIManager.updateCharacterCounter();
                    UIManager.updatePostPreview();
                }
            },
            error: (xhr) => {
                let errorMessage = window.labels.something_went_wrong || 'Something went wrong.';

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('\n');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                toastr.error(errorMessage);
            },
            complete: () => {
                $btn.prop('disabled', false);
                if ($loader.length) $loader.addClass('d-none');
            }
        });
    }
};

// ============================================================================
// EVENT HANDLERS
// ============================================================================

const EventHandlers = {
    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        // Platform selection
        $('.platform-card').on('click', function () {
            const $card = $(this);
            const $checkbox = $card.find('input[type="checkbox"]');

            // Use central toggle to keep behavior consistent and ensure YouTube fields toggle
            const newChecked = !$checkbox.is(':checked');
            togglePlatformSelection($card, newChecked);

            // Trigger a native change event on the checkbox for any other listeners
            $checkbox.trigger('change');
        });

        // Post type change
        $('input[name="post_type"]').on('change', function () {
            const postType = $(this).val();
            if (postType === 'schedule') {
                $('#schedule-section').show();
                $('#submit-text').text(window.labels.schedule_post || 'Schedule Post');
            } else {
                $('#schedule-section').hide();
                const submitText = window.isEditMode ?
                    (window.labels.update_post || 'Update Post') :
                    (window.labels.post_now || 'Post Now');
                $('#submit-text').text(submitText);
            }
        });

        // Media upload
        $('#media-upload').on('change', function () {
            MediaManager.handleMediaUpload(this.files);
        });

        // Remove new media
        $(document).on('click', '.remove-new-media', function () {
            MediaManager.removeNewMedia($(this));
        });

        // Form submission
        $(document).on('submit', '#create-post-form, #edit-post-form', function (e) {
            return EventHandlers.handleFormSubmit(e);
        });
    },

    /**
     * Handle form submission
     */
    /**
 * Handle form submission
 */
    handleFormSubmit(e) {
        const $submitBtn = $('#submit_btn');
        const $form = $(e.target);

        $submitBtn.prop('disabled', true);

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

        // Check for large files and YouTube platform compatibility
        const selectedPlatforms = SocialUtils.getSelectedPlatforms();
        const hasYouTube = selectedPlatforms.includes('youtube');

        if (window.newMediaFiles && window.newMediaFiles.length > 0) {
            for (let media of window.newMediaFiles) {
                const fileSizeMB = (media.size / (1024 * 1024)).toFixed(2);

                // If file is over 10MB and YouTube is NOT selected
                if (media.size > 10 * 1024 * 1024 && !hasYouTube) {
                    e.preventDefault();
                    toastr.error(`Large file detected (${fileSizeMB}MB). Files over 10MB can only be uploaded to YouTube. Please either:<br>1. Select YouTube as a platform, or<br>2. Use a smaller file (under 10MB)`, '', {
                        timeOut: 8000,
                        closeButton: true
                    });
                    restoreButton();
                    return false;
                }
            }
        }

        // Show upload progress for large files
        let hasLargeFiles = false;
        if (window.newMediaFiles) {
            window.newMediaFiles.forEach(media => {
                if (media.size && media.size > 100 * 1024 * 1024) { // 100MB
                    hasLargeFiles = true;
                }
            });
        }

        if (hasLargeFiles) {
            $submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Uploading large file, please wait...');
        }

        const issues = PlatformValidator.validateAllPlatforms();
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
    },

    /**
     * Initialize edit mode features
     */
    initializeEditModeFeatures() {
        if (!window.isEditMode) return;

        // Handle existing media removal
        $(document).on('click', '.remove-media', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(this);
            const mediaId = $btn.data('media-id');

            window.mediaToRemove = {
                element: $btn,
                mediaId: mediaId
            };

            $('#confirmDeleteModal').modal('show');
        });

        // Handle modal confirmation
        $('#confirmDeleteBtn').on('click', function () {
            if (window.mediaToRemove) {
                const { element, mediaId } = window.mediaToRemove;
                MediaManager.removeExistingMediaWithAjax(element, mediaId);
                $('#confirmDeleteModal').modal('hide');
                window.mediaToRemove = null;
            }
        });

        // Set initial submit button text
        const currentPostType = $('input[name="post_type"]:checked').val();
        if (currentPostType === 'schedule') {
            $('#submit-text').text(window.labels.schedule_post || 'Schedule Post');
        } else {
            $('#submit-text').text(window.labels.update_post || 'Update Post');
        }
    }
};

// ============================================================================
// INITIALIZATION
// ============================================================================

$(document).ready(function () {
    // Initialize TinyMCE
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            height: 250,
            selector: '.caption',
            plugins: 'advlist autolink lists link image charmap preview',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image',
            menubar: false,
            setup: function (editor) {
                editor.on('init', function () {
                    UIManager.updateCharacterCounter();
                    UIManager.updatePostPreview();
                });
                editor.on('change keyup input', function () {
                    UIManager.updateCharacterCounter();
                    UIManager.updatePostPreview();
                });
            }
        });
    } else {
        $('#social-media-caption').on('input', function () {
            UIManager.updateCharacterCounter();
            UIManager.updatePostPreview();
        });
    }

    // Initialize UI
    UIManager.updateSelectedPlatforms();
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Setup event listeners
    EventHandlers.setupEventListeners();

    // Initialize AI caption generator
    AICaptionGenerator.initialize();

    // Load existing data if editing
    if (typeof window.existingMedia !== 'undefined' && window.existingMedia.length > 0) {
        UIManager.updatePostPreview();
    }

    if (typeof window.existingCaption !== 'undefined' && window.existingCaption) {
        if (tinymce.get('social-media-caption')) {
            tinymce.get('social-media-caption').setContent(window.existingCaption);
        } else {
            $('#social-media-caption').val(window.existingCaption);
        }
        UIManager.updateCharacterCounter();
        UIManager.updatePostPreview();
    }

    // Initialize edit mode features
    EventHandlers.initializeEditModeFeatures();
});

// ============================================================================
// LEGACY COMPATIBILITY & TABLE FUNCTIONS
// ============================================================================

// Query params for tables (from original code)
function queryParams(params) {
    return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
        status: $('#select_social_stastuses').val(),
        platform: $('#select_social_platforms').val(),
        account: $('#select_account_filter').val()
    };
}

// Table refresh handlers
$(document).ready(function () {
    $("#select_social_platforms").on("change", function () {
        $('#table').bootstrapTable('refresh');
    });

    $('#select_social_stastuses').on('change', function () {
        $('#table').bootstrapTable('refresh');
    });
});

// ============================================================================
// QUICK VIEW FUNCTIONALITY (from original code)
// ============================================================================

function showPostQuickView(postId) {
    const $modalContent = $('#quickViewContent');
    $modalContent.html(`
        <div class="qv-loading">
            <div class="qv-loading-spinner"></div>
            <p class="qv-loading-text">Loading post details...</p>
        </div>
    `);

    let baseUrl;
    if (typeof calendarConfig !== 'undefined' && calendarConfig.routes && calendarConfig.routes.quickView) {
        baseUrl = calendarConfig.routes.quickView.replace('{id}', postId);
    } else {
        baseUrl = `/social-media-scheduler/posts/${postId}`;
    }

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
            <div class="qv-error-state">
                <i class="bx bx-error-circle qv-error-icon"></i>
                <p class="qv-error-message">Error loading post details: ${errorMessage}</p>
            </div>
        `);
        });
}

function showQuickView(postData) {
    const $modalContent = $('#quickViewContent');

    // Status configuration
    const statusConfig = {
        'published': { class: 'bg-success', text: 'Published' },
        'scheduled': { class: 'bg-warning', text: 'Scheduled' },
        'failed': { class: 'bg-danger', text: 'Failed' },
        'pending': { class: 'bg-info', text: 'Pending' },
        'partially_published': { class: 'bg-primary', text: 'Partially Published' }
    };

    let content = `
    <div class="qv-container">
        <!-- Post Header -->
        <div class="qv-post-header">
            <div class="qv-post-info">
                <div class="qv-post-title">
                    <i class="bx bx-file-blank qv-post-icon"></i>
                    <h3>Post #${postData.id}</h3>
                </div>
                <div class="qv-post-meta">
                    <span class="qv-meta-item">
                        <i class="bx bx-time-five"></i>
                        Created: ${formatQuickViewDate(postData.created_at)}
                    </span>
                    ${postData.scheduled_at ? `
                        <span class="qv-meta-item">
                            <i class="bx bx-calendar"></i>
                            Scheduled: ${formatQuickViewDate(postData.scheduled_at)}
                        </span>
                    ` : ''}
                </div>
            </div>
            <div class="qv-post-status">
                <span class="qv-status-badge text-white ${statusConfig[postData.status]?.class || 'qv-status-pending'}">
                    ${statusConfig[postData.status]?.text || postData.status.replace('_', ' ')}
                </span>
            </div>
        </div>

        <!-- Caption Section -->
        <div class="qv-content-section">
            <h4 class="qv-section-title">
                <i class="bx bx-message-square-detail"></i>
                Caption
            </h4>
            <div class="qv-caption-preview">
                ${postData.caption ?
            `<p class="qv-caption-text">${postData.caption}</p>` :
            `<p class="qv-caption-empty">No caption provided</p>`
        }
            </div>
        </div>

        <!-- Platform Status Section -->
        <div class="qv-content-section">
            <h4 class="qv-section-title">
                <i class="bx bx-globe"></i>
                Platform Status
            </h4>
            <div class="qv-platforms-grid">
    `;

    // Generate platform status cards using PLATFORM_CONFIG
    const platforms = postData.platforms || [];
    $.each(platforms, function (index, platform) {
        const log = postData.response_logs ? postData.response_logs[platform] : null;
        const config = PLATFORM_CONFIG[platform] || {
            icon: 'bx-circle',
            color: '#6c757d',
            name: platform.charAt(0).toUpperCase() + platform.slice(1)
        };

        console.log(config)
        const isSuccess = log && log.success === true;
        const isFailed = log && log.success === false;
        const isPending = !log;

        let statusClass, statusIcon, statusText, statusDetail;

        if (isSuccess) {
            statusClass = 'qv-platform-success';
            statusIcon = 'bx-check-circle';
            statusText = 'Published';
            statusDetail = `Published: ${formatQuickViewDateTime(log.published_at)}`;
        } else if (isFailed) {
            statusClass = 'qv-platform-failed';
            statusIcon = 'bx-x-circle';
            statusText = 'Failed';
            statusDetail = `Failed: ${log.failed_at ? formatQuickViewDateTime(log.failed_at) : 'Unknown'}`;
        } else {
            statusClass = 'qv-platform-pending';
            statusIcon = 'bx-time-five';
            statusText = 'Pending';
            statusDetail = 'Not processed yet';
        }

        content += `
            <div class="qv-platform-card ${statusClass}">
                <div class="qv-platform-header">
                    <div class="qv-platform-info">
                        <i class="bx ${config.icon} qv-platform-icon" style="color: ${config.color}"></i>
                        <span class="qv-platform-name">${config.name}</span>
                    </div>
                    <div class="qv-platform-status-badge">
                        <i class="bx ${statusIcon}"></i>
                        <span>${statusText}</span>
                    </div>
                </div>
                <div class="qv-platform-detail">
                    ${statusDetail}
                </div>
        `;

        // Show error details if failed
        if (isFailed && log.error) {
            const errorMsg = log.error.length > 100 ? log.error.substring(0, 100) + '...' : log.error;
            content += `
                <div class="qv-platform-error">
                    <div class="qv-error-header">
                        <strong>${log.error_code || 'Error'}</strong>
                    </div>
                    <div class="qv-error-message">${errorMsg}</div>
                </div>
            `;
        }

        content += `</div>`;
    });

    content += `
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="qv-content-section">
            <div class="qv-stats-grid">
    `;

    const successCount = postData.successful_platforms ? postData.successful_platforms.length : 0;
    const failedCount = postData.failed_platforms ? postData.failed_platforms.length : 0;
    const pendingCount = platforms.length - successCount - failedCount;

    const stats = [
        { label: 'Published', count: successCount, icon: 'bx-check-circle', class: 'qv-stat-success' },
        { label: 'Failed', count: failedCount, icon: 'bx-x-circle', class: 'qv-stat-failed' },
        { label: 'Pending', count: pendingCount, icon: 'bx-time-five', class: 'qv-stat-pending' }
    ];

    $.each(stats, function (index, stat) {
        content += `
            <div class="qv-stat-card ${stat.class}">
                <i class="bx ${stat.icon} qv-stat-icon"></i>
                <div class="qv-stat-number">${stat.count}</div>
                <div class="qv-stat-label">${stat.label}</div>
            </div>
        `;
    });

    content += `
            </div>
        </div>
    </div>
    `;

    $modalContent.html(content);
    $('#quickViewModalLabel').text(`Post #${postData.id} - Publishing Details`);
    updateQuickViewActionButtons(postData);
}

function updateQuickViewActionButtons(postData) {
    const publishBtn = $('#publishNowBtn');
    if (['pending', 'scheduled', 'failed'].includes(postData.status)) {
        publishBtn.show();
    } else {
        publishBtn.hide();
    }

    $('#editPostBtn').data('post-id', postData.id);
    $('#deletePostBtn').data('post-id', postData.id);
    $('#publishNowBtn').data('post-id', postData.id);
}

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

function openQuickViewModal(postId) {
    showPostQuickView(postId);
    $('#quickViewModal').modal('show');
}

// ============================================================================
// PUBLIC API - Export utility functions for use in other modules
// ============================================================================

window.SocialScheduler = {
    PLATFORM_CONFIG,
    SocialUtils,
    PlatformValidator,
    UIManager,
    MediaManager,
    AICaptionGenerator,
    EventHandlers
}

// ====== Add helper to centralize selection toggle ======
function togglePlatformSelection($card, checked) {
    const checkbox = $card.find('input[type="checkbox"]');
    checkbox.prop('checked', checked);
    $card.toggleClass('selected', checked);

    // If platform is youtube, toggle fields
    const platform = $card.data('platform');
    if (platform === 'youtube') {
        toggleYouTubeFields();
    }

    UIManager.updateSelectedPlatforms();
    UIManager.updatePostPreview();
}



// Hardcoded YouTube categories
const youtubeCategories = [
    { id: "1", title: "Film & Animation" },
    { id: "2", title: "Autos & Vehicles" },
    { id: "10", title: "Music" },
    { id: "15", title: "Pets & Animals" },
    { id: "17", title: "Sports" },
    { id: "19", title: "Travel & Events" },
    { id: "20", title: "Gaming" },
    { id: "22", title: "People & Blogs" },
    { id: "23", title: "Comedy" },
    { id: "24", title: "Entertainment" },
    { id: "25", title: "News & Politics" },
    { id: "26", title: "Howto & Style" },
    { id: "27", title: "Education" },
    { id: "28", title: "Science & Technology" },
    { id: "29", title: "Nonprofits & Activism" }
];

// Populate YouTube categories from hardcoded list
function populateYouTubeCategories(selectedCategoryId = null) {
    const select = $('#youtube_category');
    select.empty();
    select.append('<option value="">Select Category</option>');

    youtubeCategories.forEach(category => {
        const selected = selectedCategoryId && category.id === selectedCategoryId ? 'selected' : '';
        select.append(`<option value="${category.id}" ${selected}>${category.title}</option>`);
    });
}

// YouTube-specific fields toggle
function toggleYouTubeFields() {
    const youtubeSelected = $('#platform-youtube').is(':checked');
    const youtubeFieldsContainer = $('#youtube-fields-container');

    if (youtubeSelected) {
        youtubeFieldsContainer.slideDown(300);
        // Make required fields mandatory
        $('#youtube_title').attr('required', true);
        $('#youtube_category').attr('required', true);
        $('#privacy_status').attr('required', true);

        // Populate categories only when YouTube is selected (optimization)
        const categorySelect = $('#youtube_category');
        if (categorySelect.find('option').length <= 1) {
            // In edit mode, preserve the selected category
            const existingCategory = window.existingYouTubeMeta?.category || null;
            populateYouTubeCategories(existingCategory);
        }
    } else {
        youtubeFieldsContainer.slideUp(300);
        // Remove required attribute
        $('#youtube_title').attr('required', false);
        $('#youtube_category').attr('required', false);
        $('#privacy_status').attr('required', false);
        // Clear values
        $('#youtube_title').val('');
        $('#youtube_tags').val('');
        $('#youtube_category').val('');
        $('#privacy_status').val('public');
        $('#youtube_thumbnail').val('');
    }
}

// Initialize YouTube fields on page load (for edit mode)
$(document).ready(function() {
    // Check if we're in edit mode and YouTube is selected
    if (window.isEditMode && window.existingPlatforms && window.existingPlatforms.includes('youtube')) {
        // Populate categories immediately
        const existingCategory = window.existingYouTubeMeta?.category || null;
        console.log('Populating YouTube categories with existing category:', existingCategory);
        populateYouTubeCategories(existingCategory);
        
        // Pre-fill other YouTube fields if they exist
        if (window.existingYouTubeMeta) {
            $('#youtube_title').val(window.existingYouTubeMeta.title || '');
            $('#youtube_tags').val(window.existingYouTubeMeta.tags || '');
            $('#privacy_status').val(window.existingYouTubeMeta.privacy_status || 'public');
        }
    }
    
    // Attach change event to YouTube checkbox
    $('#platform-youtube').on('change', toggleYouTubeFields);
    
    // Trigger initial toggle to set correct state
    toggleYouTubeFields();
});
// Enhanced form validation before submit
$('#create-post-form').on('submit', function (e) {
    const youtubeSelected = $('#platform-youtube').is(':checked');

    if (youtubeSelected) {
        const title = $('#youtube_title').val().trim();
        const category = $('#youtube_category').val();
        const privacy = $('#privacy_status').val();

        if (!title) {
            e.preventDefault();
            toastr.error('Title is required when publishing to YouTube');
            $('#youtube_title').focus();
            return false;
        }

        if (!category) {
            e.preventDefault();
            toastr.error('Category is required when publishing to YouTube');
            $('#youtube_category').focus();
            return false;
        }

        // Debug: Log YouTube data being submitted
        console.log('=== YouTube Data Being Submitted ===', {
            title: title,
            category: category,
            privacy: privacy,
            tags: $('#youtube_tags').val(),
            has_thumbnail: $('#youtube_thumbnail')[0].files.length > 0
        });
    }
});

