<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getUserNotifications()
    {
        $user = Auth::user();
        $userId = $user->id;
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseHelper::success($notifications, 'Notifications retrieved successfully');
    }

    // Mark a notification as read
    public function markAsRead($notificationId)
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->update(['is_read' => true]);

        return ResponseHelper::success([], 'Notification marked as read');
    }



    public function createNotificationForUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array', // Accept multiple user IDs
            'user_ids.*' => 'exists:users,id', // Validate each user ID
            'heading' => 'required|string|max:255', // Heading from admin
            'message' => 'required|string', // Notification message
            'attachment' => 'nullable|file|mimes:jpg,png,pdf,doc,docx|max:2048', // Handle attachments
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors() // Send validation error messages
            ], 422);
        }


        $notifications = [];

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('notifications', 'public');
        }

        // Loop through all user IDs and create notifications
        foreach ($request->user_ids as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'triggered_by_username' => $request->heading,
                'type' => 'announcement',
                'post_id' => $request->post_id ?? null, // Optional post ID
                'message' => $request->message,
                'is_read' => false, // Default as unread
                'attachment' => $attachmentPath, // Attach file if provided
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert all notifications in a single query (efficient batch processing)
        Notification::insert($notifications);

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications sent successfully to selected users',
            'data' => $notifications
        ], 201);
    }
    public function  getAdminNotifications()
    {
        $user = Auth::user();
        $userId = $user->id;
        $notifications = Notification::where('type', 'announcement')
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseHelper::success($notifications, 'Notifications retrieved successfully');
    }
}
