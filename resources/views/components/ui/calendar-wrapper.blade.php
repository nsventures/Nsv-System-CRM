@props([
    'calendarId' => 'calendarDiv',
    'createButtonText' => null,
    'createModalTarget' => '#create_modal',
    'createOffcanvasTarget' => null,
    'entityType' => 'items',
    'showMiniCalendar' => false,
    'sidebarTitle' => null,
    'showStatusFilters' => false,
    'showPriorityFilters' => false,
    'sidebarContent' => null,
])

<!-- Mobile Sidebar Toggle -->
<div class="d-md-none mb-3">
    <button class="btn btn-secondary d-flex align-items-center justify-content-center" type="button" data-bs-toggle="offcanvas" data-bs-target="#calendarSidebarOffcanvas" aria-controls="calendarSidebarOffcanvas">
        <i class="bx bx-menu me-2"></i> Toggle Filters & Options
    </button>
</div>

<div class="calendar-wrapper">
    <!-- Enhanced Sidebar -->
    <div class="calendar-sidebar offcanvas-md offcanvas-start card" tabindex="-1" id="calendarSidebarOffcanvas" aria-labelledby="calendarSidebarOffcanvasLabel">
        <!-- Sidebar Title -->
        <div class="offcanvas-header border-bottom p-3 d-md-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0" id="calendarSidebarOffcanvasLabel">
                <i class="bx bx-calendar me-2"></i>
                {{ $sidebarTitle ?? get_label(ucfirst($entityType) . '_calendar', ucfirst($entityType) . ' Calendar') }}
            </h5>
            <button type="button" class="btn-close d-md-none" data-bs-dismiss="offcanvas" data-bs-target="#calendarSidebarOffcanvas" aria-label="Close"></button>
        </div>
        
        <div class="offcanvas-body flex-column p-3">
            @if ($createButtonText)
                <!-- Add Button -->
                <!-- <button class="btn btn-primary w-100 mb-4"
                    @if ($createOffcanvasTarget) data-bs-toggle="offcanvas" data-bs-target="{{ $createOffcanvasTarget }}"
            @else
            data-bs-toggle="modal" data-bs-target="{{ $createModalTarget }}" @endif>
                    <i class="bx bx-plus me-1"></i> {{ $createButtonText }}
                </button> -->
            @endif

            <!-- Mini Calendar (if enabled) -->
            @if ($showMiniCalendar)
                <div class="mb-4">
                    <input type="text" id="miniCalendar" class="d-none">
                </div>
            @endif

            <!-- Status Filters (Dynamic, shown only if enabled) -->
            @if ($showStatusFilters)
                <div class="filter-section mb-4">
                    <h6 class="text-uppercase fw-semibold mb-3" style="font-size: 0.75rem; letter-spacing: 1px;"><i class="bx bx-flag me-1"></i>
                        {{ $sidebarTitle ? $sidebarTitle . ' Status' : get_label('status', 'Status') }}
                    </h6>
                    <div id="status-filters-container">
                        <div class="placeholder-glow">
                            <span class="placeholder col-12 rounded mb-2" style="height: 24px;"></span>
                            <span class="placeholder col-8 rounded mb-2" style="height: 24px;"></span>
                            <span class="placeholder col-10 rounded mb-2" style="height: 24px;"></span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Priority Filters (Dynamic, shown only if enabled) -->
            @if ($showPriorityFilters)
                <div class="filter-section mb-4">
                    <h6 class="text-uppercase fw-semibold mb-3" style="font-size: 0.75rem; letter-spacing: 1px;"><i class="bx bx-star me-1"></i> {{ get_label('priority', 'Priority') }}</h6>
                    <div id="priority-filters-container">
                        <div class="placeholder-glow">
                            <span class="placeholder col-12 rounded mb-2" style="height: 24px;"></span>
                            <span class="placeholder col-9 rounded mb-2" style="height: 24px;"></span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Custom Sidebar Content Slot -->
            {!! $sidebarContent ?? '' !!}

            <!-- Calendar Statistics -->
            <div class="filter-section mt-auto pt-3 border-top">
                <h6 class="text-uppercase fw-semibold mb-3" style="font-size: 0.75rem; letter-spacing: 1px;"><i class="bx bx-bar-chart me-1"></i> {{ get_label('statistics', 'Statistics') }}</h6>
                <div class="small text-muted">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ get_label('total_' . $entityType, 'Total') }}:</span>
                        <span id="total-{{ $entityType }}" class="fw-semibold text-body">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ get_label('visible', 'Visible') }}:</span>
                        <span id="visible-{{ $entityType }}" class="fw-semibold text-body">0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ get_label('filtered', 'Filtered') }}:</span>
                        <span id="filtered-{{ $entityType }}" class="fw-semibold text-body">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="calendar-main card p-3">
        <div id="{{ $calendarId }}"></div>
    </div>
</div>
