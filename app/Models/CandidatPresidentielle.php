<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CandidatPresidentielle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'candidats_presidentielle';

    /** Statuts de candidature */
    public const STATUTS_CANDIDATURE = [
        'pressenti', 'declare', 'investi', 'parrainages_valides', 'retire', 'elimine_t1',
    ];

    /** Workflow de validation (partagé avec les affaires judiciaires) */
    public const STATUTS_VALIDATION = ['detecte', 'en_review', 'a_completer', 'valide'];

    protected $fillable = [
        'uuid', 'personne_politique_id', 'election', 'statut_candidature',
        'date_declaration', 'parti_soutien', 'slogan', 'nuance_politique', 'condition',
        'site_campagne_url', 'programme_url_officiel', 'couleur_hex',
        'photo_url', 'photo_credit', 'photo_licence',
        'statut_validation', 'affiche_publiquement', 'ordre_affichage',
        'source_detection', 'detecte_at', 'detection_confidence', 'detection_raw_data',
        'valide_par', 'valide_at', 'commentaire_validation',
    ];

    protected $casts = [
        'date_declaration' => 'date',
        'affiche_publiquement' => 'boolean',
        'detecte_at' => 'datetime',
        'valide_at' => 'datetime',
        'detection_confidence' => 'decimal:2',
        'detection_raw_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            if (empty($c->uuid)) {
                $c->uuid = (string) Str::uuid();
            }
        });
    }

    public function personnePolitique(): BelongsTo
    {
        return $this->belongsTo(PersonnePolitique::class, 'personne_politique_id');
    }

    public function mesures(): HasMany
    {
        return $this->hasMany(ProgrammeMesure::class, 'candidat_id');
    }

    public function propositions(): HasMany
    {
        return $this->hasMany(IngestionProposition::class, 'candidat_id');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /** Ne renvoie que les candidats réellement publiables. */
    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }

    public function scopeElection($query, string $election = '2027')
    {
        return $query->where('election', $election);
    }

    public function estPublie(): bool
    {
        return $this->statut_validation === 'valide' && $this->affiche_publiquement;
    }
}
