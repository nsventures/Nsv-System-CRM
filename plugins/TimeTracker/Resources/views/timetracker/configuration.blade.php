@extends('layout')
@section('title')
    {{ get_label('configuration', 'Configuration') }}
@endsection
@section('content')
    @php
        // Static dropdown values in seconds
        $screenshotOptions = [15, 30, 60, 120]; // Screenshot intervals
        $idleOptions = [60, 120, 300, 600, 900]; // Idle time thresholds
        $breakOptions = [300, 600, 900, 1200, 1800, 3600]; // Break time thresholds
        $maxBreakOptions = [900, 1800, 2700, 3600, 7200]; // in seconds
        $autoDeleteOptions = [7, 14, 30, 45, 60, 120]; // Auto-delete screenshot options

        function displayTime($seconds)
        {
            if ($seconds < 60) {
                return $seconds . ' seconds';
            }
            $minutes = $seconds / 60;
            return $minutes . ' ' . ($minutes == 1 ? 'minute' : 'minutes');
        }
    @endphp

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
                            <?= get_label('configuration', 'Configuration') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="alert alert-primary" role="alert">
            {{ get_label('configuration_info', 'Please note that all values are entered in seconds and will be stored in milliseconds.') }}
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('timetracker.configuration.store') }}" class="form-submit-event" method="POST">
                    <input type="hidden" name="dnr">
                    @csrf
                    @method('PUT')
                    <div class="row">

                        {{-- Screenshot Interval --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-check-label" for="screenshotInterval">
                                {{ get_label('screenshot_interval', 'Screenshot Interval') }}
                            </label>
                            <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-original-title="{{ get_label('screenshot_interval_info', 'Interval between automatic screenshots during active tracking.') }}"></i>
                            <select class="form-select" name="screenshotInterval" id="screenshotInterval">
                                @foreach ($screenshotOptions as $option)
                                    <option value="{{ $option }}"
                                        {{ old('screenshotInterval', $time_tracker_config['screenshotInterval'] / 1000) == $option ? 'selected' : '' }}>
                                        {{ displayTime($option) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Idle Time Threshold --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-check-label" for="idleTimeThreshold">
                                {{ get_label('idle_time_threshold', 'Idle Time Threshold') }}
                            </label>
                            <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-original-title="{{ get_label('idle_time_threshold_info', 'Duration of inactivity before marking user as idle.') }}"></i>
                            <select class="form-select" name="idleTimeThreshold" id="idleTimeThreshold">
                                @foreach ($idleOptions as $option)
                                    <option value="{{ $option }}"
                                        {{ old('idleTimeThreshold', $time_tracker_config['idleTimeThreshold'] / 1000) == $option ? 'selected' : '' }}>
                                        {{ displayTime($option) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Break Time Threshold --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-check-label" for="breakTimeThreshold">
                                {{ get_label('break_time_threshold', 'Break Time Threshold') }}
                            </label>
                            <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-original-title="{{ get_label('break_time_threshold_info', 'Minimum duration for a break to be logged.') }}"></i>
                            <select class="form-select" name="breakTimeThreshold" id="breakTimeThreshold">
                                @foreach ($breakOptions as $option)
                                    <option value="{{ $option }}"
                                        {{ old('breakTimeThreshold', $time_tracker_config['breakTimeThreshold'] / 1000) == $option ? 'selected' : '' }}>
                                        {{ displayTime($option) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-check-label" for="maxDailyBreakTime">
                                {{ get_label('max_daily_break_time', 'Max Break Allowed Per Day') }}
                            </label>
                            <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-original-title="{{ get_label('max_daily_break_time_info', 'Maximum total break time allowed per user per day.') }}"></i>
                            <select class="form-select" name="maxDailyBreakTime" id="maxDailyBreakTime">
                                @foreach ($maxBreakOptions as $option)
                                    <option value="{{ $option }}"
                                        {{ old('maxDailyBreakTime', $time_tracker_config['maxDailyBreakTime'] / 1000 ?? 3600) == $option ? 'selected' : '' }}>
                                        {{ displayTime($option) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-check-label" for="manualTimeApprover">
                                {{ get_label('manual_time_approver', 'Manual Time Approvers') }}
                            </label>
                            <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-original-title="{{ get_label('manual_time_approver_info', 'Users allowed to approve manually added time entries.') }}"></i>
                            <select class="form-select js-example-basic-multiple" name="manualTimeApprover[]"
                                id="manualTimeApprover" multiple>
                                @foreach ($users as $id => $fullName)
                                    <option value="{{ $id }}"
                                        {{ in_array($id, old('manualTimeApprover', $time_tracker_config['manualTimeApprover'] ?? [])) ? 'selected' : '' }}>
                                        {{ $fullName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-check-label" for="workDayStartTime">
                                {{ get_label('work_day_start_time', 'Work Day Start Time') }}
                            </label>
                            <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-original-title="{{ get_label('work_day_start_time_info', 'The default start time for the work day for all users.') }}"></i>
                            </label>
                            <input type="time" class="form-control" name="workDayStartTime" id="workDayStartTime"
                                value="{{ old('workDayStartTime', $time_tracker_config['workDayStartTime'] ?? '09:00') }}">

                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-check-label" for="auto_delete_screenshots_after_days">
                                {{ get_label('auto_delete_screenshots', 'Auto-Delete Screenshots After (Days)') }}
                            </label>
                            <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-original-title="{{ get_label('auto_delete_screenshots_info', 'Screenshots older than the selected number of days will be automatically deleted.') }}"></i>
                            <select class="form-select" name="auto_delete_screenshots_after_days"
                                id="auto_delete_screenshots_after_days">
                                @foreach ($autoDeleteOptions as $option)
                                    <option value="{{ $option }}"
                                        {{ old('auto_delete_screenshots_after_days', $time_tracker_config['auto_delete_screenshots_after_days'] ?? 30) == $option ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        {{-- Buttons --}}
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2" id="submit_btn">
                                {{ get_label('update', 'Update') }}
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                {{ get_label('cancel', 'Cancel') }}
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
