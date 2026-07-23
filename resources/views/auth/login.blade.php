@extends('layout')
<title>{{ get_label('login', 'Login') }} - {{ $general_settings['company_title'] }}</title>

@section('page_styles')
    <style>
        /* Login background: client logo wall, softened so the form stays legible.
           Scoped to the auth pages via page_styles, so nothing else is affected. */
        .authentication-wrapper::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: url("{{ asset('assets/img/backgrounds/bg_login.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: .20;
            z-index: 0;
            pointer-events: none;
        }

        /* Keep the form above the watermark */
        .authentication-wrapper,
        .authentication-inner {
            position: relative;
            z-index: 1;
        }

        /* Let the card clearly float above the logo wall */
        .authentication-inner .card {
            background-color: #fff;
            border: 1px solid rgba(16, 24, 40, .06);
            box-shadow: 0 18px 45px rgba(16, 24, 40, .14), 0 2px 6px rgba(16, 24, 40, .06);
        }

        /* On narrow screens `cover` would zoom into one or two logos — fit the grid instead */
        @media (max-width: 768px) {
            .authentication-wrapper::before {
                background-size: contain;
                opacity: .14;
            }
        }

        /* Dark mode: dim the (white) artwork and keep the card readable */
        [data-theme="dark"] .authentication-wrapper::before,
        .dark-style .authentication-wrapper::before {
            opacity: .10;
            filter: invert(1) hue-rotate(180deg);
        }

        [data-theme="dark"] .authentication-inner .card,
        .dark-style .authentication-inner .card {
            background-color: #2b2c40;
            border-color: rgba(255, 255, 255, .08);
            box-shadow: 0 18px 45px rgba(0, 0, 0, .45);
        }
    </style>
@endsection

@section('content')
    <!-- Content -->
    <div class="container-fluid">
        @if (config('constants.ALLOW_MODIFICATION') === 0)
            <div class="col-12 mt-4 text-center">
                <div class="alert alert-warning mb-0">
                    <b>Note:</b> If you cannot log in here, please close the codecanyon frame by clicking on <b>x Remove
                        Frame</b> button from the top right corner of the page or <a href="{{ url('/') }}"
                        target="_blank">&gt;&gt; Click here &lt;&lt;</a>
                </div>
            </div>
        @endif
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <!-- Register -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="{{ url('/') }}" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <img src="{{ asset($general_settings['full_logo']) }}" width="300px" alt="" />
                                </span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-2">{{ get_label('welcome_to', 'Welcome to') }}
                            <?= $general_settings['company_title'] ?>! 👋</h4>
                        <p>{{ get_label('sign_into_your_account', 'Sign into your account') }}</p>
                        <form id="formAuthentication" class="form-submit-event mb-3"
                            action="{{ url('users/authenticate') }}" method="POST">


                            <input type="hidden" name="redirect_url" value="{{ redirect()->intended(url('home'))->getTargetUrl() }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">{{ get_label('email', 'Email') }} <span
                                        class="asterisk">*</span></label>
                                <input type="text" class="form-control" id="email" name="email"
                                    placeholder="<?= get_label('please_enter_email', 'Please enter email') ?>"
                                    value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? 'admin@gmail.com' : '' ?>"
                                    autofocus />
                            </div>
                            <div class="form-password-toggle mb-3">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">{{ get_label('password', 'Password') }} <span
                                            class="asterisk">*</span></label>
                                    <a href="{{ url('forgot-password') }}">
                                        <small>{{ get_label('forgot_password', 'Forgot Password') }}?</small>
                                    </a>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="<?= get_label('please_enter_password', 'Please enter password') ?>"
                                        value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? '123456' : '' ?>"
                                        aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>

                            @php
                                $settings = get_settings('general_settings');

                            @endphp
                            @if (!empty($settings['recaptcha_enabled']) && $settings['recaptcha_enabled'])
                                <div class="mb-4">
                                    <label class="form-label d-block">{{ get_label('captcha', 'Captcha') }} <span
                                            class="asterisk">*</span></label>
                                    <div class="d-flex justify-content-start">
                                        {!! NoCaptcha::display() !!}
                                    </div>
                                    @if ($errors->has('g-recaptcha-response'))
                                        <span class="text-danger small d-block mt-1">
                                            {{ $errors->first('g-recaptcha-response') }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="mb-4">
                                <button class="btn btn-primary d-grid w-100" id="submit_btn"
                                    type="submit">{{ get_label('login', 'Login') }}</button>
                            </div>
                            @if (!isset($general_settings['allowSignup']) || $general_settings['allowSignup'] == 1)
                                <div class="text-center">
                                    <p class="mb-{{ config('constants.ALLOW_MODIFICATION') === 0 ? '3' : '0' }}">
                                        {{ get_label('dont_have_account', 'Don\'t have an account?') }} <a
                                            href="{{ url('signup') }}">{{ get_label('sign_up', 'Sign Up') }}</a></p>
                                </div>
                            @endif
                            @if (config('constants.ALLOW_MODIFICATION') === 0)
                                <div class="mb-3">
                                    <button class="btn btn-success d-grid w-100 admin-login" type="button">Login As
                                        Admin</button>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-info d-grid w-100 member-login" type="button">Login As Team
                                        Member</button>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-warning d-grid w-100 client-login" type="button">Login As
                                        Client</button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
                <!-- /Register -->
            </div>
        </div>
    </div>
    <!-- / Content -->
    {!! NoCaptcha::renderJs() !!}
@endsection
