<?php

namespace App\Console\Commands;

use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnrichMinistresWikipedia extends Command
{
    protected $signature = 'ministres:enrich-wikipedia 
                            {--limit=0 : Nombre maximum de ministres à traiter (0 = tous)}
                            {--force : Remplacer les données existantes}';

    protected $description = 'Enrichit les fiches des ministres avec les données Wikipedia (bio, date naissance, profession, parti)';

    private array $moisFr = [
        'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4,
        'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8,
        'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12,
    ];

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Récupérer les ministres à enrichir
        $query = PersonnePolitique::whereHas('postes');
        
        if (!$force) {
            // Seulement ceux qui manquent d'infos
            $query->where(function ($q) {
                $q->whereNull('date_naissance')
                  ->orWhereNull('wikipedia_url')
                  ->orWhere('wikipedia_url', '')
                  ->orWhereNull('profession')
                  ->orWhere('profession', '');
            });
        }

        $personnes = $query->get();
        
        if ($limit > 0) {
            $personnes = $personnes->take($limit);
        }

        $this->info("🔍 {$personnes->count()} ministres à enrichir...\n");

        $enrichis = 0;
        $erreurs = 0;
        
        $bar = $this->output->createProgressBar($personnes->count());
        $bar->start();

        foreach ($personnes as $personne) {
            $data = $this->fetchWikipediaData($personne);
            
            if ($data) {
                $updates = [];
                
                // Date de naissance
                if (isset($data['date_naissance']) && ($force || !$personne->date_naissance)) {
                    try {
                        $updates['date_naissance'] = \Carbon\Carbon::parse($data['date_naissance'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Ignorer si la date n'est pas parsable
                    }
                }
                
                // Lieu de naissance
                if (isset($data['lieu_naissance']) && ($force || !$personne->lieu_naissance)) {
                    $updates['lieu_naissance'] = $data['lieu_naissance'];
                }
                
                // Profession
                if (isset($data['profession']) && ($force || !$personne->profession)) {
                    $updates['profession'] = $data['profession'];
                }
                
                // Parti politique
                if (isset($data['parti']) && ($force || !$personne->parti_politique)) {
                    $updates['parti_politique'] = $data['parti'];
                }
                
                // URL Wikipedia
                if (isset($data['wikipedia_url']) && ($force || !$personne->wikipedia_url)) {
                    $updates['wikipedia_url'] = $data['wikipedia_url'];
                }
                
                // Extrait Wikipedia (bio courte)
                if (isset($data['extract']) && ($force || !$personne->wikipedia_extract)) {
                    $updates['wikipedia_extract'] = $data['extract'];
                }
                
                if (!empty($updates)) {
                    try {
                        $personne->update($updates);
                        $enrichis++;
                    } catch (\Exception $e) {
                        $erreurs++;
                    }
                }
            } else {
                $erreurs++;
            }

            $bar->advance();
            usleep(300000); // 300ms entre chaque requête
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ {$enrichis} fiches enrichies");
        if ($erreurs > 0) {
            $this->warn("⚠️  {$erreurs} fiches non trouvées sur Wikipedia");
        }

        return Command::SUCCESS;
    }

    private function fetchWikipediaData(PersonnePolitique $personne): ?array
    {
        try {
            $nomRecherche = trim($personne->prenom . ' ' . $personne->nom);
            $pageName = str_replace(' ', '_', $nomRecherche);
            
            // 1. Récupérer l'extrait et les infos de base
            $apiUrl = "https://fr.wikipedia.org/w/api.php?" . http_build_query([
                'action' => 'query',
                'titles' => $pageName,
                'prop' => 'extracts|info|pageimages',
                'exintro' => true,
                'explaintext' => true,
                'exsentences' => 3,
                'inprop' => 'url',
                'pithumbsize' => 400,
                'format' => 'json',
            ]);

            $response = Http::timeout(10)
                ->withUserAgent('CivicDash/1.0 (https://demo.objectif2027.fr)')
                ->get($apiUrl);
            
            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];
            
            $result = [];
            
            foreach ($pages as $pageId => $page) {
                if ($pageId == -1) {
                    return null; // Page non trouvée
                }
                
                // URL Wikipedia
                if (isset($page['fullurl'])) {
                    $result['wikipedia_url'] = $page['fullurl'];
                }
                
                // Extrait
                if (isset($page['extract'])) {
                    $result['extract'] = $this->cleanExtract($page['extract']);
                }
                
                // Photo
                if (isset($page['thumbnail']['source']) && !$personne->photo_url) {
                    $result['photo_url'] = $page['thumbnail']['source'];
                }
            }
            
            // 2. Récupérer les données structurées depuis Wikidata
            $wikidataInfo = $this->fetchWikidataInfo($pageName);
            if ($wikidataInfo) {
                $result = array_merge($result, $wikidataInfo);
            }
            
            return !empty($result) ? $result : null;
            
        } catch (\Exception $e) {
            return null;
        }
    }

    private function fetchWikidataInfo(string $pageName): ?array
    {
        try {
            // Récupérer l'ID Wikidata depuis Wikipedia
            $apiUrl = "https://fr.wikipedia.org/w/api.php?" . http_build_query([
                'action' => 'query',
                'titles' => $pageName,
                'prop' => 'pageprops',
                'ppprop' => 'wikibase_item',
                'format' => 'json',
            ]);

            $response = Http::timeout(10)
                ->withUserAgent('CivicDash/1.0 (https://demo.objectif2027.fr)')
                ->get($apiUrl);
            
            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];
            
            $wikidataId = null;
            foreach ($pages as $page) {
                $wikidataId = $page['pageprops']['wikibase_item'] ?? null;
                break;
            }
            
            if (!$wikidataId) {
                return null;
            }
            
            // Récupérer les données Wikidata
            $wikidataUrl = "https://www.wikidata.org/wiki/Special:EntityData/{$wikidataId}.json";
            
            $response = Http::timeout(10)
                ->withUserAgent('CivicDash/1.0 (https://demo.objectif2027.fr)')
                ->get($wikidataUrl);
            
            if (!$response->successful()) {
                return null;
            }

            $wikidataData = $response->json();
            $entity = $wikidataData['entities'][$wikidataId] ?? null;
            
            if (!$entity) {
                return null;
            }
            
            $result = [];
            
            // Date de naissance (P569)
            $dateNaissance = $this->getWikidataValue($entity, 'P569');
            if ($dateNaissance) {
                $result['date_naissance'] = $this->parseWikidataDate($dateNaissance);
            }
            
            // Lieu de naissance (P19)
            $lieuNaissance = $this->getWikidataLabel($entity, 'P19');
            if ($lieuNaissance) {
                $result['lieu_naissance'] = $lieuNaissance;
            }
            
            // Profession/Occupation (P106)
            $profession = $this->getWikidataLabel($entity, 'P106');
            if ($profession) {
                $result['profession'] = $profession;
            }
            
            // Parti politique (P102)
            $parti = $this->getWikidataLabel($entity, 'P102');
            if ($parti) {
                $result['parti'] = $this->normalizeParti($parti);
            }
            
            return !empty($result) ? $result : null;
            
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getWikidataValue(array $entity, string $property): ?string
    {
        $claims = $entity['claims'][$property] ?? [];
        if (empty($claims)) {
            return null;
        }
        
        $claim = $claims[0];
        $datavalue = $claim['mainsnak']['datavalue'] ?? null;
        
        if (!$datavalue) {
            return null;
        }
        
        if ($datavalue['type'] === 'time') {
            return $datavalue['value']['time'] ?? null;
        }
        
        if ($datavalue['type'] === 'wikibase-entityid') {
            return $datavalue['value']['id'] ?? null;
        }
        
        return $datavalue['value'] ?? null;
    }

    private function getWikidataLabel(array $entity, string $property): ?string
    {
        $claims = $entity['claims'][$property] ?? [];
        if (empty($claims)) {
            return null;
        }
        
        $claim = $claims[0];
        $datavalue = $claim['mainsnak']['datavalue'] ?? null;
        
        if (!$datavalue || $datavalue['type'] !== 'wikibase-entityid') {
            return null;
        }
        
        $entityId = $datavalue['value']['id'] ?? null;
        if (!$entityId) {
            return null;
        }
        
        // Récupérer le label de l'entité
        try {
            $url = "https://www.wikidata.org/wiki/Special:EntityData/{$entityId}.json";
            $response = Http::timeout(5)
                ->withUserAgent('CivicDash/1.0 (https://demo.objectif2027.fr)')
                ->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                $label = $data['entities'][$entityId]['labels']['fr']['value'] ?? null;
                return $label;
            }
        } catch (\Exception $e) {
            // Ignorer
        }
        
        return null;
    }

    private function parseWikidataDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }
        
        // Format: "+1954-10-13T00:00:00Z"
        if (preg_match('/^\+?(\d{4})-(\d{2})-(\d{2})/', $date, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }
        
        return null;
    }

    private function cleanExtract(string $extract): string
    {
        // Convertir en UTF-8 valide
        $extract = mb_convert_encoding($extract, 'UTF-8', 'UTF-8');
        
        // Nettoyer l'extrait
        $extract = preg_replace('/\s+/', ' ', $extract);
        $extract = trim($extract);
        
        // Supprimer les caractères non-UTF8
        $extract = preg_replace('/[\x00-\x1F\x7F]/u', '', $extract);
        
        // Limiter la longueur (utiliser mb_substr pour les caractères multi-octets)
        if (mb_strlen($extract) > 500) {
            $extract = mb_substr($extract, 0, 497) . '...';
        }
        
        return $extract;
    }

    private function normalizeParti(string $parti): string
    {
        // Normaliser les noms de partis courants
        $mapping = [
            'La République en marche' => 'Renaissance',
            'La République en marche !' => 'Renaissance',
            'En Marche !' => 'Renaissance',
            'En Marche' => 'Renaissance',
            'Renaissance (parti politique)' => 'Renaissance',
            'Les Républicains' => 'LR',
            'Les Républicains (parti politique)' => 'LR',
            'Union pour un mouvement populaire' => 'LR',
            'Rassemblement pour la République' => 'RPR',
            'Parti socialiste' => 'PS',
            'Parti socialiste (France)' => 'PS',
            'Mouvement démocrate' => 'MoDem',
            'Mouvement démocrate (France)' => 'MoDem',
            'Europe Écologie Les Verts' => 'EELV',
            'Parti communiste français' => 'PCF',
            'La France insoumise' => 'LFI',
            'Rassemblement national' => 'RN',
            'Front national' => 'RN',
            'Union des démocrates et indépendants' => 'UDI',
        ];
        
        return $mapping[$parti] ?? $parti;
    }
}
