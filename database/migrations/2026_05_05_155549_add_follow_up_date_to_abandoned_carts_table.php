<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            if (!Schema::hasColumn('abandoned_carts', 'follow_up_date')) {
                $table->date('follow_up_date')->nullable()->after('followed_up_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            if (Schema::hasColumn('abandoned_carts', 'follow_up_date')) {
                $table->dropColumn('follow_up_date');
            }
        });
    }
};
