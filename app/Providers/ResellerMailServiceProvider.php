<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSending;
use App\Services\ResellerMailManager;

class ResellerMailServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Listen for mail sending events
        Event::listen(MessageSending::class, function (MessageSending $event) {
            // Check if we need to set reseller-specific mail configuration
            if (app()->has('currentReseller')) {
                $reseller = app('currentReseller');
                if ($reseller) {
                    ResellerMailManager::setMailConfig($reseller);
                }
            }
        });
    }
}
