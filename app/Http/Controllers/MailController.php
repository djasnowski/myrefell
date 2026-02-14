<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMailRequest;
use App\Models\PlayerMail;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailController extends Controller
{
    public function __construct(
        protected MailService $mailService
    ) {}

    /**
     * Display the mail page with inbox and sent tabs.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab = $request->get('tab', 'inbox');

        $inbox = $this->mailService->getInbox($user);
        $sent = $this->mailService->getSentMail($user);

        return Inertia::render('Mail/Index', [
            'inbox' => $inbox,
            'sent' => $sent,
            'tab' => $tab,
            'unread_count' => $this->mailService->getUnreadCount($user),
            'player_gold' => $user->gold,
            'mail_cost' => PlayerMail::MAIL_COST,
        ]);
    }

    /**
     * Read a single mail.
     */
    public function show(Request $request, PlayerMail $playerMail): Response|RedirectResponse
    {
        $user = $request->user();

        $mail = $this->mailService->readMail($user, $playerMail);

        if (! $mail) {
            return back()->with('error', 'You do not have access to this mail.');
        }

        return Inertia::render('Mail/Index', [
            'inbox' => $this->mailService->getInbox($user),
            'sent' => $this->mailService->getSentMail($user),
            'tab' => $mail->recipient_id === $user->id ? 'inbox' : 'sent',
            'selected_mail' => $mail,
            'unread_count' => $this->mailService->getUnreadCount($user),
            'player_gold' => $user->gold,
            'mail_cost' => PlayerMail::MAIL_COST,
        ]);
    }

    /**
     * Send a new mail.
     */
    public function send(SendMailRequest $request): RedirectResponse
    {
        $sender = $request->user();
        $recipient = User::where('username', $request->recipient_username)->first();

        if (! $recipient) {
            return back()->with('error', 'Player not found.');
        }

        $result = $this->mailService->sendMail(
            $sender,
            $recipient,
            $request->subject,
            $request->body
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Delete a mail (soft-delete from user's perspective).
     */
    public function delete(Request $request, PlayerMail $playerMail): RedirectResponse
    {
        $user = $request->user();

        $deleted = $this->mailService->deleteMail($user, $playerMail);

        if (! $deleted) {
            return back()->with('error', 'You do not have access to this mail.');
        }

        return back()->with('success', 'Mail deleted.');
    }

}
