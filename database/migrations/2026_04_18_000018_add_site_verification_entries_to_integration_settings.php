<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            [
                'group' => 'integration',
                'key' => 'site_verification_entries',
            ],
            [
                'type' => 'json',
                'label' => 'Site Verification Entries',
                'description' => 'List of domain verification entries rendered as header meta tags.',
                'value' => '[]',
                'is_public' => true,
                'sort_order' => 9,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'integration')
            ->where('key', 'site_verification_entries')
            ->delete();
    }
};
