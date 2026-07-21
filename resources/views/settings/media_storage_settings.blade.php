@extends('layout')
@section('title')
<?= get_label('media_storage_settings', 'Media storage settings') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="fw-bold mb-0 me-2" style="font-size: 1.35rem;"><?= get_label('media_storage_settings', 'Media Storage Settings') ?></h4>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{url('home')}}"><?= get_label('home', 'Home') ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <?= get_label('settings', 'Settings') ?>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('media_storage', 'Media storage') ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="card mb-3 shadow-none border">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                <i class='bx bx-hdd me-2 text-secondary fs-5'></i><?= get_label('media_storage_settings', 'Media Storage Settings') ?>
            </h6>
        </div>
        <div class="card-body pt-3 px-3 pb-3">
            <form action="{{ url('/settings/store_media_storage') }}" class="form-submit-event" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="dnr">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="mb-2 col-md-12">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="media_storage_type"><?= get_label('select_storage_type', 'Select storage type') ?><span class="asterisk">*</span></label>
                        <select class="form-select form-select-sm" id="media_storage_type" name="media_storage_type">
                            <option value=""><?= get_label('please_select', 'Please select') ?></option>
                            <option value="local" {{ $media_storage_settings['media_storage_type'] === 'local' ? 'selected' : '' }}><?= get_label('local_storage', 'Local storage') ?></option>
                            <option value="s3" {{ $media_storage_settings['media_storage_type'] === 's3' ? 'selected' : '' }}>Amazon AWS S3</option>
                        </select>
                    </div>
                    <div class="aws-s3-fields {{ $media_storage_settings['media_storage_type'] === 's3' ? '' : 'd-none' }} col-12">
                        @if (config('constants.ALLOW_MODIFICATION') === 1)
                        <div class="row">
                            <div class="mb-2 col-md-6">
                                <label for="s3_key" class="form-label mb-1" style="font-size: 0.8rem;">AWS S3 Access Key <span class="asterisk">*</span></label>
                                <input class="form-control form-control-sm" type="text" name="s3_key" id="s3_key" placeholder="<?= get_label('please_enter_aws_s3_access_key', 'Please enter AWS S3 access key') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($media_storage_settings['s3_key'])) : $media_storage_settings['s3_key'] ?>">
                            </div>
                            <div class="mb-2 col-md-6 form-password-toggle">
                                <label for="s3_secret" class="form-label mb-1" style="font-size: 0.8rem;">AWS S3 Secret Key <span class="asterisk">*</span></label>
                                <div class="input-group input-group-merge input-group-sm">
                                    <input class="form-control form-control-sm" type="password" name="s3_secret" id="s3_secret" placeholder="<?= get_label('please_enter_aws_s3_secret_key', 'Please enter AWS S3 secret key') ?>" value="<?= $media_storage_settings['s3_secret']  ?>">
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-2 col-md-6">
                                <label for="s3_region" class="form-label mb-1" style="font-size: 0.8rem;">AWS S3 Region <span class="asterisk">*</span></label>
                                <input class="form-control form-control-sm" type="text" name="s3_region" id="s3_region" placeholder="<?= get_label('please_enter_aws_s3_region', 'Please enter AWS S3 region') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($media_storage_settings['s3_region'])) : $media_storage_settings['s3_region'] ?>">
                            </div>
                            <div class="mb-2 col-md-6">
                                <label for="s3_bucket" class="form-label mb-1" style="font-size: 0.8rem;">AWS S3 Bucket <span class="asterisk">*</span></label>
                                <input class="form-control form-control-sm" type="text" name="s3_bucket" id="s3_bucket" placeholder="<?= get_label('please_enter_aws_s3_bucket', 'Please enter AWS S3 bucket') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($media_storage_settings['s3_bucket'])) : $media_storage_settings['s3_bucket'] ?>">
                            </div>
                        </div>
                        @else
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="alert alert-light-danger border-0 py-2 px-3 mb-2" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                                    <i class="bx bx-info-circle me-2 fs-5 text-danger"></i>
                                    <?= get_label('not_allowed_in_demo_mode', 'Not allowed in demo mode') ?>.
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="d-flex justify-content-end flex-wrap gap-2 border-top pt-3 col-12 mt-2">
                        <button type="reset" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;">{{ get_label('cancel', 'Cancel') }}</button>
                        <button type="submit" class="btn btn-xs btn-primary py-1 px-3" style="font-size: 0.8rem;" id="submit_btn"><i class='bx bx-save me-1'></i> {{ get_label('update', 'Update') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection