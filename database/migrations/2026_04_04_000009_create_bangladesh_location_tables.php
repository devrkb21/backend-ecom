<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bd_divisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id')->unique();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('bd_districts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id')->unique();
            $table->foreignId('division_id')->constrained('bd_divisions')->cascadeOnDelete();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['division_id', 'name']);
        });

        Schema::create('bd_upazilas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id')->unique();
            $table->foreignId('district_id')->constrained('bd_districts')->cascadeOnDelete();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['district_id', 'name']);
        });

        Schema::create('bd_unions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id')->unique();
            $table->foreignId('upazila_id')->constrained('bd_upazilas')->cascadeOnDelete();
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['upazila_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bd_unions');
        Schema::dropIfExists('bd_upazilas');
        Schema::dropIfExists('bd_districts');
        Schema::dropIfExists('bd_divisions');
    }
};
