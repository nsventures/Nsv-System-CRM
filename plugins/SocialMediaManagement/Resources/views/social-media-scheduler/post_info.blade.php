@extends('layout')

@section('title',
get_label('post_details', 'Post Details') .
' - ' .
get_label('social_media_scheduler', 'Social Media Scheduler'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-2">
                    <li class="breadcrumb-item">
                        <a href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('social.index') }}">{{ get_label('social_media', 'Social Media') }}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        {{ get_label('post_details', 'Post Details') }}
                    </li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold">{{ get_label('post_details', 'Post Details') }}</h4>
        </div>
        <div>
            <a href="{{ route('social.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>{{ get_label('back_to_list', 'Back to List') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-6">
            <!-- Post Status Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bx bx-info-circle me-2 text-primary"></i>
                        {{ get_label('post_information', 'Post Information') }}
                    </h5>
                    @php
                    $statusClasses = [
                    'published' => 'success',
                    'scheduled' => 'warning',
                    'failed' => 'danger',
                    'pending' => 'secondary',
                    'partially_published' => 'primary',
                    ];
                    $statusClass = $statusClasses[$post->status] ?? 'secondary';
                    $postStatus = str_replace('_', ' ', ucfirst($post->status));
                    @endphp
                    <span class="badge bg-{{ $statusClass }} px-3 py-2 rounded-pill">
                        {{ get_label($post->status, $postStatus) }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-user text-muted me-2 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <small class="text-muted d-block mb-1">{{ get_label('created_by', 'Created By') }}</small>
                                    <span class="fw-medium">{{ $post->user->first_name }} {{ $post->user->last_name }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-calendar text-muted me-2 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <small class="text-muted d-block mb-1">{{ get_label('created_at', 'Created At') }}</small>
                                    <span class="fw-medium">{{ format_date($post->created_at, false, 'Y-m-d H:i') }}</span>
                                </div>
                            </div>
                        </div>
                        @if ($post->scheduled_at)
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-time-five text-muted me-2 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <small class="text-muted d-block mb-1">{{ get_label('scheduled_for', 'Scheduled For') }}</small>
                                    <span class="fw-medium">{{ format_date($post->scheduled_at, true) }}</span>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-refresh text-muted me-2 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <small class="text-muted d-block mb-1">{{ get_label('last_updated', 'Last Updated') }}</small>
                                    <span class="fw-medium">{{ format_date($post->updated_at, false, 'Y-m-d H:i') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-share-alt text-muted me-2 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <small class="text-muted d-block mb-1">{{ get_label('platforms', 'Platforms') }}</small>
                                    <span class="fw-medium">{{ count($post->platforms) }} {{ get_label('selected', 'selected') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-image text-muted me-2 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <small class="text-muted d-block mb-1">{{ get_label('media_files', 'Media Files') }}</small>
                                    <span class="fw-medium">{{ $mediaFiles->count() }} {{ $mediaFiles->count() === 1 ? get_label('file', 'File') : get_label('files', 'Files') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Post Caption -->
            @if ($post->caption)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bx bx-text me-2 text-primary"></i>
                        {{ get_label('post_caption', 'Post Caption') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="bg-light rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <p class="mb-0" style="white-space: pre-line; line-height: 1.6;">{!! $post->caption !!}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Media Files -->
            @if ($mediaFiles->count() > 0)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bx bx-image me-2 text-primary"></i>
                        {{ get_label('media_files', 'Media Files') }}
                    </h5>
                    <span class="badge bg-primary rounded-pill">
                        {{ $mediaFiles->count() }} {{ $mediaFiles->count() === 1 ? get_label('file', 'File') : get_label('files', 'Files') }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach ($mediaFiles as $media)
                        <div class="col-md-6">
                            <div class="border rounded p-2 h-100 d-flex flex-column" style="transition: all 0.3s;">
                                @if ($media['is_image'])
                                <a href="{{ $media['url'] }}" data-lightbox="post-media" class="d-block mb-2">
                                    <img src="{{ $media['url'] }}" alt="{{ $media['name'] }}"
                                        class="img-fluid rounded w-100"
                                        style="height: 180px; object-fit: cover; cursor: zoom-in;">
                                </a>
                                @elseif($media['is_video'])
                                <video class="w-100 mb-2 rounded" style="height: 180px; object-fit: cover;" controls>
                                    <source src="{{ $media['url'] }}" type="{{ $media['mime_type'] }}">
                                    {{ get_label('video_not_supported', 'Your browser does not support the video tag.') }}
                                </video>
                                @endif
                                <div class="text-center mt-auto">
                                    <small class="text-muted d-block text-truncate fw-medium">{{ $media['name'] }}</small>
                                    <small class="text-muted">{{ $media['human_readable_size'] }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Platform Statistics -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bx bx-bar-chart me-2 text-primary"></i>
                        {{ get_label('platform_statistics', 'Platform Statistics') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="text-center p-3 rounded" style="background-color: #d4edda;">
                                <div class="h3 mb-1 fw-bold text-success">
                                    {{ $platformsInfo->where('status', 'published')->count() }}
                                </div>
                                <small class="text-success fw-semibold">
                                    {{ get_label('published', 'Published') }}
                                </small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 rounded" style="background-color: #f8d7da;">
                                <div class="h3 mb-1 fw-bold text-danger">
                                    {{ $platformsInfo->where('status', 'failed')->count() }}
                                </div>
                                <small class="text-danger fw-semibold">
                                    {{ get_label('failed', 'Failed') }}
                                </small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 rounded" style="background-color: #fff3cd;">
                                <div class="h3 mb-1 fw-bold text-warning">
                                    {{ $platformsInfo->where('status', 'pending')->count() }}
                                </div>
                                <small class="text-warning fw-semibold">
                                    {{ get_label('pending', 'Pending') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-6">
            <!-- Publishing Platforms -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bx bx-share me-2 text-primary"></i>
                        {{ get_label('publishing_platforms', 'Publishing Platforms') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    @foreach ($platformsInfo as $platform)
                    <div class="d-flex align-items-center justify-content-between p-3 mb-3 border rounded"
                        style="transition: all 0.3s; background-color: #f8f9fa;">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="me-3">
                                <i class="bx {{ $platform['icon'] }}"
                                    style="color: {{ $platform['color'] }}; font-size: 2rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">{{ $platform['display_name'] }}</h6>
                                @if ($platform['status'] === 'published')
                                <small class="text-success d-block">
                                    <i class="bx bx-check-circle me-1"></i>
                                    {{ get_label('published', 'Published') }}
                                </small>
                                @if($platform['published_at'])
                                <small class="text-muted d-block">
                                    {{ format_date($platform['published_at'], true) }}
                                </small>
                                @endif
                                @elseif($platform['status'] === 'failed')
                                <small class="text-danger d-block">
                                    <i class="bx bx-x-circle me-1"></i>
                                    {{ get_label('failed', 'Failed') }}
                                </small>
                                @if (isset($platform['error']))
                                <small class="text-danger d-block" style="font-size: 0.75rem;">
                                    {{ Str::limit($platform['error'], 40) }}
                                </small>
                                @endif
                                @else
                                <small class="text-warning d-block">
                                    <i class="bx bx-time me-1"></i>
                                    {{ get_label($platform['status'], ucfirst($platform['status'])) }}
                                </small>
                                @endif
                            </div>
                        </div>
                        @if (isset($platform['post_url']))
                        <a href="{{ $platform['post_url'] }}" target="_blank"
                            class="btn btn-sm btn-outline-primary rounded-pill ms-2 flex-shrink-0">
                            <i class="bx bx-link-external"></i>
                        </a>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- YouTube Details (Only shown when YouTube is selected) -->
            @php
            $youtubePlatform = $platformsInfo->firstWhere('name', 'youtube');
            @endphp

            @if($youtubePlatform && isset($youtubePlatform['youtube_meta']))
            @php
            $yt = $youtubePlatform['youtube_meta'];
            $categoryNames = [
            '1' => 'Film & Animation', '2' => 'Autos & Vehicles', '10' => 'Music',
            '15' => 'Pets & Animals', '17' => 'Sports', '19' => 'Travel & Events',
            '20' => 'Gaming', '22' => 'People & Blogs', '23' => 'Comedy',
            '24' => 'Entertainment', '25' => 'News & Politics', '26' => 'Howto & Style',
            '27' => 'Education', '28' => 'Science & Technology', '29' => 'Nonprofits & Activism'
            ];
            $categoryName = $categoryNames[$yt['category'] ?? ''] ?? ($yt['category'] ?? '-');
            @endphp

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bx bxl-youtube me-2" style="color: #FF0000;"></i>
                        {{ get_label('youtube_details', 'YouTube Details') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <!-- Title -->
                    @if(!empty($yt['title']))
                    <div class="mb-3">
                        <small class="text-muted text-uppercase d-block mb-1 fw-semibold">
                            {{ get_label('title', 'Title') }}
                        </small>
                        <p class="mb-0 fw-medium">{{ $yt['title'] }}</p>
                    </div>
                    @endif

                    <!-- Thumbnail -->
                    @if(!empty($yt['thumbnail_url']))
                    <div class="mb-3">
                        <small class="text-muted text-uppercase d-block mb-2 fw-semibold">
                            {{ get_label('thumbnail', 'Thumbnail') }}
                        </small>
                        <a href="{{ $yt['thumbnail_url'] }}"
                            data-lightbox="youtube-thumbnail"
                            data-title="YouTube Thumbnail"
                            class="d-inline-block">
                            <img src="{{ $yt['thumbnail_url'] }}" alt="YouTube thumbnail"
                                class="img-fluid rounded border"
                                style="max-width: 100%; max-height: 200px; object-fit: contain; display: block; cursor: pointer;">
                        </a>
                    </div>
                    @endif

                    <!-- Privacy & Category -->
                    <div class="row g-3 mb-3">
                        @if(!empty($yt['privacy_status']))
                        <div class="col-6">
                            <small class="text-muted text-uppercase d-block mb-1 fw-semibold">
                                {{ get_label('privacy_status', 'Privacy') }}
                            </small>
                            <span class="badge bg-light text-dark px-3 py-2">
                                {{ ucfirst($yt['privacy_status']) }}
                            </span>
                        </div>
                        @endif
                        @if(!empty($yt['category']))
                        <div class="col-6">
                            <small class="text-muted text-uppercase d-block mb-1 fw-semibold">
                                {{ get_label('category', 'Category') }}
                            </small>
                            <span class="badge bg-light text-dark px-3 py-2">{{ $categoryName }}</span>
                        </div>
                        @endif
                    </div>

                    <!-- Tags -->
                    @if(!empty($yt['tags']) && count($yt['tags']) > 0)
                    <div class="mb-3">
                        <small class="text-muted text-uppercase d-block mb-2 fw-semibold">
                            {{ get_label('tags', 'Tags') }}
                        </small>
                        <div>
                            @foreach($yt['tags'] as $tag)
                            <span class="badge bg-primary bg-opacity-10 text-primary me-1 mb-1 px-2 py-1">
                                {{ $tag }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Description -->
                    @if(!empty($yt['description']))
                    <div class="mb-3">
                        <small class="text-muted text-uppercase d-block mb-2 fw-semibold">
                            {{ get_label('description', 'Description') }}
                        </small>
                        <div class="bg-light rounded border p-3"
                            style="max-height: 150px; overflow-y: auto; white-space: pre-line; line-height: 1.6;">
                            {{ $yt['description'] }}
                        </div>
                    </div>
                    @endif

                    <!-- View on YouTube Button -->
                    @if(!empty($youtubePlatform['post_url']))
                    <a href="{{ $youtubePlatform['post_url'] }}" target="_blank"
                        class="btn btn-danger w-100">
                        <i class="bx bxl-youtube me-2"></i>
                        {{ get_label('view_on_youtube', 'View on YouTube') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection