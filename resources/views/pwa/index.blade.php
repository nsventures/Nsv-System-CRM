@extends('layout')

@section('title')
    {{ get_label('pwa_settings', 'PWA Settings') }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h4 class="fw-bold mb-0" style="font-size: 1.35rem;">{{ get_label('pwa_settings', 'PWA Settings') }}</h4>
        <div class="d-flex align-items-center gap-3">
            <nav class="breadcrumb mb-0" aria-label="breadcrumb">
                <a class="breadcrumb-item" href="{{ url('home') }}">{{ get_label('home', 'Home') }}</a>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-item"><span>{{ get_label('settings', 'Settings') }}</span></span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current">{{ get_label('pwa_settings', 'PWA Settings') }}</span>
            </nav>
        </div>
    </div>

    <div class="card mb-3 shadow-none border">
        <div class="card-header border-bottom py-2 px-3">
            <h6 class="card-title mb-0 text-secondary" style="font-size: 0.9rem;">
                <i class='bx bx-mobile-alt me-2 text-secondary fs-5'></i>{{ get_label('pwa_settings', 'PWA Settings') }}
            </h6>
        </div>
        <div class="card-body pt-3 px-3 pb-3">
            @if (session('success'))
                <div class="alert alert-light-success border-0 py-2 px-3 mb-3 d-flex align-items-center justify-content-between" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bx bx-check-circle me-2 fs-5 text-success"></i>
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('pwa-settings.update') }}" enctype="multipart/form-data"
                class="needs-validation" novalidate data-original-name="{{ $pwaSettings['name'] ?? 'Taskify' }}"
                data-original-short-name="{{ $pwaSettings['short_name'] ?? 'Taskify' }}"
                data-original-theme-color="{{ $pwaSettings['theme_color'] ?? '#000000' }}"
                data-original-background-color="{{ $pwaSettings['background_color'] ?? '#ffffff' }}"
                data-original-description="{{ $pwaSettings['description'] ?? 'A task management app to boost productivity' }}">

                @csrf

                <div class="row">
                    {{-- Name --}}
                    <div class="col-md-6 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control form-control-sm"
                            value="{{ old('name', $pwaSettings['name'] ?? 'Taskify') }}" required>
                        @error('name')
                            <div class="text-danger small mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Short Name --}}
                    <div class="col-md-6 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="short_name">Short Name <span class="text-danger">*</span></label>
                        <input type="text" name="short_name" id="short_name" class="form-control form-control-sm"
                            value="{{ old('short_name', $pwaSettings['short_name'] ?? 'Taskify') }}" required>
                        @error('short_name')
                            <div class="text-danger small mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Theme Color --}}
                    <div class="col-md-6 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="theme_color">Theme Color <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="theme_color" id="theme_color"
                                class="form-control form-control-color" style="width: 38px; height: 31px; padding: 2px;"
                                value="{{ old('theme_color', $pwaSettings['theme_color'] ?? '#000000') }}" required>
                            <span class="text-muted small" style="font-size: 0.8rem;">{{ old('theme_color', $pwaSettings['theme_color'] ?? '#000000') }}</span>
                        </div>
                        @error('theme_color')
                            <div class="text-danger small mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Background Color --}}
                    <div class="col-md-6 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="background_color">Background Color <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="background_color" id="background_color"
                                class="form-control form-control-color" style="width: 38px; height: 31px; padding: 2px;"
                                value="{{ old('background_color', $pwaSettings['background_color'] ?? '#ffffff') }}" required>
                            <span class="text-muted small" style="font-size: 0.8rem;">{{ old('background_color', $pwaSettings['background_color'] ?? '#ffffff') }}</span>
                        </div>
                        @error('background_color')
                            <div class="text-danger small mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="col-12 mb-2">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="description">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" rows="3" class="form-control form-control-sm" required style="font-size: 0.8rem;">{{ old('description', $pwaSettings['description'] ?? 'A task management app to boost productivity') }}</textarea>
                        @error('description')
                            <div class="text-danger small mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Logo --}}
                    <div class="col-12 mb-3">
                        <label class="form-label mb-1" style="font-size: 0.8rem;" for="logo">Logo <span class="text-danger">*</span></label>
                        <div class="alert alert-light-danger border-0 py-2 px-3 mb-2" style="font-size: 0.8rem; border-radius: 6px;" role="alert">
                            <i class="bx bx-info-circle me-2 fs-5 text-danger"></i>
                            Please upload minimum <strong>512x512 PNG</strong> logo or else it will not work.
                        </div>

                        <div class="rounded-3 border border-dashed p-3 text-center" style="border-style: dashed !important; border-width: 1px !important;">
                            <input type="file" name="logo" id="logo" class="form-control form-control-sm text-center mx-auto" style="max-width: 350px;" accept="image/png">
                            <p class="text-muted small mb-0 mt-2" style="font-size: 0.75rem;">Recommended Size: larger than 512 x 512</p>
                            <p class="text-muted small mt-1 mb-0" style="font-size: 0.75rem;">Current: <code>{{ $pwaSettings['logo'] ?? '/images/icons/logo-512x512.png' }}</code></p>
                        </div>

                        @error('logo')
                            <div class="text-danger small mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="d-flex justify-content-end gap-2 border-top pt-3 col-12 mt-2">
                        <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;" onclick="resetForm()">Reset</button>
                        <button type="submit" class="btn btn-xs btn-primary py-1 px-3" style="font-size: 0.8rem;"><i class='bx bx-save me-1'></i> Update Settings</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/pages/pwa-settings.js') }}"></script>
@endsection
