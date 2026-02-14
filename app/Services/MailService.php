<?php

namespace App\Services;

use App\Models\PlayerMail;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MailService
{
    /**
     * Send mail from one player to another.
     *
     * @return array{success: bool, message: string, mail?: PlayerMail}
     */
    public function sendMail(User $sender, User $recipient, string $subject, string $body): array
    {
        if ($sender->id === $recipient->id) {
            return [
                'success' => false,
                'message' => 'You cannot send mail to yourself.',
            ];
        }

        $cost = PlayerMail::MAIL_COST;

        if ($sender->gold < $cost) {
            return [
                'success' => false,
                'message' => "You need {$cost}g to send mail.",
            ];
        }

        return DB::transaction(function () use ($sender, $recipient, $subject, $body, $cost) {
            $sender->decrement('gold', $cost);

            $mail = PlayerMail::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'subject' => $subject,
                'body' => $body,
                'gold_cost' => $cost,
                'is_carrier_pigeon' => true,
            ]);

            return [
                'success' => true,
                'message' => "Mail sent via carrier pigeon ({$cost}g)!",
                'mail' => $mail,
            ];
        });
    }

    /**
     * Get paginated inbox for a user.
     */
    public function getInbox(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return PlayerMail::forInbox($user->id)
            ->with('sender:id,username')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get paginated sent mail for a user.
     */
    public function getSentMail(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return PlayerMail::forSent($user->id)
            ->with('recipient:id,username')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Read a specific mail, marking it as read if the user is the recipient.
     */
    public function readMail(User $user, PlayerMail $mail): ?PlayerMail
    {
        if ($mail->recipient_id !== $user->id && $mail->sender_id !== $user->id) {
            return null;
        }

        if ($mail->recipient_id === $user->id) {
            $mail->markAsRead();
        }

        $mail->load(['sender:id,username', 'recipient:id,username']);

        return $mail;
    }

    /**
     * Delete a mail from the user's perspective (soft-delete).
     */
    public function deleteMail(User $user, PlayerMail $mail): bool
    {
        if ($mail->sender_id === $user->id) {
            $mail->deleteForSender();

            return true;
        }

        if ($mail->recipient_id === $user->id) {
            $mail->deleteForRecipient();

            return true;
        }

        return false;
    }

    /**
     * Get unread mail count for sidebar badge.
     */
    public function getUnreadCount(User $user): int
    {
        return PlayerMail::forInbox($user->id)
            ->unread()
            ->count();
    }
}
