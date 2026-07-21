@extends('layout')
@section('title')
<?= get_label('general_settings', 'General settings') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h4 class="fw-bold mb-0"><?= get_label('general_settings', 'General Settings') ?></h4>
        <div class="d-flex align-items-center gap-3">
            <nav class="breadcrumb mb-0" aria-label="breadcrumb">
                <a class="breadcrumb-item" href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-item"><?= get_label('settings', 'Settings') ?></span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?= get_label('general', 'General') ?></span>
            </nav>
        </div>
    </div>

    <form action="{{url('settings/store_general')}}" class="form-submit-event" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="dnr">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-xl-8 col-lg-8 col-md-12">
                
                <!-- Basic Information -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-info-circle me-2'></i> <?= get_label('basic_information', 'Basic Information') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="company_title" class="form-label"><?= get_label('company_title', 'Company Title') ?> <span class="asterisk">*</span></label>
                                <input class="form-control" type="text" id="company_title" name="company_title" placeholder="<?= get_label('please_enter_company_title', 'Please enter company title') ?>" value="{{ $general_settings['company_title'] }}">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="site_url" class="form-label">
                                    <?= get_label('site_url', 'Site URL') ?> <span class="asterisk">*</span>
                                    <small class="text-muted fw-normal ms-1">
                                        (<?= get_label('enter_site_url_without_trailing_slash', 'Enter the site URL without a trailing slash') ?>, e.g., https://example.com)
                                    </small>
                                </label>
                                <input class="form-control" type="text" id="site_url" name="site_url" placeholder="<?= get_label('please_enter_site_url', 'Please enter site URL') ?>" value="{{ $general_settings['site_url'] }}">
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="full_logo" class="form-label"><?= get_label('full_logo', 'Full Logo') ?> <a data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="<?= get_label('view_current_full_logo', 'View current full logo') ?>" href="{{asset($general_settings['full_logo'])}}" data-lightbox="full logo" data-title="<?= get_label('current_full_logo', 'Current full logo') ?>"> <i class='bx bx-show-alt text-primary'></i></a></label>
                                <input type="file" class="form-control" id="full_logo" name="full_logo">
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="favicon" class="form-label"><?= get_label('favicon', 'Favicon') ?> <a data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="<?= get_label('view_current_favicon', 'View current favicon') ?>" href="{{asset($general_settings['favicon'])}}" data-lightbox="favicon" data-title="<?= get_label('current_favicon', 'Current favicon') ?>"> <i class='bx bx-show-alt text-primary'></i></a></label>
                                <input type="file" class="form-control" id="favicon" name="favicon">
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="half_logo" class="form-label"><?= get_label('half_logo', 'Half Logo') ?> <a data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="<?= get_label('view_current_half_logo', 'View current half logo') ?>" href="{{asset($general_settings['half_logo'])}}" data-lightbox="half_logo" data-title="<?= get_label('current_half_logo', 'Current half logo') ?>"> <i class='bx bx-show-alt text-primary'></i></a></label>
                                <input type="file" class="form-control" id="half_logo" name="half_logo">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Date & Time Settings -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-time me-2'></i> <?= get_label('date_time_settings', 'Date & Time Settings') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="timezone"><?= get_label('system_time_zone', 'System Time Zone') ?> <span class="asterisk">*</span></label>
                                <select class="form-select select2" id="timezone" name="timezone" data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                                    <option value=""><?= get_label('select_time_zone', 'Select time zone') ?></option>
                                    @foreach ($timezones as $timezone)
                                    <option value="{{ $timezone['2'] }}" data-gmt="<?= $timezone[1] ?>" {{ $general_settings['timezone']==$timezone[2]?'selected':'' }}>
                                        <span class="lh-lg">
                                            {{ $timezone['2'] }} &nbsp; - &nbsp; GMT &nbsp; {{ $timezone['1'] }} &nbsp; - &nbsp; {{ $timezone['0'] }}
                                        </span>
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="date_format"><?= get_label('date_format', 'Date Format') ?> <span class="asterisk">*</span></label>
                                <select class="form-select select2" id="date_format" name="date_format" data-placeholder="<?= get_label('type_to_search', 'Type to search') ?>">
                                    <option value=""><?= get_label('select_date_format', 'Select date format') ?></option>
                                    <option value="DD-MM-YYYY|d-m-Y" <?= $general_settings['date_format'] == 'DD-MM-YYYY|d-m-Y' ? 'selected' : '' ?>>Day-Month-Year with leading zero (04-08-2023)</option>
                                    <option value="D-M-YY|j-n-y" <?= $general_settings['date_format'] == 'D-M-YY|j-n-y' ? 'selected' : '' ?>>Day-Month-Year with no leading zero (4-8-23)</option>
                                    <option value="MM-DD-YYYY|m-d-Y" <?= $general_settings['date_format'] == 'MM-DD-YYYY|m-d-Y' ? 'selected' : '' ?>>Month-Day-Year with leading zero (08-04-2023)</option>
                                    <option value="M-D-YY|n-j-y" <?= $general_settings['date_format'] == 'M-D-YY|n-j-y' ? 'selected' : '' ?>>Month-Day-Year with no leading zero (8-4-23)</option>
                                    <option value="YYYY-MM-DD|Y-m-d" <?= $general_settings['date_format'] == 'YYYY-MM-DD|Y-m-d' ? 'selected' : '' ?>>Year-Month-Day with leading zero (2023-08-04)</option>
                                    <option value="YY-M-D|Y-n-j" <?= $general_settings['date_format'] == 'YY-M-D|Y-n-j' ? 'selected' : '' ?>>Year-Month-Day with no leading zero (23-8-4)</option>
                                    <option value="MMMM DD, YYYY|F d, Y" <?= $general_settings['date_format'] == 'MMMM DD, YYYY|F d, Y' ? 'selected' : '' ?>>Month name-Day-Year with leading zero (August 04, 2023)</option>
                                    <option value="MMM DD, YYYY|M d, Y" <?= $general_settings['date_format'] == 'MMM DD, YYYY|M d, Y' ? 'selected' : '' ?>>Month abbreviation-Day-Year with leading zero (Aug 04, 2023)</option>
                                    <option value="DD-MMM-YYYY|d-M-Y" <?= $general_settings['date_format'] == 'DD-MMM-YYYY|d-M-Y' ? 'selected' : '' ?>>Day with leading zero, Month abbreviation, Year (04-Aug-2023)</option>
                                    <option value="DD MMM, YYYY|d M, Y" <?= $general_settings['date_format'] == 'DD MMM, YYYY|d M, Y' ? 'selected' : '' ?>>Day with leading zero, Month abbreviation, Year (04 Aug, 2023)</option>
                                    <option value="YYYY-MMM-DD|Y-M-d" <?= $general_settings['date_format'] == 'YYYY-MMM-DD|Y-M-d' ? 'selected' : '' ?>>Year, Month abbreviation, Day with leading zero (2023-Aug-04)</option>
                                    <option value="YYYY, MMM DD|Y, M d" <?= $general_settings['date_format'] == 'YYYY, MMM DD|Y, M d' ? 'selected' : '' ?>>Year, Month abbreviation, Day with leading zero (2023, Aug 04)</option>
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="time_format" class="form-label"><?= get_label('time_format', 'Time Format') ?></label>
                                <select class="form-select" name="time_format" id="time_format">
                                    <option value="H:i:s" {{ old('time_format', $general_settings['time_format'] ?? '') == 'H:i:s' ? 'selected' : '' }}>24-hour format - 15:45:30</option>
                                    <option value="h:i:s A" {{ old('time_format', $general_settings['time_format'] ?? '') == 'h:i:s A' ? 'selected' : '' }}>12-hour format AM/PM uppercase - 03:45:30 PM</option>
                                    <option value="h:i:s a" {{ old('time_format', $general_settings['time_format'] ?? '') == 'h:i:s a' ? 'selected' : '' }}>12-hour format AM/PM lowercase - 03:45:30 pm</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leave Settings -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-calendar-x me-2'></i> <?= get_label('leave_settings', 'Leave Settings') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="total_paid_leaves_per_year" class="form-label">{{ get_label('total_paid_leaves_per_year', 'Total Paid Leaves / Year') }} <span class="asterisk">*</span></label>
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('total_paid_leaves_info', 'Set the total number of paid leaves each user is entitled to per year.') }}"></i>
                                <input class="form-control" type="number" id="total_paid_leaves_per_year" name="total_paid_leaves_per_year" placeholder="12" value="{{ $general_settings['total_paid_leaves_per_year'] ?? 15 }}" min="0" step="0.5">
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="leave_accrual_type" class="form-label">{{ get_label('leave_accrual_type', 'Leave Accrual Type') }}</label>
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('leave_accrual_type_info', 'Choose how leaves are allocated: Lump Sum (all at once on Jan 1) or Monthly Accrual (earned monthly).') }}"></i>
                                <select class="form-select" id="leave_accrual_type" name="leave_accrual_type">
                                    <option value="lump_sum" {{ ($general_settings['leave_accrual_type'] ?? 'monthly') == 'lump_sum' ? 'selected' : '' }}>
                                        {{ get_label('lump_sum', 'Lump Sum (All at once)') }}
                                    </option>
                                    <option value="monthly" {{ ($general_settings['leave_accrual_type'] ?? 'monthly') == 'monthly' ? 'selected' : '' }}>
                                        {{ get_label('monthly_accrual', 'Monthly Accrual') }}
                                    </option>
                                </select>
                                <small class="text-muted d-block mt-1" id="accrual_rate_display">
                                    Monthly rate: <strong>{{ round(($general_settings['total_paid_leaves_per_year'] ?? 15) / 12, 2) }}</strong> days/month
                                </small>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="company_year_start" class="form-label">{{ get_label('company_year_start', 'Company Year Start') }} <span class="asterisk">*</span></label>
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('company_year_start_info', 'Set when your company/fiscal year begins (Month-Day). Leave balances will reset on this date annually. E.g., 01-01 for Jan 1 or 04-01 for Apr 1.') }}"></i>
                                @php
                                    $startMonth = $general_settings['company_year_start_month'] ?? 1;
                                    $startDay = $general_settings['company_year_start_day'] ?? 1;
                                    $startValue = sprintf('%02d-%02d', $startMonth, $startDay);
                                @endphp
                                <input class="form-control" type="text" id="company_year_start" name="company_year_start" placeholder="01-01" value="{{ $startValue }}" pattern="\d{2}-\d{2}">
                                <small class="text-muted d-block mt-1">Format: MM-DD</small>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="company_year_end" class="form-label">{{ get_label('company_year_end', 'Company Year End') }} <span class="asterisk">*</span></label>
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('company_year_end_info', 'Set when your company/fiscal year ends (Month-Day). E.g., 12-31 for Dec 31 or 03-31 for Mar 31.') }}"></i>
                                @php
                                    $endMonth = $general_settings['company_year_end_month'] ?? 12;
                                    $endDay = $general_settings['company_year_end_day'] ?? 31;
                                    $endValue = sprintf('%02d-%02d', $endMonth, $endDay);
                                @endphp
                                <input class="form-control" type="text" id="company_year_end" name="company_year_end" placeholder="12-31" value="{{ $endValue }}" pattern="\d{2}-\d{2}">
                                <small class="text-muted d-block mt-1" id="company_year_display">
                                    @php
                                        $startMonthName = \DateTime::createFromFormat('!m', $startMonth)->format('M');
                                        $endMonthName = \DateTime::createFromFormat('!m', $endMonth)->format('M');
                                    @endphp
                                    Period: <strong>{{ $startMonthName }} {{ $startDay }} - {{ $endMonthName }} {{ $endDay }}</strong>
                                </small>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="initialize_leave_balances_btn">
                                        <i class='bx bx-refresh'></i> {{ get_label('initialize_recalculate_balances', 'Recalculate Balances') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Text -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-text me-2'></i> <?= get_label('footer_text', 'Footer text') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <textarea id="footer_text" name="footer_text" class="form-control"><?= $general_settings['footer_text'] ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- End Left Column -->

            <!-- Right Column -->
            <div class="col-xl-4 col-lg-4 col-md-12">
                
                <!-- Currency Settings -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-dollar-circle me-2'></i> <?= get_label('currency_settings', 'Currency Settings') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <label for="currency_full_form" class="form-label"><?= get_label('currency_full_form', 'Currency Full Form') ?> <span class="asterisk">*</span></label>
                                <input class="form-control" type="text" id="currency_full_form" name="currency_full_form" placeholder="<?= get_label('please_enter_currency_full_form', 'Please enter currency full form') ?>" value="{{$general_settings['currency_full_form']}}">
                            </div>
                            <div class="mb-3 col-md-12">
                                <label for="currency_symbol" class="form-label"><?= get_label('currency_symbol', 'Currency Symbol') ?> <span class="asterisk">*</span></label>
                                <input class="form-control" type="text" id="currency_symbol" name="currency_symbol" placeholder="<?= get_label('please_enter_currency_symbol', 'Please enter currency symbol') ?>" value="{{$general_settings['currency_symbol']}}">
                            </div>
                            <div class="mb-3 col-md-12">
                                <label for="currency_code" class="form-label"><?= get_label('currency_code', 'Currency Code') ?> <span class="asterisk">*</span></label>
                                <input class="form-control" type="text" id="currency_code" name="currency_code" placeholder="<?= get_label('please_enter_currency_code', 'Please enter currency code') ?>" value="{{$general_settings['currency_code']}}">
                            </div>
                            <div class="mb-3 col-md-12">
                                <label for="currency_symbol_position" class="form-label"><?= get_label('currency_symbol_position', 'Currency Symbol Position') ?></label>
                                <select class="form-select" name="currency_symbol_position" id="currency_symbol_position">
                                    <option value="before" {{ old('currency_symbol_position', $general_settings['currency_symbol_position']) == 'before' ? 'selected' : '' }}><?= get_label('before', 'Before') ?> - $100</option>
                                    <option value="after" {{ old('currency_symbol_position', $general_settings['currency_symbol_position']) == 'after' ? 'selected' : '' }}><?= get_label('after', 'After') ?> - 100$</option>
                                </select>
                            </div>
                            <div class="mb-3 col-md-12">
                                <label for="currency_formate" class="form-label"><?= get_label('currency_formate', 'Currency Format') ?></label>
                                <select class="form-select" name="currency_formate" id="currency_formate">
                                    <option value="comma_separated" {{ old('currency_formate', $general_settings['currency_formate']) == 'comma_separated' ? 'selected' : '' }}><?= get_label('comma_separated', 'Comma Separated') ?> - 100,000</option>
                                    <option value="dot_separated" {{ old('currency_formate', $general_settings['currency_formate']) == 'dot_separated' ? 'selected' : '' }}><?= get_label('dot_separated', 'Dot Separated') ?> - 100.000</option>
                                </select>
                            </div>
                            <div class="mb-3 col-md-12">
                                <label for="decimal_points_in_currency" class="form-label"><?= get_label('decimal_points_in_currency', 'Decimal Points in Currency') ?></label>
                                <input class="form-control" type="number" id="decimal_points_in_currency" name="decimal_points_in_currency" step="1" placeholder="e.g. 2 for 100.00" value="{{$general_settings['decimal_points_in_currency']}}" oninput="this.value = Math.floor(this.value)" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Dashboard Sections -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-layout me-2'></i> <?= get_label('dashboard_sections', 'Dashboard Sections') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="mb-4">
                            <label class="form-check-label mb-2 d-block" for="upcomingBirthdays">
                                {{ get_label('upcoming_birthdays_section', 'Upcoming birthdays section') }} 
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('enable_upcoming_birthdays_section', 'Enable or disable showing the upcoming birthdays section on the dashboard page.') }}"></i>
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="upcomingBirthdays" name="upcomingBirthdays" @if (!isset($general_settings['upcomingBirthdays']) || $general_settings['upcomingBirthdays']==1) checked @endif>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-check-label mb-2 d-block" for="upcomingWorkAnniversaries">
                                {{ get_label('upcoming_work_anniversaries_section', 'Upcoming work anniversaries section') }}
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('enable_upcoming_work_anniversaries_section', 'Enable or disable showing the upcoming work anniversaries section on the dashboard page.') }}"></i>
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="upcomingWorkAnniversaries" name="upcomingWorkAnniversaries" @if (!isset($general_settings['upcomingWorkAnniversaries']) || $general_settings['upcomingWorkAnniversaries']==1) checked @endif>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-check-label mb-2 d-block" for="membersOnLeave">
                                {{ get_label('members_on_leave_section', 'Members on leave section') }}
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('enable_mol_section', 'Enable or disable showing the members on leave section on the dashboard page.') }}"></i>
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="membersOnLeave" name="membersOnLeave" @if (!isset($general_settings['membersOnLeave']) || $general_settings['membersOnLeave']==1) checked @endif>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appearance Settings -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-palette me-2'></i> <?= get_label('appearance_settings', 'Appearance Settings') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="mb-3">
                            <label for="primaryColorPicker" class="form-label"><?= get_label('primary_color', 'Primary Color') ?></label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="color" class="form-control form-control-color" name="primary_color" id="primaryColorPicker" title="<?= get_label('choose_your_color', 'Choose your color') ?>" value="{{ $general_settings['primary_color'] ?? '#696cff' }}">
                                <button type="button" class="btn btn-outline-warning btn-sm" id="btnResetPrimaryColor" onclick="document.getElementById('primaryColorPicker').value='#696cff'; document.documentElement.style.setProperty('--signal', '#696cff'); document.documentElement.style.setProperty('--bs-primary', '#696cff'); document.documentElement.style.setProperty('--bs-primary-rgb', '105, 108, 255'); localStorage.removeItem('taskify.primaryColor');"><?= get_label('reset_to_default', 'Reset') ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Authentication Settings -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-shield-quarter me-2'></i> <?= get_label('authentication_settings', 'Authentication Settings') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="mb-3">
                            <label class="form-check-label mb-2 d-block" for="priLangAsAuth">
                                {{ get_label('primary_language_auth', 'Primary Language for Auth') }}
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('use_primary_lang_for_auth_interfaces', 'Use the primary language chosen by the main admin for the signup, login, forgot password, and reset password interfaces.') }}"></i>
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="priLangAsAuth" name="priLangAsAuth" @if (!isset($general_settings['priLangAsAuth']) || $general_settings['priLangAsAuth']==1) checked @endif>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h6 class="card-title mb-0 text-secondary"><i class='bx bx-bell me-2'></i> <?= get_label('notification_settings', 'Notification Settings') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="mb-4">
                            <label for="toastPosition" class="form-label mb-2 d-block">
                                <?= get_label('toast_message_position', 'Toast Message Position') ?>
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('toast_position_info', 'Choose where on the screen toast messages will appear.') ?>"></i>
                            </label>
                            <select id="toastPosition" class="form-select" name="toast_position">
                                <option value="toast-top-right" {{$general_settings['toast_position'] == 'toast-top-right' ? 'selected' : ''}}>{{get_label('top_right','Top Right')}}</option>
                                <option value="toast-top-left" {{$general_settings['toast_position'] == 'toast-top-left' ? 'selected' : ''}}>{{get_label('top_left','Top Left')}}</option>
                                <option value="toast-bottom-right" {{$general_settings['toast_position'] == 'toast-bottom-right' ? 'selected' : ''}}>{{get_label('bottom_right','Bottom Right')}}</option>
                                <option value="toast-bottom-left" {{$general_settings['toast_position'] == 'toast-bottom-left' ? 'selected' : ''}}>{{get_label('bottom_left','Bottom Left')}}</option>
                                <option value="toast-top-full-width" {{$general_settings['toast_position'] == 'toast-top-full-width' ? 'selected' : ''}}>{{get_label('top_full_width','Top Full Width')}}</option>
                                <option value="toast-bottom-full-width" {{$general_settings['toast_position'] == 'toast-bottom-full-width' ? 'selected' : ''}}>{{get_label('bottom_full_width','Bottom Full Width')}}</option>
                                <option value="toast-top-center" {{$general_settings['toast_position'] == 'toast-top-center' ? 'selected' : ''}}>{{get_label('top_center','Top Center')}}</option>
                                <option value="toast-bottom-center" {{$general_settings['toast_position'] == 'toast-bottom-center' ? 'selected' : ''}}>{{get_label('bottom_center','Bottom Center')}}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="toastTimeout" class="form-label mb-2 d-block">
                                <?= get_label('toast_message_time_out', 'Toast Message Time Out') ?>
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('toast_time_out_info', 'Set the duration (in seconds) for how long toast messages will be displayed. The default is 5 seconds.') ?>"></i>
                            </label>
                            <input id="toastTimeout" class="form-control" type="number" name="toast_time_out" step="0.1" placeholder="5" value="{{$general_settings['toast_time_out']}}" min="0.1">
                        </div>
                        <div class="mb-3">
                            <button id="previewToast" class="btn btn-outline-primary w-100" type="button"><i class='bx bx-show me-1'></i> {{get_label('preview_toast','Preview Toast')}}</button>
                        </div>
                    </div>
                </div>

            </div> <!-- End Right Column -->

            <!-- Full Width Action Buttons -->
            <div class="col-12 mt-3">
                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary"><?= get_label('cancel', 'Cancel') ?></button>
                    <button type="submit" class="btn btn-primary" id="submit_btn"><i class='bx bx-save me-1'></i> <?= get_label('save_changes', 'Save Changes') ?></button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Month names array
    var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Update monthly accrual rate display when total leaves changes
    $('#total_paid_leaves_per_year').on('input change', function() {
        var totalLeaves = parseFloat($(this).val()) || 15;
        var monthlyRate = (totalLeaves / 12).toFixed(2);
        $('#accrual_rate_display').html('Monthly rate: <strong>' + monthlyRate + '</strong> days/month');
    });

    // Update company year display when start/end changes
    function updateCompanyYearDisplay() {
        var startVal = $('#company_year_start').val() || '01-01';
        var endVal = $('#company_year_end').val() || '12-31';

        var startParts = startVal.split('-');
        var endParts = endVal.split('-');

        if (startParts.length == 2 && endParts.length == 2) {
            var startMonth = parseInt(startParts[0]) - 1; // 0-indexed
            var startDay = parseInt(startParts[1]);
            var endMonth = parseInt(endParts[0]) - 1;
            var endDay = parseInt(endParts[1]);

            if (startMonth >= 0 && startMonth < 12 && endMonth >= 0 && endMonth < 12) {
                var displayText = 'Period: <strong>' + monthNames[startMonth] + ' ' + startDay +
                                 ' - ' + monthNames[endMonth] + ' ' + endDay + '</strong>';
                $('#company_year_display').html(displayText);
            }
        }
    }

    $('#company_year_start, #company_year_end').on('input change blur', function() {
        updateCompanyYearDisplay();
    });

    // Initialize leave balances button
    $('#initialize_leave_balances_btn').on('click', function() {
        var btn = $(this);
        var originalText = btn.html();

        // Confirm action
        if (!confirm('This will initialize missing leave balances and recalculate existing balances for all users based on their approved leaves. Continue?')) {
            return;
        }

        // Disable button and show loading
        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Initializing...');

        $.ajax({
            url: '/settings/initialize-leave-balances',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (!response.error) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Error initializing leave balances. Please try again.');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Appearance Settings: Live preview and localStorage
    function hexToRgb(hex) {
        var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
        hex = hex.replace(shorthandRegex, function(m, r, g, b) {
            return r + r + g + g + b + b;
        });
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? parseInt(result[1], 16) + ", " + parseInt(result[2], 16) + ", " + parseInt(result[3], 16) : null;
    }

    $('#primaryColorPicker').on('input', function() {
        var val = $(this).val();
        document.documentElement.style.setProperty('--signal', val);
        document.documentElement.style.setProperty('--bs-primary', val);
        var rgb = hexToRgb(val);
        if (rgb) {
            document.documentElement.style.setProperty('--bs-primary-rgb', rgb);
        }
    });
    
    $('#primaryColorPicker').on('change', function() {
        localStorage.setItem("taskify.primaryColor", $(this).val());
    });
});
</script>
@endsection
