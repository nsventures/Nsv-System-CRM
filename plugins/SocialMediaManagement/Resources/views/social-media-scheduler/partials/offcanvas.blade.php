<!-- YouTube Docs Offcanvas -->
<aside class="offcanvas offcanvas-end w-50 border-start" tabindex="-1" id="youtubeDocsOffcanvas"
    aria-labelledby="youtubeDocsOffcanvasLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold fs-4" id="youtubeDocsOffcanvasLabel">
            <i class="bx bxl-youtube text-danger me-2 fs-3"></i>
            {{ get_label('youtube_settings_guide', 'YouTube Settings Guide') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
            aria-label="{{ get_label('close', 'Close') }}"></button>
    </div>

    <div class="offcanvas-body p-4">
        <!-- Requirements -->
        <div class="mb-5">
            <h6 class="fw-bold mb-3 fs-5">{{ get_label('requirements', 'Requirements') }}</h6>
            <p class="text-muted fs-6">
                {{ get_label('youtube_requirements_text', 'To upload videos to YouTube automatically, you need a Client ID, Client Secret, Access Token, and Refresh Token from a Google Cloud project.') }}
            </p>
        </div>

        <!-- Setup Steps -->
        <div class="mb-5">
            <h6 class="fw-bold mb-4 fs-5">{{ get_label('setup_steps', 'Setup Steps') }}</h6>

            <!-- Step 1 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">1. {{ get_label('youtube_step1_title', 'Create Google Cloud Project') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('youtube_step1_go_to_console', 'Go to console.cloud.google.com') }}</li>
                    <li class="mb-2">• {{ get_label('youtube_step1_create_project', 'Click "Select Project" → "New Project"') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt1.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt1.1.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step1_enable_api', 'Search for "YouTube Data API v3" in the API Library and click Enable') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt1.2.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt1.2.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">2. {{ get_label('youtube_step2_title', 'Create OAuth Credentials') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('youtube_step2_go_to_credentials', 'Go to APIs & Services → Credentials') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt2.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt2.1.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step2_create_oauth', 'Click "Create Credentials" → "OAuth client ID"') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt2.2.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt2.2.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step2_configure_consent', 'Click "Configure Consent Screen"') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt2.3.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt2.3.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step2_fill_info', 'Fill in the app information and select "External" in Audience') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt2.4.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt2.4.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt2.5.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt2.5.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step2_create_client', 'Return to Credentials page and click "Create OAuth client ID"') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt2.6.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt2.6.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step2_choose_app_type', 'Choose "Web application" as application type and add https://developers.google.com/oauthplayground  as a Redirect URI') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt2.7.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt2.7.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step2_copy_credentials', 'Copy Client ID and Client Secret and save them securely') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt2.8.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt2.8.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">3. {{ get_label('youtube_step3_title', 'Add Test Users') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('youtube_step3_go_to_consent', 'Go to "OAuth Consent Screen"') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt3.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt3.1.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step3_click_audience', 'Click on "Audience"') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt3.2.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt3.2.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step3_add_users', 'Click "Add Users" and add your email (only test users can generate tokens)') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt3.3.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt3.3.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">4. {{ get_label('youtube_step4_title', 'Generate Access & Refresh Tokens') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('youtube_step4_open_playground', 'Open https://developers.google.com/oauthplayground') }}</li>
                    <li class="mb-2">• {{ get_label('youtube_step4_enable_credentials', 'Click the settings icon → enable "Use your own OAuth credentials"') }}</li>
                    <li class="mb-2">• {{ get_label('youtube_step4_paste_credentials', 'Paste your Client ID & Client Secret') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt5.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt5.1.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step4_select_scopes', 'Select scopes: youtube, youtube.upload') }}</li>
                    <li class="mb-2">• {{ get_label('youtube_step4_authorize', 'Click "Authorize APIs"') }}</li>
                    <li class="mb-2">• {{ get_label('youtube_step4_approve', 'Approve using your test user account') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt5.2.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt5.2.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('youtube_step4_exchange_code', 'Click "Exchange authorization code for tokens"') }}</li>
                    <li class="mb-2">• {{ get_label('youtube_step4_copy_tokens', 'Copy Access Token & Refresh Token') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt5.3.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt5.3.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="mb-5">
                <h6 class="fw-semibold text-dark mb-3 fs-6">5. {{ get_label('youtube_step5_title', 'Configure Settings in System') }}</h6>
                <ul class="list-unstyled fs-6 ps-3">
                    <li class="mb-2">• {{ get_label('youtube_step5_paste_client', 'Paste Client ID & Client Secret') }}</li>
                    <li class="mb-2">• {{ get_label('youtube_step5_paste_tokens', 'Paste Access Token & Refresh Token') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/yt6.1.png') }}" data-lightbox="api-docs" target="_blank">
                        <img src="{{ asset('assets/img/social/yt6.1.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="alert alert-info border-0 shadow-sm">
            <h6 class="fw-bold mb-3 fs-6">{{ get_label('important_notes', 'Important Notes') }}</h6>
            <ul class="fs-6 mb-0">
                <li>{{ get_label('youtube_note_scopes', 'Include scopes youtube and youtube.upload') }}</li>
                <li>{{ get_label('youtube_note_refresh_token', 'Refresh Token allows automatic renewal of Access Token') }}</li>
                <li>{{ get_label('youtube_note_test_users', 'Test users are required unless app is fully published') }}</li>
                <li>{{ get_label('youtube_note_redirect_uri', 'Redirect URL must match exactly or Google will show "redirect_uri_mismatch" error') }}</li>
            </ul>
        </div>
    </div>
</aside>







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
                        <img src="{{ asset('assets/img/social/step1.1.png') }}" class="w-100 rounded border shadow-sm" style="cursor: zoom-in;">
                    </a>
                </div>
                <ul class="list-unstyled fs-6 ps-3 mt-3">
                    <li class="mb-2">• {{ get_label('facebook_step1_choose_business', 'Choose "Business" as app type') }}</li>
                </ul>
                <div class="mt-3 simplelightbox-gallery">
                    <a href="{{ asset('assets/img/social/step1.2.png') }}" data-lightbox="api-docs" target="_blank">
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