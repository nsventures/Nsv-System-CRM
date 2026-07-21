@extends('layout')
@section('title')
    <?= get_label('ai_model_settings', 'AI Model Settings') ?>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
            <h4 class="fw-bold mb-0"><?= get_label('ai_model_settings', 'AI Model Settings') ?></h4>
            <div class="d-flex align-items-center gap-3">
                <nav class="breadcrumb mb-0" aria-label="breadcrumb">
                    <a class="breadcrumb-item" href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                    <span class="breadcrumb-sep">/</span>
                    <span class="breadcrumb-item"><?= get_label('settings', 'Settings') ?></span>
                    <span class="breadcrumb-sep">/</span>
                    <span class="breadcrumb-current"><?= get_label('ai_models', 'AI Models') ?></span>
                </nav>
            </div>
        </div>

        <form action="{{ route('settings.store_ai_models') }}" class="form-submit-event" method="POST">
            <input type="hidden" name="dnr">
            @csrf
            @method('PUT')
            
            <div class="row">
                <!-- Left Column -->
                <div class="col-xl-8 col-lg-8 col-md-12">
                    
                    <!-- OpenRouter Settings -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 text-secondary"><i class='bx bx-bot me-2'></i> <?= get_label('openrouter_settings', 'OpenRouter Settings') ?></h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#openRouterHelpModal">
                                {{ get_label('how_to_get_api_key', 'How to get API Key?') }}
                            </button>
                        </div>
                        <div class="card-body pt-4">
                            <h6 class="text-primary mb-3"><i class='bx bx-slider-alt me-1'></i> <?= get_label('basic_settings', 'Basic Settings') ?></h6>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="openrouter_api_key" class="form-label">
                                        <?= get_label('openrouter_api_key', 'OpenRouter API Key') ?>
                                        <span class="asterisk">*</span>
                                    </label>
                                    <input class="form-control" type="text" name="openrouter_api_key"
                                        id="openrouter_api_key"
                                        placeholder="<?= get_label('please_enter_openrouter_api_key', 'Please enter OpenRouter API Key') ?>"
                                        value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($ai_model_settings['openrouter_api_key'] ?? '')) : $ai_model_settings['openrouter_api_key'] ?? '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="openrouter_model" class="form-label">
                                        <?= get_label('openrouter_model', 'OpenRouter Model') ?>
                                    </label>
                                    <select class="form-select" name="openrouter_model" id="openrouter_model">
                                        <option value="nousresearch/deephermes-3-mistral-24b-preview:free"
                                            <?= isset($ai_model_settings['openrouter_model']) && $ai_model_settings['openrouter_model'] === 'nousresearch/deephermes-3-mistral-24b-preview:free' ? 'selected' : '' ?>>
                                            DeepHermes 3 Mistral (Free)
                                        </option>
                                        <option value="openai/gpt-3.5-turbo"
                                            <?= isset($ai_model_settings['openrouter_model']) && $ai_model_settings['openrouter_model'] === 'openai/gpt-3.5-turbo' ? 'selected' : '' ?>>
                                            GPT-3.5 Turbo
                                        </option>
                                        <option value="anthropic/claude-3-opus"
                                            <?= isset($ai_model_settings['openrouter_model']) && $ai_model_settings['openrouter_model'] === 'anthropic/claude-3-opus' ? 'selected' : '' ?>>
                                            Claude 3 Opus
                                        </option>
                                        <option value="anthropic/claude-3-sonnet"
                                            <?= isset($ai_model_settings['openrouter_model']) && $ai_model_settings['openrouter_model'] === 'anthropic/claude-3-sonnet' ? 'selected' : '' ?>>
                                            Claude 3 Sonnet
                                        </option>
                                        <option value="meta-llama/llama-3-8b-instruct"
                                            <?= isset($ai_model_settings['openrouter_model']) && $ai_model_settings['openrouter_model'] === 'meta-llama/llama-3-8b-instruct' ? 'selected' : '' ?>>
                                            Llama 3 8B Instruct
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="openrouter_endpoint" class="form-label">
                                        <?= get_label('openrouter_endpoint', 'OpenRouter Endpoint') ?>
                                    </label>
                                    <input type="url" class="form-control" name="openrouter_endpoint"
                                        id="openrouter_endpoint" placeholder="Enter OpenRouter API endpoint"
                                        value="<?= $ai_model_settings['openrouter_endpoint'] ?? '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="openrouter_is_active" class="form-label d-block">
                                        <?= get_label('is_active', 'Is Active') ?>
                                    </label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input is_active_ai_model" type="radio" name="is_active"
                                            id="openrouter_is_active" value="openrouter"
                                            <?= isset($ai_model_settings['is_active']) && $ai_model_settings['is_active'] === 'openrouter' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="openrouter_is_active">
                                            <?= get_label('set_as_active_model', 'Set as active model') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="mb-4">
                            <h6 class="text-primary mb-3"><i class='bx bx-cog me-1'></i> <?= get_label('advanced_settings', 'Advanced Settings') ?></h6>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="openrouter_system_prompt" class="form-label">
                                        <?= get_label('system_prompt', 'System Prompt') ?>
                                    </label>
                                    <textarea class="form-control" name="openrouter_system_prompt" id="openrouter_system_prompt" rows="3"><?= $ai_model_settings['openrouter_system_prompt'] ?? 'You are a helpful assistant that writes concise, professional project or task descriptions.' ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="openrouter_temperature" class="form-label">
                                        <?= get_label('temperature', 'Temperature') ?>
                                        <i class="bx bx-info-circle text-primary ms-1" data-bs-toggle="tooltip"
                                            title="Controls randomness: 0 = deterministic, 2 = maximum randomness"></i>
                                    </label>
                                    <input type="range" class="form-range" name="openrouter_temperature"
                                        id="openrouter_temperature" min="0" max="2" step="0.1"
                                        value="<?= $ai_model_settings['openrouter_temperature'] ?? '0.7' ?>">
                                    <div class="d-flex justify-content-between">
                                        <small>0</small>
                                        <small
                                            id="openrouter_temperature_value"><?= $ai_model_settings['openrouter_temperature'] ?? '0.7' ?></small>
                                        <small>2</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="openrouter_max_tokens" class="form-label">
                                        <?= get_label('max_tokens', 'Max Tokens') ?>
                                    </label>
                                    <input type="number" class="form-control" name="openrouter_max_tokens"
                                        id="openrouter_max_tokens" min="1" max="4096"
                                        value="<?= $ai_model_settings['openrouter_max_tokens'] ?? '1024' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="openrouter_top_p" class="form-label">
                                        <?= get_label('top_p', 'Top P') ?>
                                    </label>
                                    <input type="number" class="form-control" name="openrouter_top_p"
                                        id="openrouter_top_p" min="0" max="1" step="0.01"
                                        value="<?= $ai_model_settings['openrouter_top_p'] ?? '0.95' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="openrouter_frequency_penalty" class="form-label">
                                        <?= get_label('frequency_penalty', 'Frequency Penalty') ?>
                                    </label>
                                    <input type="number" class="form-control" name="openrouter_frequency_penalty"
                                        id="openrouter_frequency_penalty" min="-2" max="2" step="0.1"
                                        value="<?= $ai_model_settings['openrouter_frequency_penalty'] ?? '0' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="openrouter_presence_penalty" class="form-label">
                                        <?= get_label('presence_penalty', 'Presence Penalty') ?>
                                    </label>
                                    <input type="number" class="form-control" name="openrouter_presence_penalty"
                                        id="openrouter_presence_penalty" min="-2" max="2" step="0.1"
                                        value="<?= $ai_model_settings['openrouter_presence_penalty'] ?? '0' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gemini Settings -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 text-secondary"><i class='bx bxl-google me-2'></i> <?= get_label('gemini_settings', 'Gemini Settings') ?></h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#geminiHelpModal">
                                {{ get_label('how_to_get_api_key', 'How to get API Key?') }}
                            </button>
                        </div>
                        <div class="card-body pt-4">
                            <h6 class="text-primary mb-3"><i class='bx bx-slider-alt me-1'></i> <?= get_label('basic_settings', 'Basic Settings') ?></h6>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="gemini_api_key" class="form-label">
                                        <?= get_label('gemini_api_key', 'Gemini API Key') ?>
                                        <span class="asterisk">*</span>
                                    </label>
                                    <input class="form-control" type="text" name="gemini_api_key" id="gemini_api_key"
                                        placeholder="<?= get_label('please_enter_gemini_api_key', 'Please enter Gemini API Key') ?>"
                                        value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($ai_model_settings['gemini_api_key'] ?? '')) : $ai_model_settings['gemini_api_key'] ?? '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gemini_model" class="form-label">
                                        <?= get_label('gemini_model', 'Gemini Model') ?>
                                    </label>
                                    <select class="form-select" name="gemini_model" id="gemini_model">
                                        <option value="gemini-2.0-flash"
                                            <?= isset($ai_model_settings['gemini_model']) && $ai_model_settings['gemini_model'] === 'gemini-2.0-flash' ? 'selected' : '' ?>>
                                            Gemini 2.0 Flash (Default)
                                        </option>
                                        <option value="gemini-pro"
                                            <?= isset($ai_model_settings['gemini_model']) && $ai_model_settings['gemini_model'] === 'gemini-pro' ? 'selected' : '' ?>>
                                            Gemini Pro
                                        </option>
                                        <option value="gemini-2.0-pro"
                                            <?= isset($ai_model_settings['gemini_model']) && $ai_model_settings['gemini_model'] === 'gemini-2.0-pro' ? 'selected' : '' ?>>
                                            Gemini 2.0 Pro
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gemini_endpoint" class="form-label">
                                        <?= get_label('gemini_endpoint', 'Gemini Endpoint') ?>
                                    </label>
                                    <input type="url" class="form-control" name="gemini_endpoint"
                                        id="gemini_endpoint" placeholder="Enter Gemini API endpoint"
                                        value="<?= $ai_model_settings['gemini_endpoint'] ?? '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gemini_is_active" class="form-label d-block">
                                        <?= get_label('is_active', 'Is Active') ?>
                                    </label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input is_active_ai_model" type="radio"
                                            name="is_active" id="gemini_is_active" value="gemini"
                                            <?= isset($ai_model_settings['is_active']) && $ai_model_settings['is_active'] === 'gemini' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="gemini_is_active">
                                            <?= get_label('set_as_active_model', 'Set as active model') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="mb-4">
                            <h6 class="text-primary mb-3"><i class='bx bx-cog me-1'></i> <?= get_label('advanced_settings', 'Advanced Settings') ?></h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="gemini_temperature" class="form-label">
                                        <?= get_label('temperature', 'Temperature') ?>
                                    </label>
                                    <input type="range" class="form-range" name="gemini_temperature"
                                        id="gemini_temperature" min="0" max="2" step="0.1"
                                        value="<?= $ai_model_settings['gemini_temperature'] ?? '0.7' ?>">
                                    <div class="d-flex justify-content-between">
                                        <small>0</small>
                                        <small
                                            id="gemini_temperature_value"><?= $ai_model_settings['gemini_temperature'] ?? '0.7' ?></small>
                                        <small>2</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gemini_max_output_tokens" class="form-label">
                                        <?= get_label('max_output_tokens', 'Max Output Tokens') ?>
                                    </label>
                                    <input type="number" class="form-control" name="gemini_max_output_tokens"
                                        id="gemini_max_output_tokens" min="1" max="4096"
                                        value="<?= $ai_model_settings['gemini_max_output_tokens'] ?? '1024' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gemini_top_k" class="form-label">
                                        <?= get_label('top_k', 'Top K') ?>
                                    </label>
                                    <input type="number" class="form-control" name="gemini_top_k" id="gemini_top_k"
                                        min="1" max="100"
                                        value="<?= $ai_model_settings['gemini_top_k'] ?? '40' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gemini_top_p" class="form-label">
                                        <?= get_label('top_p', 'Top P') ?>
                                    </label>
                                    <input type="number" class="form-control" name="gemini_top_p" id="gemini_top_p"
                                        min="0" max="1" step="0.01"
                                        value="<?= $ai_model_settings['gemini_top_p'] ?? '0.95' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- End Left Column -->

                <!-- Right Column -->
                <div class="col-xl-4 col-lg-4 col-md-12">
                    
                    <!-- Global Settings -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header border-bottom">
                            <h6 class="card-title mb-0 text-secondary"><i class='bx bx-globe me-2'></i> <?= get_label('global_settings', 'Global Settings') ?></h6>
                        </div>
                        <div class="card-body pt-4">
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <label class="form-check-label d-block mb-2" for="enable_fallback">
                                        <?= get_label('enable_fallback', 'Enable Fallback Between Providers') ?>
                                    </label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="enable_fallback"
                                            id="enable_fallback" value="1"
                                            <?= isset($ai_model_settings['enable_fallback']) && $ai_model_settings['enable_fallback'] ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="fallback_provider" class="form-label">
                                        <?= get_label('fallback_provider', 'Fallback Provider') ?>
                                    </label>
                                    <select class="form-select" name="fallback_provider" id="fallback_provider">
                                        <option value="">Select Fallback Provider</option>
                                        <option value="gemini"
                                            <?= isset($ai_model_settings['fallback_provider']) && $ai_model_settings['fallback_provider'] === 'gemini' ? 'selected' : '' ?>>
                                            Gemini</option>
                                        <option value="openrouter"
                                            <?= isset($ai_model_settings['fallback_provider']) && $ai_model_settings['fallback_provider'] === 'openrouter' ? 'selected' : '' ?>>
                                            OpenRouter</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="max_retries" class="form-label">
                                        <?= get_label('max_retries', 'Max Retries') ?>
                                    </label>
                                    <input type="number" class="form-control" name="max_retries" id="max_retries"
                                        min="0" max="10"
                                        value="<?= $ai_model_settings['max_retries'] ?? '2' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="retry_delay" class="form-label">
                                        <?= get_label('retry_delay', 'Retry Delay (s)') ?>
                                    </label>
                                    <input type="number" class="form-control" name="retry_delay" id="retry_delay"
                                        min="1" value="<?= $ai_model_settings['retry_delay'] ?? '5' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="rate_limit_per_minute" class="form-label">
                                        <?= get_label('rate_limit_per_minute', 'Limit / Min') ?>
                                    </label>
                                    <input type="number" class="form-control" name="rate_limit_per_minute"
                                        id="rate_limit_per_minute" min="1" max="60"
                                        value="<?= $ai_model_settings['rate_limit_per_minute'] ?? '15' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="rate_limit_per_day" class="form-label">
                                        <?= get_label('rate_limit_per_day', 'Limit / Day') ?>
                                    </label>
                                    <input type="number" class="form-control" name="rate_limit_per_day"
                                        id="rate_limit_per_day" min="1"
                                        value="<?= $ai_model_settings['rate_limit_per_day'] ?? '1500' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="request_timeout" class="form-label">
                                        <?= get_label('request_timeout', 'Timeout (s)') ?>
                                    </label>
                                    <input type="number" class="form-control" name="request_timeout"
                                        id="request_timeout" min="1" max="60"
                                        value="<?= $ai_model_settings['request_timeout'] ?? '15' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="max_prompt_length" class="form-label">
                                        <?= get_label('max_prompt_length', 'Max Length') ?>
                                    </label>
                                    <input type="number" class="form-control" name="max_prompt_length"
                                        id="max_prompt_length" min="100" max="10000"
                                        value="<?= $ai_model_settings['max_prompt_length'] ?? '1000' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Prompt Settings -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header border-bottom">
                            <h6 class="card-title mb-0 text-secondary"><i class='bx bx-edit me-2'></i> <?= get_label('prompt_settings', 'Prompt Settings') ?></h6>
                        </div>
                        <div class="card-body pt-4">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="default_prompt_prefix" class="form-label">
                                        <?= get_label('default_prompt_prefix', 'Default Prompt Prefix') ?>
                                    </label>
                                    <input type="text" class="form-control" name="default_prompt_prefix"
                                        id="default_prompt_prefix"
                                        value="<?= $ai_model_settings['default_prompt_prefix'] ?? '' ?>">
                                    <small class="text-muted">Added before every prompt</small>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="default_prompt_suffix" class="form-label">
                                        <?= get_label('default_prompt_suffix', 'Default Prompt Suffix') ?>
                                    </label>
                                    <input type="text" class="form-control" name="default_prompt_suffix"
                                        id="default_prompt_suffix"
                                        value="<?= $ai_model_settings['default_prompt_suffix'] ?? '' ?>">
                                    <small class="text-muted">Added after every prompt</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- End Right Column -->

                <!-- Full Width Action Buttons -->
                <div class="col-12 mt-3">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-outline-secondary"><?= get_label('cancel', 'Cancel') ?></button>
                        <button type="submit" class="btn btn-primary" id="submit_btn"><i class='bx bx-save me-1'></i> <?= get_label('update', 'Update') ?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- OpenRouter API Key Guide Modal -->
    <div class="modal fade" id="openRouterHelpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ get_label('openrouter_api_integration', 'OpenRouter API Integration') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4>📌 Step 1: Sign Up or Log In</h4>
                    <ol>
                        <li>Visit <a href="https://openrouter.ai" target="_blank">OpenRouter</a> and click <b>Sign In</b>
                            at the top-right corner.</li>
                        <li>If you don't have an account, click <b>Sign Up</b> to create one.</li>
                    </ol>
                    <a href="{{ asset('storage/images/openrouter-login.png') }}" data-lightbox="OpenRouter"
                        data-title="OpenRouter Login Screen">
                        <img src="{{ asset('storage/images/openrouter-login.png') }}" alt="OpenRouter Login Screen"
                            class="img-fluid mb-3 rounded border shadow-sm">
                    </a>

                    <h4>📌 Step 2: Access API Keys Section</h4>
                    <ol>
                        <li>After logging in, click on your profile icon and select <b>Keys</b>.</li>
                    </ol>
                    <a href="{{ asset('storage/images/openrouter-apikeys.png') }}" data-lightbox="OpenRouter"
                        data-title="OpenRouter API Keys">
                        <img src="{{ asset('storage/images/openrouter-apikeys.png') }}" alt="OpenRouter API Keys"
                            class="img-fluid mb-3 rounded border shadow-sm">
                    </a>

                    <h4>📌 Step 3: Create a New API Key</h4>
                    <ol>
                        <li>Click <b>Create Key</b> and fill in the necessary details.</li>
                        <li>Your new API key will be displayed. <b>Copy</b> it for later use.</li>
                    </ol>
                    <a href="{{ asset('storage/images/openrouter-createkey.png') }}" data-lightbox="OpenRouter"
                        data-title="Create OpenRouter API Key">
                        <img src="{{ asset('storage/images/openrouter-createkey.png') }}" alt="Create OpenRouter API Key"
                            class="img-fluid mb-3 rounded border shadow-sm">
                    </a>

                    <h4>📌 Step 4: Add Your API Parameters</h4>
                    <ol>
                        <li>Here are the parameters you'll need to configure:</li>
                        <ul>
                            <li><b>Model</b>: Set the model for your requests.</li>
                            <li><b>Endpoint</b>: Define the endpoint URL.</li>
                            <li><b>System Prompt</b>: Provide a prompt that guides the model.</li>
                            <li><b>Temperature</b>: Set the temperature for randomness (default: 0.7).</li>
                            <li><b>Max Tokens</b>: Set the maximum number of tokens (default: 150).</li>
                            <li><b>Top P</b>: Control the nucleus sampling (default: 1.0).</li>
                            <li><b>Frequency Penalty</b>: Adjust the penalty for frequent tokens (default: 0.0).</li>
                            <li><b>Presence Penalty</b>: Set the penalty for new tokens (default: 0.0).</li>
                        </ul>
                    </ol>

                    <h4>📌 Step 5: Save and Integrate</h4>
                    <ol>
                        <li>Once your API key and parameters are set, paste them into the appropriate configuration fields
                            in Taskify.</li>
                        <li>Click <b>Save</b> to finalize the setup.</li>
                    </ol>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Google Gemini API Key Guide Modal -->
    <div class="modal fade" id="geminiHelpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ get_label('gemini_api_integration', 'Google Gemini API Integration') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4>📌 Step 1: Sign Up or Log In</h4>
                    <ol>
                        <li>Visit <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a> and sign in or create an account.</li>
                        <li>If you don't have an account, click <b>Sign Up</b> to create one.</li>
                    </ol>
                    <a href="{{ asset('storage/images/google-ai-dev-login.png') }}" data-lightbox="Gemini" data-title="Google Gemini Login">
                        <img src="{{ asset('storage/images/google-ai-dev-login.png') }}" alt="Google Gemini Login" class="img-fluid mb-3 rounded border shadow-sm">
                    </a>

                    <h4>📌 Step 2: Generate API Key</h4>
                    <ol>
                        <li>After logging in, navigate to the <b>API Keys</b> section.</li>
                        <li>Click on <b>Create API Key</b> to generate a new API key for your project.</li>
                    </ol>
                    <a href="{{ asset('storage/images/google-ai-dev-generate-key.png') }}" data-lightbox="Gemini" data-title="Generate API Key">
                        <img src="{{ asset('storage/images/google-ai-dev-generate-key.png') }}" alt="Generate Gemini API Key" class="img-fluid mb-3 rounded border shadow-sm">
                    </a>

                    <h4>📌 Step 3: Add Your API Parameters</h4>
                    <ol>
                        <li>Once you have the API key, configure the following parameters:</li>
                        <ul>
                            <li><b>Gemini Model</b>: Select the model you wish to use for API requests.</li>
                            <li><b>Gemini Endpoint</b>: Set the appropriate API endpoint URL.</li>
                            <li><b>Gemini Temperature</b>: Set the temperature to control randomness (default: 0.7).</li>
                            <li><b>Gemini Top K</b>: Define the number of possible tokens to sample from.</li>
                            <li><b>Gemini Top P</b>: Control the nucleus sampling for more focused outputs (default: 1.0).</li>
                            <li><b>Gemini Max Output Tokens</b>: Set the maximum number of tokens to generate in the output (default: 150).</li>
                        </ul>
                    </ol>

                    <h4>📌 Step 4: Save and Integrate</h4>
                    <ol>
                        <li>Once you have your API key and parameters, paste them into the appropriate configuration fields in Taskify.</li>
                        <li>Click <b>Save</b> to finalize your setup.</li>
                    </ol>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
