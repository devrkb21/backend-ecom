<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Returns/Refund Requests table
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Return type: return (product return) or refund (money back without return)
            $table->enum('type', ['return', 'refund'])->default('return');

            // Status flow: pending -> approved/rejected -> processing -> completed/cancelled
            $table->enum('status', [
                'pending',      // Customer submitted request
                'approved',     // Admin approved
                'rejected',     // Admin rejected
                'processing',   // Return in progress (product shipped back or refund processing)
                'received',     // Product received (for returns)
                'completed',    // Refund completed
                'cancelled',     // Cancelled by customer or admin
            ])->default('pending');

            // Reason for return/refund
            $table->enum('reason', [
                'damaged',
                'wrong_item',
                'not_as_described',
                'changed_mind',
                'defective',
                'size_issue',
                'quality_issue',
                'late_delivery',
                'other',
            ]);
            $table->text('reason_details')->nullable();

            // Refund details
            $table->enum('refund_method', ['original', 'store_credit', 'bank_transfer'])->default('original');
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->decimal('restocking_fee', 10, 2)->default(0);
            $table->decimal('shipping_deduction', 10, 2)->default(0);
            $table->decimal('final_refund_amount', 10, 2)->default(0);

            // Payment refund tracking
            $table->string('refund_transaction_id')->nullable();
            $table->enum('refund_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_error')->nullable();

            // Return shipping (for physical returns)
            $table->string('return_tracking_number')->nullable();
            $table->string('return_carrier')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();

            // Images/Evidence
            $table->json('images')->nullable();

            // Admin handling
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Customer notes
            $table->text('customer_notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('return_number');
        });

        // Return Items - for partial returns
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->enum('condition', ['unopened', 'opened', 'damaged', 'used'])->default('unopened');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
    }
};
