$(function () {
  let dailyChartInstance = null;
  let trendChartInstance = null;
  let avgProductiveChartInstance = null;
  let userInsightChartInstance = null;
  let dailyBreakdownExportData = null;
  let dailyBreakdownCsvOverrideBound = false;
  let currentData = null;
  let allUsers = [];

  function decimalToHHMM(decimal) {
    if (decimal === null || decimal === undefined || isNaN(decimal)) {
      return "00:00";
    }
    const totalMinutes = Math.round(Number(decimal) * 60);
    const sign = totalMinutes < 0 ? "-" : "";
    const minutesAbs = Math.abs(totalMinutes);
    const hours = Math.floor(minutesAbs / 60);
    const minutes = minutesAbs % 60;
    return `${sign}${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}`;
  }

  function exportDailyBreakdownCSV() {
    if (
      !dailyBreakdownExportData ||
      !dailyBreakdownExportData.categories ||
      !dailyBreakdownExportData.series
    ) {
      console.warn("No Daily Breakdown data available for CSV export");
      return;
    }

    const { categories, series } = dailyBreakdownExportData;
    const headers = ["category", ...series.map((item) => item.name)];
    const rows = [headers.join(",")];

    categories.forEach((category, index) => {
      const values = series.map((item) => decimalToHHMM(item.data[index] || 0));
      rows.push([category, ...values].join(","));
    });

    const csvContent = `\uFEFF${rows.join("\n")}`;
    const blob = new Blob([csvContent], {
      type: "text/csv;charset=utf-8;",
    });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `daily_breakdown_${moment().format("YYYY-MM-DD")}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
  }

  function bindDailyBreakdownCsvOverride() {
    if (dailyBreakdownCsvOverrideBound) {
      return;
    }

    const chartContainer = document.querySelector("#dailyBreakdownChart");
    if (!chartContainer) {
      return;
    }

    // Intercept Apex CSV click for this chart and export HH:MM values.
    chartContainer.addEventListener(
      "click",
      function (event) {
        const menuItem = event.target.closest(
          ".apexcharts-menu-item.exportCSV",
        );
        if (!menuItem) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === "function") {
          event.stopImmediatePropagation();
        }

        exportDailyBreakdownCSV();
      },
      true,
    );

    dailyBreakdownCsvOverrideBound = true;
  }

  // Initialize enhanced UI components
  initializeEnhancedUI();

  $("#userFilter").select2({
    placeholder: "Select team member(s)...",
    allowClear: true,
    width: "100%",
    closeOnSelect: false,
  });

  $("#daterange").daterangepicker(
    {
      startDate: moment().subtract(6, "days"),
      endDate: moment(),
      opens: "right",
      ranges: {
        Today: [moment(), moment()],
        Yesterday: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "Last 7 Days": [moment().subtract(6, "days"), moment()],
        "Last 30 Days": [moment().subtract(29, "days"), moment()],
        "This Month": [moment().startOf("month"), moment().endOf("month")],
        "Last Month": [
          moment().subtract(1, "month").startOf("month"),
          moment().subtract(1, "month").endOf("month"),
        ],
      },
    },
    loadDashboard,
  );

  $("#daterange").on("apply.daterangepicker", loadDashboard);
  $("#userFilter").on("change", function () {
    updateSelectedUsersCount();
    loadDashboard();
  });

  // Load dashboard on initialization
  loadDashboard();

  function initializeEnhancedUI() {
    // Check if welcome guide should be shown
    if (!localStorage.getItem("dashboardGuideShown")) {
      $("#welcomeGuide").show();
    } else {
      $("#welcomeGuide").hide();
    }

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function loadDashboard() {
    showLoadingState();

    let range = $("#daterange").data("daterangepicker");
    let start = range.startDate.format("YYYY-MM-DD");
    let end = range.endDate.format("YYYY-MM-DD");
    let comparisonEnd = range.startDate.clone().subtract(1, "days");
    let comparisonStart = comparisonEnd
      .clone()
      .subtract(range.endDate.diff(range.startDate, "days"), "days");
    let userId = $("#userFilter").val();

    fetchDashboard(
      start,
      end,
      comparisonStart.format("YYYY-MM-DD"),
      comparisonEnd.format("YYYY-MM-DD"),
      userId,
    );
  }

  function showLoadingState() {
    $("#loadingState").removeClass("d-none");
    $("#noDataState").addClass("d-none");
    $("#chartsSection").hide();
    $("#dashboardMetrics").hide();
  }

  function hideLoadingState() {
    $("#loadingState").addClass("d-none");
  }

  function showNoDataState() {
    $("#noDataState").removeClass("d-none");
    $("#chartsSection").hide();
    $("#dashboardMetrics").hide();
  }

  function showDataState() {
    $("#noDataState").addClass("d-none");
    $("#chartsSection").show();
    $("#dashboardMetrics").show();
  }

  function fetchDashboard(
    startDate,
    endDate,
    comparisonStartDate,
    comparisonEndDate,
    userId,
  ) {
    $.ajax({
      url: dashboardDataRoute,
      type: "GET",
      data: {
        start_date: startDate,
        end_date: endDate,
        comparison_start_date: comparisonStartDate,
        comparison_end_date: comparisonEndDate,
        user_id: userId,
      },
      success: function (response) {
        hideLoadingState();

        const data = response.data;
        const metrics = data.current_period.metrics;
        const percentage_changes = data.percentage_changes || {};
        const absolute_changes = data.absolute_changes || {};
        const daily_breakdown = data.current_period.daily_breakdown || [];
        const employees = response.employees || [];

        // Store current data for export functionality
        currentData = response;
        allUsers = employees;

        // Check if there's data to display
        if (!daily_breakdown.length && Object.keys(metrics).length === 0) {
          showNoDataState();
          return;
        }

        showDataState();

        // Populate user filter if empty
        if ($("#userFilter option").length <= 1) {
          $("#userFilter")
            .empty()
            .append('<option value="">All team members</option>');
          employees.forEach((emp) => {
            $("#userFilter").append(
              `<option value="${emp.id}">${emp.name}</option>`,
            );
          });
        }

        updateSelectedUsersCount();
        renderMetricsCards(metrics, percentage_changes, absolute_changes);
        renderDailyBreakdownChart(daily_breakdown);
        renderWorkingHoursTrend(daily_breakdown);
        renderTopProductiveUsers(data.top_productive_users || []);
        renderAverageProductiveHoursPerUserChart(
          response.data.average_productive_hours_per_user,
        );
        updateTeamStatistics(response.data.average_productive_hours_per_user);
      },
      error: function () {
        hideLoadingState();
        showErrorToast("Error loading dashboard data. Please try again.");
      },
    });
  }

  function renderMetricsCards(metrics, percentage_changes, absolute_changes) {
    $("#dashboardMetrics").empty();

    const metricConfig = {
      work_time: {
        icon: "bx-time",
        color: "primary",
        title: "Total Work Time",
        description: "Combined active and manual time",
      },
      active_time: {
        icon: "bx-bolt-circle",
        color: "success",
        title: "Active Time",
        description: "Automatically tracked time",
      },
      manual_time: {
        icon: "bxs-keyboard",
        color: "info",
        title: "Manual Time",
        description: "Manually entered time",
      },
      break_time: {
        icon: "bx-coffee",
        color: "warning",
        title: "Break Time",
        description: "Recorded break periods",
      },
      idle_time: {
        icon: "bx-pause-circle",
        color: "secondary",
        title: "Idle Time",
        description: "Periods of inactivity",
      },
      productive_time: {
        icon: "bx-check-circle",
        color: "success",
        title: "Productive Time",
        description: "Active work time",
      },
      unproductive_time: {
        icon: "bx-x-circle",
        color: "danger",
        title: "Unproductive Time",
        description: "Non-productive activities",
      },
      utilization: {
        icon: "bx-tachometer",
        color: "primary",
        title: "Utilization Rate",
        description: "Productivity efficiency",
      },
      neutral_time: {
        icon: "bx-adjust",
        color: "secondary",
        title: "Neutral Time",
        description: "Uncategorized time",
      },
      manual_processing_time: {
        icon: "bx-time",
        color: "info",
        title: "Manual Processing Time",
        description: "Time spent on manual processing",
      },
    };

    $.each(metrics, function (key, value) {
      const config = metricConfig[key] || {
        icon: "bx-chart",
        color: "primary",
        title: key.replace(/_/g, " "),
        description: "",
      };

      const percentChange = percentage_changes[key]
        ? percentage_changes[key].display
        : "";
      const percentDir = percentage_changes[key]
        ? percentage_changes[key].direction
        : "";
      const absChange = absolute_changes[key]
        ? absolute_changes[key].display
        : "";
      const percentColor =
        percentDir === "increase"
          ? "text-success"
          : percentDir === "decrease"
            ? "text-danger"
            : "text-muted";
      const trendIcon =
        percentDir === "increase"
          ? "bx-up-arrow-alt"
          : percentDir === "decrease"
            ? "bx-down-arrow-alt"
            : "bx-minus";
      const valueDisplay = value.display || "0h 0m";
      value.display = valueDisplay;
      const progressColor =
        percentDir === "increase"
          ? "success"
          : percentDir === "decrease"
            ? "danger"
            : "secondary";

      // Clean percent for bar
      let progressPercent = "0";
      if (percentChange) {
        progressPercent = percentChange.replace(/[+%-]/g, "").trim();
      }
      const progressPercentValue = Math.min(
        Math.max(parseFloat(progressPercent) || 0, 0),
        100,
      );

      const card = `
            <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-shrink-0">
                                <span class="bg-label-${config.color} bg-opacity-10 rounded p-1 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="bx ${config.icon} text-${config.color} fs-5"></i>
                                </span>
                            </div>
                            <div class="ms-2">
                                <p class="mb-0 text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">${config.title}</p>
                            </div>
                        </div>
                        <h5 class="mb-1 fw-semibold">${value.display}</h5>
                        ${
                          percentChange
                            ? `
                            <small class="${percentColor} fw-medium d-flex align-items-center">
                                <i class="bx ${trendIcon} me-1"></i> ${percentChange}
                                ${absChange ? `<span class="text-muted ms-2">| ${absChange}</span>` : ""}
                            </small>
                        `
                            : ""
                        }
                        <div class="progress progress-sm mt-2" style="height: 4px;">
                            <div class="progress-bar progress-bar-striped bg-${progressColor}" role="progressbar" style="width: ${progressPercentValue}%;" aria-valuenow="${progressPercentValue}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span>${config.description || "This month"}</span>
                            <span>${progressPercentValue}%</span>
                        </div>
                    </div>
                </div>
            </div>

            `;

      $("#dashboardMetrics").append(card);
    });

    // Reinitialize tooltips for new elements
    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function renderTopProductiveUsers(topUsers) {
    if (!topUsers.length) {
      $("#topProductiveUsers").html(
        '<div class="text-center text-muted py-4"><i class="bx bx-user-x" style="font-size: 2rem;"></i><p class="mt-2">No user data available</p></div>',
      );
      return;
    }

    $("#topUsersCount").text(`Top ${topUsers.length}`);

    let productiveTable = `
            <div class="">
                <table class="table ">
                    <thead class="table-primary">
                        <tr>
                            <th scope="col" class="text-center">#</th>
                            <th scope="col">Team Member</th>
                            <th scope="col" data-bs-toggle="tooltip" title="Total productive hours (active + manual time)">
                                Productive Time
                                <i class="bx bx-info-circle text-muted ms-1"></i>
                            </th>
                            <th scope="col" data-bs-toggle="tooltip" title="Productivity efficiency percentage">
                                Utilization
                                <i class="bx bx-info-circle text-muted ms-1"></i>
                            </th>
                            <th scope="col" class="text-center" data-bs-toggle="tooltip" title="7-day productivity trend">
                                Trend
                                <i class="bx bx-info-circle text-muted ms-1"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        ${topUsers
                          .map(
                            (user, idx) => `
                            <tr class="cursor-pointer top-user-row hover-highlight" data-user-id="${user.user_id}"
                                data-bs-toggle="tooltip" data-bs-placement="left" title="Click to view detailed insights for ${user.name}">
                                <th scope="row" class="text-center">
                                    <span class="badge ${idx < 3 ? "bg-warning" : "bg-light text-dark"}">${idx + 1}</span>
                                </th>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar bg-primary text-white rounded-circle me-2 d-inline-flex align-items-center justify-content-center"
                                              style="width:36px;height:36px;font-size:0.9rem;">${user.initials}</span>
                                        <div>
                                            <span class="fw-semibold">${user.name}</span>
                                            <br><small class="text-muted">ID: ${user.user_id}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">${user.productive_display}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-semibold me-2">${user.utilization_display}</span>
                                        <div class="progress" style="width: 60px; height: 6px;">
                                            <div class="progress-bar ${getUtilizationColor(parseFloat(user.utilization_display))}"
                                                 style="width: ${user.utilization_display}"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div id="sparkline-${user.user_id}" style="height:40px;width:100px;" class="mx-auto"></div>
                                </td>
                            </tr>
                        `,
                          )
                          .join("")}
                    </tbody>
                </table>
            </div>
        `;

    if (topUsers.length > 5) {
      $("#viewAllUsersBtn").removeClass("d-none");
    } else {
      $("#viewAllUsersBtn").addClass("d-none");
    }

    $("#topProductiveUsers").empty().append(productiveTable);

    // Render sparklines for each user with enhanced tooltips
    topUsers.forEach((user) => {
      let options = {
        chart: {
          type: "area",
          height: 40,
          sparkline: { enabled: true },
          animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
          },
        },
        xaxis: { crosshairs: { width: 1 } },
        stroke: { curve: "smooth", width: 2 },
        fill: {
          type: "gradient",
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.1,
          },
        },
        tooltip: {
          enabled: true,
          custom: ({ series, seriesIndex, dataPointIndex }) => {
            const dailyBreakdown =
              currentData?.data?.current_period?.daily_breakdown || [];
            const dayData = dailyBreakdown[dataPointIndex];
            return `<div class="custom-tooltip p-2 bg-dark text-white rounded shadow">
                            <strong>${user.name}</strong><br>
                            <small class="text-muted">${dayData ? dayData.day_name : "N/A"}</small><br>
                            Productive Time: <strong class="text-success">${user.productive_display}</strong><br>
                            Utilization: <strong class="text-info">${user.utilization_display}</strong><br>
                            Daily Minutes: <strong>${series[seriesIndex][dataPointIndex]} min</strong>
                        </div>`;
          },
        },
        colors: ["#00E396"],
        series: [{ data: user.daily_productive_minutes || [] }],
      };

      const chartElement = document.querySelector(`#sparkline-${user.user_id}`);
      if (chartElement) {
        let chart = new ApexCharts(chartElement, options);
        chart.render();
      }
    });

    // Reinitialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function getUtilizationColor(utilization) {
    if (utilization >= 80) return "bg-success";
    if (utilization >= 60) return "bg-warning";
    return "bg-danger";
  }

  function updateTeamStatistics(userData) {
    if (!userData || !userData.length) {
      $("#teamAverage, #highestAverage, #lowestAverage").text("--");
      return;
    }

    const hours = userData.map((user) => user.average_productive_hours);
    const teamAvg = (hours.reduce((a, b) => a + b, 0) / hours.length).toFixed(
      1,
    );
    const highest = Math.max(...hours).toFixed(1);
    const lowest = Math.min(...hours).toFixed(1);

    $("#teamAverage").text(`${teamAvg}h`);
    $("#highestAverage").text(`${highest}h`);
    $("#lowestAverage").text(`${lowest}h`);
  }

  function updateSelectedUsersCount() {
    const selectedCount = $("#userFilter").val()?.length || 0;
    const totalUsers = allUsers.length;

    if (selectedCount === 0) {
      $("#selectedUsersCount").text("All team members selected");
    } else {
      $("#selectedUsersCount").text(
        `${selectedCount} of ${totalUsers} members selected`,
      );
    }
  }

  function renderDailyBreakdownChart(dailyData) {
    if (!dailyData.length) {
      $("#dailyBreakdownChart").html(
        '<div class="text-center text-muted py-4"><i class="bx bx-bar-chart" style="font-size: 2rem;"></i><p class="mt-2">No data available for chart</p></div>',
      );
      return;
    }

    let categories = dailyData.map((item) => item.day_name);
    let activeTimes = dailyData.map((item) => item.active_time / 60);
    let manualTimes = dailyData.map((item) => item.manual_time / 60);
    let breakTimes = dailyData.map((item) => item.break_time / 60);
    let idleTimes = dailyData.map((item) => item.idle_time / 60);
    let manualProcessingTimes = dailyData.map(
      (item) => (item.manual_processing_time || 0) / 60,
    );

    const dailyBreakdownSeries = [
      { name: "Active Time", data: activeTimes },
      { name: "Manual Time", data: manualTimes },
      { name: "Break Time", data: breakTimes },
      { name: "Idle Time", data: idleTimes },
      { name: "Manual Processing Time", data: manualProcessingTimes },
    ];

    dailyBreakdownExportData = {
      categories: categories,
      series: dailyBreakdownSeries,
    };

    bindDailyBreakdownCsvOverride();

    let options = {
      chart: {
        type: "bar",
        height: 400,
        stacked: true,
        toolbar: {
          show: true,
          tools: {
            download: true,
            pan: false,
            reset: true,
            zoom: true,
          },
        },
        animations: { enabled: true },
      },
      plotOptions: {
        bar: {
          borderRadius: 4,
          dataLabels: { position: "center" },
        },
      },
      xaxis: {
        categories: categories,
        title: { text: "Days", style: { fontWeight: 600 } },
      },
      yaxis: {
        title: { text: "Hours", style: { fontWeight: 600 } },
        labels: {
          formatter: function (val) {
            return decimalToHHMM(val);
          },
        },
      },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (val, { seriesIndex, dataPointIndex }) {
            return decimalToHHMM(val);
          },
        },
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return decimalToHHMM(val);
        },
        style: { fontWeight: 600 },
      },
      legend: {
        position: "top",
        horizontalAlign: "center",
        floating: false,
        fontSize: "13px",
        markers: { width: 12, height: 12, radius: 2 },
      },
      colors: ["#00E396", "#775DD0", "#FEB019", "#FF4560", "#957df5"],
      series: dailyBreakdownSeries,
    };

    if (dailyChartInstance) {
      dailyChartInstance.updateOptions(options);
    } else {
      dailyChartInstance = new ApexCharts(
        document.querySelector("#dailyBreakdownChart"),
        options,
      );
      dailyChartInstance.render();
    }
  }

  function renderWorkingHoursTrend(dailyData) {
    if (!dailyData.length) {
      $("#workingHoursTrendChart").html(
        '<div class="text-center text-muted py-4"><i class="bx bx-line-chart" style="font-size: 2rem;"></i><p class="mt-2">No data available for chart</p></div>',
      );
      return;
    }

    let categories = dailyData.map((item) => item.day_name);
    let workTimes = dailyData.map((item) => {
      let total =
        (item.active_time +
          item.manual_time +
          item.break_time +
          item.idle_time) /
        60;
      return total;
    });
    let activeTimes = dailyData.map((item) => item.active_time / 60);
    let manualTimes = dailyData.map((item) => item.manual_time / 60);
    let manualProcessingTimes = dailyData.map(
      (item) => (item.manual_processing_time || 0) / 60,
    );

    let options = {
      chart: {
        type: "area",
        height: 350,
        zoom: { enabled: true },
        toolbar: {
          show: true,
          tools: {
            download: true,
            pan: true,
            reset: true,
            zoom: true,
          },
        },
        animations: { enabled: true },
      },
      stroke: { curve: "smooth", width: [3, 2, 2] },
      fill: {
        type: ["gradient", "solid", "solid"],
        gradient: {
          shadeIntensity: 1,
          type: "vertical",
          opacityFrom: 0.5,
          opacityTo: 0.1,
        },
      },
      xaxis: {
        categories: categories,
        title: { text: "Days", style: { fontWeight: 600 } },
      },
      yaxis: {
        title: { text: "Hours", style: { fontWeight: 600 } },
        labels: {
          formatter: function (val) {
            return decimalToHHMM(val);
          },
        },
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return decimalToHHMM(val);
        },
        // style: { fontWeight: 600 },
        // background: {
        //     enabled: true,
        //     foreColor: '#000',
        //     padding: 4,
        //     borderRadius: 4,
        //     opacity: 0.8
        // }
      },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (val) {
            return decimalToHHMM(val);
          },
        },
      },
      legend: {
        position: "top",
        horizontalAlign: "left",
        fontSize: "13px",
      },
      series: [
        { name: "Total Work Hours", data: workTimes },
        { name: "Active Hours", data: activeTimes },
        { name: "Manual Hours", data: manualTimes },
        { name: "Manual Processing Time", data: manualProcessingTimes },
      ],
      colors: ["#008FFB", "#00E396", "#775DD0", "#957df5"],
    };

    if (trendChartInstance) {
      trendChartInstance.updateOptions(options);
    } else {
      trendChartInstance = new ApexCharts(
        document.querySelector("#workingHoursTrendChart"),
        options,
      );
      trendChartInstance.render();
    }
  }

  function renderAverageProductiveHoursPerUserChart(data) {
    if (!data || !data.length) {
      $("#avgProductiveHoursChart").html(
        '<div class="text-center text-muted py-4"><i class="bx bx-bar-chart-horizontal" style="font-size: 2rem;"></i><p class="mt-2">No user data available</p></div>',
      );
      return;
    }

    const seriesData = data.map((user) => ({
      x: user.name,
      y: Number(user.average_productive_hours || 0),
    }));

    let options = {
      chart: {
        type: "bar",
        height: 400,
        toolbar: { show: true },
      },
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 4,
          dataLabels: { position: "right" },
        },
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return decimalToHHMM(val);
        },
        style: { fontWeight: 600 },
      },
      xaxis: {
        title: {
          text: "Average Hours per Day",
          style: { fontWeight: 600 },
        },
        labels: {
          formatter: function (val) {
            return decimalToHHMM(val);
          },
        },
      },
      yaxis: {
        labels: {
          style: { fontWeight: 600 },
        },
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return `${decimalToHHMM(val)} average per day`;
          },
        },
      },
      colors: ["#00E396"],
      series: [
        {
          name: "Avg Productive Hours",
          data: seriesData,
        },
      ],
    };

    if (avgProductiveChartInstance) {
      avgProductiveChartInstance.updateOptions(options);
    } else {
      avgProductiveChartInstance = new ApexCharts(
        document.querySelector("#avgProductiveHoursChart"),
        options,
      );
      avgProductiveChartInstance.render();
    }
  }

  // Enhanced user insights modal
  $(document).on("click", ".top-user-row", function () {
    let userId = $(this).data("user-id");
    let userName = $(this).find(".fw-semibold").text();
    let range = $("#daterange").data("daterangepicker");
    let start = range.startDate.format("YYYY-MM-DD");
    let end = range.endDate.format("YYYY-MM-DD");

    $("#userInsightsModalLabel").html(
      `<i class="bx bx-user-circle me-2"></i>User Insights: ${userName}`,
    );
    $("#modalLoading").show();
    $("#userInsightsContent").hide();

    const modal = new bootstrap.Modal(
      document.getElementById("userInsightsModal"),
    );
    modal.show();

    $.ajax({
      url: dashboardDataRoute,
      type: "GET",
      data: {
        start_date: start,
        end_date: end,
        user_id: [userId],
      },
      success: function (response) {
        console.log(response);

        $("#modalLoading").hide();
        $("#userInsightsContent").show();

        const userMetrics = response.data.current_period.metrics;
        const dailyBreakdown = response.data.current_period.daily_breakdown;
        console.log("userMetrics", userMetrics);

        let insightsHtml = `
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <i class="bx bx-time text-primary mb-2" style="font-size: 2rem;"></i>
                                    <h5 class="text-primary">${userMetrics.work_time.display}</h5>
                                    <small class="text-muted">Total Work Time</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="bx bx-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                                    <h5 class="text-success">${userMetrics.productive_time.display}</h5>
                                    <small class="text-muted">Productive Time</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <i class="bx bx-x-circle text-danger mb-2" style="font-size: 2rem;"></i>
                                    <h5 class="text-danger">${userMetrics.unproductive_time.display}</h5>
                                    <small class="text-muted">Unproductive Time</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="bx bx-tachometer text-info mb-2" style="font-size: 2rem;"></i>
                                    <h5 class="text-info">${userMetrics.utilization.display}</h5>
                                    <small class="text-muted">Utilization Rate</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-line-chart me-2"></i>Daily Work Hours Trend
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="userInsightsTrendChart" style="height:300px;"></div>
                        </div>
                    </div>
                `;

        $("#userInsightsContent").html(insightsHtml);

        let categories = dailyBreakdown.map((item) => item.day_name);
        let workTimes = dailyBreakdown.map((item) => {
          let total =
            (item.active_time +
              item.manual_time +
              item.break_time +
              item.idle_time +
              (item.manual_processing_time || 0)) /
            60;
          return total;
        });
        let productiveTimes = dailyBreakdown.map((item) => {
          let total = (item.active_time + item.manual_time) / 60;
          return total;
        });

        let options = {
          chart: {
            type: "line",
            height: 300,
            toolbar: { show: true },
          },
          stroke: { curve: "smooth", width: [3, 2] },
          xaxis: {
            categories: categories,
            title: { text: "Day", style: { fontWeight: 600 } },
          },
          yaxis: {
            title: { text: "Hours", style: { fontWeight: 600 } },
            labels: {
              formatter: function (val) {
                return decimalToHHMM(val);
              },
            },
          },
          tooltip: {
            shared: true,
            intersect: false,
            y: {
              formatter: function (val) {
                return decimalToHHMM(val);
              },
            },
          },
          legend: {
            position: "top",
            horizontalAlign: "center",
            fontSize: "13px",
          },
          colors: ["#008FFB", "#00E396"],
          series: [
            { name: "Total Work Hours", data: workTimes },
            { name: "Productive Hours", data: productiveTimes },
          ],
        };

        if (userInsightChartInstance) {
          userInsightChartInstance.destroy();
          userInsightChartInstance = null;
        }
        userInsightChartInstance = new ApexCharts(
          document.querySelector("#userInsightsTrendChart"),
          options,
        );
        userInsightChartInstance.render();
      },
      error: function () {
        $("#modalLoading").hide();
        $("#userInsightsContent").html(
          '<div class="text-danger text-center py-4"><i class="bx bx-error-circle" style="font-size:2rem;"></i><p class="mt-2">Failed to load user insights. Please try again.</p></div>',
        );
      },
    });
  });

  // ========================
  // ENHANCED FUNCTIONALITY
  // ========================

  // Welcome guide dismissal
  function dismissGuide() {
    $("#welcomeGuide").hide();
    localStorage.setItem("dashboardGuideShown", "true");
  }

  // Quick date range selection
  function setDateRange(period) {
    let startDate, endDate;

    switch (period) {
      case "today":
        startDate = endDate = moment();
        break;
      case "yesterday":
        startDate = endDate = moment().subtract(1, "days");
        break;
      case "last7days":
        startDate = moment().subtract(6, "days");
        endDate = moment();
        break;
      case "last30days":
        startDate = moment().subtract(29, "days");
        endDate = moment();
        break;
      case "thismonth":
        startDate = moment().startOf("month");
        endDate = moment().endOf("month");
        break;
      case "lastmonth":
        startDate = moment().subtract(1, "month").startOf("month");
        endDate = moment().subtract(1, "month").endOf("month");
        break;
      default:
        console.warn("Unknown period:", period);
        return;
    }

    $("#daterange").data("daterangepicker").setStartDate(startDate);
    $("#daterange").data("daterangepicker").setEndDate(endDate);
    loadDashboard();
  }

  // Select all users in filter
  function selectAllUsers() {
    if (allUsers.length > 0) {
      const allUserIds = allUsers.map((user) => user.id.toString());
      $("#userFilter").val(allUserIds).trigger("change");
    }
  }

  // Clear user selection
  function clearUserSelection() {
    $("#userFilter").val(null).trigger("change");
  }

  // Reset all filters to default
  function resetFilters() {
    // Reset date range to last 7 days
    $("#daterange")
      .data("daterangepicker")
      .setStartDate(moment().subtract(6, "days"));
    $("#daterange").data("daterangepicker").setEndDate(moment());

    // Clear user selection
    $("#userFilter").val(null).trigger("change");

    // Reload dashboard
    loadDashboard();
  }

  // Export chart functionality
  function exportChart(chartId) {
    let chartInstance = null;

    switch (chartId) {
      case "dailyBreakdown":
        chartInstance = dailyChartInstance;
        break;
      case "trend":
        chartInstance = trendChartInstance;
        break;
      case "averageProductiveHours":
        chartInstance = avgProductiveChartInstance;
        break;
      case "userInsights":
        chartInstance = userInsightChartInstance;
        break;
      case "workingHoursTrend":
        chartInstance = trendChartInstance;
        break;
      default:
        console.warn("Unknown chart ID:", chartId);
        return;
    }

    if (chartInstance) {
      chartInstance.dataURI().then(({ imgURI }) => {
        const link = document.createElement("a");
        link.download = `${chartId}_chart_${moment().format("YYYY-MM-DD")}.png`;
        link.href = imgURI;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      });
    }
  }

  // Toggle chart view (bar/line/area)
  function toggleChartView(chartId) {
    let chartInstance = null;
    let currentType = "";

    switch (chartId) {
      case "dailyBreakdown":
        chartInstance = dailyChartInstance;
        currentType = chartInstance?.w?.config?.chart?.type || "pie";
        break;
      case "workingHoursTrend":
        chartInstance = trendChartInstance;
        currentType = chartInstance?.w?.config?.chart?.type || "area";
        break;
      case "avgProductive":
        chartInstance = avgProductiveChartInstance;
        currentType = chartInstance?.w?.config?.chart?.type || "bar";
        break;
      default:
        console.warn("Unknown chart ID:", chartId);
        return;
    }

    if (chartInstance) {
      const newType =
        currentType === "bar"
          ? "line"
          : currentType === "line"
            ? "area"
            : "bar";

      chartInstance.updateOptions({
        chart: { type: newType },
      });
    }
  }

  // Show all users in expanded view
  function showAllUsers() {
    if (!currentData || !currentData.data.top_productive_users) {
      console.warn("No user data available");
      return;
    }

    const allProductiveUsers = currentData.data.top_productive_users;

    // Create modal or expanded view
    const modalHtml = `
            <div class="modal fade" id="allUsersModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">All Team Members</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="allUsersTable"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Remove existing modal if any
    $("#allUsersModal").remove();
    $("body").append(modalHtml);

    // Render table with all users
    renderTopProductiveUsers(allProductiveUsers);
    $("#allUsersTable").html($("#topProductiveUsers").html());

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById("allUsersModal"));
    modal.show();
  }

  // Sort users by different criteria
  function sortUsers(criteria) {
    if (!currentData || !currentData.data.top_productive_users) {
      console.warn("No user data available for sorting");
      return;
    }

    let sortedUsers = [...currentData.data.top_productive_users];

    switch (criteria) {
      case "name":
        sortedUsers.sort((a, b) => a.name.localeCompare(b.name));
        break;
      case "productive_time":
        sortedUsers.sort((a, b) => b.productive_minutes - a.productive_minutes);
        break;
      case "utilization":
        sortedUsers.sort(
          (a, b) =>
            parseFloat(b.utilization_display) -
            parseFloat(a.utilization_display),
        );
        break;
      default:
        console.warn("Unknown sorting criteria:", criteria);
        return;
    }

    // Update the display
    renderTopProductiveUsers(sortedUsers);
  }

  // Export user data as CSV
  function exportUserData() {
    if (!currentData || !currentData.data.top_productive_users) {
      console.warn("No user data available for export");
      return;
    }

    const users = currentData.data.top_productive_users;
    const csvContent = [
      ["Name", "User ID", "Productive Time", "Utilization Rate"],
      ...users.map((user) => [
        user.name,
        user.user_id,
        user.productive_display,
        user.utilization_display,
      ]),
    ]
      .map((row) => row.join(","))
      .join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.download = `user_data_${moment().format("YYYY-MM-DD")}.csv`;
    link.href = url;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }
  function showErrorToast(message) {
    // Remove any existing toast
    $("#dashboardErrorToast").remove();

    // Create toast HTML
    const toastHtml = `
            <div id="dashboardErrorToast" class="toast align-items-center text-bg-danger border-0 position-fixed bottom-0 end-0 m-4" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 9999;">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bx bx-error-circle me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
    $("body").append(toastHtml);

    // Show the toast
    const toastEl = document.getElementById("dashboardErrorToast");
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
  }

  // Export user insights data
  function exportUserInsights() {
    if (!currentData) {
      console.warn("No data available for export");
      return;
    }

    const data = currentData.data.current_period;
    const csvContent = [
      ["Metric", "Value"],
      ["Work Time", data.metrics.work_time?.display || "N/A"],
      ["Active Time", data.metrics.active_time?.display || "N/A"],
      ["Manual Time", data.metrics.manual_time?.display || "N/A"],
      ["Productive Time", data.metrics.productive_time?.display || "N/A"],
      ["Unproductive Time", data.metrics.unproductive_time?.display || "N/A"],
      ["Utilization Rate", data.metrics.utilization?.display || "N/A"],
      ["Break Time", data.metrics.break_time?.display || "N/A"],
      ["Idle Time", data.metrics.idle_time?.display || "N/A"],
    ]
      .map((row) => row.join(","))
      .join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.download = `dashboard_insights_${moment().format("YYYY-MM-DD")}.csv`;
    link.href = url;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }

  // Make functions globally available
  window.dismissGuide = dismissGuide;
  window.setDateRange = setDateRange;
  window.selectAllUsers = selectAllUsers;
  window.clearUserSelection = clearUserSelection;
  window.resetFilters = resetFilters;
  window.exportChart = exportChart;
  window.toggleChartView = toggleChartView;
  window.showAllUsers = showAllUsers;
  window.sortUsers = sortUsers;
  window.exportUserData = exportUserData;
  window.exportUserInsights = exportUserInsights;
});
$(function () {
  // Dismiss Guide Button
  $(".dismiss-guide-btn").on("click", function () {
    if (typeof dismissGuide === "function") {
      dismissGuide();
    } else {
      $("#welcomeGuide").hide();
      localStorage.setItem("dashboardGuideShown", "1");
    }
  });

  // Quick Date Range Buttons
  $(".quick-date-btn").on("click", function () {
    var range = $(this).data("range");
    if (typeof setDateRange === "function") {
      setDateRange(range);
    }
  });

  // Select All Users
  $(".select-all-users-btn").on("click", function (e) {
    e.preventDefault();
    if (typeof selectAllUsers === "function") {
      selectAllUsers();
    }
  });
  // Clear User Selection
  $(".clear-user-selection-btn").on("click", function (e) {
    e.preventDefault();
    if (typeof clearUserSelection === "function") {
      clearUserSelection();
    }
  });

  // Reset Filters Button
  $(".reset-filters-btn").on("click", function () {
    if (typeof resetFilters === "function") {
      resetFilters();
    }
  });

  // Export Chart Buttons
  $(".export-chart-btn").on("click", function () {
    var chart = $(this).data("chart");
    if (typeof exportChart === "function") {
      exportChart(chart);
    }
  });
  // Toggle Chart View Buttons
  $(".toggle-chart-view-btn").on("click", function () {
    var chart = $(this).data("chart");
    if (typeof toggleChartView === "function") {
      toggleChartView(chart);
    }
  });

  // Sort Users Buttons
  $(".sort-users-btn").on("click", function () {
    var sortBy = $(this).data("sort");
    if (typeof sortUsers === "function") {
      sortUsers(sortBy);
    }
  });
  // Export User Data Button
  $(".export-user-data-btn").on("click", function (e) {
    e.preventDefault();
    if (typeof exportUserData === "function") {
      exportUserData();
    }
  });

  // Show All Users Button
  $(".show-all-users-btn").on("click", function () {
    if (typeof showAllUsers === "function") {
      showAllUsers();
    }
  });

  // Export User Insights Button in Modal
  $(".export-user-insights-btn").on("click", function () {
    if (typeof exportUserInsights === "function") {
      exportUserInsights();
    }
  });

  // Optionally: Initialize tooltips (if not already handled elsewhere)
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});
