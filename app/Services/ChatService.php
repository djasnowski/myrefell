<?php

namespace App\Services;

use App\Models\Message;
use App\Models\PlayerRole;
use App\Models\User;
use Illuminate\Support\Collection;

class ChatService
{
    /**
     * Maximum messages per page.
     */
    public const MESSAGES_PER_PAGE = 50;

    /**
     * Maximum message length.
     */
    public const MAX_MESSAGE_LENGTH = 500;

    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * Get messages for a location channel.
     */
    public function getLocationMessages(string $locationType, int $locationId, ?int $beforeId = null): Collection
    {
        $query = Message::inLocationChannel($locationType, $locationId)
            ->visible()
            ->with('sender')
            ->orderBy('id', 'desc')
            ->limit(self::MESSAGES_PER_PAGE);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->get()->reverse()->values()->map(fn ($msg) => $this->formatMessage($msg));
    }

    /**
     * Get private messages between two users.
     */
    public function getPrivateMessages(User $user, User $otherUser, ?int $beforeId = null): Collection
    {
        $query = Message::privateBetween($user->id, $otherUser->id)
            ->visible()
            ->with('sender')
            ->orderBy('id', 'desc')
            ->limit(self::MESSAGES_PER_PAGE);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->get()->reverse()->values()->map(fn ($msg) => $this->formatMessage($msg, $user->id));
    }

    /**
     * Get list of private conversations for a user.
     */
    public function getConversations(User $user): Collection
    {
        // Get the most recent message from each conversation
        $sentMessages = Message::where('channel_type', Message::CHANNEL_PRIVATE)
            ->where('sender_id', $user->id)
            ->visible()
            ->selectRaw('channel_id as other_user_id, MAX(id) as last_message_id')
            ->groupBy('channel_id');

        $receivedMessages = Message::where('channel_type', Message::CHANNEL_PRIVATE)
            ->where('channel_id', $user->id)
            ->visible()
            ->selectRaw('sender_id as other_user_id, MAX(id) as last_message_id')
            ->groupBy('sender_id');

        // Union and get the latest message per conversation partner
        $conversationPartners = collect();

        // Get all user IDs we've chatted with (either sent or received)
        $sentToIds = Message::where('channel_type', Message::CHANNEL_PRIVATE)
            ->where('sender_id', $user->id)
            ->visible()
            ->distinct()
            ->pluck('channel_id');

        $receivedFromIds = Message::where('channel_type', Message::CHANNEL_PRIVATE)
            ->where('channel_id', $user->id)
            ->visible()
            ->distinct()
            ->pluck('sender_id');

        $otherUserIds = $sentToIds->merge($receivedFromIds)->unique();

        foreach ($otherUserIds as $otherUserId) {
            $lastMessage = Message::privateBetween($user->id, $otherUserId)
                ->visible()
                ->orderBy('id', 'desc')
                ->first();

            if ($lastMessage) {
                $otherUser = User::find($otherUserId);
                if ($otherUser) {
                    $conversationPartners->push([
                        'user_id' => $otherUser->id,
                        'username' => $otherUser->username,
                        'last_message' => $lastMessage->content,
                        'last_message_at' => $lastMessage->created_at->toISOString(),
                        'is_from_me' => $lastMessage->sender_id === $user->id,
                    ]);
                }
            }
        }

        return $conversationPartners->sortByDesc('last_message_at')->values();
    }

    /**
     * Send a message to a location channel.
     */
    public function sendLocationMessage(User $user, string $locationType, int $locationId, string $content): array
    {
        // Validate the user is at this location
        if (!$this->userIsAtLocation($user, $locationType, $locationId)) {
            return [
                'success' => false,
                'message' => 'You must be at this location to send messages here.',
            ];
        }

        // Validate content
        $validation = $this->validateContent($content);
        if (!$validation['success']) {
            return $validation;
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'channel_type' => Message::CHANNEL_LOCATION,
            'channel_id' => $locationId,
            'channel_location_type' => $locationType,
            'content' => trim($content),
        ]);

        return [
            'success' => true,
            'message' => $this->formatMessage($message->load('sender')),
        ];
    }

    /**
     * Send a private message to another user.
     */
    public function sendPrivateMessage(User $sender, User $recipient, string $content): array
    {
        // Can't message yourself
        if ($sender->id === $recipient->id) {
            return [
                'success' => false,
                'message' => 'You cannot send a message to yourself.',
            ];
        }

        // Validate content
        $validation = $this->validateContent($content);
        if (!$validation['success']) {
            return $validation;
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'channel_type' => Message::CHANNEL_PRIVATE,
            'channel_id' => $recipient->id,
            'channel_location_type' => null,
            'content' => trim($content),
        ]);

        return [
            'success' => true,
            'message' => $this->formatMessage($message->load('sender'), $sender->id),
        ];
    }

    /**
     * Delete a message (moderation).
     */
    public function deleteMessage(User $moderator, Message $message, ?string $reason = null): array
    {
        // Check if user has moderation permission
        if (!$this->canModerateMessage($moderator, $message)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to delete this message.',
            ];
        }

        // Already deleted
        if ($message->is_deleted) {
            return [
                'success' => false,
                'message' => 'This message has already been deleted.',
            ];
        }

        $message->moderatorDelete($moderator, $reason);

        return [
            'success' => true,
            'message' => 'Message deleted.',
        ];
    }

    /**
     * Check if a user can moderate a message.
     */
    public function canModerateMessage(User $user, Message $message): bool
    {
        // User can delete their own messages
        if ($message->sender_id === $user->id) {
            return true;
        }

        // Admins can delete any message
        if ($user->isAdmin()) {
            return true;
        }

        // For location messages, check if user has moderation permission at that location
        if ($message->isLocationMessage()) {
            return $this->hasModeratePermission(
                $user,
                $message->channel_location_type,
                $message->channel_id
            );
        }

        // For private messages, only participants can delete their own
        return false;
    }

    /**
     * Check if user has moderation permission at a location.
     */
    protected function hasModeratePermission(User $user, string $locationType, int $locationId): bool
    {
        // Check if user holds a role with 'moderate_chat' permission at this location
        $playerRoles = PlayerRole::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->active()
            ->with('role')
            ->get();

        foreach ($playerRoles as $playerRole) {
            if ($playerRole->role->hasPermission('moderate_chat')) {
                return true;
            }
        }

        // Check parent locations (village -> barony -> kingdom)
        if ($locationType === 'village') {
            $village = \App\Models\Village::find($locationId);
            if ($village && $village->barony_id) {
                if ($this->hasModeratePermission($user, 'barony', $village->barony_id)) {
                    return true;
                }
            }
        } elseif ($locationType === 'barony') {
            $barony = \App\Models\Barony::find($locationId);
            if ($barony && $barony->kingdom_id) {
                if ($this->hasModeratePermission($user, 'kingdom', $barony->kingdom_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user is currently at a location.
     */
    protected function userIsAtLocation(User $user, string $locationType, int $locationId): bool
    {
        return $user->current_location_type === $locationType
            && $user->current_location_id === $locationId;
    }

    /**
     * Validate message content.
     */
    protected function validateContent(string $content): array
    {
        $trimmed = trim($content);

        if (empty($trimmed)) {
            return [
                'success' => false,
                'message' => 'Message cannot be empty.',
            ];
        }

        if (mb_strlen($trimmed) > self::MAX_MESSAGE_LENGTH) {
            return [
                'success' => false,
                'message' => 'Message is too long. Maximum ' . self::MAX_MESSAGE_LENGTH . ' characters.',
            ];
        }

        return ['success' => true];
    }

    /**
     * Format a message for display.
     */
    protected function formatMessage(Message $message, ?int $currentUserId = null): array
    {
        $data = [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_username' => $message->sender->username,
            'content' => $message->content,
            'created_at' => $message->created_at->toISOString(),
            'is_deleted' => $message->is_deleted,
        ];

        // For private messages, indicate if it's from the current user
        if ($currentUserId !== null) {
            $data['is_from_me'] = $message->sender_id === $currentUserId;
        }

        return $data;
    }

    /**
     * Get new messages since a given message ID.
     */
    public function getNewLocationMessages(string $locationType, int $locationId, int $afterId): Collection
    {
        return Message::inLocationChannel($locationType, $locationId)
            ->visible()
            ->where('id', '>', $afterId)
            ->with('sender')
            ->orderBy('id', 'asc')
            ->limit(100)
            ->get()
            ->map(fn ($msg) => $this->formatMessage($msg));
    }

    /**
     * Get new private messages since a given message ID.
     */
    public function getNewPrivateMessages(User $user, User $otherUser, int $afterId): Collection
    {
        return Message::privateBetween($user->id, $otherUser->id)
            ->visible()
            ->where('id', '>', $afterId)
            ->with('sender')
            ->orderBy('id', 'asc')
            ->limit(100)
            ->get()
            ->map(fn ($msg) => $this->formatMessage($msg, $user->id));
    }
}
