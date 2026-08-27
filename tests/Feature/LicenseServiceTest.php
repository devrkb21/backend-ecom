<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(\Carbon\Carbon $createdAt): Order
    {
        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 100,
            'total' => 100,
            'shipping_name' => 'Test Customer',
            'shipping_email' => 'test@example.com',
            'shipping_address' => 'Test Address',
            'shipping_city' => 'Dhaka',
            'shipping_zip' => '1200',
            'shipping_country' => 'BD',
        ]);

        Order::whereKey($order->id)->update(['created_at' => $createdAt]);

        return $order->fresh();
    }

    public function test_unactivated_by_default(): void
    {
        $service = app(LicenseService::class);

        $this->assertFalse($service->isValid());
        $this->assertSame('unactivated', $service->status());
        $this->assertTrue($service->shouldBlockCreation());
        $this->assertNull($service->expiredSince());
    }

    public function test_valid_status_allows_creation_and_has_no_cutoff(): void
    {
        Setting::setValue('license', 'status', 'active', ['is_public' => false]);
        Setting::setValue('license', 'core_config', ['whitelabel_enabled' => true], ['is_public' => false, 'type' => 'json']);

        $service = app(LicenseService::class);

        $this->assertTrue($service->isValid());
        $this->assertFalse($service->shouldBlockCreation());
        $this->assertNull($service->expiredSince());
        $this->assertTrue($service->coreConfig('whitelabel_enabled'));
    }

    public function test_expired_status_blocks_creation_and_locks_orders_placed_after_cutoff(): void
    {
        $cutoff = now()->subDay();

        Setting::setValue('license', 'status', 'expired', ['is_public' => false]);
        Setting::setValue('license', 'expired_since', $cutoff->toIso8601String(), ['is_public' => false]);

        $service = app(LicenseService::class);

        $this->assertFalse($service->isValid());
        $this->assertTrue($service->shouldBlockCreation());
        $this->assertNotNull($service->expiredSince());

        $orderBefore = $this->makeOrder($cutoff->copy()->subHour());
        $orderAfter = $this->makeOrder($cutoff->copy()->addHour());

        $this->assertFalse($service->isOrderLocked($orderBefore));
        $this->assertTrue($service->isOrderLocked($orderAfter));
        $this->assertSame(1, $service->lockedOrdersCount());
    }

    public function test_setting_license_key_persists_and_overrides_config(): void
    {
        $service = app(LicenseService::class);
        $service->setLicenseKey('CZBD-test-key');

        $this->assertSame('CZBD-test-key', $service->licenseKey());
    }

    public function test_masked_license_key_hides_everything_but_the_last_four_characters(): void
    {
        $service = app(LicenseService::class);

        $this->assertNull($service->maskedLicenseKey());

        $service->setLicenseKey('CZBD-3f9a1c2e4b5d6a7f8091a2b3c4d5e6f7');

        $this->assertSame(str_repeat('*', 33).'e6f7', $service->maskedLicenseKey());
    }

    public function test_locked_orders_count_is_zero_while_valid_or_never_expired(): void
    {
        $service = app(LicenseService::class);

        $this->assertSame(0, $service->lockedOrdersCount());

        Setting::setValue('license', 'status', 'active', ['is_public' => false]);
        $this->makeOrder(now());

        $this->assertSame(0, $service->lockedOrdersCount());
    }

    public function test_admin_license_status_endpoint_reports_locked_orders_and_masked_key(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cutoff = now()->subDay();

        Setting::setValue('license', 'status', 'expired', ['is_public' => false]);
        Setting::setValue('license', 'expired_since', $cutoff->toIso8601String(), ['is_public' => false]);
        Setting::setValue('license', 'license_key', 'CZBD-abcd1234', ['is_public' => false]);

        $this->makeOrder($cutoff->copy()->subHour());
        $this->makeOrder($cutoff->copy()->addHour());
        $this->makeOrder($cutoff->copy()->addHours(2));

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/license');

        $response->assertOk()
            ->assertJsonPath('data.status', 'expired')
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.masked_license_key', str_repeat('*', 9).'1234')
            ->assertJsonPath('data.locked_orders_count', 2);
    }

    public function test_admin_license_page_shows_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cutoff = now()->subDay();

        Setting::setValue('license', 'status', 'expired', ['is_public' => false]);
        Setting::setValue('license', 'expired_since', $cutoff->toIso8601String(), ['is_public' => false]);
        Setting::setValue('license', 'license_key', 'CZBD-abcd1234', ['is_public' => false]);
        Setting::setValue('license', 'last_error', 'License has expired', ['is_public' => false]);

        $this->makeOrder($cutoff->copy()->addHour());

        $response = $this->actingAs($admin, 'web')->get('/admin/license');

        $response->assertOk()
            ->assertSee('License')
            ->assertSee('Expired')
            ->assertSee(str_repeat('*', 9).'1234', false)
            ->assertSee('1 order', false);
    }

    public function test_admin_can_save_and_activate_license_key_from_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'web')
            ->from('/admin/license')
            ->put('/admin/license', ['license_key' => 'CZBD-new-key-1234']);

        $response->assertRedirect('/admin/license');

        $this->assertSame('CZBD-new-key-1234', app(LicenseService::class)->licenseKey());
    }

    public function test_blade_admin_orders_list_hides_orders_placed_after_license_expired(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cutoff = now()->subDay();

        Setting::setValue('license', 'status', 'expired', ['is_public' => false]);
        Setting::setValue('license', 'expired_since', $cutoff->toIso8601String(), ['is_public' => false]);

        $visibleOrder = $this->makeOrder($cutoff->copy()->subHour());
        $this->makeOrder($cutoff->copy()->addHour());

        $response = $this->actingAs($admin, 'web')->get('/admin/orders');

        $response->assertOk()
            ->assertSee($visibleOrder->order_number);
    }

    public function test_blade_admin_cannot_view_order_placed_after_license_expired(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cutoff = now()->subDay();

        Setting::setValue('license', 'status', 'expired', ['is_public' => false]);
        Setting::setValue('license', 'expired_since', $cutoff->toIso8601String(), ['is_public' => false]);

        $lockedOrder = $this->makeOrder($cutoff->copy()->addHour());

        $response = $this->actingAs($admin, 'web')->get('/admin/orders/'.$lockedOrder->id);

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('error');
    }

    public function test_blade_admin_can_still_view_order_placed_before_license_expired(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cutoff = now()->subDay();

        Setting::setValue('license', 'status', 'expired', ['is_public' => false]);
        Setting::setValue('license', 'expired_since', $cutoff->toIso8601String(), ['is_public' => false]);

        $visibleOrder = $this->makeOrder($cutoff->copy()->subHour());

        $response = $this->actingAs($admin, 'web')->get('/admin/orders/'.$visibleOrder->id);

        $response->assertOk();
    }

    public function test_blade_admin_cannot_create_product_or_category_when_license_expired(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::setValue('license', 'status', 'expired', ['is_public' => false]);
        Setting::setValue('license', 'expired_since', now()->toIso8601String(), ['is_public' => false]);

        $productResponse = $this->actingAs($admin, 'web')->get('/admin/products/create');
        $productResponse->assertRedirect();
        $productResponse->assertSessionHas('error');

        $categoryResponse = $this->actingAs($admin, 'web')->get('/admin/categories/create');
        $categoryResponse->assertRedirect();
        $categoryResponse->assertSessionHas('error');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_blade_admin_can_still_read_products_list_when_license_expired(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::setValue('license', 'status', 'expired', ['is_public' => false]);
        Setting::setValue('license', 'expired_since', now()->toIso8601String(), ['is_public' => false]);

        $response = $this->actingAs($admin, 'web')->get('/admin/products');

        $response->assertOk();
    }
}
