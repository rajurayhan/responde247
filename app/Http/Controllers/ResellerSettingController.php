<?php

namespace App\Http\Controllers;

use App\Models\ResellerSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\ResellerMailManager;

class ResellerSettingController extends Controller
{
    /**
     * Get all reseller settings
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $resellerId = config('reseller.id');

        // Check if user is reseller admin
        if (!$user->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only reseller admins can access settings.'
            ], 403);
        }

        $settings = ResellerSetting::getGroupedSettings($resellerId);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update reseller settings
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();
        $resellerId = config('reseller.id');

        // Check if user is reseller admin
        if (!$user->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only reseller admins can update settings.'
            ], 403);
        }

        $request->validate([
            'settings' => 'required|string', // JSON string
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048|dimensions:max_width=800,max_height=400',
            'banner_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120|dimensions:max_width=2000,max_height=800',
        ], [
            'logo_file.image' => 'Logo must be an image file.',
            'logo_file.mimes' => 'Logo must be a JPEG, PNG, JPG, GIF, or WebP file.',
            'logo_file.max' => 'Logo file size must not exceed 2MB.',
            'logo_file.dimensions' => 'Logo dimensions must not exceed 800x400 pixels.',
            'banner_file.image' => 'Banner must be an image file.',
            'banner_file.mimes' => 'Banner must be a JPEG, PNG, JPG, GIF, or WebP file.',
            'banner_file.max' => 'Banner file size must not exceed 5MB.',
            'banner_file.dimensions' => 'Banner dimensions must not exceed 2000x800 pixels.',
        ]);

        try {
            // Parse settings JSON
            $settings = json_decode($request->settings, true);
            
            if (!is_array($settings)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid settings format'
                ], 400);
            }
            
            foreach ($settings as $setting) {
                $key = $setting['key'];
                $value = $setting['value'] ?? null;
                $label = $setting['label'] ?? null;
                $type = $setting['type'] ?? 'text';
                $group = $setting['group'] ?? 'general';
                $description = $setting['description'] ?? null;
                
                ResellerSetting::setValue($resellerId, $key, $value, $label, $type, $group, $description);
            }
            
            // Handle logo file upload
            if ($request->hasFile('logo_file')) {
                $file = $request->file('logo_file');
                
                // Delete old logo if exists
                $oldLogoUrl = ResellerSetting::getValue($resellerId, 'logo_url');
                if ($oldLogoUrl) {
                    $this->deleteOldFile($oldLogoUrl);
                }
                
                // Generate unique filename
                $filename = 'logo_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('resellers/' . $resellerId . '/logos', $filename, 'public');
                $url = Storage::disk('public')->url($path);
                
                ResellerSetting::setValue($resellerId, 'logo_url', $url, 'Logo URL', 'url', 'branding', 'Company logo URL');
            }
            
            // Handle banner file upload
            if ($request->hasFile('banner_file')) {
                $file = $request->file('banner_file');
                
                // Delete old banner if exists
                $oldBannerUrl = ResellerSetting::getValue($resellerId, 'homepage_banner');
                if ($oldBannerUrl) {
                    $this->deleteOldFile($oldBannerUrl);
                }
                
                // Generate unique filename
                $filename = 'banner_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('resellers/' . $resellerId . '/banners', $filename, 'public');
                $url = Storage::disk('public')->url($path);
                
                ResellerSetting::setValue($resellerId, 'homepage_banner', $url, 'Homepage Banner', 'url', 'branding', 'Homepage banner image URL');
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public reseller settings (for frontend)
     */
    public function getPublicSettings(): JsonResponse
    {
        $resellerId = config('reseller.id');
        
        // Get reseller info
        $reseller = \App\Models\Reseller::find($resellerId);
        
        if (!$reseller) {
            return response()->json([
                'success' => false,
                'message' => 'Reseller not found'
            ], 404);
        }

        // Get public settings with defaults
        $settings = [
            'org_name' => $reseller->org_name,
            'company_email' => $reseller->company_email,
            'app_name' => ResellerSetting::getValue($resellerId, 'app_name', $reseller->org_name),
            'site_title' => ResellerSetting::getValue($resellerId, 'site_title', $reseller->org_name),
            'site_tagline' => ResellerSetting::getValue($resellerId, 'site_tagline', 'Professional Voice AI Solutions'),
            'meta_description' => ResellerSetting::getValue($resellerId, 'meta_description', 'Transform your business with cutting-edge voice AI technology'),
            'logo_url' => ResellerSetting::getValue($resellerId, 'logo_url', $reseller->logo_address),
            'homepage_banner' => ResellerSetting::getValue($resellerId, 'homepage_banner'),
            'company_phone' => ResellerSetting::getValue($resellerId, 'company_phone'),
            'company_address' => ResellerSetting::getValue($resellerId, 'company_address'),
            'support_email' => ResellerSetting::getValue($resellerId, 'support_email', $reseller->company_email),
            'primary_color' => ResellerSetting::getValue($resellerId, 'primary_color', '#3B82F6'),
            'secondary_color' => ResellerSetting::getValue($resellerId, 'secondary_color', '#1E40AF'),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Initialize default settings for a reseller
     */
    public function initializeDefaults(): JsonResponse
    {
        $user = Auth::user();
        $resellerId = config('reseller.id');

        // Check if user is reseller admin
        if (!$user->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only reseller admins can initialize settings.'
            ], 403);
        }

        try {
            $reseller = \App\Models\Reseller::find($resellerId);
            
            if (!$reseller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller not found'
                ], 404);
            }

            // Default settings
            $defaultSettings = [
                [
                    'key' => 'app_name',
                    'value' => $reseller->org_name,
                    'label' => 'App Name',
                    'type' => 'text',
                    'group' => 'general',
                    'description' => 'The name of your application/service'
                ],
                [
                    'key' => 'site_title',
                    'value' => $reseller->org_name,
                    'label' => 'Site Title',
                    'type' => 'text',
                    'group' => 'general',
                    'description' => 'The main title for your website'
                ],
                [
                    'key' => 'site_tagline',
                    'value' => 'Professional Voice AI Solutions',
                    'label' => 'Site Tagline',
                    'type' => 'text',
                    'group' => 'general',
                    'description' => 'A brief description or tagline for your site'
                ],
                [
                    'key' => 'meta_description',
                    'value' => 'Transform your business with cutting-edge voice AI technology',
                    'label' => 'Meta Description',
                    'type' => 'textarea',
                    'group' => 'seo',
                    'description' => 'Description for search engines'
                ],
                [
                    'key' => 'company_phone',
                    'value' => '',
                    'label' => 'Company Phone',
                    'type' => 'text',
                    'group' => 'contact',
                    'description' => 'Primary contact phone number'
                ],
                [
                    'key' => 'company_address',
                    'value' => '',
                    'label' => 'Company Address',
                    'type' => 'textarea',
                    'group' => 'contact',
                    'description' => 'Company physical address'
                ],
                [
                    'key' => 'support_email',
                    'value' => $reseller->company_email,
                    'label' => 'Support Email',
                    'type' => 'email',
                    'group' => 'contact',
                    'description' => 'Email for customer support'
                ],
                [
                    'key' => 'primary_color',
                    'value' => '#3B82F6',
                    'label' => 'Primary Color',
                    'type' => 'color',
                    'group' => 'branding',
                    'description' => 'Primary brand color (hex code)'
                ],
                [
                    'key' => 'secondary_color',
                    'value' => '#1E40AF',
                    'label' => 'Secondary Color',
                    'type' => 'color',
                    'group' => 'branding',
                    'description' => 'Secondary brand color (hex code)'
                ],
                // Mail configuration settings
                [
                    'key' => 'mail_enabled',
                    'value' => 'false',
                    'label' => 'Use Custom Mail Configuration',
                    'type' => 'boolean',
                    'group' => 'mail',
                    'description' => 'Enable custom mail server configuration'
                ],
                [
                    'key' => 'mail_mailer',
                    'value' => 'smtp',
                    'label' => 'Mail Driver',
                    'type' => 'select',
                    'group' => 'mail',
                    'description' => 'Mail driver (smtp, sendmail, etc.)'
                ],
                [
                    'key' => 'mail_host',
                    'value' => '',
                    'label' => 'SMTP Host',
                    'type' => 'text',
                    'group' => 'mail',
                    'description' => 'SMTP server hostname'
                ],
                [
                    'key' => 'mail_port',
                    'value' => '587',
                    'label' => 'SMTP Port',
                    'type' => 'text',
                    'group' => 'mail',
                    'description' => 'SMTP server port'
                ],
                [
                    'key' => 'mail_username',
                    'value' => '',
                    'label' => 'SMTP Username',
                    'type' => 'text',
                    'group' => 'mail',
                    'description' => 'SMTP authentication username'
                ],
                [
                    'key' => 'mail_password',
                    'value' => '',
                    'label' => 'SMTP Password',
                    'type' => 'password',
                    'group' => 'mail',
                    'description' => 'SMTP authentication password'
                ],
                [
                    'key' => 'mail_encryption',
                    'value' => 'tls',
                    'label' => 'Encryption',
                    'type' => 'select',
                    'group' => 'mail',
                    'description' => 'Type of encryption (tls, ssl)'
                ],
                [
                    'key' => 'mail_from_address',
                    'value' => '',
                    'label' => 'From Address',
                    'type' => 'email',
                    'group' => 'mail',
                    'description' => 'Default email address to send from'
                ],
                [
                    'key' => 'mail_from_name',
                    'value' => $reseller->org_name,
                    'label' => 'From Name',
                    'type' => 'text',
                    'group' => 'mail',
                    'description' => 'Default sender name'
                ],
            ];

            ResellerSetting::bulkUpdate($resellerId, $defaultSettings);

            return response()->json([
                'success' => true,
                'message' => 'Default settings initialized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific setting value
     */
    public function getSetting(string $key): JsonResponse
    {
        $resellerId = config('reseller.id');
        $value = ResellerSetting::getValue($resellerId, $key);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $key,
                'value' => $value
            ]
        ]);
    }

    /**
     * Set a specific setting value
     */
    public function setSetting(Request $request, string $key): JsonResponse
    {
        $user = Auth::user();
        $resellerId = config('reseller.id');

        // Check if user is reseller admin
        if (!$user->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only reseller admins can update settings.'
            ], 403);
        }

        $request->validate([
            'value' => 'required',
            'label' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'group' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            ResellerSetting::setValue(
                $resellerId,
                $key,
                $request->value,
                $request->label,
                $request->type ?? 'text',
                $request->group ?? 'general',
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test mail configuration for reseller
     */
    public function testMailConfig(Request $request): JsonResponse
    {
        $user = Auth::user();
        $resellerId = config('reseller.id');

        // Check if user is reseller admin
        if (!$user->isContentAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only reseller admins can test mail configuration.'
            ], 403);
        }

        $reseller = \App\Models\Reseller::find($resellerId);
        if (!$reseller) {
            return response()->json([
                'success' => false,
                'message' => 'Reseller not found'
            ], 404);
        }

        // Test mail configuration
        $result = ResellerMailManager::testMailConfig($reseller);

        return response()->json($result);
    }

    /**
     * Delete old file from storage
     */
    private function deleteOldFile($url)
    {
        try {
            if ($url && Storage::disk('public')->exists($this->getFilePathFromUrl($url))) {
                Storage::disk('public')->delete($this->getFilePathFromUrl($url));
            }
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            \Log::warning('Failed to delete old file: ' . $e->getMessage(), ['url' => $url]);
        }
    }

    /**
     * Extract file path from storage URL
     */
    private function getFilePathFromUrl($url)
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        // Remove '/storage/' prefix if present
        if (strpos($path, '/storage/') === 0) {
            $path = substr($path, 9); // Remove '/storage/' (9 characters)
        }
        
        return $path;
    }
}