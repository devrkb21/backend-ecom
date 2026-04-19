<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_method_district_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained('shipping_methods')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('bd_districts')->cascadeOnDelete();
            $table->decimal('rate', 10, 2);
            $table->timestamps();

            $table->unique(['shipping_method_id', 'district_id'], 'shipping_method_district_unique');
            $table->index('district_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_method_district_rates');
    }
};
