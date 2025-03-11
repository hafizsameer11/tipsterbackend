<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function storeVideo(Request $request)
    {
        $video = new Video();
        $video->title = $request->title;
        $video->url = $request->url;
        $video->videoId = $request->videoId;
        $video->save();
        return response()->json(['message' => 'Video created successfully', 'data' => $video], 201);
    }
    public function index()
    {
        $videos = Video::first();
        return response()->json(['message' => 'Videos retrieved successfully', 'data' => $videos], 200);
    }
}
