<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get the reseller that owns this setting.
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Get setting value by key for a specific reseller
     */
    public static function getValue($resellerId, $key, $default = null)
    {
        $setting = self::where('reseller_id', $resellerId)
                      ->where('key', $key)
                      ->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key for a specific reseller
     */
    public static function setValue($resellerId, $key, $value, $label = null, $type = 'text', $group = 'general', $description = null)
    {
        $setting = self::where('reseller_id', $resellerId)
                      ->where('key', $key)
                      ->first();
        
        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            $setting = self::create([
                'reseller_id' => $resellerId,
                'key' => $key,
                'value' => $value,
                'label' => $label ?? ucfirst(str_replace('_', ' ', $key)),
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]);
        }
        
        return $setting;
    }

    /**
     * Get all settings for a reseller grouped by group
     */
    public static function getGroupedSettings($resellerId)
    {
        return self::where('reseller_id', $resellerId)
                  ->orderBy('group')
                  ->orderBy('label')
                  ->get()
                  ->groupBy('group');
    }

    /**
     * Get all settings for a reseller as a flat array
     */
    public static function getAllSettings($resellerId)
    {
        return self::where('reseller_id', $resellerId)
                  ->orderBy('group')
                  ->orderBy('label')
                  ->get();
    }

    /**
     * Bulk update settings for a reseller
     */
    public static function bulkUpdate($resellerId, array $settings)
    {
        foreach ($settings as $setting) {
            $key = $setting['key'];
            $value = $setting['value'] ?? null;
            $label = $setting['label'] ?? null;
            $type = $setting['type'] ?? 'text';
            $group = $setting['group'] ?? 'general';
            $description = $setting['description'] ?? null;

            self::setValue($resellerId, $key, $value, $label, $type, $group, $description);
        }
    }

    /**
     * Get public settings for a reseller (for frontend display)
     */
    public static function getPublicSettings($resellerId)
    {
        return self::where('reseller_id', $resellerId)
                  ->where('group', 'public')
                  ->pluck('value', 'key')
                  ->toArray();
    }
}