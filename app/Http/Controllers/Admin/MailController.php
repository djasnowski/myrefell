<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlayerMail;
use Inertia\Inertia;
use Inertia\Response;

class MailController extends Controller
{
    /**
     * Display a listing of all player mail for moderation.
     */
    public function index(): Response
    {
        $mails = PlayerMail::query()
            ->with(['sender:id,username', 'recipient:id,username'])
            ->orderByDesc('created_at')
            ->paginate(50)
            ->through(fn (PlayerMail $mail) => [
                'id' => $mail->id,
                'sender_id' => $mail->sender_id,
                'sender_username' => $mail->sender?->username ?? 'Unknown',
                'recipient_id' => $mail->recipient_id,
                'recipient_username' => $mail->recipient?->username ?? 'Unknown',
                'subject' => $mail->subject,
                'body' => $mail->body,
                'is_read' => $mail->is_read,
                'is_carrier_pigeon' => $mail->is_carrier_pigeon,
                'gold_cost' => $mail->gold_cost,
                'is_deleted_by_sender' => $mail->is_deleted_by_sender,
                'is_deleted_by_recipient' => $mail->is_deleted_by_recipient,
                'created_at' => $mail->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Mail/Index', [
            'mails' => $mails,
        ]);
    }
}
