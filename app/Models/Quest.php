<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    use HasFactory;

    /**
     * Quest categories.
     */
    public const CATEGORIES = [
        'combat' => 'Combat',
        'gathering' => 'Gathering',
        'delivery' => 'Delivery',
        'exploration' => 'Exploration',
    ];

    /**
     * Quest types.
     */
    public const TYPES = [
        'kill' => 'Defeat enemies',
        'gather' => 'Collect resources',
        'deliver' => 'Deliver items',
        'visit' => 'Visit locations',
        'craft' => 'Craft items',
    ];

    protected $fillable = [
        'name',
        'icon',
        'description',
        'objective',
        'category',
        'quest_type',
        'target_type',
        'target_identifier',
        'target_amount',
        'required_level',
        'required_skill',
        'required_skill_level',
        'gold_reward',
        'xp_reward',
        'xp_skill',
        'item_rewards',
        'repeatable',
        'cooldown_hours',
        'is_active',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'required_level' => 'integer',
            'required_skill_level' => 'integer',
            'gold_reward' => 'integer',
            'xp_reward' => 'integer',
            'item_rewards' => 'array',
            'repeatable' => 'boolean',
            'cooldown_hours' => 'integer',
            'is_active' => 'boolean',
            'weight' => 'integer',
        ];
    }

    /**
     * Get player assignments for this quest.
     */
    public function playerQuests(): HasMany
    {
        return $this->hasMany(PlayerQuest::class);
    }

    /**
     * Check if player meets requirements for this quest.
     */
    public function playerMeetsRequirements(User $user): bool
    {
        // Check combat level
        if ($user->combat_level < $this->required_level) {
            return false;
        }

        // Check skill requirement
        if ($this->required_skill) {
            $skillLevel = $user->getSkillLevel($this->required_skill);
            if ($skillLevel < $this->required_skill_level) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get display text for the category.
     */
    public function getCategoryDisplayAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Get display text for the quest type.
     */
    public function getQuestTypeDisplayAttribute(): string
    {
        return self::TYPES[$this->quest_type] ?? $this->quest_type;
    }
}
