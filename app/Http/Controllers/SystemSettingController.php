<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\ResellerSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    /**
     * Get reseller-specific setting with fallback to system setting
     */
    private function getResellerSetting($key, $default = null, $resellerId = null)
    {
        $resellerId = $resellerId ?? config('reseller.id');
        
        if ($resellerId) {
            // Try to get from reseller settings first
            $resellerValue = ResellerSetting::getValue($resellerId, $key);
            if ($resellerValue !== null) {
                return $resellerValue;
            }
        }
        
        // Fallback to system settings
        return SystemSetting::getValue($key, $default);
    }
    
    /**
     * Set reseller-specific setting
     */
    private function setResellerSetting($key, $value, $label = null, $type = 'text', $group = 'general', $description = null, $resellerId = null)
    {
        $resellerId = $resellerId ?? config('reseller.id');
        
        if ($resellerId) {
            return ResellerSetting::setValue($resellerId, $key, $value, $label, $type, $group, $description);
        }
        
        // Fallback to system settings
        return SystemSetting::setValue($key, $value, $type, $group, $description);
    }
    
    /**
     * Get all system settings (reseller-specific with fallback)
     */
    public function index(): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $resellerId = config('reseller.id');
        
        if ($resellerId) {
            // Get reseller-specific settings grouped by group
            $settings = ResellerSetting::getGroupedSettings($resellerId);
            
            // If no reseller settings exist, fallback to system settings
            if ($settings->isEmpty()) {
                $settings = SystemSetting::getGroupedSettings();
            }
        } else {
            // No reseller context, use system settings
            $settings = SystemSetting::getGroupedSettings();
        }

        return response()->json([
            'success' => true,
            'data' => $settings,
            'reseller_id' => $resellerId
        ]);
    }

    /**
     * Update system settings (reseller-specific)
     */
    public function update(Request $request): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'settings' => 'required|string', // JSON string
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB for banner
        ]);

        try {
            $resellerId = config('reseller.id');
            
            // Parse settings JSON
            $settings = json_decode($request->settings, true);
            
            foreach ($settings as $setting) {
                $key = $setting['key'];
                $value = $setting['value'] ?? null;
                $label = $setting['label'] ?? null;
                $type = $setting['type'] ?? 'text';
                $group = $setting['group'] ?? 'general';
                $description = $setting['description'] ?? null;
                
                $this->setResellerSetting($key, $value, $label, $type, $group, $description, $resellerId);
            }
            
            // Handle logo file upload
            if ($request->hasFile('logo_file')) {
                $file = $request->file('logo_file');
                $path = $file->store('system/logos', 'public');
                $url = asset('storage/' . $path);
                $this->setResellerSetting('logo_url', $url, 'Logo URL', 'url', 'branding', 'Company logo URL', $resellerId);
            }
            
            // Handle banner file upload
            if ($request->hasFile('banner_file')) {
                $file = $request->file('banner_file');
                $path = $file->store('system/banners', 'public');
                $url = asset('storage/' . $path);
                $this->setResellerSetting('homepage_banner', $url, 'Homepage Banner', 'url', 'branding', 'Homepage banner image URL', $resellerId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'reseller_id' => $resellerId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public system settings (reseller-specific with fallback)
     */
    public function getPublicSettings(): JsonResponse
    {
        $settings = [
            'site_title' => $this->getResellerSetting('site_title', 'XpartFone'),
            'site_tagline' => $this->getResellerSetting('site_tagline', 'Never Miss a call Again XpartFone answers 24x7! Voice AI Platform'),
            'meta_description' => $this->getResellerSetting('meta_description', 'Transform your business with cutting-edge voice AI technology'),
            'logo_url' => $this->getResellerSetting('logo_url'),
            'homepage_banner' => $this->getResellerSetting('homepage_banner'),
            'company_phone' => $this->getResellerSetting('company_phone', '(682) 582 8396'),
            'company_email' => $this->getResellerSetting('company_email', 'support@xpartfone.com'),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
            'reseller_id' => config('reseller.id')
        ]);
    }
    
    /**
     * Initialize default reseller settings from system settings
     */
    public function initializeDefaults(): JsonResponse
    {
        if (!Auth::user()->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $resellerId = config('reseller.id');
            
            if (!$resellerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reseller context available'
                ], 400);
            }
            
            // Default settings to initialize
            $defaultSettings = [
                [
                    'key' => 'site_title',
                    'value' => 'XpartFone',
                    'label' => 'Site Title',
                    'type' => 'text',
                    'group' => 'branding',
                    'description' => 'The main title of the website'
                ],
                [
                    'key' => 'site_tagline',
                    'value' => 'Never Miss a call Again XpartFone answers 24x7! Voice AI Platform',
                    'label' => 'Site Tagline',
                    'type' => 'text',
                    'group' => 'branding',
                    'description' => 'The tagline displayed on the homepage'
                ],
                [
                    'key' => 'meta_description',
                    'value' => 'Transform your business with cutting-edge voice AI technology',
                    'label' => 'Meta Description',
                    'type' => 'text',
                    'group' => 'seo',
                    'description' => 'SEO meta description for search engines'
                ],
                [
                    'key' => 'company_phone',
                    'value' => '(682) 582 8396',
                    'label' => 'Company Phone',
                    'type' => 'text',
                    'group' => 'contact',
                    'description' => 'Main company phone number'
                ],
                [
                    'key' => 'company_email',
                    'value' => 'support@xpartfone.com',
                    'label' => 'Company Email',
                    'type' => 'email',
                    'group' => 'contact',
                    'description' => 'Main company email address'
                ]
            ];
            
            // Initialize settings only if they don't exist
            foreach ($defaultSettings as $setting) {
                $existing = ResellerSetting::where('reseller_id', $resellerId)
                    ->where('key', $setting['key'])
                    ->first();
                    
                if (!$existing) {
                    ResellerSetting::setValue(
                        $resellerId,
                        $setting['key'],
                        $setting['value'],
                        $setting['label'],
                        $setting['type'],
                        $setting['group'],
                        $setting['description']
                    );
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Default settings initialized successfully',
                'reseller_id' => $resellerId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize default settings: ' . $e->getMessage()
            ], 500);
        }
    }
}
