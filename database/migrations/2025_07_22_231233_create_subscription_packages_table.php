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
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Starter, Professional, Enterprise
            $table->text('description');
            $table->decimal('price', 10, 2); // Monthly price
            $table->integer('voice_agents_limit'); // -1 for unlimited
            $table->integer('monthly_minutes_limit'); // -1 for unlimited
            $table->json('features'); // Array of features
            $table->string('support_level'); // email, priority, dedicated
            $table->string('analytics_level'); // basic, advanced, custom
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_packages');
    }
};
