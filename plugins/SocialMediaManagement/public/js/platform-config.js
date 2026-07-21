/**
 * Centralized Platform Configuration
 * Add new platforms here - all frontend features will automatically adapt
 */

const PLATFORM_CONFIG = {
    facebook: {
        name: 'Facebook',
        icon: 'bxl-facebook-circle',
        color: '#4267B2',
        bgColor: '#E7F3FF',
        requirements: {
            caption: { max: 63206, recommended: 500 },
            media: { required: false, types: ['image', 'video'], maxCount: 10 }
        }
    },
    instagram: {
        name: 'Instagram',
        icon: 'bxl-instagram',
        color: '#E4405F',
        bgColor: '#FFE8ED',
        requirements: {
            caption: { max: 2200, recommended: 150 },
            media: { required: true, types: ['image', 'video'], maxCount: 10 }
        }
    },
    linkedin: {
        name: 'LinkedIn',
        icon: 'bxl-linkedin',
        color: '#0077B5',
        bgColor: '#E0F2FE',
        requirements: {
            caption: { max: 3000, recommended: 300 },
            media: { required: false, types: ['image', 'video'], maxCount: 9 }
        }
    },
    pinterest: {
        name: 'Pinterest',
        icon: 'bxl-pinterest',
        color: '#E60023',
        bgColor: '#FFE5E9',
        requirements: {
            caption: { max: 500, recommended: 100 },
            media: { required: true, types: ['image'], maxCount: 1 }
        }
    },
    youtube: {
        name: 'YouTube',
        icon: 'bxl-youtube',
        color: '#FF0000',
        bgColor: '#FFE5E5',
        requirements: {
            caption: { max: 5000, recommended: 200 },
            media: { required: true, types: ['video'], maxCount: 1 }
        }
    }
    // Add new platforms here:
    // tiktok: {
    //     name: 'TikTok',
    //     icon: 'bxl-tiktok',
    //     color: '#000000',
    //     bgColor: '#F0F0F0',
    //     requirements: {
    //         caption: { max: 2200, recommended: 150 },
    //         media: { required: true, types: ['video'], maxCount: 1 }
    //     }
    // }
};

/**
 * Platform Helper Functions
 */
const PlatformHelper = {
    /**
     * Get all available platforms
     */
    getAll() {
        return PLATFORM_CONFIG;
    },

    /**
     * Get platform configuration
     */
    get(platformKey) {
        return PLATFORM_CONFIG[platformKey?.toLowerCase()] || null;
    },

    /**
     * Get platform name
     */
    getName(platformKey) {
        return this.get(platformKey)?.name || platformKey;
    },

    /**
     * Get platform icon class
     */
    getIcon(platformKey) {
        const platform = this.get(platformKey);
        return platform?.icon || 'bx-globe';
    },

    /**
     * Get platform color
     */
    getColor(platformKey) {
        const platform = this.get(platformKey);
        return platform?.color || '#6c757d';
    },

    /**
     * Get platform background color
     */
    getBgColor(platformKey) {
        const platform = this.get(platformKey);
        return platform?.bgColor || '#f8f9fa';
    },

    /**
     * Get all platform keys
     */
    getKeys() {
        return Object.keys(PLATFORM_CONFIG);
    },

    /**
     * Validate post requirements for platform
     */
    validatePost(platformKey, caption, mediaFiles) {
        const platform = this.get(platformKey);
        if (!platform) return { valid: true };

        const issues = [];
        const req = platform.requirements;

        // Validate caption length
        if (caption && req.caption) {
            const captionLength = caption.length;
            if (captionLength > req.caption.max) {
                issues.push(`${platform.name}: Caption exceeds ${req.caption.max} characters`);
            }
        }

        // Validate media requirements
        if (req.media.required && (!mediaFiles || mediaFiles.length === 0)) {
            issues.push(`${platform.name}: At least one ${req.media.types.join(' or ')} is required`);
        }

        // Validate media types
        if (mediaFiles && mediaFiles.length > 0 && req.media.types.length > 0) {
            const hasValidMedia = mediaFiles.some(media => {
                const mediaType = this.getMediaType(media);
                return req.media.types.includes(mediaType);
            });

            if (!hasValidMedia) {
                issues.push(`${platform.name}: Only ${req.media.types.join(', ')} files are supported`);
            }
        }

        // Validate media count
        if (mediaFiles && req.media.maxCount && mediaFiles.length > req.media.maxCount) {
            issues.push(`${platform.name}: Maximum ${req.media.maxCount} media file(s) allowed`);
        }

        return {
            valid: issues.length === 0,
            issues: issues
        };
    },

    /**
     * Get media type from file
     */
    getMediaType(media) {
        if (!media) return null;

        const type = media.type || media.mime_type || '';
        if (type.startsWith('image/')) return 'image';
        if (type.startsWith('video/')) return 'video';
        return null;
    },

    /**
     * Generate platform selection HTML
     */
    generatePlatformCards(selectedPlatforms = []) {
        return Object.entries(PLATFORM_CONFIG).map(([key, config]) => {
            const isSelected = selectedPlatforms.includes(key);
            return `
                <div class="col-md-4 col-lg-3">
                    <div class="platform-card ${isSelected ? 'selected' : ''}"
                         data-platform="${key}"
                         style="border-color: ${config.color};">
                        <input type="checkbox"
                               name="platforms[]"
                               value="${key}"
                               ${isSelected ? 'checked' : ''}
                               hidden>
                        <div class="platform-icon" style="color: ${config.color}; background-color: ${config.bgColor};">
                            <i class="bx ${config.icon}"></i>
                        </div>
                        <h6 class="platform-name">${config.name}</h6>
                        <div class="platform-requirements">
                            <small class="text-muted">
                                Max: ${config.requirements.caption.max} chars
                                ${config.requirements.media.required ? '<br>Media required' : ''}
                            </small>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Generate platform filter HTML
     */
    generatePlatformFilters() {
        const allHtml = `
            <div class="filter-item active" data-filter="all">
                <i class="bx bx-globe"></i>
                <span>All Platforms</span>
                <span class="badge bg-secondary" id="allCount">0</span>
            </div>
        `;

        const platformsHtml = Object.entries(PLATFORM_CONFIG).map(([key, config]) => `
            <div class="filter-item" data-filter="${key}">
                <i class="bx ${config.icon}" style="color: ${config.color};"></i>
                <span>${config.name}</span>
                <span class="badge bg-secondary" id="${key}Count">0</span>
            </div>
        `).join('');

        return allHtml + platformsHtml;
    },

    /**
     * Generate platform badges HTML
     */
    generatePlatformBadges(platforms = []) {
        return platforms.map(key => {
            const config = this.get(key);
            if (!config) return '';
            return `
                <span class="platform-badge" style="background-color: ${config.bgColor}; color: ${config.color};">
                    <i class="bx ${config.icon}"></i>
                    ${config.name}
                </span>
            `;
        }).join('');
    }
};

/**
 * Status Configuration
 */
const STATUS_CONFIG = {
    published: {
        label: 'Published',
        class: 'bg-success',
        icon: 'bx-check-circle',
        color: '#28a745'
    },
    scheduled: {
        label: 'Scheduled',
        class: 'bg-warning',
        icon: 'bx-time-five',
        color: '#ffc107'
    },
    failed: {
        label: 'Failed',
        class: 'bg-danger',
        icon: 'bx-x-circle',
        color: '#dc3545'
    },
    pending: {
        label: 'Pending',
        class: 'bg-secondary',
        icon: 'bx-time-five',
        color: '#6c757d'
    },
    partially_published: {
        label: 'Partially Published',
        class: 'bg-primary',
        icon: 'bx-error-circle',
        color: '#007bff'
    }
};

const StatusHelper = {
    get(statusKey) {
        return STATUS_CONFIG[statusKey] || STATUS_CONFIG.pending;
    },

    getLabel(statusKey) {
        return this.get(statusKey).label;
    },

    getClass(statusKey) {
        return this.get(statusKey).class;
    },

    getIcon(statusKey) {
        return this.get(statusKey).icon;
    },

    getColor(statusKey) {
        return this.get(statusKey).color;
    }
};

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PlatformHelper, StatusHelper, PLATFORM_CONFIG, STATUS_CONFIG };
}
