<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProgrammeMesure extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'programme_mesures';

    public const STATUTS_MESURE = ['annoncee', 'precisee', 'abandonnee', 'modifiee'];
    public const STATUTS_VALIDATION = ['detecte', 'en_review', 'a_completer', 'valide'];

    protected $fillable = [
        'uuid', 'candidat_id', 'theme_id', 'titre', 'resume', 'description_complete',
        'chiffrage_annonce', 'source_officielle_url', 'date_annonce', 'statut_mesure',
        'est_mise_en_avant', 'statut_validation', 'affiche_publiquement', 'ordre',
        'source_detection', 'detection_confidence', 'detection_raw_data',
        'valide_par', 'valide_at', 'commentaire_validation',
    ];

    protected $casts = [
        'date_annonce' => 'date',
        'est_mise_en_avant' => 'boolean',
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
        'detection_confidence' => 'decimal:2',
        'detection_raw_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->uuid)) {
                $m->uuid = (string) Str::uuid();
            }
        });
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(CandidatPresidentielle::class, 'candidat_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(ProgrammeTheme::class, 'theme_id');
    }

    public function arguments(): HasMany
    {
        return $this->hasMany(Argument::class, 'mesure_id');
    }

    public function argumentsPour(): HasMany
    {
        return $this->arguments()->where('sens', 'pour');
    }

    public function argumentsContre(): HasMany
    {
        return $this->arguments()->where('sens', 'contre');
    }

    public function scrutinLiens(): HasMany
    {
        return $this->hasMany(MesureScrutinLien::class, 'mesure_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }

    public function scopeMiseEnAvant($query)
    {
        return $query->where('est_mise_en_avant', true);
    }
}
