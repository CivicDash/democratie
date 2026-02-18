<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class ResearchVideoAN extends Command
{
    protected $signature = 'an:research-video
                            {url? : URL directe d\'une video videos.assemblee-nationale.fr}
                            {--date= : Date de seance au format YYYY-MM-DD}
                            {--type=seance : Type: seance, qag, commission}
                            {--fetch-nvs : Telecharger et parser les data.nvs (chapitres/speakers/metadonnees)}
                            {--output= : Fichier de sortie pour les resultats}';

    protected $description = 'Prototype de recherche : analyse le portail video AN pour extraire les endpoints, chapitres et timecodes';

    private string $portalBase = 'https://videos.assemblee-nationale.fr';
    private array $findings = [];

    public function handle(): int
    {
        $this->info('=== Recherche Video Assemblee Nationale ===');
        $this->newLine();

        $url = $this->argument('url');
        $urls = [];

        if ($url) {
            $urls[] = $url;
        } else {
            $urls = $this->discoverVideoUrls();
        }

        if (empty($urls)) {
            $this->error('Aucune URL video a analyser.');
            return 1;
        }

        if ($this->option('fetch-nvs')) {
            return $this->fetchAndParseNvs($urls);
        }

        foreach ($urls as $videoUrl) {
            $this->analyzeVideoPage($videoUrl);
        }

        $this->probeCommonEndpoints();
        $this->probeSrtFiles();
        $this->probeListingPages();

        $this->printSummary();

        $outputFile = $this->option('output')
            ?? storage_path('app/an-data/video-research-' . now()->format('Y-m-d_His') . '.json');

        File::ensureDirectoryExists(dirname($outputFile));
        File::put($outputFile, json_encode($this->findings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Resultats sauvegardes dans : {$outputFile}");

        return 0;
    }

    /**
     * Fetch and parse data.nvs XML for given video URLs.
     * This is the most valuable endpoint: it provides structured chapter data,
     * speakers with AN actor UIDs, video metadata, and thumbnails.
     */
    private function fetchAndParseNvs(array $urls): int
    {
        $results = [];

        foreach ($urls as $url) {
            $videoId = $this->extractVideoId($url);
            $nvsUrl = "{$this->portalBase}/Datas/an/{$videoId}/content/data.nvs";

            $this->info("Fetching data.nvs for {$videoId}...");
            $this->comment("  URL: {$nvsUrl}");

            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'CivicDash/1.3 Research Bot'])
                    ->get($nvsUrl);

                if (!$response->successful()) {
                    $this->error("  HTTP {$response->status()}");
                    continue;
                }

                $xml = simplexml_load_string($response->body());
                if (!$xml) {
                    $this->error("  Invalid XML");
                    continue;
                }

                $videoData = [
                    'video_id' => $videoId,
                    'portal_url' => $url,
                    'nvs_url' => $nvsUrl,
                    'status' => (string)$xml['status'],
                    'title' => html_entity_decode((string)$xml['mediatitle'], ENT_XML1, 'UTF-8'),
                    'interface_uid' => (string)$xml['interfaceuid'],
                ];

                // Parse metadata
                $metadata = [];
                foreach ($xml->metadatas->metadata ?? [] as $meta) {
                    $key = (string)$meta['name'];
                    $metadata[$key] = [
                        'value' => (string)$meta['value'],
                        'label' => (string)($meta['label'] ?? ''),
                    ];
                }
                $videoData['metadata'] = $metadata;

                $this->info("  Title: {$videoData['title']}");
                $this->info("  Status: {$videoData['status']}");
                $this->info("  Type: " . ($metadata['video_type']['label'] ?? 'unknown'));
                $this->info("  Legislature: " . ($metadata['legislature']['label'] ?? 'unknown'));

                // Parse chapters recursively
                $chapters = [];
                foreach ($xml->chapters->chapter ?? [] as $chapter) {
                    $chapters[] = $this->parseChapter($chapter, 0);
                }
                $videoData['chapters'] = $chapters;
                $totalChapters = $this->countChapters($chapters);
                $this->info("  Chapters: {$totalChapters}");

                // Parse speakers
                $speakers = [];
                foreach ($xml->speakers->speaker ?? [] as $speaker) {
                    $speakerData = [
                        'id' => (string)$speaker['id'],
                        'name' => html_entity_decode((string)$speaker->name, ENT_XML1, 'UTF-8'),
                        'role' => (string)$speaker->role,
                        'an_uid' => (string)$speaker->url, // AN actor UID (e.g., "794838" -> "PA794838")
                    ];
                    $speakers[] = $speakerData;
                }
                $videoData['speakers'] = $speakers;
                $this->info("  Speakers: " . count($speakers));

                // Display speakers
                foreach ($speakers as $s) {
                    $paId = $s['an_uid'] ? "PA{$s['an_uid']}" : 'N/A';
                    $this->comment("    {$s['name']} (AN UID: {$paId})");
                }

                // Parse files (video sources)
                $files = [];
                foreach ($xml->files->file ?? [] as $file) {
                    $files[] = [
                        'id' => (string)$file['id'],
                        'title' => (string)$file['title'],
                        'url' => (string)$file['url'],
                    ];
                }
                $videoData['files'] = $files;

                // Parse thumbnails
                $thumbnails = [];
                foreach ($xml->thumbnails->thumbnail ?? [] as $thumb) {
                    $thumbnails[] = [
                        'id' => (string)$thumb['id'],
                        'url' => (string)$thumb['url'],
                    ];
                }
                $videoData['thumbnails'] = $thumbnails;
                $this->info("  Thumbnails: " . count($thumbnails));

                // Print chapter tree
                $this->newLine();
                $this->info('  Chapter tree:');
                $this->printChapterTree($chapters, '    ');

                $results[] = $videoData;

            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
            }

            $this->newLine();
        }

        $outputFile = $this->option('output')
            ?? storage_path('app/an-data/nvs-parsed-' . now()->format('Y-m-d_His') . '.json');
        File::ensureDirectoryExists(dirname($outputFile));
        File::put($outputFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Results saved to: {$outputFile}");

        return 0;
    }

    private function parseChapter(\SimpleXMLElement $chapter, int $depth): array
    {
        $data = [
            'id' => (string)$chapter['id'],
            'label' => html_entity_decode((string)$chapter['label'], ENT_XML1, 'UTF-8'),
            'depth' => $depth,
        ];

        if ($chapter->type) {
            $data['type_key'] = (string)$chapter->type['key'];
            $data['type'] = html_entity_decode((string)$chapter->type['value'], ENT_XML1, 'UTF-8');
        }
        if ($chapter->theme) {
            $data['theme_key'] = (string)$chapter->theme['key'];
            $data['theme'] = html_entity_decode((string)$chapter->theme['value'], ENT_XML1, 'UTF-8');
        }
        if ($chapter->speaker) {
            $data['speaker_id'] = (string)$chapter->speaker['id'];
        }

        $children = [];
        foreach ($chapter->chapter ?? [] as $child) {
            $children[] = $this->parseChapter($child, $depth + 1);
        }
        if (!empty($children)) {
            $data['children'] = $children;
        }

        return $data;
    }

    private function countChapters(array $chapters): int
    {
        $count = count($chapters);
        foreach ($chapters as $ch) {
            if (isset($ch['children'])) {
                $count += $this->countChapters($ch['children']);
            }
        }
        return $count;
    }

    private function printChapterTree(array $chapters, string $indent): void
    {
        foreach ($chapters as $ch) {
            $prefix = str_repeat('  ', $ch['depth']);
            $label = $ch['label'];
            $extra = '';
            if (isset($ch['type'])) $extra .= " [{$ch['type']}]";
            if (isset($ch['theme'])) $extra .= " ({$ch['theme']})";
            $this->comment("{$indent}{$prefix}- {$label}{$extra}");

            if (isset($ch['children'])) {
                $this->printChapterTree($ch['children'], $indent);
            }
        }
    }

    /**
     * Discover video URLs from the portal listing pages.
     */
    private function discoverVideoUrls(): array
    {
        $urls = [];
        $type = $this->option('type');
        $date = $this->option('date') ?? now()->format('Y-m-d');

        $listingPages = [
            'seance' => "{$this->portalBase}/seance-publique",
            'qag' => "{$this->portalBase}/questions-au-gouvernement",
            'commission' => "{$this->portalBase}/commissions",
        ];

        $listingUrl = $listingPages[$type] ?? $listingPages['seance'];
        $this->info("Scan de la page listing : {$listingUrl}");

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'CivicDash/1.3 Research Bot'])
                ->get($listingUrl);

            if ($response->successful()) {
                $html = $response->body();
                preg_match_all(
                    '#href=["\'](/video\.\d+_[a-f0-9]+[^"\']*)["\']#i',
                    $html,
                    $matches
                );

                if (!empty($matches[1])) {
                    foreach (array_slice(array_unique($matches[1]), 0, 3) as $path) {
                        $urls[] = $this->portalBase . $path;
                    }
                    $this->info('  -> ' . count($matches[1]) . ' liens video trouves, ' . count($urls) . ' retenus');
                } else {
                    $this->warn('  -> Aucun lien video trouve dans le HTML (contenu charge en JS?)');
                }

                $this->findings['listing_page'] = [
                    'url' => $listingUrl,
                    'status' => $response->status(),
                    'video_links_found' => count($matches[1] ?? []),
                    'html_size' => strlen($html),
                ];
            }
        } catch (\Exception $e) {
            $this->error("  -> Erreur : {$e->getMessage()}");
        }

        if (empty($urls)) {
            $this->info('Utilisation d\'URLs connues pour la recherche...');
            $urls = [
                'https://videos.assemblee-nationale.fr/video.18339964_69961e36928df.2eme-seance--fin-de-vie-deuxieme-lecture-suite-18-fevrier-2026',
                'https://videos.assemblee-nationale.fr/video.18339196_69960cf6d5a3c.questions-au-gouvernement---mercredi-18-fevrier-2026-18-fevrier-2026',
            ];
        }

        return $urls;
    }

    /**
     * Analyze a single video page: extract JS sources, inline data, and embedded endpoints.
     */
    private function analyzeVideoPage(string $url): void
    {
        $this->newLine();
        $this->info("Analyse de : {$url}");

        $videoId = $this->extractVideoId($url);
        $this->comment("  Video ID: {$videoId}");

        $pageData = [
            'url' => $url,
            'video_id' => $videoId,
            'js_sources' => [],
            'inline_data' => [],
            'api_endpoints_found' => [],
            'srt_references' => [],
            'iframe_sources' => [],
            'json_ld' => [],
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'CivicDash/1.3 Research Bot'])
                ->get($url);

            if (!$response->successful()) {
                $this->error("  -> HTTP {$response->status()}");
                $pageData['error'] = "HTTP {$response->status()}";
                $this->findings['pages'][] = $pageData;
                return;
            }

            $html = $response->body();
            $pageData['html_size'] = strlen($html);

            // Extract JS file sources
            preg_match_all('#<script[^>]+src=["\']([^"\']+)["\']#i', $html, $jsMatches);
            $pageData['js_sources'] = $jsMatches[1] ?? [];
            $this->info('  JS sources: ' . count($pageData['js_sources']));

            // Look for inline JSON data (common pattern: var config = {...})
            preg_match_all(
                '#(?:var|let|const)\s+(\w+)\s*=\s*(\{[^;]{10,}\});#s',
                $html,
                $inlineMatches
            );
            foreach (($inlineMatches[1] ?? []) as $i => $varName) {
                $snippet = mb_substr($inlineMatches[2][$i], 0, 500);
                $pageData['inline_data'][$varName] = $snippet;
                $this->comment("  Inline var: {$varName} (" . strlen($inlineMatches[2][$i]) . " chars)");
            }

            // Look for data attributes on video elements
            preg_match_all('#data-(\w+)=["\']([^"\']+)["\']#i', $html, $dataAttrs);
            if (!empty($dataAttrs[1])) {
                $pageData['data_attributes'] = array_combine($dataAttrs[1], $dataAttrs[2]);
                $this->info('  Data attributes: ' . implode(', ', $dataAttrs[1]));
            }

            // Look for API/AJAX endpoint patterns in inline scripts
            preg_match_all(
                '#(?:fetch|\.get|\.post|XMLHttpRequest|ajax)\s*\(\s*["\']([^"\']+)["\']#i',
                $html,
                $apiMatches
            );
            $pageData['api_endpoints_found'] = array_unique($apiMatches[1] ?? []);
            if (!empty($pageData['api_endpoints_found'])) {
                $this->info('  API endpoints in HTML: ' . implode(', ', $pageData['api_endpoints_found']));
            }

            // Look for SRT/VTT references
            preg_match_all('#["\']([^"\']*\.(?:srt|vtt))["\']#i', $html, $srtMatches);
            $pageData['srt_references'] = array_unique($srtMatches[1] ?? []);
            if (!empty($pageData['srt_references'])) {
                $this->info('  SRT/VTT files: ' . implode(', ', $pageData['srt_references']));
            }

            // Look for video source URLs (mp4, m3u8, etc.)
            preg_match_all('#["\']([^"\']*\.(?:mp4|m3u8|webm|mpd))["\']#i', $html, $videoSrcMatches);
            $pageData['video_sources'] = array_unique($videoSrcMatches[1] ?? []);
            if (!empty($pageData['video_sources'])) {
                $this->info('  Video sources: ' . implode(', ', $pageData['video_sources']));
            }

            // Look for iframes
            preg_match_all('#<iframe[^>]+src=["\']([^"\']+)["\']#i', $html, $iframeMatches);
            $pageData['iframe_sources'] = $iframeMatches[1] ?? [];

            // Look for JSON-LD structured data
            preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#si', $html, $ldMatches);
            foreach ($ldMatches[1] ?? [] as $ldJson) {
                $decoded = json_decode(trim($ldJson), true);
                if ($decoded) {
                    $pageData['json_ld'][] = $decoded;
                    $this->info('  JSON-LD: ' . ($decoded['@type'] ?? 'unknown type'));
                }
            }

            // Extract all URLs from HTML that contain the video ID
            if ($videoId) {
                $idParts = explode('_', $videoId);
                $numericId = $idParts[0] ?? $videoId;
                preg_match_all("#[\"'](https?://[^\"']*{$numericId}[^\"']*)[\"']#i", $html, $idUrlMatches);
                $pageData['urls_with_video_id'] = array_values(array_unique($idUrlMatches[1] ?? []));
                if (!empty($pageData['urls_with_video_id'])) {
                    $this->info('  URLs with video ID:');
                    foreach ($pageData['urls_with_video_id'] as $u) {
                        $this->comment("    -> {$u}");
                    }
                }
            }

            // Analyze external JS files for API patterns
            foreach (array_slice($pageData['js_sources'], 0, 5) as $jsSrc) {
                $jsUrl = $this->resolveUrl($jsSrc, $url);
                $this->analyzeJsFile($jsUrl, $pageData);
            }

        } catch (\Exception $e) {
            $this->error("  -> Exception : {$e->getMessage()}");
            $pageData['error'] = $e->getMessage();
        }

        $this->findings['pages'][] = $pageData;
    }

    /**
     * Analyze a JS file for API endpoint patterns.
     */
    private function analyzeJsFile(string $jsUrl, array &$pageData): void
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'CivicDash/1.3 Research Bot'])
                ->get($jsUrl);

            if (!$response->successful()) return;

            $js = $response->body();
            $jsAnalysis = ['url' => $jsUrl, 'size' => strlen($js)];

            // API endpoint patterns
            preg_match_all(
                '#["\'](?:/(?:php|api|ajax|service|ws)[^"\']{3,})["\']#i',
                $js,
                $endpointMatches
            );
            $endpoints = array_map(fn($e) => trim($e, "\"'"), array_unique($endpointMatches[0] ?? []));
            if (!empty($endpoints)) {
                $jsAnalysis['api_endpoints'] = $endpoints;
                $this->comment("  JS {$jsUrl}:");
                foreach ($endpoints as $ep) {
                    $this->comment("    endpoint: {$ep}");
                }
            }

            // SRT/chapter patterns
            preg_match_all('#["\']([^"\']*(?:srt|chapter|chapitre|timecode|subtitle|caption)[^"\']*)["\']#i', $js, $subMatches);
            if (!empty($subMatches[1])) {
                $jsAnalysis['subtitle_patterns'] = array_unique($subMatches[1]);
            }

            // Datas path patterns (the AN portal stores files under /Datas/)
            preg_match_all('#["\']([^"\']*Datas[^"\']*)["\']#i', $js, $datasMatches);
            if (!empty($datasMatches[1])) {
                $jsAnalysis['datas_paths'] = array_unique($datasMatches[1]);
                foreach ($jsAnalysis['datas_paths'] as $dp) {
                    $this->comment("    Datas path: {$dp}");
                }
            }

            if (count($jsAnalysis) > 2) {
                $pageData['js_analysis'][] = $jsAnalysis;
            }

        } catch (\Exception $e) {
            // Skip JS files that fail to load
        }
    }

    /**
     * Probe common/guessed API endpoints on the video portal.
     */
    private function probeCommonEndpoints(): void
    {
        $this->newLine();
        $this->info('=== Probe des endpoints courants ===');

        $sampleIds = ['18339964_69961e36928df', '18339196_69960cf6d5a3c'];

        $endpointPatterns = [
            '/php/getInfoVideo.php?id={id}',
            '/php/getVideo.php?id={id}',
            '/php/getChapters.php?id={id}',
            '/api/video/{id}',
            '/api/video/{id}/chapters',
            '/api/video/{id}/subtitles',
            '/api/videos/{id}',
            '/api/v1/video/{id}',
            '/ws/video/{id}',
            '/ajax/video/{id}',
            '/Datas/an/{id}/info.json',
            '/Datas/an/{id}/chapters.json',
            '/Datas/an/{id}/files/info.json',
        ];

        $numericIds = array_map(fn($id) => explode('_', $id)[0], $sampleIds);
        $allEndpoints = [];

        foreach ($endpointPatterns as $pattern) {
            foreach ($sampleIds as $i => $id) {
                $allEndpoints[] = [
                    'pattern' => $pattern,
                    'url' => $this->portalBase . str_replace('{id}', $id, $pattern),
                ];
            }
            $allEndpoints[] = [
                'pattern' => $pattern . ' (numeric)',
                'url' => $this->portalBase . str_replace('{id}', $numericIds[0], $pattern),
            ];
        }

        $this->findings['endpoint_probes'] = [];

        $bar = $this->output->createProgressBar(count($allEndpoints));
        $bar->start();

        foreach ($allEndpoints as $probe) {
            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'CivicDash/1.3 Research Bot'])
                    ->get($probe['url']);

                $result = [
                    'pattern' => $probe['pattern'],
                    'url' => $probe['url'],
                    'status' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'size' => strlen($response->body()),
                ];

                if ($response->successful()) {
                    $body = $response->body();
                    $result['is_json'] = str_starts_with(trim($body), '{') || str_starts_with(trim($body), '[');
                    $result['is_xml'] = str_starts_with(trim($body), '<?xml') || str_starts_with(trim($body), '<');
                    $result['preview'] = mb_substr($body, 0, 500);

                    $this->findings['endpoint_probes'][] = $result;
                    $bar->advance();
                    continue;
                }

                if ($response->status() !== 404) {
                    $this->findings['endpoint_probes'][] = $result;
                }
            } catch (\Exception $e) {
                // Timeout or connection error, skip
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $successes = array_filter($this->findings['endpoint_probes'], fn($p) => ($p['status'] ?? 0) >= 200 && ($p['status'] ?? 0) < 300);
        $this->info(count($successes) . ' endpoint(s) accessible(s) sur ' . count($allEndpoints) . ' testes');

        foreach ($successes as $s) {
            $this->info("  [200] {$s['url']}");
            $this->comment("    Content-Type: {$s['content_type']}, Size: {$s['size']}");
            if (!empty($s['preview'])) {
                $this->comment('    Preview: ' . mb_substr($s['preview'], 0, 200));
            }
        }
    }

    /**
     * Attempt to find and download SRT subtitle files.
     */
    private function probeSrtFiles(): void
    {
        $this->newLine();
        $this->info('=== Recherche de fichiers SRT ===');

        $sampleIds = ['18339964_69961e36928df', '18339196_69960cf6d5a3c'];
        $srtPatterns = [
            '/Datas/an/{id}/files/hemi_{date}_1.srt',
            '/Datas/an/{id}/files/subtitles.srt',
            '/Datas/an/{id}/files/captions.srt',
            '/Datas/an/{id}/files/transcript.srt',
            '/Datas/an/{id}/subtitles.srt',
            '/Datas/an/{full_id}/files/hemi_1.srt',
            '/subtitles/{id}.srt',
            '/captions/{id}.srt',
        ];

        $this->findings['srt_probes'] = [];

        foreach ($sampleIds as $fullId) {
            $numericId = explode('_', $fullId)[0];
            $dateGuess = '20260218'; // Known date for our sample videos

            foreach ($srtPatterns as $pattern) {
                $url = $this->portalBase . str_replace(
                    ['{id}', '{full_id}', '{date}'],
                    [$numericId, $fullId, $dateGuess],
                    $pattern
                );

                try {
                    $response = Http::timeout(8)
                        ->withHeaders(['User-Agent' => 'CivicDash/1.3 Research Bot'])
                        ->get($url);

                    $result = [
                        'url' => $url,
                        'status' => $response->status(),
                        'content_type' => $response->header('Content-Type'),
                        'size' => strlen($response->body()),
                    ];

                    if ($response->successful() && strlen($response->body()) > 50) {
                        $result['is_srt'] = str_contains($response->body(), '-->');
                        $result['preview'] = mb_substr($response->body(), 0, 500);
                        $result['line_count'] = substr_count($response->body(), "\n");

                        $this->info("  [FOUND] {$url}");
                        $this->comment("    SRT format: " . ($result['is_srt'] ? 'YES' : 'NO'));
                        $this->comment("    Size: {$result['size']} bytes, {$result['line_count']} lines");

                        if ($result['is_srt']) {
                            $srtPath = storage_path("app/an-data/srt-sample-{$numericId}.srt");
                            File::ensureDirectoryExists(dirname($srtPath));
                            File::put($srtPath, $response->body());
                            $result['saved_to'] = $srtPath;
                            $this->info("    Saved to: {$srtPath}");

                            $this->analyzeSrt($response->body(), $result);
                        }
                    }

                    $this->findings['srt_probes'][] = $result;
                } catch (\Exception $e) {
                    // Skip
                }
            }
        }

        $found = array_filter($this->findings['srt_probes'], fn($p) => ($p['status'] ?? 0) === 200 && ($p['size'] ?? 0) > 50);
        $this->info(count($found) . ' fichier(s) SRT trouve(s)');
    }

    /**
     * Analyze an SRT file for useful content.
     */
    private function analyzeSrt(string $content, array &$result): void
    {
        $lines = explode("\n", $content);
        $cues = [];
        $currentCue = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^(\d{2}:\d{2}:\d{2}[,.]\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2}[,.]\d{3})/', $line, $m)) {
                $currentCue = ['start' => $m[1], 'end' => $m[2], 'text' => ''];
            } elseif ($currentCue !== null && $line !== '' && !is_numeric($line)) {
                $currentCue['text'] .= ($currentCue['text'] ? ' ' : '') . strip_tags($line);
            } elseif ($currentCue !== null && $line === '') {
                if (!empty($currentCue['text'])) {
                    $cues[] = $currentCue;
                }
                $currentCue = null;
            }
        }

        if ($currentCue && !empty($currentCue['text'])) {
            $cues[] = $currentCue;
        }

        $result['srt_analysis'] = [
            'total_cues' => count($cues),
            'first_cue' => $cues[0] ?? null,
            'last_cue' => end($cues) ?: null,
            'sample_cues' => array_slice($cues, 0, 5),
        ];

        // Look for amendment mentions
        $amendmentMentions = [];
        foreach ($cues as $cue) {
            if (preg_match('/amendement\s*(?:n[°o]?\s*)?(\d+)/i', $cue['text'], $m)) {
                $amendmentMentions[] = [
                    'amendment_number' => $m[1],
                    'timecode' => $cue['start'],
                    'context' => mb_substr($cue['text'], 0, 200),
                ];
            }
        }
        $result['srt_analysis']['amendment_mentions'] = $amendmentMentions;
        $result['srt_analysis']['amendment_count'] = count($amendmentMentions);

        if (!empty($amendmentMentions)) {
            $this->info('    Amendements trouves dans le SRT: ' . count($amendmentMentions));
            foreach (array_slice($amendmentMentions, 0, 5) as $am) {
                $this->comment("      #{$am['amendment_number']} @ {$am['timecode']}");
            }
        }

        // Look for speaker names (pattern: "M./Mme X" or "Le/La ministre")
        $speakerMentions = [];
        foreach ($cues as $cue) {
            if (preg_match('/^(?:M\.|Mme|Monsieur|Madame)\s+(?:le |la )?(?:président|ministre|rapporteur|secrétaire)?[^,.]{2,40}/i', $cue['text'], $m)) {
                $speakerMentions[] = [
                    'speaker' => trim($m[0]),
                    'timecode' => $cue['start'],
                ];
            }
        }
        $result['srt_analysis']['speaker_mentions'] = count($speakerMentions);
        $result['srt_analysis']['sample_speakers'] = array_slice($speakerMentions, 0, 10);
    }

    /**
     * Probe listing pages to understand URL structure.
     */
    private function probeListingPages(): void
    {
        $this->newLine();
        $this->info('=== Analyse des pages listing ===');

        $pages = [
            'seance-publique' => "{$this->portalBase}/seance-publique",
            'qag' => "{$this->portalBase}/questions-au-gouvernement",
        ];

        $this->findings['listing_analysis'] = [];

        foreach ($pages as $key => $url) {
            $this->info("Analyse de {$key} : {$url}");

            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'CivicDash/1.3 Research Bot'])
                    ->get($url);

                if (!$response->successful()) continue;

                $html = $response->body();

                // Extract video links and their text
                preg_match_all(
                    '#<a[^>]*href=["\'](?:https?://videos\.assemblee-nationale\.fr)?(/video\.(\d+)_([a-f0-9]+)\.([^"\']+))["\'][^>]*>(.*?)</a>#si',
                    $html,
                    $linkMatches,
                    PREG_SET_ORDER
                );

                $videos = [];
                foreach (array_slice($linkMatches, 0, 10) as $m) {
                    $videos[] = [
                        'path' => $m[1],
                        'numeric_id' => $m[2],
                        'hash' => $m[3],
                        'slug' => $m[4],
                        'link_text' => trim(strip_tags($m[5])),
                        'full_url' => $this->portalBase . $m[1],
                    ];
                }

                // Also try a simpler regex for JS-generated links
                preg_match_all('#/video\.(\d+)_([a-f0-9]+)\.([^\s"\'<>]+)#i', $html, $simpleMatches, PREG_SET_ORDER);
                $simpleVideos = [];
                foreach (array_slice($simpleMatches, 0, 10) as $m) {
                    $simpleVideos[] = [
                        'numeric_id' => $m[1],
                        'hash' => $m[2],
                        'slug' => $m[3],
                    ];
                }

                $this->findings['listing_analysis'][$key] = [
                    'url' => $url,
                    'html_size' => strlen($html),
                    'anchor_videos' => $videos,
                    'regex_videos' => $simpleVideos,
                    'total_video_refs' => count($simpleMatches),
                ];

                $this->info("  {$key}: " . count($simpleMatches) . ' references video trouvees');

                // Extract the URL pattern
                if (!empty($simpleVideos)) {
                    $sample = $simpleVideos[0];
                    $this->comment("  Pattern: /video.{$sample['numeric_id']}_{$sample['hash']}.{$sample['slug']}");
                }

            } catch (\Exception $e) {
                $this->error("  Erreur: {$e->getMessage()}");
            }
        }
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('========================================');
        $this->info('=== RESUME DES DECOUVERTES ===');
        $this->info('========================================');
        $this->newLine();

        // Pages analyzed
        $pages = $this->findings['pages'] ?? [];
        $this->info('Pages video analysees: ' . count($pages));
        foreach ($pages as $p) {
            $this->comment("  {$p['url']}");
            $this->comment("    JS sources: " . count($p['js_sources'] ?? []));
            $this->comment("    API endpoints in HTML: " . count($p['api_endpoints_found'] ?? []));
            $this->comment("    SRT refs: " . count($p['srt_references'] ?? []));
            $this->comment("    Video sources: " . count($p['video_sources'] ?? []));
            $this->comment("    JSON-LD: " . count($p['json_ld'] ?? []));
            if (!empty($p['js_analysis'])) {
                foreach ($p['js_analysis'] as $jsa) {
                    if (!empty($jsa['api_endpoints'])) {
                        $this->info("    JS API endpoints from {$jsa['url']}:");
                        foreach ($jsa['api_endpoints'] as $ep) {
                            $this->comment("      -> {$ep}");
                        }
                    }
                    if (!empty($jsa['datas_paths'])) {
                        $this->info("    JS Datas paths:");
                        foreach ($jsa['datas_paths'] as $dp) {
                            $this->comment("      -> {$dp}");
                        }
                    }
                }
            }
        }

        // Endpoints
        $endpoints = $this->findings['endpoint_probes'] ?? [];
        $successEndpoints = array_filter($endpoints, fn($e) => ($e['status'] ?? 0) >= 200 && ($e['status'] ?? 0) < 300);
        $this->newLine();
        $this->info('Endpoints probes: ' . count($successEndpoints) . '/' . count($endpoints) . ' reussis');
        foreach ($successEndpoints as $ep) {
            $this->info("  [OK] {$ep['url']}");
            $this->comment("    Type: {$ep['content_type']}, JSON: " . ($ep['is_json'] ?? false ? 'Y' : 'N') . ", XML: " . ($ep['is_xml'] ?? false ? 'Y' : 'N'));
        }

        // SRT
        $srtProbes = $this->findings['srt_probes'] ?? [];
        $srtFound = array_filter($srtProbes, fn($s) => ($s['status'] ?? 0) === 200 && ($s['size'] ?? 0) > 50);
        $this->newLine();
        $this->info('Fichiers SRT: ' . count($srtFound) . ' trouves');
        foreach ($srtFound as $srt) {
            $this->info("  {$srt['url']}");
            if (!empty($srt['srt_analysis'])) {
                $a = $srt['srt_analysis'];
                $this->comment("    Cues: {$a['total_cues']}, Amendements: {$a['amendment_count']}, Speakers: {$a['speaker_mentions']}");
            }
        }

        // Listings
        $listings = $this->findings['listing_analysis'] ?? [];
        $this->newLine();
        $this->info('Pages listing: ' . count($listings));
        foreach ($listings as $key => $l) {
            $this->comment("  {$key}: {$l['total_video_refs']} refs video");
        }

        $this->newLine();
        $hasEndpoints = !empty($successEndpoints);
        $hasSrt = !empty($srtFound);
        $hasJsEndpoints = false;
        foreach ($pages as $p) {
            if (!empty($p['js_analysis'])) $hasJsEndpoints = true;
        }

        $this->info('CONCLUSION:');
        if ($hasEndpoints) {
            $this->info('  -> Des endpoints API accessibles ont ete trouves. Exploitation possible.');
        }
        if ($hasSrt) {
            $this->info('  -> Des fichiers SRT sont accessibles. Matching amendements/timecodes faisable.');
        }
        if ($hasJsEndpoints) {
            $this->info('  -> Des endpoints ont ete trouves dans le JS. Investigation approfondie recommandee.');
        }
        if (!$hasEndpoints && !$hasSrt && !$hasJsEndpoints) {
            $this->warn('  -> Aucune source de timecodes facilement exploitable trouvee.');
            $this->warn('  -> Un scraping via headless browser (Puppeteer/Playwright) serait necessaire.');
        }
    }

    private function extractVideoId(string $url): string
    {
        if (preg_match('#/video\.(\d+_[a-f0-9]+)#', $url, $m)) {
            return $m[1];
        }
        return basename($url);
    }

    private function resolveUrl(string $src, string $base): string
    {
        if (str_starts_with($src, 'http')) return $src;
        if (str_starts_with($src, '//')) return 'https:' . $src;
        if (str_starts_with($src, '/')) return $this->portalBase . $src;

        $baseParts = parse_url($base);
        $baseDir = dirname($baseParts['path'] ?? '/');
        return ($baseParts['scheme'] ?? 'https') . '://' . ($baseParts['host'] ?? '') . $baseDir . '/' . $src;
    }
}
