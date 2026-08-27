<?php

namespace Tests\Feature;

use App\Models\CourierCheckResult;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourierFraudCheckSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@smoketest.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->order = Order::create([
            'order_number' => 'SMOKE-'.uniqid(),
            'status' => 'pending',
            'order_source' => 'web',
            'subtotal' => 100,
            'total' => 100,
            'shipping_name' => 'Smoke Test',
            'shipping_email' => 'smoke@example.com',
            'shipping_phone' => '01711223344',
            'shipping_address' => 'Addr',
            'shipping_city' => 'Dhaka',
            'shipping_zip' => '1200',
            'shipping_country' => 'Bangladesh',
            'normalized_phone' => '01711223344',
        ]);
    }

    public function test_order_show_renders_courier_history_card(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get("/admin/orders/{$this->order->id}");

        $response->assertOk();
        $response->assertSee('Courier Delivery History');
        $response->assertSee('Not checked yet');
    }

    public function test_courier_history_check_fails_cleanly_without_credentials(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson("/admin/orders/{$this->order->id}/courier-history-check");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure(['success', 'message', 'settings_url']);
    }

    public function test_courier_history_check_succeeds_and_returns_html_when_configured(): void
    {
        Setting::setValue('courier_check', 'pathao_users', 'fake@example.com');
        Setting::setValue('courier_check', 'pathao_passwords', 'fakepass');
        Setting::clearCache('courier_check');

        Http::fake([
            'merchant.pathao.com/api/v1/login' => Http::response(['access_token' => 'tok'], 200),
            'merchant.pathao.com/api/v1/user/success' => Http::response([
                'data' => ['customer' => ['successful_delivery' => 8, 'total_delivery' => 10]],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson("/admin/orders/{$this->order->id}/courier-history-check");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'html']);
        $this->assertStringContainsString('80%', $response->json('html'));
    }

    public function test_courier_checker_page_loads_with_search_and_settings_tabs(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get('/admin/orders/courier-checker');

        $response->assertOk();
        $response->assertSee('Search by Phone Number');
        $response->assertSee('Total Checks');
        $response->assertSee('Recent Checks');
        $response->assertSee('Merchant Credentials');
        $response->assertSee('Automatic Checking');
        $response->assertSee('Pathao');
        $response->assertSee('Steadfast');
    }

    public function test_courier_checker_search_fails_cleanly_without_credentials(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/orders/courier-checker/search', ['phone' => '01711223344']);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure(['success', 'message', 'settings_url']);
    }

    public function test_courier_checker_search_succeeds_independent_of_any_order(): void
    {
        Setting::setValue('courier_check', 'pathao_users', 'fake@example.com');
        Setting::setValue('courier_check', 'pathao_passwords', 'fakepass');
        Setting::clearCache('courier_check');

        Http::fake([
            'merchant.pathao.com/api/v1/login' => Http::response(['access_token' => 'tok'], 200),
            'merchant.pathao.com/api/v1/user/success' => Http::response([
                'data' => ['customer' => ['successful_delivery' => 3, 'total_delivery' => 10]],
            ], 200),
        ]);

        // A phone that has never placed an order in this store at all.
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/orders/courier-checker/search', ['phone' => '01799999999']);

        $response->assertOk();
        $response->assertJson(['success' => true, 'normalized_phone' => '01799999999']);
        $this->assertStringContainsString('30%', $response->json('html'));
    }

    public function test_courier_checker_credentials_update_persists(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/orders/courier-checker/credentials', [
                'pathao_users' => 'a@example.com',
                'pathao_passwords' => 'secret',
                'steadfast_users' => '',
                'steadfast_passwords' => '',
                'redx_phones' => '01711111111',
                'redx_passwords' => 'redxpass',
                'paperfly_users' => '',
                'paperfly_passwords' => '',
                'carrybee_phones' => '01722222222',
                'carrybee_passwords' => 'carrybeepass',
                'proxy_address' => '',
            ]);

        $response->assertRedirect(route('admin.orders.courier-checker', ['tab' => 'settings']));
        $this->assertEquals('a@example.com', Setting::getValue('courier_check', 'pathao_users'));
        $this->assertEquals('secret', Setting::getValue('courier_check', 'pathao_passwords'));
        // Regression check: RedX/Carrybee use a "phones" field (not "users"
        // like the other three couriers) — the form's field names must
        // match, or these silently never reach the validated payload.
        $this->assertEquals('01711111111', Setting::getValue('courier_check', 'redx_phones'));
        $this->assertEquals('redxpass', Setting::getValue('courier_check', 'redx_passwords'));
        $this->assertEquals('01722222222', Setting::getValue('courier_check', 'carrybee_phones'));
        $this->assertEquals('carrybeepass', Setting::getValue('courier_check', 'carrybee_passwords'));
    }

    public function test_courier_checker_form_field_names_match_settings_keys(): void
    {
        // Regression guard: the RedX/Carrybee textareas must be named
        // *_phones, not *_users.
        $html = $this->actingAs($this->admin, 'web')->get('/admin/orders/courier-checker')->getContent();

        $this->assertStringContainsString('name="redx_phones"', $html);
        $this->assertStringContainsString('name="carrybee_phones"', $html);
        $this->assertStringNotContainsString('name="redx_users"', $html);
        $this->assertStringNotContainsString('name="carrybee_users"', $html);
    }

    public function test_courier_checker_automation_settings_update_persists(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/orders/courier-checker/automation', [
                'courier_check_enabled' => '1',
                'courier_check_freshness_days' => 14,
                'courier_check_min_orders' => 5,
                'courier_check_max_cancel_ratio' => 50,
                'courier_check_action' => 'auto_block',
            ]);

        $response->assertRedirect(route('admin.orders.courier-checker', ['tab' => 'settings']));
        $this->assertEquals('1', Setting::getValue('fraud_blocks', 'courier_check_enabled'));
        $this->assertEquals('14', Setting::getValue('fraud_blocks', 'courier_check_freshness_days'));
        $this->assertEquals('auto_block', Setting::getValue('fraud_blocks', 'courier_check_action'));
    }

    public function test_saved_passwords_are_never_echoed_back_in_plain_text(): void
    {
        Setting::setValue('courier_check', 'pathao_passwords', 'TopSecretPassword123');
        Setting::clearCache('courier_check');

        $html = $this->actingAs($this->admin, 'web')->get('/admin/orders/courier-checker')->getContent();

        $this->assertStringNotContainsString('TopSecretPassword123', $html);
        $this->assertStringContainsString('leave blank to keep saved passwords', $html);
    }

    public function test_blank_password_submission_preserves_existing_saved_password(): void
    {
        Setting::setValue('courier_check', 'pathao_users', 'existing@example.com');
        Setting::setValue('courier_check', 'pathao_passwords', 'ExistingSecret');
        Setting::clearCache('courier_check');

        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/orders/courier-checker/credentials', [
                'pathao_users' => 'existing@example.com',
                'pathao_passwords' => '', // left blank — must not wipe the saved password
                'steadfast_users' => '',
                'steadfast_passwords' => '',
                'redx_phones' => '',
                'redx_passwords' => '',
                'paperfly_users' => '',
                'paperfly_passwords' => '',
                'carrybee_phones' => '',
                'carrybee_passwords' => '',
                'proxy_address' => '',
            ]);

        $response->assertRedirect();
        $this->assertEquals('ExistingSecret', Setting::getValue('courier_check', 'pathao_passwords'));
    }

    public function test_recent_check_can_be_viewed_from_cache_without_a_live_courier_call(): void
    {
        $cached = CourierCheckResult::create([
            'normalized_phone' => '01755512345',
            'raw_result' => [
                'steadfast' => ['success' => 4, 'cancel' => 6, 'total' => 10, 'success_ratio' => 40],
                'pathao' => ['error' => 'pathao is not configured'],
                'redx' => ['error' => 'redx is not configured'],
                'paperfly' => ['error' => 'paperfly is not configured'],
                'carrybee' => ['error' => 'carrybee is not configured'],
            ],
            'total_success' => 4,
            'total_cancel' => 6,
            'total_deliveries' => 10,
            'success_ratio' => 40,
            'couriers_ok' => 1,
            'couriers_failed' => 4,
            'checked_at' => now()->subHours(3),
        ]);

        // No credentials configured at all, and Http::fake() left unset —
        // if this route made a live call it would fail/hang; it must not.
        $response = $this->actingAs($this->admin, 'web')
            ->getJson(route('admin.orders.courier-checker.show', $cached));

        $response->assertOk();
        $response->assertJson(['success' => true, 'normalized_phone' => '01755512345']);
        $this->assertStringContainsString('40%', $response->json('html'));

        $html = $this->actingAs($this->admin, 'web')->get('/admin/orders/courier-checker')->getContent();
        $this->assertStringContainsString(route('admin.orders.courier-checker.show', $cached), $html);
    }

    public function test_fraud_blocks_page_links_to_courier_checker_instead_of_embedding_settings(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/fraud-blocks');

        $response->assertOk();
        $response->assertSee(route('admin.orders.courier-checker'), false);
        $response->assertDontSee('Cross-Courier History Check');
    }

    public function test_carrybee_404_is_treated_as_zero_deliveries_new_customer(): void
    {
        Setting::setValue('courier_check', 'carrybee_phones', '01711111111');
        Setting::setValue('courier_check', 'carrybee_passwords', 'carrybeepass');
        Setting::clearCache('courier_check');

        Http::fake([
            'merchant.carrybee.com/api/auth/csrf' => Http::response(['csrfToken' => 'tok'], 200),
            'merchant.carrybee.com/api/auth/callback/login' => Http::response('', 200),
            'merchant.carrybee.com/api/auth/session' => Http::response(['accessToken' => 'atok', 'user' => ['selectedBusinessId' => 'biz1']], 200),
            'api-merchant.carrybee.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/orders/courier-checker/search', ['phone' => '01799999998']);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('New customer', $html);
        $this->assertStringNotContainsString('Failed to fetch from Carrybee', $html);

        $cached = CourierCheckResult::where('normalized_phone', '01799999998')->first();
        $this->assertNotNull($cached);
        $this->assertArrayNotHasKey('error', $cached->raw_result['carrybee']);
        $this->assertTrue($cached->raw_result['carrybee']['new_customer'] ?? false);
        $this->assertSame(0, $cached->raw_result['carrybee']['success']);
        $this->assertSame(0, $cached->raw_result['carrybee']['total']);
    }

    public function test_pathao_customer_rating_is_shown_as_a_tag_not_raw_percentage(): void
    {
        Setting::setValue('courier_check', 'pathao_users', 'fake@example.com');
        Setting::setValue('courier_check', 'pathao_passwords', 'fakepass');
        Setting::clearCache('courier_check');

        Http::fake([
            'merchant.pathao.com/api/v1/login' => Http::response(['access_token' => 'tok'], 200),
            'merchant.pathao.com/api/v1/user/success' => Http::response([
                'data' => [
                    'customer' => ['successful_delivery' => 8, 'total_delivery' => 10],
                    'customer_rating' => 'excellent_customer',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/orders/courier-checker/search', ['phone' => '01799999997']);

        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('Excellent Customer', $html);
        $this->assertStringNotContainsString('80%', $html);
    }

    public function test_search_serves_cached_result_within_six_hours_without_a_live_call(): void
    {
        Setting::setValue('courier_check', 'pathao_users', 'fake@example.com');
        Setting::setValue('courier_check', 'pathao_passwords', 'fakepass');
        Setting::clearCache('courier_check');

        CourierCheckResult::create([
            'normalized_phone' => '01755500001',
            'raw_result' => ['pathao' => ['success' => 5, 'cancel' => 1, 'total' => 6, 'success_ratio' => 83.33]],
            'total_success' => 5,
            'total_cancel' => 1,
            'total_deliveries' => 6,
            'success_ratio' => 83.33,
            'couriers_ok' => 1,
            'couriers_failed' => 4,
            'checked_at' => now()->subHours(2),
        ]);

        Http::fake();

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/orders/courier-checker/search', ['phone' => '01755500001']);

        $response->assertOk();
        $response->assertJson(['success' => true, 'from_cache' => true]);
        Http::assertNothingSent();
        $this->assertStringContainsString('Cached', $response->json('html'));
    }

    public function test_search_refresh_bypasses_cache_and_makes_a_live_call(): void
    {
        Setting::setValue('courier_check', 'pathao_users', 'fake@example.com');
        Setting::setValue('courier_check', 'pathao_passwords', 'fakepass');
        Setting::clearCache('courier_check');

        CourierCheckResult::create([
            'normalized_phone' => '01755500002',
            'raw_result' => ['pathao' => ['success' => 5, 'cancel' => 1, 'total' => 6, 'success_ratio' => 83.33]],
            'total_success' => 5,
            'total_cancel' => 1,
            'total_deliveries' => 6,
            'success_ratio' => 83.33,
            'couriers_ok' => 1,
            'couriers_failed' => 4,
            'checked_at' => now()->subHours(2),
        ]);

        Http::fake([
            'merchant.pathao.com/api/v1/login' => Http::response(['access_token' => 'tok'], 200),
            'merchant.pathao.com/api/v1/user/success' => Http::response([
                'data' => ['customer' => ['successful_delivery' => 9, 'total_delivery' => 10]],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/orders/courier-checker/search', ['phone' => '01755500002', 'refresh' => true]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'from_cache' => false]);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'merchant.pathao.com/api/v1/login');
        });
        $this->assertStringContainsString('Fresh', $response->json('html'));
    }

    public function test_search_treats_cache_older_than_six_hours_as_stale(): void
    {
        Setting::setValue('courier_check', 'pathao_users', 'fake@example.com');
        Setting::setValue('courier_check', 'pathao_passwords', 'fakepass');
        Setting::clearCache('courier_check');

        CourierCheckResult::create([
            'normalized_phone' => '01755500003',
            'raw_result' => ['pathao' => ['success' => 5, 'cancel' => 1, 'total' => 6, 'success_ratio' => 83.33]],
            'total_success' => 5,
            'total_cancel' => 1,
            'total_deliveries' => 6,
            'success_ratio' => 83.33,
            'couriers_ok' => 1,
            'couriers_failed' => 4,
            'checked_at' => now()->subHours(7),
        ]);

        Http::fake([
            'merchant.pathao.com/api/v1/login' => Http::response(['access_token' => 'tok'], 200),
            'merchant.pathao.com/api/v1/user/success' => Http::response([
                'data' => ['customer' => ['successful_delivery' => 9, 'total_delivery' => 10]],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/orders/courier-checker/search', ['phone' => '01755500003']);

        $response->assertOk();
        $response->assertJson(['success' => true, 'from_cache' => false]);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'merchant.pathao.com/api/v1/login');
        });
    }

    public function test_order_page_check_button_serves_cache_and_refresh_forces_live(): void
    {
        Setting::setValue('courier_check', 'pathao_users', 'fake@example.com');
        Setting::setValue('courier_check', 'pathao_passwords', 'fakepass');
        Setting::clearCache('courier_check');

        CourierCheckResult::create([
            'normalized_phone' => $this->order->normalized_phone,
            'raw_result' => ['pathao' => ['success' => 5, 'cancel' => 1, 'total' => 6, 'success_ratio' => 83.33]],
            'total_success' => 5,
            'total_cancel' => 1,
            'total_deliveries' => 6,
            'success_ratio' => 83.33,
            'couriers_ok' => 1,
            'couriers_failed' => 4,
            'checked_at' => now()->subHours(1),
            'last_order_id' => $this->order->id,
        ]);

        Http::fake();

        $cachedResponse = $this->actingAs($this->admin, 'web')
            ->postJson("/admin/orders/{$this->order->id}/courier-history-check", ['refresh' => false]);

        $cachedResponse->assertOk();
        $cachedResponse->assertJson(['success' => true, 'from_cache' => true]);
        Http::assertNothingSent();

        Http::fake([
            'merchant.pathao.com/api/v1/login' => Http::response(['access_token' => 'tok'], 200),
            'merchant.pathao.com/api/v1/user/success' => Http::response([
                'data' => ['customer' => ['successful_delivery' => 9, 'total_delivery' => 10]],
            ], 200),
        ]);

        $refreshResponse = $this->actingAs($this->admin, 'web')
            ->postJson("/admin/orders/{$this->order->id}/courier-history-check", ['refresh' => true]);

        $refreshResponse->assertOk();
        $refreshResponse->assertJson(['success' => true, 'from_cache' => false]);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'merchant.pathao.com/api/v1/login');
        });
    }
}
