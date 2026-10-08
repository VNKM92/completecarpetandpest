<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Testimonial;
use App\Services\ActivityLogger;

class AdminTestimonialController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Testimonial::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('review', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('order', 'asc')->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'clientName' => 'required|string',
            'review' => 'required|string',
        ]);

        $testimonial = Testimonial::create([
            'client_name' => $request->clientName ?: $request->client_name,
            'role' => $request->role ?: 'Client',
            'location' => $request->location ?: 'Brisbane',
            'avatar' => $request->avatar,
            'rating' => intval($request->rating ?: 5),
            'review' => $request->review,
            'source' => $request->source ?: 'Google',
            'is_approved' => $request->has('isApproved') ? boolval($request->isApproved) : true,
            'order' => intval($request->order ?: 0),
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Testimonials',
            entityId: (string) $testimonial->id,
            details: ['clientName' => $testimonial->client_name],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $testimonial], 201);
    }

    public function update(Request $request, $id = null)
    {
        $testimonialId = $id ?: $request->input('id');
        $testimonial = Testimonial::find($testimonialId);
        if (!$testimonial) {
            return response()->json(['success' => false, 'message' => 'Testimonial not found'], 404);
        }

        $updateData = [];
        if ($request->has('clientName') || $request->has('client_name')) {
            $updateData['client_name'] = $request->clientName ?: $request->client_name;
        }
        if ($request->has('role')) $updateData['role'] = $request->role;
        if ($request->has('location')) $updateData['location'] = $request->location;
        if ($request->has('avatar')) $updateData['avatar'] = $request->avatar;
        if ($request->has('rating')) $updateData['rating'] = intval($request->rating);
        if ($request->has('review')) $updateData['review'] = $request->review;
        if ($request->has('source')) $updateData['source'] = $request->source;
        if ($request->has('isApproved') || $request->has('is_approved')) {
            $updateData['is_approved'] = boolval($request->isApproved ?? $request->is_approved);
        }
        if ($request->has('order')) $updateData['order'] = intval($request->order);

        $testimonial->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Testimonials',
            entityId: (string) $testimonial->id,
            details: ['clientName' => $testimonial->client_name],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $testimonial]);
    }

    public function destroy(Request $request, $id = null)
    {
        $testimonialId = $id ?: $request->query('id', $request->input('id'));
        $testimonial = Testimonial::find($testimonialId);
        if (!$testimonial) {
            return response()->json(['success' => false, 'message' => 'Testimonial not found'], 404);
        }

        $testimonial->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Testimonials',
            entityId: (string) $testimonialId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Testimonial deleted']);
    }
}
