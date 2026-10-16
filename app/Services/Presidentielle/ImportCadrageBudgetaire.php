<?php

namespace App\Services\Presidentielle;

use App\Models\CadrageBudgetaire;
use App\Models\CadrageLigne;
use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PersonnePolitique;
use App\Support\Locuteur;
use App\Support\Porteur;
use App\Support\UrlSource;
use App\Support\VerificationVerbatim;
use InvalidArgumentException;

/**
 * Import du cadrage budgétaire : contrat `presidentielle.cadrage_budgetaire.v1`.
 *
 * Une même source peut porter plusieurs cadrages (la conférence du 06/10/2026 présente un
 * programme quinquennal ET un contre-budget 2027) : le fichier en liste autant que
 * nécessaire, chacun avec sa nature et ses lignes. Une ligne = une valeur telle que la
 * source la donne, avec sa citation, son repérage et son locuteur.
 *
 * Même structure que ImportAffirmations : valider() contrôle tout sans rien écrire, puis
 * ecrire() écrit (dans la transaction de l'appelant). Le contrat est vérifié, pas
 * seulement lu ; une URL provisoire refuse le fichier. Tout entre en `detecte`, non
 * publié, chaque ligne « à vérifier » : un humain doit confirmer que le CHIFFRE correspond
 * bien à la citation.
 */
class ImportCadrageBudgetaire
{
    public const CONTRAT = 'presidentielle.cadrage_budgetaire.v1';

    /** Unité par défaut d'une ligne chiffrée, selon sa rubrique. */
    private const UNITE_PAR_DEFAUT = [
        'economies' => 'md_eur', 'depenses_nouvelles' => 'md_eur', 'recettes_nouvelles' => 'md_eur',
        'poste' => 'md_eur', 'solde_vise' => 'pct_pib', 'dette_visee' => 'pct_pib',
        'deficit_constate' => 'pct_pib', 'dette_constatee' => 'pct_pib', 'hypothese' => 'pct',
    ];

    /** @var list<string> */
    private array $erreurs = [];

    /** @var list<string> */
    private array $avertissements = [];

    /**
     * Contrôle le contenu sans rien écrire.
     *
     * @param  ?string  $porteurType  porteur du document déjà connu (import avec une prise de parole)
     * @return list<string> erreurs, vide = importable
     */
    public function valider(array $data, ?string $porteurType = null, string $election = '2027', ?int $candidatIdConnu = null): array
    {
        $this->erreurs = [];
        $this->avertissements = [];

        $contrat = $data['contrat'] ?? null;
        if ($contrat !== null && $contrat !== self::CONTRAT) {
            $this->erreurs[] = 'contrat : attendu « '.self::CONTRAT." », reçu « {$contrat} »";

            return $this->erreurs;
        }

        $cadrages = self::cadrages($data);
        if ($cadrages === null) {
            $this->erreurs[] = 'format non reconnu : clé « cadrages » (liste) attendue, chacun avec nature, titre et lignes — contrat '.self::CONTRAT;

            return $this->erreurs;
        }
        if ($cadrages === []) {
            $this->erreurs[] = 'cadrages : liste vide';

            return $this->erreurs;
        }

        $source = is_array($data['source'] ?? null) ? $data['source'] : [];
        if ($porteurType === null) {
            try {
                [$colonnes, $avert] = Porteur::depuisJson($source['porteur'] ?? $data['porteur'] ?? null);
                $porteurType = $colonnes['porteur_type'];
                $this->avertissements = [...$this->avertissements, ...$avert];
            } catch (InvalidArgumentException $e) {
                $this->erreurs[] = 'source.porteur : '.$e->getMessage();

                return $this->erreurs;
            }
        }
        if (isset($source['url']) && $source['url'] !== null && ! UrlSource::estValide($source['url'])) {
            $this->erreurs[] = "source.url : URL invalide ou provisoire « {$source['url']} »";
        }

        $slug = $data['candidat_slug'] ?? null;
        $candidatId = $slug ? self::resoudreCandidat($slug, $election) : $candidatIdConnu;
        if ($slug && ! $candidatId) {
            $this->erreurs[] = "candidat_slug : « {$slug} » introuvable pour {$election}";
        }
        $porteurEntourage = Porteur::estEntourage($porteurType);

        foreach ($cadrages as $i => $c) {
            $chemin = "cadrages[{$i}]";
            if (! is_array($c)) {
                $this->erreurs[] = "{$chemin} : objet attendu";

                continue;
            }
            $nature = $c['nature'] ?? null;
            $reference = $nature === CadrageBudgetaire::NATURE_REFERENCE;
            if (! array_key_exists((string) $nature, CadrageBudgetaire::NATURES)) {
                $this->erreurs[] = "{$chemin}.nature : « ".($nature ?? 'rien').' » inconnue (attendu : '.implode(', ', array_keys(CadrageBudgetaire::NATURES)).')';
            }
            if ($reference && $slug) {
                $this->erreurs[] = "{$chemin} : la référence officielle n'a pas de candidat (retirer candidat_slug, un fichier à part)";
            }
            if (! $reference && ! $candidatId && ! $slug) {
                $this->erreurs[] = "{$chemin} : candidat non résolu (candidat_slug absent et aucune proposition du document ne désigne un candidat unique)";
            }
            if (! $reference && $porteurEntourage) {
                $this->erreurs[] = "{$chemin} : document porté par un parti ou un groupe parlementaire — sa vision n'est pas le cadrage du candidat";
            }
            if (blank($c['titre'] ?? null)) {
                $this->erreurs[] = "{$chemin}.titre : manquant";
            } elseif (mb_strlen((string) $c['titre']) > 300) {
                $this->erreurs[] = "{$chemin}.titre : 300 caractères au plus";
            }
            $date = $c['date_source'] ?? $source['date'] ?? null;
            if (! self::dateIso($date)) {
                $this->erreurs[] = "{$chemin}.date_source : date AAAA-MM-JJ attendue (ou source.date)";
            }
            if (isset($c['non_precise']) && ! is_array($c['non_precise'])) {
                $this->erreurs[] = "{$chemin}.non_precise : liste attendue";
            }
            if (! is_array($c['lignes'] ?? null)) {
                $this->erreurs[] = "{$chemin}.lignes : liste attendue";

                continue;
            }

            foreach (array_values($c['lignes']) as $j => $l) {
                $this->validerLigne(is_array($l) ? $l : [], "{$chemin}.lignes[{$j}]", $reference, $slug, $porteurType);
            }
        }

        return $this->erreurs;
    }

    /** @return list<string> */
    public function avertissements(): array
    {
        return array_values(array_unique($this->avertissements));
    }

    /**
     * Écrit les cadrages (après valider()). À appeler dans une transaction.
     *
     * @param  ?IngestionDocument  $document  la prise de parole source ; sinon retrouvée par
     *                                        `source.url`, ou créée depuis le bloc `source`
     * @return array{cadrages: int, lignes: int, verbatim_verifie: int, verbatim_absent: int, propositions_liees: int, document_id: ?int, document_cree: bool}
     */
    public function ecrire(array $data, ?IngestionDocument $document = null, ?string $sourceNorm = null, string $election = '2027', ?int $candidatIdConnu = null): array
    {
        $cadrages = self::cadrages($data) ?? [];
        $source = is_array($data['source'] ?? null) ? $data['source'] : [];
        $slug = $data['candidat_slug'] ?? null;
        $candidatId = $slug ? self::resoudreCandidat($slug, $election) : $candidatIdConnu;
        $stats = ['cadrages' => 0, 'lignes' => 0, 'verbatim_verifie' => 0, 'verbatim_absent' => 0, 'propositions_liees' => 0, 'document_id' => null, 'document_cree' => false];

        $avecCandidat = collect($cadrages)->contains(fn ($c) => ($c['nature'] ?? null) !== CadrageBudgetaire::NATURE_REFERENCE);
        if ($avecCandidat && ! $document) {
            [$document, $stats['document_cree']] = $this->documentSource($source, $data, $candidatId);
        }
        $stats['document_id'] = $document?->id;
        $porteurType = $document?->porteur_type ?? 'candidat';
        if ($avecCandidat && Porteur::estEntourage($porteurType)) {
            throw new InvalidArgumentException('la prise de parole #'.$document->id.' est portée par un parti ou un groupe parlementaire : sa vision n\'est pas le cadrage du candidat');
        }

        // Propositions du même document : pour relier automatiquement une ligne à la
        // proposition dont elle reprend la citation.
        $propositions = $document
            ? IngestionProposition::where('document_id', $document->id)->orderBy('id')->get(['id', 'candidat_id', 'citation_verbatim'])
            : collect();

        foreach ($cadrages as $c) {
            $reference = ($c['nature'] ?? null) === CadrageBudgetaire::NATURE_REFERENCE;

            $cadrage = CadrageBudgetaire::create([
                'election' => $election,
                'candidat_id' => $reference ? null : $candidatId,
                'nature' => $c['nature'],
                'titre' => trim((string) $c['titre']),
                'date_source' => $c['date_source'] ?? ($source['date'] ?? null),
                'ingestion_document_id' => $reference ? null : $document?->id,
                'rubriques_non_precisees' => array_values(array_filter(array_map(fn ($n) => is_string($n) ? trim($n) : null, $c['non_precise'] ?? []))) ?: null,
                'note_methodologique' => self::texte($c['note_methodologique'] ?? null, 5000),
                'statut_validation' => 'detecte',
                'affiche_publiquement' => false,
            ]);
            $stats['cadrages']++;

            foreach (array_values($c['lignes'] ?? []) as $ordre => $l) {
                $colonnes = $this->colonnesLigne($l, $reference, $slug, $porteurType);
                $citation = (string) ($colonnes['citation'] ?? '');

                $verifie = false;
                if ($sourceNorm !== null && $citation !== '' && ! $reference) {
                    $issue = VerificationVerbatim::verifier($citation, $sourceNorm)['issue'];
                    $verifie = $issue === 'verifiee';
                    if ($issue === 'absente') {
                        $stats['verbatim_absent']++;
                    }
                }
                $stats['verbatim_verifie'] += $verifie ? 1 : 0;

                $propositionId = ($citation !== '' && ! $reference)
                    ? $this->propositionCorrespondante($citation, $propositions, $candidatId)
                    : null;
                $stats['propositions_liees'] += $propositionId ? 1 : 0;

                CadrageLigne::create($colonnes + [
                    'cadrage_id' => $cadrage->id,
                    'proposition_id' => $propositionId,
                    'verbatim_verifie' => $verifie,
                    'verification' => 'a_verifier',
                    'ordre' => $ordre,
                ]);
                $stats['lignes']++;
            }
        }

        return $stats;
    }

    /** Les cadrages du fichier ; un fichier à cadrage unique peut omettre la liste. */
    public static function cadrages(array $data): ?array
    {
        if (isset($data['cadrages'])) {
            return is_array($data['cadrages']) ? array_values($data['cadrages']) : null;
        }
        if (isset($data['lignes'], $data['nature'])) {
            return [$data];
        }

        return null;
    }

    private function validerLigne(array $l, string $chemin, bool $reference, ?string $slug, ?string $porteurType): void
    {
        $rubrique = $l['rubrique'] ?? null;
        $admises = $reference
            ? array_keys(CadrageLigne::RUBRIQUES)
            : [...array_keys(CadrageLigne::GRILLE), CadrageLigne::POSTE];
        if (! in_array($rubrique, $admises, true)) {
            $this->erreurs[] = "{$chemin}.rubrique : « ".($rubrique ?? 'rien').' » non admise (attendu : '.implode(', ', $admises).')';

            return;
        }

        foreach (['montant', 'montant_max'] as $cle) {
            if (isset($l[$cle]) && ! is_int($l[$cle]) && ! is_float($l[$cle])) {
                $this->erreurs[] = "{$chemin}.{$cle} : nombre attendu (la valeur telle que dite va dans valeur_texte)";
            }
        }
        $unite = $l['unite'] ?? self::uniteParDefaut($rubrique, $l['montant'] ?? null);
        if (! in_array($unite, CadrageLigne::unitesAdmises($rubrique), true)) {
            $this->erreurs[] = "{$chemin}.unite : « {$unite} » non admise pour {$rubrique} (attendu : ".implode(', ', CadrageLigne::unitesAdmises($rubrique)).')';
        }
        if (isset($l['qualificatif']) && ! array_key_exists($l['qualificatif'], CadrageLigne::QUALIFICATIFS)) {
            $this->erreurs[] = "{$chemin}.qualificatif : « {$l['qualificatif']} » inconnu (attendu : ".implode(', ', array_keys(CadrageLigne::QUALIFICATIFS)).')';
        }
        if (isset($l['portee']) && ! array_key_exists($l['portee'], CadrageLigne::PORTEES)) {
            $this->erreurs[] = "{$chemin}.portee : « {$l['portee']} » inconnue (attendu : annuelle, cumulee)";
        }
        if (isset($l['annee']) && (! is_int($l['annee']) || $l['annee'] < 1990 || $l['annee'] > 2100)) {
            $this->erreurs[] = "{$chemin}.annee : année entière attendue (ou null si non précisée)";
        }
        if (in_array($rubrique, CadrageLigne::RUBRIQUES_MULTIPLES, true) && blank($l['libelle'] ?? null)) {
            $this->erreurs[] = "{$chemin}.libelle : obligatoire pour {$rubrique}";
        }
        $reperage = $l['reperage'] ?? $l['timecode'] ?? null;
        if ($reperage !== null && mb_strlen((string) $reperage) > 100) {
            $this->erreurs[] = "{$chemin}.reperage : 100 caractères au plus";
        }

        try {
            $loc = Locuteur::normaliser($l, $slug, $porteurType);
            if ($loc['qualite_locuteur'] === Locuteur::PARTI) {
                $this->erreurs[] = "{$chemin} : « {$loc['locuteur']} » parle pour un parti — sa parole n'entre pas dans le cadrage du candidat";
            }
            if ($reference && $loc['locuteur'] !== null) {
                $this->erreurs[] = "{$chemin} : une ligne de référence n'a pas de locuteur";
            }
            foreach ($loc['avertissements'] as $a) {
                $this->avertissements[] = "{$chemin} : {$a}";
            }
        } catch (InvalidArgumentException $e) {
            $this->erreurs[] = "{$chemin} : ".$e->getMessage();
        }

        if ($reference) {
            $src = is_array($l['source'] ?? null) ? $l['source'] : [];
            if (isset($src['url']) && $src['url'] !== null && ! UrlSource::estValide($src['url'])) {
                $this->erreurs[] = "{$chemin}.source.url : URL invalide ou provisoire « {$src['url']} »";
            }
            if (isset($src['date']) && ! self::dateIso($src['date'])) {
                $this->erreurs[] = "{$chemin}.source.date : date AAAA-MM-JJ attendue";
            }
        } elseif (blank($l['citation'] ?? null)) {
            $this->avertissements[] = "{$chemin} : sans citation — la ligne ne pourra pas paraître tant qu'elle n'est pas sourcée";
        }
    }

    /** @return array<string, mixed> */
    private function colonnesLigne(array $l, bool $reference, ?string $slug, ?string $porteurType): array
    {
        $loc = Locuteur::normaliser($l, $slug, $porteurType);
        $src = $reference && is_array($l['source'] ?? null) ? $l['source'] : [];
        $autres = collect(is_array($l['autres_citations'] ?? null) ? $l['autres_citations'] : [])
            ->filter(fn ($a) => is_array($a) && filled($a['citation'] ?? null))
            ->map(fn ($a) => array_filter([
                'citation' => trim((string) $a['citation']),
                'reperage' => self::texte($a['reperage'] ?? $a['timecode'] ?? null, 100),
                'locuteur' => self::texte($a['locuteur'] ?? null, 150),
                'role_locuteur' => self::texte($a['role_locuteur'] ?? null, 200),
            ], fn ($v) => $v !== null))
            ->values()->all();

        return [
            'rubrique' => $l['rubrique'],
            'libelle' => self::texte($l['libelle'] ?? null, 300),
            'montant' => $l['montant'] ?? null,
            'montant_max' => $l['montant_max'] ?? null,
            'unite' => $l['unite'] ?? self::uniteParDefaut($l['rubrique'], $l['montant'] ?? null),
            'qualificatif' => $l['qualificatif'] ?? null,
            'portee' => $l['portee'] ?? null,
            'annee' => $l['annee'] ?? null,
            'valeur_texte' => self::texte($l['valeur_texte'] ?? null, 2000),
            'citation' => self::texte($l['citation'] ?? null, 5000),
            'reperage' => self::texte($l['reperage'] ?? $l['timecode'] ?? null, 100),
            'locuteur' => $loc['locuteur'],
            'role_locuteur' => $loc['role_locuteur'],
            'qualite_locuteur' => $loc['qualite_locuteur'],
            'source_producteur' => self::texte($src['producteur'] ?? null, 120),
            'source_titre' => self::texte($src['titre'] ?? null, 300),
            'source_url' => UrlSource::nettoyer($src['url'] ?? null),
            'source_date' => $src['date'] ?? null,
            'note' => self::texte($l['note'] ?? null, 5000),
            'autres_citations' => $autres ?: null,
        ];
    }

    /**
     * Retrouve (ou crée) la prise de parole décrite par le bloc `source`.
     *
     * @return array{0: IngestionDocument, 1: bool} document, créé ?
     */
    private function documentSource(array $source, array $data, ?int $candidatId): array
    {
        $url = UrlSource::nettoyer($source['url'] ?? null);
        if ($url && ($existant = IngestionDocument::where('url', $url)->orderByDesc('id')->first())) {
            return [$existant, false];
        }

        [$porteur] = Porteur::depuisJson($source['porteur'] ?? $data['porteur'] ?? null);

        $document = IngestionDocument::create([
            'candidat_id' => $candidatId,
            'type' => mb_substr((string) ($source['type'] ?? 'video'), 0, 20),
            'titre' => mb_substr((string) ($source['titre'] ?? 'Cadrage budgétaire'), 0, 500),
            'url' => $url,
            'date_publication' => $source['date'] ?? null,
            'duree_s' => $source['duree_s'] ?? null,
            'contrat_version' => 'cadrage.v1',
            'generateur' => self::texte($data['generateur'] ?? null, 60),
            'statut' => 'extrait',
            'porteur_type' => $porteur['porteur_type'],
            'porteur_libelle' => $porteur['porteur_libelle'],
            'endossement_url' => $porteur['endossement_url'],
            'endossement_date' => $porteur['endossement_date'],
            'endossement_note' => $porteur['endossement_note'],
        ]);

        return [$document, true];
    }

    /** La première proposition du document dont la citation contient celle de la ligne, ou y est contenue. */
    private function propositionCorrespondante(string $citation, $propositions, ?int $candidatId): ?int
    {
        $ligne = VerificationVerbatim::normaliser($citation);
        if (mb_strlen($ligne) < 15) {
            return null;
        }

        foreach ($propositions as $p) {
            if ($candidatId && (int) $p->candidat_id !== $candidatId) {
                continue;
            }
            $prop = VerificationVerbatim::normaliser((string) $p->citation_verbatim);
            if ($prop !== '' && (str_contains($prop, $ligne) || (mb_strlen($prop) >= 15 && str_contains($ligne, $prop)))) {
                return $p->id;
            }
        }

        return null;
    }

    public static function resoudreCandidat(?string $slug, string $election): ?int
    {
        if (! $slug) {
            return null;
        }
        $personne = PersonnePolitique::where('slug', $slug)->first();

        return $personne
            ? CandidatPresidentielle::where('personne_politique_id', $personne->id)->where('election', $election)->value('id')
            : null;
    }

    /** Sans montant, la valeur est en toutes lettres ; sinon l'unité usuelle de la rubrique. */
    private static function uniteParDefaut(string $rubrique, mixed $montant): string
    {
        return $montant === null ? 'texte' : (self::UNITE_PAR_DEFAUT[$rubrique] ?? 'texte');
    }

    private static function dateIso(mixed $d): bool
    {
        return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
            && checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4));
    }

    private static function texte(mixed $v, int $max): ?string
    {
        $v = trim((string) ($v ?? ''));

        return $v === '' ? null : mb_substr($v, 0, $max);
    }
}
