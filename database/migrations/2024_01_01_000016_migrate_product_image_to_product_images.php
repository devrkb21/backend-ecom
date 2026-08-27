<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, migrate any existing product.image data to product_images table
        $products = DB::table('products')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->get();

        foreach ($products as $product) {
            // Check if this product already has a primary image in product_images
            $hasPrimary = DB::table('product_images')
                ->where('product_id', $product->id)
                ->where('is_primary', true)
                ->exists();

            // Check if this exact image path already exists
            $imageExists = DB::table('product_images')
                ->where('product_id', $product->id)
                ->where('image', $product->image)
                ->exists();

            if (! $imageExists) {
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'image' => $product->image,
                    'alt_text' => $product->name,
                    'sort_order' => 0,
                    'is_primary' => ! $hasPrimary, // Set as primary only if no primary exists
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (! $hasPrimary) {
                // If image exists but no primary, set it as primary
                DB::table('product_images')
                    ->where('product_id', $product->id)
                    ->where('image', $product->image)
                    ->update(['is_primary' => true]);
            }
        }

        // Now drop the image column from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }

    public function down(): void
    {
        // Add the image column back
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('stock_quantity');
        });

        // Restore primary images back to products.image
        $primaryImages = DB::table('product_images')
            ->where('is_primary', true)
            ->get();

        foreach ($primaryImages as $img) {
            DB::table('products')
                ->where('id', $img->product_id)
                ->update(['image' => $img->image]);
        }
    }
};
