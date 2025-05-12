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
    private function calculateWinRate($userId): int
    {
        // $userId = auth()->id(); // or pass it manually if needed
        $tips = Tip::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('status', 'approved')
                    ->orWhere('status', 'rejected');
            })
            ->get();

        // Calculate this week (Monday to Sunday)
        $today = Carbon::today();
        $daysToMonday = $today->dayOfWeek === 0 ? 6 : $today->dayOfWeek - 1;
        $startOfWeek = $today->copy()->subDays($daysToMonday);
        $daysToSunday = $today->dayOfWeek === 0 ? 0 : 7 - $today->dayOfWeek;
        $endOfWeek = $today->copy()->addDays($daysToSunday)->endOfDay();

        // Filter tips in current week
        $weeklyTips = $tips->filter(function ($tip) use ($startOfWeek, $endOfWeek) {
            return $tip->created_at >= $startOfWeek && $tip->created_at <= $endOfWeek;
        });

        $total = $weeklyTips->count();
        $wins = $weeklyTips->where('result', 'won')->count();

        return $total > 0 ? round(($wins / $total) * 100, 0) : 0;
    }
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
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->toDateTimeString();
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->toDateTimeString();

        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            $tips = Tip::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->get();

            $wonTips = $tips->where('result', 'won');
            $totalTips = $tips->count();
            $totalWins = $wonTips->count();

            $totalOds = $wonTips->sum(function ($tip) {
                $ods = str_replace(',', '.', $tip->ods);
                return is_numeric($ods) ? (float)$ods : 0;
            });

            $winRate = $totalTips > 0 ? round(($totalWins / $totalTips) * 100, 2) : 0;
            $totalPoints = $totalOds - $totalTips;

            if ($totalPoints > 0) {
                $rankings[$user->id] = [
                    'points' => $totalPoints,
                    'ods' => $totalOds,
                    'win_rate' => $winRate,
                    'total_tips' => $totalTips,
                    'total_wins' => $totalWins,
                ];
            }
        }

        arsort($rankings);

        $user = User::find($userId);

        if (!array_key_exists($userId, $rankings)) {
            return [
                'user_id' => $userId,
                'username' => $user ? $user->username : 'Unknown',
                'profile_picture' => $user ? $user->profile_picture : null,
                'rank' => 0,
                'points' => 0,
                'win_rate' => '0%',
                'start_of_week' => $startOfWeek,
                'total_tips' => 0,
                'total_wins' => 0,
                'win_odds' => 0,
                'end_of_week' => $endOfWeek,
                'win_amount' => 0,
            ];
        }

        $winnerAmounts = WinnersAmount::all()->keyBy('rank');
        $rank = 1;

        foreach ($rankings as $id => $data) {
            $winAmount = $winnerAmounts[$rank] ?? null;
            if ($weeksAgo == 1) {
                $winAmount = 0;
            }
            if ($id == $userId) {
                return [
                    'user_id' => $userId,
                    'username' => $user->username,
                    'profile_picture' => $user->profile_picture ?? null,
                    'rank' => $rank,
                    'points' => round($data['points'], 2),
                    'win_rate' => round($data['win_rate'], 2) . '%',
                    'start_of_week' => $startOfWeek,
                    'total_tips' => $data['total_tips'],
                    'total_wins' => $data['total_wins'],
                    'win_odds' => round($data['ods'], 2),
                    'end_of_week' => $endOfWeek,
                    'win_amount' => $winAmount ? number_format($winAmount->amount, 0, '.', ',') : 0,
                ];
            }
            $rank++;
        }

        return null;
    }


    public function getTop30Rankings($weeksAgo = 1)
    {
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->toDateTimeString();
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->toDateTimeString();

        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            // Fetch all approved tips
            $tips = Tip::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->get();

            $wonTips = $tips->where('result', 'won');

            $totalTips = $tips->count();
            $totalWins = $wonTips->count();

            // Skip users with 0 tips completely (optional, you can remove this line if needed)
            if ($totalTips === 0) {
                continue;
            }

            $winRate = round(($totalWins / $totalTips) * 100, 2);

            // Sum odds properly (handle commas if stored as strings)
            $winOds = $wonTips->sum(function ($tip) {
                $ods = str_replace(',', '.', $tip->ods);
                return is_numeric($ods) ? (float)$ods : 0;
            });

            $totalPoints = $winOds - $totalTips;

            $rankings[$user->id] = [
                'points' => $totalPoints,
                'ods' => $winOds,
                'win_rate' => $winRate,
                'total_tips' => $totalTips,
                'total_wins' => $totalWins,
            ];
        }

        // Sort by points DESC
        uasort($rankings, fn($a, $b) => $b['points'] <=> $a['points']);

        $winnerAmounts = WinnersAmount::all()->keyBy('rank');
        $rankedUsers = [];
        $rank = 1;

        foreach ($rankings as $userId => $data) {
            $user = User::find($userId);
            if (!$user) continue;

            $winAmount = $weeksAgo == 1 ? 0 : ($winnerAmounts[$rank]->amount ?? 0);

            $rankedUsers[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'profile_picture' => $user->profile_picture ?? null,
                'rank' => $rank++,
                'points' => round($data['points'], 2),
                'win_rate' => round($data['win_rate'], 2) . '%',
                'start_of_week' => $startOfWeek,
                'total_tips' => $data['total_tips'],
                'total_wins' => $data['total_wins'],
                'win_odds' => round($data['ods'], 2),
                'end_of_week' => $endOfWeek,
                'win_amount' => number_format($winAmount, 0, '.', ','),
            ];
        }

        return collect(array_slice($rankedUsers, 0, 30));
    }


    public function getTop10Rankings($weeksAgo = 1)
    {
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->toDateTimeString();
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->toDateTimeString();

        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            // Get tips using created_at, same as getTop30Rankings
            $tips = Tip::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->get();

            $wonTips = $tips->where('result', 'won');

            $totalTips = $tips->count();
            $totalWins = $wonTips->count();

            if ($totalTips === 0) {
                continue;
            }

            $winRate = round(($totalWins / $totalTips) * 100, 2);

            $winOds = $wonTips->sum(function ($tip) {
                $ods = str_replace(',', '.', $tip->ods);
                return is_numeric($ods) ? (float)$ods : 0;
            });

            $totalPoints = $winOds - $totalTips;

            $rankings[$user->id] = [
                'points' => $totalPoints,
                'ods' => $winOds,
                'win_rate' => $winRate,
                'total_tips' => $totalTips,
                'total_wins' => $totalWins,
            ];
        }

        // Sort by points DESC
        uasort($rankings, fn($a, $b) => $b['points'] <=> $a['points']);

        $winnerAmounts = WinnersAmount::all()->keyBy('rank');
        $rankedUsers = [];
        $rank = 1;

        foreach ($rankings as $userId => $data) {
            $user = User::find($userId);
            if (!$user) continue;

            $winAmountEntry = $winnerAmounts[$rank] ?? null;
            $rankingPayment = RankingPayment::where('user_id', $userId)->first();

            $rankedUsers[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'profile_picture' => $user->profile_picture ?? null,
                'rank' => $rank++,
                'points' => round($data['points'], 2),
                'win_rate' => round($data['win_rate'], 2) . '%',
                'start_of_week' => $startOfWeek,
                'end_of_week' => $endOfWeek,
                'total_tips' => $data['total_tips'],
                'total_wins' => $data['total_wins'],
                'win_odds' => round($data['ods'], 2),
                'win_amount' => $winAmountEntry ? number_format($winAmountEntry->amount, 0, '.', ',') : 0,
                'currency' => $winAmountEntry->currency ?? null,
                'paid_status' => $rankingPayment ? true : false,
                'weekago' => $weeksAgo,
            ];
        }

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
