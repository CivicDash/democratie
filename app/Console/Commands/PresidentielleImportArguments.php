<?php

namespace App\Console\Commands;

use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\Controverse;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import de l'argumentaire (plan §4, contrats v1.1 « arguments » et v1.2
 * « arguments_controverse »). Les arguments sont des FAITS SOURCÉS AUTONOMES ; le sens
 * (pour|contre) et la note contextuelle vivent dans les liaisons argument↔mesure.
 *
 * TOUT entre en statut `detecte` — jamais publié. Les mesures cibles sont référencées par
 * candidat_slug + description libre : la commande fait un AUTO-MATCH par similarité contre
 * les mesures existantes du candidat (score conservé) ; en dessous du seuil, la liaison est
 * laissée « à résoudre » (mesure_id null, cible proposée conservée) pour arbitrage au BO.
 */
class PresidentielleImportArguments extends Command
{
    protected $signature = 'presidentielle:import-arguments
        {fichier : chemin du fichier JSON d\'arguments}
        {--election=2027 : élection cible pour la résolution des candidats}
        {--seuil=0.34 : seuil de similarité (0-1) pour apparier automatiquement une mesure}
        {--dry-run : analyse sans écrire en base}';

    protected $description = 'Importe un argumentaire (contrats §4 v1.1/v1.2) en file de modération (statut detecte).';

    private float $seuil = 0.34;

    private string $election = '2027';

    /** @var array<string,int|null> cache slug candidat → id */
    private array $candidatCache = [];

    public function handle(): int
    {
        $fichier = $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($fichier), true);
        if (! is_array($data)) {
            $this->error('JSON invalide.');

            return self::FAILURE;
        }

        $this->seuil = (float) $this->option('seuil');
        $this->election = (string) $this->option('election');
        $dryRun = (bool) $this->option('dry-run');

        $type = $data['type_import'] ?? (isset($data['controverse']) ? 'arguments_controverse' : 'arguments');
        if (! in_array($type, ['arguments', 'arguments_controverse'], true)) {
            $this->error("type_import non supporté : {$type}");

            return self::FAILURE;
        }

        DB::beginTransaction();
        try {
            $stats = $type === 'arguments_controverse'
                ? $this->importControverse($data)
                : $this->importSimple($data);

            if ($dryRun) {
                DB::rollBack();
                $this->info('DRY-RUN : aucune écriture.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec import : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Arguments insérés (detecte) : {$stats['arguments']}");
        $this->line("  Liaisons créées : {$stats['liens']} · appariées auto : {$stats['liens_apparies']} · à résoudre : {$stats['liens_a_resoudre']}");
        $this->line("  Sources : {$stats['sources']} · Controverses : {$stats['controverses']}");

        return self::SUCCESS;
    }

    /** Format v1.2 : une controverse, des arguments avec liaisons par mesure (sens porté par le lien). */
    private function importControverse(array $data): array
    {
        $stats = ['arguments' => 0, 'liens' => 0, 'liens_apparies' => 0, 'liens_a_resoudre' => 0, 'sources' => 0, 'controverses' => 0];

        $c = $data['controverse'] ?? [];
        $themeId = $this->resolveTheme($c['theme_slug'] ?? null);
        $slug = $c['slug'] ?? Str::slug($c['titre'] ?? 'controverse').'-'.Str::lower(Str::random(4));

        $controverse = Controverse::firstOrNew(['slug' => $slug]);
        $controverse->fill([
            'titre' => $c['titre'] ?? $slug,
            'theme_id' => $themeId,
            'note_methodologique' => $data['note_methodologique_a_afficher'] ?? $controverse->note_methodologique,
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ])->save();
        $stats['controverses'] = 1;

        // ref (M1, M2…) → cible proposée { candidat_slug, description }
        $ciblesParRef = [];
        foreach (($c['mesures_liees'] ?? []) as $m) {
            if (isset($m['ref'])) {
                $ciblesParRef[$m['ref']] = [
                    'candidat_slug' => $m['candidat_slug'] ?? null,
                    'description' => $m['mesure'] ?? '',
                ];
            }
        }

        foreach (($data['arguments'] ?? []) as $a) {
            $argument = $this->creerArgument($a, $controverse->id);
            $stats['arguments']++;
            $stats['sources'] += $this->importSources($argument, $a['sources'] ?? []);

            foreach (($a['liens'] ?? []) as $lien) {
                $cible = $ciblesParRef[$lien['mesure_ref'] ?? ''] ?? ['candidat_slug' => null, 'description' => ''];
                $this->creerLien($argument->id, $lien['sens'] ?? 'pour', $lien['note'] ?? null, $cible, $stats);
            }
        }

        return $stats;
    }

    /** Format v1.1 : arguments_pour / arguments_contre, cibles communes (mesures_cibles). */
    private function importSimple(array $data): array
    {
        $stats = ['arguments' => 0, 'liens' => 0, 'liens_apparies' => 0, 'liens_a_resoudre' => 0, 'sources' => 0, 'controverses' => 0];

        $cibles = array_map(fn ($m) => [
            'candidat_slug' => $m['candidat_slug'] ?? null,
            'description' => $m['reference'] ?? '',
            'note' => $m['note'] ?? null,
        ], $data['mesures_cibles'] ?? []);

        // Une note méthodologique sans controverse serait perdue : on la porte dans une
        // controverse créée pour l'occasion et on y rattache les arguments importés.
        $controverseId = null;
        if (! empty($data['note_methodologique_a_afficher'])) {
            $titre = Str::limit($cibles[0]['description'] ?? 'Controverse importée', 120, '');
            $controverse = Controverse::create([
                'slug' => Str::slug($titre).'-'.Str::lower(Str::random(4)),
                'titre' => $titre,
                'note_methodologique' => $data['note_methodologique_a_afficher'],
                'statut_validation' => 'detecte',
                'affiche_publiquement' => false,
            ]);
            $controverseId = $controverse->id;
            $stats['controverses'] = 1;
        }

        foreach (['pour' => 'arguments_pour', 'contre' => 'arguments_contre'] as $sens => $cle) {
            foreach (($data[$cle] ?? []) as $a) {
                $argument = $this->creerArgument($a, $controverseId);
                $stats['arguments']++;
                $stats['sources'] += $this->importSources($argument, $a['sources'] ?? []);

                foreach ($cibles as $cible) {
                    $this->creerLien($argument->id, $sens, $cible['note'] ?? null, $cible, $stats);
                }
            }
        }

        return $stats;
    }

    private function creerArgument(array $a, ?int $controverseId): Argument
    {
        $type = $a['type'] ?? 'etude';
        if (! in_array($type, Argument::TYPES, true)) {
            $type = 'etude';
        }

        return Argument::create([
            'controverse_id' => $controverseId,
            'titre' => Str::limit((string) ($a['titre'] ?? 'Sans titre'), 250, ''),
            'contenu' => Str::limit((string) ($a['contenu'] ?? ''), 495, ''),
            'type_argument' => $type,
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ]);
    }

    /** @return int nombre de sources créées */
    private function importSources(Argument $argument, array $sources): int
    {
        $n = 0;
        foreach ($sources as $s) {
            [$fiab, $commentaire] = $this->parseFiabilite((string) ($s['fiabilite'] ?? ''));
            ArgumentSource::create([
                'argument_id' => $argument->id,
                'type_source' => $this->inferTypeSource((string) ($s['institution'] ?? ''), $argument->type_argument),
                'titre' => Str::limit((string) ($s['institution'] ?? ''), 495, '') ?: null,
                'url' => $this->cleanUrl($s['url'] ?? null),
                'archive_url' => $this->cleanUrl($s['archive_url'] ?? null),
                'fiabilite' => $fiab,
                'commentaire_verification' => $commentaire ?: null,
            ]);
            $n++;
        }

        return $n;
    }

    private function creerLien(int $argumentId, string $sens, ?string $note, array $cible, array &$stats): void
    {
        $sens = in_array($sens, ArgumentMesureLien::SENS, true) ? $sens : 'pour';
        [$mesureId, $confiance] = $this->autoMatch($cible['candidat_slug'] ?? null, $cible['description'] ?? '');

        ArgumentMesureLien::create([
            'argument_id' => $argumentId,
            'mesure_id' => $mesureId,
            'sens' => $sens,
            'note_contextuelle' => $note,
            'candidat_slug_propose' => $cible['candidat_slug'] ?? null,
            'mesure_proposee' => $cible['description'] ?? null,
            'source_detection' => 'suggestion_auto',
            'detection_confidence' => $confiance,
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ]);

        $stats['liens']++;
        $mesureId ? $stats['liens_apparies']++ : $stats['liens_a_resoudre']++;
    }

    /**
     * Auto-match : meilleure mesure du candidat par similarité de texte avec la description.
     *
     * @return array{0: int|null, 1: float|null} [mesure_id ou null, confiance 0-1 ou null]
     */
    private function autoMatch(?string $candidatSlug, string $description): array
    {
        if (! $candidatSlug || trim($description) === '') {
            return [null, null];
        }
        $candidatId = $this->resolveCandidat($candidatSlug);
        if (! $candidatId) {
            return [null, null];
        }

        $mesures = ProgrammeMesure::where('candidat_id', $candidatId)->get(['id', 'titre', 'resume']);
        if ($mesures->isEmpty()) {
            return [null, 0.0];
        }

        $descNorm = $this->normalize($description);
        $meilleurId = null;
        $meilleurScore = 0.0;
        foreach ($mesures as $m) {
            $cibleNorm = $this->normalize(trim(($m->titre ?? '').' '.($m->resume ?? '')));
            $pct = 0.0;
            similar_text($descNorm, $cibleNorm, $pct);
            $score = $pct / 100;
            if ($score > $meilleurScore) {
                $meilleurScore = $score;
                $meilleurId = $m->id;
            }
        }

        return $meilleurScore >= $this->seuil
            ? [$meilleurId, round($meilleurScore, 3)]
            : [null, round($meilleurScore, 3)];
    }

    private function resolveCandidat(?string $slug): ?int
    {
        if (! $slug) {
            return null;
        }
        if (array_key_exists($slug, $this->candidatCache)) {
            return $this->candidatCache[$slug];
        }
        $personne = PersonnePolitique::where('slug', $slug)->first();
        $id = $personne
            ? CandidatPresidentielle::where('personne_politique_id', $personne->id)
                ->where('election', $this->election)->value('id')
            : null;

        return $this->candidatCache[$slug] = $id;
    }

    private function resolveTheme(?string $slug): ?int
    {
        return $slug ? ProgrammeTheme::where('slug', $slug)->value('id') : null;
    }

    /**
     * Extrait la fiabilité (enum) d'une chaîne libre (« haute — source primaire… »).
     *
     * @return array{0: string, 1: string} [enum, texte original complet]
     */
    private function parseFiabilite(string $txt): array
    {
        $bas = mb_strtolower($txt, 'UTF-8');
        foreach (['haute', 'moyenne', 'basse'] as $niveau) {
            if (str_contains($bas, $niveau)) {
                return [$niveau, trim($txt)];
            }
        }

        return ['moyenne', trim($txt)];
    }

    private function inferTypeSource(string $institution, string $typeArgument): string
    {
        $s = mb_strtolower($institution, 'UTF-8');
        $regles = [
            'insee' => ['insee'],
            'cour_des_comptes' => ['cour des comptes'],
            'conseil_constitutionnel' => ['conseil constitutionnel'],
            'ocde_eurostat' => ['ocde', 'eurostat', 'ipp', 'ofce', 'cor'],
            'presse_nationale' => ['franceinfo', 'public sénat', 'public senat', 'le monde', 'afp', 'libération', 'figaro', 'presse'],
            'fact_checking' => ['fact', 'checknews', 'vrai ou faux'],
        ];
        foreach ($regles as $type => $mots) {
            foreach ($mots as $mot) {
                if (str_contains($s, $mot)) {
                    return $type;
                }
            }
        }

        return match ($typeArgument) {
            'etude', 'comparaison_internationale' => 'etude_academique',
            default => 'rapport_officiel',
        };
    }

    /** URL publique ou null (jamais de placeholder). */
    private function cleanUrl($u): ?string
    {
        if (! is_string($u) || trim($u) === '' || str_contains($u, 'A_COMPLETER')) {
            return null;
        }

        return preg_match('#^https?://#i', $u) ? $u : null;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = strtr($s, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'û' => 'u', 'ù' => 'u', 'ü' => 'u', 'ç' => 'c',
            '’' => "'", '«' => ' ', '»' => ' ',
        ]);
        $s = preg_replace('/[^a-z0-9\' ]+/u', ' ', $s);

        return trim((string) preg_replace('/\s+/u', ' ', $s));
    }
}
