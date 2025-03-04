<?php

namespace App\Repositories;

use App\Helpers\NotificationHelper;
use App\Helpers\UserActivityHelper;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\PostShare;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PostRepository
{
    public function createPost($data)
    {
        Log::info('Received Post Data:', $data); // Log input data

        $imagePaths = [];

        if (!empty($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $imagePaths[] = $image->store('posts', 'public'); // Store each image in storage/app/public/posts
                }
            }
        }

        // Store images as JSON if images exist, else store an empty JSON array
        $data['images'] = !empty($imagePaths) ? json_encode($imagePaths) : json_encode([]);

        Log::info('Processed Images:', ['images' => $imagePaths]); // Log stored image paths

        $post = Post::create($data);

        // Convert stored JSON images back into separate image fields
        $decodedImages = json_decode($post->images, true) ?? [];
        $formattedImages = [];

        foreach ($decodedImages as $index => $imagePath) {
            $formattedImages['image_' . ($index + 1)] = $imagePath;
        }

        // Return formatted response
        return array_merge($post->toArray(), $formattedImages);
    }


    public function getAllPosts()
    {
        return Post::with(['user', 'likes', 'comments' => function ($query) {
            $query->where('status', 'approved')->latest()->take(2); // Fetch latest 2 approved comments
        }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) {
                // Decode images JSON and structure them as separate fields
                $decodedImages = json_decode($post->images, true) ?? [];
                $formattedImages = [];

                foreach ($decodedImages as $index => $imagePath) {
                    $formattedImages['image_' . ($index + 1)] = $imagePath;
                }

                return array_merge([
                    'id' => $post->id,
                    'user' => [
                        'id' => $post->user->id,
                        'username' => $post->user->username,
                        'profile_picture' => $post->user->profile_picture ?? null,
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
                                'profile_picture' => $comment->user->profile_picture ?? null,
                            ],
                            'content' => $comment->content,
                        ];
                    }),
                ], $formattedImages); // Merging image fields into the response
            });
    }

    public function getAllPendingPosts()
    {
        return Post::with(['user', 'likes', 'comments' => function ($query) {
            $query->where('status', 'pending')->latest()->take(2); // Fetch latest 2 approved comments
        }])
            ->orderBy('created_at', 'desc')
            ->where('status', 'pending')
            ->get()
            ->map(function ($post) {
                // Decode images JSON and structure them as separate fields
                $decodedImages = json_decode($post->images, true) ?? [];
                $formattedImages = [];

                foreach ($decodedImages as $index => $imagePath) {
                    $formattedImages['image_' . ($index + 1)] = $imagePath;
                }

                return array_merge([
                    'id' => $post->id,
                    'user' => [
                        'id' => $post->user->id,
                        'username' => $post->user->username,
                        'profile_picture' => $post->user->profile_picture ?? null,
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
                                'profile_picture' => $comment->user->profile_picture ?? null,
                            ],
                            'content' => $comment->content,
                        ];
                    }),
                ], $formattedImages); // Merging image fields into the response
            });
    }

    public function likePost($userId, $postId)
    {
        $post = Post::findOrFail($postId);

        // Check if the user already liked the post
        if ($post->likes()->where('user_id', $userId)->exists()) {
            // Unlike the post
            $post->likes()->detach($userId);

            // Log Activity for unliking
            UserActivityHelper::logActivity($userId, "Unliked a post (ID: {$post->id})");

            return [
                'likes_count' => $post->likes()->count(), // Updated like count
                'is_liked' => false, // User has unliked the post
            ];
        }

        // Like the post
        $post->likes()->attach($userId);

        // Fetch username of the user who liked the post
        $liker = User::findOrFail($userId);

        // Send Notification
        NotificationHelper::sendNotification(
            $post->user_id,
            $userId,
            'like',
            $post->id,
            "{$liker->username} liked your post."
        );

        // Log Activity
        UserActivityHelper::logActivity($userId, "Liked a post (ID: {$post->id})");

        return [
            'likes_count' => $post->likes()->count(), // Updated like count
            'is_liked' => true, // User has liked the post
        ];
    }




    public function unlikePost($userId, $postId)
    {
        $post = Post::findOrFail($postId);
        $post->likes()->detach($userId);
        return $post;
    }

    public function addComment($data)
    {
        return \App\Models\Comment::create([
            'user_id' => $data['user_id'],
            'post_id' => $data['post_id'],
            'content' => $data['content'],
            'status' => 'under_review'
        ]);
    }

    public function approveComment($commentId)
    {
        return \App\Models\Comment::where('id', $commentId)->update(['status' => 'approved']);
    }
    public function getUserPosts($userId)
    {
        return Post::with(['user', 'likes', 'comments' => function ($query) {
            $query->where('status', 'approved')->latest()->take(2); // Fetch only latest 2 approved comments
        }])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) {
                // Decode JSON images and format them separately
                $decodedImages = json_decode($post->images, true) ?? [];
                $formattedImages = [];

                foreach ($decodedImages as $index => $imagePath) {
                    $formattedImages['image_' . ($index + 1)] = $imagePath;
                }

                return array_merge([
                    'id' => $post->id,
                    'user' => [
                        'id' => $post->user->id,
                        'username' => $post->user->username,
                        'profile_picture' => $post->user->profile_picture ?? null,
                    ],
                    'timestamp' => $post->created_at->format('h:i A - m/d/Y'),
                    'content' => $post->content,
                    'likes_count' => $post->likes->count(),
                    'comments_count' => $post->comments->count(),
                    'type' => $post->type,

                    'recent_comments' => $post->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'user' => [
                                'id' => $comment->user->id,
                                'username' => $comment->user->username,
                                'profile_picture' => $comment->user->profile_picture ?? null,
                            ],
                            'content' => $comment->content,
                        ];
                    }),
                ], $formattedImages); // Merging image fields into the response
            });
    }
    public function getPostDetail($id)
    {
        return Post::with(['user', 'likes', 'comments' => function ($query) {
            $query->where('status', 'approved')->latest()->take(2); // Fetch only latest 2 approved comments
        }])
            ->where('id', $id)
            ->first();
    }
    public function approvePost($postId)
    {
        return Post::where('id', $postId)->update(['status' => 'approved']);
    }
    public function getPostManagemtnData()
    {
        $lastWeek = Carbon::now()->subWeek();
        $adminPost = Post::where('type', 'announcement')->where('status', 'approved')->count();
        $adminPostLastWeek = Post::where('type', 'announcement')->where('created_at', '<', $lastWeek)->where('status', 'approved')->count();
        $totalPost = Post::where('status', 'approved')->count();
        $totalPostLastWeek = Post::where('created_at', '<', $lastWeek)->where('status', 'approved')->count();
        $totalLikes = Like::count();
        $totalLikesLastWeek = Like::where('created_at', '<', $lastWeek)->count();
        $totalSharedPost = PostShare::count();
        $totalSharedPostLastWeek = PostShare::where('created_at', '<', $lastWeek)->count();
        $totalComments = Comment::count();
        $totalCommentsLastWeek = Comment::where('created_at', '<', $lastWeek)->count();
        $viewCount = Post::sum('view_count');
        $viewCountLastWeek = Post::where('created_at', '<', $lastWeek)->sum('view_count');
        $posts = $this->getAllPendingPosts();
        return [
            'stats' => [
                [
                    'label' => 'Total Posts',
                    'value' => number_format($totalPost),
                    'change' => $this->calculatePercentageChange($totalPost, $totalPostLastWeek),
                    'icon' => 'images.sidebarIcons.post',
                    'color' => 'red',
                ],
                [
                    'label' => 'Total Likes',
                    'value' => number_format($totalLikes),
                    'change' => $this->calculatePercentageChange($totalLikes, $totalLikesLastWeek),
                    'icon' => 'images.sidebarIcons.like',
                    'color' => 'red',
                ],
                [
                    'label' => 'Total Shares',
                    'value' => number_format($totalSharedPost),
                    'change' => $this->calculatePercentageChange($totalSharedPost, $totalSharedPostLastWeek),
                    'icon' => 'images.sidebarIcons.share',
                    'color' => 'red',
                ],
                [
                    'label' => 'Total Comments',
                    'value' => number_format($totalComments),
                    'change' => $this->calculatePercentageChange($totalComments, $totalCommentsLastWeek),
                    'icon' => 'images.sidebarIcons.comment',
                    'color' => 'red',
                ],
                [
                    'label' => 'Total Views',
                    'value' => number_format($viewCount),
                    'change' => $this->calculatePercentageChange($viewCount, $viewCountLastWeek),
                    'icon' => 'images.sidebarIcons.view',
                    'color' => 'red',
                ],
                [
                    'label' => 'Admin Post',
                    'value' => number_format($adminPost),
                    'change' => $this->calculatePercentageChange($adminPost, $adminPostLastWeek),
                    'icon' => 'images.sidebarIcons.post',
                    'color' => 'red',
                ]
            ]
        ];
        //first have to calculate the admin post  total post total likes total shares total comments totoal views
    }
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0; // If no previous users, assume 100% increase if new ones exist
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
