<?php

namespace App\Repositories;

use App\Models\RankingPayment;
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
            // $tips
            $user = User::find($userId);

            // Fetch user's tips to calculate win rate
            $tips = Tip::where('user_id', $userId)
                ->whereBetween('created_at', [$startOfWeek, $currentTime])
                ->get();

            $totalTips = $tips->count();
            $lostTips = $tips->where('result', 'loss')->count();
            $winRate = $totalTips > 0 ? round((($totalTips - $lostTips) / $totalTips) * 100, 2) : 0;
            if ($id == $userId) {
                return [
                    'user_id' => $userId,
                    'rank' => $rank,
                    'points' => $points,
                    'week_start' => $startOfWeek,
                    'status' => 'live',
                    'username' => User::find($userId)->username,
                    'profile_picture' => User::find($userId)->profile_picture,
                    'win_rate' => $winRate . '%',
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

            // Fetch user's tips to calculate win rate
            $tips = Tip::where('user_id', $userId)
                ->whereBetween('created_at', [$startOfWeek, $currentTime])
                ->get();

            $totalTips = $tips->count();
            $lostTips = $tips->where('result', 'loss')->count();
            $winRate = $totalTips > 0 ? round((($totalTips - $lostTips) / $totalTips) * 100, 2) : 0;

            $rankedUsers[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'profile_picture' => $user->profile_picture ?? null,
                'rank' => $rank++,
                'points' => $points,
                'win_rate' => $winRate . '%' // Adding win rate
            ];
        }

        return collect(array_slice($rankedUsers, 0, 30)); // Return as a collection
    }
    public function getTop10Rankings()
    {
        $now = Carbon::now();
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $currentTime = $now->toDateTimeString();

        // Fetch all users
        $allUsers = User::with('bankAccount')->get();
        $rankings = [];

        foreach ($allUsers as $user) {
            // Calculate total points (won tips sum)
            $totalPoints = Tip::where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfWeek, $currentTime])
                ->where('result', 'won')
                ->sum('ods');

            if ($totalPoints > 0) {
                $rankings[$user->id] = $totalPoints;
            }
        }

        // Sort rankings in descending order
        arsort($rankings);
        $rankedUsers = [];
        $rank = 1;

        // Fetch Winner Amounts and map by rank
        $winnerAmounts = WinnersAmount::all()->keyBy('rank');

        foreach ($rankings as $userId => $points) {
            $user = User::with('bankAccount')->find($userId);
            $rankingPayment = RankingPayment::where('user_id', $userId)->whereBetween('created_at', [$startOfWeek, $currentTime])->first();
            $paidStatus = false;
            if ($rankingPayment) {
                $paidStatus = true;
            }
            // Fetch user's tips to calculate win rate
            $tips = Tip::where('user_id', $userId)
                ->whereBetween('created_at', [$startOfWeek, $currentTime])
                ->get();

            $totalTips = $tips->count();
            $lostTips = $tips->where('result', 'loss')->count();
            $winRate = $totalTips > 0 ? round((($totalTips - $lostTips) / $totalTips) * 100, 2) : 0;

            // Get Win Amount from WinnersAmount Model using rank
            $winAmount = $winnerAmounts[$rank] ?? null;

            $rankedUsers[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'profile_picture' => $user->profile_picture ?? null,
                'rank' => $rank,
                'points' => $points,
                'win_rate' => $winRate . '%',
                'win_amount' => $winAmount ? $winAmount->amount : null, // Fetch amount
                'currency' => $winAmount ? $winAmount->currency : null, // Fetch currency
                'bank_account' => $user->bankAccount, // Load bankAccount relation,
                'paid_status' => $paidStatus,
            ];

            $rank++;
        }

        return collect(array_slice($rankedUsers, 0, 10)); // Return top 10
    }
    public function payRankingPayment($userId, $amount, $rank)
    {
        $now = Carbon::now();
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $currentTime = $now->toDateTimeString();
        $rankingPayment = RankingPayment::where('user_id', $userId)->whereBetween('created_at', [$startOfWeek, $currentTime])->first();
        if ($rankingPayment) {
            return $rankingPayment;
        }
        return RankingPayment::create([
            'user_id' => $userId,
            'amount' => $amount,
            'rank' => $rank,
            'status' => 'paid',
        ]);
    }
}
