<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            
            $table->integer('min_order_count')->default(0);
            $table->decimal('min_total_spent', 10, 2)->default(0);
            
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->text('custom_message')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // Useful to sort overlapping rules
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
