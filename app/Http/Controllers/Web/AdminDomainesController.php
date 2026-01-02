<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DomaineMinisteriel;
use App\Models\PosteMinisteriel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDomainesController extends Controller
{
    /**
     * Liste des postes à catégoriser
     */
    public function index(Request $request)
    {
        $domaines = DomaineMinisteriel::orderBy('nom')->get();
        
        // Statistiques
        $stats = [
            'total_postes' => PosteMinisteriel::count(),
            'postes_categorises' => PosteMinisteriel::whereNotNull('domaine_ministeriel_id')->count(),
            'postes_non_categorises' => PosteMinisteriel::whereNull('domaine_ministeriel_id')->count(),
            'fonctions_uniques' => PosteMinisteriel::distinct('fonction')->count('fonction'),
        ];
        
        // Filtres
        $query = PosteMinisteriel::with(['gouvernement', 'domaine', 'personne'])
            ->orderBy('fonction');
        
        if ($request->has('domaine_id')) {
            if ($request->domaine_id === 'null') {
                $query->whereNull('domaine_ministeriel_id');
            } elseif ($request->domaine_id) {
                $query->where('domaine_ministeriel_id', $request->domaine_id);
            }
        }
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('fonction', 'ilike', "%{$search}%");
        }
        
        // Grouper par fonction unique pour faciliter la catégorisation
        if ($request->boolean('group_by_fonction', true)) {
            $fonctions = PosteMinisteriel::select('fonction')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('MAX(domaine_ministeriel_id) as domaine_ministeriel_id')
                ->groupBy('fonction')
                ->orderBy('fonction');
            
            if ($request->has('domaine_id')) {
                if ($request->domaine_id === 'null') {
                    $fonctions->whereNull('domaine_ministeriel_id');
                } elseif ($request->domaine_id) {
                    $fonctions->where('domaine_ministeriel_id', $request->domaine_id);
                }
            }
            
            if ($request->has('search') && $request->search) {
                $fonctions->where('fonction', 'ilike', "%{$request->search}%");
            }
            
            $fonctions = $fonctions->paginate(50);
            
            // Enrichir avec le nom du domaine
            $domainesById = $domaines->keyBy('id');
            $fonctions->getCollection()->transform(function ($item) use ($domainesById) {
                $item->domaine = $item->domaine_ministeriel_id 
                    ? $domainesById->get($item->domaine_ministeriel_id) 
                    : null;
                return $item;
            });
            
            return Inertia::render('Admin/Domaines/Index', [
                'fonctions' => $fonctions,
                'domaines' => $domaines,
                'stats' => $stats,
                'filters' => [
                    'domaine_id' => $request->domaine_id ?? '',
                    'search' => $request->search ?? '',
                    'group_by_fonction' => true,
                ],
            ]);
        }
        
        $postes = $query->paginate(50);
        
        return Inertia::render('Admin/Domaines/Index', [
            'postes' => $postes,
            'domaines' => $domaines,
            'stats' => $stats,
            'filters' => [
                'domaine_id' => $request->domaine_id ?? '',
                'search' => $request->search ?? '',
                'group_by_fonction' => false,
            ],
        ]);
    }
    
    /**
     * Assigner un domaine à une fonction (tous les postes avec cette fonction)
     */
    public function assignerFonction(Request $request)
    {
        $request->validate([
            'fonction' => 'required|string',
            'domaine_id' => 'nullable|exists:domaines_ministeriels,id',
        ]);
        
        $count = PosteMinisteriel::where('fonction', $request->fonction)
            ->update(['domaine_ministeriel_id' => $request->domaine_id]);
        
        return back()->with('success', "{$count} poste(s) mis à jour.");
    }
    
    /**
     * Assigner un domaine à plusieurs fonctions en masse
     */
    public function assignerMasse(Request $request)
    {
        $request->validate([
            'fonctions' => 'required|array',
            'fonctions.*' => 'string',
            'domaine_id' => 'nullable|exists:domaines_ministeriels,id',
        ]);
        
        $count = PosteMinisteriel::whereIn('fonction', $request->fonctions)
            ->update(['domaine_ministeriel_id' => $request->domaine_id]);
        
        return back()->with('success', "{$count} poste(s) mis à jour.");
    }
    
    /**
     * Gestion des domaines ministériels
     */
    public function domaines()
    {
        $domaines = DomaineMinisteriel::withCount('postes')
            ->orderBy('nom')
            ->get();
        
        return Inertia::render('Admin/Domaines/Domaines', [
            'domaines' => $domaines,
        ]);
    }
    
    /**
     * Créer un nouveau domaine
     */
    public function storeDomaine(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:domaines_ministeriels,nom',
            'sigle' => 'nullable|string|max:20',
            'couleur' => 'nullable|string|max:10',
            'icone' => 'nullable|string|max:50',
        ]);
        
        DomaineMinisteriel::create($request->only(['nom', 'sigle', 'couleur', 'icone']));
        
        return back()->with('success', 'Domaine créé avec succès.');
    }
    
    /**
     * Mettre à jour un domaine
     */
    public function updateDomaine(Request $request, DomaineMinisteriel $domaine)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:domaines_ministeriels,nom,' . $domaine->id,
            'sigle' => 'nullable|string|max:20',
            'couleur' => 'nullable|string|max:10',
            'icone' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);
        
        $domaine->update($request->only(['nom', 'sigle', 'couleur', 'icone', 'description']));
        
        return back()->with('success', 'Domaine mis à jour.');
    }
    
    /**
     * Supprimer un domaine
     */
    public function destroyDomaine(DomaineMinisteriel $domaine)
    {
        // Détacher les postes
        PosteMinisteriel::where('domaine_ministeriel_id', $domaine->id)
            ->update(['domaine_ministeriel_id' => null]);
        
        $domaine->delete();
        
        return back()->with('success', 'Domaine supprimé.');
    }
    
    /**
     * Suggestions automatiques de catégorisation
     */
    public function suggestions()
    {
        $domaines = DomaineMinisteriel::orderBy('nom')->get();
        
        // Patterns pour chaque domaine
        $patterns = [
            'Intérieur' => ['intérieur', 'sécurité', 'police', 'immigration'],
            'Affaires étrangères' => ['affaires étrangères', 'europe', 'européen', 'coopération', 'francophonie'],
            'Justice' => ['justice', 'garde des sceaux', 'libertés'],
            'Armées' => ['armées', 'défense', 'anciens combattants'],
            'Économie et Finances' => ['économie', 'finances', 'budget', 'industrie', 'commerce'],
            'Éducation nationale' => ['éducation', 'enseignement', 'école', 'jeunesse'],
            'Enseignement supérieur et Recherche' => ['enseignement supérieur', 'recherche', 'université', 'innovation'],
            'Santé' => ['santé', 'sécurité sociale', 'hôpitaux'],
            'Travail et Emploi' => ['travail', 'emploi', 'formation professionnelle'],
            'Transition écologique' => ['écologie', 'environnement', 'énergie', 'développement durable', 'transition'],
            'Agriculture' => ['agriculture', 'pêche', 'alimentation', 'rural'],
            'Culture' => ['culture', 'communication', 'patrimoine', 'artistes'],
            'Sports' => ['sports', 'jeux olympiques'],
            'Outre-mer' => ['outre-mer', 'dom-tom'],
            'Cohésion des territoires' => ['territoires', 'logement', 'ville', 'aménagement'],
            'Solidarités' => ['solidarité', 'famille', 'personnes âgées', 'handicap', 'enfance'],
        ];
        
        $suggestions = [];
        
        foreach ($domaines as $domaine) {
            $keywords = $patterns[$domaine->nom] ?? [];
            if (empty($keywords)) continue;
            
            foreach ($keywords as $keyword) {
                $fonctions = PosteMinisteriel::whereNull('domaine_ministeriel_id')
                    ->where('fonction', 'ilike', "%{$keyword}%")
                    ->distinct('fonction')
                    ->pluck('fonction');
                
                foreach ($fonctions as $fonction) {
                    if (!isset($suggestions[$fonction])) {
                        $suggestions[$fonction] = [
                            'fonction' => $fonction,
                            'domaine_suggere' => $domaine,
                            'count' => PosteMinisteriel::where('fonction', $fonction)->count(),
                        ];
                    }
                }
            }
        }
        
        return Inertia::render('Admin/Domaines/Suggestions', [
            'suggestions' => array_values($suggestions),
            'domaines' => $domaines,
        ]);
    }
    
    /**
     * Appliquer toutes les suggestions
     */
    public function applySuggestions(Request $request)
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.fonction' => 'required|string',
            'assignments.*.domaine_id' => 'required|exists:domaines_ministeriels,id',
        ]);
        
        $count = 0;
        foreach ($request->assignments as $assignment) {
            $count += PosteMinisteriel::where('fonction', $assignment['fonction'])
                ->update(['domaine_ministeriel_id' => $assignment['domaine_id']]);
        }
        
        return back()->with('success', "{$count} poste(s) catégorisés.");
    }
}
