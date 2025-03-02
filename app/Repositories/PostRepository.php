<?php

namespace App\Repositories;

use App\Models\Post;

class PostRepository
{
    public function createPost($data)
    {
        if (isset($data['images'])) {
            $imagePaths = [];
            foreach ($data['images'] as $image) {
                $imagePaths[] = $image->store('posts', 'public'); // Store images in the `storage/app/public/posts` folder
            }
            $data['images'] = json_encode($imagePaths);
        }

        return Post::create($data);
    }

    public function getAllPosts()
    {
        return Post::with(['user', 'likes', 'comments' => function ($query) {
            $query->where('status', 'approved')->latest()->take(2); // Fetch latest 2 approved comments
        }])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($post) {
            return [
                'id' => $post->id,
                'user' => [
                    'id' => $post->user->id,
                    'username' => $post->user->username,
                    'profile_picture' => $post->user->profile_picture ?? null,
                ],
                'timestamp' => $post->created_at->format('h:i A - m/d/Y'),
                'content' => $post->content,
                'images' => json_decode($post->images, true) ?? [], // Decode JSON images
                'likes_count' => $post->likes->count(),
                'comments_count' => $post->comments->count(),
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
            ];
        });
    }

    public function likePost($userId, $postId)
    {
        $post = Post::findOrFail($postId);
        $post->likes()->attach($userId);
        return $post;
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
        return Post::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }
}
