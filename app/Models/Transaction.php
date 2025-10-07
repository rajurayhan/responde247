<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reseller_id',
        'subscription_package_id',
        'user_subscription_id',
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
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'metadata' => 'array',
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

    // Payment method constants
    const PAYMENT_STRIPE = 'stripe';
    const PAYMENT_PAYPAL = 'paypal';
    const PAYMENT_MANUAL = 'manual';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'subscription_package_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Content protection scope - filters transactions by reseller
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
     * Helper methods
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isSubscription(): bool
    {
        return $this->type === self::TYPE_SUBSCRIPTION;
    }

    public function isUpgrade(): bool
    {
        return $this->type === self::TYPE_UPGRADE;
    }

    public function isRenewal(): bool
    {
        return $this->type === self::TYPE_RENEWAL;
    }

    public function isRefund(): bool
    {
        return $this->type === self::TYPE_REFUND;
    }

    public function isTrial(): bool
    {
        return $this->type === self::TYPE_TRIAL;
    }

    /**
     * Accessors
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
            self::STATUS_REFUNDED => 'bg-gray-100 text-gray-800',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SUBSCRIPTION => 'bg-blue-100 text-blue-800',
            self::TYPE_UPGRADE => 'bg-purple-100 text-purple-800',
            self::TYPE_RENEWAL => 'bg-green-100 text-green-800',
            self::TYPE_REFUND => 'bg-orange-100 text-orange-800',
            self::TYPE_TRIAL => 'bg-indigo-100 text-indigo-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPaymentMethodIconAttribute(): string
    {
        return match($this->payment_method) {
            self::PAYMENT_STRIPE => '💳',
            self::PAYMENT_PAYPAL => '🔗',
            self::PAYMENT_MANUAL => '👤',
            default => '💰',
        };
    }

    /**
     * Static methods
     */
    public static function generateTransactionId(): string
    {
        return 'TXN-' . strtoupper(Str::random(12));
    }

    /**
     * Boot method to set transaction_id if not provided
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = self::generateTransactionId();
            }
        });
    }
}
