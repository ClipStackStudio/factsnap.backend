<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Notification Rules by User Type
    |--------------------------------------------------------------------------
    |
    | Define notification limits and rules for different user types.
    | These rules are enforced server-side to prevent spam and abuse.
    |
    */

    'user_types' => [
        'guest' => [
            'max_daily_facts' => 1,
            'min_interval_minutes' => 720, // 12 hours
            'max_times_per_day' => 1,
            'max_per_package_daily' => 1,
            'allowed_delivery_modes' => ['daily', 'weekly'],
            'can_disable_quiet_hours' => false,
            'default_quiet_hours' => [
                'start' => '22:00',
                'end' => '08:00'
            ],
            'supports_delivery_window' => false,
            'supports_time_sensitive' => false,
        ],
        
        'logged_in' => [
            'max_daily_facts' => 3,
            'min_interval_minutes' => 120, // 2 hours
            'max_times_per_day' => 2,
            'max_per_package_daily' => 2,
            'allowed_delivery_modes' => ['daily', 'weekly', 'times_per_day'],
            'can_disable_quiet_hours' => true,
            'default_quiet_hours' => [
                'start' => '22:00',
                'end' => '07:00'
            ],
            'supports_delivery_window' => false,
            'supports_time_sensitive' => false,
        ],
        
        'premium' => [
            'max_daily_facts' => 8,
            'min_interval_minutes' => 60, // 1 hour
            'max_times_per_day' => 4,
            'max_per_package_daily' => 4,
            'allowed_delivery_modes' => ['daily', 'weekly', 'times_per_day', 'windowed'],
            'can_disable_quiet_hours' => true,
            'default_quiet_hours' => [
                'start' => '22:00',
                'end' => '07:00'
            ],
            'supports_delivery_window' => true,
            'supports_time_sensitive' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default notification settings applied when users subscribe to packages.
    |
    */

    'defaults' => [
        'guest' => [
            'delivery_mode' => 'daily',
            'preferred_times' => ['10:00'],
            'days_of_week' => null, // All days for daily mode
        ],
        
        'logged_in' => [
            'delivery_mode' => 'daily',
            'preferred_times' => ['10:00'],
            'days_of_week' => null,
        ],
        
        'premium' => [
            'delivery_mode' => 'times_per_day',
            'preferred_times' => ['10:00', '18:00'],
            'days_of_week' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Modes
    |--------------------------------------------------------------------------
    |
    | Available delivery modes and their configurations.
    |
    */

    'delivery_modes' => [
        'daily' => [
            'description' => 'Deliver at specific time(s) every day',
            'requires_times' => true,
            'requires_days' => false,
            'max_times' => 'user_type_dependent', // Defined per user type
        ],
        
        'weekly' => [
            'description' => 'Deliver on specific day(s) at specific time',
            'requires_times' => true,
            'requires_days' => true,
            'max_times' => 1, // One time per selected day
        ],
        
        'times_per_day' => [
            'description' => 'Deliver multiple times per day',
            'requires_times' => true,
            'requires_days' => false,
            'max_times' => 'user_type_dependent',
        ],
        
        'windowed' => [
            'description' => 'Deliver within a time window (premium only)',
            'requires_times' => false,
            'requires_days' => false,
            'requires_window' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Limits
    |--------------------------------------------------------------------------
    |
    | Hard system limits to prevent abuse.
    |
    */

    'system_limits' => [
        'max_preferred_times' => 8,
        'max_delivery_window_hours' => 6,
        'min_delivery_window_minutes' => 30,
        'jitter_range_minutes' => 5, // Random delay to prevent clustering
        'max_retry_attempts' => 3,
        'backoff_multiplier' => 2, // Exponential backoff for retries
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Time format and other validation rules.
    |
    */

    'validation' => [
        'time_format' => 'H:i', // 24-hour format HH:MM
        'valid_days_of_week' => [0, 1, 2, 3, 4, 5, 6], // Sunday = 0
        'timezone_validation' => true,
        'require_device_token' => true,
    ],
];
