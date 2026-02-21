<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadsheetView extends Model
{
    protected $fillable = [
        'broadsheet_id',
        'user_id',
    ];

    /**
     * Get the broadsheet that was viewed.
     */
    public function broadsheet(): BelongsTo
    {
        return $this->belongsTo(Broadsheet::class);
    }

    /**
     * Get the user who viewed the broadsheet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
