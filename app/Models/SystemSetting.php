<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key
     */
    public static function setValue($key, $value, $label = null, $type = 'text', $group = 'general', $description = null)
    {
        $setting = self::where('key', $key)->first();
        
        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            self::create([
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
     * Get all settings grouped by group
     */
    public static function getGroupedSettings()
    {
        return self::orderBy('group')->orderBy('label')->get()->groupBy('group');
    }
}
