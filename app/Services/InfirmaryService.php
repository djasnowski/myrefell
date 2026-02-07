<?php

namespace App\Services;

use App\Models\User;

class InfirmaryService
{
    /**
     * Infirmary duration in seconds (600s production, 10s local).
     */
    public function getInfirmaryDurationSeconds(): int
    {
        return app()->environment('local') ? 10 : 600;
    }

    /**
     * Admit a player to the infirmary at their current location.
     */
    public function admitPlayer(User $player): void
    {
        $player->update([
            'is_in_infirmary' => true,
            'infirmary_started_at' => now(),
            'infirmary_heals_at' => now()->addSeconds($this->getInfirmaryDurationSeconds()),
        ]);
    }

    /**
     * Check if the infirmary timer has expired and discharge the player.
     * Returns true if the player was discharged.
     */
    public function checkAndDischarge(User $player): bool
    {
        if (! $player->is_in_infirmary) {
            return false;
        }

        if ($player->infirmary_heals_at && $player->infirmary_heals_at->isPast()) {
            $this->dischargePlayer($player);

            return true;
        }

        return false;
    }

    /**
     * Discharge the player from the infirmary, restoring full HP.
     */
    public function dischargePlayer(User $player): void
    {
        $player->update([
            'hp' => $player->max_hp,
            'is_in_infirmary' => false,
            'infirmary_started_at' => null,
            'infirmary_heals_at' => null,
        ]);
    }

    /**
     * Get the infirmary status data for the frontend.
     *
     * @return array{is_in_infirmary: bool, remaining_seconds: int, heals_at: string|null, started_at: string|null}|null
     */
    public function getInfirmaryStatus(User $player): ?array
    {
        if (! $player->is_in_infirmary) {
            return null;
        }

        $remainingSeconds = 0;
        if ($player->infirmary_heals_at) {
            $remainingSeconds = max(0, (int) now()->diffInSeconds($player->infirmary_heals_at, false));
        }

        return [
            'is_in_infirmary' => true,
            'remaining_seconds' => $remainingSeconds,
            'heals_at' => $player->infirmary_heals_at?->toIso8601String(),
            'started_at' => $player->infirmary_started_at?->toIso8601String(),
        ];
    }
}
