<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // cod, stripe, bkash
            $table->string('name'); // Cash on Delivery, Stripe, bKash
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('settings')->nullable(); // API keys, configurations
            $table->json('supported_currencies')->nullable(); // ['USD', 'BDT']
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->string('icon')->nullable(); // Icon class or image path
            $table->text('instructions')->nullable(); // Customer instructions
            $table->timestamps();
        });

        // Add payment_method to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('total');
            $table->string('payment_status')->default('pending')->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status']);
        });

        Schema::dropIfExists('payment_gateways');
    }
};
