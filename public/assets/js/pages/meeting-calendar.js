// Removed the alert('here') as it's likely for debugging and not needed in production

class MeetingsCalendarManager {
    constructor(config = {}) {
        this.config = {
            calendarContainerId: 'meetings_calendar_view', // Updated to match Blade file
            statusFiltersId: 'meeting-status-filters-container',
            baseUrl: config.baseUrl || window.baseUrl || '',
            dateFormat: config.dateFormat || window.js_date_format || 'DD/MM/YYYY',
            timeFormat: config.timeFormat || 'HH:mm',
            csrfToken: config.csrfToken || $('input[name="_token"]').attr("value"),
            ...config
        };

        this.state = {
            calendar: null,
            allMeetings: [],
            meetingStatuses: [
                { id: 'upcoming', name: 'Upcoming', color: 'primary' },
                { id: 'ongoing', name: 'Ongoing', color: 'success' },
                { id: 'completed', name: 'Completed', color: 'secondary' },
                { id: 'cancelled', name: 'Cancelled', color: 'danger' }
            ],
            activeFilters: {
                status: ['upcoming', 'ongoing']
            },
            isInitialized: false
        };

        this.cache = new Map();
        this.debounceTimers = new Map();
    }

    async init() {
        if (this.state.isInitialized) {
            console.warn('MeetingsCalendarManager already initialized');
            return this;
        }

        try {
            this.initializeFilters();
            await this.initializeCalendar();
            this.state.isInitialized = true;
            return this;
        } catch (error) {
            console.error('Failed to initialize MeetingsCalendarManager:', error);
            throw error;
        }
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

            // Cache for 5 minutes
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

        const filtersHtml = this.state.meetingStatuses.map(status =>
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
        $(`.${filterType}-filter`).off('change.mcm').on('change.mcm', (e) => {
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

        this.state.meetingStatuses.forEach(status => {
            $(`#count-status-${status.id}`).text(counts.status[status.id] || 0);
        });
    }

    calculateCounts() {
        return this.state.allMeetings.reduce((acc, meeting) => {
            const status = this.determineMeetingStatus(meeting);
            acc.status[status] = (acc.status[status] || 0) + 1;
            return acc;
        }, { status: {} });
    }

    determineMeetingStatus(meeting) {
        const now = moment();
        const start = moment(meeting.start);
        const end = moment(meeting.end);

        if (meeting.status === 'cancelled') return 'cancelled';
        if (now.isBefore(start)) return 'upcoming';
        if (now.isBetween(start, end)) return 'ongoing';
        return 'completed';
    }

    updateStatistics() {
        const totalMeetings = this.state.allMeetings.length;
        const visibleMeetings = this.getVisibleMeetingsCount();
        const filteredMeetings = totalMeetings - visibleMeetings;

        $('#total-meetings').text(totalMeetings);
        $('#visible-meetings').text(visibleMeetings);
        $('#filtered-meetings').text(filteredMeetings);
    }

    getVisibleMeetingsCount() {
        return this.state.allMeetings.filter(meeting => {
            const status = this.determineMeetingStatus(meeting);
            const statusMatch = this.state.activeFilters.status.length === 0 ||
                this.state.activeFilters.status.includes(status);
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
                right: "dayGridMonth,listWeek"
            },
            initialView: "dayGridMonth",
            editable: false,
            selectable: true,
            selectHelper: true,
            height: "auto",
            eventLimit: 4,
            nowIndicator: true,
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            events: (fetchInfo, successCallback, failureCallback) => {
                this.fetchMeetings(fetchInfo, successCallback, failureCallback);
            },
            eventDidMount: (info) => this.handleEventDidMount(info),
            eventClick: (info) => this.handleEventClick(info),
            dateClick: (info) => this.handleDateClick(info),
            select: (info) => this.handleSelect(info),
            eventMouseEnter: (info) => this.handleEventMouseEnter(info)
        });

        this.state.calendar.render();
    }

    async fetchMeetings(fetchInfo, successCallback, failureCallback) {
        try {
            const response = await $.ajax({
                url: this.config.baseUrl + "/meetings/get-calendar-data",
                type: "GET",
                data: {
                    start: fetchInfo.start.toISOString(),
                    end: fetchInfo.end.toISOString()
                }
            });

            const events = this.transformEvents(response);
            this.state.allMeetings = events;

            const filteredEvents = this.filterEvents(events);

            this.updateFilterCounters();
            this.updateStatistics();

            successCallback(filteredEvents);
        } catch (error) {
            console.error('Error fetching meetings:', error);
            failureCallback(error);
            toastr.error('Failed to load meetings');
        }
    }

    transformEvents(response) {
        return response.map(event => {
            const serverStatus = event.extendedProps?.status?.toLowerCase();
            const status = this.mapServerStatusToLocal(serverStatus) || this.determineMeetingStatus(event);

            return {
                id: event.id,
                title: event.title,
                start: event.start,
                end: event.end,
                url: event.url,
                backgroundColor: event.backgroundColor,
                borderColor: event.borderColor,
                textColor: event.textColor,
                description: event.description,
                allDay: event.allDay || false,
                status: status,
                extendedProps: {
                    ...event.extendedProps,
                    status: status,
                    organizer: event.extendedProps?.organizer || 'Unknown',
                    location: event.extendedProps?.location,
                    meetingUrl: event.url
                }
            };
        });
    }

    mapServerStatusToLocal(serverStatus) {
        const statusMap = {
            'ongoing': 'ongoing',
            'upcoming': 'upcoming',
            'completed': 'completed',
            'cancelled': 'cancelled',
            'canceled': 'cancelled'
        };
        return statusMap[serverStatus];
    }

    extractMeetingType(title) {
        const typeKeywords = {
            'interview': ['interview', 'hiring'],
            'client': ['client', 'customer'],
            'standup': ['standup', 'stand-up', 'daily'],
            'internal': ['team', 'internal', 'planning']
        };

        const lowerTitle = title.toLowerCase();
        for (const [type, keywords] of Object.entries(typeKeywords)) {
            if (keywords.some(keyword => lowerTitle.includes(keyword))) {
                return type;
            }
        }
        return null;
    }

    getStatusColor(status) {
        const statusColors = {
            upcoming: '#0d6efd',
            ongoing: '#198754',
            completed: '#6c757d',
            cancelled: '#dc3545'
        };
        return statusColors[status] || '#6c757d';
    }

    filterEvents(events) {
        return events.filter(event => {
            const statusMatch = this.state.activeFilters.status.length === 0 ||
                this.state.activeFilters.status.includes(event.status);
            return statusMatch;
        });
    }

    handleEventDidMount(info) {
        const event = info.event;
        const element = info.el;
        const status = event.extendedProps.status;

        element.classList.add(`meeting-${status}`);

        if (status === 'ongoing') {
            element.style.animation = 'pulse 2s infinite';
            element.title = 'Meeting is currently ongoing - Click to join';
        }

        const typeIndicator = document.createElement('span');
        typeIndicator.className = 'meeting-type-indicator';
        typeIndicator.textContent = event.extendedProps.type?.charAt(0).toUpperCase() || 'I';
        element.appendChild(typeIndicator);
    }


    handleEventClick(info) {
        info.jsEvent.preventDefault(); // Prevent default navigation to event.url
        const meeting = info.event;
        const status = meeting.extendedProps.status;

        if (status === 'ongoing' && meeting.extendedProps.meetingUrl) {
            this.showJoinMeetingDialog(meeting);
        } else {
            this.showMeetingDetails(meeting);
        }
    }

    showJoinMeetingDialog(meeting) {
        // Remove any existing join modal to prevent duplicates
        $('#joinMeetingModal').remove();

        const modal = $(`
        <div class="modal fade" id="joinMeetingModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Join Meeting</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>${meeting.title}</strong></p>
                        <p>This meeting is currently ongoing. Would you like to join?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="joinMeetingBtn">Join Meeting</button>
                    </div>
                </div>
            </div>
        </div>
    `);

        $('body').append(modal);
        modal.modal('show');

        // Ensure only one click handler is bound
        modal.find('#joinMeetingBtn').off('click').on('click', () => {
            window.location.href = `/meetings/join/${meeting.id}`;
            modal.modal('hide'); // Hide modal after clicking
        });

        modal.on('hidden.bs.modal', () => modal.remove());
    }

    showMeetingDetails(meeting) {
        // Ensure ongoing meetings don't trigger details view
        if (meeting.extendedProps.status === 'ongoing') {
            console.warn('Ongoing meeting should use join modal, not details');
            return;
        }

        if (typeof window.showMeetingDetails === 'function') {
            window.showMeetingDetails(meeting.id);
        } else {
            window.location.href = `/meetings`;
        }
    }

    // ... (rest of the code remains unchanged)

    handleDateClick(info) {
        const date = moment(info.dateStr).format(this.config.dateFormat);
        const time = moment().format(this.config.timeFormat);
        this.openCreateMeetingModal(date, time, date, time);
    }

    handleSelect(info) {
        const startDate = moment(info.startStr).format(this.config.dateFormat);
        const startTime = moment(info.startStr).format(this.config.timeFormat);
        const endDate = moment(info.endStr).format(this.config.dateFormat);
        const endTime = moment(info.endStr).format(this.config.timeFormat);

        this.openCreateMeetingModal(startDate, startTime, endDate, endTime);
    }

    openCreateMeetingModal(startDate, startTime, endDate, endTime) {
        const modal = $("#createMeetingModal");
        if (!modal.length) {
            console.warn("#createMeetingModal not found in DOM");
            toastr.error("Create meeting form not found");
            return;
        }

        modal.modal("show");
        modal.find("#start_date").val(startDate);
        modal.find("#start_time").val(startTime);
        modal.find("#end_date").val(endDate);
        modal.find("#end_time").val(endTime);
    }

    handleEventMouseEnter(info) {
        const meeting = info.event;
        const participants = meeting.extendedProps.participants || [];
        const location = meeting.extendedProps.location;

        let tooltipContent = `
            <div class="meeting-tooltip">
                <strong>${meeting.title}</strong><br>
                <small class="text-muted">${moment(meeting.start).format('DD/MM/YYYY HH:mm')} - ${moment(meeting.end).format('HH:mm')}</small><br>
        `;

        if (meeting.extendedProps.description) {
            tooltipContent += `<p class="mb-1">${meeting.extendedProps.description}</p>`;
        }

        if (location) {
            tooltipContent += `<small><i class="fas fa-map-marker-alt"></i> ${location}</small><br>`;
        }

        if (participants.length > 0) {
            tooltipContent += `<small><i class="fas fa-users"></i> ${participants.length} participant(s)</small><br>`;
        }

        tooltipContent += `<span class="badge bg-${this.getStatusBadgeColor(meeting.extendedProps.status)}">${meeting.extendedProps.status}</span>`;
        tooltipContent += `</div>`;

        const tooltip = $(tooltipContent);
        tooltip.css({
            position: "absolute",
            background: "rgba(0, 0, 0, 0.9)",
            color: "#fff",
            padding: "10px",
            borderRadius: "8px",
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

    getStatusBadgeColor(status) {
        const colors = {
            upcoming: 'primary',
            ongoing: 'success',
            completed: 'secondary',
            cancelled: 'danger'
        };
        return colors[status] || 'secondary';
    }

    destroy() {
        if (this.state.calendar) {
            this.state.calendar.destroy();
        }

        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();
        this.cache.clear();

        $('.status-filter').off('.mcm');

        this.state.isInitialized = false;
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

    // Public API methods
    addMeeting(meetingData) {
        if (this.state.calendar) {
            this.state.calendar.addEvent(meetingData);
        }
    }

    updateMeeting(meetingId, updates) {
        if (this.state.calendar) {
            const event = this.state.calendar.getEventById(meetingId);
            if (event) {
                event.setProp('title', updates.title);
                event.setStart(updates.start);
                event.setEnd(updates.end);
            }
        }
    }

    removeMeeting(meetingId) {
        if (this.state.calendar) {
            const event = this.state.calendar.getEventById(meetingId);
            if (event) {
                event.remove();
            }
        }
    }
}

async function initializeMeetingsCalendar(config = {}) {
    try {
        const calendarManager = new MeetingsCalendarManager(config);
        await calendarManager.init();

        if (!window.meetingsCalendarInstances) {
            window.meetingsCalendarInstances = new Map();
        }
        window.meetingsCalendarInstances.set(
            config.calendarContainerId || 'meetings_calendar_view', // Updated default
            calendarManager
        );

        return calendarManager;
    } catch (error) {
        console.error('Failed to initialize meetings calendar:', error);
        throw error;
    }
}

function meetings_calendar_view(meetingsCalenderDiv) {
    console.warn('meetings_calendar_view is deprecated. Use initializeMeetingsCalendar instead.');
    return initializeMeetingsCalendar({
        calendarContainerId: meetingsCalenderDiv.id || 'meetings_calendar_view' // Updated default
    });
}

$(document).ready(function () {
    const calendarEl = document.getElementById('meetings_calendar_view'); // Updated ID
    if (calendarEl) {
        initializeMeetingsCalendar().catch(console.error);
    }
});
