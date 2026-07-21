@extends('layout')
@section('title')
<?= get_label('create_client', 'Create client') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-2 mt-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{url('home')}}"><?= get_label('home', 'Home') ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{url('clients')}}"><?= get_label('clients', 'Clients') ?></a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('create', 'Create') ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    @role('admin')
    @php
    $account_creation_template = App\Models\Template::where('type', 'email')
    ->where('name', 'account_creation')
    ->first();
    @endphp
    @if (!($account_creation_template) || ($account_creation_template && $account_creation_template->status == 1))
    <div class="alert alert-primary" role="alert">
        {{ get_label('acc_crea_email_enabled_inf', 'As Account Creation Email Status Is Active, Please Ensure Email Settings Are Configured and Operational (Not Applicable If the Client Is for Internal Purposes).') }}
        <a href="{{ url('settings/templates') }}">
            {{ get_label('click_to_change_acc_crea_email_sts', 'Click Here to Change Account Creation Email Status.') }}
        </a>
    </div>
    @endif
    @endrole




    <div class="card">
        <div class="card-body">
            <form action="{{url('clients/store')}}" method="POST" class="form-submit-event" enctype="multipart/form-data">
                <input type="hidden" name="redirect_url" value="{{ url('clients') }}">
                @csrf
                <div class="row">
                    <div class="mb-3 col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="internal_client" name="internal_purpose">
                            <label class="form-check-label" for="internal_client"><?= get_label('internal_client', 'Is this a client for internal purpose only?') ?></label>
                            <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('internal_client_info', 'Select this option if you want to create a client for internal use only, without granting account access to the client.') ?>"></i>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="firstName" class="form-label"><?= get_label('first_name', 'First name') ?> <span class="asterisk">*</span></label>
                        <input class="tk-input" type="text" id="first_name" name="first_name" placeholder="<?= get_label('please_enter_first_name', 'Please enter first name') ?>" value="{{ old('first_name') }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="lastName" class="form-label"><?= get_label('last_name', 'Last name') ?> <span class="asterisk">*</span></label>
                        <input class="tk-input" type="text" name="last_name" id="last_name" placeholder="<?= get_label('please_enter_last_name', 'Please enter last name') ?>" value="{{ old('last_name') }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="email" class="form-label"><?= get_label('email', 'E-mail') ?> <span class="asterisk">*</span></label>
                        <input class="tk-input" type="text" id="email" name="email" placeholder="<?= get_label('please_enter_email', 'Please enter email') ?>" value="{{ old('email') }}" autocomplete="off">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label">{{ get_label('country_code_and_phone_number', 'Country code and phone number') }}</label>
                        <div style="position:relative;">
                            <input type="tel" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" data-type="create">
                            <span class="clear-input">&times;</span>
                        </div>
                        <input type="hidden" name="country_code" id="country_code">
                        <input type="hidden" name="country_iso_code" id="country_iso_code">
                    </div>
                    <div class="mb-3 col-md-6 form-password-toggle" id="passDiv">
                        <label for="password" class="form-label"><?= get_label('password', 'Password') ?> <span class="asterisk">*</span></label>
                        <div class="tk-inputgroup">
                            <input type="password" id="password" name="password" placeholder="<?= get_label('please_enter_password', 'Please enter password') ?>" autocomplete="new-password">
                            <span class="cursor-pointer toggle-password"><i class="bx bx-hide"></i></span>
                            <span class="cursor-pointer" id="generate-password"><i class="bx bxs-magic-wand"></i></span>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6 form-password-toggle" id="confirmPassDiv">
                        <label for="password_confirmation" class="form-label"><?= get_label('confirm_password', 'Confirm password') ?> <span class="asterisk">*</span></label>
                        <div class="tk-inputgroup">
                            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="<?= get_label('please_re_enter_password', 'Please re enter password') ?>" autocomplete="new-password">
                            <span class="cursor-pointer toggle-password"><i class="bx bx-hide"></i></span>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="dob" class="form-label"><?= get_label('date_of_birth', 'Date of birth') ?></label>
                        <input class="tk-input" type="text" id="dob" name="dob" placeholder="<?= get_label('please_select', 'Please select') ?>" autocomplete="off">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="doj" class="form-label"><?= get_label('date_of_joining', 'Date of joining') ?></label>
                        <input class="tk-input" type="text" id="doj" name="doj" placeholder="<?= get_label('please_select', 'Please select') ?>" autocomplete="off">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="company" class="form-label"><?= get_label('company', 'Company') ?></label>
                        <input class="tk-input" type="text" id="company" name="company" placeholder="<?= get_label('please_enter_company_name', 'Please enter company name') ?>" value="{{ old('company') }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="address" class="form-label"><?= get_label('address', 'Address') ?></label>
                        <input class="tk-input" type="text" id="address" name="address" placeholder="<?= get_label('please_enter_address', 'Please enter address') ?>" value="{{ old('address') }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="city" class="form-label"><?= get_label('city', 'City') ?></label>
                        <input class="tk-input" type="text" id="city" name="city" placeholder="<?= get_label('please_enter_city', 'Please enter city') ?>" value="{{ old('city') }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="state" class="form-label"><?= get_label('state', 'State') ?></label>
                        <input class="tk-input" type="text" id="state" name="state" placeholder="<?= get_label('please_enter_state', 'Please enter state') ?>" value="{{ old('state') }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="country" class="form-label"><?= get_label('country', 'Country') ?></label>
                        <input class="tk-input" type="text" id="country" name="country" placeholder="<?= get_label('please_enter_country', 'Please enter country') ?>" value="{{ old('country') }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="zip" class="form-label"><?= get_label('zip_code', 'Zip code') ?></label>
                        <input class="tk-input" type="text" id="zip" name="zip" placeholder="<?= get_label('please_enter_zip_code', 'Please enter ZIP code') ?>" value="{{ old('zip') }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="profile" class="form-label"><?= get_label('profile_picture', 'Profile picture') ?></label>
                        <div class="d-flex align-items-center gap-3 p-3 border border-dashed rounded" style="border-style: dashed !important; border-width: 2px !important; border-color: var(--line) !important; min-height: 110px;">
                            <img src="{{asset('storage/photos/no-image.jpg')}}" alt="client-avatar" class="d-block rounded-circle object-fit-cover" height="75" width="75" id="uploadedAvatar" />
                            <div class="flex-grow-1">
                                <input type="file" class="form-control form-control-sm account-file-input" id="profile" name="profile" accept="image/*">
                                <small class="text-muted mt-1 d-block">Allowed JPG, JPEG, PNG, GIF, BMP or WEBP.</small>
                            </div>
                        </div>
                    </div>
                    @if(isAdminOrHasAllDataAccess())
                    <div class="mb-3 col-md-6" id="statusDiv">
                        <label class="form-label" for=""><?= get_label('status', 'Status') ?> (<small class="text-muted mt-2"><?= get_label('deactivated_client_login_restricted', 'If Deactivated, the Client Won\'t Be Able to Log In to Their Account') ?></small>)</label>
                        <div class="d-flex gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="client_active" value="1" {{ old('status') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="client_active">
                                    <?= get_label('active', 'Active') ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="client_deactive" value="0" {{ old('status', '0') == '0' ? 'checked' : '' }}>
                                <label class="form-check-label" for="client_deactive">
                                    <?= get_label('deactive', 'Deactive') ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6" id="requireEvDiv">
                        <label class="form-label" for="">
                            <?= get_label('require_email_verification', 'Require email verification?') ?>
                            <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip" data-bs-placement="top" title="<?= get_label('client_require_email_verification_info', 'If Yes is selected, client will receive a verification link via email. Please ensure that email settings are configured and operational.') ?>"></i>
                        </label>
                        <div class="d-flex gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="require_ev" id="require_ev_yes" value="1" {{ old('require_ev', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="require_ev_yes">
                                    <?= get_label('yes', 'Yes') ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="require_ev" id="require_ev_no" value="0" {{ old('require_ev') == '0' ? 'checked' : '' }}>
                                <label class="form-check-label" for="require_ev_no">
                                    <?= get_label('no', 'No') ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    @endif

                    @php
                        $customFields = \App\Models\CustomField::where('module', 'client')->get();
                    @endphp

                    @if($customFields->isNotEmpty())
                        <div class="mb-3">
                            <x-custom-fields :fields="$customFields" :values="[]" :isEdit="false" />
                        </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="reset" class="btn btn-outline-secondary"><?= get_label('cancel', 'Cancel') ?></button>
                        <button type="submit" class="btn btn-primary" id="submit_btn"><?= get_label('create', 'Create') ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.getElementById('profile').addEventListener('change', function(e) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('uploadedAvatar').src = e.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    });
</script>
@endsection
