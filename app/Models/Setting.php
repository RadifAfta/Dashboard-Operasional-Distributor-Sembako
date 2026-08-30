<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
    ];

    /**
     * Get a setting by key with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return cache()->rememberForever("setting.{$key}", function () use ($key, $default) {
                $setting = static::where('key', $key)->first();

                return $setting ? $setting->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set a setting value and clear cache.
     */
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'text', ?string $description = null): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type' => $type,
                'description' => $description,
            ]
        );

        cache()->forget("setting.{$key}");

        return $setting;
    }
}
