<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Page;
use App\Services\ActivityLogger;

class AdminPageController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Page::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $pages = $query->orderBy('title', 'asc')->get();

        return response()->json(['success' => true, 'data' => $pages]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
        ]);

        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title);

        $page = Page::create([
            'title' => $request->title,
            'slug' => $slug,
            'heading' => $request->heading,
            'subheading' => $request->subheading,
            'content' => $request->content,
            'banner_image' => $request->bannerImage ?: $request->banner_image,
            'meta_title' => $request->metaTitle ?: $request->meta_title,
            'meta_desc' => $request->metaDesc ?: $request->meta_desc,
            'meta_keywords' => $request->metaKeywords ?: $request->meta_keywords,
            'canonical_url' => $request->canonicalUrl ?: $request->canonical_url,
            'og_image' => $request->ogImage ?: $request->og_image,
            'robots' => $request->robots ?: 'index, follow',
            'custom_schema' => $request->customSchema ?: $request->custom_schema,
            'is_published' => $request->has('isPublished') ? boolval($request->isPublished) : true,
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Pages',
            entityId: (string) $page->id,
            details: ['title' => $page->title, 'slug' => $slug],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $page], 201);
    }

    public function update(Request $request, $id = null)
    {
        $pageId = $id ?: $request->input('id');
        $page = Page::find($pageId);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found'], 404);
        }

        $updateData = [];
        if ($request->has('title')) $updateData['title'] = $request->title;
        if ($request->has('slug')) $updateData['slug'] = Str::slug($request->slug);
        if ($request->has('heading')) $updateData['heading'] = $request->heading;
        if ($request->has('subheading')) $updateData['subheading'] = $request->subheading;
        if ($request->has('content')) $updateData['content'] = $request->content;
        if ($request->has('bannerImage') || $request->has('banner_image')) {
            $updateData['banner_image'] = $request->bannerImage ?: $request->banner_image;
        }
        if ($request->has('metaTitle') || $request->has('meta_title')) {
            $updateData['meta_title'] = $request->metaTitle ?: $request->meta_title;
        }
        if ($request->has('metaDesc') || $request->has('meta_desc')) {
            $updateData['meta_desc'] = $request->metaDesc ?: $request->meta_desc;
        }
        if ($request->has('metaKeywords') || $request->has('meta_keywords')) {
            $updateData['meta_keywords'] = $request->metaKeywords ?: $request->meta_keywords;
        }
        if ($request->has('canonicalUrl') || $request->has('canonical_url')) {
            $updateData['canonical_url'] = $request->canonicalUrl ?: $request->canonical_url;
        }
        if ($request->has('ogImage') || $request->has('og_image')) {
            $updateData['og_image'] = $request->ogImage ?: $request->og_image;
        }
        if ($request->has('robots')) $updateData['robots'] = $request->robots;
        if ($request->has('customSchema') || $request->has('custom_schema')) {
            $updateData['custom_schema'] = $request->customSchema ?: $request->custom_schema;
        }
        if ($request->has('isPublished') || $request->has('is_published')) {
            $updateData['is_published'] = boolval($request->isPublished ?? $request->is_published);
        }

        $page->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Pages',
            entityId: (string) $page->id,
            details: ['title' => $page->title],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $page]);
    }

    public function destroy(Request $request, $id = null)
    {
        $pageId = $id ?: $request->query('id', $request->input('id'));
        $page = Page::find($pageId);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found'], 404);
        }

        $page->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Pages',
            entityId: (string) $pageId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Page deleted']);
    }
}
