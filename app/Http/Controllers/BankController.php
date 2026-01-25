<?php

namespace App\Http\Controllers;

use App\Services\BankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankController extends Controller
{
    public function __construct(
        protected BankService $bankService
    ) {}

    /**
     * Show the bank page for a village.
     */
    public function villageBank(Request $request, int $villageId): Response
    {
        return $this->showBank($request, 'village', $villageId);
    }

    /**
     * Show the bank page for a barony.
     */
    public function baronyBank(Request $request, int $baronyId): Response
    {
        return $this->showBank($request, 'barony', $baronyId);
    }

    /**
     * Show the bank page for a town.
     */
    public function townBank(Request $request, int $townId): Response
    {
        return $this->showBank($request, 'town', $townId);
    }

    /**
     * Show the bank page.
     */
    protected function showBank(Request $request, string $locationType, int $locationId): Response
    {
        $user = $request->user();

        // Check if player is at this location
        if ($user->current_location_type !== $locationType || $user->current_location_id !== $locationId) {
            return Inertia::render('Bank/NotHere', [
                'message' => 'You must be at this location to access its bank.',
            ]);
        }

        if (! $this->bankService->canAccessBank($user)) {
            return Inertia::render('Bank/NotHere', [
                'message' => 'You cannot access a bank while traveling.',
            ]);
        }

        $bankInfo = $this->bankService->getBankInfo($user);
        $transactions = $this->bankService->getRecentTransactions($user);
        $allAccounts = $this->bankService->getAllAccounts($user);
        $totalBalance = $this->bankService->getTotalBalance($user);

        return Inertia::render('Bank/Index', [
            'bank_info' => $bankInfo,
            'transactions' => $transactions,
            'all_accounts' => $allAccounts,
            'total_balance' => $totalBalance,
        ]);
    }

    /**
     * Deposit gold.
     */
    public function deposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->bankService->deposit($user, $request->input('amount'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Withdraw gold.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->bankService->withdraw($user, $request->input('amount'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get current balance (for polling).
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $bankInfo = $this->bankService->getBankInfo($user);

        if (! $bankInfo) {
            return response()->json(['error' => 'Cannot access bank'], 422);
        }

        return response()->json($bankInfo);
    }
}
