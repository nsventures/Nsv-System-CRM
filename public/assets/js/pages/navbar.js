"use strict";

// Search Modal Js
$(document).ready(function () {
    let searchTimeout;
    let currentTab = 'all';
    let allSearchResults = {};
    let currentHighlightIndex = -1;
    let recentSearches = JSON.parse(localStorage.getItem('recentSearches')) || [];



    // Initialize recent searches
    function updateRecentSearches() {
        const $recentSearches = $('#recentSearches');
        $recentSearches.empty();
        if (recentSearches.length === 0) {
            $recentSearches.append('<p class="text-muted small mb-0">No recent searches</p>');
        } else {
            recentSearches.slice(0, 5).forEach(search => {
                $recentSearches.append(`
                    <span class="badge bg-light text-dark rounded-pill border px-3 py-2 recent-search" data-query="${search}">
                        <i class="bx bx-history me-1"></i>${search}
                    </span>
                `);
            });
        }
    }

    // Open modal with keyboard shortcut
    // Open modal with keyboard shortcut and focus input
    $(document).on("keydown", function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === "k") {
            e.preventDefault();
            $("#globalSearchModal").modal("show");
        }
    });

    // Focus input when modal is shown
    $("#globalSearchModal").on("shown.bs.modal", function () {
        $("#modalSearchInput").focus();
    });

    // Handle search input with debounce
    $("#modalSearchInput").on("input", function () {
        const query = $(this).val().trim();
        clearTimeout(searchTimeout);

        if (query.length > 0) {
            $(".search-results").removeClass("d-none");
            $("#searchTabs").removeClass("d-none");
            $("#popularSection").addClass("hidden");

            if (query.length >= 2) {
                showLoading();
                searchTimeout = setTimeout(() => performSearch(query), 300);
            } else {
                showNoResults('Type at least 2 characters to search', 'bx-keyboard');
                $("#popularSection").addClass("hidden");
            }
        } else {
            hideSearchResults();
            updateRecentSearches();
        }
    });

    // Handle recent search clicks
    $(document).on('click', '.recent-search', function () {
        const query = $(this).data('query');
        $("#modalSearchInput").val(query).trigger('input');
    });

    // Tab switching
    $('.nav-link[data-tab]').on('click', function () {
        const tab = $(this).data('tab');
        switchTab(tab);
        $("#modalSearchInput").focus();
        updateAriaSelected(tab);
    });

    // Keyboard navigation for results
    $("#searchResultsList").on("keydown", function (e) {
        const results = $('.search-result-item');
        if (results.length === 0) return;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            currentHighlightIndex = (currentHighlightIndex + 1) % results.length;
            highlightResult(results, currentHighlightIndex);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            currentHighlightIndex = (currentHighlightIndex - 1 + results.length) % results.length;
            highlightResult(results, currentHighlightIndex);
        } else if (e.key === "Enter" && currentHighlightIndex >= 0) {
            e.preventDefault();
            results.eq(currentHighlightIndex)[0].click();
        }
    });

    // Reset on modal close
    $("#globalSearchModal").on("hidden.bs.modal", function () {
        $("#modalSearchInput").val("");
        hideSearchResults();
        resetTabs();
        currentHighlightIndex = -1;
        updateRecentSearches();
    });

    // Perform search
    function performSearch(query) {
        // Add to recent searches
        if (query && !recentSearches.includes(query)) {
            recentSearches.unshift(query);
            recentSearches = recentSearches.slice(0, 5);
            localStorage.setItem('recentSearches', JSON.stringify(recentSearches));
        }

        $.ajax({
            url: baseUrl + "/search",
            data: { q: query },
            method: "GET",
            success: function (response) {
                allSearchResults = response.results;
                updateTabCounts(allSearchResults);
                renderSearchResults(allSearchResults, currentTab);
            },
            error: function () {
                showNoResults('An error occurred while searching. Please try again.', 'bx-error');
                $("#popularSection").addClass("hidden");
            }
        });
    }


    // Switch tab and update state
    function switchTab(tab) {
        currentTab = tab;
        $('.nav-link[data-tab]').removeClass('active').attr('aria-selected', 'false');
        $(`.nav-link[data-tab="${tab}"]`).addClass('active').attr('aria-selected', 'true');
        renderSearchResults(allSearchResults, tab);
        currentHighlightIndex = -1;

        // Ensure tab content is visible and fits
        $('.tab-pane').removeClass('show active');
        $(`#${tab}Results`).addClass('show active');
        $("#searchTabs").removeClass('d-none');
        $("#popularSection").addClass('hidden');
    }

    // Update ARIA selected state
    function updateAriaSelected(tab) {
        $('.nav-link[data-tab]').attr('aria-selected', 'false');
        $(`.nav-link[data-tab="${tab}"]`).attr('aria-selected', 'true');
    }

    // Reset tabs
    function resetTabs() {
        currentTab = 'all';
        $('.nav-link[data-tab]').removeClass('active').attr('aria-selected', 'false');
        $('.nav-link[data-tab="all"]').addClass('active').attr('aria-selected', 'true');
        updateTabCounts({});
    }

    // Hide search results and show quick access
    function hideSearchResults() {
        $(".search-results").addClass("d-none");
        $("#searchTabs").addClass("d-none");
        $("#popularSection").removeClass("hidden");
        $("#searchResultsList").empty().removeClass('d-flex');
    }

    // Update tab counts
    function updateTabCounts(results) {

        let totalCount = 0;
        const categories = ['projects', 'tasks', 'workspaces', 'meetings', 'users'];


        categories.forEach(category => {
            const count = Array.isArray(results[category]) ? results[category].length : 0;
            if ($(`#${category}Count`).length) {
                $(`#${category}Count`).text(count);
            }
        });


        Object.keys(results).forEach(key => {
            if (Array.isArray(results[key])) {
                totalCount += results[key].length;
            }
        });


        $('#allCount').text(totalCount);
    }


    // Show loading state
    function showLoading() {
        $("#searchResultsList").html(`
            <div class="col flex-column align-items-center justify-content-center h-100 text-center">
            <div class="loading-spinner">
            <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
            </div>
            <h6 class="text-body-secondary mb-1">Searching...</h6>
            <p class="text-muted small mb-0">Finding the best matches for you</p>
            </div>
            </div>
        `).addClass('d-flex');
        $("#popularSection").addClass("hidden");
    }

    // Show no results state
    function showNoResults(message, icon = 'bx-search-alt-2') {
        $("#searchResultsList").html(`
            <div class="col no-results d-flex flex-column align-items-center justify-content-center h-100 text-center px-4">
                <div class="bg-light rounded-circle p-4 mb-3">
                    <i class="bx ${icon} fs-1 text-muted"></i>
                </div>
                <h6 class="text-body-secondary mb-2">No Results Found</h6>
                <p class="text-muted small mb-0">${message}</p>
            </div>
        `).addClass('d-flex');
        $("#popularSection").addClass("hidden");
    }

    // Render search results
    function renderSearchResults(results, activeTab = 'all') {
        const resultsList = $("#searchResultsList");
        resultsList.empty().removeClass('d-flex');

        let hasResults = false;
        let resultsToShow = activeTab === 'all' ? results : { [activeTab]: results[activeTab] || [] };

        for (const module in resultsToShow) {
            if (resultsToShow[module].length > 0) {
                hasResults = true;

                if (activeTab === 'all') {
                    resultsList.append(`
                        <div class="search-category-header bg-primary-subtle border-bottom px-3 py-2">
                            <div class="d-flex align-items-center">
                                <i class="bx ${getModuleIcon(module)} text-primary me-2"></i>
                                <small class="text-primary fw-semibold text-uppercase">${module}</small>
                                <span class="badge bg-primary ms-auto">${resultsToShow[module].length}</span>
                            </div>
                        </div>
                    `);
                }

                resultsToShow[module].forEach((item, index) => {
                    console.log(item);
                    const redirectUrl = getRedirectUrl(module, item.id);
                    const icon = getModuleIcon(module);
                    const colorClass = getModuleColorClass(module);

                    resultsList.append(`
                        <a href="${redirectUrl}" class="search-result-item d-flex align-items-center gap-3 p-3 border-bottom text-decoration-none text-reset"
                           data-id="${item.id}" data-type="${module}" role="option" aria-posinset="${index + 1}">
                            <div class="result-icon ${colorClass} rounded-3 d-flex align-items-center justify-content-center">
                                <i class="bx ${icon}"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="mb-1 text-body-emphasis fw-medium result-title">${item.title}</h6>
                            </div>
                            <i class="bx bx-chevron-right text-muted"></i>
                        </a>
                    `);
                });
            }
        }

        if (!hasResults) {
            const message = activeTab === 'all' ? 'Try different keywords or check your spelling' : `No ${activeTab} match your search`;
            showNoResults(message);
        }

        $("#popularSection").toggleClass("hidden", hasResults);
        currentHighlightIndex = -1;
    }

    // Highlight result for keyboard navigation
    function highlightResult(results, index) {
        results.removeClass('active').attr('aria-selected', 'false');
        const selected = results.eq(index);
        selected.addClass('active').attr('aria-selected', 'true');
        selected[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Helper function to get redirect URL
    function getRedirectUrl(module, id) {
        const routes = {
            projects: `/projects/information/${id}`,
            tasks: `/tasks/information/${id}`,
            meetings: "/meetings",
            workspaces: "/workspaces",
            users: `/users/profile/${id}`,
            clients: `/clients/profile/${id}`,
            todos: "/todos",
            notes: "/notes",
        };
        return baseUrl + (routes[module] || "/");
    }

    // Helper function to get icon based on module
    function getModuleIcon(module) {
        const icons = {
            projects: "bx-briefcase-alt-2",
            tasks: "bx-task",
            meetings: "bx-shape-polygon",
            workspaces: "bx-check-square",
            users: "bx-group",
            clients: "bx-group",
            todos: "bx-list-check",
            notes: "bx-notepad",
        };
        return icons[module] || "bx-circle";
    }

    // Helper function to get icon color class
    function getModuleColorClass(module) {
        const classes = {
            projects: "bg-label-primary",
            tasks: "bg-label-success",
            meetings: "bg-label-warning",
            workspaces: "bg-label-info",
            users: "bg-label-secondary",
            clients: "bg-label-dark",
            todos: "bg-label-warning",
            notes: "bg-label-dark"
        };
        return classes[module] || "bg-label-primary";
    }




    // Initialize recent searches on modal open
    $("#globalSearchModal").on("shown.bs.modal", function () {
        updateRecentSearches();
    });
});
$("#global-search").on("click", function () {
    $("#globalSearchModal").modal("show");
});
