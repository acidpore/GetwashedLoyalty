<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'loyalty_type',
        'points_earned',
        'visited_at',
        'ip_address',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'loyalty_type' => 'string',
        'points_earned' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeCarwash(Builder $query): void
    {
        $query->whereIn('loyalty_type', ['carwash', 'both']);
    }

    public function scopeCoffeeshop(Builder $query): void
    {
        $query->whereIn('loyalty_type', ['coffeeshop', 'both']);
    }

    public function scopeByLoyaltyType(Builder $query, string $type): void
    {
        if ($type === 'both') {
            $query->where('loyalty_type', 'both');
        } else {
            $query->whereIn('loyalty_type', [$type, 'both']);
        }
    }
}
