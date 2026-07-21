@extends('layout')

@section('title')
    {{ get_label('social_media_settings', 'Social Media Settings') }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-2 mt-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                    </li>
                      <li class="breadcrumb-item">
                        <a
                            href="{{ route('social.index') }}">{{ get_label('social_media', 'Social Media') }}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        {{ get_label('settings', 'Settings') }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('social.settings.update') }}" class="form-submit-event" method="POST">
                <input type="hidden" name="redirect_url" value="{{ route('social.settings.index') }}">
                @csrf
                <div class="row">
                    <!-- Facebook Settings -->
                    <div class="mb-2 col-md-12">
                        <div class="d-flex align-items-center justify-content-between">
                              <h6 class="mb-0 fw-bold d-flex align-items-center justify-content-center">
                                <i class="bx bxl-facebook-circle me-2 text-info"></i>
                                {{ get_label('facebook', 'Facebook') }}
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#facebookDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <hr class="mb-4">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="facebook_access_token" class="form-label">
                            {{ get_label('facebook_access_token', 'Facebook Access Token') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="facebook_access_token"
                               name="facebook_access_token"
                               placeholder="{{ get_label('please_enter_facebook_access_token', 'Please enter Facebook access token') }}"
                               value="{{ $socialSettings['facebook_access_token'] ?? '' }}">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="facebook_page_id" class="form-label">
                            {{ get_label('facebook_page_id', 'Facebook Page ID') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="facebook_page_id"
                               name="facebook_page_id"
                               placeholder="{{ get_label('please_enter_facebook_page_id', 'Please enter Facebook page ID') }}"
                               value="{{ $socialSettings['facebook_page_id'] ?? '' }}">
                    </div>

                    <!-- Instagram Settings -->
                    <div class="mb-2 col-md-12 mt-1">
                        <div class="d-flex align-items-center justify-content-between">
                              <h6 class="mb-0 fw-bold d-flex align-items-center justify-content-center">
                                <i class="bx bxl-instagram me-2 text-danger"></i>
                                {{ get_label('instagram', 'Instagram') }}
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#instagramDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <hr class="mb-4">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="instagram_access_token" class="form-label">
                            {{ get_label('instagram_access_token', 'Instagram Access Token') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="instagram_access_token"
                               name="instagram_access_token"
                               placeholder="{{ get_label('please_enter_instagram_access_token', 'Please enter Instagram access token') }}"
                               value="{{ $socialSettings['instagram_access_token'] ?? '' }}">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="instagram_business_account_id" class="form-label">
                            {{ get_label('instagram_business_account_id', 'Instagram Business Account ID') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="instagram_business_account_id"
                               name="instagram_business_account_id"
                               placeholder="{{ get_label('please_enter_instagram_business_account_id', 'Please enter Instagram business account ID') }}"
                               value="{{ $socialSettings['instagram_business_account_id'] ?? '' }}">
                    </div>

                    <!-- LinkedIn Settings -->
                    <div class="mb-2 col-md-12 mt-1">
                        <div class="d-flex align-items-center justify-content-between">
                               <h6 class="mb-0 fw-bold d-flex align-items-center justify-content-center">
                                <i class="bx bxl-linkedin me-2" style="color: #0077b5;"></i>
                                {{ get_label('linkedin', 'LinkedIn') }}
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#linkedinDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <hr class="mb-4">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="linkedin_access_token" class="form-label">
                            {{ get_label('linkedin_access_token', 'LinkedIn Access Token') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="linkedin_access_token"
                               name="linkedin_access_token"
                               placeholder="{{ get_label('please_enter_linkedin_access_token', 'Please enter LinkedIn access token') }}"
                               value="{{ $socialSettings['linkedin_access_token'] ?? '' }}">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="linkedin_person_id" class="form-label">
                            {{ get_label('linkedin_person_id', 'LinkedIn Person ID') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="linkedin_person_id"
                               name="linkedin_person_id"
                               placeholder="{{ get_label('please_enter_linkedin_person_id', 'Please enter LinkedIn person ID') }}"
                               value="{{ $socialSettings['linkedin_person_id'] ?? '' }}">
                    </div>

                    <!-- Pinterest Settings -->
                    <div class="mb-2 col-md-12 mt-1">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold d-flex align-items-center justify-content-center">
                                <i class="bx bxl-pinterest me-2 text-danger"></i>
                                {{ get_label('pinterest', 'Pinterest') }}
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#pinterestDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <hr class="mb-4">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="pinterest_app_id" class="form-label">
                            {{ get_label('pinterest_app_id', 'Pinterest App ID') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="pinterest_app_id"
                               name="pinterest_app_id"
                               placeholder="{{ get_label('please_enter_pinterest_app_id', 'Please enter Pinterest app ID') }}"
                               value="{{ $socialSettings['pinterest_app_id'] ?? '' }}">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="pinterest_app_secret" class="form-label">
                            {{ get_label('pinterest_app_secret', 'Pinterest App Secret') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="pinterest_app_secret"
                               name="pinterest_app_secret"
                               placeholder="{{ get_label('please_enter_pinterest_app_secret', 'Please enter Pinterest app secret') }}"
                               value="{{ $socialSettings['pinterest_app_secret'] ?? '' }}">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="pinterest_app_type" class="form-label">
                            {{ get_label('app_type', 'App Type') }}
                        </label>
                        <select class="form-select" name="pinterest_app_type" id="pinterest_app_type">
                            <option value="">{{ get_label('select_app_type', 'Select app type') }}</option>
                            <option value="trial" {{ old('pinterest_app_type', $socialSettings['pinterest_app_type'] ?? '') == 'trial' ? 'selected' : '' }}>
                                {{ get_label('trial', 'Trial') }}
                            </option>
                            <option value="standard" {{ old('pinterest_app_type', $socialSettings['pinterest_app_type'] ?? '') == 'standard' ? 'selected' : '' }}>
                                {{ get_label('standard', 'Standard') }}
                            </option>
                        </select>
                    </div>


                  {{-- Youtube Settings --}}

                    <div class="mb-2 col-md-12 mt-1">
                        <div class="d-flex align-item-center justify-content-between">
                            <h6 class="mb-0 fw-bold d-flex align-items-center justify-content-center">
                                <i class="bx bxl-youtube me-2 text-danger"></i>
                                {{ get_label('youtube', 'Youtube') }}
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#youtubeDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <hr class="mb-4">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="youtube_client_id" class="form-label">
                            {{ get_label('youtube_client_id', 'Youtube Client ID') }}
                        </label>
                        <input
                        type="text"
                        class="form-control"
                        id="youtube_client_id"
                        name="youtube_client_id"
                        placeholder="{{ get_label('please_enter_youtube_client_id', 'Please Enter Youtube Client ID') }}"
                        value="{{ $socialSettings['youtube_client_id'] ?? '' }}"
                        >
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="youtube_client_secret" class="form-label">
                            {{ get_label('youtube_client_secret', 'Youtube Client Secret') }}
                        </label>
                        <input type="text"
                        class="form-control"
                        id="youtube_client_secret"
                        name="youtube_client_secret"
                        placeholder="{{ get_label('please_enter_youtube_client_secret', 'Please Enter Youtube Client Secret') }}"
                        value="{{ $socialSettings['youtube_client_secret'] ?? '' }}"
                        >
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="youtube_access_token" class="form-label">
                            {{ get_label('youtube_access_token', 'Youtube Access Token') }}
                        </label>
                        <input type="text"
                        class="form-control"
                        id="youtube_access_token"
                        name="youtube_access_token"
                        placeholder="{{ get_label('please_enter_youtube_access_token', 'Please Enter Youtube Access Token') }}"
                        value="{{ $socialSettings['youtube_access_token'] ?? '' }}"
                        >
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="youtube_refresh_token" class="form-label">
                            {{ get_label('youtube_refresh_token', 'Youtube Refresh Token') }}
                        </label>
                        <input type="text"
                        class="form-control"
                        id="youtube_refresh_token"
                        name="youtube_refresh_token"
                        placeholder="{{ get_label('please_enter_youtube_refresh_token', 'Please Enter Youtube Refresh Token') }}"
                        value="{{ $socialSettings['youtube_refresh_token'] ?? '' }}"
                        >
                    </div>

                    <!-- Submit Buttons -->
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



<!-- Pinterest Docs Offcanvas -->
<aside class="offcanvas offcanvas-end w-50 border-start" tabindex="-1" id="pinterestDocsOffcanvas"
    aria-labelledby="pinterestDocsOffcanvasLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold fs-4" id="pinterestDocsOffcanvasLabel">
            <i class="bx bxl-pinterest text-danger me-2 fs-3"></i>{{ get_label('pinterest_settings_guide', 'Pinterest Settings Guide') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ get_label('close', 'Close') }}"></button>
    </div>
    <div class="offcanvas-body p-4">
        <div class="mb-5">
            <h6 class="fw-bold mb-3 fs-5">{{ get_label('requirements', 'Requirements') }}</h6>
            <p class="text-muted fs-6">
                {{ get_label('pinterest_requirements_text', 'To integrate Pinterest, you need an App ID, App Secret, and App Type. Your account must be converted to a business account.') }}
            </p>
        </div>

        <div class="mb-5">
            <h6 class="fw-bold mb-4 fs-5">{{ get_label('setup_steps', 'Setup Steps') }}</h6>

            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">1. {{ get_label('pinterest_step1_title', 'Convert to Business Account') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('pinterest_step1_login_settings', 'Log in to Pinterest and go to your profile settings') }}</li>
                    <li class="mb-2">• {{ get_label('pinterest_step1_select_convert', 'Under Account management, select "Convert to business account"') }}</li>
                    <li class="mb-2">• {{ get_label('pinterest_step1_follow_prompts', 'Follow the conversion prompts') }}</li>
                </ul>
                <div class="mt-3">
                    <div class="simplelightbox-gallery">
                        <a href="{{ asset('assets/img/social/pi-step1.1.png') }}" data-lightbox="api-docs" data-title="Request Example">
                            <img src="{{ asset('assets/img/social/pi-step1.1.png') }}" alt="Request Example" style="cursor: zoom-in;" class="w-100 rounded border shadow-sm">
                        </a>
                    </div>
                </div>
            </div>

            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">2. {{ get_label('pinterest_step2_title', 'Create Pinterest App') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('pinterest_step2_visit_developers', 'Visit developers.pinterest.com') }}</li>
                    <li class="mb-2">• {{ get_label('pinterest_step2_click_create_app', 'Click "Create App" and fill in required details') }}</li>
                    <li class="mb-2">• {{ get_label('pinterest_step2_submit_form', 'Submit the form to create your app') }}</li>
                </ul>
                <div class="mt-3">
                     <div class="simplelightbox-gallery">
                        <a href="{{ asset('assets/img/social/pi-step2.1.png') }}" data-lightbox="api-docs" data-title="Request Example">
                            <img src="{{ asset('assets/img/social/pi-step2.1.png') }}" alt="Request Example" style="cursor: zoom-in;" class="w-100 rounded border shadow-sm">
                        </a>
                    </div>
                </div>
            </div>

            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">3. {{ get_label('pinterest_step3_title', 'Get App Credentials') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('pinterest_step3_go_to_details', 'Go to your app details in the developer dashboard') }}</li>
                </ul>
                 <div class="mt-3">
                    <div class="simplelightbox-gallery mb-3">
                         <a href="{{ asset('assets/img/social/pi-step3.1.png') }}" data-lightbox="api-docs" data-title="Request Example">
                            <img src="{{ asset('assets/img/social/pi-step3.1.png') }}" alt="Request Example" style="cursor: zoom-in;" class="w-100 rounded border shadow-sm">
                        </a>
                    </div>
                </div>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('pinterest_step3_copy_credentials', 'Copy the App ID and App Secret from credentials section') }}</li>
                </ul>
                <div class="mt-3">
                    <div class="simplelightbox-gallery">
                         <a href="{{ asset('assets/img/social/pi-step3.2.png') }}" data-lightbox="api-docs" data-title="Request Example">
                            <img src="{{ asset('assets/img/social/pi-step3.2.png') }}" alt="Request Example" style="cursor: zoom-in;" class="w-100 rounded border shadow-sm">
                        </a>
                    </div>
                </div>
            </div>

            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">4. {{ get_label('pinterest_step4_title', 'Determine App Type') }}</h6>
                <p class="text-muted fs-6 ps-3">
                    {{ get_label('pinterest_step4_app_type_info', 'New apps start as "Trial" with limited API access. Apply for "Standard" access in the Developer Portal for full functionality. Standard apps require Pinterest review and approval.') }}
                </p>
            </div>

            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">5. {{ get_label('pinterest_step5_title', 'Configure Settings') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('pinterest_step5_enter_app_id', 'Enter your App ID in the Pinterest App Id field') }}</li>
                    <li class="mb-2">• {{ get_label('pinterest_step5_enter_app_secret', 'Enter your App Secret in the Pinterest App Secret field') }}</li>
                    <li class="mb-2">• {{ get_label('pinterest_step5_select_app_type', 'Select your App Type (Trial or Standard)') }}</li>
                </ul>
                <div class="mt-3">
                    <div class="simplelightbox-gallery">
                            <a href="{{ asset('assets/img/social/pi-step5.1.png') }}" data-lightbox="api-docs" data-title="Request Example">
                            <img src="{{ asset('assets/img/social/pi-step5.1.png') }}" alt="Request Example" style="cursor: zoom-in;" class="w-100 rounded border shadow-sm">
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info border-0 shadow-sm">
            <h6 class="fw-bold mb-3 fs-6">{{ get_label('important_notes', 'Important Notes') }}</h6>
            <ul class="fs-6 mb-0">
                <li>{{ get_label('pinterest_note_business_required', 'Business account is required for API access') }}</li>
                <li>{{ get_label('pinterest_note_trial_limited', 'Trial apps have limited API calls') }}</li>
                <li>{{ get_label('pinterest_note_standard_approval', 'Standard access requires Pinterest approval') }}</li>
            </ul>
        </div>
    </div>
</aside>

<!-- Facebook Docs Offcanvas -->
<aside class="offcanvas offcanvas-end w-50 border-start" tabindex="-1" id="facebookDocsOffcanvas"
    aria-labelledby="facebookDocsOffcanvasLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold fs-4" id="facebookDocsOffcanvasLabel">
            <i class="bx bxl-facebook-circle text-primary me-2 fs-3"></i>
            {{ get_label('facebook_settings_guide', 'Facebook Settings Guide') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ get_label('close', 'Close') }}"></button>
    </div>

    <div class="offcanvas-body p-4">
        <!-- Requirements -->
        <div class="mb-5">
            <h6 class="fw-bold mb-3 fs-5">{{ get_label('requirements', 'Requirements') }}</h6>
            <p class="text-muted fs-6">
                {{ get_label('facebook_requirements_text', 'To post to Facebook pages automatically, you need a Page Access Token and Page ID from a Facebook App.') }}
            </p>
        </div>

        <!-- Setup Steps -->
        <div class="mb-5">
            <h6 class="fw-bold mb-4 fs-5">{{ get_label('setup_steps', 'Setup Steps') }}</h6>

            <!-- Step 1 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">1. {{ get_label('facebook_step1_title', 'Create Facebook App') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('facebook_step1_go_to_developers', 'Go to developers.facebook.com') }}</li>
                    <li class="mb-2">• {{ get_label('facebook_step1_click_create_app', 'Click My Apps → Create App') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step1.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step1.1.png') }}" class="w-100 rounded border shadow-sm"  style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('facebook_step1_choose_business', 'Choose "Business" as app type') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step1.2.png') }}"   data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step1.2.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('facebook_step1_enter_details', 'Enter App Name and Contact Email, then create') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step1.3.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step1.3.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">2. {{ get_label('facebook_step2_title', 'Generate Page Access Token') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('facebook_step2_go_to_graph_api', 'Go to Tools → Graph API Explorer') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step2.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step2.1.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('facebook_step2_select_app_page', 'Select your app and choose your Facebook Page') }}</li>
                    <li class="mb-2">• {{ get_label('facebook_step2_add_permissions', 'Add permissions: pages_manage_posts, pages_read_engagement, pages_show_list') }}</li>
                    <li class="mb-2">• {{ get_label('facebook_step2_generate_token', 'Click Generate Access Token and authorize') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step2.2.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step2.2.png') }}" style="cursor: zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('facebook_step2_get_page_id', 'Enter /me?fields=id,name and submit to get your Page ID') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step2.3.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step2.3.png') }}" style="cursor:zoom-in " class="w-100 rounded border shadow-sm">
                    </a>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">3. {{ get_label('facebook_step3_title', 'Configure Settings') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('facebook_step3_paste_token', 'Paste the Page Access Token in the form') }}</li>
                    <li class="mb-2">• {{ get_label('facebook_step3_paste_page_id', 'Paste the Page ID in the corresponding field') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step3.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step3.1.png') }}" style="cursor: zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="alert alert-info border-0 shadow-sm">
            <h6 class="fw-bold mb-3 fs-6">{{ get_label('important_notes', 'Important Notes') }}</h6>
            <ul class="fs-6 mb-0">
                <li>{{ get_label('facebook_note_token_expiry', 'Access tokens expire - use Graph API to extend them') }}</li>
                <li>{{ get_label('facebook_note_permissions', 'Ensure permissions include pages_manage_posts, pages_read_engagement, pages_show_list') }}</li>
                <li>{{ get_label('facebook_note_dev_mode', 'Apps in Development mode are restricted to admins/testers - submit for review for public access') }}</li>
            </ul>
        </div>
    </div>
</aside>

<!-- Instagram Docs Offcanvas -->
<aside class="offcanvas offcanvas-end w-50 border-start" tabindex="-1" id="instagramDocsOffcanvas"
    aria-labelledby="instagramDocsOffcanvasLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold fs-4" id="instagramDocsOffcanvasLabel">
            <i class="bx bxl-instagram text-danger me-2 fs-3"></i>
            {{ get_label('instagram_settings_guide', 'Instagram Settings Guide') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ get_label('close', 'Close') }}"></button>
    </div>

    <div class="offcanvas-body p-4">
        <!-- Requirements -->
        <div class="mb-5">
            <h6 class="fw-bold mb-3 fs-5">{{ get_label('requirements', 'Requirements') }}</h6>
            <p class="text-muted fs-6">
                {{ get_label('instagram_requirements_text', 'To post to Instagram automatically, you need a Facebook Page Access Token (with Instagram permissions) and Instagram Business Account ID.') }}
            </p>
        </div>

        <!-- Setup Steps -->
        <div class="mb-5">
            <h6 class="fw-bold mb-4 fs-5">{{ get_label('setup_steps', 'Setup Steps') }}</h6>

            <!-- Step 1 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">1. {{ get_label('instagram_step1_title', 'Convert to Business Account') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('instagram_step1_open_profile', 'Open Instagram app and go to your profile') }}</li>
                    <li class="mb-2">• {{ get_label('instagram_step1_go_to_settings', 'Tap the menu (three lines) and go to Settings and Privacy.') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/ig-step1.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/ig-step1.1.png') }}" style="cursor: zoom-in" class="w-100 rounded border shadow-sm" alt="{{ get_label('instagram_step1_img_menu', 'Instagram profile menu') }}">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('instagram_step1_switch_business', 'Account type and tools → Switch to Professional Account → Select Business') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/ig-step1.2.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/ig-step1.2.png') }}" style="cursor: zoom-in" class="w-100 rounded border shadow-sm" alt="{{ get_label('instagram_step1_img_conversion', 'Instagram business conversion') }}">
                    </a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">2. {{ get_label('instagram_step2_title', 'Link Instagram to Facebook Page') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('instagram_step2_open_fb_page', 'Go to your Facebook Page (browser or app)') }}</li>
                    <li class="mb-2">• {{ get_label('instagram_step2_open_settings', 'Settings → Linked Accounts → Instagram') }}</li>
                    <li class="mb-2">• {{ get_label('instagram_step2_connect_account', 'Log in and connect your Instagram Business account') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/ig-step2.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/ig-step2.1.png') }}" style="cursor:zoom-in" class="w-100 rounded border shadow-sm" alt="{{ get_label('instagram_step2_img_linking', 'Facebook Instagram linking') }}">
                    </a>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">3. {{ get_label('instagram_step3_title', 'Generate Access Token') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('instagram_step3_open_explorer', 'Go to Tools → Graph API Explorer') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step2.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step2.1.png') }}" style="cursor:zoom-in" class="w-100 rounded border shadow-sm" alt="{{ get_label('instagram_step3_img_explorer', 'Graph API Explorer') }}">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('instagram_step3_select_app', 'Select your app and choose your Facebook Page') }}</li>
                    <li class="mb-2">• {{ get_label('instagram_step3_add_permissions', 'Add permissions: pages_manage_posts, pages_read_engagement, pages_show_list') }}</li>
                    <li class="mb-2">• {{ get_label('instagram_step3_generate_token', 'Click Generate Access Token and authorize') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step2.2.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/step2.2.png') }}" style="cursor:zoom-in" class="w-100 rounded border shadow-sm" alt="{{ get_label('instagram_step3_img_token', 'Token generation') }}">
                    </a>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">4. {{ get_label('instagram_step4_title', 'Get Instagram Business Account ID') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('instagram_step4_query', 'Using Page Access Token, query: /{facebook-page-id}?fields=instagram_business_account') }}</li>
                    <li class="mb-2">• {{ get_label('instagram_step4_copy_id', 'Copy the "id" from the instagram_business_account field') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/ig-step4.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/ig-step4.1.png') }}" style="curson:zoom-in" class="w-100 rounded border shadow-sm" alt="{{ get_label('instagram_step4_img_id', 'Instagram account ID') }}">
                    </a>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">5. {{ get_label('instagram_step5_title', 'Configure Settings') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('instagram_step5_paste_token', 'Paste Page Access Token in "Instagram Access Token" field') }}</li>
                    <li class="mb-2">• {{ get_label('instagram_step5_paste_id', 'Paste Instagram Business Account ID in corresponding field') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/ig-step5.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/ig-step5.1.png') }}" style="cursor:zoom-in" class="w-100 rounded border shadow-sm" alt="{{ get_label('instagram_step5_img_form', 'Instagram settings form') }}">
                    </a>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="alert alert-info border-0 shadow-sm">
            <h6 class="fw-bold mb-3 fs-6">{{ get_label('important_notes', 'Important Notes') }}</h6>
            <ul class="fs-6 mb-0">
                <li>{{ get_label('instagram_note_business_account', 'Instagram account must be Business or Creator account linked to Facebook Page') }}</li>
                <li>{{ get_label('instagram_note_long_lived_token', 'Use long-lived Page Access Token to avoid frequent re-authentication') }}</li>
                <li>{{ get_label('instagram_note_permissions', 'Ensure permissions include instagram_content_publish for posting') }}</li>
            </ul>
        </div>
    </div>
</aside>

<!-- LinkedIn Docs Offcanvas -->
<aside class="offcanvas offcanvas-end w-50 border-start" tabindex="-1" id="linkedinDocsOffcanvas"
    aria-labelledby="linkedinDocsOffcanvasLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold fs-4" id="linkedinDocsOffcanvasLabel">
            <i class="bx bxl-linkedin text-primary me-2 fs-3"></i>{{ get_label('linkedin_settings_guide', 'LinkedIn Settings Guide') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ get_label('close', 'Close') }}"></button>
    </div>

    <div class="offcanvas-body p-4">

        <!-- Requirements -->
        <div class="mb-5">
            <h6 class="fw-bold mb-3 fs-5">{{ get_label('requirements', 'Requirements') }}</h6>
            <p class="text-muted fs-6">
                {{ get_label('linkedin_requirements_text', 'To post automatically to LinkedIn, you need an Access Token and either your Person ID (for personal profile) or Organization ID (for company page).') }}
            </p>
        </div>

        <!-- Setup Steps -->
        <div class="mb-5">
            <h6 class="fw-bold mb-4 fs-5">{{ get_label('setup_steps', 'Setup Steps') }}</h6>

            <!-- Step 1 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">1. {{ get_label('linkedin_step1_title', 'Create a LinkedIn App') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• <a href="https://www.linkedin.com/developers" target="_blank" class="fw-medium text-primary text-decoration-none">{{ get_label('linkedin_step1_go_to_developers', 'Go to LinkedIn Developers') }}</a></li>
                    <li class="mb-2">• {{ get_label('linkedin_step1_click_create_app', 'Click "Create App", enter details, and link to a LinkedIn Page or Profile.') }}</li>
                    <li class="mb-2">• {{ get_label('linkedin_step1_complete_form', 'Complete the form and click "Create App".') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/li-step1.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/li-step1.1.png') }}" style="cursor: zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">2. {{ get_label('linkedin_step2_title', 'Add Required Products') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('linkedin_step2_go_to_products', 'In the App Dashboard, go to Products tab.') }}</li>
                    <li class="mb-2">• {{ get_label('linkedin_step2_request_products', 'Request "Share on LinkedIn" and "Sign In With LinkedIn Using OpenID Connect".') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/li-step2.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/li-step2.1.png') }}" style="cursor:zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">3. {{ get_label('linkedin_step3_title', 'Generate an Access Token') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('linkedin_step3_go_to_auth_tools', 'Go to Docs and Tools → Auth Token Tools.') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery mb-3">
                    <a href="{{ asset('assets/img/social/li-step3.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/li-step3.1.png') }}" style="cursor: zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('linkedin_step3_select_app_scopes', 'Select your app, scopes, and request Access Token.') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery mb-3">
                    <a href="{{ asset('assets/img/social/li-step3.2.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/li-step3.2.png') }}" style="cursor:zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('linkedin_step3_copy_token', 'Copy the Access Token from the response.') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/li-step3.3.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/li-step3.3.png') }}" style="cursor: zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">4. {{ get_label('linkedin_step4_title', 'Find Person or Organization ID') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('linkedin_step4_query_person_id', 'Use your Access Token to query Person ID:') }}</li>
                </ul>
                <pre class="bg-light small rounded p-2 border">GET https://api.linkedin.com/v2/userinfo
Authorization: Bearer {ACCESS_TOKEN}</pre>
                <small class="text-muted d-block mb-3">{{ get_label('linkedin_step4_person_id_note', '"sub" is the LinkedIn Person ID you will use for posting.') }}</small>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('linkedin_step4_query_org_id', 'For Organization ID, query: https://api.linkedin.com/v2/organizationAcls?q=roleAssignee') }}</li>
                    <li class="mb-2">• {{ get_label('linkedin_step4_copy_id', 'Copy the "id" or "sub" from the response.') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/li-step4.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/li-step4.1.png') }}" style="cursor: zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">5. {{ get_label('linkedin_step5_title', 'Add Token & ID in Settings') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('linkedin_step5_paste_token', 'Paste the Access Token into LinkedIn Access Token.') }}</li>
                    <li class="mb-2">• {{ get_label('linkedin_step5_paste_id', 'Paste the Person ID or Organization ID into LinkedIn Person/Org ID.') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/li-step5.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/li-step5.1.png') }}" style="cursor:zoom-in" class="w-100 rounded border shadow-sm">
                    </a>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="alert alert-info border-0 shadow-sm">
            <h6 class="fw-bold mb-3 fs-6">{{ get_label('important_notes', 'Important Notes') }}</h6>
            <ul class="fs-6 mb-0">
                <li>{{ get_label('linkedin_note_token_expiry', 'Access Tokens usually expire in 60 days. Refresh when needed.') }}</li>
                <li>{{ get_label('linkedin_note_permissions', 'Ensure permissions include w_member_social (profiles) or w_organization_social (pages).') }}</li>
                <li>{{ get_label('linkedin_note_dev_mode', 'If app is in development mode, only authorized users can post. Submit app for LinkedIn review for production use.') }}</li>
            </ul>
        </div>
    </div>
</aside>

@endsection

