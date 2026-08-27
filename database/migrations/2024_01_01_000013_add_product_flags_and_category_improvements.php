<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add show_in_menu to categories (other columns already exist)
        if (! Schema::hasColumn('categories', 'show_in_menu')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('show_in_menu')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['show_in_menu']);
        });
    }
};
