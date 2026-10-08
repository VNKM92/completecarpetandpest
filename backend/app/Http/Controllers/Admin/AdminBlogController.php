<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Services\ActivityLogger;

class AdminBlogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Blog::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('published_at', 'desc')->get();
        $categories = BlogCategory::all();

        return response()->json([
            'success' => true,
            'data' => [
                'blogs' => $items,
                'items' => $items,
                'categories' => $categories,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title);

        $blog = Blog::create([
            'title' => $request->title,
            'slug' => $slug,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'featured_img' => $request->featuredImg ?: $request->featured_img,
            'author' => $request->author ?: 'Brisbane Carpet & Pest Experts',
            'read_time' => intval($request->readTime ?: 5),
            'category_id' => $request->categoryId ?: $request->category_id,
            'tags' => is_array($request->tags) ? implode(', ', $request->tags) : $request->tags,
            'meta_title' => $request->metaTitle ?: $request->meta_title,
            'meta_desc' => $request->metaDesc ?: $request->meta_desc,
            'status' => $request->status ?: 'PUBLISHED',
            'published_at' => now(),
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Blogs',
            entityId: (string) $blog->id,
            details: ['title' => $request->title, 'slug' => $slug],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $blog->fresh('category')], 201);
    }

    public function update(Request $request, $id = null)
    {
        $blogId = $id ?: $request->input('id');
        $blog = Blog::find($blogId);
        if (!$blog) {
            return response()->json(['success' => false, 'message' => 'Blog not found'], 404);
        }

        $updateData = [];
        if ($request->has('title')) $updateData['title'] = $request->title;
        if ($request->has('slug')) $updateData['slug'] = Str::slug($request->slug);
        if ($request->has('excerpt')) $updateData['excerpt'] = $request->excerpt;
        if ($request->has('content')) $updateData['content'] = $request->content;
        if ($request->has('featuredImg') || $request->has('featured_img')) {
            $updateData['featured_img'] = $request->featuredImg ?: $request->featured_img;
        }
        if ($request->has('author')) $updateData['author'] = $request->author;
        if ($request->has('readTime')) $updateData['read_time'] = intval($request->readTime);
        if ($request->has('categoryId') || $request->has('category_id')) {
            $updateData['category_id'] = $request->categoryId ?: $request->category_id;
        }
        if ($request->has('tags')) {
            $updateData['tags'] = is_array($request->tags) ? implode(', ', $request->tags) : $request->tags;
        }
        if ($request->has('status')) $updateData['status'] = $request->status;
        if ($request->has('metaTitle') || $request->has('meta_title')) {
            $updateData['meta_title'] = $request->metaTitle ?: $request->meta_title;
        }
        if ($request->has('metaDesc') || $request->has('meta_desc')) {
            $updateData['meta_desc'] = $request->metaDesc ?: $request->meta_desc;
        }

        $blog->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Blogs',
            entityId: (string) $blog->id,
            details: ['title' => $blog->title],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $blog->fresh('category')]);
    }

    public function destroy(Request $request, $id = null)
    {
        $blogId = $id ?: $request->query('id', $request->input('id'));
        $blog = Blog::find($blogId);
        if (!$blog) {
            return response()->json(['success' => false, 'message' => 'Blog not found'], 404);
        }

        $blog->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Blogs',
            entityId: (string) $blogId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Blog post deleted']);
    }
}
