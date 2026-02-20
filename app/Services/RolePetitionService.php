<?php

namespace App\Services;

use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\RolePetition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolePetitionService
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * Create a petition to challenge a role holder.
     *
     * @return array{success: bool, message: string, petition?: RolePetition}
     */
    public function createPetition(
        User $petitioner,
        PlayerRole $targetPlayerRole,
        string $reason,
        bool $requestAppointment = false
    ): array {
        // Target role must be active
        if (! $targetPlayerRole->isActive()) {
            return [
                'success' => false,
                'message' => 'This role assignment is no longer active.',
            ];
        }

        $role = $targetPlayerRole->role;

        // Cannot petition against elected/authority roles (use election instead)
        if ($role->is_elected) {
            return [
                'success' => false,
                'message' => 'Elected officials cannot be challenged via petition. Start an election instead.',
            ];
        }

        // Petitioner must be at the location
        if ($petitioner->current_location_type !== $targetPlayerRole->location_type
            || $petitioner->current_location_id !== $targetPlayerRole->location_id) {
            return [
                'success' => false,
                'message' => 'You must be at this location to file a petition.',
            ];
        }

        // Petitioner must be a resident
        if (! $this->roleService->userResidesAt(
            $petitioner,
            $targetPlayerRole->location_type,
            $targetPlayerRole->location_id
        )) {
            return [
                'success' => false,
                'message' => 'You must be a resident of this location to file a petition.',
            ];
        }

        // Cannot petition against yourself
        if ($petitioner->id === $targetPlayerRole->user_id) {
            return [
                'success' => false,
                'message' => 'You cannot petition against yourself. Resign instead.',
            ];
        }

        // No duplicate pending petition from same petitioner for same role
        $existing = RolePetition::where('petitioner_id', $petitioner->id)
            ->where('target_player_role_id', $targetPlayerRole->id)
            ->pending()
            ->notExpired()
            ->exists();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'You already have a pending petition against this role holder.',
            ];
        }

        // King cannot be petitioned (use no-confidence vote)
        if ($role->slug === 'king') {
            return [
                'success' => false,
                'message' => 'The King cannot be challenged via petition. Use a no-confidence vote instead.',
            ];
        }

        // Find the authority figure
        $authority = $this->getAuthorityForRole($targetPlayerRole);

        if (! $authority) {
            return [
                'success' => false,
                'message' => 'No authority figure is available to review this petition. The required position is vacant.',
            ];
        }

        $petition = RolePetition::create([
            'petitioner_id' => $petitioner->id,
            'target_player_role_id' => $targetPlayerRole->id,
            'authority_user_id' => $authority['user_id'],
            'authority_role_slug' => $authority['role_slug'],
            'location_type' => $targetPlayerRole->location_type,
            'location_id' => $targetPlayerRole->location_id,
            'status' => RolePetition::STATUS_PENDING,
            'petition_reason' => $reason,
            'request_appointment' => $requestAppointment,
            'expires_at' => now()->addDays(RolePetition::EXPIRATION_DAYS),
        ]);

        return [
            'success' => true,
            'message' => 'Petition filed successfully. The authority figure will review it.',
            'petition' => $petition,
        ];
    }

    /**
     * Approve a petition (authority action).
     *
     * @return array{success: bool, message: string}
     */
    public function approvePetition(User $authority, RolePetition $petition, ?string $response = null): array
    {
        if ($petition->authority_user_id !== $authority->id) {
            return [
                'success' => false,
                'message' => 'You are not authorized to review this petition.',
            ];
        }

        if (! $petition->isPending()) {
            return [
                'success' => false,
                'message' => 'This petition is no longer pending.',
            ];
        }

        if ($petition->hasExpired()) {
            return [
                'success' => false,
                'message' => 'This petition has expired.',
            ];
        }

        return DB::transaction(function () use ($authority, $petition, $response) {
            // Approve the petition
            $petition->approve($response);

            // Remove the target from their role
            $targetPlayerRole = $petition->targetPlayerRole;
            if ($targetPlayerRole && $targetPlayerRole->isActive()) {
                $this->roleService->removeFromRole($targetPlayerRole, $authority, 'Removed by petition');
            }

            // Optionally appoint the petitioner
            if ($petition->request_appointment && $targetPlayerRole) {
                $petitioner = $petition->petitioner;
                $role = $targetPlayerRole->role;
                $this->roleService->appointRole(
                    $petitioner,
                    $role,
                    $petition->location_type,
                    $petition->location_id,
                    $authority
                );
            }

            return [
                'success' => true,
                'message' => 'Petition approved. The role holder has been removed.',
            ];
        });
    }

    /**
     * Deny a petition (authority action).
     *
     * @return array{success: bool, message: string}
     */
    public function denyPetition(User $authority, RolePetition $petition, ?string $response = null): array
    {
        if ($petition->authority_user_id !== $authority->id) {
            return [
                'success' => false,
                'message' => 'You are not authorized to review this petition.',
            ];
        }

        if (! $petition->isPending()) {
            return [
                'success' => false,
                'message' => 'This petition is no longer pending.',
            ];
        }

        $petition->deny($response);

        return [
            'success' => true,
            'message' => 'Petition denied.',
        ];
    }

    /**
     * Withdraw a petition (petitioner action).
     *
     * @return array{success: bool, message: string}
     */
    public function withdrawPetition(User $petitioner, RolePetition $petition): array
    {
        if ($petition->petitioner_id !== $petitioner->id) {
            return [
                'success' => false,
                'message' => 'This is not your petition.',
            ];
        }

        if (! $petition->isPending()) {
            return [
                'success' => false,
                'message' => 'This petition is no longer pending.',
            ];
        }

        $petition->withdraw();

        return [
            'success' => true,
            'message' => 'Petition withdrawn.',
        ];
    }

    /**
     * Determine the authority figure who reviews a petition against a role.
     *
     * @return array{user_id: int, role_slug: string}|null
     */
    public function getAuthorityForRole(PlayerRole $targetPlayerRole): ?array
    {
        $role = $targetPlayerRole->role;
        $locationType = $targetPlayerRole->location_type;
        $locationId = $targetPlayerRole->location_id;

        // Determine what authority role and location to look for
        $authorityInfo = $this->resolveAuthority($role->slug, $locationType, $locationId);

        if (! $authorityInfo) {
            return null;
        }

        // Find the authority player
        $authorityHolder = $this->roleService->getRoleHolder(
            $authorityInfo['role_slug'],
            $authorityInfo['location_type'],
            $authorityInfo['location_id']
        );

        if (! $authorityHolder) {
            return null;
        }

        return [
            'user_id' => $authorityHolder->id,
            'role_slug' => $authorityInfo['role_slug'],
        ];
    }

    /**
     * Resolve which authority role reviews challenges for a given role.
     *
     * @return array{role_slug: string, location_type: string, location_id: int}|null
     */
    protected function resolveAuthority(string $roleSlug, string $locationType, int $locationId): ?array
    {
        // Village roles (not elder) -> Elder of that village
        if ($locationType === 'village' && $roleSlug !== 'elder') {
            return [
                'role_slug' => 'elder',
                'location_type' => 'village',
                'location_id' => $locationId,
            ];
        }

        // Elder -> Baron of the barony
        if ($roleSlug === 'elder') {
            $village = \App\Models\Village::find($locationId);
            if ($village && $village->barony_id) {
                return [
                    'role_slug' => 'baron',
                    'location_type' => 'barony',
                    'location_id' => $village->barony_id,
                ];
            }

            return null;
        }

        // Town roles (not mayor) -> Mayor of that town
        if ($locationType === 'town' && $roleSlug !== 'mayor') {
            return [
                'role_slug' => 'mayor',
                'location_type' => 'town',
                'location_id' => $locationId,
            ];
        }

        // Mayor -> Baron of the barony
        if ($roleSlug === 'mayor') {
            $town = \App\Models\Town::find($locationId);
            if ($town && $town->barony_id) {
                return [
                    'role_slug' => 'baron',
                    'location_type' => 'barony',
                    'location_id' => $town->barony_id,
                ];
            }

            return null;
        }

        // Barony roles (not baron) -> Baron of that barony
        if ($locationType === 'barony' && $roleSlug !== 'baron') {
            return [
                'role_slug' => 'baron',
                'location_type' => 'barony',
                'location_id' => $locationId,
            ];
        }

        // Baron -> King of the kingdom
        if ($roleSlug === 'baron') {
            $barony = \App\Models\Barony::find($locationId);
            if ($barony && $barony->kingdom_id) {
                return [
                    'role_slug' => 'king',
                    'location_type' => 'kingdom',
                    'location_id' => $barony->kingdom_id,
                ];
            }

            return null;
        }

        // Kingdom roles (not king) -> King of that kingdom
        if ($locationType === 'kingdom' && $roleSlug !== 'king') {
            return [
                'role_slug' => 'king',
                'location_type' => 'kingdom',
                'location_id' => $locationId,
            ];
        }

        // King -> Cannot petition (use no-confidence vote)
        return null;
    }

    /**
     * Get count of pending non-expired petitions for an authority.
     */
    public function getPendingCountForAuthority(int $userId): int
    {
        return RolePetition::where('authority_user_id', $userId)
            ->pending()
            ->notExpired()
            ->count();
    }
}
