class GoogleCalendarManager {
    constructor(config = {}) {
        this.config = {
            calendarContainerId: 'googleCalendarDiv',
            statusFiltersId: 'calendar-status-filters-container',
            baseUrl: config.baseUrl || window.baseUrl || '',
            dateFormat: config.dateFormat || window.js_date_format || 'DD/MM/YYYY',
            timeFormat: config.timeFormat || 'HH:mm',
            csrfToken: config.csrfToken || $('input[name="_token"]').attr("value"),
            googleCalendarId: config.googleCalendarId || window.google_calendar_id || '',
            googleCalendarApiKey: config.googleCalendarApiKey || window.google_calendar_api_key || '',
            ...config
        };

        this.state = {
            calendar: null,
            allEvents: [], // Will be reset on each fetch
            eventStatuses: [
                { id: 'approved', name: 'Leave Approved', color: 'success' },
                { id: 'pending', name: 'Leave Pending', color: 'warning' },
                { id: 'rejected', name: 'Leave Rejected', color: 'danger' }
            ],
            activeFilters: {
                status: ['approved', 'pending', 'rejected'] // Include all statuses initially
            },
            isInitialized: false
        };

        this.cache = new Map();
        this.debounceTimers = new Map();
    }

    async init() {
        if (this.state.isInitialized) {
            console.warn('GoogleCalendarManager already initialized');
            return this;
        }

        try {
            await this.waitForContainer(`#${this.config.statusFiltersId}`, 1000);
            this.initializeFilters();
            await this.initializeCalendar();
            this.state.isInitialized = true;
            return this;
        } catch (error) {
            console.error('Failed to initialize GoogleCalendarManager:', error);
            throw error;
        }
    }

    async waitForContainer(selector, timeout = 5000) {
        const startTime = Date.now();
        while (Date.now() - startTime < timeout) {
            if (document.querySelector(selector)) {
                return;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        throw new Error(`Container ${selector} not found after ${timeout}ms`);
    }

    initializeFilters() {
        this.renderFilters();
    }

    async apiRequest(endpoint, options = {}) {
        const cacheKey = `${endpoint}-${JSON.stringify(options)}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await $.ajax({
                url: this.config.baseUrl + endpoint,
                type: options.method || "GET",
                headers: {
                    "X-CSRF-TOKEN": this.config.csrfToken,
                    ...options.headers
                },
                dataType: "json",
                ...options
            });

            this.cache.set(cacheKey, response);
            setTimeout(() => this.cache.delete(cacheKey), 5 * 60 * 1000);
            return response;
        } catch (error) {
            console.error(`API request failed for ${endpoint}:`, error);
            throw error;
        }
    }

    renderFilters() {
        this.renderStatusFilters();
    }

    renderStatusFilters() {
        const container = $(`#${this.config.statusFiltersId}`);
        if (!container.length) {
            console.warn(`Status filters container #${this.config.statusFiltersId} not found`);
            return;
        }

        const filtersHtml = this.state.eventStatuses.map(status =>
            this.createFilterHtml('status', status)
        ).join('');

        container.html(filtersHtml);
        this.bindFilterEvents('status');
    }

    createFilterHtml(filterType, item) {
        const isChecked = this.state.activeFilters[filterType].includes(item.id);
        return `
            <div class="form-check form-check-${item.color || 'secondary'} mb-1">
                <input class="form-check-input ${filterType}-filter" type="checkbox" ${isChecked ? 'checked' : ''}
                       data-${filterType}="${item.id}" id="filter${filterType.charAt(0).toUpperCase() + filterType.slice(1)}${item.id}">
                <label class="form-check-label" for="filter${filterType.charAt(0).toUpperCase() + filterType.slice(1)}${item.id}">
                    <div class="d-flex align-items-center">
                        <span class="">${item.name}</span>
                    </div>
                    <span class="filter-counter" id="count-${filterType}-${item.id}">0</span>
                </label>
            </div>
        `;
    }

    bindFilterEvents(filterType) {
        $(`.${filterType}-filter`).off('change.gcm').on('change.gcm', (e) => {
            const itemId = $(e.target).data(filterType);
            const isChecked = $(e.target).is(':checked');

            this.updateActiveFilters(filterType, itemId, isChecked);
            this.debounce('applyFilters', () => this.applyFilters(), 150);
        });
    }

    updateActiveFilters(filterType, itemId, isChecked) {
        if (isChecked) {
            if (!this.state.activeFilters[filterType].includes(itemId)) {
                this.state.activeFilters[filterType].push(itemId);
            }
        } else {
            this.state.activeFilters[filterType] = this.state.activeFilters[filterType].filter(id => id !== itemId);
        }
    }

    applyFilters() {
        if (!this.state.calendar) return;

        this.state.calendar.refetchEvents();
        this.updateFilterCounters();
        this.updateStatistics();
    }

    updateFilterCounters() {
        const counts = this.calculateCounts();
        this.state.eventStatuses.forEach(status => {
            $(`#count-status-${status.id}`).text(counts.status[status.id] || 0);
        });
    }

    calculateCounts() {
        return this.state.allEvents.reduce((acc, event) => {
            const status = event.extendedProps?.status || 'unknown';
            acc.status[status] = (acc.status[status] || 0) + 1;
            return acc;
        }, { status: {} });
    }

    updateStatistics() {
        const totalEvents = this.state.allEvents.length;
        const visibleEvents = this.getVisibleEventsCount();
        const filteredEvents = this.state.allEvents.filter(e =>
            this.state.activeFilters.status.length === 0 ||
            this.state.activeFilters.status.includes(e.extendedProps?.status || 'unknown')
        ).length;

        $('#total-events').text(totalEvents);
        $('#visible-events').text(visibleEvents);
        $('#filtered-events').text(filteredEvents);
    }

    getVisibleEventsCount() {
        return this.state.allEvents.filter(event => {
            const statusMatch = this.state.activeFilters.status.length === 0 ||
                this.state.activeFilters.status.includes(event.extendedProps?.status || 'unknown');
            return statusMatch;
        }).length;
    }

    initializeCalendar() {
        const calendarEl = document.getElementById(this.config.calendarContainerId);
        if (!calendarEl) {
            throw new Error(`Calendar container #${this.config.calendarContainerId} not found`);
        }

        this.state.calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: ['interaction', 'dayGrid', 'list', 'googleCalendar'],
            headerToolbar: {
                // left: 'prev,next today',
                // center: 'title',
                right: 'dayGridMonth,listMonth,prev,next today'
            },
            editable: false,
            selectable: false,
            height: 'auto',
            eventLimit: 4,
            themeSystem: 'bootstrap5',
            timeZone: 'local', // Ensure local timezone handling
            eventSources: [
                {
                    googleCalendarId: this.config.googleCalendarId,
                    googleCalendarApiKey: this.config.googleCalendarApiKey,
                    backgroundColor: '#696cff',
                    borderColor: '#696cff',
                    className: 'google-event'
                },
                (fetchInfo, successCallback, failureCallback) => {
                    this.fetchLeaveRequests(fetchInfo, successCallback, failureCallback);
                }
            ],
            eventMouseEnter: (info) => this.handleEventMouseEnter(info),
            eventClick: (info) => this.handleEventClick(info),
            datesSet: () => {
                this.state.allEvents = []; // Reset events on view change
                this.state.calendar.removeAllEvents();
                this.state.calendar.refetchEvents();
            },
            eventDidMount: (info) => {
                const event = info.event;
                if (event.extendedProps?.status) {
                    info.el.classList.add(`leave-${event.extendedProps.status}`);
                }
            }
        });

        this.state.calendar.render();
    }

    async fetchLeaveRequests(fetchInfo, successCallback, failureCallback) {
        try {
            const response = await $.ajax({
                url: this.config.baseUrl + "/leave-requests/get-calendar-data",
                type: "GET",
                data: {
                    date_from: moment(fetchInfo.start).format(this.config.dateFormat),
                    date_to: moment(fetchInfo.end).format(this.config.dateFormat)
                }
            });

            // Reset allEvents and deduplicate by id
            this.state.allEvents = this.deduplicateEvents(response.map(event => {
                const status = event.extendedProps?.status?.toLowerCase() || 'unknown';
                return {
                    id: event.id,
                    title: event.title,
                    start: event.start,
                    end: event.end,
                    url: null,
                    backgroundColor: event.backgroundColor || this.getEventColor(status),
                    borderColor: event.borderColor || this.getEventColor(status),
                    textColor: event.textColor || '#fff',
                    description: event.description,
                    status: status,
                    extendedProps: {
                        ...event.extendedProps,
                        status: status,
                        leaveUrl: event.url
                    }
                };
            }));

            const filteredEvents = this.filterEvents(this.state.allEvents);

            this.updateFilterCounters();
            this.updateStatistics();

            successCallback(filteredEvents);
        } catch (error) {
            console.error('Error fetching leave requests:', error);
            failureCallback(error);
            toastr.error('Failed to load leave requests');
        }
    }

    deduplicateEvents(events) {
        const seen = new Set();
        return events.filter(event => {
            const duplicate = seen.has(event.id);
            seen.add(event.id);
            return !duplicate;
        });
    }

    transformEvents(response) {
        return response.map(event => {
            const status = event.extendedProps?.status?.toLowerCase() || 'unknown';
            return {
                id: event.id,
                title: event.title,
                start: event.start,
                end: event.end,
                url: null,
                backgroundColor: event.backgroundColor || this.getEventColor(status),
                borderColor: event.borderColor || this.getEventColor(status),
                textColor: event.textColor || '#fff',
                description: event.description,
                status: status,
                extendedProps: {
                    ...event.extendedProps,
                    status: status,
                    leaveUrl: (event.id ? (this.config.baseUrl + '/leave-requests/' + event.id) : null)
                }
            };
        });
    }

    getEventColor(status) {
        const colors = {
            accepted: '#4caf50',
            pending: '#ffeb3b',
            rejected: '#f44336'
        };
        return colors[status] || '#6c757d';
    }

    filterEvents(events) {
        return events.filter(event => {
            const statusMatch = this.state.activeFilters.status.length === 0 ||
                this.state.activeFilters.status.includes(event.extendedProps?.status || 'unknown');
            return statusMatch;
        });
    }

    handleEventMouseEnter(info) {
        const event = info.event;
        const description = event.extendedProps.description || 'No description available';

        let tooltipContent = `
            <div class="calendar-tooltip">
                <strong>${event.title}</strong><br>
                <small class="text-muted">${moment(event.start).format('DD/MM/YYYY')} - ${moment(event.end || event.start).subtract(1, 'days').format('DD/MM/YYYY')}</small><br>
                <p class="mb-1">${description}</p>
                <span class="badge bg-${this.getStatusBadgeColor(event.extendedProps?.status)}">${event.extendedProps?.status || 'Unknown'}</span>
            </div>
        `;

        const tooltip = $(tooltipContent);
        tooltip.css({
            position: "absolute",
            background: "rgba(0, 0, 0, 0.8)",
            color: "#fff",
            padding: "5px",
            borderRadius: "5px",
            zIndex: "1000",
            pointerEvents: "none",
            maxWidth: "300px",
            fontSize: "12px"
        });

        $('body').append(tooltip);

        const rect = info.el.getBoundingClientRect();
        tooltip.css({
            left: Math.min(rect.left + window.scrollX, window.innerWidth - tooltip.outerWidth() - 20),
            top: rect.bottom + window.scrollY + 5
        });

        $(info.el).one('mouseleave', () => tooltip.remove());
    }

    handleEventClick(info) {
        if (info.jsEvent && typeof info.jsEvent.preventDefault === 'function') {
            info.jsEvent.preventDefault();
        }

        const event = info.event;
        const url = (event.extendedProps && event.extendedProps.leaveUrl) || (event.id ? (this.config.baseUrl + '/leave-requests/' + event.id) : null);
        if (url && url !== 'null') {
            window.location.href = url;
        } else {
            console.warn('No URL available for calendar event', event);
        }

    }


    getStatusBadgeColor(status) {
        const colors = {
            accepted: 'success',
            pending: 'warning',
            rejected: 'danger',
            unknown: 'secondary'
        };
        return colors[status?.toLowerCase()] || 'secondary';
    }

    debounce(key, func, delay) {
        if (this.debounceTimers.has(key)) {
            clearTimeout(this.debounceTimers.get(key));
        }

        const timeoutId = setTimeout(() => {
            func();
            this.debounceTimers.delete(key);
        }, delay);

        this.debounceTimers.set(key, timeoutId);
    }

    destroy() {
        if (this.state.calendar) {
            this.state.calendar.destroy();
        }

        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();
        this.cache.clear();

        $('.status-filter').off('.gcm');

        this.state.isInitialized = false;
    }
}

async function initializeGoogleCalendar(config = {}) {
    try {
        const calendarManager = new GoogleCalendarManager(config);
        await calendarManager.init();

        if (!window.googleCalendarInstances) {
            window.googleCalendarInstances = new Map();
        }
        window.googleCalendarInstances.set(
            config.calendarContainerId || 'googleCalendarDiv',
            calendarManager
        );

        return calendarManager;
    } catch (error) {
        console.error('Failed to initialize google calendar:', error);
        throw error;
    }
}

function googleCalendarView(googleCalendarDiv) {
    console.warn('googleCalendarView is deprecated. Use initializeGoogleCalendar instead.');
    return initializeGoogleCalendar({
        calendarContainerId: googleCalendarDiv.id || 'googleCalendarDiv',
        googleCalendarId: window.google_calendar_id,
        googleCalendarApiKey: window.google_calendar_api_key
    });
}

$(document).ready(function () {
    const calendarEl = document.getElementById('googleCalendarDiv');
    if (calendarEl) {
        initializeGoogleCalendar({
            googleCalendarId: window.google_calendar_id,
            googleCalendarApiKey: window.google_calendar_api_key
        }).catch(error => {
            console.error('Initialization error:', error);
            toastr.error('Failed to initialize google calendar');
        });
    }
});
