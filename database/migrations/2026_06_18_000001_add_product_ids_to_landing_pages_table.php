<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // Add product_ids JSON array for multiple product support
            $table->json('product_ids')->nullable()->after('product_id');
            // Make product_id nullable (it will no longer be the primary field)
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });

        // Backfill: migrate existing product_id into product_ids array
        DB::table('landing_pages')->whereNotNull('product_id')->cursor()->each(function ($page) {
            DB::table('landing_pages')
                ->where('id', $page->id)
                ->update(['product_ids' => json_encode([$page->product_id])]);
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('product_ids');
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
