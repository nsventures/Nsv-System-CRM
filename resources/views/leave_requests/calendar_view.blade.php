@extends('layout')
@section('title')
    {{ get_label('leave_requests', 'Leave Requests') }} - {{ get_label('calendar_view', 'Calendar View') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-2 mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{url('home')}}">{{ get_label('home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('leave_requests.index') }}">{{ get_label('leave_requests', 'Leave Requests') }}</a>

                        </li>
                        <li class="breadcrumb-item active">
                            {{ get_label('calendar_view', 'Calendar View') }}
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                @php
                    $leaveRequestDefaultView = getUserPreferences('leave_requests', 'default_view');
                @endphp
                @if ($leaveRequestDefaultView === 'calendar')
                    <span class="badge bg-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);"><span class="badge bg-secondary" id="set-default-view"
                            data-type="leave-requests"
                            data-view="calendar"><?= get_label('set_as_default_view', 'Set as Default View') ?></span></a>
                @endif
            </div>
            <div>
                <a href="{{ route('leave_requests.index') }}"><button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="{{ get_label('leave_requests', 'Leave Requests') }}"><i
                            class='bx bx-list-ul'></i></button></a>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                @php
                    $sidebarContent = '<div class="filter-section">
                        <h6><i class="bx bx-star me-1"></i> ' . get_label('status', 'Status') . '</h6>
                        <div id="leave-status-filters-container">
                            <div class="skeleton-loader"></div>
                            <div class="skeleton-loader"></div>
                            <div class="skeleton-loader"></div>
                        </div>
                    </div>';
                @endphp
                <x-ui.calendar-wrapper
                    calendarId="leave_requests_calendar_view"
                    createButtonText="{{ get_label('leave_request', 'Leave Request') }}"
                    createModalTarget="#create_leave_request_modal"
                    entityType="leave_requests"
                    showMiniCalendar="false"
                    sidebarTitle="{{ get_label('leave_requests', 'Leave Requests') }}"
                    :sidebarContent="$sidebarContent"
                />
            </div>
        </div>
    </div>

@endsection
@section('page_scripts')
<script src="{{ asset('assets/js/pages/leave-requests-calendar.js') }}"></script>
@endsection
