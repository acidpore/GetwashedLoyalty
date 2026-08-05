<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'carwash_points',
        'carwash_claimed_points',
        'carwash_total_visits',
        'carwash_last_visit_at',
        'motorwash_points',
        'motorwash_claimed_points',
        'motorwash_total_visits',
        'motorwash_last_visit_at',
        'coffeeshop_points',
        'coffeeshop_claimed_points',
        'coffeeshop_total_visits',
        'coffeeshop_last_visit_at',
        'dashboard_token',
        'token_expires_at',
        'pin',
        'pin_set_at',
    ];

    protected $casts = [
        'carwash_last_visit_at' => 'datetime',
        'motorwash_last_visit_at' => 'datetime',
        'coffeeshop_last_visit_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'pin_set_at' => 'datetime',
        'carwash_points' => 'integer',
        'carwash_claimed_points' => 'integer',
        'carwash_total_visits' => 'integer',
        'motorwash_points' => 'integer',
        'motorwash_claimed_points' => 'integer',
        'motorwash_total_visits' => 'integer',
        'coffeeshop_points' => 'integer',
        'coffeeshop_claimed_points' => 'integer',
        'coffeeshop_total_visits' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::created(fn () => self::clearDashboardCache());
        static::updated(fn () => self::clearDashboardCache());
        static::deleted(fn () => self::clearDashboardCache());
    }

    public static function clearDashboardCache(): void
    {
        Cache::forget('dashboard_loyalty_stats');
        Cache::forget('dashboard_carwash_stats');
        Cache::forget('dashboard_coffeeshop_stats');
        Cache::forget('dashboard_motorwash_stats');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitHistories(): HasMany
    {
        return $this->hasMany(VisitHistory::class);
    }

    public function rewardClaims(): HasMany
    {
        return $this->hasMany(RewardClaim::class);
    }

    public function getPoints(string $loyaltyType): int
    {
        return match($loyaltyType) {
            'carwash' => $this->carwash_points,
            'motorwash' => $this->motorwash_points,
            'coffeeshop' => $this->coffeeshop_points,
            default => 0,
        };
    }

    /** Highest milestone already claimed in the running cycle. 0 = none yet. */
    public function claimedPoints(string $loyaltyType): int
    {
        return (int) ($this->{"{$loyaltyType}_claimed_points"} ?? 0);
    }

    /**
     * The milestone waiting to be claimed: the lowest tier the customer has
     * reached but not yet traded in. Points are frozen here until it is claimed.
     */
    public function earnedMilestone(string $loyaltyType): ?array
    {
        $points = $this->getPoints($loyaltyType);
        $claimed = $this->claimedPoints($loyaltyType);

        foreach (SystemSetting::milestones($loyaltyType) as $milestone) {
            if ($milestone['at'] > $claimed && $points >= $milestone['at']) {
                return $milestone;
            }
        }

        return null;
    }

    /** The tier the customer is currently working towards. */
    public function nextMilestone(string $loyaltyType): ?array
    {
        $points = $this->getPoints($loyaltyType);

        foreach (SystemSetting::milestones($loyaltyType) as $milestone) {
            if ($points < $milestone['at']) {
                return $milestone;
            }
        }

        return null;
    }

    /**
     * Ceiling for the running cycle: the next unclaimed milestone. Check-ins
     * stop adding points here, so the customer must claim before going further.
     */
    public function pointCap(string $loyaltyType): int
    {
        $claimed = $this->claimedPoints($loyaltyType);

        foreach (SystemSetting::milestones($loyaltyType) as $milestone) {
            if ($milestone['at'] > $claimed) {
                return $milestone['at'];
            }
        }

        return SystemSetting::maxPoints($loyaltyType);
    }

    public function hasReward(string $loyaltyType): bool
    {
        return $this->earnedMilestone($loyaltyType) !== null;
    }

    /** Points still needed for the next tier. 0 once a reward is claimable. */
    public function pointsUntilReward(string $loyaltyType): int
    {
        $next = $this->nextMilestone($loyaltyType);

        return $next ? max(0, $next['at'] - $this->getPoints($loyaltyType)) : 0;
    }

    /**
     * Trade in the pending reward and log it. Claiming a middle tier only
     * unlocks the next one — points keep running. Claiming the final tier ends
     * the cycle and resets everything to 0.
     *
     * Returns the claimed milestone, or null when there was nothing to claim.
     */
    public function claimReward(string $loyaltyType, ?int $claimedBy = null): ?array
    {
        return DB::transaction(function () use ($loyaltyType, $claimedBy) {
            $fresh = self::whereKey($this->getKey())->lockForUpdate()->first();
            $milestone = $fresh?->earnedMilestone($loyaltyType);

            if (! $milestone) {
                return null;
            }

            RewardClaim::create([
                'customer_id' => $fresh->id,
                'loyalty_type' => $loyaltyType,
                'points_used' => $milestone['at'],
                'reward' => $milestone['reward'],
                'claimed_by' => $claimedBy,
                'claimed_at' => now(),
            ]);

            $isFinalTier = $milestone['at'] >= SystemSetting::maxPoints($loyaltyType);

            $fresh->update($isFinalTier
                ? ["{$loyaltyType}_points" => 0, "{$loyaltyType}_claimed_points" => 0]
                : ["{$loyaltyType}_claimed_points" => $milestone['at']]);

            $this->refresh();

            return $milestone;
        });
    }

    public function addPoints(string $loyaltyType, int $points = 1): void
    {
        if (! in_array($loyaltyType, SystemSetting::LOYALTY_TYPES, true)) {
            return;
        }

        $current = $this->getPoints($loyaltyType);

        // Customers already above the cap (config changed under them) must never
        // lose points here — the cap only stops further accrual.
        $newPoints = max($current, min($current + $points, $this->pointCap($loyaltyType)));

        $this->update([
            "{$loyaltyType}_points" => $newPoints,
            "{$loyaltyType}_last_visit_at" => now(),
        ]);
        $this->increment("{$loyaltyType}_total_visits");
    }

    /** Ends the cycle outright, without logging a claim. */
    public function resetPoints(string $loyaltyType): void
    {
        if (! in_array($loyaltyType, SystemSetting::LOYALTY_TYPES, true)) {
            return;
        }

        $this->update([
            "{$loyaltyType}_points" => 0,
            "{$loyaltyType}_claimed_points" => 0,
        ]);
    }

    public function generateMagicLink(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        
        $this->update([
            'dashboard_token' => $token,
            'token_expires_at' => now()->addDays(7),
        ]);
        
        return route('customer.magic.login', ['token' => $token]);
    }

    public function hasPin(): bool
    {
        return !empty($this->pin);
    }

    public function setPin(string $pin): void
    {
        $this->update([
            'pin' => $pin,
            'pin_set_at' => now(),
        ]);
    }

    public function verifyPin(string $pin): bool
    {
        return $this->pin === $pin;
    }
}
