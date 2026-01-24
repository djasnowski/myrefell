<?php

namespace App\Services;

use App\Models\LocationNpc;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Get all roles at a location with their current holders.
     */
    public function getRolesAtLocation(string $locationType, int $locationId): Collection
    {
        $roles = Role::where('location_type', $locationType)
            ->where('is_active', true)
            ->orderBy('tier', 'desc')
            ->get();

        return $roles->map(fn ($role) => $this->formatRoleWithHolder($role, $locationType, $locationId));
    }

    /**
     * Get roles held by a specific user.
     */
    public function getUserRoles(User $user): Collection
    {
        return PlayerRole::where('user_id', $user->id)
            ->active()
            ->with('role')
            ->get()
            ->map(fn ($pr) => $this->formatPlayerRole($pr));
    }

    /**
     * Get a specific role at a location with holder info.
     */
    public function getRoleAtLocation(Role $role, string $locationType, int $locationId): array
    {
        return $this->formatRoleWithHolder($role, $locationType, $locationId);
    }

    /**
     * Appoint a user to a role at a location.
     */
    public function appointRole(
        User $user,
        Role $role,
        string $locationType,
        int $locationId,
        ?User $appointedBy = null,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        // Check if role is for this location type
        if ($role->location_type !== $locationType) {
            return [
                'success' => false,
                'message' => 'This role is not available at this type of location.',
            ];
        }

        // Check if role is active
        if (!$role->is_active) {
            return [
                'success' => false,
                'message' => 'This role is not currently active.',
            ];
        }

        // Check if user already holds this role at this location
        $existingRole = PlayerRole::where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->active()
            ->first();

        if ($existingRole) {
            return [
                'success' => false,
                'message' => 'This player already holds this role at this location.',
            ];
        }

        // Check if there are available slots
        if (!$role->hasAvailableSlots($locationType, $locationId)) {
            return [
                'success' => false,
                'message' => 'No positions available for this role at this location.',
            ];
        }

        return DB::transaction(function () use ($user, $role, $locationType, $locationId, $appointedBy, $expiresAt) {
            // Create the player role
            $playerRole = PlayerRole::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'status' => PlayerRole::STATUS_ACTIVE,
                'appointed_at' => now(),
                'expires_at' => $expiresAt,
                'appointed_by_user_id' => $appointedBy?->id,
            ]);

            // Deactivate any NPC for this role at this location
            LocationNpc::where('role_id', $role->id)
                ->where('location_type', $locationType)
                ->where('location_id', $locationId)
                ->update(['is_active' => false]);

            return [
                'success' => true,
                'message' => "{$user->username} has been appointed as {$role->name}!",
                'player_role' => $playerRole,
            ];
        });
    }

    /**
     * Remove a user from a role.
     */
    public function removeFromRole(PlayerRole $playerRole, User $removedBy, string $reason = null): array
    {
        if (!$playerRole->isActive()) {
            return [
                'success' => false,
                'message' => 'This role assignment is not active.',
            ];
        }

        return DB::transaction(function () use ($playerRole, $removedBy, $reason) {
            $playerRole->remove($removedBy, $reason);

            // Reactivate NPC if exists
            $this->activateNpcIfNeeded($playerRole->role_id, $playerRole->location_type, $playerRole->location_id);

            return [
                'success' => true,
                'message' => "Role has been removed from {$playerRole->user->username}.",
            ];
        });
    }

    /**
     * Resign from a role.
     */
    public function resignFromRole(User $user, PlayerRole $playerRole): array
    {
        if ($playerRole->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This role does not belong to you.',
            ];
        }

        if (!$playerRole->isActive()) {
            return [
                'success' => false,
                'message' => 'You do not currently hold this role.',
            ];
        }

        return DB::transaction(function () use ($playerRole) {
            $playerRole->resign();

            // Reactivate NPC if exists
            $this->activateNpcIfNeeded($playerRole->role_id, $playerRole->location_type, $playerRole->location_id);

            return [
                'success' => true,
                'message' => "You have resigned from the {$playerRole->role->name} position.",
            ];
        });
    }

    /**
     * Check if a user has a specific permission at a location.
     */
    public function hasPermission(User $user, string $permission, string $locationType, int $locationId): bool
    {
        $userRoles = PlayerRole::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->active()
            ->with('role')
            ->get();

        foreach ($userRoles as $playerRole) {
            if ($playerRole->role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user holds a specific role at a location.
     */
    public function holdsRole(User $user, string $roleSlug, string $locationType, int $locationId): bool
    {
        return PlayerRole::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->active()
            ->whereHas('role', fn ($q) => $q->where('slug', $roleSlug))
            ->exists();
    }

    /**
     * Get the holder of a specific role at a location.
     */
    public function getRoleHolder(string $roleSlug, string $locationType, int $locationId): ?User
    {
        $role = Role::where('slug', $roleSlug)->first();
        if (!$role) {
            return null;
        }

        $playerRole = $role->getHolderAt($locationType, $locationId);
        return $playerRole?->user;
    }

    /**
     * Pay salaries to all active role holders.
     */
    public function paySalaries(): array
    {
        $paid = [];

        PlayerRole::active()
            ->with(['user', 'role'])
            ->whereHas('role', fn ($q) => $q->where('salary', '>', 0))
            ->chunk(100, function ($playerRoles) use (&$paid) {
                foreach ($playerRoles as $playerRole) {
                    $amount = $playerRole->paySalary();
                    if ($amount > 0) {
                        $paid[] = [
                            'user_id' => $playerRole->user_id,
                            'username' => $playerRole->user->username,
                            'role' => $playerRole->role->name,
                            'amount' => $amount,
                        ];
                    }
                }
            });

        return $paid;
    }

    /**
     * Create or ensure NPC exists for a role at a location.
     */
    public function ensureNpcExists(Role $role, string $locationType, int $locationId): LocationNpc
    {
        return LocationNpc::firstOrCreate(
            [
                'role_id' => $role->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
            ],
            [
                'npc_name' => LocationNpc::generateNpcName($role->slug),
                'npc_description' => "The local {$role->name}.",
                'npc_icon' => $role->icon,
                'is_active' => !$role->hasAvailableSlots($locationType, $locationId) ? false : true,
            ]
        );
    }

    /**
     * Activate NPC if no player holds the role.
     */
    protected function activateNpcIfNeeded(int $roleId, string $locationType, int $locationId): void
    {
        $playerHoldsRole = PlayerRole::where('role_id', $roleId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->active()
            ->exists();

        if (!$playerHoldsRole) {
            LocationNpc::where('role_id', $roleId)
                ->where('location_type', $locationType)
                ->where('location_id', $locationId)
                ->update(['is_active' => true]);
        }
    }

    /**
     * Format a role with its current holder information.
     */
    protected function formatRoleWithHolder(Role $role, string $locationType, int $locationId): array
    {
        $playerRole = $role->getHolderAt($locationType, $locationId);
        $npc = $role->getNpcAt($locationType, $locationId);

        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'icon' => $role->icon,
            'description' => $role->description,
            'location_type' => $role->location_type,
            'permissions' => $role->permissions ?? [],
            'bonuses' => $role->bonuses ?? [],
            'salary' => $role->salary,
            'tier' => $role->tier,
            'is_elected' => $role->is_elected,
            'max_per_location' => $role->max_per_location,
            'holder' => $playerRole ? [
                'player_role_id' => $playerRole->id,
                'user_id' => $playerRole->user_id,
                'username' => $playerRole->user->username,
                'appointed_at' => $playerRole->appointed_at->toISOString(),
                'expires_at' => $playerRole->expires_at?->toISOString(),
                'total_salary_earned' => $playerRole->total_salary_earned,
            ] : null,
            'npc' => (!$playerRole && $npc) ? [
                'id' => $npc->id,
                'name' => $npc->npc_name,
                'description' => $npc->npc_description,
                'icon' => $npc->npc_icon,
            ] : null,
            'is_vacant' => !$playerRole,
        ];
    }

    /**
     * Format a player role for display.
     */
    protected function formatPlayerRole(PlayerRole $playerRole): array
    {
        $role = $playerRole->role;

        return [
            'id' => $playerRole->id,
            'role_id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'icon' => $role->icon,
            'description' => $role->description,
            'location_type' => $playerRole->location_type,
            'location_id' => $playerRole->location_id,
            'location_name' => $playerRole->location_name,
            'permissions' => $role->permissions ?? [],
            'bonuses' => $role->bonuses ?? [],
            'salary' => $role->salary,
            'tier' => $role->tier,
            'status' => $playerRole->status,
            'appointed_at' => $playerRole->appointed_at->toISOString(),
            'expires_at' => $playerRole->expires_at?->toISOString(),
            'total_salary_earned' => $playerRole->total_salary_earned,
        ];
    }
}
