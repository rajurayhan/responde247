<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StripeService;

class CleanupStripePaymentMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:cleanup-payment-methods {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up invalid PaymentMethod references in transactions';

    /**
     * Execute the console command.
     */
    public function handle(StripeService $stripeService)
    {
        $this->info('Starting PaymentMethod cleanup...');
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        try {
            $results = $stripeService->cleanupInvalidPaymentMethods();
            
            $this->info("Cleanup completed:");
            $this->info("- Checked: {$results['checked']} transactions");
            $this->info("- Cleaned: {$results['cleaned']} invalid references");
            
            if (!empty($results['errors'])) {
                $this->error("Errors encountered:");
                foreach ($results['errors'] as $error) {
                    $this->error("- " . json_encode($error));
                }
            }
            
            if ($results['cleaned'] > 0) {
                $this->info("✅ Successfully cleaned up {$results['cleaned']} invalid PaymentMethod references");
            } else {
                $this->info("✅ No invalid PaymentMethod references found");
            }
            
        } catch (\Exception $e) {
            $this->error("Cleanup failed: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
