<?php

namespace App\Console\Commands;

use App\Models\TexteAkomaNtoso;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportAkomaNtosoSenat extends Command
{
    protected $signature = 'import:akoma-ntoso
                            {--type=depots : Type de flux (depots, adoptions)}
                            {--limit= : Limite du nombre de textes (pour tests)}
                            {--fresh : Vider la table avant import}
                            {--since= : Importer uniquement les textes des N derniers jours}';

    protected $description = 'Importe les textes législatifs au format Akoma Ntoso depuis le Sénat';

    private int $imported = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $type = $this->option('type');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $fresh = $this->option('fresh');
        $since = $this->option('since') ? (int) $this->option('since') : null;

        $this->info("🏛️  Import des textes Akoma Ntoso ({$type})...");

        if ($fresh) {
            $this->warn("⚠️  Mode --fresh : suppression des textes existants...");
            TexteAkomaNtoso::truncate();
        }

        // URL du flux
        $fluxUrl = $type === 'adoptions' 
            ? 'https://www.senat.fr/akomantoso/adoptions.xml'
            : 'https://www.senat.fr/akomantoso/depots.xml';

        // Récupérer la liste des textes
        $textes = $this->fetchTextList($fluxUrl);
        if (empty($textes)) {
            $this->error("❌ Aucun texte trouvé dans le flux");
            return Command::FAILURE;
        }

        $this->info("📄 {$this->count($textes)} textes trouvés dans le flux");

        // Filtrer par date si demandé
        if ($since) {
            $cutoff = now()->subDays($since);
            $textes = array_filter($textes, function ($t) use ($cutoff) {
                return isset($t['lastModifiedDateTime']) 
                    && strtotime($t['lastModifiedDateTime']) >= $cutoff->timestamp;
            });
            $this->info("📅 {$this->count($textes)} textes depuis {$since} jours");
        }

        if ($limit) {
            $this->warn("⚠️  Mode TEST : {$limit} textes maximum");
            $textes = array_slice($textes, 0, $limit);
        }

        $bar = $this->output->createProgressBar(count($textes));
        $bar->start();

        foreach ($textes as $texte) {
            try {
                $this->processTexte($texte);
            } catch (\Exception $e) {
                $this->errors++;
                $this->warn("\n⚠️  Erreur sur {$texte['url']}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return Command::SUCCESS;
    }

    private function fetchTextList(string $url): array
    {
        $this->info("📥 Récupération du flux {$url}...");

        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                $this->error("❌ Erreur HTTP: " . $response->status());
                return [];
            }

            $xml = @simplexml_load_string($response->body());
            if (!$xml) {
                $this->error("❌ XML invalide");
                return [];
            }

            $textes = [];
            foreach ($xml->text as $text) {
                $textes[] = [
                    'url' => (string) $text->url,
                    'lastModified' => (string) $text->lastModified,
                    'lastModifiedDateTime' => (string) $text->lastModifiedDateTime,
                ];
            }

            return $textes;
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return [];
        }
    }

    private function count(array $arr): int
    {
        return count($arr);
    }

    private function processTexte(array $texteInfo): void
    {
        $url = $texteInfo['url'];
        
        // Extraire l'UID depuis l'URL
        // https://www.senat.fr/akomantoso/ppl25-171.akn.xml -> ppl25-171
        preg_match('/\/([^\/]+)\.akn\.xml$/', $url, $matches);
        $uid = $matches[1] ?? null;
        
        if (!$uid) {
            throw new \Exception("Impossible d'extraire l'UID");
        }

        // Vérifier si on a déjà ce texte avec la même date de modification
        $existing = TexteAkomaNtoso::find($uid);
        if ($existing && $existing->last_modified) {
            $newModified = strtotime($texteInfo['lastModifiedDateTime'] ?? '');
            if ($newModified && $existing->last_modified->timestamp >= $newModified) {
                $this->skipped++;
                return;
            }
        }

        // Télécharger et parser le XML
        $response = Http::timeout(30)->get($url);
        if (!$response->successful()) {
            throw new \Exception("Erreur HTTP " . $response->status());
        }

        $data = $this->parseAkomaNtoso($response->body(), $uid);
        $data['source_url'] = $url;
        $data['last_modified'] = $texteInfo['lastModifiedDateTime'] ?? null;

        // Upsert
        if ($existing) {
            $existing->update($data);
            $this->updated++;
        } else {
            TexteAkomaNtoso::create($data);
            $this->imported++;
        }
    }

    private function parseAkomaNtoso(string $content, string $uid): array
    {
        // Supprimer les namespaces pour simplifier le parsing
        $content = preg_replace('/xmlns[^=]*="[^"]*"/', '', $content);
        $content = preg_replace('/xsi:[^=]*="[^"]*"/', '', $content);
        $content = preg_replace('/data:/', '', $content);
        
        $xml = @simplexml_load_string($content);
        if (!$xml) {
            throw new \Exception("XML invalide");
        }

        // Extraire type et numéro depuis l'UID (ppl25-171)
        preg_match('/^([a-z]+)(\d+)-(\d+)$/', $uid, $matches);
        $type = $matches[1] ?? 'unknown';
        $annee = $matches[2] ?? null;
        $numero = isset($matches[3]) ? (int) $matches[3] : null;

        // Métadonnées
        $bill = $xml->bill ?? $xml;
        $meta = $bill->meta ?? null;
        $identification = $meta->identification ?? null;
        $work = $identification->FRBRWork ?? null;

        // Titre
        $titre = null;
        $titreCourt = null;
        if ($bill->preamble && $bill->preamble->docTitle) {
            $titre = (string) $bill->preamble->docTitle;
        }
        
        // Chercher dans les alias
        if ($work) {
            foreach ($work->FRBRalias ?? [] as $alias) {
                $name = (string) ($alias['name'] ?? '');
                $value = (string) ($alias['value'] ?? '');
                if ($name === 'intitule-court') {
                    $titreCourt = $value;
                }
            }
        }

        // URLs
        $urlSenat = null;
        $signetDossier = null;
        if ($work) {
            foreach ($work->FRBRalias ?? [] as $alias) {
                $name = (string) ($alias['name'] ?? '');
                $value = (string) ($alias['value'] ?? '');
                if ($name === 'url-senat') {
                    $urlSenat = $value;
                } elseif ($name === 'signet-dossier-legislatif-senat') {
                    $signetDossier = $value;
                }
            }
        }

        // Dates
        $dateDepot = null;
        $datePresentation = null;
        $dateAdoption = null;
        $datePublicationXml = null;
        
        if ($work && $work->FRBRdate) {
            foreach ($work->FRBRdate ?? [] as $date) {
                $name = (string) ($date['name'] ?? '');
                $value = (string) ($date['date'] ?? '');
                if ($name === '#depot' || $name === 'depot') {
                    $dateDepot = $value;
                } elseif ($name === '#presentation' || $name === 'presentation') {
                    $datePresentation = $value;
                }
            }
        }
        
        if ($identification && $identification->FRBRExpression && $identification->FRBRExpression->FRBRdate) {
            $date = $identification->FRBRExpression->FRBRdate;
            $name = (string) ($date['name'] ?? '');
            $value = (string) ($date['date'] ?? '');
            if ($name === '#depot' || strpos($name, 'depot') !== false) {
                $dateDepot = $dateDepot ?? $value;
            }
        }

        if ($identification && $identification->FRBRManifestation && $identification->FRBRManifestation->FRBRdate) {
            $date = $identification->FRBRManifestation->FRBRdate;
            $name = (string) ($date['name'] ?? '');
            $value = (string) ($date['date'] ?? '');
            if ($name === '#publication-xml' || strpos($name, 'publication') !== false) {
                $datePublicationXml = $value;
            }
        }

        // Auteur
        $auteurId = null;
        $auteurNom = null;
        $references = $meta->references ?? null;
        if ($references) {
            foreach ($references->TLCPerson ?? [] as $person) {
                $auteurId = (string) ($person['eId'] ?? '');
                $auteurNom = (string) ($person['showAs'] ?? '');
                break;
            }
        }

        // Commission
        $commission = null;
        if ($references) {
            foreach ($references->TLCOrganization ?? [] as $org) {
                $showAs = (string) ($org['showAs'] ?? '');
                if (stripos($showAs, 'Commission') !== false && stripos($showAs, 'Sénat') === false) {
                    $commission = $showAs;
                    break;
                }
            }
        }

        // Workflow
        $etapeActuelle = null;
        $statut = null;
        if ($meta && $meta->workflow) {
            foreach ($meta->workflow->step ?? [] as $step) {
                $etapeActuelle = (string) ($step['refersTo'] ?? '');
                $statut = (string) ($step['outcome'] ?? '');
            }
        }

        // Contenu
        $preambule = null;
        if ($bill->preamble) {
            $preambule = $this->xmlToText($bill->preamble);
        }

        $corpsTexte = null;
        $nbArticles = 0;
        $nbTitres = 0;
        if ($bill->body) {
            $corpsTexte = $this->xmlToText($bill->body);
            $nbArticles = count($bill->body->xpath('.//article') ?? []);
            $nbTitres = count($bill->body->xpath('.//title') ?? []);
        }

        return [
            'uid' => $uid,
            'type' => $type,
            'annee' => $annee,
            'numero' => $numero,
            'session' => null,
            'titre' => $titre,
            'titre_court' => $titreCourt,
            'url_senat' => $urlSenat,
            'url_dossier' => $urlSenat ? str_replace('/leg/', '/dossier-legislatif/', $urlSenat) : null,
            'signet_dossier' => $signetDossier,
            'auteur_id' => $auteurId,
            'auteur_nom' => $auteurNom,
            'commission' => $commission,
            'date_depot' => $dateDepot,
            'date_presentation' => $datePresentation,
            'date_adoption' => $dateAdoption,
            'date_publication_xml' => $datePublicationXml,
            'etape_actuelle' => $etapeActuelle,
            'statut' => $statut,
            'preambule' => $preambule,
            'corps_texte' => $corpsTexte,
            'nb_articles' => $nbArticles,
            'nb_titres' => $nbTitres,
        ];
    }

    private function xmlToText(\SimpleXMLElement $element): string
    {
        $text = strip_tags($element->asXML());
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function displaySummary(): void
    {
        $this->info("📊 Résumé de l'import :");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✅ Importés', $this->imported],
                ['🔄 Mis à jour', $this->updated],
                ['⏭️  Ignorés (non modifiés)', $this->skipped],
                ['❌ Erreurs', $this->errors],
                ['📊 Total traité', $this->imported + $this->updated + $this->skipped + $this->errors],
            ]
        );

        // Stats finales
        $total = TexteAkomaNtoso::count();
        $ppl = TexteAkomaNtoso::where('type', 'ppl')->count();
        $pjl = TexteAkomaNtoso::where('type', 'pjl')->count();
        $this->newLine();
        $this->info("📈 Base de données :");
        $this->info("   Total textes : {$total}");
        $this->info("   Propositions de loi : {$ppl}");
        $this->info("   Projets de loi : {$pjl}");
    }
}

