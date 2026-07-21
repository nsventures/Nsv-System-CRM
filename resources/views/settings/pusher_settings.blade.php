@extends('layout')
@section('title')
<?= get_label('pusher_settings', 'Pusher settings') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="fw-bold mb-0 me-2" style="font-size: 1.35rem;"><?= get_label('pusher_settings', 'Pusher Settings') ?></h4>
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
                        <?= get_label('pusher', 'Pusher') ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="card mb-3 shadow-none border">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                <i class='bx bx-broadcast me-2 text-secondary fs-5'></i><?= get_label('pusher_settings', 'Pusher Settings') ?>
            </h6>
        </div>
        <div class="card-body pt-3 px-3 pb-3">
            <div class="alert alert-light-primary border-0 shadow-none d-flex align-items-center py-2 px-3 mb-3" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                <i class="bx bx-info-circle me-2 fs-5 text-primary"></i>
                <div>
                    <?= get_label('important_settings_for_chat_feature_to_be_work', 'Important settings for chat feature to be work') ?>, <a href="https://dashboard.pusher.com/apps" class="fw-bold" target="_blank"><?= get_label('click_here_to_find_these_settings_on_your_pusher_account', 'Click here to find these settings on your pusher account') ?></a>.
                </div>
            </div>
            
            <form action="{{url('settings/store_pusher')}}" class="form-submit-event" method="POST">
                <input type="hidden" name="dnr">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="mb-2 col-md-6">
                        <label for="pusher_app_id" class="form-label mb-1" style="font-size: 0.8rem;"><?= get_label('pusher_app_id', 'Pusher APP ID') ?> <span class="asterisk">*</span></label>
                        <input class="form-control form-control-sm" type="text" name="pusher_app_id" id="pusher_app_id" placeholder="<?= get_label('please_enter_pusher_app_id', 'Please enter pusher APP ID') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($pusher_settings['pusher_app_id'])) : $pusher_settings['pusher_app_id'] ?>">
                    </div>
                    <div class="mb-2 col-md-6">
                        <label for="pusher_app_key" class="form-label mb-1" style="font-size: 0.8rem;"><?= get_label('pusher_app_key', 'Pusher APP key') ?> <span class="asterisk">*</span></label>
                        <input class="form-control form-control-sm" type="text" name="pusher_app_key" id="pusher_app_key" placeholder="<?= get_label('please_enter_pusher_app_key', 'Please enter pusher APP key') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($pusher_settings['pusher_app_key'])) : $pusher_settings['pusher_app_key'] ?>">
                    </div>
                    <div class="mb-2 col-md-6">
                        <label for="pusher_app_secret" class="form-label mb-1" style="font-size: 0.8rem;"><?= get_label('pusher_app_secret', 'Pusher APP secret') ?> <span class="asterisk">*</span></label>
                        <input class="form-control form-control-sm" type="text" name="pusher_app_secret" id="pusher_app_secret" placeholder="<?= get_label('please_enter_pusher_app_secret', 'Please enter pusher APP secret') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($pusher_settings['pusher_app_secret'])) : $pusher_settings['pusher_app_secret'] ?>">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="pusher_app_cluster" class="form-label mb-1" style="font-size: 0.8rem;"><?= get_label('pusher_app_cluster', 'Pusher APP cluster') ?> <span class="asterisk">*</span></label>
                        <input class="form-control form-control-sm" type="text" name="pusher_app_cluster" id="pusher_app_cluster" placeholder="<?= get_label('please_enter_pusher_app_cluster', 'Please enter pusher APP cluster') ?>" value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($pusher_settings['pusher_app_cluster'])) : $pusher_settings['pusher_app_cluster'] ?>">
                    </div>
                    <div class="d-flex justify-content-end flex-wrap gap-2 border-top pt-3 col-12">
                        <button type="reset" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;"><?= get_label('cancel', 'Cancel') ?></button>
                        <button type="submit" class="btn btn-xs btn-primary py-1 px-3" style="font-size: 0.8rem;" id="submit_btn"><i class='bx bx-save me-1'></i> <?= get_label('update', 'Update') ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection