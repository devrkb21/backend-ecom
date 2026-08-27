<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasPurchasePrice = Schema::hasColumn('product_variants', 'purchase_price');
        $hasRegularPrice = Schema::hasColumn('product_variants', 'regular_price');
        $hasDiscountedPrice = Schema::hasColumn('product_variants', 'discounted_price');

        if ($hasPurchasePrice && $hasRegularPrice && $hasDiscountedPrice) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) use ($hasPurchasePrice, $hasRegularPrice, $hasDiscountedPrice) {
            if (! $hasPurchasePrice) {
                $table->decimal('purchase_price', 10, 2)->nullable();
            }

            if (! $hasRegularPrice) {
                $table->decimal('regular_price', 10, 2)->nullable();
            }

            if (! $hasDiscountedPrice) {
                $table->decimal('discounted_price', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variants', 'purchase_price')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('purchase_price');
            });
        }

        if (Schema::hasColumn('product_variants', 'regular_price')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('regular_price');
            });
        }

        if (Schema::hasColumn('product_variants', 'discounted_price')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('discounted_price');
            });
        }
    }
};
