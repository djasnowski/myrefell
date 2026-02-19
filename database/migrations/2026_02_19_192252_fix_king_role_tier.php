<?php

use App\Models\PlayerRole;
use App\Models\PlayerTitle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix king role tier from 5 to 7
        Role::where('slug', 'king')->update(['tier' => 7]);

        // Fix existing kings' titles: they should have title 'king' (tier 14) not 'baron' (tier 8)
        $kingRole = Role::where('slug', 'king')->first();
        if (! $kingRole) {
            return;
        }

        $activeKings = PlayerRole::where('role_id', $kingRole->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeKings as $playerRole) {
            $user = User::find($playerRole->user_id);
            if (! $user) {
                continue;
            }

            // Revoke existing active titles
            PlayerTitle::where('user_id', $user->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'revoked_at' => now(),
                ]);

            // Create king title
            PlayerTitle::create([
                'user_id' => $user->id,
                'title' => 'king',
                'tier' => 14,
                'domain_type' => $playerRole->location_type,
                'domain_id' => $playerRole->location_id,
                'acquisition_method' => 'appointment',
                'is_active' => true,
                'granted_at' => now(),
                'legitimacy' => 50,
            ]);

            // Update user's primary title
            $user->update([
                'primary_title' => 'king',
                'title_tier' => 14,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('slug', 'king')->update(['tier' => 5]);
    }
};
