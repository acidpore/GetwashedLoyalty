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
        $total = ['cash' => 0, 'atm' => 0];
        foreach (static::sharesBetween($from, $to) as [$method, , $amount]) {
            $total[$method] += $amount;
        }

        return $total + ['all' => $total['cash'] + $total['atm']];
    }

    /** What each day carries, keyed Y-m-d, for the daily recap. */
    public static function chargedByDay(CarbonInterface $from, CarbonInterface $to): array
    {
        $days = [];
        foreach (static::sharesBetween($from, $to) as [, $date, $amount]) {
            $days[$date] = ($days[$date] ?? 0) + $amount;
        }

        return $days;
    }

    /** @return array<int, array{0: string, 1: string, 2: int}> [method, Y-m-d, amount] */
    private static function sharesBetween(CarbonInterface $from, CarbonInterface $to): array
    {
        $longest = (int) static::max('spread_weeks') ?: 1;
        $out = [];

        static::whereBetween('paid_on', [$from->copy()->subWeeks($longest)->toDateString(), $to->toDateString()])->get()
            ->each(function (Expense $expense) use ($from, $to, &$out) {
                foreach ($expense->shares() as $share) {
                    if ($share['date']->between($from->copy()->startOfDay(), $to)) {
                        $out[] = [$expense->method, $share['date']->toDateString(), $share['amount']];
                    }
                }
            });

        return $out;
    }
}
