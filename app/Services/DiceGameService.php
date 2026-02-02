<?php

namespace App\Services;

use App\Models\TavernDiceGame;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DiceGameService
{
    /**
     * Cooldown in minutes between games (production).
     */
    public const COOLDOWN_MINUTES = 5;

    /**
     * Cooldown in seconds for dev environment.
     */
    public const COOLDOWN_SECONDS_DEV = 3;

    /**
     * Minimum wager amount.
     */
    public const MIN_WAGER = 10;

    /**
     * Maximum wager amount.
     */
    public const MAX_WAGER = 2500;

    /**
     * Energy awarded for winning.
     */
    public const ENERGY_WIN = 10;

    /**
     * Energy awarded for losing.
     */
    public const ENERGY_LOSE = 3;

    /**
     * Payout multiplier for high roll game (1.5x bet on win).
     */
    public const STANDARD_MULTIPLIER = 1.5;

    /**
     * Payout multiplier for hazard game (1.75x bet on win).
     */
    public const HAZARD_MULTIPLIER = 1.75;

    /**
     * Payout multiplier for doubles game (3x bet on win).
     */
    public const DOUBLES_MULTIPLIER = 3;

    /**
     * House rake percentage (10% cut from winnings).
     */
    public const HOUSE_RAKE = 0.10;

    /**
     * Check if user can play a dice game.
     *
     * @return array{can_play: bool, reason: string|null, cooldown_ends: string|null}
     */
    public function canPlay(User $user): array
    {
        // Check cooldown
        $lastGame = TavernDiceGame::forUser($user->id)
            ->latest()
            ->first();

        if ($lastGame) {
            $cooldownEnds = app()->environment('local')
                ? $lastGame->created_at->addSeconds(self::COOLDOWN_SECONDS_DEV)
                : $lastGame->created_at->addMinutes(self::COOLDOWN_MINUTES);
            if ($cooldownEnds->isFuture()) {
                return [
                    'can_play' => false,
                    'reason' => 'You must wait before playing again.',
                    'cooldown_ends' => $cooldownEnds->toIso8601String(),
                ];
            }
        }

        // Check minimum gold
        if ($user->gold < self::MIN_WAGER) {
            return [
                'can_play' => false,
                'reason' => 'You need at least '.self::MIN_WAGER.'g to play.',
                'cooldown_ends' => null,
            ];
        }

        return [
            'can_play' => true,
            'reason' => null,
            'cooldown_ends' => null,
        ];
    }

    /**
     * Play a dice game.
     *
     * @return array{success: bool, message: string, won?: bool, rolls?: array, payout?: int, energy?: int, game_id?: int}
     */
    public function play(User $user, string $gameType, int $wager, string $locationType, int $locationId): array
    {
        // Validate game type
        if (! in_array($gameType, TavernDiceGame::GAMES)) {
            return [
                'success' => false,
                'message' => 'Invalid game type.',
            ];
        }

        // Check if can play
        $canPlay = $this->canPlay($user);
        if (! $canPlay['can_play']) {
            return [
                'success' => false,
                'message' => $canPlay['reason'],
            ];
        }

        // Validate wager
        if ($wager < self::MIN_WAGER) {
            return [
                'success' => false,
                'message' => 'Minimum wager is '.self::MIN_WAGER.'g.',
            ];
        }

        if ($wager > self::MAX_WAGER) {
            return [
                'success' => false,
                'message' => 'Maximum wager is '.self::MAX_WAGER.'g.',
            ];
        }

        if ($user->gold < $wager) {
            return [
                'success' => false,
                'message' => "You don't have enough gold.",
            ];
        }

        // Play the game
        $result = match ($gameType) {
            TavernDiceGame::GAME_HIGH_ROLL => $this->playHighRoll($wager),
            TavernDiceGame::GAME_HAZARD => $this->playHazard($wager),
            TavernDiceGame::GAME_DOUBLES => $this->playDoubles($wager),
            default => ['won' => false, 'rolls' => [], 'payout' => -$wager, 'energy' => self::ENERGY_LOSE, 'message' => 'Unknown game.'],
        };

        return DB::transaction(function () use ($user, $gameType, $wager, $locationType, $locationId, $result) {
            // Apply gold change
            if ($result['won']) {
                $user->increment('gold', $result['payout']);
            } else {
                $user->decrement('gold', abs($result['payout']));
            }

            // Award energy (capped at max)
            $energyToAdd = min($result['energy'], $user->max_energy - $user->energy);
            if ($energyToAdd > 0) {
                $user->increment('energy', $energyToAdd);
            }

            // Record the game
            $game = TavernDiceGame::create([
                'user_id' => $user->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'game_type' => $gameType,
                'wager' => $wager,
                'rolls' => $result['rolls'],
                'won' => $result['won'],
                'payout' => $result['won'] ? $result['payout'] : -abs($result['payout']),
                'energy_awarded' => $result['energy'],
            ]);

            return [
                'success' => true,
                'message' => $result['message'],
                'won' => $result['won'],
                'rolls' => $result['rolls'],
                'payout' => $result['won'] ? $result['payout'] : -abs($result['payout']),
                'energy' => $result['energy'],
                'game_id' => $game->id,
                'new_gold' => $user->fresh()->gold,
                'new_energy' => $user->fresh()->energy,
            ];
        });
    }

    /**
     * Calculate payout after house rake.
     */
    protected function calculatePayout(int $wager, float $multiplier): int
    {
        $basePayout = (int) floor($wager * $multiplier);
        $rake = (int) floor($basePayout * self::HOUSE_RAKE);

        return $basePayout - $rake;
    }

    /**
     * Play High Roll game: both roll 2d6, highest wins. Ties go to house.
     *
     * @return array{won: bool, rolls: array, payout: int, energy: int, message: string}
     */
    protected function playHighRoll(int $wager): array
    {
        $playerDice = [random_int(1, 6), random_int(1, 6)];
        $houseDice = [random_int(1, 6), random_int(1, 6)];

        $playerTotal = array_sum($playerDice);
        $houseTotal = array_sum($houseDice);

        $rolls = [
            'player' => $playerDice,
            'house' => $houseDice,
        ];

        if ($playerTotal > $houseTotal) {
            $payout = $this->calculatePayout($wager, self::STANDARD_MULTIPLIER);

            return [
                'won' => true,
                'rolls' => $rolls,
                'payout' => $payout,
                'energy' => self::ENERGY_WIN,
                'message' => "You rolled {$playerTotal}, house rolled {$houseTotal}. You win {$payout}g!",
            ];
        }

        $tieText = $playerTotal === $houseTotal ? " It's a tie - house wins." : '';

        return [
            'won' => false,
            'rolls' => $rolls,
            'payout' => $wager,
            'energy' => self::ENERGY_LOSE,
            'message' => "You rolled {$playerTotal}, house rolled {$houseTotal}.{$tieText} You lose.",
        ];
    }

    /**
     * Play Hazard (Lite): 7/11 instant win, 2/3/12 instant loss, others establish point.
     *
     * @return array{won: bool, rolls: array, payout: int, energy: int, message: string}
     */
    protected function playHazard(int $wager): array
    {
        $allRolls = [];

        // First roll
        $dice = [random_int(1, 6), random_int(1, 6)];
        $total = array_sum($dice);
        $allRolls[] = ['dice' => $dice, 'total' => $total, 'type' => 'come_out'];

        // Instant win on 7 or 11
        if ($total === 7 || $total === 11) {
            $payout = $this->calculatePayout($wager, self::HAZARD_MULTIPLIER);

            return [
                'won' => true,
                'rolls' => $allRolls,
                'payout' => $payout,
                'energy' => self::ENERGY_WIN,
                'message' => "Rolled {$total} on the come-out roll. Natural! You win {$payout}g!",
            ];
        }

        // Instant loss on 2, 3, or 12 (craps)
        if (in_array($total, [2, 3, 12])) {
            return [
                'won' => false,
                'rolls' => $allRolls,
                'payout' => $wager,
                'energy' => self::ENERGY_LOSE,
                'message' => "Rolled {$total} on the come-out roll. Craps! You lose.",
            ];
        }

        // Establish point
        $point = $total;
        $maxRolls = 10; // Prevent infinite loops

        for ($i = 0; $i < $maxRolls; $i++) {
            $dice = [random_int(1, 6), random_int(1, 6)];
            $total = array_sum($dice);
            $allRolls[] = ['dice' => $dice, 'total' => $total, 'type' => 'point'];

            // Hit the point - win
            if ($total === $point) {
                $payout = $this->calculatePayout($wager, self::HAZARD_MULTIPLIER);

                return [
                    'won' => true,
                    'rolls' => $allRolls,
                    'payout' => $payout,
                    'energy' => self::ENERGY_WIN,
                    'message' => "Point was {$point}. Rolled {$total}. You hit your point! You win {$payout}g!",
                ];
            }

            // Roll 7 - lose
            if ($total === 7) {
                return [
                    'won' => false,
                    'rolls' => $allRolls,
                    'payout' => $wager,
                    'energy' => self::ENERGY_LOSE,
                    'message' => "Point was {$point}. Rolled 7. Seven out! You lose.",
                ];
            }
        }

        // Shouldn't happen but safety fallback
        return [
            'won' => false,
            'rolls' => $allRolls,
            'payout' => $wager,
            'energy' => self::ENERGY_LOSE,
            'message' => 'Game ended inconclusively. You lose.',
        ];
    }

    /**
     * Play Doubles: roll 2d6, doubles wins at 3x (with house rake).
     *
     * @return array{won: bool, rolls: array, payout: int, energy: int, message: string}
     */
    protected function playDoubles(int $wager): array
    {
        $dice = [random_int(1, 6), random_int(1, 6)];
        $rolls = ['player' => $dice];

        if ($dice[0] === $dice[1]) {
            $payout = $this->calculatePayout($wager, self::DOUBLES_MULTIPLIER);

            return [
                'won' => true,
                'rolls' => $rolls,
                'payout' => $payout,
                'energy' => self::ENERGY_WIN,
                'message' => "Double {$dice[0]}s! You win {$payout}g!",
            ];
        }

        return [
            'won' => false,
            'rolls' => $rolls,
            'payout' => $wager,
            'energy' => self::ENERGY_LOSE,
            'message' => "Rolled {$dice[0]} and {$dice[1]}. No doubles. You lose.",
        ];
    }

    /**
     * Get recent game history for a user.
     */
    public function getGameHistory(User $user, int $limit = 10): Collection
    {
        return TavernDiceGame::forUser($user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($game) => [
                'id' => $game->id,
                'game_type' => $game->game_type,
                'wager' => $game->wager,
                'won' => $game->won,
                'payout' => $game->payout,
                'energy_awarded' => $game->energy_awarded,
                'played_at' => $game->created_at->diffForHumans(),
            ]);
    }

    /**
     * Get stats for a user at a specific tavern.
     *
     * @return array{wins: int, losses: int, total_profit: int}
     */
    public function getTavernStats(User $user, string $locationType, int $locationId): array
    {
        $games = TavernDiceGame::forUser($user->id)
            ->atLocation($locationType, $locationId)
            ->get();

        return [
            'wins' => $games->where('won', true)->count(),
            'losses' => $games->where('won', false)->count(),
            'total_profit' => $games->sum('payout'),
        ];
    }
}
