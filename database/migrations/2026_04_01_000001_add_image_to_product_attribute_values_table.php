<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            if (! Schema::hasColumn('product_attribute_values', 'image')) {
                $table->string('image')->nullable()->after('color_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            if (Schema::hasColumn('product_attribute_values', 'image')) {
                $table->dropColumn('image');
            }
        });
    }
};
