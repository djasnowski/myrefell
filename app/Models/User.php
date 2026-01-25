<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'is_admin',
        'gender',
        'home_village_id',
        'current_location_type',
        'current_location_id',
        'hp',
        'max_hp',
        'energy',
        'max_energy',
        'gold',
        'primary_title',
        'title_tier',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'hp' => 'integer',
            'max_hp' => 'integer',
            'energy' => 'integer',
            'max_energy' => 'integer',
            'gold' => 'integer',
            'title_tier' => 'integer',
            'is_traveling' => 'boolean',
            'travel_started_at' => 'datetime',
            'travel_arrives_at' => 'datetime',
        ];
    }

    /**
     * Get the player's home village.
     */
    public function homeVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'home_village_id');
    }

    /**
     * Get the player's skills.
     */
    public function skills(): HasMany
    {
        return $this->hasMany(PlayerSkill::class, 'player_id');
    }

    /**
     * Get the player's inventory items.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(PlayerInventory::class, 'player_id');
    }

    /**
     * Get all titles held by this user.
     */
    public function titles(): HasMany
    {
        return $this->hasMany(PlayerTitle::class);
    }

    /**
     * Get all active titles held by this user.
     */
    public function activeTitles(): HasMany
    {
        return $this->hasMany(PlayerTitle::class)
            ->where('is_active', true)
            ->whereNull('revoked_at');
    }

    /**
     * Get the user's highest active title.
     */
    public function highestTitle(): ?PlayerTitle
    {
        return $this->activeTitles()
            ->orderByDesc('tier')
            ->first();
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if user is a peasant (tier 1).
     */
    public function isPeasant(): bool
    {
        return $this->primary_title === 'peasant' || $this->title_tier === 1;
    }

    /**
     * Check if user is a knight (tier 2 or higher).
     */
    public function isKnight(): bool
    {
        return $this->title_tier >= 2;
    }

    /**
     * Check if user is a lord (tier 3 or higher).
     */
    public function isLord(): bool
    {
        return $this->title_tier >= 3;
    }

    /**
     * Check if user is a king (tier 4).
     */
    public function isKing(): bool
    {
        return $this->title_tier >= 4;
    }

    /**
     * Calculate the player's combat level.
     * Formula: floor((ATK + STR + DEF) / 3)
     */
    public function getCombatLevelAttribute(): int
    {
        $attack = $this->getSkillLevel('attack');
        $strength = $this->getSkillLevel('strength');
        $defense = $this->getSkillLevel('defense');

        return (int) floor(($attack + $strength + $defense) / 3);
    }

    /**
     * Get a specific skill level.
     */
    public function getSkillLevel(string $skillName): int
    {
        $skill = $this->skills()->where('skill_name', $skillName)->first();

        // Combat skills start at 5, others at 1
        $default = in_array($skillName, ['attack', 'strength', 'defense']) ? 5 : 1;

        return $skill?->level ?? $default;
    }

    /**
     * Check if player has enough energy for an action.
     */
    public function hasEnergy(int $amount): bool
    {
        return $this->energy >= $amount;
    }

    /**
     * Consume energy for an action.
     */
    public function consumeEnergy(int $amount): bool
    {
        if (! $this->hasEnergy($amount)) {
            return false;
        }

        $this->decrement('energy', $amount);

        return true;
    }

    /**
     * Check if player is currently traveling.
     */
    public function isTraveling(): bool
    {
        return $this->is_traveling && $this->travel_arrives_at?->isFuture();
    }

    /**
     * Check if player is alive (HP > 0).
     */
    public function isAlive(): bool
    {
        return $this->hp > 0;
    }

    /**
     * Get all election candidacies for this user.
     */
    public function electionCandidacies(): HasMany
    {
        return $this->hasMany(ElectionCandidate::class);
    }

    /**
     * Get all votes cast by this user.
     */
    public function electionVotes(): HasMany
    {
        return $this->hasMany(ElectionVote::class, 'voter_user_id');
    }

    /**
     * Get all elections won by this user.
     */
    public function electionsWon(): HasMany
    {
        return $this->hasMany(Election::class, 'winner_user_id');
    }

    /**
     * Get all daily tasks assigned to this user.
     */
    public function dailyTasks(): HasMany
    {
        return $this->hasMany(PlayerDailyTask::class);
    }

    /**
     * Get all bank accounts for this user.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * Get all bank transactions for this user.
     */
    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /**
     * Get today's daily tasks.
     */
    public function todaysTasks(): HasMany
    {
        return $this->hasMany(PlayerDailyTask::class)
            ->where('assigned_date', today());
    }

    /**
     * Get all quests for this user.
     */
    public function quests(): HasMany
    {
        return $this->hasMany(PlayerQuest::class);
    }

    /**
     * Get active quests for this user.
     */
    public function activeQuests(): HasMany
    {
        return $this->hasMany(PlayerQuest::class)
            ->where('status', PlayerQuest::STATUS_ACTIVE);
    }

    /**
     * Get all employment records for this user.
     */
    public function employment(): HasMany
    {
        return $this->hasMany(PlayerEmployment::class);
    }

    /**
     * Get active employment for this user.
     */
    public function activeEmployment(): HasMany
    {
        return $this->hasMany(PlayerEmployment::class)
            ->where('status', PlayerEmployment::STATUS_EMPLOYED);
    }

    /**
     * Get the player's horse.
     */
    public function horse(): HasOne
    {
        return $this->hasOne(PlayerHorse::class);
    }

    /**
     * Check if player has a horse.
     */
    public function hasHorse(): bool
    {
        return $this->horse()->exists();
    }

    /**
     * Get the player's travel speed multiplier.
     */
    public function getTravelSpeedMultiplier(): float
    {
        if (!$this->hasHorse()) {
            return 1.0;
        }

        return $this->horse->speed_multiplier;
    }
}
