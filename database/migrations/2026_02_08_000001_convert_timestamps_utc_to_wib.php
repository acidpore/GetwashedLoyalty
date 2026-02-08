<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert all timestamps from UTC to WIB (Asia/Jakarta, +7 hours)
     * This is a DATA-SAFE migration - only updates timestamp values, no data loss.
     */
    public function up(): void
    {
        // Update customers table - add 7 hours to all timestamp columns
        DB::statement("
            UPDATE customers 
            SET 
                carwash_last_visit_at = DATE_ADD(carwash_last_visit_at, INTERVAL 7 HOUR),
                motorwash_last_visit_at = DATE_ADD(motorwash_last_visit_at, INTERVAL 7 HOUR),
                coffeeshop_last_visit_at = DATE_ADD(coffeeshop_last_visit_at, INTERVAL 7 HOUR),
                token_expires_at = DATE_ADD(token_expires_at, INTERVAL 7 HOUR),
                pin_set_at = DATE_ADD(pin_set_at, INTERVAL 7 HOUR),
                created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE carwash_last_visit_at IS NOT NULL 
               OR motorwash_last_visit_at IS NOT NULL 
               OR coffeeshop_last_visit_at IS NOT NULL
               OR created_at IS NOT NULL
        ");

        // Update visit_histories table
        DB::statement("
            UPDATE visit_histories 
            SET 
                visited_at = DATE_ADD(visited_at, INTERVAL 7 HOUR),
                created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE visited_at IS NOT NULL
        ");

        // Update users table
        DB::statement("
            UPDATE users 
            SET 
                created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE created_at IS NOT NULL
        ");

        // Update broadcasts table
        DB::statement("
            UPDATE broadcasts 
            SET 
                scheduled_at = DATE_ADD(scheduled_at, INTERVAL 7 HOUR),
                sent_at = DATE_ADD(sent_at, INTERVAL 7 HOUR),
                created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE created_at IS NOT NULL
        ");

        // Update qr_codes table
        DB::statement("
            UPDATE qr_codes 
            SET 
                created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE created_at IS NOT NULL
        ");

        // Update system_settings table
        DB::statement("
            UPDATE system_settings 
            SET 
                created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE created_at IS NOT NULL
        ");
    }

    /**
     * Reverse the migration - subtract 7 hours to revert back to UTC
     */
    public function down(): void
    {
        // Revert customers table
        DB::statement("
            UPDATE customers 
            SET 
                carwash_last_visit_at = DATE_SUB(carwash_last_visit_at, INTERVAL 7 HOUR),
                motorwash_last_visit_at = DATE_SUB(motorwash_last_visit_at, INTERVAL 7 HOUR),
                coffeeshop_last_visit_at = DATE_SUB(coffeeshop_last_visit_at, INTERVAL 7 HOUR),
                token_expires_at = DATE_SUB(token_expires_at, INTERVAL 7 HOUR),
                pin_set_at = DATE_SUB(pin_set_at, INTERVAL 7 HOUR),
                created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE carwash_last_visit_at IS NOT NULL 
               OR motorwash_last_visit_at IS NOT NULL 
               OR coffeeshop_last_visit_at IS NOT NULL
               OR created_at IS NOT NULL
        ");

        // Revert visit_histories table
        DB::statement("
            UPDATE visit_histories 
            SET 
                visited_at = DATE_SUB(visited_at, INTERVAL 7 HOUR),
                created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE visited_at IS NOT NULL
        ");

        // Revert users table
        DB::statement("
            UPDATE users 
            SET 
                created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at IS NOT NULL
        ");

        // Revert broadcasts table
        DB::statement("
            UPDATE broadcasts 
            SET 
                scheduled_at = DATE_SUB(scheduled_at, INTERVAL 7 HOUR),
                sent_at = DATE_SUB(sent_at, INTERVAL 7 HOUR),
                created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at IS NOT NULL
        ");

        // Revert qr_codes table
        DB::statement("
            UPDATE qr_codes 
            SET 
                created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at IS NOT NULL
        ");

        // Revert system_settings table
        DB::statement("
            UPDATE system_settings 
            SET 
                created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
                updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at IS NOT NULL
        ");
    }
};
