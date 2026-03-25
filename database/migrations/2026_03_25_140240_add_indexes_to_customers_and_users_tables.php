<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('carwash_last_visit_at');
            $table->index('coffeeshop_last_visit_at');
            $table->index('motorwash_last_visit_at');
            $table->index('carwash_points');
            $table->index('coffeeshop_points');
            $table->index('motorwash_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['carwash_last_visit_at']);
            $table->dropIndex(['coffeeshop_last_visit_at']);
            $table->dropIndex(['motorwash_last_visit_at']);
            $table->dropIndex(['carwash_points']);
            $table->dropIndex(['coffeeshop_points']);
            $table->dropIndex(['motorwash_points']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
