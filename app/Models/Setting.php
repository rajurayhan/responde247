<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'reseller_id',
        'user_id'
    ];

    /**
     * Get setting value with proper type casting
     */
    public function getTypedValueAttribute()
    {
        switch ($this->type) {
            case 'json':
                return json_decode($this->value, true);
            case 'boolean':
                return (bool) $this->value;
            case 'integer':
                return (int) $this->value;
            default:
                return $this->value;
        }
    }

    /**
     * Set setting value with proper type handling
     */
    public function setTypedValueAttribute($value)
    {
        switch ($this->type) {
            case 'json':
                $this->value = json_encode($value);
                break;
            case 'boolean':
                $this->value = $value ? '1' : '0';
                break;
            case 'integer':
                $this->value = (string) $value;
                break;
            default:
                $this->value = (string) $value;
        }
    }

    /**
     * Scope to get settings by group
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to get settings for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the reseller that owns the setting.
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Get the user that owns the setting.
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

    /**
     * Get a setting by key
     */
    public static function getValue($key, $default = null)
    {
        $user = Auth::user();
        $query = static::where('key', $key);
        
        if ($user) {
            $query->where('reseller_id', $user->reseller_id);
        }
        
        $setting = $query->first();
        
        if (!$setting) {
            return $default;
        }

        return $setting->typed_value;
    }

    /**
     * Set a setting value
     */
    public static function setValue($key, $value, $type = 'string', $group = 'general', $description = null)
    {
        $user = Auth::user();
        $query = static::where('key', $key);
        
        if ($user) {
            $query->where('reseller_id', $user->reseller_id);
        }
        
        $setting = $query->first();
        
        if ($setting) {
            $setting->update([
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description
            ]);
        } else {
            $setting = static::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'reseller_id' => $user ? $user->reseller_id : null,
                'user_id' => $user ? $user->id : null
            ]);
        }

        return $setting;
    }
} 