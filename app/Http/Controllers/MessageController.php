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
    // Fetch all chats with user details and latest message
    $chats = Chat::with(['user', 'messages' => function ($query) {
        $query->latest()->limit(1); // Get only the latest message
    }])
        ->withCount(['messages as unread_message_count' => function ($query) {
            $query->where('is_read', false);
        }])
        ->get();

    // Transform data to match required format
    $formattedChats = $chats->map(function ($chat) {
        $latestMessage = $chat->messages->first();

        return [
            "id" => $chat->id,
            "name" => $chat->user->username, // Name of the user
            "lastMessage" => $latestMessage ? $latestMessage->content : "No messages yet",
            "lastMessageTime" => $latestMessage ? $latestMessage->created_at->format('h:i A') : "",
            "lastMessageCount" => $chat->unread_message_count,
            "UserImage" => asset('storage/' . $chat->user->profile_picture), // Convert to full URL
        ];
    });

    return response()->json($formattedChats);
}
public function getMessagesForAdmin($chatId){
    $messages=Message::where('chat_id',$chatId)->get();
    $messages=$messages->map(function($message){
        return[
            'id'=>$message->id,
            'text'=>$message->content,
            'isUser'=>$message->send_type=='user',
            'timestamp'=>$message->created_at->format('h:i A - m/d/Y'),
        ];
    });
    return $messages;
}
}
