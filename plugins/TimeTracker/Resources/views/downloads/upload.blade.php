@extends('layout')
@section('title', get_label('upload_time_tracker_app','Upload Time Tracker App'))
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
                        <li class="breadcrumb-item">
                           <a href="{{ route('timetracker.downloads.index') }}">
                               <?= get_label('downloads', 'Downloads') ?>
                           </a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('upload', 'Upload') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="alert alert-primary" role="alert">
            {{ get_label('file_upload_info', 'Upload any setup or file (installers, archives, documents, etc.). Give it a title so it is easy to find. Platform / version are optional — fill them only for OS app builds. Uploaded files get a shareable public download link.') }}
        </div>
        <div class="card">
            <div class="card-body">
                <form class="form-submit-event" action="{{ route('timetracker.downloads.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="redirect_url" value="{{ route('timetracker.downloads.index') }}" >
                    <div class="mb-3">
                        <label class="form-label">{{ get_label('title','Title') }}</label>
                        <input type="text" name="title" class="form-control" placeholder="{{ get_label('eg_setup_name','e.g. Onboarding Setup') }}">
                        <small class="text-muted">{{ get_label('title_hint','Leave blank to use the file name.') }}</small>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ get_label('platform','Platform') }} <span class="text-muted">({{ get_label('optional','optional') }})</span></label>
                            <select name="platform" class="form-control">
                                <option value="">{{ get_label('none','None') }}</option>
                                <option value="windows">{{ get_label('windows','Windows') }}</option>
                                <option value="mac">{{ get_label('macOS','macOS') }}</option>
                                <option value="linux">{{ get_label('linux','Linux') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ get_label('architecture','Architecture') }} <span class="text-muted">({{ get_label('optional','optional') }})</span></label>
                            <select name="arch" class="form-control">
                                <option value="">{{ get_label('default','Default') }}</option>
                                <option value="x64">{{ get_label('x64','x64') }}</option>
                                <option value="arm64">{{ get_label('arm64','arm64') }}</option>
                                <option value="intel">{{ get_label('Intel','Intel') }}</option>
                                <option value="m1">{{ get_label('m1','M1') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ get_label('version','Version') }} <span class="text-muted">({{ get_label('optional','optional') }})</span></label>
                            <input type="text" name="version" class="form-control" placeholder="e.g. 1.0.3">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ get_label('upload_file','Upload File') }}</label>
                        <input type="file" name="file" class="form-control" required>
                        <small class="text-muted">{{ get_label('max_file_size_500','Max 500 MB. Script/executable web files (.php, .html, .svg, …) are not allowed.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ get_label('description_optional','Description / Changelog (Optional)') }}</label>
                        <textarea name="changelog" rows="3" class="form-control"></textarea>
                    </div>
                    <button type="submit" id="submit_btn" class="btn btn-primary">
                        <i class="bx bx-upload me-1"></i> {{ get_label('upload','Upload') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
