<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\UserSubscription;
use App\Models\Transaction;
use App\Services\ResellerEmailService;

class SubscriptionUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserSubscription $subscription;
    protected Transaction $transaction;
    protected string $updateType;
    protected array $updateData;

    public function __construct(UserSubscription $subscription, Transaction $transaction, string $updateType, array $updateData = [])
    {
        $this->subscription = $subscription;
        $this->transaction = $transaction;
        $this->updateType = $updateType;
        $this->updateData = $updateData;
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
        $invoiceDate = \Carbon\Carbon::parse($this->transaction->created_at)->format('F j, Y');
        $periodStart = $this->subscription->current_period_start ? \Carbon\Carbon::parse($this->subscription->current_period_start)->format('F j, Y') : $invoiceDate;
        $periodEnd = $this->subscription->current_period_end ? \Carbon\Carbon::parse($this->subscription->current_period_end)->format('F j, Y') : \Carbon\Carbon::parse($this->transaction->created_at)->addMonth()->format('F j, Y');

        $subject = match($this->updateType) {
            'plan_changed' => "Subscription Plan Updated - {$branding['app_name']} 📈",
            'billing_updated' => "Billing Information Updated - {$branding['app_name']} 💳",
            'renewed' => "Subscription Renewed - {$branding['app_name']} 🔄",
            default => "Subscription Updated - {$branding['app_name']} 📝"
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.subscription-updated', [
                'user' => $notifiable,
                'subscription' => $this->subscription,
                'transaction' => $this->transaction,
                'package' => $package,
                'amount' => $amount,
                'invoiceNumber' => $invoiceNumber,
                'invoiceDate' => $invoiceDate,
                'periodStart' => $periodStart,
                'periodEnd' => $periodEnd,
                'updateType' => $this->updateType,
                'updateData' => $this->updateData,
                'branding' => $branding,
                'headerTitle' => $this->getHeaderTitle(),
                'headerSubtitle' => 'Subscription Update Notification',
            ]);
    }

    private function getHeaderTitle(): string
    {
        return match($this->updateType) {
            'plan_changed' => 'Subscription Plan Updated',
            'billing_updated' => 'Billing Information Updated',
            'renewed' => 'Subscription Renewed',
            default => 'Subscription Updated'
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'transaction_id' => $this->transaction->id,
            'update_type' => $this->updateType,
            'update_data' => $this->updateData,
        ];
    }
}
