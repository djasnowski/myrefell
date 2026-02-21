<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadsheet extends Model
{
    use HasFactory;

    public const PUBLISH_COST = 50;

    public const MAX_TITLE = 150;

    public const MAX_CONTENT_LENGTH = 10000;

    protected $fillable = [
        'author_id',
        'title',
        'content',
        'plain_text',
        'location_type',
        'location_id',
        'barony_id',
        'kingdom_id',
        'location_name',
        'view_count',
        'endorse_count',
        'denounce_count',
        'comment_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'view_count' => 'integer',
            'endorse_count' => 'integer',
            'denounce_count' => 'integer',
            'comment_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the author of this broadsheet.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get reactions on this broadsheet.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(BroadsheetReaction::class);
    }

    /**
     * Get comments on this broadsheet.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BroadsheetComment::class);
    }

    /**
     * Get views on this broadsheet.
     */
    public function views(): HasMany
    {
        return $this->hasMany(BroadsheetView::class);
    }

    /**
     * Scope to broadsheets at a specific location.
     */
    public function scopeAtLocation(Builder $query, string $type, int $id): Builder
    {
        return $query->where('location_type', $type)->where('location_id', $id);
    }

    /**
     * Scope to broadsheets in a barony.
     */
    public function scopeInBarony(Builder $query, int $baronyId): Builder
    {
        return $query->where('barony_id', $baronyId);
    }

    /**
     * Scope to broadsheets in a kingdom.
     */
    public function scopeInKingdom(Builder $query, int $kingdomId): Builder
    {
        return $query->where('kingdom_id', $kingdomId);
    }

    /**
     * Get the current user's reaction type for this broadsheet.
     */
    public function getUserReaction(int $userId): ?string
    {
        return $this->reactions()
            ->where('user_id', $userId)
            ->value('type');
    }
}
