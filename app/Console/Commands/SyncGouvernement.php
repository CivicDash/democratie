<?php

namespace App\Console\Commands;

use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\Ministre;
use App\Models\Remaniement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncGouvernement extends Command
{
    protected $signature = 'sync:gouvernement 
                            {--force : Forcer le réimport}
                            {--dry-run : Mode simulation}';

    protected $description = 'Synchronisation de la composition du Gouvernement depuis info.gouv.fr';

    // SPARQL query pour Wikidata
    private const WIKIDATA_ENDPOINT = 'https://query.wikidata.org/sparql';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🏛️ Synchronisation du Gouvernement depuis Wikidata');
        $this->newLine();

        // 1. Requête SPARQL pour le gouvernement français actuel
        $this->info('📥 Requête Wikidata (gouvernement français actuel)...');
        
        try {
            $membres = $this->fetchFromWikidata();

            if (empty($membres)) {
                $this->warn('⚠️ Aucun membre trouvé via Wikidata.');
                $this->info('💡 Vérifiez manuellement : https://www.wikidata.org/wiki/Q16');
                return Command::FAILURE;
            }

            $this->info('   → ' . count($membres) . ' membres trouvés');
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 2. Afficher les résultats
        $this->table(
            ['#', 'Fonction', 'Nom', 'Photo'],
            collect($membres)->map(fn($m, $i) => [
                $i + 1,
                Str::limit($m['fonction'], 50),
                $m['nom'],
                $m['photo'] ? '✅' : '❌',
            ])->toArray()
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('🔄 Mode simulation - Aucune modification effectuée');
            return Command::SUCCESS;
        }

        // 3. Sauvegarder en base
        $this->info('💾 Enregistrement en base de données...');
        $this->saveGouvernement($membres, $force);

        $this->newLine();
        $this->info('✅ Synchronisation terminée !');

        return Command::SUCCESS;
    }

    private function fetchFromWikidata(): array
    {
        // Requête SPARQL pour les ministres français actuels
        // Q110935320 = Gouvernement Borne 2 (adapter selon le gouvernement actuel)
        // Q123456 = exemple pour gouvernement Bayrou/Lecornu
        $sparql = <<<'SPARQL'
SELECT DISTINCT ?person ?personLabel ?positionLabel ?image WHERE {
  # Chercher les personnes avec position de ministre en France
  ?person wdt:P31 wd:Q5 .
  ?person p:P39 ?statement .
  ?statement ps:P39 ?position .
  
  # Positions ministérielles françaises
  VALUES ?position {
    wd:Q1395070   # Premier ministre
    wd:Q4164871   # ministre
    wd:Q1937016   # ministre français
    wd:Q27175706  # secrétaire d'État français
  }
  
  # Pas de date de fin (toujours en fonction)
  FILTER NOT EXISTS { ?statement pq:P582 ?endDate }
  
  # Après 2024
  OPTIONAL { ?statement pq:P580 ?startDate }
  FILTER (!BOUND(?startDate) || ?startDate > "2024-01-01"^^xsd:dateTime)
  
  OPTIONAL { ?person wdt:P18 ?image }
  
  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en" }
}
ORDER BY ?positionLabel
LIMIT 100
SPARQL;

        $response = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/sparql-results+json',
                'User-Agent' => 'CivicDash/1.0 (https://civicdash.fr; democratie@civicdash.fr)',
            ])
            ->get(self::WIKIDATA_ENDPOINT, [
                'query' => $sparql,
                'format' => 'json',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erreur Wikidata: ' . $response->status());
        }

        $data = $response->json();
        $membres = [];

        foreach ($data['results']['bindings'] ?? [] as $row) {
            $membres[] = [
                'nom' => $row['personLabel']['value'] ?? 'Inconnu',
                'fonction' => $row['positionLabel']['value'] ?? 'Membre du gouvernement',
                'photo' => $row['image']['value'] ?? null,
                'wikidata_id' => basename($row['person']['value'] ?? ''),
            ];
        }

        return $membres;
    }

    private function parseGouvernement(string $html): array
    {
        $membres = [];

        // Utiliser DOMDocument pour parser le HTML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Chercher les blocs de ministres (structure info.gouv.fr)
        // Pattern 1: Chercher les articles/cards de ministres
        $ministerCards = $xpath->query("//article[contains(@class, 'minister')] | //div[contains(@class, 'minister')] | //li[contains(@class, 'minister')]");

        if ($ministerCards->length > 0) {
            foreach ($ministerCards as $card) {
                $membre = $this->extractMemberFromCard($card, $xpath);
                if ($membre) {
                    $membres[] = $membre;
                }
            }
        }

        // Pattern 2: Chercher les cartes avec structure nom + fonction
        if (empty($membres)) {
            $cards = $xpath->query("//div[contains(@class, 'card')] | //article | //section//div[.//h2 or .//h3]");
            
            foreach ($cards as $card) {
                $membre = $this->extractMemberFromGenericCard($card, $xpath);
                if ($membre) {
                    $membres[] = $membre;
                }
            }
        }

        // Pattern 3: Chercher les images avec alt contenant "ministre"
        if (empty($membres)) {
            $images = $xpath->query("//img[contains(@alt, 'Ministre') or contains(@alt, 'ministre') or contains(@alt, 'Secrétaire')]");
            
            foreach ($images as $img) {
                $alt = $img->getAttribute('alt');
                $src = $img->getAttribute('src');
                
                // Essayer d'extraire nom et fonction de l'alt
                if (preg_match('/^(.+?)\s*[-–]\s*(.+)$/u', $alt, $matches)) {
                    $membres[] = [
                        'nom' => trim($matches[1]),
                        'fonction' => trim($matches[2]),
                        'photo' => $this->normalizePhotoUrl($src),
                    ];
                } elseif (preg_match('/^(Ministre|Secrétaire).+$/u', $alt)) {
                    // L'alt est la fonction, chercher le nom à proximité
                    $parent = $img->parentNode;
                    $nomNode = $xpath->query(".//span | .//p | .//h3", $parent)->item(0);
                    if ($nomNode) {
                        $membres[] = [
                            'nom' => trim($nomNode->textContent),
                            'fonction' => $alt,
                            'photo' => $this->normalizePhotoUrl($src),
                        ];
                    }
                }
            }
        }

        // Dédupliquer et nettoyer
        $membres = collect($membres)
            ->filter(fn($m) => !empty($m['nom']) && strlen($m['nom']) > 2)
            ->unique('nom')
            ->values()
            ->toArray();

        return $membres;
    }

    private function extractMemberFromCard(\DOMNode $card, \DOMXPath $xpath): ?array
    {
        // Chercher le nom
        $nameNode = $xpath->query(".//h2 | .//h3 | .//span[contains(@class, 'name')] | .//p[contains(@class, 'name')]", $card)->item(0);
        $name = $nameNode ? trim($nameNode->textContent) : null;

        // Chercher la fonction
        $functionNode = $xpath->query(".//p[contains(@class, 'function')] | .//span[contains(@class, 'title')] | .//p[not(contains(@class, 'name'))]", $card)->item(0);
        $function = $functionNode ? trim($functionNode->textContent) : null;

        // Chercher la photo
        $imgNode = $xpath->query(".//img", $card)->item(0);
        $photo = $imgNode ? $imgNode->getAttribute('src') : null;

        if ($name && $function && strlen($name) > 2) {
            return [
                'nom' => $name,
                'fonction' => $function,
                'photo' => $this->normalizePhotoUrl($photo),
            ];
        }

        return null;
    }

    private function extractMemberFromGenericCard(\DOMNode $card, \DOMXPath $xpath): ?array
    {
        $text = trim($card->textContent);
        
        // Filtrer les blocs trop longs ou trop courts
        if (strlen($text) < 10 || strlen($text) > 500) {
            return null;
        }

        // Chercher un pattern "Prénom Nom" suivi d'une fonction
        $lines = preg_split('/\n+/', $text);
        $lines = array_filter($lines, fn($l) => strlen(trim($l)) > 2);
        $lines = array_values($lines);

        if (count($lines) >= 2) {
            // Première ligne = nom, deuxième = fonction ?
            $potentialName = trim($lines[0]);
            $potentialFunction = trim($lines[1]);

            // Vérifier que le nom ressemble à un nom (2-4 mots, pas trop long)
            if (preg_match('/^[A-ZÀ-Ü][a-zà-ü]+(\s+[A-ZÀ-Ü][a-zà-ü]+){1,3}$/', $potentialName)) {
                // Chercher une photo
                $imgNode = $xpath->query(".//img", $card)->item(0);
                $photo = $imgNode ? $imgNode->getAttribute('src') : null;

                return [
                    'nom' => $potentialName,
                    'fonction' => $potentialFunction,
                    'photo' => $this->normalizePhotoUrl($photo),
                ];
            }
        }

        return null;
    }

    private function normalizePhotoUrl(?string $url): ?string
    {
        if (!$url) return null;
        
        // Convertir en URL absolue si relative
        if (str_starts_with($url, '/')) {
            $url = 'https://www.info.gouv.fr' . $url;
        }
        
        // Ignorer les placeholders
        if (str_contains($url, 'placeholder') || str_contains($url, 'default')) {
            return null;
        }

        return $url;
    }

    private function saveGouvernement(array $membres, bool $force): void
    {
        // Créer ou récupérer le gouvernement actuel
        $gouvernement = Gouvernement::firstOrCreate(
            ['actif' => true],
            [
                'nom' => 'Gouvernement Bayrou',
                'premier_ministre' => 'François Bayrou',
                'president' => 'Emmanuel Macron',
                'date_debut' => '2024-12-23', // Date officielle de nomination
                'numero' => 4,
                'legislature' => '17',
            ]
        );

        if ($force) {
            // Supprimer les anciens ministres
            Ministre::where('gouvernement_id', $gouvernement->id)->delete();
            Ministere::where('gouvernement_id', $gouvernement->id)->delete();
        }

        // Désactiver les anciens ministres
        Ministre::where('gouvernement_id', $gouvernement->id)->update(['actif' => false]);

        $ordre = 1;
        foreach ($membres as $membre) {
            // Identifier le type de fonction
            $type = $this->identifyFunctionType($membre['fonction']);
            
            // Créer ou trouver le ministère
            $ministere = Ministere::firstOrCreate(
                [
                    'gouvernement_id' => $gouvernement->id,
                    'nom' => $this->extractMinistereFromFonction($membre['fonction']),
                ],
                [
                    'sigle' => $this->generateSigle($membre['fonction']),
                    'type' => $type === 'premier_ministre' ? 'ministere' : $type,
                    'ordre' => $ordre,
                    'couleur' => Ministere::getCouleurDefaut($membre['fonction']),
                    'actif' => true,
                ]
            );

            // Extraire prénom/nom
            [$prenom, $nom] = $this->splitName($membre['nom']);

            // Créer le ministre
            Ministre::updateOrCreate(
                [
                    'gouvernement_id' => $gouvernement->id,
                    'prenom' => $prenom,
                    'nom' => $nom,
                ],
                [
                    'ministere_id' => $ministere->id,
                    'fonction' => $membre['fonction'],
                    'type_fonction' => $type,
                    'date_debut' => $gouvernement->date_debut,
                    'actif' => true,
                    'photo_url' => $membre['photo'],
                ]
            );

            $ordre++;
        }

        // Stats finales
        $this->info('   → Gouvernement: ' . $gouvernement->nom);
        $this->info('   → Ministères: ' . Ministere::where('gouvernement_id', $gouvernement->id)->count());
        $this->info('   → Ministres actifs: ' . Ministre::where('gouvernement_id', $gouvernement->id)->where('actif', true)->count());
    }

    private function identifyFunctionType(string $fonction): string
    {
        $fonctionLower = strtolower($fonction);

        if (str_contains($fonctionLower, 'premier ministre')) {
            return 'premier_ministre';
        }
        if (str_contains($fonctionLower, 'secrétaire d\'état') || str_contains($fonctionLower, "secrétaire d'état")) {
            return 'secretaire_etat';
        }
        if (str_contains($fonctionLower, 'ministre délégué') || str_contains($fonctionLower, 'ministre déléguée')) {
            return 'ministre_delegue';
        }
        return 'ministre';
    }

    private function extractMinistereFromFonction(string $fonction): string
    {
        // Nettoyer la fonction pour en extraire le nom du ministère
        $fonction = preg_replace('/^(Ministre|Secrétaire d\'État|Ministre délégué[e]?)\s*(de la|du|des|de l\'|auprès|,|chargé[e]? de)?\s*/iu', '', $fonction);
        return trim($fonction) ?: $fonction;
    }

    private function generateSigle(string $fonction): string
    {
        // Générer un sigle à partir de la fonction
        $mots = preg_split('/[\s,]+/', $fonction);
        $mots = array_filter($mots, fn($m) => strlen($m) > 3 && !in_array(strtolower($m), ['de', 'la', 'le', 'les', 'du', 'des', 'et', 'auprès', 'chargé', 'chargée']));
        
        $sigle = '';
        foreach (array_slice($mots, 0, 4) as $mot) {
            $sigle .= strtoupper(mb_substr($mot, 0, 1));
        }
        
        return $sigle ?: 'MIN';
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName));
        
        if (count($parts) === 1) {
            return ['', $parts[0]];
        }
        
        // Généralement: Prénom Nom ou Prénom Prénom Nom
        // Le dernier mot est souvent le nom de famille
        $nom = array_pop($parts);
        $prenom = implode(' ', $parts);
        
        return [$prenom, $nom];
    }
}
