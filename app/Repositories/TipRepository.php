<?php

namespace App\Repositories;

use App\Models\Tip;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

class TipRepository
{
    public function all() {}

    public function find($id)
    {
        // Add logic to find data by ID
    }

    public function create(array $data)
    {
        $user = Auth::user();
        $data['user_id'] = $user->id;
        return Tip::create($data);
    }
    public function getFreeTipofUser($userId)
    {
        $user = User::findOrFail($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $tips = Tip::where('user_id', $userId)->with('bettingCompany')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalTips = $tips->count();
        $wintips = $tips->where('result', 'won')->count();
        $winRate = $totalTips > 0 ? round(($wintips / $totalTips) * 100, 0) : 0;
        $lastFiveResults = $tips->take(5)->pluck('result')->map(function ($result) {
            return strtoupper(substr($result, 0, 1)); // Extract first letter and convert to uppercase
        })->toArray();
        $tipsWithUser = $tips->map(function ($tip) use ($user, $winRate, $lastFiveResults) {
            return array_merge($tip->toArray(), [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'profile_picture' => $user->profile_picture ?? null,
                    'win_rate' => $winRate . '%',
                    'last_five' => $lastFiveResults,
                ],
            ]);
        });

        return $tipsWithUser;
    }
    public function getAllTips()
    {
        $lastWeek = Carbon::now()->subWeek();

        $tips = Tip::with('bettingCompany', 'user')->orderBy('created_at', 'desc')->get();
        $totalUsers = User::count();
        $totalUsersLastWeek = User::whereDate('created_at', '<', $lastWeek)->count();
        //tipsters are the users having at least one tip
        $tipsters = User::whereHas('tips')->count();
        $tipstersLastWeek = User::whereHas('tips')->whereDate('created_at', '<', $lastWeek)->count();
        $totalTips = $tips->count();
        $totalTipsLastWeek = Tip::whereDate('created_at', '<', $lastWeek)->count();

        $totalUsersChange = $this->calculatePercentageChange($totalUsers, $totalUsersLastWeek);
        $tipstersChange = $this->calculatePercentageChange($tipsters, $tipstersLastWeek);
        $totalTipsChange = $this->calculatePercentageChange($totalTips, $totalTipsLastWeek);

        return [
            'stats' => [
                [
                    'title' => 'Total Users',
                    'value' => number_format($totalUsers),
                    'change' => $totalUsersChange,
                    'icon' => 'images.sidebarIcons.user',
                    'color' => 'red',
                ],
                [
                    'title' => 'Total Tipsters',
                    'value' => number_format($tipsters),
                    'change' => $tipstersChange,
                    'icon' => 'images.sidebarIcons.user',
                    'color' => 'red',
                ],
                [
                    'title' => 'Total Tips',
                    'value' => number_format($totalTips),
                    'change' => $totalTipsChange,
                    'icon' => 'images.sidebarIcons.user',
                    'color' => 'red',
                ],

            ],
            'tips' => $tips
        ];
    }
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0; // If no previous users, assume 100% increase if new ones exist
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
    public function getAllRunningTips()
    {
        $tips = Tip::where('result', 'running')
            ->with('user', 'bettingCompany') // Eager load user data
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedTips = $tips->groupBy('user_id')->map(function ($userTips) {
            $user = $userTips->first()->user;

            $allTips = Tip::where('user_id', $user->id)->where('status', 'approved')->orderBy('created_at', 'desc')->get();
            $totalTips = $allTips->count();
            $wintips = $allTips->where('result', 'won')->count();
            $winRate = $totalTips > 0 ? round(($wintips / $totalTips) * 100, 0) : 0;
            $lastFiveResults = $allTips
                ->reject(fn($tip) => strtolower($tip->result) === 'running') // Skip where result is "running"
                ->take(5)
                ->pluck('result')
                ->map(fn($result) => strtoupper(substr($result, 0, 1))) // Extract first letter and convert to uppercase
                ->toArray();

            $tipsWithUser = $userTips->map(function ($tip) use ($user, $winRate, $lastFiveResults) {
                return array_merge($tip->toArray(), [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'profile_picture' => $user->profile_picture ?? null,
                        'win_rate' => $winRate . '%',
                        'last_five' => $lastFiveResults,
                        'role' => $user->role
                    ],
                ]);
            });

            return $tipsWithUser;
        });

        return $groupedTips->values()->flatten(1); // Flatten to avoid nested arrays
    }



    public function approveTip($tipId)
    {
        $tip = Tip::findOrFail($tipId);
        if (!$tip) {
            throw new Exception('Tip not found.');
        }
        $tip->status = 'approved';
        $tip->save();

        return $tip;
    }
    public function setTipResult($tipId, $result)
    {
        $tip = Tip::findOrFail($tipId);
        if (!$tip) {
            throw new Exception('Tip not found.');
        }
        $tip->result = $result;
        $tip->save();

        return $tip;
    }
    public function update($id, array $data)
    {
        $tip = Tip::findOrFail($id);
        if (!$tip) {
            throw new Exception('Tip not found.');
        }
        $tip->update($data);

        return $tip;
    }

    public function delete($id)
    {
        // Add logic to delete data
    }
}
