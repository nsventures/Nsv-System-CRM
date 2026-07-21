class ActivityCalendarManager {
    constructor(config = {}) {
        this.config = {
            calendarContainerId: config.calendarContainerId || 'activity_calendar_view',
            statusFiltersId: config.statusFiltersId || 'activity-status-filters-container',
            typeFiltersId: config.typeFiltersId || 'activity-type-filters-container',
            baseUrl: config.baseUrl || window.baseUrl || '',
            dateFormat: config.dateFormat || window.js_date_format || 'DD/MM/YYYY',
            timeFormat: config.timeFormat || 'HH:mm',
            csrfToken: config.csrfToken || $('input[name="_token"]').attr("value"),
            ...config
        };

        this.state = {
            calendar: null,
            allEvents: [],
            activities: [
                { id: 'Created', name: 'Created', color: 'success' }, // #a0e4a3 -> success
                { id: 'Updated', name: 'Updated', color: 'warning' }, // #ffca66 -> warning
                { id: 'Deleted', name: 'Deleted', color: 'danger' }, // #ff6b5c -> danger
                { id: 'Duplicated', name: 'Duplicated', color: 'info' }, // #6ed4f0 -> info
                { id: 'Uploaded', name: 'Uploaded', color: 'primary' }, // #9bafff -> primary
                { id: 'Updated status', name: 'Updated Status', color: 'info' }, // #6ed4f0 -> info
                { id: 'Updated priority', name: 'Updated Priority', color: 'info' }, // #6ed4f0 -> info
                { id: 'Signed', name: 'Signed', color: 'secondary' }, // #aab0b8 -> secondary
                { id: 'Unsigned', name: 'Unsigned', color: 'dark' }, // #4f5b67 -> dark
                { id: 'Stopped', name: 'Stopped', color: 'info' }, // #6ed4f0 -> info
                { id: 'Started', name: 'Started', color: 'info' }, // #6ed4f0 -> info
                { id: 'Paused', name: 'Paused', color: 'info' } // #6ed4f0 -> info
            ],
            types: [], // Will be populated dynamically from API
            activeFilters: {
                activity: [],
                type: []
            },
            isInitialized: false
        };

        // Initialize all filters as active
        this.state.activeFilters.activity = this.state.activities.map(activity => activity.id);

        this.cache = new Map();
        this.debounceTimers = new Map();
    }

    async init() {
        if (this.state.isInitialized) {
            console.warn('ActivityCalendarManager already initialized');
            return this;
        }

        try {
            this.initializeFilters();
            await this.initializeCalendar();
            this.state.isInitialized = true;
            console.log('ActivityCalendarManager initialized successfully');
            return this;
        } catch (error) {
            console.error('Failed to initialize ActivityCalendarManager:', error);
            throw error;
        }
    }

    initializeFilters() {
        this.renderFilters();
    }

    renderFilters() {
        this.renderActivityFilters();
        this.renderTypeFilters();
    }

    renderActivityFilters() {
        const container = $(`#${this.config.statusFiltersId}`);
        if (!container.length) {
            console.warn(`Activity filters container #${this.config.statusFiltersId} not found`);
            return;
        }

        const filtersHtml = this.state.activities.map(activity =>
            this.createFilterHtml('activity', activity)
        ).join('');

        container.html(filtersHtml);
        this.bindFilterEvents('activity');
    }

    renderTypeFilters() {
        const container = $(`#${this.config.typeFiltersId}`);
        if (!container.length) {
            console.warn(`Type filters container #${this.config.typeFiltersId} not found`);
            return;
        }

        const filtersHtml = this.state.types.map(type =>
            this.createFilterHtml('type', type)
        ).join('');

        container.html(filtersHtml);
        this.bindFilterEvents('type');
    }

    createFilterHtml(filterType, item) {
        const isChecked = this.state.activeFilters[filterType].includes(item.id);
        return `
            <div class="form-check form-check-${item.color || 'secondary'} mb-1">
                <input class="form-check-input ${filterType}-filter" type="checkbox" ${isChecked ? 'checked' : ''}
                       data-${filterType}="${item.id}" id="filter${filterType.charAt(0).toUpperCase() + filterType.slice(1)}${item.id.replace(/\s+/g, '')}">
                <label class="form-check-label" for="filter${filterType.charAt(0).toUpperCase() + filterType.slice(1)}${item.id.replace(/\s+/g, '')}">
                    <div class="d-flex align-items-center">
                        <span class="">${item.name}</span>
                        <span class="filter-counter ms-2" id="count-${filterType}-${item.id.replace(/\s+/g, '')}">0</span>
                    </div>
                </label>
            </div>
        `;
    }

    bindFilterEvents(filterType) {
        $(`.${filterType}-filter`).off('change.acm').on('change.acm', (e) => {
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
        console.log(`Active ${filterType} filters:`, this.state.activeFilters[filterType]);
    }

    addSelectAllButtons() {
        // Removed Select All/Deselect All buttons
    }

    applyFilters() {
        if (!this.state.calendar) return;

        this.state.calendar.refetchEvents();
        this.updateFilterCounters();
        this.updateStatistics();
    }

    updateFilterCounters() {
        const counts = this.calculateCounts();

        // Update activity counters
        this.state.activities.forEach(activity => {
            const counter = $(`#count-activity-${activity.id.replace(/\s+/g, '')}`);
            if (counter.length) {
                counter.text(counts.activity[activity.id] || 0);
            }
        });

        // Update type counters
        this.state.types.forEach(type => {
            const counter = $(`#count-type-${type.id.replace(/\s+/g, '')}`);
            if (counter.length) {
                counter.text(counts.type[type.id] || 0);
            }
        });
    }

    calculateCounts() {
        return this.state.allEvents.reduce((acc, event) => {
            const activity = event.activity || 'Unknown';
            const type = event.type || 'Unknown';

            acc.activity[activity] = (acc.activity[activity] || 0) + 1;
            acc.type[type] = (acc.type[type] || 0) + 1;

            return acc;
        }, { activity: {}, type: {} });
    }

    updateStatistics() {
        const totalEvents = this.state.allEvents.length;
        const visibleEvents = this.getVisibleEventsCount();
        const filteredEvents = totalEvents - visibleEvents;

        $('#total-activities').text(totalEvents);
        $('#visible-activities').text(visibleEvents);
        $('#filtered-activities').text(filteredEvents);
    }

    getVisibleEventsCount() {
        return this.state.allEvents.filter(event => {
            const activityMatch = this.state.activeFilters.activity.length === 0 ||
                this.state.activeFilters.activity.includes(event.activity || 'Unknown');
            const typeMatch = this.state.activeFilters.type.length === 0 ||
                this.state.activeFilters.type.includes(event.type || 'Unknown');
            return activityMatch && typeMatch;
        }).length;
    }

    async initializeCalendar() {
        const calendarEl = document.getElementById(this.config.calendarContainerId);
        if (!calendarEl) {
            throw new Error(`Calendar container #${this.config.calendarContainerId} not found`);
        }

        this.state.calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: ['interaction', 'dayGrid', 'list'],
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listYear'
            },
            editable: false,
            selectable: true,
            selectHelper: true,
            height: 'auto',
            eventLimit: 4,
            themeSystem: 'bootstrap5',
            timeZone: 'local',
            events: (fetchInfo, successCallback, failureCallback) => {
                this.fetchActivities(fetchInfo.start, fetchInfo.end, successCallback, failureCallback);
            },
            eventMouseEnter: (info) => this.handleEventMouseEnter(info),
            eventClick: (info) => this.handleEventClick(info),
            datesSet: () => {
                this.state.allEvents = [];
            },
            eventDidMount: (info) => {
                const event = info.event;
                const activity = event.extendedProps.activity || 'unknown';
                const type = event.extendedProps.type || 'unknown';

                info.el.classList.add(`activity-${activity.toLowerCase().replace(/\s+/g, '-')}`);
                info.el.classList.add(`type-${type.toLowerCase().replace(/\s+/g, '-')}`);
            }
        });

        this.state.calendar.render();
    }

    fetchActivities(startDate, endDate, successCallback, failureCallback) {
        const url = this.config.baseUrl.endsWith('/')
            ? this.config.baseUrl + "activity-log/get-calendar-data"
            : this.config.baseUrl + "/activity-log/get-calendar-data";

        $.ajax({
            url: url,
            type: "GET",
            data: {
                date_from: moment(startDate).format(this.config.dateFormat),
                date_to: moment(endDate).format(this.config.dateFormat),
                limit: 1500
            },
            success: (response) => {
                console.log('Fetched activities:', response.length);

                // Store all events for filtering
                this.state.allEvents = response.map(event => ({
                    id: event.id,
                    title: event.title,
                    start: event.start,
                    end: event.end,
                    url: event.url,
                    backgroundColor: event.backgroundColor,
                    borderColor: event.borderColor || event.backgroundColor,
                    textColor: event.textColor,
                    activity: event.activity || 'Unknown',
                    type: event.type || 'Unknown',
                    allDay: event.allDay || false
                }));

                // Extract unique types from response and update state
                this.updateTypesFromEvents();

                // Apply current filters
                const filteredEvents = this.filterEvents(this.state.allEvents);

                // Update UI
                this.updateFilterCounters();
                this.updateStatistics();

                successCallback(filteredEvents);
            },
            error: (xhr, status, error) => {
                console.error('Error fetching activities:', error, xhr.responseText);
                failureCallback(error);

                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to load activities');
                }
            }
        });
    }

    updateTypesFromEvents() {
        const uniqueTypes = [...new Set(this.state.allEvents.map(event => event.type))];
        const newTypes = uniqueTypes.map(type => ({
            id: type,
            name: type.charAt(0).toUpperCase() + type.slice(1),
            color: 'secondary' // Default color for types
        }));

        // Only update if types changed
        const currentTypeIds = this.state.types.map(t => t.id);
        const newTypeIds = newTypes.map(t => t.id);

        if (!this.arraysEqual(currentTypeIds, newTypeIds)) {
            this.state.types = newTypes;
            // Initialize type filters as all active
            this.state.activeFilters.type = newTypes.map(type => type.id);

            // Re-render type filters if container exists
            if (document.getElementById(this.config.typeFiltersId)) {
                this.renderTypeFilters();
                this.bindFilterEvents('type');
            }
        }
    }

    arraysEqual(a, b) {
        return a.length === b.length && a.every((val, index) => val === b[index]);
    }

    filterEvents(events) {
        return events.filter(event => {
            const activityMatch = this.state.activeFilters.activity.length === 0 ||
                this.state.activeFilters.activity.includes(event.activity || 'Unknown');
            const typeMatch = this.state.activeFilters.type.length === 0 ||
                this.state.activeFilters.type.includes(event.type || 'Unknown');
            return activityMatch && typeMatch;
        });
    }

    handleEventMouseEnter(info) {
        const event = info.event;
        const startTime = moment(event.start).format('DD/MM/YYYY HH:mm');
        const activity = event.extendedProps.activity || 'Unknown';
        const type = event.extendedProps.type || 'Unknown';

        const tooltipContent = `
            <div class="calendar-tooltip">
                <strong>${event.title}</strong><br>
                <small class="text-muted">${startTime}</small><br>
                <div class="mt-2">
                    <span class="badge bg-primary me-1">${activity}</span>
                    <span class="badge bg-info">${type}</span>
                </div>
            </div>
        `;

        const tooltip = $(tooltipContent);
        tooltip.css({
            position: "absolute",
            background: "rgba(0, 0, 0, 0.9)",
            color: "#fff",
            padding: "10px 14px",
            borderRadius: "8px",
            zIndex: "1000",
            pointerEvents: "none",
            maxWidth: "350px",
            fontSize: "13px",
            boxShadow: "0 4px 20px rgba(0,0,0,0.4)",
            border: "1px solid rgba(255,255,255,0.1)"
        });

        $('body').append(tooltip);

        const rect = info.el.getBoundingClientRect();
        const tooltipWidth = tooltip.outerWidth();
        const tooltipHeight = tooltip.outerHeight();

        let left = rect.left + window.scrollX;
        let top = rect.bottom + window.scrollY + 8;

        if (left + tooltipWidth > window.innerWidth) {
            left = window.innerWidth - tooltipWidth - 20;
        }

        if (top + tooltipHeight > window.innerHeight + window.scrollY) {
            top = rect.top + window.scrollY - tooltipHeight - 8;
        }

        tooltip.css({ left: left, top: top });

        $(info.el).one('mouseleave', () => tooltip.remove());
    }

    handleEventClick(info) {
        info.jsEvent.preventDefault();
        const event = info.event;

        if (event.url) {
            window.open(event.url, '_blank');
        }
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

    // Public API
    refresh() {
        if (this.state.calendar) {
            this.state.calendar.refetchEvents();
        }
    }

    setFilters(filters) {
        if (filters.activity && Array.isArray(filters.activity)) {
            this.state.activeFilters.activity = filters.activity;
            $('.activity-filter').each((i, checkbox) => {
                const $checkbox = $(checkbox);
                const activityId = $checkbox.data('activity');
                $checkbox.prop('checked', this.state.activeFilters.activity.includes(activityId));
            });
        }

        if (filters.type && Array.isArray(filters.type)) {
            this.state.activeFilters.type = filters.type;
            $('.type-filter').each((i, checkbox) => {
                const $checkbox = $(checkbox);
                const typeId = $checkbox.data('type');
                $checkbox.prop('checked', this.state.activeFilters.type.includes(typeId));
            });
        }

        this.applyFilters();
    }

    getEvents() {
        return this.state.allEvents;
    }

    getActiveFilters() {
        return { ...this.state.activeFilters };
    }

    destroy() {
        if (this.state.calendar) {
            this.state.calendar.destroy();
        }

        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();
        this.cache.clear();

        $('.activity-filter, .type-filter').off('.acm');
        $('.calendar-tooltip').remove();

        this.state.isInitialized = false;
    }
}

// Initialize function
async function initializeActivityCalendar(config = {}) {
    try {
        const calendarManager = new ActivityCalendarManager(config);
        await calendarManager.init();

        if (!window.activityCalendarInstances) {
            window.activityCalendarInstances = new Map();
        }

        const instanceKey = config.calendarContainerId || 'activity_calendar_view';
        window.activityCalendarInstances.set(instanceKey, calendarManager);

        return calendarManager;
    } catch (error) {
        console.error('Failed to initialize activity calendar:', error);
        throw error;
    }
}

// Backward compatibility
function activity_calendar_view(activityCalenderDiv) {
    const containerId = activityCalenderDiv?.id || 'activity_calendar_view';
    return initializeActivityCalendar({ calendarContainerId: containerId });
}

// Auto-initialize
$(document).ready(function () {
    const calendarEl = document.getElementById('activity_calendar_view');
    if (calendarEl) {
        initializeActivityCalendar().catch(error => {
            console.error('Auto-initialization failed:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to initialize activity calendar');
            }
        });
    }
});
