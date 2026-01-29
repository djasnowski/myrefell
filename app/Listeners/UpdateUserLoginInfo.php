<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateUserLoginInfo
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $event->user->update([
            'last_login_ip' => request()->ip(),
            'last_login_at' => now(),
        ]);
    }
}
