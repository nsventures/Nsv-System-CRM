@extends('layout')

@section('title')
    <?= get_label('social_media_analytics', 'Social Media Analytics') ?>
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/social/social.css') }}">
    <div class="container-fluid my-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('home.index') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('social.index') }}"><?= get_label('social_media', 'Social Media') ?></a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('analytics', 'Analytics') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <!-- Account Filter -->
                    <div class="col-md-3 col-lg-2 mb-3">
                        <label for="accountSelect" class="form-label"><?= get_label('account', 'Account') ?></label>
                        <select id="accountSelect" class="form-select" aria-label="Account selection">
                            <option value="all" selected><?= get_label('all_accounts', 'All Accounts') ?></option>
                        </select>
                    </div>

                    <!-- Date Range Picker -->
                    <div class="col-md-4 col-lg-3 mb-3">
                        <label for="social_date_between" class="form-label"><?= get_label('date_range', 'Date Range') ?></label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                            <input type="text" class="form-control" id="social_date_between"
                                placeholder="<?= get_label('select_date_range', 'Select date range') ?>" autocomplete="off">
                        </div>
                        <input type="hidden" id="social_start_date" name="startDate">
                        <input type="hidden" id="social_end_date" name="endDate">
                    </div>

                    <!-- Quick Date Range Select -->
                    <div class="col-md-3 col-lg-2 mb-3">
                        <label for="dateRangeSelect" class="form-label"><?= get_label('quick_select', 'Quick Select') ?></label>
                        <select id="dateRangeSelect" class="form-select" aria-label="Date range selection">
                            <option value="7"><?= get_label('last_7_days', 'Last 7 days') ?></option>
                            <option value="30" selected><?= get_label('last_30_days', 'Last 30 days') ?></option>
                            <option value="90"><?= get_label('last_90_days', 'Last 90 days') ?></option>
                            <option value="365"><?= get_label('last_year', 'Last year') ?></option>
                            <option value="custom"><?= get_label('custom_range', 'Custom Range') ?></option>
                        </select>
                    </div>

                    <!-- Refresh Button -->
                    <div class="col-md-2 col-lg-1 mb-3">
                        <button type="button" class="btn btn-outline-secondary w-100" id="refreshSocialAnalytics" 
                                title="<?= get_label('refresh', 'Refresh') ?>">
                            <i class="bx bx-refresh"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="py-5 text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"><?= get_label('loading', 'Loading...') ?></span>
            </div>
            <p class="mt-3"><?= get_label('loading_analytics_data', 'Loading analytics data...') ?></p>
        </div>

        <!-- Analytics Content -->
        <div id="analyticsContent">
            <!-- Overall Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3 flex-shrink-0">
                                    <span class="avatar-initial bg-label-primary rounded">
                                        <i class="bx bx-book-content"></i>
                                    </span>
                                </div>
                                <div>
                                    <small class="text-muted d-block"><?= get_label('total_posts', 'Total Posts') ?></small>
                                    <div class="d-flex align-items-center">
                                        <h4 class="mb-0 me-1" id="totalPosts">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3 flex-shrink-0">
                                    <span class="avatar-initial bg-label-success rounded">
                                        <i class="bx bx-check-circle"></i>
                                    </span>
                                </div>
                                <div>
                                    <small class="text-muted d-block"><?= get_label('published_posts', 'Published Posts') ?></small>
                                    <div class="d-flex align-items-center">
                                        <h4 class="mb-0 me-1" id="publishedPosts">0</h4>
                                        <p class="text-success mb-0" id="successRate">(0%)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3 flex-shrink-0">
                                    <span class="avatar-initial bg-label-warning rounded">
                                        <i class="bx bx-time-five"></i>
                                    </span>
                                </div>
                                <div>
                                    <small class="text-muted d-block"><?= get_label('scheduled_posts', 'Scheduled Posts') ?></small>
                                    <div class="d-flex align-items-center">
                                        <h4 class="mb-0 me-1" id="scheduledPosts">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3 flex-shrink-0">
                                    <span class="avatar-initial bg-label-info rounded">
                                        <i class="bx bx-image"></i>
                                    </span>
                                </div>
                                <div>
                                    <small class="text-muted d-block"><?= get_label('media_files', 'Media Files') ?></small>
                                    <div class="d-flex align-items-center">
                                        <h4 class="mb-0 me-1" id="totalMediaFiles">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account-wise Performance (NEW - Shows when "All Accounts" selected) -->
            <div id="accountPerformanceSection" class="row mb-4" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= get_label('account_wise_performance', 'Account-wise Performance') ?></h5>
                        </div>
                        <div class="card-body">
                            <div id="accountPerformanceChart" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="row mb-4">
                <!-- Daily Activity Chart -->
                <div class="col-xl-8 col-lg-7 mb-3">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0"><?= get_label('daily_activity', 'Daily Activity') ?></h5>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    id="dailyActivityDropdown" data-bs-toggle="dropdown">
                                    <i class="bx bx-calendar"></i> <?= get_label('view', 'View') ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item trend-period" href="#" data-period="daily"><?= get_label('daily', 'Daily') ?></a></li>
                                    <li><a class="dropdown-item trend-period" href="#" data-period="weekly"><?= get_label('weekly', 'Weekly') ?></a></li>
                                    <li><a class="dropdown-item trend-period" href="#" data-period="monthly"><?= get_label('monthly', 'Monthly') ?></a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="dailyActivityChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="col-xl-4 col-lg-5 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= get_label('post_status_distribution', 'Post Status Distribution') ?></h5>
                        </div>
                        <div class="card-body">
                            <div id="statusDistributionChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="row mb-4">
                <!-- Platform Performance -->
                <div class="col-xl-6 col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= get_label('platform_performance', 'Platform Performance') ?></h5>
                        </div>
                        <div class="card-body">
                            <div id="platformPerformanceChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Peak Hours -->
                <div class="col-xl-6 col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= get_label('posting_peak_hours', 'Posting Peak Hours') ?></h5>
                        </div>
                        <div class="card-body">
                            <div id="peakHoursChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Tables Row -->
            <div class="row mb-4">
                <!-- Platform Statistics -->
                <div class="col-xl-7 col-lg-6 mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header border-0 bg-white pb-0">
                            <h5 class="card-title d-flex align-items-center mb-0">
                                <?= get_label('platform_statistics', 'Platform Statistics') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="platformStatsChart"></div>
                        </div>
                    </div>
                </div>

                <!-- Media & Scheduling Statistics -->
                <div class="col-xl-5 col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= get_label('media_and_scheduling', 'Media & Scheduling') ?></h5>
                        </div>
                        <div class="card-body">
                            <!-- Media Statistics -->
                            <div class="row mb-3 text-center">
                                <div class="col-6">
                                    <h4 class="mb-1" id="postsWithMedia">0</h4>
                                    <small class="text-muted"><?= get_label('with_media', 'With Media') ?></small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-1" id="avgMediaPerPost">0</h4>
                                    <small class="text-muted"><?= get_label('avg_media_per_post', 'Avg Media/Post') ?></small>
                                </div>
                                <hr class="mt-2">
                            </div>
                            <!-- Scheduling Chart -->
                            <div class="text-center">
                                <div id="schedulingChart" style="height: 200px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.analyticConfig = {
            routes: {
                getAnalyticsData: "{{ route('social.analytics.data') }}",
                getPostingTrends: "{{ route('social.analytics.trends') }}",
                getActiveAccounts: "{{ route('social.accounts.active') }}"
            }
        }
    </script>
    <script src="{{ asset('assets/js/social/platform-config.js') }}"></script>
    <script src="{{ asset('assets/js/social/social-analytics.js') }}"></script>
@endsection