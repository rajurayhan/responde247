<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Assistant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'user_id',
        'vapi_assistant_id',
        'created_by',
        'reseller_id',
        'type',
        'phone_number',
        'webhook_url',
        // Transcriber configuration
        'transcriber',
        // Model configuration
        'model',
        // Voice configuration
        'voice',
        // First message configuration
        'first_message',
        'first_message_interruptions_enabled',
        'first_message_mode',
        // Voicemail detection
        'voicemail_detection',
        // Messages configuration
        'client_messages',
        'server_messages',
        // Call configuration
        'max_duration_seconds',
        'background_sound',
        'model_output_in_messages_enabled',
        // Transport configurations
        'transport_configurations',
        // Observability
        'observability_plan',
        // Credentials
        'credential_ids',
        'credentials',
        // Hooks
        'hooks',
        // Voicemail and end call messages
        'voicemail_message',
        'end_call_message',
        'end_call_phrases',
        // Compliance
        'compliance_plan',
        // Background speech denoising
        'background_speech_denoising_plan',
        // Analysis plan
        'analysis_plan',
        // Artifact plan
        'artifact_plan',
        // Speaking plans
        'start_speaking_plan',
        'stop_speaking_plan',
        // Monitor plan
        'monitor_plan',
        // Keypad input plan
        'keypad_input_plan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transcriber' => 'array',
        'model' => 'array',
        'voice' => 'array',
        'voicemail_detection' => 'array',
        'client_messages' => 'array',
        'server_messages' => 'array',
        'transport_configurations' => 'array',
        'observability_plan' => 'array',
        'credential_ids' => 'array',
        'credentials' => 'array',
        'hooks' => 'array',
        'end_call_phrases' => 'array',
        'compliance_plan' => 'array',
        'background_speech_denoising_plan' => 'array',
        'analysis_plan' => 'array',
        'artifact_plan' => 'array',
        'start_speaking_plan' => 'array',
        'stop_speaking_plan' => 'array',
        'monitor_plan' => 'array',
        'keypad_input_plan' => 'array',
        'first_message_interruptions_enabled' => 'boolean',
        'model_output_in_messages_enabled' => 'boolean',
        'reseller_id' => 'string',
    ];

    /**
     * Get the user that owns the assistant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that created the assistant.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Get the reseller that owns the assistant.
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    /**
     * Scope to get assistants for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get assistants created by a specific user
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }
    
    /**
     * Scope to get assistants for a specific reseller
     */
    public function scopeReseller($query)
    {
        $user = Auth::user();
        return $query->where('reseller_id', $user->reseller_id);
    }

    public function scopeContentProtection($query)
    {
        $user = Auth::user();
        $query->where('reseller_id', $user->reseller_id);
        if (!$user->isContentAdmin()) {
            $query->forUser($user->id);
        }
        return $query;
    }

    /**
     * Scope to get demo assistants
     */
    public function scopeDemo($query)
    {
        return $query->where('type', 'demo');
    }

    /**
     * Scope to get production assistants
     */
    public function scopeProduction($query)
    {
        return $query->where('type', 'production');
    }

    /**
     * Check if assistant is a demo
     */
    public function isDemo(): bool
    {
        return $this->type === 'demo';
    }

    /**
     * Check if assistant is production
     */
    public function isProduction(): bool
    {
        return $this->type === 'production';
    }
    
    /**
     * Get the call logs for this assistant.
     */
    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }
}
