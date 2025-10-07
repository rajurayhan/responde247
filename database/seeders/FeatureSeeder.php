<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Feature;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'title' => 'Natural Language Processing',
                'description' => 'Advanced NLP capabilities that understand context, intent, and natural conversation flow.',
                'icon' => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Real-time Processing',
                'description' => 'Lightning-fast response times with real-time voice processing and analysis.',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Analytics Dashboard',
                'description' => 'Comprehensive analytics to track performance, user engagement, and conversation insights.',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Enterprise Security',
                'description' => 'Bank-level security with end-to-end encryption and compliance with industry standards.',
                'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($features as $feature) {
            Feature::create($feature);
        }
    }
}
