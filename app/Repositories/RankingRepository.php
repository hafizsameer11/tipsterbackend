<?php

namespace App\Repositories;

use App\Models\RankingPayment;
use App\Models\Tip;
use App\Models\User;
use App\Models\WinnersAmount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
        $winnerAmounts = WinnersAmount::all()->map(function ($winner) {
            $winner->amount = number_format($winner->amount, 0, '.', ',');
            return $winner;
        });

        return $winnerAmounts;
        // return response()->json($winnerAmounts);
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
    public function getUserRanking($userId, $weeksAgo = 1)
    {
        $now = Carbon::now();

        // Calculate the start and end of the selected week
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->format('d-m-Y');
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->format('d-m-Y');

        // Get all users
        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            // Get tips within the selected week (using match_date)
            $tips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [$startOfWeek, $endOfWeek])
                ->where('result', 'won')
                ->get();

            // Ensure win rate is calculated correctly
            $totalTips = Tip::where('user_id', $user->id)
                ->where('status', 'approved')
                ->count();
            $totalWins = Tip::where('user_id', $user->id)
                ->where('result', 'won')
                ->count();

            $winRate = $totalTips > 0 ? round(($totalWins / $totalTips) * 100, 2) : 0;

            // Log::info("User ID: {$user->id}, Total Predictions: {$totalTips}, Total Wins: {$totalWins}, Win Rate: {$winRate}");

            $totalPoints = $tips->sum(function ($tip) use ($winRate) {
                return $tip->ods * ($winRate / 100);
            });

            if ($totalPoints > 0) {
                $rankings[$user->id] = [
                    'points' => $totalPoints,
                    'win_rate' => $winRate // Store win rate with ranking
                ];
            }
        }

        arsort($rankings);

        if (!array_key_exists($userId, $rankings)) {
            $user = User::find($userId);
            return [
                'user_id' => $userId,
                'rank' => 0,
                'points' => 0,
                'week_start' => $startOfWeek,
                'week_end' => $endOfWeek,
                'status' => 'live',
                'username' => $user ? $user->username : 'Unknown',
                'profile_picture' => $user ? $user->profile_picture : null,
                'win_rate' => '0%', // Return zero if no data is found
            ];
        }
        $winnerAmounts = WinnersAmount::all()->keyBy('rank');
        $rank = 1;

        foreach ($rankings as $id => $data) {
            $winAmount = $winnerAmounts[$rank] ?? null;
            if ($weeksAgo == 1) {
                $winAmount = null;
            }
            if ($id == $userId) {
                $user = User::find($userId);

                return [
                    'user_id' => $userId,
                    'rank' => $rank,
                    'points' => round($data['points'], 2),
                    'week_start' => $startOfWeek,
                    'week_end' => $endOfWeek,
                    'status' => 'live',
                    'username' => $user->username,
                    'profile_picture' => $user->profile_picture,
                    'win_rate' => round($data['win_rate'], 2) . '%', // Corrected win rate assignment
                    'win_amount' => $winAmount ? number_format($winAmount->amount, 0, '.', ',') : null
                ];
            }
            $rank++;
        }

        return null;
    }


    /**
     * Get top 30 users based on weekly rankings
     */
    public function getTop30Rankings($weeksAgo = 1)
    {
        $now = Carbon::now();

        // Calculate the start and end of the selected week
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->format('d-m-Y');
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->format('d-m-Y');

        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            // Get tips within the selected week (using match_date)
            $tips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [$startOfWeek, $endOfWeek])
                ->where('result', 'won')
                ->get();

            // Calculate win rate across all-time predictions
            $totalTips = Tip::where('user_id', $user->id)
                ->where('status', 'approved')->count();

            $totalWins = Tip::where('user_id', $user->id)
                ->where('result', 'won')->count();

            $winRate = $totalTips > 0 ? round(($totalWins / $totalTips) * 100, 2) : 0;

            // Log::info("User ID: {$user->id}, Total Predictions: {$totalTips}, Total Wins: {$totalWins}, Win Rate: {$winRate}");

            // Calculate points using (Odds * Win Rate) / 100
            $totalPoints = $tips->sum(function ($tip) use ($winRate) {
                return $tip->ods * ($winRate / 100);
            });

            if ($totalPoints > 0) {
                $rankings[$user->id] = [
                    'points' => $totalPoints,
                    'win_rate' => $winRate, // Store win rate properly
                ];
            }
        }

        // Sort rankings in descending order
        arsort($rankings);
        $rankedUsers = [];
        $rank = 1;

        $winnerAmounts = WinnersAmount::all()->keyBy('rank');
        foreach ($rankings as $userId => $data) {
            $user = User::find($userId);
            $winAmount = $winnerAmounts[$rank] ?? null;
            if ($weeksAgo == 1) {
                $winAmount = null;
            }
            $rankedUsers[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'profile_picture' => $user->profile_picture ?? null,
                'rank' => $rank++,
                'points' => round($data['points'], 2),
                'win_rate' => round($data['win_rate'], 2) . '%', // Now this is correctly assigned
                'start_of_week' => $startOfWeek,
                'end_of_week' => $endOfWeek,
                'win_amount' => $winAmount ? number_format($winAmount->amount, 0, '.', ',') : null,
            ];
        }

        return collect(array_slice($rankedUsers, 0, 30));
    }

    public function getTop10Rankings($weeksAgo = 1)
    {
        $now = Carbon::now();

        // Calculate the start and end of the selected week
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->format('d-m-Y');
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->format('d-m-Y');

        // Fetch all users
        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            // Get tips within the selected week (using match_date)
            $tips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [$startOfWeek, $endOfWeek])
                ->where('result', 'won')
                ->get();

            // Calculate win rate across all-time predictions
            $totalTips = Tip::where('user_id', $user->id)
                ->where('status', 'approved')->count();

            $totalWins = Tip::where('user_id', $user->id)
                ->where('result', 'won')->count();

            $winRate = $totalTips > 0 ? round(($totalWins / $totalTips) * 100, 2) : 0;

            $totalPoints = $tips->sum(function ($tip) use ($winRate) {
                return $tip->ods * ($winRate / 100);
            });

            if ($totalPoints > 0) {
                $rankings[$user->id] = [
                    'points' => $totalPoints,
                    'win_rate' => $winRate, // Store win rate properly
                ];
            }
        }

        // Sort rankings in descending order
        arsort($rankings);
        $rankedUsers = [];
        $rank = 1;

        // Fetch Winner Amounts and map by rank
        $winnerAmounts = WinnersAmount::all()->keyBy('rank');

        foreach ($rankings as $userId => $data) {
            $user = User::find($userId);

            // Get Win Amount from WinnersAmount Model using rank
            $winAmount = $winnerAmounts[$rank] ?? null;

            // Check if ranking payment exists for the selected week
            $rankingPayment = RankingPayment::where('user_id', $userId)
                ->first();
            $paidStatus = $rankingPayment ? true : false;
            // $winAmount = $winnerAmounts[$rank] ?? null;
            $rankedUsers[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'profile_picture' => $user->profile_picture ?? null,
                'rank' => $rank++,
                'points' => round($data['points'], 2),
                'win_rate' => round($data['win_rate'], 2) . '%',
                'win_amount' => $winAmount ? $winAmount->amount : null,
                'currency' => $winAmount ? $winAmount->currency : null,
                'paid_status' => $paidStatus,
                'start_of_week' => $startOfWeek,
                'end_of_week' => $endOfWeek,
                'weekago' => $weeksAgo
            ];
        }

        // Return top 10
        return collect(array_slice($rankedUsers, 0, 10));
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
