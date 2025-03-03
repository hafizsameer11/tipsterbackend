<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function createChat(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $chat = Chat::firstOrCreate(['user_id' => $request->user_id, 'status' => 'open']);

        return response()->json([
            'message' => 'Chat started',
            'chat' => $chat
        ]);
    }

    // Get all chats for a specific user
    public function getUserChats($userId)
    {
        $chats = Chat::where('user_id', $userId)->with('messages')->latest()->get();
        return response()->json($chats);
    }

    // Close a chat (Admin can close)
    public function closeChat($chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $chat->update(['status' => 'closed']);

        return response()->json([
            'message' => 'Chat closed successfully',
            'chat' => $chat
        ]);
    }
}
