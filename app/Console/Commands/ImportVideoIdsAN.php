<?php

namespace App\Console\Commands;

use App\Models\ReunionAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportVideoIdsAN extends Command
{
    protected $signature = 'import:video-ids-an
        {--section=all : Section to scrape (all, seance-publique, questions-au-gouvernement, commissions)}
        {--dry-run : Show what would be imported without writing to DB}
        {--limit=0 : Limit number of videos to process (0 = all)}';

    protected $description = 'Discover AN video IDs from the portal and link them to reunions via data.nvs meeting_id';

    private int $found = 0;

    private int $linked = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $section = $this->option('section');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('=== Import Video IDs from AN Portal ===');

        $sections = $section === 'all'
            ? ['seance-publique', 'questions-au-gouvernement', 'commissions']
            : [$section];

        $allVideos = [];

        foreach ($sections as $sec) {
            $this->info("Scraping {$sec}...");
            $videos = $this->scrapeSection($sec);
            $this->info('  Found '.count($videos).' videos');
            $allVideos = array_merge($allVideos, $videos);
        }

        $allVideos = collect($allVideos)->unique('video_id')->values();
        $this->found = $allVideos->count();
        $this->info("Total unique videos: {$this->found}");

        if ($limit > 0) {
            $allVideos = $allVideos->take($limit);
            $this->info("Processing limited to {$limit} videos");
        }

        $bar = $this->output->createProgressBar($allVideos->count());

        foreach ($allVideos as $video) {
            $this->processVideo($video, $dryRun);
            $bar->advance();
            usleep(300_000); // 300ms between NVS fetches
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Metric', 'Count'], [
            ['Videos found', $this->found],
            ['Linked to reunion', $this->linked],
            ['Already linked / no match', $this->skipped],
            ['Errors (NVS fetch)', $this->errors],
        ]);

        return self::SUCCESS;
    }

    private function scrapeSection(string $section): array
    {
        $url = "https://videos.assemblee-nationale.fr/{$section}";

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                $this->error("  Failed to fetch {$url}: HTTP {$response->status()}");

                return [];
            }

            $html = $response->body();
            preg_match_all(
                '/video\.(\d+_[a-f0-9]+)\.([^"\']+)/',
                $html,
                $matches,
                PREG_SET_ORDER
            );

            $videos = [];
            $seen = [];

            foreach ($matches as $m) {
                $videoId = $m[1];
                $slug = $m[2];

                if (isset($seen[$videoId])) {
                    continue;
                }
                $seen[$videoId] = true;

                $videos[] = [
                    'video_id' => $videoId,
                    'slug' => $slug,
                    'section' => $section,
                    'url' => "https://videos.assemblee-nationale.fr/video.{$videoId}.{$slug}",
                ];
            }

            return $videos;
        } catch (\Exception $e) {
            $this->error("  Error scraping {$url}: {$e->getMessage()}");

            return [];
        }
    }

    private function processVideo(array $video, bool $dryRun): void
    {
        $videoId = $video['video_id'];

        $existing = ReunionAN::where('video_id', $videoId)->first();
        if ($existing) {
            $this->skipped++;

            return;
        }

        $meetingId = $this->fetchMeetingIdFromNvs($videoId);

        if (! $meetingId) {
            $meetingId = $this->guessReunionFromSlug($video);
        }

        if (! $meetingId) {
            $this->skipped++;

            return;
        }

        $reunion = ReunionAN::find($meetingId);
        if (! $reunion) {
            $this->skipped++;

            return;
        }

        if ($reunion->video_id) {
            $this->skipped++;

            return;
        }

        if ($dryRun) {
            $this->line("  [DRY] Would link {$videoId} -> {$meetingId}");
            $this->linked++;

            return;
        }

        $reunion->update([
            'video_id' => $videoId,
            'url_video' => $video['url'],
        ]);

        $this->linked++;
    }

    private function fetchMeetingIdFromNvs(string $videoId): ?string
    {
        $url = "https://videos.assemblee-nationale.fr/Datas/an/{$videoId}/content/data.nvs";

        try {
            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                $this->errors++;

                return null;
            }

            $xml = @simplexml_load_string($response->body());
            if (! $xml) {
                $this->errors++;

                return null;
            }

            foreach ($xml->metadatas->metadata ?? [] as $meta) {
                $name = (string) ($meta['name'] ?? '');
                if ($name === 'meeting_id') {
                    $value = (string) ($meta['value'] ?? '');
                    if ($value && str_starts_with($value, 'RUANR')) {
                        return $value;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->errors++;
            Log::warning("import:video-ids-an NVS fetch error for {$videoId}: {$e->getMessage()}");

            return null;
        }
    }

    private function guessReunionFromSlug(array $video): ?string
    {
        $slug = $video['slug'];

        if (! preg_match('/(\d{1,2})-([a-z]+)-(\d{4})/', $slug, $dateMatch)) {
            return null;
        }

        $months = [
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12,
        ];

        $day = (int) $dateMatch[1];
        $month = $months[$dateMatch[2]] ?? null;
        $year = (int) $dateMatch[3];

        if (! $month) {
            return null;
        }

        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $query = ReunionAN::whereDate('date_debut', $date)
            ->where('captation_video', true)
            ->whereNull('video_id');

        if ($video['section'] === 'seance-publique' || $video['section'] === 'questions-au-gouvernement') {
            $query->where('type_reunion', 'seance_type');
        } elseif ($video['section'] === 'commissions') {
            $query->where('type_reunion', 'Commission');
        }

        if (str_contains($slug, 'questions-au-gouvernement')) {
            $query->where(function ($q) {
                $q->whereJsonContains('odj_resume', 'Questions au Gouvernement')
                    ->orWhere('uid', 'like', '%QG%');
            });
        }

        $reunion = $query->first();

        return $reunion?->uid;
    }
}
