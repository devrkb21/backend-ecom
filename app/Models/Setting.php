<?php

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
        'label',
        'description',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get a setting value by group and key
     */
    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember(
            "settings.{$group}.{$key}",
            3600,
            fn() => static::where('group', $group)->where('key', $key)->first()
        );

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Get all settings for a group
     */
    public static function getGroup(string $group, bool $publicOnly = true): array
    {
        $cacheKey = "settings.group.{$group}" . ($publicOnly ? '.public' : '.all');

        return Cache::remember($cacheKey, 3600, function () use ($group, $publicOnly) {
            $query = static::where('group', $group)->orderBy('sort_order');

            if ($publicOnly) {
                $query->where('is_public', true);
            }

            return $query->get()->mapWithKeys(function ($setting) {
                return [$setting->key => static::castValue($setting->value, $setting->type)];
            })->toArray();
        });
    }

    /**
     * Get all public settings grouped
     */
    public static function getAllPublic(): array
    {
        return Cache::remember('settings.all.public', 3600, function () {
            return static::where('is_public', true)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('group')
                ->map(function ($items) {
                    return $items->mapWithKeys(function ($setting) {
                        return [$setting->key => static::castValue($setting->value, $setting->type)];
                    });
                })
                ->toArray();
        });
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $group, string $key, mixed $value, array $attributes = []): static
    {
        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            array_merge(['value' => static::serializeValue($value)], $attributes)
        );

        static::clearCache($group, $key);

        return $setting;
    }

    /**
     * Cast value based on type
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            'image' => is_string($value) && $value !== '' && !str_starts_with($value, 'http')
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($value)
                : $value,
            default => $value,
        };
    }

    /**
     * Serialize value for storage
     */
    protected static function serializeValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * Clear cache for a setting
     */
    public static function clearCache(?string $group = null, ?string $key = null): void
    {
        if ($group && $key) {
            Cache::forget("settings.{$group}.{$key}");
        }

        if ($group) {
            Cache::forget("settings.group.{$group}.public");
            Cache::forget("settings.group.{$group}.all");
        }

        Cache::forget('settings.all.public');
    }
}
