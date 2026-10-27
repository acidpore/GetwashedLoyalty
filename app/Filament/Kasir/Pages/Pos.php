<?php

namespace App\Filament\Kasir\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemSetting;
use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

/**
 * The cart lives in Alpine on the client so tapping items is instant;
 * the server only answers customer lookups and records the final sale.
 * Extends Dashboard only to own the panel's root URL.
 */
class Pos extends Dashboard
{
    protected static string $routePath = '/';

    protected static ?string $title = 'Kasir';

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
            'products' => Product::where('is_active', true)->orderBy('sort')->orderBy('name')
                ->get(['id', 'name', 'category', 'price']),
            'categories' => Product::CATEGORIES,
            'methods' => Order::METHODS,
        ];
    }

    /** Matches on the last digits so 0812…, 62812… and +62 812… all find the same person. */
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

    public function checkout(array $lines, string $method, int $paid, ?int $customerId, bool $redeem): array
    {
        try {
            $order = Order::checkout($lines, $method, $paid, $customerId, $redeem, auth()->id());
        } catch (InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }

        $order->load('items', 'customer.user');

        return [
            'number' => $order->number,
            'date' => $order->created_at->translatedFormat('d M Y H:i'),
            'cashier' => auth()->user()->name,
            'items' => $order->items->map->only('name', 'price', 'qty', 'note')->all(),
            'total' => $order->total,
            'method' => Order::METHODS[$order->payment_method],
            'paid' => $order->paid,
            'change' => $order->change,
            'customer' => $order->customer ? $this->customerCard($order->customer->refresh()) : null,
            'points_earned' => $order->points_earned,
        ];
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
