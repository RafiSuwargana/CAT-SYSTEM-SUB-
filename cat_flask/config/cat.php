<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flask API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Flask API yang menangani perhitungan IRT
    |
    */

    'flask_api_url' => env('FLASK_API_URL', 'http://localhost:5000'),
    'flask_api_timeout' => env('FLASK_API_TIMEOUT', 30),
    
    /*
    |--------------------------------------------------------------------------
    | CAT Algorithm Settings
    |--------------------------------------------------------------------------
    |
    | Settings untuk algoritma CAT
    |
    */

    'max_items' => env('CAT_MAX_ITEMS', 30),
    'min_items' => env('CAT_MIN_ITEMS', 10),
    'target_se' => env('CAT_TARGET_SE', 0.25),
    'theta_bounds' => [
        'min' => env('CAT_THETA_MIN', -6),
        'max' => env('CAT_THETA_MAX', 6)
    ],
    'change_control' => [
        'early_items' => env('CAT_CHANGE_EARLY', 1.0),  // Items 1-5
        'later_items' => env('CAT_CHANGE_LATER', 0.25)  // Items 6+
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Scoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration untuk scoring system
    |
    */

    'scoring' => [
        'base_score' => env('CAT_BASE_SCORE', 100),
        'theta_multiplier' => env('CAT_THETA_MULTIPLIER', 15)
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration untuk fallback behavior
    |
    */

    'enable_fallback' => env('CAT_ENABLE_FALLBACK', true),
    'fallback_timeout' => env('CAT_FALLBACK_TIMEOUT', 5),
    
    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration untuk logging API calls
    |
    */

    'log_api_calls' => env('CAT_LOG_API_CALLS', true),
    'log_level' => env('CAT_LOG_LEVEL', 'info'),
];
