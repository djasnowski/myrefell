<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationActivityLog extends Model
{
    use HasFactory;

    // Player activity types
    public const TYPE_TRAINING = 'training';

    public const TYPE_GATHERING = 'gathering';

    public const TYPE_CRAFTING = 'crafting';

    public const TYPE_TRADING = 'trading';

    public const TYPE_HEALING = 'healing';

    public const TYPE_BLESSING = 'blessing';

    public const TYPE_BANKING = 'banking';

    public const TYPE_WORKING = 'working';

    public const TYPE_FARMING = 'farming';

    public const TYPE_TRAVEL = 'travel';

    public const TYPE_REST = 'rest';

    public const TYPE_ABDICATION = 'abdication';

    // System activity types (no user_id)
    public const TYPE_TAX_COLLECTION = 'tax_collection';

    public const TYPE_SALARY_PAYMENT = 'salary_payment';

    public const TYPE_SALARY_FAILED = 'salary_failed';

    public const TYPE_UPSTREAM_TAX = 'upstream_tax';

    public const TYPE_ROLE_CHANGE = 'role_change';

    public const TYPE_DISASTER = 'disaster';

    public const TYPE_MIGRATION = 'migration';

    // Location types
    public const LOCATION_VILLAGE = 'village';

    public const LOCATION_TOWN = 'town';

    public const LOCATION_BARONY = 'barony';

    public const LOCATION_DUCHY = 'duchy';

    public const LOCATION_KINGDOM = 'kingdom';

    protected $fillable = [
        'user_id',
        'location_type',
        'location_id',
        'activity_type',
        'activity_subtype',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user who performed this activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by location.
     */
    public function scopeAtLocation(Builder $query, string $locationType, int $locationId): Builder
    {
        return $query
            ->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Scope to get recent activities.
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->limit($limit);
    }

    /**
     * Scope to filter by activity type.
     */
    public function scopeOfType(Builder $query, string $activityType): Builder
    {
        return $query->where('activity_type', $activityType);
    }

    /**
     * Static helper to create a log entry.
     */
    public static function log(
        int $userId,
        string $locationType,
        int $locationId,
        string $activityType,
        string $description,
        ?string $activitySubtype = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'activity_type' => $activityType,
            'activity_subtype' => $activitySubtype,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Static helper to create a system-generated log entry (no user).
     */
    public static function logSystemEvent(
        string $locationType,
        int $locationId,
        string $activityType,
        string $description,
        ?string $activitySubtype = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => null,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'activity_type' => $activityType,
            'activity_subtype' => $activitySubtype,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get the location model for this activity.
     */
    public function getLocation(): ?Model
    {
        return match ($this->location_type) {
            self::LOCATION_VILLAGE => Village::find($this->location_id),
            self::LOCATION_TOWN => Town::find($this->location_id),
            self::LOCATION_BARONY => Barony::find($this->location_id),
            self::LOCATION_DUCHY => Duchy::find($this->location_id),
            self::LOCATION_KINGDOM => Kingdom::find($this->location_id),
            default => null,
        };
    }
}
