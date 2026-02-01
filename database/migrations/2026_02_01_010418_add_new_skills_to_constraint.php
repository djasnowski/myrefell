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
        // Update the check constraint to allow 'agility' and 'herblore'
        DB::statement('ALTER TABLE player_skills DROP CONSTRAINT IF EXISTS player_skills_skill_name_check');
        DB::statement("
            ALTER TABLE player_skills
            ADD CONSTRAINT player_skills_skill_name_check
            CHECK (skill_name IN (
                'attack', 'strength', 'defense', 'hitpoints', 'range',
                'farming', 'mining', 'fishing', 'woodcutting',
                'cooking', 'smithing', 'crafting', 'prayer', 'thieving',
                'agility', 'herblore'
            ))
        ");

        // Add agility skill to all existing players who don't have it
        $users = User::whereDoesntHave('skills', function ($query) {
            $query->where('skill_name', 'agility');
        })->get();

        foreach ($users as $user) {
            PlayerSkill::create([
                'player_id' => $user->id,
                'skill_name' => 'agility',
                'level' => 1,
                'xp' => 0,
            ]);
        }

        // Add herblore skill to all existing players who don't have it
        $users = User::whereDoesntHave('skills', function ($query) {
            $query->where('skill_name', 'herblore');
        })->get();

        foreach ($users as $user) {
            PlayerSkill::create([
                'player_id' => $user->id,
                'skill_name' => 'herblore',
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
        // Delete all agility and herblore skills
        PlayerSkill::where('skill_name', 'agility')->delete();
        PlayerSkill::where('skill_name', 'herblore')->delete();

        // Restore the old constraint without 'agility' and 'herblore'
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
