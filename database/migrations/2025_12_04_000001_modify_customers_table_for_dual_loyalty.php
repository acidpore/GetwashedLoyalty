<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('carwash_points')->default(0)->after('user_id');
            $table->integer('carwash_total_visits')->default(0)->after('carwash_points');
            $table->datetime('carwash_last_visit_at')->nullable()->after('carwash_total_visits');
            
            $table->integer('coffeeshop_points')->default(0)->after('carwash_last_visit_at');
            $table->integer('coffeeshop_total_visits')->default(0)->after('coffeeshop_points');
            $table->datetime('coffeeshop_last_visit_at')->nullable()->after('coffeeshop_total_visits');
        });

        DB::table('customers')->update([
            'carwash_points' => DB::raw('current_points'),
            'carwash_total_visits' => DB::raw('total_visits'),
            'carwash_last_visit_at' => DB::raw('last_visit_at'),
        ]);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['current_points', 'total_visits', 'last_visit_at']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('current_points')->default(0)->after('user_id');
            $table->integer('total_visits')->default(0)->after('current_points');
            $table->datetime('last_visit_at')->nullable()->after('total_visits');
        });

        DB::table('customers')->update([
            'current_points' => DB::raw('carwash_points'),
            'total_visits' => DB::raw('carwash_total_visits'),
            'last_visit_at' => DB::raw('carwash_last_visit_at'),
        ]);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'carwash_points',
                'carwash_total_visits',
                'carwash_last_visit_at',
                'coffeeshop_points',
                'coffeeshop_total_visits',
                'coffeeshop_last_visit_at',
            ]);
        });
    }
};
