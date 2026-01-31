<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\BirthService;
use App\Services\InventoryService;
use App\Services\ReferralService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        protected InventoryService $inventoryService,
        protected BirthService $birthService,
        protected ReferralService $referralService
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->registrationRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'username' => $input['username'],
                'email' => $input['email'],
                'password' => $input['password'],
                'gender' => $input['gender'],
                'hp' => 10,
                'max_hp' => 10,
                'energy' => 300,
                'max_energy' => 300,
                'gold' => 100, // Starting gold
                'registration_ip' => request()->ip(),
            ]);

            // Create initial skills for the player
            $this->createInitialSkills($user);

            // Give starter items
            $this->inventoryService->giveStarterKit($user);

            // Assign home village and title using BirthService
            $this->birthService->assignNewPlayer($user);

            // Handle referral if a referral code was provided
            $this->handleReferral($user, $input['referral_code'] ?? null);

            return $user;
        });
    }

    /**
     * Create initial skills for a new player.
     * Combat skills start at level 5, others at level 1.
     */
    protected function createInitialSkills(User $user): void
    {
        foreach (PlayerSkill::SKILLS as $skill) {
            $isCombatSkill = in_array($skill, PlayerSkill::COMBAT_SKILLS);
            $startingLevel = $isCombatSkill ? 5 : 1;
            $startingXp = PlayerSkill::xpForLevel($startingLevel);

            PlayerSkill::create([
                'player_id' => $user->id,
                'skill_name' => $skill,
                'level' => $startingLevel,
                'xp' => $startingXp,
            ]);
        }
    }

    /**
     * Handle referral tracking for a new user.
     */
    protected function handleReferral(User $user, ?string $referralCode): void
    {
        if (! $referralCode) {
            return;
        }

        $referrer = $this->referralService->findReferrerByCode($referralCode);

        if (! $referrer) {
            return;
        }

        $this->referralService->createReferral(
            $referrer,
            $user,
            request()->ip()
        );
    }
}
