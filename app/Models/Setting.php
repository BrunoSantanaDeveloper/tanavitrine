<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
        'description',
        'options',
        'accept',
        'sort_order',
        'is_enabled'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_enabled' => 'boolean',
        'value' => 'json',
        'options' => 'json',
        'sort_order' => 'integer'
    ];

    public const GROUP_PERMISSIONS = 'permissions';

    public static function getValueByKey(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)
                ->where('is_enabled', true)
                ->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function setValueByKey(string $key, mixed $value): void
    {
        $setting = self::firstOrCreate(['key' => $key]);
        $setting->value = $value;
        $setting->save();
        Cache::forget("settings.{$key}");
    }

    public static function getByGroup(string $group): array
    {
        return Cache::rememberForever("settings.group.{$group}", function () use ($group) {
            return self::where('group', $group)
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($setting) => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'options' => $setting->options,
                    'accept' => $setting->accept,
                ])
                ->toArray();
        });
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("settings.{$setting->key}");
            Cache::forget("settings.group.{$setting->group}");
        });

        static::deleted(function ($setting) {
            Cache::forget("settings.{$setting->key}");
            Cache::forget("settings.group.{$setting->group}");
        });
    }

    public static function getThemeColors(): array
    {
        return Cache::rememberForever('settings.theme.colors', function () {
            return self::where('group', 'appearance')
                ->where('type', 'color')
                ->where('is_enabled', true)
                ->get()
                ->mapWithKeys(fn ($setting) => [$setting->key => $setting->value])
                ->toArray();
        });
    }

    /**
     * Get all permission-related settings.
     */
    public static function getPermissionSettings(): array
    {
        return self::getByGroup(self::GROUP_PERMISSIONS);
    }

    /**
     * Clear all permission-related cache.
     */
    public static function clearPermissionCache(): void
    {
        Cache::forget("settings.group." . self::GROUP_PERMISSIONS);
    }
}
