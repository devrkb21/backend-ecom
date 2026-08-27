<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cart_items', 'product_variant_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('product_variant_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->nullOnDelete();
            });
        } elseif (! $this->hasForeignKey('cart_items', 'cart_items_product_variant_id_foreign')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreign('product_variant_id', 'cart_items_product_variant_id_foreign')
                    ->references('id')
                    ->on('product_variants')
                    ->nullOnDelete();
            });
        }

        // Add the replacement unique key first so MySQL always has a valid index for FK checks.
        if (! $this->hasIndex('cart_items', 'cart_items_cart_product_variant_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(
                    ['cart_id', 'product_id', 'product_variant_id'],
                    'cart_items_cart_product_variant_unique'
                );
            });
        }

        if ($this->hasIndex('cart_items', 'cart_items_cart_id_product_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_cart_id_product_id_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('cart_items', 'cart_items_cart_product_variant_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_cart_product_variant_unique');
            });
        }

        if ($this->hasForeignKey('cart_items', 'cart_items_product_variant_id_foreign')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropForeign('cart_items_product_variant_id_foreign');
            });
        }

        if (Schema::hasColumn('cart_items', 'product_variant_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('product_variant_id');
            });
        }

        if (! $this->hasIndex('cart_items', 'cart_items_cart_id_product_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id'], 'cart_items_cart_id_product_id_unique');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $results = DB::select("PRAGMA index_list('{$table}')");
            foreach ($results as $row) {
                // In some older PHP/sqlite versions, indices might be objects or arrays
                $name = is_object($row) ? ($row->name ?? null) : ($row['name'] ?? null);
                if ($name === $index) {
                    return true;
                }
            }

            return false;
        }

        return (bool) DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        return (bool) DB::table('information_schema.table_constraints')
            ->whereRaw('constraint_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
