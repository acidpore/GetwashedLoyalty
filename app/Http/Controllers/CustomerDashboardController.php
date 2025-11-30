<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
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
}
