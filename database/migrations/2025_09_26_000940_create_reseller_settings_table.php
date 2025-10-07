<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reseller_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('reseller_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, email, url, image, etc.
            $table->string('group')->default('general'); // general, branding, contact, etc.
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
            
            // Unique constraint for reseller + key combination
            $table->unique(['reseller_id', 'key']);
            
            // Index for faster queries
            $table->index(['reseller_id', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_settings');
    }
};