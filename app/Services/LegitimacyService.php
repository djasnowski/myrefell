<?php

namespace App\Services;

use App\Models\Election;
use App\Models\LegitimacyEvent;
use App\Models\NoConfidenceVote;
use App\Models\PlayerRole;
use App\Models\PlayerTitle;
use App\Models\Religion;
use App\Models\Trial;
use Illuminate\Database\Eloquent\Model;

class LegitimacyService
{
    // Legitimacy change values
    public const ELECTION_LANDSLIDE = 20;      // >70% of vote
    public const ELECTION_MAJORITY = 10;       // 55-70% of vote
    public const ELECTION_NARROW = 5;          // 50-55% of vote
    public const ELECTION_CONTESTED = -5;      // Won but contested

    public const TIME_IN_OFFICE_MONTHLY = 1;   // +1 per month, max +20
    public const TIME_IN_OFFICE_MAX = 20;

    public const POLICY_SUCCESS = 10;
    public const POLICY_FAILURE = -10;

    public const WAR_WON = 10;
    public const WAR_LOST = -20;

    public const CHURCH_SUPPORT = 15;
    public const CHURCH_OPPOSITION = -25;
    public const EXCOMMUNICATION = -30;

    public const SCANDAL_MINOR = -10;
    public const SCANDAL_MAJOR = -20;
    public const SCANDAL_SEVERE = -30;

    public const NO_CONFIDENCE_SURVIVED = 5;

    public const CRIME_CONVICTED = -15;

    public const TAX_SUCCESS = 5;
    public const TAX_FAILURE = -5;

    public const POPULAR_DECISION = 5;
    public const UNPOPULAR_DECISION = -10;

    // Legitimacy thresholds
    public const THRESHOLD_VERY_HIGH = 80;
    public const THRESHOLD_HIGH = 65;
    public const THRESHOLD_STABLE = 50;
    public const THRESHOLD_LOW = 35;
    public const THRESHOLD_CRITICAL = 20;

    /**
     * Apply legitimacy change to a player role or title.
     */
    public function applyChange(
        PlayerRole|PlayerTitle $holder,
        string $eventType,
        int $change,
        ?string $description = null,
        ?array $metadata = null
    ): LegitimacyEvent {
        $before = $holder->legitimacy;
        $after = max(0, min(100, $before + $change));

        $holder->update(['legitimacy' => $after]);

        return LegitimacyEvent::create([
            'player_role_id' => $holder instanceof PlayerRole ? $holder->id : null,
            'holder_type' => get_class($holder),
            'holder_id' => $holder->id,
            'event_type' => $eventType,
            'legitimacy_change' => $change,
            'legitimacy_before' => $before,
            'legitimacy_after' => $after,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Handle election result and set initial legitimacy.
     */
    public function handleElectionResult(PlayerRole|PlayerTitle $holder, Election $election): LegitimacyEvent
    {
        $winningCandidate = $election->candidates()
            ->where('user_id', $holder->user_id)
            ->first();

        if (!$winningCandidate) {
            return $this->applyChange(
                $holder,
                LegitimacyEvent::TYPE_ELECTION_NARROW,
                self::ELECTION_NARROW,
                'Assumed office'
            );
        }

        $totalVotes = $election->candidates()->sum('votes_received');
        $winnerVotes = $winningCandidate->votes_received;

        if ($totalVotes === 0) {
            // Uncontested election
            return $this->applyChange(
                $holder,
                LegitimacyEvent::TYPE_ELECTION_MAJORITY,
                self::ELECTION_MAJORITY,
                'Won uncontested election'
            );
        }

        $votePercentage = ($winnerVotes / $totalVotes) * 100;

        if ($votePercentage >= 70) {
            $type = LegitimacyEvent::TYPE_ELECTION_LANDSLIDE;
            $change = self::ELECTION_LANDSLIDE;
            $description = "Landslide victory with {$votePercentage}% of the vote";
        } elseif ($votePercentage >= 55) {
            $type = LegitimacyEvent::TYPE_ELECTION_MAJORITY;
            $change = self::ELECTION_MAJORITY;
            $description = "Strong victory with {$votePercentage}% of the vote";
        } elseif ($votePercentage >= 50) {
            $type = LegitimacyEvent::TYPE_ELECTION_NARROW;
            $change = self::ELECTION_NARROW;
            $description = "Narrow victory with {$votePercentage}% of the vote";
        } else {
            // Plurality win (most votes but <50%)
            $type = LegitimacyEvent::TYPE_ELECTION_CONTESTED;
            $change = self::ELECTION_CONTESTED;
            $description = "Contested victory with only {$votePercentage}% of the vote";
        }

        // Reset legitimacy to base 50, then apply election modifier
        $holder->update(['legitimacy' => 50]);

        return $this->applyChange($holder, $type, $change, $description, [
            'election_id' => $election->id,
            'vote_percentage' => round($votePercentage, 1),
            'total_votes' => $totalVotes,
            'winner_votes' => $winnerVotes,
        ]);
    }

    /**
     * Apply monthly time-in-office bonus.
     */
    public function applyMonthlyBonus(PlayerRole|PlayerTitle $holder): ?LegitimacyEvent
    {
        // Check if already at max time bonus
        $existingTimeBonus = $holder->legitimacyEvents()
            ->where('event_type', LegitimacyEvent::TYPE_TIME_IN_OFFICE)
            ->sum('legitimacy_change');

        if ($existingTimeBonus >= self::TIME_IN_OFFICE_MAX) {
            return null;
        }

        $holder->increment('months_in_office');

        return $this->applyChange(
            $holder,
            LegitimacyEvent::TYPE_TIME_IN_OFFICE,
            self::TIME_IN_OFFICE_MONTHLY,
            "Month {$holder->months_in_office} in office"
        );
    }

    /**
     * Handle surviving a no-confidence vote.
     */
    public function handleNoConfidenceSurvived(PlayerRole|PlayerTitle $holder, NoConfidenceVote $vote): LegitimacyEvent
    {
        return $this->applyChange(
            $holder,
            LegitimacyEvent::TYPE_NO_CONFIDENCE_SURVIVED,
            self::NO_CONFIDENCE_SURVIVED,
            'Survived vote of no confidence',
            ['vote_id' => $vote->id]
        );
    }

    /**
     * Handle church support or opposition.
     */
    public function handleChurchRelations(PlayerRole|PlayerTitle $holder, Religion $religion, bool $isSupport): LegitimacyEvent
    {
        if ($isSupport) {
            return $this->applyChange(
                $holder,
                LegitimacyEvent::TYPE_CHURCH_SUPPORT,
                self::CHURCH_SUPPORT,
                "Received support from {$religion->name}",
                ['religion_id' => $religion->id]
            );
        }

        return $this->applyChange(
            $holder,
            LegitimacyEvent::TYPE_CHURCH_OPPOSITION,
            self::CHURCH_OPPOSITION,
            "Opposed by {$religion->name}",
            ['religion_id' => $religion->id]
        );
    }

    /**
     * Handle excommunication.
     */
    public function handleExcommunication(PlayerRole|PlayerTitle $holder, Religion $religion): LegitimacyEvent
    {
        return $this->applyChange(
            $holder,
            LegitimacyEvent::TYPE_EXCOMMUNICATION,
            self::EXCOMMUNICATION,
            "Excommunicated by {$religion->name}",
            ['religion_id' => $religion->id]
        );
    }

    /**
     * Handle scandal.
     */
    public function handleScandal(PlayerRole|PlayerTitle $holder, string $severity, string $description): LegitimacyEvent
    {
        $change = match ($severity) {
            'minor' => self::SCANDAL_MINOR,
            'major' => self::SCANDAL_MAJOR,
            'severe' => self::SCANDAL_SEVERE,
            default => self::SCANDAL_MINOR,
        };

        return $this->applyChange(
            $holder,
            LegitimacyEvent::TYPE_SCANDAL,
            $change,
            $description,
            ['severity' => $severity]
        );
    }

    /**
     * Handle criminal conviction.
     */
    public function handleCrimeConviction(PlayerRole|PlayerTitle $holder, Trial $trial): LegitimacyEvent
    {
        return $this->applyChange(
            $holder,
            LegitimacyEvent::TYPE_CRIME_CONVICTED,
            self::CRIME_CONVICTED,
            'Convicted of a crime while in office',
            ['trial_id' => $trial->id]
        );
    }

    /**
     * Handle war outcome (future use).
     */
    public function handleWarOutcome(PlayerRole|PlayerTitle $holder, bool $won, ?string $warName = null): LegitimacyEvent
    {
        if ($won) {
            return $this->applyChange(
                $holder,
                LegitimacyEvent::TYPE_WAR_WON,
                self::WAR_WON,
                $warName ? "Won the {$warName}" : 'Won a war'
            );
        }

        return $this->applyChange(
            $holder,
            LegitimacyEvent::TYPE_WAR_LOST,
            self::WAR_LOST,
            $warName ? "Lost the {$warName}" : 'Lost a war'
        );
    }

    /**
     * Handle policy success/failure.
     */
    public function handlePolicyOutcome(PlayerRole|PlayerTitle $holder, bool $success, string $policyDescription): LegitimacyEvent
    {
        if ($success) {
            return $this->applyChange(
                $holder,
                LegitimacyEvent::TYPE_POLICY_SUCCESS,
                self::POLICY_SUCCESS,
                "Successful policy: {$policyDescription}"
            );
        }

        return $this->applyChange(
            $holder,
            LegitimacyEvent::TYPE_POLICY_FAILURE,
            self::POLICY_FAILURE,
            "Failed policy: {$policyDescription}"
        );
    }

    /**
     * Get legitimacy status description.
     */
    public function getLegitimacyStatus(int $legitimacy): string
    {
        return match (true) {
            $legitimacy >= self::THRESHOLD_VERY_HIGH => 'Beloved',
            $legitimacy >= self::THRESHOLD_HIGH => 'Respected',
            $legitimacy >= self::THRESHOLD_STABLE => 'Accepted',
            $legitimacy >= self::THRESHOLD_LOW => 'Questioned',
            $legitimacy >= self::THRESHOLD_CRITICAL => 'Unpopular',
            default => 'Despised',
        };
    }

    /**
     * Get legitimacy effects description.
     */
    public function getLegitimacyEffects(int $legitimacy): array
    {
        $effects = [];

        if ($legitimacy >= self::THRESHOLD_VERY_HIGH) {
            $effects[] = '+10% tax collection efficiency';
            $effects[] = 'No confidence votes very unlikely to succeed';
            $effects[] = '+10% army morale';
        } elseif ($legitimacy >= self::THRESHOLD_HIGH) {
            $effects[] = '+5% tax collection efficiency';
            $effects[] = 'No confidence votes unlikely to succeed';
        } elseif ($legitimacy >= self::THRESHOLD_STABLE) {
            $effects[] = 'Normal governance';
        } elseif ($legitimacy >= self::THRESHOLD_LOW) {
            $effects[] = '-5% tax collection efficiency';
            $effects[] = 'No confidence votes more likely';
        } elseif ($legitimacy >= self::THRESHOLD_CRITICAL) {
            $effects[] = '-10% tax collection efficiency';
            $effects[] = 'No confidence votes very likely to succeed';
            $effects[] = 'Risk of rebellion';
        } else {
            $effects[] = '-20% tax collection efficiency';
            $effects[] = 'Any no confidence vote will succeed';
            $effects[] = 'Rebellion imminent';
            $effects[] = 'Army desertion possible';
        }

        return $effects;
    }

    /**
     * Calculate tax efficiency modifier based on legitimacy.
     */
    public function getTaxEfficiencyModifier(int $legitimacy): float
    {
        return match (true) {
            $legitimacy >= self::THRESHOLD_VERY_HIGH => 1.10,
            $legitimacy >= self::THRESHOLD_HIGH => 1.05,
            $legitimacy >= self::THRESHOLD_STABLE => 1.00,
            $legitimacy >= self::THRESHOLD_LOW => 0.95,
            $legitimacy >= self::THRESHOLD_CRITICAL => 0.90,
            default => 0.80,
        };
    }

    /**
     * Calculate no-confidence success modifier based on legitimacy.
     */
    public function getNoConfidenceModifier(int $legitimacy): float
    {
        // Returns a modifier for required votes (lower = easier to remove)
        return match (true) {
            $legitimacy >= self::THRESHOLD_VERY_HIGH => 1.5,   // Need 75% instead of 50%
            $legitimacy >= self::THRESHOLD_HIGH => 1.25,       // Need ~63%
            $legitimacy >= self::THRESHOLD_STABLE => 1.0,      // Normal 50%
            $legitimacy >= self::THRESHOLD_LOW => 0.8,         // Only need 40%
            $legitimacy >= self::THRESHOLD_CRITICAL => 0.6,    // Only need 30%
            default => 0.0,                                     // Any vote removes
        };
    }
}
