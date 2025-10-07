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
        Schema::table('assistants', function (Blueprint $table) {
            // Transcriber configuration
            $table->json('transcriber')->nullable();
            
            // Model configuration
            $table->json('model')->nullable();
            
            // Voice configuration
            $table->json('voice')->nullable();
            
            // First message configuration
            $table->text('first_message')->nullable();
            $table->boolean('first_message_interruptions_enabled')->default(false);
            $table->string('first_message_mode')->default('assistant-speaks-first');
            
            // Voicemail detection
            $table->json('voicemail_detection')->nullable();
            
            // Messages configuration
            $table->json('client_messages')->nullable();
            $table->json('server_messages')->nullable();
            
            // Call configuration
            $table->integer('max_duration_seconds')->default(600);
            $table->string('background_sound')->nullable();
            $table->boolean('model_output_in_messages_enabled')->default(false);
            
            // Transport configurations
            $table->json('transport_configurations')->nullable();
            
            // Observability
            $table->json('observability_plan')->nullable();
            
            // Credentials
            $table->json('credential_ids')->nullable();
            $table->json('credentials')->nullable();
            
            // Hooks
            $table->json('hooks')->nullable();
            
            // Voicemail and end call messages
            $table->text('voicemail_message')->nullable();
            $table->text('end_call_message')->nullable();
            $table->json('end_call_phrases')->nullable();
            
            // Compliance
            $table->json('compliance_plan')->nullable();
            
            // Background speech denoising
            $table->json('background_speech_denoising_plan')->nullable();
            
            // Analysis plan
            $table->json('analysis_plan')->nullable();
            
            // Artifact plan
            $table->json('artifact_plan')->nullable();
            
            // Speaking plans
            $table->json('start_speaking_plan')->nullable();
            $table->json('stop_speaking_plan')->nullable();
            
            // Monitor plan
            $table->json('monitor_plan')->nullable();
            
            // Keypad input plan
            $table->json('keypad_input_plan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn([
                'transcriber',
                'model',
                'voice',
                'first_message',
                'first_message_interruptions_enabled',
                'first_message_mode',
                'voicemail_detection',
                'client_messages',
                'server_messages',
                'max_duration_seconds',
                'background_sound',
                'model_output_in_messages_enabled',
                'transport_configurations',
                'observability_plan',
                'credential_ids',
                'credentials',
                'hooks',
                'voicemail_message',
                'end_call_message',
                'end_call_phrases',
                'compliance_plan',
                'background_speech_denoising_plan',
                'analysis_plan',
                'artifact_plan',
                'start_speaking_plan',
                'stop_speaking_plan',
                'monitor_plan',
                'keypad_input_plan',
            ]);
        });
    }
};
