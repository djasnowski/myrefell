<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharterSignatory extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_id',
        'user_id',
        'comment',
    ];

    /**
     * Get the charter this signatory signed.
     */
    public function charter(): BelongsTo
    {
        return $this->belongsTo(Charter::class);
    }

    /**
     * Get the user who signed the charter.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
