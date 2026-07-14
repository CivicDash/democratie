<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IngestionProposition extends Model
{
    use HasFactory;

    protected $table = 'ingestion_propositions';

    public const TYPES = ['mesure', 'position', 'revirement', 'declaration'];
    public const STATUTS = ['detecte', 'validee', 'rattachee', 'rejetee'];

    protected $fillable = [
        'uuid', 'document_id', 'candidat_slug', 'candidat_id', 'theme_slug', 'theme_id',
        'type', 'resume_propose', 'citation_verbatim', 'timestamp_ou_paragraphe',
        'source_url', 'confiance', 'verbatim_verifie', 'statut', 'mesure_id',
        'valide_par', 'valide_at', 'raw_llm_output',
    ];

    protected $casts = [
        'confiance' => 'decimal:2',
        'verbatim_verifie' => 'boolean',
        'valide_at' => 'datetime',
        'raw_llm_output' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (empty($p->uuid)) {
                $p->uuid = (string) Str::uuid();
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(IngestionDocument::class, 'document_id');
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(CandidatPresidentielle::class, 'candidat_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(ProgrammeTheme::class, 'theme_id');
    }

    public function mesure(): BelongsTo
    {
        return $this->belongsTo(ProgrammeMesure::class, 'mesure_id');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'detecte');
    }
}
