<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResellerMailTrait;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, ResellerMailTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'company',
        'bio',
        'status',
        'profile_picture',
        'reseller_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function scopeReseller($query)
    {
        $resellerId = config('reseller.id');
        return $query->where('reseller_id', $resellerId);
    }

    /**
     * Get validation rules for user creation/update
     */
    public static function getValidationRules($userId = null, $resellerId = null)
    {
        $resellerId = $resellerId ?? config('reseller.id');
        
        $emailRule = 'required|email|max:255';
        
        if ($userId) {
            // For updates, exclude current user from uniqueness check
            $emailRule .= '|unique:users,email,' . $userId . ',id,reseller_id,' . $resellerId;
        } else {
            // For creation, check uniqueness within the reseller
            $emailRule .= '|unique:users,email,NULL,id,reseller_id,' . $resellerId;
        }
        
        return [
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'password' => $userId ? 'sometimes|string|min:8' : 'required|string|min:8',
            'role' => 'sometimes|string|in:user,admin,content_admin,reseller_admin',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'status' => 'sometimes|string|in:active,inactive',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Check if email is unique within the reseller
     */
    public static function isEmailUniqueInReseller($email, $resellerId, $excludeUserId = null)
    {
        $query = static::where('email', $email)
                      ->where('reseller_id', $resellerId);
        
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        
        return !$query->exists();
    }
    /**
     * Get the profile picture URL
     */
    public function getProfilePictureAttribute($value)
    {
        if ($value) {
            return Storage::disk('public')->url($value);
        }
        return null;
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    /**
     * Check if user is a super admin (bypasses subscription checks)
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'content_admin';
    }

    /**
     * Check if user is a content admin (includes reseller admins)
     */
    public function isContentAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'content_admin' || $this->role === 'reseller_admin';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is a reseller admin
     */
    public function isResellerAdmin(): bool
    {
        return $this->role === 'reseller_admin' && $this->reseller_id !== null;
    }

    /**
     * Get the reseller that the user belongs to.
     */
    public function reseller()
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    /**
     * Get the reseller ID for the user
     */
    public function getResellerId()
    {
        return $this->reseller_id;
    }

    /**
     * Get the assistants owned by the user.
     */
    public function assistants()
    {
        return $this->hasMany(Assistant::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where(function($query) {
                $query->where('status', 'active')
                      ->orWhere('status', 'pending');
            });
    }

    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    public function hasPendingSubscription()
    {
        return $this->subscriptions()->where('status', 'pending')->exists();
    }

    public function getCurrentSubscriptionAttribute()
    {
        return $this->activeSubscription;
    }

    public function canCreateAssistant()
    {
        // Check if user has active subscription
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        // If user belongs to a reseller, check reseller subscription
        if ($this->reseller_id) {
            if (!$this->canCreateAssistantWithReseller()) {
                return false;
            }
        }

        $subscription = $this->currentSubscription;
        $package = $subscription->package;
        
        // Check if unlimited or within limit
        if ($package->isUnlimited('voice_agents_limit')) {
            return true;
        }

        $currentCount = $this->assistants()->count();
        return $currentCount < $package->voice_agents_limit;
    }

    public function getRemainingAssistantsAttribute()
    {
        if (!$this->hasActiveSubscription()) {
            return 0;
        }

        $subscription = $this->currentSubscription;
        $package = $subscription->package;
        
        if ($package->isUnlimited('voice_agents_limit')) {
            return -1; // Unlimited
        }

        $currentCount = $this->assistants()->count();
        return max(0, $package->voice_agents_limit - $currentCount);
    }

    /**
     * Check if user can create assistant considering reseller subscription
     */
    public function canCreateAssistantWithReseller(): bool
    {
        // If user doesn't belong to a reseller, allow creation
        if (!$this->reseller_id) {
            return true;
        }

        // Get the reseller
        $reseller = $this->reseller;
        if (!$reseller) {
            return false;
        }

        // Check if reseller is active
        if (!$reseller->isActive()) {
            return false;
        }

        // Check if reseller has active subscription
        if (!$reseller->hasActiveSubscription()) {
            return false;
        }

        return true;
    }

    /**
     * Get validation message for assistant creation failure
     */
    public function getAssistantCreationValidationMessage(): string
    {
        // Check user subscription first
        if (!$this->hasActiveSubscription()) {
            return 'You need an active subscription to create assistants. Please subscribe to a plan to get started.';
        }

        // Check reseller subscription if user belongs to a reseller
        if ($this->reseller_id) {
            $reseller = $this->reseller;
            
            if (!$reseller) {
                return 'Your reseller account is not found. Please contact support.';
            }

            if (!$reseller->isActive()) {
                return 'Your reseller account is inactive. Please contact your reseller administrator.';
            }

            if (!$reseller->hasActiveSubscription()) {
                return 'Your reseller does not have an active subscription. Please contact your reseller administrator.';
            }
        }

        // Check assistant limits
        $subscription = $this->currentSubscription;
        $package = $subscription->package;
        
        if (!$package->isUnlimited('voice_agents_limit')) {
            $currentCount = $this->assistants()->count();
            if ($currentCount >= $package->voice_agents_limit) {
                return 'You have reached your assistant limit for your current subscription plan. Please upgrade your plan to create more assistants.';
            }
        }

        return 'You can create assistants.';
    }

    /**
     * Scope to protect content based on user role and reseller
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
            $query->where('id', $user->id); // Users can only see themselves
        }
        return $query;
    }

    /**
     * Scope to filter users for a specific user (non-admin)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('id', $userId);
    }
}
