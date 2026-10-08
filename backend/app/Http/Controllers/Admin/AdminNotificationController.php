<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::orderBy('created_at', 'desc')->take(30)->get();

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function update(Request $request)
    {
        $id = $request->input('id');
        $all = $request->input('all', false);

        if ($all) {
            Notification::where('is_read', false)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
        }

        if ($id) {
            $notification = Notification::find($id);
            if ($notification) {
                $notification->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
            }
            return response()->json(['success' => true, 'data' => $notification]);
        }

        return response()->json(['success' => false, 'message' => 'Missing ID or all parameter'], 400);
    }
}
