@extends('layout')
@section('title')
<?= get_label('email_settings', 'E-mail settings') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h4 class="fw-bold mb-0"><?= get_label('email_settings', 'E-mail Settings') ?></h4>
        <div class="d-flex align-items-center gap-3">
            <nav class="breadcrumb mb-0" aria-label="breadcrumb">
                <a class="breadcrumb-item" href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-item"><?= get_label('settings', 'Settings') ?></span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?= get_label('email', 'E-mail') ?></span>
            </nav>
        </div>
    </div>
    
    <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
        <i class="bx bx-info-circle me-2 fs-4"></i>
        <div>
            <?= get_label('important_settings_for_email_feature_to_be_work', 'Important settings for email feature to be work') ?>, <a href="https://www.gmass.co/smtp-test" class="fw-bold" target="_blank"><?= get_label('click_here_to_test_your_email_settings', 'Click here to test your email settings') ?></a>.
        </div>
    </div>
    
    <form action="{{url('settings/store_email')}}" class="form-submit-event" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="dnr">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header border-bottom d-flex align-items-center text-secondary">
                        <i class="bx bx-envelope me-2"></i>
                        <h6 class="mb-0 fw-semibold"><?= get_label('email_configuration', 'E-mail Configuration') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="email" class="form-label"><?= get_label('email', 'E-mail') ?> <span class="asterisk">*</span></label>
                                <input class="form-control" type="text" id="email" name="email" placeholder="<?= get_label('please_enter_email', 'Please enter email') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($email_settings['email'])) : $email_settings['email'] ?>">
                            </div>
                            <div class="mb-3 col-md-6 form-password-toggle">
                                <label for="password" class="form-label"><?= get_label('password', 'Password') ?> <span class="asterisk">*</span></label>
                                <div class="input-group input-group-merge">
                                    <input class="form-control" id="password" type="password" name="password" placeholder="<?= get_label('please_enter_password', 'Please enter password') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($email_settings['password'])) : $email_settings['password'] ?>">
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="smtp_host" class="form-label"><?= get_label('smtp_host', 'SMTP host') ?> <span class="asterisk">*</span></label>
                                <input class="form-control" type="text" id="smtp_host" name="smtp_host" placeholder="<?= get_label('please_enter_smtp_host', 'Enter SMTP host') ?>" value="<?= $email_settings['smtp_host'] ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="smtp_port" class="form-label"><?= get_label('smtp_port', 'SMTP port') ?> <span class="asterisk">*</span></label>
                                <input class="form-control" type="text" id="smtp_port" name="smtp_port" placeholder="<?= get_label('please_enter_smtp_port', 'Enter SMTP port') ?>" value="<?= $email_settings['smtp_port'] ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="email_content_type"><?= get_label('email_content_type', 'Email content type') ?> <span class="asterisk">*</span></label>
                                <select class="form-select" id="email_content_type" name="email_content_type">
                                    <option value="text" <?= $email_settings['email_content_type'] == 'text' ? 'selected' : '' ?>>Text</option>
                                    <option value="html" <?= $email_settings['email_content_type'] == 'html' ? 'selected' : '' ?>>HTML</option>
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="smtp_encryption"><?= get_label('smtp_encryption', 'SMTP Encryption') ?> <span class="asterisk">*</span></label>
                                <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                    <option value="off" <?= $email_settings['smtp_encryption'] == 'off' ? 'selected' : '' ?>>Off</option>
                                    <option value="ssl" <?= $email_settings['smtp_encryption'] == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    <option value="tls" <?= $email_settings['smtp_encryption'] == 'tls' ? 'selected' : '' ?>>TLS</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 text-end mt-4">
                                <button type="reset" class="btn btn-outline-secondary me-2"><?= get_label('cancel', 'Cancel') ?></button>
                                <button type="submit" class="btn btn-primary" id="submit_btn"><?= get_label('update', 'Update') ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
