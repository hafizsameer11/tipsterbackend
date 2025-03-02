<?php

namespace App\Repositories;

use App\Models\Post;
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
            ], $formattedImages); // Merging image fields into the response
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

}
