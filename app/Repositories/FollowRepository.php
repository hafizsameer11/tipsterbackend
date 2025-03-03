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

        // Check if the follow record already exists
        $existingFollow = Follow::where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->first();

        if ($existingFollow) {
            // Unfollow (delete the record)
            $existingFollow->delete();
            return [
                'message' => 'Unfollowed successfully',
                'is_following' => false
            ];
        }

        // Follow (create new record)
        Follow::create([
            'follower_id' => $followerId,
            'following_id' => $followingId
        ]);

        return [
            'message' => 'Followed successfully',
            'is_following' => true
        ];
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
