<?php

namespace App\Services;

use App\Models\PlayerTitle;
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
}
