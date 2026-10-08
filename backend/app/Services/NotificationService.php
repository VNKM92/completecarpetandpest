<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function create(
        string $title,
        string $message,
        string $type = 'INFO',
        ?string $roleTarget = null,
        ?int $userId = null,
        ?string $link = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'role_target' => $roleTarget,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'is_read' => false,
        ]);
    }
}
