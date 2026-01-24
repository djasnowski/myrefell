<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankAccount extends Model
{
    protected $fillable = [
        'user_id',
        'location_type',
        'location_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
        ];
    }

    /**
     * Get the user who owns this account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location (village, castle, or town).
     */
    public function location(): MorphTo
    {
        return $this->morphTo('location');
    }

    /**
     * Get transactions for this account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /**
     * Get recent transactions.
     */
    public function recentTransactions(int $limit = 10): HasMany
    {
        return $this->transactions()
            ->orderByDesc('created_at')
            ->limit($limit);
    }
}
