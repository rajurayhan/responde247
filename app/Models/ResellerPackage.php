<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerPackage extends Model
{
    use HasFactory;

    protected $fillable = [
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
        return $this->hasMany(ResellerSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
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

    /**
     * Check if this package has unlimited minutes
     */
    public function isUnlimitedMinutes(): bool
    {
        return $this->monthly_minutes_limit === -1;
    }

    /**
     * Check if this package has minute limits
     */
    public function isMinuteLimited(): bool
    {
        return $this->monthly_minutes_limit > 0;
    }

    /**
     * Get the effective cost limit for this package
     */
    public function getEffectiveCostLimit(): float
    {
        if ($this->isUnlimitedMinutes()) {
            return $this->price;
        }
        
        return 0; // No cost limit for minute-limited packages
    }

    /**
     * Check if this package supports yearly billing
     */
    public function supportsYearlyBilling(): bool
    {
        return !is_null($this->yearly_price) && $this->yearly_price > 0;
    }

    /**
     * Get the yearly price or calculate it from monthly price
     */
    public function getYearlyPrice(): float
    {
        if ($this->yearly_price) {
            return $this->yearly_price;
        }
        
        // Calculate yearly price from monthly (10 months for 2 months free)
        return $this->price * 10;
    }

    /**
     * Get the effective price for a given billing interval
     */
    public function getPriceForInterval(string $interval): float
    {
        return $interval === 'yearly' ? $this->getYearlyPrice() : $this->price;
    }

    /**
     * Get the savings percentage for yearly billing
     */
    public function getYearlySavingsPercentage(): float
    {
        if (!$this->supportsYearlyBilling()) {
            return 0;
        }
        
        $monthlyYearlyCost = $this->price * 12;
        $yearlyCost = $this->yearly_price;
        
        if ($monthlyYearlyCost <= 0) {
            return 0;
        }
        
        return round((($monthlyYearlyCost - $yearlyCost) / $monthlyYearlyCost) * 100, 1);
    }

    /**
     * Get formatted yearly savings
     */
    public function getFormattedYearlySavings(): string
    {
        $savings = $this->getYearlySavingsPercentage();
        return $savings > 0 ? "Save {$savings}%" : '';
    }
}
