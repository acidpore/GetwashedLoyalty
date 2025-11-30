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
        'current_points',
        'total_visits',
        'last_visit_at',
    ];

    protected $casts = [
        'last_visit_at' => 'datetime',
        'current_points' => 'integer',
        'total_visits' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitHistories(): HasMany
    {
        return $this->hasMany(VisitHistory::class);
    }

    public function hasEarnedReward(): bool
    {
        return $this->current_points >= SystemSetting::rewardPointsThreshold();
    }

    public function pointsUntilReward(): int
    {
        return max(0, SystemSetting::rewardPointsThreshold() - $this->current_points);
    }

    public function addPoints(int $points = 1): void
    {
        $this->increment('current_points', $points);
        $this->increment('total_visits');
        $this->update(['last_visit_at' => now()]);
    }

    public function resetPoints(): void
    {
        $this->update(['current_points' => 0]);
    }
}
