<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Religion;
use App\Models\ReligionInvite;
use App\Models\ReligionLog;
use App\Models\ReligionMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReligionInviteService
{
    public function __construct(protected ReligionService $religionService) {}

    /**
     * Send an invite to a player.
     */
    public function sendInvite(User $inviter, int $religionId, int $inviteeUserId, ?string $message = null): array
    {
        $religion = Religion::find($religionId);
        if (! $religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Check if inviter is a priest or prophet
        $inviterMembership = ReligionMember::where('user_id', $inviter->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $inviterMembership || $inviterMembership->isFollower()) {
            return ['success' => false, 'message' => 'Only priests and prophets can invite new members.'];
        }

        // Check invitee exists
        $invitee = User::find($inviteeUserId);
        if (! $invitee) {
            return ['success' => false, 'message' => 'Player not found.'];
        }

        // Check invitee is not already a member
        $existingMembership = ReligionMember::where('user_id', $inviteeUserId)->first();
        if ($existingMembership) {
            if ($existingMembership->religion_id === $religionId) {
                return ['success' => false, 'message' => "{$invitee->username} is already a member."];
            }

            return ['success' => false, 'message' => "{$invitee->username} is already a member of another religion."];
        }

        // Check for existing active invite
        $existingInvite = ReligionInvite::active()
            ->forUser($inviteeUserId)
            ->forReligion($religionId)
            ->first();

        if ($existingInvite) {
            return ['success' => false, 'message' => "{$invitee->username} already has a pending invite."];
        }

        return DB::transaction(function () use ($inviter, $religion, $invitee, $message) {
            // Create the invite
            $invite = ReligionInvite::create([
                'religion_id' => $religion->id,
                'invited_by_user_id' => $inviter->id,
                'invited_user_id' => $invitee->id,
                'status' => ReligionInvite::STATUS_PENDING,
                'message' => $message,
                'expires_at' => now()->addDays(ReligionInvite::INVITE_EXPIRY_DAYS),
            ]);

            // Send a private message notifying the invitee
            $messageContent = "{$inviter->username} has invited you to join {$religion->name}!";
            if ($message) {
                $messageContent .= "\n\nMessage: \"{$message}\"";
            }
            $messageContent .= "\n\nVisit any shrine to view and respond to this invite.";

            Message::create([
                'sender_id' => $inviter->id,
                'channel_type' => Message::CHANNEL_PRIVATE,
                'channel_id' => $invitee->id,
                'content' => $messageContent,
            ]);

            // Log the invite
            ReligionLog::log(
                $religion->id,
                ReligionLog::EVENT_MEMBER_INVITED,
                "{$inviter->username} invited {$invitee->username} to join",
                $inviter->id,
                $invitee->id
            );

            return [
                'success' => true,
                'message' => "You have invited {$invitee->username} to join {$religion->name}.",
                'data' => ['invite' => $this->formatInvite($invite)],
            ];
        });
    }

    /**
     * Accept an invite.
     */
    public function acceptInvite(User $player, int $inviteId): array
    {
        $invite = ReligionInvite::with(['religion', 'inviter'])->find($inviteId);

        if (! $invite) {
            return ['success' => false, 'message' => 'Invite not found.'];
        }

        if ($invite->invited_user_id !== $player->id) {
            return ['success' => false, 'message' => 'This invite is not for you.'];
        }

        if (! $invite->canRespond()) {
            if ($invite->isExpired()) {
                return ['success' => false, 'message' => 'This invite has expired.'];
            }

            return ['success' => false, 'message' => 'This invite can no longer be accepted.'];
        }

        return DB::transaction(function () use ($player, $invite) {
            $religion = $invite->religion;
            $inviter = $invite->inviter;

            // Handle existing membership - leave current religion first
            $existingMembership = ReligionMember::where('user_id', $player->id)->first();
            if ($existingMembership) {
                $oldReligion = $existingMembership->religion;
                $wasProphet = $existingMembership->isProphet();
                $wasOfficer = $existingMembership->isOfficer();

                // Log the departure from old religion
                if ($wasProphet) {
                    ReligionLog::log(
                        $oldReligion->id,
                        ReligionLog::EVENT_PROPHET_ABDICATED,
                        "{$player->username} abdicated as Prophet to join {$religion->name}",
                        $player->id
                    );
                } elseif ($wasOfficer) {
                    ReligionLog::log(
                        $oldReligion->id,
                        ReligionLog::EVENT_MEMBER_LEFT,
                        "{$player->username} ({$existingMembership->rank_display}) left to join {$religion->name}",
                        $player->id
                    );
                } else {
                    ReligionLog::log(
                        $oldReligion->id,
                        ReligionLog::EVENT_MEMBER_LEFT,
                        "{$player->username} left to join {$religion->name}",
                        $player->id
                    );
                }

                // Delete the old membership
                $existingMembership->delete();

                // If was prophet, auto-promote successor
                if ($wasProphet) {
                    $this->promoteSuccessor($oldReligion, $player);
                }
            }

            // Accept the invite
            $invite->accept();

            // Create the membership
            ReligionMember::create([
                'user_id' => $player->id,
                'religion_id' => $religion->id,
                'rank' => ReligionMember::RANK_FOLLOWER,
                'devotion' => 0,
                'joined_at' => now(),
            ]);

            // Log the join
            ReligionLog::log(
                $religion->id,
                ReligionLog::EVENT_MEMBER_JOINED,
                "{$player->username} joined via invite from {$inviter->username}",
                $player->id
            );

            // Notify the inviter
            Message::create([
                'sender_id' => $player->id,
                'channel_type' => Message::CHANNEL_PRIVATE,
                'channel_id' => $inviter->id,
                'content' => "{$player->username} has accepted your invitation to join {$religion->name}!",
            ]);

            return [
                'success' => true,
                'message' => "You have joined {$religion->name}!",
            ];
        });
    }

    /**
     * Decline an invite.
     */
    public function declineInvite(User $player, int $inviteId, ?string $responseMessage = null): array
    {
        $invite = ReligionInvite::with(['religion', 'inviter'])->find($inviteId);

        if (! $invite) {
            return ['success' => false, 'message' => 'Invite not found.'];
        }

        if ($invite->invited_user_id !== $player->id) {
            return ['success' => false, 'message' => 'This invite is not for you.'];
        }

        if (! $invite->canRespond()) {
            return ['success' => false, 'message' => 'This invite can no longer be declined.'];
        }

        return DB::transaction(function () use ($player, $invite, $responseMessage) {
            $religion = $invite->religion;
            $inviter = $invite->inviter;

            // Decline the invite
            $invite->decline($responseMessage);

            // Optionally notify the inviter
            $messageContent = "{$player->username} has declined your invitation to join {$religion->name}.";
            if ($responseMessage) {
                $messageContent .= "\n\nMessage: \"{$responseMessage}\"";
            }

            Message::create([
                'sender_id' => $player->id,
                'channel_type' => Message::CHANNEL_PRIVATE,
                'channel_id' => $inviter->id,
                'content' => $messageContent,
            ]);

            return [
                'success' => true,
                'message' => 'You have declined the invite.',
            ];
        });
    }

    /**
     * Cancel an invite (inviter only).
     */
    public function cancelInvite(User $player, int $inviteId): array
    {
        $invite = ReligionInvite::with('religion')->find($inviteId);

        if (! $invite) {
            return ['success' => false, 'message' => 'Invite not found.'];
        }

        // Check if player is the inviter or a priest/prophet of the religion
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $invite->religion_id)
            ->first();

        $canCancel = $invite->invited_by_user_id === $player->id
            || ($membership && ! $membership->isFollower());

        if (! $canCancel) {
            return ['success' => false, 'message' => 'You cannot cancel this invite.'];
        }

        if (! $invite->isPending()) {
            return ['success' => false, 'message' => 'This invite has already been responded to.'];
        }

        $invite->cancel();

        return [
            'success' => true,
            'message' => 'The invite has been cancelled.',
        ];
    }

    /**
     * Get pending invites for a user.
     */
    public function getPendingInvitesForUser(User $player): array
    {
        return ReligionInvite::active()
            ->forUser($player->id)
            ->with(['religion', 'inviter'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($invite) => $this->formatInvite($invite))
            ->toArray();
    }

    /**
     * Get pending invites sent by religion leadership.
     */
    public function getPendingInvitesForReligion(int $religionId): array
    {
        return ReligionInvite::active()
            ->forReligion($religionId)
            ->with(['invitee', 'inviter'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($invite) => $this->formatInvite($invite))
            ->toArray();
    }

    /**
     * Expire old invites (called via scheduler).
     */
    public function expireOldInvites(): int
    {
        return ReligionInvite::pending()
            ->where('expires_at', '<=', now())
            ->update(['status' => ReligionInvite::STATUS_EXPIRED]);
    }

    /**
     * Format an invite for API response.
     */
    protected function formatInvite(ReligionInvite $invite): array
    {
        return [
            'id' => $invite->id,
            'religion' => $invite->religion ? [
                'id' => $invite->religion->id,
                'name' => $invite->religion->name,
                'icon' => $invite->religion->icon,
                'color' => $invite->religion->color,
                'type' => $invite->religion->type,
                'is_cult' => $invite->religion->isCult(),
            ] : null,
            'inviter' => $invite->inviter ? [
                'id' => $invite->inviter->id,
                'username' => $invite->inviter->username,
            ] : null,
            'invitee' => $invite->invitee ? [
                'id' => $invite->invitee->id,
                'username' => $invite->invitee->username,
            ] : null,
            'status' => $invite->status,
            'status_display' => $invite->status_display,
            'message' => $invite->message,
            'expires_at' => $invite->expires_at->toIso8601String(),
            'expires_in' => $invite->expires_at->diffForHumans(),
            'can_respond' => $invite->canRespond(),
            'created_at' => $invite->created_at->toIso8601String(),
        ];
    }

    /**
     * Promote a successor when a prophet abdicates.
     * Priority: oldest priest, then oldest member.
     */
    protected function promoteSuccessor(Religion $religion, User $formerProphet): void
    {
        // First try to find the oldest priest
        $successor = ReligionMember::where('religion_id', $religion->id)
            ->where('rank', ReligionMember::RANK_PRIEST)
            ->orderBy('joined_at', 'asc')
            ->first();

        // If no priest, find the oldest member
        if (! $successor) {
            $successor = ReligionMember::where('religion_id', $religion->id)
                ->orderBy('joined_at', 'asc')
                ->first();
        }

        // No one left to promote
        if (! $successor) {
            return;
        }

        $successorUser = $successor->user;

        // Promote to prophet
        $successor->update(['rank' => ReligionMember::RANK_PROPHET]);

        // Log the succession
        ReligionLog::log(
            $religion->id,
            ReligionLog::EVENT_LEADERSHIP_TRANSFERRED,
            "{$successorUser->username} became Prophet after {$formerProphet->username} abdicated",
            $successorUser->id
        );

        // Notify the new prophet (sent from former prophet)
        Message::create([
            'sender_id' => $formerProphet->id,
            'channel_type' => Message::CHANNEL_PRIVATE,
            'channel_id' => $successorUser->id,
            'content' => "You have become the Prophet of {$religion->name} after {$formerProphet->username} left the religion.",
        ]);
    }
}
