<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\AffaireJudiciaire;
use App\Models\Maire;
use App\Models\PersonnePolitique;
use App\Models\Senateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DetectAffairesWikidata extends Command
{
    protected $signature = 'affaires:detect-wikidata
        {--type=all : depute|senateur|gouvernement|maire|all}
        {--limit=500 : Nombre max d\'entités à traiter}
        {--dry-run : Simuler sans écrire}';

    protected $description = 'Détecte les affaires judiciaires via Wikidata SPARQL (condamnations P1399)';

    private const SPARQL_ENDPOINT = 'https://query.wikidata.org/sparql';

    private int $detected = 0;

    private int $duplicates = 0;

    public function handle(): int
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        $this->info('Détection Wikidata des affaires judiciaires...');
        if ($dryRun) {
            $this->warn('Mode simulation (dry-run)');
        }

        if (in_array($type, ['all', 'depute'])) {
            $this->detectForDeputes($dryRun);
        }
        if (in_array($type, ['all', 'senateur'])) {
            $this->detectForSenateurs($dryRun);
        }
        if (in_array($type, ['all', 'gouvernement'])) {
            $this->detectForGouvernement($dryRun);
        }
        if (in_array($type, ['all', 'maire'])) {
            $this->detectForMaires($dryRun);
        }

        $this->newLine();
        $this->info("Résultat : {$this->detected} affaire(s) détectée(s), {$this->duplicates} doublon(s) ignoré(s)");

        return self::SUCCESS;
    }

    private function detectForDeputes(bool $dryRun): void
    {
        $this->info('Scan des députés via SPARQL générique...');

        $results = $this->queryFrenchParliamentarians('depute');
        $this->info('  Résultats SPARQL : '.count($results));

        $deputes = ActeurAN::deputes()->whereNotNull('wikipedia_url')->get()
            ->keyBy(fn ($d) => $this->normalizeWpUrl($d->wikipedia_url));

        foreach ($results as $result) {
            $wpUrl = $this->normalizeWpUrl($result['article']['value'] ?? '');
            $depute = $deputes->get($wpUrl);

            if (! $depute) {
                continue;
            }

            $this->processResult($result, [
                'acteur_an_uid' => $depute->uid,
                'nom' => $depute->nom,
                'prenom' => $depute->prenom,
                'parti_politique' => $depute->groupe_politique_actuel?->libelle_abrege,
                'fonction_au_moment' => 'Député',
            ], $dryRun);
        }
    }

    private function detectForSenateurs(bool $dryRun): void
    {
        $this->info('Scan des sénateurs via SPARQL générique...');

        $results = $this->queryFrenchParliamentarians('senateur');
        $this->info('  Résultats SPARQL : '.count($results));

        $senateurs = Senateur::actifs()->whereNotNull('wikipedia_url')->get()
            ->keyBy(fn ($s) => $this->normalizeWpUrl($s->wikipedia_url));

        foreach ($results as $result) {
            $wpUrl = $this->normalizeWpUrl($result['article']['value'] ?? '');
            $senateur = $senateurs->get($wpUrl);

            if (! $senateur) {
                continue;
            }

            $this->processResult($result, [
                'senateur_matricule' => $senateur->matricule,
                'nom' => $senateur->nom_usuel,
                'prenom' => $senateur->prenom_usuel,
                'parti_politique' => $senateur->groupe_politique,
                'fonction_au_moment' => 'Sénateur',
            ], $dryRun);
        }
    }

    private function detectForGouvernement(bool $dryRun): void
    {
        $this->info('Scan des membres du gouvernement via SPARQL...');

        $results = $this->queryFrenchParliamentarians('gouvernement');
        $this->info('  Résultats SPARQL : '.count($results));

        $personnesById = PersonnePolitique::whereNotNull('wikidata_id')
            ->get()
            ->keyBy('wikidata_id');

        $personnesByUrl = PersonnePolitique::whereNotNull('wikipedia_url')
            ->get()
            ->keyBy(fn ($p) => $this->normalizeWpUrl($p->wikipedia_url));

        foreach ($results as $result) {
            $wikidataUri = $result['personne']['value'] ?? '';
            $qid = $this->extractQid($wikidataUri);
            $wpUrl = $this->normalizeWpUrl($result['article']['value'] ?? '');

            $personne = ($qid ? $personnesById->get($qid) : null)
                ?? $personnesByUrl->get($wpUrl);

            if (! $personne) {
                continue;
            }

            $this->processResult($result, [
                'personne_politique_id' => $personne->id,
                'nom' => $personne->nom,
                'prenom' => $personne->prenom,
                'parti_politique' => $personne->parti_politique,
                'fonction_au_moment' => 'Membre du gouvernement',
            ], $dryRun);
        }
    }

    private function detectForMaires(bool $dryRun): void
    {
        $this->info('Scan des maires via SPARQL...');

        $results = $this->queryFrenchParliamentarians('maire');
        $this->info('  Résultats SPARQL : '.count($results));

        $maires = Maire::enExercice()
            ->whereNotNull('wikipedia_url')
            ->where('population_commune', '>=', 10000)
            ->get()
            ->keyBy(fn ($m) => $this->normalizeWpUrl($m->wikipedia_url));

        foreach ($results as $result) {
            $wpUrl = $this->normalizeWpUrl($result['article']['value'] ?? '');
            $maire = $maires->get($wpUrl);

            if (! $maire) {
                continue;
            }

            $this->processResult($result, [
                'maire_id' => $maire->id,
                'nom' => $maire->nom,
                'prenom' => $maire->prenom,
                'parti_politique' => $maire->nuance_libelle,
                'fonction_au_moment' => 'Maire de '.$maire->nom_commune,
            ], $dryRun);
        }
    }

    private function normalizeWpUrl(?string $url): string
    {
        if (! $url) {
            return '';
        }
        $url = str_replace('http://', 'https://', $url);

        return rtrim(urldecode($url), '/');
    }

    private function queryFrenchParliamentarians(string $type): array
    {
        $positionFilter = match ($type) {
            'depute' => 'wd:Q3044918',    // député de la XVIIe législature
            'senateur' => 'wd:Q15099714', // sénateur français
            'gouvernement' => 'wd:Q83307', // ministre
            default => 'wd:Q3044918',
        };

        $sparql = <<<'SPARQL'
SELECT ?personne ?personneLabel ?article ?condamnation ?condamnationLabel ?date WHERE {
  ?personne wdt:P31 wd:Q5 .
  ?personne wdt:P27 wd:Q142 .
  ?personne p:P1399 ?statement .
  ?statement ps:P1399 ?condamnation .
  OPTIONAL { ?statement pq:P585 ?date }
  OPTIONAL {
    ?article schema:about ?personne .
    ?article schema:isPartOf <https://fr.wikipedia.org/> .
  }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en" }
}
SPARQL;

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Accept' => 'application/sparql-results+json',
                    'User-Agent' => 'CivicDash/1.5 (contact@civicdash.fr)',
                ])
                ->get(self::SPARQL_ENDPOINT, ['query' => $sparql]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['results']['bindings'] ?? [];
            }

            $this->error("Erreur SPARQL : HTTP {$response->status()}");
        } catch (\Exception $e) {
            $this->error("Erreur SPARQL : {$e->getMessage()}");
        }

        return [];
    }

    private function processResult(array $result, array $eluData, bool $dryRun): void
    {
        $label = $result['condamnationLabel']['value'] ?? $result['personneLabel']['value'] ?? 'Affaire judiciaire';
        $date = isset($result['date']['value']) ? substr($result['date']['value'], 0, 10) : null;
        $infraction = $result['infractionLabel']['value'] ?? null;

        $titre = Str::limit($label, 497);

        $existing = AffaireJudiciaire::where(function ($q) use ($eluData) {
            if (isset($eluData['acteur_an_uid'])) {
                $q->where('acteur_an_uid', $eluData['acteur_an_uid']);
            } elseif (isset($eluData['senateur_matricule'])) {
                $q->where('senateur_matricule', $eluData['senateur_matricule']);
            } elseif (isset($eluData['personne_politique_id'])) {
                $q->where('personne_politique_id', $eluData['personne_politique_id']);
            } elseif (isset($eluData['maire_id'])) {
                $q->where('maire_id', $eluData['maire_id']);
            }
        })->where('titre', $titre)->exists();

        if ($existing) {
            $this->duplicates++;

            return;
        }

        if ($dryRun) {
            $this->line("  [DRY] {$eluData['prenom']} {$eluData['nom']} : {$titre}");
            $this->detected++;

            return;
        }

        $affaire = AffaireJudiciaire::create(array_merge($eluData, [
            'titre' => $titre,
            'type_affaire' => $this->guessTypeAffaire($infraction, $titre),
            'categorie' => $this->guessCategorie($infraction, $titre),
            'statut_judiciaire' => 'en_cours',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'source_detection' => 'wikidata',
            'detecte_at' => now(),
            'detection_confidence' => 0.80,
            'detection_raw_data' => $result,
            'date_condamnation_definitive' => $date,
        ]));

        $affaire->sources()->create([
            'type_source' => 'wikidata',
            'titre' => $label,
            'url' => $result['personne']['value'] ?? null,
            'fiabilite' => 'moyenne',
        ]);

        $affaire->moderationLogs()->create([
            'action' => 'detection',
            'nouveau_statut' => 'detecte',
            'commentaire' => 'Détection Wikidata (P1399), confiance : 0.80',
            'metadata' => ['source' => 'wikidata', 'confidence' => 0.80],
        ]);

        $this->detected++;
        $this->line("  + {$eluData['prenom']} {$eluData['nom']} : {$titre}");
    }

    private function extractWikidataIds($models, string $urlField): array
    {
        $ids = [];
        foreach ($models as $model) {
            $id = $this->getWikidataIdFromUrl($model->{$urlField});
            if ($id) {
                $ids[] = $id;
            }
        }

        return array_unique($ids);
    }

    private function getWikidataIdFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (preg_match('/wikidata\.org\/wiki\/(Q\d+)/', $url, $m)) {
            return $m[1];
        }

        if (str_contains($url, 'wikipedia.org')) {
            $title = basename(parse_url($url, PHP_URL_PATH));
            try {
                $resp = Http::timeout(10)
                    ->get('https://fr.wikipedia.org/w/api.php', [
                        'action' => 'query',
                        'titles' => urldecode($title),
                        'prop' => 'pageprops',
                        'format' => 'json',
                    ]);
                if ($resp->successful()) {
                    $pages = $resp->json()['query']['pages'] ?? [];
                    foreach ($pages as $page) {
                        if (isset($page['pageprops']['wikibase_item'])) {
                            return $page['pageprops']['wikibase_item'];
                        }
                    }
                }
            } catch (\Exception $e) {
                // silently skip
            }
        }

        return null;
    }

    private function extractQid(string $uri): ?string
    {
        if (preg_match('/(Q\d+)$/', $uri, $m)) {
            return $m[1];
        }

        return null;
    }

    private function guessTypeAffaire(?string $infraction, string $titre): string
    {
        $text = mb_strtolower(($infraction ?? '').' '.$titre);
        $mapping = [
            'corruption' => 'corruption',
            'détournement' => 'detournement_fonds',
            'fraude fiscal' => 'fraude_fiscale',
            'abus de biens' => 'abus_biens_sociaux',
            'prise illégale' => 'prise_illegale_interet',
            'favoritisme' => 'favoritisme',
            'trafic d\'influence' => 'trafic_influence',
            'emploi fictif' => 'emploi_fictif',
            'recel' => 'recel',
            'blanchiment' => 'blanchiment',
            'harcèlement' => 'harcelement',
            'violence' => 'violence',
            'diffamation' => 'diffamation',
            'financement' => 'financement_illegal_campagne',
            'conflit d\'intérêt' => 'conflit_interets',
        ];

        foreach ($mapping as $keyword => $type) {
            if (str_contains($text, $keyword)) {
                return $type;
            }
        }

        return 'autre';
    }

    private function guessCategorie(?string $infraction, string $titre): string
    {
        $text = mb_strtolower(($infraction ?? '').' '.$titre);
        if (preg_match('/corrupt|détournement|fraude|abus|favoritisme|trafic|prise illégale|conflit/u', $text)) {
            return 'probite';
        }
        if (preg_match('/financement|campagne|compte/u', $text)) {
            return 'financement';
        }
        if (preg_match('/harcèl|violence|agression|menace/u', $text)) {
            return 'personne';
        }
        if (preg_match('/manquement|déclaration|probité/u', $text)) {
            return 'manquement';
        }

        return 'autre';
    }
}
