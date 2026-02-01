<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use App\Services\CraftingService;
use Illuminate\Http\JsonResponse;
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

        $info = $this->craftingService->getCraftingInfo($user);
        $smithingSkill = $user->skills()->where('skill_name', 'smithing')->first();
        $smithingLevel = $smithingSkill?->level ?? 1;

        // Filter and organize smithing recipes by metal tier
        $smithingRecipes = $info['all_recipes']['smithing'] ?? [];
        $forgeRecipes = $this->organizeByMetalTier($smithingRecipes);

        // Get bar recipes separately
        $barRecipes = array_filter($smithingRecipes, fn ($r) => str_ends_with($r['name'], ' Bar'));

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
                'recipes_by_tier' => $forgeRecipes,
                'bar_recipes' => array_values($barRecipes),
                'player_energy' => $info['player_energy'],
                'max_energy' => $info['max_energy'],
                'free_slots' => $info['free_slots'],
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
     * Forge an item (same as craft but for forge UI).
     */
    public function forge(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): JsonResponse
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

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Organize recipes by metal tier.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function organizeByMetalTier(array $recipes): array
    {
        $organized = [];

        foreach (self::METAL_TIERS as $metal => $tierData) {
            $organized[$metal] = [
                'weapons' => [],
                'armor' => [],
                'ammunition' => [],
            ];
        }

        foreach ($recipes as $recipe) {
            // Skip bar recipes
            if (str_ends_with($recipe['name'], ' Bar')) {
                continue;
            }

            // Determine metal tier from recipe name
            $metal = $this->extractMetalFromName($recipe['name']);
            if (! $metal || ! isset($organized[$metal])) {
                continue;
            }

            // Categorize by item type
            $category = $this->categorizeItem($recipe['name']);
            if (isset($organized[$metal][$category])) {
                $organized[$metal][$category][] = $recipe;
            }
        }

        return $organized;
    }

    /**
     * Extract metal tier from item name.
     */
    protected function extractMetalFromName(string $name): ?string
    {
        foreach (array_keys(self::METAL_TIERS) as $metal) {
            if (str_starts_with($name, $metal.' ')) {
                return $metal;
            }
        }

        return null;
    }

    /**
     * Categorize an item as weapon, armor, or ammunition.
     */
    protected function categorizeItem(string $name): string
    {
        $armorTypes = ['Helm', 'Shield', 'Chainbody', 'Platebody', 'Platelegs', 'Plateskirt'];
        $ammoTypes = ['Tips', 'Arrowtips', 'Throwing Knives'];

        foreach ($armorTypes as $type) {
            if (str_contains($name, $type)) {
                return 'armor';
            }
        }

        foreach ($ammoTypes as $type) {
            if (str_contains($name, $type)) {
                return 'ammunition';
            }
        }

        return 'weapons';
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
