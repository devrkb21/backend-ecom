<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flash Sales main table
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('priority')->default(0); // For ordering multiple flash sales
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index('is_featured');
        });

        // Flash Sale Products pivot table
        Schema::create('flash_sale_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('flash_price', 10, 2);
            $table->decimal('original_price', 10, 2); // Snapshot of original price
            $table->integer('discount_percentage')->default(0);
            $table->integer('quantity_limit')->nullable(); // Max quantity for sale
            $table->integer('sold_count')->default(0);
            $table->integer('per_user_limit')->default(1); // Max per customer
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['flash_sale_id', 'product_id']);
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_products');
        Schema::dropIfExists('flash_sales');
    }
};
