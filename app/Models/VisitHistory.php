<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'points_earned',
        'visited_at',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'visited_at' => 'datetime',
        'points_earned' => 'integer',
    ];

    /**
     * Get the customer that owns this visit history.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Create a new visit record for a customer.
     */
    public static function recordVisit(Customer $customer, int $points = 1, ?string $ipAddress = null): self
    {
        return self::create([
            'customer_id' => $customer->id,
            'points_earned' => $points,
            'visited_at' => now(),
            'ip_address' => $ipAddress ?? request()->ip(),
        ]);
    }
}
