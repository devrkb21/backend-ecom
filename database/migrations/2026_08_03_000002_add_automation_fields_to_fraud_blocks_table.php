<?php

use App\Support\FraudNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fraud_blocks', function (Blueprint $table) {
            $table->string('normalized_value', 500)->nullable()->after('value');
            $table->string('source', 10)->default('manual')->after('normalized_value');
            $table->boolean('needs_review')->default(false)->after('is_active');

            $table->index(['type', 'normalized_value']);
            $table->index('needs_review');
        });

        DB::table('fraud_blocks')->orderBy('id')->chunkById(500, function ($blocks) {
            foreach ($blocks as $block) {
                DB::table('fraud_blocks')->where('id', $block->id)->update([
                    'normalized_value' => FraudNormalizer::forType($block->type, $block->value),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('fraud_blocks', function (Blueprint $table) {
            $table->dropIndex(['type', 'normalized_value']);
            $table->dropIndex(['needs_review']);

            $table->dropColumn(['normalized_value', 'source', 'needs_review']);
        });
    }
};
