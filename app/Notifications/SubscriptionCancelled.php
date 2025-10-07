<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\UserSubscription;
use App\Services\ResellerEmailService;

class SubscriptionCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserSubscription $subscription;
    protected string $cancellationReason;
    protected ?\Carbon\Carbon $cancellationDate;

    public function __construct(UserSubscription $subscription, string $cancellationReason = 'user_request', ?\Carbon\Carbon $cancellationDate = null)
    {
        $this->subscription = $subscription;
        $this->cancellationReason = $cancellationReason;
        $this->cancellationDate = $cancellationDate ?? \Carbon\Carbon::now();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Get reseller branding data
        $branding = ResellerEmailService::getResellerBranding($notifiable);
        
        $package = $this->subscription->package;
        $effectiveDate = $this->subscription->current_period_end ? \Carbon\Carbon::parse($this->subscription->current_period_end)->format('F j, Y') : 'Immediately';
        
        return (new MailMessage)
            ->subject("Subscription Cancelled - {$branding['app_name']} 😢")
            ->view('emails.subscription-cancelled', [
                'user' => $notifiable,
                'subscription' => $this->subscription,
                'package' => $package,
                'cancellationReason' => $this->cancellationReason,
                'cancellationDate' => $this->cancellationDate,
                'effectiveDate' => $effectiveDate,
                'branding' => $branding,
                'headerTitle' => 'Subscription Cancelled',
                'headerSubtitle' => 'Your Service Access Update',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'cancellation_reason' => $this->cancellationReason,
            'cancellation_date' => $this->cancellationDate,
        ];
    }
}
