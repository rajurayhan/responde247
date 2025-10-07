<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class SubscriptionPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'name',
        'description',
        'price',
        'yearly_price',
        'voice_agents_limit',
        'monthly_minutes_limit',
        'extra_per_minute_charge',
        'features',
        'support_level',
        'analytics_level',
        'is_popular',
        'is_active',
    ];

    protected $casts = [
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'extra_per_minute_charge' => 'decimal:4',
        'features' => 'array',
        'reseller_id' => 'string',
    ];

    protected $appends = [
        'formatted_price',
        'formatted_yearly_price',
        'formatted_voice_agents_limit',
        'formatted_minutes_limit',
        'formatted_extra_per_minute_charge'
    ];

    /**
     * Get features as array (split from comma-separated string)
     */
    public function getFeaturesArrayAttribute()
    {
        if (empty($this->features)) {
            return [];
        }
        // If features is already an array, return it
        if (is_array($this->features)) {
            return $this->features;
        }
        // Fallback for legacy comma-separated strings
        return array_map('trim', explode(',', $this->features));
    }

    /**
     * Set features from array (join as comma-separated string)
     */
    public function setFeaturesArrayAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['features'] = $value;
        } else {
            $this->attributes['features'] = $value;
        }
    }

    /**
     * Get features as array for backward compatibility
     */
    public function getFeaturesAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        
        // If value is already an array, return it
        if (is_array($value)) {
            return $value;
        }
        
        // Try to decode JSON first
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        
        // Use a more robust approach to split features for legacy data
        // First, replace commas in numbers with a placeholder
        $value = preg_replace('/(\d+),(\d+)/', '$1###$2', $value);
        
        // Split by comma
        $features = array_map('trim', explode(',', $value));
        
        // Restore commas in numbers
        $features = array_map(function($feature) {
            return str_replace('###', ',', $feature);
        }, $features);
        
        // Filter out empty features
        return array_filter($features);
    }

    /**
     * Set features as array for JSON storage
     */
    public function setFeaturesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['features'] = json_encode($value);
        } else if (is_string($value)) {
            // If it's a string, try to parse it as comma-separated values
            $features = array_map('trim', explode(',', $value));
            $features = array_filter($features); // Remove empty values
            $this->attributes['features'] = json_encode($features);
        } else {
            // Fallback: store as empty array
            $this->attributes['features'] = json_encode([]);
        }
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Content protection scope - filters subscription packages by reseller
     */
    public function scopeContentProtection($query)
    {
        $user = Auth::user();
        $query->where('reseller_id', $user->reseller_id);
        if (!$user->isContentAdmin()) {
            // $query->where('user_id', $user->id);
        }
        return $query;
    }

    public function isUnlimited($field)
    {
        return $this->getAttribute($field) === -1;
    }

    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 0);
    }

    public function getFormattedYearlyPriceAttribute()
    {
        return '$' . number_format($this->yearly_price, 0);
    }

    public function getFormattedVoiceAgentsLimitAttribute()
    {
        return $this->isUnlimited('voice_agents_limit') ? 'Unlimited' : $this->voice_agents_limit;
    }

    public function getFormattedMinutesLimitAttribute()
    {
        return $this->isUnlimited('monthly_minutes_limit') ? 'Unlimited' : number_format($this->monthly_minutes_limit);
    }

    public function getFormattedExtraPerMinuteChargeAttribute()
    {
        return '$' . number_format($this->extra_per_minute_charge, 4);
    }
}
