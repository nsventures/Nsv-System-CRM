"use strict";
class DashboardManager {
    constructor() {
        this.charts = {};
        this.colors = {
            success: "#22C55E",
            danger: "#EF4444",
            warning: "#F59E0B",
            info: "#3B82F6",
            primary: "#8B5CF6",
            secondary: "#6B7280",
            pastelSuccess: "#63ED7A",
            pastelDanger: "#FC544B",
            pastelWarning: "#FCD34D",
            pastelInfo: "#93C5FD",
            pastelPrimary: "#C4B5FD",
            grid: "#E2E8F0",
            text: "#64748B"
        };
        this.chartPalette = [
            "#22C55E", "#EF4444", "#F59E0B", "#3B82F6", "#8B5CF6",
            "#EC4899", "#14B8A6", "#F97316", "#84CC16", "#6366F1"
        ];
        this.dashboardDataEndpoint = "/dashboard/data";
        this.invoicesReportEndpoint = "/reports/income-vs-expense-report-data";
    }
    formatDataForChart(chartType, data, labels = [], categories = [], seriesName = "Values") {
        switch (chartType) {
            case 'polarArea':
            case 'pie':
            case 'donut':
            case 'radialBar':
                return this.formatCircularChartData(data, labels);
            case 'bar':
            case 'column':
                return this.formatBarChartData(data, categories, seriesName);
            case 'line':
            case 'area':
            case 'mixed':
                return this.formatLineChartData(data, categories, seriesName);
            default:
                console.warn(`Unsupported chart type: ${chartType}`);
                return { series: [], labels: [], categories: [] };
        }
    }
    formatCircularChartData(data, labels) {
        const formattedData = Array.isArray(data)
            ? data.map(val => Math.max(0, Number(val) || 0))
            : [];
        const formattedLabels = Array.isArray(labels) && labels.length === data.length
            ? labels
            : formattedData.map((_, i) => `Item ${i + 1}`);
        return {
            series: formattedData,
            labels: formattedLabels,
            categories: []
        };
    }
    formatBarChartData(data, categories, seriesName) {
        const formattedData = Array.isArray(data)
            ? data.map(val => Math.max(0, Number(val) || 0))
            : [];
        const formattedCategories = Array.isArray(categories) && categories.length === data.length
            ? categories
            : formattedData.map((_, i) => `Category ${i + 1}`);
        return {
            series: [{ name: seriesName, data: formattedData }],
            labels: [],
            categories: formattedCategories
        };
    }
    formatLineChartData(data, categories = [], seriesName = "Values") {
        let formattedSeries = [];
        if (!Array.isArray(data)) {
            return { series: [], labels: [], categories: [] };
        }
        const first = data[0];
        const isSeriesObject = first && typeof first === 'object' && ('data' in first || 'name' in first);
        const isSimpleNumericArray = typeof first === 'number' || typeof first === 'string' || Array.isArray(first) && typeof first[0] === 'number';
        if (isSeriesObject) {
            // data is [{name, data}, {name, data}]
            formattedSeries = data.map((series, i) => ({
                name: series.name || `${seriesName} ${i + 1}`,
                data: (Array.isArray(series.data) ? series.data : []).map(val => Math.max(0, Number(val) || 0))
            }));
        } else if (isSimpleNumericArray) {
            // data is [10, 20, 30]
            formattedSeries = [{
                name: seriesName,
                data: data.map(val => Math.max(0, Number(val) || 0))
            }];
        } else {
            // fallback: try to coerce to numeric array
            formattedSeries = [{
                name: seriesName,
                data: data.map(val => Math.max(0, Number(val) || 0))
            }];
        }
        const maxLength = Math.max(...formattedSeries.map(s => s.data.length), 0);
        // Use passed categories if useful; otherwise build sensible default labels.
        let formattedCategories = Array.isArray(categories) ? [...categories] : [];
        if (formattedCategories.length > maxLength) {
            formattedCategories = formattedCategories.slice(0, maxLength);
        } else if (formattedCategories.length < maxLength) {
            // pad with readable placeholders
            for (let i = formattedCategories.length; i < maxLength; i++) {
                formattedCategories.push(`Point ${i + 1}`);
            }
        }
        return {
            series: formattedSeries,
            labels: [],
            categories: formattedCategories
        };
    }
    init() {
        this.initFilters();
        // this.initSortable();
        this.initTooltips();
        this.updateDashboard();
    }
    renderTkDonut(elementId, data, labels, colors) {
        const el = document.getElementById(elementId);
        if (!el) return;
        const legendEl = document.getElementById(elementId.replace('-donut', '-legend'));
        const centerLabel = el.dataset.centerLabel || '';
        const series = (Array.isArray(data) ? data : []).map(v => Math.max(0, Number(v) || 0));
        const total = series.reduce((a, b) => a + b, 0);
        if (this.charts['#' + elementId]) { this.charts['#' + elementId].destroy(); }
        if (total === 0) {
            el.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;height:140px;color:#64748b;font-size:13px;">${el.dataset.emptyLabel || 'No data'}</div>`;
            if (legendEl) legendEl.innerHTML = '';
            return;
        }
        el.innerHTML = '';
        const palette = colors && colors.length ? colors : this.chartPalette;
        const chart = new ApexCharts(el, {
            series, labels,
            colors: palette,
            chart: { type: 'donut', height: 160, toolbar: { show: false } },
            plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: { show: true, label: centerLabel, fontSize: '11px', fontWeight: 600, formatter: () => total } } } } },
            dataLabels: { enabled: false },
            legend: { show: false },
            stroke: { width: 2 },
            tooltip: { y: { formatter: (val) => val } }
        });
        chart.render();
        this.charts['#' + elementId] = chart;
        if (legendEl) {
            legendEl.innerHTML = labels.map((lbl, i) => {
                const count = series[i] || 0;
                const pct = total > 0 ? ((count / total) * 100).toFixed(0) : 0;
                const color = palette[i % palette.length];
                return `<div class="tk-legend-row"><span class="tk-legend-dot" style="background:${color}"></span><span class="tk-legend-label">${lbl}</span><span class="tk-legend-val">${count}</span><span class="tk-legend-pct">${pct}%</span></div>`;
            }).join('');
        }
    }
    renderSparklines(trends) {
        if (!trends) return;
        const VB_W = 100;
        const VB_H = 28;
        const PAD_Y = 3;
        const TREND_KEY = {
            "projects-tile": "projects",
            "tasks-tile": "tasks",
            "users-tile": "users",
            "clients-tile": "clients",
            "meetings-tile": "meetings",
            "todos-tile": "todos",
        };

        const buildLinePath = (series) => {
            let data = [...series];
            if (data.length === 1) {
                data = [data[0], data[0]];
            }
            const n = data.length;
            const min = Math.min(...data);
            const max = Math.max(...data);
            const range = max - min || 1;
            const usableH = VB_H - PAD_Y * 2;
            let d = "";
            for (let i = 0; i < n; i++) {
                const x = (i / (n - 1)) * VB_W;
                const y = PAD_Y + (1 - (data[i] - min) / range) * usableH;
                d += (i === 0 ? "M" : " L") + Math.round(x * 100) / 100 + " " + Math.round(y * 100) / 100;
            }
            return d;
        };

        const formatDelta = (delta) => {
            const sign = delta > 0 ? "+" : delta < 0 ? "−" : "";
            return sign + Math.abs(Math.round(delta));
        };

        document.querySelectorAll(".tk-metric").forEach(metric => {
            const key = TREND_KEY[metric.id];
            if (key && Object.prototype.hasOwnProperty.call(trends, key)) {
                const series = trends[key];
                const sparkEl = metric.querySelector(".tk-metric-spark");
                const trendEl = metric.querySelector(".tk-metric-trend");

                if (!Array.isArray(series) || series.length === 0) {
                    if (sparkEl) sparkEl.innerHTML = "";
                    if (trendEl) trendEl.innerHTML = "";
                    return;
                }

                const delta = series[series.length - 1] - series[0];
                const dir = delta > 0 ? "up" : delta < 0 ? "down" : "flat";

                if (trendEl) {
                    trendEl.classList.remove("is-up", "is-down", "is-flat");
                    trendEl.classList.add("is-" + dir);
                    const arrow = dir === "down" ? "↓" : dir === "up" ? "↑" : "→";
                    trendEl.innerHTML = `<span class="tk-trend-arrow">${arrow}</span><span>${formatDelta(delta)}</span>`;
                }

                if (sparkEl) {
                    const d = buildLinePath(series);
                    sparkEl.innerHTML = `<svg viewBox="0 0 ${VB_W} ${VB_H}" preserveAspectRatio="none" focusable="false"><path class="tk-spark-line" d="${d}"></path></svg>`;
                }
            }
        });
    }
    renderPolarAreaChart(selector, data, colors, labels, label = "") {
        if (!document.querySelector(selector)) return;
        const { series, labels: formattedLabels } = this.formatDataForChart('polarArea', data, labels);
        const options = {
            series,
            labels: formattedLabels,
            colors: colors || this.chartPalette,
            chart: { type: 'polarArea', toolbar: { show: false } },
            stroke: { colors: ['#fff'] },
            fill: { opacity: 0.9 },
            dataLabels: { enabled: true, formatter: (val, opts) => opts.w.globals.series[opts.seriesIndex] },
            legend: { position: 'right', labels: { colors: this.colors.text } },
            yaxis: { show: false },
            responsive: [{ breakpoint: 480, options: { chart: { width: 350 }, legend: { position: 'bottom' } } }]
        };
        this.renderChartInstance(selector, options);
    }
    renderPieChart(selector, data, labels, colors = null) {
        if (!document.querySelector(selector)) return;
        const { series, labels: formattedLabels } = this.formatDataForChart('pie', data, labels);
        const options = {
            series,
            chart: { type: 'pie', height: 350, toolbar: { show: false } },
            labels: formattedLabels,
            colors: colors || this.chartPalette,
            dataLabels: { enabled: true, formatter: (val, opts) => opts.w.globals.series[opts.seriesIndex] },
            tooltip: { y: { formatter: (val, { seriesIndex }) => `${val} (${this.calculatePercentage(series)[seriesIndex]})` } },
            legend: { position: 'right', fontSize: '14px' },
            responsive: [{ breakpoint: 480, options: { chart: { width: 300 }, legend: { position: 'bottom' } } }]
        };
        this.renderChartInstance(selector, options);
    }
    renderDonutChart(selector, data, colors, labels, label = "") {
        if (!document.querySelector(selector)) return;
        const { series, labels: formattedLabels } = this.formatDataForChart('donut', data, labels);
        const options = {
            series,
            colors: colors || this.chartPalette,
            labels: formattedLabels,
            chart: { type: "donut", height: 250 },
            plotOptions: {
                pie: {
                    donut: {
                        size: "80%",
                        labels: {
                            show: true,
                            total: { show: true, label, fontSize: "16px", fontWeight: 500, formatter: () => series.reduce((a, b) => a + b, 0) }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: (val, { seriesIndex }) => `${val} (${this.calculatePercentage(series)[seriesIndex]})` } },
            legend: { position: "right", fontSize: "14px", markers: { radius: 12 } },
            responsive: [{ breakpoint: 480, options: { chart: { width: 180 } } }]
        };
        this.renderChartInstance(selector, options);
    }
    renderChartInstance(selector, options) {
        if (this.charts[selector]) this.charts[selector].destroy();
        this.charts[selector] = new ApexCharts(document.querySelector(selector), options);
        this.charts[selector].render();
    }
    updateDashboard() {
        const filters = this.getFilters();
        $.ajax({
            type: "POST",
            url: this.dashboardDataEndpoint,
            data: filters,
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            dataType: "JSON",
            success: (response) => {
                // Update tiles
                $('#projects-tile .count').text(response.projects_count || 0).show();
                $('#tasks-tile .count').text(response.tasks_count || 0).show();
                $('#users-tile .count').text(response.users_count || 0).show();
                $('#clients-tile .count').text(response.clients_count || 0).show();
                $('#meetings-tile .count').text(response.meetings_count || 0).show();
                $('#todos-tile .count').text(response.todos_count || 0).show();
                // Update tk-donut card totals
                $('#tk-project-total').text(response.projects_count || 0);
                $('#tk-task-total').text(response.tasks_count || 0);
                $('#tk-todo-total').text(response.todos_count || 0);
                // Update charts
                this.renderPolarAreaChart("#projectStatisticsChart", response.project_data || [], response.bg_colors || [], response.labels || [], label_total_projects);
                this.renderPieChart("#taskStatisticsChart", response.task_data || [], response.labels || [], response.bg_colors || []);
                this.renderDonutChart("#todoStatisticsChart", response.todo_data || [], [this.colors.pastelSuccess, this.colors.pastelDanger], ['Done', 'Pending'], label_total_todos);
                // Update combined tk bar chart
                const projectData = response.project_data || [];
                const taskData = response.task_data || [];
                const combinedLabels = response.labels || [];
                
                if (document.getElementById('tk-combined-bar-chart')) {
                    if (this.charts['#tk-combined-bar-chart']) {
                        this.charts['#tk-combined-bar-chart'].destroy();
                    }
                    document.getElementById('tk-combined-bar-chart').innerHTML = '';
                    
                    const options = {
                        series: [
                            { name: 'Projects', data: projectData },
                            { name: 'Tasks', data: taskData }
                        ],
                        chart: { 
                            type: 'bar', 
                            height: 320, 
                            toolbar: { show: false },
                            parentHeightOffset: 0,
                            fontFamily: 'inherit',
                            dropShadow: {
                                enabled: true,
                                top: 4,
                                left: 0,
                                blur: 10,
                                opacity: 0.05
                            }
                        },
                        colors: ['#6366F1', '#10B981'], // Indigo and Emerald
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '40%',
                                borderRadius: 5,
                                borderRadiusApplication: 'end',
                                dataLabels: { position: 'top' }
                            },
                        },
                        dataLabels: { 
                            enabled: true, 
                            offsetY: -20,
                            style: { fontSize: '11px', colors: ['#475569'], fontWeight: 600, fontFamily: 'inherit' },
                            formatter: function (val) { return val > 0 ? val : ''; }
                        },
                        stroke: { show: true, width: 3, colors: ['transparent'] },
                        xaxis: { 
                            categories: combinedLabels,
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                            labels: { style: { colors: '#64748b', fontSize: '12px', fontWeight: 500, fontFamily: 'inherit' } }
                        },
                        yaxis: { 
                            show: true,
                            tickAmount: 4,
                            labels: { 
                                style: { colors: '#64748b', fontSize: '11px', fontFamily: 'inherit' },
                                formatter: function (val) { return Math.round(val); }
                            }
                        },
                        grid: {
                            show: true,
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            xaxis: { lines: { show: false } },
                            yaxis: { lines: { show: true } },
                            padding: { top: 10, right: 0, bottom: 0, left: 10 }
                        },
                        fill: { 
                            type: 'gradient',
                            gradient: { 
                                shade: 'light', 
                                type: 'vertical', 
                                shadeIntensity: 0.3, 
                                gradientToColors: ['#8B5CF6', '#34D399'], // Violet and Mint
                                inverseColors: false, 
                                opacityFrom: 0.9, 
                                opacityTo: 0.45, 
                                stops: [0, 95, 100] 
                            }
                        },
                        legend: { 
                            position: 'top', 
                            horizontalAlign: 'right',
                            markers: { radius: 12 },
                            fontWeight: 500,
                            fontFamily: 'inherit',
                            fontSize: '12px',
                            itemMargin: { horizontal: 10, vertical: 0 }
                        },
                        tooltip: {
                            theme: 'light',
                            y: { formatter: function (val) { return val } },
                            marker: { show: true }
                        }
                    };
                    const chart = new ApexCharts(document.querySelector("#tk-combined-bar-chart"), options);
                    chart.render();
                    this.charts['#tk-combined-bar-chart'] = chart;
                }
                this.renderTkDonut('tk-todo-donut', response.todo_data || [], [
                    ($('#tk-todo-donut').data('label-done') || 'Completed'),
                    ($('#tk-todo-donut').data('label-pending') || 'Pending')
                ], [this.colors.pastelSuccess, this.colors.pastelDanger]);
                // Update status lists
                this.updateStatusList('#project-statistics .status-list', response.project_status_counts || {}, response.statuses || [], response.total_projects || 0, 'projects');
                this.updateStatusList('#task-statistics .status-list', response.task_status_counts || {}, response.statuses || [], response.total_tasks || 0, 'tasks');
                // Update todo list
                this.updateTodoList('#todos-overview .todo-list', response.todos || []);
                // Update timeline
                this.updateTimeline('#recent-activities .timeline', response.activities || []);
                 // Update selected users count
                 $('#selectedUsersCount').text(label_all_team_members_selected || 'All team members').show();
                 // Fetch income vs expense chart (no filters)
                 this.updateIncomeExpenseChart({});
                 // Render sparklines from trends data
                 this.renderSparklines(response.trends);
             },
             error: (xhr, status, error) => console.error("Dashboard Update Error:", error)
         });
     }
    updateStatusList(selector, statusCounts, statuses, totalCount, type) {
        const container = $(selector);
        container.html(''); // Clear existing content
        if (container.length) {
            // Add table-responsive and table structure
            container.append(`
            <div class="table-responsive tk-table">
                <table class="table table-sm mb-0">
                    <tbody>
        `);
            const tbody = container.find('tbody'); // Reference to the tbody for appending rows
            if (Object.keys(statusCounts).length && statuses.length) {
                statuses.forEach(status => {
                    const count = statusCounts[status.id] || 0;
                    const percentage = totalCount > 0 ? ((count / totalCount) * 100).toFixed(1) : 0;
                    tbody.append(`
                    <tr>
                        <td class="border-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="legend-dot bg-${status.color} me-2" style="width: 12px; height: 12px; border-radius: 50%;"></div>
                                <a href="${type === 'projects' ? '/projects/list' : '/tasks'}?status=${status.id}&status_ids[]=${status.id}"
   class="text-decoration-none text-dark fw-medium">
   ${status.title}
</a>
                            </div>
                        </td>
                        <td class="border-0 py-2 text-end">
                            <span class="fw-bold text-${status.color}">${count}</span>
                        </td>
                        <td class="border-0 py-2 text-end text-muted">
                            <small>${percentage}%</small>
                        </td>
                    </tr>
                `);
                });
                tbody.append(`
                <tr class="border-top">
                    <td class="pt-2 fw-bold">
                        <i class="bx bx-menu me-2"></i>${label_total}
                    </td>
                    <td class="pt-2 text-end fw-bold text-primary">${totalCount}</td>
                    <td class="pt-2 text-end text-muted">
                        <small>100%</small>
                    </td>
                </tr>
            `);
            } else {
                tbody.append(`
                <tr>
                    <td colspan="3" class="text-muted text-center">
                        ${label_no_data_available}
                    </td>
                </tr>
            `);
            }
            // Close table and tbody
            container.append(`
                    </tbody>
                </table>
            </div>
        `);
        } else {
            console.warn(`Container ${selector} not found`);
        }
    }
    updateTodoList(selector, todos) {
        const container = $(selector);
        container.html(''); // Clear existing content
        let html = '';
        if (container.length) {
            if (Array.isArray(todos) && todos.length > 0) {
                todos.forEach(todo => {
                    html += `
                    <li class="list-group-item d-flex align-items-center px-3 py-2 border-0">
                        <div class="me-3">
                            <div class="form-check mb-0">
                                <input type="checkbox"
                                       id="${todo.id}"
                                       onclick="update_status(this)"
                                       name="${todo.id}"
                                       class="form-check-input mt-0"
                                       ${todo.is_completed ? 'checked' : ''}>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <div class="text-body fw-bold ${todo.is_completed ? 'text-decoration-line-through text-muted' : ''}" id="${todo.id}_title" style="font-size: 13px; line-height: 1.2;">
                                        ${todo.title}
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">${todo.created_at}</small>
                                </div>
                                <div class="user-progress d-flex align-items-center gap-2">
                                    <a href="javascript:void(0);"
                                       class="edit-todo text-muted"
                                       data-bs-toggle="modal"
                                       data-bs-target="#edit_todo_modal"
                                       data-id="${todo.id}"
                                       title="${label_update}">
                                        <i class="bx bx-edit fs-5"></i>
                                    </a>
                                    <a href="javascript:void(0);"
                                       class="delete text-danger"
                                       data-id="${todo.id}"
                                       data-type="todos"
                                       title="${label_delete}">
                                        <i class="bx bx-trash fs-5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>`;
                });

            } else {
                html += `
                <div class="d-flex justify-content-center align-items-center text-muted p-3">
                    <span>${label_no_todos_found}</span>
                </div>`;
            }
            container.append(html);
        } else {
            console.warn(`Container ${selector} not found`);
        }
    }
    updateTimeline(selector, activities) {
        const container = $(selector);
        container.html('');
        if (activities.length) {
            activities.forEach(activity => {
                // Determine timeline point class based on activity type
                let timelinePointClass = 'timeline-point-primary';
                switch (activity.activity) {
                    case 'created':
                        timelinePointClass = 'timeline-point-success';
                        break;
                    case 'updated':
                        timelinePointClass = 'timeline-point-info';
                        break;
                    case 'deleted':
                        timelinePointClass = 'timeline-point-danger';
                        break;
                    case 'updated status':
                        timelinePointClass = 'timeline-point-warning';
                        break;
                }
                container.append(`
                <li class="timeline-item timeline-item-transparent">
                    <span class="timeline-point ${timelinePointClass}"></span>
                    <div class="timeline-event">
                        <div class="timeline-header d-flex justify-content-between align-items-center">
                            <h6 class="fw-semibold mb-1">${activity.message}</h6>
                            <small class="text-muted">${activity.created_at_diff}</small>
                        </div>
                        <div class="timeline-body">
                            <p class="text-muted">${activity.created_at_formatted}</p>
                        </div>
                    </div>
                </li>
            `);
            });
        } else {
            container.append(`
            <li class="timeline-item timeline-item-transparent text-center">
                <span class="timeline-point timeline-point-primary"></span>
                <div class="timeline-event">
                    <div class="timeline-header">
                        <h6 class="text-muted mb-0">${window.label_no_activities || 'No recent activities'}</h6>
                    </div>
                </div>
            </li>
        `);
        }
    }
    updateIncomeExpenseChart(filters) {
        const heroEl = document.getElementById('tk-hero-chart');
        if (!heroEl) return; // chart not on page (non-admin)

        $.ajax({
            type: "GET",
            url: this.invoicesReportEndpoint,
            data: filters,
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            dataType: "JSON",
            success: (response) => {
                const { invoices = [], expenses = [] } = response;

                const groupByDate = (data, dateField, amountField) => {
                    return data.reduce((acc, item) => {
                        const date = (item[dateField] || '').split(' ')[0] || '';
                        const amount = parseFloat((item[amountField] || '').toString().replace(/[^0-9.-]+/g, "")) || 0;
                        if (!date) return acc;
                        acc[date] = (acc[date] || 0) + amount;
                        return acc;
                    }, {});
                };

                const invoicesByDate = groupByDate(invoices, 'from_date', 'amount');
                const expensesByDate = groupByDate(expenses, 'expense_date', 'amount');

                const parseDMY = (d) => {
                    if (!d) return new Date(0);
                    const parts = d.split('-');
                    if (parts.length !== 3) return new Date(d);
                    const [dd, mm, yyyy] = parts.map(p => Number(p));
                    return new Date(yyyy, mm - 1, dd);
                };

                const allDates = [...new Set([...Object.keys(invoicesByDate), ...Object.keys(expensesByDate)])];
                allDates.sort((a, b) => parseDMY(a) - parseDMY(b));

                const incomeData  = allDates.map(d => invoicesByDate[d] || 0);
                const expenseData = allDates.map(d => expensesByDate[d] || 0);

                const labelIncome   = heroEl.dataset.labelIncome   || 'Income';
                const labelExpense  = heroEl.dataset.labelExpense   || 'Expenses';
                const emptyLabel    = heroEl.dataset.emptyLabel     || 'No data available';

                // Destroy previous instance
                if (this.charts['#tk-hero-chart']) {
                    this.charts['#tk-hero-chart'].destroy();
                }

                if (allDates.length === 0) {
                    heroEl.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;height:200px;color:#64748b;font-size:14px;">${emptyLabel}</div>`;
                    return;
                }

                heroEl.innerHTML = '';

                const options = {
                    series: [
                        { name: labelIncome,  data: incomeData  },
                        { name: labelExpense, data: expenseData }
                    ],
                    chart: {
                        type: 'area',
                        height: 220,
                        toolbar: { show: false },
                        zoom: { enabled: false }
                    },
                    colors: ['var(--signal, #22C55E)', this.colors.secondary],
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] }
                    },
                    stroke: { curve: 'smooth', width: 2 },
                    xaxis: {
                        categories: allDates,
                        labels: { style: { colors: this.colors.text, fontSize: '11px' }, rotate: -30 },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: { labels: { style: { colors: this.colors.text, fontSize: '11px' } } },
                    grid: { borderColor: this.colors.grid, strokeDashArray: 4 },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px' },
                    tooltip: { y: { formatter: (val) => val.toLocaleString() } }
                };

                const chart = new ApexCharts(heroEl, options);
                chart.render();
                this.charts['#tk-hero-chart'] = chart;

                // Wire up the segment toggle (Both / Income / Expense)
                document.querySelectorAll('.tk-seg-btn[data-chart="hero"]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.tk-seg-btn[data-chart="hero"]').forEach(b => {
                            b.classList.remove('on');
                            b.setAttribute('aria-checked', 'false');
                        });
                        btn.classList.add('on');
                        btn.setAttribute('aria-checked', 'true');
                        const val = btn.dataset.value;
                        if (val === 'income')        chart.updateSeries([{ name: labelIncome,  data: incomeData  }]);
                        else if (val === 'expense')  chart.updateSeries([{ name: labelExpense, data: expenseData }]);
                        else                          chart.updateSeries([{ name: labelIncome, data: incomeData }, { name: labelExpense, data: expenseData }]);
                    });
                });
            },
            error: (xhr, status, error) => console.error("Income vs Expense Chart Update Error:", error)
        });
    }
    getFilters() {
        // No filters — always fetch all data
        return { start_date: "", end_date: "", user_ids: [] };
    }
    initFilters() {
        // Filters removed — dashboard always shows all data
    }
    initSortable() {
        const $container = $("#dashboard-items");
        if (!$container.length) return;
        Sortable.create($container[0], {
            animation: 150,
            ghostClass: "sortable-ghost",
            handle: ".draggable-item",
            onEnd: () => this.saveDashboardOrder()
        });
        this.loadDashboardOrder();
    }
    saveDashboardOrder() {
        const order = [];
        $("#dashboard-items .draggable-item").each(function (index) {
            order.push({ id: $(this).data("id"), height: $(this).outerHeight(), width: $(this).outerWidth(), position: index + 1 });
        });
        localStorage.setItem("dashboardOrder", JSON.stringify(order));
    }
    loadDashboardOrder() {
        const savedOrder = localStorage.getItem("dashboardOrder");
        if (!savedOrder) return;
        const order = JSON.parse(savedOrder);
        order.forEach(item => {
            const $item = $(`#dashboard-items .draggable-item[data-id="${item.id}"]`);
            $("#dashboard-items").append($item);
        });
    }
    initTooltips() {
        $(".draggable-item").each(function () {
            $(this).addClass("position-relative").append(`
                <span class="drag-tooltip-icon end-0 fs-4 me-4 mt-2 position-absolute top-0"
                      data-bs-toggle="tooltip" title="${label_drag_to_reorder}">
                    <i class="bx bx-move text-muted small"></i>
                </span>
            `);
        });
        $("[data-bs-toggle='tooltip']").tooltip();
    }
    calculatePercentage(data) {
        const total = data.reduce((a, b) => a + b, 0);
        return data.map(value => ((value / total) * 100).toFixed(2) + "%");
    }
}
$(document).ready(() => {
    const dashboard = new DashboardManager();
    dashboard.init();
});