<?php

namespace Tests\Feature;

use App\Models\Order;
use devrkb21\PathaoLaravel\Events\PathaoWebhookReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PathaoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pathao_tracking_url_appends_phone_number()
    {
        $order = Order::create([
            'order_number' => 'ORD-123456',
            'status' => 'pending',
            'order_source' => 'web',
            'subtotal' => 100,
            'tax' => 0,
            'shipping' => 50,
            'total' => 150,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_name' => 'John Doe',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka, Bangladesh',
            'shipping_country' => 'BD',
            'tracking_number' => 'consignment123',
            'carrier' => 'pathao'
        ]);

        $url = $order->generateTrackingUrl('consignment123', 'pathao');
        $this->assertEquals('https://merchant.pathao.com/tracking?consignment_id=consignment123&phone=01700000000', $url);
    }

    public function test_pathao_webhook_updates_order_status_but_not_payment_status_on_delivered()
    {
        $order = Order::create([
            'order_number' => 'ORD-1234567',
            'status' => 'processing',
            'order_source' => 'web',
            'subtotal' => 100,
            'tax' => 0,
            'shipping' => 50,
            'total' => 150,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_name' => 'John Doe',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka, Bangladesh',
            'shipping_country' => 'BD',
            'tracking_number' => 'consignment123',
            'carrier' => 'pathao'
        ]);

        $eventPayload = [
            'event' => 'order.delivered',
            'consignment_id' => 'consignment123',
            'merchant_order_id' => $order->order_number,
            'updated_at' => now()->toIso8601String(),
        ];

        event(new PathaoWebhookReceived($eventPayload));

        $order->refresh();

        $this->assertEquals('delivered', $order->status);
        $this->assertEquals('pending', $order->payment_status); // payment remains pending
    }

    public function test_pathao_webhook_updates_payment_status_only_on_paid()
    {
        $order = Order::create([
            'order_number' => 'ORD-12345678',
            'status' => 'delivered',
            'order_source' => 'web',
            'subtotal' => 100,
            'tax' => 0,
            'shipping' => 50,
            'total' => 150,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_name' => 'John Doe',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka, Bangladesh',
            'shipping_country' => 'BD',
            'tracking_number' => 'consignment123',
            'carrier' => 'pathao'
        ]);

        $eventPayload = [
            'event' => 'order.paid',
            'consignment_id' => 'consignment123',
            'merchant_order_id' => $order->order_number,
            'updated_at' => now()->toIso8601String(),
        ];

        event(new PathaoWebhookReceived($eventPayload));

        $order->refresh();

        $this->assertEquals('delivered', $order->status);
        $this->assertEquals('paid', $order->payment_status); // payment updates to paid
    }

    public function test_pathao_store_creation()
    {
        $admin = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        \devrkb21\PathaoLaravel\Facades\PathaoLaravel::shouldReceive('CREATE_STORE')
            ->once()
            ->andReturn([
                'status' => 200,
                'data' => [
                    'data' => [
                        'store_id' => 999,
                        'store_name' => 'Mock Store',
                    ]
                ]
            ]);

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/settings/couriers/pathao/store', [
                'name' => 'Dhaka Store',
                'contact_name' => 'John Doe',
                'contact_number' => '01712345678',
                'address' => 'House 12, Road 4, Sector 3, Uttara',
                'city_id' => 1,
                'zone_id' => 1,
                'area_id' => 1,
            ]);

        $response->assertRedirect('/admin/settings/couriers/pathao');
        $response->assertSessionHas('success', "Store 'Mock Store' created successfully in Pathao. Store ID: 999.");
    }

    public function test_pathao_store_creation_validation_fails()
    {
        $admin = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/settings/couriers/pathao/store', [
                'name' => '', // blank name
                'contact_name' => 'John Doe',
                'contact_number' => '12345', // invalid phone
                'address' => 'Short', // too short address
                'city_id' => 'abc', // invalid numeric
                'zone_id' => '',
                'area_id' => '',
            ]);

        $response->assertStatus(302); // Redirect back on validation failure
        $response->assertSessionHasErrors(['name', 'contact_number', 'address', 'city_id', 'zone_id', 'area_id']);
    }

    public function test_pathao_webhook_integration_challenge_response()
    {
        \App\Models\Setting::setValue('courier', 'pathao_webhook_integration_secret', 'f3992ecc-59da-4cbe-a049-a13da2018d51');
        
        config([
            'pathao.webhook_integration_secret' => 'f3992ecc-59da-4cbe-a049-a13da2018d51',
        ]);

        $response = $this->postJson('/api/pathao/webhook', [
            'event' => 'webhook_integration',
        ], [
            'X-Pathao-Merchant-Webhook-Integration-Secret' => 'f3992ecc-59da-4cbe-a049-a13da2018d51',
        ]);

        $response->assertStatus(202);
        $response->assertHeader('X-Pathao-Merchant-Webhook-Integration-Secret', 'f3992ecc-59da-4cbe-a049-a13da2018d51');
    }

    public function test_pathao_webhook_integration_challenge_response_fallback()
    {
        \App\Models\Setting::setValue('courier', 'pathao_webhook_integration_secret', 'f3992ecc-59da-4cbe-a049-a13da2018d51');
        
        config([
            'pathao.webhook_integration_secret' => 'f3992ecc-59da-4cbe-a049-a13da2018d51',
        ]);

        $response = $this->postJson('/api/pathao/webhook', [
            'event' => 'webhook_integration',
        ]);

        $response->assertStatus(202);
        $response->assertHeader('X-Pathao-Merchant-Webhook-Integration-Secret', 'f3992ecc-59da-4cbe-a049-a13da2018d51');
    }

    public function test_pathao_test_connection_successful()
    {
        $admin = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        \devrkb21\PathaoLaravel\Facades\PathaoLaravel::shouldReceive('GET_MERCHANT_INFO')
            ->once()
            ->andReturn([
                'status' => 200,
                'data' => [
                    'data' => [
                        'merchant_id' => 1234,
                        'merchant_name' => 'Mock Merchant',
                        'merchant_email' => 'merchant@example.com',
                        'merchant_contact_number' => '01711223344',
                        'country_id' => 1,
                    ]
                ]
            ]);

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/settings/couriers/pathao/test-connection');

        $response->assertRedirect('/admin/settings/couriers/pathao');
        $response->assertSessionHas('success', 'Pathao Connection Successful! Merchant profile loaded and cached.');

        $cachedInfo = \App\Models\Setting::getValue('courier', 'pathao_merchant_info');
        $this->assertEquals('Mock Merchant', $cachedInfo['merchant_name']);
        $this->assertEquals(1234, $cachedInfo['merchant_id']);
    }

    public function test_pathao_test_connection_failed()
    {
        $admin = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        \devrkb21\PathaoLaravel\Facades\PathaoLaravel::shouldReceive('GET_MERCHANT_INFO')
            ->once()
            ->andReturn([
                'status' => 401,
                'message' => 'Unauthorized Access',
            ]);

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/settings/couriers/pathao/test-connection');

        $response->assertRedirect('/admin/settings/couriers/pathao');
        $response->assertSessionHas('error', 'Pathao Connection Failed: Unauthorized Access');
    }
}
