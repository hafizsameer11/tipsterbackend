<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function getAllUsers()
    {
        $users = User::all();
        $users = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->username,
                'avatar' => asset('storage/' . $user->profile_picture),
            ];
        });
        return ResponseHelper::success($users, 'User data fetched successfully');
        // return response()->json(['message' => 'User data fetched successfully']);
        // try {
        //     $data = $this->userService->getAllUsers();
        //     return ResponseHelper::success($data, 'User data fetched successfully');
        // } catch (Exception $e) {
        //     return ResponseHelper::error($e->getMessage());
        // }
    }
    public function viewProfile($userId)
    {
        try {
            $userdata = $this->userService->viewProfile($userId);
            return ResponseHelper::success($userdata, 'User fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    //admin part
    public function getUserManagementData()
    {
        try {
            $data = $this->userService->getUserManagementData();
            return ResponseHelper::success($data, 'User data fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function userDetails($userId)
    {
        try {
            $data = $this->userService->find($userId);
            return ResponseHelper::success($data, 'User data fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function updateProfile(Request $request, $userId)
    {
        try {
            $data = $this->userService->update($userId, $request->all());
            return ResponseHelper::success($data, 'User data updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function delete()
    {
        $user = Auth::user();
        $authUser = User::where('id', $user->id)->first();
        $authUser->email = $authUser->email . '-deleted';
        $authUser->save();
        return ResponseHelper::success($authUser, 'User deleted successfully');
    }
    public function checkUserVipStatus()
    {
        $user = Auth::user();
        $user = User::where('id', $user->id)->first();
        $vipStatus = $user->vip_status;
        return ResponseHelper::success($user, 'User data fetched successfully');
    }
    public function setFcmToken(Request $request)
    {
        $userId = Auth::user()->id;
        Log::info("FC token set: " . $request->fcmToken);
        $fcmToken = $request->fcmToken;

        $user = User::where('id', $userId)->first();
        $user->fcmToken = $fcmToken;
        $user->save();
        return response()->json(['status' => 'success', 'message' => 'FCM token set successfully'], 200);
    }
}
