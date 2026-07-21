@props([
    'prefix' => 'item',
    'filters' => ['date_between', 'start_date_between', 'end_date_between'],
    'colClass' => 'col-md-4',
    'showIcons' => true,
    'showLabel' => true,
])

{{-- Advanced Date Filters Component
    This component generates date range filter inputs with preset ranges

    Usage:
    <x-advanced-date-filters
        prefix="task"
        :filters="['date_between', 'start_date_between', 'end_date_between']"
        colClass="col-md-4"
        :showIcons="true"
        :showLabel="true"
    />

    Props:
    - prefix: string (required) - Prefix for input IDs (e.g., 'task', 'project', 'meeting')
    - filters: array (optional) - Which filters to show. Options: 'date_between', 'start_date_between', 'end_date_between'
    - colClass: string (optional) - Bootstrap column class for responsive layout (default: 'col-md-4')
    - showIcons: boolean (optional) - Whether to show calendar icons (default: true)
    - showLabel: boolean (optional) - Whether to show the filter label above the input (default: true)
--}}

@php
    $filterLabels = [
        'date_between' => get_label('date_between', 'Date Between'),
        'start_date_between' => get_label('start_date_between', 'Start date between'),
        'end_date_between' => get_label('end_date_between', 'End date between'),
        'date_range' => get_label('date_range', 'Date Range'),
    ];
@endphp

{{-- Date Filter Inputs --}}
@foreach ($filters as $filter)
    @if (isset($filterLabels[$filter]))
        <div class="{{ $colClass }}">
            @if ($showLabel)
                <label class="form-label small text-muted mb-1">{{ $filterLabels[$filter] }}</label>
            @endif
            <div class="tk-inputgroup">
                @if ($showIcons)
                    <span><i class="bx bx-calendar"></i></span>
                @endif
                <input type="text" id="{{ $prefix }}_{{ $filter }}"
                    name="{{ $prefix }}_{{ $filter }}" placeholder="{{ $filterLabels[$filter] }}"
                    autocomplete="off">
            </div>
        </div>
    @endif
@endforeach

{{-- Hidden Fields for Date Values --}}
@foreach ($filters as $filter)
    @php
        // Hidden fields follow the pattern [prefix]_[type]_from/to
        // AND fallback pattern [prefix]_[type.replace('_between', '')]_from/to
        // We'll provide both to ensure maximum compatibility with different backend expectations
        $idBase = $prefix . '_' . $filter;
        $nameBase = $prefix . '_' . str_replace('_between', '', $filter);
    @endphp
    <input type="hidden" id="{{ $idBase }}_from" name="{{ $nameBase }}_from">
    <input type="hidden" id="{{ $idBase }}_to" name="{{ $nameBase }}_to">
@endforeach

{{-- Note: Date range picker initialization should be done in page-specific JavaScript files --}}
{{-- Use initAdvancedDateRangePicker() function from advanced-daterange-filter.js --}}

