<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\ResellerSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ResellerEmailService
{
    /**
     * Get reseller branding data for emails
     */
    public static function getResellerBranding(?User $user = null, ?Reseller $reseller = null): array
    {
        // Get reseller from user relationship or provided reseller
        if (!$reseller && $user) {
            // Try to load the reseller relationship if not already loaded
            if (!$user->relationLoaded('reseller')) {
                $user->load('reseller');
            }
            $reseller = $user->reseller;
        }
        
        // If still no reseller, try to get from current context
        if (!$reseller) {
            $reseller = app('currentReseller');
        }
        
        // Fallback to default if no reseller found
        if (!$reseller) {
            Log::warning('No reseller found for branding, using default', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'user_reseller_id' => $user?->reseller_id,
                'current_reseller_id' => config('reseller.id')
            ]);
            return self::getDefaultBranding();
        }
        
        $appName = ResellerSetting::getValue($reseller->id, 'app_name') ?: $reseller->org_name;
        
        // Debug: Log branding retrieval
        Log::info('ResellerEmailService branding data', [
            'reseller_id' => $reseller->id,
            'reseller_org_name' => $reseller->org_name,
            'app_name_setting' => ResellerSetting::getValue($reseller->id, 'app_name'),
            'final_app_name' => $appName,
            'domain' => $reseller->domain
        ]);
        
        return [
            'app_name' => $appName,
            'company_name' => $reseller->org_name,
            'company_email' => $reseller->company_email,
            'support_email' => ResellerSetting::getValue($reseller->id, 'support_email') ?: $reseller->company_email,
            'logo_url' => self::getLogoUrl($reseller),
            'primary_color' => ResellerSetting::getValue($reseller->id, 'primary_color') ?: '#667eea',
            'website_url' => 'https://' . $reseller->domain,
            'domain' => $reseller->domain,
            'footer_text' => ResellerSetting::getValue($reseller->id, 'footer_text') ?: '© ' . date('Y') . ' ' . $reseller->org_name . '. All rights reserved.',
        ];
    }
    
    /**
     * Get reseller branding from current request context
     * Uses the reseller middleware data if available
     */
    public static function getCurrentResellerBranding(): array
    {
        // Try to get from current reseller instance (set by middleware)
        $reseller = app('currentReseller');
        
        if ($reseller) {
            return self::getResellerBranding(null, $reseller);
        }
        
        // Fallback to default
        return self::getDefaultBranding();
    }
    
    /**
     * Get logo URL for reseller
     */
    private static function getLogoUrl(Reseller $reseller): string
    {
        $logoUrl = ResellerSetting::getValue($reseller->id, 'logo_url', $reseller->logo_address);
        
        // If it's a local storage path, convert to full URL
        if ($logoUrl && !str_starts_with($logoUrl, 'http')) {
            return 'https://' . $reseller->domain . '/api/saas-public/logo.png';
        }
        
        return $logoUrl ?: 'https://' . $reseller->domain . '/api/saas-public/logo.png';
    }
    
    /**
     * Get default branding when no reseller is available
     */
    private static function getDefaultBranding(): array
    {
        return [
            'app_name' => 'AI Phone System',
            'company_name' => 'AI Phone System',
            'company_email' => 'support@example.com',
            'support_email' => 'support@example.com',
            'logo_url' => '/api/saas-public/logo.png',
            'primary_color' => '#667eea',
            'website_url' => config('app.url'),
            'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost',
            'footer_text' => '© ' . date('Y') . ' AI Phone System. All rights reserved.',
        ];
    }
}

