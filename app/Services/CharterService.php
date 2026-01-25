<?php

namespace App\Services;

use App\Models\Castle;
use App\Models\Charter;
use App\Models\CharterSignatory;
use App\Models\Kingdom;
use App\Models\SettlementRuin;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CharterService
{
    /**
     * Minimum distance between settlements (in coordinate units).
     */
    public const MIN_SETTLEMENT_DISTANCE = 50;

    /**
     * Get all charters for a kingdom.
     */
    public function getKingdomCharters(Kingdom $kingdom): Collection
    {
        return Charter::forKingdom($kingdom->id)
            ->with(['founder', 'issuer', 'kingdom'])
            ->withCount('signatories')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($charter) => $this->formatCharter($charter));
    }

    /**
     * Get all charters created by a user.
     */
    public function getUserCharters(User $user): Collection
    {
        return Charter::where('founder_id', $user->id)
            ->with(['kingdom', 'issuer'])
            ->withCount('signatories')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($charter) => $this->formatCharter($charter));
    }

    /**
     * Get a single charter with full details.
     */
    public function getCharterDetails(Charter $charter): array
    {
        $charter->load(['founder', 'issuer', 'kingdom', 'signatories.user']);

        return $this->formatCharter($charter, true);
    }

    /**
     * Create a new charter request.
     */
    public function createCharter(
        User $founder,
        Kingdom $kingdom,
        string $settlementName,
        string $settlementType,
        ?string $description = null,
        ?array $taxTerms = null,
        ?int $coordinatesX = null,
        ?int $coordinatesY = null,
        ?string $biome = null
    ): array {
        // Validate settlement name is unique
        if ($this->settlementNameExists($settlementName)) {
            return [
                'success' => false,
                'message' => 'A settlement with this name already exists.',
            ];
        }

        // Calculate cost based on settlement type
        $cost = Charter::getCostForType($settlementType);
        $requiredSignatories = Charter::getRequiredSignatoriesForType($settlementType);

        // Check if founder has enough gold
        if ($founder->gold < $cost) {
            return [
                'success' => false,
                'message' => "You need {$cost} gold to request a charter for a {$settlementType}. You have {$founder->gold} gold.",
            ];
        }

        // Check if founder already has a pending charter
        $existingPending = Charter::where('founder_id', $founder->id)
            ->whereIn('status', [Charter::STATUS_PENDING, Charter::STATUS_APPROVED])
            ->first();

        if ($existingPending) {
            return [
                'success' => false,
                'message' => 'You already have an active charter request. Complete or cancel it before starting another.',
            ];
        }

        // Validate coordinates if provided
        if ($coordinatesX !== null && $coordinatesY !== null) {
            $coordinateCheck = $this->validateCoordinates($kingdom->id, $coordinatesX, $coordinatesY);
            if (!$coordinateCheck['valid']) {
                return [
                    'success' => false,
                    'message' => $coordinateCheck['message'],
                ];
            }
        }

        return DB::transaction(function () use ($founder, $kingdom, $settlementName, $settlementType, $description, $taxTerms, $cost, $requiredSignatories, $coordinatesX, $coordinatesY, $biome) {
            // Deduct gold from founder
            $founder->decrement('gold', $cost);

            // Create the charter
            $charter = Charter::create([
                'settlement_name' => $settlementName,
                'description' => $description,
                'settlement_type' => $settlementType,
                'kingdom_id' => $kingdom->id,
                'founder_id' => $founder->id,
                'tax_terms' => $taxTerms ?? [
                    'village_rate' => 10,
                    'kingdom_tribute' => 5,
                    'years_tax_free' => 1,
                ],
                'gold_cost' => $cost,
                'status' => Charter::STATUS_PENDING,
                'required_signatories' => $requiredSignatories,
                'current_signatories' => 1, // Founder counts as first signatory
                'submitted_at' => now(),
                'coordinates_x' => $coordinatesX,
                'coordinates_y' => $coordinatesY,
                'biome' => $biome ?? $kingdom->biome,
            ]);

            // Founder automatically signs their own charter
            CharterSignatory::create([
                'charter_id' => $charter->id,
                'user_id' => $founder->id,
                'comment' => 'Founding charter request.',
            ]);

            return [
                'success' => true,
                'message' => "Charter request for {$settlementName} has been submitted. Gather {$requiredSignatories} signatories and await royal approval.",
                'charter' => $this->formatCharter($charter->fresh()),
            ];
        });
    }

    /**
     * Sign a charter as a supporter.
     */
    public function signCharter(User $user, Charter $charter, ?string $comment = null): array
    {
        // Check charter is still pending
        if (!$charter->isPending()) {
            return [
                'success' => false,
                'message' => 'This charter is no longer accepting signatures.',
            ];
        }

        // Check if user has already signed
        $existingSignature = CharterSignatory::where('charter_id', $charter->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingSignature) {
            return [
                'success' => false,
                'message' => 'You have already signed this charter.',
            ];
        }

        // User must be a resident of the kingdom
        $userKingdom = $user->homeVillage?->castle?->kingdom;
        if (!$userKingdom || $userKingdom->id !== $charter->kingdom_id) {
            return [
                'success' => false,
                'message' => 'You must be a resident of this kingdom to sign this charter.',
            ];
        }

        return DB::transaction(function () use ($user, $charter, $comment) {
            // Create signature
            CharterSignatory::create([
                'charter_id' => $charter->id,
                'user_id' => $user->id,
                'comment' => $comment,
            ]);

            // Update signatory count
            $charter->increment('current_signatories');
            $charter->refresh();

            $message = "You have signed the charter for {$charter->settlement_name}.";

            if ($charter->hasEnoughSignatories()) {
                $message .= ' The charter now has enough signatories and awaits royal approval.';
            } else {
                $remaining = $charter->required_signatories - $charter->current_signatories;
                $message .= " {$remaining} more signatures needed.";
            }

            return [
                'success' => true,
                'message' => $message,
                'charter' => $this->formatCharter($charter),
            ];
        });
    }

    /**
     * Approve a charter (by King or authorized official).
     */
    public function approveCharter(User $approver, Charter $charter): array
    {
        // Check charter is pending
        if (!$charter->isPending()) {
            return [
                'success' => false,
                'message' => 'This charter is not pending approval.',
            ];
        }

        // Check if approver has authority (must be king of the kingdom)
        $kingdom = $charter->kingdom;
        if ($kingdom->king_user_id !== $approver->id && !$approver->is_admin) {
            return [
                'success' => false,
                'message' => 'Only the King can approve charters in this kingdom.',
            ];
        }

        // Check if charter has enough signatories
        if (!$charter->hasEnoughSignatories()) {
            return [
                'success' => false,
                'message' => "This charter needs {$charter->required_signatories} signatories. It currently has {$charter->current_signatories}.",
            ];
        }

        return DB::transaction(function () use ($approver, $charter) {
            $charter->update([
                'status' => Charter::STATUS_APPROVED,
                'issuer_id' => $approver->id,
                'approved_at' => now(),
                'expires_at' => now()->addDays(Charter::APPROVAL_EXPIRY_DAYS),
            ]);

            return [
                'success' => true,
                'message' => "Charter for {$charter->settlement_name} has been approved! The founder has {Charter::APPROVAL_EXPIRY_DAYS} days to found the settlement.",
                'charter' => $this->formatCharter($charter->fresh()),
            ];
        });
    }

    /**
     * Reject a charter.
     */
    public function rejectCharter(User $rejector, Charter $charter, string $reason): array
    {
        // Check charter is pending
        if (!$charter->isPending()) {
            return [
                'success' => false,
                'message' => 'This charter is not pending approval.',
            ];
        }

        // Check if rejector has authority
        $kingdom = $charter->kingdom;
        if ($kingdom->king_user_id !== $rejector->id && !$rejector->is_admin) {
            return [
                'success' => false,
                'message' => 'Only the King can reject charters in this kingdom.',
            ];
        }

        return DB::transaction(function () use ($rejector, $charter, $reason) {
            // Refund 50% of the charter cost to the founder
            $refundAmount = (int) ($charter->gold_cost * 0.5);
            $charter->founder->increment('gold', $refundAmount);

            $charter->update([
                'status' => Charter::STATUS_REJECTED,
                'issuer_id' => $rejector->id,
                'rejection_reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => "Charter for {$charter->settlement_name} has been rejected. {$refundAmount} gold has been refunded to the founder.",
                'charter' => $this->formatCharter($charter->fresh()),
            ];
        });
    }

    /**
     * Found the settlement from an approved charter.
     */
    public function foundSettlement(User $founder, Charter $charter, ?int $coordinatesX = null, ?int $coordinatesY = null): array
    {
        // Verify founder
        if ($charter->founder_id !== $founder->id) {
            return [
                'success' => false,
                'message' => 'Only the charter founder can found this settlement.',
            ];
        }

        // Check charter is approved
        if (!$charter->isApproved()) {
            return [
                'success' => false,
                'message' => 'This charter has not been approved.',
            ];
        }

        // Check if charter has expired
        if ($charter->isExpired()) {
            $this->expireCharter($charter);

            return [
                'success' => false,
                'message' => 'This charter has expired.',
            ];
        }

        // Use provided coordinates or charter coordinates
        $x = $coordinatesX ?? $charter->coordinates_x;
        $y = $coordinatesY ?? $charter->coordinates_y;

        // Validate coordinates
        if ($x === null || $y === null) {
            return [
                'success' => false,
                'message' => 'Settlement coordinates are required.',
            ];
        }

        $coordinateCheck = $this->validateCoordinates($charter->kingdom_id, $x, $y);
        if (!$coordinateCheck['valid']) {
            return [
                'success' => false,
                'message' => $coordinateCheck['message'],
            ];
        }

        return DB::transaction(function () use ($charter, $x, $y) {
            $kingdom = $charter->kingdom;
            $biome = $charter->biome ?? $kingdom->biome;

            // Create the settlement based on type
            $settlement = match ($charter->settlement_type) {
                Charter::TYPE_VILLAGE => $this->createVillage($charter, $x, $y, $biome),
                Charter::TYPE_CASTLE => $this->createCastle($charter, $kingdom, $x, $y, $biome),
                Charter::TYPE_TOWN => $this->createTown($charter, $kingdom, $x, $y, $biome),
                default => null,
            };

            if (!$settlement) {
                throw new \Exception('Failed to create settlement.');
            }

            // Update charter status
            $updateData = [
                'status' => Charter::STATUS_ACTIVE,
                'founded_at' => now(),
                'vulnerability_ends_at' => now()->addDays(Charter::VULNERABILITY_DAYS),
                'coordinates_x' => $x,
                'coordinates_y' => $y,
                'biome' => $biome,
            ];

            if ($charter->settlement_type === Charter::TYPE_VILLAGE || $charter->settlement_type === Charter::TYPE_TOWN) {
                $updateData['founded_village_id'] = $settlement->id;
            } elseif ($charter->settlement_type === Charter::TYPE_CASTLE) {
                $updateData['founded_castle_id'] = $settlement->id;
            }

            $charter->update($updateData);

            return [
                'success' => true,
                'message' => "{$charter->settlement_name} has been founded! The settlement is vulnerable for the next " . Charter::VULNERABILITY_DAYS . ' days.',
                'charter' => $this->formatCharter($charter->fresh()),
                'settlement' => [
                    'type' => $charter->settlement_type,
                    'id' => $settlement->id,
                    'name' => $settlement->name,
                ],
            ];
        });
    }

    /**
     * Cancel a charter (by founder, before approval).
     */
    public function cancelCharter(User $user, Charter $charter): array
    {
        // Verify founder
        if ($charter->founder_id !== $user->id && !$user->is_admin) {
            return [
                'success' => false,
                'message' => 'Only the charter founder can cancel this request.',
            ];
        }

        // Check charter is pending
        if (!$charter->isPending()) {
            return [
                'success' => false,
                'message' => 'This charter cannot be cancelled.',
            ];
        }

        return DB::transaction(function () use ($charter) {
            // Refund 75% of the charter cost
            $refundAmount = (int) ($charter->gold_cost * 0.75);
            $charter->founder->increment('gold', $refundAmount);

            // Delete signatories and charter
            $charter->signatories()->delete();
            $charter->delete();

            return [
                'success' => true,
                'message' => "Charter request cancelled. {$refundAmount} gold has been refunded.",
            ];
        });
    }

    /**
     * Reclaim a ruined settlement.
     */
    public function reclaimRuin(User $founder, SettlementRuin $ruin): array
    {
        if (!$ruin->is_reclaimable) {
            return [
                'success' => false,
                'message' => 'This ruin cannot be reclaimed.',
            ];
        }

        if ($founder->gold < $ruin->reclaim_cost) {
            return [
                'success' => false,
                'message' => "You need {$ruin->reclaim_cost} gold to reclaim this ruin. You have {$founder->gold} gold.",
            ];
        }

        // Check if founder already has a pending charter
        $existingPending = Charter::where('founder_id', $founder->id)
            ->whereIn('status', [Charter::STATUS_PENDING, Charter::STATUS_APPROVED])
            ->first();

        if ($existingPending) {
            return [
                'success' => false,
                'message' => 'You already have an active charter request.',
            ];
        }

        return DB::transaction(function () use ($founder, $ruin) {
            // Deduct gold
            $founder->decrement('gold', $ruin->reclaim_cost);

            // Create a pre-approved charter for the reclaim
            $charter = Charter::create([
                'settlement_name' => $ruin->name,
                'description' => "Reclaimed from ruins. {$ruin->description}",
                'settlement_type' => Charter::TYPE_VILLAGE,
                'kingdom_id' => $ruin->kingdom_id,
                'founder_id' => $founder->id,
                'gold_cost' => $ruin->reclaim_cost,
                'status' => Charter::STATUS_APPROVED,
                'required_signatories' => 1,
                'current_signatories' => 1,
                'submitted_at' => now(),
                'approved_at' => now(),
                'expires_at' => now()->addDays(Charter::APPROVAL_EXPIRY_DAYS),
                'coordinates_x' => $ruin->coordinates_x,
                'coordinates_y' => $ruin->coordinates_y,
                'biome' => $ruin->biome,
            ]);

            // Founder signs
            CharterSignatory::create([
                'charter_id' => $charter->id,
                'user_id' => $founder->id,
                'comment' => 'Reclaiming ruined settlement.',
            ]);

            // Mark ruin as no longer reclaimable
            $ruin->update(['is_reclaimable' => false]);

            return [
                'success' => true,
                'message' => "You have claimed the rights to rebuild {$ruin->name}. You may now found the settlement.",
                'charter' => $this->formatCharter($charter),
            ];
        });
    }

    /**
     * Get ruins in a kingdom.
     */
    public function getKingdomRuins(Kingdom $kingdom): Collection
    {
        return SettlementRuin::inKingdom($kingdom->id)
            ->reclaimable()
            ->with('originalFounder')
            ->orderByDesc('ruined_at')
            ->get()
            ->map(fn ($ruin) => [
                'id' => $ruin->id,
                'name' => $ruin->name,
                'description' => $ruin->description,
                'biome' => $ruin->biome,
                'coordinates' => [
                    'x' => $ruin->coordinates_x,
                    'y' => $ruin->coordinates_y,
                ],
                'reclaim_cost' => $ruin->reclaim_cost,
                'original_founder' => $ruin->originalFounder ? [
                    'id' => $ruin->originalFounder->id,
                    'username' => $ruin->originalFounder->username,
                ] : null,
                'ruined_at' => $ruin->ruined_at->toISOString(),
            ]);
    }

    /**
     * Expire a charter that has passed its deadline.
     */
    protected function expireCharter(Charter $charter): void
    {
        $charter->update([
            'status' => Charter::STATUS_EXPIRED,
        ]);

        // Create a ruin at the location if coordinates were set
        if ($charter->coordinates_x !== null && $charter->coordinates_y !== null) {
            SettlementRuin::create([
                'name' => $charter->settlement_name . ' (Abandoned)',
                'description' => 'A settlement that was never built. The charter expired.',
                'kingdom_id' => $charter->kingdom_id,
                'original_charter_id' => $charter->id,
                'original_founder_id' => $charter->founder_id,
                'coordinates_x' => $charter->coordinates_x,
                'coordinates_y' => $charter->coordinates_y,
                'biome' => $charter->biome ?? $charter->kingdom->biome,
                'reclaim_cost' => (int) ($charter->gold_cost * 0.3),
                'ruined_at' => now(),
            ]);
        }
    }

    /**
     * Create a village from a charter.
     */
    protected function createVillage(Charter $charter, int $x, int $y, string $biome): Village
    {
        return Village::create([
            'name' => $charter->settlement_name,
            'description' => $charter->description ?? "A newly founded village in {$charter->kingdom->name}.",
            'castle_id' => null, // Independent village
            'is_town' => false,
            'population' => 0,
            'wealth' => 0,
            'biome' => $biome,
            'is_port' => false,
            'coordinates_x' => $x,
            'coordinates_y' => $y,
        ]);
    }

    /**
     * Create a castle from a charter.
     */
    protected function createCastle(Charter $charter, Kingdom $kingdom, int $x, int $y, string $biome): Castle
    {
        // Find or create a town for the castle
        $town = $kingdom->towns()->first();

        return Castle::create([
            'name' => $charter->settlement_name,
            'description' => $charter->description ?? "A newly founded castle in {$kingdom->name}.",
            'town_id' => $town?->id,
            'biome' => $biome,
            'coordinates_x' => $x,
            'coordinates_y' => $y,
        ]);
    }

    /**
     * Create a town from a charter.
     */
    protected function createTown(Charter $charter, Kingdom $kingdom, int $x, int $y, string $biome): Village
    {
        return Village::create([
            'name' => $charter->settlement_name,
            'description' => $charter->description ?? "A newly founded town in {$kingdom->name}.",
            'castle_id' => null,
            'is_town' => true,
            'population' => 0,
            'wealth' => 0,
            'biome' => $biome,
            'is_port' => false,
            'coordinates_x' => $x,
            'coordinates_y' => $y,
        ]);
    }

    /**
     * Check if a settlement name already exists.
     */
    protected function settlementNameExists(string $name): bool
    {
        return Village::where('name', $name)->exists()
            || Castle::where('name', $name)->exists()
            || Charter::whereIn('status', [Charter::STATUS_PENDING, Charter::STATUS_APPROVED, Charter::STATUS_ACTIVE])
                ->where('settlement_name', $name)
                ->exists();
    }

    /**
     * Validate coordinates for a new settlement.
     */
    protected function validateCoordinates(int $kingdomId, int $x, int $y): array
    {
        // Check bounds (0-1000)
        if ($x < 0 || $x > 1000 || $y < 0 || $y > 1000) {
            return [
                'valid' => false,
                'message' => 'Coordinates must be between 0 and 1000.',
            ];
        }

        // Check minimum distance from existing villages
        $nearbyVillage = Village::whereRaw(
            'SQRT(POWER(coordinates_x - ?, 2) + POWER(coordinates_y - ?, 2)) < ?',
            [$x, $y, self::MIN_SETTLEMENT_DISTANCE]
        )->first();

        if ($nearbyVillage) {
            return [
                'valid' => false,
                'message' => "Too close to existing settlement: {$nearbyVillage->name}. Minimum distance is " . self::MIN_SETTLEMENT_DISTANCE . ' units.',
            ];
        }

        // Check minimum distance from existing castles
        $nearbyCastle = Castle::whereRaw(
            'SQRT(POWER(coordinates_x - ?, 2) + POWER(coordinates_y - ?, 2)) < ?',
            [$x, $y, self::MIN_SETTLEMENT_DISTANCE]
        )->first();

        if ($nearbyCastle) {
            return [
                'valid' => false,
                'message' => "Too close to existing castle: {$nearbyCastle->name}. Minimum distance is " . self::MIN_SETTLEMENT_DISTANCE . ' units.',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Format a charter for API response.
     */
    protected function formatCharter(Charter $charter, bool $includeSignatories = false): array
    {
        $data = [
            'id' => $charter->id,
            'settlement_name' => $charter->settlement_name,
            'description' => $charter->description,
            'settlement_type' => $charter->settlement_type,
            'kingdom' => [
                'id' => $charter->kingdom->id,
                'name' => $charter->kingdom->name,
            ],
            'founder' => $charter->founder ? [
                'id' => $charter->founder->id,
                'username' => $charter->founder->username,
            ] : null,
            'issuer' => $charter->issuer ? [
                'id' => $charter->issuer->id,
                'username' => $charter->issuer->username,
            ] : null,
            'tax_terms' => $charter->tax_terms,
            'gold_cost' => $charter->gold_cost,
            'status' => $charter->status,
            'required_signatories' => $charter->required_signatories,
            'current_signatories' => $charter->current_signatories,
            'signatories_count' => $charter->signatories_count ?? $charter->signatories()->count(),
            'has_enough_signatories' => $charter->hasEnoughSignatories(),
            'coordinates' => $charter->coordinates_x !== null ? [
                'x' => $charter->coordinates_x,
                'y' => $charter->coordinates_y,
            ] : null,
            'biome' => $charter->biome,
            'is_vulnerable' => $charter->isVulnerable(),
            'vulnerability_ends_at' => $charter->vulnerability_ends_at?->toISOString(),
            'submitted_at' => $charter->submitted_at?->toISOString(),
            'approved_at' => $charter->approved_at?->toISOString(),
            'founded_at' => $charter->founded_at?->toISOString(),
            'expires_at' => $charter->expires_at?->toISOString(),
            'rejection_reason' => $charter->rejection_reason,
            'founded_settlement' => null,
        ];

        // Include founded settlement info if active
        if ($charter->isActive()) {
            if ($charter->foundedVillage) {
                $data['founded_settlement'] = [
                    'type' => 'village',
                    'id' => $charter->foundedVillage->id,
                    'name' => $charter->foundedVillage->name,
                ];
            } elseif ($charter->foundedCastle) {
                $data['founded_settlement'] = [
                    'type' => 'castle',
                    'id' => $charter->foundedCastle->id,
                    'name' => $charter->foundedCastle->name,
                ];
            }
        }

        // Include signatories if requested
        if ($includeSignatories && $charter->relationLoaded('signatories')) {
            $data['signatories'] = $charter->signatories->map(fn ($sig) => [
                'id' => $sig->id,
                'user' => [
                    'id' => $sig->user->id,
                    'username' => $sig->user->username,
                ],
                'comment' => $sig->comment,
                'signed_at' => $sig->created_at->toISOString(),
            ])->toArray();
        }

        return $data;
    }
}
