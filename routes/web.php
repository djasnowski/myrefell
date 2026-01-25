<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BaronyController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CombatController;
use App\Http\Controllers\DungeonController;
use App\Http\Controllers\CraftingController;
use App\Http\Controllers\DocketController;
use App\Http\Controllers\GatheringController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\HealerController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KingdomController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\SkillsController;
use App\Http\Controllers\TownController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\NoConfidenceController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\CharterController;
use App\Http\Controllers\CrimeController;
use App\Http\Controllers\TrialController;
use App\Http\Controllers\SocialClassController;
use App\Http\Controllers\StableController;
use App\Http\Controllers\CaravanController;
use App\Http\Controllers\TradeRouteController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\VillageController;
use App\Http\Controllers\MarketController;
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

    // Skills
    Route::get('skills', [SkillsController::class, 'index'])->name('skills.index');

    // Inventory routes
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory');
    Route::post('inventory/move', [InventoryController::class, 'move'])->name('inventory.move');
    Route::post('inventory/drop', [InventoryController::class, 'drop'])->name('inventory.drop');
    Route::post('inventory/equip', [InventoryController::class, 'equip'])->name('inventory.equip');
    Route::post('inventory/unequip', [InventoryController::class, 'unequip'])->name('inventory.unequip');

    // World location routes
    Route::get('kingdoms', [KingdomController::class, 'index'])->name('kingdoms.index');
    Route::get('kingdoms/{kingdom}', [KingdomController::class, 'show'])->name('kingdoms.show');
    Route::get('kingdoms/{kingdom}/baronies', [KingdomController::class, 'baronies'])->name('kingdoms.baronies');

    Route::get('baronies', [BaronyController::class, 'index'])->name('baronies.index');
    Route::get('baronies/{barony}', [BaronyController::class, 'show'])->name('baronies.show');
    Route::get('baronies/{barony}/villages', [BaronyController::class, 'villages'])->name('baronies.villages');
    Route::get('baronies/{barony}/towns', [BaronyController::class, 'towns'])->name('baronies.towns');

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

    // Travel
    Route::get('travel', [TravelController::class, 'index'])->name('travel.index');
    Route::get('travel/status', [TravelController::class, 'status'])->name('travel.status');
    Route::post('travel/start', [TravelController::class, 'start'])->name('travel.start');
    Route::post('travel/cancel', [TravelController::class, 'cancel'])->name('travel.cancel');
    Route::post('travel/arrive', [TravelController::class, 'arrive'])->name('travel.arrive');

    // Stable (Horses)
    Route::get('stable', [StableController::class, 'index'])->name('stable.index');
    Route::post('stable/buy', [StableController::class, 'buy'])->name('stable.buy');
    Route::post('stable/sell', [StableController::class, 'sell'])->name('stable.sell');
    Route::post('stable/rename', [StableController::class, 'rename'])->name('stable.rename');
    Route::post('stable/stable', [StableController::class, 'stable'])->name('stable.stable');
    Route::post('stable/retrieve', [StableController::class, 'retrieve'])->name('stable.retrieve');
    Route::post('stable/rest', [StableController::class, 'rest'])->name('stable.rest');

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
    Route::post('market/buy', [MarketController::class, 'buy'])->name('market.buy');
    Route::post('market/sell', [MarketController::class, 'sell'])->name('market.sell');
    Route::get('market/prices', [MarketController::class, 'prices'])->name('market.prices');

    // Healer
    Route::get('villages/{village}/healer', [HealerController::class, 'villageHealer'])->name('villages.healer');
    Route::get('baronies/{barony}/infirmary', [HealerController::class, 'baronyInfirmary'])->name('baronies.infirmary');
    Route::get('towns/{town}/infirmary', [HealerController::class, 'townInfirmary'])->name('towns.infirmary');
    Route::post('healer/heal', [HealerController::class, 'heal'])->name('healer.heal');
    Route::post('healer/heal-amount', [HealerController::class, 'healAmount'])->name('healer.heal-amount');

    // Gathering
    Route::get('gathering', [GatheringController::class, 'index'])->name('gathering.index');
    Route::get('gathering/{activity}', [GatheringController::class, 'show'])->name('gathering.show');
    Route::post('gathering/gather', [GatheringController::class, 'gather'])->name('gathering.gather');

    // Training (Combat Stats)
    Route::get('training', [TrainingController::class, 'index'])->name('training.index');
    Route::post('training/train', [TrainingController::class, 'train'])->name('training.train');
    Route::get('training/status', [TrainingController::class, 'status'])->name('training.status');

    // Crafting
    Route::get('crafting', [CraftingController::class, 'index'])->name('crafting.index');
    Route::post('crafting/craft', [CraftingController::class, 'craft'])->name('crafting.craft');
    Route::get('crafting/recipe/{recipe}', [CraftingController::class, 'recipe'])->name('crafting.recipe');

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
    Route::get('towns/{town}/jobs', [JobController::class, 'townJobs'])->name('towns.jobs');
    Route::post('jobs/apply', [JobController::class, 'apply'])->name('jobs.apply');
    Route::post('jobs/{employment}/work', [JobController::class, 'work'])->name('jobs.work');
    Route::post('jobs/{employment}/quit', [JobController::class, 'quit'])->name('jobs.quit');
    Route::get('jobs/status', [JobController::class, 'status'])->name('jobs.status');

    // Roles
    Route::get('villages/{village}/roles', [RoleController::class, 'villageRoles'])->name('villages.roles');
    Route::get('baronies/{barony}/roles', [RoleController::class, 'baronyRoles'])->name('baronies.roles');
    Route::get('kingdoms/{kingdom}/roles', [RoleController::class, 'kingdomRoles'])->name('kingdoms.roles');
    Route::get('roles', [RoleController::class, 'myRoles'])->name('roles.index');
    Route::post('roles/appoint', [RoleController::class, 'appoint'])->name('roles.appoint');
    Route::post('roles/{playerRole}/resign', [RoleController::class, 'resign'])->name('roles.resign');
    Route::post('roles/{playerRole}/remove', [RoleController::class, 'remove'])->name('roles.remove');
    Route::get('roles/status', [RoleController::class, 'status'])->name('roles.status');
    Route::post('roles/claim', [RoleController::class, 'claim'])->name('roles.claim');

    // Migration (moving between villages)
    Route::get('migration', [MigrationController::class, 'index'])->name('migration.index');
    Route::post('migration/request/{village}', [MigrationController::class, 'request'])->name('migration.request');
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

    // Dungeons
    Route::get('dungeons', [DungeonController::class, 'index'])->name('dungeons.index');
    Route::get('dungeons/status', [DungeonController::class, 'status'])->name('dungeons.status');
    Route::get('dungeons/{dungeon}', [DungeonController::class, 'show'])->name('dungeons.show');
    Route::post('dungeons/enter', [DungeonController::class, 'enter'])->name('dungeons.enter');
    Route::post('dungeons/fight', [DungeonController::class, 'fight'])->name('dungeons.fight');
    Route::post('dungeons/next-floor', [DungeonController::class, 'nextFloor'])->name('dungeons.next-floor');
    Route::post('dungeons/eat', [DungeonController::class, 'eat'])->name('dungeons.eat');
    Route::post('dungeons/abandon', [DungeonController::class, 'abandon'])->name('dungeons.abandon');

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

    // Calendar (World Time)
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

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

    // Caravans
    Route::get('trade/caravans', [CaravanController::class, 'index'])->name('trade.caravans');
    Route::post('trade/caravans', [CaravanController::class, 'store'])->name('trade.caravans.store');
    Route::post('trade/caravans/{caravan}/load', [CaravanController::class, 'loadGoods'])->name('trade.caravans.load');
    Route::post('trade/caravans/{caravan}/dispatch', [CaravanController::class, 'dispatch'])->name('trade.caravans.dispatch');
    Route::post('trade/caravans/{caravan}/unload', [CaravanController::class, 'unload'])->name('trade.caravans.unload');
    Route::post('trade/caravans/{caravan}/disband', [CaravanController::class, 'disband'])->name('trade.caravans.disband');

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
});

require __DIR__.'/settings.php';
