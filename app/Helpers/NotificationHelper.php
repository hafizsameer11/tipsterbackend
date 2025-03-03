<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;

class NotificationHelper
{
    public static function sendNotification($userId, $triggeredById, $type, $postId = null, $customMessage = null)
    {
        $triggeredByUser = User::find($triggeredById);
        if (!$triggeredByUser) {
            return false; // Avoid errors if user is not found
        }

        // Use the custom message if provided, otherwise use a default message
        $message = $customMessage ?? "{$triggeredByUser->username} performed an action on your post.";

        return Notification::create([
            'user_id' => $userId,
            'triggered_by_username' => $triggeredByUser->username, // Store username instead of ID
            'type' => $type,
            'post_id' => $postId,
            'message' => $message,
        ]);
    }
}
