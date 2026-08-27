<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            if (! Schema::hasColumn('abandoned_carts', 'reminder_count')) {
                $table->unsignedTinyInteger('reminder_count')->default(0)->after('followed_up_at');
            }

            if (! Schema::hasColumn('abandoned_carts', 'first_reminder_sent_at')) {
                $table->timestamp('first_reminder_sent_at')->nullable()->after('reminder_count');
            }

            if (! Schema::hasColumn('abandoned_carts', 'last_reminder_sent_at')) {
                $table->timestamp('last_reminder_sent_at')->nullable()->after('first_reminder_sent_at');
            }

            if (! Schema::hasColumn('abandoned_carts', 'last_reminder_channel')) {
                $table->string('last_reminder_channel', 20)->nullable()->after('last_reminder_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('abandoned_carts', 'last_reminder_channel')) {
                $dropColumns[] = 'last_reminder_channel';
            }

            if (Schema::hasColumn('abandoned_carts', 'last_reminder_sent_at')) {
                $dropColumns[] = 'last_reminder_sent_at';
            }

            if (Schema::hasColumn('abandoned_carts', 'first_reminder_sent_at')) {
                $dropColumns[] = 'first_reminder_sent_at';
            }

            if (Schema::hasColumn('abandoned_carts', 'reminder_count')) {
                $dropColumns[] = 'reminder_count';
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
