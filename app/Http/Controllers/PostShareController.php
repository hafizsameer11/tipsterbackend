<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Post;
use App\Models\PostShare;
use Illuminate\Http\Request;

class PostShareController extends Controller
{
    public function sharePost(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'user_id' => 'required|exists:users,id'
        ]);

        $post = Post::findOrFail($request->post_id);

        // Record the share action
        PostShare::create([
            'post_id' => $post->id,
            'user_id' => $request->user_id,
        ]);

        // Increase the share count
        $post->increment('share_count');

        return ResponseHelper::success($post, 'Post shared successfully');
    }

    // Get users who shared a post
    public function getShares($postId)
    {
        $shares = PostShare::where('post_id', $postId)->with('user')->get();

        return ResponseHelper::success($shares, 'Shares retrieved successfully');
    }
}
