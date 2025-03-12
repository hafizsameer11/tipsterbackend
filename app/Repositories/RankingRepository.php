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

            // Calculate total tips and win rate in the last 30 days
            $totalTips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [Carbon::now()->subDays(30)->format('d-m-Y'), Carbon::now()->format('d-m-Y')])
                ->count();

            $totalWins = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [Carbon::now()->subDays(30)->format('d-m-Y'), Carbon::now()->format('d-m-Y')])
                ->where('result', 'won')
                ->count();

            $winRate = $totalTips > 0 ? ($totalWins / $totalTips) * 100 : 0;

            // Calculate points based on win rate
            $totalPoints = $tips->sum(function ($tip) use ($winRate) {
                return $tip->ods * ($winRate / 100);
            });

            if ($totalPoints > 0) {
                $rankings[$user->id] = $totalPoints;
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
                'win_rate' => '0%',
            ];
        }

        $rank = 1;
        foreach ($rankings as $id => $points) {
            if ($id == $userId) {
                $user = User::find($userId);

                return [
                    'user_id' => $userId,
                    'rank' => $rank,
                    'points' => round($points, 2),
                    'week_start' => $startOfWeek,
                    'week_end' => $endOfWeek,
                    'status' => 'live',
                    'username' => $user->username,
                    'profile_picture' => $user->profile_picture,
                    'win_rate' => round($winRate, 2) . '%',
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
            $tips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [$startOfWeek, $endOfWeek])
                ->where('result', 'won')
                ->get();

            $totalTips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [Carbon::now()->subDays(30)->format('d-m-Y'), Carbon::now()->format('d-m-Y')])
                ->count();

            $totalWins = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [Carbon::now()->subDays(30)->format('d-m-Y'), Carbon::now()->format('d-m-Y')])
                ->where('result', 'won')
                ->count();

            $winRate = $totalTips > 0 ? ($totalWins / $totalTips) * 100 : 0;

            $totalPoints = $tips->sum(function ($tip) use ($winRate) {
                return $tip->ods * ($winRate / 100);
            });

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
                'points' => round($points, 2),
                'win_rate' => round($winRate, 2) . '%'
            ];
        }

        return collect(array_slice($rankedUsers, 0, 30));
    }
    public function getTop10Rankings($weeksAgo = 1)
    {
        // Calculate the start and end of the selected week using match_date
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->format('d-m-Y');
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->format('d-m-Y');

        // Fetch all users with bank accounts
        $allUsers = User::with('bankAccount')->get();
        $rankings = [];

        foreach ($allUsers as $user) {
            // Get tips within the selected week using match_date
            $tips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [$startOfWeek, $endOfWeek])
                ->where('result', 'won')
                ->get();

            // Calculate total tips and win rate in the last 30 days
            $totalTips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [Carbon::now()->subDays(30)->format('d-m-Y'), Carbon::now()->format('d-m-Y')])
                ->count();

            $totalWins = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [Carbon::now()->subDays(30)->format('d-m-Y'), Carbon::now()->format('d-m-Y')])
                ->where('result', 'won')
                ->count();

            $winRate = $totalTips > 0 ? ($totalWins / $totalTips) * 100 : 0;

            // Calculate points using (Odds * Win Rate) / 100
            $totalPoints = $tips->sum(function ($tip) use ($winRate) {
                return $tip->ods * ($winRate / 100);
            });

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

            // Check if ranking payment exists for the selected week
            $rankingPayment = RankingPayment::where('user_id', $userId)
                ->whereBetween('match_date', [$startOfWeek, $endOfWeek])
                ->first();
            $paidStatus = $rankingPayment ? true : false;

            // Get tips again to calculate win rate
            $tips = Tip::where('user_id', $userId)
                ->whereBetween('match_date', [$startOfWeek, $endOfWeek])
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
                'points' => round($points, 2),
                'win_rate' => round($winRate, 2) . '%',
                'win_amount' => $winAmount ? $winAmount->amount : null,
                'currency' => $winAmount ? $winAmount->currency : null,
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
