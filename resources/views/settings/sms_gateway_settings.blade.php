@extends('layout')
@section('title')
<?= get_label('messaging_integrations_settings', 'Messaging & Integrations Settings') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="fw-bold mb-0 me-2" style="font-size: 1.35rem;"><?= get_label('messaging_integrations_settings', 'Messaging & Integrations Settings') ?></h4>
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
                        <?= get_label('messaging_and_integrations', 'Messaging & Integrations') ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    @php
    $sms_gateway_settings = get_settings('sms_gateway_settings');
    $whatsapp_settings = get_settings('whatsapp_settings');
    $slack_settings = get_settings('slack_settings');
    @endphp

    <div class="row">
        <!-- Left Column - SMS Gateway Settings -->
        <div class="col-xl-8 col-lg-8 col-md-12">
            <div class="card mb-3 shadow-none border">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                        <i class='bx bx-message-square-detail me-2 text-secondary'></i><?= get_label('sms_gateway', 'SMS Gateway') ?>
                    </h6>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#sms_instuction_modal">
                        <i class='bx bx-help-circle me-1'></i><?= get_label('click_for_sms_gateway_settings_help', 'Help') ?>
                    </button>
                </div>
                <div class="card-body pt-3 px-3 pb-3">
                    <div class="alert alert-light-primary border-0 shadow-none d-flex align-items-center py-2 px-3 mb-3" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                        <i class="bx bx-info-circle me-2 fs-5 text-primary"></i>
                        <div>
                            <?= get_label('important_settings_for_SMS_feature_to_be_work', 'Important settings for SMS feature to be work') ?>
                        </div>
                    </div>
                    
                    <form action="{{url('settings/store_sms_gateway')}}" class="form-submit-event" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="dnr">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-2">
                            <div class="col-md-6 mb-2">
                                <label for="base_url" class="form-label mb-1" style="font-size: 0.8rem;">{{get_label('base_url','Base URL')}} <span class="asterisk">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="base_url" value="{{$sms_gateway_settings['base_url'] ?? ''}}" placeholder="e.g. https://api.gateway.com/sms">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="sms_gateway_method" class="form-label mb-1" style="font-size: 0.8rem;">{{get_label('method','Method')}} <span class="asterisk">*</span></label>
                                <select class="form-select form-select-sm" name="sms_gateway_method">
                                    <option value="POST" {{ ($sms_gateway_settings && isset($sms_gateway_settings['sms_gateway_method']) && $sms_gateway_settings['sms_gateway_method'] == 'POST') ? 'selected' : '' }}>POST</option>
                                    <option value="GET" {{ ($sms_gateway_settings && isset($sms_gateway_settings['sms_gateway_method']) && $sms_gateway_settings['sms_gateway_method'] == 'GET') ? 'selected' : '' }}>GET</option>
                                </select>
                            </div>
                        </div>

                        <div class="border-top pt-3 mb-3 mt-2">
                            <h6 class="text-secondary mb-2" style="font-size: 0.85rem;"><i class='bx bx-key me-1'></i> {{get_label('create_authorization_token','Create authorization token')}}</h6>
                            <div class="row mb-2">
                                <div class="col-md-6 mb-2">
                                    <label for="converterInputAccountSID" class="form-label mb-1" style="font-size: 0.8rem;">{{get_label('account_sid','Account SID')}}</label>
                                    <input type="text" class="form-control form-control-sm" id="converterInputAccountSID" placeholder="Enter Account SID">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="converterInputAuthToken" class="form-label mb-1" style="font-size: 0.8rem;">{{get_label('auth_token','Auth token')}}</label>
                                    <input type="text" class="form-control form-control-sm" id="converterInputAuthToken" placeholder="Enter Auth Token">
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.75rem;" id="createBasicToken"><?= get_label('create', 'Create') ?></button>
                                <span class="mb-0 text-success" id="basicToken" style="font-size: 0.8rem; font-weight: 500;"></span>
                            </div>
                        </div>

                        <div class="border-top pt-3 mb-3">
                            <div class="nav-align-top mb-2">
                                <ul class="nav nav-tabs" role="tablist" style="font-size: 0.8rem;">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link py-1 px-3 active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-header" aria-controls="navs-top-header" aria-selected="true">
                                            {{get_label('header','Header')}}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link py-1 px-3" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-body" aria-controls="navs-top-body" aria-selected="false">
                                            {{get_label('body','Body')}}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link py-1 px-3" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-params" aria-controls="navs-top-params" aria-selected="false">
                                            {{get_label('params','Params')}}
                                        </button>
                                    </li>
                                </ul>
                                <div class="tab-content bg-transparent shadow-none px-0 pt-2 pb-0">
                                    <div class="tab-pane fade show active" id="navs-top-header" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12" id="header-rows">
                                                <div class="d-flex align-items-end mb-2">
                                                    <div class="col-md-5 mx-1">
                                                        <label class="form-label text-muted mb-1" style="font-size: 0.75rem;" for="header_key">{{get_label('key','Key')}}</label>
                                                        <input type="text" id="header_key" class="form-control form-control-sm" placeholder="Header Key">
                                                    </div>
                                                    <div class="col-md-5 mx-1">
                                                        <label class="form-label text-muted mb-1" style="font-size: 0.75rem;" for="header_value">{{get_label('value','Value')}}</label>
                                                        <input type="text" id="header_value" class="form-control form-control-sm" placeholder="Header Value">
                                                    </div>
                                                    <div class="col-md-1 mx-3 text-center">
                                                        <label class="form-label text-muted mb-1 d-block" style="font-size: 0.75rem;" for="add-header"><?= get_label('action', 'Action') ?></label>
                                                        <button type="button" class="btn btn-xs btn-success" id="add-header" style="height: 31px; width: 31px; display: inline-flex; align-items: center; justify-content: center;"><i class="bx bx-check"></i></button>
                                                    </div>
                                                </div>
                                                @foreach ($sms_gateway_settings['header_data'] ?? [] as $key => $value)
                                                <div class="d-flex header-row mb-2">
                                                    <div class="col-md-5 mx-1">
                                                        <input type="text" class="form-control form-control-sm" name="header_key[]" value="{{ $key }}">
                                                    </div>
                                                    <div class="col-md-5 mx-1">
                                                        <input type="text" class="form-control form-control-sm" name="header_value[]" value="{{ $value }}">
                                                    </div>
                                                    <div class="col-md-1 mx-3 text-center">
                                                        <button type="button" class="btn btn-xs btn-danger remove-header" style="height: 31px; width: 31px; display: inline-flex; align-items: center; justify-content: center;"><i class="bx bx-trash"></i></button>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="navs-top-body" role="tabpanel">
                                        <ul class="nav nav-tabs" role="tablist" style="font-size: 0.75rem;">
                                            <li class="nav-item">
                                                <button type="button" class="nav-link py-1 px-3 active" role="tab" data-bs-toggle="tab" data-bs-target="#text-json-tab" aria-controls="text-json-tab" aria-selected="true">
                                                    text/JSON
                                                </button>
                                            </li>
                                            <li class="nav-item">
                                                <button type="button" class="nav-link py-1 px-3" role="tab" data-bs-toggle="tab" data-bs-target="#formdata-tab" aria-controls="formdata-tab" aria-selected="false">
                                                    FormData
                                                </button>
                                            </li>
                                        </ul>
                                        <div class="tab-content bg-transparent shadow-none px-0 pt-2 pb-0">
                                            <div class="tab-pane fade show active" id="text-json-tab" role="tabpanel">
                                                <div class="col-md-12">
                                                    <textarea name="text_format_data" class="text_format_data form-control form-control-sm" rows="3" placeholder='{"to": "{only_mobile_number}", "message": "{message}"}' style="font-size: 0.8rem;">{{$sms_gateway_settings['text_format_data'] ?? ''}}</textarea>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="formdata-tab" role="tabpanel">
                                                <div class="col-md-12" id="body-formdata-rows">
                                                    <div class="d-flex align-items-end mb-2">
                                                        <div class="col-md-5 mx-1">
                                                            <label class="form-label text-muted mb-1" style="font-size: 0.75rem;" for="body_formdata_key">{{get_label('key','Key')}}</label>
                                                            <input type="text" id="body_formdata_key" class="form-control form-control-sm" placeholder="Body Key">
                                                        </div>
                                                        <div class="col-md-5 mx-1">
                                                            <label class="form-label text-muted mb-1" style="font-size: 0.75rem;" for="body_formdata_value">{{get_label('value','Value')}}</label>
                                                            <input type="text" id="body_formdata_value" class="form-control form-control-sm" placeholder="Body Value">
                                                        </div>
                                                        <div class="col-md-1 mx-3 text-center">
                                                            <label class="form-label text-muted mb-1 d-block" style="font-size: 0.75rem;" for="add-body-formdata"><?= get_label('action', 'Action') ?></label>
                                                            <button type="button" class="btn btn-xs btn-success" id="add-body-formdata" style="height: 31px; width: 31px; display: inline-flex; align-items: center; justify-content: center;"><i class="bx bx-check"></i></button>
                                                        </div>
                                                    </div>
                                                    @foreach ($sms_gateway_settings['body_formdata'] ?? [] as $key => $value)
                                                    <div class="d-flex body-formdata-row mb-2">
                                                        <div class="col-md-5 mx-1">
                                                            <input type="text" class="form-control form-control-sm" name="body_key[]" value="{{ $key }}">
                                                        </div>
                                                        <div class="col-md-5 mx-1">
                                                            <input type="text" class="form-control form-control-sm" name="body_value[]" value="{{ $value }}">
                                                        </div>
                                                        <div class="col-md-1 mx-3 text-center">
                                                            <button type="button" class="btn btn-xs btn-danger remove-body-formdata" style="height: 31px; width: 31px; display: inline-flex; align-items: center; justify-content: center;"><i class="bx bx-trash"></i></button>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="navs-top-params" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12" id="params-rows">
                                                <div class="d-flex align-items-end mb-2">
                                                    <div class="col-md-5 mx-1">
                                                        <label class="form-label text-muted mb-1" style="font-size: 0.75rem;" for="params_key">{{get_label('key','Key')}}</label>
                                                        <input type="text" id="params_key" class="form-control form-control-sm" placeholder="Param Key">
                                                    </div>
                                                    <div class="col-md-5 mx-1">
                                                        <label class="form-label text-muted mb-1" style="font-size: 0.75rem;" for="params_value">{{get_label('value','Value')}}</label>
                                                        <input type="text" id="params_value" class="form-control form-control-sm" placeholder="Param Value">
                                                    </div>
                                                    <div class="col-md-1 mx-3 text-center">
                                                        <label class="form-label text-muted mb-1 d-block" style="font-size: 0.75rem;" for="add-params"><?= get_label('action', 'Action') ?></label>
                                                        <button type="button" class="btn btn-xs btn-success" id="add-params" style="height: 31px; width: 31px; display: inline-flex; align-items: center; justify-content: center;"><i class="bx bx-check"></i></button>
                                                    </div>
                                                </div>
                                                @foreach ($sms_gateway_settings['params_data'] ?? [] as $key => $value)
                                                <div class="d-flex params-row mb-2">
                                                    <div class="col-md-5 mx-1">
                                                        <input type="text" class="form-control form-control-sm" name="params_key[]" value="{{ $key }}">
                                                    </div>
                                                    <div class="col-md-5 mx-1">
                                                        <input type="text" class="form-control form-control-sm" name="params_value[]" value="{{ $value }}">
                                                    </div>
                                                    <div class="col-md-1 mx-3 text-center">
                                                        <button type="button" class="btn btn-xs btn-danger remove-params" style="height: 31px; width: 31px; display: inline-flex; align-items: center; justify-content: center;"><i class="bx bx-trash"></i></button>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 mb-3">
                            <h6 class="text-secondary mb-2" style="font-size: 0.8rem;">{{get_label('available_placeholders', 'Available placeholders')}}</h6>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-sm table-bordered" style="font-size: 0.8rem;">
                                    <thead>
                                        <tr>
                                            <th>{{get_label('placeholder','Placeholder')}}</th>
                                            <th class="text-center" style="width: 80px;">{{get_label('action','Action')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="copyText">{only_mobile_number}</td>
                                            <td class="text-center">
                                                <a href="javascript:void(0);" onclick="copyToClipboard(0)" title="{{get_label('copy_to_clipboard','Copy to clipboard')}}">
                                                    <i class="bx bx-copy text-warning fs-6"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="copyText">{country_code}</td>
                                            <td class="text-center">
                                                <a href="javascript:void(0);" onclick="copyToClipboard(1)" title="{{get_label('copy_to_clipboard','Copy to clipboard')}}">
                                                    <i class="bx bx-copy text-warning fs-6"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="copyText">{message}</td>
                                            <td class="text-center">
                                                <a href="javascript:void(0);" onclick="copyToClipboard(2)" title="{{get_label('copy_to_clipboard','Copy to clipboard')}}">
                                                    <i class="bx bx-copy text-warning fs-6"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end flex-wrap gap-2 border-top pt-3">
                            <button type="reset" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;"><?= get_label('cancel', 'Cancel') ?></button>
                            @if (config('constants.ALLOW_MODIFICATION') === 1)
                            <button type="button" class="btn btn-xs btn-success py-1 px-3" style="font-size: 0.8rem;" id="testSmsSettingsButton"><?= get_label('send_test_message', 'Send Test Message') ?></button>
                            @endif
                            <button type="submit" class="btn btn-xs btn-primary py-1 px-3" style="font-size: 0.8rem;" id="submit_btn"><i class='bx bx-save me-1'></i> <?= get_label('update', 'Update') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column - WhatsApp & Slack Settings -->
        <div class="col-xl-4 col-lg-4 col-md-12">
            
            <!-- WhatsApp Settings -->
            <div class="card mb-3 shadow-none border">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                        <i class='bx bxl-whatsapp me-2 text-secondary fs-5'></i><?= get_label('whatsapp', 'WhatsApp') ?>
                    </h6>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#whatsapp_instuction_modal">
                        <i class='bx bx-help-circle me-1'></i><?= get_label('click_for_help', 'Help') ?>
                    </button>
                </div>
                <div class="card-body pt-3 px-3 pb-3">
                    <div class="alert alert-light-primary border-0 shadow-none d-flex align-items-center py-2 px-3 mb-3" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                        <i class="bx bx-info-circle me-2 fs-5 text-primary"></i>
                        <div>
                            <?= get_label('important_settings_for_whatsapp_notification_feature_to_be_work', 'Important settings for WhatsApp notification feature to be work.') ?>
                        </div>
                    </div>
                    
                    <form action="{{url('settings/store_whatsapp')}}" class="form-submit-event" method="POST">
                        <input type="hidden" name="dnr">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-2">
                            <label for="whatsapp_access_token" class="form-label mb-1" style="font-size: 0.8rem;">{{ get_label('whatsapp_access_token', 'WhatsApp Access Token') }} <span class="asterisk">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="whatsapp_access_token" value="{{$whatsapp_settings['whatsapp_access_token'] ?? ''}}" placeholder="Enter Access Token">
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp_phone_number_id" class="form-label mb-1" style="font-size: 0.8rem;">{{get_label('whatsapp_phone_number_id', 'WhatsApp Phone Number ID')}} <span class="asterisk">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="whatsapp_phone_number_id" value="{{$whatsapp_settings['whatsapp_phone_number_id'] ?? ''}}" placeholder="Enter Phone Number ID">
                        </div>
                        
                        <div class="d-flex justify-content-end flex-wrap gap-2 border-top pt-3">
                            <button type="reset" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;"><?= get_label('cancel', 'Cancel') ?></button>
                            @if (config('constants.ALLOW_MODIFICATION') === 1)
                            <button type="button" class="btn btn-xs btn-success py-1 px-3" style="font-size: 0.8rem;" id="testWhatsappSettingsButton"><?= get_label('send_test_message', 'Send Test Message') ?></button>
                            @endif
                            <button type="submit" class="btn btn-xs btn-primary py-1 px-3" style="font-size: 0.8rem;" id="submit_btn"><i class='bx bx-save me-1'></i> <?= get_label('update', 'Update') ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Slack Settings -->
            <div class="card mb-3 shadow-none border">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                        <i class='bx bxl-slack me-2 text-secondary fs-5'></i><?= get_label('slack', 'Slack') ?>
                    </h6>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#slack_instruction_modal">
                        <i class='bx bx-help-circle me-1'></i><?= get_label('click_for_help', 'Help') ?>
                    </button>
                </div>
                <div class="card-body pt-3 px-3 pb-3">
                    <div class="alert alert-light-primary border-0 shadow-none d-flex align-items-center py-2 px-3 mb-3" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                        <i class="bx bx-info-circle me-2 fs-5 text-primary"></i>
                        <div>
                            <?= get_label('important_settings_for_slack_notification_feature_to_be_work', 'Important settings for Slack notification feature to be work.') ?>
                        </div>
                    </div>
                    
                    <form action="{{ route('slack_settings.store') }}" class="form-submit-event" method="POST">
                        <input type="hidden" name="dnr">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="slack_bot_token" class="form-label mb-1" style="font-size: 0.8rem;">{{ get_label('slack_bot_token', 'Slack bot token') }} <span class="asterisk">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="slack_bot_token" value="{{ $slack_settings['slack_bot_token'] ?? '' }}" placeholder="Enter Slack Bot Token">
                        </div>
                        
                        <div class="d-flex justify-content-end flex-wrap gap-2 border-top pt-3">
                            <button type="reset" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;"><?= get_label('cancel', 'Cancel') ?></button>
                            @if (config('constants.ALLOW_MODIFICATION') === 1)
                            <button type="button" class="btn btn-xs btn-success py-1 px-3" style="font-size: 0.8rem;" id="testSlackSettingsButton"><?= get_label('send_test_message', 'Send Test Message') ?></button>
                            @endif
                            <button type="submit" class="btn btn-xs btn-primary py-1 px-3" style="font-size: 0.8rem;" id="submit_btn"><i class='bx bx-save me-1'></i> <?= get_label('update', 'Update') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="sms_instuction_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsModalLabel"><?= get_label('sms_gateway_configuration', 'Sms Gateway Configuration') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul>
                    <li>Read and follow instructions carefully while configuration sms gateway setting </li>
                    <li class="my-4">Firstly open your sms gateway account . You can find api keys in your account -> API keys & credentials -> create api key </li>
                    <li class="my-4">After create key you can see here Account sid and auth token </li>
                    <div class="simplelightbox-gallery">
                        <a href="{{asset('storage/images/base_url_and_params.png')}}" target="_blank">
                            <img src="{{asset('storage/images/base_url_and_params.png')}}" class="w-100">
                        </a>
                    </div>
                    <li class="my-4">For Base url Messaging -> Send an SMS</li>
                    <div class="simplelightbox-gallery">
                        <a href="{{asset('storage/images/api_key_and_token.png')}}" target="_blank">
                            <img src="{{asset('storage/images/api_key_and_token.png')}}" class="w-100">
                        </a>
                    </div>
                    <li class="my-4">check this for admin panel settings</li>
                    <div class="simplelightbox-gallery">
                        <a href="{{asset('storage/images/sms_gateway_1.png')}}" target="_blank">
                            <img src="{{asset('storage/images/sms_gateway_1.png')}}" class="w-100">
                        </a>
                    </div>
                    <div class="simplelightbox-gallery">
                        <a href="{{asset('storage/images/sms_gateway_2.png')}}" target="_blank">
                            <img src="{{asset('storage/images/sms_gateway_2.png')}}" class="w-100">
                        </a>
                    </div>
                    <li class="my-4"><b>Make sure you entered valid data as per instructions before proceed</b></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?= get_label('close', 'Close') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="whatsapp_instuction_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="whatsappModalLabel">
                    <?= get_label('whatsapp_configuration', 'WhatsApp Configuration') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Steps for WhatsApp Cloud API Setup:</h6>
                <ol>
                    <li>
                        <strong>Access Facebook Developers Dashboard:</strong>
                        <ul>
                            <li>Go to <a href="https://developers.facebook.com/apps/" target="_blank">Facebook for
                                    Developers</a></li>
                            <li>Log in or create a developer account if you haven't already</li>
                        </ul>
                        <div class="simplelightbox-gallery">
                            <a href="{{ asset('/storage/images/fb_developer_dashboard.png') }}" target="_blank">
                                <img src="{{ asset('/storage/images/fb_developer_dashboard.png') }}"
                                    alt="Facebook Developer Dashboard" class="img-fluid mb-3 mt-2">
                            </a>
                        </div>
                    </li>
                    <li>
                        <strong>Create or Select an App:</strong>
                        <ul>
                            <li>Click "Create App" or select your existing app</li>
                            <li>Choose "Business" as the app type if creating new</li>
                        </ul>
                        <div class="simplelightbox-gallery">
                            <a href="{{ asset('/storage/images/create_app_image.png') }}" target="_blank">
                                <img src="{{ asset('/storage/images/create_app_image.png') }}"
                                    alt="Create App Process" class="img-fluid mb-3 mt-2">
                            </a>
                        </div>
                    </li>
                    <li>
                        <strong>Set up WhatsApp:</strong>
                        <ul>
                            <li>In the app dashboard, find and add the "WhatsApp" product</li>
                            <li>Follow the setup process, including business verification if required</li>
                        </ul>
                        <div class="simplelightbox-gallery">
                            <a href="{{ asset('/storage/images/whatsapp_setup_image.png') }}" target="_blank">
                                <img src="{{ asset('/storage/images/whatsapp_setup_image.png') }}"
                                    alt="WhatsApp Setup in Developer Dashboard" class="img-fluid mb-3 mt-2">
                            </a>
                        </div>
                    </li>
                    <li>
                        <strong>Get Access Token and Phone Number ID:</strong>
                        <ul>
                            <li>In the WhatsApp section, find "Getting Started"</li>
                            <li>Locate your Temporary Access Token and Phone Number ID</li>
                            <li><strong>To create a Permanent Access Token:</strong>
                                <ul>
                                    <li>Go to <strong>Business Settings</strong> > <strong>Users</strong> > <strong>System Users</strong> in the Facebook Developers Dashboard</li>
                                    <li>Add a new System User with the <strong>Admin</strong> role, if not already created</li>
                                    <li>Under <strong>System Users</strong>, select your created user and, under <strong>Assets</strong>, assign <strong>WhatsApp Business Account</strong> permissions for <em>Manage and Message</em></li>
                                    <li>Click <strong>Generate New Token</strong> and select your app with <em>whatsapp_business_messaging</em> and <em>business_management</em> permissions</li>
                                    <li><strong>Select "Never Expiration" to ensure the token does not expire automatically</strong></li>
                                    <li><strong>Alternatively, you can generate a long-lived token using the Graph API:</strong>
                                        <pre>https://graph.facebook.com/v16.0/oauth/access_token?grant_type=fb_exchange_token&client_id={app-id}&client_secret={app-secret}&fb_exchange_token={short-lived-token}</pre>
                                        <strong>Note:</strong> Replace the placeholders with your actual credentials. This method returns a long-lived token that you can refresh periodically.
                                    </li>
                                    <li>Use the generated token in your application as a permanent access token</li>
                                    <li><strong>Note:</strong> This token will last until revoked or permissions change. Make sure to store it securely.</li>
                                </ul>
                            </li>
                        </ul>
                        <div class="simplelightbox-gallery">
                            <a href="{{ asset('/storage/images/access_token_phone_id_image.png') }}" target="_blank">
                                <img src="{{ asset('/storage/images/access_token_phone_id_image.png') }}" alt="Access Token and Phone Number ID Location" class="img-fluid mb-3 mt-2">
                            </a>
                        </div>
                    </li>
                    <li>
                        <strong>Create Message Template (Important):</strong>
                        <ul>
                            <li>In the WhatsApp section, go to "Message Templates"</li>
                            <li>Click "Create Template"</li>
                            <li>Name your template "taskify_notification"</li>
                            <li>Set language to English</li>
                            <li>In the Body section, enter exactly:
                                <pre>@{{ 1 }}

Please take necessary actions if required.

Thank you,
@{{ 2 }}</pre>
                            </li>
                            <li>Provide sample content for the @{{ 1 }} , @{{ 2 }} variable</li>
                            <li>Submit the template for review</li>
                        </ul>
                        <div class="simplelightbox-gallery">
                            <a href="{{ asset('/storage/images/template_creation_image.png') }}" target="_blank">
                                <img src="{{ asset('/storage/images/template_creation_image.png') }}"
                                    alt="Message Template Creation" class="img-fluid mb-3 mt-2">
                            </a>
                        </div>
                    </li>
                </ol>
                <p><strong>Note:</strong> It's crucial to create the template exactly as shown for the integration to
                    work correctly. The @{{ 1 }} , @{{ 2 }} represents a variable in the
                    WhatsApp template, not a Blade variable.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?= get_label('close', 'Close') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="slack_instruction_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="slackModalLabel">
                    <?= get_label('slack_bot_configuration', 'Slack Bot Configuration') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Steps for Slack Bot Token Setup:</h6>
                <ol>
                    <li>
                        <strong>Create a Slack App:</strong>
                        <ol type="a">
                            <li>Go to <a href="https://api.slack.com/apps" target="_blank">https://api.slack.com/apps</a></li>
                            <li>Click "Create New App"</li>
                            <li>Choose "From scratch"</li>
                            <li>Name your app (e.g., "Taskify Notifier") and select your workspace</li>
                            <li>Click "Create App"</li>
                        </ol>
                        <img src="{{ asset('/storage/images/create-slack-app.png') }}" alt="Create Slack App" class="img-fluid mb-3 mt-2">
                    </li>
                    <li>
                        <strong>Set up Bot Token Scopes:</strong>
                        <ol type="a">
                            <li>In the left sidebar, click on "OAuth & Permissions"</li>
                            <li>Scroll down to "Scopes"</li>
                            <li>Under "Bot Token Scopes", click "Add an OAuth Scope"</li>
                            <li>Add these scopes:
                                <ul>
                                    <li>chat:write</li>
                                    <li>users:read</li>
                                    <li>users:read.email</li>
                                </ul>
                            </li>
                        </ol>
                        <img src="{{ asset('/storage/images/bot-token-scopes.png') }}" alt="Bot Token Scopes" class="img-fluid mb-3 mt-2">
                    </li>
                    <li>
                        <strong>Install the app to your workspace:</strong>
                        <ol type="a">
                            <li>Scroll up to the top of the "OAuth & Permissions" page</li>
                            <li>Click "Install to Workspace"</li>
                            <li>Review the permissions and click "Allow"</li>
                        </ol>
                        <img src="{{ asset('/storage/images/slack-app.png') }}" alt="Install to Workspace" class="img-fluid mb-3 mt-2">
                    </li>
                    <li>
                        <strong>Get your Bot Token:</strong>
                        <ol type="a">
                            <li>After installation, you'll be back on the "OAuth & Permissions" page</li>
                            <li>Look for "Bot User OAuth Token" under "OAuth Tokens for Your Workspace"</li>
                            <li>Click "Copy" to copy this token</li>
                            <li>Store this token securely (we'll use it in the code later)</li>
                        </ol>
                        <img src="{{ asset('/storage/images/bot-token.png') }}" alt="Get Bot Token" class="img-fluid mb-3 mt-2">
                    </li>
                    <li>
                        <strong>Enable Socket Mode (optional, but recommended for enhanced security):</strong>
                        <ol type="a">
                            <li>In the left sidebar, click on "Socket Mode"</li>
                            <li>Toggle "Enable Socket Mode" to On</li>
                            <li>If prompted, generate an app-level token and store it securely</li>
                        </ol>
                        <img src="{{ asset('/storage/images/slack-soket-mode.png') }}" alt="Enable Socket Mode" class="img-fluid mb-3 mt-2">
                    </li>
                </ol>
                <div class="alert alert-warning" role="alert">
                    <strong>Important:</strong> Keep your Bot Token and app-level token (if generated) confidential. Do not share them publicly or commit them to version control systems.
                </div>
                <div class="alert alert-info" role="alert">
                    <strong>Additional Info:</strong> To send a Slack notification to a specific user, the Slack user ID will be automatically retrieved using the email ID registered in the system.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?= get_label('close', 'Close') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<script src="{{asset('assets/js/pages/sms-gateway-settings.js')}}"></script>
@endsection