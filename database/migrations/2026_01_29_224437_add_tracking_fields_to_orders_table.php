<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_number')->nullable()->after('shipping_method');
            $table->string('carrier')->nullable()->after('tracking_number');
            $table->string('carrier_tracking_url')->nullable()->after('carrier');
            $table->timestamp('shipped_at')->nullable()->after('carrier_tracking_url');
            $table->timestamp('estimated_delivery_at')->nullable()->after('shipped_at');
            $table->timestamp('delivered_at')->nullable()->after('estimated_delivery_at');
            
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_number',
                'carrier',
                'carrier_tracking_url',
                'shipped_at',
                'estimated_delivery_at',
                'delivered_at',
            ]);
        });
    }
};
