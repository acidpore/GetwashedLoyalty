<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->json('loyalty_types_temp')->nullable()->after('loyalty_type');
        });

        $qrCodes = DB::table('qr_codes')->get();
        
        foreach ($qrCodes as $qr) {
            $loyaltyTypes = match($qr->loyalty_type) {
                'carwash' => ['carwash'],
                'coffeeshop' => ['coffeeshop'],
                'both' => ['carwash', 'coffeeshop'],
                default => ['carwash'],
            };
            
            DB::table('qr_codes')
                ->where('id', $qr->id)
                ->update(['loyalty_types_temp' => json_encode($loyaltyTypes)]);
        }

        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropColumn('loyalty_type');
        });

        Schema::table('qr_codes', function (Blueprint $table) {
            $table->renameColumn('loyalty_types_temp', 'loyalty_types');
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->string('loyalty_type_temp')->nullable()->after('loyalty_types');
        });

        $qrCodes = DB::table('qr_codes')->get();
        
        foreach ($qrCodes as $qr) {
            $types = json_decode($qr->loyalty_types, true) ?? [];
            
            $loyaltyType = 'carwash';
            if (in_array('carwash', $types) && in_array('coffeeshop', $types)) {
                $loyaltyType = 'both';
            } elseif (in_array('coffeeshop', $types)) {
                $loyaltyType = 'coffeeshop';
            }
            
            DB::table('qr_codes')
                ->where('id', $qr->id)
                ->update(['loyalty_type_temp' => $loyaltyType]);
        }

        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropColumn('loyalty_types');
        });

        Schema::table('qr_codes', function (Blueprint $table) {
            $table->renameColumn('loyalty_type_temp', 'loyalty_type');
        });
    }
};
