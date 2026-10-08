<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Enquiry;
use App\Services\ActivityLogger;

class AdminEnquiryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Enquiry::query();

        if ($status && $status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('enquiry_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('service', 'like', "%{$search}%");
            });
        }

        $enquiries = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $enquiries]);
    }

    public function update(Request $request, $id = null)
    {
        $enquiryId = $id ?: $request->input('id');
        $enquiry = Enquiry::find($enquiryId);
        if (!$enquiry) {
            return response()->json(['success' => false, 'message' => 'Enquiry not found'], 404);
        }

        $updateData = [];
        if ($request->has('status')) $updateData['status'] = $request->status;
        if ($request->has('isRead')) $updateData['is_read'] = boolval($request->isRead);
        if ($request->has('notes')) $updateData['notes'] = $request->notes;

        $enquiry->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Enquiries',
            entityId: (string) $enquiry->id,
            details: $updateData,
            request: $request
        );

        return response()->json(['success' => true, 'data' => $enquiry]);
    }

    public function destroy(Request $request, $id = null)
    {
        $enquiryId = $id ?: $request->query('id', $request->input('id'));
        $enquiry = Enquiry::find($enquiryId);
        if (!$enquiry) {
            return response()->json(['success' => false, 'message' => 'Enquiry not found'], 404);
        }

        $enquiry->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Enquiries',
            entityId: (string) $enquiryId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Enquiry deleted']);
    }
}
