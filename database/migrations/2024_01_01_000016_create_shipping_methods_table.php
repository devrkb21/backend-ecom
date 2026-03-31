<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Pricing
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->decimal('cost_per_item', 10, 2)->default(0);
            $table->decimal('cost_per_kg', 10, 2)->default(0);
            $table->decimal('free_shipping_threshold', 10, 2)->nullable(); // Free shipping above this amount
            
            // Restrictions
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_order_amount', 10, 2)->nullable();
            $table->decimal('max_weight', 10, 2)->nullable(); // Max weight in kg
            
            // Delivery time
            $table->integer('min_delivery_days')->nullable();
            $table->integer('max_delivery_days')->nullable();
            
            // Zones/Countries (JSON array of country codes, null = all)
            $table->json('allowed_countries')->nullable();
            $table->json('excluded_countries')->nullable();
            
            // Settings
            $table->json('settings')->nullable();
            
            $table->timestamps();
        });

        // Add shipping_method column to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method')->nullable()->after('shipping');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_method');
        });

        Schema::dropIfExists('shipping_methods');
    }
};
