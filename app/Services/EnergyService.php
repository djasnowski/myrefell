<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class EnergyService
{
    public function __construct(
        protected BlessingEffectService $blessingEffectService,
        protected BeliefEffectService $beliefEffectService
    ) {}

    /**
     * Energy regeneration rate in seconds.
     */
    public const REGEN_SECONDS = 10; // 10 seconds for testing (normally 300 = 5 minutes)

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
     * Applies belief energy cost reduction.
     *
     * @throws \Exception If player doesn't have enough energy
     */
    public function consumeEnergy(User $player, int $amount): bool
    {
        // Apply belief energy cost reduction (Sloth belief)
        $energyCostReduction = $this->beliefEffectService->getEffect($player, 'energy_cost_reduction');
        if ($energyCostReduction > 0) {
            $amount = (int) floor($amount * (1 - $energyCostReduction / 100));
            $amount = max(1, $amount); // Minimum 1 energy cost
        }

        if (! $this->hasEnergy($player, $amount)) {
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
     * Applies blessing energy regen bonus.
     */
    public function regenerateEnergy(User $player): int
    {
        if ($player->energy >= $player->max_energy) {
            return 0;
        }

        $regenAmount = self::REGEN_AMOUNT;

        // Apply blessing energy regen bonus (e.g., 20 = +20% more energy)
        $energyRegenBonus = $this->blessingEffectService->getEffect($player, 'energy_regen_bonus');

        // Apply HQ prayer energy recovery bonus (flat bonus, not percentage)
        $regenAmount += (int) $this->blessingEffectService->getEffect($player, 'energy_recovery_bonus');

        // Apply belief energy regen bonus (Temperance belief)
        $energyRegenBonus += $this->beliefEffectService->getEffect($player, 'energy_regen_bonus');

        if ($energyRegenBonus > 0) {
            $regenAmount = (int) ceil($regenAmount * (1 + $energyRegenBonus / 100));
        }

        return $this->addEnergy($player, $regenAmount);
    }

    /**
     * Regenerate energy for all players who aren't at max.
     * Returns the number of players affected.
     */
    public function regenerateAllPlayers(): int
    {
        // Get all players who need energy regen
        $players = User::where('energy', '<', \DB::raw('max_energy'))->get();
        $affected = 0;

        foreach ($players as $player) {
            $regenAmount = self::REGEN_AMOUNT;

            // Agility bonus: +1 energy per 20 agility levels
            $agilityLevel = $player->getSkillLevel('agility');
            $regenAmount += (int) floor($agilityLevel / 20);

            // Apply blessing energy regen bonus (e.g., 20 = +20% more energy)
            $energyRegenBonus = $this->blessingEffectService->getEffect($player, 'energy_regen_bonus');

            // Apply HQ prayer energy recovery bonus (flat bonus, not percentage)
            $regenAmount += (int) $this->blessingEffectService->getEffect($player, 'energy_recovery_bonus');

            // Apply belief energy regen bonus (Temperance belief)
            $energyRegenBonus += $this->beliefEffectService->getEffect($player, 'energy_regen_bonus');

            if ($energyRegenBonus > 0) {
                $regenAmount = (int) ceil($regenAmount * (1 + $energyRegenBonus / 100));
            }

            // Add energy up to max
            $newEnergy = min($player->energy + $regenAmount, $player->max_energy);
            if ($newEnergy > $player->energy) {
                $player->energy = $newEnergy;
                $player->save();
                $affected++;
            }
        }

        return $affected;
    }

    /**
     * Get the time until next energy regen.
     */
    public function getTimeUntilNextEnergy(): int
    {
        // Calculate time until next regen interval
        $now = Carbon::now();
        $currentSecond = $now->timestamp;
        $nextRegen = (int) ceil($currentSecond / self::REGEN_SECONDS) * self::REGEN_SECONDS;

        return max(1, $nextRegen - $currentSecond);
    }

    /**
     * Get energy regeneration info for display.
     */
    public function getRegenInfo(User $player): array
    {
        $atMax = $player->energy >= $player->max_energy;

        // Calculate actual regen amount with all bonuses
        $baseAmount = self::REGEN_AMOUNT;
        $regenAmount = $baseAmount;
        $bonuses = [];

        // Apply blessing energy regen bonus (percentage)
        $blessingRegenBonus = $this->blessingEffectService->getEffect($player, 'energy_regen_bonus');
        if ($blessingRegenBonus > 0) {
            $bonuses[] = [
                'source' => 'Blessing',
                'amount' => "+{$blessingRegenBonus}%",
            ];
        }

        // Apply HQ prayer energy recovery bonus (flat bonus)
        $hqRecoveryBonus = (int) $this->blessingEffectService->getEffect($player, 'energy_recovery_bonus');
        if ($hqRecoveryBonus > 0) {
            $regenAmount += $hqRecoveryBonus;
            $bonuses[] = [
                'source' => 'HQ Prayer',
                'amount' => "+{$hqRecoveryBonus}",
            ];
        }

        // Apply belief energy regen bonus (percentage)
        $beliefRegenBonus = $this->beliefEffectService->getEffect($player, 'energy_regen_bonus');
        if ($beliefRegenBonus > 0) {
            $bonuses[] = [
                'source' => 'Belief',
                'amount' => "+{$beliefRegenBonus}%",
            ];
        }

        // Apply total percentage bonus
        $totalPercentBonus = $blessingRegenBonus + $beliefRegenBonus;
        if ($totalPercentBonus > 0) {
            $regenAmount = (int) ceil($regenAmount * (1 + $totalPercentBonus / 100));
        }

        return [
            'current' => $player->energy,
            'max' => $player->max_energy,
            'at_max' => $atMax,
            'regen_rate' => self::REGEN_SECONDS,
            'regen_amount' => $regenAmount,
            'base_regen_amount' => $baseAmount,
            'regen_bonuses' => $bonuses,
            'seconds_until_next' => $atMax ? null : $this->getTimeUntilNextEnergy(),
        ];
    }
}
