<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add bkash_payment_id to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->string('bkash_payment_id')->nullable()->after('transaction_id');
        });

        // Add role index to users (missing)
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });

        // Order Notes table
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('note');
            $table->enum('type', ['internal', 'customer', 'system'])->default('internal');
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamps();

            $table->index(['order_id', 'type']);
        });

        // Audit Logs table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('order_notes');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('bkash_payment_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
    }
};
