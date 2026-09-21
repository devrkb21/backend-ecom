<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'group' => 'integration',
                'key' => 'revesms_secret_key',
                'type' => 'text',
                'label' => 'REVE Secret Key',
                'description' => 'REVE SMS secretkey value.',
                'value' => '',
                'is_public' => false,
                'sort_order' => 15,
            ],
            [
                'group' => 'integration',
                'key' => 'revesms_client_id',
                'type' => 'text',
                'label' => 'REVE Client ID',
                'description' => 'Optional REVE SMS client ID.',
                'value' => '',
                'is_public' => false,
                'sort_order' => 16,
            ],
        ] as $item) {
            $exists = DB::table('settings')
                ->where('group', $item['group'])
                ->where('key', $item['key'])
                ->exists();

            if (! $exists) {
                DB::table('settings')->insert(array_merge($item, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'integration')
            ->whereIn('key', [
                'revesms_secret_key',
                'revesms_client_id',
            ])
            ->delete();
    }
};