<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ForgotUsernameNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class ForgotUsernameController extends Controller
{
    /**
     * Display the forgot username form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-username', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle sending the username reminder email.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Rate limiting: 5 attempts per minute per IP
        $key = 'forgot-username:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 60);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Always show success message to prevent email enumeration
        // But only actually send if user exists
        if ($user) {
            $user->notify(new ForgotUsernameNotification);
        }

        return back()->with('status', 'If an account with that email exists, we have sent a username reminder.');
    }
}
