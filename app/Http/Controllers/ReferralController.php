<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    /**
     * Display the referral dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stats = $this->referralService->getStats($user);
        $referrals = $this->referralService->getReferralsList($user);

        return Inertia::render('Referrals/Index', [
            'stats' => $stats,
            'referrals' => $referrals,
            'rewards' => [
                'referrer_reward' => ReferralService::REFERRER_REWARD,
                'referred_bonus' => ReferralService::REFERRED_BONUS,
                'required_level' => ReferralService::REQUIRED_LEVEL,
            ],
        ]);
    }
}
