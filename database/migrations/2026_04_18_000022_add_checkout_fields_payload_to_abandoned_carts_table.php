<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            if (!Schema::hasColumn('abandoned_carts', 'checkout_fields_payload')) {
                $table->json('checkout_fields_payload')->nullable()->after('shipping_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            if (Schema::hasColumn('abandoned_carts', 'checkout_fields_payload')) {
                $table->dropColumn('checkout_fields_payload');
            }
        });
    }
};
