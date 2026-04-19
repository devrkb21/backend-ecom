<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_method_location_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained('shipping_methods')->cascadeOnDelete();
            $table->string('location_type', 20); // division, district, upazila
            $table->unsignedBigInteger('location_id');
            $table->timestamps();

            $table->unique(['shipping_method_id', 'location_type', 'location_id'], 'shipping_method_location_rule_unique');
            $table->index(['location_type', 'location_id'], 'shipping_method_location_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_method_location_rules');
    }
};
