<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\ResellerSetting;

class SaasPublicController extends Controller
{
    /**
     * Serve the reseller's logo based on domain
     * 
     * @param Request $request
     * @return Response
     */
    public function getLogo(Request $request): Response
    {
        try {
            // Get current reseller from the middleware (will be null if middleware not run)
            $reseller = app('currentReseller');
            $resellerSettings = app('currentResellerSettings');
            
            if (!$reseller) {
                return $this->serveDefaultLogo();
            }

            // Get logo URL from reseller settings or reseller model
            $logoUrl = null;
            if ($resellerSettings) {
                $logoUrl = ResellerSetting::getValue($reseller->id, 'logo_url', $reseller->logo_address);
            } else {
                $logoUrl = $reseller->logo_address;
            }

            // If no logo is configured, serve default
            if (!$logoUrl) {
                return $this->serveDefaultLogo();
            }

            // Handle different logo URL types
            if (filter_var($logoUrl, FILTER_VALIDATE_URL)) {
                // External URL - redirect or proxy
                return $this->proxyExternalLogo($logoUrl);
            } else {
                // Local file - serve from storage
                return $this->serveLocalLogo($logoUrl);
            }

        } catch (\Exception $e) {
            Log::error('Error serving reseller logo', [
                'error' => $e->getMessage(),
                'domain' => $request->getHost(),
                'reseller_id' => $reseller->id ?? null
            ]);
            
            return $this->serveDefaultLogo();
        }
    }

    /**
     * Serve default logo when reseller has no logo
     * 
     * @return Response
     */
    private function serveDefaultLogo(): Response
    {
        // Check if default logo exists in public directory
        $defaultLogoPath = public_path('logo.png');
        
        if (file_exists($defaultLogoPath)) {
            $content = file_get_contents($defaultLogoPath);
            $mimeType = 'image/png';
        } else {
            // Generate a simple placeholder SVG
            $content = $this->generatePlaceholderSvg();
            $mimeType = 'image/svg+xml';
        }

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=3600') // Cache for 1 hour
            ->header('ETag', md5($content));
    }

    /**
     * Proxy external logo URL with local caching
     * 
     * @param string $logoUrl
     * @return Response
     */
    private function proxyExternalLogo(string $logoUrl): Response
    {
        try {
            // Create cache key based on URL
            $cacheKey = 'logo_cache_' . md5($logoUrl);
            $cachePath = 'logos/cache/' . md5($logoUrl) . '.cache';
            
            // Check if we have cached content
            $cachedData = $this->getCachedLogo($cacheKey, $cachePath);
            if ($cachedData) {
                return response($cachedData['content'])
                    ->header('Content-Type', $cachedData['mime_type'])
                    ->header('Cache-Control', 'public, max-age=3600') // Longer cache for cached content
                    ->header('ETag', $cachedData['etag'])
                    ->header('X-Logo-Source', 'cached');
            }

            // Download and cache the logo
            $logoData = $this->downloadAndCacheLogo($logoUrl, $cacheKey, $cachePath);
            
            if (!$logoData) {
                return $this->serveDefaultLogo();
            }

            return response($logoData['content'])
                ->header('Content-Type', $logoData['mime_type'])
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('ETag', $logoData['etag'])
                ->header('X-Logo-Source', 'fresh');

        } catch (\Exception $e) {
            Log::warning('Failed to proxy external logo', [
                'logo_url' => $logoUrl,
                'error' => $e->getMessage()
            ]);
            
            return $this->serveDefaultLogo();
        }
    }

    /**
     * Get cached logo data
     * 
     * @param string $cacheKey
     * @param string $cachePath
     * @return array|null
     */
    private function getCachedLogo(string $cacheKey, string $cachePath): ?array
    {
        try {
            // Check if cached file exists and is not expired
            $metadataPath = str_replace('.cache', '.meta', $cachePath);
            $contentPath = str_replace('.cache', '.content', $cachePath);
            
            if (Storage::disk('public')->exists($metadataPath) && Storage::disk('public')->exists($contentPath)) {
                $metadata = json_decode(Storage::disk('public')->get($metadataPath), true);
                
                if ($metadata && isset($metadata['expires_at']) && $metadata['expires_at'] > time()) {
                    // Load the binary content
                    $content = Storage::disk('public')->get($contentPath);
                    
                    return [
                        'content' => $content,
                        'mime_type' => $metadata['mime_type'],
                        'etag' => $metadata['etag'],
                        'expires_at' => $metadata['expires_at']
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::debug('Cache retrieval failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Download and cache logo
     * 
     * @param string $logoUrl
     * @param string $cacheKey
     * @param string $cachePath
     * @return array|null
     */
    private function downloadAndCacheLogo(string $logoUrl, string $cacheKey, string $cachePath): ?array
    {
        try {
            // Use Laravel HTTP client with optimizations
            $response = Http::timeout(5) // Reduced timeout
                ->withHeaders([
                    'User-Agent' => 'SaaS-Logo-Proxy/2.0',
                    'Accept' => 'image/*',
                ])
                ->get($logoUrl);

            if (!$response->successful()) {
                Log::warning('External logo HTTP error', [
                    'url' => $logoUrl,
                    'status' => $response->status()
                ]);
                return null;
            }

            $content = $response->body();
            if (empty($content)) {
                return null;
            }

            // Validate it's actually an image and reasonable size
            if (strlen($content) > 5 * 1024 * 1024) { // Max 5MB
                Log::warning('Logo file too large', ['url' => $logoUrl, 'size' => strlen($content)]);
                return null;
            }

            $mimeType = $this->detectMimeType($logoUrl, $content);
            if (!str_starts_with($mimeType, 'image/')) {
                Log::warning('Invalid image type', ['url' => $logoUrl, 'mime' => $mimeType]);
                return null;
            }

            $etag = md5($content);
            $expiresAt = time() + 1800; // Cache for 30 minutes

            $logoData = [
                'content' => $content,
                'mime_type' => $mimeType,
                'etag' => $etag,
                'expires_at' => $expiresAt,
                'url' => $logoUrl,
                'cached_at' => time()
            ];

            // Store in both memory and file cache
            $this->storeCachedLogo($cacheKey, $cachePath, $logoData);

            return $logoData;

        } catch (\Exception $e) {
            Log::warning('Failed to download logo', [
                'url' => $logoUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Store logo in cache
     * 
     * @param string $cacheKey
     * @param string $cachePath
     * @param array $logoData
     * @return void
     */
    private function storeCachedLogo(string $cacheKey, string $cachePath, array $logoData): void
    {
        try {
            // Separate metadata and content
            $metadata = [
                'mime_type' => $logoData['mime_type'],
                'etag' => $logoData['etag'],
                'expires_at' => $logoData['expires_at'],
                'url' => $logoData['url'],
                'cached_at' => $logoData['cached_at']
            ];
            
            $metadataPath = str_replace('.cache', '.meta', $cachePath);
            $contentPath = str_replace('.cache', '.content', $cachePath);
            
            // Store metadata as JSON
            Storage::disk('public')->put($metadataPath, json_encode($metadata));
            
            // Store binary content separately
            Storage::disk('public')->put($contentPath, $logoData['content']);

        } catch (\Exception $e) {
            Log::debug('Failed to store logo cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Serve logo from local storage
     * 
     * @param string $logoPath
     * @return Response
     */
    private function serveLocalLogo(string $logoPath): Response
    {
        try {
            // Remove storage URL prefix if present  
            $cleanPath = str_replace(url('storage/'), '', $logoPath);
            
            if (Storage::disk('public')->exists($cleanPath)) {
                $content = Storage::disk('public')->get($cleanPath);
                $mimeType = $this->detectMimeTypeFromPath($cleanPath);
                
                return response($content)
                    ->header('Content-Type', $mimeType)
                    ->header('Cache-Control', 'public, max-age=3600') // Cache for 1 hour
                    ->header('ETag', md5($content));
            } else {
                Log::warning('Local logo file not found', [
                    'logo_path' => $logoPath,
                    'clean_path' => $cleanPath
                ]);
                
                return $this->serveDefaultLogo();
            }

        } catch (\Exception $e) {
            Log::warning('Failed to serve local logo', [
                'logo_path' => $logoPath,
                'error' => $e->getMessage()
            ]);
            
            return $this->serveDefaultLogo();
        }
    }

    /**
     * Generate a placeholder SVG logo
     * 
     * @return string
     */
    private function generatePlaceholderSvg(): string
    {
        return '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="200" height="200" fill="#f3f4f6"/>
            <text x="100" y="100" font-family="Arial, sans-serif" font-size="16" text-anchor="middle" dy=".3em" fill="#6b7280">
                Logo
            </text>
        </svg>';
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

    /**
     * Detect MIME type from file path
     * 
     * @param string $path
     * @return string
     */
    private function detectMimeTypeFromPath(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp'
        ];

        return $mimeTypes[$extension] ?? 'image/png';
    }
}