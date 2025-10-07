<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'icon',
        'order',
        'is_active',
        'reseller_id',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'reseller_id' => 'string',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to get features for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the reseller that owns the feature.
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Get the user that owns the feature.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for content protection - filters by reseller and user
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
            $query->forUser($user->id);
        }
        return $query;
    }
}
