<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE orders MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::table('orders')
                ->whereNotIn('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])
                ->update(['status' => 'pending']);

            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
