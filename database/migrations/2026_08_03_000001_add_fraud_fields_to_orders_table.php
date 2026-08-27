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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('device_ip', 45)->nullable()->after('checkout_fields_payload');
            $table->string('device_hash', 64)->nullable()->after('device_ip');
            $table->string('normalized_phone', 20)->nullable()->after('device_hash');
            $table->boolean('is_fraud_flagged')->default(false)->after('normalized_phone');
            $table->json('fraud_flag_reasons')->nullable()->after('is_fraud_flagged');
            $table->unsignedTinyInteger('fraud_risk_score')->default(0)->after('fraud_flag_reasons');

            $table->index(['normalized_phone', 'created_at']);
            $table->index(['device_ip', 'created_at']);
            $table->index(['device_hash', 'created_at']);
            $table->index('is_fraud_flagged');
        });

        // Backfill from existing data so velocity/repeat-offender queries work
        // for orders placed before this migration, not just new ones.
        DB::table('orders')
            ->select('id', 'shipping_phone', 'checkout_fields_payload')
            ->orderBy('id')
            ->chunkById(500, function ($orders) {
                foreach ($orders as $order) {
                    $payload = json_decode((string) $order->checkout_fields_payload, true) ?: [];

                    DB::table('orders')->where('id', $order->id)->update([
                        'normalized_phone' => FraudNormalizer::phone($order->shipping_phone),
                        'device_ip' => FraudNormalizer::ip($payload['device_ip'] ?? null),
                        'device_hash' => FraudNormalizer::device($payload['device_user_agent'] ?? null),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['normalized_phone', 'created_at']);
            $table->dropIndex(['device_ip', 'created_at']);
            $table->dropIndex(['device_hash', 'created_at']);
            $table->dropIndex(['is_fraud_flagged']);

            $table->dropColumn([
                'device_ip',
                'device_hash',
                'normalized_phone',
                'is_fraud_flagged',
                'fraud_flag_reasons',
                'fraud_risk_score',
            ]);
        });
    }
};
