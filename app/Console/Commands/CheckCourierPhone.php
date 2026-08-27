<?php

namespace App\Console\Commands;

use App\Services\CourierHistoryCheckService;
use App\Support\FraudNormalizer;
use Illuminate\Console\Command;

class CheckCourierPhone extends Command
{
    protected $signature = 'courier:check-phone {phone : Bangladeshi phone number, any format}';

    protected $description = 'Ad-hoc cross-courier delivery-history lookup for a phone number (bypasses the freshness cache)';

    public function handle(CourierHistoryCheckService $courierHistoryCheckService): int
    {
        $normalizedPhone = FraudNormalizer::phone($this->argument('phone'));

        if ($normalizedPhone === null) {
            $this->error('Could not parse a phone number from that input.');

            return self::FAILURE;
        }

        $this->info("Checking {$normalizedPhone} across Steadfast, Pathao, RedX, Paperfly, Carrybee...");

        $result = $courierHistoryCheckService->check($normalizedPhone);

        $this->table(
            ['Courier', 'Success', 'Cancel', 'Ratio'],
            collect(['steadfast', 'pathao', 'redx', 'paperfly', 'carrybee'])->map(function ($key) use ($result) {
                $row = $result->raw_result[$key] ?? null;

                if (! is_array($row) || isset($row['error'])) {
                    $message = $row['error'] ?? 'not checked';
                    if (! empty($row['message']) && $row['message'] !== ($row['error'] ?? null)) {
                        $message .= ' ('.$row['message'].')';
                    }

                    return [$key, '-', '-', $message];
                }

                return [$key, $row['success'] ?? 0, $row['cancel'] ?? 0, ($row['success_ratio'] ?? 0).'%'];
            })
        );

        $this->newLine();
        $this->line("Aggregate: {$result->total_deliveries} deliveries, {$result->success_ratio}% success ratio ({$result->couriers_ok} couriers OK, {$result->couriers_failed} failed)");

        return self::SUCCESS;
    }
}
