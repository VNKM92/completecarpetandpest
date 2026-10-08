<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailLog;
use App\Services\ActivityLogger;

class AdminEmailController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = EmailLog::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->take(100)->get();

        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipient' => 'required|email',
            'subject' => 'required|string',
        ]);

        $log = EmailLog::create([
            'recipient' => $request->recipient,
            'subject' => $request->subject,
            'body' => $request->body,
            'status' => 'SENT',
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Emails',
            entityId: (string) $log->id,
            details: ['recipient' => $request->recipient, 'subject' => $request->subject],
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Email logged successfully', 'data' => $log], 201);
    }
}
