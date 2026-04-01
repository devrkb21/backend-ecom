<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('can_access_admin_panel')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        $defaults = [
            [
                'key' => 'admin',
                'name' => 'Admin',
                'description' => 'Full control over admin panel and settings.',
                'permissions' => json_encode(['*']),
                'can_access_admin_panel' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'shop_manager',
                'name' => 'Shop Manager',
                'description' => 'Manages catalog, operations, and reports.',
                'permissions' => json_encode([
                    'dashboard.view',
                    'catalog.manage',
                    'media.manage',
                    'orders.manage',
                    'payments.view',
                    'returns.manage',
                    'abandoned_carts.manage',
                    'marketing.manage',
                    'analytics.view',
                    'users.manage',
                    'settings.manage',
                ]),
                'can_access_admin_panel' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'cashier',
                'name' => 'Cashier',
                'description' => 'Handles orders, payments, and return processing.',
                'permissions' => json_encode([
                    'dashboard.view',
                    'orders.manage',
                    'payments.view',
                    'returns.manage',
                ]),
                'can_access_admin_panel' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'sales',
                'name' => 'Sales',
                'description' => 'Focuses on order workflow and customer follow-up.',
                'permissions' => json_encode([
                    'dashboard.view',
                    'orders.manage',
                    'returns.manage',
                    'abandoned_carts.manage',
                    'users.manage',
                ]),
                'can_access_admin_panel' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'customer',
                'name' => 'Customer',
                'description' => 'Default storefront user role with no admin panel access.',
                'permissions' => json_encode([]),
                'can_access_admin_panel' => false,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('admin_roles')->upsert(
            $defaults,
            ['key'],
            ['name', 'description', 'permissions', 'can_access_admin_panel', 'is_active', 'is_system', 'sort_order', 'updated_at']
        );

        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where(function ($query) {
                    $query->whereNull('role')->orWhere('role', '');
                })
                ->update(['role' => 'customer']);

            $driver = DB::getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE users MODIFY role VARCHAR(100) NOT NULL DEFAULT 'customer'");
            }

            $knownRoleKeys = DB::table('admin_roles')->pluck('key')->all();
            $userRoleKeys = DB::table('users')
                ->select('role')
                ->distinct()
                ->pluck('role')
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->values();

            foreach ($userRoleKeys as $roleKey) {
                if (in_array($roleKey, $knownRoleKeys, true)) {
                    continue;
                }

                DB::table('admin_roles')->insert([
                    'key' => $roleKey,
                    'name' => ucwords(str_replace(['_', '-'], ' ', $roleKey)),
                    'description' => 'Auto-generated from existing users.',
                    'permissions' => json_encode([]),
                    'can_access_admin_panel' => false,
                    'is_active' => true,
                    'is_system' => false,
                    'sort_order' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $knownRoleKeys[] = $roleKey;
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')
                ->whereNotIn('role', ['customer', 'admin', 'shop_manager', 'cashier', 'sales'])
                ->update(['role' => 'customer']);

            $driver = DB::getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE users MODIFY role ENUM('customer','admin','shop_manager','cashier','sales') NOT NULL DEFAULT 'customer'");
            }
        }

        Schema::dropIfExists('admin_roles');
    }
};
