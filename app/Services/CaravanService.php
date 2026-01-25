<?php

namespace App\Services;

use App\Models\Caravan;
use App\Models\CaravanEvent;
use App\Models\CaravanGoods;
use App\Models\Item;
use App\Models\LocationTreasury;
use App\Models\TariffCollection;
use App\Models\TradeTariff;
use App\Models\TradeRoute;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CaravanService
{
    // Base costs
    public const CARAVAN_CREATION_COST = 1000;
    public const GUARD_HIRE_COST = 50;
    public const BASE_CAPACITY = 100;

    // NPC merchant names
    protected array $merchantFirstNames = [
        'Aldric', 'Bertram', 'Cedric', 'Edmund', 'Geoffrey',
        'Harald', 'Ivan', 'Jasper', 'Klaus', 'Lothar',
        'Magnus', 'Nikolai', 'Otto', 'Percival', 'Quentin',
        'Roland', 'Sigmund', 'Thaddeus', 'Ulric', 'Viktor',
    ];

    protected array $merchantLastNames = [
        'Goldweaver', 'Silktrader', 'Ironmonger', 'Coinsworth', 'Merchant',
        'Fairprice', 'Goodbargain', 'Richcart', 'Wealthton', 'Prosperby',
    ];

    /**
     * Create a new player caravan.
     */
    public function createCaravan(
        User $owner,
        string $name,
        string $locationType,
        int $locationId,
        int $guards = 0
    ): array {
        $totalCost = self::CARAVAN_CREATION_COST + ($guards * self::GUARD_HIRE_COST);

        if ($owner->gold < $totalCost) {
            return [
                'success' => false,
                'message' => "Insufficient gold. Need {$totalCost} gold to create caravan with {$guards} guards.",
            ];
        }

        return DB::transaction(function () use ($owner, $name, $locationType, $locationId, $guards, $totalCost) {
            $owner->decrement('gold', $totalCost);

            $caravan = Caravan::create([
                'name' => $name,
                'owner_id' => $owner->id,
                'current_location_type' => $locationType,
                'current_location_id' => $locationId,
                'destination_type' => $locationType,
                'destination_id' => $locationId,
                'status' => Caravan::STATUS_PREPARING,
                'capacity' => self::BASE_CAPACITY,
                'guards' => $guards,
                'is_npc' => false,
            ]);

            return [
                'success' => true,
                'message' => "Caravan '{$name}' created successfully.",
                'caravan' => $caravan,
            ];
        });
    }

    /**
     * Create an NPC caravan for the simulation.
     */
    public function createNpcCaravan(
        string $originType,
        int $originId,
        string $destinationType,
        int $destinationId
    ): Caravan {
        $merchantName = $this->generateMerchantName();

        return Caravan::create([
            'name' => "{$merchantName}'s Caravan",
            'owner_id' => null,
            'current_location_type' => $originType,
            'current_location_id' => $originId,
            'destination_type' => $destinationType,
            'destination_id' => $destinationId,
            'status' => Caravan::STATUS_PREPARING,
            'capacity' => rand(50, 150),
            'guards' => rand(1, 5),
            'is_npc' => true,
            'npc_merchant_name' => $merchantName,
        ]);
    }

    /**
     * Load goods onto a caravan.
     */
    public function loadGoods(
        Caravan $caravan,
        Item $item,
        int $quantity,
        int $purchasePrice,
        ?User $owner = null
    ): array {
        if ($caravan->status !== Caravan::STATUS_PREPARING) {
            return [
                'success' => false,
                'message' => 'Can only load goods while caravan is preparing.',
            ];
        }

        if ($quantity > $caravan->remaining_capacity) {
            return [
                'success' => false,
                'message' => "Not enough capacity. Available: {$caravan->remaining_capacity} units.",
            ];
        }

        // For player caravans, check inventory
        if ($owner && !$caravan->is_npc) {
            $inventoryItem = $owner->inventory()->where('item_id', $item->id)->first();
            if (!$inventoryItem || $inventoryItem->quantity < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Insufficient items in inventory.',
                ];
            }

            // Remove from player inventory
            if ($inventoryItem->quantity === $quantity) {
                $inventoryItem->delete();
            } else {
                $inventoryItem->decrement('quantity', $quantity);
            }
        }

        // Check if goods of this type already exist in caravan
        $existingGoods = $caravan->goods()->where('item_id', $item->id)->first();

        if ($existingGoods) {
            // Update weighted average purchase price
            $totalQuantity = $existingGoods->quantity + $quantity;
            $newAvgPrice = (int) (
                (($existingGoods->quantity * $existingGoods->purchase_price) + ($quantity * $purchasePrice))
                / $totalQuantity
            );
            $existingGoods->update([
                'quantity' => $totalQuantity,
                'purchase_price' => $newAvgPrice,
            ]);
        } else {
            CaravanGoods::create([
                'caravan_id' => $caravan->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'purchase_price' => $purchasePrice,
                'origin_type' => $caravan->current_location_type,
                'origin_id' => $caravan->current_location_id,
            ]);
        }

        return [
            'success' => true,
            'message' => "Loaded {$quantity} {$item->name} onto caravan.",
        ];
    }

    /**
     * Set caravan destination and depart.
     */
    public function depart(
        Caravan $caravan,
        string $destinationType,
        int $destinationId,
        ?TradeRoute $route = null
    ): array {
        if (!$caravan->canDepart()) {
            return [
                'success' => false,
                'message' => 'Caravan cannot depart. Check status and goods.',
            ];
        }

        // Calculate travel time
        $travelDays = $route?->base_travel_days ?? $this->calculateTravelDays(
            $caravan->current_location_type,
            $caravan->current_location_id,
            $destinationType,
            $destinationId
        );

        return DB::transaction(function () use ($caravan, $destinationType, $destinationId, $route, $travelDays) {
            $caravan->update([
                'destination_type' => $destinationType,
                'destination_id' => $destinationId,
                'trade_route_id' => $route?->id,
                'status' => Caravan::STATUS_TRAVELING,
                'travel_progress' => 0,
                'travel_total' => $travelDays,
                'departed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "Caravan departed. Estimated travel time: {$travelDays} days.",
                'travel_days' => $travelDays,
            ];
        });
    }

    /**
     * Process daily travel tick for all traveling caravans.
     */
    public function processDailyTravel(): array
    {
        $results = [];

        Caravan::traveling()->each(function ($caravan) use (&$results) {
            $result = $this->processSingleCaravanDay($caravan);
            $results[] = [
                'caravan_id' => $caravan->id,
                'name' => $caravan->name,
                'result' => $result,
            ];
        });

        return $results;
    }

    /**
     * Process a single day of travel for a caravan.
     */
    protected function processSingleCaravanDay(Caravan $caravan): array
    {
        $events = [];

        // Roll for random events
        $event = $this->rollForEvent($caravan);
        if ($event) {
            $events[] = $event;
        }

        // Advance travel progress (may be modified by events)
        $daysAdvanced = 1 - ($event?->days_delayed ?? 0);
        if ($daysAdvanced > 0) {
            $caravan->increment('travel_progress', $daysAdvanced);
        }

        // Check if arrived
        if ($caravan->hasArrived()) {
            $arrivalResult = $this->handleArrival($caravan);
            $events[] = $arrivalResult['event'];

            return [
                'status' => 'arrived',
                'events' => $events,
            ];
        }

        return [
            'status' => 'traveling',
            'progress' => $caravan->travel_progress,
            'total' => $caravan->travel_total,
            'events' => $events,
        ];
    }

    /**
     * Roll for random travel events.
     */
    protected function rollForEvent(Caravan $caravan): ?CaravanEvent
    {
        $roll = rand(1, 100);
        $route = $caravan->tradeRoute;

        // Bandit attack
        $banditChance = $route?->effective_bandit_chance ?? 5;
        // Guards reduce bandit chance
        $banditChance = max(1, $banditChance - ($caravan->guards * 2));

        if ($roll <= $banditChance) {
            return $this->handleBanditAttack($caravan);
        }

        // Weather delay (5% chance)
        if ($roll <= $banditChance + 5) {
            return $this->handleWeatherDelay($caravan);
        }

        // Goods spoilage (3% chance)
        if ($roll <= $banditChance + 8) {
            return $this->handleGoodsSpoilage($caravan);
        }

        return null;
    }

    /**
     * Handle bandit attack event.
     */
    protected function handleBanditAttack(Caravan $caravan): CaravanEvent
    {
        $guardsLost = 0;
        $goldLost = 0;
        $goodsLost = 0;
        $daysDelayed = 1;

        // Guards fight bandits
        if ($caravan->guards > 0) {
            $defenseRoll = rand(1, $caravan->guards * 10);
            $attackRoll = rand(1, 50);

            if ($defenseRoll >= $attackRoll) {
                // Guards repelled the attack
                $guardsLost = rand(0, max(0, $caravan->guards - 1));
                $caravan->decrement('guards', $guardsLost);

                return CaravanEvent::create([
                    'caravan_id' => $caravan->id,
                    'event_type' => CaravanEvent::TYPE_BANDIT_ATTACK,
                    'description' => $guardsLost > 0
                        ? "Bandits attacked but guards repelled them. Lost {$guardsLost} guard(s) in the fight."
                        : 'Bandits attacked but guards successfully defended the caravan.',
                    'guards_lost' => $guardsLost,
                    'days_delayed' => $daysDelayed,
                ]);
            }
        }

        // Attack succeeded
        $goldLost = (int) ($caravan->gold_carried * (rand(20, 50) / 100));
        $goodsLost = (int) ($caravan->total_goods * (rand(10, 30) / 100));
        $guardsLost = $caravan->guards > 0 ? rand(1, $caravan->guards) : 0;

        // Apply losses
        if ($goldLost > 0) {
            $caravan->decrement('gold_carried', $goldLost);
        }
        if ($guardsLost > 0) {
            $caravan->decrement('guards', $guardsLost);
        }
        if ($goodsLost > 0) {
            $this->removeRandomGoods($caravan, $goodsLost);
        }

        return CaravanEvent::create([
            'caravan_id' => $caravan->id,
            'event_type' => CaravanEvent::TYPE_BANDIT_ATTACK,
            'description' => "Bandits raided the caravan! Lost {$goldLost} gold, {$goodsLost} goods, and {$guardsLost} guard(s).",
            'gold_lost' => $goldLost,
            'goods_lost' => $goodsLost,
            'guards_lost' => $guardsLost,
            'days_delayed' => $daysDelayed,
        ]);
    }

    /**
     * Handle weather delay event.
     */
    protected function handleWeatherDelay(Caravan $caravan): CaravanEvent
    {
        $daysDelayed = rand(1, 3);

        return CaravanEvent::create([
            'caravan_id' => $caravan->id,
            'event_type' => CaravanEvent::TYPE_WEATHER_DELAY,
            'description' => "Bad weather forced the caravan to halt for {$daysDelayed} day(s).",
            'days_delayed' => $daysDelayed,
        ]);
    }

    /**
     * Handle goods spoilage event.
     */
    protected function handleGoodsSpoilage(Caravan $caravan): CaravanEvent
    {
        $goodsLost = (int) ($caravan->total_goods * (rand(5, 15) / 100));

        if ($goodsLost > 0) {
            $this->removeRandomGoods($caravan, $goodsLost);
        }

        return CaravanEvent::create([
            'caravan_id' => $caravan->id,
            'event_type' => CaravanEvent::TYPE_GOODS_SPOILED,
            'description' => "Some goods spoiled during travel. Lost {$goodsLost} units.",
            'goods_lost' => $goodsLost,
        ]);
    }

    /**
     * Remove random goods from caravan.
     */
    protected function removeRandomGoods(Caravan $caravan, int $amount): void
    {
        $goods = $caravan->goods()->get();
        $remaining = $amount;

        foreach ($goods->shuffle() as $goodsItem) {
            if ($remaining <= 0) {
                break;
            }

            $toRemove = min($remaining, $goodsItem->quantity);
            $goodsItem->decrement('quantity', $toRemove);
            $remaining -= $toRemove;

            if ($goodsItem->quantity <= 0) {
                $goodsItem->delete();
            }
        }
    }

    /**
     * Handle caravan arrival at destination.
     */
    protected function handleArrival(Caravan $caravan): array
    {
        // Update location and status
        $caravan->update([
            'current_location_type' => $caravan->destination_type,
            'current_location_id' => $caravan->destination_id,
            'status' => Caravan::STATUS_ARRIVED,
            'arrived_at' => now(),
        ]);

        // Collect tariffs
        $tariffsPaid = $this->collectTariffs($caravan);

        $event = CaravanEvent::create([
            'caravan_id' => $caravan->id,
            'event_type' => CaravanEvent::TYPE_SAFE_ARRIVAL,
            'description' => "Caravan arrived safely at destination. Tariffs paid: {$tariffsPaid} gold.",
            'gold_lost' => $tariffsPaid,
            'metadata' => ['tariffs_paid' => $tariffsPaid],
        ]);

        return [
            'event' => $event,
            'tariffs_paid' => $tariffsPaid,
        ];
    }

    /**
     * Collect tariffs when entering a territory.
     */
    protected function collectTariffs(Caravan $caravan): int
    {
        $totalTariffs = 0;

        // Get applicable tariffs for destination
        $tariffs = TradeTariff::active()
            ->where(function ($query) use ($caravan) {
                // Check barony tariffs
                $query->where('location_type', 'barony')
                    ->orWhere('location_type', 'kingdom');
            })
            ->get();

        foreach ($caravan->goods as $goods) {
            foreach ($tariffs as $tariff) {
                if ($tariff->appliesToItem($goods->item_id)) {
                    $tariffAmount = $tariff->calculateTariff($goods->total_value);
                    $totalTariffs += $tariffAmount;

                    // Record collection
                    TariffCollection::create([
                        'caravan_id' => $caravan->id,
                        'trade_tariff_id' => $tariff->id,
                        'amount_collected' => $tariffAmount,
                        'location_type' => $tariff->location_type,
                        'location_id' => $tariff->location_id,
                    ]);

                    // Add to treasury
                    $treasury = LocationTreasury::firstOrCreate(
                        [
                            'location_type' => $tariff->location_type,
                            'location_id' => $tariff->location_id,
                        ],
                        ['balance' => 0]
                    );
                    $treasury->increment('balance', $tariffAmount);
                }
            }
        }

        // Deduct from caravan's carried gold
        if ($totalTariffs > 0) {
            $caravan->decrement('gold_carried', min($totalTariffs, $caravan->gold_carried));
        }

        return $totalTariffs;
    }

    /**
     * Sell goods at current location.
     */
    public function sellGoods(Caravan $caravan, int $itemId, int $quantity, int $salePrice): array
    {
        if ($caravan->status !== Caravan::STATUS_ARRIVED) {
            return [
                'success' => false,
                'message' => 'Can only sell goods when caravan has arrived.',
            ];
        }

        $goods = $caravan->goods()->where('item_id', $itemId)->first();

        if (!$goods || $goods->quantity < $quantity) {
            return [
                'success' => false,
                'message' => 'Insufficient goods to sell.',
            ];
        }

        $totalRevenue = $quantity * $salePrice;
        $totalCost = $quantity * $goods->purchase_price;
        $profit = $totalRevenue - $totalCost;

        return DB::transaction(function () use ($caravan, $goods, $quantity, $totalRevenue, $profit) {
            // Update goods
            if ($goods->quantity === $quantity) {
                $goods->delete();
            } else {
                $goods->decrement('quantity', $quantity);
            }

            // Add gold to caravan
            $caravan->increment('gold_carried', $totalRevenue);

            return [
                'success' => true,
                'message' => "Sold {$quantity} goods for {$totalRevenue} gold. Profit: {$profit} gold.",
                'revenue' => $totalRevenue,
                'profit' => $profit,
            ];
        });
    }

    /**
     * Disband caravan and return goods/gold to owner.
     */
    public function disbandCaravan(Caravan $caravan): array
    {
        if ($caravan->is_npc) {
            $caravan->update(['status' => Caravan::STATUS_DISBANDED]);
            return [
                'success' => true,
                'message' => 'NPC caravan disbanded.',
            ];
        }

        $owner = $caravan->owner;
        if (!$owner) {
            return [
                'success' => false,
                'message' => 'No owner found for caravan.',
            ];
        }

        return DB::transaction(function () use ($caravan, $owner) {
            // Return gold to owner
            if ($caravan->gold_carried > 0) {
                $owner->increment('gold', $caravan->gold_carried);
            }

            // Return goods to inventory
            foreach ($caravan->goods as $goods) {
                $existingInventory = $owner->inventory()->where('item_id', $goods->item_id)->first();
                if ($existingInventory) {
                    $existingInventory->increment('quantity', $goods->quantity);
                } else {
                    $owner->inventory()->create([
                        'item_id' => $goods->item_id,
                        'quantity' => $goods->quantity,
                    ]);
                }
            }

            // Delete goods and update status
            $caravan->goods()->delete();
            $caravan->update([
                'status' => Caravan::STATUS_DISBANDED,
                'gold_carried' => 0,
            ]);

            return [
                'success' => true,
                'message' => 'Caravan disbanded. Goods and gold returned to owner.',
            ];
        });
    }

    /**
     * Generate a random NPC merchant name.
     */
    protected function generateMerchantName(): string
    {
        $firstName = $this->merchantFirstNames[array_rand($this->merchantFirstNames)];
        $lastName = $this->merchantLastNames[array_rand($this->merchantLastNames)];

        return "{$firstName} {$lastName}";
    }

    /**
     * Calculate travel days between two locations.
     */
    protected function calculateTravelDays(
        string $originType,
        int $originId,
        string $destType,
        int $destId
    ): int {
        // Simple distance calculation - can be enhanced with actual map data
        // For now, use a base of 2-5 days
        return rand(2, 5);
    }

    /**
     * Get available trade routes from a location.
     */
    public function getAvailableRoutes(string $locationType, int $locationId): Collection
    {
        return TradeRoute::active()
            ->fromOrigin($locationType, $locationId)
            ->get();
    }

    /**
     * Spawn NPC caravans for the simulation.
     */
    public function spawnNpcCaravans(int $count = 3): array
    {
        $routes = TradeRoute::active()->inRandomOrder()->limit($count)->get();
        $caravans = [];

        foreach ($routes as $route) {
            $caravan = $this->createNpcCaravan(
                $route->origin_type,
                $route->origin_id,
                $route->destination_type,
                $route->destination_id
            );

            // Load random goods (simplified)
            $caravan->update(['status' => Caravan::STATUS_TRAVELING, 'departed_at' => now()]);

            $caravans[] = $caravan;
        }

        return $caravans;
    }
}
