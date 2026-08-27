<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'filename',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
        'title',
        'collection',
    ];

    protected $appends = ['url', 'thumbnail_url', 'formatted_size'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($media) {
            $path = $media->path;

            // Delete product images completely
            ProductImage::where('image', $path)->delete();

            // Nullify references in other tables
            if (Schema::hasColumn('categories', 'image')) {
                Category::where('image', $path)->update(['image' => null]);
            }
            if (Schema::hasColumn('categories', 'banner_image')) {
                Category::where('banner_image', $path)->update(['banner_image' => null]);
            }

            ProductVariant::where('image', $path)->update(['image' => null]);
            ProductAttributeValue::where('image', $path)->update(['image' => null]);

            LoyaltyReward::where('image', $path)->update(['image' => null]);
            FlashSale::where('banner_image', $path)->update(['banner_image' => null]);

            // For settings, set the value to empty string if it matches the image
            Setting::where('value', $path)->update(['value' => '']);

            // Also handle cases where the full URL might be saved
            if ($media->disk) {
                try {
                    $url = Storage::disk($media->disk)->url($path);

                    ProductImage::where('image', $url)->delete();
                    if (Schema::hasColumn('categories', 'image')) {
                        Category::where('image', $url)->update(['image' => null]);
                    }
                    if (Schema::hasColumn('categories', 'banner_image')) {
                        Category::where('banner_image', $url)->update(['banner_image' => null]);
                    }
                    ProductVariant::where('image', $url)->update(['image' => null]);
                    ProductAttributeValue::where('image', $url)->update(['image' => null]);
                    LoyaltyReward::where('image', $url)->update(['image' => null]);
                    FlashSale::where('banner_image', $url)->update(['banner_image' => null]);
                    Setting::where('value', $url)->update(['value' => '']);
                } catch (\Exception $e) {
                    // Ignore URL generation errors
                }
            }
        });
    }

    /**
     * Get the full URL to the media file
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get thumbnail URL (same as main for now, could be different if thumbnails are generated)
     */
    public function getThumbnailUrlAttribute(): string
    {
        return $this->url;
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if media is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Scope for images only
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope for specific collection
     */
    public function scopeCollection($query, string $collection)
    {
        return $query->where('collection', $collection);
    }
}
