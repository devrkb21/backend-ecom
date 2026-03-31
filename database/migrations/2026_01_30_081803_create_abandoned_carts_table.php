<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            
            // Status: pending, follow_up, recovered, cancelled
            $table->enum('status', ['pending', 'follow_up', 'recovered', 'cancelled'])->default('pending');
            
            // Checkout step reached
            $table->string('checkout_step')->nullable(); // cart, shipping, payment
            
            // Contact Information (from checkout form)
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            
            // Shipping Information
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_zip')->nullable();
            $table->string('shipping_country')->nullable();
            
            // Cart Data (JSON snapshot)
            $table->json('cart_items')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            
            // Payment & Shipping Method selected
            $table->string('payment_method')->nullable();
            $table->string('shipping_method')->nullable();
            
            // Recovery tracking
            $table->foreignId('recovered_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('recovered_at')->nullable();
            
            // Admin notes
            $table->text('admin_notes')->nullable();
            $table->foreignId('followed_up_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('followed_up_at')->nullable();
            
            // Last activity
            $table->timestamp('last_activity_at')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
