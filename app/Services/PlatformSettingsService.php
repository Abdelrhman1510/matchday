<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

class PlatformSettingsService
{
    protected const CACHE_PREFIX = 'platform_setting_';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key
     */
    public function get(string $key, $default = null)
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            function () use ($key, $default) {
                $setting = PlatformSetting::where('key', $key)->first();
                return $setting ? $setting->getTypedValue() : $default;
            }
        );
    }

    /**
     * Set a setting value by key
     */
    public function set(string $key, $value, string $type = 'string', string $group = null): void
    {
        // Convert value to string for storage
        $storedValue = match($type) {
            'bool' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        PlatformSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
            ]
        );

        // Bust the cache
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Get all settings in a group
     */
    public function getGroup(string $group): array
    {
        $cacheKey = self::CACHE_PREFIX . 'group_' . $group;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($group) {
                $settings = PlatformSetting::where('group', $group)->get();
                
                $result = [];
                foreach ($settings as $setting) {
                    $result[$setting->key] = $setting->getTypedValue();
                }
                
                return $result;
            }
        );
    }

    /**
     * Clear all settings cache
     */
    public function clearCache(): void
    {
        $settings = PlatformSetting::all();
        
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }

        // Clear group caches
        $groups = PlatformSetting::distinct('group')->pluck('group');
        foreach ($groups as $group) {
            Cache::forget(self::CACHE_PREFIX . 'group_' . $group);
        }
    }

    /**
     * Get multiple settings by keys
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }
}
