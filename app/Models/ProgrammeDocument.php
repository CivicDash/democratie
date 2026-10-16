<?php

namespace App\Models;

use App\Models\Concerns\APorteur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProgrammeDocument extends Model
{
    use APorteur, HasFactory, SoftDeletes;

    protected $table = 'programme_documents';

    protected $fillable = [
        'uuid', 'candidat_id', 'titre', 'version', 'url', 'archive_url', 'hash_contenu',
        'structure', 'statut_validation', 'affiche_publiquement', 'valide_par', 'valide_at',
        'porteur_type', 'porteur_libelle', 'endossement_url', 'endossement_date', 'endossement_note',
    ];

    protected $casts = [
        'structure' => 'array',
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(ProgrammeDocumentItem::class, 'document_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }
}
