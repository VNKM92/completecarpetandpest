<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

class AdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->query('module');
        $action = $request->query('action');
        $search = $request->query('search');

        $query = ActivityLog::with('user');

        if ($module && $module !== 'ALL') {
            $query->where('module', $module);
        }
        if ($action && $action !== 'ALL') {
            $query->where('action', $action);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->take(100)->get();

        return response()->json(['success' => true, 'data' => $logs]);
    }
}
