<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reseller;
use App\Models\ResellerSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class WarmLogoCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logo:warm-cache {--force : Force refresh all cached logos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-warm logo cache for all resellers to improve first-load performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting logo cache warming...');
        
        $resellers = Reseller::where('status', 'active')->get();
        $processed = 0;
        $cached = 0;
        $errors = 0;

        foreach ($resellers as $reseller) {
            $this->info("Processing reseller: {$reseller->org_name} ({$reseller->domain})");
            
            // Get logo URL
            $logoUrl = ResellerSetting::getValue($reseller->id, 'logo_url', $reseller->logo_address);
            
            if (!$logoUrl) {
                $this->line("  No logo configured - skipping");
                continue;
            }

            if (!filter_var($logoUrl, FILTER_VALIDATE_URL)) {
                $this->line("  Local logo - skipping cache warming");
                continue;
            }

            $processed++;
            
            if ($this->cacheExternalLogo($logoUrl, $reseller)) {
                $cached++;
                $this->info("  ✓ Logo cached successfully");
            } else {
                $errors++;
                $this->error("  ✗ Failed to cache logo");
            }
        }

        $this->newLine();
        $this->info("Logo cache warming completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Resellers processed', $processed],
                ['Logos cached', $cached],
                ['Errors', $errors],
            ]
        );
    }

    /**
     * Cache external logo
     * 
     * @param string $logoUrl
     * @param Reseller $reseller
     * @return bool
     */
    private function cacheExternalLogo(string $logoUrl, Reseller $reseller): bool
    {
        try {
            $cacheKey = 'logo_cache_' . md5($logoUrl);
            $cachePath = 'logos/cache/' . md5($logoUrl) . '.cache';
            
            // Check if already cached and not forcing refresh
            if (!$this->option('force')) {
                $metadataPath = str_replace('.cache', '.meta', $cachePath);
                $contentPath = str_replace('.cache', '.content', $cachePath);
                
                if (Storage::disk('public')->exists($metadataPath) && Storage::disk('public')->exists($contentPath)) {
                    $metadata = json_decode(Storage::disk('public')->get($metadataPath), true);
                    if ($metadata && isset($metadata['expires_at']) && $metadata['expires_at'] > time()) {
                        $this->line("  Already cached - skipping");
                        return true;
                    }
                }
            }

            // Download logo
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'SaaS-Logo-Cache-Warmer/1.0',
                    'Accept' => 'image/*',
                ])
                ->get($logoUrl);

            if (!$response->successful()) {
                $this->error("  HTTP error: " . $response->status());
                return false;
            }

            $content = $response->body();
            if (empty($content)) {
                $this->error("  Empty response");
                return false;
            }

            // Validate size
            if (strlen($content) > 5 * 1024 * 1024) {
                $this->error("  Logo too large: " . strlen($content) . " bytes");
                return false;
            }

            // Detect MIME type
            $mimeType = $this->detectMimeType($logoUrl, $content);
            if (!str_starts_with($mimeType, 'image/')) {
                $this->error("  Invalid image type: " . $mimeType);
                return false;
            }

            $etag = md5($content);
            $expiresAt = time() + 3600; // Cache for 1 hour

            $logoData = [
                'content' => $content,
                'mime_type' => $mimeType,
                'etag' => $etag,
                'expires_at' => $expiresAt,
                'url' => $logoUrl,
                'cached_at' => time(),
                'reseller_id' => $reseller->id,
                'reseller_domain' => $reseller->domain
            ];

            // Store cache using the same method as the controller
            $metadata = [
                'mime_type' => $logoData['mime_type'],
                'etag' => $logoData['etag'],
                'expires_at' => $logoData['expires_at'],
                'url' => $logoData['url'],
                'cached_at' => $logoData['cached_at'],
                'reseller_id' => $logoData['reseller_id'],
                'reseller_domain' => $logoData['reseller_domain']
            ];
            
            $metadataPath = str_replace('.cache', '.meta', $cachePath);
            $contentPath = str_replace('.cache', '.content', $cachePath);
            
            // Store metadata as JSON
            Storage::disk('public')->put($metadataPath, json_encode($metadata));
            
            // Store binary content separately
            Storage::disk('public')->put($contentPath, $logoData['content']);

            $this->line("  Size: " . number_format(strlen($content)) . " bytes, Type: " . $mimeType);
            
            return true;

        } catch (\Exception $e) {
            $this->error("  Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Detect MIME type from URL and content
     * 
     * @param string $url
     * @param string $content
     * @return string
     */
    private function detectMimeType(string $url, string $content): string
    {
        // Try to detect from file extension
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp'
        ];

        if (isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }

        // Try to detect from content
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedType = $finfo->buffer($content);
        
        return $detectedType ?: 'image/png';
    }
}
