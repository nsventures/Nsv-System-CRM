class ProjectCalendarManager {
    constructor(config = {}) {
        this.config = {
            calendarContainerId: 'projectCalenderDiv',
            statusFiltersId: 'status-filters-container',
            priorityFiltersId: 'priority-filters-container',
            baseUrl: config.baseUrl || window.baseUrl || '',
            dateFormat: config.dateFormat || window.js_date_format || 'DD/MM/YYYY',
            csrfToken: config.csrfToken || $('input[name="_token"]').attr("value"),
            ...config
        };

        this.state = {
            calendar: null,
            allProjects: [],
            projectStatuses: [],
            projectPriorities: [],
            activeFilters: {
                status: [],
                priority: []
            },
            isInitialized: false
        };

        this.cache = new Map();
        this.debounceTimers = new Map();
    }

    async init() {
        if (this.state.isInitialized) {
            console.warn('ProjectCalendarManager already initialized');
            return this;
        }

        try {
            await this.loadFilterOptions();
            await this.initializeCalendar();
            this.initializeQuickActions();
            this.state.isInitialized = true;
            return this;
        } catch (error) {
            console.error('Failed to initialize ProjectCalendarManager:', error);
            throw error;
        }
    }

    async loadFilterOptions() {
        const filterSection = $('.filter-section');
        filterSection.addClass('loading-filters');

        try {
            const [statusResponse, priorityResponse] = await Promise.all([
                this.apiRequest('/get-statuses'),
                this.apiRequest('/get-priorities')
            ]);

            this.state.projectStatuses = statusResponse.statuses || statusResponse;
            this.state.projectPriorities = priorityResponse.priorities || priorityResponse;

            this.state.activeFilters.status = this.state.projectStatuses.map(s => s.id.toString());
            this.state.activeFilters.priority = this.state.projectPriorities.map(p => p.id.toString());

            this.renderFilters();
        } catch (error) {
            console.error('Error loading filter options:', error);
            this.handleFilterLoadError();
        } finally {
            filterSection.removeClass('loading-filters');
        }
    }

    async apiRequest(endpoint, options = {}) {
        const cacheKey = `${endpoint}-${JSON.stringify(options)}`;

        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

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
        return response;
    }

    renderFilters() {
        this.renderStatusFilters();
        this.renderPriorityFilters();
    }

    renderStatusFilters() {
        const container = $(`#${this.config.statusFiltersId}`);
        if (!container.length) return;

        const filtersHtml = this.state.projectStatuses.map(status =>
            this.createFilterHtml('status', status)
        ).join('');

        container.html(filtersHtml);
        this.bindFilterEvents('status');
    }

    renderPriorityFilters() {
        const container = $(`#${this.config.priorityFiltersId}`);
        if (!container.length) return;

        const filtersHtml = this.state.projectPriorities.map(priority =>
            this.createFilterHtml('priority', priority)
        ).join('');

        container.html(filtersHtml);
        this.bindFilterEvents('priority');
    }

    createFilterHtml(type, item) {
        return `
            <div class="form-check form-check-${item.color || 'secondary'} ">
                <input class="form-check-input ${type}-filter" type="checkbox" checked
                       data-${type}="${item.id}" id="filter${type.charAt(0).toUpperCase() + type.slice(1)}${item.id}">
                <label class="form-check-label" for="filter${type.charAt(0).toUpperCase() + type.slice(1)}${item.id}">
                    <div class="d-flex align-items-center">
                        <span class="">${item.title || item.name}</span>
                    </div>
                    <span class="filter-counter" id="count-${type}-${item.id}">0</span>
                </label>
            </div>
        `;
    }

    bindFilterEvents(type) {
        $(`.${type}-filter`).off('change.pcm').on('change.pcm', (e) => {
            const itemId = $(e.target).data(type).toString();
            const isChecked = $(e.target).is(':checked');

            this.updateActiveFilters(type, itemId, isChecked);
            this.debounce('applyFilters', () => this.applyFilters(), 150);
        });
    }

    updateActiveFilters(type, itemId, isChecked) {
        if (isChecked) {
            if (!this.state.activeFilters[type].includes(itemId)) {
                this.state.activeFilters[type].push(itemId);
            }
        } else {
            this.state.activeFilters[type] = this.state.activeFilters[type].filter(id => id !== itemId);
        }
    }

    initializeQuickActions() {
        $('#selectAllFilters').off('click.pcm').on('click.pcm', () => this.selectAllFilters());
        $('#clearAllFilters').off('click.pcm').on('click.pcm', () => this.clearAllFilters());
        $('#refreshCalendar').off('click.pcm').on('click.pcm', () => this.refreshCalendar());
    }

    selectAllFilters() {
        $('.status-filter, .priority-filter').prop('checked', true);
        this.state.activeFilters.status = this.state.projectStatuses.map(s => s.id.toString());
        this.state.activeFilters.priority = this.state.projectPriorities.map(p => p.id.toString());
        this.applyFilters();
    }

    clearAllFilters() {
        $('.status-filter, .priority-filter').prop('checked', false);
        this.state.activeFilters.status = [];
        this.state.activeFilters.priority = [];
        this.applyFilters();
    }

    refreshCalendar() {
        if (this.state.calendar) {
            this.state.calendar.refetchEvents();
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

        this.state.projectStatuses.forEach(status => {
            $(`#count-status-${status.id}`).text(counts.status[status.id.toString()] || 0);
        });

        this.state.projectPriorities.forEach(priority => {
            $(`#count-priority-${priority.id}`).text(counts.priority[priority.id.toString()] || 0);
        });
    }

    calculateCounts() {
        return this.state.allProjects.reduce((acc, project) => {
            const statusId = project.status_id?.toString();
            const priorityId = project.priority_id?.toString();

            if (statusId) {
                acc.status[statusId] = (acc.status[statusId] || 0) + 1;
            }
            if (priorityId) {
                acc.priority[priorityId] = (acc.priority[priorityId] || 0) + 1;
            }

            return acc;
        }, { status: {}, priority: {} });
    }

    updateStatistics() {
        const totalProjects = this.state.allProjects.length;
        const visibleProjects = this.getVisibleProjectsCount();

        $('#total-projects').text(totalProjects);
        $('#visible-projects').text(visibleProjects);
        $('#filtered-projects').text(totalProjects - visibleProjects);
    }

    getVisibleProjectsCount() {
        return this.state.allProjects.filter(project => {
            const statusMatch = this.state.activeFilters.status.length === 0 ||
                this.state.activeFilters.status.includes(project.status_id?.toString());
            const priorityMatch = this.state.activeFilters.priority.length === 0 ||
                this.state.activeFilters.priority.includes(project.priority_id?.toString());
            return statusMatch && priorityMatch;
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
                right: "dayGridMonth,listYear,",
            },
            initialView: "dayGridMonth",
            editable: true,
            selectable: true,
            selectHelper: true,
            height: "auto",
            eventLimit: 4,
            events: (fetchInfo, successCallback, failureCallback) => {
                this.fetchProjects(fetchInfo, successCallback, failureCallback);
            },
            eventDidMount: (info) => this.handleEventDidMount(info),
            eventClick: (info) => this.handleEventClick(info),
            dateClick: (info) => this.handleDateClick(info),
            select: (info) => this.handleSelect(info),
            eventDrop: (info) => this.handleEventDrop(info),
            eventResize: (info) => this.handleEventResize(info),
            eventMouseEnter: (info) => this.handleEventMouseEnter(info)
        });

        this.state.calendar.render();
    }

    async fetchProjects(fetchInfo, successCallback, failureCallback) {
        try {
            const response = await $.ajax({
                url: this.config.baseUrl + "/projects/get-calendar-data",
                type: "GET",
                data: {
                    start: fetchInfo.start.toISOString(),
                    end: fetchInfo.end.toISOString(),
                }
            });

            const events = this.transformEvents(response);

            this.state.allProjects = events;

            const filteredEvents = this.filterEvents(events);

            this.updateFilterCounters();
            this.updateStatistics();

            successCallback(filteredEvents);
        } catch (error) {
            console.error('Error fetching projects:', error);
            failureCallback(error);
        }
    }

    transformEvents(response) {
        console.log(response);

        return response.map(event => ({
            id: event.id,
            tasks_info_url: event.project_info_url,
            title: event.title,
            start: event.start,
            end: moment(event.end).add(1, "days").format("YYYY-MM-DD"),
            backgroundColor: event.backgroundColor,
            borderColor: event.borderColor,
            textColor: event.textColor,
            extendedProps: {
                status_id: event.status_id,
                priority_id: event.priority_id
            },
            status_id: event.status_id,
            priority_id: event.priority_id
        }));
    }

    filterEvents(events) {
        return events.filter(event => {
            const statusMatch = this.state.activeFilters.status.length === 0 ||
                this.state.activeFilters.status.includes(event.status_id?.toString());
            const priorityMatch = this.state.activeFilters.priority.length === 0 ||
                this.state.activeFilters.priority.includes(event.priority_id?.toString());
            return statusMatch && priorityMatch;
        });
    }

    handleEventDidMount(info) {
        const event = info.event;
        const element = info.el;

        const status = this.state.projectStatuses.find(s =>
            s.id.toString() === event.extendedProps.status_id?.toString()
        );
        const priority = this.state.projectPriorities.find(p =>
            p.id.toString() === event.extendedProps.priority_id?.toString()
        );

        if (status?.color) {
            element.style.backgroundColor = status.color;
            element.style.borderColor = status.color;
            element.classList.add('status-color');
        }

        if (priority?.color) {
            element.style.borderLeft = `4px solid ${priority.color}`;
        }
    }

    handleEventClick(info) {
        editProject(info.event.id, true, this.config.baseUrl, this.config.dateFormat);
    }

    handleDateClick(info) {
        const date = moment(info.dateStr).format(this.config.dateFormat);
        this.openCreateProjectOffcanvas(date, date);
    }

    handleSelect(info) {
        const startDate = moment(info.startStr).format(this.config.dateFormat);
        const endDate = moment(info.endStr).subtract(1, "days").format(this.config.dateFormat);
        this.openCreateProjectOffcanvas(startDate, endDate);
    }

    openCreateProjectOffcanvas(startDate, endDate) {
        const $offcanvas = $("#create_project_offcanvas");
        if (!$offcanvas.length) {
            console.warn("#create_project_offcanvas not found in DOM");
            toastr.error("Create project form not found");
            return;
        }

        // Open offcanvas
        $offcanvas.offcanvas("show");

        // Populate start and end date fields
        $offcanvas.find("#start_date").val(startDate);
        $offcanvas.find("#end_date").val(endDate);

        // Initialize DateRangePicker for date fields
        initializeDateRangePicker($offcanvas.find("#start_date, #end_date"));
    }

    handleEventDrop(info) {
        this.showUpdateConfirmation(info, 'drag');
    }

    handleEventResize(info) {
        this.showUpdateConfirmation(info, 'resize');
    }

    handleEventMouseEnter(info) {
        this.showTooltip(info);
    }

    showUpdateConfirmation(info, type) {
        const modalId = type === 'drag' ? '#confirmDragProjectModal' : '#confirmResizeProjectModal';
        $(modalId).modal("show");

        $(modalId).off("click.pcm", "#confirm").on("click.pcm", "#confirm", () => {
            this.updateProjectDates(info, modalId);
        });

        $(modalId).off("click.pcm", "#cancel").on("click.pcm", "#cancel", () => {
            info.revert();
            $(modalId).modal("hide");
        });
    }

    async updateProjectDates(info, modalId) {
        const confirmBtn = $(modalId).find("#confirm");
        confirmBtn.html(window.label_please_wait || 'Please wait...').attr("disabled", true);

        try {
            const start = moment(info.event.start).format(this.config.dateFormat);
            const end = moment(info.event.end).subtract(1, "days").format(this.config.dateFormat);

            const response = await $.ajax({
                url: this.config.baseUrl + "/projects/update-dates",
                type: "PATCH",
                headers: { "X-CSRF-TOKEN": this.config.csrfToken },
                data: {
                    id: info.event.id,
                    start_date: start,
                    end_date: end === "Invalid date" ? start : end,
                }
            });

            if (response.error === false) {
                $(modalId).modal("hide");
                toastr.success(response.message);
                this.state.calendar.refetchEvents();
            } else {
                toastr.error(response.message);
                info.revert();
            }
        } catch (error) {
            console.error('Error updating project dates:', error);
            toastr.error(window.label_something_went_wrong || 'Something went wrong');
            info.revert();
        } finally {
            confirmBtn.html(window.label_yes || 'Yes').attr("disabled", false);
            $(modalId).modal("hide");
        }
    }

    showTooltip(info) {
        const tooltip = $(`<div class="calendar-tooltip">${info.event.title}</div>`);
        tooltip.css({
            position: "absolute",
            background: "rgba(0, 0, 0, 0.8)",
            color: "#fff",
            padding: "5px",
            borderRadius: "5px",
            zIndex: "1000",
            pointerEvents: "none"
        });

        $('body').append(tooltip);

        const rect = info.el.getBoundingClientRect();
        tooltip.css({
            left: rect.left + window.scrollX,
            top: rect.bottom + window.scrollY
        });

        $(info.el).one('mouseleave', () => tooltip.remove());
    }

    handleFilterLoadError() {
        $(`#${this.config.statusFiltersId}`).html('<p class="text-danger small">Error loading statuses</p>');
        $(`#${this.config.priorityFiltersId}`).html('<p class="text-danger small">Error loading priorities</p>');
    }

    destroy() {
        if (this.state.calendar) {
            this.state.calendar.destroy();
        }

        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();
        this.cache.clear();

        $('.status-filter, .priority-filter').off('.pcm');
        $('#selectAllFilters, #clearAllFilters, #refreshCalendar').off('.pcm');

        this.state.isInitialized = false;
    }

    /**
    * Utility methods
    */
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
}

// Usage function that wraps initialization
async function initializeProjectCalendar(config = {}) {
    try {
        const calendarManager = new ProjectCalendarManager(config);
        await calendarManager.init();

        // Store instance globally for external access if needed
        if (!window.projectCalendarInstances) {
            window.projectCalendarInstances = new Map();
        }
        window.projectCalendarInstances.set(config.calendarContainerId || 'projectCalenderDiv', calendarManager);

        return calendarManager;
    } catch (error) {
        console.error('Failed to initialize project calendar:', error);
        throw error;
    }
}

// Auto-initialize when document is ready (maintains backward compatibility)
$(document).ready(function () {
    const calendarEl = document.getElementById('projectCalenderDiv');
    if (calendarEl) {
        initializeProjectCalendar().catch(console.error);
    }
});
