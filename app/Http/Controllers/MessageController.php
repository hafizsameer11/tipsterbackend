<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'sender_type' => 'required|in:user,admin',
            'content' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,png,pdf,docx',
        ]);

        $messageData = [
            'chat_id' => $request->chat_id,
            'sender_type' => $request->sender_type,
            'content' => $request->content,
        ];

        // Handle file attachment
        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')->store('chat_attachments', 'public');
            $messageData['attachment'] = asset('storage/' . $filePath);
        }

        $message = Message::create($messageData);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message
        ]);
    }

    public function getChatMessages($chatId)
    {
        $chat = Chat::where('user_id', $chatId)->first();
        $messages = Message::where('chat_id', $chat->id)->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }
    public function getChatsForAdmin()
    {
        // Fetch all chats with user details and count of unread messages
        $chats = Chat::with(['user', 'messages' => function ($query) {
            $query->latest()->limit(1); // Get only the latest message
        }])
            ->withCount(['messages as unread_message_count' => function ($query) {
                $query->where('is_read', false);
            }])
            ->get();

        // Ensure latest_message is set properly
        $chats->each(function ($chat) {
            $chat->latest_message = $chat->messages->isNotEmpty() ? $chat->messages->first() : null;
            unset($chat->messages); // Remove messages collection to reduce payload
        });

        return response()->json($chats);
    }
}
