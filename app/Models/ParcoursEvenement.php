<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ParcoursEvenement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parcours_evenements';

    public const TYPES = ['mandat', 'fonction_gouvernementale', 'poste_prive', 'engagement'];

    protected $fillable = [
        'uuid', 'personne_politique_id', 'type', 'titre', 'organisation', 'description',
        'date_debut', 'date_fin', 'en_cours', 'source_url',
        'statut_validation', 'affiche_publiquement', 'ordre',
        'source_detection', 'detection_confidence', 'detection_raw_data',
        'valide_par', 'valide_at',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'en_cours' => 'boolean',
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
        'detection_confidence' => 'decimal:2',
        'detection_raw_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $e) {
            if (empty($e->uuid)) {
                $e->uuid = (string) Str::uuid();
            }
        });
    }

    public function personnePolitique(): BelongsTo
    {
        return $this->belongsTo(PersonnePolitique::class, 'personne_politique_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }

    public function scopeChronologique($query)
    {
        return $query->orderByRaw('date_debut IS NULL')->orderBy('date_debut', 'desc');
    }
}
