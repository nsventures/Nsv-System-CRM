@props(['alert' => '', 'alertUrl' => '', 'alertAction' => '', 'calendarId' => '', 'listComponent' => '', 'data' => []])

<div class="tabs nav flex-wrap mb-3" role="tablist">
    <button
        type="button"
        class="tab nav-link active list-button"
        role="tab"
        data-bs-toggle="tab"
        data-bs-target="#{{ $calendarId }}-list"
        aria-controls="{{ $calendarId }}-list"
        aria-selected="true">
        {{ get_label('list', 'List') }}
    </button>
    <button
        type="button"
        class="tab nav-link calendar-button"
        role="tab"
        data-bs-toggle="tab"
        data-bs-target="#{{ $calendarId }}-calendar"
        aria-controls="{{ $calendarId }}-calendar"
        aria-selected="false">
        {{ get_label('calendar', 'Calendar') }}
    </button>
</div>

<div class="tab-content shadow-none p-0">
    <div class="tab-pane fade active show" id="{{ $calendarId }}-list" role="tabpanel">
        @if ($alert)
            <div class="alert alert-primary alert-dismissible" role="alert">
                {{ $alert }},
                <a href="{{ $alertUrl }}">{{ $alertAction }}</a>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <x-dynamic-component :component="$listComponent" :users="$data" />
    </div>
    <div class="tab-pane fade" id="{{ $calendarId }}-calendar" role="tabpanel">
        <div id="{{ $calendarId }}"></div>
    </div>
</div>
