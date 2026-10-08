<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;
use App\Services\ActivityLogger;

class AdminMediaController extends Controller
{
    public function index(Request $request)
    {
        $folder = $request->query('folder');
        $search = $request->query('search');

        $query = Media::query();

        if ($folder && $folder !== 'all') {
            $query->where('folder', $folder);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                  ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'url' => 'required|string',
        ]);

        $media = Media::create([
            'filename' => $request->filename,
            'url' => $request->url,
            'mime_type' => $request->mimeType ?: $request->mime_type,
            'size' => $request->size ? intval($request->size) : null,
            'alt_text' => $request->altText ?: $request->alt_text,
            'folder' => $request->folder ?: 'general',
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Media',
            entityId: (string) $media->id,
            details: ['filename' => $media->filename],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $media], 201);
    }

    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $folder = $request->input('folder', 'uploads');

        // Store file in public disk
        $path = $file->storeAs("media/{$folder}", $filename, 'public');
        $url = "/storage/{$path}";

        $media = Media::create([
            'filename' => $file->getClientOriginalName(),
            'url' => $url,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $request->input('altText', $file->getClientOriginalName()),
            'folder' => $folder,
        ]);

        ActivityLogger::log(
            action: 'UPLOAD',
            module: 'Media',
            entityId: (string) $media->id,
            details: ['url' => $url],
            request: $request
        );

        return response()->json([
            'success' => true,
            'url' => $url,
            'data' => $media,
        ], 201);
    }

    public function destroy(Request $request, $id = null)
    {
        $mediaId = $id ?: $request->query('id', $request->input('id'));
        $media = Media::find($mediaId);
        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Media not found'], 404);
        }

        $media->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Media',
            entityId: (string) $mediaId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Media deleted']);
    }
}
