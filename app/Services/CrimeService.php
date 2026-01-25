<?php

namespace App\Services;

use App\Models\Accusation;
use App\Models\Bounty;
use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\CrimeWitness;
use App\Models\Exile;
use App\Models\JailInmate;
use App\Models\LocationTreasury;
use App\Models\Outlaw;
use App\Models\PlayerRole;
use App\Models\Punishment;
use App\Models\Trial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CrimeService
{
    /**
     * Record a crime being committed.
     */
    public function commitCrime(
        User $perpetrator,
        string $crimeTypeSlug,
        string $locationType,
        int $locationId,
        ?User $victim = null,
        ?string $description = null,
        array $evidence = [],
        array $witnessIds = []
    ): Crime {
        $crimeType = CrimeType::where('slug', $crimeTypeSlug)->firstOrFail();

        return DB::transaction(function () use ($crimeType, $perpetrator, $victim, $locationType, $locationId, $description, $evidence, $witnessIds) {
            $crime = Crime::create([
                'crime_type_id' => $crimeType->id,
                'perpetrator_id' => $perpetrator->id,
                'victim_id' => $victim?->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'description' => $description,
                'evidence' => $evidence,
                'status' => count($witnessIds) > 0 ? Crime::STATUS_REPORTED : Crime::STATUS_UNDETECTED,
                'committed_at' => now(),
                'detected_at' => count($witnessIds) > 0 ? now() : null,
            ]);

            // Add witnesses
            foreach ($witnessIds as $witnessId) {
                CrimeWitness::create([
                    'crime_id' => $crime->id,
                    'witness_id' => $witnessId,
                    'is_npc' => false,
                ]);
            }

            return $crime;
        });
    }

    /**
     * File an accusation against another player.
     */
    public function fileAccusation(
        User $accuser,
        User $accused,
        string $crimeTypeSlug,
        string $locationType,
        int $locationId,
        string $accusationText,
        array $evidenceProvided = [],
        ?Crime $linkedCrime = null
    ): Accusation|string {
        $crimeType = CrimeType::where('slug', $crimeTypeSlug)->first();

        if (!$crimeType) {
            return 'Invalid crime type.';
        }

        if ($accuser->id === $accused->id) {
            return 'You cannot accuse yourself.';
        }

        // Check if there's already a pending accusation for this
        $existingAccusation = Accusation::where('accuser_id', $accuser->id)
            ->where('accused_id', $accused->id)
            ->where('crime_type_id', $crimeType->id)
            ->pending()
            ->first();

        if ($existingAccusation) {
            return 'You already have a pending accusation against this person for this crime.';
        }

        return Accusation::create([
            'crime_id' => $linkedCrime?->id,
            'accuser_id' => $accuser->id,
            'accused_id' => $accused->id,
            'crime_type_id' => $crimeType->id,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'accusation_text' => $accusationText,
            'evidence_provided' => $evidenceProvided,
            'status' => Accusation::STATUS_PENDING,
        ]);
    }

    /**
     * Review an accusation (by an authority figure).
     */
    public function reviewAccusation(
        Accusation $accusation,
        User $reviewer,
        string $decision,
        ?string $notes = null
    ): bool|string {
        if (!$accusation->isPending()) {
            return 'This accusation has already been reviewed.';
        }

        // Check if reviewer has authority
        if (!$this->hasJudicialAuthority($reviewer, $accusation->location_type, $accusation->location_id)) {
            return 'You do not have authority to review accusations here.';
        }

        return DB::transaction(function () use ($accusation, $reviewer, $decision, $notes) {
            switch ($decision) {
                case 'accept':
                    $accusation->accept($reviewer, $notes);

                    // Create a crime record if one doesn't exist
                    if (!$accusation->crime_id) {
                        $crime = Crime::create([
                            'crime_type_id' => $accusation->crime_type_id,
                            'perpetrator_id' => $accusation->accused_id,
                            'victim_id' => $accusation->accuser_id,
                            'location_type' => $accusation->location_type,
                            'location_id' => $accusation->location_id,
                            'description' => $accusation->accusation_text,
                            'evidence' => $accusation->evidence_provided,
                            'status' => Crime::STATUS_TRIAL_PENDING,
                            'committed_at' => $accusation->created_at,
                            'detected_at' => now(),
                        ]);
                        $accusation->update(['crime_id' => $crime->id]);
                    } else {
                        $accusation->crime->update(['status' => Crime::STATUS_TRIAL_PENDING]);
                    }

                    // Schedule a trial
                    $this->scheduleTrial($accusation);
                    break;

                case 'reject':
                    $accusation->reject($reviewer, $notes);
                    break;

                case 'false':
                    $accusation->markAsFalse($reviewer, $notes);
                    // The accuser committed false accusation - could trigger punishment
                    break;
            }

            return true;
        });
    }

    /**
     * Schedule a trial for an accusation.
     */
    public function scheduleTrial(Accusation $accusation): Trial
    {
        $crimeType = $accusation->crimeType;

        // Determine the judge based on court level
        $judgeId = $this->findJudge(
            $crimeType->court_level,
            $accusation->location_type,
            $accusation->location_id
        );

        return Trial::create([
            'crime_id' => $accusation->crime_id,
            'accusation_id' => $accusation->id,
            'defendant_id' => $accusation->accused_id,
            'judge_id' => $judgeId,
            'court_level' => $crimeType->court_level,
            'location_type' => $accusation->location_type,
            'location_id' => $accusation->location_id,
            'status' => Trial::STATUS_SCHEDULED,
            'scheduled_at' => now()->addDay(), // Trial scheduled for next day
        ]);
    }

    /**
     * Render a verdict in a trial.
     */
    public function renderVerdict(
        Trial $trial,
        User $judge,
        string $verdict,
        string $reasoning,
        array $punishments = []
    ): bool|string {
        if ($trial->judge_id !== $judge->id) {
            return 'You are not the judge in this trial.';
        }

        if ($trial->isConcluded()) {
            return 'This trial has already concluded.';
        }

        return DB::transaction(function () use ($trial, $verdict, $reasoning, $punishments, $judge) {
            $trial->renderVerdict($verdict, $reasoning);

            // Update the crime status
            $trial->crime->update(['status' => Crime::STATUS_RESOLVED]);

            // Apply punishments if guilty
            if ($verdict === Trial::VERDICT_GUILTY) {
                foreach ($punishments as $punishmentData) {
                    $this->applyPunishment($trial, $judge, $punishmentData);
                }
            }

            return true;
        });
    }

    /**
     * Apply a punishment to a criminal.
     */
    public function applyPunishment(Trial $trial, User $issuedBy, array $data): Punishment
    {
        $punishment = Punishment::create([
            'trial_id' => $trial->id,
            'criminal_id' => $trial->defendant_id,
            'issued_by' => $issuedBy->id,
            'type' => $data['type'],
            'fine_amount' => $data['fine_amount'] ?? null,
            'jail_days' => $data['jail_days'] ?? null,
            'exile_from_type' => $data['exile_from_type'] ?? null,
            'exile_from_id' => $data['exile_from_id'] ?? null,
            'community_service_hours' => $data['community_service_hours'] ?? null,
            'status' => Punishment::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        // Execute the punishment
        $this->executePunishment($punishment);

        return $punishment;
    }

    /**
     * Execute a punishment.
     */
    public function executePunishment(Punishment $punishment): void
    {
        $criminal = $punishment->criminal;

        switch ($punishment->type) {
            case Punishment::TYPE_FINE:
                $this->executeFine($punishment, $criminal);
                break;

            case Punishment::TYPE_JAIL:
                $this->executeJail($punishment, $criminal);
                break;

            case Punishment::TYPE_EXILE:
                $this->executeExile($punishment, $criminal);
                break;

            case Punishment::TYPE_OUTLAWRY:
                $this->executeOutlawry($punishment, $criminal);
                break;

            case Punishment::TYPE_EXECUTION:
                $this->executeExecution($punishment, $criminal);
                break;
        }
    }

    protected function executeFine(Punishment $punishment, User $criminal): void
    {
        $amount = $punishment->fine_amount;

        if ($criminal->gold >= $amount) {
            $criminal->decrement('gold', $amount);

            // Add to location treasury
            $trial = $punishment->trial;
            if ($trial) {
                $treasury = LocationTreasury::getOrCreate($trial->location_type, $trial->location_id);
                $treasury->deposit($amount, 'fine_payment', "Fine from {$criminal->username}", $criminal->id);
            }

            $punishment->activate();
            $punishment->complete();
        } else {
            // Can't pay - convert to jail time
            $jailDays = ceil(($amount - $criminal->gold) / 100); // 100 gold = 1 day
            $criminal->update(['gold' => 0]);

            $punishment->update([
                'type' => Punishment::TYPE_JAIL,
                'jail_days' => $jailDays,
                'notes' => "Converted from fine of {$amount} gold (unable to pay)",
            ]);

            $this->executeJail($punishment, $criminal);
        }
    }

    protected function executeJail(Punishment $punishment, User $criminal): void
    {
        $punishment->activate();
        $punishment->update([
            'ends_at' => now()->addDays($punishment->jail_days),
        ]);

        // Determine jail location
        $trial = $punishment->trial;
        $jailLocationType = $trial?->location_type ?? 'village';
        $jailLocationId = $trial?->location_id ?? $criminal->current_village_id;

        JailInmate::create([
            'prisoner_id' => $criminal->id,
            'punishment_id' => $punishment->id,
            'jail_location_type' => $jailLocationType,
            'jail_location_id' => $jailLocationId,
            'jailed_at' => now(),
            'release_at' => now()->addDays($punishment->jail_days),
        ]);
    }

    protected function executeExile(Punishment $punishment, User $criminal): void
    {
        $punishment->activate();

        Exile::create([
            'user_id' => $criminal->id,
            'punishment_id' => $punishment->id,
            'exiled_from_type' => $punishment->exile_from_type,
            'exiled_from_id' => $punishment->exile_from_id,
            'reason' => $punishment->notes ?? 'Sentenced by court',
            'status' => Exile::STATUS_ACTIVE,
            'exiled_at' => now(),
            'expires_at' => $punishment->ends_at,
        ]);

        // Force the player to leave if they're in the exiled location
        // This would be handled by the travel system checking exile status
    }

    protected function executeOutlawry(Punishment $punishment, User $criminal): void
    {
        $punishment->activate();

        $trial = $punishment->trial;
        $declaredByType = $trial?->location_type ?? 'kingdom';
        $declaredById = $trial?->location_id ?? 1;

        // Upgrade to kingdom level for outlawry
        if ($declaredByType === 'village') {
            $village = \App\Models\Village::find($declaredById);
            if ($village && $village->barony) {
                $declaredByType = 'barony';
                $declaredById = $village->barony_id;
            }
        }

        Outlaw::create([
            'user_id' => $criminal->id,
            'punishment_id' => $punishment->id,
            'declared_by_type' => $declaredByType,
            'declared_by_id' => $declaredById,
            'reason' => $punishment->notes ?? 'Declared outlaw by court',
            'status' => Outlaw::STATUS_ACTIVE,
            'declared_at' => now(),
            'expires_at' => $punishment->ends_at,
        ]);

        // Create an automatic bounty
        Bounty::create([
            'target_id' => $criminal->id,
            'crime_id' => $trial?->crime_id,
            'poster_type' => $declaredByType,
            'poster_location_id' => $declaredById,
            'reward_amount' => 1000, // Base bounty
            'capture_type' => Bounty::CAPTURE_DEAD_OR_ALIVE,
            'reason' => 'Wanted outlaw',
            'status' => Bounty::STATUS_ACTIVE,
        ]);
    }

    protected function executeExecution(Punishment $punishment, User $criminal): void
    {
        $punishment->activate();

        // Kill the character
        $criminal->update([
            'hp' => 0,
            'is_dead' => true,
            'died_at' => now(),
            'death_reason' => 'Executed for crimes',
        ]);

        $punishment->complete();
    }

    /**
     * Post a bounty on a player.
     */
    public function postBounty(
        User $target,
        User|null $postedBy,
        int $rewardAmount,
        string $reason,
        string $captureType = Bounty::CAPTURE_DEAD_OR_ALIVE,
        ?Crime $crime = null,
        string $posterType = Bounty::POSTER_PLAYER,
        ?int $posterLocationId = null
    ): Bounty|string {
        if ($posterType === Bounty::POSTER_PLAYER) {
            if (!$postedBy) {
                return 'A player must be specified when posting a personal bounty.';
            }
            if ($postedBy->gold < $rewardAmount) {
                return 'You do not have enough gold to post this bounty.';
            }
            // Deduct gold upfront
            $postedBy->decrement('gold', $rewardAmount);
        }

        return Bounty::create([
            'target_id' => $target->id,
            'posted_by' => $postedBy?->id,
            'crime_id' => $crime?->id,
            'poster_type' => $posterType,
            'poster_location_id' => $posterLocationId,
            'reward_amount' => $rewardAmount,
            'capture_type' => $captureType,
            'reason' => $reason,
            'status' => Bounty::STATUS_ACTIVE,
            'expires_at' => now()->addDays(30), // Bounties expire after 30 days
        ]);
    }

    /**
     * Check if a user has judicial authority at a location.
     */
    public function hasJudicialAuthority(User $user, string $locationType, int $locationId): bool
    {
        // Check for relevant roles
        $relevantSlugs = match ($locationType) {
            'village' => ['elder', 'village_chief'],
            'barony' => ['baron', 'magistrate'],
            'kingdom' => ['king', 'high_judge'],
            default => [],
        };

        return PlayerRole::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->whereHas('role', fn($q) => $q->whereIn('slug', $relevantSlugs))
            ->where('status', PlayerRole::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Find a judge for a trial.
     */
    protected function findJudge(string $courtLevel, string $locationType, int $locationId): ?int
    {
        $judgeSlugs = match ($courtLevel) {
            CrimeType::COURT_VILLAGE => ['elder', 'village_chief'],
            CrimeType::COURT_BARONY => ['baron', 'magistrate'],
            CrimeType::COURT_KINGDOM => ['king', 'high_judge'],
            CrimeType::COURT_CHURCH => ['high_priest'],
            default => [],
        };

        $judgeRole = PlayerRole::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->whereHas('role', fn($q) => $q->whereIn('slug', $judgeSlugs))
            ->where('status', PlayerRole::STATUS_ACTIVE)
            ->first();

        return $judgeRole?->user_id;
    }

    /**
     * Check if a user is currently jailed.
     */
    public function isJailed(User $user): bool
    {
        return JailInmate::where('prisoner_id', $user->id)
            ->currentlyServing()
            ->exists();
    }

    /**
     * Check if a user is an outlaw.
     */
    public function isOutlaw(User $user): bool
    {
        return Outlaw::where('user_id', $user->id)
            ->active()
            ->exists();
    }

    /**
     * Check if a user is exiled from a location.
     */
    public function isExiledFrom(User $user, string $locationType, int $locationId): bool
    {
        $exiles = Exile::where('user_id', $user->id)->active()->get();

        foreach ($exiles as $exile) {
            if ($exile->appliesToLocation($locationType, $locationId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process jail releases (called by scheduler).
     */
    public function processJailReleases(): int
    {
        $count = 0;

        $dueForRelease = JailInmate::dueForRelease()->get();

        foreach ($dueForRelease as $inmate) {
            $inmate->release();
            $count++;
        }

        return $count;
    }

    /**
     * Get active bounties for a target.
     */
    public function getActiveBounties(User $target): \Illuminate\Database\Eloquent\Collection
    {
        return Bounty::forTarget($target->id)->active()->get();
    }

    /**
     * Get pending accusations at a location.
     */
    public function getPendingAccusations(string $locationType, int $locationId): \Illuminate\Database\Eloquent\Collection
    {
        return Accusation::atLocation($locationType, $locationId)
            ->pending()
            ->with(['accuser', 'accused', 'crimeType'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get pending trials for a judge.
     */
    public function getPendingTrials(User $judge): \Illuminate\Database\Eloquent\Collection
    {
        return Trial::forJudge($judge->id)
            ->pending()
            ->with(['defendant', 'crime.crimeType', 'accusation'])
            ->orderBy('scheduled_at')
            ->get();
    }
}
