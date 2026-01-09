<?php
// ============================================
// FILE: app/Models/SystemSetting.php
// AUDIT FIX: Updated column names to match migration
// Migration uses: setting_key, setting_value
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     * AUDIT FIX: Changed from 'key'/'value' to 'setting_key'/'setting_value'
     * to match the database schema in migration 2025_01_01_000000
     *
     * @var array
     */
    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide setting values which may contain sensitive configuration
     *
     * @var array
     */
    protected $hidden = [
        'setting_value',
    ];

    /**
     * Get a setting by key
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Set a setting by key
     */
    public static function set($key, $value)
    {
        return static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
    }

    /**
     * Get all settings as key-value array
     */
    public static function getAllAsArray(): array
    {
        return static::pluck('setting_value', 'setting_key')->toArray();
    }
}