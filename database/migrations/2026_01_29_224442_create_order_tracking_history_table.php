<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tracking_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('status'); // pending, processing, shipped, out_for_delivery, delivered, etc.
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('carrier_status')->nullable(); // Raw status from carrier
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tracking_history');
    }
};
