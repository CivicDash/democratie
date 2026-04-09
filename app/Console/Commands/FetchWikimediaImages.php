<?php

namespace App\Console\Commands;

use App\Models\CommuneGalerieImage;
use App\Models\CommunePage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchWikimediaImages extends Command
{
    protected $signature = 'communes:fetch-wikimedia-images
        {--code_insee= : Code INSEE specifique}
        {--limit=50 : Nombre max de communes a traiter}
        {--images=6 : Nombre max d\'images par commune}';

    protected $description = 'Importer des images depuis Wikimedia Commons pour les pages communes';

    public function handle(): int
    {
        $query = CommunePage::with('ville:id,nom');

        if ($code = $this->option('code_insee')) {
            $query->where('code_insee', $code);
        }

        $pages = $query->whereIn('statut', ['active', 'auto_generee'])
            ->limit((int) $this->option('limit'))
            ->get();

        $maxImages = (int) $this->option('images');
        $total = 0;

        $this->withProgressBar($pages, function (CommunePage $page) use ($maxImages, &$total) {
            $nom = $page->ville?->nom;
            if (! $nom) {
                return;
            }

            $existingCount = $page->galerieImages()->where('source', 'wikimedia')->count();
            if ($existingCount >= $maxImages) {
                return;
            }

            $images = $this->fetchImagesForCity($nom, $maxImages - $existingCount);

            foreach ($images as $img) {
                $exists = CommuneGalerieImage::where('commune_page_id', $page->id)
                    ->where('wikimedia_url', $img['url'])
                    ->exists();

                if (! $exists) {
                    CommuneGalerieImage::create([
                        'commune_page_id' => $page->id,
                        'source' => 'wikimedia',
                        'wikimedia_url' => $img['url'],
                        'legende' => $img['title'],
                        'credit' => 'Wikimedia Commons',
                        'ordre' => 100 + $total,
                        'visible' => true,
                    ]);
                    $total++;
                }
            }
        });

        $this->newLine();
        $this->info("{$total} images importees depuis Wikimedia Commons.");

        return self::SUCCESS;
    }

    private function fetchImagesForCity(string $cityName, int $limit): array
    {
        $images = [];

        try {
            $response = Http::timeout(10)->withUserAgent('CivicDash/1.0 (https://demo.objectif2027.fr)')->get('https://fr.wikipedia.org/w/api.php', [
                'action' => 'query',
                'generator' => 'images',
                'titles' => $cityName,
                'prop' => 'imageinfo',
                'iiprop' => 'url|mime|size',
                'iiurlwidth' => 800,
                'gimlimit' => $limit * 3,
                'format' => 'json',
            ]);

            if (! $response->successful()) {
                return [];
            }

            $pages = $response->json('query.pages', []);

            foreach ($pages as $page) {
                $info = $page['imageinfo'][0] ?? null;
                if (! $info) {
                    continue;
                }

                $mime = $info['mime'] ?? '';
                if (! str_starts_with($mime, 'image/')) {
                    continue;
                }

                $width = $info['width'] ?? 0;
                $height = $info['height'] ?? 0;
                if ($width < 400 || $height < 300) {
                    continue;
                }

                $url = $info['thumburl'] ?? $info['url'] ?? null;
                if (! $url) {
                    continue;
                }

                $title = str_replace(['File:', '_'], ['', ' '], $page['title'] ?? '');
                $title = preg_replace('/\.\w+$/', '', $title);

                $images[] = [
                    'url' => $url,
                    'title' => $title,
                ];

                if (count($images) >= $limit) {
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Wikimedia fetch failed for {$cityName}: {$e->getMessage()}");
        }

        return $images;
    }
}
