<?php

namespace App\Console\Commands;

use App\Models\Reseller;
use App\Models\ResellerSetting;
use Illuminate\Console\Command;

class UpdateResellerMailSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reseller:update-mail-settings 
                            {--host= : SMTP host}
                            {--port= : SMTP port}
                            {--username= : SMTP username}
                            {--password= : SMTP password}
                            {--encryption= : SMTP encryption}
                            {--from-address= : From email address}
                            {--from-name= : From name}
                            {--enabled= : Enable/disable mail (true/false)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update reseller mail settings in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reseller = Reseller::where('org_name', 'Sulus.AI')->first();
        
        if (!$reseller) {
            $this->error('Sulus.AI reseller not found');
            return 1;
        }

        $this->info("Updating mail settings for reseller: {$reseller->org_name} (ID: {$reseller->id})");

        $settings = [
            'mail_enabled' => $this->option('enabled'),
            'mail_mailer' => 'smtp',
            'mail_host' => $this->option('host'),
            'mail_port' => $this->option('port'),
            'mail_username' => $this->option('username'),
            'mail_password' => $this->option('password'),
            'mail_encryption' => $this->option('encryption'),
            'mail_from_address' => $this->option('from-address'),
            'mail_from_name' => $this->option('from-name'),
        ];

        foreach ($settings as $key => $value) {
            if ($value !== null) {
                ResellerSetting::setValue($reseller->id, $key, $value);
                $this->info("Updated {$key}: " . ($key === 'mail_password' ? '***hidden***' : $value));
            }
        }

        $this->info('Mail settings updated successfully!');
        
        // Test the configuration
        $this->info('Testing mail configuration...');
        $result = \App\Services\ResellerMailManager::testMailConfig($reseller);
        
        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
        } else {
            $this->error('❌ ' . $result['message']);
        }

        return 0;
    }
}
