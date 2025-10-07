<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site_title',
                'value' => 'XpartFone',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Site Title',
                'description' => 'The main title of your website'
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Never Miss a call Again XpartFone answers 24x7!',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Site Tagline',
                'description' => 'A short description or tagline for your site'
            ],
            [
                'key' => 'company_phone',
                'value' => '(682) 582 8396',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Company Phone',
                'description' => 'Primary contact phone number for the company'
            ],
            [
                'key' => 'company_email',
                'value' => 'support@xpartfone.com',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Company Email',
                'description' => 'Primary contact email address for the company'
            ],
            [
                'key' => 'company_name',
                'value' => 'XpartFone',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Company Name',
                'description' => 'Official company name for legal documents and branding'
            ],
            [
                'key' => 'meta_description',
                'value' => 'Transform your business with cutting-edge voice AI technology. Create intelligent voice agents that understand, respond, and engage with your customers 24/7.',
                'type' => 'textarea',
                'group' => 'seo',
                'label' => 'Meta Description',
                'description' => 'Description for search engines (SEO)'
            ],
            [
                'key' => 'logo_url',
                'value' => '/logo.png',
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Site Logo',
                'description' => 'Upload your site logo (recommended: 200x60px)'
            ],
            [
                'key' => 'homepage_banner',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Homepage Banner',
                'description' => 'Banner image for the homepage (recommended: 1200x400px)'
            ]
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
