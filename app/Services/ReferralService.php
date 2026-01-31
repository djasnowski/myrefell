<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    // Reward amounts
    public const REFERRER_REWARD = 250;

    public const REFERRED_BONUS = 50;

    // Anti-abuse settings
    public const REQUIRED_LEVEL = 2;

    public const MIN_ACCOUNT_AGE_MINUTES = 60; // 1 hour minimum before qualifying

    public const IP_COOLDOWN_DAYS = 30; // One referral per IP per month

    /**
     * Generate a unique referral code for a user.
     */
    public function generateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        do {
            $code = 'REF-'.strtoupper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Get or create a referral code for a user.
     */
    public function getReferralCode(User $user): string
    {
        return $user->referral_code ?? $this->generateReferralCode($user);
    }

    /**
     * Get the referral link for a user.
     */
    public function getReferralLink(User $user): string
    {
        $code = $this->getReferralCode($user);

        return url('/register?ref='.$code);
    }

    /**
     * Find a user by their referral code.
     */
    public function findReferrerByCode(string $code): ?User
    {
        return User::where('referral_code', $code)->first();
    }

    /**
     * Check if an IP address has been used for a referral recently.
     */
    public function isIpRecentlyUsed(string $ipAddress): bool
    {
        return Referral::where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subDays(self::IP_COOLDOWN_DAYS))
            ->exists();
    }

    /**
     * Create a referral record when a new user registers with a referral code.
     */
    public function createReferral(User $referrer, User $referred, ?string $ipAddress = null): ?Referral
    {
        // Don't allow self-referral
        if ($referrer->id === $referred->id) {
            return null;
        }

        // Check if user was already referred
        if (Referral::where('referred_id', $referred->id)->exists()) {
            return null;
        }

        // Check IP cooldown
        if ($ipAddress && $this->isIpRecentlyUsed($ipAddress)) {
            // Still create the referral but mark it - we'll check this during reward
            // This way the referred user still gets tracked, but reward may be blocked
        }

        return Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'status' => Referral::STATUS_PENDING,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Check if a referred user qualifies for the referral reward.
     */
    public function checkQualification(User $referred): bool
    {
        $referral = Referral::where('referred_id', $referred->id)->first();

        if (! $referral || ! $referral->isPending()) {
            return false;
        }

        // Check email verification
        if (! $referred->hasVerifiedEmail()) {
            return false;
        }

        // Check level requirement (combat level)
        if ($referred->combat_level < self::REQUIRED_LEVEL) {
            return false;
        }

        // Check minimum account age (anti-abuse)
        $accountAgeMinutes = $referred->created_at->diffInMinutes(now());
        if ($accountAgeMinutes < self::MIN_ACCOUNT_AGE_MINUTES) {
            return false;
        }

        return true;
    }

    /**
     * Process qualification and potentially award the referral.
     */
    public function processQualification(User $referred): bool
    {
        if (! $this->checkQualification($referred)) {
            return false;
        }

        $referral = Referral::where('referred_id', $referred->id)->first();

        if (! $referral) {
            return false;
        }

        // Check IP abuse - if this IP was used for another referral in cooldown period
        // (excluding this specific referral)
        $ipAbused = false;
        if ($referral->ip_address) {
            $ipAbused = Referral::where('ip_address', $referral->ip_address)
                ->where('id', '!=', $referral->id)
                ->where('created_at', '>=', now()->subDays(self::IP_COOLDOWN_DAYS))
                ->where('status', '!=', Referral::STATUS_PENDING)
                ->exists();
        }

        // Mark as qualified
        $referral->update([
            'status' => Referral::STATUS_QUALIFIED,
            'qualified_at' => now(),
        ]);

        // Award the referrer (if not IP abused)
        if (! $ipAbused) {
            $this->awardReferrer($referral);
        }

        return true;
    }

    /**
     * Award the referrer for a successful referral.
     */
    protected function awardReferrer(Referral $referral): void
    {
        if ($referral->isRewarded()) {
            return;
        }

        $referrer = $referral->referrer;

        if (! $referrer) {
            return;
        }

        // Award gold
        $referrer->increment('gold', self::REFERRER_REWARD);

        // Update referral record
        $referral->update([
            'status' => Referral::STATUS_REWARDED,
            'rewarded_at' => now(),
            'reward_amount' => self::REFERRER_REWARD,
        ]);

        // TODO: Could add notification here
    }

    /**
     * Award the referred user their signup bonus (on email verification).
     */
    public function awardReferredBonus(User $referred): bool
    {
        $referral = Referral::where('referred_id', $referred->id)->first();

        if (! $referral) {
            return false;
        }

        // Only award if email is verified and hasn't been awarded yet
        if (! $referred->hasVerifiedEmail()) {
            return false;
        }

        // Check if bonus was already awarded (could track this in metadata or separate field)
        // For now, we'll use a simple check - award on first email verification
        $referred->increment('gold', self::REFERRED_BONUS);

        return true;
    }

    /**
     * Get referral statistics for a user.
     */
    public function getStats(User $user): array
    {
        $referrals = Referral::where('referrer_id', $user->id)->get();

        return [
            'referral_code' => $this->getReferralCode($user),
            'referral_link' => $this->getReferralLink($user),
            'total_referrals' => $referrals->count(),
            'pending_referrals' => $referrals->where('status', Referral::STATUS_PENDING)->count(),
            'qualified_referrals' => $referrals->where('status', Referral::STATUS_QUALIFIED)->count(),
            'rewarded_referrals' => $referrals->where('status', Referral::STATUS_REWARDED)->count(),
            'total_earned' => $referrals->where('status', Referral::STATUS_REWARDED)->sum('reward_amount'),
        ];
    }

    /**
     * Get detailed referral list for a user.
     */
    public function getReferralsList(User $user): array
    {
        return Referral::where('referrer_id', $user->id)
            ->with('referred:id,username,level,created_at')
            ->latest()
            ->get()
            ->map(fn (Referral $r) => [
                'id' => $r->id,
                'username' => $r->referred->username ?? 'Unknown',
                'level' => $r->referred->level ?? 0,
                'status' => $r->status,
                'reward_amount' => $r->reward_amount,
                'created_at' => $r->created_at->toIso8601String(),
                'qualified_at' => $r->qualified_at?->toIso8601String(),
                'rewarded_at' => $r->rewarded_at?->toIso8601String(),
            ])
            ->toArray();
    }
}
