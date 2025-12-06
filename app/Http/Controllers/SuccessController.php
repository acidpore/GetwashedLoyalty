<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SuccessController extends Controller
{
    public function index(Request $request)
    {
        $name = $request->query('name', 'Customer');
        $loyaltyType = $request->query('loyalty_type', 'carwash');
        $carwashPoints = (int) $request->query('carwash_points', 0);
        $motorwashPoints = (int) $request->query('motorwash_points', 0);
        $coffeeshopPoints = (int) $request->query('coffeeshop_points', 0);
        $carwashReward = filter_var($request->query('carwash_reward', false), FILTER_VALIDATE_BOOLEAN);
        $motorwashReward = filter_var($request->query('motorwash_reward', false), FILTER_VALIDATE_BOOLEAN);
        $coffeeshopReward = filter_var($request->query('coffeeshop_reward', false), FILTER_VALIDATE_BOOLEAN);

        return view('success', compact(
            'name',
            'loyaltyType',
            'carwashPoints',
            'motorwashPoints',
            'coffeeshopPoints',
            'carwashReward',
            'motorwashReward',
            'coffeeshopReward'
        ));
    }
}
