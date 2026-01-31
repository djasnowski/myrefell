<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Auth\Events\Verified;

class CheckReferralQualification
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    /**
     * Handle email verification events.
     */
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        // Award the referred user their bonus (50 gold)
        $this->referralService->awardReferredBonus($user);

        // Check if they qualify for the referrer's reward
        $this->referralService->processQualification($user);
    }
}
