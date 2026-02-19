<?php

namespace App\Http\Controllers;

use App\Models\Accusation;
use App\Models\Charter;
use App\Models\ManumissionRequest;
use App\Models\MigrationRequest;
use App\Models\PlayerRole;
use App\Models\Punishment;
use App\Models\RolePetition;
use App\Models\Trial;
use App\Models\Village;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleDutiesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $activeRole = PlayerRole::where('user_id', $user->id)
            ->active()
            ->with('role')
            ->first();

        if (! $activeRole) {
            return Inertia::render('Roles/Duties', [
                'role' => null,
                'categories' => [],
            ]);
        }

        $slug = $activeRole->role->slug;
        $locationType = $activeRole->location_type;
        $locationId = $activeRole->location_id;
        $categories = [];

        // Elder duties
        if ($slug === 'elder') {
            $categories[] = [
                'key' => 'migrations',
                'label' => 'Migration Requests',
                'description' => 'Players requesting to settle in your village',
                'count' => MigrationRequest::where('status', 'pending')
                    ->where('to_village_id', $locationId)
                    ->whereNull('elder_decided_at')
                    ->count(),
                'href' => '/migration',
                'icon' => 'map-pin',
            ];

            $categories[] = [
                'key' => 'accusations',
                'label' => 'Accusations',
                'description' => 'Criminal accusations requiring review',
                'count' => Accusation::where('status', 'pending')
                    ->where('location_type', 'village')
                    ->where('location_id', $locationId)
                    ->count(),
                'href' => '/crime/accusations',
                'icon' => 'gavel',
            ];

            $categories[] = $this->petitionsCategory($activeRole);
        }

        // Mayor duties
        if ($slug === 'mayor') {
            $categories[] = [
                'key' => 'accusations',
                'label' => 'Accusations',
                'description' => 'Criminal accusations at town level',
                'count' => Accusation::where('status', 'pending')
                    ->where('location_type', 'town')
                    ->where('location_id', $locationId)
                    ->count(),
                'href' => '/crime/accusations',
                'icon' => 'gavel',
            ];

            $categories[] = $this->petitionsCategory($activeRole);
        }

        // Baron duties
        if ($slug === 'baron') {
            $villageIds = Village::where('barony_id', $locationId)->pluck('id');

            $categories[] = [
                'key' => 'migrations',
                'label' => 'Migration Requests',
                'description' => 'Migration requests approved by elders, awaiting your approval',
                'count' => MigrationRequest::where('status', 'pending')
                    ->whereIn('to_village_id', $villageIds)
                    ->whereNotNull('elder_decided_at')
                    ->whereNull('baron_decided_at')
                    ->count(),
                'href' => '/migration',
                'icon' => 'map-pin',
            ];

            $categories[] = [
                'key' => 'manumissions',
                'label' => 'Manumission Requests',
                'description' => 'Serfs requesting freedom from bondage',
                'count' => ManumissionRequest::where('status', 'pending')
                    ->where('barony_id', $locationId)
                    ->count(),
                'href' => '/social-class/manumission-requests',
                'icon' => 'hand-helping',
            ];

            $categories[] = [
                'key' => 'accusations',
                'label' => 'Accusations',
                'description' => 'Criminal accusations at barony level',
                'count' => Accusation::where('status', 'pending')
                    ->where('location_type', 'barony')
                    ->where('location_id', $locationId)
                    ->count(),
                'href' => '/crime/accusations',
                'icon' => 'gavel',
            ];

            $categories[] = $this->petitionsCategory($activeRole);
        }

        // King duties
        if ($slug === 'king') {
            $categories[] = [
                'key' => 'charters',
                'label' => 'Charter Requests',
                'description' => 'Settlement charters awaiting royal approval',
                'count' => Charter::where('status', 'pending')
                    ->where('kingdom_id', $locationId)
                    ->count(),
                'href' => '/kingdoms/'.$locationId.'/charters',
                'icon' => 'scroll-text',
            ];

            $ennoblementCount = 0;
            if (class_exists(\App\Models\EnnoblementRequest::class)) {
                $ennoblementCount = \App\Models\EnnoblementRequest::where('status', 'pending')
                    ->where('king_id', $user->id)
                    ->count();
            }

            $categories[] = [
                'key' => 'ennoblements',
                'label' => 'Ennoblement Requests',
                'description' => 'Citizens seeking noble status',
                'count' => $ennoblementCount,
                'href' => '/social-class/ennoblement-requests',
                'icon' => 'crown',
            ];

            $categories[] = $this->petitionsCategory($activeRole);
        }

        // Guard Captain duties
        if (in_array($slug, ['guard_captain', 'town_guard_captain'])) {
            $categories[] = [
                'key' => 'accusations',
                'label' => 'Accusations',
                'description' => 'Criminal accusations to investigate',
                'count' => Accusation::where('status', 'pending')
                    ->where('location_type', $locationType)
                    ->where('location_id', $locationId)
                    ->count(),
                'href' => '/crime/accusations',
                'icon' => 'shield-alert',
            ];
        }

        // Magistrate duties
        if ($slug === 'magistrate') {
            $categories[] = [
                'key' => 'accusations',
                'label' => 'Accusations',
                'description' => 'Criminal accusations awaiting court review',
                'count' => Accusation::where('status', 'pending')
                    ->where('location_type', 'town')
                    ->where('location_id', $locationId)
                    ->count(),
                'href' => '/crime/accusations',
                'icon' => 'gavel',
            ];

            $categories[] = [
                'key' => 'trials',
                'label' => 'Pending Trials',
                'description' => 'Trials requiring your attention',
                'count' => Trial::whereIn('status', ['scheduled', 'in_progress', 'awaiting_verdict'])
                    ->where('location_type', 'town')
                    ->where('location_id', $locationId)
                    ->count(),
                'href' => '/crime/trials',
                'icon' => 'scale',
            ];
        }

        // Jailsman duties
        if ($slug === 'jailsman') {
            $categories[] = [
                'key' => 'punishments',
                'label' => 'Pending Punishments',
                'description' => 'Sentences awaiting execution',
                'count' => Punishment::where('status', 'pending')
                    ->where('location_type', $locationType)
                    ->where('location_id', $locationId)
                    ->count(),
                'href' => '/crime',
                'icon' => 'lock',
            ];
        }

        return Inertia::render('Roles/Duties', [
            'role' => [
                'name' => $activeRole->role->name,
                'slug' => $activeRole->role->slug,
                'location_name' => $activeRole->location_name,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Build the petitions category for authority roles.
     *
     * @return array{key: string, label: string, description: string, count: int, href: string, icon: string}
     */
    private function petitionsCategory(PlayerRole $activeRole): array
    {
        return [
            'key' => 'petitions',
            'label' => 'Role Petitions',
            'description' => 'Citizens challenging appointed role holders',
            'count' => RolePetition::where('authority_user_id', $activeRole->user_id)
                ->where('status', 'pending')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count(),
            'href' => '/roles/petitions',
            'icon' => 'scroll',
        ];
    }
}
