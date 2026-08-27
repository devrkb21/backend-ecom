<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Product $product;

    private LandingPage $landingPage;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Fruits',
            'slug' => 'fruits',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Juicy Rajshahi Mango',
            'slug' => 'juicy-rajshahi-mango',
            'regular_price' => 200,
            'sale_price' => 150,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $this->landingPage = LandingPage::create([
            'product_id' => $this->product->id,
            'title' => 'Vibrant Mango Fest',
            'slug' => 'mango-fest',
            'template_type' => 'am',
            'theme_color' => '#437A2C',
            'features' => [
                ['title' => 'Direct from Garden', 'icon' => 'bi-apple', 'description' => 'Pure and sweet.'],
            ],
            'testimonials' => [
                ['name' => 'Abid Hasan', 'rating' => 5, 'comment' => 'Super fresh mangoes!'],
            ],
            'is_active' => true,
            'views_count' => 0,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@innercollection.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_api_requires_internal_secret_token(): void
    {
        $response = $this->getJson("/api/v1/landing-pages/slug/{$this->landingPage->slug}");
        $response->assertStatus(403);
    }

    public function test_api_returns_landing_page_with_secret_and_increments_views(): void
    {
        $secret = config('shop.internal_api_secret', 'super_secret_key_123');

        $response = $this->withHeaders([
            'X-Internal-Secret' => $secret,
        ])->getJson("/api/v1/landing-pages/slug/{$this->landingPage->slug}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'mango-fest')
            ->assertJsonPath('data.product.name', 'Juicy Rajshahi Mango');

        $this->assertEquals(1, $this->landingPage->fresh()->views_count);
    }

    public function test_api_returns_404_on_missing_or_inactive_slug(): void
    {
        $secret = config('shop.internal_api_secret', 'super_secret_key_123');

        // Missing slug
        $response = $this->withHeaders([
            'X-Internal-Secret' => $secret,
        ])->getJson('/api/v1/landing-pages/slug/non-existent-slug');
        $response->assertStatus(404);

        // Inactive landing page
        $this->landingPage->update(['is_active' => false]);
        $response2 = $this->withHeaders([
            'X-Internal-Secret' => $secret,
        ])->getJson("/api/v1/landing-pages/slug/{$this->landingPage->slug}");
        $response2->assertStatus(404);
    }

    public function test_admin_landing_pages_crud_requires_authentication(): void
    {
        $this->get('/admin/landing-pages')->assertRedirect('/login');
        $this->get('/admin/landing-pages/create')->assertRedirect('/login');
        $this->post('/admin/landing-pages', [])->assertRedirect('/login');
    }

    public function test_admin_can_view_landing_pages_list(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get('/admin/landing-pages');

        $response->assertOk()
            ->assertSee('Vibrant Mango Fest')
            ->assertSee('mango-fest');
    }

    public function test_admin_can_create_landing_page(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->post('/admin/landing-pages', [
                'product_id' => $this->product->id,
                'product_ids' => [$this->product->id],
                'title' => 'New Apparel Landing Page',
                'slug' => 'apparel-page',
                'template_type' => 'clothing',
                'theme_color' => '#000000',
                'features' => [
                    ['title' => 'Fit Guarantee', 'icon' => 'bi-award', 'description' => 'Great styling.'],
                ],
                'testimonials' => [
                    ['name' => 'Sumona Roy', 'rating' => 5, 'comment' => 'Beautiful stitching.'],
                ],
                'is_active' => '1',
            ]);

        $response->assertRedirect('/admin/landing-pages');
        $this->assertDatabaseHas('landing_pages', [
            'slug' => 'apparel-page',
            'template_type' => 'clothing',
            'theme_color' => '#000000',
        ]);
    }

    public function test_admin_can_update_landing_page(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put("/admin/landing-pages/{$this->landingPage->id}", [
                'product_id' => $this->product->id,
                'product_ids' => [$this->product->id],
                'title' => 'Updated Mango Fest Title',
                'slug' => 'mango-fest-updated',
                'template_type' => 'am',
                'theme_color' => '#437A2C',
                'is_active' => '1',
            ]);

        $response->assertRedirect('/admin/landing-pages');
        $this->assertDatabaseHas('landing_pages', [
            'id' => $this->landingPage->id,
            'title' => 'Updated Mango Fest Title',
            'slug' => 'mango-fest-updated',
        ]);
    }
}
