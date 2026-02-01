<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\EnnoblementRequest;
use App\Models\Kingdom;
use App\Models\ManumissionRequest;
use App\Models\PlayerRole;
use App\Models\SocialClassHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SocialClassService
{
    public function __construct(
        protected BankService $bankService
    ) {}

    /**
     * Change a user's social class.
     */
    public function changeClass(User $user, string $newClass, string $reason, ?User $grantedBy = null): bool
    {
        $oldClass = $user->social_class;

        if ($oldClass === $newClass) {
            return false;
        }

        return DB::transaction(function () use ($user, $oldClass, $newClass, $reason, $grantedBy) {
            // Record the change
            SocialClassHistory::create([
                'user_id' => $user->id,
                'old_class' => $oldClass,
                'new_class' => $newClass,
                'reason' => $reason,
                'granted_by_user_id' => $grantedBy?->id,
            ]);

            // Update the user
            $user->update([
                'social_class' => $newClass,
                // Clear serf-specific fields if becoming free
                'bound_to_barony_id' => $newClass === User::CLASS_SERF ? $user->bound_to_barony_id : null,
                'labor_days_owed' => $newClass === User::CLASS_SERF ? $user->labor_days_owed : 0,
                'labor_days_completed' => $newClass === User::CLASS_SERF ? $user->labor_days_completed : 0,
            ]);

            return true;
        });
    }

    /**
     * Enslave a user as a serf (bind to a barony).
     */
    public function enserfUser(User $user, Barony $barony, string $reason, ?User $grantedBy = null): bool
    {
        return DB::transaction(function () use ($user, $barony, $reason, $grantedBy) {
            SocialClassHistory::create([
                'user_id' => $user->id,
                'old_class' => $user->social_class,
                'new_class' => User::CLASS_SERF,
                'reason' => $reason,
                'granted_by_user_id' => $grantedBy?->id,
            ]);

            $user->update([
                'social_class' => User::CLASS_SERF,
                'bound_to_barony_id' => $barony->id,
                'labor_days_owed' => 10, // Owe 10 labor days per season
                'labor_days_completed' => 0,
            ]);

            return true;
        });
    }

    /**
     * Request manumission (freedom for a serf).
     */
    public function requestManumission(
        User $serf,
        string $requestType,
        ?string $reason = null,
        int $goldOffered = 0
    ): ManumissionRequest|string {
        if (! $serf->isSerf()) {
            return 'You are not a serf.';
        }

        if (! $serf->bound_to_barony_id) {
            return 'You are not bound to a barony.';
        }

        // Check for existing pending request
        $existingRequest = ManumissionRequest::where('serf_id', $serf->id)
            ->where('status', ManumissionRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return 'You already have a pending manumission request.';
        }

        // Get the baron
        $barony = Barony::find($serf->bound_to_barony_id);
        $baron = $this->getBaronForBarony($barony);

        if (! $baron) {
            return 'No baron found for your barony.';
        }

        // For purchase requests, validate gold
        if ($requestType === ManumissionRequest::TYPE_PURCHASE) {
            if ($goldOffered < ManumissionRequest::PURCHASE_COST) {
                return 'Insufficient gold offered. Freedom costs '.number_format(ManumissionRequest::PURCHASE_COST).' gold.';
            }
            if ($serf->gold < $goldOffered) {
                return 'You do not have enough gold.';
            }
        }

        return ManumissionRequest::create([
            'serf_id' => $serf->id,
            'baron_id' => $baron->id,
            'barony_id' => $barony->id,
            'request_type' => $requestType,
            'gold_offered' => $goldOffered,
            'reason' => $reason,
            'status' => ManumissionRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Approve a manumission request.
     */
    public function approveManumission(ManumissionRequest $request, User $baron, ?string $responseMessage = null): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        if ($request->baron_id !== $baron->id) {
            return false;
        }

        return DB::transaction(function () use ($request, $baron, $responseMessage) {
            $serf = $request->serf;

            // For purchase requests, transfer gold
            if ($request->request_type === ManumissionRequest::TYPE_PURCHASE) {
                if ($serf->gold < $request->gold_offered) {
                    return false;
                }
                $serf->decrement('gold', $request->gold_offered);
                $baron->increment('gold', $request->gold_offered);
            }

            // Grant freedom
            $this->changeClass($serf, User::CLASS_FREEMAN, 'manumission_'.$request->request_type, $baron);

            // Update request
            $request->update([
                'status' => ManumissionRequest::STATUS_APPROVED,
                'response_message' => $responseMessage,
                'responded_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Deny a manumission request.
     */
    public function denyManumission(ManumissionRequest $request, User $baron, ?string $responseMessage = null): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        if ($request->baron_id !== $baron->id) {
            return false;
        }

        $request->update([
            'status' => ManumissionRequest::STATUS_DENIED,
            'response_message' => $responseMessage,
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Request ennoblement (nobility for a freeman).
     */
    public function requestEnnoblement(
        User $requester,
        Kingdom $kingdom,
        string $requestType,
        ?string $reason = null,
        int $goldOffered = 0,
        ?User $spouse = null
    ): EnnoblementRequest|string {
        if ($requester->isSerf()) {
            return 'Serfs cannot request ennoblement.';
        }

        if ($requester->isNoble()) {
            return 'You are already a noble.';
        }

        // Check for existing pending request
        $existingRequest = EnnoblementRequest::where('requester_id', $requester->id)
            ->where('status', EnnoblementRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return 'You already have a pending ennoblement request.';
        }

        // Get the king
        $king = $this->getKingForKingdom($kingdom);

        if (! $king) {
            return 'No king found for this kingdom.';
        }

        // For purchase requests, validate gold
        if ($requestType === EnnoblementRequest::TYPE_PURCHASE) {
            if ($goldOffered < EnnoblementRequest::PURCHASE_COST) {
                return 'Insufficient gold offered. Nobility costs '.number_format(EnnoblementRequest::PURCHASE_COST).' gold.';
            }
            if ($requester->gold < $goldOffered) {
                return 'You do not have enough gold.';
            }
        }

        // For marriage requests, validate spouse
        if ($requestType === EnnoblementRequest::TYPE_MARRIAGE) {
            if (! $spouse || ! $spouse->isNoble()) {
                return 'Marriage requests require a noble spouse.';
            }
        }

        return EnnoblementRequest::create([
            'requester_id' => $requester->id,
            'king_id' => $king->id,
            'kingdom_id' => $kingdom->id,
            'request_type' => $requestType,
            'gold_offered' => $goldOffered,
            'spouse_id' => $spouse?->id,
            'reason' => $reason,
            'status' => EnnoblementRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Approve an ennoblement request.
     */
    public function approveEnnoblement(
        EnnoblementRequest $request,
        User $king,
        string $titleGranted = 'Knight',
        ?string $responseMessage = null
    ): bool {
        if (! $request->isPending()) {
            return false;
        }

        if ($request->king_id !== $king->id) {
            return false;
        }

        return DB::transaction(function () use ($request, $king, $titleGranted, $responseMessage) {
            $requester = $request->requester;

            // For purchase requests, transfer gold
            if ($request->request_type === EnnoblementRequest::TYPE_PURCHASE) {
                if ($requester->gold < $request->gold_offered) {
                    return false;
                }
                $requester->decrement('gold', $request->gold_offered);
                // Gold goes to kingdom treasury
                $treasury = \App\Models\LocationTreasury::getOrCreate('kingdom', $request->kingdom_id);
                $treasury->deposit(
                    $request->gold_offered,
                    'ennoblement_payment',
                    "Title purchase by {$requester->username}",
                    $requester->id
                );
            }

            // Grant nobility
            $this->changeClass($requester, User::CLASS_NOBLE, 'ennoblement_'.$request->request_type, $king);

            // Update user's title
            $requester->update([
                'primary_title' => strtolower($titleGranted),
                'title_tier' => 2, // Knight tier
            ]);

            // Update request
            $request->update([
                'status' => EnnoblementRequest::STATUS_APPROVED,
                'response_message' => $responseMessage,
                'responded_at' => now(),
                'title_granted' => $titleGranted,
            ]);

            return true;
        });
    }

    /**
     * Deny an ennoblement request.
     */
    public function denyEnnoblement(EnnoblementRequest $request, User $king, ?string $responseMessage = null): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        if ($request->king_id !== $king->id) {
            return false;
        }

        $request->update([
            'status' => EnnoblementRequest::STATUS_DENIED,
            'response_message' => $responseMessage,
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Become a burgher (requires town residence and guild membership).
     */
    public function becomeBurgher(User $user): bool|string
    {
        if ($user->isSerf()) {
            return 'Serfs cannot become burghers.';
        }

        if ($user->isBurgher() || $user->isNoble()) {
            return 'You are already a burgher or higher class.';
        }

        // Check if user lives in a town
        if (! $user->livesInTown()) {
            return 'You must live in a town to become a burgher.';
        }

        // Check for guild membership (optional for now, can be enforced later)
        // For now, we'll allow becoming burgher if living in a town or city area

        $this->changeClass($user, User::CLASS_BURGHER, 'became_burgher');

        return true;
    }

    /**
     * Join the clergy.
     */
    public function joinClergy(User $user): bool|string
    {
        if ($user->isSerf()) {
            return 'Serfs cannot join the clergy.';
        }

        if ($user->isClergy()) {
            return 'You are already clergy.';
        }

        // Check if user is a member of a religion and has a priest role
        $hasPriestRole = PlayerRole::where('user_id', $user->id)
            ->whereHas('role', function ($q) {
                $q->whereIn('slug', ['priest', 'high_priest', 'prophet']);
            })
            ->where('status', PlayerRole::STATUS_ACTIVE)
            ->exists();

        if (! $hasPriestRole) {
            return 'You must hold a religious office to become clergy.';
        }

        $this->changeClass($user, User::CLASS_CLERGY, 'joined_clergy');

        return true;
    }

    /**
     * Process weekly labor obligations for serfs.
     */
    public function processWeeklyObligations(): array
    {
        $results = ['processed' => 0, 'punished' => 0];

        $serfs = User::where('social_class', User::CLASS_SERF)
            ->whereNotNull('bound_to_barony_id')
            ->get();

        foreach ($serfs as $serf) {
            // Check if obligations are complete
            if (! $serf->hasCompletedLaborObligations()) {
                // Serf has not completed obligations - could apply penalties
                // For now, just track it
                $results['punished']++;
            }

            $results['processed']++;
        }

        return $results;
    }

    /**
     * Reset labor obligations for a new season.
     */
    public function resetSeasonalObligations(): int
    {
        return User::where('social_class', User::CLASS_SERF)
            ->whereNotNull('bound_to_barony_id')
            ->update([
                'labor_days_owed' => 10, // 10 days per season
                'labor_days_completed' => 0,
                'last_obligation_check' => now(),
            ]);
    }

    /**
     * Get the baron for a barony.
     */
    protected function getBaronForBarony(Barony $barony): ?User
    {
        $baronRole = PlayerRole::where('location_type', 'barony')
            ->where('location_id', $barony->id)
            ->whereHas('role', fn ($q) => $q->where('slug', 'baron'))
            ->where('status', PlayerRole::STATUS_ACTIVE)
            ->first();

        return $baronRole?->user;
    }

    /**
     * Get the king for a kingdom.
     */
    protected function getKingForKingdom(Kingdom $kingdom): ?User
    {
        $kingRole = PlayerRole::where('location_type', 'kingdom')
            ->where('location_id', $kingdom->id)
            ->whereHas('role', fn ($q) => $q->where('slug', 'king'))
            ->where('status', PlayerRole::STATUS_ACTIVE)
            ->first();

        return $kingRole?->user;
    }

    /**
     * Get social class statistics.
     */
    public function getClassStatistics(): array
    {
        return User::selectRaw('social_class, count(*) as count')
            ->groupBy('social_class')
            ->pluck('count', 'social_class')
            ->toArray();
    }

    /**
     * Check if a user can perform an action based on their class.
     */
    public function canPerformAction(User $user, string $action): bool
    {
        return match ($action) {
            'vote' => $user->canVote(),
            'join_guild' => $user->canJoinGuild(),
            'own_business' => $user->canOwnBusiness(),
            'own_property' => $user->canOwnProperty(),
            'hold_high_office' => $user->canHoldHighOffice(),
            'travel_freely' => $user->canFreelyTravel(),
            default => true,
        };
    }
}
