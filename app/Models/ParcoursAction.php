<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ParcoursAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parcours_actions';

    public const TYPES = ['loi_portee', 'vote_cle', 'rapport_parlementaire', 'usage_493'];

    protected $fillable = [
        'uuid', 'parcours_evenement_id', 'type', 'reference_type', 'reference_id',
        'titre_court', 'explication', 'date_action', 'source_url',
        'source_detection', 'critere',
        'statut_validation', 'affiche_publiquement', 'ordre', 'valide_par', 'valide_at',
    ];

    protected $casts = [
        'date_action' => 'date',
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $a) {
            if (empty($a->uuid)) {
                $a->uuid = (string) Str::uuid();
            }
        });
    }

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(ParcoursEvenement::class, 'parcours_evenement_id');
    }

    /** Publiable uniquement si validé + affiché + explication rédigée (neutralité §1). */
    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')
            ->where('affiche_publiquement', true)
            ->whereNotNull('explication')
            ->where('explication', '!=', '');
    }
}
