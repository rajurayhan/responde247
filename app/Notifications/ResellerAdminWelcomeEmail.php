<?php

namespace App\Notifications;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\ResellerEmailService;

class ResellerAdminWelcomeEmail extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reseller;
    protected $temporaryPassword;
    protected $loginUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reseller $reseller, string $temporaryPassword)
    {
        $this->reseller = $reseller;
        $this->temporaryPassword = $temporaryPassword;
        $this->loginUrl = config('app.url') . '/login';
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
        $branding = ResellerEmailService::getResellerBranding($notifiable, $this->reseller);
        
        return (new MailMessage)
            ->subject("Welcome to {$branding['app_name']} - Your Admin Account 🎉")
            ->view('emails.reseller-admin-welcome', [
                'user' => $notifiable,
                'reseller' => $this->reseller,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => $branding['website_url'] . '/login',
                'branding' => $branding,
                'headerTitle' => 'Welcome to Your Admin Account',
                'headerSubtitle' => 'Reseller Administrator Access',
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
            'reseller_name' => $this->reseller->org_name,
            'admin_email' => $notifiable->email,
            'login_url' => $this->loginUrl,
        ];
    }
}
