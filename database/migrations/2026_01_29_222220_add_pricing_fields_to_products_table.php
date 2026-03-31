<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Rename price to regular_price
            $table->renameColumn('price', 'regular_price');
        });

        Schema::table('products', function (Blueprint $table) {
            // Add buy_price (cost price) for revenue calculation
            $table->decimal('buy_price', 10, 2)->nullable()->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('buy_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('regular_price', 'price');
        });
    }
};
