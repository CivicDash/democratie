<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EvenementLegislatif;
use App\Models\ReunionAN;
use App\Models\OrganeAN;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CalendrierController extends Controller
{
    /**
     * Page principale du calendrier législatif unifié (AN + Sénat)
     */
    public function index(Request $request)
    {
        $mois = $request->get('mois', now()->month);
        $annee = $request->get('annee', now()->year);
        $source = $request->get('source'); // 'an', 'senat', ou null pour tous
        $type = $request->get('type'); // 'seance', 'commission', etc.
        
        // Date de référence
        $dateRef = Carbon::createFromDate($annee, $mois, 1);
        
        // Événements du mois depuis la table unifiée
        $evenements = EvenementLegislatif::query()
            ->mois($dateRef)
            ->confirmes()
            ->when($source, fn($q) => $q->source($source))
            ->when($type, fn($q) => $q->type($type))
            ->orderBy('date_debut')
            ->get()
            ->map(fn($e) => $e->toCalendarEvent());
        
        // Grouper par jour pour le calendrier (convertir en tableau pour Inertia)
        $evenementsParJour = $evenements->groupBy(function ($e) {
            return Carbon::parse($e['start'])->format('Y-m-d');
        })->map(fn($items) => $items->values()->all())->all();
        
        // Statistiques du mois
        $stats = [
            'total' => $evenements->count(),
            'an' => $evenements->where('source', 'an')->count(),
            'senat' => $evenements->where('source', 'senat')->count(),
            'elysee' => $evenements->where('source', 'elysee')->count(),
            'seances' => $evenements->where('type', 'seance')->count(),
            'commissions' => $evenements->where('type', 'commission')->count(),
            'reunions' => $evenements->where('type', 'reunion')->count(),
        ];
        
        // Types disponibles
        $typesDisponibles = EvenementLegislatif::select('type')
            ->distinct()
            ->pluck('type')
            ->map(fn($t) => [
                'value' => $t,
                'label' => EvenementLegislatif::ICONES[$t] ?? '📅' . ' ' . ucfirst($t),
            ]);
        
        // Sources
        $sourcesDisponibles = [
            ['value' => 'an', 'label' => '🔵 Assemblée nationale', 'couleur' => '#0055A4'],
            ['value' => 'senat', 'label' => '🔴 Sénat', 'couleur' => '#DC143C'],
            ['value' => 'elysee', 'label' => '🟡 Élysée', 'couleur' => '#FFD700'],
        ];
        
        return Inertia::render('Parlement/Calendrier/Index', [
            'evenements' => $evenements,
            'evenementsParJour' => $evenementsParJour,
            'stats' => $stats,
            'mois' => (int)$mois,
            'annee' => (int)$annee,
            'dateRef' => $dateRef->format('Y-m-d'),
            'filtres' => [
                'source' => $source,
                'type' => $type,
            ],
            'typesDisponibles' => $typesDisponibles,
            'sourcesDisponibles' => $sourcesDisponibles,
        ]);
    }

    /**
     * Vue semaine
     */
    public function semaine(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $source = $request->get('source');
        
        $dateRef = Carbon::parse($date);
        $debutSemaine = $dateRef->copy()->startOfWeek();
        $finSemaine = $dateRef->copy()->endOfWeek();
        
        $evenements = EvenementLegislatif::query()
            ->periode($debutSemaine, $finSemaine)
            ->confirmes()
            ->when($source, fn($q) => $q->source($source))
            ->orderBy('date_debut')
            ->get()
            ->map(fn($e) => $e->toCalendarEvent());
        
        // Grouper par jour
        $joursArray = [];
        for ($i = 0; $i < 7; $i++) {
            $jour = $debutSemaine->copy()->addDays($i);
            $jourKey = $jour->format('Y-m-d');
            $joursArray[$jourKey] = [
                'date' => $jourKey,
                'label' => $jour->translatedFormat('l j'),
                'estAujourdhui' => $jour->isToday(),
                'evenements' => $evenements->filter(fn($e) => 
                    Carbon::parse($e['start'])->format('Y-m-d') === $jourKey
                )->values(),
            ];
        }
        
        return Inertia::render('Parlement/Calendrier/Semaine', [
            'jours' => $joursArray,
            'debutSemaine' => $debutSemaine->format('Y-m-d'),
            'finSemaine' => $finSemaine->format('Y-m-d'),
            'semaineLabel' => $debutSemaine->translatedFormat('j M') . ' - ' . $finSemaine->translatedFormat('j M Y'),
            'filtres' => ['source' => $source],
        ]);
    }

    /**
     * Détail d'un événement
     */
    public function show(string $uid)
    {
        // Chercher d'abord dans la table unifiée
        $evenement = EvenementLegislatif::where('uid', $uid)->first();
        
        if ($evenement) {
            // Événements similaires
            $similaires = EvenementLegislatif::where('source', $evenement->source)
                ->where('type', $evenement->type)
                ->where('uid', '!=', $uid)
                ->aVenir()
                ->orderBy('date_debut')
                ->limit(5)
                ->get()
                ->map(fn($e) => $e->toCalendarEvent());
            
            return Inertia::render('Parlement/Calendrier/Show', [
                'evenement' => array_merge($evenement->toCalendarEvent(), [
                    'description' => $evenement->description,
                    'urlDossier' => $evenement->url_dossier,
                ]),
                'similaires' => $similaires,
            ]);
        }
        
        // Fallback: chercher dans reunions_an (ancienne table)
        $reunion = ReunionAN::with('organe')
            ->where('uid', $uid)
            ->firstOrFail();
        
        $reunionsSimilaires = ReunionAN::where('organe_ref', $reunion->organe_ref)
            ->where('uid', '!=', $uid)
            ->whereMonth('date_debut', $reunion->date_debut?->month ?? now()->month)
            ->orderBy('date_debut')
            ->limit(5)
            ->get()
            ->map(fn($r) => $this->formatReunionLegacy($r));
        
        return Inertia::render('Parlement/Calendrier/Show', [
            'evenement' => $this->formatReunionDetailedLegacy($reunion),
            'similaires' => $reunionsSimilaires,
        ]);
    }

    /**
     * API: Événements du jour (pour widget dashboard)
     */
    public function aujourdhui()
    {
        $evenements = EvenementLegislatif::query()
            ->jour(now())
            ->confirmes()
            ->orderBy('date_debut')
            ->limit(10)
            ->get()
            ->map(fn($e) => $e->toCalendarEvent());
        
        return response()->json([
            'date' => now()->format('Y-m-d'),
            'evenements' => $evenements,
            'count' => $evenements->count(),
        ]);
    }

    /**
     * API: Prochains événements (pour widget)
     */
    public function prochaines(Request $request)
    {
        $limit = $request->get('limit', 5);
        $source = $request->get('source');
        
        $evenements = EvenementLegislatif::query()
            ->aVenir()
            ->confirmes()
            ->when($source, fn($q) => $q->source($source))
            ->orderBy('date_debut')
            ->limit($limit)
            ->get()
            ->map(fn($e) => $e->toCalendarEvent());
        
        return response()->json([
            'evenements' => $evenements,
        ]);
    }

    // ========================================
    // MÉTHODES LEGACY (pour compatibilité)
    // ========================================

    private function formatReunionLegacy(ReunionAN $reunion): array
    {
        return [
            'uid' => $reunion->uid,
            'title' => $reunion->titre_odj ?? $reunion->organe_nom ?? 'Réunion',
            'start' => $reunion->date_debut?->toIso8601String(),
            'end' => $reunion->date_fin?->toIso8601String(),
            'source' => 'an',
            'sourceLabel' => 'Assemblée nationale',
            'type' => 'reunion',
            'typeLabel' => $reunion->type_reunion ?? 'Réunion',
            'color' => '#0055A4',
            'icon' => '📋',
            'lieu' => $reunion->lieu_libelle,
            'description' => null,
            'instance' => $reunion->organe?->libelle,
            'urlSource' => null,
            'urlVideo' => null,
            'statut' => $reunion->etat === 'Annulé' ? 'annule' : 'confirme',
        ];
    }

    private function formatReunionDetailedLegacy(ReunionAN $reunion): array
    {
        $base = $this->formatReunionLegacy($reunion);
        
        return array_merge($base, [
            'description' => $reunion->odj_resume ?? $reunion->odj_convocation,
            'urlDossier' => null,
        ]);
    }
}
