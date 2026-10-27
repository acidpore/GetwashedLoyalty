<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('price');
            // [{name, multiple, choices: [{name, price}]}]
            $table->json('options')->nullable()->after('image');
        });

        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('opening_cash');
            $table->timestamp('opened_at');
            $table->unsignedInteger('expected_cash')->nullable();
            $table->unsignedInteger('closing_cash')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('note')->nullable();
            $table->index(['user_id', 'closed_at']);
        });

        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['percent', 'amount']);
            $table->unsignedInteger('value');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('cashier_id')->constrained('cash_shifts')->nullOnDelete();
            $table->unsignedInteger('subtotal')->default(0)->after('shift_id');
            $table->unsignedInteger('discount')->default(0)->after('subtotal');
            $table->string('promo_name')->nullable()->after('discount');
            $table->foreignId('reward_claim_id')->nullable()->after('reward')->constrained()->nullOnDelete();
            $table->foreignId('visit_history_id')->nullable()->after('reward_claim_id')->constrained()->nullOnDelete();
            // Customer's coffeeshop counters before this sale, so a void can put them back.
            $table->json('loyalty_before')->nullable()->after('visit_history_id');
            $table->timestamp('voided_at')->nullable()->index();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
        });
        DB::table('orders')->update(['subtotal' => DB::raw('total')]);

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('options')->nullable()->after('qty');
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit', 16);
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('ingredient_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 10, 2);
        });

        Schema::create('ingredient_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 12, 2);
            $table->string('reason');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_movements');
        Schema::dropIfExists('ingredient_product');
        Schema::dropIfExists('ingredients');
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('options'));
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
            $table->dropConstrainedForeignId('reward_claim_id');
            $table->dropConstrainedForeignId('visit_history_id');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['subtotal', 'discount', 'promo_name', 'loyalty_before', 'voided_at', 'void_reason']);
        });
        Schema::dropIfExists('promos');
        Schema::dropIfExists('cash_shifts');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['image', 'options']));
    }
};
