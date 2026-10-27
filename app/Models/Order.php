<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Order extends Model
{
    public const METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
    ];

    protected $fillable = [
        'number', 'customer_id', 'cashier_id', 'shift_id', 'subtotal', 'discount', 'promo_name', 'total',
        'payment_method', 'paid', 'change', 'points_earned', 'reward', 'reward_claim_id', 'visit_history_id',
        'loyalty_before', 'voided_at', 'voided_by', 'void_reason',
    ];

    protected $casts = [
        'loyalty_before' => 'array',
        'voided_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopePaid(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    /**
     * Records a sale. Prices, option surcharges and promos always come from the
     * database, never from the browser. With a customer attached the sale counts
     * as a coffeeshop visit (1 point), after redeeming their pending reward if asked.
     *
     * @param  array<int, array{id:int, qty:int, options?:array<int, array{0:int,1:int}>, note?:string}>  $lines
     * @param  array{promo_id?:int|null, amount?:int|null}  $discount
     */
    public static function checkout(array $lines, string $method, int $paid, ?int $customerId, bool $redeem, ?int $cashierId, array $discount = [], ?int $shiftId = null): self
    {
        if (! isset(self::METHODS[$method])) {
            throw new InvalidArgumentException('Metode bayar tidak dikenal.');
        }

        $products = Product::with('ingredients')->where('is_active', true)
            ->whereKey(collect($lines)->pluck('id'))->get()->keyBy('id');

        $items = collect($lines)
            ->filter(fn ($line) => isset($products[(int) ($line['id'] ?? 0)]) && (int) ($line['qty'] ?? 0) > 0)
            ->map(function ($line) use ($products) {
                $product = $products[(int) $line['id']];
                [$label, $extra] = $product->resolveOptions($line['options'] ?? []);

                return [
                    'product' => $product,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price + $extra,
                    'qty' => min(99, (int) $line['qty']),
                    'options' => $label ?: null,
                    'note' => mb_substr(trim((string) ($line['note'] ?? '')), 0, 120) ?: null,
                ];
            })
            ->values();

        if ($items->isEmpty() && ! $redeem) {
            throw new InvalidArgumentException('Keranjang masih kosong.');
        }

        $subtotal = $items->sum(fn ($item) => $item['price'] * $item['qty']);

        $promo = ! empty($discount['promo_id']) ? Promo::where('is_active', true)->find($discount['promo_id']) : null;
        $cut = $promo ? $promo->discountFor($subtotal) : max(0, (int) ($discount['amount'] ?? 0));
        $cut = min($cut, $subtotal);
        $total = $subtotal - $cut;

        // Non-cash payments are always exact.
        $paid = $method === 'cash' ? $paid : $total;

        if ($paid < $total) {
            throw new InvalidArgumentException('Uang yang dibayar kurang.');
        }

        return DB::transaction(function () use ($items, $subtotal, $cut, $promo, $total, $method, $paid, $customerId, $redeem, $cashierId, $shiftId) {
            $customer = $customerId ? Customer::whereKey($customerId)->lockForUpdate()->first() : null;
            $before = $customer?->only('coffeeshop_points', 'coffeeshop_claimed_points', 'coffeeshop_total_visits');
            $reward = $customer && $redeem ? $customer->claimReward('coffeeshop', $cashierId) : null;

            $order = self::create([
                'number' => 'L' . now()->format('ymd') . '-' . str_pad((string) (self::whereDate('created_at', today())->lockForUpdate()->count() + 1), 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer?->id,
                'cashier_id' => $cashierId,
                'shift_id' => $shiftId,
                'subtotal' => $subtotal,
                'discount' => $cut,
                'promo_name' => $promo?->name ?? ($cut ? 'Diskon manual' : null),
                'total' => $total,
                'payment_method' => $method,
                'paid' => $paid,
                'change' => $paid - $total,
                'reward' => $reward['reward'] ?? null,
                'reward_claim_id' => $reward ? RewardClaim::where('customer_id', $customer->id)->latest('id')->value('id') : null,
                'loyalty_before' => $before,
            ]);

            $order->items()->createMany($items->map(fn ($item) => collect($item)->except('product')->all())->all());

            if ($reward) {
                $order->items()->create(['name' => $reward['reward'], 'price' => 0, 'qty' => 1, 'note' => 'Hadiah loyalty']);
            }

            foreach ($items as $item) {
                $item['product']->consumeStock($item['qty']);
            }

            if ($customer) {
                $earned = $customer->addPoints('coffeeshop');

                $visit = VisitHistory::create([
                    'customer_id' => $customer->id,
                    'loyalty_types' => ['coffeeshop'],
                    'points_earned' => $earned,
                    'visited_at' => now(),
                    'ip_address' => request()->ip(),
                ]);

                $order->update(['points_earned' => $earned, 'visit_history_id' => $visit->id]);
            }

            return $order;
        });
    }

    /**
     * Cancels the sale: stock goes back, and the customer's loyalty is rolled
     * back to how it was. If they already had a later coffeeshop visit, only
     * this sale's point comes off, so later activity is never undone.
     */
    public function void(string $reason, ?int $userId): void
    {
        if ($this->voided_at) {
            return;
        }

        DB::transaction(function () use ($reason, $userId) {
            $this->update(['voided_at' => now(), 'voided_by' => $userId, 'void_reason' => $reason]);

            foreach ($this->items()->with('product.ingredients')->get() as $item) {
                $item->product?->consumeStock(-$item->qty);
            }

            $customer = $this->customer_id ? Customer::whereKey($this->customer_id)->lockForUpdate()->first() : null;

            if (! $customer) {
                return;
            }

            $laterVisit = VisitHistory::where('customer_id', $customer->id)
                ->where('id', '>', (int) $this->visit_history_id)
                ->whereJsonContains('loyalty_types', 'coffeeshop')
                ->exists();

            if (! $laterVisit && $this->loyalty_before) {
                $customer->update($this->loyalty_before);
                RewardClaim::whereKey($this->reward_claim_id)->delete();
            } else {
                $customer->update([
                    'coffeeshop_points' => max(0, $customer->coffeeshop_points - $this->points_earned),
                    'coffeeshop_total_visits' => max(0, $customer->coffeeshop_total_visits - 1),
                ]);
            }

            VisitHistory::whereKey($this->visit_history_id)->delete();
        });
    }

    public function receiptText(): string
    {
        $rp = fn (int $n) => 'Rp' . number_format($n, 0, ',', '.');
        $lines = ["*Getwashed Latte*", $this->number . ' · ' . $this->created_at->format('d/m/Y H:i'), ''];

        foreach ($this->items as $item) {
            $lines[] = $item->qty . 'x ' . $item->name . ($item->options ? " ({$item->options})" : '') . ' — ' . ($item->price ? $rp($item->price * $item->qty) : 'Gratis');
        }

        $lines[] = '';
        if ($this->discount) {
            $lines[] = 'Subtotal: ' . $rp($this->subtotal);
            $lines[] = 'Diskon: -' . $rp($this->discount);
        }
        $lines[] = '*Total: ' . $rp($this->total) . '*';
        $lines[] = self::METHODS[$this->payment_method] . ': ' . $rp($this->paid);
        if ($this->change) {
            $lines[] = 'Kembali: ' . $rp($this->change);
        }
        if ($this->customer) {
            $lines[] = '';
            $lines[] = 'Poin coffee kamu: ' . $this->customer->coffeeshop_points;
        }
        $lines[] = '';
        $lines[] = 'Terima kasih sudah mampir!';

        return implode("\n", $lines);
    }
}
