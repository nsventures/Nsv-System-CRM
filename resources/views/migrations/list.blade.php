@extends('layout')
@section('title')
Migrations List
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
                        Migrations
                    </li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ url('/migrate') }}" class="btn btn-primary">
                <i class="bx bx-play-circle me-1"></i>
                Run All Migrations
            </a>
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
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Pending Migrations ({{ count($migrations) }} found)</h5>
        </div>
        <div class="card-body">
            @if(count($migrations) > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Migration File</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($migrations as $index => $migration)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <code>{{ $migration['filename'] }}</code>
                                        @if(isset($migration['file_exists']) && !$migration['file_exists'])
                                            <br><small class="text-warning"><i class="bx bx-error-circle me-1"></i>File not found in database/migrations</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!isset($migration['file_exists']) || $migration['file_exists'] !== false)
                                            <a href="{{ $migration['url'] }}" class="btn btn-sm btn-primary" onclick="return confirm('Run this migration?');">
                                                <i class="bx bx-play me-1"></i>
                                                Run
                                            </a>
                                        @else
                                            <span class="badge bg-warning">File Not Found</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    No pending migrations found.
                </div>
            @endif
        </div>
    </div>

    @if(isset($status))
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Migration Status</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-size: 12px;">{{ $status }}</pre>
            </div>
        </div>
    @endif
</div>
@endsection

