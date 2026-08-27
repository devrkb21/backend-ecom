<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add missing columns that the model expects
            if (! Schema::hasColumn('products', 'short_description')) {
                $table->string('short_description', 500)->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'is_new')) {
                $table->boolean('is_new')->default(false)->after('is_featured');
            }
            if (! Schema::hasColumn('products', 'is_bestseller')) {
                $table->boolean('is_bestseller')->default(false)->after('is_new');
            }
            if (! Schema::hasColumn('products', 'sales_count')) {
                $table->integer('sales_count')->default(0)->after('is_bestseller');
            }
            if (! Schema::hasColumn('products', 'meta_data')) {
                $table->json('meta_data')->nullable()->after('sales_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['short_description', 'is_new', 'is_bestseller', 'sales_count', 'meta_data']);
        });
    }
};
