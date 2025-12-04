<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
                $setting = self::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
        } catch (\Exception $e) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value, ?string $description = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );

        Cache::forget("setting_{$key}");

        return $setting;
    }

    public static function rewardPointsThreshold(): int
    {
        return (int) self::get('reward_points_threshold', 5);
    }

    public static function carwashRewardThreshold(): int
    {
        return (int) self::get('carwash_reward_threshold', 5);
    }

    public static function coffeeshopRewardThreshold(): int
    {
        return (int) self::get('coffeeshop_reward_threshold', 5);
    }

    public static function carwashRewardMessage(): string
    {
        return self::get('carwash_reward_message', 'DISKON CAR WASH');
    }

    public static function coffeeshopRewardMessage(): string
    {
        return self::get('coffeeshop_reward_message', 'GRATIS KOPI');
    }

    public static function clearCache(): void
    {
        Cache::flush();
    }
}
