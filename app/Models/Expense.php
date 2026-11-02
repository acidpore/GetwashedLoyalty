<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const METHODS = ['cash' => 'Tunai', 'atm' => 'ATM'];

    protected $fillable = ['paid_on', 'name', 'amount', 'method', 'spread_weeks', 'note', 'user_id'];

    protected $casts = [
        'paid_on' => 'date',
        'amount' => 'integer',
        'spread_weeks' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Owners book big buys (milk on ATM) over several weeks so one slow week does not
     * carry the whole purchase. Week k carries amount / weeks from paid_on + 7k days;
     * the last week takes the rounding remainder so the shares add up exactly.
     *
     * @return array<int, array{date: CarbonInterface, amount: int}>
     */
    public function shares(): array
    {
        $weeks = max(1, $this->spread_weeks);
        $base = intdiv($this->amount, $weeks);

        return array_map(fn ($k) => [
            'date' => $this->paid_on->copy()->addWeeks($k),
            'amount' => $k === $weeks - 1 ? $this->amount - $base * ($weeks - 1) : $base,
        ], range(0, $weeks - 1));
    }

    /** What a period carries, split by how it was paid. */
    public static function chargedBetween(CarbonInterface $from, CarbonInterface $to): array
    {
        $longest = (int) static::max('spread_weeks') ?: 1;
        $total = ['cash' => 0, 'atm' => 0];

        static::whereBetween('paid_on', [$from->copy()->subWeeks($longest)->toDateString(), $to->toDateString()])->get()
            ->each(function (Expense $expense) use ($from, $to, &$total) {
                foreach ($expense->shares() as $share) {
                    if ($share['date']->between($from->copy()->startOfDay(), $to)) {
                        $total[$expense->method] += $share['amount'];
                    }
                }
            });

        return $total + ['all' => $total['cash'] + $total['atm']];
    }
}
