<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller

{
    protected $NotificationService;
    public function __construct(NotificationService $NotificationService)
    {
        $this->NotificationService = $NotificationService;
    }
    public function getUserNotifications()
    {
        $user = Auth::user();
        $userId = $user->id;
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
        //update all current notification and mark as read
        foreach ($notifications as $notification) {
            $notification->update(['is_read' => true]);
        }

        return ResponseHelper::success($notifications, 'Notifications retrieved successfully');
    }
    public function getUnreadNotificationCount()
    {
        $user = Auth::user();
        $user = User::where('id', $user->id)->first();
        $userId = $user->id;

        // 🔍 Check for latest active subscription
        $subscription = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->latest('renewal_date')
            ->first();

        if ($subscription && Carbon::parse($subscription->renewal_date)->isPast()) {
            // ⌛ Subscription has expired
            $subscription->status = 'expired';
            $subscription->save();

            $user->vip_status = 'inactive';
            $user->save();
        }

        $count = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return ResponseHelper::success(
            [
                'count' => $count,
                'vipStatus' => $user->vip_status
            ],
            'Unread notification count retrieved successfully'
        );
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
            // Send notification to each user
            $this->NotificationService->sendToUserById($userId, $request->heading, $request->message);
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
    public function getAllUserActivity()
    {
        $activities = UserActivity::with('user')->orderBy('created_at', 'desc')->get();
        return ResponseHelper::success($activities, 'Activities retrieved successfully');
    }
}
