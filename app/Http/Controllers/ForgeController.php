<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\PlayerInventory;
use App\Models\Town;
use App\Models\Village;
use App\Services\CraftingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ForgeController extends Controller
{
    /**
     * Metal tiers configuration for the forge UI.
     */
    private const METAL_TIERS = [
        'Bronze' => ['base_level' => 1, 'color' => 'orange'],
        'Iron' => ['base_level' => 15, 'color' => 'gray'],
        'Steel' => ['base_level' => 30, 'color' => 'slate'],
        'Gold' => ['base_level' => 40, 'color' => 'yellow'],
        'Mithril' => ['base_level' => 45, 'color' => 'blue'],
        'Celestial' => ['base_level' => 60, 'color' => 'purple'],
        'Oria' => ['base_level' => 75, 'color' => 'amber'],
    ];

    public function __construct(
        protected CraftingService $craftingService
    ) {}

    /**
     * Show the forge page (location-scoped).
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        if (! $this->craftingService->canCraft($user)) {
            return Inertia::render('Forge/NotAvailable', [
                'message' => 'You cannot access the forge at your current location.',
            ]);
        }

        $smithingSkill = $user->skills()->where('skill_name', 'smithing')->first();
        $smithingLevel = $smithingSkill?->level ?? 1;

        // Get smelting (bar) recipes only for the forge (bypass workshop filter)
        $smeltingRecipes = $this->craftingService->getAllRecipes($user, ['smelting'])['smelting'] ?? [];

        // Get bars in inventory grouped by type
        $barsInInventory = $user->inventory()
            ->whereHas('item', fn ($q) => $q->where('name', 'like', '% Bar'))
            ->with('item:id,name')
            ->get()
            ->map(fn ($inv) => [
                'name' => $inv->item->name,
                'quantity' => $inv->quantity,
                'metal' => str_replace(' Bar', '', $inv->item->name),
            ])
            ->sortBy(fn ($bar) => array_search($bar['metal'], array_keys(self::METAL_TIERS)) ?? 999)
            ->values()
            ->toArray();

        $barCount = array_sum(array_column($barsInInventory, 'quantity'));

        $data = [
            'forge_info' => [
                'can_forge' => true,
                'metal_tiers' => $this->getMetalTiersInfo($smithingLevel),
                'smelting_recipes' => array_values($smeltingRecipes),
                'player_energy' => $user->energy,
                'max_energy' => $user->max_energy,
                'free_slots' => PlayerInventory::MAX_SLOTS - $user->inventory()->count(),
                'bar_count' => (int) $barCount,
                'bars_in_inventory' => $barsInInventory,
                'smithing_level' => $smithingLevel,
                'smithing_xp' => $smithingSkill?->xp ?? 0,
                'smithing_xp_progress' => $smithingSkill?->getXpProgress() ?? 0,
                'smithing_xp_to_next' => $smithingSkill?->xpToNextLevel() ?? 60,
            ],
        ];

        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('Forge/Index', $data);
    }

    /**
     * Smelt a bar at the forge.
     */
    public function forge(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): RedirectResponse
    {
        $request->validate([
            'recipe' => 'required|string',
        ]);

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        $user = $request->user();
        $result = $this->craftingService->craft(
            $user,
            $request->input('recipe'),
            $locationType ?? $user->current_location_type,
            $location?->id ?? $user->current_location_id
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Get metal tiers info with unlock status.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getMetalTiersInfo(int $smithingLevel): array
    {
        $tiers = [];

        foreach (self::METAL_TIERS as $metal => $data) {
            $tiers[$metal] = [
                'name' => $metal,
                'base_level' => $data['base_level'],
                'color' => $data['color'],
                'unlocked' => $smithingLevel >= $data['base_level'],
            ];
        }

        return $tiers;
    }

    /**
     * Determine location type from model.
     */
    protected function getLocationType($location): ?string
    {
        return match (true) {
            $location instanceof Village => 'village',
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }
}
