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
use App\Services\NotificationService;


class PostController extends Controller
{
    protected $postService, $NotificationService;

    public function __construct(PostService $postService, NotificationService $NotificationService)
    {
        $this->postService = $postService;
        $this->NotificationService = $NotificationService;
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
            $userd = Auth::user();
            $notification = $this->NotificationService->sendToUserById($userd->id, 'Like Alert', 'You have successfully liked a post.');
            return ResponseHelper::success($posts, 'Posts fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function likePost($postId)
    {
        try {
            $post =  $this->postService->likePost(auth()->id(), $postId);
            $notification = $this->NotificationService->sendToUserById($post['userId'], 'Like Alert', 'You have successfully liked a post.');
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
    public function deleteComment($commentId)
    {
        try {
            $this->postService->deleteComment($commentId);
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
    public function getPostManagemtnData()
    {
        try {
            $post = $this->postService->getPostManagemtnData();
            return ResponseHelper::success($post, 'Post fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function makePostPin($id)
    {
        try {
            $post = Post::where('id', $id)->first();
            if (!$post) {
                throw new Exception('Post not found');
            }

            //check if alrady pin than unpin
            if ($post->is_pinned) {
                $post->is_pinned = false;
                $post->save();
            } else {
                $post->is_pinned = true;
                $post->save();
            }
            return ResponseHelper::success($post, 'Post pinned successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
