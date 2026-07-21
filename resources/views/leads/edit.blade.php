@extends('layout')
@section('title')
    {{ get_label('update_lead', 'Update Lead') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
            <!-- Left Side: Breadcrumbs and Badge -->
            <div class="d-flex align-items-center gap-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            {{ get_label('leads_management', 'Leads Management') }}
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('leads.index') }}">{{ get_label('leads', 'Leads') }}</a>
                        </li>
                        <li class="breadcrumb-item active">
                            {{ get_label('update', 'Update') }}
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('leads.update' ,['id' => $lead->id]) }}" method="POST" class="form-submit-event"
                    enctype="multipart/form-data">
                    <input type="hidden" name="redirect_url" value="{{ route('leads.index') }}">
                    @csrf
                    <div class="row">
                        <!-- Personal Details -->
                        <div class="col-md-12">
                            <h5>{{ get_label('personal_details', 'Personal Details') }}</h5>
                            <hr>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">{{ get_label('first_name', 'First Name') }} <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="tk-input" required
                                placeholder="{{ get_label('enter_first_name', 'Enter first name') }}"
                                value="{{ $lead->first_name }}">
                            @error('first_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">{{ get_label('last_name', 'Last Name') }} <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="tk-input" required
                                placeholder="{{ get_label('enter_last_name', 'Enter last name') }}"
                                value="{{ $lead->last_name }}">
                            @error('last_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">{{ get_label('email', 'Email') }} <span
                                    class="text-danger">*</span></label>
                            <input type="email" name="email" class="tk-input" required
                                placeholder="{{ get_label('enter_email', 'Enter email address') }}"
                                value="{{ $lead->email }}">
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label
                                class="form-label">{{ get_label('country_code_and_phone_number', 'Country code and phone number') }}</label>
                            <div class="tk-inputgroup">
                                <input type="tel" name="phone" id="phone" class="tk-input"
                                    value="{{ $lead->phone }}">
                                <span class="clear-input">×</span>
                            </div>
                            <input type="hidden" name="country_code" id="country_code"
                                value="{{ $lead->country_code }}">
                            <input type="hidden" name="country_iso_code" id="country_iso_code"
                                value="{{ $lead->country_iso_code }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="lead_sources"
                                class="form-label">{{ get_label('lead_sources', 'Lead Sources') }}</label>
                            <select class="tk-select tom_select" name="source_id" id="select_lead_source"
                                data-allow-clear="false" data-consider-workspace="true">
                                @if($lead->source)
                                <option value="{{ $lead->source->id }}">{{ ucwords($lead->source->name) }}</option>
                                @endif
                            </select>
                            @error('source_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        <div class="col-md-4 mb-3">
                            <label for="lead_stages" class="form-label">{{ get_label('lead_stages', 'Lead Stages') }} <span
                                    class="text-danger">*</span></label>
                            <select class="tk-select tom_select" name="stage_id" id="select_lead_stage"
                                data-allow-clear="false" data-consider-workspace="true" required>
                                @if($lead->stage )
                                <option value="{{ $lead->stage->id }}">{{ ucwords($lead->stage->name) }}</option>
                                @endif
                            </select>
                            @error('stage_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="assign_to" class="form-label">{{ get_label('assigned_to', 'Assign To') }} <span
                                    class="text-danger">*</span></label>
                            <select name="assigned_to" class="tk-select tom_select" id="select_lead_assignee"
                                data-allow-clear="false" data-consider-workspace="true" required>
                                @if($lead->assigned_user)
                                <option value="{{ $lead->assigned_user->id }}">{{ ucwords($lead->assigned_user->first_name . ' ' . $lead->assigned_user->last_name) }}</option>
                                @endif
                            </select>
                            @error('assigned_to')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>



                        <!-- Professional Details -->
                        <div class="col-md-12 mt-4">
                            <h5>{{ get_label('professional_details', 'Professional Details') }}</h5>
                            <hr>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="job_title" class="form-label">{{ get_label('job_title', 'Job Title') }}</label>
                            <input type="text" name="job_title" class="tk-input"
                                placeholder="{{ get_label('enter_job_title', 'Enter job title') }}"
                                value="{{ $lead->job_title }}">
                            @error('job_title')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="industry" class="form-label">{{ get_label('industry', 'Industry') }}</label>
                            <input type="text" name="industry" class="tk-input"
                                placeholder="{{ get_label('enter_industry', 'Enter industry') }}"
                                value="{{ $lead->industry }}">
                            @error('industry')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="company" class="form-label">{{ get_label('company', 'Company') }} <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="company" class="tk-input" required
                                placeholder="{{ get_label('enter_company', 'Enter company name') }}"
                                value="{{ $lead->company }}">
                            @error('company')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="website" class="form-label">{{ get_label('website', 'Website') }}</label>
                            <input type="text" name="website" class="tk-input"
                                placeholder="{{ get_label('enter_website', 'Enter company website') }}"
                                value="{{ $lead->website }}">
                            @error('website')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Social Links -->
                        <div class="col-md-12 mt-4">
                            <h5>{{ get_label('social_links', 'Social Links') }}</h5>
                            <hr>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="linkedin" class="form-label">{{ get_label('linkedin', 'LinkedIn') }}</label>
                            <input type="url" name="linkedin" class="tk-input"
                                placeholder="{{ get_label('enter_linkedin_url', 'Enter LinkedIn URL') }}"
                                value="{{ $lead->linkedin }}">
                            @error('linkedin')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="instagram" class="form-label">{{ get_label('instagram', 'Instagram') }}</label>
                            <input type="url" name="instagram" class="tk-input"
                                placeholder="{{ get_label('enter_instagram_url', 'Enter Instagram URL') }}"
                                value="{{ $lead->instagram }}">
                            @error('instagram')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="facebook" class="form-label">{{ get_label('facebook', 'Facebook') }}</label>
                            <input type="url" name="facebook" class="tk-input"
                                placeholder="{{ get_label('enter_facebook_url', 'Enter Facebook URL') }}"
                                value="{{ $lead->facebook }}">
                            @error('facebook')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="pinterest" class="form-label">{{ get_label('pinterest', 'Pinterest') }}</label>
                            <input type="url" name="pinterest" class="tk-input"
                                placeholder="{{ get_label('enter_pinterest_url', 'Enter Pinterest URL') }}"
                                value="{{ $lead->pinterest }}">
                            @error('pinterest')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-md-12 mt-4">
                            <h5>{{ get_label('address', 'Address') }}</h5>
                            <hr>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">{{ get_label('city', 'City') }}</label>
                            <input type="text" name="city" class="tk-input"
                                placeholder="{{ get_label('please_enter_city', 'Please enter city') }}"
                                value="{{ $lead->city }}">
                            @error('city')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="state" class="form-label">{{ get_label('state', 'State') }}</label>
                            <input type="text" name="state" class="tk-input"
                                placeholder="{{ get_label('please_enter_state', 'Please enter state') }}"
                                value="{{ $lead->state }}">
                            @error('state')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="zip" class="form-label">{{ get_label('zip_code', 'Zip Code') }}</label>
                            <input type="number" name="zip" class="tk-input"
                                placeholder="{{ get_label('please_enter_zip_code', 'Please enter ZIP code') }}"
                                value="{{ $lead->zip }}">
                            @error('zip')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="country" class="form-label">{{ get_label('country', 'Country') }}</label>
                            <input type="text" name="country" class="tk-input"
                                placeholder="{{ get_label('please_enter_country', 'Please enter country') }}"
                                value="{{ $lead->country }}">
                            @error('country')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                    </div>
                    <!-- Submit Button -->
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary me-2" id="submit_btn"><?= get_label('update', 'Update') ?></button>
                        <button type="reset" class="btn btn-outline-secondary"><?= get_label('cancel', 'Cancel') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
