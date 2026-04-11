<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'counters',
        'checksum',
        'notes',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'counters' => 'array',
    ];

    public function scopeLatest($query)
    {
        return $query->orderBy('snapshot_date', 'desc');
    }

    public static function capture(?string $notes = null): self
    {
        $counters = [
            'acteurs_an' => ActeurAN::count(),
            'senateurs' => Senateur::count(),
            'scrutins_an' => ScrutinAN::count(),
            'amendements_an' => AmendementAN::count(),
            'dossiers_an' => DossierLegislatifAN::count(),
            'dossiers_senat' => DossierLegislatifSenat::count(),
            'questions_an' => QuestionAN::count(),
            'hatvp_declarations' => HatvpDeclaration::count(),
            'maires' => Maire::count(),
            'communes_budgets' => CommuneBudget::count(),
            'france_demographics' => FranceDemographics::count(),
            'france_economy' => FranceEconomy::count(),
            'france_employment' => FranceEmploymentDetailed::count(),
            'personnes_politiques' => PersonnePolitique::count(),
        ];

        $checksum = md5(json_encode($counters));

        return self::create([
            'snapshot_date' => now()->toDateString(),
            'counters' => $counters,
            'checksum' => $checksum,
            'notes' => $notes,
        ]);
    }

    public function diffWith(self $other): array
    {
        $diff = [];
        $myCounters = $this->counters ?? [];
        $otherCounters = $other->counters ?? [];

        foreach ($myCounters as $key => $value) {
            $otherValue = $otherCounters[$key] ?? 0;
            if ($value !== $otherValue) {
                $diff[$key] = [
                    'before' => $otherValue,
                    'after' => $value,
                    'change' => $value - $otherValue,
                    'percentage' => $otherValue > 0 ? round((($value - $otherValue) / $otherValue) * 100, 2) : null,
                ];
            }
        }

        return $diff;
    }
}
