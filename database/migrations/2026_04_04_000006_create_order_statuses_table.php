<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('label', 80);
            $table->string('color', 20)->default('#6c757d');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('order_statuses')->insert([
            [
                'key' => 'pending',
                'label' => 'Pending',
                'color' => '#ffc107',
                'sort_order' => 1,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'processing',
                'label' => 'Processing',
                'color' => '#17a2b8',
                'sort_order' => 2,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'shipped',
                'label' => 'Shipped',
                'color' => '#6f42c1',
                'sort_order' => 3,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'color' => '#28a745',
                'sort_order' => 4,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'cancelled',
                'label' => 'Cancelled',
                'color' => '#dc3545',
                'sort_order' => 5,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
