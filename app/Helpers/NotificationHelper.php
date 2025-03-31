<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;

class NotificationHelper
{
    public static $notificationService;
    public function __construct(NotificationService $notificationService)
    {
        self::$notificationService = $notificationService;
    }

    public static function sendNotification($userId, $triggeredById, $type, $postId = null, $customMessage = null)
    {
        $triggeredByUser = User::find($triggeredById);
        if (!$triggeredByUser) {
            return false; // Avoid errors if user is not found
        }

        // Use the custom message if provided, otherwise use a default message
        $message = $customMessage ?? "{$triggeredByUser->username} performed an action on your post.";
        // Send the notification using the NotificationService
        $notification = self::$notificationService->sendToUserById($userId, $type, $message);
        return Notification::create([
            'user_id' => $userId,
            'triggered_by_username' => $triggeredByUser->username, // Store username instead of ID
            'type' => $type,
            'post_id' => $postId,
            'message' => $message,
        ]);
    }
}
