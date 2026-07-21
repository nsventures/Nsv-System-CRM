@extends('layout')
@section('title', get_label('downloads', 'Downloads'))
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
                            <?= get_label('downloads', 'Downloads') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- Header -->
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">{{ get_label('downloads', 'Downloads') }}</h5>
                            <small class="text-muted">Download desktop applications for team monitoring</small>
                        </div>
                        @if (isAdminOrHasAllDataAccess())
                            <a href="{{ route('timetracker.downloads.upload') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i>
                                <span class="d-none d-sm-inline-block">{{ get_label('upload_app', 'Upload App') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- Apps Grid -->
        <div class="row">
            @forelse ($downloads as $groupKey => $files)
                @foreach ($files as $file)
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <!-- Platform Header -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-lg me-3">
                                        <span class="avatar-initial bg-label-primary rounded">
                                            @if ($file->platform == 'windows')
                                                <i class="bx bxl-windows bx-sm"></i>
                                            @elseif($file->platform == 'mac')
                                                <i class="bx bxl-apple bx-sm"></i>
                                            @elseif($file->platform == 'linux')
                                                <i class="bx bxl-tux bx-sm"></i>
                                            @else
                                                <i class="bx bx-mobile-alt bx-sm"></i>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ ucfirst($file->platform) }} App</h6>
                                        <small class="text-muted">{{ get_label('desktop_application','Desktop Application') }}</small>
                                    </div>
                                </div>
                                <!-- App Info -->
                                <div class="info-container">
                                    <ul class="list-unstyled mb-4">
                                        <li class="d-flex align-items-center mb-2">
                                            <i class="bx bx-check-shield text-success me-2"></i>
                                            <span class="fw-medium me-2">{{ get_label('version','Version') }}:</span>
                                            <span class="badge bg-label-success">v{{ $file->version }}</span>
                                        </li>
                                        <li class="d-flex align-items-center mb-2">
                                            <i class="bx bx-chip text-info me-2"></i>
                                            <span class="fw-medium me-2">{{ get_label('architecture','Architecture') }}:</span>
                                            <span
                                                class="badge bg-label-info">{{ strtoupper($file->arch ?? 'Universal') }}</span>
                                        </li>
                                        <li class="d-flex align-items-center mb-2">
                                            <i class="bx bx-file text-warning me-2"></i>
                                            <span class="fw-medium me-2">Type:</span>
                                            <span class="badge bg-label-warning">{{ strtoupper($file->file_type) }}</span>
                                        </li>
                                        <li class="d-flex align-items-center">
                                            <i class="bx bx-calendar text-secondary me-2"></i>
                                            <span class="fw-medium me-2">{{ get_label('updated_at','Updated At') }}:</span>
                                            <small class="text-muted">{{ format_date($file->created_at) }}</small>
                                        </li>
                                    </ul>
                                </div>
                                <!-- Changelog -->
                                @if ($file->changelog)
                                    <div class="mb-3">
                                        <h6 class="mb-2">
                                            <i class="bx bx-list-ul me-1"></i>{{ get_label('changelog','Change Log') }}
                                        </h6>
                                        <p class="text-muted small mb-0">{{ Str::limit($file->changelog, 100) }}</p>
                                    </div>
                                @endif
                            </div>
                            <!-- Card Footer -->
                            <div class="card-footer border-top bg-transparent pt-3">
                                <div class="d-flex gap-2">
                                    <a href="{{ Storage::url($file->file_path) }}" class="btn btn-primary flex-fill"
                                        target="_blank">
                                        <i class="bx bx-download me-1"></i>
                                        {{ get_label('download','Download') }}
                                    </a>
                                    @if (isAdminOrHasAllDataAccess())
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form class="form-submit-event"
                                                        action="{{ route('timetracker.downloads.destroy', $file->id) }}"
                                                        method="POST" class="d-inline w-100"
                                                        onsubmit="return confirm('⚠️ WARNING: This will permanently delete {{ ucfirst($file->platform) }} v{{ $file->version }} ({{ strtoupper($file->arch ?? 'Universal') }}).\n\nUsers will no longer be able to download this version.\n\nAre you absolutely sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bx bx-trash me-1"></i>{{ get_label('delete_version','Delete Version') }}
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="navigator.clipboard.writeText('{{ url(Storage::url($file->file_path)) }}'); this.innerHTML='<i class=\'bx bx-check me-1\'></i>Copied!'; setTimeout(() => this.innerHTML='<i class=\'bx bx-copy me-1\'></i>Copy Download Link', 2000)">
                                                    <i class="bx bx-copy me-1"></i>{{ get_label('copy_download_link','Copy Download Link') }}
                                                </a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body py-5 text-center">
                            <div class="avatar avatar-xl mx-auto mb-3">
                                <span class="avatar-initial bg-label-secondary rounded">
                                    <i class="bx bx-download bx-lg"></i>
                                </span>
                            </div>
                            <h5 class="mb-2">{{ get_label('no_downloads_found', 'No downloads found') }}</h5>
                            <p class="text-muted mb-4">{{ get_label('there_are_currently_no_applications_available_for_download','There are currently no applications available for download.') }}</p>
                            @if (isAdminOrHasAllDataAccess())
                                <a href="{{ route('timetracker.downloads.upload') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>{{ get_label('upload_first_app','Upload First App') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
