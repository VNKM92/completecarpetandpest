<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    public static function log(
        string $action,
        string $module,
        ?string $entityId = null,
        $details = null,
        ?int $userId = null,
        ?string $userName = null,
        ?Request $request = null
    ): ActivityLog {
        $ip = $request ? $request->ip() : request()->ip();
        $userAgent = $request ? $request->userAgent() : request()->userAgent();

        if (!$userId && auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            $userId = $user->id;
            $userName = $userName ?: $user->name;
        }

        return ActivityLog::create([
            'user_id' => $userId,
            'user_name' => $userName ?: 'System',
            'action' => $action,
            'module' => $module,
            'entity_id' => $entityId,
            'details' => is_array($details) || is_object($details) ? json_encode($details) : $details,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
