<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Services\PostService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
          $post=  $this->postService->likePost(auth()->id(), $postId);
            return ResponseHelper::success($post, 'Post liked successfully');
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

    public function addComment(CommentRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            $validatedData['user_id'] = $user->id;
            $comment =    $this->postService->addComment($validatedData);
            return ResponseHelper::success($comment, 'Comment added for review');
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
    public function getPostForUser($userId)
    {
        try {

            $post = $this->postService->getUserPosts($userId);
            return ResponseHelper::success($post, 'Post fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function getPostDetail($id)
    {
        try {
            $post = $this->postService->getPostDetails($id);
            return ResponseHelper::success($post, 'Post fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function approvePost($postId)
    {
        try {
            $post =   $this->postService->approvePost($postId);
            return ResponseHelper::success($post, 'Post approved');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
public function deletePost($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->delete(); // Soft delete

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
