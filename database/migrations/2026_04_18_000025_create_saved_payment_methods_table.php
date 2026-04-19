<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('gateway')->default('stripe');
            $table->string('provider_customer_id')->nullable();
            $table->string('provider_payment_method_id')->unique();
            $table->string('card_brand')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->unsignedTinyInteger('card_exp_month')->nullable();
            $table->unsignedSmallInteger('card_exp_year')->nullable();
            $table->string('card_fingerprint')->nullable();
            $table->string('cardholder_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'gateway', 'is_active']);
            $table->index(['provider_customer_id']);
            $table->index(['card_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_payment_methods');
    }
};
