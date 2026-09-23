<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsIntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@innercollection.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->seed(SettingSeeder::class);
    }

    public function test_revesms_provider_saves_canonical_urls(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/integrations', [
                'sms_enabled' => '1',
                'sms_provider' => 'revesms',
                'revesms_api_key' => 'reve-key',
                'revesms_secret_key' => 'reve-secret',
                'revesms_sender_id' => '8809612',
                'revesms_client_id' => 'client-1',
                'sms_api_base_url' => 'https://attacker.example.com/sendtext',
                'sms_balance_url' => 'https://attacker.example.com/balance',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'group' => 'integration',
            'key' => 'sms_api_base_url',
            'value' => SmsService::REVESMS_SEND_URL,
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'integration',
            'key' => 'sms_balance_url',
            'value' => SmsService::REVESMS_BALANCE_URL,
        ]);
    }

    public function test_bulksmsbd_provider_saves_canonical_urls(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/integrations', [
                'sms_enabled' => '1',
                'sms_provider' => 'bulksmsbd',
                'sms_api_key' => 'bulk-key',
                'sms_sender_id' => 'bulk-sender',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'group' => 'integration',
            'key' => 'sms_api_base_url',
            'value' => SmsService::BULKSMSBD_SEND_URL,
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'integration',
            'key' => 'sms_balance_url',
            'value' => SmsService::BULKSMSBD_BALANCE_URL,
        ]);
    }

    public function test_custom_provider_saves_manual_urls(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/integrations', [
                'sms_enabled' => '1',
                'sms_provider' => 'custom',
                'custom_sms_api_key' => 'custom-key',
                'custom_sms_send_url' => 'https://sms.gateway.example.com/send',
                'custom_sms_balance_url' => 'https://sms.gateway.example.com/balance',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'group' => 'integration',
            'key' => 'custom_sms_send_url',
            'value' => 'https://sms.gateway.example.com/send',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'integration',
            'key' => 'sms_api_base_url',
            'value' => 'https://sms.gateway.example.com/send',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'integration',
            'key' => 'sms_balance_url',
            'value' => 'https://sms.gateway.example.com/balance',
        ]);
    }

    public function test_sms_provider_validation_rejects_unknown_values(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/integrations', [
                'sms_enabled' => '1',
                'sms_provider' => 'not-a-provider',
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sms_provider']);
    }

    public function test_send_test_sms_fails_when_integration_disabled(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->post('/admin/settings/integrations/sms-test', [
                'sms_test_number' => '8801712345678',
                'sms_test_message' => 'Hello test',
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'SMS integration is disabled.',
            ]);
    }

    public function test_send_test_sms_sends_through_active_provider(): void
    {
        Http::fake([
            'www.bulksmsbd.net/*' => Http::response(['response_code' => 202], 200),
        ]);

        Setting::setValue('integration', 'sms_enabled', '1');
        Setting::setValue('integration', 'sms_provider', 'bulksmsbd');
        Setting::setValue('integration', 'sms_api_key', 'bulk-key');
        Setting::setValue('integration', 'sms_sender_id', 'bulk-sender');

        $response = $this->actingAs($this->admin, 'web')
            ->post('/admin/settings/integrations/sms-test', [
                'sms_test_number' => '+8801712345678',
                'sms_test_message' => 'Integration test',
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Test SMS sent successfully to +8801712345678.',
            ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'bulksmsbd.net/api/smsapi')
                && $request['number'] === '8801712345678'
                && $request['message'] === 'Integration test';
        });
    }

    public function test_send_test_sms_reports_provider_failure(): void
    {
        Http::fake([
            'www.bulksmsbd.net/*' => Http::response(['response_code' => 1007], 200),
        ]);

        Setting::setValue('integration', 'sms_enabled', '1');
        Setting::setValue('integration', 'sms_provider', 'bulksmsbd');
        Setting::setValue('integration', 'sms_api_key', 'bulk-key');
        Setting::setValue('integration', 'sms_sender_id', 'bulk-sender');

        $response = $this->actingAs($this->admin, 'web')
            ->post('/admin/settings/integrations/sms-test', [
                'sms_test_number' => '8801712345678',
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_reve_sms_send_uses_reve_sender_id_and_endpoint(): void
    {
        Http::fake([
            '*smpp.revesms.com*' => Http::response('0', 200),
        ]);

        Setting::setValue('integration', 'sms_enabled', '1');
        Setting::setValue('integration', 'sms_provider', 'revesms');
        Setting::setValue('integration', 'revesms_api_key', 'reve-key');
        Setting::setValue('integration', 'revesms_secret_key', 'reve-secret');
        Setting::setValue('integration', 'revesms_sender_id', '8809612');
        Setting::setValue('integration', 'sms_sender_id', 'bulk-sender');

        $result = app(SmsService::class)->send('8801712345678', 'Hello REVE');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'smpp.revesms.com')
                && str_contains($request->url(), 'apikey=reve-key')
                && str_contains($request->url(), 'callerID=8809612')
                && ! str_contains($request->url(), 'callerID=bulk-sender');
        });
    }

    public function test_custom_mode_falls_back_to_reve_credentials(): void
    {
        Http::fake([
            '*' => Http::response('0', 200),
        ]);

        Setting::setValue('integration', 'sms_enabled', '1');
        Setting::setValue('integration', 'sms_provider', 'custom');
        Setting::setValue('integration', 'revesms_api_key', 'reve-key');
        Setting::setValue('integration', 'revesms_secret_key', 'reve-secret');
        Setting::setValue('integration', 'revesms_sender_id', '8809612');
        // Deliberately no custom_* credentials.

        $result = app(SmsService::class)->send('8801712345678', 'Hello custom');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'revesms');
        });
    }
}
