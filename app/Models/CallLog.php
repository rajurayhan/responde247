<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CallLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'call_id',
        'assistant_id',
        'user_id',
        'reseller_id',
        'phone_number',
        'caller_number',
        'duration',
        'status',
        'direction',
        'start_time',
        'end_time',
        'transcript',
        'summary',
        'metadata',
        'webhook_data',
        'cost',
        'currency',
        'call_record_file_name',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'has_recording',
        'public_audio_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'webhook_data' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cost' => 'decimal:4',
        'reseller_id' => 'string',
    ];

    /**
     * Get the assistant that owns the call log.
     */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Assistant::class);
    }

    /**
     * Get the user that owns the call log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reseller that owns the call log.
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Scope to get call logs for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get call logs for a specific assistant
     */
    public function scopeForAssistant($query, $assistantId)
    {
        return $query->where('assistant_id', $assistantId);
    }

    /**
     * Scope to get completed calls
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed calls
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'busy', 'no-answer']);
    }

    /**
     * Scope to get inbound calls
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    /**
     * Scope to get outbound calls
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * Scope for content protection - filters by reseller and user
     */
    public function scopeContentProtection($query)
    {
        $user = Auth::user();
        $query->where('reseller_id', $user->reseller_id);
        if (!$user->isContentAdmin()) {
            $query->where('user_id', $user->id);
        }
        return $query;
    }

    /**
     * Check if call is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if call is failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'busy', 'no-answer']);
    }

    /**
     * Check if call is inbound
     */
    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    /**
     * Check if call is outbound
     */
    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return 'N/A';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCostAttribute(): string
    {
        if (!$this->cost) {
            return 'N/A';
        }

        return number_format($this->cost, 4) . ' ' . ($this->currency ?? 'USD');
    }

    /**
     * Get public audio URL for call recording
     */
    public function getPublicAudioUrlAttribute(): ?string
    {
        if (!$this->call_record_file_name) {
            return null;
        }

        return url('/p/' . $this->call_record_file_name);
    }

    /**
     * Check if call has recording
     */
    public function hasRecording(): bool
    {
        return !empty($this->call_record_file_name);
    }

    /**
     * Get has_recording attribute for API
     */
    public function getHasRecordingAttribute(): bool
    {
        return $this->hasRecording();
    }
} 