<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class AiService
{
    /**
     * Determines which AI model to use and generates a description.
     *
     * @param string $prompt The input for generating the description.
     * @return mixed The generated description or error response.
     */
    public function generateDescription(string $prompt)
    {
        $ai_model_settings = $this->getAiSettings();
        $selectedModel = $ai_model_settings['is_active'];

        if ($selectedModel === 'openrouter') {
            Log::info('Creating Description Using Openrouter AI Model/API');
            return $this->generateDescriptionOpenRouter($prompt, $ai_model_settings['openrouter_api_key'] ?? null);
        } elseif ($selectedModel === 'gemini') {
            Log::info('Creating Description Using Google Gemini AI Model/API');
            return $this->generateDescriptionGemini($prompt, $ai_model_settings['gemini_api_key'] ?? null);
        } else {
            return [
                'error' => true,
                'message' => 'Invalid AI model selected. Please update your settings.'
            ];
        }
    }

    /**
     * Generates a project/task description using OpenRouter's API.
     *
     * @param string $prompt The input for generating the description.
     * @param string|null $apiKey Optional API key to override settings
     * @return array{error: bool, data?: string, message?: string} Response array with status and data/message
     */
    public function generateDescriptionOpenRouter(string $prompt, $apiKey = null): array
    {
        // Get settings
        $settings = $this->getAiSettings('openrouter');

        // Use provided API key or get from settings
        $apiKey = $apiKey ?: ($settings['openrouter_api_key'] ?? null);

        if (empty($apiKey)) {
            Log::error('Missing OpenRouter API key');
            return [
                'error' => true,
                'message' => 'System configuration error: Missing API key.',
            ];
        }

        // Get dynamic settings
        $endpoint = $settings['openrouter_endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions';
        $model = $settings['openrouter_model'] ?? 'nousresearch/deephermes-3-mistral-24b-preview:free';
        $systemPrompt = $settings['openrouter_system_prompt'] ?? 'You are a helpful assistant that writes concise, professional project or task descriptions.';
        $temperature = $settings['openrouter_temperature'] ?? 0.7;
        $maxTokens = $settings['openrouter_max_tokens'] ?? 1024;
        $topP = $settings['openrouter_top_p'] ?? 0.95;
        $frequencyPenalty = $settings['openrouter_frequency_penalty'] ?? 0;
        $presencePenalty = $settings['openrouter_presence_penalty'] ?? 0;
        $timeout = $settings['request_timeout'] ?? 15;
        $maxRetries = $settings['max_retries'] ?? 2;

        // Apply prompt formatting if configured
        $formattedPrompt = $prompt;
        if (!empty($settings['default_prompt_prefix'])) {
            $formattedPrompt = $settings['default_prompt_prefix'] . ' ' . $formattedPrompt;
        }
        if (!empty($settings['default_prompt_suffix'])) {
            $formattedPrompt .= ' ' . $settings['default_prompt_suffix'];
        }

        // Check prompt length
        $maxPromptLength = $settings['max_prompt_length'] ?? 1000;
        if (empty($formattedPrompt) || strlen($formattedPrompt) > $maxPromptLength) {
            return [
                'error' => true,
                'message' => "Invalid prompt length. Must be between 1 and {$maxPromptLength} characters.",
            ];
        }

        $client = new Client(['timeout' => $timeout]);
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $response = $client->post($endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'HTTP-Referer' => config('app.url'),
                        'X-Title' => 'Taskify', // Optional: Name your app
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $formattedPrompt],
                        ],
                        'temperature' => (float)$temperature,
                        'max_tokens' => (int)$maxTokens,
                        'top_p' => (float)$topP,
                        'frequency_penalty' => (float)$frequencyPenalty,
                        'presence_penalty' => (float)$presencePenalty,
                    ],
                ]);

                $body = json_decode($response->getBody(), true);

                if (isset($body['choices'][0]['message']['content'])) {
                    return [
                        'error' => false,
                        'data' => $body['choices'][0]['message']['content'],
                    ];
                }

                return [
                    'error' => true,
                    'message' => $body['error']['message'] ?? 'Unknown error from OpenRouter',
                ];

            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    Log::error('OpenRouter API Error', [
                        'message' => $e->getMessage(),
                    ]);

                    // Try fallback if enabled
                    if (
                        !empty($settings['enable_fallback']) && $settings['enable_fallback'] &&
                        !empty($settings['fallback_provider']) && $settings['fallback_provider'] === 'gemini'
                    ) {
                        $fallbackResult = $this->generateDescriptionGemini($prompt);
                        if (!$fallbackResult['error']) {
                            // Add note that fallback was used
                            $fallbackResult['data'] = '[Generated using fallback provider] ' . $fallbackResult['data'];
                        }
                        return $fallbackResult;
                    }

                    return [
                        'error' => true,
                        'message' => 'An error occurred while generating the description using OpenRouter API.',
                    ];
                }

                // Wait before retrying
                $retryDelay = $settings['retry_delay'] ?? 1;
                sleep($retryDelay);
            }
        }

        return [
            'error' => true,
            'message' => 'Failed to generate description after multiple attempts.',
        ];
    }

    /**
     * Generates a project/task description using Gemini API.
     *
     * @param string $prompt The input for generating the description.
     * @param string|null $apiKey Optional API key to override settings
     * @return array{error: bool, data?: string, message?: string} Response array with status and data/message
     */
    public function generateDescriptionGemini(string $prompt, $apiKey = null)
    {
        try {
            // Get settings
            $settings = $this->getAiSettings('gemini');

            // Use provided API key or get from settings
            $apiKey = $apiKey ?: ($settings['gemini_api_key'] ?? null);

            if (empty($apiKey)) {
                Log::error('Missing Gemini API key');
                return [
                    'error' => true,
                    'message' => 'System configuration error: Missing API key.',
                ];
            }

            // Get dynamic settings
            $model = $settings['gemini_model'] ?? 'gemini-2.0-flash';
            $endpointTemplate = $settings['gemini_endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
            $endpoint = sprintf($endpointTemplate, $model);

            if (strpos($endpoint, '?key=') === false) {
                $endpoint .= '?key=' . $apiKey;
            }

            $temperature = $settings['gemini_temperature'] ?? 0.7;
            $topK = $settings['gemini_top_k'] ?? 40;
            $topP = $settings['gemini_top_p'] ?? 0.95;
            $maxOutputTokens = $settings['gemini_max_output_tokens'] ?? 1024;
            $timeout = $settings['request_timeout'] ?? 15;
            $maxRetries = $settings['max_retries'] ?? 2;

            // Rate limiting settings
            $MAX_REQUESTS_PER_MINUTE = $settings['rate_limit_per_minute'] ?? 15;
            $MAX_REQUESTS_PER_DAY = $settings['rate_limit_per_day'] ?? 1500;

            $userId = auth()->user()?->id ?? request()->ip();
            $minuteKey = "gemini_rate_minute_{$userId}";
            $dayKey = "gemini_rate_day_{$userId}";

            $currentTime = now();
            $minuteRequests = Cache::get($minuteKey, 0);

            if ($minuteRequests >= $MAX_REQUESTS_PER_MINUTE) {
                $retryAfter = 60 - $currentTime->second;
                return [
                    'error' => true,
                    'message' => "Rate limit exceeded. Please try again in {$retryAfter} seconds.",
                ];
            }

            $dayRequests = Cache::get($dayKey, 0);
            if ($dayRequests >= $MAX_REQUESTS_PER_DAY) {
                $tomorrow = $currentTime->addDay()->startOfDay();
                $hoursRemaining = $currentTime->diffInHours($tomorrow);
                return [
                    'error' => true,
                    'message' => "Daily limit exceeded. Please try again in {$hoursRemaining} hours.",
                ];
            }

            // Apply prompt formatting if configured
            $formattedPrompt = $prompt;
            if (!empty($settings['default_prompt_prefix'])) {
                $formattedPrompt = $settings['default_prompt_prefix'] . ' ' . $formattedPrompt;
            }
            if (!empty($settings['default_prompt_suffix'])) {
                $formattedPrompt .= ' ' . $settings['default_prompt_suffix'];
            }

            // Set default prompt prefix for Gemini if not specified
            if (strpos($formattedPrompt, "Generate a concise") === false) {
                $formattedPrompt = "Generate a concise, professional description for the following: {$formattedPrompt}";
            }

            $maxPromptLength = $settings['max_prompt_length'] ?? 1000;
            if (empty($formattedPrompt) || strlen($formattedPrompt) > $maxPromptLength) {
                return [
                    'error' => true,
                    'message' => "Invalid prompt length. Must be between 1 and {$maxPromptLength} characters.",
                ];
            }

            $client = new Client(['timeout' => $timeout]);
            $attempt = 0;

            while ($attempt < $maxRetries) {
                try {
                    $response = $client->post($endpoint, [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'text' => $formattedPrompt
                                        ]
                                    ]
                                ]
                            ],
                            'generationConfig' => [
                                'temperature' => (float)$temperature,
                                'topK' => (int)$topK,
                                'topP' => (float)$topP,
                                'maxOutputTokens' => (int)$maxOutputTokens,
                            ]
                        ]
                    ]);

                    $result = json_decode($response->getBody(), true);

                    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                        return [
                            'error' => true,
                            'message' => 'Invalid API response. Please Contact Support'
                        ];
                    }

                    Cache::put($minuteKey, $minuteRequests + 1, now()->addMinutes(1));
                    Cache::put($dayKey, $dayRequests + 1, now()->addDays(1));

                    return [
                        'error' => false,
                        'data' => $result['candidates'][0]['content']['parts'][0]['text'],
                    ];

                } catch (\Exception $e) {
                    $attempt++;
                    if ($attempt >= $maxRetries) {
                        Log::error('Gemini API Error', [
                            'message' => $e->getMessage(),
                        ]);

                        // Try fallback if enabled
                        if (
                            !empty($settings['enable_fallback']) && $settings['enable_fallback'] &&
                            !empty($settings['fallback_provider']) && $settings['fallback_provider'] === 'openrouter'
                        ) {
                            $fallbackResult = $this->generateDescriptionOpenRouter($prompt);
                            if (!$fallbackResult['error']) {
                                // Add note that fallback was used
                                $fallbackResult['data'] = '[Generated using fallback provider] ' . $fallbackResult['data'];
                            }
                            return $fallbackResult;
                        }

                        return [
                            'error' => true,
                            'message' => 'Failed to generate description. Please try again later.',
                        ];
                    }

                    // Wait before retrying
                    $retryDelay = $settings['retry_delay'] ?? 1;
                    sleep($retryDelay);
                }
            }

            return [
                'error' => true,
                'message' => 'Failed to generate description after multiple attempts.',
            ];

        } catch (\Exception $e) {
            Log::critical('Unexpected Error in generate_description_gemini', [
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => true,
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }
    }

    /**
     * Retrieve AI model settings from the database
     *
     * @param string|null $provider Specific provider to get settings for
     * @return array AI settings from the database with defaults applied
     */
    public function getAiSettings(?string $provider = null): array
    {
        // Check if settings are cached (using the helper's logic or custom logic)
        // Since we are moving this to a service, we can use app-level caching if we want, or call the optimized helper.
        // For independence, let's query directly but perhaps we should rely on the now-optimized get_settings?
        // But get_settings returns generic key-value. AI settings are a JSON blob in one variable.

        $settings = Setting::where('variable', 'ai_model_settings')->first();

        if (!$settings) {
            // Return default settings if none found
            return [
                'is_active' => 'openrouter',
                'openrouter_endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
                'openrouter_system_prompt' => 'You are a helpful assistant that writes concise, professional project or task descriptions.',
                'openrouter_temperature' => 0.7,
                'openrouter_max_tokens' => 1024,
                'openrouter_top_p' => 0.95,
                'gemini_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                'gemini_temperature' => 0.7,
                'gemini_top_k' => 40,
                'gemini_top_p' => 0.95,
                'gemini_max_output_tokens' => 1024,
                'rate_limit_per_minute' => 15,
                'rate_limit_per_day' => 1500,
                'max_retries' => 2,
                'retry_delay' => 1,
                'request_timeout' => 15,
                'max_prompt_length' => 1000,
            ];
        }

        $settings = json_decode($settings->value, true);

        // If a specific provider is requested, only return those settings
        if ($provider) {
            $providerSettings = [];
            // Get all settings that belong to the requested provider
            foreach ($settings as $key => $value) {
                if (strpos($key, $provider) === 0 || (!str_contains($key, 'openrouter_') && !str_contains($key, 'gemini_'))) {
                    $providerSettings[$key] = $value;
                }
            }

            // Add global settings that aren't provider-specific
            $globalKeys = [
                'is_active',
                'rate_limit_per_minute',
                'rate_limit_per_day',
                'max_retries',
                'retry_delay',
                'request_timeout',
                'max_prompt_length',
                'enable_fallback',
                'fallback_provider'
            ];

            foreach ($globalKeys as $key) {
                if (isset($settings[$key])) {
                    $providerSettings[$key] = $settings[$key];
                }
            }

            return $providerSettings;
        }

        return $settings;
    }
}
