<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Notifications\PasswordResetEmail;
use App\Notifications\CustomVerifyEmail;
use App\View\Composers\ResellerComposer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register fallback reseller bindings
        $this->app->bind('currentReseller', function () {
            // Return null if not bound by middleware
            return null;
        });
        
        $this->app->bind('currentResellerSettings', function () {
            // Return null if not bound by middleware  
            return null;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use our custom password reset notification
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new PasswordResetEmail($token))->toMail($notifiable);
        });

        // Use our custom email verification notification
        VerifyEmail::toMailUsing(function ($notifiable, $verificationUrl) {
            return (new CustomVerifyEmail())->toMail($notifiable);
        });

        // Register view composer for reseller data
        View::composer('app', ResellerComposer::class);
    }
}
