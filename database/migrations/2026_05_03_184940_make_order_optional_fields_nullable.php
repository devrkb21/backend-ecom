<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_email')->nullable()->change();
            $table->string('shipping_city')->nullable()->change();
            $table->string('shipping_zip')->nullable()->change();
            $table->string('shipping_country')->nullable()->default('BD')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_email')->nullable(false)->change();
            $table->string('shipping_city')->nullable(false)->change();
            $table->string('shipping_zip')->nullable(false)->change();
            $table->string('shipping_country')->nullable(false)->default(null)->change();
        });
    }
};
