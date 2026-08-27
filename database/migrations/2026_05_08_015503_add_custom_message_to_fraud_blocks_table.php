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
        Schema::table('fraud_blocks', function (Blueprint $table) {
            if (! Schema::hasColumn('fraud_blocks', 'custom_message')) {
                $table->string('custom_message', 1000)->nullable()->after('reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fraud_blocks', function (Blueprint $table) {
            $table->dropColumn('custom_message');
        });
    }
};
