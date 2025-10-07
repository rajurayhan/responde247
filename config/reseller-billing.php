<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Overage Billing Threshold
    |--------------------------------------------------------------------------
    |
    | The minimum overage amount (in USD) that triggers immediate billing.
    | If overage is below this threshold, it will be carried forward to the
    | next billing period.
    |
    */
    'overage_threshold' => env('RESELLER_OVERAGE_THRESHOLD', 10.00),

    /*
    |--------------------------------------------------------------------------
    | Payment Failure Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days to wait before suspending service after payment failure.
    | During this grace period, the system will retry charging the customer.
    |
    */
    'payment_failure_grace_period' => env('RESELLER_PAYMENT_GRACE_PERIOD', 7),

    /*
    |--------------------------------------------------------------------------
    | Payment Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Number of times to retry failed payment attempts before giving up.
    |
    */
    'payment_retry_attempts' => env('RESELLER_PAYMENT_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry Delays
    |--------------------------------------------------------------------------
    |
    | Delay (in minutes) between each retry attempt.
    | Uses exponential backoff: 30 minutes, 2 hours, 1 day
    |
    */
    'retry_delays' => [30, 120, 1440],

    /*
    |--------------------------------------------------------------------------
    | Auto Billing Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic overage billing. When disabled, overages
    | will only be tracked but not automatically charged.
    |
    */
    'auto_billing_enabled' => env('RESELLER_AUTO_BILLING', true),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | Enable test mode for billing operations. When enabled, Stripe charges
    | will be simulated and transactions will be marked as completed without
    | actual payment processing.
    |
    */
    'test_mode' => env('RESELLER_BILLING_TEST_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Carry Forward Enabled
    |--------------------------------------------------------------------------
    |
    | Enable carrying forward small overages (below threshold) to the next
    | billing period. When disabled, small overages are billed at period end.
    |
    */
    'carry_forward_enabled' => env('RESELLER_CARRY_FORWARD', true),

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking Method
    |--------------------------------------------------------------------------
    |
    | Method used to track usage: 'cost' or 'duration'
    | - cost: Track actual call costs from Vapi.ai (recommended)
    | - duration: Calculate cost based on call duration
    |
    */
    'tracking_method' => env('RESELLER_TRACKING_METHOD', 'cost'),

    /*
    |--------------------------------------------------------------------------
    | Usage Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Percentage thresholds at which to send usage alerts to resellers.
    | Alerts will be sent when usage reaches these percentages of their limit.
    |
    */
    'usage_alert_thresholds' => [75, 90, 100],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for billing operations.
    |
    */
    'currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Detailed Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging of all usage tracking and billing operations.
    | Useful for debugging and auditing, but may generate large log files.
    |
    */
    'detailed_logging' => env('RESELLER_BILLING_LOG_DETAIL', true),

    /*
    |--------------------------------------------------------------------------
    | Billing Email Recipients
    |--------------------------------------------------------------------------
    |
    | Email addresses to notify about billing events (in addition to reseller).
    | Useful for sending copies of important billing notifications to admins.
    |
    */
    'admin_notification_emails' => env('RESELLER_BILLING_ADMIN_EMAILS', ''),

    /*
    |--------------------------------------------------------------------------
    | Enable Notifications
    |--------------------------------------------------------------------------
    |
    | Enable or disable different types of notifications.
    |
    */
    'notifications' => [
        'usage_alerts' => env('RESELLER_NOTIFICATIONS_USAGE_ALERTS', true),
        'overage_billed' => env('RESELLER_NOTIFICATIONS_OVERAGE_BILLED', true),
        'payment_failed' => env('RESELLER_NOTIFICATIONS_PAYMENT_FAILED', true),
        'monthly_report' => env('RESELLER_NOTIFICATIONS_MONTHLY_REPORT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Settings for webhook processing related to usage tracking.
    |
    */
    'webhook' => [
        'process_async' => env('RESELLER_BILLING_WEBHOOK_ASYNC', true),
        'retry_on_failure' => env('RESELLER_BILLING_WEBHOOK_RETRY', true),
        'max_retries' => env('RESELLER_BILLING_WEBHOOK_MAX_RETRIES', 3),
    ],
];

