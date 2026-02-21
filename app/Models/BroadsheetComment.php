<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadsheetComment extends Model
{
    use HasFactory;

    public const MAX_BODY = 500;

    protected $fillable = [
        'broadsheet_id',
        'user_id',
        'parent_id',
        'body',
    ];

    /**
     * Get the broadsheet this comment belongs to.
     */
    public function broadsheet(): BelongsTo
    {
        return $this->belongsTo(Broadsheet::class);
    }

    /**
     * Get the user who wrote this comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment (if this is a reply).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BroadsheetComment::class, 'parent_id');
    }

    /**
     * Get replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(BroadsheetComment::class, 'parent_id');
    }
}
