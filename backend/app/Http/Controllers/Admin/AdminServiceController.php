<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ActivityLogger;

class AdminServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Service::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('short_desc', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('order', 'asc')->get();
        $categories = ServiceCategory::orderBy('order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'categories' => $categories,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);

        $features = $request->features;
        if (is_array($features) || is_object($features)) {
            $features = json_encode($features);
        }

        $tabData = $request->tabData;
        if (is_array($tabData) || is_object($tabData)) {
            $tabData = json_encode($tabData);
        }

        $service = Service::create([
            'name' => $request->name,
            'slug' => $slug,
            'category_id' => $request->categoryId ?: null,
            'short_desc' => $request->shortDesc,
            'description' => $request->description,
            'price_starting' => $request->priceStarting ? floatval($request->priceStarting) : null,
            'price_unit' => $request->priceUnit ?: 'Fixed',
            'duration' => $request->duration,
            'icon' => $request->icon,
            'hero_image' => $request->heroImage,
            'features' => $features,
            'tab_data' => $tabData,
            'meta_title' => $request->metaTitle,
            'meta_desc' => $request->metaDesc,
            'is_featured' => boolval($request->isFeatured),
            'is_active' => $request->has('isActive') ? boolval($request->isActive) : true,
            'order' => intval($request->order ?: 0),
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Services',
            entityId: (string) $service->id,
            details: ['name' => $request->name, 'slug' => $slug],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $service->fresh('category')], 201);
    }

    public function update(Request $request, $id = null)
    {
        $serviceId = $id ?: $request->input('id');
        $service = Service::find($serviceId);
        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $request->name;
        if ($request->has('slug')) $updateData['slug'] = Str::slug($request->slug);
        if ($request->has('categoryId')) $updateData['category_id'] = $request->categoryId ?: null;
        if ($request->has('shortDesc')) $updateData['short_desc'] = $request->shortDesc;
        if ($request->has('description')) $updateData['description'] = $request->description;
        if ($request->has('priceStarting')) $updateData['price_starting'] = $request->priceStarting ? floatval($request->priceStarting) : null;
        if ($request->has('priceUnit')) $updateData['price_unit'] = $request->priceUnit;
        if ($request->has('duration')) $updateData['duration'] = $request->duration;
        if ($request->has('icon')) $updateData['icon'] = $request->icon;
        if ($request->has('heroImage')) $updateData['hero_image'] = $request->heroImage;
        if ($request->has('metaTitle')) $updateData['meta_title'] = $request->metaTitle;
        if ($request->has('metaDesc')) $updateData['meta_desc'] = $request->metaDesc;
        if ($request->has('isFeatured')) $updateData['is_featured'] = boolval($request->isFeatured);
        if ($request->has('isActive')) $updateData['is_active'] = boolval($request->isActive);
        if ($request->has('order')) $updateData['order'] = intval($request->order);

        if ($request->has('features')) {
            $f = $request->features;
            $updateData['features'] = (is_array($f) || is_object($f)) ? json_encode($f) : $f;
        }
        if ($request->has('tabData')) {
            $t = $request->tabData;
            $updateData['tab_data'] = (is_array($t) || is_object($t)) ? json_encode($t) : $t;
        }

        $service->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Services',
            entityId: (string) $service->id,
            details: ['name' => $service->name],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $service->fresh('category')]);
    }

    public function destroy(Request $request, $id = null)
    {
        $serviceId = $id ?: $request->query('id', $request->input('id'));
        $service = Service::find($serviceId);
        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        $service->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Services',
            entityId: (string) $serviceId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Service deleted']);
    }
}
