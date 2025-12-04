<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_histories', function (Blueprint $table) {
            $table->enum('loyalty_type', ['carwash', 'coffeeshop', 'both'])
                ->default('carwash')
                ->after('customer_id');
        });

        DB::table('visit_histories')->update(['loyalty_type' => 'carwash']);
    }

    public function down(): void
    {
        Schema::table('visit_histories', function (Blueprint $table) {
            $table->dropColumn('loyalty_type');
        });
    }
};
