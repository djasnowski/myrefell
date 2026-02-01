<?php

namespace App\Http\Controllers;

use App\Services\MinigameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MinigameController extends Controller
{
    public function __construct(
        protected MinigameService $minigameService
    ) {}

    /**
     * Display the minigames page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $playerInfo = $this->minigameService->getPlayerInfo($user);
        $rewardsHistory = $this->minigameService->getRewardsHistory($user);

        return Inertia::render('Minigames/Index', [
            'can_play' => $playerInfo['can_play'],
            'streak' => $playerInfo['streak'],
            'last_played' => $playerInfo['last_play'],
            'reward_chances' => $playerInfo['reward_chances'],
            'recent_plays' => $this->mapRecentPlays($rewardsHistory),
        ]);
    }

    /**
     * Map recent plays to frontend format.
     */
    protected function mapRecentPlays(array $plays): array
    {
        return array_map(fn (array $play, int $index) => [
            'id' => $index,
            'reward_type' => $play['reward_item'] ? 'item' : 'gold',
            'reward_amount' => $play['reward_value'],
            'reward_rarity' => $play['reward_type'],
            'item_name' => $play['reward_item'],
            'played_at' => $play['played_at'],
        ], $plays, array_keys($plays));
    }

    /**
     * Spin the wheel of fortune.
     */
    public function spin(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $result = $this->minigameService->play($user);

            if (! $result['success']) {
                return back()->with('error', $result['error'] ?? 'You have already played today.');
            }

            // Map to frontend expected format
            $spinResult = [
                'success' => true,
                'reward_type' => $result['reward_item'] ? 'item' : 'gold',
                'reward_amount' => $result['reward_value'],
                'reward_item' => $result['reward_item'],
                'rarity' => $result['reward_type'],
                'message' => $this->formatRewardMessage($result),
                'new_streak' => $result['streak'],
                'segment_index' => $this->calculateSegmentIndex($result),
            ];

            // Only send result data - don't send success flash to avoid
            // revealing reward before wheel animation completes
            return back()->with('result', $spinResult);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Calculate which wheel segment to land on based on result.
     */
    protected function calculateSegmentIndex(array $result): int
    {
        // Map rewards to segment indexes
        // Segments: 0=50g, 1=rare item, 2=100g, 3=150g, 4=epic item, 5=200g,
        //           6=mystery, 7=300g, 8=500g, 9=jackpot, 10=750g, 11=1000g

        // Mystery Box always lands on segment 6
        if ($result['reward_type'] === 'mystery') {
            return 6;
        }

        // Items land on their rarity segment
        if ($result['reward_item']) {
            return $result['reward_type'] === 'epic' ? 4 : 1;
        }

        $gold = $result['reward_value'];

        return match (true) {
            $gold <= 75 => 0,    // 50 Gold
            $gold <= 125 => 2,   // 100 Gold
            $gold <= 175 => 3,   // 150 Gold
            $gold <= 250 => 5,   // 200 Gold
            $gold <= 400 => 7,   // 300 Gold
            $gold <= 600 => 8,   // 500 Gold
            $gold <= 875 => 10,  // 750 Gold
            default => 11,       // 1000 Gold
        };
    }

    /**
     * Format reward message for display.
     */
    protected function formatRewardMessage(array $result): string
    {
        if (! $result['success']) {
            return $result['error'] ?? 'Something went wrong.';
        }

        $parts = [];
        $rewardType = ucfirst($result['reward_type'] ?? 'unknown');

        if ($result['reward_value'] > 0) {
            $parts[] = "{$result['reward_value']} gold";
        }

        if ($result['reward_item']) {
            $parts[] = $result['reward_item']['name'];
        }

        if (empty($parts)) {
            return 'You spun the wheel!';
        }

        return "{$rewardType} reward! You won: ".implode(' and ', $parts);
    }
}
