<?php

namespace App\Console\Commands;

use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchMinistresPhotos extends Command
{
    protected $signature = 'ministres:fetch-photos 
                            {--limit=0 : Nombre maximum de photos à récupérer (0 = illimité)}
                            {--force : Remplacer les photos existantes}';

    protected $description = 'Récupère les photos des ministres depuis Wikipedia';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Récupérer les personnes qui ont eu des postes ministériels
        $query = PersonnePolitique::whereHas('postes');

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('photo_url')
                    ->orWhere('photo_url', '');
            });
        }

        $personnes = $query->get();

        $this->info("🔍 {$personnes->count()} ministres à traiter...\n");

        $photosRecuperees = 0;
        $erreurs = 0;

        $bar = $this->output->createProgressBar($personnes->count());
        $bar->start();

        foreach ($personnes as $personne) {
            if ($limit > 0 && $photosRecuperees >= $limit) {
                break;
            }

            // Utiliser prénom + nom sans le titre (M. / Mme)
            $nomRecherche = trim($personne->prenom.' '.$personne->nom);
            $photoUrl = $this->fetchWikipediaPhoto($nomRecherche);

            if ($photoUrl) {
                $personne->update(['photo_url' => $photoUrl]);
                $photosRecuperees++;
            } else {
                $erreurs++;
            }

            $bar->advance();

            // Petit délai pour ne pas surcharger l'API Wikipedia
            usleep(200000); // 200ms
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$photosRecuperees} photos récupérées");
        if ($erreurs > 0) {
            $this->warn("⚠️  {$erreurs} photos non trouvées");
        }

        return Command::SUCCESS;
    }

    private function fetchWikipediaPhoto(string $nom): ?string
    {
        try {
            $pageName = str_replace(' ', '_', $nom);

            $apiUrl = 'https://fr.wikipedia.org/w/api.php?'.http_build_query([
                'action' => 'query',
                'titles' => $pageName,
                'prop' => 'pageimages',
                'format' => 'json',
                'pithumbsize' => 400,
            ]);

            $response = Http::timeout(10)
                ->withUserAgent('CivicDash/1.0 (https://demo.objectif2027.fr)')
                ->get($apiUrl);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];

            foreach ($pages as $page) {
                if (isset($page['thumbnail']['source'])) {
                    return $page['thumbnail']['source'];
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
