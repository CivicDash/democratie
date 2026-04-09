<?php

namespace App\Http\Controllers;

use App\Models\CommuneArticle;
use App\Models\CommuneEvenement;
use App\Models\CommunePage;
use App\Models\Ville;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = Cache::remember('sitemap_xml', 3600, function () {
            $sitemap = Sitemap::create();

            $sitemap->add(Url::create('/')
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0));

            Ville::select('slug', 'updated_at')
                ->orderBy('population', 'desc')
                ->limit(5000)
                ->chunk(500, function ($villes) use ($sitemap) {
                    foreach ($villes as $ville) {
                        $sitemap->add(Url::create("/villes/{$ville->slug}")
                            ->setLastModificationDate($ville->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.7));
                    }
                });

            $communePages = CommunePage::with('ville:id,slug')
                ->select('id', 'code_insee', 'ville_id', 'updated_at')
                ->whereIn('statut', ['active', 'auto_generee'])
                ->get();

            $sections = ['', '/elus', '/budget', '/elections'];

            foreach ($communePages as $page) {
                foreach ($sections as $section) {
                    $sitemap->add(Url::create("/commune-hub/{$page->code_insee}{$section}")
                        ->setLastModificationDate($page->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority($section === '' ? 0.8 : 0.6));
                }
            }

            CommuneArticle::where('publie', true)
                ->select('slug', 'commune_page_id', 'publie_at', 'updated_at')
                ->with('communePage:id,code_insee')
                ->orderByDesc('publie_at')
                ->limit(2000)
                ->chunk(500, function ($articles) use ($sitemap) {
                    foreach ($articles as $article) {
                        if (! $article->communePage) {
                            continue;
                        }
                        $sitemap->add(Url::create("/commune-hub/{$article->communePage->code_insee}/actualites/{$article->slug}")
                            ->setLastModificationDate($article->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                            ->setPriority(0.5));
                    }
                });

            CommuneEvenement::where('publie', true)
                ->where('date_debut', '>=', now()->subMonths(3))
                ->select('slug', 'commune_page_id', 'date_debut', 'updated_at')
                ->with('communePage:id,code_insee')
                ->orderByDesc('date_debut')
                ->limit(1000)
                ->chunk(500, function ($evenements) use ($sitemap) {
                    foreach ($evenements as $event) {
                        if (! $event->communePage) {
                            continue;
                        }
                        $sitemap->add(Url::create("/commune-hub/{$event->communePage->code_insee}/evenements/{$event->slug}")
                            ->setLastModificationDate($event->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.5));
                    }
                });

            return $sitemap->render();
        });

        return response($sitemap, 200, ['Content-Type' => 'application/xml']);
    }
}
