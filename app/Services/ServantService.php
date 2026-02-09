<?php

namespace App\Services;

use App\Config\ConstructionConfig;
use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\HouseServant;
use App\Models\HouseStorage;
use App\Models\Item;
use App\Models\PlayerHouse;
use App\Models\ServantTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ServantService
{
    public function __construct(
        protected HouseBuffService $houseBuffService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function hireServant(User $user, string $tier): array
    {
        $tierConfig = ConstructionConfig::SERVANT_TIERS[$tier] ?? null;
        if (! $tierConfig) {
            return ['success' => false, 'message' => 'Invalid servant tier.'];
        }

        $house = PlayerHouse::where('player_id', $user->id)->with('servant')->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        if ($house->servant) {
            return ['success' => false, 'message' => 'You already have a servant.'];
        }

        // Check servant quarters room with bed
        $servantQuarters = HouseRoom::where('player_house_id', $house->id)
            ->where('room_type', 'servant_quarters')
            ->first();

        if (! $servantQuarters) {
            return ['success' => false, 'message' => 'You need a Servant Quarters room first.'];
        }

        $hasBed = HouseFurniture::where('house_room_id', $servantQuarters->id)
            ->where('hotspot_slug', 'bed')
            ->exists();

        if (! $hasBed) {
            return ['success' => false, 'message' => 'Build a bed in your Servant Quarters first.'];
        }

        // Check construction level
        $level = $user->skills->where('skill_name', 'construction')->first()?->level ?? 1;
        if ($level < $tierConfig['level']) {
            return ['success' => false, 'message' => "You need Construction level {$tierConfig['level']} to hire a {$tierConfig['name']}."];
        }

        if ($user->gold < $tierConfig['hire_cost']) {
            return ['success' => false, 'message' => "Not enough gold. You need {$tierConfig['hire_cost']}g."];
        }

        return DB::transaction(function () use ($user, $house, $tier, $tierConfig) {
            $user->decrement('gold', $tierConfig['hire_cost']);

            HouseServant::create([
                'player_house_id' => $house->id,
                'servant_type' => $tier,
                'name' => $tierConfig['name'],
                'last_paid_at' => now(),
                'hired_at' => now(),
            ]);

            return ['success' => true, 'message' => "Hired a {$tierConfig['name']} for {$tierConfig['hire_cost']}g!"];
        });
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function dismissServant(User $user): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->with('servant')->first();
        if (! $house || ! $house->servant) {
            return ['success' => false, 'message' => 'You do not have a servant.'];
        }

        $name = $house->servant->name;
        $house->servant->delete();

        return ['success' => true, 'message' => "{$name} has been dismissed."];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function assignTask(User $user, string $taskType, array $params): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->with('servant')->first();
        if (! $house || ! $house->servant) {
            return ['success' => false, 'message' => 'You do not have a servant.'];
        }

        $servant = $house->servant;
        if ($servant->on_strike) {
            return ['success' => false, 'message' => 'Your servant is on strike! Pay their wages first.'];
        }

        return match ($taskType) {
            'sawmill_run' => $this->assignSawmillRun($user, $house, $servant, $params),
            'fetch_materials' => $this->assignFetchMaterials($house, $servant, $params),
            'serve_food' => $this->assignServeFood($user, $house, $servant),
            default => ['success' => false, 'message' => 'Invalid task type.'],
        };
    }

    /**
     * @return array{success: bool, message: string}
     */
    protected function assignSawmillRun(User $user, PlayerHouse $house, HouseServant $servant, array $params): array
    {
        $plankName = $params['plank_name'] ?? '';
        $quantity = max(1, (int) ($params['quantity'] ?? 1));

        $recipe = ConstructionConfig::PLANK_RECIPES[$plankName] ?? null;
        if (! $recipe) {
            return ['success' => false, 'message' => 'Invalid plank type.'];
        }

        // Check logs in storage
        $logItem = Item::where('name', $recipe['log'])->first();
        if (! $logItem) {
            return ['success' => false, 'message' => 'Log type not found.'];
        }

        $storageEntry = HouseStorage::where('player_house_id', $house->id)
            ->where('item_id', $logItem->id)
            ->first();

        if (! $storageEntry || $storageEntry->quantity < $quantity) {
            return ['success' => false, 'message' => "Not enough {$recipe['log']} in storage."];
        }

        // Check gold for fees
        $totalFee = $recipe['fee'] * $quantity;
        if ($user->gold < $totalFee) {
            return ['success' => false, 'message' => "Not enough gold for sawmill fees ({$totalFee}g)."];
        }

        return DB::transaction(function () use ($user, $servant, $plankName, $quantity, $totalFee) {
            $user->decrement('gold', $totalFee);

            $task = ServantTask::create([
                'house_servant_id' => $servant->id,
                'task_type' => 'sawmill_run',
                'task_data' => ['plank_name' => $plankName, 'quantity' => $quantity, 'gold_paid' => $totalFee],
                'status' => 'queued',
            ]);

            if (! $servant->currentTask()) {
                $this->startNextTask($servant);
            }

            return ['success' => true, 'message' => "Queued sawmill run: {$quantity}x {$plankName}."];
        });
    }

    /**
     * @return array{success: bool, message: string}
     */
    protected function assignFetchMaterials(PlayerHouse $house, HouseServant $servant, array $params): array
    {
        $itemName = $params['item_name'] ?? '';
        $quantity = max(1, (int) ($params['quantity'] ?? 1));

        $item = Item::where('name', $itemName)->first();
        if (! $item) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        $storageEntry = HouseStorage::where('player_house_id', $house->id)
            ->where('item_id', $item->id)
            ->first();

        if (! $storageEntry || $storageEntry->quantity < $quantity) {
            return ['success' => false, 'message' => "Not enough {$itemName} in storage."];
        }

        $task = ServantTask::create([
            'house_servant_id' => $servant->id,
            'task_type' => 'fetch_materials',
            'task_data' => ['item_name' => $itemName, 'quantity' => $quantity],
            'status' => 'queued',
        ]);

        if (! $servant->currentTask()) {
            $this->startNextTask($servant);
        }

        return ['success' => true, 'message' => "Queued fetch: {$quantity}x {$itemName}."];
    }

    /**
     * @return array{success: bool, message: string}
     */
    protected function assignServeFood(User $user, PlayerHouse $house, HouseServant $servant): array
    {
        // Find food in storage
        $foodStorage = HouseStorage::where('player_house_id', $house->id)
            ->whereHas('item', fn ($q) => $q->where('subtype', 'food'))
            ->with('item')
            ->first();

        if (! $foodStorage) {
            return ['success' => false, 'message' => 'No food items in storage.'];
        }

        $task = ServantTask::create([
            'house_servant_id' => $servant->id,
            'task_type' => 'serve_food',
            'task_data' => ['item_name' => $foodStorage->item->name, 'food_value' => $foodStorage->item->food_value ?? 10],
            'status' => 'queued',
        ]);

        if (! $servant->currentTask()) {
            $this->startNextTask($servant);
        }

        return ['success' => true, 'message' => "Queued serve food: {$foodStorage->item->name}."];
    }

    public function startNextTask(HouseServant $servant): void
    {
        $task = $servant->nextQueuedTask();
        if (! $task || $servant->on_strike) {
            return;
        }

        $config = $servant->getConfig();
        $taskData = $task->task_data;

        $duration = match ($task->task_type) {
            'sawmill_run' => (int) ceil(($taskData['quantity'] ?? 1) / $config['carry_capacity']) * $config['base_speed'],
            default => $config['base_speed'],
        };

        // Apply servant_speed_bonus from house buffs
        $servant->load('house.player');
        $effects = $this->houseBuffService->getHouseEffects($servant->house->player);
        $speedBonus = $effects['servant_speed_bonus'] ?? 0;
        if ($speedBonus > 0) {
            $duration = (int) max(1, $duration * (1 - $speedBonus / 100));
        }

        $task->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'estimated_completion' => now()->addSeconds($duration),
        ]);
    }

    public function completeTask(ServantTask $task): void
    {
        $task->load('servant.house.player');
        $servant = $task->servant;
        $house = $servant->house;
        $player = $house->player;

        if ($servant->on_strike) {
            $task->update([
                'status' => 'failed',
                'completed_at' => now(),
                'result_message' => 'Servant is on strike.',
            ]);

            return;
        }

        $taskData = $task->task_data;

        match ($task->task_type) {
            'sawmill_run' => $this->completeSawmillRun($task, $house, $taskData),
            'fetch_materials' => $this->completeFetchMaterials($task, $house, $player, $taskData),
            'serve_food' => $this->completeServeFood($task, $house, $player, $taskData),
            default => $task->update([
                'status' => 'failed',
                'completed_at' => now(),
                'result_message' => 'Unknown task type.',
            ]),
        };

        $this->startNextTask($servant);
    }

    protected function completeSawmillRun(ServantTask $task, PlayerHouse $house, array $taskData): void
    {
        $plankName = $taskData['plank_name'];
        $quantity = $taskData['quantity'];
        $recipe = ConstructionConfig::PLANK_RECIPES[$plankName] ?? null;

        if (! $recipe) {
            $task->update(['status' => 'failed', 'completed_at' => now(), 'result_message' => 'Invalid plank recipe.']);

            return;
        }

        // Remove logs from storage
        $logItem = Item::where('name', $recipe['log'])->first();
        if ($logItem) {
            $storageEntry = HouseStorage::where('player_house_id', $house->id)
                ->where('item_id', $logItem->id)
                ->first();

            if ($storageEntry) {
                if ($storageEntry->quantity <= $quantity) {
                    $storageEntry->delete();
                } else {
                    $storageEntry->decrement('quantity', $quantity);
                }
            }
        }

        // Add planks to storage
        $plankItem = Item::where('name', $plankName)->first();
        if ($plankItem) {
            $plankStorage = HouseStorage::firstOrCreate(
                ['player_house_id' => $house->id, 'item_id' => $plankItem->id],
                ['quantity' => 0]
            );
            $plankStorage->increment('quantity', $quantity);
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result_message' => "Converted {$quantity}x {$recipe['log']} into {$quantity}x {$plankName}.",
        ]);
    }

    protected function completeFetchMaterials(ServantTask $task, PlayerHouse $house, User $player, array $taskData): void
    {
        $itemName = $taskData['item_name'];
        $quantity = $taskData['quantity'];

        $item = Item::where('name', $itemName)->first();
        if (! $item) {
            $task->update(['status' => 'failed', 'completed_at' => now(), 'result_message' => 'Item not found.']);

            return;
        }

        // Remove from storage
        $storageEntry = HouseStorage::where('player_house_id', $house->id)
            ->where('item_id', $item->id)
            ->first();

        if (! $storageEntry || $storageEntry->quantity < $quantity) {
            $task->update(['status' => 'failed', 'completed_at' => now(), 'result_message' => 'Not enough items in storage.']);

            return;
        }

        if ($storageEntry->quantity <= $quantity) {
            $storageEntry->delete();
        } else {
            $storageEntry->decrement('quantity', $quantity);
        }

        // Add to player inventory
        $this->inventoryService->addItem($player, $item, $quantity);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result_message' => "Fetched {$quantity}x {$itemName} to your inventory.",
        ]);
    }

    protected function completeServeFood(ServantTask $task, PlayerHouse $house, User $player, array $taskData): void
    {
        $itemName = $taskData['item_name'];
        $foodValue = $taskData['food_value'] ?? 10;

        $item = Item::where('name', $itemName)->first();
        if (! $item) {
            $task->update(['status' => 'failed', 'completed_at' => now(), 'result_message' => 'Food item not found.']);

            return;
        }

        // Remove 1 food from storage
        $storageEntry = HouseStorage::where('player_house_id', $house->id)
            ->where('item_id', $item->id)
            ->first();

        if (! $storageEntry || $storageEntry->quantity < 1) {
            $task->update(['status' => 'failed', 'completed_at' => now(), 'result_message' => 'No food left in storage.']);

            return;
        }

        if ($storageEntry->quantity <= 1) {
            $storageEntry->delete();
        } else {
            $storageEntry->decrement('quantity', 1);
        }

        // Restore energy (cap at max)
        $energyGain = min($foodValue, $player->max_energy - $player->energy);
        if ($energyGain > 0) {
            $player->increment('energy', $energyGain);
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result_message' => "Served {$itemName} (+{$energyGain} energy).",
        ]);
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function cancelTask(User $user, int $taskId): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->with('servant')->first();
        if (! $house || ! $house->servant) {
            return ['success' => false, 'message' => 'You do not have a servant.'];
        }

        $task = ServantTask::where('id', $taskId)
            ->where('house_servant_id', $house->servant->id)
            ->first();

        if (! $task) {
            return ['success' => false, 'message' => 'Task not found.'];
        }

        if ($task->status !== 'queued') {
            return ['success' => false, 'message' => 'Can only cancel queued tasks.'];
        }

        // Refund gold for sawmill runs
        if ($task->task_type === 'sawmill_run') {
            $goldPaid = $task->task_data['gold_paid'] ?? 0;
            if ($goldPaid > 0) {
                $user->increment('gold', $goldPaid);
            }
        }

        $task->delete();

        return ['success' => true, 'message' => 'Task cancelled.'];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function payWages(User $user): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->with('servant')->first();
        if (! $house || ! $house->servant) {
            return ['success' => false, 'message' => 'You do not have a servant.'];
        }

        $servant = $house->servant;
        if (! $servant->on_strike) {
            return ['success' => false, 'message' => 'Your servant is not on strike.'];
        }

        $config = $servant->getConfig();
        if ($user->gold < $config['weekly_wage']) {
            return ['success' => false, 'message' => "Not enough gold. Wage is {$config['weekly_wage']}g."];
        }

        $user->decrement('gold', $config['weekly_wage']);
        $servant->update([
            'on_strike' => false,
            'last_paid_at' => now(),
        ]);

        $this->startNextTask($servant);

        return ['success' => true, 'message' => "{$servant->name} is back to work!"];
    }

    /**
     * @return array{total: int, paid: int, strikes: int}
     */
    public function processWeeklyWages(): array
    {
        $servants = HouseServant::with('house.player')->get();
        $stats = ['total' => $servants->count(), 'paid' => 0, 'strikes' => 0];

        foreach ($servants as $servant) {
            $config = $servant->getConfig();
            $player = $servant->house->player;

            if ($player->gold >= $config['weekly_wage']) {
                $player->decrement('gold', $config['weekly_wage']);
                $servant->update(['last_paid_at' => now()]);
                $stats['paid']++;
            } else {
                $servant->update(['on_strike' => true]);
                $stats['strikes']++;
            }
        }

        return $stats;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getServantData(User $user): ?array
    {
        $house = PlayerHouse::where('player_id', $user->id)
            ->with(['servant.tasks', 'storage.item'])
            ->first();

        if (! $house || ! $house->servant) {
            return null;
        }

        $servant = $house->servant;
        $config = $servant->getConfig();
        $currentTask = $servant->currentTask();
        $queuedTasks = $servant->tasks->where('status', 'queued')->sortBy('id')->values();
        $recentCompleted = $servant->tasks->whereIn('status', ['completed', 'failed'])->sortByDesc('completed_at')->take(5)->values();

        // Available sawmill runs (logs in storage matched against recipes)
        $availableSawmill = [];
        foreach (ConstructionConfig::PLANK_RECIPES as $plankName => $recipe) {
            $logItem = $house->storage->first(fn ($s) => $s->item->name === $recipe['log']);
            if ($logItem && $logItem->quantity > 0) {
                $availableSawmill[] = [
                    'plank_name' => $plankName,
                    'log_name' => $recipe['log'],
                    'logs_in_storage' => $logItem->quantity,
                    'fee' => $recipe['fee'],
                ];
            }
        }

        // Available fetch items (all items in storage)
        $availableFetch = $house->storage->map(fn ($s) => [
            'item_name' => $s->item->name,
            'quantity' => $s->quantity,
        ])->values()->toArray();

        // Has food
        $hasFood = $house->storage->contains(fn ($s) => $s->item->subtype === 'food');

        return [
            'servant_type' => $servant->servant_type,
            'name' => $servant->name,
            'on_strike' => $servant->on_strike,
            'tier_config' => [
                'name' => $config['name'],
                'level' => $config['level'],
                'weekly_wage' => $config['weekly_wage'],
                'carry_capacity' => $config['carry_capacity'],
                'base_speed' => $config['base_speed'],
            ],
            'current_task' => $currentTask ? [
                'id' => $currentTask->id,
                'task_type' => $currentTask->task_type,
                'task_data' => $currentTask->task_data,
                'seconds_remaining' => max(0, (int) now()->diffInSeconds($currentTask->estimated_completion, false)),
            ] : null,
            'queued_tasks' => $queuedTasks->map(fn ($t) => [
                'id' => $t->id,
                'task_type' => $t->task_type,
                'task_data' => $t->task_data,
            ])->toArray(),
            'recent_completed' => $recentCompleted->map(fn ($t) => [
                'id' => $t->id,
                'task_type' => $t->task_type,
                'result_message' => $t->result_message,
            ])->toArray(),
            'available_sawmill' => $availableSawmill,
            'available_fetch' => $availableFetch,
            'has_food' => $hasFood,
        ];
    }
}
