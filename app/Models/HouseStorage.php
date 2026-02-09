<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseStorage extends Model
{
    protected $table = 'house_storage';

    protected $fillable = [
        'player_house_id',
        'item_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(PlayerHouse::class, 'player_house_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
