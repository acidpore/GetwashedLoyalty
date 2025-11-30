<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::create([
            'key' => 'reward_points_threshold',
            'value' => '5',
            'description' => 'Number of points required to earn a reward',
        ]);
    }
}
