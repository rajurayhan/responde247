<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ClearLogoCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logo:clear-cache {--expired-only : Only clear expired cache entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear logo cache to free up storage space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing logo cache...');
        
        $cacheDir = 'logos/cache/';
        $files = Storage::disk('public')->files($cacheDir);
        
        $cleared = 0;
        $kept = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            if (!str_ends_with($file, '.cache')) {
                continue;
            }

            try {
                $cacheData = json_decode(Storage::disk('public')->get($file), true);
                $fileSize = Storage::disk('public')->size($file);
                $totalSize += $fileSize;
                
                if ($this->option('expired-only')) {
                    // Only clear expired entries
                    if ($cacheData && isset($cacheData['expires_at']) && $cacheData['expires_at'] > time()) {
                        $kept++;
                        $this->line("  Keeping: " . basename($file) . " (expires: " . date('Y-m-d H:i:s', $cacheData['expires_at']) . ")");
                        continue;
                    }
                }

                // Clear the file
                Storage::disk('public')->delete($file);
                
                // Clear from memory cache if we have the URL
                if ($cacheData && isset($cacheData['url'])) {
                    $cacheKey = 'logo_cache_' . md5($cacheData['url']);
                    Cache::forget($cacheKey);
                }
                
                $cleared++;
                $this->line("  Cleared: " . basename($file) . " (" . number_format($fileSize) . " bytes)");
                
            } catch (\Exception $e) {
                $this->error("  Error processing " . basename($file) . ": " . $e->getMessage());
            }
        }

        // Clear all logo-related memory cache if not expired-only
        if (!$this->option('expired-only')) {
            $this->info('Clearing memory cache...');
            // Note: Laravel doesn't have a way to clear cache by pattern, so we'd need Redis for this
            // For now, we'll just clear the specific keys we know about
        }

        $this->newLine();
        $this->info("Logo cache clearing completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files cleared', $cleared],
                ['Files kept', $kept],
                ['Total size processed', number_format($totalSize) . ' bytes'],
                ['Space freed', number_format($totalSize - ($kept * 1000)) . ' bytes (approx)'],
            ]
        );
    }
}
