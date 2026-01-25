<?php

namespace App\Http\Controllers;

use App\Models\PlayerInventory;
use App\Services\EnergyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function __construct(
        private EnergyService $energyService
    ) {}

    /**
     * Display the player dashboard.
     */
    public function dashboard(Request $request): Response
    {
        $player = $request->user();
        $player->load(['skills', 'homeVillage.barony.kingdom', 'inventory.item']);

        // Get current location info
        $currentLocation = $this->getCurrentLocationInfo($player);

        // Build inventory slots array
        $inventorySlots = array_fill(0, PlayerInventory::MAX_SLOTS, null);
        foreach ($player->inventory as $slot) {
            if ($slot->slot_number >= 0 && $slot->slot_number < PlayerInventory::MAX_SLOTS) {
                $inventorySlots[$slot->slot_number] = [
                    'id' => $slot->id,
                    'item' => [
                        'id' => $slot->item->id,
                        'name' => $slot->item->name,
                        'description' => $slot->item->description,
                        'type' => $slot->item->type,
                        'subtype' => $slot->item->subtype,
                        'rarity' => $slot->item->rarity,
                        'stackable' => $slot->item->stackable,
                        'equipment_slot' => $slot->item->equipment_slot,
                        'atk_bonus' => $slot->item->atk_bonus,
                        'str_bonus' => $slot->item->str_bonus,
                        'def_bonus' => $slot->item->def_bonus,
                        'hp_bonus' => $slot->item->hp_bonus,
                        'base_value' => $slot->item->base_value,
                    ],
                    'quantity' => $slot->quantity,
                    'is_equipped' => $slot->is_equipped,
                ];
            }
        }

        return Inertia::render('dashboard', [
            'player' => [
                'id' => $player->id,
                'username' => $player->username,
                'gender' => $player->gender,
                'hp' => $player->hp,
                'max_hp' => $player->max_hp,
                'energy' => $player->energy,
                'max_energy' => $player->max_energy,
                'gold' => $player->gold,
                'combat_level' => $player->combat_level,
                'is_traveling' => $player->is_traveling,
            ],
            'skills' => $player->skills->map(fn ($skill) => [
                'name' => $skill->skill_name,
                'level' => $skill->level,
                'xp' => $skill->xp,
                'xp_to_next' => $skill->xpToNextLevel(),
                'xp_progress' => $skill->getXpProgress(),
            ]),
            'energy_info' => $this->energyService->getRegenInfo($player),
            'current_location' => $currentLocation,
            'home_village' => $player->homeVillage ? [
                'id' => $player->homeVillage->id,
                'name' => $player->homeVillage->name,
                'barony' => $player->homeVillage->barony ? [
                    'id' => $player->homeVillage->barony->id,
                    'name' => $player->homeVillage->barony->name,
                ] : null,
                'kingdom' => $player->homeVillage->barony?->kingdom ? [
                    'id' => $player->homeVillage->barony->kingdom->id,
                    'name' => $player->homeVillage->barony->kingdom->name,
                ] : null,
            ] : null,
            'inventory' => [
                'slots' => $inventorySlots,
                'max_slots' => PlayerInventory::MAX_SLOTS,
            ],
        ]);
    }

    /**
     * Get the player's current location info.
     */
    private function getCurrentLocationInfo($player): ?array
    {
        if (!$player->current_location_type || !$player->current_location_id) {
            return null;
        }

        $modelClass = match ($player->current_location_type) {
            'village' => \App\Models\Village::class,
            'barony' => \App\Models\Barony::class,
            'town' => \App\Models\Town::class,
            'kingdom' => \App\Models\Kingdom::class,
            default => null,
        };

        if (!$modelClass) {
            return null;
        }

        $location = $modelClass::find($player->current_location_id);

        if (!$location) {
            return null;
        }

        return [
            'type' => $player->current_location_type,
            'id' => $location->id,
            'name' => $location->name,
            'biome' => $location->biome,
        ];
    }

    /**
     * Get player stats (API endpoint).
     */
    public function stats(Request $request)
    {
        $player = $request->user();
        $player->load('skills');

        return response()->json([
            'hp' => $player->hp,
            'max_hp' => $player->max_hp,
            'energy' => $player->energy,
            'max_energy' => $player->max_energy,
            'gold' => $player->gold,
            'combat_level' => $player->combat_level,
            'skills' => $player->skills->mapWithKeys(fn ($skill) => [
                $skill->skill_name => [
                    'level' => $skill->level,
                    'xp' => $skill->xp,
                ],
            ]),
            'energy_info' => $this->energyService->getRegenInfo($player),
        ]);
    }
}
