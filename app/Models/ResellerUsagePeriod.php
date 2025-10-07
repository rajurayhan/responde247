<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerUsagePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'reseller_subscription_id',
        'reseller_package_id',
        'period_start',
        'period_end',
        'total_calls',
        'total_duration_seconds',
        'total_cost',
        'monthly_cost_limit',
        'monthly_minutes_limit',
        'extra_per_minute_charge',
        'overage_cost',
        'overage_minutes',
        'overage_status',
        'overage_billed_at',
        'overage_transaction_id',
        'carried_forward_amount',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'total_calls' => 'integer',
        'total_duration_seconds' => 'integer',
        'total_cost' => 'decimal:4',
        'monthly_cost_limit' => 'decimal:4',
        'monthly_minutes_limit' => 'integer',
        'extra_per_minute_charge' => 'decimal:4',
        'overage_cost' => 'decimal:4',
        'overage_minutes' => 'integer',
        'overage_billed_at' => 'datetime',
        'carried_forward_amount' => 'decimal:4',
        'metadata' => 'array',
        'reseller_id' => 'string',
    ];

    /**
     * Get the reseller that owns this usage period
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    /**
     * Get the subscription that owns this usage period
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ResellerSubscription::class, 'reseller_subscription_id');
    }

    /**
     * Get the package for this usage period
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(ResellerPackage::class, 'reseller_package_id');
    }

    /**
     * Get the transaction for overage billing
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ResellerTransaction::class, 'overage_transaction_id');
    }

    /**
     * Get all call logs for this period
     */
    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class, 'reseller_id', 'reseller_id')
            ->whereBetween('created_at', [$this->period_start, $this->period_end]);
    }

    /**
     * Scope to get current period (not ended yet)
     */
    public function scopeCurrentPeriod($query)
    {
        return $query->where('period_end', '>', now());
    }

    /**
     * Scope to get periods with pending billing
     */
    public function scopePendingBilling($query)
    {
        return $query->where('overage_status', 'pending')
            ->where('overage_cost', '>', 0);
    }

    /**
     * Scope to get periods with overages
     */
    public function scopeWithOverage($query)
    {
        return $query->where('overage_cost', '>', 0);
    }

    /**
     * Check if this period has overage
     */
    public function hasOverage(): bool
    {
        return $this->overage_cost > 0;
    }

    /**
     * Check if overage is over the billing threshold
     */
    public function isOverThreshold(): bool
    {
        $threshold = config('reseller-billing.overage_threshold', 10.00);
        $totalOverage = $this->overage_cost + $this->carried_forward_amount;
        
        return $totalOverage >= $threshold;
    }

    /**
     * Get the overage amount including carried forward
     */
    public function getOverageAmount(): float
    {
        return (float) ($this->overage_cost + $this->carried_forward_amount);
    }

    /**
     * Check if period has ended
     */
    public function hasEnded(): bool
    {
        return $this->period_end->isPast();
    }

    /**
     * Check if period is current (active)
     */
    public function isCurrent(): bool
    {
        return now()->between($this->period_start, $this->period_end);
    }

    /**
     * Get usage percentage (based on cost limit)
     */
    public function getUsagePercentage(): float
    {
        if ($this->monthly_cost_limit <= 0 || $this->monthly_minutes_limit === -1) {
            return 0; // Unlimited package
        }

        return ($this->total_cost / $this->monthly_cost_limit) * 100;
    }

    /**
     * Check if this is an unlimited package
     */
    public function isUnlimitedPackage(): bool
    {
        return $this->monthly_minutes_limit === -1;
    }

    /**
     * Get formatted period label
     */
    public function getPeriodLabelAttribute(): string
    {
        return $this->period_start->format('M j, Y') . ' - ' . $this->period_end->format('M j, Y');
    }

    /**
     * Get formatted total cost
     */
    public function getFormattedTotalCostAttribute(): string
    {
        return '$' . number_format($this->total_cost, 2);
    }

    /**
     * Get formatted overage cost
     */
    public function getFormattedOverageCostAttribute(): string
    {
        return '$' . number_format($this->getOverageAmount(), 2);
    }

    /**
     * Get overage minutes (calculated attribute)
     */
    public function getOverageMinutesAttribute(): int
    {
        if ($this->monthly_minutes_limit <= 0) {
            return 0;
        }
        
        $totalMinutes = $this->total_duration_seconds / 60;
        return max(0, (int) ($totalMinutes - $this->monthly_minutes_limit));
    }

    /**
     * Get overage cost (calculated attribute)
     */
    public function getOverageCostAttribute(): float
    {
        if ($this->monthly_minutes_limit > 0) {
            // For minute packages, use duration-based calculation
            return $this->overage_minutes * $this->extra_per_minute_charge;
        } else {
            // For unlimited packages, use cost-based calculation
            return max(0, $this->total_cost - $this->monthly_cost_limit);
        }
    }

    /**
     * Check if this is a minute-limited package
     */
    public function isMinuteLimited(): bool
    {
        return $this->monthly_minutes_limit > 0;
    }

    /**
     * Check if this is an unlimited package
     */
    public function isUnlimited(): bool
    {
        return $this->monthly_minutes_limit === -1;
    }
}
