<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Call Logs Performance Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for optimizing call logs
    | performance, including pagination, caching, and data limits.
    |
    */

    // Pagination settings
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
        'min_per_page' => 5,
    ],

    // Search settings
    'search' => [
        'min_search_length' => 3,
        'max_search_results' => 50,
        'enable_fulltext_search' => true,
        'searchable_fields' => [
            'call_id',
            'summary',
            'transcript'
        ],
    ],

    // Data optimization
    'optimization' => [
        'exclude_from_list' => [
            'transcript',
            'webhook_data',
            'metadata'
        ],
        'exclude_for_non_admin' => [
            'cost',
            'currency',
            'webhook_data',
            'metadata'
        ],
        'enable_selective_loading' => true,
    ],

    // Caching settings
    'caching' => [
        'enable_cache' => env('CALL_LOGS_CACHE_ENABLED', true),
        'cache_ttl' => env('CALL_LOGS_CACHE_TTL', 300), // 5 minutes
        'cache_prefix' => 'call_logs',
    ],

    // Database optimization
    'database' => [
        'enable_indexes' => true,
        'batch_size' => 1000,
        'query_timeout' => 30, // seconds
    ],

    // API rate limiting
    'rate_limiting' => [
        'enabled' => env('CALL_LOGS_RATE_LIMITING', true),
        'max_requests_per_minute' => 60,
        'max_requests_per_hour' => 1000,
    ],

    // Export settings
    'export' => [
        'max_records_per_export' => 10000,
        'export_formats' => ['csv', 'json', 'xlsx'],
        'enable_background_export' => true,
    ],

    // Monitoring
    'monitoring' => [
        'enable_performance_tracking' => env('CALL_LOGS_PERFORMANCE_TRACKING', true),
        'log_slow_queries' => env('CALL_LOGS_LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => 1000, // milliseconds
    ],
]; 