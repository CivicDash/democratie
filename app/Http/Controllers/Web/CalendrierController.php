<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ReunionAN;
use App\Models\OrganeAN;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CalendrierController extends Controller
{
    /**
     * Page principale du calendrier législatif
     */
    public function index(Request $request)
    {
        $mois = $request->get('mois', now()->month);
        $annee = $request->get('annee', now()->year);
        $type = $request->get('type'); // Commission, Séance, etc.
        $organe = $request->get('organe');
        
        // Date de référence
        $dateRef = Carbon::createFromDate($annee, $mois, 1);
        
        // Réunions du mois
        $reunions = ReunionAN::with('organe:uid,libelle,libelle_abrege')
            ->mois($annee, $mois)
            ->when($type, fn($q) => $q->type($type))
            ->when($organe, fn($q) => $q->where('organe_ref', $organe))
            ->orderBy('date_debut')
            ->get()
            ->map(fn($r) => $this->formatReunion($r));
        
        // Grouper par jour pour le calendrier
        $reunionsParJour = $reunions->groupBy(function ($r) {
            return Carbon::parse($r['date_debut'])->format('Y-m-d');
        });
        
        // Statistiques du mois
        $stats = [
            'total' => $reunions->count(),
            'confirmees' => $reunions->where('etat', 'Confirmé')->count(),
            'annulees' => $reunions->where('etat', 'Annulé')->count(),
            'commissions' => $reunions->where('type_reunion', 'Commission')->count(),
            'seances' => $reunions->where('type_reunion', 'Séance publique')->count(),
        ];
        
        // Types de réunions disponibles
        $typesDisponibles = ReunionAN::select('type_reunion')
            ->distinct()
            ->whereNotNull('type_reunion')
            ->pluck('type_reunion')
            ->toArray();
        
        // Organes avec réunions
        $organesDisponibles = Cache::remember('calendrier_organes', 3600, function () {
            return OrganeAN::whereIn('uid', function ($query) {
                $query->select('organe_ref')
                    ->from('reunions_an')
                    ->distinct();
            })
            ->select('uid', 'libelle', 'libelle_abrege')
            ->orderBy('libelle')
            ->get()
            ->map(fn($o) => [
                'uid' => $o->uid,
                'nom' => $o->libelle_abrege ?? $o->libelle,
            ]);
        });
        
        return Inertia::render('Parlement/Calendrier/Index', [
            'reunions' => $reunions,
            'reunionsParJour' => $reunionsParJour,
            'stats' => $stats,
            'mois' => (int)$mois,
            'annee' => (int)$annee,
            'dateRef' => $dateRef->format('Y-m-d'),
            'filtres' => [
                'type' => $type,
                'organe' => $organe,
            ],
            'typesDisponibles' => $typesDisponibles,
            'organesDisponibles' => $organesDisponibles,
        ]);
    }

    /**
     * Vue semaine
     */
    public function semaine(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $dateRef = Carbon::parse($date);
        $debutSemaine = $dateRef->copy()->startOfWeek();
        $finSemaine = $dateRef->copy()->endOfWeek();
        
        $reunions = ReunionAN::with('organe:uid,libelle,libelle_abrege')
            ->periode($debutSemaine, $finSemaine)
            ->where('etat', '!=', 'Annulé')
            ->orderBy('date_debut')
            ->get()
            ->map(fn($r) => $this->formatReunion($r));
        
        // Grouper par jour
        $joursArray = [];
        for ($i = 0; $i < 7; $i++) {
            $jour = $debutSemaine->copy()->addDays($i);
            $jourKey = $jour->format('Y-m-d');
            $joursArray[$jourKey] = [
                'date' => $jourKey,
                'label' => $jour->translatedFormat('l j'),
                'estAujourdhui' => $jour->isToday(),
                'reunions' => $reunions->filter(fn($r) => 
                    Carbon::parse($r['date_debut'])->format('Y-m-d') === $jourKey
                )->values(),
            ];
        }
        
        return Inertia::render('Parlement/Calendrier/Semaine', [
            'jours' => $joursArray,
            'debutSemaine' => $debutSemaine->format('Y-m-d'),
            'finSemaine' => $finSemaine->format('Y-m-d'),
            'semaineLabel' => $debutSemaine->translatedFormat('j M') . ' - ' . $finSemaine->translatedFormat('j M Y'),
        ]);
    }

    /**
     * Détail d'une réunion
     */
    public function show(string $uid)
    {
        $reunion = ReunionAN::with('organe')
            ->where('uid', $uid)
            ->firstOrFail();
        
        // Réunions similaires (même organe, même mois)
        $reunionsSimilaires = ReunionAN::where('organe_ref', $reunion->organe_ref)
            ->where('uid', '!=', $uid)
            ->whereMonth('date_debut', $reunion->date_debut?->month ?? now()->month)
            ->orderBy('date_debut')
            ->limit(5)
            ->get()
            ->map(fn($r) => $this->formatReunion($r));
        
        return Inertia::render('Parlement/Calendrier/Show', [
            'reunion' => $this->formatReunionDetailed($reunion),
            'reunionsSimilaires' => $reunionsSimilaires,
        ]);
    }

    /**
     * API: Réunions du jour (pour widget dashboard)
     */
    public function aujourdhui()
    {
        $reunions = ReunionAN::with('organe:uid,libelle,libelle_abrege')
            ->aujourdhui()
            ->where('etat', '!=', 'Annulé')
            ->orderBy('date_debut')
            ->limit(10)
            ->get()
            ->map(fn($r) => $this->formatReunion($r));
        
        return response()->json([
            'date' => now()->format('Y-m-d'),
            'reunions' => $reunions,
            'count' => $reunions->count(),
        ]);
    }

    /**
     * API: Prochaines réunions (pour widget)
     */
    public function prochaines(Request $request)
    {
        $limit = $request->get('limit', 5);
        
        $reunions = ReunionAN::with('organe:uid,libelle,libelle_abrege')
            ->aVenir()
            ->orderBy('date_debut')
            ->limit($limit)
            ->get()
            ->map(fn($r) => $this->formatReunion($r));
        
        return response()->json([
            'reunions' => $reunions,
        ]);
    }

    /**
     * Formater une réunion pour l'affichage
     */
    private function formatReunion(ReunionAN $reunion): array
    {
        return [
            'uid' => $reunion->uid,
            'titre' => $reunion->titre_odj ?? $reunion->organe_nom ?? 'Réunion',
            'type_reunion' => $reunion->type_reunion,
            'emoji' => $reunion->emoji_type,
            'date_debut' => $reunion->date_debut?->toIso8601String(),
            'date_formatee' => $reunion->date_formatee,
            'date_courte' => $reunion->date_courte,
            'heure' => $reunion->date_debut?->format('H:i'),
            'lieu' => $reunion->lieu_libelle,
            'etat' => $reunion->etat,
            'couleur_etat' => $reunion->couleur_etat,
            'organe' => $reunion->organe ? [
                'uid' => $reunion->organe->uid,
                'nom' => $reunion->organe->libelle_abrege ?? $reunion->organe->libelle,
                'couleur' => '#6B7280', // Couleur par défaut
            ] : null,
            'nb_points_odj' => $reunion->nb_points_odj,
            'est_en_cours' => $reunion->est_en_cours,
            'est_a_venir' => $reunion->est_a_venir,
            'visio' => $reunion->visio_conference,
            'presse' => $reunion->ouverture_presse,
            'video' => $reunion->captation_video,
        ];
    }

    /**
     * Formater une réunion avec tous les détails
     */
    private function formatReunionDetailed(ReunionAN $reunion): array
    {
        $base = $this->formatReunion($reunion);
        
        return array_merge($base, [
            'odj_convocation' => $reunion->odj_convocation,
            'odj_resume' => $reunion->odj_resume,
            'points_odj' => $reunion->points_odj,
            'participants_internes' => $reunion->participants_internes,
            'personnes_auditionnees' => $reunion->personnes_auditionnees,
            'format_reunion' => $reunion->format_reunion,
            'date_creation' => $reunion->date_creation?->format('d/m/Y'),
            'compte_rendu_ref' => $reunion->compte_rendu_ref,
            'reunion_internationale' => $reunion->reunion_internationale,
            'pays' => $reunion->pays_reunion_internationale,
        ]);
    }
}

