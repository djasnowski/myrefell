<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GuildBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'skill_name',
        'effects',
        'required_guild_level',
    ];

    protected function casts(): array
    {
        return [
            'effects' => 'array',
            'required_guild_level' => 'integer',
        ];
    }

    /**
     * Get the guilds that have this benefit.
     */
    public function guilds(): BelongsToMany
    {
        return $this->belongsToMany(Guild::class, 'guild_benefit_guild')
            ->withTimestamps();
    }

    /**
     * Check if a guild can unlock this benefit.
     */
    public function canBeUnlockedBy(Guild $guild): bool
    {
        // Check guild level requirement
        if ($guild->level < $this->required_guild_level) {
            return false;
        }

        // Check if already unlocked
        if ($guild->benefits->contains($this->id)) {
            return false;
        }

        // Check skill match (if benefit is skill-specific)
        if ($this->skill_name && $this->skill_name !== $guild->primary_skill) {
            return false;
        }

        return true;
    }
}
