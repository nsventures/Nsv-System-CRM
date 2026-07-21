@extends('layout')
@section('title')
    <?= get_label('migrations', 'Migrations') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-2 mt-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('migrations', 'Migrations') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-primary me-2" id="runAllMigrations">
                <i class="bx bx-play-circle me-1"></i>
                <?= get_label('run_all_migrations', 'Run All Migrations') ?>
            </button>
            <button type="button" class="btn btn-info me-2" id="checkSequence">
                <i class="bx bx-check-circle me-1"></i>
                <?= get_label('check_migration_sequence', 'Check Migration Sequence') ?>
            </button>
            <button type="button" class="btn btn-warning me-2" id="validateMigrations">
                <i class="bx bx-shield me-1"></i>
                <?= get_label('validate_migrations', 'Validate Migrations') ?>
            </button>
            <button type="button" class="btn btn-secondary" id="fixIssues">
                <i class="bx bx-wrench me-1"></i>
                <?= get_label('fix_migration_issues', 'Fix Migration Issues') ?>
            </button>
            <button type="button" class="btn btn-outline-primary ms-2" id="refreshStatus">
                <i class="bx bx-refresh me-1"></i>
                <?= get_label('refresh', 'Refresh') ?>
            </button>
        </div>
    </div>

    @if(session('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            @if(session('dependency_info'))
                @php
                    $dependencyInfo = session('dependency_info');
                @endphp
                <div class="mt-3">
                    <strong><?= get_label('dependency_information', 'Dependency Information:') ?></strong>
                    <ul class="mb-2 mt-2">
                        <li><?= get_label('required_table', 'Required Table:') ?> <code>{{ $dependencyInfo['table'] ?? '' }}</code></li>
                        <li><?= get_label('dependency_migration', 'Dependency Migration:') ?> <code>{{ $dependencyInfo['dependency_migration'] ?? '' }}</code></li>
                        @if(isset($dependencyInfo['is_dependency_run']) && !$dependencyInfo['is_dependency_run'])
                            <li class="text-warning">
                                <i class="bx bx-error-circle me-1"></i>
                                <?= get_label('dependency_not_run', 'Dependency migration has not been run yet.') ?>
                                <a href="{{ url('/migrate/file/' . ($dependencyInfo['dependency_migration'] ?? '')) }}" class="btn btn-sm btn-warning ms-2">
                                    <i class="bx bx-play me-1"></i>
                                    <?= get_label('run_dependency', 'Run Dependency') ?>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div id="alertContainer"></div>

    <!-- Status Summary Cards -->
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-label-primary rounded">
                                <i class="bx bx-list-ul fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= get_label('total_migrations', 'Total Migrations') ?></h6>
                            <h4 class="mb-0">{{ $totalMigrations ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-label-warning rounded">
                                <i class="bx bx-time fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= get_label('pending_migrations', 'Pending Migrations') ?></h6>
                            <h4 class="mb-0">{{ $pendingCount ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-label-success rounded">
                                <i class="bx bx-check-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= get_label('ran_migrations', 'Ran Migrations') ?></h6>
                            <h4 class="mb-0">{{ $ranCount ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sequence Validation Results -->
    <div id="sequenceResults" class="d-none mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-info-circle me-1"></i>
                    <?= get_label('migration_sequence_status', 'Migration Sequence Status') ?>
                </h5>
            </div>
            <div class="card-body">
                <div id="sequenceContent"></div>
            </div>
        </div>
    </div>

    <!-- Pending Migrations -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <?= get_label('pending_migrations', 'Pending Migrations') ?>
                <span class="badge bg-warning">{{ $pendingCount ?? 0 }}</span>
            </h5>
        </div>
        <div class="card-body">
            @if(count($pendingMigrations ?? []) > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= get_label('migration_file', 'Migration File') ?></th>
                                <th><?= get_label('timestamp', 'Timestamp') ?></th>
                                <th><?= get_label('status', 'Status') ?></th>
                                <th><?= get_label('actions', 'Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingMigrations ?? [] as $index => $migration)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <code>{{ $migration['filename'] ?? '' }}</code>
                                        @if(isset($migration['file_exists']) && !$migration['file_exists'])
                                            <br><small class="text-warning">
                                                <i class="bx bx-error-circle me-1"></i>
                                                <?= get_label('file_not_found', 'File not found') ?>
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($migration['datetime']))
                                            <small class="text-muted">{{ $migration['datetime'] }}</small>
                                        @elseif(isset($migration['timestamp']))
                                            <small class="text-muted">{{ $migration['timestamp'] }}</small>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <?= get_label('pending', 'Pending') ?>
                                        </span>
                                    </td>
                                    <td>
                                        @if(!isset($migration['file_exists']) || $migration['file_exists'] !== false)
                                            <a href="{{ url('/migrate/file/' . ($migration['filename'] ?? '')) }}"
                                               class="btn btn-sm btn-primary"
                                               onclick="return confirm('<?= get_label('run_this_migration', 'Run this migration?') ?>');">
                                                <i class="bx bx-play me-1"></i>
                                                <?= get_label('run', 'Run') ?>
                                            </a>
                                        @else
                                            <span class="badge bg-warning">
                                                <?= get_label('file_not_found', 'File Not Found') ?>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-1"></i>
                    <?= get_label('no_pending_migrations', 'No pending migrations found.') ?>
                </div>
            @endif
        </div>
    </div>

    <!-- Ran Migrations (Collapsible) -->
    <div class="card border-0 shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <a class="text-decoration-none" data-bs-toggle="collapse" href="#ranMigrationsCollapse" role="button">
                    <?= get_label('ran_migrations', 'Ran Migrations') ?>
                    <span class="badge bg-success">{{ $ranCount ?? 0 }}</span>
                    <i class="bx bx-chevron-down ms-1"></i>
                </a>
            </h5>
        </div>
        <div class="collapse" id="ranMigrationsCollapse">
            <div class="card-body">
                @if(count($ranMigrations ?? []) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?= get_label('migration_file', 'Migration File') ?></th>
                                    <th><?= get_label('timestamp', 'Timestamp') ?></th>
                                    <th><?= get_label('status', 'Status') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ranMigrations ?? [] as $index => $migration)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <code>{{ $migration['filename'] ?? '' }}</code>
                                        </td>
                                        <td>
                                            @if(isset($migration['datetime']))
                                                <small class="text-muted">{{ $migration['datetime'] }}</small>
                                            @elseif(isset($migration['timestamp']))
                                                <small class="text-muted">{{ $migration['timestamp'] }}</small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?= get_label('ran', 'Ran') ?>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        <?= get_label('no_ran_migrations', 'No ran migrations found.') ?>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/pages/migrations.js') }}"></script>
@endsection

