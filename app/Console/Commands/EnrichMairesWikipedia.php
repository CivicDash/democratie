<?php

namespace App\Console\Commands;

use App\Models\Maire;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichMairesWikipedia extends Command
{
    private const USER_AGENT = 'CivicDashBot/1.0 (https://civicdash.fr; contact@civicdash.fr) PHP/Laravel';

    protected $signature = 'enrich:maires-wikipedia 
                            {--limit=100 : Nombre de maires à traiter}
                            {--min-population=10000 : Population minimum de la commune}
                            {--force : Forcer la mise à jour même si déjà synchronisé}
                            {--delay=1000 : Délai entre requêtes en ms}';

    protected $description = 'Enrichit les fiches maires avec les données Wikipedia/Wikidata';

    private int $enriched = 0;

    private int $notFound = 0;

    private int $errors = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('🏛️ Enrichissement des maires depuis Wikipedia/Wikidata');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $minPop = (int) $this->option('min-population');
        $force = $this->option('force');
        $delay = (int) $this->option('delay');

        // Récupérer les maires à enrichir
        $query = Maire::where('en_exercice', true)
            ->where(function ($q) {
                $q->whereNotNull('population_commune')
                    ->orWhereRaw('1=1'); // Fallback si population non renseignée
            })
            ->orderByDesc('population_commune');

        if ($minPop > 0) {
            $query->where('population_commune', '>=', $minPop);
        }

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('wikipedia_last_sync')
                    ->orWhere('wikipedia_last_sync', '<', now()->subDays(30));
            });
        }

        $maires = $query->limit($limit)->get();
        $total = $maires->count();

        if ($total === 0) {
            $this->warn('Aucun maire à enrichir avec ces critères.');

            return Command::SUCCESS;
        }

        $this->info("📊 {$total} maires à traiter (communes ≥ {$minPop} hab.)");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($maires as $maire) {
            $this->processMaire($maire);
            $bar->advance();

            // Rate limiting
            usleep($delay * 1000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return Command::SUCCESS;
    }

    private function processMaire(Maire $maire): void
    {
        try {
            // 1. Chercher sur Wikidata
            $wikidataResult = $this->searchWikidata($maire);

            if (! $wikidataResult) {
                // 2. Fallback: chercher sur Wikipedia FR directement
                $wikipediaResult = $this->searchWikipedia($maire);

                if ($wikipediaResult) {
                    $maire->update([
                        'wikipedia_url' => $wikipediaResult['url'],
                        'wikipedia_extract' => $wikipediaResult['extract'],
                        'photo_wikipedia_url' => $wikipediaResult['photo'] ?? $maire->photo_wikipedia_url,
                        'wikipedia_last_sync' => now(),
                    ]);
                    $this->enriched++;
                } else {
                    $maire->update(['wikipedia_last_sync' => now()]);
                    $this->notFound++;
                }

                return;
            }

            // Traiter le résultat Wikidata
            $updateData = [
                'wikidata_id' => $wikidataResult['id'],
                'wikipedia_last_sync' => now(),
            ];

            if (! empty($wikidataResult['wikipedia_url'])) {
                $updateData['wikipedia_url'] = $wikidataResult['wikipedia_url'];

                // Récupérer l'extrait Wikipedia
                $extract = $this->getWikipediaExtract($wikidataResult['wikipedia_url']);
                if ($extract) {
                    $updateData['wikipedia_extract'] = $extract;
                }
            }

            if (! empty($wikidataResult['photo']) && ! $maire->photo_url) {
                $updateData['photo_wikipedia_url'] = $wikidataResult['photo'];
            }

            if (! empty($wikidataResult['lieu_naissance'])) {
                $updateData['lieu_naissance'] = $wikidataResult['lieu_naissance'];
            }

            if (! empty($wikidataResult['formation'])) {
                $updateData['formation'] = implode(', ', array_slice($wikidataResult['formation'], 0, 3));
            }

            if (! empty($wikidataResult['mandats'])) {
                $updateData['mandats_precedents'] = $wikidataResult['mandats'];
            }

            $maire->update($updateData);
            $this->enriched++;

        } catch (\Exception $e) {
            Log::warning("Erreur enrichissement maire {$maire->id}: ".$e->getMessage());
            $this->errors++;
        }
    }

    /**
     * Recherche sur Wikidata avec la requête SPARQL
     */
    private function searchWikidata(Maire $maire): ?array
    {
        $nom = $maire->prenom.' '.$maire->nom;
        $commune = $maire->nom_commune;

        // Recherche simple via l'API de recherche Wikidata
        $searchUrl = 'https://www.wikidata.org/w/api.php?'.http_build_query([
            'action' => 'wbsearchentities',
            'search' => $nom,
            'language' => 'fr',
            'format' => 'json',
            'type' => 'item',
            'limit' => 5,
        ]);

        $response = $this->httpClient()->get($searchUrl);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $results = $data['search'] ?? [];

        foreach ($results as $result) {
            $entityId = $result['id'];

            // Vérifier si c'est bien un politique français
            $entityData = $this->getWikidataEntity($entityId);

            if ($entityData && $this->isMatchingMaire($entityData, $maire)) {
                return $this->extractWikidataInfo($entityData);
            }
        }

        return null;
    }

    /**
     * Récupère les détails d'une entité Wikidata
     */
    private function getWikidataEntity(string $entityId): ?array
    {
        $url = 'https://www.wikidata.org/w/api.php?'.http_build_query([
            'action' => 'wbgetentities',
            'ids' => $entityId,
            'languages' => 'fr',
            'props' => 'claims|sitelinks|labels|descriptions',
            'format' => 'json',
        ]);

        $response = $this->httpClient()->get($url);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return $data['entities'][$entityId] ?? null;
    }

    /**
     * Vérifie si l'entité Wikidata correspond au maire
     */
    private function isMatchingMaire(array $entity, Maire $maire): bool
    {
        $claims = $entity['claims'] ?? [];

        // P31 = instance of (doit être Q5 = humain)
        $instanceOf = $claims['P31'][0]['mainsnak']['datavalue']['value']['id'] ?? null;
        if ($instanceOf !== 'Q5') {
            return false;
        }

        // P27 = nationalité (doit être Q142 = France)
        $nationality = $claims['P27'][0]['mainsnak']['datavalue']['value']['id'] ?? null;
        if ($nationality && $nationality !== 'Q142') {
            return false;
        }

        // P39 = fonction (chercher si maire ou fonction politique)
        $positions = $claims['P39'] ?? [];
        $isMaireOrPolitician = false;

        foreach ($positions as $position) {
            $posId = $position['mainsnak']['datavalue']['value']['id'] ?? null;
            // Q30185 = maire
            // Q1764225 = homme/femme politique français(e)
            if (in_array($posId, ['Q30185', 'Q1764225', 'Q82955'])) {
                $isMaireOrPolitician = true;
                break;
            }
        }

        // Vérifier date de naissance si disponible
        if ($maire->date_naissance && isset($claims['P569'][0])) {
            $wikiBirthDate = $claims['P569'][0]['mainsnak']['datavalue']['value']['time'] ?? null;
            if ($wikiBirthDate) {
                $wikiYear = (int) substr($wikiBirthDate, 1, 4);
                $maireYear = $maire->date_naissance->year;
                if (abs($wikiYear - $maireYear) > 1) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Extrait les informations utiles de Wikidata
     */
    private function extractWikidataInfo(array $entity): array
    {
        $claims = $entity['claims'] ?? [];
        $result = [
            'id' => $entity['id'],
            'wikipedia_url' => null,
            'photo' => null,
            'lieu_naissance' => null,
            'formation' => [],
            'mandats' => [],
        ];

        // Lien Wikipedia FR
        $frwiki = $entity['sitelinks']['frwiki']['title'] ?? null;
        if ($frwiki) {
            $result['wikipedia_url'] = 'https://fr.wikipedia.org/wiki/'.urlencode(str_replace(' ', '_', $frwiki));
        }

        // Photo (P18)
        if (isset($claims['P18'][0])) {
            $imageName = $claims['P18'][0]['mainsnak']['datavalue']['value'] ?? null;
            if ($imageName) {
                $result['photo'] = $this->getCommonsImageUrl($imageName);
            }
        }

        // Lieu de naissance (P19)
        if (isset($claims['P19'][0])) {
            $placeId = $claims['P19'][0]['mainsnak']['datavalue']['value']['id'] ?? null;
            if ($placeId) {
                $result['lieu_naissance'] = $this->getWikidataLabel($placeId);
            }
        }

        // Formation (P69)
        foreach (($claims['P69'] ?? []) as $edu) {
            $eduId = $edu['mainsnak']['datavalue']['value']['id'] ?? null;
            if ($eduId) {
                $label = $this->getWikidataLabel($eduId);
                if ($label) {
                    $result['formation'][] = $label;
                }
            }
        }

        // Mandats (P39)
        foreach (($claims['P39'] ?? []) as $pos) {
            $posId = $pos['mainsnak']['datavalue']['value']['id'] ?? null;
            if ($posId) {
                $label = $this->getWikidataLabel($posId);
                if ($label) {
                    $qualifiers = $pos['qualifiers'] ?? [];
                    $mandat = ['fonction' => $label];

                    // Date de début
                    if (isset($qualifiers['P580'][0])) {
                        $startTime = $qualifiers['P580'][0]['datavalue']['value']['time'] ?? null;
                        if ($startTime) {
                            $mandat['debut'] = substr($startTime, 1, 10);
                        }
                    }

                    // Date de fin
                    if (isset($qualifiers['P582'][0])) {
                        $endTime = $qualifiers['P582'][0]['datavalue']['value']['time'] ?? null;
                        if ($endTime) {
                            $mandat['fin'] = substr($endTime, 1, 10);
                        }
                    }

                    $result['mandats'][] = $mandat;
                }
            }
        }

        return $result;
    }

    /**
     * Récupère le label français d'une entité Wikidata
     */
    private function getWikidataLabel(string $entityId): ?string
    {
        static $cache = [];

        if (isset($cache[$entityId])) {
            return $cache[$entityId];
        }

        $url = 'https://www.wikidata.org/w/api.php?'.http_build_query([
            'action' => 'wbgetentities',
            'ids' => $entityId,
            'languages' => 'fr',
            'props' => 'labels',
            'format' => 'json',
        ]);

        $response = $this->httpClient(5)->get($url);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $label = $data['entities'][$entityId]['labels']['fr']['value'] ?? null;

        $cache[$entityId] = $label;

        return $label;
    }

    /**
     * Construit l'URL d'une image Wikimedia Commons
     */
    private function getCommonsImageUrl(string $filename): string
    {
        $filename = str_replace(' ', '_', $filename);
        $hash = md5($filename);

        return sprintf(
            'https://upload.wikimedia.org/wikipedia/commons/thumb/%s/%s/%s/200px-%s',
            $hash[0],
            substr($hash, 0, 2),
            $filename,
            $filename
        );
    }

    /**
     * Recherche directe sur Wikipedia FR (fallback)
     */
    private function searchWikipedia(Maire $maire): ?array
    {
        $searchTerms = [
            $maire->prenom.' '.$maire->nom,
            $maire->prenom.' '.$maire->nom.' (homme politique)',
            $maire->prenom.' '.$maire->nom.' (femme politique)',
        ];

        foreach ($searchTerms as $term) {
            $url = 'https://fr.wikipedia.org/w/api.php?'.http_build_query([
                'action' => 'query',
                'list' => 'search',
                'srsearch' => $term.' maire '.$maire->nom_commune,
                'format' => 'json',
                'srlimit' => 3,
            ]);

            $response = $this->httpClient()->get($url);

            if (! $response->successful()) {
                continue;
            }

            $data = $response->json();
            $results = $data['query']['search'] ?? [];

            foreach ($results as $result) {
                $title = $result['title'];
                $snippet = strip_tags($result['snippet']);

                // Vérification basique : le snippet mentionne "maire"
                if (stripos($snippet, 'maire') !== false || stripos($snippet, $maire->nom_commune) !== false) {
                    $pageUrl = 'https://fr.wikipedia.org/wiki/'.urlencode(str_replace(' ', '_', $title));
                    $extract = $this->getWikipediaExtract($pageUrl);
                    $photo = $this->getWikipediaPageImage($title);

                    return [
                        'url' => $pageUrl,
                        'extract' => $extract,
                        'photo' => $photo,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Récupère l'extrait d'une page Wikipedia
     */
    private function getWikipediaExtract(string $url): ?string
    {
        $title = basename(urldecode(parse_url($url, PHP_URL_PATH)));

        $apiUrl = 'https://fr.wikipedia.org/w/api.php?'.http_build_query([
            'action' => 'query',
            'titles' => $title,
            'prop' => 'extracts',
            'exintro' => true,
            'explaintext' => true,
            'format' => 'json',
        ]);

        $response = Http::timeout(10)->get($apiUrl);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $pages = $data['query']['pages'] ?? [];

        foreach ($pages as $page) {
            $extract = $page['extract'] ?? null;
            if ($extract) {
                // Limiter à ~500 caractères
                return mb_substr($extract, 0, 500);
            }
        }

        return null;
    }

    /**
     * Récupère l'image principale d'une page Wikipedia
     */
    private function getWikipediaPageImage(string $title): ?string
    {
        $apiUrl = 'https://fr.wikipedia.org/w/api.php?'.http_build_query([
            'action' => 'query',
            'titles' => $title,
            'prop' => 'pageimages',
            'pithumbsize' => 200,
            'format' => 'json',
        ]);

        $response = Http::timeout(10)->get($apiUrl);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $pages = $data['query']['pages'] ?? [];

        foreach ($pages as $page) {
            return $page['thumbnail']['source'] ?? null;
        }

        return null;
    }

    private function displaySummary(): void
    {
        $this->info('✅ Enrichissement terminé !');
        $this->newLine();

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✅ Enrichis', $this->enriched],
                ['❌ Non trouvés', $this->notFound],
                ['⚠️ Erreurs', $this->errors],
                ['⏭️ Ignorés', $this->skipped],
            ]
        );
    }

    /**
     * Client HTTP avec User-Agent approprié pour Wikipedia
     */
    private function httpClient(int $timeout = 10): PendingRequest
    {
        return Http::timeout($timeout)
            ->withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json',
            ]);
    }
}
