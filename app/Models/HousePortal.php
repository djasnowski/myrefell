<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousePortal extends Model
{
    protected $fillable = [
        'player_house_id',
        'portal_slot',
        'destination_type',
        'destination_id',
        'destination_name',
    ];

    protected function casts(): array
    {
        return [
            'portal_slot' => 'integer',
            'destination_id' => 'integer',
        ];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(PlayerHouse::class, 'player_house_id');
    }
}
