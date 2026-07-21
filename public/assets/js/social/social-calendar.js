// Global variables to maintain state
let currentDate = new Date();
let currentView = 'month';
let calendarData = {};
let allPosts = {};
let activeFilters = {
    platforms: ['all'],
    statuses: []
};
let csrfToken = $('meta[name="csrf-token"]').attr('content');
let userTimezone = 'Asia/Kolkata'; // Will be set from backend response

// Configuration - will be set from Blade template
let calendarConfig = window.calendarConfig || {};

// Initialize calendar when DOM is loaded
$(document).ready(function () {
    // Check if CSRF token exists
    if (!$('meta[name="csrf-token"]').length) {
        console.warn('CSRF token not found. Please ensure meta tag is present in the layout.');
    }

    initializeCalendar();
});

function initializeCalendar() {
    bindEvents();
    loadCalendarData();
    setupSharedQuickViewActions(); // Setup shared actions for edit, delete, publish
}

function bindEvents() {
    // Navigation
    $('#prevMonth').on('click', function () {
        if (currentView === 'month') {
            navigateMonth(-1);
        } else if (currentView === 'week') {
            navigateWeek(-1);
        }
    });

    $('#nextMonth').on('click', function () {
        if (currentView === 'month') {
            navigateMonth(1);
        } else if (currentView === 'week') {
            navigateWeek(1);
        }
    });

    $('#todayBtn').on('click', function () {
        goToToday();
    });

    // View controls
    $('.view-btn').on('click', function () {
        changeView($(this).data('view'));
    });

    // Platform filters
    $('.filter-item[data-filter]').on('click', function () {
        togglePlatformFilter($(this));
    });

    // Status filters
    $('.filter-item[data-status]').on('click', function () {
        toggleStatusFilter($(this));
    });
}

function setupSharedQuickViewActions() {
    // Set up edit button
    $('#editPostBtn').off('click').on('click', function () {
        const postId = $(this).data('post-id');
        if (postId) {
            window.location.href = calendarConfig.routes.socialEdit.replace('{id}', postId);
        }
    });

    // Set up delete button
    $('#deletePostBtn').off('click').on('click', function () {
        const postId = $(this).data('post-id');
        if (postId) {
            deletePostFromCalendar(postId);
        }
    });

    // Set up publish now button
    $('#publishNowBtn').off('click').on('click', function () {
        const postId = $(this).data('post-id');
        if (postId) {
            publishNowFromCalendar(postId);
        }
    });
}

function loadCalendarData() {
    try {
        showLoading();

        let params = '';
        if (currentView === 'month') {
            const month = currentDate.getMonth() + 1;
            const year = currentDate.getFullYear();
            params = `?month=${month}&year=${year}`;
        } else if (currentView === 'week') {
            const startOfWeek = getStartOfWeek(currentDate);
            const endOfWeek = getEndOfWeek(currentDate);
            params = `?start_date=${formatDateForAPI(startOfWeek)}&end_date=${formatDateForAPI(endOfWeek)}&view=week`;
        }

        $.ajax({
            url: `${calendarConfig.routes.calendarData}${params}`,
            type: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (response) {
                if (response.error) {
                    throw new Error(response.message || 'Failed to load calendar data');
                }

                // FIXED: Store timezone from response and use it consistently
                userTimezone = response.timezone || 'Asia/Kolkata';

                calendarData = response.data || {};
                allPosts = { ...calendarData };

                // Debug log to verify data
                console.log('Calendar data loaded:', {
                    timezone: userTimezone,
                    dataKeys: Object.keys(calendarData),
                    sampleData: Object.keys(calendarData).slice(0, 3).map(key => ({
                        date: key,
                        posts: calendarData[key].length,
                        firstPost: calendarData[key][0]
                    }))
                });

                renderCalendar();
                updateStats(response.stats);
                updateFilterCounts();
            },
            error: function (xhr, status, error) {
                console.error('Error loading calendar data:', error);
                showError('Failed to load calendar data: ' + error);
                hideLoading();
            }
        });

    } catch (error) {
        console.error('Error loading calendar data:', error);
        showError('Failed to load calendar data: ' + error.message);
        hideLoading();
    }
}

// FIXED: Format date consistently for API calls
function formatDateForAPI(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function showLoading() {
    const content = $('#calendarContent');
    content.html(`
        <div class="loading">
            <div class="spinner"></div>
            <div class="mt-2">Loading calendar...</div>
        </div>
    `);
}

function hideLoading() {
    // Loading will be replaced by calendar content
}

function renderCalendar() {
    try {
        if (currentView === 'month') {
            renderMonthView();
        } else if (currentView === 'week') {
            renderWeekView();
        }

        // Bind post click events
        bindPostEvents();

    } catch (error) {
        console.error('Error rendering calendar:', error);
        showError('Failed to render calendar');
    }
}

function renderMonthView() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Update header
    $('#currentMonth').text(
        new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate)
    );

    // Generate calendar grid
    const calendarHTML = generateCalendarGrid(year, month);
    $('#calendarContent').html(calendarHTML);
}

function renderWeekView() {
    // Update header to show week range
    const startOfWeek = getStartOfWeek(currentDate);
    const endOfWeek = getEndOfWeek(currentDate);
    const monthName = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(startOfWeek);
    const year = startOfWeek.getFullYear();

    let headerText = '';
    if (startOfWeek.getMonth() === endOfWeek.getMonth()) {
        headerText = `${monthName} ${startOfWeek.getDate()} - ${endOfWeek.getDate()}, ${year}`;
    } else {
        const endMonthName = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(endOfWeek);
        headerText = `${monthName} ${startOfWeek.getDate()} - ${endMonthName} ${endOfWeek.getDate()}, ${year}`;
    }
    $('#currentMonth').text(headerText);

    // Generate week view
    const weekHTML = generateWeekGrid();
    $('#calendarContent').html(weekHTML);
}

function generateWeekGrid() {
    const startOfWeek = getStartOfWeek(currentDate);
    const today = new Date();

    let html = '<div class="week-view-container">';

    // Week header with days
    html += '<div class="week-header">';
    html += '<div class="week-time-column">Time</div>';

    const dayNames = [
        calendarConfig.labels?.mon || 'Mon',
        calendarConfig.labels?.tue || 'Tue',
        calendarConfig.labels?.wed || 'Wed',
        calendarConfig.labels?.thu || 'Thu',
        calendarConfig.labels?.fri || 'Fri',
        calendarConfig.labels?.sat || 'Sat',
        calendarConfig.labels?.sun || 'Sun'
    ];

    for (let i = 0; i < 7; i++) {
        const currentDay = new Date(startOfWeek);
        currentDay.setDate(startOfWeek.getDate() + i);
        const isToday = isSameDate(currentDay, today);

        html += `<div class="week-day-header ${isToday ? 'today' : ''}">`;
        html += `<div>${dayNames[i]}</div>`;
        html += `<div class="week-day-number">${currentDay.getDate()}</div>`;
        html += '</div>';
    }
    html += '</div>';

    // Week grid with time slots
    html += '<div class="week-grid">';

    // Generate 24 hour slots
    for (let hour = 0; hour < 24; hour++) {
        // Time column
        const timeLabel = `${hour.toString().padStart(2, '0')}:00`;
        html += `<div class="week-time-slot">${timeLabel}</div>`;

        // Day columns
        for (let day = 0; day < 7; day++) {
            const currentDay = new Date(startOfWeek);
            currentDay.setDate(startOfWeek.getDate() + day);
            const dateStr = formatDateForAPI(currentDay); // Use consistent date formatting
            const isToday = isSameDate(currentDay, today);

            // Get posts for this hour and day
            const hourPosts = getPostsForHour(dateStr, hour);
            const filteredPosts = filterPosts(hourPosts);

            html += `<div class="week-day-slot ${isToday ? 'today' : ''}" data-date="${dateStr}" data-hour="${hour}">`;

            // Render posts (show up to 3, then "more")
            const visiblePosts = filteredPosts.slice(0, 3);
            const hiddenPostsCount = filteredPosts.length - visiblePosts.length;

            visiblePosts.forEach(post => {
                html += renderWeekPostItem(post);
            });

            if (hiddenPostsCount > 0) {
                html += `<div class="week-more-posts" data-date="${dateStr}" data-hour="${hour}">
                    +${hiddenPostsCount} more
                </div>`;
            }

            html += `<button class="week-add-post-btn" data-date="${dateStr}" data-hour="${hour}" title="${calendarConfig.labels?.addPost || 'Add Post'}">
                        <i class="bx bx-plus"></i>
                    </button>`;
            html += '</div>';
        }
    }

    html += '</div>';
    html += '</div>';

    return html;
}

// FIXED: Better date comparison function
function isSameDate(date1, date2) {
    return date1.getFullYear() === date2.getFullYear() &&
        date1.getMonth() === date2.getMonth() &&
        date1.getDate() === date2.getDate();
}

// FIXED: Improve post time filtering for week view
function getPostsForHour(dateStr, hour) {
    const dayPosts = calendarData[dateStr] || [];
    return dayPosts.filter(post => {
        // Use display_date if available (from backend), otherwise fall back to scheduled_at/created_at
        let postDateTime = post.display_date || post.scheduled_at || post.created_at;
        if (!postDateTime) return false;

        // Parse the datetime and get hour - the backend already converted to user timezone
        const postDate = new Date(postDateTime);
        const postHour = postDate.getHours();
        return postHour === hour;
    });
}

function renderWeekPostItem(post) {
    const platforms = (post.platform_icons || []).map(icon =>
        `<i class="bx ${icon} platform-icon" style="color: ${getPlatformColor(icon)};"></i>`
    ).join('');

    const mediaIcon = (post.media_count && post.media_count > 0) ?
        `<div class="week-media-indicator">
            <i class="bx bx-image"></i>
            <span>${post.media_count}</span>
        </div>` : '';

    const statusClass = post.status || 'pending';

    return `
        <div class="week-post-item ${statusClass}" data-post-id="${post.id}">
            <div class="week-post-header">
                <div class="week-post-time">${post.time || '00:00'}</div>
                <div class="week-post-platforms">${platforms}</div>
            </div>
            <div class="week-post-caption">${truncateText(post.caption, 30)}</div>
            <div class="week-post-meta">
                <span class="week-status-badge status-${statusClass}">${formatStatus(post.status)}</span>
                ${mediaIcon}
            </div>
        </div>
    `;
}

function generateCalendarGrid(year, month) {
    const firstDay = new Date(year, month, 1);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    let html = '<div class="calendar-grid">';

    // Day headers
    const dayNames = [
        calendarConfig.labels?.sun || 'Sun',
        calendarConfig.labels?.mon || 'Mon',
        calendarConfig.labels?.tue || 'Tue',
        calendarConfig.labels?.wed || 'Wed',
        calendarConfig.labels?.thu || 'Thu',
        calendarConfig.labels?.fri || 'Fri',
        calendarConfig.labels?.sat || 'Sat'
    ];

    dayNames.forEach(day => {
        html += `<div class="calendar-day-header">${day}</div>`;
    });

    // Calendar days
    const today = new Date();
    const currentDateLoop = new Date(startDate);

    for (let week = 0; week < 6; week++) {
        for (let day = 0; day < 7; day++) {
            const dateStr = formatDateForAPI(currentDateLoop); // Use consistent date formatting
            const isCurrentMonth = currentDateLoop.getMonth() === month;
            const isToday = isSameDate(currentDateLoop, today);
            const dayPosts = calendarData[dateStr] || [];
            const filteredPosts = filterPosts(dayPosts);

            let dayClass = 'calendar-day';
            if (!isCurrentMonth) dayClass += ' other-month';
            if (isToday) dayClass += ' today';

            html += `<div class="${dayClass}" data-date="${dateStr}">`;
            html += `<div class="day-number">${currentDateLoop.getDate()}</div>`;

            // Add posts (show up to 3, then show count)
            const visiblePosts = filteredPosts.slice(0, 3);
            const hiddenPostsCount = filteredPosts.length - visiblePosts.length;

            visiblePosts.forEach(post => {
                html += renderPostItem(post);
            });

            if (hiddenPostsCount > 0) {
                html += `<div class="more-posts" data-date="${dateStr}" title="Click to view all posts">
                    +${hiddenPostsCount} more
                </div>`;
            }

            html += `<button class="add-post-btn" data-date="${dateStr}" title="${calendarConfig.labels?.addPost || 'Add Post'}">
                        <i class="bx bx-plus"></i>
                    </button>`;
            html += '</div>';

            currentDateLoop.setDate(currentDateLoop.getDate() + 1);
        }
    }

    html += '</div>';
    return html;
}

function renderPostItem(post) {
    const platforms = (post.platform_icons || []).map(icon =>
        `<i class="bx ${icon} platform-icon" style="color: ${getPlatformColor(icon)};"></i>`
    ).join('');

    const mediaIcon = (post.media_count && post.media_count > 0) ?
        `<div class="media-indicator">
            <i class="bx bx-image"></i>
            <span>${post.media_count}</span>
        </div>` : '';

    const statusClass = post.status || 'pending';

    return `
        <div class="post-item ${statusClass}" data-post-id="${post.id}">
            <div class="post-header">
                <div class="post-time">${post.time || '00:00'}</div>
                <div class="post-platforms">${platforms}</div>
            </div>
            <div class="post-caption">${truncateText(post.caption, 60)}</div>
            <div class="post-meta">
                <span class="status-badge status-${statusClass}">${formatStatus(post.status)}</span>
                ${mediaIcon}
            </div>
        </div>
    `;
}

// Rest of the functions remain the same...
function truncateText(text, maxLength) {
    if (!text) return 'No caption';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function formatStatus(status) {
    const statusMap = {
        'published': 'Published',
        'scheduled': 'Scheduled',
        'failed': 'Failed',
        'partially_published': 'Partial',
        'pending': 'Pending'
    };
    return statusMap[status] || status;
}

function getPlatformColor(input) {
    const colors = {
        // Icon classes
        'bxl-facebook-circle': '#1877f2',
        'bxl-facebook': '#1877f2',
        'bxl-instagram': '#E4405F',
        'bxl-linkedin': '#0077b5',
        'bxl-pinterest': '#bd081c',
        'bxl-twitter': '#1DA1F2',
        // Platform names
        facebook: '#1877f2',
        instagram: '#E4405F',
        twitter: '#1DA1F2',
        linkedin: '#0077b5',
        pinterest: '#bd081c'
    };
    return colors[input?.toLowerCase()] || '#6c757d';
}

function filterPosts(posts) {
    return posts.filter(post => {
        // Platform filter
        const platformMatch = activeFilters.platforms.includes('all') ||
            (post.platforms && post.platforms.some(platform => activeFilters.platforms.includes(platform.toLowerCase())));

        // Status filter
        const statusMatch = activeFilters.statuses.length === 0 ||
            activeFilters.statuses.includes(post.status);

        return platformMatch && statusMatch;
    });
}

function bindPostEvents() {
    // Post click events - Use the shared showPostQuickView function
    $(document).off('click', '.post-item, .week-post-item').on('click', '.post-item, .week-post-item', function (e) {
        e.stopPropagation();
        const postId = $(this).data('post-id');

        // Use the shared quick view function and update button states
        if (typeof showPostQuickView === 'function') {
            showPostQuickView(postId);

            // Set post ID on action buttons for the calendar context
            $('#editPostBtn').data('post-id', postId);
            $('#deletePostBtn').data('post-id', postId);
            $('#publishNowBtn').data('post-id', postId);

            // Show the modal
            $('#quickViewModal').modal('show');
        } else {
            // Fallback if showPostQuickView doesn't exist
            console.log('Post clicked:', postId);
            alert(`Post ${postId} clicked - please implement showPostQuickView function`);
        }
    });

    // Add post button events (both month and week view)
    $(document).off('click', '.add-post-btn, .week-add-post-btn').on('click', '.add-post-btn, .week-add-post-btn', function (e) {
        e.stopPropagation();
        const date = $(this).data('date');
        const hour = $(this).data('hour') || null;
        createNewPost(date, hour);
    });

    // More posts click events (both month and week view)
    $(document).off('click', '.more-posts, .week-more-posts').on('click', '.more-posts, .week-more-posts', function (e) {
        e.stopPropagation();
        const date = $(this).data('date');
        const hour = $(this).data('hour') || null;
        showTimeslotPosts(date, hour);
    });
}

// Rest of the existing functions with minimal changes for timezone consistency...

// Week view navigation helpers - FIXED to use consistent date formatting
function getStartOfWeek(date) {
    const result = new Date(date);
    const day = result.getDay();
    const diff = result.getDate() - day + (day === 0 ? -6 : 1); // Monday as start of week
    result.setDate(diff);
    result.setHours(0, 0, 0, 0);
    return result;
}

function getEndOfWeek(date) {
    const result = getStartOfWeek(date);
    result.setDate(result.getDate() + 6);
    result.setHours(23, 59, 59, 999);
    return result;
}

function navigateMonth(direction) {
    currentDate.setMonth(currentDate.getMonth() + direction);
    loadCalendarData();
}

function navigateWeek(direction) {
    currentDate.setDate(currentDate.getDate() + (direction * 7));
    loadCalendarData();
}

function goToToday() {
    currentDate = new Date();
    loadCalendarData();
}

function changeView(view) {
    currentView = view;
    $('.view-btn').removeClass('active');
    $(`[data-view="${view}"]`).addClass('active');

    // Reload data for proper week/month range
    loadCalendarData();
}

// FIXED: Create new post with consistent date handling
function createNewPost(date = null, hour = null) {
    let url = calendarConfig.routes?.socialCreate || '#';
    const params = [];

    if (date) {
        let scheduledDateTime = date;
        if (hour !== null) {
            // Format time consistently
            const hourStr = hour.toString().padStart(2, '0');
            scheduledDateTime = `${date}T${hourStr}:00:00`;
        }
        params.push(`scheduled_date=${encodeURIComponent(scheduledDateTime)}`);
    }

    if (params.length > 0) {
        url += `?${params.join('&')}`;
    }

    window.location.href = url;
}

function showTimeslotPosts(date, hour = null) {
    let posts = calendarData[date] || [];

    // If hour is specified (week view), filter by hour
    if (hour !== null) {
        posts = getPostsForHour(date, hour);
    }

    const filteredPosts = filterPosts(posts);

    if (filteredPosts.length === 0) {
        showInfo('No posts found for this time slot.');
        return;
    }

    const dateObj = new Date(date + 'T00:00:00'); // Parse date consistently
    const timeLabel = hour !== null ? ` at ${hour.toString().padStart(2, '0')}:00` : '';
    const title = `Posts for ${dateObj.toLocaleDateString()}${timeLabel}`;

    const modalContent = `
        <div class="day-posts-container">
            <h6 class="mb-3 text-muted">
                <i class="bx bx-calendar"></i> ${title}
            </h6>

            <div class="day-posts-list d-flex flex-column gap-2">
                ${filteredPosts.map(post => `
                    <div class="day-post-item p-3 rounded border bg-light"
                         data-post-id="${post.id}" style="cursor:pointer;">

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted">
                                <i class="bx bx-time-five"></i> ${post.time || '00:00'}
                            </span>
                            <span class="badge ${getStatusBadgeClass(post.status)}">
                                ${formatStatus(post.status)}
                            </span>
                        </div>

                        <!-- Caption -->
                        <div class="mb-2">
                            ${post.caption
            ? `<p class="mb-0 text-truncate-2">${truncateText(post.caption, 150)}</p>`
            : `<p class="text-muted mb-0 fst-italic">No caption</p>`}
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-between align-items-center small text-muted">
                            <div>
                                ${(post.platforms || []).map(p => `
                                    <i class="bx ${getPlatformIcon(p)} me-1"
                                       style="color:${getPlatformColor(p)}; font-size:1.1rem;"></i>
                                `).join('')}
                            </div>
                            ${post.media_count > 0
            ? `<span><i class="bx bx-image"></i> ${post.media_count}</span>`
            : ''}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;

    $('#quickViewContent').html(modalContent);
    $('#quickViewModalLabel').text(title);

    // Hide action buttons for day view
    $('#editPostBtn').hide();
    $('#publishNowBtn').hide();
    $('#deletePostBtn').hide();

    // Post click -> detailed quick view
    $('.day-post-item').off('click').on('click', function () {
        const postId = $(this).data('post-id');
        $('#quickViewModal').modal('hide');

        setTimeout(() => {
            if (typeof showPostQuickView === 'function') {
                showPostQuickView(postId);

                $('#editPostBtn').data('post-id', postId).show();
                $('#deletePostBtn').data('post-id', postId).show();
                $('#publishNowBtn').data('post-id', postId);
                showPublishButtonIfNeeded(postId);

                $('#quickViewModal').modal('show');
            }
        }, 300);
    });

    $('#quickViewModal').modal('show');
}

/** Helpers for styling */
function getStatusBadgeClass(status) {
    const statusClasses = {
        'published': 'bg-success',
        'scheduled': 'bg-warning',
        'failed': 'bg-danger',
        'pending': 'bg-secondary',
        'partially_published': 'bg-primary'
    };
    return statusClasses[status] || 'bg-secondary';
}

function getPlatformIcon(platform) {
    const icons = {
        facebook: 'bxl-facebook-circle',
        instagram: 'bxl-instagram',
        twitter: 'bxl-twitter',
        linkedin: 'bxl-linkedin',
        pinterest: 'bxl-pinterest'
    };
    return icons[platform?.toLowerCase()] || 'bx-globe';
}

function showPublishButtonIfNeeded(postId) {
    // Find post in calendar data to check status
    let post = null;
    for (const dateKey in allPosts) {
        const foundPost = allPosts[dateKey].find(p => p.id == postId);
        if (foundPost) {
            post = foundPost;
            break;
        }
    }

    if (post && ['pending', 'scheduled', 'failed'].includes(post.status)) {
        $('#publishNowBtn').show();
    } else {
        $('#publishNowBtn').hide();
    }
}

function updateStats(stats) {
    if (!stats) return;

    $('#publishedCount').text(stats.published || 0);
    $('#scheduledCount').text(stats.scheduled || 0);
    $('#failedCount').text(stats.failed || 0);
    $('#partialCount').text(stats.partially_published || 0);
}

function updateFilterCounts() {
    const counts = {
        all: 0,
        facebook: 0,
        instagram: 0,
        twitter: 0,
        linkedin: 0,
        pinterest: 0
    };

    Object.values(allPosts).flat().forEach(post => {
        counts.all++;
        (post.platforms || []).forEach(platform => {
            const platformKey = platform.toLowerCase();
            if (counts[platformKey] !== undefined) {
                counts[platformKey]++;
            }
        });
    });

    Object.keys(counts).forEach(key => {
        const element = $(`#${key}Count`);
        if (element.length) element.text(counts[key]);
    });
}

function togglePlatformFilter(element) {
    const filter = element.data('filter');

    if (filter === 'all') {
        activeFilters.platforms = ['all'];
        $('.filter-item[data-filter]').removeClass('active');
        element.addClass('active');
    } else {
        // Remove 'all' filter
        activeFilters.platforms = activeFilters.platforms.filter(f => f !== 'all');
        $('.filter-item[data-filter="all"]').removeClass('active');

        // Toggle specific filter
        if (activeFilters.platforms.includes(filter)) {
            activeFilters.platforms = activeFilters.platforms.filter(f => f !== filter);
            element.removeClass('active');
        } else {
            activeFilters.platforms.push(filter);
            element.addClass('active');
        }

        // If no platforms selected, show all
        if (activeFilters.platforms.length === 0) {
            activeFilters.platforms = ['all'];
            $('.filter-item[data-filter="all"]').addClass('active');
        }
    }

    renderCalendar();
}

function toggleStatusFilter(element) {
    const status = element.data('status');

    if (activeFilters.statuses.includes(status)) {
        activeFilters.statuses = activeFilters.statuses.filter(s => s !== status);
        element.removeClass('active');
    } else {
        activeFilters.statuses.push(status);
        element.addClass('active');
    }

    renderCalendar();
}

function showError(message) {
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    } else {
        console.error(message);
        alert(message);
    }
}

function showSuccess(message) {
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else {
        console.log(message);
        alert(message);
    }
}

function showInfo(message) {
    if (typeof toastr !== 'undefined') {
        toastr.info(message);
    } else {
        console.info(message);
        alert(message);
    }
}

// Handle modal cleanup
$('#quickViewModal').on('hidden.bs.modal', function () {
    // Reset modal content
    $('#quickViewContent').html('');
    $('#quickViewModalLabel').text('Post Publishing Details');

    // Show action buttons
    $('#editPostBtn').show();
    $('#deletePostBtn').show();

    // Clear post IDs from buttons
    $('#editPostBtn').removeData('post-id');
    $('#deletePostBtn').removeData('post-id');
    $('#publishNowBtn').removeData('post-id');
});

// Function to set configuration from main page (kept for backward compatibility)
function setCalendarConfig(config) {
    calendarConfig = { ...calendarConfig, ...config };
}
