<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('related_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('related_product_id')->constrained('products')->onDelete('cascade');
            $table->enum('relation_type', ['manual', 'category', 'frequently_bought', 'viewed_together'])->default('manual');
            $table->integer('score')->default(0); // For sorting by relevance
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id']);
            $table->index(['product_id', 'relation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('related_products');
    }
};
