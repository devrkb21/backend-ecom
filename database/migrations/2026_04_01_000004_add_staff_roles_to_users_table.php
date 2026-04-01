<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('customer','admin','shop_manager','cashier','sales') NOT NULL DEFAULT 'customer'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::table('users')
                ->whereIn('role', ['shop_manager', 'cashier', 'sales'])
                ->update(['role' => 'customer']);

            DB::statement("ALTER TABLE users MODIFY role ENUM('customer','admin') NOT NULL DEFAULT 'customer'");
        }
    }
};
