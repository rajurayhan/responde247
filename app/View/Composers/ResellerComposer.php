<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\ResellerSetting;

class ResellerComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Get reseller data from the middleware (will be null if middleware not run)
        $reseller = app('currentReseller');
        $resellerSettings = app('currentResellerSettings');

        // Default values
        $resellerData = [
            'app_name' => 'AI Phone System',
            'slogan' => 'Never Miss a Call Again',
            'description' => 'AI-powered phone system that answers 24/7',
            'logo_url' => '/api/saas-public/logo.png',
            'primary_color' => '#4F46E5',
            'secondary_color' => '#10B981',
            'company_name' => 'AI Phone System',
            'company_email' => 'support@example.com',
            'company_phone' => '+1 (555) 123-4567',
            'company_address' => '',
            'domain' => request()->getHost(),
            'reseller_id' => null,
            'webhook_url' => route('core.webhook'),
        ];

        if ($reseller) {
            $resellerData['reseller_id'] = $reseller->id;
            $resellerData['domain'] = $reseller->domain;
            $resellerData['company_name'] = $reseller->org_name;
            $resellerData['company_email'] = $reseller->company_email;

            // Get settings from ResellerSetting
            if ($resellerSettings) {
                $resellerData = array_merge($resellerData, [
                    'app_name' => ResellerSetting::getValue($reseller->id, 'app_name', $resellerData['app_name']),
                    'slogan' => ResellerSetting::getValue($reseller->id, 'slogan', $resellerData['slogan']),
                    'description' => ResellerSetting::getValue($reseller->id, 'description', $resellerData['description']),
                    'primary_color' => ResellerSetting::getValue($reseller->id, 'primary_color', $resellerData['primary_color']),
                    'secondary_color' => ResellerSetting::getValue($reseller->id, 'secondary_color', $resellerData['secondary_color']),
                    'company_phone' => ResellerSetting::getValue($reseller->id, 'company_phone', $resellerData['company_phone']),
                    'company_address' => ResellerSetting::getValue($reseller->id, 'company_address', $resellerData['company_address']),
                    'logo_url' => $resellerData['logo_url'],
                    
                    // Additional branding settings
                    'favicon_url' => ResellerSetting::getValue($reseller->id, 'favicon_url', '/favicon.ico'),
                    'meta_title' => ResellerSetting::getValue($reseller->id, 'meta_title', $resellerData['app_name'] . ' - ' . $resellerData['slogan']),
                    'meta_description' => ResellerSetting::getValue($reseller->id, 'meta_description', $resellerData['description']),
                    'meta_keywords' => ResellerSetting::getValue($reseller->id, 'meta_keywords', 'ai, phone system, voice agent, automation'),
                    
                    // Contact and social settings
                    'website_url' => ResellerSetting::getValue($reseller->id, 'website_url', 'https://' . $reseller->domain),
                    'support_email' => ResellerSetting::getValue($reseller->id, 'support_email', $reseller->company_email),
                    'facebook_url' => ResellerSetting::getValue($reseller->id, 'facebook_url', ''),
                    'twitter_url' => ResellerSetting::getValue($reseller->id, 'twitter_url', ''),
                    'linkedin_url' => ResellerSetting::getValue($reseller->id, 'linkedin_url', ''),
                    
                    // Footer settings
                    'footer_text' => ResellerSetting::getValue($reseller->id, 'footer_text', '© ' . date('Y') . ' ' . $reseller->org_name . '. All rights reserved.'),
                    'privacy_policy_url' => ResellerSetting::getValue($reseller->id, 'privacy_policy_url', '/privacy'),
                    'terms_of_service_url' => ResellerSetting::getValue($reseller->id, 'terms_of_service_url', '/terms'),
                    
                    // Feature toggles
                    'show_demo_request' => ResellerSetting::getValue($reseller->id, 'show_demo_request', 'true') === 'true',
                    'show_contact_form' => ResellerSetting::getValue($reseller->id, 'show_contact_form', 'true') === 'true',
                    'show_pricing' => ResellerSetting::getValue($reseller->id, 'show_pricing', 'true') === 'true',
                    'show_testimonials' => ResellerSetting::getValue($reseller->id, 'show_testimonials', 'true') === 'true',
                ]);
            }
        }

        // Share with the view
        $view->with('resellerData', $resellerData);
    }
}
