<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBan extends Model
{
    /** @use HasFactory<\Database\Factories\UserBanFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'banned_by',
        'reason',
        'banned_at',
        'unbanned_at',
        'unbanned_by',
        'unban_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'banned_at' => 'datetime',
            'unbanned_at' => 'datetime',
        ];
    }

    /**
     * Get the user who was banned.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who issued the ban.
     */
    public function bannedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /**
     * Get the admin who lifted the ban.
     */
    public function unbannedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unbanned_by');
    }

    /**
     * Check if the ban is currently active.
     */
    public function isActive(): bool
    {
        return $this->unbanned_at === null;
    }
}
