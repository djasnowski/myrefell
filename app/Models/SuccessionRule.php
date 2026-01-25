<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessionRule extends Model
{
    use HasFactory;

    const TYPE_PRIMOGENITURE = 'primogeniture'; // Eldest child
    const TYPE_ULTIMOGENITURE = 'ultimogeniture'; // Youngest child
    const TYPE_SENIORITY = 'seniority'; // Oldest dynasty member
    const TYPE_ELECTIVE = 'elective'; // Chosen by vote
    const TYPE_GAVELKIND = 'gavelkind'; // Split among children

    const GENDER_AGNATIC = 'agnatic'; // Males only
    const GENDER_AGNATIC_COGNATIC = 'agnatic-cognatic'; // Males first, then females
    const GENDER_COGNATIC = 'cognatic'; // Equal (absolute)
    const GENDER_ENATIC = 'enatic'; // Females only

    protected $fillable = [
        'dynasty_id', 'succession_type', 'gender_law', 'allows_bastards',
        'allows_adoption', 'minimum_age', 'additional_requirements',
    ];

    protected function casts(): array
    {
        return [
            'allows_bastards' => 'boolean',
            'allows_adoption' => 'boolean',
            'additional_requirements' => 'array',
        ];
    }

    public function dynasty(): BelongsTo
    {
        return $this->belongsTo(Dynasty::class);
    }

    /**
     * Determine heirs based on succession rules.
     */
    public function determineHeirs(): array
    {
        $candidates = $this->getEligibleCandidates();

        return match ($this->succession_type) {
            self::TYPE_PRIMOGENITURE => $this->sortByPrimogeniture($candidates),
            self::TYPE_ULTIMOGENITURE => $this->sortByUltimogeniture($candidates),
            self::TYPE_SENIORITY => $this->sortBySeniority($candidates),
            default => $candidates->toArray(),
        };
    }

    /**
     * Get eligible candidates based on gender law.
     */
    protected function getEligibleCandidates()
    {
        $query = $this->dynasty->members()
            ->eligibleHeirs()
            ->where('generation', '>', 1); // Not the founder

        if (!$this->allows_bastards) {
            $query->legitimate();
        }

        // Apply gender law
        switch ($this->gender_law) {
            case self::GENDER_AGNATIC:
                $query->male();
                break;
            case self::GENDER_ENATIC:
                $query->female();
                break;
            // COGNATIC and AGNATIC_COGNATIC include all genders
        }

        // Apply minimum age
        if ($this->minimum_age > 0) {
            // Would need to calculate from birth_date in real implementation
        }

        return $query->get();
    }

    /**
     * Sort candidates by primogeniture (eldest first).
     */
    protected function sortByPrimogeniture($candidates): array
    {
        $sorted = $candidates->sortBy([
            ['generation', 'asc'],
            ['birth_order', 'asc'],
        ]);

        // If agnatic-cognatic, put males first within each generation
        if ($this->gender_law === self::GENDER_AGNATIC_COGNATIC) {
            $sorted = $sorted->sortBy([
                ['generation', 'asc'],
                fn ($a, $b) => ($a->gender === 'male' ? 0 : 1) - ($b->gender === 'male' ? 0 : 1),
                ['birth_order', 'asc'],
            ]);
        }

        return $sorted->values()->toArray();
    }

    /**
     * Sort candidates by ultimogeniture (youngest first).
     */
    protected function sortByUltimogeniture($candidates): array
    {
        return $candidates->sortBy([
            ['generation', 'desc'],
            ['birth_order', 'desc'],
        ])->values()->toArray();
    }

    /**
     * Sort candidates by seniority (oldest member first).
     */
    protected function sortBySeniority($candidates): array
    {
        return $candidates->sortBy('birth_date')->values()->toArray();
    }
}
