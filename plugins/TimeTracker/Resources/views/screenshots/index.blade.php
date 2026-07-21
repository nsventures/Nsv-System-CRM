@extends('layout')
@section('page_styles')
    <link rel="stylesheet" href="{{ asset('assets/css/timetracker/timetracker.css') }}">
@endsection
@section('title', get_label('screenshot_gallery','Screenshot Gallery'))

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-2 mt-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <?= get_label('team_monitoring_and_productivity_tracker', 'Team Monitoring and Productivity Tracker') ?>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('screenshot_gallery','Screenshot Gallery') ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 mt-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <div class="filter-control-wrapper" data-hp-filter="screenshots-date-range">
                        <label class="form-label fw-semibold ">{{ get_label('select_date_range','Select Date Range') }}</label>
                        <input type="text" id="dateRange" class="form-control cursor-pointer bg-white" readonly data-hp-filter-input="screenshots-date-range">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-control-wrapper" data-hp-filter="screenshots-users">
                        <label class="form-label fw-semibold ">{{get_label('users','Users')}}</label>
                        <select id="filterUser" class="form-select" multiple data-hp-filter-input="screenshots-users">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                {{-- <div class="col-md-4 d-grid">
                    <button id="filterBtn" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i> Apply Filter
                    </button>
                </div> --}}
            </div>
            <div class="d-flex mt-3 flex-wrap gap-2">
                <button class="btn btn-sm btn-outline-secondary preset-btn filter-chip" data-preset="today" data-hp-filter-chip="screenshots-date-range">{{ get_label('today','Today') }}</button>
                <button class="btn btn-sm btn-outline-secondary preset-btn filter-chip" data-preset="yesterday" data-hp-filter-chip="screenshots-date-range">{{ get_label('yesterday','Yesterday') }}</button>
                <button class="btn btn-sm btn-outline-secondary preset-btn filter-chip" data-preset="thisWeek" data-hp-filter-chip="screenshots-date-range">{{ get_label('this_week','This Week') }}</button>
                <button class="btn btn-sm btn-outline-secondary preset-btn filter-chip" data-preset="lastWeek" data-hp-filter-chip="screenshots-date-range">{{ get_label('last_week','Last Week') }}</button>
                <button class="btn btn-sm btn-outline-secondary preset-btn filter-chip" data-preset="thisMonth" data-hp-filter-chip="screenshots-date-range">{{ get_label('this_month','This Month') }}</button>
            </div>
        </div>
    </div>

    <!-- Gallery Grid -->
    <div id="gallery" class="row g-3"></div>

    <!-- Load More -->
    <div class="my-4 text-center">
        <button id="loadMoreBtn" class="btn btn-secondary px-4 d-none">
            <i class="fas fa-plus-circle me-2"></i> {{ get_label('load_more','Load More') }}
        </button>
    </div>

    <!-- Loader -->
    <div id="loader" class="my-4 text-center d-none">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted mt-2">{{ get_label('loading_screenshots','Loading screenshots') }}...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="my-5 text-center d-none">
        <i class="fas fa-images text-muted fs-1"></i>
        <h5 class="text-muted mt-2">{{ get_label('no_screenshots_found','No screenshots found') }}</h5>
        <p class="text-muted">{{ get_label('try_adjusting_your_filters_or_date_range','Try adjusting your filters or date range') }}</p>
    </div>
</div>

<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/js/daterangepicker.js') }}"></script>
<script>
    var screenshotDataUrl = "{{ route('timetracker.screenshots.data') }}";
    var label_select_users = @json(get_label('select_users','Select Users'));
    var label_today = @json(get_label('today','Today'));
    var label_yesterday  = @json(get_label('yesterday','Yesterday'));
    var label_this_week = @json(get_label('this_week','This Week'));
    var label_last_week = @json(get_label('last_week','Last Week'));
    var label_this_month = @json(get_label('this_month','This Month'));
</script>
<script src="{{ asset('assets/js/timetracker-plugin/screenshots.js') }}"></script>
@endsection
