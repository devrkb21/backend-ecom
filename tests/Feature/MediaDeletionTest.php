<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_can_be_deleted_via_controller_without_column_errors(): void
    {
        // Fake the public_root storage disk
        Storage::fake('public_root');

        // Create an admin user for authentication
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@innercollection.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create a category (without image column in database)
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        // Create a media item
        $media = Media::create([
            'filename' => 'main_logo.png',
            'original_name' => 'main_logo.png',
            'path' => 'media/2026/05/main_logo.png',
            'disk' => 'public_root',
            'mime_type' => 'image/png',
            'size' => 1024,
            'collection' => 'default',
        ]);

        // Write a fake file to storage
        Storage::disk('public_root')->put('media/2026/05/main_logo.png', 'fake image content');

        // Assert file exists before deletion
        Storage::disk('public_root')->assertExists('media/2026/05/main_logo.png');

        // Delete the media via the controller destroy route as admin
        $response = $this->actingAs($admin, 'web')
            ->delete("/admin/media/{$media->id}");

        $response->assertRedirect();

        // Assert media record is deleted from database
        $this->assertDatabaseMissing('media', [
            'id' => $media->id,
        ]);

        // Assert file is deleted from storage
        Storage::disk('public_root')->assertMissing('media/2026/05/main_logo.png');
    }
}
