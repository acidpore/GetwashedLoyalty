<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'carwash_points',
        'carwash_total_visits',
        'carwash_last_visit_at',
        'coffeeshop_points',
        'coffeeshop_total_visits',
        'coffeeshop_last_visit_at',
    ];

    protected $casts = [
        'carwash_last_visit_at' => 'datetime',
        'coffeeshop_last_visit_at' => 'datetime',
        'carwash_points' => 'integer',
        'carwash_total_visits' => 'integer',
        'coffeeshop_points' => 'integer',
        'coffeeshop_total_visits' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitHistories(): HasMany
    {
        return $this->hasMany(VisitHistory::class);
    }

    public function getPoints(string $loyaltyType): int
    {
        return match($loyaltyType) {
            'carwash' => $this->carwash_points,
            'coffeeshop' => $this->coffeeshop_points,
            default => 0,
        };
    }

    public function hasReward(string $loyaltyType): bool
    {
        $threshold = match($loyaltyType) {
            'carwash' => SystemSetting::get('carwash_reward_threshold', 5),
            'coffeeshop' => SystemSetting::get('coffeeshop_reward_threshold', 5),
            default => 5,
        };

        return $this->getPoints($loyaltyType) >= $threshold;
    }

    public function pointsUntilReward(string $loyaltyType): int
    {
        $threshold = match($loyaltyType) {
            'carwash' => SystemSetting::get('carwash_reward_threshold', 5),
            'coffeeshop' => SystemSetting::get('coffeeshop_reward_threshold', 5),
            default => 5,
        };

        return max(0, $threshold - $this->getPoints($loyaltyType));
    }

    public function addPoints(string $loyaltyType, int $points = 1): void
    {
        match($loyaltyType) {
            'carwash' => $this->addCarwashPoints($points),
            'coffeeshop' => $this->addCoffeeshopPoints($points),
            default => null,
        };
    }

    public function resetPoints(string $loyaltyType): void
    {
        match($loyaltyType) {
            'carwash' => $this->update(['carwash_points' => 0]),
            'coffeeshop' => $this->update(['coffeeshop_points' => 0]),
            default => null,
        };
    }

    private function addCarwashPoints(int $points = 1): void
    {
        $this->increment('carwash_points', $points);
        $this->increment('carwash_total_visits');
        $this->update(['carwash_last_visit_at' => now()]);
    }

    private function addCoffeeshopPoints(int $points = 1): void
    {
        $this->increment('coffeeshop_points', $points);
        $this->increment('coffeeshop_total_visits');
        $this->update(['coffeeshop_last_visit_at' => now()]);
    }
}

