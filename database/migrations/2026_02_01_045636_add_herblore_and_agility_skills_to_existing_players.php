<?php

use App\Models\PlayerSkill;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the check constraint to allow new skills
        DB::statement('ALTER TABLE player_skills DROP CONSTRAINT IF EXISTS player_skills_skill_name_check');
        DB::statement("
            ALTER TABLE player_skills
            ADD CONSTRAINT player_skills_skill_name_check
            CHECK (skill_name IN (
                'attack', 'strength', 'defense', 'hitpoints', 'range',
                'farming', 'mining', 'fishing', 'woodcutting',
                'cooking', 'smithing', 'crafting', 'prayer', 'thieving',
                'herblore', 'agility'
            ))
        ");

        // Add herblore skill to all existing players who don't have it
        $usersWithoutHerblore = User::whereDoesntHave('skills', function ($query) {
            $query->where('skill_name', 'herblore');
        })->get();

        foreach ($usersWithoutHerblore as $user) {
            PlayerSkill::create([
                'player_id' => $user->id,
                'skill_name' => 'herblore',
                'level' => 1,
                'xp' => 0,
            ]);
        }

        // Add agility skill to all existing players who don't have it
        $usersWithoutAgility = User::whereDoesntHave('skills', function ($query) {
            $query->where('skill_name', 'agility');
        })->get();

        foreach ($usersWithoutAgility as $user) {
            PlayerSkill::create([
                'player_id' => $user->id,
                'skill_name' => 'agility',
                'level' => 1,
                'xp' => 0,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete herblore and agility skills
        PlayerSkill::where('skill_name', 'herblore')->delete();
        PlayerSkill::where('skill_name', 'agility')->delete();

        // Restore the old constraint
        DB::statement('ALTER TABLE player_skills DROP CONSTRAINT IF EXISTS player_skills_skill_name_check');
        DB::statement("
            ALTER TABLE player_skills
            ADD CONSTRAINT player_skills_skill_name_check
            CHECK (skill_name IN (
                'attack', 'strength', 'defense', 'hitpoints', 'range',
                'farming', 'mining', 'fishing', 'woodcutting',
                'cooking', 'smithing', 'crafting', 'prayer', 'thieving'
            ))
        ");
    }
};
