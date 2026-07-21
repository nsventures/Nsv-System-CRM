@extends('layout')
@section('title')
    <?= get_label('security_settings', 'Security settings') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="fw-bold mb-0 me-2" style="font-size: 1.35rem;"><?= get_label('security_settings', 'Security Settings') ?></h4>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <?= get_label('settings', 'Settings') ?>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('security', 'Security') ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <form action="{{ url('settings/store_security') }}" class="form-submit-event" method="POST">
        <input type="hidden" name="dnr">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-xl-8 col-lg-8 col-md-12">
                
                <!-- Access Settings -->
                <div class="card mb-3 shadow-none border">
                    <div class="card-header border-bottom py-2 px-3">
                        <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                            <i class="bx bx-lock-open-alt me-2 text-secondary fs-5"></i><?= get_label('access_settings', 'Access Settings') ?>
                        </h6>
                    </div>
                    <div class="card-body pt-3 px-3 pb-3">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mb-1" style="font-size: 0.8rem;">
                                    <?= get_label('max_attempts', 'Max Attempts') ?> <small class="text-muted">(<?= get_label('max_attempts_info', 'Fill in if you want to set a limit; otherwise, leave it blank') ?>)</small>
                                    <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('max_attempts_info_1', 'The maximum number of login attempts allowed before the account is locked.') ?>"></i>
                                </label>
                                <input class="form-control form-control-sm" type="number" name="max_attempts" step="1" placeholder="5" value="{{ $general_settings['max_attempts'] ?? 5 }}" min="1">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mb-1" style="font-size: 0.8rem;">
                                    <?= get_label('lock_time', 'Lock Time (minutes)') ?> <small class="text-muted">(<?= get_label('lock_time_info', 'This will not apply if Max Attempts is left blank') ?>)</small>
                                    <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('lock_time_info_1', 'The duration in minutes for which the account will be locked after exceeding the maximum login attempts.') ?>"></i>
                                </label>
                                <input class="form-control form-control-sm" type="number" name="lock_time" step="1" placeholder="1" value="{{ $general_settings['lock_time'] ?? 1 }}" min="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Settings -->
                <div class="card mb-3 shadow-none border">
                    <div class="card-header border-bottom py-2 px-3">
                        <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                            <i class="bx bx-upload me-2 text-secondary fs-5"></i><?= get_label('upload_settings', 'Upload Settings') ?>
                        </h6>
                    </div>
                    <div class="card-body pt-3 px-3 pb-3">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mb-1" style="font-size: 0.8rem;">
                                    <?= get_label('allowed_max_upload_size_in_mb_default_512', 'Allowed Max Upload Size (MB) - Default: 512') ?>
                                    <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('allowed_max_upload_size_info', 'Also, set the `upload_max_filesize` and `post_max_size` PHP configurations on your server accordingly to ensure the maximum upload size works as expected.') ?>"></i>
                                </label>
                                <input class="form-control form-control-sm" type="number" name="allowed_max_upload_size" step="1" placeholder="512" value="{{ !isset($general_settings['allowed_max_upload_size']) ? '512' : $general_settings['allowed_max_upload_size'] }}" min="1">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mb-1" style="font-size: 0.8rem;" for="max_files">
                                    <?= get_label('max_files_allowed', 'Max Files Allowed') ?>
                                    <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('max_files_allowed_info', 'Set the maximum number of files that can be uploaded at a time. Also, set the `max_file_uploads` PHP configurations on your server accordingly to ensure the Max Files Allowed works as expected.') ?>"></i>
                                </label>
                                <input class="form-control form-control-sm" type="number" id="max_files" name="max_files" step="1" placeholder="10" value="{{ !isset($general_settings['max_files']) ? '10' : $general_settings['max_files'] }}" min="1">
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label mb-1" style="font-size: 0.8rem;" for="allowed_file_types">
                                    <?= get_label('allowed_file_types', 'Allowed File Types') ?>
                                    <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('allowed_file_types_info', 'Specify the file types allowed for upload, separated by commas. Default: .pdf, .doc, .docx, .png, .jpg, .xls, .xlsx, .zip, .rar, .txt.') ?>"></i>
                                </label>
                                <input class="form-control form-control-sm" type="text" id="allowed_file_types" name="allowed_file_types" placeholder=".pdf, .doc, .docx, .png, .jpg, .xls, .xlsx, .zip, .rar, .txt" value="{{ !isset($general_settings['allowed_file_types']) ? '.pdf, .doc, .docx, .png, .jpg, .xls, .xlsx, .zip, .rar, .txt' : $general_settings['allowed_file_types'] }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google reCAPTCHA Settings -->
                <div class="card mb-3 shadow-none border">
                    <div class="card-header border-bottom py-2 px-3">
                        <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                            <i class="bx bxl-google me-2 text-secondary fs-5"></i><?= get_label('google_recaptcha_settings', 'Google reCAPTCHA Settings') ?>
                        </h6>
                    </div>
                    <div class="card-body pt-3 px-3 pb-3">
                        <div class="alert alert-light-primary border-0 shadow-none d-flex align-items-center py-2 px-3 mb-3" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                            <i class="bx bx-info-circle me-2 fs-5 text-primary"></i>
                            <div>
                                {!! get_label('recaptcha_not_configured_info', 'If Google reCAPTCHA is not configured Please generate your keys from the <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">Google reCAPTCHA Admin Console</a> or contact the admin.') !!}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mb-1" style="font-size: 0.8rem;" for="recaptcha_site_key"><?= get_label('recaptcha_site_key', 'Google reCAPTCHA Site Key') ?></label>
                                <input class="form-control form-control-sm" type="text" id="recaptcha_site_key" name="recaptcha_site_key" placeholder="Enter your site key" value="{{ $general_settings['recaptcha_site_key'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mb-1" style="font-size: 0.8rem;" for="recaptcha_secret_key"><?= get_label('recaptcha_secret_key', 'Google reCAPTCHA Secret Key') ?></label>
                                <input class="form-control form-control-sm" type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" placeholder="Enter your secret key" value="{{ $general_settings['recaptcha_secret_key'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- End Left Column -->

            <!-- Right Column -->
            <div class="col-xl-4 col-lg-4 col-md-12">
                
                <!-- Security Features -->
                <div class="card mb-3 shadow-none border">
                    <div class="card-header border-bottom py-2 px-3">
                        <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                            <i class='bx bx-shield-quarter me-2 text-secondary fs-5'></i><?= get_label('security_features', 'Security Features') ?>
                        </h6>
                    </div>
                    <div class="card-body pt-3 px-3 pb-3">
                        <div class="mb-3">
                            <label class="form-check-label mb-1 d-block" style="font-size: 0.8rem;" for="allowSignup">
                                <?= get_label('enable_disable_signup', 'Enable Signup') ?>
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('enable_disable_signup_info', 'If disabled, team member and client will not be able to create an account by themselves.') ?>"></i>
                            </label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" id="allowSignup" name="allowSignup" @if (!isset($general_settings['allowSignup']) || $general_settings['allowSignup'] == 1) checked @endif>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-check-label mb-1 d-block" style="font-size: 0.8rem;" for="recaptcha_enabled">
                                <?= get_label('enable_recaptcha', 'Enable Google reCAPTCHA') ?>
                                <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('enable_recaptcha_info', 'If enabled, reCAPTCHA will be shown on login and signup pages.') ?>"></i>
                            </label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" @if (!isset($general_settings['recaptcha_enabled']) || $general_settings['recaptcha_enabled'] == 1) checked @endif>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- End Right Column -->

            <!-- Full Width Action Buttons -->
            <div class="col-12 mt-2">
                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <button type="reset" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;"><?= get_label('cancel', 'Cancel') ?></button>
                    <button type="submit" class="btn btn-xs btn-primary py-1 px-3" style="font-size: 0.8rem;" id="submit_btn"><i class='bx bx-save me-1'></i> <?= get_label('update', 'Update') ?></button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
