<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait SafeTimestampConversion
{
    /**
     * Safely convert Stripe timestamp to Carbon instance with validation
     */
    protected function safeTimestampConversion($timestamp): Carbon
    {
        try {
            // Ensure timestamp is numeric
            if (!is_numeric($timestamp)) {
                Log::warning('Invalid timestamp format from Stripe', ['timestamp' => $timestamp]);
                return now();
            }

            // Convert to integer to handle any string timestamps
            $timestamp = (int) $timestamp;

            // Check if timestamp is reasonable (not too far in the future)
            $maxTimestamp = strtotime('+10 years'); // 10 years from now
            if ($timestamp > $maxTimestamp) {
                Log::warning('Stripe timestamp too far in future, using fallback', [
                    'stripe_timestamp' => $timestamp,
                    'max_allowed' => $maxTimestamp,
                    'stripe_date' => date('Y-m-d H:i:s', $timestamp),
                    'max_date' => date('Y-m-d H:i:s', $maxTimestamp)
                ]);
                
                // Use a reasonable fallback date (1 year from now)
                return now()->addYear();
            }

            // Check if timestamp is too far in the past
            $minTimestamp = strtotime('-1 year'); // 1 year ago
            if ($timestamp < $minTimestamp) {
                Log::warning('Stripe timestamp too far in past, using fallback', [
                    'stripe_timestamp' => $timestamp,
                    'min_allowed' => $minTimestamp,
                    'stripe_date' => date('Y-m-d H:i:s', $timestamp),
                    'min_date' => date('Y-m-d H:i:s', $minTimestamp)
                ]);
                
                return now();
            }

            $carbon = Carbon::createFromTimestamp($timestamp);
            
            // Additional validation: ensure the resulting date is valid
            if (!$carbon->isValid()) {
                Log::warning('Invalid Carbon date created from Stripe timestamp', [
                    'timestamp' => $timestamp,
                    'carbon_date' => $carbon->toDateTimeString()
                ]);
                return now();
            }

            return $carbon;

        } catch (\Exception $e) {
            Log::error('Error converting Stripe timestamp', [
                'timestamp' => $timestamp,
                'error' => $e->getMessage()
            ]);
            
            // Return current time as fallback
            return now();
        }
    }
}
