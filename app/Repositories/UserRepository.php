<?php

namespace App\Repositories;

use App\Models\Follow;
use App\Models\Post;
use App\Models\Tip;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserSubscription;
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
        $userTips = Tip::where('user_id', $userId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->orderBy('created_at', 'desc')
            ->get();
        $totalPredictions = $userTips->count();
        $totalWins = $userTips->where('result', 'won')->count();
        $winRate = $totalPredictions > 0 ? round(($totalWins / $totalPredictions) * 100, 2) : 0;
        $lastFiveResults = $userTips->take(5)->pluck('result')->map(function ($result) {
            return strtoupper(substr($result, 0, 1)); // Convert result to first letter (W/L)
        })->toArray();
        $totalOdds = $userTips->where('result', 'won')->sum('ods');
        $averageOdds = $totalWins > 0 ? round($totalOdds / $totalWins, 2) : 0;
        $user = User::find($userId);
        $userFormatedtips = $this->tipRepository->getFreeTipofUser($userId);
        $graphicalData = $this->getUserMonthlyWinRateGraph($userId);
        $isFollowing = Follow::where('follower_id', auth()->id())->where('following_id', $userId)->exists();
        $follower_count = Follow::where('following_id', $userId)->count();
        $subscriber = UserSubscription::where('subscribed_to_id', $userId)->where('subscriber_id', auth()->id())->exists();
        return [
            'user_id' => $userId,
            'user' => $user,
            'win_rate' => $winRate . '%',
            'total_wins' => $totalWins,
            'last_five' => $lastFiveResults,
            'average_odds' => $averageOdds,
            'total_predictions' => $totalPredictions,
            'tips' => $userFormatedtips,
            'graphicalData' => $graphicalData,
            'isFollowing' => $isFollowing,
            'follower_count' => $follower_count,
            'subscriber' => $subscriber
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
        return User::all();
    }

    public function find($id)
    {
        $user = User::with('subscription')->find($id);
        if (!$user) {
            throw new Exception('User not found.');
        }
        $userTips = $this->tipRepository->getFreeTipofUser($user->id);
        $userPosts = Post::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        $userStatistics = $this->viewprofile($user->id);
        $userActivity = UserActivity::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        return [
            'user' => $user,
            'tips' => $userTips,
            'posts' => $userPosts,
            'statistics' => $userStatistics,
            'userActivity' => $userActivity
        ];
    }
    public function findByEmail($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new Exception('User not found.');
        }
        //calculate user running tips
        $userTips=Tip::where('user_id', $user->id)->where('result', 'running')->count();

        $user['running_tips']=$userTips;
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
        $user = User::find($id);
        if (!$user) {
            throw new Exception('User not found.');
        }
        if (isset($data['password']) && $data['password']) {
            $data['password'] = bcrypt($data['password']);
        }
        if (isset($data['profile_picture']) && $data['profile_picture']) {
            $path = $data['profile_picture']->store('profile_picture', 'public');
            $data['profile_picture'] = $path;
        }
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        // Add logic to delete data
    }
    public function changePassword(string $oldPassword, string $newPassword, $userId): ?User
    {
        $user = User::find($userId);

        if (!Hash::check($oldPassword, $user->password)) {
            throw new Exception('Invalid old password');
        }
        $user->password = Hash::make($newPassword);
        $user->save();
        return $user;
    }
    //admin part
    public function getUserManagementData()
    {
        $today = Carbon::now();
        $lastWeek = Carbon::now()->subWeek();

        $totalUsers = User::count();
        $totalUsersLastWeek = User::where('created_at', '<', $lastWeek)->count();
        $totalUsersChange = $this->calculatePercentageChange($totalUsers, $totalUsersLastWeek);

        $onlineUsers = User::where('is_active', true)->count();
        $onlineUsersLastWeek = User::where('is_active', true)->where('updated_at', '<', $lastWeek)->count();
        $onlineUsersChange = $this->calculatePercentageChange($onlineUsers, $onlineUsersLastWeek);

        $subscribedUsers = User::where('vip_status', 'active')->count();
        $subscribedUsersLastWeek = User::where('vip_status', 'active')->where('updated_at', '<', $lastWeek)->count();
        $subscribedUsersChange = $this->calculatePercentageChange($subscribedUsers, $subscribedUsersLastWeek);
        $users = User::with('subscription')->orderBy('created_at', 'desc')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'profile_picture' => $user->profile_picture ?? null,
                'is_active' => $user->is_active,
                'vip_status' => $user->vip_status,
                'phone' => $user->phone,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ];
        });

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
                    'title' => 'Online Users',
                    'value' => number_format($onlineUsers),
                    'change' => $onlineUsersChange,
                    'icon' => 'images.sidebarIcons.user',
                    'color' => 'red',
                ],
                [
                    'title' => 'Subscribed Users',
                    'value' => number_format($subscribedUsers),
                    'change' => $subscribedUsersChange,
                    'icon' => 'images.sidebarIcons.user',
                    'color' => 'red',
                ],
            ],
            'users' => $users,
        ];
    }

    // Helper function to calculate percentage change
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0; // If no previous users, assume 100% increase if new ones exist
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
