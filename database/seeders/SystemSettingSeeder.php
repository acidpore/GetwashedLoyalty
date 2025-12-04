<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'carwash_reward_threshold',
                'value' => '5',
                'description' => 'Points required for car wash reward',
            ],
            [
                'key' => 'coffeeshop_reward_threshold',
                'value' => '5',
                'description' => 'Points required for coffee shop reward',
            ],
            [
                'key' => 'carwash_reward_message',
                'value' => 'DISKON CAR WASH',
                'description' => 'Reward message for car wash loyalty',
            ],
            [
                'key' => 'coffeeshop_reward_message',
                'value' => 'GRATIS KOPI',
                'description' => 'Reward message for coffee shop loyalty',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
