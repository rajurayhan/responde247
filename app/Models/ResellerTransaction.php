<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResellerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'reseller_package_id',
        'reseller_subscription_id',
        'usage_period_id',
        'transaction_id',
        'external_transaction_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_method_id',
        'payment_details',
        'billing_email',
        'billing_name',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_postal_code',
        'type',
        'description',
        'metadata',
        'overage_details',
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'metadata' => 'array',
        'overage_details' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'reseller_id' => 'string',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    // Type constants
    const TYPE_SUBSCRIPTION = 'subscription';
    const TYPE_UPGRADE = 'upgrade';
    const TYPE_RENEWAL = 'renewal';
    const TYPE_REFUND = 'refund';
    const TYPE_TRIAL = 'trial';
    const TYPE_OVERAGE = 'overage';

    // Payment method constants
    const PAYMENT_STRIPE = 'stripe';
    const PAYMENT_PAYPAL = 'paypal';
    const PAYMENT_MANUAL = 'manual';

    /**
     * Relationships
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ResellerPackage::class, 'reseller_package_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ResellerSubscription::class, 'reseller_subscription_id');
    }

    public function usagePeriod(): BelongsTo
    {
        return $this->belongsTo(ResellerUsagePeriod::class, 'usage_period_id');
    }

    /**
     * Boot method to generate transaction_id
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = 'RTX_' . Str::upper(Str::random(12));
            }
        });
    }

    /**
     * Scope for active transactions
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_COMPLETED]);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if transaction is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }
}
