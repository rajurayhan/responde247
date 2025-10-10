<?php

namespace App\Console\Commands;

use App\Models\Reseller;
use App\Models\ResellerSetting;
use App\Services\ResellerMailManager;
use Illuminate\Console\Command;

class DiagnoseMailConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:diagnose {--fix : Fix problematic configurations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose and optionally fix mail configuration issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Diagnosing mail configuration issues...');
        
        // Check global mail configuration
        $this->checkGlobalMailConfig();
        
        // Check reseller mail configurations
        $this->checkResellerMailConfigs();
        
        // Check for problematic resellers
        $this->checkProblematicResellers();
        
        $this->info('✅ Diagnosis complete!');
    }
    
    private function checkGlobalMailConfig()
    {
        $this->info("\n📧 Global Mail Configuration:");
        
        $defaultMailer = config('mail.default');
        $this->line("Default mailer: {$defaultMailer}");
        
        $mailgunDomain = config('services.mailgun.domain');
        $mailgunSecret = config('services.mailgun.secret');
        
        $this->line("Mailgun domain: " . ($mailgunDomain ?: 'NOT SET'));
        $this->line("Mailgun secret: " . ($mailgunSecret ? 'SET' : 'NOT SET'));
        
        if ($defaultMailer === 'mailgun' && (!$mailgunDomain || !$mailgunSecret)) {
            $this->error("❌ Default mailer is set to mailgun but credentials are missing!");
        } else {
            $this->info("✅ Global mail configuration looks good");
        }
    }
    
    private function checkResellerMailConfigs()
    {
        $this->info("\n🏢 Reseller Mail Configurations:");
        
        $mailEnabledResellers = ResellerSetting::where('key', 'mail_enabled')
            ->where('value', 'true')
            ->get();
            
        if ($mailEnabledResellers->isEmpty()) {
            $this->info("✅ No resellers have custom mail enabled");
            return;
        }
        
        $this->line("Found {$mailEnabledResellers->count()} resellers with mail enabled:");
        
        foreach ($mailEnabledResellers as $setting) {
            $reseller = Reseller::find($setting->reseller_id);
            if (!$reseller) {
                $this->error("❌ Reseller {$setting->reseller_id} not found!");
                continue;
            }
            
            $mailer = ResellerSetting::getValue($reseller->id, 'mail_mailer');
            $this->line("- {$reseller->org_name} ({$reseller->domain}): {$mailer}");
            
            if ($mailer === 'mailgun') {
                $mailgunDomain = config('services.mailgun.domain');
                $mailgunSecret = config('services.mailgun.secret');
                
                if (!$mailgunDomain || !$mailgunSecret) {
                    $this->error("  ❌ Mailgun configured but credentials missing!");
                    
                    if ($this->option('fix')) {
                        ResellerSetting::setValue($reseller->id, 'mail_mailer', 'smtp');
                        $this->info("  ✅ Fixed: Changed to SMTP");
                    }
                } else {
                    $this->info("  ✅ Mailgun properly configured");
                }
            }
        }
    }
    
    private function checkProblematicResellers()
    {
        $this->info("\n🚨 Checking for problematic resellers:");
        
        // Check for resellers that don't exist but are referenced
        $allResellerIds = ResellerSetting::distinct()->pluck('reseller_id');
        $existingResellerIds = Reseller::pluck('id');
        $missingResellers = $allResellerIds->diff($existingResellerIds);
        
        if ($missingResellers->isNotEmpty()) {
            $this->error("❌ Found settings for non-existent resellers:");
            foreach ($missingResellers as $resellerId) {
                $this->line("- {$resellerId}");
                
                if ($this->option('fix')) {
                    ResellerSetting::where('reseller_id', $resellerId)->delete();
                    $this->info("  ✅ Cleaned up orphaned settings");
                }
            }
        } else {
            $this->info("✅ All reseller settings reference existing resellers");
        }
    }
}
