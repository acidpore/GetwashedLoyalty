<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class VisitHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'loyalty_types',
        'points_earned',
        'visited_at',
        'ip_address',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'loyalty_types' => 'array',
        'points_earned' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::created(fn () => Cache::forget('dashboard_loyalty_stats'));
        static::deleted(fn () => Cache::forget('dashboard_loyalty_stats'));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeCarwash(Builder $query): void
    {
        $query->whereJsonContains('loyalty_types', 'carwash');
    }

    public function scopeMotorwash(Builder $query): void
    {
        $query->whereJsonContains('loyalty_types', 'motorwash');
    }

    public function scopeCoffeeshop(Builder $query): void
    {
        $query->whereJsonContains('loyalty_types', 'coffeeshop');
    }

    public function scopeByLoyaltyType(Builder $query, string $type): void
    {
        $query->whereJsonContains('loyalty_types', $type);
    }
}
