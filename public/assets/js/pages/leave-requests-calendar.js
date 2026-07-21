class LeaveRequestsCalendarManager {
    constructor(config = {}) {
        this.config = {
            calendarContainerId: 'leave_requests_calendar_view',
            statusFiltersId: 'leave-status-filters-container',
            baseUrl: config.baseUrl || window.baseUrl || '',
            dateFormat: config.dateFormat || window.js_date_format || 'DD/MM/YYYY',
            timeFormat: config.timeFormat || 'HH:mm',
            csrfToken: config.csrfToken || $('input[name="_token"]').attr("value"),
            ...config
        };

        this.state = {
            calendar: null,
            allLeaveRequests: [],
            leaveStatuses: [
                { id: 'pending', name: 'Pending', color: 'warning' },
                { id: 'approved', name: 'Approved', color: 'success' },
                { id: 'rejected', name: 'Rejected', color: 'danger' }
            ],
            activeFilters: {
                status: ['pending', 'approved']
            },
            isInitialized: false
        };

        this.cache = new Map();
        this.debounceTimers = new Map();
    }

    async init() {
        if (this.state.isInitialized) {
            console.warn('LeaveRequestsCalendarManager already initialized');
            return this;
        }

        try {
            await this.waitForContainer(`#${this.config.statusFiltersId}`, 1000);
            this.initializeFilters();
            await this.initializeCalendar();
            this.state.isInitialized = true;
            return this;
        } catch (error) {
            console.error('Failed to initialize LeaveRequestsCalendarManager:', error);
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

        const filtersHtml = this.state.leaveStatuses.map(status =>
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
        $(`.${filterType}-filter`).off('change.lrcm').on('change.lrcm', (e) => {
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
        this.state.leaveStatuses.forEach(status => {
            $(`#count-status-${status.id}`).text(counts.status[status.id] || 0);
        });
    }

    calculateCounts() {
        return this.state.allLeaveRequests.reduce((acc, leave) => {
            const status = leave.status;
            acc.status[status] = (acc.status[status] || 0) + 1;
            return acc;
        }, { status: {} });
    }

    updateStatistics() {
        const totalLeaveRequests = this.state.allLeaveRequests.length;
        const visibleLeaveRequests = this.getVisibleLeaveRequestsCount();
        const filteredLeaveRequests = totalLeaveRequests - visibleLeaveRequests;

        $('#total-leave_requests').text(totalLeaveRequests);
        $('#visible-leave_requests').text(visibleLeaveRequests);
        $('#filtered-leave_requests').text(filteredLeaveRequests);
    }

    getVisibleLeaveRequestsCount() {
        return this.state.allLeaveRequests.filter(leave => {
            const statusMatch = this.state.activeFilters.status.length === 0 ||
                this.state.activeFilters.status.includes(leave.status);
            return statusMatch;
        }).length;
    }

    initializeCalendar() {
        const calendarEl = document.getElementById(this.config.calendarContainerId);
        if (!calendarEl) {
            throw new Error(`Calendar container #${this.config.calendarContainerId} not found`);
        }

        this.state.calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: ["interaction", "dayGrid", "list"],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listYear"
            },
            initialView: "dayGridMonth",
            editable: false,
            selectable: true,
            selectHelper: true,
            height: "auto",
            eventLimit: 4,
            events: (fetchInfo, successCallback, failureCallback) => {
                this.fetchLeaveRequests(fetchInfo, successCallback, failureCallback);
            },
            eventMouseEnter: (info) => this.handleEventMouseEnter(info),
            eventClick: (info) => this.handleEventClick(info),
            datesSet: () => {
                this.state.calendar.removeAllEvents();
                this.state.calendar.refetchEvents();
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

            const events = this.transformEvents(response);
            this.state.allLeaveRequests = events;
            const filteredEvents = this.filterEvents(events);

            this.updateFilterCounters();
            this.updateStatistics();

            successCallback(filteredEvents);
        } catch (error) {
            console.error('Error fetching leave requests:', error);
            failureCallback(error);
            toastr.error('Failed to load leave requests');
        }
    }

    transformEvents(response) {
        console.log('Raw leave requests data:', response);
        return response.map(event => ({
            id: event.id,
            title: event.title,
            start: event.start,
            end: moment(event.end).add(1, "days").format("YYYY-MM-DD"),
            url: '/leave-requests', // Prevent FullCalendar default navigation
            backgroundColor: event.backgroundColor,
            borderColor: event.borderColor,
            textColor: event.textColor,
            description: event.description,
            status: event.extendedProps?.status?.toLowerCase() || 'pending',
            extendedProps: {
                ...event.extendedProps,
                status: event.extendedProps?.status?.toLowerCase() || 'pending',
                leaveUrl: '/leave-requests' // Store URL in extendedProps
            }
        }));
    }

    filterEvents(events) {
        return events.filter(event => {
            const statusMatch = this.state.activeFilters.status.length === 0 ||
                this.state.activeFilters.status.includes(event.status);
            return statusMatch;
        });
    }

    handleEventMouseEnter(info) {
        const leave = info.event;
        const description = leave.extendedProps.description || 'No description available';

        let tooltipContent = `
            <div class="leave-tooltip">
                <strong>${leave.title}</strong><br>
                <small class="text-muted">${moment(leave.start).format('DD/MM/YYYY')} - ${moment(leave.end).subtract(1, 'days').format('DD/MM/YYYY')}</small><br>
                <p class="mb-1">${description}</p>
                <span class="badge bg-${this.getStatusBadgeColor(leave.extendedProps.status)}">${leave.extendedProps.status}</span>
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
        // info.jsEvent.preventDefault(); // Prevent default navigation
        const leave = info.event;

    }



    getStatusBadgeColor(status) {
        const colors = {
            pending: 'warning',
            approved: 'success',
            rejected: 'danger'
        };
        return colors[status] || 'secondary';
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

        $('.status-filter').off('.lrcm');

        this.state.isInitialized = false;
    }
}

async function initializeLeaveRequestsCalendar(config = {}) {
    try {
        const calendarManager = new LeaveRequestsCalendarManager(config);
        await calendarManager.init();

        if (!window.leaveRequestsCalendarInstances) {
            window.leaveRequestsCalendarInstances = new Map();
        }
        window.leaveRequestsCalendarInstances.set(
            config.calendarContainerId || 'leave_requests_calendar_view',
            calendarManager
        );

        return calendarManager;
    } catch (error) {
        console.error('Failed to initialize leave requests calendar:', error);
        throw error;
    }
}

function leave_request_calendar_view(leaveRequestCalenderDiv) {
    console.warn('leave_request_calendar_view is deprecated. Use initializeLeaveRequestsCalendar instead.');
    return initializeLeaveRequestsCalendar({
        calendarContainerId: leaveRequestCalenderDiv.id || 'leave_requests_calendar_view'
    });
}

$(document).ready(function () {
    const calendarEl = document.getElementById('leave_requests_calendar_view');
    if (calendarEl) {
        initializeLeaveRequestsCalendar().catch(error => {
            console.error('Initialization error:', error);
            toastr.error('Failed to initialize leave requests calendar');
        });
    }
});
