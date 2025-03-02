<?php

namespace App\Repositories;

use App\Models\Follow;
use App\Models\User;

class FollowRepository
{
    public function followUser($followerId, $followingId)
    {
        if ($followerId == $followingId) {
            throw new \Exception('You cannot follow yourself.');
        }

        $exists = Follow::where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->exists();

        if ($exists) {
            throw new \Exception('Already following this user.');
        }

        return Follow::create([
            'follower_id' => $followerId,
            'following_id' => $followingId
        ]);
    }

    public function unfollowUser($followerId, $followingId)
    {
        return Follow::where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->delete();
    }

    public function getUserFollowers($userId)
    {
        return User::whereHas('followers', function ($query) use ($userId) {
            $query->where('following_id', $userId);
        })->get();
    }

    public function getUserFollowing($userId)
    {
        return User::whereHas('following', function ($query) use ($userId) {
            $query->where('follower_id', $userId);
        })->get();
    }
}
