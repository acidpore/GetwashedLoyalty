<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashShift extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'opening_cash', 'opened_at', 'expected_cash', 'closing_cash', 'closed_at', 'note'];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shift_id');
    }

    public static function openFor(int $userId): ?self
    {
        return self::where('user_id', $userId)->whereNull('closed_at')->latest('opened_at')->first();
    }

    /** Opening float plus every cash sale that was not voided. */
    public function expectedCash(): int
    {
        return $this->opening_cash + (int) $this->orders()->paid()->where('payment_method', 'cash')->sum('total');
    }

    public function difference(): ?int
    {
        return $this->closing_cash === null ? null : $this->closing_cash - (int) $this->expected_cash;
    }

    public function close(int $counted, ?string $note): void
    {
        $this->update([
            'expected_cash' => $this->expectedCash(),
            'closing_cash' => $counted,
            'closed_at' => now(),
            'note' => $note,
        ]);
    }
}
