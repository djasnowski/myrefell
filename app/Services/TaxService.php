<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\LocationTreasury;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\TaxCollection;
use App\Models\Town;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxService
{
    /**
     * Default tax rate if not configured (10%).
     */
    public const DEFAULT_TAX_RATE = 10;

    /**
     * Minimum tax rate (0%).
     */
    public const MIN_TAX_RATE = 0;

    /**
     * Maximum tax rate (50%).
     */
    public const MAX_TAX_RATE = 50;

    /**
     * Get the treasury for a location, creating if necessary.
     */
    public function getTreasury(string $locationType, int $locationId): LocationTreasury
    {
        return LocationTreasury::getOrCreate($locationType, $locationId);
    }

    /**
     * Get treasury info formatted for display.
     */
    public function getTreasuryInfo(string $locationType, int $locationId): array
    {
        $treasury = $this->getTreasury($locationType, $locationId);
        $location = $this->resolveLocation($locationType, $locationId);

        return [
            'id' => $treasury->id,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'location_name' => $location?->name ?? 'Unknown',
            'balance' => $treasury->balance,
            'total_collected' => $treasury->total_collected,
            'total_distributed' => $treasury->total_distributed,
            'tax_rate' => $this->getTaxRate($locationType, $locationId),
        ];
    }

    /**
     * Get the tax rate for a location.
     * Baronies and kingdoms have tax_rate fields; villages use their barony's rate.
     */
    public function getTaxRate(string $locationType, int $locationId): float
    {
        $location = $this->resolveLocation($locationType, $locationId);

        if (! $location) {
            return self::DEFAULT_TAX_RATE;
        }

        return match ($locationType) {
            'kingdom' => (float) ($location->tax_rate ?? self::DEFAULT_TAX_RATE),
            'barony' => (float) ($location->tax_rate ?? self::DEFAULT_TAX_RATE),
            'village' => (float) ($location->barony?->tax_rate ?? self::DEFAULT_TAX_RATE),
            default => self::DEFAULT_TAX_RATE,
        };
    }

    /**
     * Set the tax rate for a location (barony or kingdom only).
     */
    public function setTaxRate(string $locationType, int $locationId, float $rate, User $setBy): array
    {
        if (! in_array($locationType, ['barony', 'kingdom'])) {
            return [
                'success' => false,
                'message' => 'Tax rates can only be set for baronies and kingdoms.',
            ];
        }

        if ($rate < self::MIN_TAX_RATE || $rate > self::MAX_TAX_RATE) {
            return [
                'success' => false,
                'message' => 'Tax rate must be between '.self::MIN_TAX_RATE.'% and '.self::MAX_TAX_RATE.'%.',
            ];
        }

        $location = $this->resolveLocation($locationType, $locationId);

        if (! $location) {
            return [
                'success' => false,
                'message' => 'Location not found.',
            ];
        }

        $location->update(['tax_rate' => $rate]);

        Log::info('Tax rate updated', [
            'location_type' => $locationType,
            'location_id' => $locationId,
            'new_rate' => $rate,
            'set_by' => $setBy->id,
        ]);

        return [
            'success' => true,
            'message' => "Tax rate set to {$rate}%.",
            'tax_rate' => $rate,
        ];
    }

    /**
     * Collect daily taxes from all players and propagate up the hierarchy.
     * Flow: Players -> Villages -> Baronies -> Kingdoms
     */
    public function collectDailyTaxes(): array
    {
        $today = now()->toDateString();
        $results = [
            'players_taxed' => 0,
            'player_tax_total' => 0,
            'village_upstream_total' => 0,
            'town_upstream_total' => 0,
            'barony_upstream_total' => 0,
        ];

        DB::transaction(function () use ($today, &$results) {
            // Step 1: Collect taxes from players to their home location (village, town, or barony)
            $results = array_merge($results, $this->collectPlayerTaxes($today));

            // Step 2: Villages pay taxes to their baronies
            $results['village_upstream_total'] = $this->collectUpstreamTaxes('village', $today);

            // Step 3: Towns pay taxes to their baronies
            $results['town_upstream_total'] = $this->collectUpstreamTaxes('town', $today);

            // Step 4: Baronies pay taxes to their kingdoms
            $results['barony_upstream_total'] = $this->collectUpstreamTaxes('barony', $today);
        });

        return $results;
    }

    /**
     * Collect taxes from all players to their home location (village, town, or barony).
     */
    protected function collectPlayerTaxes(string $taxPeriod): array
    {
        $playersTaxed = 0;
        $totalCollected = 0;

        // Get all players with gold who have a home location
        User::where('gold', '>', 0)
            ->where(function ($query) {
                $query->whereNotNull('home_village_id')
                    ->orWhereNotNull('home_location_type');
            })
            ->with(['homeVillage.barony'])
            ->chunk(100, function ($users) use ($taxPeriod, &$playersTaxed, &$totalCollected) {
                foreach ($users as $user) {
                    // Determine home location type and ID
                    $locationType = null;
                    $locationId = null;

                    if ($user->home_village_id) {
                        $locationType = 'village';
                        $locationId = $user->home_village_id;
                    } elseif ($user->home_location_type && $user->home_location_id) {
                        $locationType = $user->home_location_type;
                        $locationId = $user->home_location_id;
                    }

                    if (! $locationType || ! $locationId) {
                        continue;
                    }

                    // Calculate tax based on location's tax rate
                    $taxRate = $this->getTaxRate($locationType, $locationId);
                    $taxAmount = (int) floor($user->gold * ($taxRate / 100));

                    if ($taxAmount <= 0) {
                        continue;
                    }

                    // Deduct from player
                    $user->decrement('gold', $taxAmount);

                    // Add to location's treasury
                    $treasury = $this->getTreasury($locationType, $locationId);
                    $treasury->deposit(
                        $taxAmount,
                        TreasuryTransaction::TYPE_TAX_INCOME,
                        "Income tax from {$user->username}",
                        $user->id
                    );

                    // Record the tax collection
                    TaxCollection::create([
                        'payer_user_id' => $user->id,
                        'receiver_location_type' => $locationType,
                        'receiver_location_id' => $locationId,
                        'amount' => $taxAmount,
                        'tax_type' => TaxCollection::TYPE_INCOME,
                        'description' => 'Daily income tax',
                        'tax_period' => $taxPeriod,
                    ]);

                    $playersTaxed++;
                    $totalCollected += $taxAmount;
                }
            });

        return [
            'players_taxed' => $playersTaxed,
            'player_tax_total' => $totalCollected,
        ];
    }

    /**
     * Collect upstream taxes (village/town -> barony -> kingdom).
     */
    protected function collectUpstreamTaxes(string $fromLocationType, string $taxPeriod): int
    {
        $totalCollected = 0;

        if ($fromLocationType === 'town') {
            // Towns pay to their baronies
            Town::whereNotNull('barony_id')
                ->with('barony')
                ->chunk(100, function ($towns) use ($taxPeriod, &$totalCollected) {
                    foreach ($towns as $town) {
                        $barony = $town->barony;
                        if (! $barony) {
                            continue;
                        }

                        $townTreasury = $this->getTreasury('town', $town->id);
                        if ($townTreasury->balance <= 0) {
                            continue;
                        }

                        // Use barony's tax rate
                        $taxRate = $this->getTaxRate('barony', $barony->id);
                        $taxAmount = (int) floor($townTreasury->balance * ($taxRate / 100));

                        if ($taxAmount <= 0) {
                            continue;
                        }

                        // Withdraw from town
                        $townTreasury->withdraw(
                            $taxAmount,
                            TreasuryTransaction::TYPE_UPSTREAM_TAX,
                            "Upstream tax to {$barony->name}",
                            null,
                            'barony',
                            $barony->id
                        );

                        // Deposit to barony
                        $baronyTreasury = $this->getTreasury('barony', $barony->id);
                        $baronyTreasury->deposit(
                            $taxAmount,
                            TreasuryTransaction::TYPE_TAX_INCOME,
                            "Tax from {$town->name}",
                            null,
                            'town',
                            $town->id
                        );

                        // Record the collection
                        TaxCollection::create([
                            'payer_location_type' => 'town',
                            'payer_location_id' => $town->id,
                            'receiver_location_type' => 'barony',
                            'receiver_location_id' => $barony->id,
                            'amount' => $taxAmount,
                            'tax_type' => TaxCollection::TYPE_UPSTREAM,
                            'description' => 'Town upstream tax',
                            'tax_period' => $taxPeriod,
                        ]);

                        $totalCollected += $taxAmount;
                    }
                });
        } elseif ($fromLocationType === 'village') {
            // Villages pay to their baronies
            Village::whereNotNull('barony_id')
                ->with('barony')
                ->chunk(100, function ($villages) use ($taxPeriod, &$totalCollected) {
                    foreach ($villages as $village) {
                        $barony = $village->barony;
                        if (! $barony) {
                            continue;
                        }

                        $villageTreasury = $this->getTreasury('village', $village->id);
                        if ($villageTreasury->balance <= 0) {
                            continue;
                        }

                        // Use barony's tax rate
                        $taxRate = $this->getTaxRate('barony', $barony->id);
                        $baseTaxAmount = (int) floor($villageTreasury->balance * ($taxRate / 100));

                        if ($baseTaxAmount <= 0) {
                            continue;
                        }

                        // Apply Town Clerk efficiency bonus (if any)
                        $efficiencyMultiplier = $this->getTownClerkEfficiencyBonus($barony->id);
                        $taxAmount = (int) floor($baseTaxAmount * $efficiencyMultiplier);
                        $bonusAmount = $taxAmount - $baseTaxAmount;

                        // Withdraw from village
                        $villageTreasury->withdraw(
                            $baseTaxAmount,
                            TreasuryTransaction::TYPE_UPSTREAM_TAX,
                            "Upstream tax to {$barony->name}",
                            null,
                            'barony',
                            $barony->id
                        );

                        // Deposit to barony (includes efficiency bonus)
                        $baronyTreasury = $this->getTreasury('barony', $barony->id);
                        $description = $bonusAmount > 0
                            ? "Tax from {$village->name} (+{$bonusAmount}g clerk bonus)"
                            : "Tax from {$village->name}";
                        $baronyTreasury->deposit(
                            $taxAmount,
                            TreasuryTransaction::TYPE_TAX_INCOME,
                            $description,
                            null,
                            'village',
                            $village->id
                        );

                        // Record the collection
                        TaxCollection::create([
                            'payer_location_type' => 'village',
                            'payer_location_id' => $village->id,
                            'receiver_location_type' => 'barony',
                            'receiver_location_id' => $barony->id,
                            'amount' => $taxAmount,
                            'tax_type' => TaxCollection::TYPE_UPSTREAM,
                            'description' => $bonusAmount > 0 ? "Village upstream tax (+{$bonusAmount}g efficiency bonus)" : 'Village upstream tax',
                            'tax_period' => $taxPeriod,
                        ]);

                        $totalCollected += $taxAmount;
                    }
                });
        } elseif ($fromLocationType === 'barony') {
            // Baronies pay to their kingdoms
            Barony::whereNotNull('kingdom_id')
                ->with('kingdom')
                ->chunk(100, function ($baronies) use ($taxPeriod, &$totalCollected) {
                    foreach ($baronies as $barony) {
                        $kingdom = $barony->kingdom;
                        if (! $kingdom) {
                            continue;
                        }

                        $baronyTreasury = $this->getTreasury('barony', $barony->id);
                        if ($baronyTreasury->balance <= 0) {
                            continue;
                        }

                        // Use kingdom's tax rate
                        $taxRate = $this->getTaxRate('kingdom', $kingdom->id);
                        $taxAmount = (int) floor($baronyTreasury->balance * ($taxRate / 100));

                        if ($taxAmount <= 0) {
                            continue;
                        }

                        // Withdraw from barony
                        $baronyTreasury->withdraw(
                            $taxAmount,
                            TreasuryTransaction::TYPE_UPSTREAM_TAX,
                            "Upstream tax to {$kingdom->name}",
                            null,
                            'kingdom',
                            $kingdom->id
                        );

                        // Deposit to kingdom
                        $kingdomTreasury = $this->getTreasury('kingdom', $kingdom->id);
                        $kingdomTreasury->deposit(
                            $taxAmount,
                            TreasuryTransaction::TYPE_TAX_INCOME,
                            "Tax from {$barony->name}",
                            null,
                            'barony',
                            $barony->id
                        );

                        // Record the collection
                        TaxCollection::create([
                            'payer_location_type' => 'barony',
                            'payer_location_id' => $barony->id,
                            'receiver_location_type' => 'kingdom',
                            'receiver_location_id' => $kingdom->id,
                            'amount' => $taxAmount,
                            'tax_type' => TaxCollection::TYPE_UPSTREAM,
                            'description' => 'Barony upstream tax',
                            'tax_period' => $taxPeriod,
                        ]);

                        $totalCollected += $taxAmount;
                    }
                });
        }

        return $totalCollected;
    }

    /**
     * Distribute salaries to all active role holders from their location's treasury.
     */
    public function distributeSalaries(): array
    {
        $today = now()->toDateString();
        $results = [
            'salaries_paid' => 0,
            'total_amount' => 0,
            'failed' => 0,
        ];

        DB::transaction(function () use ($today, &$results) {
            PlayerRole::active()
                ->with(['user', 'role'])
                ->whereHas('role', fn ($q) => $q->where('salary', '>', 0))
                ->chunk(100, function ($playerRoles) use ($today, &$results) {
                    foreach ($playerRoles as $playerRole) {
                        // Check if already paid today
                        $alreadyPaid = SalaryPayment::where('player_role_id', $playerRole->id)
                            ->where('pay_period', $today)
                            ->exists();

                        if ($alreadyPaid) {
                            continue;
                        }

                        $salary = $playerRole->role->salary;
                        $locationType = $playerRole->location_type;
                        $locationId = $playerRole->location_id;

                        // Get the treasury for this location
                        $treasury = $this->getTreasury($locationType, $locationId);

                        // Check if treasury has enough funds
                        if ($treasury->balance < $salary) {
                            Log::warning('Insufficient treasury funds for salary', [
                                'player_role_id' => $playerRole->id,
                                'user_id' => $playerRole->user_id,
                                'salary' => $salary,
                                'treasury_balance' => $treasury->balance,
                            ]);
                            $results['failed']++;

                            continue;
                        }

                        // Withdraw from treasury
                        $treasury->withdraw(
                            $salary,
                            TreasuryTransaction::TYPE_SALARY_PAYMENT,
                            "Salary for {$playerRole->role->name}: {$playerRole->user->username}",
                            $playerRole->user_id
                        );

                        // Pay the user
                        $playerRole->user->increment('gold', $salary);
                        $playerRole->increment('total_salary_earned', $salary);

                        // Record the payment
                        SalaryPayment::create([
                            'user_id' => $playerRole->user_id,
                            'player_role_id' => $playerRole->id,
                            'amount' => $salary,
                            'source_location_type' => $locationType,
                            'source_location_id' => $locationId,
                            'pay_period' => $today,
                        ]);

                        $results['salaries_paid']++;
                        $results['total_amount'] += $salary;
                    }
                });
        });

        return $results;
    }

    /**
     * Get tax history for a user.
     */
    public function getUserTaxHistory(User $user, int $limit = 20): Collection
    {
        return TaxCollection::where('payer_user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($tax) => [
                'id' => $tax->id,
                'amount' => $tax->amount,
                'tax_type' => $tax->tax_type,
                'receiver_type' => $tax->receiver_location_type,
                'receiver_name' => $tax->receiver_location_name,
                'description' => $tax->description,
                'tax_period' => $tax->tax_period->toDateString(),
                'created_at' => $tax->created_at->toISOString(),
                'formatted_date' => $tax->created_at->format('M j, g:i A'),
            ]);
    }

    /**
     * Get salary history for a user.
     */
    public function getUserSalaryHistory(User $user, int $limit = 20): Collection
    {
        return SalaryPayment::where('user_id', $user->id)
            ->with(['playerRole.role'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'role_name' => $payment->playerRole?->role?->name ?? 'Unknown Role',
                'source_type' => $payment->source_location_type,
                'source_name' => $payment->source_location_name,
                'pay_period' => $payment->pay_period->toDateString(),
                'created_at' => $payment->created_at->toISOString(),
                'formatted_date' => $payment->created_at->format('M j, g:i A'),
            ]);
    }

    /**
     * Get treasury transactions for a location.
     */
    public function getTreasuryTransactions(string $locationType, int $locationId, int $limit = 20): Collection
    {
        $treasury = $this->getTreasury($locationType, $locationId);

        return $treasury->transactions()
            ->with('relatedUser')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => $tx->amount,
                'balance_after' => $tx->balance_after,
                'description' => $tx->description,
                'related_user' => $tx->relatedUser ? [
                    'id' => $tx->relatedUser->id,
                    'username' => $tx->relatedUser->username,
                ] : null,
                'created_at' => $tx->created_at->toISOString(),
                'formatted_date' => $tx->created_at->format('M j, g:i A'),
            ]);
    }

    /**
     * Resolve a location model by type and ID.
     */
    protected function resolveLocation(string $type, int $id): ?object
    {
        $modelClass = match ($type) {
            'village' => Village::class,
            'barony' => Barony::class,
            'kingdom' => Kingdom::class,
            default => null,
        };

        if (! $modelClass) {
            return null;
        }

        return $modelClass::find($id);
    }

    /**
     * Check if a user can configure taxes at a location.
     */
    public function canConfigureTaxes(User $user, string $locationType, int $locationId): bool
    {
        // Admins can always configure
        if ($user->isAdmin()) {
            return true;
        }

        // Check for appropriate role permission
        $requiredPermission = match ($locationType) {
            'barony' => 'set_taxes',
            'kingdom' => 'set_kingdom_taxes',
            default => null,
        };

        if (! $requiredPermission) {
            return false;
        }

        // Check if user has a role with this permission at this location
        return PlayerRole::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->active()
            ->whereHas('role', function ($q) use ($requiredPermission) {
                $q->whereJsonContains('permissions', $requiredPermission);
            })
            ->exists();
    }

    /**
     * Get the total Town Clerk efficiency bonus for a barony.
     * This bonus increases tax collection from villages.
     *
     * @return float The multiplier (e.g., 1.10 for 10% bonus)
     */
    protected function getTownClerkEfficiencyBonus(int $baronyId): float
    {
        // Get the town_clerk role
        $townClerkRole = Role::where('slug', 'town_clerk')->first();
        if (! $townClerkRole) {
            return 1.0;
        }

        // Find all towns in this barony with an active town clerk
        $towns = Town::where('barony_id', $baronyId)->pluck('id');
        if ($towns->isEmpty()) {
            return 1.0;
        }

        // Get active town clerks in these towns
        $activeClerk = PlayerRole::active()
            ->where('role_id', $townClerkRole->id)
            ->where('location_type', 'town')
            ->whereIn('location_id', $towns)
            ->with('role')
            ->first();

        if (! $activeClerk) {
            return 1.0;
        }

        // Get the efficiency bonus from the role
        $efficiencyBonus = $activeClerk->role->getBonus('efficiency_bonus', 0);

        // Convert percentage to multiplier (e.g., 10 -> 1.10)
        return 1.0 + ($efficiencyBonus / 100);
    }
}
