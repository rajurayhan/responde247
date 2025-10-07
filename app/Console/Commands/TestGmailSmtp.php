<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestGmailSmtp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-gmail {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Gmail SMTP configuration by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address');
            return 1;
        }

        $this->info('Testing Gmail SMTP configuration...');
        $this->info('Current mail configuration:');
        $this->info('MAIL_MAILER: ' . config('mail.default'));
        $this->info('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->info('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->info('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->info('MAIL_FROM_ADDRESS: ' . config('mail.from.address'));
        
        try {
            Mail::raw('This is a test email from your Laravel application to verify Gmail SMTP configuration.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Gmail SMTP Test - ' . now()->format('Y-m-d H:i:s'));
            });
            
            $this->info('✅ Test email sent successfully to: ' . $email);
            $this->info('Check your inbox (and spam folder) for the test email.');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email:');
            $this->error($e->getMessage());
            
            // Log the full error for debugging
            Log::error('Gmail SMTP Test Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'config' => [
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'username' => config('mail.mailers.smtp.username'),
                ]
            ]);
            
            $this->warn('Common Gmail SMTP issues:');
            $this->warn('1. Make sure you\'re using an App Password (not your regular Gmail password)');
            $this->warn('2. Enable 2-Factor Authentication on your Gmail account');
            $this->warn('3. Generate App Password: Google Account > Security > App passwords');
            $this->warn('4. Use MAIL_ENCRYPTION=tls and MAIL_PORT=587');
            
            return 1;
        }
        
        return 0;
    }
}
