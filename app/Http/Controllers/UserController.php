<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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
            $data = $this->userService->update($request->all(), $userId);
            return ResponseHelper::success($data, 'User data updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
