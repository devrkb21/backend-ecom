<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label')->nullable(); // "Home", "Office", "Warehouse"
            $table->enum('type', ['shipping', 'billing', 'both'])->default('both');
            $table->boolean('is_default')->default(false);
            
            // Contact info
            $table->string('name'); // Recipient name
            $table->string('phone');
            $table->string('email')->nullable();
            
            // Address details
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable(); // Division/Province
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Bangladesh');
            
            // Additional info
            $table->text('instructions')->nullable(); // Delivery instructions
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
