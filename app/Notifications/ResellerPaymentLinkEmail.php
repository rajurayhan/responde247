<?php

namespace App\Notifications;

use App\Models\Reseller;
use App\Models\ResellerSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\ResellerEmailService;

class ResellerPaymentLinkEmail extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reseller;
    protected $subscription;
    protected $paymentLinkUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reseller $reseller, ResellerSubscription $subscription, string $paymentLinkUrl)
    {
        $this->reseller = $reseller;
        $this->subscription = $subscription;
        $this->paymentLinkUrl = $paymentLinkUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        // Get reseller branding data
        $branding = ResellerEmailService::getResellerBranding(null, $this->reseller);
        
        $package = $this->subscription->package;
        $amount = $this->subscription->custom_amount ?? $package->price;
        $billingInterval = $this->subscription->metadata['billing_interval'] ?? 'monthly';
        
        return (new MailMessage)
            ->subject("Complete Your Subscription Payment - {$branding['app_name']} 💳")
            ->view('emails.reseller-payment-link', [
                'reseller' => $this->reseller,
                'subscription' => $this->subscription,
                'package' => $package,
                'amount' => $amount,
                'billingInterval' => $billingInterval,
                'paymentLinkUrl' => $this->paymentLinkUrl,
                'branding' => $branding,
                'headerTitle' => 'Complete Your Subscription Payment',
                'headerSubtitle' => 'Secure Payment Required',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reseller_id' => $this->reseller->id,
            'subscription_id' => $this->subscription->id,
            'payment_link_url' => $this->paymentLinkUrl,
        ];
    }
}
