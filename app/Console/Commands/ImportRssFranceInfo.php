<?php

namespace App\Console\Commands;

use App\Models\ImportLog;
use App\Models\PoliticalNews;
use App\Services\RssFeedService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportRssFranceInfo extends Command
{
    protected $signature = 'import:rss-franceinfo
                            {--feed= : Feed spécifique à importer}
                            {--dry-run : Afficher sans écrire}';

    protected $description = 'Importe les flux RSS France Info (partis, élections, AN, politique, plans sociaux)';

    private const FEEDS = [
        'partis' => [
            'front-national' => 'https://www.franceinfo.fr/politique/front-national.rss',
            'nouveau-front-populaire' => 'https://www.franceinfo.fr/politique/nouveau-front-populaire.rss',
            'les-republicains' => 'https://www.franceinfo.fr/politique/les-republicains.rss',
            'parti-communiste-francais' => 'https://www.franceinfo.fr/politique/parti-communiste-francais.rss',
            'eelv' => 'https://www.franceinfo.fr/politique/eelv.rss',
            'ps' => 'https://www.franceinfo.fr/politique/ps.rss',
        ],
        'institutions' => [
            'elections' => 'https://www.franceinfo.fr/elections.rss',
            'assemblee-nationale' => 'https://www.franceinfo.fr/politique/parlement-francais/assemblee-nationale.rss',
        ],
        'thematique' => [
            'politique' => 'https://www.franceinfo.fr/politique.rss',
            'plans-sociaux' => 'https://www.franceinfo.fr/economie/emploi/plans-sociaux.rss',
            'economie' => 'https://www.franceinfo.fr/economie.rss',
        ],
    ];

    public function handle(RssFeedService $rssService): int
    {
        $importLog = ImportLog::start('import:rss-franceinfo', 'system');
        $this->info('📰 Import des flux RSS France Info');

        $specificFeed = $this->option('feed');
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach (self::FEEDS as $category => $feeds) {
            foreach ($feeds as $feedName => $feedUrl) {
                if ($specificFeed && $specificFeed !== $feedName) {
                    continue;
                }

                $this->line("   📡 {$feedName} ({$category})...");

                $items = $rssService->fetch($feedUrl);
                if (empty($items)) {
                    $this->warn("   ⚠️  Aucun article pour {$feedName}");
                    $errors++;

                    continue;
                }

                foreach ($items as $item) {
                    if (empty($item['guid']) || empty($item['title'])) {
                        continue;
                    }

                    $exists = PoliticalNews::where('guid', $item['guid'])->exists();
                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("      [DRY] {$item['title']}");
                        $created++;

                        continue;
                    }

                    try {
                        PoliticalNews::create([
                            'title' => mb_substr($item['title'], 0, 255),
                            'description' => $item['description'] ? mb_substr($item['description'], 0, 2000) : null,
                            'url' => $item['link'],
                            'image_url' => $item['image_url'],
                            'source_feed' => $feedName,
                            'category' => $category,
                            'published_at' => ! empty($item['pubDate']) ? Carbon::parse($item['pubDate']) : now(),
                            'guid' => mb_substr($item['guid'], 0, 512),
                        ]);
                        $created++;
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->warn("   ❌ Erreur : {$e->getMessage()}");
                    }
                }

                $this->line("      ✅ ".count($items).' articles traités');
            }
        }

        $importLog->finish($created, 0, $skipped, $errors);
        $this->newLine();
        $this->info("✅ Terminé — {$created} créés, {$skipped} ignorés (déjà existants), {$errors} erreurs");

        return Command::SUCCESS;
    }
}
