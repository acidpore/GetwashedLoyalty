<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SuccessController extends Controller
{
    private const PROGRAMS = [
        'carwash' => ['name' => 'Car Wash', 'image' => 'carwash.png'],
        'motorwash' => ['name' => 'Motor Wash', 'image' => 'motorcycle.png'],
        'coffeeshop' => ['name' => 'Coffee Shop', 'image' => 'drink.png'],
    ];

    public function index(Request $request)
    {
        $types = array_values(array_intersect(
            explode(',', (string) $request->query('loyalty_types', '')),
            SystemSetting::LOYALTY_TYPES
        )) ?: ['carwash'];

        $programs = collect($types)->map(function (string $type) use ($request) {
            $points = (int) $request->query("{$type}_points", 0);
            $milestones = SystemSetting::milestones($type);
            $hasReward = filter_var($request->query("{$type}_reward", false), FILTER_VALIDATE_BOOLEAN);

            // Points freeze at the milestone waiting to be claimed, so that tier is the highest one reached.
            $earned = collect($milestones)->where('at', '<=', $points)->last();
            $next = collect($milestones)->firstWhere('at', '>', $points);

            return self::PROGRAMS[$type] + [
                'type' => $type,
                'points' => $points,
                'max_points' => SystemSetting::maxPoints($type),
                'has_reward' => $hasReward && $earned !== null,
                'earned_reward' => $earned['reward'] ?? null,
                'next_reward' => $next['reward'] ?? null,
                'points_until_reward' => $next ? $next['at'] - $points : 0,
            ];
        });

        return view('success', [
            'name' => $request->query('name', 'Customer'),
            'programs' => $programs,
            'hasReward' => $programs->contains('has_reward', true),
        ]);
    }
}
