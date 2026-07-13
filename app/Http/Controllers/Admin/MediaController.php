<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Display media library page
     */
    public function index(Request $request)
    {
        $query = Media::images()->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        if ($collection = $request->input('collection')) {
            $query->collection($collection);
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $media = $query->paginate($perPage)->withQueryString();

        return view('admin.media.index', compact('media'));
    }

    /**
     * Get media list as JSON (for AJAX modal)
     */
    public function list(Request $request)
    {
        $query = Media::images()->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        if ($collection = $request->input('collection')) {
            $query->collection($collection);
        }

        $media = $query->paginate(24);

        return response()->json([
            'success' => true,
            'data' => $media->items(),
            'pagination' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ]
        ]);
    }

    /**
     * Upload new media file(s)
     */
    /**
     * Convert image to WebP format using Imagick or GD
     */
    public static function convertToWebp(string $sourcePath, string $destinationPath, int $quality = 85): bool
    {
        // Try Imagick first
        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick();
                $imagick->readImage($sourcePath);
                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality($quality);
                $imagick->writeImage($destinationPath);
                $imagick->clear();
                $imagick->destroy();
                return true;
            } catch (\Exception $e) {
                // Fallback to GD
            }
        }

        // Fallback to GD
        if (function_exists('imagewebp')) {
            try {
                $info = @getimagesize($sourcePath);
                if (!$info) {
                    return false;
                }

                $mime = $info['mime'];
                switch ($mime) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        $image = @imagecreatefromjpeg($sourcePath);
                        break;
                    case 'image/png':
                        $image = @imagecreatefrompng($sourcePath);
                        break;
                    case 'image/gif':
                        $image = @imagecreatefromgif($sourcePath);
                        break;
                    case 'image/webp':
                        $image = @imagecreatefromwebp($sourcePath);
                        break;
                    default:
                        return false;
                }

                if (!$image) {
                    return false;
                }

                // Preserve alpha transparency
                imagealphablending($image, false);
                imagesavealpha($image, true);

                $result = @imagewebp($image, $destinationPath, $quality);
                imagedestroy($image);

                return $result;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Upload new media file(s)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|image|mimes:jpg,jpeg,png,gif,webp,svg|max:5120', // 5MB max
            'collection' => 'nullable|string|max:50',
        ]);

        $collection = $request->input('collection', 'default');
        $uploaded = [];

        foreach ($request->file('files') as $file) {
            try {
                $originalName = trim((string) basename((string) $file->getClientOriginalName()));
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $nameWithoutExt) ?? '';
                $safeName = $safeName !== '' ? $safeName : 'upload';
                
                $originalExtension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                $fileSize = $file->getSize();
                
                $subFolder = 'media/' . now()->format('Y/m');
                
                if ($mimeType === 'image/svg+xml' || $originalExtension === 'svg') {
                    $filename = $safeName . '_' . uniqid() . '.svg';
                    $path = $file->storeAs($subFolder, $filename, 'public_root');
                } else {
                    $filename = $safeName . '_' . uniqid() . '.webp';
                    $tempPath = $file->getRealPath();
                    $fullSubFolder = public_path($subFolder);
                    
                    if (!file_exists($fullSubFolder)) {
                        mkdir($fullSubFolder, 0755, true);
                    }
                    
                    $destinationPath = $fullSubFolder . '/' . $filename;
                    $converted = self::convertToWebp($tempPath, $destinationPath, 85);
                    
                    if ($converted) {
                        $path = $subFolder . '/' . $filename;
                        $fileSize = @filesize($destinationPath) ?: $file->getSize();
                        $mimeType = 'image/webp';
                    } else {
                        // Fallback
                        $filename = $safeName . '_' . uniqid() . '.' . $originalExtension;
                        $path = $file->storeAs($subFolder, $filename, 'public_root');
                    }
                }

                // Get image dimensions
                $dimensions = @getimagesize(public_path($path));
                $width = $dimensions[0] ?? null;
                $height = $dimensions[1] ?? null;

                $media = Media::create([
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'disk' => 'public_root',
                    'mime_type' => $mimeType,
                    'size' => $fileSize,
                    'width' => $width,
                    'height' => $height,
                    'collection' => $collection,
                ]);

                $uploaded[] = $media;
            } catch (\Exception $e) {
                // Continue with other files if one fails
                continue;
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => count($uploaded) . ' file(s) uploaded successfully',
                'data' => $uploaded,
            ]);
        }

        return redirect()->back()->with('success', count($uploaded) . ' file(s) uploaded successfully');
    }

    /**
     * Get single media details
     */
    public function show(Media $media)
    {
        return response()->json([
            'success' => true,
            'data' => $media,
        ]);
    }

    /**
     * Update media metadata
     */
    public function update(Request $request, Media $media)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $media->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Media updated successfully',
                'data' => $media,
            ]);
        }

        return redirect()->back()->with('success', 'Media updated successfully');
    }

    /**
     * Delete media file
     */
    public function destroy(Media $media)
    {
        // Delete file from storage
        Storage::disk($media->disk)->delete($media->path);
        
        $media->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Media deleted successfully');
    }

    /**
     * Bulk delete media files
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:media,id',
        ]);

        Media::whereIn('id', $request->input('ids'))
            ->get()
            ->each(function (Media $item): void {
                Storage::disk($item->disk)->delete($item->path);
                $item->delete();
            });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => count($request->input('ids')) . ' file(s) deleted successfully',
            ]);
        }

        return redirect()->back()->with('success', count($request->input('ids')) . ' file(s) deleted successfully');
    }

    /**
     * Bulk convert all non-webp raster images to WebP format
     */
    public function bulkConvertWebp(Request $request)
    {
        $mediaItems = Media::images()
            ->where('mime_type', '!=', 'image/webp')
            ->where('mime_type', '!=', 'image/svg+xml')
            ->select(['id', 'original_name', 'filename'])
            ->get();

        return response()->json([
            'success' => true,
            'items' => $mediaItems->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->original_name ?: $item->filename
            ])
        ]);
    }

    /**
     * Convert one single media item to WebP format and update references
     */
    public function singleConvertWebp(Request $request, Media $media)
    {
        $oldPath = $media->path;
        
        // Get extension
        $originalExtension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION));
        if ($originalExtension === 'svg' || $originalExtension === 'webp' || $media->mime_type === 'image/webp') {
            return response()->json([
                'success' => true,
                'message' => 'File already WebP or SVG.',
            ]);
        }

        $nameWithoutExt = pathinfo($oldPath, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $nameWithoutExt) ?? '';
        $safeName = $safeName !== '' ? $safeName : 'converted';
        
        $subFolder = dirname($oldPath);
        $fullSubFolder = public_path($subFolder);
        
        $newFilename = $safeName . '_' . uniqid() . '.webp';
        $sourcePath = public_path($oldPath);
        $destinationPath = $fullSubFolder . '/' . $newFilename;

        if (!file_exists($sourcePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Source file not found.',
            ], 404);
        }

        $converted = self::convertToWebp($sourcePath, $destinationPath, 85);

        if ($converted) {
            $newPath = $subFolder . '/' . $newFilename;
            $fileSize = @filesize($destinationPath) ?: $media->size;
            
            // Get image dimensions
            $dimensions = @getimagesize($destinationPath);
            $width = $dimensions[0] ?? $media->width;
            $height = $dimensions[1] ?? $media->height;

            // Update Media record
            $media->update([
                'filename' => $newFilename,
                'path' => $newPath,
                'mime_type' => 'image/webp',
                'size' => $fileSize,
                'width' => $width,
                'height' => $height,
            ]);

            // Update all database tables references to avoid 404
            // Update product_images
            \DB::table('product_images')->where('image', $oldPath)->update(['image' => $newPath]);
            \DB::table('product_images')->where('image', '/' . $oldPath)->update(['image' => '/' . $newPath]);
            \DB::table('product_images')->where('image', 'storage/' . $oldPath)->update(['image' => 'storage/' . $newPath]);
            \DB::table('product_images')->where('image', '/storage/' . $oldPath)->update(['image' => '/storage/' . $newPath]);
            
            // Update product_variants
            \DB::table('product_variants')->where('image', $oldPath)->update(['image' => $newPath]);
            \DB::table('product_variants')->where('image', '/' . $oldPath)->update(['image' => '/' . $newPath]);
            \DB::table('product_variants')->where('image', 'storage/' . $oldPath)->update(['image' => 'storage/' . $newPath]);
            \DB::table('product_variants')->where('image', '/storage/' . $oldPath)->update(['image' => '/storage/' . $newPath]);
            
            // Update product_attribute_values
            \DB::table('product_attribute_values')->where('image', $oldPath)->update(['image' => $newPath]);
            \DB::table('product_attribute_values')->where('image', '/' . $oldPath)->update(['image' => '/' . $newPath]);
            \DB::table('product_attribute_values')->where('image', 'storage/' . $oldPath)->update(['image' => 'storage/' . $newPath]);
            \DB::table('product_attribute_values')->where('image', '/storage/' . $oldPath)->update(['image' => '/storage/' . $newPath]);
            
            // Update categories
            if (\Schema::hasTable('categories')) {
                \DB::table('categories')->where('image', $oldPath)->update(['image' => $newPath]);
                \DB::table('categories')->where('image', '/' . $oldPath)->update(['image' => '/' . $newPath]);
                \DB::table('categories')->where('image', 'storage/' . $oldPath)->update(['image' => 'storage/' . $newPath]);
                \DB::table('categories')->where('image', '/storage/' . $oldPath)->update(['image' => '/storage/' . $newPath]);

                if (\Schema::hasColumn('categories', 'banner_image')) {
                    \DB::table('categories')->where('banner_image', $oldPath)->update(['banner_image' => $newPath]);
                    \DB::table('categories')->where('banner_image', '/' . $oldPath)->update(['banner_image' => '/' . $newPath]);
                    \DB::table('categories')->where('banner_image', 'storage/' . $oldPath)->update(['banner_image' => 'storage/' . $newPath]);
                    \DB::table('categories')->where('banner_image', '/storage/' . $oldPath)->update(['banner_image' => '/storage/' . $newPath]);
                }
            }
            
            // Update landing_pages
            if (\Schema::hasTable('landing_pages')) {
                \DB::table('landing_pages')->where('banner_image', $oldPath)->update(['banner_image' => $newPath]);
                \DB::table('landing_pages')->where('banner_image', '/' . $oldPath)->update(['banner_image' => '/' . $newPath]);
                \DB::table('landing_pages')->where('banner_image', 'storage/' . $oldPath)->update(['banner_image' => 'storage/' . $newPath]);
                \DB::table('landing_pages')->where('banner_image', '/storage/' . $oldPath)->update(['banner_image' => '/storage/' . $newPath]);
            }
            
            // Update flash_sales
            if (\Schema::hasTable('flash_sales')) {
                \DB::table('flash_sales')->where('banner_image', $oldPath)->update(['banner_image' => $newPath]);
                \DB::table('flash_sales')->where('banner_image', '/' . $oldPath)->update(['banner_image' => '/' . $newPath]);
                \DB::table('flash_sales')->where('banner_image', 'storage/' . $oldPath)->update(['banner_image' => 'storage/' . $newPath]);
                \DB::table('flash_sales')->where('banner_image', '/storage/' . $oldPath)->update(['banner_image' => '/storage/' . $newPath]);
            }

            // Update settings
            \DB::table('settings')->where('value', $oldPath)->update(['value' => $newPath]);
            \DB::table('settings')->where('value', '/' . $oldPath)->update(['value' => '/' . $newPath]);
            \DB::table('settings')->where('value', 'storage/' . $oldPath)->update(['value' => 'storage/' . $newPath]);
            \DB::table('settings')->where('value', '/storage/' . $oldPath)->update(['value' => '/storage/' . $newPath]);
            
            // Update JSON arrays in settings
            $heroBannersSetting = \DB::table('settings')->where('group', 'hero')->where('key', 'banners')->first();
            if ($heroBannersSetting && $heroBannersSetting->value) {
                $value = $heroBannersSetting->value;
                $value = str_replace(
                    [
                        json_encode($oldPath),
                        json_encode('/' . $oldPath),
                        json_encode('storage/' . $oldPath),
                        json_encode('/storage/' . $oldPath)
                    ],
                    json_encode($newPath),
                    $value
                );
                \DB::table('settings')->where('group', 'hero')->where('key', 'banners')->update(['value' => $value]);
            }

            // Update products text contents (descriptions)
            \DB::statement("UPDATE products SET description = REPLACE(description, ?, ?)", [$oldPath, $newPath]);
            \DB::statement("UPDATE products SET short_description = REPLACE(short_description, ?, ?)", [$oldPath, $newPath]);

            // Update pages text contents
            if (\Schema::hasTable('pages')) {
                \DB::statement("UPDATE pages SET content = REPLACE(content, ?, ?)", [$oldPath, $newPath]);
            }

            // Delete old file
            if (file_exists($sourcePath)) {
                @unlink($sourcePath);
            }

            // Clear settings cache
            \App\Models\Setting::clearCache('hero');
            \App\Models\Setting::clearCache('general');
            \App\Models\Setting::clearCache('invoice');

            return response()->json([
                'success' => true,
                'message' => 'Successfully converted file to WebP.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'WebP conversion failed.',
        ], 500);
    }
}
