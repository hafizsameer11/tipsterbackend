<?php

namespace App\Services;

use App\Repositories\PostRepository;
use Illuminate\Support\Facades\Auth;

class PostService
{
    protected $postRepository;

    public function __construct(PostRepository $PostRepository)
    {
        $this->postRepository = $PostRepository;
    }

    public function createPost($data)
    {
        return $this->postRepository->createPost($data);
    }

    public function getAllPosts()
    {
        return $this->postRepository->getAllPosts();
    }

    public function likePost($userId, $postId)
    {
        return $this->postRepository->likePost($userId, $postId);
    }

    public function unlikePost($userId, $postId)
    {
        return $this->postRepository->unlikePost($userId, $postId);
    }

    public function addComment($data)
    {
        $user = Auth::user();
        $data['user_id'] = $user->id;
        return $this->postRepository->addComment($data);
    }

    public function approveComment($commentId)
    {
        return $this->postRepository->approveComment($commentId);
    }
    public function getUserPosts($userId)
    {
        return $this->postRepository->getUserPosts($userId);
    }
    public function getPostDetails($id)
    {
        try {
            return $this->postRepository->getPostDetail($id);
        } catch (\Exception $e) {
            throw new \Exception("Post Fetching Failed " . $e->getMessage());
        }
    }
    public function approvePost($postId)
    {
        try {
            return $this->postRepository->approvePost($postId);
        } catch (\Exception $e) {
            throw new \Exception("Post Fetching Failed " . $e->getMessage());
        }
    }
}
