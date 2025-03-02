<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\FollowService;
use Exception;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    protected $followService;

    public function __construct(FollowService $followService)
    {
        $this->followService = $followService;
    }

    public function followUser($followingId)
    {
        try {
            $this->followService->followUser(auth()->id(), $followingId);
            return ResponseHelper::success([], 'Followed successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function unfollowUser($followingId)
    {
        try {
            $this->followService->unfollowUser(auth()->id(), $followingId);
            return ResponseHelper::success([], 'Unfollowed successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function getUserFollowers($userId)
    {
        try {
            $followers = $this->followService->getUserFollowers($userId);
            return ResponseHelper::success($followers, 'Followers fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function getUserFollowing($userId)
    {
        try {
            $following = $this->followService->getUserFollowing($userId);
            return ResponseHelper::success($following, 'Following fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
