<?php

namespace App\Filament\Kasir\Pages;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promo;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\WhatsAppService;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use LivewireAttributesRenderless;

/**
 * The cart lives in Alpine on the client so tapping items is instant;
 * the server only answers customer lookups and records the final sale.
 * Selling needs an open cash shift so the drawer can be reconciled later.
 */
class Pos extends Page
{
    protected static ?string $slug = 'kasir';

    protected static ?string $title = 'Kasir';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.kasir.pos';

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getViewData(): array
    {
        return [
            'products' => Product::where('is_active', true)->orderBy('sort')->orderBy('name')->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id, 'name' => $p->name, 'category' => $p->category, 'price' => $p->price,
                    'image' => $p->imageUrl() ?? asset('img/menu-' . (isset(Product::CATEGORIES[$p->category]) ? $p->category : 'lainnya') . '.jpg'), 'options' => array_values($p->options ?? []),
                ]),
            'categories' => Product::CATEGORIES,
            'methods' => Order::METHODS,
            'promos' => Promo::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Promo $p) => ['id' => $p->id, 'name' => $p->name, 'type' => $p->type, 'value' => $p->value, 'label' => $p->label()]),
            'shift' => $this->shiftCard(),
        ];
    }

    private function shiftCard(): ?array
    {
        $shift = CashShift::openFor(auth()->id());

        return $shift ? ['id' => $shift->id, 'opening' => $shift->opening_cash, 'opened' => $shift->opened_at->format('H:i')] : null;
    }

    #[Renderless] // Alpine owns the till state; a re-render would reset it.
    public function openShift(int $opening): array
    {
        CashShift::openFor(auth()->id()) ?? CashShift::create(['user_id' => auth()->id(), 'opening_cash' => max(0, $opening), 'opened_at' => now()]);

        return $this->shiftCard();
    }

    /** Preview for the close form: what should be in the drawer right now. */
    #[Renderless] // Alpine owns the till state; a re-render would reset it.
    public function shiftSummary(): ?array
    {
        $shift = CashShift::openFor(auth()->id());

        return $shift ? [
            'expected' => $shift->expectedCash(),
            'orders' => $shift->orders()->paid()->count(),
            'sales' => (int) $shift->orders()->paid()->sum('total'),
        ] : null;
    }

    #[Renderless] // Alpine owns the till state; a re-render would reset it.
    public function closeShift(int $counted, ?string $note): ?array
    {
        $shift = CashShift::openFor(auth()->id());
        if (! $shift) {
            return null;
        }
        $shift->close(max(0, $counted), $note ?: null);

        return ['expected' => (int) $shift->expected_cash, 'counted' => $shift->closing_cash, 'difference' => $shift->difference()];
    }

    /** Matches on the last digits so 0812…, 62812… and +62 812… all find the same person. */
    #[Renderless] // Alpine owns the till state; a re-render would reset it.
    public function findCustomer(string $query): array
    {
        $digits = preg_replace(['/\D/', '/^(0|62)/'], '', $query);

        if (strlen($digits) < 6) {
            return [];
        }

        return Customer::with('user:id,name,phone')
            ->whereHas('user', fn ($q) => $q->where('phone', 'like', '%' . $digits))
            ->limit(5)
            ->get()
            ->map(fn (Customer $customer) => $this->customerCard($customer))
            ->all();
    }

    /** Same shape as the self check-in: phone stored as 62…, zeroed counters. */
    #[Renderless] // Alpine owns the till state; a re-render would reset it.
    public function registerCustomer(string $name, string $phone): array
    {
        $name = trim($name);
        $digits = preg_replace('/\D/', '', $phone);
        $digits = str_starts_with($digits, '0') ? '62' . substr($digits, 1) : (str_starts_with($digits, '62') ? $digits : '62' . $digits);

        if ($name === '' || strlen($digits) < 10 || strlen($digits) > 15) {
            return ['error' => 'Isi nama dan nomor HP yang valid.'];
        }

        $user = User::firstOrCreate(['phone' => $digits], ['name' => $name, 'role' => 'customer', 'password' => null]);
        $customer = Customer::firstOrCreate(['user_id' => $user->id], [
            'carwash_points' => 0, 'carwash_total_visits' => 0,
            'motorwash_points' => 0, 'motorwash_total_visits' => 0,
            'coffeeshop_points' => 0, 'coffeeshop_total_visits' => 0,
        ]);

        return $this->customerCard($customer->load('user'));
    }

    #[Renderless] // Alpine owns the till state; a re-render would reset it.
    public function checkout(array $lines, string $method, int $paid, ?int $customerId, bool $redeem, array $discount = []): array
    {
        $shift = CashShift::openFor(auth()->id());
        if (! $shift) {
            return ['error' => 'Buka shift dulu sebelum transaksi.'];
        }

        try {
            $order = Order::checkout($lines, $method, $paid, $customerId, $redeem, auth()->id(), $discount, $shift->id);
        } catch (InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }

        $order->load('items', 'customer.user');

        return [
            'id' => $order->id,
            'number' => $order->number,
            'date' => $order->created_at->translatedFormat('d M Y H:i'),
            'cashier' => auth()->user()->name,
            'items' => $order->items->map->only('name', 'price', 'qty', 'options', 'note')->all(),
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'promo' => $order->promo_name,
            'total' => $order->total,
            'method' => Order::METHODS[$order->payment_method],
            'paid' => $order->paid,
            'change' => $order->change,
            'customer' => $order->customer ? $this->customerCard($order->customer->refresh()) : null,
            'points_earned' => $order->points_earned,
        ];
    }

    #[Renderless] // Alpine owns the till state; a re-render would reset it.
    public function sendReceipt(int $orderId): bool
    {
        $order = Order::with('items', 'customer.user')->where('cashier_id', auth()->id())->find($orderId);
        $phone = $order?->customer?->user?->phone;

        return $phone && app(WhatsAppService::class)->sendMessage($phone, $order->receiptText());
    }

    private function customerCard(Customer $customer): array
    {
        $next = $customer->nextMilestone('coffeeshop');
        $reward = $customer->earnedMilestone('coffeeshop');

        return [
            'id' => $customer->id,
            'name' => $customer->user?->name,
            'phone' => $customer->user?->phone,
            'points' => $customer->getPoints('coffeeshop'),
            'target' => $next['at'] ?? SystemSetting::maxPoints('coffeeshop'),
            'reward' => $reward['reward'] ?? null,
        ];
    }
}
