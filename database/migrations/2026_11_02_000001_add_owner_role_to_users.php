<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer', 'cashier', 'owner') DEFAULT 'customer'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'owner')->update(['role' => 'cashier']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer', 'cashier') DEFAULT 'customer'");
    }
};
