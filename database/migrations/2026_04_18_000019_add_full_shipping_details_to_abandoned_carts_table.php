<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            if (! Schema::hasColumn('abandoned_carts', 'shipping_location_text')) {
                $table->text('shipping_location_text')->nullable()->after('shipping_address');
            }

            if (! Schema::hasColumn('abandoned_carts', 'shipping_area')) {
                $table->string('shipping_area', 255)->nullable()->after('shipping_location_text');
            }

            if (! Schema::hasColumn('abandoned_carts', 'shipping_division')) {
                $table->string('shipping_division', 120)->nullable()->after('shipping_area');
            }

            if (! Schema::hasColumn('abandoned_carts', 'shipping_district')) {
                $table->string('shipping_district', 120)->nullable()->after('shipping_division');
            }

            if (! Schema::hasColumn('abandoned_carts', 'shipping_upazila')) {
                $table->string('shipping_upazila', 120)->nullable()->after('shipping_district');
            }

            if (! Schema::hasColumn('abandoned_carts', 'shipping_union')) {
                $table->string('shipping_union', 120)->nullable()->after('shipping_upazila');
            }
        });
    }

    public function down(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            $columns = [
                'shipping_union',
                'shipping_upazila',
                'shipping_district',
                'shipping_division',
                'shipping_area',
                'shipping_location_text',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('abandoned_carts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
