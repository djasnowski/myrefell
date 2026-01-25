<?php

namespace App\Http\Controllers;

use App\Models\Castle;
use App\Models\Kingdom;
use App\Models\Village;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    public function __construct(
        protected TaxService $taxService
    ) {}

    /**
     * Display taxes at a village.
     */
    public function villageTaxes(Request $request, Village $village): Response
    {
        $user = $request->user();

        // Check if player is at this village
        if ($user->current_location_type !== 'village' || $user->current_location_id !== $village->id) {
            return Inertia::render('Taxes/NotHere', [
                'location' => $village->name,
            ]);
        }

        return $this->renderTaxesPage($user, 'village', $village->id, $village->name, $village->castle);
    }

    /**
     * Display taxes at a castle.
     */
    public function castleTaxes(Request $request, Castle $castle): Response
    {
        $user = $request->user();

        // Check if player is at this castle
        if ($user->current_location_type !== 'castle' || $user->current_location_id !== $castle->id) {
            return Inertia::render('Taxes/NotHere', [
                'location' => $castle->name,
            ]);
        }

        return $this->renderTaxesPage($user, 'castle', $castle->id, $castle->name, null, $castle->kingdom);
    }

    /**
     * Display taxes at a kingdom.
     */
    public function kingdomTaxes(Request $request, Kingdom $kingdom): Response
    {
        $user = $request->user();

        return $this->renderTaxesPage($user, 'kingdom', $kingdom->id, $kingdom->name);
    }

    /**
     * Render the taxes page for any location type.
     */
    protected function renderTaxesPage(
        $user,
        string $locationType,
        int $locationId,
        string $locationName,
        ?Castle $castle = null,
        ?Kingdom $kingdom = null
    ): Response {
        $treasuryInfo = $this->taxService->getTreasuryInfo($locationType, $locationId);
        $transactions = $this->taxService->getTreasuryTransactions($locationType, $locationId);
        $canConfigureTaxes = $this->taxService->canConfigureTaxes($user, $locationType, $locationId);

        // Get user's personal tax history if viewing village
        $userTaxHistory = null;
        $userSalaryHistory = null;
        if ($locationType === 'village') {
            $userTaxHistory = $this->taxService->getUserTaxHistory($user, 10);
            $userSalaryHistory = $this->taxService->getUserSalaryHistory($user, 10);
        }

        return Inertia::render('Taxes/Index', [
            'location_type' => $locationType,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'treasury' => $treasuryInfo,
            'transactions' => $transactions,
            'can_configure' => $canConfigureTaxes,
            'user_tax_history' => $userTaxHistory,
            'user_salary_history' => $userSalaryHistory,
            'min_tax_rate' => TaxService::MIN_TAX_RATE,
            'max_tax_rate' => TaxService::MAX_TAX_RATE,
            'castle' => $castle ? [
                'id' => $castle->id,
                'name' => $castle->name,
                'tax_rate' => $castle->tax_rate,
            ] : null,
            'kingdom' => $kingdom ? [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'tax_rate' => $kingdom->tax_rate,
            ] : null,
            'player' => [
                'id' => $user->id,
                'username' => $user->username,
                'gold' => $user->gold,
            ],
        ]);
    }

    /**
     * Set the tax rate for a castle or kingdom.
     */
    public function setTaxRate(Request $request): JsonResponse
    {
        $request->validate([
            'location_type' => 'required|in:castle,kingdom',
            'location_id' => 'required|integer',
            'tax_rate' => 'required|numeric|min:0|max:50',
        ]);

        $user = $request->user();
        $locationType = $request->location_type;
        $locationId = $request->location_id;

        // Check permission
        if (!$this->taxService->canConfigureTaxes($user, $locationType, $locationId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to configure taxes here.',
            ], 403);
        }

        $result = $this->taxService->setTaxRate(
            $locationType,
            $locationId,
            (float) $request->tax_rate,
            $user
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get user's tax history.
     */
    public function myTaxes(Request $request): Response
    {
        $user = $request->user();
        $taxHistory = $this->taxService->getUserTaxHistory($user, 50);
        $salaryHistory = $this->taxService->getUserSalaryHistory($user, 50);

        return Inertia::render('Taxes/MyTaxes', [
            'tax_history' => $taxHistory,
            'salary_history' => $salaryHistory,
            'player' => [
                'id' => $user->id,
                'username' => $user->username,
                'gold' => $user->gold,
            ],
        ]);
    }

    /**
     * Get treasury status (for API/polling).
     */
    public function treasuryStatus(Request $request): JsonResponse
    {
        $request->validate([
            'location_type' => 'required|in:village,castle,kingdom',
            'location_id' => 'required|integer',
        ]);

        $treasuryInfo = $this->taxService->getTreasuryInfo(
            $request->location_type,
            $request->location_id
        );

        return response()->json($treasuryInfo);
    }
}
