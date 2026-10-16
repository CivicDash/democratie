<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une valeur d'un cadrage budgétaire, telle que la source la donne : montant, unité,
 * portée et année ne sont jamais convertis ni additionnés par nous. Une ligne ne paraît
 * qu'une fois vérifiée par un humain (le chiffre correspond bien à la citation).
 */
class CadrageLigne extends Model
{
    use HasFactory;

    protected $table = 'cadrage_lignes';

    /** Grille commune, dans l'ordre d'affichage. Les postes détaillés sont listés à part. */
    public const GRILLE = [
        'horizon' => 'Horizon',
        'economies' => 'Économies',
        'depenses_nouvelles' => 'Dépenses nouvelles',
        'recettes_nouvelles' => 'Recettes nouvelles',
        'solde_vise' => 'Déficit visé',
        'dette_visee' => 'Dette visée',
        'hypothese' => 'Hypothèses',
        'autre_objectif' => 'Autres objectifs',
    ];

    public const POSTE = 'poste';

    /** Rubriques propres à la ligne de référence officielle. */
    public const REFERENCE_SEULE = [
        'deficit_constate' => 'Déficit constaté',
        'dette_constatee' => 'Dette constatée',
        'avis_hcfp' => 'Avis du Haut Conseil des finances publiques',
    ];

    public const RUBRIQUES = self::GRILLE + [self::POSTE => 'Postes détaillés'] + self::REFERENCE_SEULE;

    /** Rubriques à plusieurs lignes par nature (une ligne = un poste, une hypothèse…). */
    public const RUBRIQUES_MULTIPLES = ['poste', 'hypothese', 'autre_objectif'];

    /** Une année est indispensable pour lire un niveau de déficit ou de dette. */
    public const RUBRIQUES_DATEES = ['solde_vise', 'dette_visee', 'deficit_constate', 'dette_constatee'];

    public const UNITES = [
        'md_eur' => 'Md€',
        'm_eur' => 'M€',
        'eur' => '€',
        'pct_pib' => '% du PIB',
        'pts_pib' => 'point(s) de PIB',
        'pct' => '%',
        'texte' => 'en toutes lettres',
    ];

    /** Unités admises par rubrique (toutes admettent `texte`, avec la valeur en toutes lettres). */
    public const UNITES_PAR_RUBRIQUE = [
        'horizon' => ['texte'],
        'avis_hcfp' => ['texte'],
        'economies' => ['md_eur', 'm_eur', 'eur', 'pct_pib', 'pts_pib', 'texte'],
        'depenses_nouvelles' => ['md_eur', 'm_eur', 'eur', 'pct_pib', 'pts_pib', 'texte'],
        'recettes_nouvelles' => ['md_eur', 'm_eur', 'eur', 'pct_pib', 'pts_pib', 'texte'],
        'solde_vise' => ['pct_pib', 'pts_pib', 'md_eur', 'texte'],
        'dette_visee' => ['pct_pib', 'pts_pib', 'md_eur', 'texte'],
        'deficit_constate' => ['pct_pib', 'md_eur', 'texte'],
        'dette_constatee' => ['pct_pib', 'md_eur', 'texte'],
    ];

    public const QUALIFICATIFS = [
        'environ' => 'environ',
        'au_moins' => 'au moins',
        'plus_de' => 'plus de',
        'au_plus' => 'au plus',
        'moins_de' => 'moins de',
    ];

    public const PORTEES = [
        'annuelle' => 'par an',
        'cumulee' => 'cumulé',
    ];

    public const VERIFICATIONS = [
        'a_verifier' => 'À vérifier',
        'verifie' => 'Vérifié',
    ];

    /** Champs dont la modification remet la ligne « à vérifier » : ce qu'un humain a confirmé. */
    public const CHAMPS_VERIFIES = ['rubrique', 'libelle', 'montant', 'montant_max', 'unite', 'qualificatif', 'portee', 'annee', 'valeur_texte', 'citation', 'reperage', 'locuteur', 'role_locuteur', 'qualite_locuteur', 'source_producteur', 'source_titre', 'source_url', 'source_date', 'ingestion_document_id', 'proposition_id'];

    protected $fillable = [
        'cadrage_id', 'rubrique', 'libelle', 'montant', 'montant_max', 'unite', 'qualificatif',
        'portee', 'annee', 'valeur_texte', 'citation', 'reperage',
        'locuteur', 'role_locuteur', 'qualite_locuteur', 'proposition_id', 'ingestion_document_id',
        'source_producteur', 'source_titre', 'source_url', 'source_date',
        'note', 'autres_citations', 'verbatim_verifie', 'verification', 'verifie_par', 'verifie_at', 'ordre',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_max' => 'decimal:2',
        'annee' => 'integer',
        'source_date' => 'date',
        'autres_citations' => 'array',
        'verbatim_verifie' => 'boolean',
        'verifie_at' => 'datetime',
        'ordre' => 'integer',
    ];

    public function cadrage(): BelongsTo
    {
        return $this->belongsTo(CadrageBudgetaire::class, 'cadrage_id');
    }

    public function proposition(): BelongsTo
    {
        return $this->belongsTo(IngestionProposition::class, 'proposition_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(IngestionDocument::class, 'ingestion_document_id');
    }

    /** Seules les lignes vérifiées paraissent — comme AffirmationConstat::estAffiche(). */
    public function estAffichee(): bool
    {
        return $this->verification === 'verifie';
    }

    public static function libelleRubrique(string $cle): string
    {
        return self::RUBRIQUES[$cle] ?? $cle;
    }

    /** @return list<string> */
    public static function unitesAdmises(string $rubrique): array
    {
        return self::UNITES_PAR_RUBRIQUE[$rubrique] ?? array_keys(self::UNITES);
    }
}
