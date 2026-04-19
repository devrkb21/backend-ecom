<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['product_variant_id']);
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }
};
