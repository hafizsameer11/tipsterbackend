<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardData extends Controller
{
    public function getDashboardData()
    {
        $lastWeek = Carbon::now()->subWeek();
        $totalUsers = User::count();
        $totalUsersLastWeek = User::whereDate('created_at', '>=', $lastWeek)->count();
        //tipsters are the users having at least one tip
        $tipsters = User::whereHas('tips', function ($query) {
            $query->whereDate('created_at', '>=', Carbon::now()->subWeek());
        })->count();
        $tipstersLastWeek = User::whereHas('tips', function ($query) {
            $query->whereDate('created_at', '>=', Carbon::now()->subWeek());
        })->whereDate('created_at', '>=', $lastWeek)->count();
        // $totalTipsters=
        $latestPost = Post::where('status', 'approved')->with('user')->orderBy('created_at', 'desc')->take(5)->get();
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
    }
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0; // If no previous users, assume 100% increase if new ones exist
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
