<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\UserSubscription;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Services\ResellerEmailService;

class SubscriptionInvoice extends Notification
{
    use Queueable;

    protected UserSubscription $subscription;
    protected Transaction $transaction;
    protected array $invoiceData;

    /**
     * Create a new notification instance.
     */
    public function __construct(UserSubscription $subscription, Transaction $transaction, array $invoiceData = [])
    {
        $this->subscription = $subscription;
        $this->transaction = $transaction;
        $this->invoiceData = $invoiceData;
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
    public function toMail(object $notifiable): MailMessage
    {
        // Get reseller branding data
        $branding = ResellerEmailService::getResellerBranding($notifiable);
        
        $package = $this->subscription->package;
        $amount = $this->transaction->amount;
        $invoiceNumber = $this->invoiceData['id'] ?? $this->transaction->external_transaction_id ?? 'INV-' . $this->transaction->id;
        $invoiceDate = Carbon::parse($this->transaction->created_at)->format('F j, Y');
        $dueDate = Carbon::parse($this->transaction->created_at)->format('F j, Y');
        $periodStart = $this->subscription->current_period_start ? Carbon::parse($this->subscription->current_period_start)->format('F j, Y') : $invoiceDate;
        $periodEnd = $this->subscription->current_period_end ? Carbon::parse($this->subscription->current_period_end)->format('F j, Y') : Carbon::parse($this->transaction->created_at)->addMonth()->format('F j, Y');

        return (new MailMessage)
            ->subject('Invoice for ' . $package->name . ' Subscription - ' . $invoiceNumber)
            ->view('emails.subscription-invoice', [
                'user' => $notifiable,
                'package' => $package,
                'amount' => $amount,
                'invoiceNumber' => $invoiceNumber,
                'invoiceDate' => $invoiceDate,
                'dueDate' => $dueDate,
                'periodStart' => $periodStart,
                'periodEnd' => $periodEnd,
                'branding' => $branding,
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
            'subscription_id' => $this->subscription->id,
            'transaction_id' => $this->transaction->id,
            'invoice_number' => $this->invoiceData['id'] ?? $this->transaction->external_transaction_id ?? 'INV-' . $this->transaction->id,
            'amount' => $this->transaction->amount,
            'package_name' => $this->subscription->package->name,
        ];
    }
} 