<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

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
            ->orderBy('visited_at', 'desc')
            ->take(10)
            ->get();

        $threshold = SystemSetting::rewardPointsThreshold();

        return view('dashboard.customer', [
            'customer' => $customer,
            'user' => $user,
            'recentVisits' => $recentVisits,
            'pointsToReward' => max(0, $threshold - $customer->current_points),
            'hasReward' => $customer->current_points >= $threshold,
        ]);
    }
    public function magicLogin(Request $request, string $token)
    {
        $customer = \App\Models\Customer::where('dashboard_token', $token)->first();

        if (!$customer) {
            return redirect()->route('login')
                ->with('error', 'Token tidak valid.');
        }

        if ($customer->token_expires_at && $customer->token_expires_at->isPast()) {
            return redirect()->route('login')
                ->with('error', 'Link sudah kadaluarsa. Silakan login manual.');
        }

        // Auto login
        \Illuminate\Support\Facades\Auth::login($customer->user);

        return redirect()->route('customer.dashboard');
    }
}
