<?php

namespace App\Repositories;

use App\Models\Tip;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    protected $tipRepository;

    public function __construct(TipRepository $RankingRepository)
    {
        $this->tipRepository = $RankingRepository;
    }

    public function viewprofile($userId)
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->subDays(30)->toDateString();

        // Fetch user's tips from the last 30 days
        $userTips = Tip::where('user_id', $userId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->orderBy('created_at', 'desc')
            ->get();

        // Total Predictions (total tips in the last 30 days)
        $totalPredictions = $userTips->count();

        // Total Wins (only 'win' results)
        $totalWins = $userTips->where('result', 'won')->count();

        // Win Rate Calculation (if there are predictions)
        $winRate = $totalPredictions > 0 ? round(($totalWins / $totalPredictions) * 100, 2) : 0;

        // Last 5 Wins (recent results)
        $lastFiveResults = $userTips->take(5)->pluck('result')->map(function ($result) {
            return strtoupper(substr($result, 0, 1)); // Convert result to first letter (W/L)
        })->toArray();

        // Average Odds (only from winning tips)
        $totalOdds = $userTips->where('result', 'won')->sum('ods');
        $averageOdds = $totalWins > 0 ? round($totalOdds / $totalWins, 2) : 0;
        $user = User::find($userId);
        $userFormatedtips = $this->tipRepository->getFreeTipofUser($userId);
        $graphicalData = $this->getUserMonthlyWinRateGraph($userId);
        return [
            'user_id' => $userId,
            'user' => $user,
            'win_rate' => $winRate . '%',
            'total_wins' => $totalWins,
            'last_five' => $lastFiveResults,
            'average_odds' => $averageOdds,
            'total_predictions' => $totalPredictions,
            'tips' => $userFormatedtips,
            'graphicalData' => $graphicalData
        ];
    }

public function getUserMonthlyWinRateGraph($userId)
{
    $now = Carbon::now();
    $oneYearAgo = $now->subMonths(11)->startOfMonth(); // Get start of 12th month

    // Prepare data structure
    $winRateData = [];

    for ($i = 0; $i < 12; $i++) {
        $startOfMonth = $oneYearAgo->copy()->addMonths($i)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $monthLabel = $startOfMonth->format('M'); // Example: Jan, Feb

        // Get all tips within this month
        $monthlyTips = Tip::where('user_id', $userId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        // Count wins & total predictions
        $totalPredictions = $monthlyTips->count();
        $totalWins = $monthlyTips->where('result', 'win')->count();

        // Calculate monthly win rate
        $winRate = $totalPredictions > 0 ? round(($totalWins / $totalPredictions) * 100, 2) : 0;

        // Store in array
        $winRateData[] = [
            'month' => $monthLabel,
            'win_rate' => $winRate,
        ];
    }

    return collect($winRateData);
}
    public function all()
    {
        // Add logic to fetch all data
    }

    public function find($id)
    {
        // Add logic to find data by ID
    }
    public function findByEmail($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new Exception('User not found.');
        }
        return $user;
    }

    public function create(array $data)
    {
        $data['password'] = bcrypt($data['password']);
        if (isset($data['profile_picture']) && $data['profile_picture']) {
            $path = $data['profile_picture']->store('profile_picture', 'public');
            $data['profile_picture'] = $path;
        }
        $data['otp'] = rand(1000, 9999);

        $user = User::create($data);

        return $user;
    }


    public function update($id, array $data)
    {
        // Add logic to update data
    }

    public function delete($id)
    {
        // Add logic to delete data
    }
    public function changePassword(string $oldPassword, string $newPassword,$userId): ?User
    {
        $user = User::find($userId);

        if (!Hash::check($oldPassword, $user->password)) {
           throw new Exception('Invalid old password');
        }
        $user->password = Hash::make($newPassword);
        $user->save();
        return $user;
    }
}
