@extends('layout')

@section('title')
    <?= get_label('social_media_calendar', 'Social Media Calendar') ?>
@endsection
@section('content')
    <link rel="stylesheet" href="{{ asset('assets/css/social/social.css') }}">
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ url('/home') }}">{{ get_label('home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('social.index') }}">{{ get_label('social_media', 'Social Media') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ get_label('calendar', 'Calendar') }}</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('social.create') }}">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="right"
                        data-bs-original-title="{{ get_label('create_post', 'Create Post') }}">
                        <i class="bx bx-plus"></i>
                    </button>
                </a>
                <a href="{{ route('social.index') }}"><button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="<?= get_label('list_view', 'List View') ?>"><i
                            class='bx bx-list-ul'></i></button></a>

                 <a href="{{ route('social.analytics') }}">
                    <button type="button" id="" class="btn btn-sm btn-primary action_create_template"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="{{ get_label('analytics', 'Analytics') }}">
                        <i class='bx bx-chart'></i>
                    </button>
                </a>
            </div>
        </div>

        <!-- NEW: Account Filter Bar -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-3 col-lg-2">
                        <label for="calendarAccountSelect" class="form-label mb-1 small">
                            {{ get_label('account', 'Account') }}
                        </label>
                        <select id="calendarAccountSelect" class="form-select form-select-sm">
                            <option value="all" selected>{{ get_label('all_accounts', 'All Accounts') }}</option>
                        </select>
                    </div>
                    <div class="col-md-9 col-lg-10 text-end">
                        <small class="text-muted" id="accountInfoText">
                            {{ get_label('showing_posts_from_all_accounts', 'Showing posts from all accounts') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="calendar-container">
            <!-- Header -->
            <div class="calendar-header todo-gradient-primary">
                <div class="calendar-nav">
                    <button class="nav-btn" id="prevMonth">
                        <i class="bx bx-chevron-left"></i>
                    </button>
                    <h3 id="currentMonth" class="mb-0 text-white">{{ now()->format('F Y') }}</h3>
                    <button class="nav-btn" id="nextMonth">
                        <i class="bx bx-chevron-right"></i>
                    </button>
                    <button class="nav-btn" id="todayBtn" title="{{ get_label('go_to_today', 'Go to Today') }}">
                        <i class="bx bx-calendar-event"></i>
                    </button>
                </div>

                <div class="view-controls">
                    <button class="view-btn active" data-view="month">{{ get_label('month', 'Month') }}</button>
                    <button class="view-btn" data-view="week">{{ get_label('week', 'Week') }}</button>
                    <a href="{{ route('social.create') }}" class="btn btn-light">
                        <i class="bx bx-plus"></i> {{ get_label('new_post', 'New Post') }}
                    </a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-count text-success" id="publishedCount">0</div>
                    <div class="stat-label">{{ get_label('published', 'Published') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-count text-warning" id="scheduledCount">0</div>
                    <div class="stat-label">{{ get_label('scheduled', 'Scheduled') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-count text-danger" id="failedCount">0</div>
                    <div class="stat-label">{{ get_label('failed', 'Failed') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-count text-info" id="partialCount">0</div>
                    <div class="stat-label">{{ get_label('partial', 'Partial') }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="calendar-filters">
                <div class="filter-group">
                    <div class="filter-item active" data-filter="all">
                        <i class="bx bx-globe"></i>
                        <span>{{ get_label('all_posts', 'All Posts') }}</span>
                        <span class="badge bg-secondary" id="allCount">0</span>
                    </div>
                    @foreach($platforms as $key => $platform)
                        <div class="filter-item" data-filter="{{ $key }}">
                            <i class="bx {{ $platform['icon'] }} platform-icon" style="color: {{ $platform['color'] }};"></i>
                            <span>{{ get_label($key, ucfirst($key)) }}</span>
                            <span class="badge bg-secondary" id="{{ $key }}Count">0</span>
                        </div>
                    @endforeach

                    <div style="margin-left: auto; display: flex; gap: 10px;">
                        <div class="filter-item" data-status="published">
                            <div class="bg-success" style="width: 12px; height: 12px; border-radius: 2px;"></div>
                            <span>{{ get_label('published', 'Published') }}</span>
                        </div>
                        <div class="filter-item" data-status="scheduled">
                            <div class="bg-warning" style="width: 12px; height: 12px; border-radius: 2px;"></div>
                            <span>{{ get_label('scheduled', 'Scheduled') }}</span>
                        </div>
                        <div class="filter-item" data-status="failed">
                            <div class="bg-danger" style="width: 12px; height: 12px; border-radius: 2px;"></div>
                            <span>{{ get_label('failed', 'Failed') }}</span>
                        </div>
                        <div class="filter-item" data-status="pending">
                            <div class="bg-secondary" style="width: 12px; height: 12px; border-radius: 2px;"></div>
                            <span>{{ get_label('pending', 'Pending') }}</span>
                        </div>
                        <div class="filter-item" data-status="partially_published">
                            <div class="bg-primary" style="width: 12px; height: 12px; border-radius: 2px;"></div>
                            <span>{{ get_label('partial', 'Partial') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div id="calendarContent">
                <div class="loading">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="quickViewModalLabel">
                        <i class="bx bx-show-alt me-2"></i>{{ get_label('post_publishing_details', 'Post Publishing Details') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="quickViewContent">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <script>
        window.calendarConfig = {
            routes: {
                calendarData: '{{ route('social.calendar.data') }}',
                socialCreate: '{{ route('social.create') }}',
                getActiveAccounts: '{{ route('social.accounts.active') }}' // NEW
            },
            labels: {
                sun: '{{ get_label('sun', 'Sun') }}',
                mon: '{{ get_label('mon', 'Mon') }}',
                tue: '{{ get_label('tue', 'Tue') }}',
                wed: '{{ get_label('wed', 'Wed') }}',
                thu: '{{ get_label('thu', 'Thu') }}',
                fri: '{{ get_label('fri', 'Fri') }}',
                sat: '{{ get_label('sat', 'Sat') }}',
                addPost: '{{ get_label('add_post', 'Add Post') }}',
                successfulPlatforms: '{{ get_label('successful_platforms', 'Successful Platforms') }}',
                failedPlatforms: '{{ get_label('failed_platforms', 'Failed Platforms') }}',
                caption: '{{ get_label('caption', 'Caption') }}',
                noCaption: '{{ get_label('no_caption', 'No caption') }}',
                media: '{{ get_label('media', 'Media') }}',
                details: '{{ get_label('details', 'Details') }}',
                status: '{{ get_label('status', 'Status') }}',
                platforms: '{{ get_label('platforms', 'Platforms') }}',
                scheduled: '{{ get_label('scheduled', 'Scheduled') }}',
                created: '{{ get_label('created', 'Created') }}',
                author: '{{ get_label('author', 'Author') }}',
                postDetails: '{{ get_label('post_details', 'Post Details') }}',
                account: '{{ get_label('account', 'Account') }}', // NEW
                showingPostsFromAllAccounts: '{{ get_label('showing_posts_from_all_accounts', 'Showing posts from all accounts') }}', // NEW
                showingPostsFrom: '{{ get_label('showing_posts_from', 'Showing posts from') }}' // NEW
            }
        };
        // Pass platforms config to JS for dynamic icons/colors
        window.platformsConfig = @json($platforms);
    </script>

    <script src="{{ asset('assets/js/social/social-calendar.js') }}"></script>
    <script src="{{ asset('assets/js/social/social.js') }}"></script>

@endsection