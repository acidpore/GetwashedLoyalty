<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardClaim extends Model
{
    protected $fillable = [
        'customer_id',
        'loyalty_type',
        'points_used',
        'reward',
        'claimed_by',
        'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'points_used' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }
}
