<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add points balance to users
        Schema::table('users', function (Blueprint $table) {
            $table->integer('loyalty_points')->default(0)->after('email');
            $table->integer('lifetime_points')->default(0)->after('loyalty_points');
            $table->string('loyalty_tier')->default('bronze')->after('lifetime_points'); // bronze, silver, gold, platinum
        });

        // Loyalty points transactions
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['earned', 'redeemed', 'expired', 'adjusted', 'bonus', 'referral']);
            $table->integer('points'); // Positive for earned, negative for redeemed
            $table->integer('balance_after'); // Points balance after transaction
            $table->string('description');
            $table->json('metadata')->nullable(); // Extra data like order total, reason, etc.
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index('expires_at');
        });

        // Loyalty rewards catalog
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('points_required');
            $table->enum('reward_type', ['discount_percentage', 'discount_fixed', 'free_shipping', 'free_product', 'coupon']);
            $table->decimal('reward_value', 10, 2); // Percentage or fixed amount
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null'); // For free product rewards
            $table->integer('quantity_available')->nullable(); // Limited rewards
            $table->integer('redeemed_count')->default(0);
            $table->integer('per_user_limit')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'points_required']);
        });

        // User reward redemptions
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reward_id')->constrained('loyalty_rewards')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('points_spent');
            $table->string('coupon_code')->nullable(); // Generated coupon if applicable
            $table->enum('status', ['pending', 'applied', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('coupon_code');
        });

        // Loyalty tiers configuration
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bronze, Silver, Gold, Platinum
            $table->string('slug')->unique();
            $table->integer('min_points'); // Minimum lifetime points for this tier
            $table->decimal('points_multiplier', 3, 2)->default(1.00); // Earn rate multiplier
            $table->integer('birthday_bonus')->default(0); // Bonus points on birthday
            $table->boolean('free_shipping')->default(false);
            $table->integer('exclusive_discount')->default(0); // Percentage
            $table->text('benefits')->nullable(); // JSON or text description
            $table->string('badge_image')->nullable();
            $table->timestamps();

            $table->index('min_points');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_transactions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points', 'lifetime_points', 'loyalty_tier']);
        });
    }
};
