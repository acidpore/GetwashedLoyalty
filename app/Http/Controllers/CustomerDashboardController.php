<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display customer dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        $customer = $user->customer;

        // If no customer profile, redirect to check-in
        if (!$customer) {
            return redirect()->route('checkin')
                ->with('info', 'Silakan check-in terlebih dahulu.');
        }

        // Get recent visit history (last 10)
        $recentVisits = $customer->visitHistories()
            ->orderBy('visited_at', 'desc')
            ->take(10)
            ->get();

        return view('dashboard.customer', [
            'customer' => $customer,
            'user' => $user,
            'recentVisits' => $recentVisits,
            'pointsToReward' => max(0, 5 - $customer->current_points),
            'hasReward' => $customer->current_points >= 5,
        ]);
    }
}
