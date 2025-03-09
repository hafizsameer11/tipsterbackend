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
        $tips = $this->getAllPosts();
        $stats = [
            [
                'title' => 'Total Users',
                'value' => $totalUsers,
                'change' => $this->calculatePercentageChange($totalUsers, $totalUsersLastWeek),
                'icon' => 'images.sidebarIcons.user',
                'color' => 'red'
            ],
            [
                'title' => 'Total TipSters',
                'value' => $tipsters,
                'change' => $this->calculatePercentageChange($tipsters, $tipstersLastWeek),
                'icon' => 'images.sidebarIcons.user',
                'color' => '#1C26D5'

            ],
            [
                'title' => 'Subscription Revenue',
                'value' => "N 22,600",
                'change' => "10%",
                'icon' => 'images.sidebarIcons.user',
                'color' => '#D51C92'
            ],
        ];
        return [
            'stats' => $stats,
            'users' => $users,
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
    public function getAllPosts()
    {
        return Post::with(['user', 'likes', 'comments'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) {
                // Decode images JSON and structure them as separate fields
                $decodedImages = json_decode($post->images, true) ?? [];
                $formattedImages = [];

                foreach ($decodedImages as $index => $imagePath) {
                    $formattedImages['image_' . ($index + 1)] = asset('storage/' . $imagePath) ?? null;
                }

                return array_merge([
                    'id' => $post->id,
                    'user' => [
                        'id' => $post->user->id,
                        'username' => $post->user->username,
                        'profile_picture' => asset('storage/' . $post->user->profile_picture ?? '') ?? null,

                    ],
                    'timestamp' => $post->created_at->format('h:i A - m/d/Y'),
                    'content' => $post->content,
                    'type' => $post->type,
                    'likes_count' => $post->likes->count(),
                    'comments_count' => $post->comments->count(),
                    'share_count' => $post->share_count,
                    'view_count' => $post->view_count,
                    'recent_comments' => $post->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'user' => [
                                'id' => $comment->user->id,
                                'username' => $comment->user->username,
                                'profile_picture' => asset('storage/' . $comment->user->profile_picture) ?? null,
                            ],
                            'content' => $comment->content,
                        ];
                    }),
                ], $formattedImages); // Merging image fields into the response
            });
    }
}
