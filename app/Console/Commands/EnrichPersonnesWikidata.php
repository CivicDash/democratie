<?php

namespace App\Console\Commands;

use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnrichPersonnesWikidata extends Command
{
    protected $signature = 'enrich:personnes-wikidata
        {--limit=500 : Nombre max de personnes à traiter}
        {--force : Ré-enrichir même si wikidata_id déjà présent}';

    protected $description = 'Enrichit les PersonnePolitique avec leur Wikidata ID à partir de leur URL Wikipedia';

    private const BATCH_SIZE = 50;

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = PersonnePolitique::whereNotNull('wikipedia_url')
            ->where('wikipedia_url', '!=', '');

        if (! $force) {
            $query->whereNull('wikidata_id');
        }

        $personnes = $query->limit($limit)->get();

        $this->info("Enrichissement Wikidata de {$personnes->count()} personne(s)...");

        $enriched = 0;
        $errors = 0;

        foreach ($personnes->chunk(self::BATCH_SIZE) as $chunk) {
            $titles = $chunk->mapWithKeys(function ($p) {
                $title = $this->extractWikipediaTitle($p->wikipedia_url);

                return $title ? [$p->id => $title] : [];
            })->filter();

            if ($titles->isEmpty()) {
                continue;
            }

            $titleString = $titles->values()->implode('|');

            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'CivicDash/1.5 (contact@civicdash.fr)',
                    ])
                    ->get('https://fr.wikipedia.org/w/api.php', [
                        'action' => 'query',
                        'titles' => $titleString,
                        'prop' => 'pageprops',
                        'ppprop' => 'wikibase_item',
                        'format' => 'json',
                        'redirects' => 1,
                    ]);

                if (! $response->successful()) {
                    $this->error("Erreur API Wikipedia : HTTP {$response->status()}");
                    $errors++;

                    continue;
                }

                $pages = $response->json('query.pages') ?? [];
                $titleToQid = [];

                foreach ($pages as $page) {
                    $pageTitle = $page['title'] ?? null;
                    $qid = $page['pageprops']['wikibase_item'] ?? null;
                    if ($pageTitle && $qid) {
                        $titleToQid[$this->normalizeTitle($pageTitle)] = $qid;
                    }
                }

                foreach ($titles as $personneId => $title) {
                    $normalizedTitle = $this->normalizeTitle(urldecode($title));
                    $qid = $titleToQid[$normalizedTitle] ?? null;

                    if ($qid) {
                        PersonnePolitique::where('id', $personneId)->update([
                            'wikidata_id' => $qid,
                            'wikipedia_last_sync' => now(),
                        ]);
                        $enriched++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Erreur batch : {$e->getMessage()}");
                $errors++;
            }

            usleep(200_000);
        }

        $this->newLine();
        $this->info("Terminé : {$enriched} enrichie(s), {$errors} erreur(s)");

        return self::SUCCESS;
    }

    private function extractWikipediaTitle(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! $path) {
            return null;
        }

        $parts = explode('/wiki/', $path);

        return $parts[1] ?? null;
    }

    private function normalizeTitle(string $title): string
    {
        return str_replace('_', ' ', trim($title));
    }
}
