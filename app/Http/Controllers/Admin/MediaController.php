<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $media = $query->paginate(24)->withQueryString();

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
                // Generate unique filename
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('media/' . date('Y/m'), $filename, 'public');

                // Get image dimensions
                $dimensions = @getimagesize($file->getRealPath());
                $width = $dimensions[0] ?? null;
                $height = $dimensions[1] ?? null;

                $media = Media::create([
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'disk' => 'public',
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
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

        $media = Media::whereIn('id', $request->input('ids'))->get();

        foreach ($media as $item) {
            Storage::disk($item->disk)->delete($item->path);
            $item->delete();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => count($request->input('ids')) . ' file(s) deleted successfully',
            ]);
        }

        return redirect()->back()->with('success', count($request->input('ids')) . ' file(s) deleted successfully');
    }
}
