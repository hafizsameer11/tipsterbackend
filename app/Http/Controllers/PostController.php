<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\PostRequest;
use App\Services\PostService;
use Exception;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }
    public function createPost(PostRequest $request)
{
    try {
        $validatedData = $request->validated();
        $validatedData['user_id'] = auth()->id();

        $post = $this->postService->createPost($validatedData);
        return ResponseHelper::success($post, 'Post created successfully');
    } catch (Exception $e) {
        return ResponseHelper::error($e->getMessage());
    }
}
public function getAllPosts()
    {
        try {
            $posts = $this->postService->getAllPosts();
            return ResponseHelper::success($posts, 'Posts fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function likePost($postId)
    {
        try {
            $this->postService->likePost(auth()->id(), $postId);
            return ResponseHelper::success([], 'Post liked successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function unlikePost($postId)
    {
        try {
            $this->postService->unlikePost(auth()->id(), $postId);
            return ResponseHelper::success([], 'Post unliked successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function addComment(Request $request, $postId)
    {
        try {
            $validatedData = $request->validate([
                'content' => 'required|string'
            ]);

            $validatedData['user_id'] = auth()->id();
            $validatedData['post_id'] = $postId;

            $this->postService->addComment($validatedData);
            return ResponseHelper::success([], 'Comment added for review');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function approveComment($commentId)
    {
        try {
            $this->postService->approveComment($commentId);
            return ResponseHelper::success([], 'Comment approved');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
