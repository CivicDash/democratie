<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EvenementLegislatif;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Export du calendrier législatif en format iCalendar (ICS)
 * 
 * Permet d'exporter les événements AN + Sénat + Élysée vers :
 * - Google Calendar
 * - Apple Calendar
 * - Microsoft Outlook
 * - Thunderbird
 * - Tout client compatible iCal
 */
class CalendarExportController extends Controller
{
    /**
     * Export du calendrier complet ou filtré
     * 
     * GET /api/calendar/export.ics
     * 
     * Paramètres :
     * - source: an|senat|elysee (optionnel, tous par défaut)
     * - type: seance|commission|reunion|vote|audition (optionnel)
     * - from: date de début (optionnel, défaut: aujourd'hui - 1 mois)
     * - to: date de fin (optionnel, défaut: aujourd'hui + 3 mois)
     */
    public function export(Request $request): Response
    {
        $request->validate([
            'source' => ['nullable', 'in:an,senat,elysee'],
            'type' => ['nullable', 'in:seance,commission,reunion,vote,audition,autre'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        
        // Période par défaut : -1 mois à +3 mois
        $from = $request->input('from') 
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->subMonth()->startOfDay();
            
        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->addMonths(3)->endOfDay();
        
        // Construire la requête
        $query = EvenementLegislatif::query()
            ->whereBetween('date_debut', [$from, $to])
            ->confirmes()
            ->orderBy('date_debut');
        
        // Filtrer par source
        if ($source = $request->input('source')) {
            $query->source($source);
        }
        
        // Filtrer par type
        if ($type = $request->input('type')) {
            $query->type($type);
        }
        
        $evenements = $query->get();
        
        // Générer le nom du calendrier
        $calendarName = $this->buildCalendarName($request);
        
        // Générer le contenu iCal
        $icalContent = $this->generateIcal($evenements, $calendarName, $from, $to);
        
        // Nom du fichier
        $filename = $this->buildFilename($request);
        
        return response($icalContent, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Flux iCal dynamique (pour abonnement)
     * 
     * GET /api/calendar/feed.ics
     * 
     * Retourne toujours les événements des 3 prochains mois
     * pour permettre un abonnement qui se met à jour automatiquement
     */
    public function feed(Request $request): Response
    {
        $request->validate([
            'source' => ['nullable', 'in:an,senat,elysee'],
            'type' => ['nullable', 'in:seance,commission,reunion,vote,audition,autre'],
        ]);
        
        // Période fixe pour le flux : -1 semaine à +3 mois
        $from = now()->subWeek()->startOfDay();
        $to = now()->addMonths(3)->endOfDay();
        
        // Construire la requête
        $query = EvenementLegislatif::query()
            ->whereBetween('date_debut', [$from, $to])
            ->confirmes()
            ->orderBy('date_debut');
        
        if ($source = $request->input('source')) {
            $query->source($source);
        }
        
        if ($type = $request->input('type')) {
            $query->type($type);
        }
        
        $evenements = $query->get();
        
        $calendarName = $this->buildCalendarName($request);
        $icalContent = $this->generateIcal($evenements, $calendarName, $from, $to);
        
        // Pour le flux, on autorise le cache (1 heure)
        return response($icalContent, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Export d'un événement unique
     * 
     * GET /api/calendar/event/{id}.ics
     */
    public function single(EvenementLegislatif $evenement): Response
    {
        $icalContent = $this->generateIcal(
            collect([$evenement]), 
            "CivicDash - {$evenement->titre}",
            $evenement->date_debut,
            $evenement->date_fin ?? $evenement->date_debut
        );
        
        $filename = "civicdash-evenement-{$evenement->id}.ics";
        
        return response($icalContent, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Générer le contenu iCalendar complet
     */
    protected function generateIcal($evenements, string $calendarName, Carbon $from, Carbon $to): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//CivicDash//Calendrier Législatif//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            "X-WR-CALNAME:{$calendarName}",
            'X-WR-CALDESC:Agenda parlementaire français - Assemblée nationale\\, Sénat\\, Élysée',
            'X-WR-TIMEZONE:Europe/Paris',
            '',
            // Définition du fuseau horaire Europe/Paris
            'BEGIN:VTIMEZONE',
            'TZID:Europe/Paris',
            'BEGIN:DAYLIGHT',
            'TZOFFSETFROM:+0100',
            'TZOFFSETTO:+0200',
            'TZNAME:CEST',
            'DTSTART:19700329T020000',
            'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
            'END:DAYLIGHT',
            'BEGIN:STANDARD',
            'TZOFFSETFROM:+0200',
            'TZOFFSETTO:+0100',
            'TZNAME:CET',
            'DTSTART:19701025T030000',
            'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
            'END:STANDARD',
            'END:VTIMEZONE',
        ];
        
        // Ajouter chaque événement
        foreach ($evenements as $evenement) {
            $lines[] = '';
            $lines[] = $evenement->toIcalEvent();
        }
        
        $lines[] = '';
        $lines[] = 'END:VCALENDAR';
        
        return implode("\r\n", $lines);
    }

    /**
     * Construire le nom du calendrier selon les filtres
     */
    protected function buildCalendarName(Request $request): string
    {
        $parts = ['CivicDash'];
        
        if ($source = $request->input('source')) {
            $sourceNames = [
                'an' => 'Assemblée nationale',
                'senat' => 'Sénat',
                'elysee' => 'Élysée',
            ];
            $parts[] = $sourceNames[$source] ?? ucfirst($source);
        } else {
            $parts[] = 'Parlement';
        }
        
        if ($type = $request->input('type')) {
            $typeNames = [
                'seance' => 'Séances',
                'commission' => 'Commissions',
                'reunion' => 'Réunions',
                'vote' => 'Votes',
                'audition' => 'Auditions',
            ];
            $parts[] = $typeNames[$type] ?? ucfirst($type);
        }
        
        return implode(' - ', $parts);
    }

    /**
     * Construire le nom du fichier selon les filtres
     */
    protected function buildFilename(Request $request): string
    {
        $parts = ['civicdash'];
        
        if ($source = $request->input('source')) {
            $parts[] = $source;
        } else {
            $parts[] = 'parlement';
        }
        
        if ($type = $request->input('type')) {
            $parts[] = $type;
        }
        
        $parts[] = now()->format('Y-m-d');
        
        return implode('-', $parts) . '.ics';
    }

    /**
     * Liste des flux disponibles (pour documentation API)
     */
    public function availableFeeds(): array
    {
        $baseUrl = url('/api/calendar');
        
        return [
            'feeds' => [
                [
                    'name' => 'Calendrier complet',
                    'description' => 'Tous les événements (AN + Sénat + Élysée)',
                    'url' => "{$baseUrl}/feed.ics",
                    'download' => "{$baseUrl}/export.ics",
                ],
                [
                    'name' => 'Assemblée nationale',
                    'description' => 'Séances et commissions de l\'Assemblée nationale',
                    'url' => "{$baseUrl}/feed.ics?source=an",
                    'download' => "{$baseUrl}/export.ics?source=an",
                ],
                [
                    'name' => 'Sénat',
                    'description' => 'Séances et commissions du Sénat',
                    'url' => "{$baseUrl}/feed.ics?source=senat",
                    'download' => "{$baseUrl}/export.ics?source=senat",
                ],
                [
                    'name' => 'Élysée',
                    'description' => 'Agenda présidentiel',
                    'url' => "{$baseUrl}/feed.ics?source=elysee",
                    'download' => "{$baseUrl}/export.ics?source=elysee",
                ],
                [
                    'name' => 'Séances publiques uniquement',
                    'description' => 'Séances publiques (AN + Sénat)',
                    'url' => "{$baseUrl}/feed.ics?type=seance",
                    'download' => "{$baseUrl}/export.ics?type=seance",
                ],
                [
                    'name' => 'Votes uniquement',
                    'description' => 'Scrutins et votes',
                    'url' => "{$baseUrl}/feed.ics?type=vote",
                    'download' => "{$baseUrl}/export.ics?type=vote",
                ],
            ],
            'parameters' => [
                'source' => ['an', 'senat', 'elysee'],
                'type' => ['seance', 'commission', 'reunion', 'vote', 'audition'],
                'from' => 'Date de début (YYYY-MM-DD)',
                'to' => 'Date de fin (YYYY-MM-DD)',
            ],
            'usage' => [
                'google_calendar' => 'Ajouter via URL du flux feed.ics',
                'apple_calendar' => 'Fichier > Nouvel abonnement > URL du flux',
                'outlook' => 'Importer le fichier .ics téléchargé',
            ],
        ];
    }
}
