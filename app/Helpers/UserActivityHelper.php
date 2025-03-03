<?php

namespace App\Helpers;

use App\Models\UserActivity;

class UserActivityHelper
{
    /**
     * Log user activity
     *
     * @param int $userId The user performing the activity
     * @param string $activity The description of the activity
     */
    public static function logActivity($userId, $activity)
    {
        return UserActivity::create([
            'user_id' => $userId,
            'activity' => $activity,
        ]);
    }
}
