<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add company phone setting if it doesn't exist
        if (!SystemSetting::where('key', 'company_phone')->exists()) {
            SystemSetting::create([
                'key' => 'company_phone',
                'value' => '(682) 582 8396',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Company Phone',
                'description' => 'Primary contact phone number for the company'
            ]);
        }

        // Add company email setting if it doesn't exist
        if (!SystemSetting::where('key', 'company_email')->exists()) {
            SystemSetting::create([
                'key' => 'company_email',
                'value' => 'support@xpartfone.com',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Company Email',
                'description' => 'Primary contact email address for the company'
            ]);
        }

        // Add company name setting if it doesn't exist
        if (!SystemSetting::where('key', 'company_name')->exists()) {
            SystemSetting::create([
                'key' => 'company_name',
                'value' => 'XpartFone',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Company Name',
                'description' => 'Official company name for legal documents and branding'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the company contact settings
        SystemSetting::where('key', 'company_phone')->delete();
        SystemSetting::where('key', 'company_email')->delete();
        SystemSetting::where('key', 'company_name')->delete();
    }
};
