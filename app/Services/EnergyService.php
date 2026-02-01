<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class EnergyService
{
    /**
     * Energy regeneration rate in minutes.
     */
    public const REGEN_MINUTES = 5;

    /**
     * Energy amount regenerated per tick.
     */
    public const REGEN_AMOUNT = 10;

    /**
     * Check if player has enough energy.
     */
    public function hasEnergy(User $player, int $amount): bool
    {
        return $player->energy >= $amount;
    }

    /**
     * Consume energy from player.
     *
     * @throws \Exception If player doesn't have enough energy
     */
    public function consumeEnergy(User $player, int $amount): bool
    {
        if (!$this->hasEnergy($player, $amount)) {
            return false;
        }

        $player->energy -= $amount;
        $player->save();

        return true;
    }

    /**
     * Add energy to player (up to max).
     */
    public function addEnergy(User $player, int $amount): int
    {
        $newEnergy = min($player->energy + $amount, $player->max_energy);
        $gained = $newEnergy - $player->energy;

        $player->energy = $newEnergy;
        $player->save();

        return $gained;
    }

    /**
     * Set energy to a specific value (clamped to 0-max).
     */
    public function setEnergy(User $player, int $amount): void
    {
        $player->energy = max(0, min($amount, $player->max_energy));
        $player->save();
    }

    /**
     * Set energy on death (25% of previous energy).
     */
    public function setEnergyOnDeath(User $player): void
    {
        $newEnergy = (int) floor($player->energy * 0.25);
        $this->setEnergy($player, $newEnergy);
    }

    /**
     * Regenerate energy for a single player.
     * Called periodically by the scheduler.
     */
    public function regenerateEnergy(User $player): int
    {
        if ($player->energy >= $player->max_energy) {
            return 0;
        }

        return $this->addEnergy($player, self::REGEN_AMOUNT);
    }

    /**
     * Regenerate energy for all players who aren't at max.
     * Returns the number of players affected.
     */
    public function regenerateAllPlayers(): int
    {
        $affected = User::where('energy', '<', \DB::raw('max_energy'))
            ->increment('energy', self::REGEN_AMOUNT);

        // Clamp any that went over max
        User::whereRaw('energy > max_energy')
            ->update(['energy' => \DB::raw('max_energy')]);

        return $affected;
    }

    /**
     * Get the time until next energy regen.
     */
    public function getTimeUntilNextEnergy(): int
    {
        // Calculate time until next 5-minute mark
        $now = Carbon::now();
        $nextRegen = $now->copy()->addMinutes(self::REGEN_MINUTES - ($now->minute % self::REGEN_MINUTES))->second(0);

        return $now->diffInSeconds($nextRegen);
    }

    /**
     * Get energy regeneration info for display.
     */
    public function getRegenInfo(User $player): array
    {
        $atMax = $player->energy >= $player->max_energy;

        return [
            'current' => $player->energy,
            'max' => $player->max_energy,
            'at_max' => $atMax,
            'regen_rate' => self::REGEN_MINUTES,
            'regen_amount' => self::REGEN_AMOUNT,
            'seconds_until_next' => $atMax ? null : $this->getTimeUntilNextEnergy(),
        ];
    }
}
