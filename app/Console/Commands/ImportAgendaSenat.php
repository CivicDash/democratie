<?php

namespace App\Console\Commands;

use App\Models\EvenementLegislatif;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Sabre\VObject\Reader;

class ImportAgendaSenat extends Command
{
    protected $signature = 'import:agenda-senat 
        {--instance=all : Instance spécifique (Seance, COM-LOIS, Global, etc.) ou "all"}
        {--fresh : Supprimer les événements Sénat existants avant import}';

    protected $description = 'Importer l\'agenda du Sénat depuis les flux iCal';

    // Liste des instances disponibles avec leurs codes
    private array $instances = [
        'Global' => ['nom' => 'Toutes activités', 'type' => 'autre'],
        'Seance' => ['nom' => 'Séance publique', 'type' => 'seance'],
        'COM-LOIS' => ['nom' => 'Commission des lois', 'type' => 'commission'],
        'COM-FIN' => ['nom' => 'Commission des finances', 'type' => 'commission'],
        'COM-AE' => ['nom' => 'Commission des affaires étrangères', 'type' => 'commission'],
        'COM-AFFECO' => ['nom' => 'Commission des affaires économiques', 'type' => 'commission'],
        'COM-AFFSOC' => ['nom' => 'Commission des affaires sociales', 'type' => 'commission'],
        'COM-CULT' => ['nom' => 'Commission de la culture', 'type' => 'commission'],
        'COM-DEVDUR' => ['nom' => 'Commission du développement durable', 'type' => 'commission'],
        'COM-AFEURO' => ['nom' => 'Commission des affaires européennes', 'type' => 'commission'],
    ];

    private int $created = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $instance = $this->option('instance');
        $fresh = $this->option('fresh');

        $this->info('🏛️ Import agenda Sénat depuis iCal');
        $this->newLine();

        if ($fresh) {
            $count = EvenementLegislatif::senat()->delete();
            $this->warn("🗑️  {$count} événements Sénat supprimés");
        }

        // Déterminer quelles instances importer
        if ($instance === 'all') {
            $instancesToImport = $this->instances;
        } elseif (isset($this->instances[$instance])) {
            $instancesToImport = [$instance => $this->instances[$instance]];
        } else {
            $this->error("Instance inconnue: {$instance}");
            $this->info('Instances disponibles: '.implode(', ', array_keys($this->instances)));

            return self::FAILURE;
        }

        // Importer chaque instance
        foreach ($instancesToImport as $code => $config) {
            $this->importInstance($code, $config);
        }

        // Résumé
        $this->newLine();
        $this->info('📊 Résumé:');
        $this->table(
            ['Créés', 'Mis à jour', 'Ignorés', 'Erreurs'],
            [[$this->created, $this->updated, $this->skipped, $this->errors]]
        );

        return self::SUCCESS;
    }

    private function importInstance(string $code, array $config): void
    {
        $url = "https://www.senat.fr/aglae/{$code}/ical.ics";

        $this->info("📥 Import {$config['nom']} ({$code})...");

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                $this->error("  ❌ Erreur HTTP {$response->status()} pour {$url}");
                $this->errors++;

                return;
            }

            $icalContent = $response->body();

            if (empty($icalContent)) {
                $this->warn("  ⚠️ Fichier iCal vide pour {$code}");

                return;
            }

            $this->parseIcal($icalContent, $code, $config);

        } catch (\Exception $e) {
            $this->error('  ❌ Erreur: '.$e->getMessage());
            $this->errors++;
        }
    }

    private function parseIcal(string $content, string $instanceCode, array $config): void
    {
        try {
            $vcalendar = Reader::read($content);
        } catch (\Exception $e) {
            $this->error('  ❌ Erreur parsing iCal: '.$e->getMessage());
            $this->errors++;

            return;
        }

        if (! isset($vcalendar->VEVENT)) {
            $this->warn('  ⚠️ Aucun événement dans le calendrier');

            return;
        }

        $count = 0;
        foreach ($vcalendar->VEVENT as $vevent) {
            $this->processEvent($vevent, $instanceCode, $config);
            $count++;
        }

        $this->info("  ✅ {$count} événements traités");
    }

    private function processEvent($vevent, string $instanceCode, array $config): void
    {
        try {
            // Extraire les données de l'événement
            $uid = (string) ($vevent->UID ?? '');
            $summary = (string) ($vevent->SUMMARY ?? 'Sans titre');
            $description = (string) ($vevent->DESCRIPTION ?? '');
            $location = (string) ($vevent->LOCATION ?? '');
            $url = (string) ($vevent->URL ?? '');

            // Dates
            $dtstart = $vevent->DTSTART;
            $dtend = $vevent->DTEND ?? null;

            if (! $dtstart) {
                $this->skipped++;

                return;
            }

            $dateDebut = $dtstart->getDateTime();
            $dateFin = $dtend ? $dtend->getDateTime() : null;

            // Détecter si c'est une journée entière
            $journeeEntiere = false;
            if ($dtstart->hasTime() === false) {
                $journeeEntiere = true;
            }

            // Créer un UID unique pour notre système
            $ourUid = 'senat-'.$instanceCode.'-'.md5($uid.$dateDebut->format('Y-m-d H:i:s'));

            // Last modified
            $lastModified = isset($vevent->{'LAST-MODIFIED'})
                ? $vevent->{'LAST-MODIFIED'}->getDateTime()
                : null;

            // Déterminer le type
            $type = $config['type'];

            // Affiner le type selon le contenu
            if (stripos($summary, 'audition') !== false) {
                $type = EvenementLegislatif::TYPE_AUDITION;
            } elseif (stripos($summary, 'vote') !== false || stripos($summary, 'scrutin') !== false) {
                $type = EvenementLegislatif::TYPE_VOTE;
            }

            // Extraire l'URL vidéo si présente dans la description
            $urlVideo = null;
            if (preg_match('/https?:\/\/videos\.senat\.fr[^\s<>]+/', $description, $matches)) {
                $urlVideo = $matches[0];
            }

            // Nettoyer la description
            $description = strip_tags($description);
            $description = preg_replace('/\s+/', ' ', $description);
            $description = trim($description);
            if (strlen($description) > 2000) {
                $description = substr($description, 0, 1997).'...';
            }

            // Upsert
            $event = EvenementLegislatif::updateOrCreate(
                ['uid' => $ourUid],
                [
                    'source' => EvenementLegislatif::SOURCE_SENAT,
                    'type' => $type,
                    'titre' => $this->cleanText($summary, 255),
                    'description' => $description ?: null,
                    'lieu' => $this->cleanText($location, 255) ?: null,
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin,
                    'journee_entiere' => $journeeEntiere,
                    'instance_code' => $instanceCode,
                    'instance_nom' => $config['nom'],
                    'url_source' => $url ?: null,
                    'url_video' => $urlVideo,
                    'couleur' => EvenementLegislatif::COULEURS[EvenementLegislatif::SOURCE_SENAT],
                    'icone' => EvenementLegislatif::ICONES[$type] ?? '📅',
                    'ical_uid' => $uid,
                    'ical_last_modified' => $lastModified,
                    'statut' => EvenementLegislatif::STATUT_CONFIRME,
                ]
            );

            if ($event->wasRecentlyCreated) {
                $this->created++;
            } else {
                $this->updated++;
            }

        } catch (\Exception $e) {
            $this->error('  ⚠️ Erreur événement: '.$e->getMessage());
            $this->errors++;
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
