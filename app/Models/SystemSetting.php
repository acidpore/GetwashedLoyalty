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

    private static array $runtimeCache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$runtimeCache)) {
            return self::$runtimeCache[$key];
        }

        try {
            $value = Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
                $setting = self::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
            self::$runtimeCache[$key] = $value;
            return $value;
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

        unset(self::$runtimeCache[$key]);
        Cache::forget("setting_{$key}");

        return $setting;
    }

    public const LOYALTY_TYPES = ['carwash', 'motorwash', 'coffeeshop'];

    /**
     * Milestone tiers for a loyalty type, sorted ascending and de-duplicated:
     * [['at' => 10, 'reward' => 'Gratis Cuci Mobil'], ['at' => 20, ...]]
     *
     * A customer earns a tier when their points reach its `at`. The last tier
     * is the max point cap; claiming resets points to 0.
     */
    public static function milestones(string $loyaltyType): array
    {
        $raw = self::get("{$loyaltyType}_milestones");
        $rows = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($rows) || $rows === []) {
            // ponytail: not configured yet = single tier at the old threshold, i.e. the pre-milestone behaviour
            $rows = [[
                'at' => (int) self::get("{$loyaltyType}_reward_threshold", 5),
                'reward' => self::get("{$loyaltyType}_reward_message", 'HADIAH'),
            ]];
        }

        return self::normalizeMilestones($rows);
    }

    /** Sorted ascending, keyed-out duplicates, junk rows dropped. */
    public static function normalizeMilestones(array $rows): array
    {
        $clean = [];

        foreach ($rows as $row) {
            $at = (int) ($row['at'] ?? 0);

            if ($at < 1) {
                continue;
            }

            $clean[$at] = [
                'at' => $at,
                'reward' => trim((string) ($row['reward'] ?? '')) ?: 'HADIAH',
            ];
        }

        ksort($clean);

        return array_values($clean);
    }

    /** Point cap = the last milestone. Points stop accruing here until claimed. */
    public static function maxPoints(string $loyaltyType): int
    {
        $milestones = self::milestones($loyaltyType);

        return (int) (end($milestones)['at'] ?? 5);
    }

    /** Points needed for the very first reward — "sudah bisa tukar" starts here. */
    public static function firstMilestonePoints(string $loyaltyType): int
    {
        return (int) (self::milestones($loyaltyType)[0]['at'] ?? 5);
    }

    public static function rewardPointsThreshold(): int
    {
        return (int) self::get('reward_points_threshold', 5);
    }

    public static function carwashRewardThreshold(): int
    {
        return self::maxPoints('carwash');
    }

    public static function coffeeshopRewardThreshold(): int
    {
        return self::maxPoints('coffeeshop');
    }

    public static function carwashRewardMessage(): string
    {
        return self::get('carwash_reward_message', 'DISKON CAR WASH');
    }

    public static function coffeeshopRewardMessage(): string
    {
        return self::get('coffeeshop_reward_message', 'GRATIS KOPI');
    }

    public static function motorwashRewardThreshold(): int
    {
        return self::maxPoints('motorwash');
    }

    public static function motorwashRewardMessage(): string
    {
        return self::get('motorwash_reward_message', 'DISKON CUCI MOTOR');
    }

    public static function clearCache(): void
    {
        self::$runtimeCache = [];
        Cache::flush();
    }
}
