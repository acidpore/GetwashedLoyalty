<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->insert([
            [
                'key' => 'motorwash_reward_threshold',
                'value' => '5',
                'description' => 'Threshold poin untuk reward Cuci Motor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'motorwash_reward_message',
                'value' => 'DISKON CUCI MOTOR',
                'description' => 'Pesan reward untuk program Cuci Motor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['motorwash_reward_threshold', 'motorwash_reward_message'])
            ->delete();
    }
};
