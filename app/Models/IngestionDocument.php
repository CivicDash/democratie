<?php

namespace App\Models;

use App\Models\Concerns\APorteur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class IngestionDocument extends Model
{
    use APorteur, HasFactory;

    protected $table = 'ingestion_documents';

    public const TYPES = ['video', 'audio', 'article', 'communique'];
    public const STATUTS = ['collecte', 'transcrit', 'extrait', 'traite', 'erreur'];

    protected $fillable = [
        'uuid', 'candidat_id', 'type', 'titre', 'url', 'date_publication', 'duree_s',
        'transcription_path', 'transcription_note', 'archive_url', 'hash_contenu',
        'contrat_version', 'generateur', 'statut',
        'porteur_type', 'porteur_libelle', 'endossement_url', 'endossement_date', 'endossement_note',
    ];

    protected $casts = [
        'date_publication' => 'date',
        'duree_s' => 'integer',
        'endossement_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $d) {
            if (empty($d->uuid)) {
                $d->uuid = (string) Str::uuid();
            }
        });
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(CandidatPresidentielle::class, 'candidat_id');
    }

    public function propositions(): HasMany
    {
        return $this->hasMany(IngestionProposition::class, 'document_id');
    }

    /** Cadrages budgétaires dont ce document est la source. */
    public function cadrages(): HasMany
    {
        return $this->hasMany(CadrageBudgetaire::class, 'ingestion_document_id');
    }
}
