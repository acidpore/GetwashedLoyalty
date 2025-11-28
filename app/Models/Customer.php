<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'current_points',
        'total_visits',
        'last_visit_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_visit_at' => 'datetime',
        'current_points' => 'integer',
        'total_visits' => 'integer',
    ];

    /**
     * Get the user that owns the customer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all visit histories for this customer.
     */
    public function visitHistories(): HasMany
    {
        return $this->hasMany(VisitHistory::class);
    }

    /**
     * Check if customer has earned a reward (5 points).
     */
    public function hasEarnedReward(): bool
    {
        return $this->current_points >= 5;
    }

    /**
     * Get points remaining until next reward.
     */
    public function pointsUntilReward(): int
    {
        return max(0, 5 - $this->current_points);
    }

    /**
     * Add points and update visit count.
     */
    public function addPoints(int $points = 1): void
    {
        $this->increment('current_points', $points);
        $this->increment('total_visits');
        $this->update(['last_visit_at' => now()]);
    }

    /**
     * Reset points after reward redemption.
     */
    public function resetPoints(): void
    {
        $this->update(['current_points' => 0]);
    }
}
