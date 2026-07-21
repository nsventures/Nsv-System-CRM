{{--
    Taskify v2 — reusable table component.

    A thin wrapper around the existing `bootstrap-table` plugin (the app-wide
    table engine), so every table is authored the same way and inherits the
    design-system styling from custom.css. It keeps ALL server-side features
    (AJAX url, server pagination, search, sort, column toggle, refresh) — it
    only standardises the markup. Any extra table option is passed straight
    through via attributes (e.g. data-sort-name, data-sort-order,
    data-query-params, data-detail-view, data-show-export …).

    Props:
      id       — required table id (kept for the existing JS hooks/formatters)
      url      — data-url (server-side endpoint returning {total, rows})
      columns  — array of column configs:
                 ['field','label','sortable','checkbox','visible',
                  'formatter','events','align','width','class']

    Slots:
      before   — markup inside .table-responsive before the table
                 (e.g. the hidden #data_type / #multi_select inputs)
      prepend  — markup inside <table> before <thead> (e.g. a bulk alert)
--}}
@props([
    'id',
    'url' => null,
    'columns' => [],
])
@php
    $tableDefaults = [
        'id' => $id,
        'data-toggle' => 'table',
        'data-loading-template' => 'loadingTemplate',
        'data-icons-prefix' => 'bx',
        'data-icons' => 'icons',
        'data-data-field' => 'rows',
        'data-total-field' => 'total',
        'data-side-pagination' => 'server',
        'data-pagination' => 'true',
        'data-search' => 'true',
        'data-show-refresh' => 'true',
        'data-show-columns' => 'true',
        'data-trim-on-search' => 'false',
        'data-mobile-responsive' => 'true',
        'data-page-list' => '[5, 10, 20, 50, 100, 200]',
    ];
    if (!empty($url)) {
        $tableDefaults['data-url'] = $url;
    }
@endphp
<div class="table-responsive text-nowrap tk-table">
    {{ $before ?? '' }}
    <table {{ $attributes->merge($tableDefaults) }}>
        {{ $prepend ?? '' }}
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th
                        @if (!empty($col['checkbox']))
                            data-checkbox="true"
                        @else
                            data-field="{{ $col['field'] ?? '' }}"
                        @endif
                        @if (!empty($col['sortable'])) data-sortable="true" @endif
                        @if (array_key_exists('visible', $col) && $col['visible'] === false) data-visible="false" @endif
                        @if (!empty($col['formatter'])) data-formatter="{{ $col['formatter'] }}" @endif
                        @if (!empty($col['events'])) data-events="{{ $col['events'] }}" @endif
                        @if (!empty($col['align'])) data-align="{{ $col['align'] }}" @endif
                        @if (!empty($col['width'])) data-width="{{ $col['width'] }}" @endif
                        @if (!empty($col['class'])) class="{{ $col['class'] }}" @endif
                    >{!! $col['label'] ?? '' !!}</th>
                @endforeach
            </tr>
        </thead>
        {{ $slot ?? '' }}
    </table>
</div>
