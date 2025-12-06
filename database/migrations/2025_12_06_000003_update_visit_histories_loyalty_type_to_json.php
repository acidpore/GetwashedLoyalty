<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_histories', function (Blueprint $table) {
            $table->json('loyalty_types_temp')->nullable()->after('loyalty_type');
        });

        $visitHistories = DB::table('visit_histories')->get();
        
        foreach ($visitHistories as $visit) {
            $loyaltyTypes = match($visit->loyalty_type) {
                'carwash' => ['carwash'],
                'coffeeshop' => ['coffeeshop'],
                'both' => ['carwash', 'coffeeshop'],
                default => ['carwash'],
            };
            
            DB::table('visit_histories')
                ->where('id', $visit->id)
                ->update(['loyalty_types_temp' => json_encode($loyaltyTypes)]);
        }

        Schema::table('visit_histories', function (Blueprint $table) {
            $table->dropColumn('loyalty_type');
        });

        Schema::table('visit_histories', function (Blueprint $table) {
            $table->renameColumn('loyalty_types_temp', 'loyalty_types');
        });
    }

    public function down(): void
    {
        Schema::table('visit_histories', function (Blueprint $table) {
            $table->string('loyalty_type_temp')->nullable()->after('loyalty_types');
        });

        $visitHistories = DB::table('visit_histories')->get();
        
        foreach ($visitHistories as $visit) {
            $types = json_decode($visit->loyalty_types, true) ?? [];
            
            $loyaltyType = 'carwash';
            if (in_array('carwash', $types) && in_array('coffeeshop', $types)) {
                $loyaltyType = 'both';
            } elseif (in_array('coffeeshop', $types)) {
                $loyaltyType = 'coffeeshop';
            }
            
            DB::table('visit_histories')
                ->where('id', $visit->id)
                ->update(['loyalty_type_temp' => $loyaltyType]);
        }

        Schema::table('visit_histories', function (Blueprint $table) {
            $table->dropColumn('loyalty_types');
        });

        Schema::table('visit_histories', function (Blueprint $table) {
            $table->renameColumn('loyalty_type_temp', 'loyalty_type');
        });
    }
};
