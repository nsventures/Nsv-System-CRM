@extends('layout')
@section('title')
<?= get_label('update_user_profile', 'Update user profile') ?>
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
                        <a href="{{url('users')}}"><?= get_label('users', 'Users') ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{url('users/profile/'.$user->id)}}">{{$user->first_name.' '.$user->last_name}}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('update', 'Update') ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{url('users/update_user/' . $user->id)}}" class="form-submit-event" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="redirect_url" value="{{ url('users') }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label for="firstName" class="form-label"><?= get_label('first_name', 'First name') ?> <span class="asterisk">*</span></label>
                        <input class="tk-input" type="text" id="first_name" name="first_name" placeholder="<?= get_label('please_enter_first_name', 'Please enter first name') ?>" value="{{ $user->first_name }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="lastName" class="form-label"><?= get_label('last_name', 'Last name') ?> <span class="asterisk">*</span></label>
                        <input class="tk-input" type="text" name="last_name" placeholder="<?= get_label('please_enter_last_name', 'Please enter last name') ?>" id="last_name" value="{{ $user->last_name }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="role"><?= get_label('role', 'Role') ?> <span class="asterisk">*</span></label>
                        <select class="tk-select text-capitalize tom_static_select" id="role" name="role" data-placeholder="<?= get_label('Please select', 'Please select') ?>" data-allow-clear="false">
                            <option></option>
                            @foreach ($roles as $role)
                            <option value="{{$role->id}}" <?php if ($user->getRoleNames()->first() == $role->name) {
                                                                echo 'selected';
                                                            }  ?>>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="email" class="form-label"><?= get_label('email', 'E-mail') ?> <span class="asterisk">*</span></label>
                        <input class="tk-input" type="text" id="email" name="email" placeholder="<?= get_label('please_enter_email', 'Please enter email') ?>" value="{{ $user->email }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label">{{ get_label('country_code_and_phone_number', 'Country code and phone number') }}</label>
                        <div class="tk-inputgroup">
                            <input type="tel" name="phone" id="phone" class="tk-input" value="{{ $user->phone }}">
                            <span class="clear-input">&times;</span>
                        </div>
                        <input type="hidden" name="country_code" id="country_code" value="{{ $user->country_code }}">
                        <input type="hidden" name="country_iso_code" id="country_iso_code" value="{{ $user->country_iso_code }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="address" class="form-label"><?= get_label('address', 'Address') ?></label>
                        <input class="tk-input" type="text" id="address" name="address" placeholder="<?= get_label('please_enter_address', 'Please enter address') ?>" value="{{ $user->address }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="city" class="form-label"><?= get_label('city', 'City') ?></label>
                        <input class="tk-input" type="text" id="city" name="city" placeholder="<?= get_label('please_enter_city', 'Please enter city') ?>" value="{{ $user->city }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="state" class="form-label"><?= get_label('state', 'State') ?></label>
                        <input class="tk-input" type="text" id="state" name="state" placeholder="<?= get_label('please_enter_state', 'Please enter state') ?>" value="{{ $user->state }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="country" class="form-label"><?= get_label('country', 'Country') ?></label>
                        <input class="tk-input" type="text" id="country" name="country" placeholder="<?= get_label('please_enter_country', 'Please enter country') ?>" value="{{ $user->country }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="zip" class="form-label"><?= get_label('zip_code', 'ZIP code') ?></label>
                        <input class="tk-input" type="text" id="zip" name="zip" placeholder="<?= get_label('please_enter_zip_code', 'Please enter ZIP code') ?>" value="{{ $user->zip }}">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="dob" class="form-label"><?= get_label('date_of_birth', 'Date of birth') ?></label>
                        <input class="tk-input" type="text" id="dob" name="dob" value="{{ $user->dob?format_date($user->dob) : ''}}" placeholder="<?= get_label('please_select', 'Please select') ?>" autocomplete="off">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="doj" class="form-label"><?= get_label('date_of_joining', 'Date of joining') ?></label>
                        <input class="tk-input" type="text" id="doj" name="doj" value="{{ $user->doj?format_date($user->doj) : ''}}" placeholder="<?= get_label('please_select', 'Please select') ?>" autocomplete="off">
                    </div>
                    @if(isAdminOrHasAllDataAccess())
                    <div class="mb-3 col-md-6 form-password-toggle">
                        <label for="password" class="form-label"><?= get_label('password', 'Password') ?> <small class="text-muted"> ({{get_label('leave_blank_if_no_change', 'Leave it blank if no change')}})</small></label>
                        <div class="tk-inputgroup">
                            <input type="password" id="password" class="tk-input" name="password" placeholder="<?= get_label('please_enter_password', 'Please enter password') ?>" autocomplete="new-password">
                            <span class="cursor-pointer toggle-password"><i class="bx bx-hide"></i></span>
                            <span class="cursor-pointer" id="generate-password"><i class="bx bxs-magic-wand"></i></span>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6 form-password-toggle">
                        <label for="password_confirmation" class="form-label"><?= get_label('confirm_password', 'Confirm password') ?></label>
                        <div class="tk-inputgroup">
                            <input type="password" id="password_confirmation" class="tk-input" name="password_confirmation" placeholder="<?= get_label('please_re_enter_password', 'Please re enter password') ?>" autocomplete="new-password">
                            <span class="cursor-pointer toggle-password"><i class="bx bx-hide"></i></span>
                        </div>
                    </div>
                    @endif
                    <div class="mb-3 col-md-6">
                        <label for="photo" class="form-label"><?= get_label('profile_picture', 'Profile picture') ?></label>
                        <div class="d-flex align-items-center gap-3 p-3 border border-dashed rounded" style="border-style: dashed !important; border-width: 2px !important; border-color: var(--line) !important; min-height: 110px;">
                            <img src="{{$user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')}}" alt="user-avatar" class="d-block rounded-circle object-fit-cover" height="75" width="75" id="uploadedAvatar" />
                            <div class="flex-grow-1">
                                <input type="file" class="tk-input account-file-input" id="photo" name="profile" accept="image/*">
                                <small class="text-muted mt-1 d-block">Allowed JPG, JPEG, PNG, GIF, BMP or WEBP.</small>
                            </div>
                        </div>
                    </div>
                    @if(isAdminOrHasAllDataAccess())
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for=""><?= get_label('status', 'Status') ?> (<small class="text-muted mt-2"><?= get_label('deactivated_user_login_restricted', 'If Deactivated, the User Won\'t Be Able to Log In to Their Account') ?></small>)</label>
                        <div class="d-flex gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="user_active" value="1" {{ old('status', $user->status) == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="user_active">
                                    <?= get_label('active', 'Active') ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="user_deactive" value="0" {{ old('status', $user->status) == '0' ? 'checked' : '' }}>
                                <label class="form-check-label" for="user_deactive">
                                    <?= get_label('deactive', 'Deactive') ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    @endif

                    @php
                        $customFields = \App\Models\CustomField::where('module', 'user')->get();
                        $customFieldValues = [];
                        foreach ($user->customFieldValues as $fieldValue) {
                            $customFieldValues[$fieldValue->custom_field_id] = $fieldValue->value;
                        }
                    @endphp

                    @if($customFields->isNotEmpty())
                        <div class="mb-3">
                            <x-custom-fields :fields="$customFields" :values="$customFieldValues" :isEdit="true" />
                        </div>
                    @endif

                    <div class="d-flex justify-content-end mt-4 gap-2">
                        <button type="reset" class="btn btn-outline-secondary"><?= get_label('cancel', 'Cancel') ?></button>
                        <button type="submit" id="submit_btn" class="btn btn-primary"><?= get_label('update', 'Update') ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.querySelector('.account-file-input');
        const avatarImage = document.getElementById('uploadedAvatar');
        if (fileInput && avatarImage) {
            fileInput.onchange = () => {
                if (fileInput.files[0]) {
                    avatarImage.src = window.URL.createObjectURL(fileInput.files[0]);
                }
            };
        }
    });
</script>
@endsection
