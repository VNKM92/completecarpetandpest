<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappLog;
use App\Services\ActivityLogger;

class AdminWhatsAppController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = WhatsappLog::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->take(100)->get();

        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $log = WhatsappLog::create([
            'phone' => $request->phone,
            'message' => $request->message,
            'direction' => $request->direction ?: 'OUTBOUND',
            'status' => 'SENT',
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'WhatsApp',
            entityId: (string) $log->id,
            details: ['phone' => $request->phone],
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'WhatsApp message logged successfully', 'data' => $log], 201);
    }
}
