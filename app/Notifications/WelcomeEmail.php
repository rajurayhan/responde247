<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use App\Services\ResellerEmailService;

class WelcomeEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
        
        $timestamp = time();
        $hash = sha1($notifiable->email . $timestamp);
        $verificationUrl = $branding['website_url'] . '/api/verify-email/' . $hash . '?t=' . $timestamp;

        return (new MailMessage)
            ->subject("Welcome to {$branding['app_name']}! 🎉")
            ->view('emails.welcome', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl,
                'branding' => $branding,
                'headerTitle' => "Welcome to {$branding['app_name']}!",
                'headerSubtitle' => 'Your Voice AI Platform',
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
            //
        ];
    }
}
