<?php

namespace App\Notifications;

use App\Models\ResellerSubscription;
use App\Services\ResellerEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ResellerSubscriptionActivated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subscription;

    public function __construct(ResellerSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $reseller = $this->subscription->reseller;
        $package = $this->subscription->package;
        
        // Get reseller branding data
        $branding = ResellerEmailService::getResellerBranding($notifiable, $reseller);
        
        // Debug: Log branding data
        Log::info('ResellerSubscriptionActivated branding data', [
            'reseller_id' => $reseller->id,
            'reseller_org_name' => $reseller->org_name,
            'branding' => $branding,
            'admin_email' => $notifiable->email
        ]);
        
        // Calculate billing amount based on billing interval
        $billingInterval = $this->subscription->billing_interval ?? 'monthly';
        $amount = $this->subscription->custom_amount ?? ($billingInterval === 'yearly' ? $package->yearly_price : $package->price);
        $billingText = $billingInterval === 'yearly' ? 'year' : 'month';
        
        // Calculate next billing date
        $nextBillingDate = $this->subscription->current_period_end 
            ? $this->subscription->current_period_end->format('F j, Y')
            : 'N/A';

        return (new MailMessage)
            ->subject("Your Reseller Subscription is Now Active! - {$branding['app_name']} 🎉 ")
            ->view('emails.reseller-subscription-activated', [
                'user' => $notifiable,
                'reseller' => $reseller,
                'subscription' => $this->subscription,
                'package' => $package,
                'amount' => $amount,
                'billingInterval' => $billingInterval,
                'billingText' => $billingText,
                'nextBillingDate' => $nextBillingDate,
                'branding' => $branding,
                'headerTitle' => 'Your Reseller Subscription is Active!',
                'headerSubtitle' => 'Welcome to the reseller program',
            ]);
    }
}
