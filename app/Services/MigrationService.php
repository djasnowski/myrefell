<?php

namespace App\Services;

use App\Models\LocationActivityLog;
use App\Models\LocationNpc;
use App\Models\MigrationRequest;
use App\Models\PlayerRole;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MigrationService
{
    /**
     * Request to migrate to a new village.
     */
    public function requestMigration(User $user, Village $toVillage): array
    {
        // Can't migrate to current village
        if ($user->home_village_id === $toVillage->id) {
            return [
                'success' => false,
                'message' => 'You already live in this village.',
            ];
        }

        // Must be physically at the village to settle there
        if ($user->current_location_type !== 'village' || $user->current_location_id !== $toVillage->id) {
            return [
                'success' => false,
                'message' => 'You must travel to this village before you can settle here.',
            ];
        }

        // Check cooldown
        if (! $this->canMigrate($user)) {
            $nextMigration = $user->last_migration_at->addDays(MigrationRequest::MIGRATION_COOLDOWN_DAYS);

            return [
                'success' => false,
                'message' => "You must wait until {$nextMigration->format('M j, Y')} before migrating again.",
            ];
        }

        // Check for existing pending request
        $existingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->first();

        if ($existingRequest) {
            return [
                'success' => false,
                'message' => 'You already have a pending migration request. Cancel it first to request a new one.',
            ];
        }

        // Auto-resign from any role when migrating
        $userRole = PlayerRole::where('user_id', $user->id)->active()->with('role')->first();
        if ($userRole) {
            $roleName = $userRole->role->name;
            $locationId = $userRole->location_id;
            $locationType = $userRole->location_type;
            $roleId = $userRole->role_id;

            // Resign from the role
            $userRole->resign();

            // Reactivate NPC if no player holds the role
            $playerHoldsRole = PlayerRole::where('role_id', $roleId)
                ->where('location_type', $locationType)
                ->where('location_id', $locationId)
                ->active()
                ->exists();

            if (! $playerHoldsRole) {
                LocationNpc::where('role_id', $roleId)
                    ->where('location_type', $locationType)
                    ->where('location_id', $locationId)
                    ->update(['is_active' => true]);
            }

            // Log the abdication
            LocationActivityLog::log(
                $user->id,
                $locationType,
                $locationId,
                LocationActivityLog::TYPE_ABDICATION,
                "{$user->username} has abdicated from their position as {$roleName}",
                null,
                ['role_name' => $roleName]
            );
        }

        return DB::transaction(function () use ($user, $toVillage) {
            $request = MigrationRequest::create([
                'user_id' => $user->id,
                'from_village_id' => $user->home_village_id,
                'to_village_id' => $toVillage->id,
                'status' => MigrationRequest::STATUS_PENDING,
            ]);

            // Auto-approve levels that don't have role holders
            $this->autoApproveVacantLevels($request);

            // Check if fully approved (no authorities exist)
            if ($request->checkAllApprovals()) {
                return $this->completeMigration($request);
            }

            return [
                'success' => true,
                'message' => 'Migration request submitted. Awaiting approval from local authorities.',
                'request' => $request,
            ];
        });
    }

    /**
     * Auto-approve levels that don't have role holders.
     */
    protected function autoApproveVacantLevels(MigrationRequest $request): void
    {
        // If no elder at destination, auto-approve elder level
        if (! $request->needsElderApproval()) {
            $request->update([
                'elder_approved' => true,
                'elder_decided_at' => now(),
            ]);
        }

        // If no baron at destination barony, auto-approve baron level
        if (! $request->needsBaronApproval()) {
            $request->update([
                'baron_approved' => true,
                'baron_decided_at' => now(),
            ]);
        }

        // If no king at destination kingdom, auto-approve king level
        if (! $request->needsKingApproval()) {
            $request->update([
                'king_approved' => true,
                'king_decided_at' => now(),
            ]);
        }
    }

    /**
     * Approve a migration request at a specific level.
     */
    public function approve(MigrationRequest $request, User $approver, string $level): array
    {
        if (! $request->isPending()) {
            return [
                'success' => false,
                'message' => 'This request is no longer pending.',
            ];
        }

        // Verify approver has authority
        if (! $this->canApproveAt($approver, $request, $level)) {
            return [
                'success' => false,
                'message' => 'You do not have authority to approve this request.',
            ];
        }

        return DB::transaction(function () use ($request, $approver, $level) {
            $request->update([
                "{$level}_approved" => true,
                "{$level}_decided_by" => $approver->id,
                "{$level}_decided_at" => now(),
            ]);

            // Check if fully approved now
            $request->refresh();
            if ($request->checkAllApprovals()) {
                return $this->completeMigration($request);
            }

            return [
                'success' => true,
                'message' => "Migration approved at {$level} level.",
                'request' => $request,
            ];
        });
    }

    /**
     * Deny a migration request.
     */
    public function deny(MigrationRequest $request, User $denier, string $level, ?string $reason = null): array
    {
        if (! $request->isPending()) {
            return [
                'success' => false,
                'message' => 'This request is no longer pending.',
            ];
        }

        // Verify denier has authority
        if (! $this->canApproveAt($denier, $request, $level)) {
            return [
                'success' => false,
                'message' => 'You do not have authority to deny this request.',
            ];
        }

        $request->update([
            "{$level}_approved" => false,
            "{$level}_decided_by" => $denier->id,
            "{$level}_decided_at" => now(),
            'status' => MigrationRequest::STATUS_DENIED,
            'denial_reason' => $reason,
        ]);

        return [
            'success' => true,
            'message' => 'Migration request denied.',
            'request' => $request,
        ];
    }

    /**
     * Cancel a migration request.
     */
    public function cancel(MigrationRequest $request, User $user): array
    {
        if ($request->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'You can only cancel your own requests.',
            ];
        }

        if (! $request->isPending()) {
            return [
                'success' => false,
                'message' => 'This request is no longer pending.',
            ];
        }

        $request->update(['status' => MigrationRequest::STATUS_CANCELLED]);

        return [
            'success' => true,
            'message' => 'Migration request cancelled.',
        ];
    }

    /**
     * Complete the migration - actually move the user.
     */
    protected function completeMigration(MigrationRequest $request): array
    {
        $user = $request->user;
        $toVillage = $request->toVillage;

        // Move the user
        $user->update([
            'home_village_id' => $toVillage->id,
            'last_migration_at' => now(),
        ]);

        // Mark request as completed
        $request->update([
            'status' => MigrationRequest::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => "Welcome to {$toVillage->name}! You are now a resident.",
            'request' => $request,
        ];
    }

    /**
     * Check if user can migrate (cooldown check).
     */
    public function canMigrate(User $user): bool
    {
        if (! $user->last_migration_at) {
            return true;
        }

        $cooldownEnd = $user->last_migration_at->addDays(MigrationRequest::MIGRATION_COOLDOWN_DAYS);

        return now()->gte($cooldownEnd);
    }

    /**
     * Check if a user can approve at a specific level.
     */
    public function canApproveAt(User $user, MigrationRequest $request, string $level): bool
    {
        $toVillage = $request->toVillage;

        return match ($level) {
            'elder' => $this->isElderOf($user, $toVillage->id),
            'baron' => $toVillage->barony && $this->isBaronOf($user, $toVillage->barony->id),
            'king' => $toVillage->barony?->kingdom && $this->isKingOf($user, $toVillage->barony->kingdom->id),
            default => false,
        };
    }

    /**
     * Check if user is elder of a village.
     */
    protected function isElderOf(User $user, int $villageId): bool
    {
        return PlayerRole::where('user_id', $user->id)
            ->where('location_type', 'village')
            ->where('location_id', $villageId)
            ->whereHas('role', fn ($q) => $q->where('slug', 'elder'))
            ->active()
            ->exists();
    }

    /**
     * Check if user is baron of a barony.
     */
    protected function isBaronOf(User $user, int $baronyId): bool
    {
        return PlayerRole::where('user_id', $user->id)
            ->where('location_type', 'barony')
            ->where('location_id', $baronyId)
            ->whereHas('role', fn ($q) => $q->where('slug', 'baron'))
            ->active()
            ->exists();
    }

    /**
     * Check if user is king of a kingdom.
     */
    protected function isKingOf(User $user, int $kingdomId): bool
    {
        return PlayerRole::where('user_id', $user->id)
            ->where('location_type', 'kingdom')
            ->where('location_id', $kingdomId)
            ->whereHas('role', fn ($q) => $q->where('slug', 'king'))
            ->active()
            ->exists();
    }

    /**
     * Get pending migration requests that a user can approve.
     */
    public function getPendingRequestsForApprover(User $user): Collection
    {
        // Get villages where user is elder
        $elderVillages = PlayerRole::where('user_id', $user->id)
            ->where('location_type', 'village')
            ->whereHas('role', fn ($q) => $q->where('slug', 'elder'))
            ->active()
            ->pluck('location_id');

        // Get baronies where user is baron
        $baronBaronies = PlayerRole::where('user_id', $user->id)
            ->where('location_type', 'barony')
            ->whereHas('role', fn ($q) => $q->where('slug', 'baron'))
            ->active()
            ->pluck('location_id');

        // Get kingdoms where user is king
        $kingKingdoms = PlayerRole::where('user_id', $user->id)
            ->where('location_type', 'kingdom')
            ->whereHas('role', fn ($q) => $q->where('slug', 'king'))
            ->active()
            ->pluck('location_id');

        return MigrationRequest::pending()
            ->with(['user', 'fromVillage', 'toVillage.barony.kingdom'])
            ->where(function ($q) use ($elderVillages) {
                // Elder can approve requests to their village
                $q->whereIn('to_village_id', $elderVillages)
                    ->whereNull('elder_approved');
            })
            ->orWhere(function ($q) use ($baronBaronies) {
                // Baron can approve requests to villages in their barony
                $q->whereHas('toVillage', fn ($vq) => $vq->whereIn('barony_id', $baronBaronies))
                    ->whereNull('baron_approved');
            })
            ->orWhere(function ($q) use ($kingKingdoms) {
                // King can approve requests to villages in their kingdom
                $q->whereHas('toVillage.barony', fn ($bq) => $bq->whereIn('kingdom_id', $kingKingdoms))
                    ->whereNull('king_approved');
            })
            ->get();
    }

    /**
     * Get user's migration request history.
     */
    public function getUserRequests(User $user): Collection
    {
        return MigrationRequest::where('user_id', $user->id)
            ->with(['fromVillage', 'toVillage'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
