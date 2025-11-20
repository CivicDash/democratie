# 🎯 IMPLÉMENTATION PONT PROPOSITIONS ↔ DONNÉES AN

## ✅ CE QUI EST FAIT

1. ✅ Migration `2025_11_20_150000_add_dossier_link_to_propositions.php`
2. ✅ Modèle `PropositionLoi` : Relations `dossierLegislatif()` et `scrutinAN()`
3. ✅ Menu révisé dans `AuthenticatedLayout.vue`

---

## 🔄 EN COURS : LegislationController

### Code à ajouter à `app/Http/Controllers/Web/LegislationController.php`

**Remplacer la méthode `show()` existante (lignes 121-172) par :**

```php
/**
 * Afficher une proposition de loi
 * 
 * GET /legislation/{proposition}
 */
public function show(PropositionLoi $proposition): Response
{
    $proposition->load([
        'amendements' => function ($query) {
            $query->orderBy('date_depot', 'desc')->limit(20);
        },
        'votes' => function ($query) {
            $query->orderBy('date_vote', 'desc')->limit(10);
        },
        'votesCitoyens',
        'dossierLegislatif',
        'scrutinAN',
    ]);

    // Vérifier si liée à des données réelles AN
    $hasRealData = $proposition->hasRealData();
    
    $realData = null;
    if ($hasRealData && $proposition->dossierLegislatif) {
        $realData = $this->getRealLegislativeData($proposition);
    }

    return Inertia::render('Legislation/Show', [
        'proposition' => [
            'id' => $proposition->id,
            'numero' => $proposition->numero,
            'titre' => $proposition->titre,
            'resume' => $proposition->resume,
            'texte' => $proposition->texte,
            'source' => $proposition->source,
            'statut' => $proposition->statut,
            'theme' => $proposition->theme,
            'auteurs' => $proposition->auteurs,
            'date_depot' => $proposition->date_depot,
            'date_discussion' => $proposition->date_discussion,
            'url_dossier' => $proposition->url_dossier,
            'nb_amendements' => $proposition->amendements->count(),
            'nb_signataires' => $proposition->nb_signataires,
            'dossier_legislatif_uid' => $proposition->dossier_legislatif_uid,
            'scrutin_an_uid' => $proposition->scrutin_an_uid,
        ],
        'amendements' => $proposition->amendements->map(function ($amendement) {
            return [
                'id' => $amendement->id,
                'numero' => $amendement->numero,
                'auteur' => $amendement->auteur,
                'texte' => $amendement->texte,
                'statut' => $amendement->statut,
                'date_depot' => $amendement->date_depot,
            ];
        }),
        'votes' => $proposition->votes->map(function ($vote) {
            return [
                'id' => $vote->id,
                'libelle' => $vote->libelle,
                'date' => $vote->date,
                'pour' => $vote->pour,
                'contre' => $vote->contre,
                'abstentions' => $vote->abstentions,
            ];
        }),
        'hasRealData' => $hasRealData,
        'realData' => $realData,
        'similar' => [],
    ]);
}
```

**Ajouter ces 3 méthodes helper à la fin de la classe (avant le dernier `}`) :**

```php
/**
 * Récupérer les données législatives réelles depuis l'AN
 */
protected function getRealLegislativeData(PropositionLoi $proposition): array
{
    $dossier = $proposition->dossierLegislatif;
    
    // Récupérer les textes législatifs
    $textes = $dossier->textesLegislatifs()
        ->orderBy('created_at')
        ->get();
    
    // Récupérer les scrutins liés
    $scrutins = ScrutinAN::query()
        ->where('legislature', $dossier->legislature)
        ->orderBy('date_scrutin')
        ->limit(20)
        ->get();
    
    // Récupérer les amendements
    $amendements = AmendementAN::whereHas('texte', function ($q) use ($dossier) {
        $q->where('dossier_ref', $dossier->uid);
    })
    ->with('auteur')
    ->latest()
    ->limit(50)
    ->get();
    
    // Construire la timeline
    $timeline = $this->buildRealTimeline($dossier, $textes, $scrutins);
    
    // Votes par groupe (si scrutin principal)
    $votesParGroupe = [];
    if ($proposition->scrutinAN) {
        $votesParGroupe = $this->getVotesParGroupe($proposition->scrutinAN);
    } elseif ($scrutins->isNotEmpty()) {
        $votesParGroupe = $this->getVotesParGroupe($scrutins->first());
    }
    
    return [
        'dossier' => [
            'uid' => $dossier->uid,
            'titre' => $dossier->titre,
            'titre_court' => $dossier->titre_court,
            'legislature' => $dossier->legislature,
        ],
        'textes' => $textes->map(fn($t) => [
            'uid' => $t->uid,
            'titre' => $t->titre,
            'type' => $t->type ?? 'Texte',
            'created_at' => $t->created_at?->format('d/m/Y'),
        ]),
        'scrutins' => $scrutins->map(fn($s) => [
            'uid' => $s->uid,
            'numero' => $s->numero,
            'titre' => $s->titre,
            'date' => $s->date_scrutin?->format('d/m/Y'),
            'pour' => $s->pour,
            'contre' => $s->contre,
            'abstentions' => $s->abstentions,
            'resultat_libelle' => $s->resultat_libelle,
        ]),
        'amendements' => $amendements->map(fn($a) => [
            'uid' => $a->uid,
            'numero' => $a->numero,
            'auteur_nom' => $a->auteur ? ($a->auteur->prenom . ' ' . $a->auteur->nom) : 'Inconnu',
            'dispositif' => substr($a->dispositif ?? '', 0, 200),
            'etat_libelle' => $a->etat_libelle,
        ]),
        'timeline' => $timeline,
        'votesParGroupe' => $votesParGroupe,
        'stats' => [
            'nb_textes' => $textes->count(),
            'nb_scrutins' => $scrutins->count(),
            'nb_amendements' => $amendements->count(),
            'nb_amendements_adoptes' => $amendements->where('etat_code', 'adopté')->count(),
        ],
    ];
}

/**
 * Construire la timeline réelle du processus législatif
 */
protected function buildRealTimeline($dossier, $textes, $scrutins): array
{
    $events = [];
    
    // Dépôt du dossier
    if ($dossier->created_at) {
        $events[] = [
            'date' => $dossier->created_at->format('Y-m-d'),
            'label' => 'Dépôt du dossier',
            'type' => 'depot',
            'icon' => '📝',
        ];
    }
    
    // Textes législatifs
    foreach ($textes as $texte) {
        $events[] = [
            'date' => $texte->created_at->format('Y-m-d'),
            'label' => $texte->titre ?: 'Texte législatif',
            'type' => 'texte',
            'icon' => '📄',
        ];
    }
    
    // Scrutins
    foreach ($scrutins as $scrutin) {
        $events[] = [
            'date' => $scrutin->date_scrutin->format('Y-m-d'),
            'label' => 'Scrutin n°' . $scrutin->numero,
            'type' => 'scrutin',
            'icon' => '🗳️',
            'resultat' => $scrutin->resultat_libelle,
        ];
    }
    
    // Trier par date
    usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));
    
    return $events;
}

/**
 * Récupérer les votes par groupe parlementaire
 */
protected function getVotesParGroupe(ScrutinAN $scrutin): array
{
    $groupeService = app(\App\Services\GroupeParlementaireService::class);
    
    $votesParGroupe = VoteIndividuelAN::where('scrutin_ref', $scrutin->uid)
        ->whereNotNull('groupe_ref')
        ->with('groupe')
        ->get()
        ->groupBy('groupe_ref')
        ->map(function ($votes, $groupeRef) use ($groupeService) {
            $groupe = $votes->first()->groupe;
            
            $pour = $votes->where('position', 'pour')->count();
            $contre = $votes->where('position', 'contre')->count();
            $abstention = $votes->where('position', 'abstention')->count();
            $total = $votes->count();
            
            return [
                'groupe_ref' => $groupeRef,
                'groupe_nom' => $groupe?->libelle ?? 'Inconnu',
                'groupe_sigle' => $groupe?->libelleAbrev ?? '?',
                'groupe_color' => $groupeService->getColor($groupe?->libelleAbrev ?? ''),
                'pour' => $pour,
                'contre' => $contre,
                'abstention' => $abstention,
                'total' => $total,
                'pour_percent' => $total > 0 ? round(($pour / $total) * 100, 1) : 0,
            ];
        })
        ->values()
        ->toArray();
    
    return $votesParGroupe;
}
```

---

## 📋 ÉTAPES SUIVANTES

1. ⏳ Modifier `LegislationController.php` (code ci-dessus)
2. ⏳ Modifier `resources/js/Pages/Legislation/Show.vue` pour afficher `realData`
3. ⏳ Lancer migration sur serveur
4. ⏳ Lier manuellement quelques propositions à des dossiers pour tester

---

## 🧪 TEST

```bash
# Lancer migration
php artisan migrate

# Lier une proposition test
php artisan tinker
$prop = PropositionLoi::first();
$dossier = DossierLegislatifAN::first();
$prop->dossier_legislatif_uid = $dossier->uid;
$prop->save();

# Vérifier
$prop->hasRealData(); // true
$prop->dossierLegislatif; // DossierLegislatifAN
```

---

## 🎯 RÉSULTAT ATTENDU

Page proposition enrichie avec :
- ✅ Timeline réelle des étapes
- ✅ Scrutins officiels avec résultats
- ✅ Amendements réels
- ✅ Votes par groupe politique
- ✅ Statistiques précises
- ✅ Liens vers pages détaillées

