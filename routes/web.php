<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\CastleController;
use App\Http\Controllers\CraftingController;
use App\Http\Controllers\GatheringController;
use App\Http\Controllers\HealerController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KingdomController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\TownController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\VillageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Dev auto-login route (only available in local environment)
if (app()->environment('local')) {
    Route::get('/dev/login', function () {
        $user = \App\Models\User::where('username', 'dan')->first();

        if ($user) {
            auth()->login($user);
            return redirect()->route('dashboard');
        }

        return redirect()->route('home')->with('error', 'Dev user not found. Run: php artisan db:seed --class=DanAdminSeeder');
    })->name('dev.login');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [MapController::class, 'index'])->name('dashboard');
    Route::get('api/player/stats', [PlayerController::class, 'stats'])->name('player.stats');

    // Inventory routes
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory');
    Route::post('inventory/move', [InventoryController::class, 'move'])->name('inventory.move');
    Route::post('inventory/drop', [InventoryController::class, 'drop'])->name('inventory.drop');
    Route::post('inventory/equip', [InventoryController::class, 'equip'])->name('inventory.equip');
    Route::post('inventory/unequip', [InventoryController::class, 'unequip'])->name('inventory.unequip');

    // World location routes
    Route::get('kingdoms', [KingdomController::class, 'index'])->name('kingdoms.index');
    Route::get('kingdoms/{kingdom}', [KingdomController::class, 'show'])->name('kingdoms.show');
    Route::get('kingdoms/{kingdom}/castles', [KingdomController::class, 'castles'])->name('kingdoms.castles');

    Route::get('castles', [CastleController::class, 'index'])->name('castles.index');
    Route::get('castles/{castle}', [CastleController::class, 'show'])->name('castles.show');
    Route::get('castles/{castle}/villages', [CastleController::class, 'villages'])->name('castles.villages');

    Route::get('towns', [TownController::class, 'index'])->name('towns.index');
    Route::get('towns/{town}', [TownController::class, 'show'])->name('towns.show');
    Route::get('towns/{town}/hall', [TownController::class, 'hall'])->name('towns.hall');

    Route::get('villages', [VillageController::class, 'index'])->name('villages.index');
    Route::get('villages/{village}', [VillageController::class, 'show'])->name('villages.show');
    Route::get('villages/{village}/residents', [VillageController::class, 'residents'])->name('villages.residents');

    // Election routes
    Route::get('elections', [ElectionController::class, 'index'])->name('elections.index');
    Route::get('elections/{election}', [ElectionController::class, 'show'])->name('elections.show');
    Route::get('elections/{election}/status', [ElectionController::class, 'status'])->name('elections.status');
    Route::post('elections/{election}/candidacy', [ElectionController::class, 'declareCandidacy'])->name('elections.candidacy.declare');
    Route::delete('elections/{election}/candidacy', [ElectionController::class, 'withdrawCandidacy'])->name('elections.candidacy.withdraw');
    Route::post('elections/{election}/vote', [ElectionController::class, 'vote'])->name('elections.vote');

    // Village elections
    Route::post('villages/{village}/elections', [ElectionController::class, 'startVillageElection'])->name('villages.elections.start');
    Route::post('villages/{village}/self-appoint', [ElectionController::class, 'selfAppoint'])->name('villages.self-appoint');

    // Town elections
    Route::post('towns/{town}/elections/mayor', [ElectionController::class, 'startMayorElection'])->name('towns.elections.mayor');

    // Kingdom elections
    Route::post('kingdoms/{kingdom}/elections/king', [ElectionController::class, 'startKingElection'])->name('kingdoms.elections.king');

    // Daily Tasks
    Route::get('daily-tasks', [DailyTaskController::class, 'index'])->name('daily-tasks.index');
    Route::get('daily-tasks/status', [DailyTaskController::class, 'status'])->name('daily-tasks.status');
    Route::post('daily-tasks/{task}/claim', [DailyTaskController::class, 'claim'])->name('daily-tasks.claim');
    Route::post('daily-tasks/{task}/progress', [DailyTaskController::class, 'progress'])->name('daily-tasks.progress');

    // Travel
    Route::get('travel', [TravelController::class, 'index'])->name('travel.index');
    Route::get('travel/status', [TravelController::class, 'status'])->name('travel.status');
    Route::post('travel/start', [TravelController::class, 'start'])->name('travel.start');
    Route::post('travel/cancel', [TravelController::class, 'cancel'])->name('travel.cancel');
    Route::post('travel/arrive', [TravelController::class, 'arrive'])->name('travel.arrive');

    // Bank
    Route::get('villages/{village}/bank', [BankController::class, 'villageBank'])->name('villages.bank');
    Route::get('castles/{castle}/bank', [BankController::class, 'castleBank'])->name('castles.bank');
    Route::get('towns/{town}/bank', [BankController::class, 'townBank'])->name('towns.bank');
    Route::post('bank/deposit', [BankController::class, 'deposit'])->name('bank.deposit');
    Route::post('bank/withdraw', [BankController::class, 'withdraw'])->name('bank.withdraw');
    Route::get('bank/balance', [BankController::class, 'balance'])->name('bank.balance');

    // Healer
    Route::get('villages/{village}/healer', [HealerController::class, 'villageHealer'])->name('villages.healer');
    Route::get('castles/{castle}/infirmary', [HealerController::class, 'castleInfirmary'])->name('castles.infirmary');
    Route::get('towns/{town}/infirmary', [HealerController::class, 'townInfirmary'])->name('towns.infirmary');
    Route::post('healer/heal', [HealerController::class, 'heal'])->name('healer.heal');
    Route::post('healer/heal-amount', [HealerController::class, 'healAmount'])->name('healer.heal-amount');

    // Gathering
    Route::get('gathering', [GatheringController::class, 'index'])->name('gathering.index');
    Route::get('gathering/{activity}', [GatheringController::class, 'show'])->name('gathering.show');
    Route::post('gathering/gather', [GatheringController::class, 'gather'])->name('gathering.gather');

    // Crafting
    Route::get('crafting', [CraftingController::class, 'index'])->name('crafting.index');
    Route::post('crafting/craft', [CraftingController::class, 'craft'])->name('crafting.craft');
    Route::get('crafting/recipe/{recipe}', [CraftingController::class, 'recipe'])->name('crafting.recipe');

    // Quests
    Route::get('villages/{village}/quests', [QuestController::class, 'noticeBoard'])->name('villages.quests');
    Route::get('quests', [QuestController::class, 'questLog'])->name('quests.index');
    Route::post('quests/accept', [QuestController::class, 'accept'])->name('quests.accept');
    Route::post('quests/{playerQuest}/abandon', [QuestController::class, 'abandon'])->name('quests.abandon');
    Route::post('quests/{playerQuest}/claim', [QuestController::class, 'claim'])->name('quests.claim');

    // Port
    Route::get('villages/{village}/port', [PortController::class, 'show'])->name('villages.port');
    Route::post('port/book', [PortController::class, 'book'])->name('port.book');
});

require __DIR__.'/settings.php';
