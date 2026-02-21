<?php

namespace App\Services;

use App\Models\Broadsheet;
use App\Models\BroadsheetComment;
use App\Models\BroadsheetReaction;
use App\Models\BroadsheetView;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BroadsheetService
{
    public const BARONY_THRESHOLD = 5;

    public const KINGDOM_THRESHOLD = 15;

    /**
     * Publish cost by location type.
     *
     * @var array<string, int>
     */
    public const PUBLISH_COSTS = [
        'village' => 50,
        'town' => 50,
        'barony' => 100,
        'duchy' => 150,
        'kingdom' => 200,
    ];

    /**
     * Get the publish cost for a given location type.
     */
    public function getPublishCost(?string $locationType): int
    {
        return self::PUBLISH_COSTS[$locationType] ?? Broadsheet::PUBLISH_COST;
    }

    /**
     * Publish a new broadsheet at the player's current location.
     *
     * @param  array{type: string, id: int, name: string, barony_id: ?int, barony_name: ?string, kingdom_id: int, kingdom_name: string}  $locationData
     * @return array{success: bool, message: string, broadsheet?: Broadsheet}
     */
    public function publish(User $user, array $data, array $locationData): array
    {
        $publishCost = $this->getPublishCost($locationData['type']);

        if ($user->gold < $publishCost) {
            return ['success' => false, 'message' => 'You need '.$publishCost.'g to publish a broadsheet.'];
        }

        $hasPublishedToday = Broadsheet::where('author_id', $user->id)
            ->whereDate('published_at', today())
            ->exists();

        if ($hasPublishedToday) {
            return ['success' => false, 'message' => 'You have already published a broadsheet today.'];
        }

        return DB::transaction(function () use ($user, $data, $locationData, $publishCost) {
            $user->decrement('gold', $publishCost);

            $broadsheet = Broadsheet::create([
                'author_id' => $user->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'plain_text' => $data['plain_text'],
                'location_type' => $locationData['type'],
                'location_id' => $locationData['id'],
                'barony_id' => $locationData['barony_id'],
                'kingdom_id' => $locationData['kingdom_id'],
                'location_name' => $locationData['name'],
                'published_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Broadsheet published for '.$publishCost.'g!',
                'broadsheet' => $broadsheet,
            ];
        });
    }

    /**
     * Get paginated broadsheets at a specific location.
     */
    public function getLocalBroadsheets(string $type, int $id, int $perPage = 10): LengthAwarePaginator
    {
        return Broadsheet::atLocation($type, $id)
            ->with('author:id,username')
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    /**
     * Get paginated broadsheets across a barony (requires 5+ endorsements to spread).
     */
    public function getBaronyBroadsheets(?int $baronyId, int $perPage = 10): ?LengthAwarePaginator
    {
        if (! $baronyId) {
            return null;
        }

        return Broadsheet::inBarony($baronyId)
            ->where('endorse_count', '>=', self::BARONY_THRESHOLD)
            ->with('author:id,username')
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    /**
     * Get paginated broadsheets across a kingdom (requires 15+ endorsements), ordered by endorsements.
     */
    public function getKingdomBroadsheets(?int $kingdomId, int $perPage = 10): ?LengthAwarePaginator
    {
        if (! $kingdomId) {
            return null;
        }

        return Broadsheet::inKingdom($kingdomId)
            ->where('endorse_count', '>=', self::KINGDOM_THRESHOLD)
            ->with('author:id,username')
            ->orderByDesc('endorse_count')
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    /**
     * Toggle a reaction (endorse/denounce) on a broadsheet.
     *
     * @return array{success: bool, message: string}
     */
    public function react(User $user, Broadsheet $broadsheet, string $type): array
    {
        if (! in_array($type, [BroadsheetReaction::TYPE_ENDORSE, BroadsheetReaction::TYPE_DENOUNCE])) {
            return ['success' => false, 'message' => 'Invalid reaction type.'];
        }

        return DB::transaction(function () use ($user, $broadsheet, $type) {
            $existing = BroadsheetReaction::where('broadsheet_id', $broadsheet->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                if ($existing->type === $type) {
                    // Remove reaction
                    $existing->delete();
                    $broadsheet->decrement($type === BroadsheetReaction::TYPE_ENDORSE ? 'endorse_count' : 'denounce_count');

                    return ['success' => true, 'message' => ucfirst($type).' removed.'];
                }

                // Switch reaction
                $oldType = $existing->type;
                $existing->update(['type' => $type]);
                $broadsheet->decrement($oldType === BroadsheetReaction::TYPE_ENDORSE ? 'endorse_count' : 'denounce_count');
                $broadsheet->increment($type === BroadsheetReaction::TYPE_ENDORSE ? 'endorse_count' : 'denounce_count');

                return ['success' => true, 'message' => 'Reaction changed to '.$type.'.'];
            }

            // New reaction
            BroadsheetReaction::create([
                'broadsheet_id' => $broadsheet->id,
                'user_id' => $user->id,
                'type' => $type,
            ]);
            $broadsheet->increment($type === BroadsheetReaction::TYPE_ENDORSE ? 'endorse_count' : 'denounce_count');

            return ['success' => true, 'message' => ucfirst($type).'d!'];
        });
    }

    /**
     * Add a comment to a broadsheet.
     *
     * @return array{success: bool, message: string, comment?: BroadsheetComment}
     */
    public function comment(User $user, Broadsheet $broadsheet, string $body, ?int $parentId = null): array
    {
        if ($parentId) {
            $parent = BroadsheetComment::where('id', $parentId)
                ->where('broadsheet_id', $broadsheet->id)
                ->first();

            if (! $parent) {
                return ['success' => false, 'message' => 'Parent comment not found.'];
            }

            // Only allow one level of nesting
            if ($parent->parent_id !== null) {
                return ['success' => false, 'message' => 'You can only reply to top-level comments.'];
            }
        }

        $comment = BroadsheetComment::create([
            'broadsheet_id' => $broadsheet->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        $broadsheet->increment('comment_count');

        return [
            'success' => true,
            'message' => 'Comment posted.',
            'comment' => $comment,
        ];
    }

    /**
     * Delete a comment (author only). Cascades to replies.
     *
     * @return array{success: bool, message: string}
     */
    public function deleteComment(User $user, BroadsheetComment $comment): array
    {
        if ($comment->user_id !== $user->id) {
            return ['success' => false, 'message' => 'You can only delete your own comments.'];
        }

        $broadsheet = $comment->broadsheet;
        $replyCount = $comment->replies()->count();

        $comment->delete();

        // Decrement by 1 (this comment) + number of replies (cascade-deleted by DB)
        $broadsheet->decrement('comment_count', 1 + $replyCount);

        return ['success' => true, 'message' => 'Comment deleted.'];
    }

    /**
     * Delete a broadsheet (author only).
     *
     * @return array{success: bool, message: string}
     */
    public function delete(User $user, Broadsheet $broadsheet): array
    {
        if ($broadsheet->author_id !== $user->id) {
            return ['success' => false, 'message' => 'You can only delete your own broadsheets.'];
        }

        $broadsheet->delete();

        return ['success' => true, 'message' => 'Broadsheet deleted.'];
    }

    /**
     * Record a unique view (skip if author or already viewed).
     */
    public function recordView(User $user, Broadsheet $broadsheet): void
    {
        if ($user->id === $broadsheet->author_id) {
            return;
        }

        $created = BroadsheetView::firstOrCreate([
            'broadsheet_id' => $broadsheet->id,
            'user_id' => $user->id,
        ]);

        if ($created->wasRecentlyCreated) {
            $broadsheet->increment('view_count');
        }
    }

    /**
     * Get a broadsheet with full details for the show page.
     *
     * @return array{broadsheet: Broadsheet, comments: \Illuminate\Database\Eloquent\Collection}
     */
    public function getBroadsheetWithDetails(Broadsheet $broadsheet, User $user): array
    {
        $broadsheet->load('author:id,username');

        $this->recordView($user, $broadsheet);

        $broadsheet->refresh();

        $userReaction = $broadsheet->getUserReaction($user->id);

        $comments = BroadsheetComment::where('broadsheet_id', $broadsheet->id)
            ->whereNull('parent_id')
            ->with(['user:id,username', 'replies' => function ($query) {
                $query->with('user:id,username')->orderBy('created_at');
            }])
            ->orderByDesc('created_at')
            ->get();

        $broadsheet->user_reaction = $userReaction;

        return [
            'broadsheet' => $broadsheet,
            'comments' => $comments,
        ];
    }
}
