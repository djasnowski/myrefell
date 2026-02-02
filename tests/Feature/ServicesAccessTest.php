<?php

use App\Services\AdminAnalyticsService;
use App\Services\AgilityService;
use App\Services\ApothecaryService;
use App\Services\ArmyService;
use App\Services\BankService;
use App\Services\BattleService;
use App\Services\BirthService;
use App\Services\BlessingEffectService;
use App\Services\BusinessService;
use App\Services\CalendarService;
use App\Services\CaravanService;
use App\Services\CharterService;
use App\Services\ChatService;
use App\Services\CombatService;
use App\Services\CookingService;
use App\Services\CraftingService;
use App\Services\CrimeService;
use App\Services\DailyTaskService;
use App\Services\DiceGameService;
use App\Services\DisasterService;
use App\Services\DiseaseService;
use App\Services\DocketService;
use App\Services\DungeonService;
use App\Services\DynastyService;
use App\Services\ElectionService;
use App\Services\EnergyService;
use App\Services\FestivalService;
use App\Services\FoodConsumptionService;
use App\Services\GatheringService;
use App\Services\GuildService;
use App\Services\HealerService;
use App\Services\InventoryService;
use App\Services\JobService;
use App\Services\LegitimacyService;
use App\Services\LootService;
use App\Services\MarketService;
use App\Services\MarriageService;
use App\Services\MigrationService;
use App\Services\MinigameService;
use App\Services\NpcLifecycleService;
use App\Services\NpcReproductionService;
use App\Services\OnlinePlayersService;
use App\Services\PortService;
use App\Services\QuestService;
use App\Services\ReferralService;
use App\Services\ReligionService;
use App\Services\ResourceDecayService;
use App\Services\RoleService;
use App\Services\RoleStockingService;
use App\Services\SiegeService;
use App\Services\SocialClassService;
use App\Services\StableService;
use App\Services\TaxService;
use App\Services\ThievingService;
use App\Services\TitleService;
use App\Services\TownBonusService;
use App\Services\TrainingService;
use App\Services\TravelService;
use App\Services\WarService;

test('AdminAnalyticsService can be resolved', function () {
    expect(app(AdminAnalyticsService::class))->toBeInstanceOf(AdminAnalyticsService::class);
});

test('AgilityService can be resolved', function () {
    expect(app(AgilityService::class))->toBeInstanceOf(AgilityService::class);
});

test('ApothecaryService can be resolved', function () {
    expect(app(ApothecaryService::class))->toBeInstanceOf(ApothecaryService::class);
});

test('ArmyService can be resolved', function () {
    expect(app(ArmyService::class))->toBeInstanceOf(ArmyService::class);
});

test('BankService can be resolved', function () {
    expect(app(BankService::class))->toBeInstanceOf(BankService::class);
});

test('BattleService can be resolved', function () {
    expect(app(BattleService::class))->toBeInstanceOf(BattleService::class);
});

test('BirthService can be resolved', function () {
    expect(app(BirthService::class))->toBeInstanceOf(BirthService::class);
});

test('BlessingEffectService can be resolved', function () {
    expect(app(BlessingEffectService::class))->toBeInstanceOf(BlessingEffectService::class);
});

test('BusinessService can be resolved', function () {
    expect(app(BusinessService::class))->toBeInstanceOf(BusinessService::class);
});

test('CalendarService can be resolved', function () {
    expect(app(CalendarService::class))->toBeInstanceOf(CalendarService::class);
});

test('CaravanService can be resolved', function () {
    expect(app(CaravanService::class))->toBeInstanceOf(CaravanService::class);
});

test('CharterService can be resolved', function () {
    expect(app(CharterService::class))->toBeInstanceOf(CharterService::class);
});

test('ChatService can be resolved', function () {
    expect(app(ChatService::class))->toBeInstanceOf(ChatService::class);
});

test('CombatService can be resolved', function () {
    expect(app(CombatService::class))->toBeInstanceOf(CombatService::class);
});

test('CookingService can be resolved', function () {
    expect(app(CookingService::class))->toBeInstanceOf(CookingService::class);
});

test('CraftingService can be resolved', function () {
    expect(app(CraftingService::class))->toBeInstanceOf(CraftingService::class);
});

test('CrimeService can be resolved', function () {
    expect(app(CrimeService::class))->toBeInstanceOf(CrimeService::class);
});

test('DailyTaskService can be resolved', function () {
    expect(app(DailyTaskService::class))->toBeInstanceOf(DailyTaskService::class);
});

test('DiceGameService can be resolved', function () {
    expect(app(DiceGameService::class))->toBeInstanceOf(DiceGameService::class);
});

test('DisasterService can be resolved', function () {
    expect(app(DisasterService::class))->toBeInstanceOf(DisasterService::class);
});

test('DiseaseService can be resolved', function () {
    expect(app(DiseaseService::class))->toBeInstanceOf(DiseaseService::class);
});

test('DocketService can be resolved', function () {
    expect(app(DocketService::class))->toBeInstanceOf(DocketService::class);
});

test('DungeonService can be resolved', function () {
    expect(app(DungeonService::class))->toBeInstanceOf(DungeonService::class);
});

test('DynastyService can be resolved', function () {
    expect(app(DynastyService::class))->toBeInstanceOf(DynastyService::class);
});

test('ElectionService can be resolved', function () {
    expect(app(ElectionService::class))->toBeInstanceOf(ElectionService::class);
});

test('EnergyService can be resolved', function () {
    expect(app(EnergyService::class))->toBeInstanceOf(EnergyService::class);
});

test('FestivalService can be resolved', function () {
    expect(app(FestivalService::class))->toBeInstanceOf(FestivalService::class);
});

test('FoodConsumptionService can be resolved', function () {
    expect(app(FoodConsumptionService::class))->toBeInstanceOf(FoodConsumptionService::class);
});

test('GatheringService can be resolved', function () {
    expect(app(GatheringService::class))->toBeInstanceOf(GatheringService::class);
});

test('GuildService can be resolved', function () {
    expect(app(GuildService::class))->toBeInstanceOf(GuildService::class);
});

test('HealerService can be resolved', function () {
    expect(app(HealerService::class))->toBeInstanceOf(HealerService::class);
});

test('InventoryService can be resolved', function () {
    expect(app(InventoryService::class))->toBeInstanceOf(InventoryService::class);
});

test('JobService can be resolved', function () {
    expect(app(JobService::class))->toBeInstanceOf(JobService::class);
});

test('LegitimacyService can be resolved', function () {
    expect(app(LegitimacyService::class))->toBeInstanceOf(LegitimacyService::class);
});

test('LootService can be resolved', function () {
    expect(app(LootService::class))->toBeInstanceOf(LootService::class);
});

test('MarketService can be resolved', function () {
    expect(app(MarketService::class))->toBeInstanceOf(MarketService::class);
});

test('MarriageService can be resolved', function () {
    expect(app(MarriageService::class))->toBeInstanceOf(MarriageService::class);
});

test('MigrationService can be resolved', function () {
    expect(app(MigrationService::class))->toBeInstanceOf(MigrationService::class);
});

test('MinigameService can be resolved', function () {
    expect(app(MinigameService::class))->toBeInstanceOf(MinigameService::class);
});

test('NpcLifecycleService can be resolved', function () {
    expect(app(NpcLifecycleService::class))->toBeInstanceOf(NpcLifecycleService::class);
});

test('NpcReproductionService can be resolved', function () {
    expect(app(NpcReproductionService::class))->toBeInstanceOf(NpcReproductionService::class);
});

test('OnlinePlayersService can be resolved', function () {
    expect(app(OnlinePlayersService::class))->toBeInstanceOf(OnlinePlayersService::class);
});

test('PortService can be resolved', function () {
    expect(app(PortService::class))->toBeInstanceOf(PortService::class);
});

test('QuestService can be resolved', function () {
    expect(app(QuestService::class))->toBeInstanceOf(QuestService::class);
});

test('ReferralService can be resolved', function () {
    expect(app(ReferralService::class))->toBeInstanceOf(ReferralService::class);
});

test('ReligionService can be resolved', function () {
    expect(app(ReligionService::class))->toBeInstanceOf(ReligionService::class);
});

test('ResourceDecayService can be resolved', function () {
    expect(app(ResourceDecayService::class))->toBeInstanceOf(ResourceDecayService::class);
});

test('RoleService can be resolved', function () {
    expect(app(RoleService::class))->toBeInstanceOf(RoleService::class);
});

test('RoleStockingService can be resolved', function () {
    expect(app(RoleStockingService::class))->toBeInstanceOf(RoleStockingService::class);
});

test('SiegeService can be resolved', function () {
    expect(app(SiegeService::class))->toBeInstanceOf(SiegeService::class);
});

test('SocialClassService can be resolved', function () {
    expect(app(SocialClassService::class))->toBeInstanceOf(SocialClassService::class);
});

test('StableService can be resolved', function () {
    expect(app(StableService::class))->toBeInstanceOf(StableService::class);
});

test('TaxService can be resolved', function () {
    expect(app(TaxService::class))->toBeInstanceOf(TaxService::class);
});

test('ThievingService can be resolved', function () {
    expect(app(ThievingService::class))->toBeInstanceOf(ThievingService::class);
});

test('TitleService can be resolved', function () {
    expect(app(TitleService::class))->toBeInstanceOf(TitleService::class);
});

test('TownBonusService can be resolved', function () {
    expect(app(TownBonusService::class))->toBeInstanceOf(TownBonusService::class);
});

test('TrainingService can be resolved', function () {
    expect(app(TrainingService::class))->toBeInstanceOf(TrainingService::class);
});

test('TravelService can be resolved', function () {
    expect(app(TravelService::class))->toBeInstanceOf(TravelService::class);
});

test('WarService can be resolved', function () {
    expect(app(WarService::class))->toBeInstanceOf(WarService::class);
});
