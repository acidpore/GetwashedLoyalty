<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->index();
            $table->unsignedInteger('price');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total');
            $table->enum('payment_method', ['cash', 'qris', 'transfer']);
            $table->unsignedInteger('paid');
            $table->unsignedInteger('change')->default(0);
            $table->unsignedTinyInteger('points_earned')->default(0);
            $table->string('reward')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Name and price are copied so old receipts survive menu edits.
            $table->string('name');
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('qty');
            $table->string('note')->nullable();
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer', 'cashier') DEFAULT 'customer'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'cashier')->update(['role' => 'customer']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer') DEFAULT 'customer'");
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
};
