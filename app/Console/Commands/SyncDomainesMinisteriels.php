<?php

namespace App\Console\Commands;

use App\Models\DomaineMinisteriel;
use App\Models\PosteMinisteriel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncDomainesMinisteriels extends Command
{
    protected $signature = 'sync:domaines-ministeriels 
                            {--init : Initialiser les domaines standards}
                            {--link : Lier les postes existants aux domaines}
                            {--enrich : Enrichir avec Wikipedia}';

    protected $description = 'Synchroniser les domaines ministériels et lier les postes existants';

    public function handle(): int
    {
        if ($this->option('init')) {
            $this->initDomainesStandards();
        }

        if ($this->option('link')) {
            $this->linkPostesToDomaines();
        }

        if ($this->option('enrich')) {
            $this->enrichFromWikipedia();
        }

        if (!$this->option('init') && !$this->option('link') && !$this->option('enrich')) {
            $this->info('Options disponibles :');
            $this->info('  --init   : Créer les domaines ministériels standards');
            $this->info('  --link   : Lier les postes ministériels aux domaines');
            $this->info('  --enrich : Enrichir les domaines depuis Wikipedia');
        }

        return Command::SUCCESS;
    }

    private function initDomainesStandards(): void
    {
        $this->info('📂 Initialisation des domaines ministériels standards...');

        $domaines = DomaineMinisteriel::getDomainesStandards();
        $created = 0;

        foreach ($domaines as $data) {
            $domaine = DomaineMinisteriel::updateOrCreate(
                ['slug' => Str::slug($data['nom'])],
                array_merge($data, ['slug' => Str::slug($data['nom'])])
            );

            if ($domaine->wasRecentlyCreated) {
                $this->line("  ✅ {$data['nom']}");
                $created++;
            } else {
                $this->line("  🔄 {$data['nom']} (mis à jour)");
            }
        }

        $this->info("✅ {$created} domaines créés, " . (count($domaines) - $created) . " mis à jour");
    }

    private function linkPostesToDomaines(): void
    {
        $this->info('🔗 Liaison des postes ministériels aux domaines...');

        $domaines = DomaineMinisteriel::all();
        $postesNonLies = PosteMinisteriel::whereNull('domaine_ministeriel_id')->get();
        $linked = 0;
        $notFound = [];

        // Mots-clés pour chaque domaine
        $keywords = [
            'intérieur' => ['intérieur', 'police', 'sécurité intérieure', 'gendarmerie'],
            'affaires-etrangeres' => ['affaires étrangères', 'europe', 'diplomatie', 'européennes', 'francophonie'],
            'justice' => ['justice', 'garde des sceaux', 'libertés'],
            'armees' => ['armées', 'défense', 'anciens combattants', 'forces armées'],
            'economie-et-finances' => ['économie', 'finances', 'budget', 'comptes publics', 'commerce extérieur', 'industrie', 'numérique', 'souveraineté industrielle'],
            'education-nationale' => ['éducation nationale', 'enseignement scolaire'],
            'enseignement-superieur-et-recherche' => ['enseignement supérieur', 'recherche', 'universités', 'innovation'],
            'sante' => ['santé', 'sécurité sociale', 'hôpitaux', 'prévention'],
            'travail-et-emploi' => ['travail', 'emploi', 'formation professionnelle', 'insertion'],
            'transition-ecologique' => ['écologie', 'environnement', 'transition écologique', 'biodiversité', 'énergie', 'climat', 'transports', 'mer'],
            'agriculture' => ['agriculture', 'alimentation', 'pêche', 'forêts', 'ruralité'],
            'culture' => ['culture', 'communication', 'médias'],
            'sports' => ['sports', 'jeux olympiques', 'paralympiques'],
            'outre-mer' => ['outre-mer', 'ultramarin'],
            'cohesion-des-territoires' => ['cohésion des territoires', 'logement', 'ville', 'aménagement du territoire', 'décentralisation', 'collectivités'],
            'solidarites' => ['solidarités', 'famille', 'autonomie', 'personnes âgées', 'handicap', 'enfance', 'égalité femmes-hommes'],
        ];

        $this->output->progressStart($postesNonLies->count());

        foreach ($postesNonLies as $poste) {
            $fonctionLower = strtolower($poste->fonction);
            $found = false;

            foreach ($keywords as $slug => $kws) {
                foreach ($kws as $kw) {
                    if (str_contains($fonctionLower, $kw)) {
                        $domaine = $domaines->firstWhere('slug', $slug);
                        if ($domaine) {
                            $poste->update(['domaine_ministeriel_id' => $domaine->id]);
                            $linked++;
                            $found = true;
                            break 2;
                        }
                    }
                }
            }

            if (!$found && $poste->type_fonction !== 'premier_ministre') {
                $notFound[] = $poste->fonction;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("✅ {$linked} postes liés à leur domaine");

        if (count($notFound) > 0) {
            $this->warn("⚠️  " . count($notFound) . " postes non classés :");
            $uniqueNotFound = array_unique($notFound);
            foreach (array_slice($uniqueNotFound, 0, 20) as $fonction) {
                $this->line("   - {$fonction}");
            }
            if (count($uniqueNotFound) > 20) {
                $this->line("   ... et " . (count($uniqueNotFound) - 20) . " autres");
            }
        }
    }

    private function enrichFromWikipedia(): void
    {
        $this->info('📚 Enrichissement depuis Wikipedia...');

        $domaines = DomaineMinisteriel::whereNotNull('wikipedia_url')
            ->where(function($q) {
                $q->whereNull('wikipedia_extract')
                  ->orWhere('wikipedia_extract', '');
            })
            ->get();

        $enriched = 0;

        foreach ($domaines as $domaine) {
            $this->line("  🔍 {$domaine->nom}...");

            try {
                // Extraire le titre de la page Wikipedia
                $url = $domaine->wikipedia_url;
                preg_match('/wikipedia\.org\/wiki\/(.+)$/', $url, $matches);
                if (empty($matches[1])) {
                    $this->warn("    ⚠️  URL invalide");
                    continue;
                }

                $title = urldecode($matches[1]);

                // Appeler l'API Wikipedia avec Http de Laravel
                $apiUrl = "https://fr.wikipedia.org/api/rest_v1/page/summary/" . rawurlencode($title);
                $response = Http::withUserAgent('CivicDash/1.0 (contact@civicdash.fr)')
                    ->timeout(10)
                    ->get($apiUrl);
                    
                if (!$response->successful()) {
                    $this->warn("    ⚠️  Erreur HTTP: " . $response->status());
                    continue;
                }

                $data = $response->json();
                if (!$data || empty($data['extract'])) {
                    $this->warn("    ⚠️  Pas d'extrait");
                    continue;
                }

                $domaine->update([
                    'wikipedia_extract' => $data['extract'],
                    'description' => $domaine->description ?: substr($data['extract'], 0, 500),
                ]);

                $this->line("    ✅ Enrichi");
                $enriched++;

                usleep(200000); // 200ms entre les requêtes
            } catch (\Exception $e) {
                $this->error("    ❌ Erreur: " . $e->getMessage());
            }
        }

        $this->info("✅ {$enriched} domaines enrichis");
    }
}
