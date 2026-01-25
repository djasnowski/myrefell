<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * Show the chat page for the player's current location.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $messages = $this->chatService->getLocationMessages(
            $user->current_location_type,
            $user->current_location_id
        );

        $conversations = $this->chatService->getConversations($user);

        return Inertia::render('Chat/Index', [
            'messages' => $messages,
            'conversations' => $conversations,
            'current_location_type' => $user->current_location_type,
            'current_location_id' => $user->current_location_id,
            'location_name' => $this->getLocationName($user->current_location_type, $user->current_location_id),
            'max_message_length' => ChatService::MAX_MESSAGE_LENGTH,
        ]);
    }

    /**
     * Show chat for a specific village.
     */
    public function villageChat(Request $request, int $villageId): Response
    {
        $user = $request->user();

        if ($user->current_location_type !== 'village' || $user->current_location_id !== $villageId) {
            return Inertia::render('Chat/NotHere', [
                'message' => 'You must be at this village to view its chat.',
            ]);
        }

        $messages = $this->chatService->getLocationMessages('village', $villageId);
        $conversations = $this->chatService->getConversations($user);
        $village = \App\Models\Village::findOrFail($villageId);

        return Inertia::render('Chat/Index', [
            'messages' => $messages,
            'conversations' => $conversations,
            'current_location_type' => 'village',
            'current_location_id' => $villageId,
            'location_name' => $village->name,
            'max_message_length' => ChatService::MAX_MESSAGE_LENGTH,
        ]);
    }

    /**
     * Show chat for a specific barony.
     */
    public function baronyChat(Request $request, int $baronyId): Response
    {
        $user = $request->user();

        if ($user->current_location_type !== 'barony' || $user->current_location_id !== $baronyId) {
            return Inertia::render('Chat/NotHere', [
                'message' => 'You must be at this barony to view its chat.',
            ]);
        }

        $messages = $this->chatService->getLocationMessages('barony', $baronyId);
        $conversations = $this->chatService->getConversations($user);
        $barony = \App\Models\Barony::findOrFail($baronyId);

        return Inertia::render('Chat/Index', [
            'messages' => $messages,
            'conversations' => $conversations,
            'current_location_type' => 'barony',
            'current_location_id' => $baronyId,
            'location_name' => $barony->name,
            'max_message_length' => ChatService::MAX_MESSAGE_LENGTH,
        ]);
    }

    /**
     * Show a private conversation.
     */
    public function privateChat(Request $request, int $userId): Response
    {
        $user = $request->user();
        $otherUser = User::findOrFail($userId);

        if ($user->id === $otherUser->id) {
            return Inertia::render('Chat/NotHere', [
                'message' => 'You cannot chat with yourself.',
            ]);
        }

        $messages = $this->chatService->getPrivateMessages($user, $otherUser);
        $conversations = $this->chatService->getConversations($user);

        return Inertia::render('Chat/Private', [
            'messages' => $messages,
            'conversations' => $conversations,
            'other_user' => [
                'id' => $otherUser->id,
                'username' => $otherUser->username,
            ],
            'max_message_length' => ChatService::MAX_MESSAGE_LENGTH,
        ]);
    }

    /**
     * Send a message to the current location channel.
     */
    public function sendLocationMessage(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:' . ChatService::MAX_MESSAGE_LENGTH,
            'location_type' => 'required|string|in:village,barony,kingdom',
            'location_id' => 'required|integer',
        ]);

        $user = $request->user();
        $result = $this->chatService->sendLocationMessage(
            $user,
            $request->input('location_type'),
            $request->input('location_id'),
            $request->input('content')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Send a private message.
     */
    public function sendPrivateMessage(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:' . ChatService::MAX_MESSAGE_LENGTH,
            'recipient_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();
        $recipient = User::findOrFail($request->input('recipient_id'));
        $result = $this->chatService->sendPrivateMessage($user, $recipient, $request->input('content'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Poll for new location messages.
     */
    public function pollLocation(Request $request): JsonResponse
    {
        $request->validate([
            'location_type' => 'required|string|in:village,barony,kingdom',
            'location_id' => 'required|integer',
            'after_id' => 'required|integer',
        ]);

        $user = $request->user();

        // Check user is at this location
        if ($user->current_location_type !== $request->input('location_type') ||
            $user->current_location_id !== (int) $request->input('location_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are not at this location.',
            ], 422);
        }

        $messages = $this->chatService->getNewLocationMessages(
            $request->input('location_type'),
            $request->input('location_id'),
            $request->input('after_id')
        );

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Poll for new private messages.
     */
    public function pollPrivate(Request $request): JsonResponse
    {
        $request->validate([
            'other_user_id' => 'required|exists:users,id',
            'after_id' => 'required|integer',
        ]);

        $user = $request->user();
        $otherUser = User::findOrFail($request->input('other_user_id'));

        $messages = $this->chatService->getNewPrivateMessages(
            $user,
            $otherUser,
            $request->input('after_id')
        );

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Delete a message (moderation).
     */
    public function deleteMessage(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $result = $this->chatService->deleteMessage($user, $message, $request->input('reason'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get list of conversations.
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversations = $this->chatService->getConversations($user);

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Get the name of a location.
     */
    protected function getLocationName(string $locationType, int $locationId): string
    {
        return match ($locationType) {
            'village' => \App\Models\Village::find($locationId)?->name ?? 'Unknown Village',
            'barony' => \App\Models\Barony::find($locationId)?->name ?? 'Unknown Barony',
            'town' => \App\Models\Town::find($locationId)?->name ?? 'Unknown Town',
            'kingdom' => \App\Models\Kingdom::find($locationId)?->name ?? 'Unknown Kingdom',
            default => 'Unknown Location',
        };
    }
}
