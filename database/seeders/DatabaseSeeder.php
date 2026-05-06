<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // // Create test customer
        // User::create([
        //     'name' => 'Test Customer',
        //     'email' => 'customer@example.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'customer',
        //     'email_verified_at' => now(),
        // ]);

        // // Create categories
        // $electronics = Category::create([
        //     'name' => 'Electronics',
        //     'slug' => 'electronics',
        //     'description' => 'Electronic devices and gadgets',
        //     'is_active' => true,
        //     'sort_order' => 1,
        // ]);

        // $clothing = Category::create([
        //     'name' => 'Clothing',
        //     'slug' => 'clothing',
        //     'description' => 'Fashion and apparel',
        //     'is_active' => true,
        //     'sort_order' => 2,
        // ]);

        // $books = Category::create([
        //     'name' => 'Books',
        //     'slug' => 'books',
        //     'description' => 'Books and publications',
        //     'is_active' => true,
        //     'sort_order' => 3,
        // ]);

        // // Create subcategories
        // $phones = Category::create([
        //     'name' => 'Smartphones',
        //     'slug' => 'smartphones',
        //     'description' => 'Mobile phones and accessories',
        //     'parent_id' => $electronics->id,
        //     'is_active' => true,
        //     'sort_order' => 1,
        // ]);

        // $laptops = Category::create([
        //     'name' => 'Laptops',
        //     'slug' => 'laptops',
        //     'description' => 'Portable computers',
        //     'parent_id' => $electronics->id,
        //     'is_active' => true,
        //     'sort_order' => 2,
        // ]);

        // // Create products
        // Product::create([
        //     'category_id' => $phones->id,
        //     'name' => 'iPhone 15 Pro',
        //     'slug' => 'iphone-15-pro',
        //     'description' => 'Latest Apple iPhone with A17 Pro chip',
        //     'regular_price' => 999.99,
        //     'sale_price' => null,
        //     'buy_price' => 750.00,
        //     'sku' => 'IP15PRO-001',
        //     'stock_quantity' => 50,
        //     'is_active' => true,
        //     'is_featured' => true,
        // ]);

        // Product::create([
        //     'category_id' => $phones->id,
        //     'name' => 'Samsung Galaxy S24',
        //     'slug' => 'samsung-galaxy-s24',
        //     'description' => 'Samsung flagship smartphone',
        //     'regular_price' => 899.99,
        //     'sale_price' => 849.99,
        //     'buy_price' => 650.00,
        //     'sku' => 'SGS24-001',
        //     'stock_quantity' => 75,
        //     'is_active' => true,
        //     'is_featured' => true,
        // ]);

        // Product::create([
        //     'category_id' => $laptops->id,
        //     'name' => 'MacBook Pro 16"',
        //     'slug' => 'macbook-pro-16',
        //     'description' => 'Apple MacBook Pro with M3 Pro chip',
        //     'regular_price' => 2499.99,
        //     'sale_price' => null,
        //     'buy_price' => 1900.00,
        //     'sku' => 'MBP16-001',
        //     'stock_quantity' => 25,
        //     'is_active' => true,
        //     'is_featured' => true,
        // ]);

        // Product::create([
        //     'category_id' => $laptops->id,
        //     'name' => 'Dell XPS 15',
        //     'slug' => 'dell-xps-15',
        //     'description' => 'Premium Windows laptop',
        //     'regular_price' => 1799.99,
        //     'sale_price' => 1699.99,
        //     'buy_price' => 1350.00,
        //     'sku' => 'DXPS15-001',
        //     'stock_quantity' => 30,
        //     'is_active' => true,
        //     'is_featured' => false,
        // ]);

        // Product::create([
        //     'category_id' => $clothing->id,
        //     'name' => 'Classic T-Shirt',
        //     'slug' => 'classic-t-shirt',
        //     'description' => 'Comfortable cotton t-shirt',
        //     'regular_price' => 29.99,
        //     'sale_price' => null,
        //     'buy_price' => 12.00,
        //     'sku' => 'CTS-001',
        //     'stock_quantity' => 200,
        //     'is_active' => true,
        //     'is_featured' => false,
        // ]);

        // Product::create([
        //     'category_id' => $books->id,
        //     'name' => 'Laravel: Up & Running',
        //     'slug' => 'laravel-up-and-running',
        //     'description' => 'A framework for building modern PHP apps',
        //     'regular_price' => 49.99,
        //     'sale_price' => 39.99,
        //     'buy_price' => 25.00,
        //     'sku' => 'BOOK-LAR-001',
        //     'stock_quantity' => 100,
        //     'is_active' => true,
        //     'is_featured' => true,
        // ]);

        // Seed payment gateways and shipping methods
        $this->call([
            PaymentGatewaySeeder::class,
            //ShippingMethodSeeder::class,
            SettingSeeder::class,
            LoyaltyTierSeeder::class,
            PagesTableSeeder::class,
            //CouponSeeder::class,
        ]);
    }
}
