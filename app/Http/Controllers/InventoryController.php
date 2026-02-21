<?php

namespace App\Http\Controllers;

use App\Models\PlayerInventory;
use App\Services\FoodConsumptionService;
use App\Services\PotionBuffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        protected FoodConsumptionService $foodConsumptionService
    ) {}

    /**
     * Display the player's inventory.
     */
    public function index(Request $request): Response
    {
        $player = $request->user();
        $inventory = $player->inventory()->with('item')->get();

        // Create array with nulls for empty slots
        $slots = array_fill(0, PlayerInventory::MAX_SLOTS, null);

        foreach ($inventory as $slot) {
            if ($slot->slot_number >= 0 && $slot->slot_number < PlayerInventory::MAX_SLOTS) {
                $slots[$slot->slot_number] = [
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
                        'energy_bonus' => $slot->item->energy_bonus,
                        'base_value' => $slot->item->base_value,
                        'required_level' => $slot->item->required_level,
                        'required_skill' => $slot->item->required_skill,
                        'required_skill_level' => $slot->item->required_skill_level,
                    ],
                    'quantity' => $slot->quantity,
                    'is_equipped' => $slot->is_equipped,
                ];
            }
        }

        // Check if player is at a village or town (for donate feature)
        $canDonate = in_array($player->current_location_type, ['village', 'town']);

        // Get equipped items organized by slot type
        $equippedItems = $inventory->where('is_equipped', true);
        $equipment = [];
        $equipmentSlots = ['head', 'amulet', 'chest', 'legs', 'weapon', 'shield', 'ring', 'necklace', 'bracelet'];

        foreach ($equipmentSlots as $slotType) {
            $equipped = $equippedItems->first(fn ($inv) => $inv->item->equipment_slot === $slotType);
            $equipment[$slotType] = $equipped ? [
                'slot_number' => $equipped->slot_number,
                'item' => [
                    'id' => $equipped->item->id,
                    'name' => $equipped->item->name,
                    'description' => $equipped->item->description,
                    'type' => $equipped->item->type,
                    'subtype' => $equipped->item->subtype,
                    'rarity' => $equipped->item->rarity,
                    'atk_bonus' => $equipped->item->atk_bonus,
                    'str_bonus' => $equipped->item->str_bonus,
                    'def_bonus' => $equipped->item->def_bonus,
                    'hp_bonus' => $equipped->item->hp_bonus,
                    'energy_bonus' => $equipped->item->energy_bonus,
                ],
            ] : null;
        }

        // Calculate combat stats
        $totalAtkBonus = $equippedItems->sum(fn ($inv) => $inv->item->atk_bonus);
        $totalStrBonus = $equippedItems->sum(fn ($inv) => $inv->item->str_bonus);
        $totalDefBonus = $equippedItems->sum(fn ($inv) => $inv->item->def_bonus);
        $totalHpBonus = $equippedItems->sum(fn ($inv) => $inv->item->hp_bonus);

        $combatStats = [
            'attack_level' => $player->getSkillLevel('attack'),
            'strength_level' => $player->getSkillLevel('strength'),
            'defense_level' => $player->getSkillLevel('defense'),
            'hitpoints_level' => $player->getSkillLevel('hitpoints'),
            'atk_bonus' => $totalAtkBonus,
            'str_bonus' => $totalStrBonus,
            'def_bonus' => $totalDefBonus,
            'hp_bonus' => $totalHpBonus,
        ];

        return Inertia::render('inventory', [
            'slots' => $slots,
            'max_slots' => PlayerInventory::MAX_SLOTS,
            'gold' => $player->gold,
            'can_donate' => $canDonate,
            'equipment' => $equipment,
            'combat_stats' => $combatStats,
        ]);
    }

    /**
     * Move an item to a different slot.
     */
    public function move(Request $request)
    {
        $request->validate([
            'from_slot' => 'required|integer|min:0|max:'.(PlayerInventory::MAX_SLOTS - 1),
            'to_slot' => 'required|integer|min:0|max:'.(PlayerInventory::MAX_SLOTS - 1),
        ]);

        $player = $request->user();
        $fromSlot = $request->from_slot;
        $toSlot = $request->to_slot;

        if ($fromSlot === $toSlot) {
            return back();
        }

        // Swap slots - use transaction with row locking to prevent race conditions
        DB::transaction(function () use ($player, $fromSlot, $toSlot) {
            // Lock rows while reading to prevent race conditions
            $fromItem = $player->inventory()->where('slot_number', $fromSlot)->lockForUpdate()->first();
            $toItem = $player->inventory()->where('slot_number', $toSlot)->lockForUpdate()->first();

            if (! $fromItem) {
                return; // No item in source slot
            }

            if ($toItem) {
                // Move toItem to temp slot first
                $toItem->update(['slot_number' => -1]);
                // Move fromItem to target slot
                $fromItem->update(['slot_number' => $toSlot]);
                // Move toItem to fromSlot
                $toItem->update(['slot_number' => $fromSlot]);
            } else {
                // No swap needed, just move
                $fromItem->update(['slot_number' => $toSlot]);
            }
        });

        return back();
    }

    /**
     * Drop an item from inventory.
     */
    public function drop(Request $request)
    {
        $request->validate([
            'slot' => 'required|integer|min:0|max:'.(PlayerInventory::MAX_SLOTS - 1),
            'quantity' => 'nullable|integer|min:1',
        ]);

        $player = $request->user();
        $slot = $player->inventory()->where('slot_number', $request->slot)->first();

        if (! $slot) {
            return back()->withErrors(['error' => 'No item in that slot.']);
        }

        $quantity = $request->quantity ?? $slot->quantity;

        if ($quantity >= $slot->quantity) {
            $slot->delete();
        } else {
            $slot->decrement('quantity', $quantity);
        }

        return back();
    }

    /**
     * Equip an item.
     */
    public function equip(Request $request)
    {
        $request->validate([
            'slot' => 'required|integer|min:0|max:'.(PlayerInventory::MAX_SLOTS - 1),
        ]);

        $player = $request->user();
        $slot = $player->inventory()->with('item')->where('slot_number', $request->slot)->first();

        if (! $slot || ! $slot->item->equipment_slot) {
            return back()->withErrors(['error' => 'Cannot equip this item.']);
        }

        // Check level requirements
        $item = $slot->item;
        if ($item->required_level) {
            // Determine which skill to check
            $skillToCheck = $item->required_skill;
            if (! $skillToCheck) {
                // Default: weapons require attack, everything else requires defense
                $skillToCheck = $item->equipment_slot === 'weapon' ? 'attack' : 'defense';
            }

            $playerLevel = $player->getSkillLevel($skillToCheck);
            if ($playerLevel < $item->required_level) {
                return back()->withErrors([
                    'error' => "You need {$item->required_level} ".ucfirst($skillToCheck).' to equip this item.',
                ]);
            }
        }

        // Unequip any item in the same equipment slot
        $player->inventory()
            ->whereHas('item', fn ($q) => $q->where('equipment_slot', $slot->item->equipment_slot))
            ->where('is_equipped', true)
            ->update(['is_equipped' => false]);

        // Equip the new item
        $slot->update(['is_equipped' => true]);

        return back();
    }

    /**
     * Unequip an item.
     */
    public function unequip(Request $request)
    {
        $request->validate([
            'slot' => 'required|integer|min:0|max:'.(PlayerInventory::MAX_SLOTS - 1),
        ]);

        $player = $request->user();
        $slot = $player->inventory()->where('slot_number', $request->slot)->first();

        if (! $slot || ! $slot->is_equipped) {
            return back()->withErrors(['error' => 'Item is not equipped.']);
        }

        $slot->update(['is_equipped' => false]);

        return back();
    }

    /**
     * Consume an item (eat food, drink potion, use medical supplies).
     */
    public function consume(Request $request, PotionBuffService $potionBuffService)
    {
        $request->validate([
            'slot' => 'required|integer|min:0|max:'.(PlayerInventory::MAX_SLOTS - 1),
        ]);

        $player = $request->user();
        $slot = $player->inventory()->with('item')->where('slot_number', $request->slot)->first();

        if (! $slot) {
            return back()->withErrors(['error' => 'No item in that slot.']);
        }

        $item = $slot->item;

        if (! $item->isConsumable()) {
            return back()->withErrors(['error' => 'This item cannot be consumed.']);
        }

        // Check if this is a buff potion
        if ($potionBuffService->isBuffPotion($item)) {
            $result = $potionBuffService->consumePotion($player, $slot->id);

            if ($result['success']) {
                $buffs = implode(', ', $result['buffs_applied']);

                return back()->with('success', "{$result['message']} {$buffs} for {$result['duration_minutes']} minutes.");
            }

            return back()->withErrors(['error' => $result['message']]);
        }

        // Check if item has any effect (for non-buff consumables)
        if ($item->hp_bonus <= 0 && $item->energy_bonus <= 0) {
            return back()->withErrors(['error' => 'This item has no consumable effect.']);
        }

        $messages = [];

        // Apply HP restoration
        if ($item->hp_bonus > 0) {
            $oldHp = $player->hp;
            $newHp = min($player->hp + $item->hp_bonus, $player->max_hp);
            $healed = $newHp - $oldHp;

            if ($healed > 0) {
                $player->hp = $newHp;
                $messages[] = "Restored {$healed} HP";
            } else {
                $messages[] = 'HP already full';
            }
        }

        // Apply energy restoration
        if ($item->energy_bonus > 0) {
            $oldEnergy = $player->energy;
            $newEnergy = min($player->energy + $item->energy_bonus, $player->max_energy);
            $restored = $newEnergy - $oldEnergy;

            if ($restored > 0) {
                $player->energy = $newEnergy;
                $messages[] = "Restored {$restored} energy";
            } else {
                $messages[] = 'Energy already full';
            }
        }

        $player->save();

        // Remove one item from the stack
        if ($slot->quantity > 1) {
            $slot->decrement('quantity');
        } else {
            $slot->delete();
        }

        $message = "Used {$item->name}. ".implode(', ', $messages).'.';

        return back()->with('success', $message);
    }

    /**
     * Donate food items to the village/town granary.
     */
    public function donate(Request $request)
    {
        $request->validate([
            'slot' => 'required|integer|min:0|max:'.(PlayerInventory::MAX_SLOTS - 1),
            'quantity' => 'nullable|integer|min:1',
        ]);

        $player = $request->user();

        // Check if player is at a village or town
        if (! $player->current_location_type || ! in_array($player->current_location_type, ['village', 'town'])) {
            return back()->withErrors(['error' => 'You must be at a village or town to donate food.']);
        }

        $slot = $player->inventory()->with('item')->where('slot_number', $request->slot)->first();

        if (! $slot) {
            return back()->withErrors(['error' => 'No item in that slot.']);
        }

        $item = $slot->item;

        // Check if item can be donated (food or crop types)
        $donateableSubtypes = ['food', 'crop', 'grain'];
        if (! in_array($item->subtype, $donateableSubtypes)) {
            return back()->withErrors(['error' => 'Only food and crop items can be donated to the granary.']);
        }

        $quantity = min($request->quantity ?? $slot->quantity, $slot->quantity);

        // Add to granary via FoodConsumptionService (respects capacity, stores as grain)
        $added = $this->foodConsumptionService->addFoodToLocation(
            $player->current_location_type,
            $player->current_location_id,
            $quantity
        );

        if ($added <= 0) {
            return back()->withErrors(['error' => 'The granary is full.']);
        }

        // Remove donated amount from player inventory
        if ($added >= $slot->quantity) {
            $slot->delete();
        } else {
            $slot->decrement('quantity', $added);
        }

        return back()->with('success', "Donated {$added} {$item->name} to the granary!");
    }
}
