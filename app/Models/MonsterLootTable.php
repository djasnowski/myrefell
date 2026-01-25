<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonsterLootTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'monster_id',
        'item_id',
        'drop_chance',
        'quantity_min',
        'quantity_max',
    ];

    protected function casts(): array
    {
        return [
            'drop_chance' => 'decimal:2',
            'quantity_min' => 'integer',
            'quantity_max' => 'integer',
        ];
    }

    /**
     * Get the monster this loot entry belongs to.
     */
    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }

    /**
     * Get the item that can drop.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Roll for this drop (returns quantity or 0 if not dropped).
     */
    public function rollDrop(): int
    {
        $roll = mt_rand(0, 10000) / 100; // 0.00 to 100.00

        if ($roll <= $this->drop_chance) {
            return rand($this->quantity_min, $this->quantity_max);
        }

        return 0;
    }
}
