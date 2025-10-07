<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;

class Reseller extends Model
{
    use HasFactory, HasUuids, Notifiable;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'org_name',
        'logo_address',
        'company_email',
        'domain',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active resellers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive resellers.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Check if the reseller is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the reseller is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Get the users that belong to this reseller
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the admin user for this reseller
     */
    public function adminUser()
    {
        return $this->hasOne(User::class)->where('role', 'reseller_admin');
    }

    /**
     * Get active users for this reseller
     */
    public function activeUsers()
    {
        return $this->hasMany(User::class)->where('status', 'active');
    }

    /**
     * Get the reseller's subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(ResellerSubscription::class);
    }

    /**
     * Get the reseller's active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(ResellerSubscription::class)
            ->where(function($query) {
                $query->where('status', 'active')
                      ->orWhere('status', 'pending');
            });
    }

    /**
     * Get the reseller's transactions
     */
    public function transactions()
    {
        return $this->hasMany(ResellerTransaction::class);
    }

    /**
     * Check if reseller has active subscription
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Get the reseller's current subscription
     */
    public function getCurrentSubscriptionAttribute()
    {
        return $this->activeSubscription;
    }

    /**
     * Get the assistants that belong to this reseller
     */
    public function assistants()
    {
        return $this->hasMany(Assistant::class);
    }

    /**
     * Get the call logs that belong to this reseller
     */
    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Get the email address for password reset notifications.
     */
    public function getEmailForPasswordReset()
    {
        return $this->company_email;
    }

    /**
     * Get the email address for notifications.
     */
    public function routeNotificationForMail($notification)
    {
        return $this->company_email;
    }
}
