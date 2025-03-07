<?php

namespace App\Repositories;

use App\Models\Tip;
use App\Models\User;
use App\Models\WinnersAmount;
use Carbon\Carbon;

class RankingRepository
{
    public function createWinnersAmount($rank, $amount)
    {
        return WinnersAmount::create([
            'rank' => $rank,
            'amount' => $amount,
        ]);
    }
    public function getWinnersAmount()
    {
        return WinnersAmount::all();
    }
    public function updateWinnersAmounts($winnersData)
    {
        foreach ($winnersData as $data) {
            WinnersAmount::updateOrCreate(
                ['rank' => $data['rank']], // Find by rank
                ['amount' => $data['amount']] // Update or create
            );
        }
        return WinnersAmount::all(); // Return updated records
    }

    public function getWinnersAmountByRank($rank)
    {
        return WinnersAmount::where('rank', '=', $rank)->first();
    }
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
                    'status' => 'live',
                    'username' => User::find($userId)->username,
                    'profile_picture' => User::find($userId)->profile_picture
                ];
            }
            $rank++;
        }

        return null;
    }

    /**
     * Get top 30 users based on weekly rankings
     */
    public function getTop30Rankings()
    {
        $now = Carbon::now();
        $startOfWeek = $now->startOfWeek()->toDateString();
        $currentTime = $now->toDateTimeString();

        // Fetch all users and calculate their weekly points dynamically
        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            $userTips = Tip::where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfWeek, $currentTime])
                ->get();

            $totalPoints = $userTips->where('result', 'won')->sum('ods');
            $totalTips = $userTips->count();
            $lostTips = $userTips->where('result', 'loss')->count();
            $winRate = $totalTips > 0 ? round((($totalTips - $lostTips) / $totalTips) * 100, 2) : 0;
            $lastFiveResults = $userTips->sortByDesc('created_at')->take(5)->pluck('result')->map(function ($result) {
                return strtoupper(substr($result, 0, 1)); // Extract first letter and convert to uppercase
            })->toArray();

            if ($totalPoints > 0) {
                $rankings[$user->id] = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'profile_picture' => $user->profile_picture ?? null,
                    'points' => $totalPoints,
                    'win_rate' => $winRate . '%',
                    'last_five' => $lastFiveResults
                ];
            }
        }

        // Sort rankings by total points in descending order
        usort($rankings, function ($a, $b) {
            return $b['points'] <=> $a['points'];
        });

        // Assign ranks
        foreach ($rankings as $index => &$user) {
            $user['rank'] = $index + 1;
        }

        return collect(array_slice($rankings, 0, 30)); // Return top 30 as a collection
    }
}
