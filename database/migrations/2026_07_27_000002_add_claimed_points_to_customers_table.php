<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('carwash_claimed_points')->default(0)->after('carwash_points');
            $table->unsignedInteger('motorwash_claimed_points')->default(0)->after('motorwash_points');
            $table->unsignedInteger('coffeeshop_claimed_points')->default(0)->after('coffeeshop_points');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'carwash_claimed_points',
                'motorwash_claimed_points',
                'coffeeshop_claimed_points',
            ]);
        });
    }
};
