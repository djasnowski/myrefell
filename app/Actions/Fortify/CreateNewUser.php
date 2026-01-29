<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\BirthService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        protected InventoryService $inventoryService,
        protected BirthService $birthService
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
                'energy' => 150,
                'max_energy' => 150,
                'gold' => 100, // Starting gold
                'registration_ip' => request()->ip(),
            ]);

            // Create initial skills for the player
            $this->createInitialSkills($user);

            // Give starter items
            $this->inventoryService->giveStarterKit($user);

            // Assign home village and title using BirthService
            $this->birthService->assignNewPlayer($user);

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
}
