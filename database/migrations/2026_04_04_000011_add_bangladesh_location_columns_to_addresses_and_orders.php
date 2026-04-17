<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('address_line_2')->constrained('bd_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('division_id')->constrained('bd_districts')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->after('district_id')->constrained('bd_upazilas')->nullOnDelete();
            $table->foreignId('union_id')->nullable()->after('upazila_id')->constrained('bd_unions')->nullOnDelete();
            $table->string('area')->nullable()->after('union_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_division_id')->nullable()->after('shipping_address')->constrained('bd_divisions')->nullOnDelete();
            $table->foreignId('shipping_district_id')->nullable()->after('shipping_division_id')->constrained('bd_districts')->nullOnDelete();
            $table->foreignId('shipping_upazila_id')->nullable()->after('shipping_district_id')->constrained('bd_upazilas')->nullOnDelete();
            $table->foreignId('shipping_union_id')->nullable()->after('shipping_upazila_id')->constrained('bd_unions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_division_id']);
            $table->dropForeign(['shipping_district_id']);
            $table->dropForeign(['shipping_upazila_id']);
            $table->dropForeign(['shipping_union_id']);
            $table->dropColumn([
                'shipping_division_id',
                'shipping_district_id',
                'shipping_upazila_id',
                'shipping_union_id',
            ]);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['upazila_id']);
            $table->dropForeign(['union_id']);
            $table->dropColumn([
                'division_id',
                'district_id',
                'upazila_id',
                'union_id',
                'area',
            ]);
        });
    }
};
