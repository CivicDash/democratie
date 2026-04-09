<?php

namespace App\Console\Commands;

use App\Models\ReunionAN;
use App\Models\VideoChapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportVideoChaptersAN extends Command
{
    protected $signature = 'import:video-chapters-an
        {video_id? : Specific video ID to process (e.g. 18339196_69960cf6d5a3c)}
        {--all : Process all reunions that have a video_id}
        {--force : Re-import even if chapters already exist}
        {--dry-run : Show what would be imported without writing}';

    protected $description = 'Fetch data.nvs for AN videos and import chapter hierarchy into video_chapters table';

    private int $processed = 0;

    private int $chaptersCreated = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $specificId = $this->argument('video_id');
        $all = $this->option('all');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info('=== Import Video Chapters from AN data.nvs ===');

        if ($specificId) {
            $videoIds = collect([$specificId]);
        } elseif ($all) {
            $videoIds = ReunionAN::whereNotNull('video_id')
                ->pluck('video_id');
            $this->info("Found {$videoIds->count()} reunions with video_id");
        } else {
            $this->error('Specify a video_id argument or use --all');

            return self::FAILURE;
        }

        if ($videoIds->isEmpty()) {
            $this->warn('No videos to process. Run import:video-ids-an first.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($videoIds->count());

        foreach ($videoIds as $videoId) {
            if (! $force && VideoChapter::where('video_id', $videoId)->exists()) {
                $bar->advance();

                continue;
            }

            $this->importChaptersForVideo($videoId, $dryRun);
            $bar->advance();
            usleep(300_000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Metric', 'Count'], [
            ['Videos processed', $this->processed],
            ['Chapters created', $this->chaptersCreated],
            ['Errors', $this->errors],
        ]);

        return self::SUCCESS;
    }

    private function importChaptersForVideo(string $videoId, bool $dryRun): void
    {
        $url = "https://videos.assemblee-nationale.fr/Datas/an/{$videoId}/content/data.nvs";

        try {
            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                $this->errors++;

                return;
            }

            $xml = @simplexml_load_string($response->body());
            if (! $xml) {
                $this->errors++;

                return;
            }

            $this->processed++;

            $reunionUid = $this->extractMeetingId($xml);
            $speakers = $this->extractSpeakers($xml);

            if ($dryRun) {
                $chapterCount = $this->countChapters($xml->chapters);
                $this->line("  [DRY] {$videoId}: {$chapterCount} chapters, meeting={$reunionUid}");
                $this->chaptersCreated += $chapterCount;

                return;
            }

            if (! $dryRun) {
                VideoChapter::where('video_id', $videoId)->delete();
            }

            $sortOrder = 0;
            $this->parseChapterTree($xml->chapters, $videoId, $reunionUid, null, $speakers, $sortOrder);
        } catch (\Exception $e) {
            $this->errors++;
            Log::warning("import:video-chapters-an error for {$videoId}: {$e->getMessage()}");
        }
    }

    private function extractMeetingId(\SimpleXMLElement $xml): ?string
    {
        foreach ($xml->metadatas->metadata ?? [] as $meta) {
            if ((string) ($meta['name'] ?? '') === 'meeting_id') {
                $value = (string) ($meta['value'] ?? '');

                return $value ?: null;
            }
        }

        return null;
    }

    private function extractSpeakers(\SimpleXMLElement $xml): array
    {
        $speakers = [];

        foreach ($xml->speakers->speaker ?? [] as $sp) {
            $id = (string) ($sp['id'] ?? '');
            if (! $id) {
                continue;
            }

            $name = (string) ($sp->name ?? $sp['label'] ?? '');
            $anUid = null;

            $urlVal = (string) ($sp->url ?? '');
            if ($urlVal && is_numeric(trim($urlVal))) {
                $anUid = 'PA'.trim($urlVal);
            }

            $speakers[$id] = [
                'name' => $name,
                'an_uid' => $anUid,
            ];
        }

        return $speakers;
    }

    private function parseChapterTree(
        \SimpleXMLElement $node,
        string $videoId,
        ?string $reunionUid,
        ?string $parentNid,
        array $speakers,
        int &$sortOrder
    ): void {
        foreach ($node->chapter ?? [] as $chapter) {
            $nid = (string) ($chapter['id'] ?? '');
            $label = (string) ($chapter['label'] ?? '');

            $typeKey = null;
            $typeLabel = null;
            $themeKey = null;
            $themeLabel = null;
            $speakerVodalysId = null;
            $speakerAnUid = null;
            $speakerName = null;

            foreach ($chapter->type ?? [] as $type) {
                $typeKey = (int) ($type['key'] ?? 0);
                $typeLabel = (string) ($type['value'] ?? '');
            }

            foreach ($chapter->theme ?? [] as $theme) {
                $themeKey = (int) ($theme['key'] ?? 0);
                $themeLabel = (string) ($theme['value'] ?? '');
            }

            foreach ($chapter->speaker ?? [] as $sp) {
                $spId = (string) ($sp['id'] ?? '');
                $speakerVodalysId = $spId;

                if (isset($speakers[$spId])) {
                    $speakerAnUid = $speakers[$spId]['an_uid'];
                    $speakerName = $speakers[$spId]['name'];
                }
            }

            $sortOrder++;

            VideoChapter::create([
                'video_id' => $videoId,
                'reunion_uid' => $reunionUid,
                'chapter_nid' => $nid,
                'parent_nid' => $parentNid,
                'label' => $label,
                'chapter_type_key' => $typeKey,
                'chapter_type_label' => $typeLabel,
                'theme_key' => $themeKey,
                'theme_label' => $themeLabel,
                'speaker_vodalys_id' => $speakerVodalysId,
                'speaker_an_uid' => $speakerAnUid,
                'speaker_name' => $speakerName,
                'timecode_seconds' => null,
                'sort_order' => $sortOrder,
            ]);

            $this->chaptersCreated++;

            if ($chapter->chapter) {
                $this->parseChapterTree($chapter, $videoId, $reunionUid, $nid, $speakers, $sortOrder);
            }
        }
    }

    private function countChapters(\SimpleXMLElement $node): int
    {
        $count = 0;
        foreach ($node->chapter ?? [] as $chapter) {
            $count++;
            if ($chapter->chapter) {
                $count += $this->countChapters($chapter);
            }
        }

        return $count;
    }
}
