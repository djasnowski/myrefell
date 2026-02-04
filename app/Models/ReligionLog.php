<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligionLog extends Model
{
    // Event types
    public const EVENT_FOUNDED = 'founded';

    public const EVENT_MEMBER_JOINED = 'member_joined';

    public const EVENT_MEMBER_LEFT = 'member_left';

    public const EVENT_MEMBER_PROMOTED = 'member_promoted';

    public const EVENT_MEMBER_DEMOTED = 'member_demoted';

    public const EVENT_LEADERSHIP_TRANSFERRED = 'leadership_transferred';

    public const EVENT_CONVERTED_TO_RELIGION = 'converted_to_religion';

    public const EVENT_MADE_PUBLIC = 'made_public';

    public const EVENT_STRUCTURE_BUILT = 'structure_built';

    public const EVENT_DISSOLVED = 'dissolved';

    public const EVENT_MEMBER_INVITED = 'member_invited';

    public const EVENT_BELIEF_ADDED = 'belief_added';

    public const EVENT_HQ_BUILT = 'hq_built';

    public const EVENT_HQ_UPGRADED = 'hq_upgraded';

    public const EVENT_PROPHET_ABDICATED = 'prophet_abdicated';

    protected $fillable = [
        'religion_id',
        'actor_id',
        'target_id',
        'event_type',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    /**
     * Log a religion event.
     */
    public static function log(
        int $religionId,
        string $eventType,
        string $description,
        ?int $actorId = null,
        ?int $targetId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'religion_id' => $religionId,
            'actor_id' => $actorId,
            'target_id' => $targetId,
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
