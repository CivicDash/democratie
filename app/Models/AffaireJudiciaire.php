<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AffaireJudiciaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'affaires_judiciaires';

    protected $fillable = [
        'uuid', 'personne_politique_id', 'acteur_an_uid', 'senateur_matricule', 'maire_id',
        'nom', 'prenom', 'parti_politique', 'fonction_au_moment',
        'titre', 'description', 'type_affaire', 'categorie',
        'date_faits', 'date_mise_en_examen', 'date_jugement_premiere_instance',
        'date_jugement_appel', 'date_jugement_cassation', 'date_condamnation_definitive',
        'statut_judiciaire', 'peine_prison_mois', 'peine_prison_avec_sursis',
        'peine_amende_euros', 'peine_ineligibilite_mois', 'peine_complementaire',
        'statut_validation', 'affiche_publiquement',
        'source_detection', 'detecte_at', 'detection_confidence', 'detection_raw_data',
        'valide_par', 'valide_at', 'commentaire_validation',
        'juridiction', 'numero_dossier', 'lien_decision_justice', 'ordre_affichage',
    ];

    protected $casts = [
        'date_faits' => 'date',
        'date_mise_en_examen' => 'date',
        'date_jugement_premiere_instance' => 'date',
        'date_jugement_appel' => 'date',
        'date_jugement_cassation' => 'date',
        'date_condamnation_definitive' => 'date',
        'peine_prison_avec_sursis' => 'boolean',
        'peine_amende_euros' => 'decimal:2',
        'affiche_publiquement' => 'boolean',
        'detecte_at' => 'datetime',
        'valide_at' => 'datetime',
        'detection_confidence' => 'decimal:2',
        'detection_raw_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($a) => $a->uuid = $a->uuid ?? (string) Str::uuid());
    }

    // ── Relations ──

    public function personnePolitique(): BelongsTo
    {
        return $this->belongsTo(PersonnePolitique::class, 'personne_politique_id');
    }

    public function acteurAN(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'acteur_an_uid', 'uid');
    }

    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }

    public function maire(): BelongsTo
    {
        return $this->belongsTo(Maire::class, 'maire_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AffaireSource::class, 'affaire_id');
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(AffaireModerationLog::class, 'affaire_id')
                    ->orderByDesc('created_at');
    }

    // ── Scopes ──

    public function scopePubliques($q)
    {
        return $q->where('affiche_publiquement', true)
                 ->where('statut_validation', 'valide');
    }

    public function scopeEnAttente($q)
    {
        return $q->where('statut_validation', 'detecte');
    }

    public function scopeEnReview($q)
    {
        return $q->where('statut_validation', 'en_review');
    }

    public function scopeACompleter($q)
    {
        return $q->where('statut_validation', 'a_completer');
    }

    public function scopeCondamnations($q)
    {
        return $q->whereIn('statut_judiciaire', [
            'condamne_premiere_instance', 'condamne_appel', 'condamne_definitif',
        ]);
    }

    public function scopeDefinitives($q)
    {
        return $q->where('statut_judiciaire', 'condamne_definitif');
    }

    public function scopeByParti($q, string $p)
    {
        return $q->where('parti_politique', $p);
    }

    public function scopeByType($q, string $t)
    {
        return $q->where('type_affaire', $t);
    }

    public function scopeByCategorie($q, string $c)
    {
        return $q->where('categorie', $c);
    }

    public function scopeForDepute($q, string $uid)
    {
        return $q->where('acteur_an_uid', $uid);
    }

    public function scopeForSenateur($q, string $mat)
    {
        return $q->where('senateur_matricule', $mat);
    }

    public function scopeForPersonne($q, int $id)
    {
        return $q->where('personne_politique_id', $id);
    }

    public function scopeForMaire($q, int $id)
    {
        return $q->where('maire_id', $id);
    }

    // ── Accessors ──

    public function getStatutJudiciaireLibelleAttribute(): string
    {
        return match ($this->statut_judiciaire) {
            'en_cours' => 'Procédure en cours',
            'mis_en_examen' => 'Mis en examen',
            'condamne_premiere_instance' => 'Condamné (1ère instance)',
            'condamne_appel' => 'Condamné (appel)',
            'condamne_definitif' => 'Condamné (définitif)',
            'relaxe' => 'Relaxé',
            'acquitte' => 'Acquitté',
            'prescrit' => 'Prescrit',
            'non_lieu' => 'Non-lieu',
            'amnistie' => 'Amnistié',
            default => $this->statut_judiciaire,
        };
    }

    public function getStatutJudiciaireCouleurAttribute(): string
    {
        return match ($this->statut_judiciaire) {
            'condamne_definitif' => 'red',
            'condamne_appel' => 'orange',
            'condamne_premiere_instance', 'mis_en_examen' => 'yellow',
            'en_cours' => 'gray',
            'relaxe', 'acquitte' => 'green',
            'prescrit', 'non_lieu', 'amnistie' => 'slate',
            default => 'gray',
        };
    }

    public function getTypeAffaireLibelleAttribute(): string
    {
        return match ($this->type_affaire) {
            'corruption' => 'Corruption',
            'detournement_fonds' => 'Détournement de fonds publics',
            'fraude_fiscale' => 'Fraude fiscale',
            'abus_biens_sociaux' => 'Abus de biens sociaux',
            'prise_illegale_interet' => 'Prise illégale d\'intérêts',
            'favoritisme' => 'Favoritisme',
            'trafic_influence' => 'Trafic d\'influence',
            'emploi_fictif' => 'Emploi fictif',
            'recel' => 'Recel',
            'blanchiment' => 'Blanchiment',
            'harcelement' => 'Harcèlement',
            'violence' => 'Violence',
            'diffamation' => 'Diffamation',
            'injure' => 'Injure',
            'financement_illegal_campagne' => 'Financement illégal de campagne',
            'compte_campagne_rejete' => 'Compte de campagne rejeté',
            'conflit_interets' => 'Conflit d\'intérêts',
            'manquement_probite' => 'Manquement à la probité',
            default => ucfirst(str_replace('_', ' ', $this->type_affaire)),
        };
    }

    public function getPeineResumeAttribute(): ?string
    {
        $parts = [];
        if ($this->peine_prison_mois) {
            $txt = $this->peine_prison_mois >= 12
                ? floor($this->peine_prison_mois / 12) . ' an(s)'
                : $this->peine_prison_mois . ' mois';
            $parts[] = $txt . ($this->peine_prison_avec_sursis ? ' avec sursis' : ' ferme');
        }
        if ($this->peine_amende_euros) {
            $parts[] = number_format((float) $this->peine_amende_euros, 0, ',', ' ') . ' € d\'amende';
        }
        if ($this->peine_ineligibilite_mois) {
            $parts[] = $this->peine_ineligibilite_mois . ' mois d\'inéligibilité';
        }
        return count($parts) > 0 ? implode(' + ', $parts) : null;
    }

    public function getGraviteScoreAttribute(): int
    {
        $score = match ($this->categorie) {
            'probite' => 7,
            'financement' => 5,
            'personne' => 4,
            'manquement' => 3,
            default => 2,
        };
        if ($this->peine_prison_mois && !$this->peine_prison_avec_sursis) {
            $score += 2;
        }
        if ($this->peine_ineligibilite_mois) {
            $score += 1;
        }
        return min(10, $score);
    }

    // ── Méthodes workflow ──

    public function prendreEnCharge(User $modo): void
    {
        $ancien = $this->statut_validation;
        $this->update(['statut_validation' => 'en_review']);
        $this->logModeration('prise_en_charge', $ancien, 'en_review', null, $modo);
    }

    public function valider(User $modo, ?string $commentaire = null): void
    {
        $ancien = $this->statut_validation;
        $this->update([
            'statut_validation' => 'valide',
            'affiche_publiquement' => true,
            'valide_par' => $modo->id,
            'valide_at' => now(),
            'commentaire_validation' => $commentaire,
        ]);
        $this->logModeration('validation', $ancien, 'valide', $commentaire, $modo);
    }

    public function rejeter(User $modo, string $motif): void
    {
        $ancien = $this->statut_validation;
        $this->update([
            'statut_validation' => 'rejete',
            'affiche_publiquement' => false,
            'commentaire_validation' => $motif,
        ]);
        $this->logModeration('rejet', $ancien, 'rejete', $motif, $modo);
    }

    public function demanderComplement(User $modo, string $commentaire): void
    {
        $ancien = $this->statut_validation;
        $this->update(['statut_validation' => 'a_completer']);
        $this->logModeration('demande_complement', $ancien, 'a_completer', $commentaire, $modo);
    }

    public function archiver(User $modo, string $motif): void
    {
        $ancien = $this->statut_validation;
        $this->update([
            'statut_validation' => 'archive',
            'affiche_publiquement' => false,
        ]);
        $this->logModeration('archivage', $ancien, 'archive', $motif, $modo);
    }

    private function logModeration(
        string $action,
        ?string $ancien,
        ?string $nouveau,
        ?string $commentaire,
        ?User $modo
    ): void {
        $this->moderationLogs()->create([
            'action' => $action,
            'ancien_statut' => $ancien,
            'nouveau_statut' => $nouveau,
            'commentaire' => $commentaire,
            'moderator_id' => $modo?->id,
        ]);
    }

    // ── Helpers statiques ──

    public static function TYPES_AFFAIRE(): array
    {
        return [
            'corruption', 'detournement_fonds', 'fraude_fiscale', 'abus_biens_sociaux',
            'prise_illegale_interet', 'favoritisme', 'trafic_influence', 'emploi_fictif',
            'recel', 'blanchiment', 'harcelement', 'violence', 'diffamation', 'injure',
            'financement_illegal_campagne', 'compte_campagne_rejete',
            'conflit_interets', 'manquement_probite', 'autre',
        ];
    }

    public static function CATEGORIES(): array
    {
        return ['probite', 'financement', 'personne', 'manquement', 'autre'];
    }

    public static function STATUTS_JUDICIAIRES(): array
    {
        return [
            'en_cours', 'mis_en_examen', 'condamne_premiere_instance',
            'condamne_appel', 'condamne_definitif', 'relaxe', 'acquitte',
            'prescrit', 'non_lieu', 'amnistie',
        ];
    }

    public static function STATUTS_VALIDATION(): array
    {
        return [
            'detecte', 'en_review', 'valide', 'rejete', 'a_completer', 'conteste', 'archive',
        ];
    }
}
