<?php

namespace App\Http\Controllers;

use App\Models\Castle;
use App\Models\Kingdom;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\Village;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * Display roles at a village.
     */
    public function villageRoles(Request $request, Village $village): Response
    {
        $user = $request->user();

        // Check if player is at this village
        if ($user->current_location_type !== 'village' || $user->current_location_id !== $village->id) {
            return Inertia::render('Roles/NotHere', [
                'location' => $village->name,
            ]);
        }

        return $this->renderRolesPage($user, 'village', $village->id, $village->name);
    }

    /**
     * Display roles at a castle.
     */
    public function castleRoles(Request $request, Castle $castle): Response
    {
        $user = $request->user();

        // Check if player is at this castle
        if ($user->current_location_type !== 'castle' || $user->current_location_id !== $castle->id) {
            return Inertia::render('Roles/NotHere', [
                'location' => $castle->name,
            ]);
        }

        return $this->renderRolesPage($user, 'castle', $castle->id, $castle->name);
    }

    /**
     * Display roles at a kingdom.
     */
    public function kingdomRoles(Request $request, Kingdom $kingdom): Response
    {
        $user = $request->user();

        return $this->renderRolesPage($user, 'kingdom', $kingdom->id, $kingdom->name);
    }

    /**
     * Render the roles page for any location type.
     */
    protected function renderRolesPage($user, string $locationType, int $locationId, string $locationName): Response
    {
        $roles = $this->roleService->getRolesAtLocation($locationType, $locationId);
        $userRoles = $this->roleService->getUserRoles($user);
        $userRolesHere = $userRoles->filter(
            fn ($r) => $r['location_type'] === $locationType && $r['location_id'] === $locationId
        )->values();

        return Inertia::render('Roles/Index', [
            'location_type' => $locationType,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'roles' => $roles,
            'user_roles' => $userRoles,
            'user_roles_here' => $userRolesHere,
            'player' => [
                'id' => $user->id,
                'username' => $user->username,
                'gold' => $user->gold,
                'title_tier' => $user->title_tier,
            ],
        ]);
    }

    /**
     * Get user's current roles.
     */
    public function myRoles(Request $request): Response
    {
        $user = $request->user();
        $userRoles = $this->roleService->getUserRoles($user);

        return Inertia::render('Roles/MyRoles', [
            'roles' => $userRoles,
            'player' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
        ]);
    }

    /**
     * Resign from a role.
     */
    public function resign(Request $request, PlayerRole $playerRole): RedirectResponse
    {
        $user = $request->user();
        $result = $this->roleService->resignFromRole($user, $playerRole);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Appoint someone to a role (for authorized users).
     */
    public function appoint(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'location_type' => 'required|in:village,castle,kingdom',
            'location_id' => 'required|integer',
        ]);

        $currentUser = $request->user();
        $targetUser = \App\Models\User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        // Check if current user has permission to appoint
        // For now, only admins or higher-tier role holders can appoint
        $canAppoint = $currentUser->isAdmin() || $this->roleService->hasPermission(
            $currentUser,
            'appoint_roles',
            $request->location_type,
            $request->location_id
        );

        if (!$canAppoint) {
            return back()->with('error', 'You do not have permission to appoint roles.');
        }

        $result = $this->roleService->appointRole(
            $targetUser,
            $role,
            $request->location_type,
            $request->location_id,
            $currentUser
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Remove someone from a role (for authorized users).
     */
    public function remove(Request $request, PlayerRole $playerRole): RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $currentUser = $request->user();

        // Check if current user has permission to remove
        $canRemove = $currentUser->isAdmin() || $this->roleService->hasPermission(
            $currentUser,
            'remove_roles',
            $playerRole->location_type,
            $playerRole->location_id
        );

        if (!$canRemove) {
            return back()->with('error', 'You do not have permission to remove this role holder.');
        }

        $result = $this->roleService->removeFromRole($playerRole, $currentUser, $request->reason);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Get roles status (for API/sidebar).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRoles = $this->roleService->getUserRoles($user);

        return response()->json([
            'roles' => $userRoles,
            'count' => $userRoles->count(),
        ]);
    }
}
