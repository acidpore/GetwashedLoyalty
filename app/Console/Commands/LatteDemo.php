<?php

namespace App\Console\Commands;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\IngredientMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promo;
use App\Models\RewardClaim;
use App\Models\User;
use App\Models\VisitHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills the Latte POS with 60 days of fake sales so the client can try the
 * dashboard and reports. Everything it makes is recognisable (orders "D…",
 * customers 62800000…, demo@ cashier) so --clear removes exactly that.
 * Real customers are never touched.
 */
class LatteDemo extends Command
{
    protected $signature = 'latte:demo {--clear : Hapus data demo saja}';

    protected $description = 'Isi / hapus data demo POS Getwashed Latte';

    private const MENU = [
        ['Espresso', 'kopi', 18000], ['Americano', 'kopi', 22000], ['Cafe Latte', 'kopi', 28000], ['Cappuccino', 'kopi', 28000],
        ['Kopi Susu Aren', 'kopi', 25000], ['Caramel Macchiato', 'kopi', 32000], ['Matcha Latte', 'non-kopi', 28000],
        ['Chocolate', 'non-kopi', 25000], ['Lemon Tea', 'non-kopi', 18000], ['Croissant', 'makanan', 22000],
        ['Banana Bread', 'makanan', 20000], ['French Fries', 'makanan', 25000],
    ];

    private const INGREDIENTS = [
        ['Biji kopi', 'gram', 18], ['Susu', 'ml', 150], ['Gula aren', 'ml', 20], ['Bubuk matcha', 'gram', 10],
        ['Bubuk cokelat', 'gram', 25], ['Cup 12oz', 'pcs', 1],
    ];

    private const NAMES = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eka', 'Fajar', 'Gita', 'Hana', 'Indra', 'Joko', 'Kirana', 'Lina', 'Maya', 'Nanda', 'Oki'];

    public function handle(): int
    {
        $this->clear();
        if ($this->option('clear')) {
            $this->info('Data demo dihapus.');

            return self::SUCCESS;
        }

        $cashier = User::create(['name' => 'Kasir Demo', 'email' => 'demo@getwashedxlatte.com', 'password' => 'demo12345', 'role' => 'cashier']);

        $ingredients = collect(self::INGREDIENTS)->map(fn ($i) => Ingredient::create(['name' => $i[0] . ' (demo)', 'unit' => $i[1], 'stock' => 0, 'min_stock' => $i[2] * 20]));
        foreach (self::MENU as $n => [$name, $category, $price]) {
            $product = Product::firstOrCreate(['name' => $name], ['category' => $category, 'price' => $price, 'sort' => $n, 'is_active' => true]);
            if ($category !== 'makanan' && empty($product->options)) {
                $product->update(['options' => [
                    ['name' => 'Suhu', 'multiple' => false, 'choices' => [['name' => 'Ice', 'price' => 0], ['name' => 'Hot', 'price' => 0]]],
                    ['name' => 'Tambahan', 'multiple' => true, 'choices' => [['name' => 'Extra shot', 'price' => 5000], ['name' => 'Oat milk', 'price' => 6000]]],
                ]]);
            }
            $recipe = match ($category) {
                'kopi' => [0 => 18, 1 => str_contains($name, 'Americano') || $name === 'Espresso' ? 0 : 150, 2 => $name === 'Kopi Susu Aren' ? 20 : 0, 5 => 1],
                'non-kopi' => [1 => 150, 3 => $name === 'Matcha Latte' ? 10 : 0, 4 => $name === 'Chocolate' ? 25 : 0, 5 => 1],
                default => [],
            };
            foreach (array_filter($recipe) as $i => $qty) {
                $product->recipe()->create(['ingredient_id' => $ingredients[$i]->id, 'qty' => $qty]);
            }
        }
        // Enough for the sales below, with two items left near their minimum for the low-stock warning.
        $ingredients->each(fn ($i, $k) => $i->adjust($k < 4 ? 100000 : 5000, 'Stok awal demo', null));
        $promos = collect([['Happy Hour 20%', 'percent', 20], ['Potongan 5rb', 'amount', 5000]])
            ->map(fn ($p) => Promo::create(['name' => $p[0] . ' (demo)', 'type' => $p[1], 'value' => $p[2], 'is_active' => true]));

        $customers = collect(self::NAMES)->map(function ($name, $n) {
            $user = User::create(['name' => $name . ' (demo)', 'phone' => '62800000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'role' => 'customer', 'password' => null]);

            return Customer::create(['user_id' => $user->id, 'carwash_points' => 0, 'carwash_total_visits' => 0, 'motorwash_points' => 0, 'motorwash_total_visits' => 0, 'coffeeshop_points' => 0, 'coffeeshop_total_visits' => 0]);
        });

        $products = Product::where('is_active', true)->get();
        mt_srand(2026);
        $seq = 0;
        for ($d = 59; $d >= 0; $d--) {
            $day = today()->subDays($d);
            $shift = CashShift::create(['user_id' => $cashier->id, 'opening_cash' => 200000, 'opened_at' => $day->copy()->setTime(7, 55)]);
            $busy = in_array($day->dayOfWeekIso, [5, 6, 7]) ? 6 : 0;
            for ($n = 0, $count = mt_rand(6, 14) + $busy; $n < $count; $n++) {
                $lines = collect(range(1, mt_rand(1, 3)))->map(function () use ($products) {
                    $p = $products->random();

                    return ['id' => $p->id, 'qty' => mt_rand(1, 2), 'options' => $p->options ? [[0, mt_rand(0, 1)]] : [], 'note' => mt_rand(0, 6) ? '' : 'less sugar'];
                })->all();
                $member = mt_rand(0, 9) < 4 ? $customers->random() : null;
                $discount = mt_rand(0, 9) ? [] : ['promo_id' => $promos->random()->id];
                $order = Order::checkout($lines, ['cash', 'cash', 'qris', 'qris', 'transfer'][mt_rand(0, 4)], 1000000, $member?->id, false, $cashier->id, $discount, $shift->id);
                $hour = [8, 8, 9, 10, 11, 12, 12, 13, 15, 16, 16, 17, 19, 20][mt_rand(0, 13)];
                $order->forceFill(['number' => 'D' . $day->format('ymd') . '-' . str_pad((string) ++$seq, 4, '0', STR_PAD_LEFT), 'created_at' => $day->copy()->setTime($hour, mt_rand(0, 59)), 'paid' => $order->total, 'change' => 0])->save();
                if (! mt_rand(0, 40)) {
                    $order->void('Salah input menu', $cashier->id);
                }
            }
            if ($d > 0) {
                $shift->close($shift->expectedCash() + [0, 0, 0, 0, -2000, 5000, -10000][mt_rand(0, 6)], null);
                $shift->update(['closed_at' => $day->copy()->setTime(21, 5)]);
            }
        }

        $this->info('Data demo dibuat. Login kasir demo: demo@getwashedxlatte.com / demo12345');

        return self::SUCCESS;
    }

    private function clear(): void
    {
        DB::transaction(function () {
            $customerIds = Customer::whereHas('user', fn ($q) => $q->where('phone', 'like', '62800000%'))->pluck('id');
            $orderIds = Order::where('number', 'like', 'D%')->pluck('id');
            OrderItem::whereIn('order_id', $orderIds)->delete();
            Order::whereIn('id', $orderIds)->delete();
            VisitHistory::whereIn('customer_id', $customerIds)->delete();
            RewardClaim::whereIn('customer_id', $customerIds)->delete();
            $userIds = Customer::whereIn('id', $customerIds)->pluck('user_id');
            Customer::whereIn('id', $customerIds)->delete();
            User::whereIn('id', $userIds)->delete();
            if ($cashier = User::where('email', 'demo@getwashedxlatte.com')->first()) {
                CashShift::where('user_id', $cashier->id)->delete();
                $cashier->delete();
            }
            $ingredientIds = Ingredient::where('name', 'like', '%(demo)')->pluck('id');
            IngredientMovement::whereIn('ingredient_id', $ingredientIds)->delete();
            DB::table('ingredient_product')->whereIn('ingredient_id', $ingredientIds)->delete();
            Ingredient::whereIn('id', $ingredientIds)->delete();
            Promo::where('name', 'like', '%(demo)')->delete();
            // ponytail: menu stays after --clear; the client will likely edit it into their real menu.
        });
    }
}
