@extends('layout')

@section('title')
    {{ get_label('create_social_account', 'Create Social Account') }}
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
                            <a href="{{ route('social.accounts.index') }}">{{ get_label('social_accounts', 'Social Accounts') }}</a>
                        </li>
                        <li class="breadcrumb-item active">
                            {{ get_label('create', 'Create') }}
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form class="form-submit-event" method="POST" action="{{ route('social.accounts.store') }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ get_label('name', 'Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">{{ get_label('status', 'Status') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" selected>{{ get_label('active', 'Active') }}</option>
                                <option value="inactive">{{ get_label('inactive', 'Inactive') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="description" class="form-label">{{ get_label('description', 'Description') }}</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Facebook Settings -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="bx bxl-facebook-circle me-2 text-primary"></i> Facebook Settings
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#facebookDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="facebook_page_id" class="form-label">Page ID</label>
                                <input type="text" class="form-control" id="facebook_page_id" 
                                    name="facebook_page_id" placeholder="Enter Facebook Page ID">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="facebook_access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control" id="facebook_access_token" 
                                    name="facebook_access_token" placeholder="Enter Facebook Access Token">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Instagram Settings -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="bx bxl-instagram me-2 text-danger"></i> Instagram Settings
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#instagramDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="instagram_business_account_id" class="form-label">Business Account ID</label>
                                <input type="text" class="form-control" id="instagram_business_account_id" 
                                    name="instagram_business_account_id" placeholder="Enter Instagram Business Account ID">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="instagram_access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control" id="instagram_access_token" 
                                    name="instagram_access_token" placeholder="Enter Instagram Access Token">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- LinkedIn Settings -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="bx bxl-linkedin me-2 text-primary"></i> LinkedIn Settings
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#linkedinDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="linkedin_person_id" class="form-label">Person ID</label>
                                <input type="text" class="form-control" id="linkedin_person_id" 
                                    name="linkedin_person_id" placeholder="Enter LinkedIn Person ID">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="linkedin_access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control" id="linkedin_access_token" 
                                    name="linkedin_access_token" placeholder="Enter LinkedIn Access Token">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Pinterest Settings -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="bx bxl-pinterest me-2 text-danger"></i> Pinterest Settings
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#pinterestDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="pinterest_app_id" class="form-label">App ID</label>
                                <input type="text" class="form-control" id="pinterest_app_id" 
                                    name="pinterest_app_id" placeholder="Enter Pinterest App ID">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="pinterest_app_secret" class="form-label">App Secret</label>
                                <input type="text" class="form-control" id="pinterest_app_secret" 
                                    name="pinterest_app_secret" placeholder="Enter Pinterest App Secret">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="pinterest_app_type" class="form-label">App Type</label>
                                <input type="text" class="form-control" id="pinterest_app_type" 
                                    name="pinterest_app_type" placeholder="Enter Pinterest App Type">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- YouTube Settings -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="bx bxl-youtube me-2 text-danger"></i> YouTube Settings
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#youtubeDocsOffcanvas">
                                <i class="bx bx-help-circle me-1"></i>
                                {{ get_label('help', 'Help') }}
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="youtube_client_id" class="form-label">Client ID</label>
                                <input type="text" class="form-control" id="youtube_client_id" 
                                    name="youtube_client_id" placeholder="Enter YouTube Client ID">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="youtube_client_secret" class="form-label">Client Secret</label>
                                <input type="text" class="form-control" id="youtube_client_secret" 
                                    name="youtube_client_secret" placeholder="Enter YouTube Client Secret">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="youtube_access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control" id="youtube_access_token" 
                                    name="youtube_access_token" placeholder="Enter YouTube Access Token">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="youtube_refresh_token" class="form-label">Refresh Token</label>
                                <input type="text" class="form-control" id="youtube_refresh_token" 
                                    name="youtube_refresh_token" placeholder="Enter YouTube Refresh Token">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary" id="submit_btn">
                            <i class="bx bx-save"></i> {{ get_label('save', 'Save') }}
                        </button>
                        <a href="{{ route('social.accounts.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x"></i> {{ get_label('cancel', 'Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#social-account-form').on('submit', function(e) {
                e.preventDefault();
                
                var $submitBtn = $('#submit-btn');
                var originalText = $submitBtn.html();
                $submitBtn.html('<i class="bx bx-loader bx-spin"></i> Saving...').prop('disabled', true);

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (!response.error) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 1000);
                        } else {
                            toastr.error(response.message);
                            $submitBtn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'An error occurred';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                        $submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>

    {{-- Include offcanvas partials here --}}
    @include('social-media-scheduler::social-media-scheduler.partials.offcanvas')
@endsection