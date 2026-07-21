@extends('layout')
@section('title')
    {{ get_label('meetings', 'Meetings') }} - {{ get_label('calendar_view', 'Calendar View') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-2 mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            {{ get_label('meetings', 'Meetings') }}
                        </li>
                        <li class="breadcrumb-item active">
                            {{ get_label('calendar_view', 'Calendar View') }}
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                @php
                    $meetingsDefaultView = getUserPreferences('meetings', 'default_view');
                @endphp
                @if ($meetingsDefaultView === 'calendar')
                    <span class="badge bg-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);"><span class="badge bg-secondary" id="set-default-view" data-type="meetings"
                            data-view="calendar"><?= get_label('set_as_default_view', 'Set as Default View') ?></span></a>
                @endif
            </div>
            <div>
                <a href="{{ route('meetings.index') }}"><button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="{{ get_label('meetings', 'Meetings') }}"><i
                            class='bx bx-shape-polygon'></i></button></a>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
               @php
                    $sidebarContent = '<div class="filter-section">
                        <h6><i class="bx bx-star me-1"></i> ' . get_label("status", "Status") . '</h6>
                        <div id="meeting-status-filters-container">
                            <div class="skeleton-loader"></div>
                            <div class="skeleton-loader"></div>
                            <div class="skeleton-loader"></div>
                        </div>
                    </div>';
                @endphp
                <x-ui.calendar-wrapper
                    calendarId="meetings_calendar_view"
                    createButtonText="{{ get_label('create_meeting', 'Create meeting') }}"
                    createModalTarget="#createMeetingModal"
                    entityType="meetings"
                    showMiniCalendar="false"
                    sidebarTitle="{{ get_label('meetings', 'Meetings') }}"
                    :sidebarContent="$sidebarContent"
                />
            </div>
        </div>
    </div>
@endsection
@section('page_scripts')
    <script>
        var label_update = '<?= get_label('update', 'Update') ?>';
        var label_delete = '<?= get_label('delete', 'Delete') ?>';
        var label_duplicate = '<?= get_label('duplicate', 'Duplicate') ?>';
        var label_not_assigned = '<?= get_label('not_assigned', 'Not assigned') ?>';

        // Debug: Check if status filters container exists
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('meeting-status-filters-container');
            console.log('Status filters container:', container ? 'Found' : 'Not found');
        });
    </script>
    <script src="{{ asset('assets/js/pages/meeting-calendar.js') }}"></script>
@endsection
