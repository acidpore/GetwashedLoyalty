<?php

namespace App\Models;

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

    protected $fillable = ['number', 'customer_id', 'cashier_id', 'total', 'payment_method', 'paid', 'change', 'points_earned', 'reward'];

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

    /**
     * Records a sale. Prices always come from the products table, never from
     * the browser. With a customer attached the sale counts as a coffeeshop
     * visit (1 point), after redeeming their pending reward if asked.
     *
     * @param  array<int, array{id:int, qty:int}>  $lines
     */
    public static function checkout(array $lines, string $method, int $paid, ?int $customerId, bool $redeem, ?int $cashierId): self
    {
        if (! isset(self::METHODS[$method])) {
            throw new InvalidArgumentException('Metode bayar tidak dikenal.');
        }

        $qty = collect($lines)
            ->filter(fn ($line) => (int) ($line['qty'] ?? 0) > 0)
            ->mapWithKeys(fn ($line) => [(int) $line['id'] => min(99, (int) $line['qty'])]);

        $products = Product::where('is_active', true)->whereKey($qty->keys())->get();

        if ($products->isEmpty() && ! $redeem) {
            throw new InvalidArgumentException('Keranjang masih kosong.');
        }

        $total = $products->sum(fn (Product $product) => $product->price * $qty[$product->id]);

        // Non-cash payments are always exact.
        $paid = $method === 'cash' ? $paid : $total;

        if ($paid < $total) {
            throw new InvalidArgumentException('Uang yang dibayar kurang.');
        }

        return DB::transaction(function () use ($products, $qty, $total, $method, $paid, $customerId, $redeem, $cashierId) {
            $customer = $customerId ? Customer::find($customerId) : null;
            $reward = $customer && $redeem ? $customer->claimReward('coffeeshop', $cashierId) : null;

            $order = self::create([
                'number' => 'L' . now()->format('ymd') . '-' . str_pad((string) (self::whereDate('created_at', today())->lockForUpdate()->count() + 1), 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer?->id,
                'cashier_id' => $cashierId,
                'total' => $total,
                'payment_method' => $method,
                'paid' => $paid,
                'change' => $paid - $total,
                'reward' => $reward['reward'] ?? null,
            ]);

            $order->items()->createMany($products->map(fn (Product $product) => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => $qty[$product->id],
            ])->all());

            if ($reward) {
                $order->items()->create(['name' => $reward['reward'], 'price' => 0, 'qty' => 1, 'note' => 'Hadiah loyalty']);
            }

            if ($customer) {
                $earned = $customer->addPoints('coffeeshop');
                $order->update(['points_earned' => $earned]);

                VisitHistory::create([
                    'customer_id' => $customer->id,
                    'loyalty_types' => ['coffeeshop'],
                    'points_earned' => $earned,
                    'visited_at' => now(),
                    'ip_address' => request()->ip(),
                ]);
            }

            return $order;
        });
    }
}
