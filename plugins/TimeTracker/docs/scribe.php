<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Title & Description
    |--------------------------------------------------------------------------
    */
    'title' => 'Time Tracker Plugin API',
    'description' => 'API documentation for the Time Tracker Plugin. Includes endpoints for logging work activity, uploading screenshots, and loading time tracking configuration.',

    /*
    |--------------------------------------------------------------------------
    | Route Groups
    |--------------------------------------------------------------------------
    */
    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/plugin/timetracker'],
                'domains' => ['*'], // Accept all domains (or restrict to one)
            ],
            'apply' => [
                'headers' => [
                    'Authorization' => 'Bearer {token}',
                    'Accept' => 'application/json',
                ],
                'middleware' => [
                    'api',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Settings
    |--------------------------------------------------------------------------
    */
    'output' => [
        'path' => 'Plugins/TimeTracker/docs',
        'type' => 'html',
        'static' => true,
        'download' => [
            'postman' => true,
            'openapi' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Settings
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'enabled' => true,
        'default' => 'Bearer {token}',
        'in' => 'header',
        'name' => 'Authorization',
    ],

    /*
    |--------------------------------------------------------------------------
    | Example Requests
    |--------------------------------------------------------------------------
    */
    'examples' => [
        'enabled' => true,
        'source' => 'auto',
    ],

    /*
    |--------------------------------------------------------------------------
    | Postman Collection Options
    |--------------------------------------------------------------------------
    */
    'postman' => [
        'enabled' => true,
        'base_url' => env('APP_URL', 'http://localhost'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    */
    'language' => 'en',

];
