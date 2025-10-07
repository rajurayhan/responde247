<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reseller_id',
        'subscription_package_id',
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
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'ends_at' => 'datetime',
        'custom_amount' => 'decimal:2',
        'metadata' => 'array',
        'reseller_id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'subscription_package_id');
    }

    /**
     * Get the reseller that owns the subscription.
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
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

    public function getAmount()
    {
        return $this->custom_amount ?? $this->package->price;
    }

    public function hasPaymentLink()
    {
        return !is_null($this->payment_link_url);
    }

    public function isPaymentLinkExpired()
    {
        // Payment links typically expire after 7 days
        return $this->created_at && $this->created_at->addDays(7)->isPast();
    }

    public function daysUntilExpiration()
    {
        if (!$this->current_period_end) {
            return 0;
        }
        
        $days = now()->diffInDays($this->current_period_end, false);
        return max(0, (int) $days);
    }

    /**
     * Scope to apply content protection based on reseller and user access.
     */
    public function scopeContentProtection($query)
    {
        $user = Auth::user();
        
        // If no authenticated user, return empty result for security
        if (!$user) {
            $query->whereRaw('1 = 0'); // This will return no results
            return $query;
        }
        
        $query->where('reseller_id', $user->reseller_id);
        if (!$user->isContentAdmin()) {
            $query->where('user_id', $user->id);
        }
        return $query;
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'active' => 'bg-green-100 text-green-800',
            'pending' => 'bg-purple-100 text-purple-800',
            'cancelled' => 'bg-yellow-100 text-yellow-800',
            'expired' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
