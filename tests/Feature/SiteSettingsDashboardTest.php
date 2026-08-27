<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsDashboardTest extends TestCase
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

    public function test_admin_can_view_settings_dashboard(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get('/admin/settings/site');

        $response->assertOk()
            ->assertSee('Hero Section')
            ->assertSee('Navigation Menu')
            ->assertSee('Appearance & Colors');

        $systemResponse = $this->actingAs($this->admin, 'web')
            ->get('/admin/settings/system');

        $systemResponse->assertOk()
            ->assertSee('General Settings')
            ->assertSee('Checkout Settings')
            ->assertSee('Invoice Settings')
            ->assertSee('Integrations')
            ->assertSee('SMS Templates');
    }

    public function test_admin_can_update_general_settings(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/site/general', [
                'settings' => [
                    'site_title' => 'Inner Collection Updated',
                    'site_description' => 'A very fine store.',
                    'order_number_prefix' => 'IC',
                    'order_number_generation_mode' => 'timestamp_random',
                ],
            ]);

        $response->assertRedirect('/admin/settings/system?group=general');

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'site_title',
            'value' => 'Inner Collection Updated',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'order_number_prefix',
            'value' => 'IC',
        ]);
    }

    public function test_admin_can_update_appearance_colors(): void
    {
        // Call GET first to initialize settings
        $this->actingAs($this->admin, 'web')->get('/admin/settings/site');

        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/site/appearance', [
                'settings' => [
                    'primary_color' => '#123456',
                    'primary_hover_color' => '#654321',
                ],
            ]);

        $response->assertRedirect('/admin/settings/site?group=appearance');

        $this->assertDatabaseHas('settings', [
            'group' => 'appearance',
            'key' => 'primary_color',
            'value' => '#123456',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'appearance',
            'key' => 'primary_hover_color',
            'value' => '#654321',
        ]);
    }

    public function test_admin_can_update_navigation_menu(): void
    {
        // Call GET first to initialize settings
        $this->actingAs($this->admin, 'web')->get('/admin/settings/site');

        $menuData = [
            [
                'label' => 'Home',
                'url' => '/',
                'type' => 'link',
                'highlight' => false,
                'children' => [],
            ],
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/site/navigation', [
                'settings' => [
                    'header_menu' => json_encode($menuData),
                ],
            ]);

        $response->assertRedirect('/admin/settings/site?group=navigation');

        $setting = Setting::where('group', 'navigation')->where('key', 'header_menu')->first();
        $this->assertNotNull($setting);
        $decoded = json_decode($setting->value, true);
        $this->assertEquals('Home', $decoded[0]['label']);
    }

    public function test_admin_can_update_hero_banners(): void
    {
        // Call GET first to initialize settings
        $this->actingAs($this->admin, 'web')->get('/admin/settings/site');

        $bannersData = [
            [
                'title' => 'Vibrant Collection',
                'subtitle' => 'Special discount active',
                'description' => 'Pure styling.',
                'image' => 'media/banners/banner1.webp',
                'button_text' => 'Grab Now',
                'button_link' => '/offers',
                'enabled' => true,
            ],
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->put('/admin/settings/site/hero', [
                'settings' => [
                    'banners' => json_encode($bannersData),
                ],
            ]);

        $response->assertRedirect('/admin/settings/site?group=hero');

        $setting = Setting::where('group', 'hero')->where('key', 'banners')->first();
        $this->assertNotNull($setting);
        $decoded = json_decode($setting->value, true);
        $this->assertEquals('Vibrant Collection', $decoded[0]['title']);
    }
}
