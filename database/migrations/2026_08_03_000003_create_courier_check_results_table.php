<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_check_results', function (Blueprint $table) {
            $table->id();
            $table->string('normalized_phone', 20)->unique();
            $table->json('raw_result')->nullable();
            $table->unsignedInteger('total_success')->default(0);
            $table->unsignedInteger('total_cancel')->default(0);
            $table->unsignedInteger('total_deliveries')->default(0);
            $table->decimal('success_ratio', 5, 2)->default(0);
            $table->unsignedTinyInteger('couriers_ok')->default(0);
            $table->unsignedTinyInteger('couriers_failed')->default(0);
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('last_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_check_results');
    }
};
