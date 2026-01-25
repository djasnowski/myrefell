<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FestivalParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'festival_id',
        'user_id',
        'role',
        'gold_spent',
        'gold_earned',
        'activities_completed',
    ];

    protected function casts(): array
    {
        return [
            'gold_spent' => 'integer',
            'gold_earned' => 'integer',
            'activities_completed' => 'array',
        ];
    }

    public function festival(): BelongsTo
    {
        return $this->belongsTo(Festival::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
