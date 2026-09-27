<?php

namespace App\Services\Presidentielle;

use App\Models\Affirmation;
use App\Models\AffirmationConstat;
use App\Models\AffirmationGraphique;
use App\Models\AffirmationSource;
use App\Models\ProgrammeTheme;
use App\Support\UrlSource;
use Illuminate\Support\Facades\DB;

/**
 * Import des repères chiffrés : contrat `presidentielle.affirmations.v2` (une question
 * neutre par repère, sans verdict), et lecture du v1 hérité de « Ce qu'on entend » (ses
 * verdicts et sa coloration perçue sont ignorés : le format à verdict est abandonné depuis
 * le 27/09/2026 ; la question se saisit alors dans le back-office).
 *
 * Plus strict que les imports existants, et c'est voulu :
 *  - le contrat est vérifié, pas seulement lu ;
 *  - une URL est valide ou `null` — tout autre valeur REFUSE le fichier, au lieu d'être
 *    mise à null sans bruit : ce JSON sort d'une conversion, et un placeholder y signale
 *    une erreur de conversion, pas une source à compléter ;
 *  - une source déclarée et citée nulle part est refusée : elle serait publiée sans
 *    qu'aucune phrase ne dise ce qu'elle établit.
 *
 * Tout entre en `detecte`, non publié. Une fiche déjà en base n'est remplacée qu'à la
 * demande (`remplacer`), et jamais si elle est publiée : après l'import, c'est le
 * back-office qui fait foi, plus le Markdown du dossier.
 */
class ImportAffirmations
{
    public const CONTRAT = 'presidentielle.affirmations.v2';

    /** Ancien format « Ce qu'on entend » : lu, verdicts ignorés. */
    public const CONTRAT_V1 = 'presidentielle.affirmations.v1';

    /** @var list<string> */
    private array $erreurs = [];

    /**
     * Contrôle le contenu sans rien écrire.
     *
     * @return list<string> erreurs, vide = importable
     */
    public function valider(array $data, bool $remplacer = false): array
    {
        $this->erreurs = [];

        if (! in_array($data['contrat'] ?? null, [self::CONTRAT, self::CONTRAT_V1], true)) {
            $this->erreur('contrat', 'attendu « '.self::CONTRAT.' », reçu « '.($data['contrat'] ?? 'rien').' »');

            return $this->erreurs;
        }
        $v2 = $data['contrat'] === self::CONTRAT;
        $fiches = self::fiches($data);
        if ($fiches === []) {
            $this->erreur($v2 ? 'reperes' : 'affirmations', 'liste absente ou vide');

            return $this->erreurs;
        }

        $election = (string) ($data['election'] ?? '2027');
        $themes = ProgrammeTheme::pluck('id', 'slug')->all();
        $catalogue = array_column(config('eurostat.indicateurs', []), 'code');
        $slugs = [];

        foreach (array_values($fiches) as $i => $f) {
            $this->validerFiche(is_array($f) ? $f : [], ($v2 ? 'reperes' : 'affirmations')."[{$i}]", $election, $themes, $catalogue, $remplacer, $slugs, $v2);
        }

        return $this->erreurs;
    }

    /**
     * Écrit les fiches (après valider()). À appeler dans une transaction.
     *
     * @return array{fiches: int, constats: int, a_verifier: int, sources: int, graphiques: int}
     */
    public function ecrire(array $data): array
    {
        $election = (string) ($data['election'] ?? '2027');
        $themes = ProgrammeTheme::pluck('id', 'slug')->all();
        $stats = ['fiches' => 0, 'constats' => 0, 'a_verifier' => 0, 'sources' => 0, 'graphiques' => 0];

        DB::transaction(function () use ($data, $election, $themes, &$stats) {
            foreach (self::fiches($data) as $f) {
                $fiche = Affirmation::withTrashed()->firstOrNew(['election' => $election, 'slug' => $f['slug']]);
                if ($fiche->exists) {
                    if ($fiche->trashed()) {
                        $fiche->restore();
                    }
                    $fiche->graphiques()->delete();
                    $fiche->constats()->delete();
                    $fiche->sources()->delete();
                    $fiche->themesSecondaires()->detach();
                }

                $fiche->fill([
                    // L'énoncé ne sert plus que de trace interne de l'origine ; en v2, la
                    // question en tient lieu s'il n'est pas fourni.
                    'enonce' => $f['enonce'] ?? $f['question'],
                    'question' => $f['question'] ?? null,
                    'resume' => $f['resume'] ?? null,
                    'theme_id' => $themes[$f['theme']],
                    'part_de_valeur' => false,
                    'derniere_verification' => $f['derniere_verification'] ?? null,
                    'coloration_percue' => null,
                    'statut_validation' => 'detecte',
                    'affiche_publiquement' => false,
                    'valide_par' => null,
                    'valide_at' => null,
                ])->save();

                $fiche->themesSecondaires()->sync(array_map(fn ($s) => $themes[$s], $f['themes_secondaires'] ?? []));

                $sources = [];
                foreach ($f['sources'] ?? [] as $s) {
                    $sources[$s['cle']] = $fiche->sources()->create([
                        'cle' => $s['cle'],
                        'producteur' => $s['producteur'],
                        'titre' => $s['titre'],
                        'url' => $s['url'] ?? null,
                        'archive_url' => $s['archive_url'] ?? null,
                        'categorie' => $s['categorie'],
                        'date_publication' => $s['date_publication'] ?? null,
                        'date_consultation' => $s['date_consultation'] ?? null,
                    ])->id;
                    $stats['sources']++;
                }

                $constats = [];
                foreach (array_values($f['constats']) as $ordre => $c) {
                    $constat = $fiche->constats()->create([
                        'section' => $c['section'],
                        'groupe' => $c['groupe'] ?? null,
                        'ordre' => $ordre,
                        'texte' => $c['texte'],
                        'verification' => $c['verification'],
                        'note_verification' => $c['note_verification'] ?? null,
                    ]);
                    $constat->sources()->sync(array_map(fn ($cle) => $sources[$cle], $c['sources'] ?? []));
                    if (isset($c['ref'])) {
                        $constats[$c['ref']] = $constat->id;
                    }
                    $stats['constats']++;
                    $stats['a_verifier'] += $c['verification'] === 'verifie' ? 0 : 1;
                }

                foreach (array_values($f['graphiques'] ?? []) as $ordre => $g) {
                    $fiche->graphiques()->create([
                        'constat_id' => $constats[$g['constat']] ?? null,
                        'ordre' => $ordre,
                        'type' => $g['type'],
                        'titre' => $g['titre'],
                        'sous_titre' => $g['sous_titre'] ?? null,
                        'indicateurs' => array_values($g['indicateurs']),
                        'options' => $g['options'] ?? null,
                        'note' => $g['note'] ?? null,
                    ]);
                    $stats['graphiques']++;
                }

                $stats['fiches']++;
            }
        });

        return $stats;
    }

    /** @return list<array> les fiches, quelle que soit la clé du contrat */
    private static function fiches(array $data): array
    {
        $fiches = $data['contrat'] === self::CONTRAT ? ($data['reperes'] ?? null) : ($data['affirmations'] ?? null);

        return is_array($fiches) ? array_values($fiches) : [];
    }

    private function validerFiche(array $f, string $chemin, string $election, array $themes, array $catalogue, bool $remplacer, array &$slugs, bool $v2): void
    {
        $slug = $f['slug'] ?? null;
        if (! is_string($slug) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 120) {
            $this->erreur("{$chemin}.slug", 'absent ou mal formé (minuscules, chiffres et tirets)');
        } else {
            $chemin = "{$chemin} ({$slug})";
            if (isset($slugs[$slug])) {
                $this->erreur($chemin, 'slug en double dans le fichier');
            }
            $slugs[$slug] = true;

            $existante = Affirmation::withTrashed()->where('election', $election)->where('slug', $slug)->first();
            if ($existante?->affiche_publiquement && ! $existante->trashed()) {
                $this->erreur($chemin, 'fiche publiée : elle se modifie dans le back-office, pas par import');
            } elseif ($existante && ! $remplacer) {
                $this->erreur($chemin, 'fiche déjà en base — relancer avec « remplacer » pour l\'écraser (les modifications faites dans le back-office seront perdues)');
            }
        }

        if ($v2) {
            $this->texte($f, 'question', $chemin, 300);
            if (is_string($f['question'] ?? null) && ! str_ends_with(trim($f['question']), '?')) {
                $this->erreur("{$chemin}.question", 'une question se termine par « ? »');
            }
        } else {
            $this->texte($f, 'enonce', $chemin, 300);
        }
        if (! isset($themes[$f['theme'] ?? ''])) {
            $this->erreur("{$chemin}.theme", 'thème inconnu « '.($f['theme'] ?? '').' »');
        }
        foreach ((array) ($f['themes_secondaires'] ?? []) as $s) {
            if (! isset($themes[$s])) {
                $this->erreur("{$chemin}.themes_secondaires", "thème inconnu « {$s} »");
            }
        }
        $this->date($f['derniere_verification'] ?? null, "{$chemin}.derniere_verification");

        if ($v2 && array_key_exists('verdicts', $f)) {
            $this->erreur("{$chemin}.verdicts", 'un repère ne porte pas de verdict');
        }

        // Sources : clés uniques, catégorie connue, URL valide ou null, hors domaines exclus.
        $cles = [];
        foreach (array_values((array) ($f['sources'] ?? [])) as $k => $s) {
            $cs = "{$chemin}.sources[{$k}]";
            $cle = $s['cle'] ?? null;
            if (! is_string($cle) || $cle === '' || strlen($cle) > 80) {
                $this->erreur($cs, 'clé absente');

                continue;
            }
            $cs .= " ({$cle})";
            if (isset($cles[$cle])) {
                $this->erreur($cs, 'clé en double');
            }
            $cles[$cle] = false;
            $this->texte($s, 'producteur', $cs, 200);
            $this->texte($s, 'titre', $cs, 500);
            if (! isset(AffirmationSource::CATEGORIES[$s['categorie'] ?? ''])) {
                $this->erreur("{$cs}.categorie", 'catégorie inconnue « '.($s['categorie'] ?? '').' »');
            }
            foreach (['url', 'archive_url'] as $champ) {
                $u = $s[$champ] ?? null;
                if ($u !== null && ! UrlSource::estValide($u)) {
                    $this->erreur("{$cs}.{$champ}", "ni une URL http(s) ni null : « {$u} »");
                }
            }
            if (ReglesAffirmation::domaineExclu($s['url'] ?? null)) {
                $this->erreur("{$cs}.url", 'domaine exclu par le cadre éditorial');
            }
            $this->date($s['date_publication'] ?? null, "{$cs}.date_publication");
            $this->date($s['date_consultation'] ?? null, "{$cs}.date_consultation");
        }

        // Constats : section et état connus, sources citées déclarées.
        $refs = [];
        $constats = $f['constats'] ?? null;
        if (! is_array($constats) || $constats === []) {
            $this->erreur("{$chemin}.constats", 'aucun constat');
            $constats = [];
        }
        foreach (array_values($constats) as $k => $c) {
            $cc = "{$chemin}.constats[{$k}]";
            if (! isset(AffirmationConstat::SECTIONS[$c['section'] ?? ''])) {
                $this->erreur("{$cc}.section", 'section inconnue « '.($c['section'] ?? '').' »');
            }
            if (! isset(AffirmationConstat::VERIFICATIONS[$c['verification'] ?? ''])) {
                $this->erreur("{$cc}.verification", 'attendu « verifie » ou « a_verifier »');
            }
            if (! is_string($c['texte'] ?? null) || trim($c['texte']) === '') {
                $this->erreur("{$cc}.texte", 'vide');
            }
            foreach ((array) ($c['sources'] ?? []) as $cle) {
                if (! array_key_exists($cle, $cles)) {
                    $this->erreur("{$cc}.sources", "clé de source inconnue « {$cle} »");
                } else {
                    $cles[$cle] = true;
                }
            }
            if (isset($c['ref'])) {
                if (isset($refs[$c['ref']])) {
                    $this->erreur("{$cc}.ref", 'référence en double « '.$c['ref'].' »');
                }
                $refs[$c['ref']] = true;
            }
        }
        foreach ($cles as $cle => $citee) {
            if (! $citee) {
                $this->erreur("{$chemin}.sources ({$cle})", 'source citée par aucun constat');
            }
        }

        // Graphiques : rattachés à un constat, type autorisé, indicateurs du catalogue.
        foreach (array_values((array) ($f['graphiques'] ?? [])) as $k => $g) {
            $cg = "{$chemin}.graphiques[{$k}]";
            if (! isset($refs[$g['constat'] ?? ''])) {
                $this->erreur("{$cg}.constat", 'doit désigner la « ref » d\'un constat de la fiche');
            }
            $type = $g['type'] ?? '';
            if (! isset(AffirmationGraphique::TYPES[$type])) {
                $this->erreur("{$cg}.type", "type non autorisé « {$type} »");
            } else {
                [$min, $max] = AffirmationGraphique::NB_INDICATEURS[$type];
                $n = count((array) ($g['indicateurs'] ?? []));
                if ($n < $min || $n > $max) {
                    $this->erreur("{$cg}.indicateurs", "{$n} indicateur(s) pour le type {$type}");
                }
            }
            foreach ((array) ($g['indicateurs'] ?? []) as $code) {
                if (! in_array($code, $catalogue, true)) {
                    $this->erreur("{$cg}.indicateurs", "indicateur absent du catalogue Eurostat « {$code} »");
                }
            }
            $this->texte($g, 'titre', $cg, 300);
        }
    }

    private function texte(array $objet, string $champ, string $chemin, int $max): void
    {
        $v = $objet[$champ] ?? null;
        if (! is_string($v) || trim($v) === '') {
            $this->erreur("{$chemin}.{$champ}", 'vide');
        } elseif (mb_strlen($v) > $max) {
            $this->erreur("{$chemin}.{$champ}", "plus de {$max} caractères");
        }
    }

    private function date(mixed $v, string $chemin): void
    {
        if ($v !== null && (! is_string($v) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) || ! strtotime($v))) {
            $this->erreur($chemin, 'date attendue au format AAAA-MM-JJ');
        }
    }

    private function erreur(string $chemin, string $message): void
    {
        $this->erreurs[] = "{$chemin} : {$message}";
    }
}
