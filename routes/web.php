<?php

use App\Http\Controllers\Admin\AppealController as AdminAppealController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DynastyController as AdminDynastyController;
use App\Http\Controllers\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Admin\ReligionController as AdminReligionController;
use App\Http\Controllers\Admin\SuspiciousActivityController as AdminSuspiciousActivityController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgilityController;
use App\Http\Controllers\AnvilController;
use App\Http\Controllers\ApothecaryController;
use App\Http\Controllers\ArenaController;
use App\Http\Controllers\ArmyController;
use App\Http\Controllers\Auth\ForgotUsernameController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BaronyController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\BlessingController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CaravanController;
use App\Http\Controllers\CharterController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CombatController;
use App\Http\Controllers\CraftingController;
use App\Http\Controllers\CrimeController;
use App\Http\Controllers\CultHideoutController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\DiceGameController;
use App\Http\Controllers\DocketController;
use App\Http\Controllers\DuchyController;
use App\Http\Controllers\DungeonController;
use App\Http\Controllers\DynastyController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FarmingController;
use App\Http\Controllers\ForgeController;
use App\Http\Controllers\GatheringController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\HealerController;
use App\Http\Controllers\InfirmaryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\KingdomController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarriageController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\MinigameController;
use App\Http\Controllers\NoConfidenceController;
use App\Http\Controllers\PlayerConstructionController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerHouseController;
use App\Http\Controllers\PlayerProfileController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\ReligionHeadquartersController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoleStockingController;
use App\Http\Controllers\SawmillController;
use App\Http\Controllers\ServiceFavoriteController;
use App\Http\Controllers\SiegeController;
use App\Http\Controllers\SkillsController;
use App\Http\Controllers\SocialClassController;
use App\Http\Controllers\StableController;
use App\Http\Controllers\SuccessionController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\TavernController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\ThievingController;
use App\Http\Controllers\TitleController;
use App\Http\Controllers\TownController;
use App\Http\Controllers\TradeRouteController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\TrialController;
use App\Http\Controllers\VillageController;
use App\Http\Controllers\WarController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/features', function () {
    return Inertia::render('Features/Index', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('features');

Route::get('/privacy', function () {
    return Inertia::render('Privacy/Index', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('privacy');

Route::get('/terms', function () {
    return Inertia::render('Terms/Index', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('terms');

Route::get('/rules', function () {
    return Inertia::render('Rules/Index', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('rules');

Route::get('/ban-appeals', function () {
    return Inertia::render('BanAppeals');
})->name('ban-appeals');

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

// Forgot username routes (for guests)
Route::get('forgot-username', [ForgotUsernameController::class, 'create'])
    ->middleware('guest')
    ->name('username.request');
Route::post('forgot-username', [ForgotUsernameController::class, 'store'])
    ->middleware('guest')
    ->name('username.email');

// Public player profiles
Route::get('players/{username}', [PlayerProfileController::class, 'show'])->name('players.show');

// Public leaderboard
Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

// Banned user routes (must be auth but before ban check)
Route::middleware(['auth'])->group(function () {
    Route::get('banned', [App\Http\Controllers\BannedController::class, 'index'])->name('banned');
    Route::post('banned/appeal', [App\Http\Controllers\BannedController::class, 'appeal'])->name('banned.appeal');
});

Route::middleware(['auth', 'verified', App\Http\Middleware\EnsureUserNotBanned::class])->group(function () {
    // Impersonation routes
    Route::impersonate();

    Route::get('dashboard', fn () => Inertia::render('dashboard', [
        'showTutorial' => auth()->user()->show_tutorial ?? false,
    ]))->name('dashboard');

    Route::post('tutorial/dismiss', function () {
        auth()->user()->update(['show_tutorial' => false]);

        return back();
    })->name('tutorial.dismiss');

    Route::post('changelog/mark-read', function () {
        // Get current version from the same place as middleware
        $currentVersion = '0.6.0';
        auth()->user()->update(['last_seen_changelog' => $currentVersion]);

        return back();
    })->name('changelog.mark-read');
    Route::get('api/player/stats', [PlayerController::class, 'stats'])->name('player.stats');

    // Online count API (for polling without page reload)
    Route::get('api/online-count', function () {
        return response()->json([
            'count' => app(\App\Services\OnlinePlayersService::class)->getOnlineCount(),
        ]);
    })->name('api.online-count');

    // Skills
    Route::get('skills', [SkillsController::class, 'index'])->name('skills.index');

    // Service Favorites
    Route::post('services/favorites/toggle', [ServiceFavoriteController::class, 'toggle'])->name('services.favorites.toggle');

    // Inventory routes
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory');
    Route::post('inventory/move', [InventoryController::class, 'move'])->name('inventory.move');
    Route::post('inventory/drop', [InventoryController::class, 'drop'])->name('inventory.drop');
    Route::post('inventory/equip', [InventoryController::class, 'equip'])->name('inventory.equip');
    Route::post('inventory/unequip', [InventoryController::class, 'unequip'])->name('inventory.unequip');
    Route::post('inventory/consume', [InventoryController::class, 'consume'])->name('inventory.consume');
    Route::post('inventory/donate', [InventoryController::class, 'donate'])->name('inventory.donate');

    // World location routes
    Route::get('kingdoms', [KingdomController::class, 'index'])->name('kingdoms.index');
    Route::get('kingdoms/{kingdom}', [KingdomController::class, 'show'])->name('kingdoms.show');
    Route::get('kingdoms/{kingdom}/baronies', [KingdomController::class, 'baronies'])->name('kingdoms.baronies');
    Route::get('duchies', [DuchyController::class, 'index'])->name('duchies.index');
    Route::get('duchies/{duchy}', [DuchyController::class, 'show'])->name('duchies.show');
    Route::get('duchies/{duchy}/baronies', [DuchyController::class, 'baronies'])->name('duchies.baronies');

    Route::get('baronies', [BaronyController::class, 'index'])->name('baronies.index');
    Route::get('baronies/{barony}', [BaronyController::class, 'show'])->name('baronies.show');
    Route::get('baronies/{barony}/villages', [BaronyController::class, 'villages'])->name('baronies.villages');
    Route::get('baronies/{barony}/towns', [BaronyController::class, 'towns'])->name('baronies.towns');

    Route::get('towns', [TownController::class, 'index'])->name('towns.index');
    Route::get('towns/{town}', [TownController::class, 'show'])->name('towns.show');
    Route::get('towns/{town}/hall', [TownController::class, 'hall'])->name('towns.hall');
    Route::get('towns/{town}/treasury', [TownController::class, 'treasury'])->name('towns.treasury');

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

    // No Confidence Votes
    Route::get('no-confidence', [NoConfidenceController::class, 'index'])->name('no-confidence.index');
    Route::get('no-confidence/{noConfidenceVote}', [NoConfidenceController::class, 'show'])->name('no-confidence.show');
    Route::get('no-confidence/{noConfidenceVote}/status', [NoConfidenceController::class, 'status'])->name('no-confidence.status');
    Route::post('no-confidence/{noConfidenceVote}/vote', [NoConfidenceController::class, 'vote'])->name('no-confidence.vote');
    Route::post('villages/{village}/no-confidence', [NoConfidenceController::class, 'startVillageNoConfidence'])->name('villages.no-confidence');
    Route::post('towns/{town}/no-confidence', [NoConfidenceController::class, 'startTownNoConfidence'])->name('towns.no-confidence');
    Route::post('kingdoms/{kingdom}/no-confidence', [NoConfidenceController::class, 'startKingdomNoConfidence'])->name('kingdoms.no-confidence');

    // Daily Tasks
    Route::get('daily-tasks', [DailyTaskController::class, 'index'])->name('daily-tasks.index');
    Route::get('daily-tasks/status', [DailyTaskController::class, 'status'])->name('daily-tasks.status');
    Route::post('daily-tasks/{task}/claim', [DailyTaskController::class, 'claim'])->name('daily-tasks.claim');
    Route::post('daily-tasks/{task}/progress', [DailyTaskController::class, 'progress'])->name('daily-tasks.progress');

    // Minigames
    Route::get('minigames', [MinigameController::class, 'index'])->name('minigames.index');
    Route::post('minigames/spin', [MinigameController::class, 'spin'])->name('minigames.spin');
    Route::post('minigames/submit-score', [MinigameController::class, 'submitScore'])->name('minigames.submit-score');
    Route::get('minigames/{minigame}/leaderboards', [MinigameController::class, 'getLeaderboards'])->name('minigames.leaderboards');
    Route::post('minigames/collect-rewards', [MinigameController::class, 'collectRewards'])->name('minigames.collect-rewards');
    Route::get('minigames/pending-rewards', [MinigameController::class, 'getPendingRewards'])->name('minigames.pending-rewards');

    // Infirmary
    Route::post('infirmary/discharge', [InfirmaryController::class, 'discharge'])->name('infirmary.discharge');

    // Travel
    Route::get('travel', [MapController::class, 'index'])->name('travel.index');
    Route::get('travel/status', [TravelController::class, 'status'])->name('travel.status');
    Route::post('travel/start', [TravelController::class, 'start'])->name('travel.start');
    Route::post('travel/cancel', [TravelController::class, 'cancel'])->name('travel.cancel');
    Route::post('travel/arrive', [TravelController::class, 'arrive'])->name('travel.arrive');
    Route::post('travel/skip', [TravelController::class, 'skip'])->name('travel.skip');

    // Stable (Horses) - Legacy route redirects to location-scoped
    Route::get('stable', [StableController::class, 'legacyIndex'])->name('stable.index');
    // Global POST routes (use user's current_location)
    Route::post('stable/buy', [StableController::class, 'buy'])->name('stable.buy');
    Route::post('stable/sell', [StableController::class, 'sell'])->name('stable.sell');
    Route::post('stable/rename', [StableController::class, 'rename'])->name('stable.rename');
    Route::post('stable/stable', [StableController::class, 'stable'])->name('stable.stable');
    Route::post('stable/retrieve', [StableController::class, 'retrieve'])->name('stable.retrieve');
    Route::post('stable/rest', [StableController::class, 'rest'])->name('stable.rest');
    Route::post('stable/switch-active', [StableController::class, 'switchActive'])->name('stable.switchActive');
    Route::post('stable/feed', [StableController::class, 'feedHorses'])->name('stable.feed');

    // Bank
    Route::get('villages/{village}/bank', [BankController::class, 'villageBank'])->name('villages.bank');
    Route::get('baronies/{barony}/bank', [BankController::class, 'baronyBank'])->name('baronies.bank');
    Route::get('towns/{town}/bank', [BankController::class, 'townBank'])->name('towns.bank');
    Route::post('bank/deposit', [BankController::class, 'deposit'])->name('bank.deposit');
    Route::post('bank/withdraw', [BankController::class, 'withdraw'])->name('bank.withdraw');
    Route::get('bank/balance', [BankController::class, 'balance'])->name('bank.balance');

    // Market
    Route::get('villages/{village}/market', [MarketController::class, 'villageMarket'])->name('villages.market');
    Route::get('baronies/{barony}/market', [MarketController::class, 'baronyMarket'])->name('baronies.market');
    Route::get('towns/{town}/market', [MarketController::class, 'townMarket'])->name('towns.market');
    Route::get('kingdoms/{kingdom}/market', [MarketController::class, 'kingdomMarket'])->name('kingdoms.market');
    Route::post('market/buy', [MarketController::class, 'buy'])->name('market.buy');
    Route::post('market/sell', [MarketController::class, 'sell'])->name('market.sell');
    Route::get('market/prices', [MarketController::class, 'prices'])->name('market.prices');
    Route::post('market/sell-quote', [MarketController::class, 'sellQuote'])->name('market.sell-quote');

    // Role Stocking
    Route::get('market/stock', [RoleStockingController::class, 'index'])->name('market.stock');
    Route::post('market/stock', [RoleStockingController::class, 'stock'])->name('market.stock.submit');

    // Healer
    Route::get('villages/{village}/healer', [HealerController::class, 'villageHealer'])->name('villages.healer');
    Route::get('baronies/{barony}/infirmary', [HealerController::class, 'baronyInfirmary'])->name('baronies.infirmary');
    Route::get('towns/{town}/infirmary', [HealerController::class, 'townInfirmary'])->name('towns.infirmary');
    Route::get('kingdoms/{kingdom}/infirmary', [HealerController::class, 'kingdomInfirmary'])->name('kingdoms.infirmary');
    Route::post('healer/heal', [HealerController::class, 'heal'])->name('healer.heal');
    Route::post('healer/heal-amount', [HealerController::class, 'healAmount'])->name('healer.heal-amount');
    Route::post('healer/treat-disease', [HealerController::class, 'treatDisease'])->name('healer.treat-disease');

    // Titles & Petitions
    Route::get('titles', [TitleController::class, 'index'])->name('titles.index');
    Route::get('titles/petition/{titleType}', [TitleController::class, 'showPetitionForm'])->name('titles.petition.form');
    Route::post('titles/petition', [TitleController::class, 'submitPetition'])->name('titles.petition.submit');
    Route::post('titles/petition/{petition}/withdraw', [TitleController::class, 'withdrawPetition'])->name('titles.petition.withdraw');
    Route::get('titles/review/{petition}', [TitleController::class, 'reviewPetition'])->name('titles.petition.review');
    Route::post('titles/review/{petition}/approve', [TitleController::class, 'approvePetition'])->name('titles.petition.approve');
    Route::post('titles/review/{petition}/deny', [TitleController::class, 'denyPetition'])->name('titles.petition.deny');
    Route::get('titles/ceremony/{petition}', [TitleController::class, 'showCeremony'])->name('titles.ceremony');
    Route::post('titles/ceremony/{petition}/complete', [TitleController::class, 'completeCeremony'])->name('titles.ceremony.complete');
    Route::post('titles/grant', [TitleController::class, 'grantTitle'])->name('titles.grant');

    // Farming
    Route::get('farming', [FarmingController::class, 'index'])->name('farming.index');
    Route::post('farming/buy-plot', [FarmingController::class, 'buyPlot'])->name('farming.buy-plot');
    Route::post('farming/{plot}/plant', [FarmingController::class, 'plant'])->name('farming.plant');
    Route::post('farming/{plot}/water', [FarmingController::class, 'water'])->name('farming.water');
    Route::post('farming/{plot}/tend', [FarmingController::class, 'tend'])->name('farming.tend');
    Route::post('farming/{plot}/harvest', [FarmingController::class, 'harvest'])->name('farming.harvest');
    Route::post('farming/{plot}/clear', [FarmingController::class, 'clear'])->name('farming.clear');

    // Shrine & Blessings
    Route::get('shrine', [BlessingController::class, 'index'])->name('shrine.index');
    Route::post('shrine/bless', [BlessingController::class, 'bless'])->name('shrine.bless');
    Route::post('shrine/pray', [BlessingController::class, 'pray'])->name('shrine.pray');
    Route::post('shrine/activate-beliefs', [BlessingController::class, 'activateBeliefs'])->name('shrine.activate-beliefs');
    Route::post('shrine/activate-cult-beliefs', [BlessingController::class, 'activateCultBeliefs'])->name('shrine.activate-cult-beliefs');
    Route::get('shrine/active', [BlessingController::class, 'getActiveBlessings'])->name('shrine.active');

    // Training (Combat Stats) - Legacy routes (redirect to location-scoped)
    Route::get('training', [TrainingController::class, 'legacyIndex'])->name('training.index');
    Route::post('training/train', [TrainingController::class, 'train'])->name('training.train');
    Route::get('training/status', [TrainingController::class, 'status'])->name('training.status');

    // Crafting - Legacy routes (redirect to location-scoped)
    Route::get('crafting', [CraftingController::class, 'legacyIndex'])->name('crafting.index');
    Route::post('crafting/craft', [CraftingController::class, 'craft'])->name('crafting.craft');
    Route::get('crafting/recipe/{recipe}', [CraftingController::class, 'recipe'])->name('crafting.recipe');

    // Location-scoped services: Villages
    Route::prefix('villages/{village}')->name('villages.')->middleware('at.location')->group(function () {
        Route::get('training', [TrainingController::class, 'index'])->name('training');
        Route::post('training/train', [TrainingController::class, 'train'])->name('training.train');
        Route::get('gathering', [GatheringController::class, 'index'])->name('gathering');
        Route::post('gathering/gather', [GatheringController::class, 'gather'])->name('gathering.gather');
        Route::get('gathering/{activity}', [GatheringController::class, 'show'])->name('gathering.show');
        Route::get('crafting', [CraftingController::class, 'index'])->name('crafting');
        Route::post('crafting/craft', [CraftingController::class, 'craft'])->name('crafting.craft');
        Route::get('forge', [ForgeController::class, 'index'])->name('forge');
        Route::post('forge/smelt', [ForgeController::class, 'forge'])->name('forge.smelt');
        Route::get('anvil', [AnvilController::class, 'index'])->name('anvil');
        Route::post('anvil/smith', [AnvilController::class, 'smith'])->name('anvil.smith');
        Route::get('shrine', [BlessingController::class, 'index'])->name('shrine');
        Route::post('shrine/bless', [BlessingController::class, 'bless'])->name('shrine.bless');
        Route::post('shrine/pray', [BlessingController::class, 'pray'])->name('shrine.pray');
        Route::post('shrine/activate-beliefs', [BlessingController::class, 'activateBeliefs'])->name('shrine.activate-beliefs');
        Route::post('shrine/activate-cult-beliefs', [BlessingController::class, 'activateCultBeliefs'])->name('shrine.activate-cult-beliefs');
        Route::post('shrine/request/{blessingRequest}/approve', [BlessingController::class, 'approveRequest'])->name('shrine.approve');
        Route::post('shrine/request/{blessingRequest}/deny', [BlessingController::class, 'denyRequest'])->name('shrine.deny');
        Route::get('stables', [StableController::class, 'index'])->name('stables');
        Route::get('tavern', [TavernController::class, 'index'])->name('tavern');
        Route::post('tavern/rest', [TavernController::class, 'rest'])->name('tavern.rest');
        Route::post('tavern/cook', [TavernController::class, 'cook'])->name('tavern.cook');
        Route::post('tavern/dice', [DiceGameController::class, 'play'])->name('tavern.dice');
        Route::get('arena', [ArenaController::class, 'index'])->name('arena');
        Route::get('thieving', [ThievingController::class, 'index'])->name('thieving');
        Route::post('thieving/attempt', [ThievingController::class, 'thieve'])->name('thieving.attempt');
        Route::get('apothecary', [ApothecaryController::class, 'index'])->name('apothecary');
        Route::post('apothecary/brew', [ApothecaryController::class, 'brew'])->name('apothecary.brew');
        Route::get('farming', [FarmingController::class, 'index'])->name('farming');
        Route::post('farming/buy-plot', [FarmingController::class, 'buyPlot'])->name('farming.buy-plot');
        Route::post('farming/{plot}/plant', [FarmingController::class, 'plant'])->name('farming.plant');
        Route::post('farming/{plot}/water', [FarmingController::class, 'water'])->name('farming.water');
        Route::post('farming/{plot}/tend', [FarmingController::class, 'tend'])->name('farming.tend');
        Route::post('farming/{plot}/harvest', [FarmingController::class, 'harvest'])->name('farming.harvest');
        Route::post('farming/{plot}/clear', [FarmingController::class, 'clear'])->name('farming.clear');
        Route::get('agility', [AgilityController::class, 'index'])->name('agility');
        Route::post('agility/train', [AgilityController::class, 'train'])->name('agility.train');
        Route::get('religions/{religion}', [ReligionController::class, 'showAtLocation'])->name('religions.show');
        Route::get('religions/{religion}/headquarters', [ReligionHeadquartersController::class, 'showAtLocation'])->name('religions.headquarters.show');
        Route::post('religions/{religion}/headquarters/build', [ReligionHeadquartersController::class, 'build'])->name('religions.headquarters.build');
        Route::post('religions/{religion}/headquarters/donate', [ReligionHeadquartersController::class, 'donate'])->name('religions.headquarters.donate');
        Route::post('religions/{religion}/headquarters/upgrade', [ReligionHeadquartersController::class, 'startUpgrade'])->name('religions.headquarters.upgrade');
        Route::post('religions/{religion}/headquarters/features', [ReligionHeadquartersController::class, 'buildFeature'])->name('religions.headquarters.features.build');
        Route::post('religions/{religion}/headquarters/features/{feature}/upgrade', [ReligionHeadquartersController::class, 'upgradeFeature'])->name('religions.headquarters.features.upgrade');
        Route::post('religions/{religion}/headquarters/features/{feature}/pray', [ReligionHeadquartersController::class, 'pray'])->name('religions.headquarters.features.pray');
        Route::post('religions/{religion}/headquarters/projects/{project}/contribute', [ReligionHeadquartersController::class, 'contribute'])->name('religions.headquarters.contribute');
        Route::post('religions/{religion}/headquarters/projects/{project}/complete', [ReligionHeadquartersController::class, 'completeProject'])->name('religions.headquarters.complete');
        Route::get('cults/{religion}/hideout', [CultHideoutController::class, 'showAtLocation'])->name('cults.hideout.show');
        Route::post('cults/{religion}/hideout/build', [CultHideoutController::class, 'build'])->name('cults.hideout.build');
        Route::post('cults/{religion}/hideout/upgrade', [CultHideoutController::class, 'startUpgrade'])->name('cults.hideout.upgrade');
        Route::post('cults/{religion}/hideout/projects/{project}/contribute', [CultHideoutController::class, 'contribute'])->name('cults.hideout.contribute');
        Route::post('cults/{religion}/hideout/projects/{project}/complete', [CultHideoutController::class, 'completeProject'])->name('cults.hideout.complete');
        Route::get('sawmill', [SawmillController::class, 'index'])->name('sawmill');
        Route::post('sawmill/convert', [SawmillController::class, 'convert'])->name('sawmill.convert');
        Route::get('construction', [PlayerConstructionController::class, 'index'])->name('construction');
        Route::post('construction/contract', [PlayerConstructionController::class, 'doContract'])->name('construction.contract');
    });

    // Location-scoped services: Towns
    Route::prefix('towns/{town}')->name('towns.')->middleware('at.location')->group(function () {
        Route::get('training', [TrainingController::class, 'index'])->name('training');
        Route::post('training/train', [TrainingController::class, 'train'])->name('training.train');
        Route::get('gathering', [GatheringController::class, 'index'])->name('gathering');
        Route::post('gathering/gather', [GatheringController::class, 'gather'])->name('gathering.gather');
        Route::get('gathering/{activity}', [GatheringController::class, 'show'])->name('gathering.show');
        Route::get('crafting', [CraftingController::class, 'index'])->name('crafting');
        Route::post('crafting/craft', [CraftingController::class, 'craft'])->name('crafting.craft');
        Route::get('forge', [ForgeController::class, 'index'])->name('forge');
        Route::post('forge/smelt', [ForgeController::class, 'forge'])->name('forge.smelt');
        Route::get('anvil', [AnvilController::class, 'index'])->name('anvil');
        Route::post('anvil/smith', [AnvilController::class, 'smith'])->name('anvil.smith');
        Route::get('shrine', [BlessingController::class, 'index'])->name('shrine');
        Route::post('shrine/bless', [BlessingController::class, 'bless'])->name('shrine.bless');
        Route::post('shrine/pray', [BlessingController::class, 'pray'])->name('shrine.pray');
        Route::post('shrine/activate-beliefs', [BlessingController::class, 'activateBeliefs'])->name('shrine.activate-beliefs');
        Route::post('shrine/activate-cult-beliefs', [BlessingController::class, 'activateCultBeliefs'])->name('shrine.activate-cult-beliefs');
        Route::post('shrine/request/{blessingRequest}/approve', [BlessingController::class, 'approveRequest'])->name('shrine.approve');
        Route::post('shrine/request/{blessingRequest}/deny', [BlessingController::class, 'denyRequest'])->name('shrine.deny');
        Route::get('stables', [StableController::class, 'index'])->name('stables');
        Route::get('tavern', [TavernController::class, 'index'])->name('tavern');
        Route::post('tavern/rest', [TavernController::class, 'rest'])->name('tavern.rest');
        Route::post('tavern/cook', [TavernController::class, 'cook'])->name('tavern.cook');
        Route::post('tavern/dice', [DiceGameController::class, 'play'])->name('tavern.dice');
        Route::get('arena', [ArenaController::class, 'index'])->name('arena');
        Route::get('thieving', [ThievingController::class, 'index'])->name('thieving');
        Route::post('thieving/attempt', [ThievingController::class, 'thieve'])->name('thieving.attempt');
        Route::get('apothecary', [ApothecaryController::class, 'index'])->name('apothecary');
        Route::post('apothecary/brew', [ApothecaryController::class, 'brew'])->name('apothecary.brew');
        Route::get('farming', [FarmingController::class, 'index'])->name('farming');
        Route::post('farming/buy-plot', [FarmingController::class, 'buyPlot'])->name('farming.buy-plot');
        Route::post('farming/{plot}/plant', [FarmingController::class, 'plant'])->name('farming.plant');
        Route::post('farming/{plot}/water', [FarmingController::class, 'water'])->name('farming.water');
        Route::post('farming/{plot}/tend', [FarmingController::class, 'tend'])->name('farming.tend');
        Route::post('farming/{plot}/harvest', [FarmingController::class, 'harvest'])->name('farming.harvest');
        Route::post('farming/{plot}/clear', [FarmingController::class, 'clear'])->name('farming.clear');
        Route::get('agility', [AgilityController::class, 'index'])->name('agility');
        Route::post('agility/train', [AgilityController::class, 'train'])->name('agility.train');
        Route::get('religions/{religion}', [ReligionController::class, 'showAtLocation'])->name('religions.show');
        Route::get('religions/{religion}/headquarters', [ReligionHeadquartersController::class, 'showAtLocation'])->name('religions.headquarters.show');
        Route::post('religions/{religion}/headquarters/build', [ReligionHeadquartersController::class, 'build'])->name('religions.headquarters.build');
        Route::post('religions/{religion}/headquarters/donate', [ReligionHeadquartersController::class, 'donate'])->name('religions.headquarters.donate');
        Route::post('religions/{religion}/headquarters/upgrade', [ReligionHeadquartersController::class, 'startUpgrade'])->name('religions.headquarters.upgrade');
        Route::post('religions/{religion}/headquarters/features', [ReligionHeadquartersController::class, 'buildFeature'])->name('religions.headquarters.features.build');
        Route::post('religions/{religion}/headquarters/features/{feature}/upgrade', [ReligionHeadquartersController::class, 'upgradeFeature'])->name('religions.headquarters.features.upgrade');
        Route::post('religions/{religion}/headquarters/features/{feature}/pray', [ReligionHeadquartersController::class, 'pray'])->name('religions.headquarters.features.pray');
        Route::post('religions/{religion}/headquarters/projects/{project}/contribute', [ReligionHeadquartersController::class, 'contribute'])->name('religions.headquarters.contribute');
        Route::post('religions/{religion}/headquarters/projects/{project}/complete', [ReligionHeadquartersController::class, 'completeProject'])->name('religions.headquarters.complete');
        Route::get('cults/{religion}/hideout', [CultHideoutController::class, 'showAtLocation'])->name('cults.hideout.show');
        Route::post('cults/{religion}/hideout/build', [CultHideoutController::class, 'build'])->name('cults.hideout.build');
        Route::post('cults/{religion}/hideout/upgrade', [CultHideoutController::class, 'startUpgrade'])->name('cults.hideout.upgrade');
        Route::post('cults/{religion}/hideout/projects/{project}/contribute', [CultHideoutController::class, 'contribute'])->name('cults.hideout.contribute');
        Route::post('cults/{religion}/hideout/projects/{project}/complete', [CultHideoutController::class, 'completeProject'])->name('cults.hideout.complete');
        Route::get('sawmill', [SawmillController::class, 'index'])->name('sawmill');
        Route::post('sawmill/convert', [SawmillController::class, 'convert'])->name('sawmill.convert');
        Route::get('construction', [PlayerConstructionController::class, 'index'])->name('construction');
        Route::post('construction/contract', [PlayerConstructionController::class, 'doContract'])->name('construction.contract');
    });

    // Location-scoped services: Baronies
    Route::prefix('baronies/{barony}')->name('baronies.')->middleware('at.location')->group(function () {
        Route::get('training', [TrainingController::class, 'index'])->name('training');
        Route::post('training/train', [TrainingController::class, 'train'])->name('training.train');
        Route::get('crafting', [CraftingController::class, 'index'])->name('crafting');
        Route::post('crafting/craft', [CraftingController::class, 'craft'])->name('crafting.craft');
        Route::get('forge', [ForgeController::class, 'index'])->name('forge');
        Route::post('forge/smelt', [ForgeController::class, 'forge'])->name('forge.smelt');
        Route::get('anvil', [AnvilController::class, 'index'])->name('anvil');
        Route::post('anvil/smith', [AnvilController::class, 'smith'])->name('anvil.smith');
        Route::get('shrine', [BlessingController::class, 'index'])->name('shrine');
        Route::post('shrine/bless', [BlessingController::class, 'bless'])->name('shrine.bless');
        Route::post('shrine/pray', [BlessingController::class, 'pray'])->name('shrine.pray');
        Route::post('shrine/activate-beliefs', [BlessingController::class, 'activateBeliefs'])->name('shrine.activate-beliefs');
        Route::post('shrine/activate-cult-beliefs', [BlessingController::class, 'activateCultBeliefs'])->name('shrine.activate-cult-beliefs');
        Route::post('shrine/request/{blessingRequest}/approve', [BlessingController::class, 'approveRequest'])->name('shrine.approve');
        Route::post('shrine/request/{blessingRequest}/deny', [BlessingController::class, 'denyRequest'])->name('shrine.deny');
        Route::get('stables', [StableController::class, 'index'])->name('stables');
        Route::get('tavern', [TavernController::class, 'index'])->name('tavern');
        Route::post('tavern/rest', [TavernController::class, 'rest'])->name('tavern.rest');
        Route::post('tavern/cook', [TavernController::class, 'cook'])->name('tavern.cook');
        Route::post('tavern/dice', [DiceGameController::class, 'play'])->name('tavern.dice');
        Route::get('arena', [ArenaController::class, 'index'])->name('arena');
        Route::get('thieving', [ThievingController::class, 'index'])->name('thieving');
        Route::post('thieving/attempt', [ThievingController::class, 'thieve'])->name('thieving.attempt');
        Route::get('apothecary', [ApothecaryController::class, 'index'])->name('apothecary');
        Route::post('apothecary/brew', [ApothecaryController::class, 'brew'])->name('apothecary.brew');
        Route::get('agility', [AgilityController::class, 'index'])->name('agility');
        Route::post('agility/train', [AgilityController::class, 'train'])->name('agility.train');
        Route::get('religions/{religion}', [ReligionController::class, 'showAtLocation'])->name('religions.show');
        Route::get('religions/{religion}/headquarters', [ReligionHeadquartersController::class, 'showAtLocation'])->name('religions.headquarters.show');
        Route::post('religions/{religion}/headquarters/build', [ReligionHeadquartersController::class, 'build'])->name('religions.headquarters.build');
        Route::post('religions/{religion}/headquarters/donate', [ReligionHeadquartersController::class, 'donate'])->name('religions.headquarters.donate');
        Route::post('religions/{religion}/headquarters/upgrade', [ReligionHeadquartersController::class, 'startUpgrade'])->name('religions.headquarters.upgrade');
        Route::post('religions/{religion}/headquarters/features', [ReligionHeadquartersController::class, 'buildFeature'])->name('religions.headquarters.features.build');
        Route::post('religions/{religion}/headquarters/features/{feature}/upgrade', [ReligionHeadquartersController::class, 'upgradeFeature'])->name('religions.headquarters.features.upgrade');
        Route::post('religions/{religion}/headquarters/features/{feature}/pray', [ReligionHeadquartersController::class, 'pray'])->name('religions.headquarters.features.pray');
        Route::post('religions/{religion}/headquarters/projects/{project}/contribute', [ReligionHeadquartersController::class, 'contribute'])->name('religions.headquarters.contribute');
        Route::post('religions/{religion}/headquarters/projects/{project}/complete', [ReligionHeadquartersController::class, 'completeProject'])->name('religions.headquarters.complete');
        Route::get('cults/{religion}/hideout', [CultHideoutController::class, 'showAtLocation'])->name('cults.hideout.show');
        Route::post('cults/{religion}/hideout/build', [CultHideoutController::class, 'build'])->name('cults.hideout.build');
        Route::post('cults/{religion}/hideout/upgrade', [CultHideoutController::class, 'startUpgrade'])->name('cults.hideout.upgrade');
        Route::post('cults/{religion}/hideout/projects/{project}/contribute', [CultHideoutController::class, 'contribute'])->name('cults.hideout.contribute');
        Route::post('cults/{religion}/hideout/projects/{project}/complete', [CultHideoutController::class, 'completeProject'])->name('cults.hideout.complete');
        Route::get('sawmill', [SawmillController::class, 'index'])->name('sawmill');
        Route::post('sawmill/convert', [SawmillController::class, 'convert'])->name('sawmill.convert');
        Route::get('construction', [PlayerConstructionController::class, 'index'])->name('construction');
        Route::post('construction/contract', [PlayerConstructionController::class, 'doContract'])->name('construction.contract');
    });

    // Location-scoped services: Duchies
    Route::prefix('duchies/{duchy}')->name('duchies.')->middleware('at.location')->group(function () {
        Route::get('training', [TrainingController::class, 'index'])->name('training');
        Route::post('training/train', [TrainingController::class, 'train'])->name('training.train');
        Route::get('crafting', [CraftingController::class, 'index'])->name('crafting');
        Route::post('crafting/craft', [CraftingController::class, 'craft'])->name('crafting.craft');
        Route::get('forge', [ForgeController::class, 'index'])->name('forge');
        Route::post('forge/smelt', [ForgeController::class, 'forge'])->name('forge.smelt');
        Route::get('anvil', [AnvilController::class, 'index'])->name('anvil');
        Route::post('anvil/smith', [AnvilController::class, 'smith'])->name('anvil.smith');
        Route::get('shrine', [BlessingController::class, 'index'])->name('shrine');
        Route::post('shrine/bless', [BlessingController::class, 'bless'])->name('shrine.bless');
        Route::post('shrine/pray', [BlessingController::class, 'pray'])->name('shrine.pray');
        Route::post('shrine/activate-beliefs', [BlessingController::class, 'activateBeliefs'])->name('shrine.activate-beliefs');
        Route::post('shrine/activate-cult-beliefs', [BlessingController::class, 'activateCultBeliefs'])->name('shrine.activate-cult-beliefs');
        Route::post('shrine/request/{blessingRequest}/approve', [BlessingController::class, 'approveRequest'])->name('shrine.approve');
        Route::post('shrine/request/{blessingRequest}/deny', [BlessingController::class, 'denyRequest'])->name('shrine.deny');
        Route::get('stables', [StableController::class, 'index'])->name('stables');
        Route::get('tavern', [TavernController::class, 'index'])->name('tavern');
        Route::post('tavern/rest', [TavernController::class, 'rest'])->name('tavern.rest');
        Route::post('tavern/cook', [TavernController::class, 'cook'])->name('tavern.cook');
        Route::post('tavern/dice', [DiceGameController::class, 'play'])->name('tavern.dice');
        Route::get('arena', [ArenaController::class, 'index'])->name('arena');
        Route::get('thieving', [ThievingController::class, 'index'])->name('thieving');
        Route::post('thieving/attempt', [ThievingController::class, 'thieve'])->name('thieving.attempt');
        Route::get('apothecary', [ApothecaryController::class, 'index'])->name('apothecary');
        Route::post('apothecary/brew', [ApothecaryController::class, 'brew'])->name('apothecary.brew');
        Route::get('agility', [AgilityController::class, 'index'])->name('agility');
        Route::post('agility/train', [AgilityController::class, 'train'])->name('agility.train');
        Route::get('religions/{religion}', [ReligionController::class, 'showAtLocation'])->name('religions.show');
        Route::get('religions/{religion}/headquarters', [ReligionHeadquartersController::class, 'showAtLocation'])->name('religions.headquarters.show');
        Route::post('religions/{religion}/headquarters/build', [ReligionHeadquartersController::class, 'build'])->name('religions.headquarters.build');
        Route::post('religions/{religion}/headquarters/donate', [ReligionHeadquartersController::class, 'donate'])->name('religions.headquarters.donate');
        Route::post('religions/{religion}/headquarters/upgrade', [ReligionHeadquartersController::class, 'startUpgrade'])->name('religions.headquarters.upgrade');
        Route::post('religions/{religion}/headquarters/features', [ReligionHeadquartersController::class, 'buildFeature'])->name('religions.headquarters.features.build');
        Route::post('religions/{religion}/headquarters/features/{feature}/upgrade', [ReligionHeadquartersController::class, 'upgradeFeature'])->name('religions.headquarters.features.upgrade');
        Route::post('religions/{religion}/headquarters/features/{feature}/pray', [ReligionHeadquartersController::class, 'pray'])->name('religions.headquarters.features.pray');
        Route::post('religions/{religion}/headquarters/projects/{project}/contribute', [ReligionHeadquartersController::class, 'contribute'])->name('religions.headquarters.contribute');
        Route::post('religions/{religion}/headquarters/projects/{project}/complete', [ReligionHeadquartersController::class, 'completeProject'])->name('religions.headquarters.complete');
        Route::get('cults/{religion}/hideout', [CultHideoutController::class, 'showAtLocation'])->name('cults.hideout.show');
        Route::post('cults/{religion}/hideout/build', [CultHideoutController::class, 'build'])->name('cults.hideout.build');
        Route::post('cults/{religion}/hideout/upgrade', [CultHideoutController::class, 'startUpgrade'])->name('cults.hideout.upgrade');
        Route::post('cults/{religion}/hideout/projects/{project}/contribute', [CultHideoutController::class, 'contribute'])->name('cults.hideout.contribute');
        Route::post('cults/{religion}/hideout/projects/{project}/complete', [CultHideoutController::class, 'completeProject'])->name('cults.hideout.complete');
        Route::get('construction', [PlayerConstructionController::class, 'index'])->name('construction');
        Route::post('construction/contract', [PlayerConstructionController::class, 'doContract'])->name('construction.contract');
    });

    // Location-scoped services: Kingdoms
    Route::prefix('kingdoms/{kingdom}')->name('kingdoms.')->middleware('at.location')->group(function () {
        Route::get('training', [TrainingController::class, 'index'])->name('training');
        Route::post('training/train', [TrainingController::class, 'train'])->name('training.train');
        Route::get('crafting', [CraftingController::class, 'index'])->name('crafting');
        Route::post('crafting/craft', [CraftingController::class, 'craft'])->name('crafting.craft');
        Route::get('forge', [ForgeController::class, 'index'])->name('forge');
        Route::post('forge/smelt', [ForgeController::class, 'forge'])->name('forge.smelt');
        Route::get('anvil', [AnvilController::class, 'index'])->name('anvil');
        Route::post('anvil/smith', [AnvilController::class, 'smith'])->name('anvil.smith');
        Route::get('shrine', [BlessingController::class, 'index'])->name('shrine');
        Route::post('shrine/bless', [BlessingController::class, 'bless'])->name('shrine.bless');
        Route::post('shrine/pray', [BlessingController::class, 'pray'])->name('shrine.pray');
        Route::post('shrine/activate-beliefs', [BlessingController::class, 'activateBeliefs'])->name('shrine.activate-beliefs');
        Route::post('shrine/activate-cult-beliefs', [BlessingController::class, 'activateCultBeliefs'])->name('shrine.activate-cult-beliefs');
        Route::post('shrine/request/{blessingRequest}/approve', [BlessingController::class, 'approveRequest'])->name('shrine.approve');
        Route::post('shrine/request/{blessingRequest}/deny', [BlessingController::class, 'denyRequest'])->name('shrine.deny');
        Route::get('stables', [StableController::class, 'index'])->name('stables');
        Route::get('tavern', [TavernController::class, 'index'])->name('tavern');
        Route::post('tavern/rest', [TavernController::class, 'rest'])->name('tavern.rest');
        Route::post('tavern/cook', [TavernController::class, 'cook'])->name('tavern.cook');
        Route::post('tavern/dice', [DiceGameController::class, 'play'])->name('tavern.dice');
        Route::get('thieving', [ThievingController::class, 'index'])->name('thieving');
        Route::post('thieving/attempt', [ThievingController::class, 'thieve'])->name('thieving.attempt');
        Route::get('apothecary', [ApothecaryController::class, 'index'])->name('apothecary');
        Route::post('apothecary/brew', [ApothecaryController::class, 'brew'])->name('apothecary.brew');
        Route::get('religions/{religion}', [ReligionController::class, 'showAtLocation'])->name('religions.show');
        Route::get('religions/{religion}/headquarters', [ReligionHeadquartersController::class, 'showAtLocation'])->name('religions.headquarters.show');
        Route::post('religions/{religion}/headquarters/build', [ReligionHeadquartersController::class, 'build'])->name('religions.headquarters.build');
        Route::post('religions/{religion}/headquarters/donate', [ReligionHeadquartersController::class, 'donate'])->name('religions.headquarters.donate');
        Route::post('religions/{religion}/headquarters/upgrade', [ReligionHeadquartersController::class, 'startUpgrade'])->name('religions.headquarters.upgrade');
        Route::post('religions/{religion}/headquarters/features', [ReligionHeadquartersController::class, 'buildFeature'])->name('religions.headquarters.features.build');
        Route::post('religions/{religion}/headquarters/features/{feature}/upgrade', [ReligionHeadquartersController::class, 'upgradeFeature'])->name('religions.headquarters.features.upgrade');
        Route::post('religions/{religion}/headquarters/features/{feature}/pray', [ReligionHeadquartersController::class, 'pray'])->name('religions.headquarters.features.pray');
        Route::post('religions/{religion}/headquarters/projects/{project}/contribute', [ReligionHeadquartersController::class, 'contribute'])->name('religions.headquarters.contribute');
        Route::post('religions/{religion}/headquarters/projects/{project}/complete', [ReligionHeadquartersController::class, 'completeProject'])->name('religions.headquarters.complete');
        Route::get('cults/{religion}/hideout', [CultHideoutController::class, 'showAtLocation'])->name('cults.hideout.show');
        Route::post('cults/{religion}/hideout/build', [CultHideoutController::class, 'build'])->name('cults.hideout.build');
        Route::post('cults/{religion}/hideout/upgrade', [CultHideoutController::class, 'startUpgrade'])->name('cults.hideout.upgrade');
        Route::post('cults/{religion}/hideout/projects/{project}/contribute', [CultHideoutController::class, 'contribute'])->name('cults.hideout.contribute');
        Route::post('cults/{religion}/hideout/projects/{project}/complete', [CultHideoutController::class, 'completeProject'])->name('cults.hideout.complete');
        Route::get('construction', [PlayerConstructionController::class, 'index'])->name('construction');
        Route::post('construction/contract', [PlayerConstructionController::class, 'doContract'])->name('construction.contract');
    });

    // Crafting Docket
    Route::get('docket', [DocketController::class, 'index'])->name('docket.index');
    Route::get('docket/status', [DocketController::class, 'status'])->name('docket.status');
    Route::post('docket/npc-order', [DocketController::class, 'npcOrder'])->name('docket.npc-order');
    Route::post('docket/place-order', [DocketController::class, 'placeOrder'])->name('docket.place-order');
    Route::post('docket/{order}/accept', [DocketController::class, 'acceptOrder'])->name('docket.accept');
    Route::post('docket/{order}/complete', [DocketController::class, 'completeOrder'])->name('docket.complete');
    Route::post('docket/{order}/cancel', [DocketController::class, 'cancelOrder'])->name('docket.cancel');
    Route::post('docket/{order}/abandon', [DocketController::class, 'abandonOrder'])->name('docket.abandon');

    // Quests
    Route::get('villages/{village}/quests', [QuestController::class, 'noticeBoard'])->name('villages.quests');
    Route::get('quests', [QuestController::class, 'questLog'])->name('quests.index');
    Route::post('quests/accept', [QuestController::class, 'accept'])->name('quests.accept');
    Route::post('quests/{playerQuest}/abandon', [QuestController::class, 'abandon'])->name('quests.abandon');
    Route::post('quests/{playerQuest}/claim', [QuestController::class, 'claim'])->name('quests.claim');

    // Port
    Route::get('villages/{village}/port', [PortController::class, 'show'])->name('villages.port');
    Route::post('port/book', [PortController::class, 'book'])->name('port.book');

    // Jobs
    Route::get('villages/{village}/jobs', [JobController::class, 'villageJobs'])->name('villages.jobs');
    Route::get('baronies/{barony}/jobs', [JobController::class, 'baronyJobs'])->name('baronies.jobs');
    Route::get('duchies/{duchy}/jobs', [JobController::class, 'duchyJobs'])->name('duchies.jobs');
    Route::get('kingdoms/{kingdom}/jobs', [JobController::class, 'kingdomJobs'])->name('kingdoms.jobs');
    Route::get('towns/{town}/jobs', [JobController::class, 'townJobs'])->name('towns.jobs');
    Route::post('jobs/apply', [JobController::class, 'apply'])->name('jobs.apply');
    Route::post('jobs/{employment}/work', [JobController::class, 'work'])->name('jobs.work');
    Route::post('jobs/{employment}/quit', [JobController::class, 'quit'])->name('jobs.quit');
    Route::get('jobs/status', [JobController::class, 'status'])->name('jobs.status');
    Route::post('jobs/{employment}/fire', [JobController::class, 'fire'])->name('jobs.fire');
    Route::get('jobs/supervised-workers', [JobController::class, 'supervisedWorkers'])->name('jobs.supervised-workers');

    // Roles
    Route::get('villages/{village}/roles', [RoleController::class, 'villageRoles'])->name('villages.roles');
    Route::get('towns/{town}/roles', [RoleController::class, 'townRoles'])->name('towns.roles');
    Route::get('baronies/{barony}/roles', [RoleController::class, 'baronyRoles'])->name('baronies.roles');
    Route::get('duchies/{duchy}/roles', [RoleController::class, 'duchyRoles'])->name('duchies.roles');
    Route::get('kingdoms/{kingdom}/roles', [RoleController::class, 'kingdomRoles'])->name('kingdoms.roles');
    Route::get('roles', [RoleController::class, 'myRoles'])->name('roles.index');
    Route::post('roles/appoint', [RoleController::class, 'appoint'])->name('roles.appoint');
    Route::post('roles/{playerRole}/resign', [RoleController::class, 'resign'])->name('roles.resign');
    Route::post('roles/{playerRole}/remove', [RoleController::class, 'remove'])->name('roles.remove');
    Route::get('roles/status', [RoleController::class, 'status'])->name('roles.status');
    Route::post('roles/claim', [RoleController::class, 'claim'])->name('roles.claim');

    // Migration (moving between locations)
    Route::get('migration', [MigrationController::class, 'index'])->name('migration.index');
    Route::post('migration/request/{village}', [MigrationController::class, 'request'])->name('migration.request');
    Route::post('migration/request-town/{town}', [MigrationController::class, 'requestTown'])->name('migration.request-town');
    Route::post('migration/request-barony/{barony}', [MigrationController::class, 'requestBarony'])->name('migration.request-barony');
    Route::post('migration/request-kingdom/{kingdom}', [MigrationController::class, 'requestKingdom'])->name('migration.request-kingdom');
    Route::post('migration/{migrationRequest}/cancel', [MigrationController::class, 'cancel'])->name('migration.cancel');
    Route::post('migration/{migrationRequest}/approve', [MigrationController::class, 'approve'])->name('migration.approve');
    Route::post('migration/{migrationRequest}/deny', [MigrationController::class, 'deny'])->name('migration.deny');

    // Taxes
    Route::get('villages/{village}/taxes', [TaxController::class, 'villageTaxes'])->name('villages.taxes');
    Route::get('baronies/{barony}/taxes', [TaxController::class, 'baronyTaxes'])->name('baronies.taxes');
    Route::get('kingdoms/{kingdom}/taxes', [TaxController::class, 'kingdomTaxes'])->name('kingdoms.taxes');
    Route::get('taxes', [TaxController::class, 'myTaxes'])->name('taxes.index');
    Route::post('taxes/set-rate', [TaxController::class, 'setTaxRate'])->name('taxes.set-rate');
    Route::get('taxes/treasury-status', [TaxController::class, 'treasuryStatus'])->name('taxes.treasury-status');

    // Combat
    Route::get('combat', [CombatController::class, 'index'])->name('combat.index');
    Route::get('combat/status', [CombatController::class, 'status'])->name('combat.status');
    Route::post('combat/start', [CombatController::class, 'start'])->name('combat.start');
    Route::post('combat/attack', [CombatController::class, 'attack'])->name('combat.attack');
    Route::post('combat/eat', [CombatController::class, 'eat'])->name('combat.eat');
    Route::post('combat/flee', [CombatController::class, 'flee'])->name('combat.flee');

    // Dungeons (kingdom-scoped)
    Route::get('kingdoms/{kingdom}/dungeons', [DungeonController::class, 'index'])->name('kingdoms.dungeons.index');
    Route::get('kingdoms/{kingdom}/dungeons/loot', [DungeonController::class, 'lootStorage'])->name('kingdoms.dungeons.loot');
    Route::post('kingdoms/{kingdom}/dungeons/loot/claim', [DungeonController::class, 'claimLoot'])->name('kingdoms.dungeons.loot.claim');
    Route::post('kingdoms/{kingdom}/dungeons/loot/claim-all', [DungeonController::class, 'claimAllLoot'])->name('kingdoms.dungeons.loot.claim-all');
    Route::get('kingdoms/{kingdom}/dungeons/{dungeon}', [DungeonController::class, 'show'])->name('kingdoms.dungeons.show');
    Route::post('kingdoms/{kingdom}/dungeons/enter', [DungeonController::class, 'enter'])->name('kingdoms.dungeons.enter');
    Route::post('kingdoms/{kingdom}/dungeons/fight', [DungeonController::class, 'fight'])->name('kingdoms.dungeons.fight');
    Route::post('kingdoms/{kingdom}/dungeons/next-floor', [DungeonController::class, 'nextFloor'])->name('kingdoms.dungeons.next-floor');
    Route::post('kingdoms/{kingdom}/dungeons/eat', [DungeonController::class, 'eat'])->name('kingdoms.dungeons.eat');
    Route::post('kingdoms/{kingdom}/dungeons/abandon', [DungeonController::class, 'abandon'])->name('kingdoms.dungeons.abandon');
    Route::get('kingdoms/{kingdom}/dungeons/status', [DungeonController::class, 'status'])->name('kingdoms.dungeons.status');

    // Legacy dungeon route - redirect to kingdom-scoped
    Route::get('dungeons', [DungeonController::class, 'legacyIndex'])->name('dungeons.index');

    // Chat
    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('villages/{village}/chat', [ChatController::class, 'villageChat'])->name('villages.chat');
    Route::get('baronies/{barony}/chat', [ChatController::class, 'baronyChat'])->name('baronies.chat');
    Route::get('chat/private/{user}', [ChatController::class, 'privateChat'])->name('chat.private');
    Route::get('chat/conversations', [ChatController::class, 'conversations'])->name('chat.conversations');
    Route::post('chat/send/location', [ChatController::class, 'sendLocationMessage'])->name('chat.send.location');
    Route::post('chat/send/private', [ChatController::class, 'sendPrivateMessage'])->name('chat.send.private');
    Route::post('chat/poll/location', [ChatController::class, 'pollLocation'])->name('chat.poll.location');
    Route::post('chat/poll/private', [ChatController::class, 'pollPrivate'])->name('chat.poll.private');
    Route::delete('chat/messages/{message}', [ChatController::class, 'deleteMessage'])->name('chat.messages.delete');

    // Religions
    Route::get('religions', [ReligionController::class, 'index'])->name('religions.index');
    Route::get('religions/structures', [ReligionController::class, 'structures'])->name('religions.structures');
    Route::get('religions/successors', [ReligionController::class, 'successors'])->name('religions.successors');
    Route::get('religions/invites', [ReligionController::class, 'pendingInvites'])->name('religions.invites');
    Route::get('religions/{religion}', [ReligionController::class, 'show'])->name('religions.show');
    Route::post('religions/create-cult', [ReligionController::class, 'createCult'])->name('religions.create-cult');
    Route::post('religions/join', [ReligionController::class, 'join'])->name('religions.join');
    Route::post('religions/leave', [ReligionController::class, 'leave'])->name('religions.leave');
    Route::post('religions/action', [ReligionController::class, 'performAction'])->name('religions.action');
    Route::post('religions/promote', [ReligionController::class, 'promote'])->name('religions.promote');
    Route::post('religions/demote', [ReligionController::class, 'demote'])->name('religions.demote');
    Route::post('religions/convert', [ReligionController::class, 'convertToReligion'])->name('religions.convert');
    Route::post('religions/build-structure', [ReligionController::class, 'buildStructure'])->name('religions.build-structure');
    Route::post('religions/make-public', [ReligionController::class, 'makePublic'])->name('religions.make-public');
    Route::post('religions/kingdom-status', [ReligionController::class, 'setKingdomStatus'])->name('religions.kingdom-status');
    Route::post('religions/dissolve', [ReligionController::class, 'dissolve'])->name('religions.dissolve');
    Route::post('religions/invite', [ReligionController::class, 'invite'])->name('religions.invite');
    Route::post('religions/invite/accept', [ReligionController::class, 'acceptInvite'])->name('religions.invite.accept');
    Route::post('religions/invite/decline', [ReligionController::class, 'declineInvite'])->name('religions.invite.decline');
    Route::post('religions/invite/cancel', [ReligionController::class, 'cancelInvite'])->name('religions.invite.cancel');

    // Legacy HQ redirect - redirects old /religions/{id}/headquarters to location-scoped URL
    Route::get('religions/{religion}/headquarters', [ReligionHeadquartersController::class, 'redirectToLocationScoped'])
        ->name('religions.headquarters.redirect');

    // Calendar (World Time)
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Events (Festivals, Tournaments, Royal Events)
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/festivals/{festival}', [EventController::class, 'showFestival'])->name('events.festivals.show');
    Route::post('events/festivals/{festival}/join', [EventController::class, 'joinFestival'])->name('events.festivals.join');
    Route::post('events/festivals/{festival}/leave', [EventController::class, 'leaveFestival'])->name('events.festivals.leave');
    Route::get('events/tournaments/{tournament}', [EventController::class, 'showTournament'])->name('events.tournaments.show');
    Route::post('events/tournaments/{tournament}/register', [EventController::class, 'registerForTournament'])->name('events.tournaments.register');
    Route::post('events/tournaments/{tournament}/withdraw', [EventController::class, 'withdrawFromTournament'])->name('events.tournaments.withdraw');

    // Charters
    Route::get('charters', [CharterController::class, 'index'])->name('charters.index');
    Route::get('charters/{charter}', [CharterController::class, 'show'])->name('charters.show');
    Route::get('charters/{charter}/status', [CharterController::class, 'status'])->name('charters.status');
    Route::get('kingdoms/{kingdom}/charters', [CharterController::class, 'kingdomCharters'])->name('kingdoms.charters');
    Route::post('charters', [CharterController::class, 'store'])->name('charters.store');
    Route::post('charters/{charter}/sign', [CharterController::class, 'sign'])->name('charters.sign');
    Route::post('charters/{charter}/approve', [CharterController::class, 'approve'])->name('charters.approve');
    Route::post('charters/{charter}/reject', [CharterController::class, 'reject'])->name('charters.reject');
    Route::post('charters/{charter}/found', [CharterController::class, 'found'])->name('charters.found');
    Route::post('charters/{charter}/cancel', [CharterController::class, 'cancel'])->name('charters.cancel');
    Route::post('ruins/{ruin}/reclaim', [CharterController::class, 'reclaim'])->name('ruins.reclaim');

    // Businesses
    Route::get('businesses', [BusinessController::class, 'myBusinesses'])->name('businesses.index');
    Route::get('businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');
    Route::get('villages/{village}/businesses', [BusinessController::class, 'villageBusinesses'])->name('villages.businesses');
    Route::get('towns/{town}/businesses', [BusinessController::class, 'townBusinesses'])->name('towns.businesses');
    Route::get('baronies/{barony}/businesses', [BusinessController::class, 'baronyBusinesses'])->name('baronies.businesses');
    Route::post('businesses/establish', [BusinessController::class, 'establish'])->name('businesses.establish');
    Route::post('businesses/{business}/close', [BusinessController::class, 'close'])->name('businesses.close');
    Route::post('businesses/{business}/deposit', [BusinessController::class, 'deposit'])->name('businesses.deposit');
    Route::post('businesses/{business}/withdraw', [BusinessController::class, 'withdraw'])->name('businesses.withdraw');
    Route::post('businesses/{business}/hire', [BusinessController::class, 'hire'])->name('businesses.hire');
    Route::post('businesses/{business}/employees/{employee}/fire', [BusinessController::class, 'fire'])->name('businesses.fire');
    Route::post('businesses/{business}/add-stock', [BusinessController::class, 'addStock'])->name('businesses.add-stock');
    Route::post('businesses/{business}/remove-stock', [BusinessController::class, 'removeStock'])->name('businesses.remove-stock');
    Route::get('businesses-status', [BusinessController::class, 'status'])->name('businesses.status');

    // Trade Routes
    Route::get('trade/routes', [TradeRouteController::class, 'index'])->name('trade.routes');
    Route::post('trade/routes', [TradeRouteController::class, 'store'])->name('trade.routes.store');
    Route::get('baronies/{barony}/trade-routes', [TradeRouteController::class, 'baronyTradeRoutes'])->name('baronies.trade-routes');
    Route::post('baronies/{barony}/trade-routes', [TradeRouteController::class, 'storeBaronyRoute'])->name('baronies.trade-routes.store');

    // Caravans
    Route::get('trade/caravans', [CaravanController::class, 'index'])->name('trade.caravans');
    Route::get('trade/caravans/{caravan}', [CaravanController::class, 'show'])->name('trade.caravans.show');
    Route::post('trade/caravans', [CaravanController::class, 'store'])->name('trade.caravans.store');
    Route::post('trade/caravans/{caravan}/load', [CaravanController::class, 'loadGoods'])->name('trade.caravans.load');
    Route::post('trade/caravans/{caravan}/dispatch', [CaravanController::class, 'dispatch'])->name('trade.caravans.dispatch');
    Route::post('trade/caravans/{caravan}/unload', [CaravanController::class, 'unload'])->name('trade.caravans.unload');
    Route::post('trade/caravans/{caravan}/disband', [CaravanController::class, 'disband'])->name('trade.caravans.disband');
    Route::post('trade/caravans/{caravan}/remove-goods', [CaravanController::class, 'removeGoods'])->name('trade.caravans.remove-goods');

    // Tariffs
    Route::get('trade/tariffs', [TariffController::class, 'index'])->name('trade.tariffs');
    Route::post('trade/tariffs', [TariffController::class, 'store'])->name('trade.tariffs.store');
    Route::put('trade/tariffs/{tariff}', [TariffController::class, 'update'])->name('trade.tariffs.update');

    // Guilds
    Route::get('guilds', [GuildController::class, 'index'])->name('guilds.index');
    Route::get('guilds/location', [GuildController::class, 'locationGuilds'])->name('guilds.location');
    Route::get('guilds/{guild}', [GuildController::class, 'show'])->name('guilds.show');
    Route::post('guilds/create', [GuildController::class, 'create'])->name('guilds.create');
    Route::post('guilds/join', [GuildController::class, 'join'])->name('guilds.join');
    Route::post('guilds/leave', [GuildController::class, 'leave'])->name('guilds.leave');
    Route::post('guilds/donate', [GuildController::class, 'donate'])->name('guilds.donate');
    Route::post('guilds/pay-dues', [GuildController::class, 'payDues'])->name('guilds.pay-dues');
    Route::post('guilds/promote', [GuildController::class, 'promote'])->name('guilds.promote');
    Route::post('guilds/start-election', [GuildController::class, 'startElection'])->name('guilds.start-election');
    Route::post('guilds/declare-candidacy', [GuildController::class, 'declareCandidacy'])->name('guilds.declare-candidacy');
    Route::post('guilds/vote', [GuildController::class, 'vote'])->name('guilds.vote');
    Route::post('guilds/set-membership-fee', [GuildController::class, 'setMembershipFee'])->name('guilds.set-membership-fee');
    Route::post('guilds/set-weekly-dues', [GuildController::class, 'setWeeklyDues'])->name('guilds.set-weekly-dues');
    Route::post('guilds/set-public-status', [GuildController::class, 'setPublicStatus'])->name('guilds.set-public-status');

    // Social Class
    Route::get('social-class', [SocialClassController::class, 'index'])->name('social-class.index');
    Route::post('social-class/manumission', [SocialClassController::class, 'requestManumission'])->name('social-class.manumission');
    Route::post('social-class/manumission/{manumissionRequest}/cancel', [SocialClassController::class, 'cancelManumission'])->name('social-class.manumission.cancel');
    Route::post('social-class/ennoblement', [SocialClassController::class, 'requestEnnoblement'])->name('social-class.ennoblement');
    Route::post('social-class/ennoblement/{ennoblementRequest}/cancel', [SocialClassController::class, 'cancelEnnoblement'])->name('social-class.ennoblement.cancel');
    Route::post('social-class/burgher', [SocialClassController::class, 'becomeBurgher'])->name('social-class.burgher');

    // Baron admin - Manumission requests
    Route::get('social-class/manumission-requests', [SocialClassController::class, 'manumissionRequests'])->name('social-class.manumission-requests');
    Route::post('social-class/manumission/{manumissionRequest}/approve', [SocialClassController::class, 'approveManumission'])->name('social-class.manumission.approve');
    Route::post('social-class/manumission/{manumissionRequest}/deny', [SocialClassController::class, 'denyManumission'])->name('social-class.manumission.deny');

    // King admin - Ennoblement requests
    Route::get('social-class/ennoblement-requests', [SocialClassController::class, 'ennoblementRequests'])->name('social-class.ennoblement-requests');
    Route::post('social-class/ennoblement/{ennoblementRequest}/approve', [SocialClassController::class, 'approveEnnoblement'])->name('social-class.ennoblement.approve');
    Route::post('social-class/ennoblement/{ennoblementRequest}/deny', [SocialClassController::class, 'denyEnnoblement'])->name('social-class.ennoblement.deny');

    // Crime & Law
    Route::get('crime', [CrimeController::class, 'index'])->name('crime.index');
    Route::get('crime/court', [CrimeController::class, 'court'])->name('crime.court');
    Route::get('crime/types', [CrimeController::class, 'crimeTypes'])->name('crime.types');
    Route::get('crime/bounties', [CrimeController::class, 'bountyBoard'])->name('crime.bounties');
    Route::get('crime/accuse', [CrimeController::class, 'accuseForm'])->name('crime.accuse.form');
    Route::post('crime/accuse', [CrimeController::class, 'accuse'])->name('crime.accuse');
    Route::post('crime/accusation/{accusation}/withdraw', [CrimeController::class, 'withdrawAccusation'])->name('crime.accusation.withdraw');
    Route::post('crime/bounty', [CrimeController::class, 'postBounty'])->name('crime.bounty.post');
    Route::post('crime/bounty/{bounty}/cancel', [CrimeController::class, 'cancelBounty'])->name('crime.bounty.cancel');

    // Judge actions - Accusations
    Route::get('crime/accusations', [CrimeController::class, 'pendingAccusations'])->name('crime.accusations');
    Route::post('crime/accusation/{accusation}/review', [CrimeController::class, 'reviewAccusation'])->name('crime.accusation.review');

    // Judge actions - Trials
    Route::get('crime/trials', [CrimeController::class, 'pendingTrials'])->name('crime.trials');
    Route::post('crime/trial/{trial}/verdict', [CrimeController::class, 'renderVerdict'])->name('crime.trial.verdict');

    // Trial viewer
    Route::get('crime/trials/{trial}', [TrialController::class, 'show'])->name('crime.trials.show');
    Route::post('crime/trials/{trial}/defense', [TrialController::class, 'submitDefense'])->name('crime.trials.defense');

    // Pardon (King only)
    Route::post('crime/punishment/{punishment}/pardon', [CrimeController::class, 'pardon'])->name('crime.punishment.pardon');

    // Warfare - Armies
    Route::get('warfare/armies', [ArmyController::class, 'index'])->name('warfare.armies');
    Route::get('warfare/armies/{army}', [ArmyController::class, 'show'])->name('warfare.armies.show');
    Route::post('warfare/armies', [ArmyController::class, 'store'])->name('warfare.armies.store');
    Route::post('warfare/armies/{army}/disband', [ArmyController::class, 'disband'])->name('warfare.armies.disband');
    Route::post('warfare/armies/{army}/rename', [ArmyController::class, 'rename'])->name('warfare.armies.rename');
    Route::post('warfare/armies/{army}/deposit', [ArmyController::class, 'deposit'])->name('warfare.armies.deposit');
    Route::post('warfare/armies/{army}/withdraw', [ArmyController::class, 'withdraw'])->name('warfare.armies.withdraw');
    Route::post('warfare/armies/{army}/recruit', [ArmyController::class, 'recruit'])->name('warfare.armies.recruit');
    Route::post('warfare/armies/{army}/move', [ArmyController::class, 'move'])->name('warfare.armies.move');
    Route::post('warfare/mercenaries/{company}/hire', [ArmyController::class, 'hireMercenary'])->name('warfare.mercenaries.hire');

    // Warfare - Wars
    Route::get('warfare/wars', [WarController::class, 'index'])->name('warfare.wars');
    Route::get('warfare/wars/{war}', [WarController::class, 'show'])->name('warfare.wars.show');
    Route::get('warfare/wars/{war}/peace', [WarController::class, 'peaceForm'])->name('warfare.peace');
    Route::post('warfare/wars/{war}/peace', [WarController::class, 'offerPeace'])->name('warfare.peace.store');
    Route::post('warfare/wars/{war}/peace/{treaty}/respond', [WarController::class, 'respondToPeace'])->name('warfare.peace.respond');
    Route::get('warfare/declare', [WarController::class, 'declareForm'])->name('warfare.declare');
    Route::post('warfare/declare', [WarController::class, 'declare'])->name('warfare.declare.store');

    // Warfare - Sieges
    Route::get('warfare/sieges/{siege}', [SiegeController::class, 'show'])->name('warfare.sieges.show');
    Route::post('warfare/sieges/{siege}/assault', [SiegeController::class, 'assault'])->name('warfare.sieges.assault');
    Route::post('warfare/sieges/{siege}/lift', [SiegeController::class, 'lift'])->name('warfare.sieges.lift');
    Route::post('warfare/sieges/{siege}/build-equipment', [SiegeController::class, 'buildEquipment'])->name('warfare.sieges.build-equipment');

    // Warfare - Battles
    Route::get('warfare/battles/{battle}', [BattleController::class, 'show'])->name('warfare.battles.show');

    // Dynasty
    Route::get('dynasty', [DynastyController::class, 'index'])->name('dynasty.index');
    Route::post('dynasty', [DynastyController::class, 'found'])->name('dynasty.found');
    Route::put('dynasty', [DynastyController::class, 'update'])->name('dynasty.update');
    Route::post('dynasty/leave', [DynastyController::class, 'leave'])->name('dynasty.leave');
    Route::post('dynasty/dissolve', [DynastyController::class, 'dissolve'])->name('dynasty.dissolve');
    Route::get('dynasty/tree', [DynastyController::class, 'tree'])->name('dynasty.tree');
    Route::get('dynasty/history', [DynastyController::class, 'history'])->name('dynasty.history');
    Route::get('dynasty/alliances', [DynastyController::class, 'alliances'])->name('dynasty.alliances');
    Route::post('dynasty/alliances/{alliance}/break', [DynastyController::class, 'breakAlliance'])->name('dynasty.alliances.break');

    // Marriage Proposals
    Route::get('dynasty/proposals', [MarriageController::class, 'proposals'])->name('dynasty.proposals');
    Route::get('dynasty/proposals/create', [MarriageController::class, 'proposeForm'])->name('dynasty.proposals.create');
    Route::post('dynasty/proposals', [MarriageController::class, 'store'])->name('dynasty.proposals.store');
    Route::post('dynasty/proposals/{proposal}/accept', [MarriageController::class, 'accept'])->name('dynasty.proposals.accept');
    Route::post('dynasty/proposals/{proposal}/reject', [MarriageController::class, 'reject'])->name('dynasty.proposals.reject');
    Route::post('dynasty/proposals/{proposal}/withdraw', [MarriageController::class, 'withdraw'])->name('dynasty.proposals.withdraw');

    // Succession
    Route::get('dynasty/succession', [SuccessionController::class, 'index'])->name('dynasty.succession');
    Route::put('dynasty/succession', [SuccessionController::class, 'update'])->name('dynasty.succession.update');
    Route::post('dynasty/succession/disinherit/{member}', [SuccessionController::class, 'disinherit'])->name('dynasty.succession.disinherit');

    // Player House
    Route::prefix('house')->name('house.')->group(function () {
        Route::get('/', [PlayerHouseController::class, 'index'])->name('index');
        Route::post('/purchase', [PlayerHouseController::class, 'purchase'])->name('purchase');
        Route::post('/upgrade', [PlayerHouseController::class, 'upgrade'])->name('upgrade');
        Route::post('/build-room', [PlayerHouseController::class, 'buildRoom'])->name('build-room');
        Route::post('/build-furniture', [PlayerHouseController::class, 'buildFurniture'])->name('build-furniture');
        Route::post('/demolish-furniture', [PlayerHouseController::class, 'demolishFurniture'])->name('demolish-furniture');
        Route::post('/deposit', [PlayerHouseController::class, 'deposit'])->name('deposit');
        Route::post('/withdraw', [PlayerHouseController::class, 'withdraw'])->name('withdraw');
        Route::post('/set-portal', [PlayerHouseController::class, 'setPortal'])->name('set-portal');
        Route::post('/teleport', [PlayerHouseController::class, 'teleport'])->name('teleport');
    });

    // Buildings
    Route::get('buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::post('buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::post('buildings/{building}/repair', [BuildingController::class, 'repair'])->name('buildings.repair');
    Route::post('buildings/projects/{project}/cancel', [BuildingController::class, 'cancel'])->name('buildings.cancel');

    // Referrals
    Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');

    // Dev/Admin tools (dan only)
    Route::post('dev/set-energy', [AdminController::class, 'setEnergy'])->name('dev.set-energy');
});

// Admin routes
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
        Route::post('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');
        Route::put('/users/{user}/password', [AdminUserController::class, 'setPassword'])->name('users.set-password');

        // Items
        Route::get('/items', [AdminItemController::class, 'index'])->name('items.index');

        // Suspicious Activity
        Route::get('/suspicious-activity', [AdminSuspiciousActivityController::class, 'index'])->name('suspicious-activity.index');
        Route::get('/suspicious-activity/{user}', [AdminSuspiciousActivityController::class, 'show'])->name('suspicious-activity.show');
        Route::post('/suspicious-activity/{user}/clear', [AdminSuspiciousActivityController::class, 'clearFlag'])->name('suspicious-activity.clear');

        // Appeals
        Route::get('/appeals', [AdminAppealController::class, 'index'])->name('appeals.index');

        // Dynasties
        Route::get('/dynasties', [AdminDynastyController::class, 'index'])->name('dynasties.index');
        Route::get('/dynasties/{dynasty}', [AdminDynastyController::class, 'show'])->name('dynasties.show');
        Route::get('/dynasties/{dynasty}/edit', [AdminDynastyController::class, 'edit'])->name('dynasties.edit');
        Route::put('/dynasties/{dynasty}', [AdminDynastyController::class, 'update'])->name('dynasties.update');

        // Religions
        Route::get('/religions', [AdminReligionController::class, 'index'])->name('religions.index');
        Route::get('/religions/{religion}', [AdminReligionController::class, 'show'])->name('religions.show');
        Route::get('/religions/{religion}/edit', [AdminReligionController::class, 'edit'])->name('religions.edit');
        Route::put('/religions/{religion}', [AdminReligionController::class, 'update'])->name('religions.update');
    });

require __DIR__.'/settings.php';
