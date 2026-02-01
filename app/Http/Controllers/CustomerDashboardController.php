<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            return redirect('/admin');
        }
        
        $customer = $user->customer;

        if (!$customer) {
            return redirect()->route('checkin')
                ->with('info', 'Silakan check-in terlebih dahulu.');
        }

        $recentVisits = $customer->visitHistories()
            ->select(['id', 'customer_id', 'loyalty_types', 'points_earned', 'visited_at'])
            ->orderByDesc('visited_at')
            ->limit(10)
            ->get();

        $loyaltyPrograms = [
            [
                'name' => 'Car Wash',
                'type' => 'carwash',
                'points' => $customer->carwash_points,
                'threshold' => SystemSetting::carwashRewardThreshold(),
                'message' => SystemSetting::carwashRewardMessage(),
                'has_reward' => $customer->hasReward('carwash'),
                'icon' => 'car',
                'gradient' => 'from-blue-400 to-blue-600',
            ],
            [
                'name' => 'Motor Wash',
                'type' => 'motorwash',
                'points' => $customer->motorwash_points,
                'threshold' => SystemSetting::motorwashRewardThreshold(),
                'message' => SystemSetting::motorwashRewardMessage(),
                'has_reward' => $customer->hasReward('motorwash'),
                'icon' => 'motorcycle',
                'gradient' => 'from-orange-400 to-red-500',
            ],
            [
                'name' => 'Coffee Shop',
                'type' => 'coffeeshop',
                'points' => $customer->coffeeshop_points,
                'threshold' => SystemSetting::coffeeshopRewardThreshold(),
                'message' => SystemSetting::coffeeshopRewardMessage(),
                'has_reward' => $customer->hasReward('coffeeshop'),
                'icon' => 'coffee',
                'gradient' => 'from-emerald-400 to-teal-600',
            ],
        ];

        return view('dashboard.customer', [
            'customer' => $customer,
            'user' => $user,
            'recentVisits' => $recentVisits,
            'loyaltyPrograms' => $loyaltyPrograms,
        ]);
    }

    public function magicLogin(Request $request, string $token)
    {
        $customer = Customer::where('dashboard_token', $token)->first();

        if (!$customer) {
            return redirect()->route('login')
                ->with('error', 'Token tidak valid.');
        }

        if ($customer->token_expires_at && $customer->token_expires_at->isPast()) {
            return redirect()->route('login')
                ->with('error', 'Link sudah kadaluarsa. Silakan check-in ulang.');
        }

        if ($customer->user->isBanned()) {
            return redirect()->route('login')
                ->with('error', 'Akun Anda diblokir.');
        }

        Auth::login($customer->user);
        $customer->user->recordLogin($request->ip());

        if (!$customer->hasPin()) {
            return redirect()->route('customer.pin.setup')
                ->with('info', 'Silakan atur PIN untuk login berikutnya.');
        }

        return redirect()->route('customer.dashboard');
    }

    public function showPinSetup()
    {
        $user = auth()->user();
        $customer = $user->customer;

        if (!$customer) {
            return redirect()->route('checkin');
        }

        return view('dashboard.pin-setup', [
            'hasPin' => $customer->hasPin(),
        ]);
    }

    public function storePinSetup(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6|regex:/^[0-9]+$/',
            'pin_confirmation' => 'required|same:pin',
        ], [
            'pin.size' => 'PIN harus 6 digit.',
            'pin.regex' => 'PIN harus berupa angka.',
            'pin_confirmation.same' => 'Konfirmasi PIN tidak sama.',
        ]);

        $customer = auth()->user()->customer;
        $customer->setPin($request->pin);

        return redirect()->route('customer.dashboard')
            ->with('success', 'PIN berhasil diatur. Gunakan untuk login berikutnya.');
    }
}
