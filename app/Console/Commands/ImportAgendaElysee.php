<?php

namespace App\Console\Commands;

use App\Models\EvenementLegislatif;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ImportAgendaElysee extends Command
{
    protected $signature = 'import:agenda-elysee 
        {--mois= : Mois spécifique (ex: novembre-2025)}
        {--fresh : Supprimer les événements Élysée existants avant import}';

    protected $description = 'Importer l\'agenda du Président depuis elysee.fr (scraping HTML)';

    private int $created = 0;

    private int $updated = 0;

    private int $errors = 0;

    // Mapping des mois français
    private array $moisNoms = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    public function handle(): int
    {
        $this->info('🏛️ Import agenda Élysée depuis elysee.fr');
        $this->newLine();

        if ($this->option('fresh')) {
            $count = EvenementLegislatif::elysee()->delete();
            $this->warn("🗑️  {$count} événements Élysée supprimés");
        }

        $moisOption = $this->option('mois');

        if ($moisOption) {
            $this->importMois($moisOption);
        } else {
            // Import du mois courant
            $this->importMois(null);

            // Mois précédent
            $moisPrec = now()->subMonth();
            $this->importMois($this->moisNoms[$moisPrec->month].'-'.$moisPrec->year);
        }

        // Résumé
        $this->newLine();
        $this->info('📊 Résumé:');
        $this->table(
            ['Créés', 'Mis à jour', 'Erreurs'],
            [[$this->created, $this->updated, $this->errors]]
        );

        return self::SUCCESS;
    }

    private function importMois(?string $mois): void
    {
        if ($mois) {
            $url = "https://www.elysee.fr/agenda-{$mois}";
            $this->info("📥 Import {$mois}...");
        } else {
            $url = 'https://www.elysee.fr/agenda';
            $this->info('📥 Import mois courant...');
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CivicDash/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            if (! $response->successful()) {
                $this->error("  ❌ Erreur HTTP {$response->status()}");
                $this->errors++;

                return;
            }

            $html = $response->body();
            $this->parseHtml($html);

        } catch (\Exception $e) {
            $this->error('  ❌ Erreur: '.$e->getMessage());
            $this->errors++;
        }
    }

    private function parseHtml(string $html): void
    {
        $crawler = new Crawler($html);
        $count = 0;

        // Structure trouvée : div.banner--table avec id="d-YYMMDD"
        // Contient ul.list-table > li avec .list-table__hour et .list-table__type

        $crawler->filter('div.banner--table[id^="d-"]')->each(function (Crawler $dayDiv) use (&$count) {
            // Extraire la date depuis l'ID (format: d-YYMMDD)
            $id = $dayDiv->attr('id'); // "d-251202" = 2 décembre 2025

            if (! $id || ! preg_match('/^d-(\d{2})(\d{2})(\d{2})$/', $id, $matches)) {
                return;
            }

            $annee = 2000 + (int) $matches[1]; // 25 -> 2025
            $mois = (int) $matches[2]; // 12
            $jour = (int) $matches[3]; // 02

            try {
                $date = Carbon::createFromDate($annee, $mois, $jour);
            } catch (\Exception $e) {
                return;
            }

            // Parser les événements de ce jour
            $dayDiv->filter('ul.list-table > li')->each(function (Crawler $eventLi) use ($date, &$count) {
                $this->processEvent($eventLi, $date, $count);
            });
        });

        $this->info("  ✅ {$count} événements traités");
    }

    private function processEvent(Crawler $eventLi, Carbon $date, int &$count): void
    {
        try {
            // Extraire l'heure
            $heure = null;
            $minute = 0;
            $journeeEntiere = false;

            $hourNode = $eventLi->filter('.list-table__hour');
            if ($hourNode->count() > 0) {
                $heureText = trim($hourNode->text());

                if (preg_match('/^(\d{1,2})h(\d{2})?$/i', $heureText, $matches)) {
                    $heure = (int) $matches[1];
                    $minute = (int) ($matches[2] ?? 0);
                } elseif (stripos($heureText, 'journée') !== false || stripos($heureText, 'matin') !== false) {
                    $journeeEntiere = true;
                }
            }

            // Extraire le type
            $type = EvenementLegislatif::TYPE_AUTRE;
            $typeNode = $eventLi->filter('.list-table__type');
            if ($typeNode->count() > 0) {
                $typeText = strtolower(trim($typeNode->text()));

                if (str_contains($typeText, 'conseil')) {
                    $type = EvenementLegislatif::TYPE_REUNION;
                } elseif (str_contains($typeText, 'entretien') || str_contains($typeText, 'rencontre')) {
                    $type = EvenementLegislatif::TYPE_AUDITION;
                } elseif (str_contains($typeText, 'visite') || str_contains($typeText, 'déplacement')) {
                    $type = EvenementLegislatif::TYPE_AUTRE;
                } elseif (str_contains($typeText, 'déjeuner') || str_contains($typeText, 'dîner')) {
                    $type = EvenementLegislatif::TYPE_REUNION;
                }
            }

            // Extraire le titre/description
            $titre = '';
            $contentNode = $eventLi->filter('.list-table__content');
            if ($contentNode->count() > 0) {
                // Le titre est souvent dans .list-table__type ou .m-b-n
                $typeText = $typeNode->count() > 0 ? trim($typeNode->text()) : '';

                $descNode = $contentNode->filter('.m-b-n, .list-table__description, p');
                $description = $descNode->count() > 0 ? trim($descNode->text()) : '';

                $titre = $typeText ?: $description;
                if ($typeText && $description && $typeText !== $description) {
                    $titre = $typeText.' - '.$description;
                }
            }

            // Fallback : utiliser tout le texte
            if (empty($titre)) {
                $titre = trim($eventLi->text());
                // Nettoyer les heures du titre
                $titre = preg_replace('/^\d{1,2}h\d{0,2}\s*/i', '', $titre);
            }

            if (strlen($titre) < 5) {
                return;
            }

            // Limiter le titre
            if (strlen($titre) > 255) {
                $titre = substr($titre, 0, 252).'...';
            }

            // Date de début
            $dateDebut = $date->copy();
            if ($heure !== null) {
                $dateDebut->setTime($heure, $minute);
            } else {
                $dateDebut->setTime(9, 0); // Heure par défaut
            }

            // UID unique
            $uid = 'elysee-'.$date->format('Ymd').'-'.md5($titre);

            // URL source (liens associés)
            $urlSource = null;
            $linkNode = $eventLi->filter('.list-table__links a, a');
            if ($linkNode->count() > 0) {
                $href = $linkNode->first()->attr('href');
                if ($href && ! str_starts_with($href, '#')) {
                    $urlSource = str_starts_with($href, 'http') ? $href : 'https://www.elysee.fr'.$href;
                }
            }

            // Icône selon le type
            $icone = match ($type) {
                EvenementLegislatif::TYPE_REUNION => '👔',
                EvenementLegislatif::TYPE_AUDITION => '🤝',
                default => '🏛️',
            };

            // Upsert
            $event = EvenementLegislatif::updateOrCreate(
                ['uid' => $uid],
                [
                    'source' => EvenementLegislatif::SOURCE_ELYSEE,
                    'type' => $type,
                    'titre' => $this->cleanText($titre, 255),
                    'description' => null,
                    'lieu' => null,
                    'date_debut' => $dateDebut,
                    'date_fin' => null,
                    'journee_entiere' => $journeeEntiere,
                    'instance_code' => 'PRESIDENCE',
                    'instance_nom' => 'Présidence de la République',
                    'url_source' => $urlSource,
                    'couleur' => EvenementLegislatif::COULEURS[EvenementLegislatif::SOURCE_ELYSEE],
                    'icone' => $icone,
                    'statut' => EvenementLegislatif::STATUT_CONFIRME,
                ]
            );

            if ($event->wasRecentlyCreated) {
                $this->created++;
            } else {
                $this->updated++;
            }

            $count++;

        } catch (\Exception $e) {
            $this->warn('  ⚠️ Erreur parsing: '.$e->getMessage());
        }
    }

    private function cleanText(?string $text, int $maxLength = 255): ?string
    {
        if (! $text) {
            return null;
        }

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength - 3).'...';
        }

        return $text ?: null;
    }
}
