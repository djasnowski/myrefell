<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyTask extends Model
{
    use HasFactory;

    /**
     * Task categories.
     */
    public const CATEGORIES = [
        'combat',
        'gathering',
        'crafting',
        'service',
    ];

    /**
     * Task types.
     */
    public const TASK_TYPES = [
        'kill' => 'Kill enemies',
        'gather' => 'Gather resources',
        'craft' => 'Craft items',
        'fish' => 'Catch fish',
        'mine' => 'Mine ore',
        'chop' => 'Chop wood',
        'cook' => 'Cook food',
        'smith' => 'Smith items',
        'deliver' => 'Deliver items',
        'visit' => 'Visit location',
        'train' => 'Train skill',
        'pray' => 'Pray at shrine',
    ];

    protected $fillable = [
        'name',
        'icon',
        'description',
        'category',
        'task_type',
        'target_type',
        'target_identifier',
        'target_amount',
        'required_skill',
        'required_skill_level',
        'location_type',
        'home_village_only',
        'gold_reward',
        'xp_reward',
        'xp_skill',
        'energy_cost',
        'is_active',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'required_skill_level' => 'integer',
            'home_village_only' => 'boolean',
            'gold_reward' => 'integer',
            'xp_reward' => 'integer',
            'energy_cost' => 'integer',
            'is_active' => 'boolean',
            'weight' => 'integer',
        ];
    }

    /**
     * Get all player assignments for this task.
     */
    public function playerTasks(): HasMany
    {
        return $this->hasMany(PlayerDailyTask::class);
    }

    /**
     * Check if player meets the skill requirements.
     */
    public function playerMeetsRequirements(User $user): bool
    {
        if (! $this->required_skill) {
            return true;
        }

        $skillLevel = $user->getSkillLevel($this->required_skill);

        return $skillLevel >= $this->required_skill_level;
    }

    /**
     * Get display text for the task type.
     */
    public function getTaskTypeDisplayAttribute(): string
    {
        return self::TASK_TYPES[$this->task_type] ?? $this->task_type;
    }
}
