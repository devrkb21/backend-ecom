<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFieldManagerTest extends TestCase
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
        
        // Seed default checkout fields if necessary, or let SiteSettingController handle it
    }

    public function test_admin_can_view_checkout_field_manager(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get('/admin/settings/system?group=checkout');

        $response->assertOk()
            ->assertSee('Checkout Settings')
            ->assertSee('Billing Fields')
            ->assertSee('Shipping Fields')
            ->assertSee('Additional Fields')
            ->assertSee('Field Properties');
    }

    public function test_admin_can_update_checkout_settings(): void
    {
        $dummyFields = [
            'billing' => [
                [
                    'id' => 'field_billing_first_name',
                    'section' => 'billing',
                    'key' => 'billing_first_name',
                    'type' => 'text',
                    'label' => 'First Name Custom',
                    'placeholder' => 'Enter First Name',
                    'validations' => ['required'],
                    'required' => true,
                    'enabled' => true,
                    'sort_order' => 1,
                ]
            ],
            'shipping' => [],
            'additional' => []
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/site/checkout', [
                'settings' => [
                    'checkout_form_enabled' => '1',
                    'tax_enabled' => '1',
                    'tax_percentage' => '7.5',
                    'checkout_fields_schema' => json_encode($dummyFields),
                ]
            ]);

        $response->assertRedirect();
        
        // Assert the values are stored in the database
        $this->assertDatabaseHas('settings', [
            'group' => 'checkout',
            'key' => 'checkout_form_enabled',
            'value' => '1',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'checkout',
            'key' => 'enable_guest_checkout',
            'value' => '0',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'checkout',
            'key' => 'tax_enabled',
            'value' => '1',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'checkout',
            'key' => 'tax_percentage',
            'value' => '7.5',
        ]);

        // Validate JSON schema stored
        $schemaSetting = Setting::where('group', 'checkout')->where('key', 'checkout_fields_schema')->first();
        $this->assertNotNull($schemaSetting);
        $decoded = json_decode($schemaSetting->value, true);
        $this->assertEquals('First Name Custom', $decoded['billing'][0]['label']);
    }
}
