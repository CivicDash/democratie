<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParlementaireStats extends Model
{
    protected $table = 'parlementaires_stats';

    protected $fillable = [
        'parlementaire_id',
        'type',
        'legislature',
        'votes_total',
        'votes_pour',
        'votes_contre',
        'votes_abstention',
        'scrutins_total',
        'taux_presence',
        'amendements_total',
        'amendements_adoptes',
        'amendements_rejetes',
        'amendements_retires',
        'taux_adoption_amendements',
        'discipline_groupe',
        'votes_rebelles',
        'questions_total',
        'interventions_total',
        'calculated_at',
    ];

    protected $casts = [
        'taux_presence' => 'float',
        'taux_adoption_amendements' => 'float',
        'discipline_groupe' => 'float',
        'calculated_at' => 'datetime',
    ];

    /**
     * Récupérer les stats d'un député
     */
    public static function forDepute(string $uid, int $legislature = 17): ?self
    {
        return static::where('parlementaire_id', $uid)
            ->where('type', 'depute')
            ->where('legislature', $legislature)
            ->first();
    }

    /**
     * Récupérer les stats d'un sénateur
     */
    public static function forSenateur(string $matricule): ?self
    {
        return static::where('parlementaire_id', $matricule)
            ->where('type', 'senateur')
            ->first();
    }

    /**
     * Mettre à jour ou créer les stats d'un député
     */
    public static function updateDeputeStats(string $uid, int $legislature, array $data): self
    {
        return static::updateOrCreate(
            [
                'parlementaire_id' => $uid,
                'type' => 'depute',
                'legislature' => $legislature,
            ],
            array_merge($data, ['calculated_at' => now()])
        );
    }

    /**
     * Mettre à jour ou créer les stats d'un sénateur
     */
    public static function updateSenateurStats(string $matricule, array $data): self
    {
        return static::updateOrCreate(
            [
                'parlementaire_id' => $matricule,
                'type' => 'senateur',
                'legislature' => null,
            ],
            array_merge($data, ['calculated_at' => now()])
        );
    }

    /**
     * Vérifier si les stats sont obsolètes (plus de 24h)
     */
    public function isStale(): bool
    {
        if (! $this->calculated_at) {
            return true;
        }

        return $this->calculated_at->diffInHours(now()) >= 24;
    }

    /**
     * Convertir en tableau pour la vue
     */
    public function toViewArray(): array
    {
        return [
            'votes_total' => $this->votes_total,
            'votes_pour' => $this->votes_pour,
            'votes_contre' => $this->votes_contre,
            'votes_abstention' => $this->votes_abstention,
            'taux_presence' => round($this->taux_presence, 1),
            'amendements_total' => $this->amendements_total,
            'amendements_adoptes' => $this->amendements_adoptes,
            'taux_adoption_amendements' => round($this->taux_adoption_amendements, 1),
            'discipline_groupe' => $this->discipline_groupe ? round($this->discipline_groupe, 1) : null,
            'votes_rebelles' => $this->votes_rebelles,
            'questions_total' => $this->questions_total,
            'interventions_total' => $this->interventions_total ?? 0,
            'calculated_at' => $this->calculated_at?->format('d/m/Y H:i'),
        ];
    }
}
