<?php

namespace App\Repositories;

use App\Models\Tip;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TipRepository
{
    protected $NotificationSevice;
    public function __construct(NotificationService $NotificationSevice)
    {
        $this->NotificationSevice = $NotificationSevice;
    }
    public function all() {}

    public function find($id)
    {
        // Add logic to find data by ID
    }

    public function create(array $data)
    {
        $user = Auth::user();
        $data['user_id'] = $user->id;
        $dateString = trim($data['match_date']); // e.g., "27-03-2025"
        Log::info("match date $dateString");
        // Convert the date string to Carbon, add a day, and format it back
        // $data['match_date'] = Carbon::createFromFormat('d-m-Y',  $dateString)
        //     ->addDay()
        //     ->format('d-m-Y');

        return Tip::create($data);
    }
    public function getFreeTipofUser($userId)
    {
        $user = User::findOrFail($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $tips = Tip::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('status', 'approved')
                    ->orWhere('status', 'rejected');
            })
            ->with('bettingCompany')
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
    public function getAllRunningTips($weeksAgo = 1)
    {
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->format('d-m-Y');
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->format('d-m-Y');
        $topUserIds = $this->getTop3UserIdsOfLastWeek(); // Call helper method
        $tips = Tip::where('status', 'approved')
            ->with(['user', 'bettingCompany'])
            ->whereNotIn('user_id', $topUserIds) // Exclude top user IDs
            ->orderBy('created_at', 'desc')
            ->get();



        $groupedTips = $tips->groupBy('user_id')->map(function ($userTips) use ($startOfWeek, $endOfWeek) {
            $user = $userTips->first()->user;

            $allTips = Tip::where(column: 'user_id', operator: $user->id)->where('status', 'approved')->orderBy('created_at', 'desc')->get();
            $totalTips = Tip::where('user_id', $user->id)->where('status', 'approved')->whereBetween('match_date', [$startOfWeek, $endOfWeek])->count();
            $wintips = Tip::where('user_id', $user->id)->where('status', 'approved')->where('result', 'won')->whereBetween('match_date', [$startOfWeek, $endOfWeek])->count();
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
    public function getTop3UserIdsOfLastWeek($weeksAgo = 2)
    {
        // Use Carbon week range without formatting
        $startOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->startOfWeek()->format('d-m-Y');
        $endOfWeek = Carbon::now()->subWeeks($weeksAgo - 1)->endOfWeek()->format('d-m-Y');

        $allUsers = User::all();
        $rankings = [];

        foreach ($allUsers as $user) {
            // Get all weekly tips (approved/rejected)
            $weeklyTips = Tip::where('user_id', $user->id)
                ->whereBetween('match_date', [$startOfWeek, $endOfWeek])
                ->whereIn('status', ['approved', 'rejected'])
                ->get();

            $wonTips = $weeklyTips->where('result', 'won');

            $totalTips = $weeklyTips->count();
            $totalWins = $wonTips->count();

            $winRate = $totalTips > 0 ? round(($totalWins / $totalTips) * 100, 2) : 0;

            $totalPoints = $wonTips->sum(function ($tip) use ($winRate) {
                return is_numeric($tip->ods) ? $tip->ods * ($winRate / 100) : 0;
            });

            if ($totalPoints > 0) {
                $rankings[$user->id] = $totalPoints;
            }
        }

        // Sort and return only top 3 user IDs
        arsort($rankings);

        return array_slice(array_keys($rankings), 0, 3);
    }


    public function getAllVipTips()
    {
        $startOfWeek = Carbon::now()->subWeeks(1)->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->subWeeks(1)->endOfWeek()->format('Y-m-d');
        $topUserIds = $this->getTop3UserIdsOfLastWeek();
        Log::info("Top and vip users", $topUserIds);
        $tips = Tip::where('status', 'approved')
            ->with(['user', 'bettingCompany'])
            ->whereIn('user_id', $topUserIds)
            ->orderBy('created_at', 'desc')
            ->get();


        $groupedTips = $tips->groupBy('user_id')->map(function ($userTips) use ($startOfWeek, $endOfWeek) {
            $user = $userTips->first()->user;

            $allTips = Tip::where('user_id', $user->id)->where('status', 'approved')->orderBy('created_at', 'desc')->get();
            $totalTips = $allTips->whereBetween('match_date', [$startOfWeek, $endOfWeek])->count();
            $wintips = $allTips->where('result', 'won')->whereBetween('match_date', [$startOfWeek, $endOfWeek])->count();

            $winRate = $totalTips > 0 ? round(($wintips / $totalTips) * 100, 0) : 0;
            $lastFiveResults = $allTips
                ->reject(fn($tip) => strtolower($tip->result) === 'running')
                ->take(5)
                ->pluck('result')
                ->map(fn($result) => strtoupper(substr($result, 0, 1)))
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
        $userId = $tip->user_id;

        $originalResult = $tip->result;
        $originalStatus = $tip->status;

        $updatedResult = $data['result'] ?? $originalResult;
        $updatedStatus = $data['status'] ?? $originalStatus;

        // Check if result changed
        if ($originalResult !== $updatedResult) {
            $body = "Your tip with booking code {$tip->codes} has {$updatedResult}.";
            $this->NotificationSevice->sendToUserById($userId, 'Tip Result Updated', $body);
        }

        // Check if status changed
        if ($originalStatus !== $updatedStatus) {
            if ($updatedStatus === 'approved') {
                $body = "Your tip with booking code {$tip->codes} has been approved.";
            } elseif ($updatedStatus === 'rejected') {
                $body = "Your tip with booking code {$tip->codes} was rejected. Please check for the reason.";
            } else {
                $body = "Status of your tip with booking code {$tip->codes} has been updated to {$updatedStatus}.";
            }

            $this->NotificationSevice->sendToUserById($userId, 'Tip Status Updated', $body);
        }

        $tip->update($data);

        return $tip;
    }


    public function delete($id)
    {
        $tip = Tip::findOrFail($id);
        if (!$tip) {
            throw new Exception('Tip not found.');
        }
        $tip->delete();

        return $tip;
    }
}
