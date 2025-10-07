<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\ResellerSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class ResellerMailManager
{
    /**
     * Set mail configuration for a specific reseller
     */
    public static function setMailConfig(?Reseller $reseller = null): void
    {
        // If no reseller provided, try to get from current request
        if (!$reseller) {
            $reseller = app('currentReseller');
        }

        // If still no reseller, use default mail config
        if (!$reseller) {
            return;
        }

        // Check if reseller has custom mail config enabled
        $mailEnabled = ResellerSetting::getValue($reseller->id, 'mail_enabled');
        if (!$mailEnabled || $mailEnabled === 'false') {
            return;
        }

        // Get mail settings
        $mailMailer = ResellerSetting::getValue($reseller->id, 'mail_mailer');
        $mailHost = ResellerSetting::getValue($reseller->id, 'mail_host');
        $mailPort = ResellerSetting::getValue($reseller->id, 'mail_port');
        $mailUsername = ResellerSetting::getValue($reseller->id, 'mail_username');
        $mailPassword = ResellerSetting::getValue($reseller->id, 'mail_password');
        $mailEncryption = ResellerSetting::getValue($reseller->id, 'mail_encryption');
        $mailFromAddress = ResellerSetting::getValue($reseller->id, 'mail_from_address');
        $mailFromName = ResellerSetting::getValue($reseller->id, 'mail_from_name');

        // Validate required settings
        if (empty($mailHost) || empty($mailUsername) || empty($mailPassword)) {
            Log::warning('Incomplete mail configuration for reseller', [
                'reseller_id' => $reseller->id,
                'domain' => $reseller->domain
            ]);
            return;
        }

        // Set mail configuration at runtime
        Config::set('mail.default', $mailMailer);
        Config::set('mail.mailers.smtp.host', $mailHost);
        Config::set('mail.mailers.smtp.port', $mailPort);
        Config::set('mail.mailers.smtp.username', $mailUsername);
        Config::set('mail.mailers.smtp.password', $mailPassword);
        Config::set('mail.mailers.smtp.encryption', $mailEncryption);
        Config::set('mail.from.address', $mailFromAddress ?: $reseller->company_email);
        Config::set('mail.from.name', $mailFromName ?: $reseller->org_name);

        Log::info('Using custom mail configuration for reseller', [
            'reseller_id' => $reseller->id,
            'domain' => $reseller->domain,
            'mail_host' => $mailHost
        ]);
    }

    /**
     * Test mail configuration for a reseller
     */
    public static function testMailConfig(Reseller $reseller): array
    {
        try {
            // Backup current configuration
            $defaultMailer = Config::get('mail.default');
            $defaultHost = Config::get('mail.mailers.smtp.host');
            $defaultPort = Config::get('mail.mailers.smtp.port');
            $defaultUsername = Config::get('mail.mailers.smtp.username');
            $defaultPassword = Config::get('mail.mailers.smtp.password');
            $defaultEncryption = Config::get('mail.mailers.smtp.encryption');
            $defaultFromAddress = Config::get('mail.from.address');
            $defaultFromName = Config::get('mail.from.name');

            // Set reseller configuration
            self::setMailConfig($reseller);

            // Get current configuration values
            $host = Config::get('mail.mailers.smtp.host');
            $port = Config::get('mail.mailers.smtp.port');
            $username = Config::get('mail.mailers.smtp.username');
            $password = Config::get('mail.mailers.smtp.password');
            $encryption = Config::get('mail.mailers.smtp.encryption');

            // Create a test transport using Symfony Mailer
            // Remove spaces from password and URL encode special characters
            $cleanPassword = str_replace(' ', '', $password);
            $encodedPassword = urlencode($cleanPassword);
            $dsn = "smtp://{$username}:{$encodedPassword}@{$host}:{$port}";
            if ($encryption) {
                $dsn .= "?encryption={$encryption}";
            }

            $transport = \Symfony\Component\Mailer\Transport::fromDsn($dsn);
            
            // Try to send a test email (this will test the connection)
            $mailer = new \Symfony\Component\Mailer\Mailer($transport);
            
            $email = (new \Symfony\Component\Mime\Email())
                ->from(Config::get('mail.from.address'))
                ->to(Config::get('mail.from.address')) // Send to self for testing
                ->subject('Mail Configuration Test')
                ->text('This is a test email to verify mail configuration.')
                ->html('<p>This is a test email to verify mail configuration.</p>');
            
            $mailer->send($email);
            
            // Restore original configuration
            Config::set('mail.default', $defaultMailer);
            Config::set('mail.mailers.smtp.host', $defaultHost);
            Config::set('mail.mailers.smtp.port', $defaultPort);
            Config::set('mail.mailers.smtp.username', $defaultUsername);
            Config::set('mail.mailers.smtp.password', $defaultPassword);
            Config::set('mail.mailers.smtp.encryption', $defaultEncryption);
            Config::set('mail.from.address', $defaultFromAddress);
            Config::set('mail.from.name', $defaultFromName);
            
            return [
                'success' => true,
                'message' => 'Mail configuration is valid and test email sent successfully'
            ];
        } catch (\Exception $e) {
            // Restore original configuration
            Config::set('mail.default', $defaultMailer ?? 'smtp');
            Config::set('mail.mailers.smtp.host', $defaultHost ?? '');
            Config::set('mail.mailers.smtp.port', $defaultPort ?? '587');
            Config::set('mail.mailers.smtp.username', $defaultUsername ?? '');
            Config::set('mail.mailers.smtp.password', $defaultPassword ?? '');
            Config::set('mail.mailers.smtp.encryption', $defaultEncryption ?? 'tls');
            Config::set('mail.from.address', $defaultFromAddress ?? '');
            Config::set('mail.from.name', $defaultFromName ?? '');
            
            Log::error('Mail configuration test failed', [
                'reseller_id' => $reseller->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Mail configuration test failed: ' . $e->getMessage()
            ];
        }
    }
}
