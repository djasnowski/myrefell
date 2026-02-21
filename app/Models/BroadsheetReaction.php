<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadsheetReaction extends Model
{
    use HasFactory;

    public const TYPE_ENDORSE = 'endorse';

    public const TYPE_DENOUNCE = 'denounce';

    protected $fillable = [
        'broadsheet_id',
        'user_id',
        'type',
    ];

    /**
     * Get the broadsheet this reaction belongs to.
     */
    public function broadsheet(): BelongsTo
    {
        return $this->belongsTo(Broadsheet::class);
    }

    /**
     * Get the user who made this reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
