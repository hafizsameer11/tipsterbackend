<?php

namespace App\Http\Controllers;

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
        $messages = Message::where('chat_id', $chatId)->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

}
