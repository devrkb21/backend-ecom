<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Console\Command;

class CheckLowStock extends Command
{
    protected $signature = 'inventory:check-low-stock {--threshold=5 : Stock level threshold}';

    protected $description = 'Check for products with low stock and notify admins';

    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');

        $lowStockProducts = Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', $threshold)
            ->get();

        if ($lowStockProducts->isEmpty()) {
            $this->info('No low stock products found.');

            return Command::SUCCESS;
        }

        $admins = User::where('role', 'admin')->get();

        foreach ($lowStockProducts as $product) {
            foreach ($admins as $admin) {
                $admin->notify(new LowStockAlert($product));
            }
        }

        $this->info("Sent low stock alerts for {$lowStockProducts->count()} product(s) to {$admins->count()} admin(s).");

        return Command::SUCCESS;
    }
}
