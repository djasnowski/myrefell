<?php

namespace App\Services;

use App\Models\PlayerTitle;
use App\Models\TitlePetition;
use App\Models\TitleType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TitleService
{
    /**
     * Grant a title to a user.
     */
    public function grantTitle(
        User $recipient,
        TitleType $titleType,
        User $grantedBy,
        ?string $domainType = null,
        ?int $domainId = null,
        string $acquisitionMethod = 'appointment'
    ): array {
        // Check if granter has authority to grant this title
        if (! $this->canGrantTitle($grantedBy, $titleType)) {
            return [
                'success' => false,
                'message' => "You do not have the authority to grant the title of {$titleType->name}.",
            ];
        }

        // Check domain limits
        if ($titleType->is_landed && $domainType && $domainId) {
            if ($titleType->isLimitReachedInDomain($domainType, $domainId)) {
                $current = $titleType->countInDomain($domainType, $domainId);

                return [
                    'success' => false,
                    'message' => "The limit of {$titleType->limit_per_domain} {$titleType->name}(s) has been reached for this {$domainType}. Current: {$current}.",
                ];
            }
        } elseif ($titleType->limit_per_domain && $domainType && $domainId) {
            // Honorary titles with domain limits
            if ($titleType->isLimitReachedInDomain($domainType, $domainId)) {
                return [
                    'success' => false,
                    'message' => "No more {$titleType->name} positions available in this {$domainType}.",
                ];
            }
        }

        // Check superior limits (e.g., Knights can only have 2 Squires)
        if ($titleType->limit_per_superior) {
            if ($titleType->isLimitReachedForSuperior($grantedBy->id)) {
                return [
                    'success' => false,
                    'message' => "You have already granted the maximum number of {$titleType->name} titles ({$titleType->limit_per_superior}).",
                ];
            }
        }

        // Check if recipient already has this title
        $existingTitle = PlayerTitle::where('user_id', $recipient->id)
            ->where('title_type_id', $titleType->id)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->first();

        if ($existingTitle) {
            return [
                'success' => false,
                'message' => "{$recipient->username} already holds the title of {$titleType->name}.",
            ];
        }

        return DB::transaction(function () use ($recipient, $titleType, $grantedBy, $domainType, $domainId, $acquisitionMethod) {
            $playerTitle = PlayerTitle::create([
                'user_id' => $recipient->id,
                'title_type_id' => $titleType->id,
                'title' => $titleType->slug,
                'tier' => $titleType->tier,
                'domain_type' => $domainType ?? $titleType->domain_type,
                'domain_id' => $domainId,
                'acquisition_method' => $acquisitionMethod,
                'granted_by_user_id' => $grantedBy->id,
                'is_active' => true,
                'granted_at' => now(),
                'legitimacy' => 100,
            ]);

            // Update user's primary title if this is higher tier
            if ($titleType->tier > $recipient->title_tier) {
                $recipient->update([
                    'primary_title' => $titleType->slug,
                    'title_tier' => $titleType->tier,
                ]);
            }

            return [
                'success' => true,
                'message' => "{$recipient->username} has been granted the title of {$titleType->name}.",
                'title' => $playerTitle,
            ];
        });
    }

    /**
     * Revoke a title from a user.
     */
    public function revokeTitle(
        PlayerTitle $playerTitle,
        User $revokedBy,
        ?string $reason = null
    ): array {
        $titleType = $playerTitle->titleType;
        $recipient = $playerTitle->user;

        // Check if revoker has authority
        if (! $this->canRevokeTitle($revokedBy, $playerTitle)) {
            return [
                'success' => false,
                'message' => 'You do not have the authority to revoke this title.',
            ];
        }

        return DB::transaction(function () use ($playerTitle, $recipient, $titleType, $reason) {
            $playerTitle->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

            // Update user's primary title to their next highest
            $this->updatePrimaryTitle($recipient);

            return [
                'success' => true,
                'message' => "The title of {$titleType->name} has been revoked from {$recipient->username}.".($reason ? " Reason: {$reason}" : ''),
            ];
        });
    }

    /**
     * Check if a user can grant a specific title type.
     */
    public function canGrantTitle(User $granter, TitleType $titleType): bool
    {
        if (empty($titleType->granted_by)) {
            return false; // No one can grant this (e.g., Emperor - conquest only)
        }

        $granterTitle = $granter->highestTitle();
        if (! $granterTitle) {
            return false;
        }

        $granterTitleSlug = $granterTitle->titleType?->slug ?? $granterTitle->title;

        return $titleType->canBeGrantedBy($granterTitleSlug);
    }

    /**
     * Check if a user can revoke a specific title.
     */
    public function canRevokeTitle(User $revoker, PlayerTitle $playerTitle): bool
    {
        // The granter can revoke
        if ($playerTitle->granted_by_user_id === $revoker->id) {
            return true;
        }

        // Higher tier title holders can revoke
        $revokerTitle = $revoker->highestTitle();
        if ($revokerTitle && $revokerTitle->tier > $playerTitle->tier) {
            return true;
        }

        return false;
    }

    /**
     * Get titles that a user can grant.
     */
    public function getGrantableTitles(User $granter): Collection
    {
        $granterTitle = $granter->highestTitle();
        if (! $granterTitle) {
            return collect();
        }

        $granterTitleSlug = $granterTitle->titleType?->slug ?? $granterTitle->title;

        return TitleType::active()
            ->get()
            ->filter(fn ($titleType) => $titleType->canBeGrantedBy($granterTitleSlug));
    }

    /**
     * Get available title slots in a domain.
     */
    public function getAvailableTitleSlots(string $domainType, int $domainId): Collection
    {
        return TitleType::active()
            ->where(function ($query) use ($domainType) {
                $query->where('domain_type', $domainType)
                    ->orWhereNull('domain_type');
            })
            ->whereNotNull('limit_per_domain')
            ->get()
            ->map(function ($titleType) use ($domainType, $domainId) {
                $current = $titleType->countInDomain($domainType, $domainId);
                $available = $titleType->availableSlotsInDomain($domainType, $domainId);

                return [
                    'title_type' => $titleType,
                    'current' => $current,
                    'limit' => $titleType->limit_per_domain,
                    'available' => $available,
                ];
            });
    }

    /**
     * Update a user's primary title to their highest active title.
     */
    protected function updatePrimaryTitle(User $user): void
    {
        $highestTitle = $user->activeTitles()
            ->with('titleType')
            ->orderByDesc('tier')
            ->first();

        if ($highestTitle) {
            $user->update([
                'primary_title' => $highestTitle->titleType?->slug ?? $highestTitle->title,
                'title_tier' => $highestTitle->tier,
            ]);
        } else {
            // Reset to peasant if no titles
            $user->update([
                'primary_title' => 'peasant',
                'title_tier' => 2,
            ]);
        }
    }

    /**
     * Get all titles held by a user.
     */
    public function getUserTitles(User $user): Collection
    {
        return $user->activeTitles()
            ->with(['titleType', 'grantedBy', 'domain'])
            ->orderByDesc('tier')
            ->get()
            ->map(fn ($title) => [
                'id' => $title->id,
                'name' => $title->titleType?->name ?? ucfirst($title->title),
                'tier' => $title->tier,
                'category' => $title->titleType?->category,
                'style_of_address' => $title->titleType?->style_of_address,
                'domain_type' => $title->domain_type,
                'domain_name' => $title->domain?->name,
                'granted_by' => $title->grantedBy?->username,
                'granted_at' => $title->granted_at?->format('Y-m-d'),
                'is_landed' => $title->titleType?->is_landed ?? false,
            ]);
    }

    /**
     * Format a user's full styled name with title.
     */
    public function getStyledName(User $user): string
    {
        $highestTitle = $user->highestTitle();
        if (! $highestTitle || ! $highestTitle->titleType) {
            return $user->username;
        }

        $isFemale = $user->gender === 'female';

        return $highestTitle->titleType->getStyledName($user->username, $isFemale);
    }

    // =========================================================================
    // PETITION SYSTEM
    // =========================================================================

    /**
     * Submit a petition for a title.
     */
    public function submitPetition(
        User $petitioner,
        TitleType $titleType,
        User $petitionTo,
        ?string $message = null,
        bool $isPurchase = false,
        ?string $domainType = null,
        ?int $domainId = null
    ): array {
        // Check if title allows petitions
        if (! $titleType->requiresPetition() && ! $titleType->can_purchase) {
            return [
                'success' => false,
                'message' => "The title of {$titleType->name} cannot be petitioned for.",
            ];
        }

        // Check if the petition target can grant this title
        if (! $this->canGrantTitle($petitionTo, $titleType)) {
            return [
                'success' => false,
                'message' => "{$petitionTo->username} does not have the authority to grant the title of {$titleType->name}.",
            ];
        }

        // Check if petitioner meets requirements
        $requirementCheck = $titleType->userMeetsRequirements($petitioner);
        if (! $requirementCheck['meets_all'] && ! $isPurchase) {
            $unmetList = implode(', ', array_keys($requirementCheck['unmet']));

            return [
                'success' => false,
                'message' => "You do not meet the requirements for {$titleType->name}. Missing: {$unmetList}.",
                'unmet_requirements' => $requirementCheck['unmet'],
            ];
        }

        // Check if already has pending petition for this title
        $existingPetition = TitlePetition::where('petitioner_id', $petitioner->id)
            ->where('title_type_id', $titleType->id)
            ->pending()
            ->notExpired()
            ->first();

        if ($existingPetition) {
            return [
                'success' => false,
                'message' => "You already have a pending petition for {$titleType->name}.",
            ];
        }

        // Check if already holds this title
        $existingTitle = PlayerTitle::where('user_id', $petitioner->id)
            ->where('title_type_id', $titleType->id)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->first();

        if ($existingTitle) {
            return [
                'success' => false,
                'message' => "You already hold the title of {$titleType->name}.",
            ];
        }

        // For purchases, check if petitioner has enough gold
        $goldOffered = 0;
        if ($isPurchase && $titleType->can_purchase) {
            if ($petitioner->gold < $titleType->purchase_cost) {
                return [
                    'success' => false,
                    'message' => "You need {$titleType->purchase_cost} gold to purchase this title. You have {$petitioner->gold}.",
                ];
            }
            $goldOffered = $titleType->purchase_cost;
        }

        // Check domain limits
        $targetDomainType = $domainType ?? $titleType->domain_type;
        $targetDomainId = $domainId;

        if ($titleType->limit_per_domain && $targetDomainType && $targetDomainId) {
            if ($titleType->isLimitReachedInDomain($targetDomainType, $targetDomainId)) {
                return [
                    'success' => false,
                    'message' => "No more {$titleType->name} positions available in this {$targetDomainType}.",
                ];
            }
        }

        // Check superior limits
        if ($titleType->limit_per_superior) {
            if ($titleType->isLimitReachedForSuperior($petitionTo->id)) {
                return [
                    'success' => false,
                    'message' => "{$petitionTo->username} has already granted the maximum number of {$titleType->name} titles.",
                ];
            }
        }

        $petition = TitlePetition::create([
            'petitioner_id' => $petitioner->id,
            'title_type_id' => $titleType->id,
            'petition_to_id' => $petitionTo->id,
            'domain_type' => $targetDomainType,
            'domain_id' => $targetDomainId,
            'status' => TitlePetition::STATUS_PENDING,
            'petition_message' => $message,
            'is_purchase' => $isPurchase,
            'gold_offered' => $goldOffered,
            'ceremony_required' => $titleType->requires_ceremony,
            'expires_at' => now()->addDays(TitlePetition::DEFAULT_EXPIRATION_DAYS),
        ]);

        return [
            'success' => true,
            'message' => "Your petition for the title of {$titleType->name} has been submitted to {$petitionTo->username}.",
            'petition' => $petition,
        ];
    }

    /**
     * Approve a petition.
     */
    public function approvePetition(
        TitlePetition $petition,
        User $approver,
        ?string $responseMessage = null
    ): array {
        if (! $petition->isPending()) {
            return [
                'success' => false,
                'message' => 'This petition is no longer pending.',
            ];
        }

        if ($petition->petition_to_id !== $approver->id) {
            return [
                'success' => false,
                'message' => 'You are not the recipient of this petition.',
            ];
        }

        if ($petition->hasExpired()) {
            $petition->update(['status' => TitlePetition::STATUS_EXPIRED]);

            return [
                'success' => false,
                'message' => 'This petition has expired.',
            ];
        }

        $titleType = $petition->titleType;
        $petitioner = $petition->petitioner;

        return DB::transaction(function () use ($petition, $approver, $titleType, $petitioner, $responseMessage) {
            // Handle purchase payment
            if ($petition->is_purchase && $petition->gold_offered > 0) {
                if ($petitioner->gold < $petition->gold_offered) {
                    return [
                        'success' => false,
                        'message' => 'Petitioner no longer has enough gold.',
                    ];
                }
                $petitioner->decrement('gold', $petition->gold_offered);
                $approver->increment('gold', $petition->gold_offered);
            }

            // Update petition status
            $petition->approve($responseMessage);

            // If no ceremony required, grant title immediately
            if (! $titleType->requires_ceremony) {
                $grantResult = $this->grantTitle(
                    $petitioner,
                    $titleType,
                    $approver,
                    $petition->domain_type,
                    $petition->domain_id,
                    $petition->is_purchase ? 'purchase' : 'petition'
                );

                if (! $grantResult['success']) {
                    // Refund gold if grant failed
                    if ($petition->is_purchase && $petition->gold_offered > 0) {
                        $petitioner->increment('gold', $petition->gold_offered);
                        $approver->decrement('gold', $petition->gold_offered);
                    }

                    return $grantResult;
                }

                return [
                    'success' => true,
                    'message' => "You have approved {$petitioner->username}'s petition. They are now a {$titleType->name}.",
                    'petition' => $petition->fresh(),
                    'title' => $grantResult['title'],
                ];
            }

            return [
                'success' => true,
                'message' => "You have approved {$petitioner->username}'s petition for {$titleType->name}. A ceremony is required to complete the investiture.",
                'petition' => $petition->fresh(),
                'ceremony_required' => true,
            ];
        });
    }

    /**
     * Deny a petition.
     */
    public function denyPetition(
        TitlePetition $petition,
        User $denier,
        ?string $responseMessage = null
    ): array {
        if (! $petition->isPending()) {
            return [
                'success' => false,
                'message' => 'This petition is no longer pending.',
            ];
        }

        if ($petition->petition_to_id !== $denier->id) {
            return [
                'success' => false,
                'message' => 'You are not the recipient of this petition.',
            ];
        }

        $petition->deny($responseMessage);

        return [
            'success' => true,
            'message' => "You have denied {$petition->petitioner->username}'s petition for {$petition->titleType->name}.",
            'petition' => $petition->fresh(),
        ];
    }

    /**
     * Withdraw a petition.
     */
    public function withdrawPetition(TitlePetition $petition, User $petitioner): array
    {
        if ($petition->petitioner_id !== $petitioner->id) {
            return [
                'success' => false,
                'message' => 'This is not your petition.',
            ];
        }

        if (! $petition->isPending() && ! $petition->isAwaitingCeremony()) {
            return [
                'success' => false,
                'message' => 'This petition cannot be withdrawn.',
            ];
        }

        $petition->withdraw();

        return [
            'success' => true,
            'message' => "You have withdrawn your petition for {$petition->titleType->name}.",
        ];
    }

    /**
     * Complete a ceremony for an approved petition.
     */
    public function completeCeremony(TitlePetition $petition, User $officiant): array
    {
        if (! $petition->isAwaitingCeremony()) {
            return [
                'success' => false,
                'message' => 'This petition is not awaiting a ceremony.',
            ];
        }

        // Officiant must be the one who approved or someone of equal/higher rank
        $approver = $petition->petitionTo;
        if ($officiant->id !== $approver->id) {
            $officiantTitle = $officiant->highestTitle();
            $approverTitle = $approver->highestTitle();

            if (! $officiantTitle || ($approverTitle && $officiantTitle->tier < $approverTitle->tier)) {
                return [
                    'success' => false,
                    'message' => 'You do not have the authority to officiate this ceremony.',
                ];
            }
        }

        $titleType = $petition->titleType;
        $petitioner = $petition->petitioner;

        return DB::transaction(function () use ($petition, $officiant, $titleType, $petitioner) {
            // Grant the title
            $grantResult = $this->grantTitle(
                $petitioner,
                $titleType,
                $officiant,
                $petition->domain_type,
                $petition->domain_id,
                'ceremony'
            );

            if (! $grantResult['success']) {
                return $grantResult;
            }

            // Mark ceremony as completed
            $petition->completeCeremony();

            return [
                'success' => true,
                'message' => "The ceremony is complete! {$petitioner->username} is now {$titleType->style_of_address} {$petitioner->username}, {$titleType->name}.",
                'petition' => $petition->fresh(),
                'title' => $grantResult['title'],
            ];
        });
    }

    /**
     * Get pending petitions for a user to review (as potential grantor).
     */
    public function getPendingPetitionsToReview(User $user): Collection
    {
        return TitlePetition::forGrantor($user->id)
            ->pending()
            ->notExpired()
            ->with(['petitioner', 'titleType', 'domain'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($petition) => $this->formatPetition($petition));
    }

    /**
     * Get petitions awaiting ceremony.
     */
    public function getPetitionsAwaitingCeremony(User $user): Collection
    {
        return TitlePetition::forGrantor($user->id)
            ->awaitingCeremony()
            ->with(['petitioner', 'titleType', 'domain'])
            ->orderBy('responded_at', 'asc')
            ->get()
            ->map(fn ($petition) => $this->formatPetition($petition));
    }

    /**
     * Get a user's own petitions.
     */
    public function getUserPetitions(User $user): Collection
    {
        return TitlePetition::byPetitioner($user->id)
            ->with(['petitionTo', 'titleType', 'domain'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($petition) => $this->formatPetition($petition, true));
    }

    /**
     * Get titles available for petition by a user.
     */
    public function getTitlesAvailableForPetition(User $user): Collection
    {
        return TitleType::active()
            ->where('progression_type', TitleType::PROGRESSION_PETITION)
            ->get()
            ->map(function ($titleType) use ($user) {
                $requirementCheck = $titleType->userMeetsRequirements($user);
                $canPurchase = $titleType->can_purchase && $user->gold >= $titleType->purchase_cost;

                return [
                    'id' => $titleType->id,
                    'name' => $titleType->name,
                    'slug' => $titleType->slug,
                    'tier' => $titleType->tier,
                    'category' => $titleType->category,
                    'description' => $titleType->description,
                    'style_of_address' => $titleType->style_of_address,
                    'meets_requirements' => $requirementCheck['meets_all'],
                    'unmet_requirements' => $requirementCheck['unmet'],
                    'can_purchase' => $canPurchase,
                    'purchase_cost' => $titleType->purchase_cost,
                    'requires_ceremony' => $titleType->requires_ceremony,
                    'domain_type' => $titleType->domain_type,
                ];
            });
    }

    /**
     * Get potential grantors for a title (users who can grant it).
     */
    public function getPotentialGrantors(TitleType $titleType, ?string $domainType = null, ?int $domainId = null): Collection
    {
        $grantedByTitles = $titleType->granted_by_titles;
        if (empty($grantedByTitles)) {
            return collect();
        }

        return User::whereHas('activeTitles', function ($query) use ($grantedByTitles) {
            $query->whereHas('titleType', function ($q) use ($grantedByTitles) {
                $q->whereIn('slug', $grantedByTitles);
            });
        })
            ->with(['activeTitles.titleType'])
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'username' => $user->username,
                'title' => $user->highestTitle()?->titleType?->name ?? 'Unknown',
                'styled_name' => $this->getStyledName($user),
            ]);
    }

    /**
     * Format a petition for display.
     */
    protected function formatPetition(TitlePetition $petition, bool $forPetitioner = false): array
    {
        $data = [
            'id' => $petition->id,
            'title_name' => $petition->titleType->name,
            'title_slug' => $petition->titleType->slug,
            'status' => $petition->status,
            'petition_message' => $petition->petition_message,
            'response_message' => $petition->response_message,
            'is_purchase' => $petition->is_purchase,
            'gold_offered' => $petition->gold_offered,
            'ceremony_required' => $petition->ceremony_required,
            'ceremony_completed' => $petition->ceremony_completed,
            'domain_type' => $petition->domain_type,
            'domain_name' => $petition->domain?->name,
            'created_at' => $petition->created_at->format('Y-m-d H:i'),
            'expires_at' => $petition->expires_at?->format('Y-m-d H:i'),
            'responded_at' => $petition->responded_at?->format('Y-m-d H:i'),
        ];

        if ($forPetitioner) {
            $data['petition_to'] = [
                'id' => $petition->petitionTo->id,
                'username' => $petition->petitionTo->username,
                'styled_name' => $this->getStyledName($petition->petitionTo),
            ];
        } else {
            $data['petitioner'] = [
                'id' => $petition->petitioner->id,
                'username' => $petition->petitioner->username,
            ];
        }

        return $data;
    }
}
