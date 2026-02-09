<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\LocationNpc;
use App\Models\PlayerRole;
use App\Models\PlayerTitle;
use App\Models\Role;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Minimum population for self-appointment. Below this, anyone can claim.
     * At or above this threshold, an election is required.
     */
    public const SELF_APPOINT_THRESHOLD = 25;

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
     * Self-appoint to a vacant leadership role.
     * Only allowed if:
     * - You are physically at this location
     * - You reside at this location (home village, or village belongs to barony/kingdom)
     * - Location has < 5 residents/members
     */
    public function selfAppoint(User $user, Role $role, string $locationType, int $locationId): array
    {
        // Check if user is physically at this location
        if ($user->current_location_type !== $locationType || $user->current_location_id !== $locationId) {
            return [
                'success' => false,
                'message' => 'You must travel to this location to claim a role here.',
            ];
        }

        // Check if user resides at this location
        if (! $this->userResidesAt($user, $locationType, $locationId)) {
            return [
                'success' => false,
                'message' => 'You must be a resident of this location to claim a role here.',
            ];
        }

        // Check if role is vacant
        $currentHolder = PlayerRole::where('role_id', $role->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->active()
            ->first();

        if ($currentHolder) {
            return [
                'success' => false,
                'message' => "This role is already held by {$currentHolder->user->username}.",
            ];
        }

        // Check population - self-appointment only allowed with < 5 people
        $population = $this->getLocationPopulation($locationType, $locationId);

        if ($population >= self::SELF_APPOINT_THRESHOLD) {
            return [
                'success' => false,
                'message' => "This location has {$population} residents. An election is required to fill this role.",
            ];
        }

        // Use regular appoint logic (appointedBy = null means self-appointed)
        return $this->appointRole($user, $role, $locationType, $locationId, null, null);
    }

    /**
     * Check if a user resides at a location.
     * Supports polymorphic home locations (town, village, barony, kingdom).
     */
    public function userResidesAt(User $user, string $locationType, int $locationId): bool
    {
        // Check new polymorphic home location first
        if ($user->home_location_type && $user->home_location_id) {
            // Direct match - user lives directly at this location
            if ($user->home_location_type === $locationType && $user->home_location_id === $locationId) {
                return true;
            }

            // Check hierarchy - user lives in a location within the target
            return match ($locationType) {
                'town' => $user->home_location_type === 'town' && $user->home_location_id === $locationId,
                'village' => $user->home_location_type === 'village' && $user->home_location_id === $locationId,
                'barony' => $this->homeIsInBarony($user, $locationId),
                'kingdom' => $this->homeIsInKingdom($user, $locationId),
                default => false,
            };
        }

        // Fall back to legacy home_village_id
        $homeVillage = $user->homeVillage;

        if (! $homeVillage) {
            return false;
        }

        return match ($locationType) {
            'village' => $user->home_village_id === $locationId,
            'barony' => $homeVillage->barony_id === $locationId,
            'kingdom' => $homeVillage->barony?->kingdom_id === $locationId,
            default => false,
        };
    }

    /**
     * Check if user's home location is within a barony.
     */
    protected function homeIsInBarony(User $user, int $baronyId): bool
    {
        return match ($user->home_location_type) {
            'village' => \App\Models\Village::where('id', $user->home_location_id)
                ->where('barony_id', $baronyId)->exists(),
            'town' => \App\Models\Town::where('id', $user->home_location_id)
                ->where('barony_id', $baronyId)->exists(),
            'barony' => $user->home_location_id === $baronyId,
            default => false,
        };
    }

    /**
     * Check if user's home location is within a kingdom.
     */
    protected function homeIsInKingdom(User $user, int $kingdomId): bool
    {
        return match ($user->home_location_type) {
            'village' => \App\Models\Village::whereHas('barony', fn ($q) => $q->where('kingdom_id', $kingdomId))
                ->where('id', $user->home_location_id)->exists(),
            'town' => \App\Models\Town::whereHas('barony', fn ($q) => $q->where('kingdom_id', $kingdomId))
                ->where('id', $user->home_location_id)->exists(),
            'barony' => \App\Models\Barony::where('id', $user->home_location_id)
                ->where('kingdom_id', $kingdomId)->exists(),
            'kingdom' => $user->home_location_id === $kingdomId,
            default => false,
        };
    }

    /**
     * Get the population count for a location.
     */
    public function getLocationPopulation(string $locationType, int $locationId): int
    {
        return match ($locationType) {
            'village' => \App\Models\Village::find($locationId)?->residents()->count() ?? 0,
            'town' => \App\Models\User::where('home_location_type', 'town')
                ->where('home_location_id', $locationId)->count(),
            'barony' => \App\Models\User::where('home_location_type', 'barony')
                ->where('home_location_id', $locationId)->count(),
            'kingdom' => \App\Models\User::where('home_location_type', 'kingdom')
                ->where('home_location_id', $locationId)->count(),
            default => 0,
        };
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
        // Check if user already holds ANY role anywhere (one role per player globally)
        // Auto-resign from existing role if they have one
        $existingAnyRole = PlayerRole::where('user_id', $user->id)
            ->active()
            ->with('role')
            ->first();

        if ($existingAnyRole) {
            // Auto-resign from current role before claiming new one
            $existingAnyRole->resign();
            $this->activateNpcIfNeeded($existingAnyRole->role_id, $existingAnyRole->location_type, $existingAnyRole->location_id);
            $this->clearLocationRulerIfNeeded($existingAnyRole);
        }

        // Check if role is for this location type
        if ($role->location_type !== $locationType) {
            return [
                'success' => false,
                'message' => 'This role is not available at this type of location.',
            ];
        }

        // Check if role is active
        if (! $role->is_active) {
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
        if (! $role->hasAvailableSlots($locationType, $locationId)) {
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

            // Grant title matching the role
            $this->grantRoleTitle($user, $role, $locationType, $locationId);

            // Update the ruler user_id on the location model for primary ruler roles
            $this->updateLocationRuler($role->slug, $locationType, $locationId, $user->id);

            return [
                'success' => true,
                'message' => "{$user->username} has been appointed as {$role->name}!",
                'player_role' => $playerRole,
            ];
        });
    }

    /**
     * Role tier to appropriate social title mapping.
     * In medieval society, holding an office conferred a minimum social standing.
     */
    protected const ROLE_TIER_TO_TITLE = [
        1 => ['title' => 'peasant', 'tier' => 2],    // Menial roles - no elevation
        2 => ['title' => 'freeman', 'tier' => 3],     // Skilled tradesmen were free citizens
        3 => ['title' => 'freeman', 'tier' => 3],     // Guild officers, captains, professionals
        4 => ['title' => 'yeoman', 'tier' => 4],      // Civic leaders: mayors, elders, marshals
        5 => ['title' => 'baron', 'tier' => 8],       // Landed nobility
        6 => ['title' => 'duke', 'tier' => 12],       // High nobility
        7 => ['title' => 'king', 'tier' => 14],       // Royalty
    ];

    /**
     * Grant a title to a user based on their new role.
     * Maps the role's tier to an appropriate medieval social title.
     */
    protected function grantRoleTitle(User $user, Role $role, string $locationType, int $locationId): void
    {
        $mapping = self::ROLE_TIER_TO_TITLE[$role->tier] ?? self::ROLE_TIER_TO_TITLE[1];
        $title = $mapping['title'];
        $tier = $mapping['tier'];

        // Only upgrade title if new title tier is higher than current
        if ($tier <= ($user->title_tier ?? 2)) {
            return;
        }

        // Revoke existing active titles for this user
        PlayerTitle::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        // Create new title
        PlayerTitle::create([
            'user_id' => $user->id,
            'title' => $title,
            'tier' => $tier,
            'domain_type' => $locationType,
            'domain_id' => $locationId,
            'acquisition_method' => 'appointment',
            'is_active' => true,
            'granted_at' => now(),
            'legitimacy' => 50,
        ]);

        // Update user's primary title
        $user->primary_title = $title;
        $user->title_tier = $tier;
        $user->save();
    }

    /**
     * Revert a user's title back to peasant when they lose their role.
     */
    protected function revertTitle(User $user): void
    {
        // Revoke active titles
        PlayerTitle::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        $user->primary_title = 'peasant';
        $user->title_tier = 2;
        $user->save();
    }

    /**
     * Remove a user from a role.
     */
    public function removeFromRole(PlayerRole $playerRole, User $removedBy, ?string $reason = null): array
    {
        if (! $playerRole->isActive()) {
            return [
                'success' => false,
                'message' => 'This role assignment is not active.',
            ];
        }

        return DB::transaction(function () use ($playerRole, $removedBy, $reason) {
            $playerRole->remove($removedBy, $reason);

            // Revert title back to peasant
            $this->revertTitle($playerRole->user);

            // Reactivate NPC if exists
            $this->activateNpcIfNeeded($playerRole->role_id, $playerRole->location_type, $playerRole->location_id);

            // Clear the ruler user_id on the location model
            $this->clearLocationRulerIfNeeded($playerRole);

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

        if (! $playerRole->isActive()) {
            return [
                'success' => false,
                'message' => 'You do not currently hold this role.',
            ];
        }

        return DB::transaction(function () use ($playerRole) {
            $playerRole->resign();

            // Revert title back to peasant
            $this->revertTitle($playerRole->user);

            // Reactivate NPC if exists
            $this->activateNpcIfNeeded($playerRole->role_id, $playerRole->location_type, $playerRole->location_id);

            // Clear the ruler user_id on the location model
            $this->clearLocationRulerIfNeeded($playerRole);

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
        if (! $role) {
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
                'is_active' => ! $role->hasAvailableSlots($locationType, $locationId) ? false : true,
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

        if (! $playerHoldsRole) {
            LocationNpc::where('role_id', $roleId)
                ->where('location_type', $locationType)
                ->where('location_id', $locationId)
                ->update(['is_active' => true]);
        }
    }

    /**
     * Update the ruler user_id on the location model for primary ruler roles.
     */
    protected function updateLocationRuler(string $roleSlug, string $locationType, int $locationId, ?int $userId): void
    {
        $rulerColumn = match ($roleSlug) {
            'king' => 'king_user_id',
            'baron' => 'baron_user_id',
            'mayor' => 'mayor_user_id',
            default => null,
        };

        if (! $rulerColumn) {
            return;
        }

        $model = match ($locationType) {
            'kingdom' => Kingdom::find($locationId),
            'barony' => Barony::find($locationId),
            'town' => Town::find($locationId),
            default => null,
        };

        if ($model && isset($model->$rulerColumn) || $model) {
            $model->update([$rulerColumn => $userId]);
        }
    }

    /**
     * Clear the ruler user_id when a player resigns from a ruler role.
     */
    protected function clearLocationRulerIfNeeded(PlayerRole $playerRole): void
    {
        $role = $playerRole->role;
        if (! $role) {
            return;
        }

        $this->updateLocationRuler($role->slug, $playerRole->location_type, $playerRole->location_id, null);
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
                'title' => $playerRole->user->primary_title ?? 'peasant',
                'social_class' => $playerRole->user->social_class ?? 'serf',
                'total_level' => $playerRole->user->total_level ?? 3,
                'appointed_at' => $playerRole->appointed_at->toISOString(),
                'expires_at' => $playerRole->expires_at?->toISOString(),
                'total_salary_earned' => $playerRole->total_salary_earned,
            ] : null,
            'npc' => (! $playerRole && $npc) ? [
                'id' => $npc->id,
                'name' => $npc->npc_name,
                'description' => $npc->npc_description,
                'icon' => $npc->npc_icon,
            ] : null,
            'is_vacant' => ! $playerRole,
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
