<?php

namespace App\Console\Commands;

use App\Models\FlashSale;
use Illuminate\Console\Command;

class ExpireFlashSales extends Command
{
    protected $signature = 'flash-sales:expire';

    protected $description = 'Deactivate expired flash sales';

    public function handle(): int
    {
        $expired = FlashSale::where('is_active', true)
            ->where('ends_at', '<=', now())
            ->update(['is_active' => false]);

        $this->info("Deactivated {$expired} expired flash sale(s).");

        return Command::SUCCESS;
    }
}
