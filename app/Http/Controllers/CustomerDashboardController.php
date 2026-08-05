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

        $meta = [
            'carwash' => ['name' => 'Car Wash', 'icon' => 'car', 'gradient' => 'from-blue-400 to-blue-600'],
            'motorwash' => ['name' => 'Motor Wash', 'icon' => 'motorcycle', 'gradient' => 'from-orange-400 to-red-500'],
            'coffeeshop' => ['name' => 'Coffee Shop', 'icon' => 'coffee', 'gradient' => 'from-emerald-400 to-teal-600'],
        ];

        $loyaltyPrograms = collect($meta)
            ->map(function (array $info, string $type) use ($customer) {
                $earned = $customer->earnedMilestone($type);
                $next = $customer->nextMilestone($type);

                return $info + [
                    'type' => $type,
                    'points' => $customer->getPoints($type),
                    // threshold = target berikutnya kalau masih ngumpulin, max kalau sudah mentok
                    'threshold' => $next['at'] ?? SystemSetting::maxPoints($type),
                    'message' => $earned['reward'] ?? ($next['reward'] ?? ''),
                    'has_reward' => $earned !== null,
                    'earned_reward' => $earned['reward'] ?? null,
                    'next_reward' => $next['reward'] ?? null,
                    'points_until_reward' => $customer->pointsUntilReward($type),
                    'milestones' => SystemSetting::milestones($type),
                    'max_points' => SystemSetting::maxPoints($type),
                ];
            })
            ->values()
            ->all();

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

        Auth::login($customer->user);

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
