@extends('layout')
@section('title')
    {{ get_label('google_calendar', 'Google Calendar') }}
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h4 class="fw-bold mb-0" style="font-size: 1.35rem;">{{ get_label('google_calendar', 'Google Calendar') }}</h4>
        <div class="d-flex align-items-center gap-3">
            <nav class="breadcrumb mb-0" aria-label="breadcrumb">
                <a class="breadcrumb-item" href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-item">{{ get_label('settings', 'Settings') }}</span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current">{{ get_label('google_calendar', 'Google Calendar') }}</span>
            </nav>
        </div>
    </div>
    
    <div class="card mb-3 shadow-none border">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                <i class='bx bx-calendar me-2 text-secondary fs-5'></i>{{ get_label('google_calendar_settings', 'Google Calendar Settings') }}
            </h6>
            <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#google_calender_instruction_modal">
                <i class='bx bx-help-circle me-1'></i>{{ get_label('click_for_help', 'Help') }}
            </button>
        </div>
        <div class="card-body pt-3 px-3 pb-3">
            <div class="alert alert-light-primary border-0 shadow-none d-flex align-items-center py-2 px-3 mb-3" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                <i class="bx bx-info-circle me-2 fs-5 text-primary"></i>
                <div>
                    {{ get_label('documentation_for_integration_with_google_calendar', 'Documentation for integration with Google Calendar') }}.
                </div>
            </div>
            
            <form action="{{ route('google_calendar.store') }}" class="form-submit-event" method="POST">
                <input type="hidden" name="dnr">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="api_key">{{ get_label('api_key', 'API Key') }}</label>
                        <input class="form-control form-control-sm" type="text" name="api_key" id="api_key"
                            placeholder="{{ get_label('please_enter_your_google_api_key', 'Please Enter Your Google API Key') }}"
                            value="{{ config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($google_calendar_settings['api_key'])) : $google_calendar_settings['api_key'] }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="calendar_id">{{ get_label('google_calendar_id', 'Google Calendar ID') }}</label>
                        <input class="form-control form-control-sm" type="text" name="calendar_id" id="calendar_id"
                            placeholder="{{ get_label('please_enter_your_google_calendar_id', 'Please Enter Your Google Calendar ID') }}"
                            value="{{ config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($google_calendar_settings['calendar_id'])) : $google_calendar_settings['calendar_id'] }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="calendar_name">{{get_label('calendar_name','Calendar Name')}}</label>
                        <input class="form-control form-control-sm" type="text" name="calendar_name" id="calendar_name"
                            placeholder="{{ get_label('enter_your_calendar_name_to_be_displayed','Enter your calendar name to be displayed') }}"
                            value="{{ config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($google_calendar_settings['calendar_name'] ?? '')) : ($google_calendar_settings['calendar_name'] ?? '') }}">
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3 col-12">
                        <button type="reset" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;">{{ get_label('cancel', 'Cancel') }}</button>
                        <button type="submit" class="btn btn-xs btn-primary py-1 px-3" style="font-size: 0.8rem;" id="submit_btn"><i class='bx bx-save me-1'></i> {{ get_label('update', 'Update') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="google_calender_instruction_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ get_label('google_calendar_integration', 'Google Calendar Integration') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4>📌 Step 1: Create a Google Cloud Project</h4>
                <ol>
                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>.</li>
                    <li>Click <b>Select a Project</b> → <b>New Project</b>.</li>
                    <li>Enter a <b>Project Name</b> (e.g., Taskify Calendar).</li>
                    <li>Click <b>Create</b> and wait for the project to be initialized.</li>
                </ol>
                <a href="{{ asset('storage/google-calendar/create_new_project.png') }}" data-lightbox="google-calendar"
                    data-title="Create Google Project">
                    <img src="{{ asset('storage/google-calendar/create_new_project.png') }}"
                        alt="Create Google Project" class="img-fluid mb-3 rounded border shadow-sm">
                </a>

                <h4>📌 Step 2: Enable Google Calendar API</h4>
                <ol>
                    <li>Inside your project, go to <b>API & Services</b> → <b>Library</b>.</li>
                    <li>Search for <b>Google Calendar API</b> and select it.</li>
                    <li>Click <b>Enable</b>.</li>
                </ol>
                <a href="{{ asset('storage/google-calendar/enable-google-calendar-api.png') }}"
                    data-lightbox="google-calendar" data-title="Enable Google Calendar API">
                    <img src="{{ asset('storage/google-calendar/enable-google-calendar-api.png') }}"
                        alt="Enable Calendar API" class="img-fluid mb-3 rounded border shadow-sm">
                </a>

                <h4>📌 Step 3: Generate API Credentials</h4>
                <ol>
                    <li>Go to <b>API & Services</b> → <b>Credentials</b>.</li>
                    <li>Click <b>Create Credentials</b> → <b>API Key</b>.</li>
                    <li>Your API Key will appear. <b>Copy it</b> for later use.</li>
                    <li>(Optional) Click <b>Restrict Key</b> and select <b>HTTP Referrer</b>.</li>
                    <li>Enter your domain (e.g., <code>https://yourdomain.com/*</code>).</li>
                    <li>Click <b>Save</b>.</li>
                </ol>
                <a href="{{ asset('storage/google-calendar/create-api-key.png') }}" data-lightbox="google-calendar"
                    data-title="Generate API Key">
                    <img src="{{ asset('storage/google-calendar/create-api-key.png') }}" alt="Generate API Key"
                        class="img-fluid mb-3 rounded border shadow-sm">
                </a>

                <h4>📌 Step 4: Make Your Google Calendar Public</h4>
                <ol>
                    <li>Go to <a href="https://calendar.google.com/" target="_blank">Google Calendar</a>.</li>
                    <li>Under <b>My Calendars</b>, hover over your calendar and click <b>Settings & Sharing</b>.</li>
                    <li>Under <b>Access Permissions</b>, check <b>Make available to public</b>.</li>
                    <li>Ensure <b>See all event details</b> is selected.</li>
                </ol>
                <a href="{{ asset('storage/google-calendar/make-google-calendar-public.png') }}"
                    data-lightbox="google-calendar" data-title="Make Google Calendar Public">
                    <img src="{{ asset('storage/google-calendar/make-google-calendar-public.png') }}"
                        alt="Make Google Calendar Public" class="img-fluid mb-3 rounded border shadow-sm">
                </a>

                <h4>📌 Step 5: Get Your Google Calendar ID</h4>
                <ol>
                    <li>Go to <b>Google Calendar</b> → <b>Settings & Sharing</b>.</li>
                    <li>Scroll down to <b>Integrate Calendar</b>.</li>
                    <li>Copy the <b>Calendar ID</b> (e.g., <code>abcd1234@group.calendar.google.com</code>).</li>
                </ol>
                <a href="{{ asset('storage/google-calendar/get-calendar-id.png') }}" data-lightbox="google-calendar"
                    data-title="Find Google Calendar ID">
                    <img src="{{ asset('storage/google-calendar/get-calendar-id.png') }}" alt="Find Google Calendar ID"
                        class="img-fluid mb-3 rounded border shadow-sm">
                </a>

                <h4>📌 Step 6: Update Taskify Settings</h4>
                <ol>
                    <li>Go to Taskify and paste your <b>API Key</b> and <b>Calendar ID</b>.</li>
                    <li>Click <b>Update</b>.</li>
                </ol>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
