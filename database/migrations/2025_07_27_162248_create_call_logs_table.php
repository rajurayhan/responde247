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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('call_id')->unique();
            $table->unsignedBigInteger('assistant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('phone_number')->nullable();
            $table->string('caller_number')->nullable();
            $table->integer('duration')->nullable();
            $table->enum('status', ['initiated', 'ringing', 'in-progress', 'completed', 'failed', 'busy', 'no-answer', 'cancelled'])->default('initiated');
            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->longText('transcript')->nullable();
            $table->text('summary')->nullable();
            $table->json('metadata')->nullable();
            $table->json('webhook_data')->nullable();
            $table->decimal('cost', 10, 4)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            // Indexes
            $table->index(['assistant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['direction', 'created_at']);
            $table->index('start_time');

            // Foreign keys
            $table->foreign('assistant_id')->references('id')->on('assistants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
