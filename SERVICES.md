# MyRefell Services Documentation

This document provides a comprehensive overview of all service classes in the MyRefell application. Use this as a reference for planning Pest tests.

---

## Table of Contents

1. [Player Systems](#player-systems)
2. [Combat & Dungeons](#combat--dungeons)
3. [Economy & Trade](#economy--trade)
4. [Skills & Progression](#skills--progression)
5. [Social Systems](#social-systems)
6. [NPC Systems](#npc-systems)
7. [World Systems](#world-systems)
8. [Military & Warfare](#military--warfare)
9. [Dynasty & Marriage](#dynasty--marriage)
10. [Governance & Roles](#governance--roles)
11. [Religion](#religion)

---

## Player Systems

### EnergyService

Manages player energy regeneration, consumption, and death penalties.

| Method | Signature | Description |
|--------|-----------|-------------|
| `regenerateEnergy` | `regenerateEnergy(User $user): int` | Regenerates energy based on time since last update |
| `consumeEnergy` | `consumeEnergy(User $user, int $amount): bool` | Consumes energy for an action |
| `hasEnergy` | `hasEnergy(User $user, int $amount): bool` | Checks if user has enough energy |
| `applyDeathPenalty` | `applyDeathPenalty(User $user): void` | Applies energy penalty on player death |
| `getMaxEnergy` | `getMaxEnergy(User $user): int` | Returns maximum energy based on level |

### InventoryService

Manages player inventory with item stacking and slot management.

| Method | Signature | Description |
|--------|-----------|-------------|
| `addItem` | `addItem(User $user, Item $item, int $quantity = 1): array` | Adds item to inventory, handles stacking |
| `removeItem` | `removeItem(User $user, Item $item, int $quantity = 1): array` | Removes item from inventory |
| `hasItem` | `hasItem(User $user, Item $item, int $quantity = 1): bool` | Checks if player has item quantity |
| `countItem` | `countItem(User $user, Item $item): int` | Counts total quantity of an item |
| `hasEmptySlot` | `hasEmptySlot(User $user): bool` | Checks if inventory has free slots |
| `freeSlots` | `freeSlots(User $user): int` | Returns number of free inventory slots |
| `giveStarterKit` | `giveStarterKit(User $user): void` | Gives new player starting items |
| `getInventorySlots` | `getInventorySlots(User $user): Collection` | Returns all inventory slots |

### StableService

Manages horse purchase, sale, and stabling.

| Method | Signature | Description |
|--------|-----------|-------------|
| `getAvailableHorses` | `getAvailableHorses(User $user): Collection` | Gets horses available for purchase at location |
| `purchaseHorse` | `purchaseHorse(User $user, Horse $horse): array` | Purchases a horse for the player |
| `sellHorse` | `sellHorse(User $user, PlayerHorse $playerHorse): array` | Sells player's horse |
| `getPlayerHorses` | `getPlayerHorses(User $user): Collection` | Gets all horses owned by player |
| `setActiveHorse` | `setActiveHorse(User $user, PlayerHorse $horse): array` | Sets the active riding horse |
| `feedHorse` | `feedHorse(PlayerHorse $horse, int $amount): void` | Restores horse stamina |
| `consumeStamina` | `consumeStamina(PlayerHorse $horse, int $amount): void` | Consumes horse stamina for travel |

### TravelService

Handles coordinate-based travel with horse speed bonuses and seasonal modifiers.

| Method | Signature | Description |
|--------|-----------|-------------|
| `canTravel` | `canTravel(User $user): bool` | Checks if user can start traveling |
| `startTravel` | `startTravel(User $user, string $destType, int $destId): array` | Initiates travel to destination |
| `completeTravel` | `completeTravel(User $user): array` | Completes travel and updates location |
| `cancelTravel` | `cancelTravel(User $user): array` | Cancels ongoing travel |
| `calculateTravelTime` | `calculateTravelTime(User $user, $from, $to): int` | Calculates travel time in minutes |
| `getSeasonalModifier` | `getSeasonalModifier(): float` | Returns seasonal travel time modifier |
| `getTravelProgress` | `getTravelProgress(User $user): array` | Returns current travel progress |

### BirthService

Handles weighted village selection for new player spawning.

| Method | Signature | Description |
|--------|-----------|-------------|
| `selectBirthVillage` | `selectBirthVillage(?int $kingdomId = null): Village` | Selects spawn village using weighted random |
| `getKingdomPopulations` | `getKingdomPopulations(): Collection` | Returns population counts by kingdom |

---

## Combat & Dungeons

### CombatService

Turn-based monster combat with equipment bonuses and flee mechanics.

| Method | Signature | Description |
|--------|-----------|-------------|
| `startCombat` | `startCombat(User $user, Monster $monster): Combat` | Initiates combat with a monster |
| `processTurn` | `processTurn(Combat $combat, string $action): array` | Processes a combat turn |
| `calculatePlayerDamage` | `calculatePlayerDamage(User $user): int` | Calculates player attack damage |
| `calculateMonsterDamage` | `calculateMonsterDamage(Monster $monster): int` | Calculates monster attack damage |
| `attemptFlee` | `attemptFlee(Combat $combat): array` | Attempts to flee from combat |
| `endCombat` | `endCombat(Combat $combat, string $outcome): void` | Ends combat and awards rewards |
| `getEquipmentBonus` | `getEquipmentBonus(User $user, string $stat): int` | Gets equipment stat bonus |

### DungeonService

Multi-floor dungeon exploration with bosses and accumulated rewards.

| Method | Signature | Description |
|--------|-----------|-------------|
| `enterDungeon` | `enterDungeon(User $user, Dungeon $dungeon): DungeonRun` | Starts a dungeon run |
| `exploreRoom` | `exploreRoom(DungeonRun $run): array` | Explores current room for encounters |
| `advanceFloor` | `advanceFloor(DungeonRun $run): array` | Advances to next dungeon floor |
| `completeDungeon` | `completeDungeon(DungeonRun $run): array` | Completes dungeon and awards loot |
| `abandonDungeon` | `abandonDungeon(DungeonRun $run): void` | Abandons current dungeon run |
| `getActiveDungeonRun` | `getActiveDungeonRun(User $user): ?DungeonRun` | Gets user's active dungeon run |

### LootService

Monster loot rolling and distribution.

| Method | Signature | Description |
|--------|-----------|-------------|
| `rollLoot` | `rollLoot(Monster $monster): array` | Rolls loot drops from monster |
| `distributeLoot` | `distributeLoot(User $user, array $loot): array` | Adds loot to player inventory |
| `calculateDropChance` | `calculateDropChance(MonsterDrop $drop): float` | Calculates modified drop chance |

---

## Economy & Trade

### BankService

Location-based banking with deposits and withdrawals.

| Method | Signature | Description |
|--------|-----------|-------------|
| `deposit` | `deposit(User $user, int $amount): array` | Deposits gold into bank |
| `withdraw` | `withdraw(User $user, int $amount): array` | Withdraws gold from bank |
| `getBalance` | `getBalance(User $user): int` | Gets bank balance at current location |
| `canAccessBank` | `canAccessBank(User $user): bool` | Checks if user can access bank |
| `getBankInfo` | `getBankInfo(User $user): ?array` | Gets bank info for current location |

### TaxService

Hierarchical tax collection (player->village->barony->kingdom) and salary distribution.

| Method | Signature | Description |
|--------|-----------|-------------|
| `collectDailyTaxes` | `collectDailyTaxes(): array` | Collects taxes from all players |
| `distributeSalaries` | `distributeSalaries(): array` | Distributes salaries to role holders |
| `calculatePlayerTax` | `calculatePlayerTax(User $user): int` | Calculates tax for a player |
| `getVillageTaxRate` | `getVillageTaxRate(Village $village): float` | Gets village tax rate |

### MarketService

Dynamic market with seasonal pricing and supply/demand.

| Method | Signature | Description |
|--------|-----------|-------------|
| `canAccessMarket` | `canAccessMarket(User $user): bool` | Checks if user can access market |
| `getMarketInfo` | `getMarketInfo(User $user): ?array` | Gets market info for location |
| `getMarketPrices` | `getMarketPrices(string $locationType, int $locationId): Collection` | Gets all market prices |
| `getSellableItems` | `getSellableItems(User $user, string $locationType, int $locationId): Collection` | Gets items player can sell |
| `buyItem` | `buyItem(User $user, int $itemId, int $quantity): array` | Buys item from market |
| `sellItem` | `sellItem(User $user, int $itemId, int $quantity): array` | Sells item to market |
| `updatePrice` | `updatePrice(MarketPrice $marketPrice, ?WorldState $worldState = null): void` | Updates price based on conditions |
| `getRecentTransactions` | `getRecentTransactions(User $user, int $limit = 10): Collection` | Gets user's recent transactions |
| `refreshLocationPrices` | `refreshLocationPrices(string $locationType, int $locationId): void` | Refreshes all prices at location |

### CaravanService

Trade caravan system with travel events and tariffs.

| Method | Signature | Description |
|--------|-----------|-------------|
| `createCaravan` | `createCaravan(User $user, string $name, string $originType, int $originId): TradeCaravan` | Creates a new caravan |
| `addGoods` | `addGoods(TradeCaravan $caravan, int $itemId, int $quantity): array` | Adds goods to caravan |
| `removeGoods` | `removeGoods(TradeCaravan $caravan, int $itemId, int $quantity): array` | Removes goods from caravan |
| `departCaravan` | `departCaravan(TradeCaravan $caravan, string $destType, int $destId): array` | Starts caravan journey |
| `processCaravanTick` | `processCaravanTick(TradeCaravan $caravan): array` | Processes daily caravan progress |
| `arriveCaravan` | `arriveCaravan(TradeCaravan $caravan): array` | Handles caravan arrival |
| `rollTravelEvent` | `rollTravelEvent(TradeCaravan $caravan): ?array` | Rolls for random travel event |
| `calculateTariff` | `calculateTariff(TradeCaravan $caravan): int` | Calculates border tariff |
| `sellAllGoods` | `sellAllGoods(TradeCaravan $caravan): array` | Sells all caravan goods at destination |
| `disbandCaravan` | `disbandCaravan(TradeCaravan $caravan): void` | Disbands a caravan |

### BusinessService

Player-owned businesses with employees, treasury, and production.

| Method | Signature | Description |
|--------|-----------|-------------|
| `createBusiness` | `createBusiness(User $owner, string $name, string $type, string $locationType, int $locationId): Business` | Creates a new business |
| `hirEmployee` | `hireEmployee(Business $business, User $employee, string $role): array` | Hires an employee |
| `fireEmployee` | `fireEmployee(Business $business, User $employee): array` | Fires an employee |
| `processProduction` | `processProduction(Business $business): array` | Processes daily production |
| `depositToTreasury` | `depositToTreasury(Business $business, int $amount): void` | Deposits to business treasury |
| `withdrawFromTreasury` | `withdrawFromTreasury(Business $business, int $amount, User $user): array` | Withdraws from treasury |
| `getBusinesses` | `getBusinesses(User $user): Collection` | Gets businesses owned by user |

### PortService

Ship travel between kingdoms via port villages.

| Method | Signature | Description |
|--------|-----------|-------------|
| `canAccessPort` | `canAccessPort(User $user): bool` | Checks if user can access port |
| `getCurrentPort` | `getCurrentPort(User $user): ?Village` | Gets current port village |
| `calculateTravelTime` | `calculateTravelTime(Village $from, Village $to): int` | Calculates ship travel time |
| `calculateCost` | `calculateCost(int $travelTime): int` | Calculates passage cost |
| `getAvailableDestinations` | `getAvailableDestinations(User $user): array` | Gets available ship destinations |
| `bookPassage` | `bookPassage(User $user, int $destinationPortId): array` | Books passage to destination |
| `getPortInfo` | `getPortInfo(User $user): ?array` | Gets port info for current location |
| `getHarbormasterName` | `getHarbormasterName(string $kingdomName): string` | Gets harbormaster name for kingdom |

---

## Skills & Progression

### TrainingService

Combat training at training grounds.

| Method | Signature | Description |
|--------|-----------|-------------|
| `canTrain` | `canTrain(User $user): bool` | Checks if user can train at location |
| `getAvailableExercises` | `getAvailableExercises(User $user): array` | Gets available training exercises |
| `train` | `train(User $user, string $exercise, ?string $locationType, ?int $locationId): array` | Performs training exercise |
| `getExerciseInfo` | `getExerciseInfo(User $user, string $exercise): ?array` | Gets info about an exercise |
| `getCombatLevel` | `getCombatLevel(User $user): int` | Calculates combat level from stats |
| `getCombatStats` | `getCombatStats(User $user): array` | Gets all combat stat levels |

### GatheringService

Resource gathering (mining, fishing, woodcutting) with seasonal modifiers.

| Method | Signature | Description |
|--------|-----------|-------------|
| `canGather` | `canGather(User $user, string $activity): bool` | Checks if user can gather at location |
| `getAvailableActivities` | `getAvailableActivities(User $user): array` | Gets available gathering activities |
| `gather` | `gather(User $user, string $activity, ?string $locationType, ?int $locationId): array` | Performs gathering action |
| `getSeasonalModifier` | `getSeasonalModifier(): float` | Gets current seasonal modifier |
| `calculateYield` | `calculateYield(float $modifier): int` | Calculates yield based on modifier |
| `getActivityInfo` | `getActivityInfo(User $user, string $activity): ?array` | Gets info about an activity |
| `getSeasonalData` | `getSeasonalData(): array` | Gets seasonal data for display |

### CraftingService

Item crafting with recipes and skill requirements.

| Method | Signature | Description |
|--------|-----------|-------------|
| `canCraft` | `canCraft(User $user): bool` | Checks if user can craft at location |
| `getAvailableRecipes` | `getAvailableRecipes(User $user): array` | Gets recipes user can make |
| `getAllRecipes` | `getAllRecipes(User $user): array` | Gets all recipes (including locked) |
| `canMakeRecipe` | `canMakeRecipe(User $user, string $recipeId): bool` | Checks if player can make recipe |
| `craft` | `craft(User $user, string $recipeId, ?string $locationType, ?int $locationId): array` | Crafts an item |
| `getCraftingInfo` | `getCraftingInfo(User $user): ?array` | Gets crafting info for location |

### DailyTaskService

Daily task assignment and reward claiming.

| Method | Signature | Description |
|--------|-----------|-------------|
| `getTodaysTasks` | `getTodaysTasks(User $user): Collection` | Gets or generates today's tasks |
| `assignDailyTasks` | `assignDailyTasks(User $user): Collection` | Assigns new daily tasks |
| `recordProgress` | `recordProgress(User $user, string $taskType, ?string $targetIdentifier, int $amount): void` | Records task progress |
| `claimReward` | `claimReward(User $user, PlayerDailyTask $playerTask): array` | Claims completed task reward |
| `getTaskStats` | `getTaskStats(User $user): array` | Gets task completion statistics |
| `seedDefaultTasks` | `seedDefaultTasks(): void` | Seeds default daily tasks (static) |

### QuestService

Quest acceptance, tracking, and completion.

| Method | Signature | Description |
|--------|-----------|-------------|
| `getAvailableQuests` | `getAvailableQuests(User $user): Collection` | Gets quests available to player |
| `acceptQuest` | `acceptQuest(User $user, Quest $quest): array` | Accepts a quest |
| `abandonQuest` | `abandonQuest(User $user, PlayerQuest $playerQuest): array` | Abandons an active quest |
| `checkProgress` | `checkProgress(PlayerQuest $playerQuest): void` | Updates quest progress |
| `completeQuest` | `completeQuest(User $user, PlayerQuest $playerQuest): array` | Completes quest and grants rewards |
| `getActiveQuests` | `getActiveQuests(User $user): Collection` | Gets player's active quests |

---

## Social Systems

### ChatService

Location and private messaging with moderation.

| Method | Signature | Description |
|--------|-----------|-------------|
| `sendLocationMessage` | `sendLocationMessage(User $user, string $message): ChatMessage` | Sends message to current location |
| `sendPrivateMessage` | `sendPrivateMessage(User $from, User $to, string $message): ChatMessage` | Sends private message |
| `getLocationMessages` | `getLocationMessages(string $locationType, int $locationId, int $limit): Collection` | Gets recent location messages |
| `getPrivateMessages` | `getPrivateMessages(User $user1, User $user2, int $limit): Collection` | Gets private message history |
| `moderateMessage` | `moderateMessage(ChatMessage $message, User $moderator, string $action): void` | Moderates a message |

### MigrationService

Player relocation with multi-level approval.

| Method | Signature | Description |
|--------|-----------|-------------|
| `requestMigration` | `requestMigration(User $user, Village $destination): MigrationRequest` | Requests relocation |
| `approveMigration` | `approveMigration(MigrationRequest $request, User $approver): array` | Approves migration request |
| `rejectMigration` | `rejectMigration(MigrationRequest $request, User $rejector, string $reason): void` | Rejects migration request |
| `completeMigration` | `completeMigration(MigrationRequest $request): void` | Completes approved migration |
| `getPendingRequests` | `getPendingRequests(Village $village): Collection` | Gets pending requests for village |

### CharterService

Settlement founding with signatories and royal approval.

| Method | Signature | Description |
|--------|-----------|-------------|
| `createCharter` | `createCharter(User $founder, string $settlementName, string $type, array $terms): Charter` | Creates a charter |
| `addSignatory` | `addSignatory(Charter $charter, User $user): array` | Adds signatory to charter |
| `removeSignatory` | `removeSignatory(Charter $charter, User $user): void` | Removes signatory |
| `requestApproval` | `requestApproval(Charter $charter): array` | Requests royal approval |
| `approveCharter` | `approveCharter(Charter $charter, User $approver): array` | Approves charter |
| `foundSettlement` | `foundSettlement(Charter $charter): Village` | Founds the settlement |

### SocialClassService

Manages social class progression (serf/freeman/burgher/noble/clergy).

| Method | Signature | Description |
|--------|-----------|-------------|
| `getSocialClass` | `getSocialClass(User $user): string` | Gets user's social class |
| `canPromote` | `canPromote(User $user, string $targetClass): bool` | Checks if user can be promoted |
| `promote` | `promote(User $user, string $targetClass, ?User $promoter): array` | Promotes user to class |
| `demote` | `demote(User $user, string $targetClass, ?User $demoter, string $reason): array` | Demotes user |
| `getClassRequirements` | `getClassRequirements(string $className): array` | Gets requirements for class |
| `getClassPrivileges` | `getClassPrivileges(string $className): array` | Gets privileges for class |
| `requestManumission` | `requestManumission(User $serf): ManumissionRequest` | Serf requests freedom |
| `grantManumission` | `grantManumission(ManumissionRequest $request, User $granter): array` | Grants freedom to serf |

### CrimeService

Accusations, trials, and punishments (fines, jail, exile, outlawry).

| Method | Signature | Description |
|--------|-----------|-------------|
| `fileAccusation` | `fileAccusation(User $accuser, User $accused, string $crimeType, ?string $evidence): CrimeAccusation` | Files criminal accusation |
| `startTrial` | `startTrial(CrimeAccusation $accusation, User $judge): Trial` | Starts a trial |
| `renderVerdict` | `renderVerdict(Trial $trial, string $verdict, ?string $punishment): array` | Renders trial verdict |
| `applyPunishment` | `applyPunishment(User $criminal, string $punishment, int $severity): void` | Applies punishment |
| `serveJailTime` | `serveJailTime(User $prisoner): array` | Processes jail time |
| `pardon` | `pardon(User $criminal, User $pardoner): array` | Pardons a criminal |
| `declareOutlaw` | `declareOutlaw(User $criminal): void` | Declares user an outlaw |

### LegitimacyService

Political legitimacy tracking for role holders.

| Method | Signature | Description |
|--------|-----------|-------------|
| `calculateLegitimacy` | `calculateLegitimacy(PlayerTitle $title): int` | Calculates current legitimacy |
| `handleElectionResult` | `handleElectionResult(PlayerTitle $title, Election $election): void` | Updates legitimacy after election |
| `handleNoConfidenceSurvived` | `handleNoConfidenceSurvived(PlayerTitle $title, NoConfidenceVote $vote): void` | Bonus for surviving no-confidence |
| `applyDecayTick` | `applyDecayTick(): void` | Applies daily legitimacy decay |
| `addLegitimacy` | `addLegitimacy(PlayerTitle $title, int $amount, string $reason): void` | Adds legitimacy |
| `removeLegitimacy` | `removeLegitimacy(PlayerTitle $title, int $amount, string $reason): void` | Removes legitimacy |

### GuildService

Guild creation, membership, elections, and benefits.

| Method | Signature | Description |
|--------|-----------|-------------|
| `createGuild` | `createGuild(User $founder, string $name, string $type, string $locationType, int $locationId): Guild` | Creates a new guild |
| `joinGuild` | `joinGuild(User $user, Guild $guild): array` | Joins a guild |
| `leaveGuild` | `leaveGuild(User $user, Guild $guild): array` | Leaves a guild |
| `promoteMembe` | `promoteMember(Guild $guild, User $member, string $rank): array` | Promotes guild member |
| `demoteMember` | `demoteMember(Guild $guild, User $member): array` | Demotes guild member |
| `expelMember` | `expelMember(Guild $guild, User $member, string $reason): array` | Expels member from guild |
| `holdElection` | `holdElection(Guild $guild): GuildElection` | Starts guildmaster election |
| `getGuildBenefits` | `getGuildBenefits(Guild $guild): array` | Gets guild membership benefits |
| `setPriceControl` | `setPriceControl(Guild $guild, Item $item, int $minPrice): array` | Sets minimum price for item |

---

## NPC Systems

### NpcLifecycleService

NPC aging and death processing.

| Method | Signature | Description |
|--------|-----------|-------------|
| `processAging` | `processAging(): array` | Ages all NPCs and handles deaths |
| `checkMortality` | `checkMortality(Npc $npc): bool` | Checks if NPC dies this tick |
| `handleDeath` | `handleDeath(Npc $npc, string $cause): void` | Handles NPC death |
| `spawnReplacementNpc` | `spawnReplacementNpc(string $role, string $locationType, int $locationId): Npc` | Spawns replacement NPC |

### NpcReproductionService

NPC marriages and births.

| Method | Signature | Description |
|--------|-----------|-------------|
| `processReproduction` | `processReproduction(): array` | Processes NPC reproduction |
| `arrangeMarriage` | `arrangeMarriage(Npc $npc1, Npc $npc2): NpcMarriage` | Arranges NPC marriage |
| `tryConceive` | `tryConceive(NpcMarriage $marriage): ?Npc` | Attempts conception |
| `birthChild` | `birthChild(NpcMarriage $marriage): Npc` | Births an NPC child |

### FoodConsumptionService

Village food consumption and starvation mechanics.

| Method | Signature | Description |
|--------|-----------|-------------|
| `processConsumption` | `processConsumption(): array` | Processes daily food consumption |
| `consumeVillageFood` | `consumeVillageFood(Village $village): array` | Consumes food in village |
| `checkStarvation` | `checkStarvation(Village $village): array` | Checks for starvation effects |
| `getFoodRequirement` | `getFoodRequirement(Village $village): int` | Gets daily food requirement |

---

## World Systems

### CalendarService

Game time progression (weeks/seasons/years).

| Method | Signature | Description |
|--------|-----------|-------------|
| `advanceDay` | `advanceDay(): WorldState` | Advances game by one day |
| `getCurrentDate` | `getCurrentDate(): array` | Gets current game date |
| `getSeason` | `getSeason(): string` | Gets current season |
| `getSeasonDay` | `getSeasonDay(): int` | Gets day within current season |
| `processSeasonChange` | `processSeasonChange(string $newSeason): void` | Handles season transition |
| `processYearEnd` | `processYearEnd(): void` | Handles year-end events |

### ResourceDecayService

Item spoilage with seasonal modifiers.

| Method | Signature | Description |
|--------|-----------|-------------|
| `processDecay` | `processDecay(): array` | Processes all item decay |
| `decayItem` | `decayItem(InventorySlot $slot): bool` | Decays a single item stack |
| `getDecayRate` | `getDecayRate(Item $item): float` | Gets decay rate for item |
| `getSeasonalDecayModifier` | `getSeasonalDecayModifier(): float` | Gets seasonal decay modifier |

### DisasterService

Natural disasters with building damage.

| Method | Signature | Description |
|--------|-----------|-------------|
| `checkForDisasters` | `checkForDisasters(string $currentSeason): array` | Checks and triggers disasters |
| `triggerDisaster` | `triggerDisaster(DisasterType $type, string $locationType, int $locationId, ?int $severity): Disaster` | Triggers a disaster |
| `endDisaster` | `endDisaster(Disaster $disaster): void` | Ends an active disaster |
| `processDailyDisasters` | `processDailyDisasters(): array` | Processes active disasters |
| `getActiveDisasters` | `getActiveDisasters(string $locationType, int $locationId): Collection` | Gets active disasters at location |

### DiseaseService

Disease outbreaks, infections, and quarantines.

| Method | Signature | Description |
|--------|-----------|-------------|
| `startOutbreak` | `startOutbreak(DiseaseType $diseaseType, string $locationType, int $locationId): DiseaseOutbreak` | Starts disease outbreak |
| `infectUser` | `infectUser(User $user, DiseaseType $diseaseType, ?DiseaseOutbreak $outbreak): array` | Infects a user |
| `hasImmunity` | `hasImmunity(User $user, DiseaseType $diseaseType): bool` | Checks for immunity |
| `processDailyTick` | `processDailyTick(): array` | Processes all active infections |
| `treatInfection` | `treatInfection(DiseaseInfection $infection): array` | Treats an infection |
| `issueQuarantine` | `issueQuarantine(DiseaseOutbreak $outbreak, string $locationType, int $locationId, User $orderedBy, ?string $reason): QuarantineOrder` | Issues quarantine order |
| `liftQuarantine` | `liftQuarantine(QuarantineOrder $order): void` | Lifts quarantine |

### FestivalService

Festivals and tournaments.

| Method | Signature | Description |
|--------|-----------|-------------|
| `scheduleFestival` | `scheduleFestival(FestivalType $type, string $locationType, int $locationId, DateTimeInterface $startsAt, ?User $organizer, ?string $customName): Festival` | Schedules a festival |
| `startFestival` | `startFestival(Festival $festival): Festival` | Starts a scheduled festival |
| `endFestival` | `endFestival(Festival $festival): Festival` | Ends a festival |
| `joinFestival` | `joinFestival(Festival $festival, User $user, string $role): array` | Joins festival as participant |
| `createTournament` | `createTournament(TournamentType $type, string $locationType, int $locationId, string $name, DateTimeInterface $registrationEndsAt, DateTimeInterface $startsAt, ?Festival $festival, ?User $sponsor, int $sponsorContribution): Tournament` | Creates a tournament |
| `registerForTournament` | `registerForTournament(Tournament $tournament, User $user): array` | Registers for tournament |
| `startTournament` | `startTournament(Tournament $tournament): array` | Starts tournament and generates bracket |
| `resolveMatch` | `resolveMatch(TournamentMatch $match): array` | Resolves a tournament match |
| `advanceTournament` | `advanceTournament(Tournament $tournament): array` | Advances to next round |
| `getUpcomingFestivals` | `getUpcomingFestivals(?string $locationType, ?int $locationId): Collection` | Gets upcoming festivals |
| `scheduleSeasonalFestivals` | `scheduleSeasonalFestivals(string $season, int $year): array` | Schedules seasonal festivals |

### HealerService

Healing and disease treatment at healer locations.

| Method | Signature | Description |
|--------|-----------|-------------|
| `canAccessHealer` | `canAccessHealer(User $user): bool` | Checks if user can access healer |
| `getHealingOptions` | `getHealingOptions(User $user): array` | Gets available healing options |
| `calculateCost` | `calculateCost(int $hpToRestore): int` | Calculates healing cost |
| `heal` | `heal(User $user, int $amount): array` | Heals the player |
| `healByOption` | `healByOption(User $user, string $optionId): array` | Heals using predefined option |
| `getHealerInfo` | `getHealerInfo(User $user): ?array` | Gets healer info for location |
| `getActiveInfection` | `getActiveInfection(User $user): ?DiseaseInfection` | Gets user's active infection |
| `getDiseaseTreatmentCost` | `getDiseaseTreatmentCost(DiseaseInfection $infection): int` | Gets disease treatment cost |
| `treatDisease` | `treatDisease(User $user, DiseaseInfection $infection): array` | Treats a disease |
| `getDiseaseInfo` | `getDiseaseInfo(User $user): ?array` | Gets disease info for healer page |

### TownBonusService

Role-based yield bonuses and stockpile contributions.

| Method | Signature | Description |
|--------|-----------|-------------|
| `getYieldBonus` | `getYieldBonus(User $user, string $activity): float` | Gets yield bonus for activity |
| `getContributionRate` | `getContributionRate(User $user, string $activity): float` | Gets contribution rate for activity |
| `calculateBonusQuantity` | `calculateBonusQuantity(float $yieldBonus, int $baseQuantity): int` | Calculates bonus quantity |
| `calculateContribution` | `calculateContribution(float $contributionRate, int $totalQuantity): int` | Calculates stockpile contribution |
| `contributeToStockpile` | `contributeToStockpile(User $user, int $itemId, int $quantity): bool` | Contributes to stockpile |
| `getBonusInfo` | `getBonusInfo(User $user): array` | Gets bonus info for display |
| `getCraftingActivity` | `getCraftingActivity(string $category): string` | Gets activity type for crafting category |

---

## Military & Warfare

### ArmyService

Army raising, unit recruitment, supply lines, and mercenaries.

| Method | Signature | Description |
|--------|-----------|-------------|
| `raiseArmy` | `raiseArmy(string $name, string $ownerType, int $ownerId, string $locationType, int $locationId, ?int $commanderId, ?int $npcCommanderId): Army` | Raises a new army |
| `recruitUnit` | `recruitUnit(Army $army, string $unitType, int $count): ArmyUnit` | Recruits soldiers into army |
| `calculateUpkeep` | `calculateUpkeep(Army $army): void` | Calculates army upkeep |
| `moveArmy` | `moveArmy(Army $army, string $locationType, int $locationId): Army` | Moves army to location |
| `encampArmy` | `encampArmy(Army $army): Army` | Sets army to encamped |
| `disbandArmy` | `disbandArmy(Army $army): void` | Disbands an army |
| `processDailyMaintenance` | `processDailyMaintenance(Army $army): array` | Processes daily maintenance |
| `establishSupplyLine` | `establishSupplyLine(Army $army, string $sourceType, int $sourceId, int $supplyRate, int $distance): SupplyLine` | Establishes supply line |
| `hireMercenaries` | `hireMercenaries(MercenaryCompany $company, User $hirer, ?string $hirerType, ?int $hirerEntityId, int $contractDays): MercenaryCompany` | Hires mercenary company |
| `releaseMercenaries` | `releaseMercenaries(MercenaryCompany $company): void` | Releases mercenaries |
| `getRecruitmentCost` | `getRecruitmentCost(string $unitType, int $count): int` | Gets recruitment cost |

### WarService

War declaration, participants, goals, and peace treaties.

| Method | Signature | Description |
|--------|-----------|-------------|
| `declareWar` | `declareWar(string $name, string $casusBelli, string $attackerType, int $attackerId, string $defenderType, int $defenderId, ?int $attackerKingdomId, ?int $defenderKingdomId): War` | Declares war |
| `hasActiveTruce` | `hasActiveTruce(string $attackerType, int $attackerId, string $defenderType, int $defenderId): bool` | Checks for active truce |
| `addParticipant` | `addParticipant(War $war, string $participantType, int $participantId, string $side, string $role, bool $isWarLeader): WarParticipant` | Adds war participant |
| `addWarGoal` | `addWarGoal(War $war, string $goalType, string $claimantType, int $claimantId, ?string $targetType, ?int $targetId, int $warScoreValue): WarGoal` | Adds war goal |
| `updateWarScore` | `updateWarScore(War $war, string $side, int $amount): void` | Updates war score |
| `offerPeace` | `offerPeace(War $war, string $treatyType, ?string $winnerSide, array $territoryChanges, int $goldPayment, int $truceDays): PeaceTreaty` | Offers peace treaty |
| `removeParticipant` | `removeParticipant(War $war, string $participantType, int $participantId): void` | Removes participant |
| `isAtWar` | `isAtWar(string $entityType, int $entityId): bool` | Checks if entity is at war |
| `getActiveWars` | `getActiveWars(string $entityType, int $entityId): Collection` | Gets active wars for entity |
| `updateContributionScore` | `updateContributionScore(WarParticipant $participant, int $amount): void` | Updates contribution score |

### BattleService

Field battle resolution with combat mechanics.

| Method | Signature | Description |
|--------|-----------|-------------|
| `initiateBattle` | `initiateBattle(array $attackerArmies, array $defenderArmies, string $locationType, int $locationId, ?War $war, string $battleType): Battle` | Initiates a battle |
| `processBattleTick` | `processBattleTick(Battle $battle): array` | Processes daily battle tick |
| `endBattle` | `endBattle(Battle $battle, string $status, ?string $winnerSide): void` | Ends a battle |

### SiegeService

Siege warfare with fortifications and assaults.

| Method | Signature | Description |
|--------|-----------|-------------|
| `startSiege` | `startSiege(Army $attackingArmy, string $targetType, int $targetId, ?War $war): Siege` | Starts a siege |
| `processSiegeTick` | `processSiegeTick(Siege $siege): array` | Processes daily siege tick |
| `addSiegeEquipment` | `addSiegeEquipment(Siege $siege, string $equipment, int $count): void` | Adds siege equipment |
| `attemptAssault` | `attemptAssault(Siege $siege): array` | Attempts siege assault |
| `captureSiege` | `captureSiege(Siege $siege): void` | Captures siege target |
| `liftSiege` | `liftSiege(Siege $siege): void` | Lifts siege (attackers withdraw) |

---

## Dynasty & Marriage

### DynastyService

Dynasty founding, members, succession, and alliances.

| Method | Signature | Description |
|--------|-----------|-------------|
| `foundDynasty` | `foundDynasty(User $founder, string $name, ?string $motto, string $successionType, string $genderLaw): Dynasty` | Founds a new dynasty |
| `addMember` | `addMember(Dynasty $dynasty, string $firstName, string $gender, ?DynastyMember $father, ?DynastyMember $mother, ?User $user, bool $isLegitimate): DynastyMember` | Adds member to dynasty |
| `recordDeath` | `recordDeath(DynastyMember $member, string $cause): void` | Records member death |
| `processSuccession` | `processSuccession(Dynasty $dynasty): ?DynastyMember` | Processes succession on death |
| `recalculateHeir` | `recalculateHeir(Dynasty $dynasty): void` | Recalculates heir |
| `disinherit` | `disinherit(DynastyMember $member, ?string $reason): void` | Disinherits a member |
| `formAlliance` | `formAlliance(Dynasty $dynasty1, Dynasty $dynasty2, string $allianceType, ?int $marriageId, array $terms, ?int $durationDays): DynastyAlliance` | Forms dynasty alliance |
| `breakAlliance` | `breakAlliance(DynastyAlliance $alliance, Dynasty $breakingDynasty): void` | Breaks an alliance |

### MarriageService

Marriage proposals, weddings, divorce, and births.

| Method | Signature | Description |
|--------|-----------|-------------|
| `propose` | `propose(DynastyMember $proposer, DynastyMember $proposed, int $offeredDowry, array $offeredItems, ?string $message, ?int $expiresInDays): MarriageProposal` | Creates marriage proposal |
| `acceptProposal` | `acceptProposal(MarriageProposal $proposal, ?string $responseMessage): Marriage` | Accepts proposal |
| `rejectProposal` | `rejectProposal(MarriageProposal $proposal, ?string $responseMessage): void` | Rejects proposal |
| `createMarriage` | `createMarriage(DynastyMember $spouse1, DynastyMember $spouse2, int $dowryAmount, array $dowryItems, string $marriageType, ?string $locationType, ?int $locationId): Marriage` | Creates marriage directly |
| `divorce` | `divorce(Marriage $marriage, ?string $reason): void` | Divorces a marriage |
| `annul` | `annul(Marriage $marriage, ?string $reason): void` | Annuls a marriage |
| `recordBirth` | `recordBirth(DynastyMember $mother, ?DynastyMember $father, string $childName, string $gender, ?Marriage $marriage, bool $isTwins): DynastyMember` | Records a birth |
| `expireOldProposals` | `expireOldProposals(): int` | Expires old proposals |
| `getMarriageCandidates` | `getMarriageCandidates(DynastyMember $member, int $limit): Collection` | Gets marriage candidates |

---

## Governance & Roles

### RoleService

Role management, appointments, and permissions.

| Method | Signature | Description |
|--------|-----------|-------------|
| `getRolesAtLocation` | `getRolesAtLocation(string $locationType, int $locationId): Collection` | Gets roles at location |
| `getUserRoles` | `getUserRoles(User $user): Collection` | Gets roles held by user |
| `getRoleAtLocation` | `getRoleAtLocation(Role $role, string $locationType, int $locationId): array` | Gets role with holder info |
| `selfAppoint` | `selfAppoint(User $user, Role $role, string $locationType, int $locationId): array` | Self-appoints to vacant role |
| `userResidesAt` | `userResidesAt(User $user, string $locationType, int $locationId): bool` | Checks if user resides at location |
| `appointRole` | `appointRole(User $user, Role $role, string $locationType, int $locationId, ?User $appointedBy, ?DateTimeInterface $expiresAt): array` | Appoints user to role |
| `removeFromRole` | `removeFromRole(PlayerRole $playerRole, User $removedBy, ?string $reason): array` | Removes user from role |
| `resignFromRole` | `resignFromRole(User $user, PlayerRole $playerRole): array` | Resigns from role |
| `hasPermission` | `hasPermission(User $user, string $permission, string $locationType, int $locationId): bool` | Checks user permission |
| `holdsRole` | `holdsRole(User $user, string $roleSlug, string $locationType, int $locationId): bool` | Checks if user holds role |
| `getRoleHolder` | `getRoleHolder(string $roleSlug, string $locationType, int $locationId): ?User` | Gets role holder |
| `paySalaries` | `paySalaries(): array` | Pays salaries to role holders |
| `ensureNpcExists` | `ensureNpcExists(Role $role, string $locationType, int $locationId): LocationNpc` | Ensures NPC exists for role |

### ElectionService

Elections and no-confidence votes.

| Method | Signature | Description |
|--------|-----------|-------------|
| `canSelfAppoint` | `canSelfAppoint(Village $village): bool` | Checks if self-appointment allowed |
| `selfAppoint` | `selfAppoint(User $user, Village $village, string $role): Election` | Self-appoints to village role |
| `startElection` | `startElection(string $type, ?string $role, Model $domain, User $initiator, ?int $durationHours): Election` | Starts an election |
| `declareCandidacy` | `declareCandidacy(Election $election, User $user, ?string $platform): ElectionCandidate` | Declares candidacy |
| `castVote` | `castVote(Election $election, User $voter, ElectionCandidate $candidate): ElectionVote` | Casts election vote |
| `finalizeElection` | `finalizeElection(Election $election): Election` | Finalizes election |
| `validateVoterEligibility` | `validateVoterEligibility(Election $election, User $user): bool` | Validates voter eligibility |
| `grantElectionTitle` | `grantElectionTitle(Election $election, User $user): PlayerTitle` | Grants title to winner |
| `getEligibleVoterCount` | `getEligibleVoterCount(Election $election): int` | Gets eligible voter count |
| `startNoConfidenceVote` | `startNoConfidenceVote(User $initiator, User $target, string $role, Model $domain, ?string $reason): NoConfidenceVote` | Starts no-confidence vote |
| `castNoConfidenceBallot` | `castNoConfidenceBallot(NoConfidenceVote $vote, User $voter, bool $voteForRemoval): NoConfidenceBallot` | Casts no-confidence ballot |
| `finalizeNoConfidenceVote` | `finalizeNoConfidenceVote(NoConfidenceVote $vote): NoConfidenceVote` | Finalizes no-confidence vote |
| `validateNoConfidenceEligibility` | `validateNoConfidenceEligibility(User $user, ?Model $domain): bool` | Validates no-confidence eligibility |
| `validateTargetHoldsRole` | `validateTargetHoldsRole(User $target, string $role, Model $domain): bool` | Validates target holds role |
| `getNoConfidenceEligibleVoterCount` | `getNoConfidenceEligibleVoterCount(Model $domain): int` | Gets eligible voter count |
| `getRoleHolder` | `getRoleHolder(string $role, Model $domain): ?User` | Gets role holder |

### RoleStockingService

Role-based market stocking permissions.

| Method | Signature | Description |
|--------|-----------|-------------|
| `getStockableItems` | `getStockableItems(User $user): Collection` | Gets items user can stock |
| `stockItem` | `stockItem(User $user, int $itemId, int $quantity): array` | Stocks item to market |
| `getManagedStockpile` | `getManagedStockpile(User $user): Collection` | Gets stockpile items user manages |
| `getRolesForItem` | `getRolesForItem(string $itemName): array` | Gets roles that can stock item (static) |

### JobService

Employment system with supervisors and wages.

| Method | Signature | Description |
|--------|-----------|-------------|
| `getAvailableJobs` | `getAvailableJobs(User $user, string $locationType, int $locationId): Collection` | Gets available jobs at location |
| `getCurrentEmployment` | `getCurrentEmployment(User $user): Collection` | Gets user's current jobs |
| `getEmploymentAtLocation` | `getEmploymentAtLocation(User $user, string $locationType, int $locationId): Collection` | Gets jobs at location |
| `applyForJob` | `applyForJob(User $user, EmploymentJob $job, string $locationType, int $locationId): array` | Applies for a job |
| `quitJob` | `quitJob(User $user, PlayerEmployment $employment): array` | Quits a job |
| `fireWorker` | `fireWorker(User $supervisor, PlayerEmployment $employment, ?string $reason): array` | Fires a worker |
| `getSupervisedWorkers` | `getSupervisedWorkers(User $supervisor, string $locationType, int $locationId): Collection` | Gets supervised workers |
| `work` | `work(User $user, PlayerEmployment $employment): array` | Performs work shift |
| `seedDefaultJobs` | `seedDefaultJobs(): void` | Seeds default jobs (static) |

---

## Religion

### ReligionService

Cults, religions, devotion, and religious structures.

| Method | Signature | Description |
|--------|-----------|-------------|
| `getAvailableReligions` | `getAvailableReligions(User $player): array` | Gets religions player can join |
| `getPlayerReligions` | `getPlayerReligions(User $player): array` | Gets player's religions |
| `getReligionDetails` | `getReligionDetails(Religion $religion, User $player): array` | Gets religion details |
| `createCult` | `createCult(User $player, string $name, string $description, array $beliefIds): array` | Creates a new cult |
| `joinReligion` | `joinReligion(User $player, int $religionId): array` | Joins a religion |
| `leaveReligion` | `leaveReligion(User $player, int $religionId): array` | Leaves a religion |
| `performAction` | `performAction(User $player, int $religionId, string $actionType, ?int $structureId, int $donationAmount): array` | Performs religious action |
| `promoteToPriest` | `promoteToPriest(User $player, int $memberId): array` | Promotes member to priest |
| `demoteToFollower` | `demoteToFollower(User $player, int $memberId): array` | Demotes priest to follower |
| `convertToReligion` | `convertToReligion(User $player, int $religionId): array` | Converts cult to religion |
| `buildStructure` | `buildStructure(User $player, int $religionId, string $structureType, string $locationType, int $locationId): array` | Builds religious structure |
| `setKingdomReligionStatus` | `setKingdomReligionStatus(User $player, int $kingdomId, int $religionId, string $status): array` | Sets kingdom religion status |
| `getStructuresAtLocation` | `getStructuresAtLocation(string $locationType, int $locationId): array` | Gets structures at location |
| `getAllBeliefs` | `getAllBeliefs(): array` | Gets all available beliefs |
| `makePublic` | `makePublic(User $player, int $religionId): array` | Makes religion public |

---

## Testing Notes

When writing Pest tests for these services:

1. **Mock dependencies** - Most services use constructor injection. Mock the injected services.
2. **Database transactions** - Many methods use `DB::transaction()`. Consider using `RefreshDatabase` trait.
3. **User state** - Many methods check user state (traveling, location, energy). Set up fixtures carefully.
4. **Return arrays** - Most public methods return `array` with `'success'` key for easy assertions.
5. **Model factories** - Create factories for all models used by these services.
6. **Edge cases** - Test boundaries like empty inventories, zero gold, expired timestamps.
7. **Permission checks** - Many services have authorization logic to test.
8. **Event triggers** - Some services may dispatch events; mock or assert on these.

### Priority Order for Testing

1. **Core player systems**: EnergyService, InventoryService, TravelService
2. **Economy**: BankService, MarketService, TaxService
3. **Combat**: CombatService, LootService, DungeonService
4. **Skills**: TrainingService, GatheringService, CraftingService
5. **Social**: RoleService, ElectionService, GuildService
6. **World**: CalendarService, DisasterService, DiseaseService
7. **Dynasty**: DynastyService, MarriageService
8. **Military**: ArmyService, WarService, BattleService, SiegeService
9. **Religion**: ReligionService
