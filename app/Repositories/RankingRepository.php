<?php

namespace App\Repositories;

use App\Models\Tip;
use App\Models\User;
use Carbon\Carbon;

class RankingRepository
{
    public function getUserRanking($userId)
    {
        $now = Carbon::now();
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $currentTime = $now->toDateTimeString(); // Get current time for live ranking

        // Fetch all users and calculate their weekly points dynamically
        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            $totalPoints = Tip::where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfWeek, $currentTime])
                ->where('result', 'won')
                ->sum('ods');

            if ($totalPoints > 0) {
                $rankings[$user->id] = $totalPoints;
            }
        }

        // Sort users by highest points
        arsort($rankings);

        // Assign ranks dynamically
        $rank = 1;
        foreach ($rankings as $id => $points) {
            if ($id == $userId) {
                return [
                    'user_id' => $userId,
                    'rank' => $rank,
                    'points' => $points,
                    'week_start' => $startOfWeek,
                    'status' => 'live'
                ];
            }
            $rank++;
        }

        return null; // Return null if user is not ranked
    }

    /**
     * Get top 30 users based on weekly rankings
     */
    public function getTop30Rankings()
    {
        $now = Carbon::now();
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $currentTime = $now->toDateTimeString();

        // Fetch all users and calculate their weekly points dynamically
        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            $totalPoints = Tip::where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfWeek, $currentTime])
                ->where('result', 'won')
                ->sum('ods');

            if ($totalPoints > 0) {
                $rankings[$user->id] = $totalPoints;
            }
        }
        arsort($rankings);
        $rankedUsers = [];
        $rank = 1;
        foreach ($rankings as $userId => $points) {
            $user = User::find($userId);
            $rankedUsers[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'profile_picture' => $user->profile_picture ?? null,
                'rank' => $rank++,
                'points' => $points
            ];
        }

        return collect(array_slice($rankedUsers, 0, 30)); // Return as a collection
    }
}
