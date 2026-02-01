<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
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
     * Social class constants.
     */
    public const CLASS_SERF = 'serf';

    public const CLASS_FREEMAN = 'freeman';

    public const CLASS_BURGHER = 'burgher';

    public const CLASS_NOBLE = 'noble';

    public const CLASS_CLERGY = 'clergy';

    /**
     * Social class hierarchy (higher = more privileged).
     */
    public const CLASS_HIERARCHY = [
        self::CLASS_SERF => 1,
        self::CLASS_FREEMAN => 2,
        self::CLASS_BURGHER => 3,
        self::CLASS_CLERGY => 3,
        self::CLASS_NOBLE => 4,
    ];

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
        'banned_at',
        'registration_ip',
        'last_login_ip',
        'last_login_at',
        'show_tutorial',
        'gender',
        'social_class',
        'bound_to_barony_id',
        'labor_days_owed',
        'labor_days_completed',
        'last_obligation_check',
        'home_village_id',
        'current_location_type',
        'current_location_id',
        'hp',
        'max_hp',
        'energy',
        'max_energy',
        'weeks_without_food',
        'gold',
        'primary_title',
        'title_tier',
        'dynasty_id',
        'dynasty_member_id',
        'spouse_id',
        'is_traveling',
        'travel_started_at',
        'travel_arrives_at',
        'travel_destination_type',
        'travel_destination_id',
        'referral_code',
        'last_seen_changelog',
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
            'banned_at' => 'datetime',
            'last_login_at' => 'datetime',
            'show_tutorial' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'hp' => 'integer',
            'max_hp' => 'integer',
            'energy' => 'integer',
            'max_energy' => 'integer',
            'weeks_without_food' => 'integer',
            'gold' => 'integer',
            'title_tier' => 'integer',
            'is_traveling' => 'boolean',
            'travel_started_at' => 'datetime',
            'travel_arrives_at' => 'datetime',
            'labor_days_owed' => 'integer',
            'labor_days_completed' => 'integer',
            'last_obligation_check' => 'date',
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
     * Get all bans for this user.
     */
    public function bans(): HasMany
    {
        return $this->hasMany(UserBan::class);
    }

    /**
     * Get the most recent ban record for this user.
     */
    public function latestBan(): HasOne
    {
        return $this->hasOne(UserBan::class)->latestOfMany('banned_at');
    }

    /**
     * Check if user is currently banned.
     */
    public function isBanned(): bool
    {
        return $this->banned_at !== null;
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
        $default = in_array($skillName, PlayerSkill::COMBAT_SKILLS) ? 5 : 1;

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
     * Get all role assignments for this user.
     */
    public function playerRoles(): HasMany
    {
        return $this->hasMany(PlayerRole::class);
    }

    /**
     * Get active role assignments for this user.
     */
    public function activePlayerRoles(): HasMany
    {
        return $this->hasMany(PlayerRole::class)
            ->where('status', PlayerRole::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
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
     * Only applies if horse is with the player (not stabled).
     */
    public function getTravelSpeedMultiplier(): float
    {
        if (! $this->hasHorseWithMe()) {
            return 1.0;
        }

        return $this->horse->speed_multiplier;
    }

    /**
     * Check if player has a horse with them (not stabled).
     */
    public function hasHorseWithMe(): bool
    {
        $horse = $this->horse;

        return $horse && ! $horse->is_stabled;
    }

    /**
     * Check if player's horse is stabled somewhere.
     */
    public function hasStabledHorse(): bool
    {
        $horse = $this->horse;

        return $horse && $horse->is_stabled;
    }

    /**
     * Check if horse can be used for travel (has stamina).
     */
    public function canUseHorseForTravel(): bool
    {
        if (! $this->hasHorseWithMe()) {
            return false;
        }

        return $this->horse->isAvailableForTravel();
    }

    // ==================== SOCIAL CLASS METHODS ====================

    /**
     * Get the barony this serf is bound to.
     */
    public function boundBarony(): BelongsTo
    {
        return $this->belongsTo(Barony::class, 'bound_to_barony_id');
    }

    /**
     * Get manumission requests made by this user.
     */
    public function manumissionRequests(): HasMany
    {
        return $this->hasMany(ManumissionRequest::class, 'serf_id');
    }

    /**
     * Get ennoblement requests made by this user.
     */
    public function ennoblementRequests(): HasMany
    {
        return $this->hasMany(EnnoblementRequest::class, 'requester_id');
    }

    /**
     * Get social class change history.
     */
    public function socialClassHistory(): HasMany
    {
        return $this->hasMany(SocialClassHistory::class);
    }

    /**
     * Get all disease infections for this user.
     */
    public function diseaseInfections(): HasMany
    {
        return $this->hasMany(DiseaseInfection::class);
    }

    /**
     * Get active disease infections for this user.
     */
    public function activeDiseaseInfections(): HasMany
    {
        return $this->hasMany(DiseaseInfection::class)->active();
    }

    /**
     * Get disease immunities for this user.
     */
    public function diseaseImmunities(): HasMany
    {
        return $this->hasMany(DiseaseImmunity::class);
    }

    /**
     * Get referrals made by this user.
     */
    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Get the user's service favorites.
     */
    public function serviceFavorites(): HasMany
    {
        return $this->hasMany(UserServiceFavorite::class)->orderBy('sort_order');
    }

    /**
     * Get the referral that brought this user (if any).
     */
    public function referredBy(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    /**
     * Check if user is a serf.
     */
    public function isSerf(): bool
    {
        return $this->social_class === self::CLASS_SERF;
    }

    /**
     * Check if user is a freeman.
     */
    public function isFreeman(): bool
    {
        return $this->social_class === self::CLASS_FREEMAN;
    }

    /**
     * Check if user is a burgher.
     */
    public function isBurgher(): bool
    {
        return $this->social_class === self::CLASS_BURGHER;
    }

    /**
     * Check if user is a noble.
     */
    public function isNoble(): bool
    {
        return $this->social_class === self::CLASS_NOBLE;
    }

    /**
     * Check if user is clergy.
     */
    public function isClergy(): bool
    {
        return $this->social_class === self::CLASS_CLERGY;
    }

    /**
     * Get the social class rank (1-4).
     */
    public function getSocialClassRank(): int
    {
        return self::CLASS_HIERARCHY[$this->social_class] ?? 1;
    }

    /**
     * Check if user can vote in elections.
     * Serfs cannot vote.
     */
    public function canVote(): bool
    {
        return ! $this->isSerf();
    }

    /**
     * Check if user can join guilds.
     * Only burghers and above can join guilds.
     */
    public function canJoinGuild(): bool
    {
        return in_array($this->social_class, [
            self::CLASS_BURGHER,
            self::CLASS_NOBLE,
        ]);
    }

    /**
     * Check if user can hold high office (Baron, King).
     * Only nobles can hold high office.
     */
    public function canHoldHighOffice(): bool
    {
        return $this->isNoble();
    }

    /**
     * Check if user can freely travel (leave their land).
     * Serfs need permission to leave their barony.
     */
    public function canFreelyTravel(): bool
    {
        return ! $this->isSerf();
    }

    /**
     * Check if user can own land/property.
     * Serfs have limited property rights.
     */
    public function canOwnProperty(): bool
    {
        return ! $this->isSerf();
    }

    /**
     * Check if user can own a business.
     * Burghers and above can own businesses.
     */
    public function canOwnBusiness(): bool
    {
        return in_array($this->social_class, [
            self::CLASS_BURGHER,
            self::CLASS_NOBLE,
        ]);
    }

    /**
     * Check if serf has completed their labor obligations.
     */
    public function hasCompletedLaborObligations(): bool
    {
        if (! $this->isSerf()) {
            return true;
        }

        return $this->labor_days_completed >= $this->labor_days_owed;
    }

    /**
     * Get remaining labor days owed.
     */
    public function getRemainingLaborDays(): int
    {
        if (! $this->isSerf()) {
            return 0;
        }

        return max(0, $this->labor_days_owed - $this->labor_days_completed);
    }

    /**
     * Complete a labor day.
     */
    public function completeLaborDay(): void
    {
        if ($this->isSerf() && $this->labor_days_completed < $this->labor_days_owed) {
            $this->increment('labor_days_completed');
        }
    }

    /**
     * Get the display name for the social class.
     */
    public function getSocialClassDisplayAttribute(): string
    {
        return match ($this->social_class) {
            self::CLASS_SERF => 'Serf',
            self::CLASS_FREEMAN => 'Freeman',
            self::CLASS_BURGHER => 'Burgher',
            self::CLASS_NOBLE => 'Noble',
            self::CLASS_CLERGY => 'Clergy',
            default => 'Unknown',
        };
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
