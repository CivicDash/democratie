<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Un événement de campagne : meeting, débat, discours, déplacement.
 *
 * À distinguer de `IngestionDocument`, qui est une SOURCE dépouillée. Un événement peut
 * n'en avoir aucune (il est à venir) ou en avoir une (il a été transcrit et analysé).
 */
class EvenementCampagne extends Model
{
    use HasFactory;

    protected $table = 'evenements_campagne';

    protected $fillable = [
        'uuid', 'election', 'type', 'titre', 'description',
        'date_debut', 'date_fin', 'journee_entiere', 'precision_date',
        'lieu', 'ville', 'departement', 'organisateur', 'media',
        'url_source', 'url_video', 'archive_url', 'ingestion_document_id',
        'statut', 'statut_validation', 'affiche_publiquement', 'note_methodologique',
        'valide_par', 'valide_at',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'journee_entiere' => 'boolean',
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $e) => $e->uuid ??= (string) Str::uuid());
    }

    public function candidats(): BelongsToMany
    {
        return $this->belongsToMany(CandidatPresidentielle::class, 'evenement_campagne_candidat', 'evenement_id', 'candidat_id')
            ->withPivot(['role', 'participation_confirmee'])
            ->withTimestamps();
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(IngestionDocument::class, 'ingestion_document_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }

    public function scopeChronologique($query)
    {
        return $query->orderBy('date_debut');
    }

    /**
     * Un événement n'est publiable que s'il est daté ET vérifiable à une source.
     * Un calendrier sans lien de vérification contredirait la promesse du site.
     */
    public function raisonsNonPubliable(): array
    {
        $raisons = [];
        if (! $this->date_debut) {
            $raisons[] = 'date manquante';
        }
        if (blank($this->url_source) && blank($this->url_video)) {
            $raisons[] = 'aucune source vérifiable (url_source ou url_video)';
        }

        return $raisons;
    }
}
