<?php

namespace App\Console\Commands;

use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncWikipediaPersonnesPolitiques extends Command
{
    protected $signature = 'sync:wikipedia-personnes 
                            {--limit=50 : Nombre de personnes à traiter}
                            {--force : Forcer la resynchronisation}
                            {--only-missing : Seulement celles sans photo}';

    protected $description = 'Enrichir les personnes politiques depuis Wikipedia (photos, biographies)';

    private const WIKIPEDIA_API = 'https://fr.wikipedia.org/w/api.php';
    private const USER_AGENT = 'CivicDash/1.0 (contact@civicdash.fr)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');
        $onlyMissing = $this->option('only-missing');

        $this->info('🔄 Synchronisation Wikipedia des personnes politiques');
        $this->newLine();

        // Sélectionner les personnes à traiter
        $query = PersonnePolitique::query();
        
        if ($onlyMissing) {
            $query->whereNull('photo_url')
                  ->orWhereNull('wikipedia_extract');
        }
        
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('wikipedia_url')
                  ->orWhereNull('photo_url');
            });
        }

        $personnes = $query->limit($limit)->get();

        if ($personnes->isEmpty()) {
            $this->info('✅ Toutes les personnes sont déjà synchronisées');
            return Command::SUCCESS;
        }

        $this->info("📋 {$personnes->count()} personnes à traiter");
        $this->newLine();

        $bar = $this->output->createProgressBar($personnes->count());
        $bar->start();

        $stats = ['success' => 0, 'failed' => 0, 'photos' => 0];

        foreach ($personnes as $personne) {
            try {
                $result = $this->enrichFromWikipedia($personne);
                
                if ($result['success']) {
                    $stats['success']++;
                    if ($result['photo']) {
                        $stats['photos']++;
                    }
                } else {
                    $stats['failed']++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("   ⚠️ Erreur pour {$personne->nom_complet}: " . $e->getMessage());
                $stats['failed']++;
            }

            $bar->advance();
            
            // Pause pour éviter de surcharger Wikipedia
            usleep(500000); // 0.5s
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Synchronisation terminée !');
        $this->info("   → Succès : {$stats['success']}");
        $this->info("   → Échecs : {$stats['failed']}");
        $this->info("   → Photos récupérées : {$stats['photos']}");

        return Command::SUCCESS;
    }

    private function enrichFromWikipedia(PersonnePolitique $personne): array
    {
        // Construire le titre Wikipedia
        $wikiTitle = $this->buildWikipediaTitle($personne);
        
        // 1. Récupérer les infos de la page
        $pageInfo = $this->getWikipediaPageInfo($wikiTitle);
        
        if (!$pageInfo) {
            // Essayer avec une variante du nom
            $wikiTitle = $this->buildWikipediaTitle($personne, true);
            $pageInfo = $this->getWikipediaPageInfo($wikiTitle);
        }

        if (!$pageInfo) {
            return ['success' => false, 'photo' => false];
        }

        $updates = [];
        $photoFound = false;

        // URL Wikipedia
        if (!empty($pageInfo['fullurl'])) {
            $updates['wikipedia_url'] = $pageInfo['fullurl'];
        }

        // Extrait
        if (!empty($pageInfo['extract'])) {
            $updates['wikipedia_extract'] = Str::limit($pageInfo['extract'], 2000);
        }

        // 2. Récupérer la photo (image principale de l'infobox)
        if (empty($personne->photo_url) || $this->option('force')) {
            $photoUrl = $this->getWikipediaPhoto($wikiTitle);
            if ($photoUrl) {
                $updates['photo_url'] = $photoUrl;
                $photoFound = true;
            }
        }

        // Mettre à jour
        if (!empty($updates)) {
            $personne->update($updates);
        }

        return ['success' => true, 'photo' => $photoFound];
    }

    private function buildWikipediaTitle(PersonnePolitique $personne, bool $variant = false): string
    {
        $prenom = $personne->prenom;
        $nom = $personne->nom;

        // Gérer les cas spéciaux
        if ($variant) {
            // Essayer avec le nom complet sans accents
            $prenom = $this->removeAccents($prenom);
            $nom = $this->removeAccents($nom);
        }

        // Format: Prénom_Nom
        return str_replace(' ', '_', "{$prenom}_{$nom}");
    }

    private function getWikipediaPageInfo(string $title): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => self::USER_AGENT,
        ])->get(self::WIKIPEDIA_API, [
            'action' => 'query',
            'titles' => $title,
            'prop' => 'info|extracts',
            'inprop' => 'url',
            'exintro' => true,
            'explaintext' => true,
            'exsentences' => 5,
            'format' => 'json',
            'redirects' => 1,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $pages = $data['query']['pages'] ?? [];
        
        // Récupérer la première page (et pas la page -1 qui indique "not found")
        foreach ($pages as $pageId => $page) {
            if ($pageId > 0) {
                return $page;
            }
        }

        return null;
    }

    private function getWikipediaPhoto(string $title): ?string
    {
        // 1. Récupérer l'image principale de la page
        $response = Http::withHeaders([
            'User-Agent' => self::USER_AGENT,
        ])->get(self::WIKIPEDIA_API, [
            'action' => 'query',
            'titles' => $title,
            'prop' => 'pageimages',
            'pithumbsize' => 500, // Taille de la miniature
            'format' => 'json',
            'redirects' => 1,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $pages = $data['query']['pages'] ?? [];

        foreach ($pages as $page) {
            if (!empty($page['thumbnail']['source'])) {
                return $page['thumbnail']['source'];
            }
        }

        return null;
    }

    private function removeAccents(string $string): string
    {
        $unwanted = [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'À' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'Î' => 'I', 'Ï' => 'I',
            'Ô' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
            'ñ' => 'n', 'Ñ' => 'N',
        ];
        
        return strtr($string, $unwanted);
    }
}
