<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\UserSubscription;
use App\Models\Transaction;
use App\Services\ResellerEmailService;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserSubscription $subscription;
    protected Transaction $transaction;
    protected string $failureReason;
    protected int $attemptCount;

    public function __construct(UserSubscription $subscription, Transaction $transaction, string $failureReason = 'payment_declined', int $attemptCount = 1)
    {
        $this->subscription = $subscription;
        $this->transaction = $transaction;
        $this->failureReason = $failureReason;
        $this->attemptCount = $attemptCount;
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
        $amount = $this->transaction->amount;
        $invoiceNumber = $this->transaction->external_transaction_id ?? 'INV-' . $this->transaction->id;
        $failureDate = \Carbon\Carbon::parse($this->transaction->created_at)->format('F j, Y \a\t g:i A');
        $nextRetryDate = \Carbon\Carbon::now()->addDays(3)->format('F j, Y');
        
        return (new MailMessage)
            ->subject("Payment Failed - Action Required - {$branding['app_name']} ⚠️")
            ->view('emails.payment-failed', [
                'user' => $notifiable,
                'subscription' => $this->subscription,
                'transaction' => $this->transaction,
                'package' => $package,
                'amount' => $amount,
                'invoiceNumber' => $invoiceNumber,
                'failureReason' => $this->failureReason,
                'attemptCount' => $this->attemptCount,
                'failureDate' => $failureDate,
                'nextRetryDate' => $nextRetryDate,
                'branding' => $branding,
                'headerTitle' => 'Payment Failed',
                'headerSubtitle' => 'Action Required to Maintain Service',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'transaction_id' => $this->transaction->id,
            'failure_reason' => $this->failureReason,
            'attempt_count' => $this->attemptCount,
        ];
    }
}
