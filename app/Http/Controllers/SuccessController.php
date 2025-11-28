<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SuccessController extends Controller
{
    /**
     * Display the success page after check-in.
     */
    public function index(Request $request)
    {
        // Validate query parameters
        $points = $request->query('points', 0);
        $name = $request->query('name', 'Customer');
        $reward = $request->query('reward', false);

        return view('success', [
            'points' => $points,
            'name' => $name,
            'hasReward' => filter_var($reward, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
