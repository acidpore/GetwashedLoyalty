<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('motorwash_points')->default(0)->after('coffeeshop_last_visit_at');
            $table->integer('motorwash_total_visits')->default(0)->after('motorwash_points');
            $table->datetime('motorwash_last_visit_at')->nullable()->after('motorwash_total_visits');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['motorwash_points', 'motorwash_total_visits', 'motorwash_last_visit_at']);
        });
    }
};
