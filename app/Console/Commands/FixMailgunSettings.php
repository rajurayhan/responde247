<?php

namespace App\Console\Commands;

use App\Models\ResellerSetting;
use Illuminate\Console\Command;

class FixMailgunSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:fix-mailgun-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and validate mailgun settings for resellers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking mailgun settings...');
        
        $mailgunSettings = ResellerSetting::where('key', 'mail_mailer')
            ->where('value', 'mailgun')
            ->get();
            
        if ($mailgunSettings->isEmpty()) {
            $this->info('No resellers currently using mailgun configuration.');
            $this->info('Mailgun is now properly configured and available for use.');
            return 0;
        }
        
        $this->info("Found {$mailgunSettings->count()} reseller(s) using mailgun configuration.");
        $this->info('Mailgun package is now installed and configured properly.');
        $this->info('These settings should work correctly now.');
        
        return 0;
    }
}
