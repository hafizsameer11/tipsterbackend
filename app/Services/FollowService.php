<?php

namespace App\Services;

use App\Repositories\FollowRepository;

class FollowService
{
    protected $followRepository;

    public function __construct(FollowRepository $FollowRepository)
    {
        $this->followRepository = $FollowRepository;
    }

    public function followUser($followerId, $followingId)
    {
        try {
            return $this->followRepository->followUser($followerId, $followingId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function unfollowUser($followerId, $followingId)
    {
        try {
            return $this->followRepository->unfollowUser($followerId, $followingId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getUserFollowers($userId)
    {
        try {
            return $this->followRepository->getUserFollowers($userId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getUserFollowing($userId)
    {
        try {
            return $this->followRepository->getUserFollowing($userId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
