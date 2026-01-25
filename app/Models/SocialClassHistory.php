<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialClassHistory extends Model
{
    protected $table = 'social_class_history';

    protected $fillable = [
        'user_id',
        'old_class',
        'new_class',
        'reason',
        'granted_by_user_id',
    ];

    /**
     * Get the user whose class changed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who granted the class change.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    /**
     * Get the old class display name.
     */
    public function getOldClassDisplayAttribute(): string
    {
        return ucfirst($this->old_class);
    }

    /**
     * Get the new class display name.
     */
    public function getNewClassDisplayAttribute(): string
    {
        return ucfirst($this->new_class);
    }
}
