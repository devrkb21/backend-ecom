<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
            \App\Models\ProductImage::where('image', $path)->delete();

            // Nullify references in other tables
            if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'image')) {
                \App\Models\Category::where('image', $path)->update(['image' => null]);
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'banner_image')) {
                \App\Models\Category::where('banner_image', $path)->update(['banner_image' => null]);
            }
            
            \App\Models\ProductVariant::where('image', $path)->update(['image' => null]);
            \App\Models\ProductAttributeValue::where('image', $path)->update(['image' => null]);
            
            \App\Models\LoyaltyReward::where('image', $path)->update(['image' => null]);
            \App\Models\FlashSale::where('banner_image', $path)->update(['banner_image' => null]);

            // For settings, set the value to empty string if it matches the image
            \App\Models\Setting::where('value', $path)->update(['value' => '']);
            
            // Also handle cases where the full URL might be saved
            if ($media->disk) {
                try {
                    $url = \Illuminate\Support\Facades\Storage::disk($media->disk)->url($path);
                    
                    \App\Models\ProductImage::where('image', $url)->delete();
                    if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'image')) {
                        \App\Models\Category::where('image', $url)->update(['image' => null]);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'banner_image')) {
                        \App\Models\Category::where('banner_image', $url)->update(['banner_image' => null]);
                    }
                    \App\Models\ProductVariant::where('image', $url)->update(['image' => null]);
                    \App\Models\ProductAttributeValue::where('image', $url)->update(['image' => null]);
                    \App\Models\LoyaltyReward::where('image', $url)->update(['image' => null]);
                    \App\Models\FlashSale::where('banner_image', $url)->update(['banner_image' => null]);
                    \App\Models\Setting::where('value', $url)->update(['value' => '']);
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
        
        return round($bytes, 2) . ' ' . $units[$i];
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
