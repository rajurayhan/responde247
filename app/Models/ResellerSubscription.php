<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'reseller_package_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'ends_at',
        'stripe_subscription_id',
        'stripe_customer_id',
        'payment_link_id',
        'payment_link_url',
        'stripe_checkout_session_id',
        'checkout_session_url',
        'custom_amount',
        'billing_interval',
        'metadata',
        'current_period_usage_cost',
        'current_period_calls',
        'current_period_duration',
        'pending_overage_cost',
        'last_usage_calculated_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'ends_at' => 'datetime',
        'custom_amount' => 'decimal:2',
        'billing_interval' => 'string',
        'metadata' => 'array',
        'reseller_id' => 'string',
        'current_period_usage_cost' => 'decimal:4',
        'current_period_calls' => 'integer',
        'current_period_duration' => 'integer',
        'pending_overage_cost' => 'decimal:4',
        'last_usage_calculated_at' => 'datetime',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ResellerPackage::class, 'reseller_package_id');
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isExpired()
    {
        return $this->status === 'expired';
    }

    public function hasExpired()
    {
        return $this->current_period_end && $this->current_period_end->isPast();
    }

    public function isCustomSubscription()
    {
        return !is_null($this->custom_amount);
    }

    /**
     * Get usage periods for this subscription
     */
    public function usagePeriods()
    {
        return $this->hasMany(ResellerUsagePeriod::class, 'reseller_subscription_id');
    }

    /**
     * Get the current usage period
     */
    public function getCurrentUsagePeriod(): ?ResellerUsagePeriod
    {
        return $this->usagePeriods()
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first();
    }

    /**
     * Check if current period has usage overage
     */
    public function hasUsageOverage(): bool
    {
        return $this->pending_overage_cost > 0;
    }

    /**
     * Get usage percentage for current period
     */
    public function getUsagePercentage(): float
    {
        $currentPeriod = $this->getCurrentUsagePeriod();
        
        if (!$currentPeriod) {
            return 0;
        }

        return $currentPeriod->getUsagePercentage();
    }

    /**
     * Reset period usage counters
     */
    public function resetPeriodUsage(): void
    {
        $this->update([
            'current_period_usage_cost' => 0,
            'current_period_calls' => 0,
            'current_period_duration' => 0,
            'pending_overage_cost' => 0,
            'last_usage_calculated_at' => null,
        ]);
    }

    /**
     * Check if this subscription is yearly billing
     */
    public function isYearlyBilling(): bool
    {
        return $this->billing_interval === 'yearly';
    }

    /**
     * Check if this subscription is monthly billing
     */
    public function isMonthlyBilling(): bool
    {
        return $this->billing_interval === 'monthly';
    }

    /**
     * Get the effective price for this subscription
     * Returns yearly price if yearly billing, otherwise monthly price
     */
    public function getEffectivePrice(): float
    {
        if ($this->custom_amount) {
            return $this->custom_amount;
        }

        $package = $this->package;
        if (!$package) {
            return 0;
        }

        return $this->isYearlyBilling() ? $package->yearly_price : $package->price;
    }

    /**
     * Get the billing interval display name
     */
    public function getBillingIntervalDisplayAttribute(): string
    {
        return $this->billing_interval === 'yearly' ? 'Yearly' : 'Monthly';
    }

    /**
     * Get the next billing date
     */
    public function getNextBillingDate(): ?\Carbon\Carbon
    {
        if (!$this->current_period_end) {
            return null;
        }

        return $this->current_period_end;
    }
}
