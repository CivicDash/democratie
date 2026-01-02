<?php

namespace App\Console\Commands;

use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\PersonnePolitique;
use App\Models\PosteMinisteriel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportGouvernementWikipedia extends Command
{
    protected $signature = 'import:gouvernement-wikipedia 
                            {url : URL Wikipedia du gouvernement (ex: https://fr.wikipedia.org/wiki/Gouvernement_Bayrou)}
                            {--dry-run : Afficher les données sans les importer}
                            {--with-photos : Récupérer les photos des ministres depuis leurs pages Wikipedia}';

    protected $description = 'Importe les données d\'un gouvernement depuis Wikipedia';

    // Mapping des types de fonction
    private array $typeFonctionMapping = [
        'premier ministre' => 'premier_ministre',
        'ministre d\'état' => 'ministre_etat',
        'ministres d\'état' => 'ministre_etat',
        'ministre' => 'ministre',
        'ministres' => 'ministre',
        'ministre délégué' => 'ministre_delegue',
        'ministres délégués' => 'ministre_delegue',
        'secrétaire d\'état' => 'secretaire_etat',
        'secrétaires d\'état' => 'secretaire_etat',
    ];

    public function handle(): int
    {
        $url = $this->argument('url');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Récupération de la page Wikipedia : {$url}");

        // Extraire le titre de la page depuis l'URL
        $pageName = basename(parse_url($url, PHP_URL_PATH));
        
        // Utiliser l'API Wikipedia pour récupérer le contenu
        $apiUrl = "https://fr.wikipedia.org/w/api.php?" . http_build_query([
            'action' => 'parse',
            'page' => urldecode($pageName),
            'format' => 'json',
            'prop' => 'text|wikitext',
        ]);

        $response = Http::timeout(30)
            ->withUserAgent('CivicDash/1.0 (https://demo.objectif2027.fr)')
            ->get($apiUrl);
        
        if (!$response->successful()) {
            $this->error("❌ Impossible de récupérer la page Wikipedia");
            return Command::FAILURE;
        }

        $data = $response->json();
        
        if (isset($data['error'])) {
            $this->error("❌ Erreur Wikipedia : " . $data['error']['info']);
            return Command::FAILURE;
        }

        $html = $data['parse']['text']['*'] ?? '';
        $wikitext = $data['parse']['wikitext']['*'] ?? '';
        $title = $data['parse']['title'] ?? $pageName;

        $this->info("📄 Page : {$title}");

        // Parser le wikitext pour extraire les informations de l'infobox (plus fiable)
        $gouvernementData = $this->parseGouvernementWikitext($wikitext, $html, $title);
        
        // Dédupliquer les ministres par nom
        $ministresUniques = [];
        foreach ($gouvernementData['ministres'] as $m) {
            $key = Str::slug($m['nom']);
            if (!isset($ministresUniques[$key])) {
                $ministresUniques[$key] = $m;
            }
        }
        $gouvernementData['ministres'] = array_values($ministresUniques);

        if ($dryRun) {
            $this->info("\n📋 Données extraites (dry-run) :");
            $this->displayExtractedData($gouvernementData);
            return Command::SUCCESS;
        }

        // Importer les données
        $this->importGouvernement($gouvernementData);

        $this->info("\n✅ Import terminé !");
        return Command::SUCCESS;
    }

    private function parseGouvernementWikitext(string $wikitext, string $html, string $title): array
    {
        $data = [
            'nom' => $this->extractGouvernementName($title),
            'premier_ministre' => null,
            'president' => null,
            'date_debut' => null,
            'date_fin' => null,
            'ministres' => [],
        ];

        // Extraire les paramètres de l'infobox depuis le wikitext
        // Pattern pour "| parametre = valeur"
        $patterns = [
            'president' => '/\|\s*président\s*=\s*\[\[([^\]|]+)/iu',
            'premier_ministre_chef' => '/\|\s*chef\s*=\s*\[\[([^\]|]+)/iu',
            'premier_ministre_label' => '/\|\s*premier[_ ]ministre\s*=\s*\[\[([^\]|]+)/iu',
            'date_debut' => '/\|\s*(?:début|formation)\s*=\s*([^\n|]+)/iu',
            'date_fin' => '/\|\s*fin\s*=\s*([^\n|]+)/iu',
        ];

        // Extraire le président
        if (preg_match($patterns['president'], $wikitext, $match)) {
            $data['president'] = $this->cleanName($match[1]);
            $this->info("  📌 Président trouvé : {$data['president']}");
        }

        // Extraire le Premier ministre (essayer "chef" puis "premier ministre")
        if (preg_match($patterns['premier_ministre_chef'], $wikitext, $match)) {
            $data['premier_ministre'] = $this->cleanName($match[1]);
            $this->info("  📌 Premier ministre trouvé (chef) : {$data['premier_ministre']}");
        } elseif (preg_match($patterns['premier_ministre_label'], $wikitext, $match)) {
            $data['premier_ministre'] = $this->cleanName($match[1]);
            $this->info("  📌 Premier ministre trouvé : {$data['premier_ministre']}");
        }

        // Extraire les dates
        if (preg_match($patterns['date_debut'], $wikitext, $match)) {
            $dateStr = trim($match[1]);
            $data['date_debut'] = $this->parseDate($dateStr);
            if ($data['date_debut']) {
                $this->info("  📅 Date début trouvée : {$data['date_debut']} (depuis '{$dateStr}')");
            }
        }

        if (preg_match($patterns['date_fin'], $wikitext, $match)) {
            $dateStr = trim($match[1]);
            $data['date_fin'] = $this->parseDate($dateStr);
            if ($data['date_fin']) {
                $this->info("  📅 Date fin trouvée : {$data['date_fin']} (depuis '{$dateStr}')");
            }
        }

        // Si pas de données depuis wikitext, essayer HTML comme fallback
        if (!$data['premier_ministre'] || !$data['date_debut']) {
            $htmlData = $this->parseGouvernementHtmlFallback($html);
            $data['premier_ministre'] = $data['premier_ministre'] ?? $htmlData['premier_ministre'];
            $data['president'] = $data['president'] ?? $htmlData['president'];
            $data['date_debut'] = $data['date_debut'] ?? $htmlData['date_debut'];
            $data['date_fin'] = $data['date_fin'] ?? $htmlData['date_fin'];
        }

        // Extraire les ministres depuis le HTML (les tableaux sont plus faciles à parser en HTML)
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        
        $data['ministres'] = $this->parseMinistresFromTables($xpath, $dom);

        return $data;
    }

    private function parseGouvernementHtmlFallback(string $html): array
    {
        $data = [
            'premier_ministre' => null,
            'president' => null,
            'date_debut' => null,
            'date_fin' => null,
        ];

        // Utiliser DOMDocument pour parser le HTML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Extraire les infos de l'infobox
        $infobox = $xpath->query("//table[contains(@class, 'infobox')]")->item(0);
        if ($infobox) {
            $data = array_merge($data, $this->parseInfobox($xpath, $infobox));
        }

        return $data;
    }

    private function extractGouvernementName(string $title): string
    {
        // "Gouvernement Bayrou" -> "Bayrou"
        return preg_replace('/^Gouvernement\s+/', '', $title);
    }

    private function parseInfobox(\DOMXPath $xpath, \DOMNode $infobox): array
    {
        $data = [];
        
        $rows = $xpath->query(".//tr", $infobox);
        foreach ($rows as $row) {
            $th = $xpath->query(".//th", $row)->item(0);
            $td = $xpath->query(".//td", $row)->item(0);
            
            if (!$th || !$td) continue;
            
            $label = strtolower(trim($th->textContent));
            $value = trim($td->textContent);
            
            if (str_contains($label, 'président')) {
                $data['president'] = $this->cleanName($value);
            } elseif (str_contains($label, 'premier ministre')) {
                $data['premier_ministre'] = $this->cleanName($value);
            } elseif (str_contains($label, 'formation') || str_contains($label, 'début')) {
                $data['date_debut'] = $this->parseDate($value);
            } elseif (str_contains($label, 'fin') && !str_contains($label, 'affaires')) {
                $data['date_fin'] = $this->parseDate($value);
            }
        }
        
        return $data;
    }

    private function parseMinistresFromTables(\DOMXPath $xpath, \DOMDocument $dom): array
    {
        $ministres = [];
        $currentType = 'ministre';

        // Chercher les titres de sections et les tableaux qui suivent
        $headings = $xpath->query("//h2|//h3|//h4|//table[contains(@class, 'wikitable')]");
        
        foreach ($headings as $node) {
            if ($node->nodeName === 'h2' || $node->nodeName === 'h3' || $node->nodeName === 'h4') {
                $headingText = strtolower(trim($node->textContent));
                
                foreach ($this->typeFonctionMapping as $keyword => $type) {
                    if (str_contains($headingText, $keyword)) {
                        $currentType = $type;
                        break;
                    }
                }
            } elseif ($node->nodeName === 'table') {
                $tableMinistres = $this->parseMinistresTable($xpath, $node, $currentType);
                $ministres = array_merge($ministres, $tableMinistres);
            }
        }

        // Si pas de tableaux trouvés, essayer de parser les listes
        if (empty($ministres)) {
            $ministres = $this->parseMinistresFromLists($xpath);
        }

        return $ministres;
    }

    private function parseMinistresTable(\DOMXPath $xpath, \DOMNode $table, string $defaultType): array
    {
        $ministres = [];
        $rows = $xpath->query(".//tr", $table);
        
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip header row
            
            $cells = $xpath->query(".//td", $row);
            if ($cells->length < 2) continue;
            
            // Essayer d'extraire nom et fonction
            $nom = null;
            $fonction = null;
            
            foreach ($cells as $cell) {
                $text = trim($cell->textContent);
                
                // Chercher un lien qui pourrait être le nom
                $links = $xpath->query(".//a", $cell);
                foreach ($links as $link) {
                    $linkText = trim($link->textContent);
                    if ($this->looksLikeName($linkText)) {
                        $nom = $this->cleanName($linkText);
                        break;
                    }
                }
                
                // Si pas de lien, utiliser le texte
                if (!$nom && $this->looksLikeName($text)) {
                    $nom = $this->cleanName($text);
                } elseif (!$fonction && strlen($text) > 5 && !$this->looksLikeName($text)) {
                    $fonction = $this->cleanFonction($text);
                }
            }
            
            if ($nom) {
                $ministres[] = [
                    'nom' => $nom,
                    'fonction' => $fonction ?? 'Ministre',
                    'type_fonction' => $defaultType,
                ];
            }
        }
        
        return $ministres;
    }

    private function parseMinistresFromLists(\DOMXPath $xpath): array
    {
        $ministres = [];
        $currentType = 'ministre';
        
        // Chercher les sections avec des listes de ministres
        $sections = $xpath->query("//h2/span[@class='mw-headline']|//h3/span[@class='mw-headline']|//ul/li");
        
        foreach ($sections as $node) {
            if ($node->parentNode->nodeName === 'h2' || $node->parentNode->nodeName === 'h3') {
                $text = strtolower(trim($node->textContent));
                foreach ($this->typeFonctionMapping as $keyword => $type) {
                    if (str_contains($text, $keyword)) {
                        $currentType = $type;
                        break;
                    }
                }
            } elseif ($node->nodeName === 'li') {
                $text = trim($node->textContent);
                
                // Pattern: "Fonction : Nom" ou "Nom, fonction"
                if (preg_match('/^([^:]+)\s*:\s*(.+)$/u', $text, $matches)) {
                    $fonction = $this->cleanFonction($matches[1]);
                    $nom = $this->cleanName($matches[2]);
                    
                    if ($this->looksLikeName($nom)) {
                        $ministres[] = [
                            'nom' => $nom,
                            'fonction' => $fonction,
                            'type_fonction' => $currentType,
                        ];
                    }
                }
            }
        }
        
        return $ministres;
    }

    private function looksLikeName(string $text): bool
    {
        // Un nom a généralement 2-4 mots, commence par une majuscule
        $words = preg_split('/\s+/', trim($text));
        if (count($words) < 2 || count($words) > 5) return false;
        
        // Vérifier que ça ne contient pas de mots-clés de fonction
        $lowerText = strtolower($text);
        $functionKeywords = ['ministre', 'secrétaire', 'délégué', 'chargé', 'auprès', 'économie', 'intérieur', 'justice'];
        foreach ($functionKeywords as $keyword) {
            if (str_contains($lowerText, $keyword)) return false;
        }
        
        // Exclure les noms de partis et autres faux positifs
        $exclusions = [
            'les républicains', 'mouvement démocrate', 'renaissance', 'horizons', 'parti socialiste',
            'union des démocrates', 'fédération progressiste', 'parti radical', 'divers gauche',
            'divers droite', 'sans étiquette', 'répartition', 'coalition', 'composition',
        ];
        foreach ($exclusions as $exclusion) {
            if (str_contains($lowerText, $exclusion)) return false;
        }
        
        // Exclure les dates
        if (preg_match('/\d{1,2}\s+(janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)\s+\d{4}/i', $text)) {
            return false;
        }
        
        return preg_match('/^[A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ]/u', $text) === 1;
    }

    private function cleanName(string $name): string
    {
        // Nettoyer le nom
        $name = preg_replace('/\[.*?\]/', '', $name); // Supprimer les références [1], [2]
        $name = preg_replace('/\(.*?\)/', '', $name); // Supprimer les parenthèses
        $name = trim($name);
        
        // Supprimer les titres
        $name = preg_replace('/^(M\.|Mme|Dr|Pr)\s+/i', '', $name);
        
        return $name;
    }

    private function cleanFonction(string $fonction): string
    {
        $fonction = preg_replace('/\[.*?\]/', '', $fonction);
        $fonction = preg_replace('/modifier.*$/i', '', $fonction);
        return trim($fonction);
    }

    private function parseDate(string $dateStr): ?string
    {
        // Nettoyer
        $dateStr = preg_replace('/\[.*?\]/', '', $dateStr);
        $dateStr = preg_replace('/\{\{[^}]+\}\}/', '', $dateStr); // Supprimer les templates wiki
        $dateStr = trim($dateStr);
        
        // Pattern 1: "DD-MM-YYYY" ou "DD/MM/YYYY"
        if (preg_match('/(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $dateStr, $matches)) {
            $jour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mois = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $annee = $matches[3];
            return "{$annee}-{$mois}-{$jour}";
        }
        
        // Pattern 2: "YYYY-MM-DD" (format ISO)
        if (preg_match('/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $dateStr, $matches)) {
            $annee = $matches[1];
            $mois = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $jour = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            return "{$annee}-{$mois}-{$jour}";
        }
        
        // Patterns français avec mois en texte
        $moisMapping = [
            'janvier' => '01', 'février' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'août' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'décembre' => '12',
        ];
        
        // Pattern 3: "14 mai 2017"
        if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/u', $dateStr, $matches)) {
            $jour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $moisNom = strtolower($matches[2]);
            $annee = $matches[3];
            
            if (isset($moisMapping[$moisNom])) {
                return "{$annee}-{$moisMapping[$moisNom]}-{$jour}";
            }
        }
        
        // Pattern 4: "mai 2017" (juste mois et année)
        if (preg_match('/(\w+)\s+(\d{4})/u', $dateStr, $matches)) {
            $moisNom = strtolower($matches[1]);
            $annee = $matches[2];
            
            if (isset($moisMapping[$moisNom])) {
                return "{$annee}-{$moisMapping[$moisNom]}-01";
            }
        }
        
        return null;
    }

    private function displayExtractedData(array $data): void
    {
        $this->table(['Champ', 'Valeur'], [
            ['Nom', $data['nom'] ?? 'N/A'],
            ['Premier ministre', $data['premier_ministre'] ?? 'N/A'],
            ['Président', $data['president'] ?? 'N/A'],
            ['Date début', $data['date_debut'] ?? 'N/A'],
            ['Date fin', $data['date_fin'] ?? 'En cours'],
            ['Nb ministres', count($data['ministres'])],
        ]);

        if (!empty($data['ministres'])) {
            $this->info("\n👔 Ministres trouvés :");
            $this->table(
                ['Nom', 'Fonction', 'Type'],
                array_map(fn($m) => [$m['nom'], Str::limit($m['fonction'], 50), $m['type_fonction']], $data['ministres'])
            );
        }
    }

    private function importGouvernement(array $data): void
    {
        $this->info("\n📥 Import en cours...");

        // Extraire le Premier ministre depuis la liste des ministres si pas trouvé dans l'infobox
        $premierMinistre = $data['premier_ministre'];
        if (!$premierMinistre) {
            foreach ($data['ministres'] as $m) {
                if ($m['type_fonction'] === 'premier_ministre') {
                    $premierMinistre = $m['nom'];
                    break;
                }
            }
        }
        
        // Fallback sur le nom du gouvernement
        if (!$premierMinistre) {
            $premierMinistre = $data['nom'];
        }

        // Fallback pour date_debut si non trouvée
        $dateDebut = $data['date_debut'] ?? now()->format('Y-m-d');
        if (!$data['date_debut']) {
            $this->warn("⚠️  Date de début non trouvée, utilisation de la date du jour. Pensez à la corriger manuellement.");
        }

        // Créer ou mettre à jour le gouvernement
        $gouvernement = Gouvernement::updateOrCreate(
            ['nom' => $data['nom']],
            [
                'premier_ministre' => $premierMinistre,
                'president' => $data['president'] ?? 'Emmanuel Macron',
                'date_debut' => $dateDebut,
                'date_fin' => $data['date_fin'],
                'actif' => $data['date_fin'] === null,
            ]
        );

        $this->info("✅ Gouvernement : {$gouvernement->nom} (ID: {$gouvernement->id})");

        $ordre = 0;
        $withPhotos = $this->option('with-photos');
        $photosRecuperees = 0;
        
        foreach ($data['ministres'] as $ministreData) {
            $ordre++;
            
            // Trouver ou créer la personne
            $parts = $this->splitName($ministreData['nom']);
            
            $personne = PersonnePolitique::firstOrCreate(
                ['slug' => Str::slug($ministreData['nom'])],
                [
                    'prenom' => $parts['prenom'],
                    'nom' => $parts['nom'],
                    'civilite' => $this->guessCivilite($parts['prenom']),
                ]
            );

            // Récupérer la photo si demandé et si la personne n'en a pas déjà une
            if ($withPhotos && empty($personne->photo_url)) {
                $photoUrl = $this->fetchWikipediaPhoto($ministreData['nom']);
                if ($photoUrl) {
                    $personne->update(['photo_url' => $photoUrl]);
                    $photosRecuperees++;
                    $this->line("  📷 Photo trouvée pour {$ministreData['nom']}");
                }
            }

            // Créer le poste
            PosteMinisteriel::updateOrCreate(
                [
                    'gouvernement_id' => $gouvernement->id,
                    'personne_id' => $personne->id,
                ],
                [
                    'fonction' => $ministreData['fonction'],
                    'type_fonction' => $ministreData['type_fonction'],
                    'ordre' => $ordre,
                    'date_debut' => $gouvernement->date_debut,
                    'date_fin' => $gouvernement->date_fin,
                ]
            );

            $this->line("  → {$personne->nom_complet} : {$ministreData['fonction']}");
        }

        $this->info("📊 {$ordre} postes importés");
        if ($withPhotos) {
            $this->info("📷 {$photosRecuperees} photos récupérées");
        }
    }

    /**
     * Récupère la photo d'une personne depuis sa page Wikipedia
     */
    private function fetchWikipediaPhoto(string $nom): ?string
    {
        try {
            // Construire le nom de la page Wikipedia
            $pageName = str_replace(' ', '_', $nom);
            
            // Utiliser l'API Wikipedia pour récupérer les images de la page
            $apiUrl = "https://fr.wikipedia.org/w/api.php?" . http_build_query([
                'action' => 'query',
                'titles' => $pageName,
                'prop' => 'pageimages',
                'format' => 'json',
                'pithumbsize' => 400,
            ]);

            $response = Http::timeout(10)
                ->withUserAgent('CivicDash/1.0 (https://demo.objectif2027.fr)')
                ->get($apiUrl);
            
            if (!$response->successful()) {
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
            $this->warn("  ⚠️  Erreur récupération photo {$nom}: " . $e->getMessage());
            return null;
        }
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName));
        
        if (count($parts) === 1) {
            return ['prenom' => '', 'nom' => $parts[0]];
        }
        
        // Le dernier mot est généralement le nom de famille
        $nom = array_pop($parts);
        $prenom = implode(' ', $parts);
        
        // Gérer les noms composés (de, du, le, la)
        if (in_array(strtolower($nom), ['maire', 'pen', 'gaulle']) && count($parts) > 0) {
            $nom = array_pop($parts) . ' ' . $nom;
            $prenom = implode(' ', $parts);
        }
        
        return ['prenom' => $prenom, 'nom' => $nom];
    }

    private function guessCivilite(string $prenom): ?string
    {
        $prenomsFeminins = ['Marie', 'Anne', 'Catherine', 'Elisabeth', 'Élisabeth', 'Amélie', 
            'Aurore', 'Rachida', 'Nathalie', 'Sylvie', 'Marine', 'Agnès', 'Brigitte', 'Florence',
            'Geneviève', 'Sophie', 'Valérie', 'Nicole', 'Patricia', 'Ségolène', 'Roselyne',
            'Marlène', 'Barbara', 'Bérangère', 'Olivia', 'Sarah', 'Naïma', 'Marina'];
        
        $firstPart = explode(' ', $prenom)[0] ?? '';
        
        if (in_array($firstPart, $prenomsFeminins)) {
            return 'Mme';
        }
        
        return 'M.';
    }
}
