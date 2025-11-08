# 🎉 IMPORT COMPLET DES VOTES & ACTIVITÉ PARLEMENTAIRE

## ✅ Ce qui a été créé (8 novembre 2025)

### 📊 **1. Structure de données (3 tables)**

#### `votes_deputes`
- Position du député/sénateur sur chaque scrutin (pour/contre/abstention/absent)
- Résultat global du vote (adopté/rejeté)
- Contexte et lien vers le scrutin
- **Index optimisés** : depute_senateur_id, date_vote, position

#### `interventions_parlementaires`
- Toutes les prises de parole en séance/commission
- Contenu textuel + nombre de mots
- Liens vidéo et texte
- **Index optimisés** : depute_senateur_id, date_intervention, type

#### `questions_gouvernement`
- Questions écrites/orales au gouvernement
- Texte question + réponse (si disponible)
- Ministère concerné, délai de réponse
- **Index optimisés** : depute_senateur_id, statut, date_depot

---

### 🛠️ **2. Modèles Eloquent (3 modèles)**

#### `VoteDepute.php`
```php
// Scopes disponibles
VoteDepute::pour()->count();
VoteDepute::contre()->count();
VoteDepute::abstention()->count();
VoteDepute::adopte()->count();
VoteDepute::rejete()->count();

// Accesseurs
$vote->position_label; // "Pour", "Contre", etc.
$vote->resultat_label; // "Adopté", "Rejeté"
$vote->total_votants;  // pour + contre + abstentions
```

#### `InterventionParlementaire.php`
```php
// Accesseurs
$intervention->duree_minutes; // Conversion auto secondes → minutes
```

#### `QuestionGouvernement.php`
```php
// Scopes
QuestionGouvernement::ecrites()->count();
QuestionGouvernement::orales()->count();
QuestionGouvernement::repondues()->count();
QuestionGouvernement::enAttente()->count();

// Accesseurs
$question->delai_reponse_jours; // Calcul auto
```

---

### 🔗 **3. Relations ajoutées dans `DeputeSenateur`**

```php
$depute->votes()->get();           // Tous les votes
$depute->interventions()->get();   // Toutes les interventions
$depute->questions()->get();       // Toutes les questions

// Exemples de requêtes
$depute->votes()->pour()->count();
$depute->votes()->contre()->count();
$depute->interventions()->where('type', 'seance')->count();
$depute->questions()->repondues()->count();
```

---

### 🚀 **4. Commandes Artisan (2 commandes)**

#### `enrich:deputes-votes`
```bash
# Import complet
php artisan enrich:deputes-votes

# Test sur 10 députés
php artisan enrich:deputes-votes --limit=10

# Votes uniquement (skip interventions/questions)
php artisan enrich:deputes-votes --votes-only

# Interventions uniquement
php artisan enrich:deputes-votes --interventions-only

# Questions uniquement
php artisan enrich:deputes-votes --questions-only

# Un député spécifique
php artisan enrich:deputes-votes --depute=PA267350
```

#### `enrich:senateurs-votes`
```bash
# Même syntaxe que pour les députés
php artisan enrich:senateurs-votes
php artisan enrich:senateurs-votes --limit=5
```

**Fonctionnalités :**
- ✅ Pause de 2s entre chaque élu (rate limiting)
- ✅ Progress bar avec statistiques
- ✅ Gestion d'erreurs robuste
- ✅ Log des erreurs dans `storage/logs/laravel.log`
- ✅ Résumé détaillé à la fin

---

### 📜 **5. Scripts shell (2 scripts)**

#### `scripts/enrich_complete.sh`
Import complet de **TOUS** les députés et sénateurs.

**Durée estimée :** ~32 minutes
- 575 députés × 2s = ~20 min
- 348 sénateurs × 2s = ~12 min

**Contenu :**
- État initial (compteurs)
- Enrichissement députés
- Enrichissement sénateurs
- Résultat final avec statistiques
- Top 5 députés les plus actifs

#### `scripts/test_enrich_votes.sh`
Test rapide avec **3 députés + 2 sénateurs**.

**Durée estimée :** ~10 secondes

---

### 📚 **6. Documentation**

#### `docs/IMPORT_VOTES_COMPLET.md`
Documentation complète avec :
- Structure des tables
- Exemples de requêtes SQL
- Cas d'usage Eloquent
- Statistiques après import
- Dépannage

---

## 🚀 Comment utiliser ?

### **Étape 1 : Migrations**
```bash
docker compose exec app php artisan migrate --force
```

### **Étape 2 : Test rapide (optionnel)**
```bash
bash scripts/test_enrich_votes.sh
```

### **Étape 3 : Import complet**
```bash
bash scripts/enrich_complete.sh
```

### **Étape 4 : Vérification**
```sql
-- Total des données
SELECT COUNT(*) FROM votes_deputes;
SELECT COUNT(*) FROM interventions_parlementaires;
SELECT COUNT(*) FROM questions_gouvernement;

-- Top 5 députés les plus actifs
SELECT 
    ds.nom_complet,
    COUNT(vd.id) as nb_votes
FROM deputes_senateurs ds
JOIN votes_deputes vd ON vd.depute_senateur_id = ds.id
GROUP BY ds.nom_complet
ORDER BY nb_votes DESC
LIMIT 5;
```

---

## 📊 Données attendues après import

| Type | Quantité estimée |
|------|------------------|
| **Votes** | ~200 000 - 300 000 votes |
| **Interventions** | ~50 000 - 100 000 interventions |
| **Questions** | ~20 000 - 30 000 questions |

**Stockage estimé :** ~500 Mo

---

## 💡 Cas d'usage frontend

### 1. Page profil député
```php
Route::get('/deputes/{id}', function($id) {
    $depute = DeputeSenateur::with(['votes', 'interventions', 'questions'])
        ->findOrFail($id);
    
    return Inertia::render('Deputes/Show', [
        'depute' => $depute,
        'stats' => [
            'nb_votes' => $depute->votes->count(),
            'nb_pour' => $depute->votes->where('position', 'pour')->count(),
            'nb_contre' => $depute->votes->where('position', 'contre')->count(),
            'nb_abstentions' => $depute->votes->where('position', 'abstention')->count(),
            'nb_interventions' => $depute->interventions->count(),
            'nb_questions' => $depute->questions->count(),
            'taux_presence' => /* calcul */,
        ],
        'derniers_votes' => $depute->votes()
            ->orderBy('date_vote', 'desc')
            ->limit(10)
            ->get(),
    ]);
});
```

### 2. Analyse d'un scrutin
```php
Route::get('/scrutins/{numero}', function($numero) {
    $votes = VoteDepute::where('numero_scrutin', $numero)
        ->with('deputeSenateur.groupeParlementaire')
        ->get();
    
    $resultats = $votes->groupBy('position')->map->count();
    
    $par_groupe = $votes->groupBy('deputeSenateur.groupe_politique')
        ->map(function($groupeVotes) {
            return $groupeVotes->groupBy('position')->map->count();
        });
    
    return Inertia::render('Scrutins/Show', [
        'numero' => $numero,
        'titre' => $votes->first()->titre,
        'resultats' => $resultats,
        'par_groupe' => $par_groupe,
    ]);
});
```

### 3. Comparaison entre députés
```php
Route::get('/deputes/comparer', function(Request $request) {
    $ids = $request->input('ids'); // [1, 2, 3]
    
    $deputes = DeputeSenateur::with(['votes', 'interventions', 'questions'])
        ->whereIn('id', $ids)
        ->get();
    
    $comparaison = $deputes->map(function($d) {
        return [
            'nom' => $d->nom_complet,
            'groupe' => $d->groupe_politique,
            'nb_votes' => $d->votes->count(),
            'nb_pour' => $d->votes->where('position', 'pour')->count(),
            'nb_contre' => $d->votes->where('position', 'contre')->count(),
            'nb_interventions' => $d->interventions->count(),
            'nb_questions' => $d->questions->count(),
        ];
    });
    
    return Inertia::render('Deputes/Comparer', [
        'comparaison' => $comparaison,
    ]);
});
```

---

## ⚠️ Notes importantes

1. **Rate limiting :** Les APIs NosDéputés/Sénateurs limitent les requêtes. Ne pas réduire la pause de 2s !
2. **Mises à jour :** Relancer périodiquement (tous les mois) pour avoir les derniers votes
3. **Performance :** Les requêtes sont optimisées avec des index. Utiliser `->with()` pour éviter N+1
4. **API publique :** Ces données sont publiques et librement réutilisables

---

## 🎯 Prochaines étapes possibles

1. ✅ **Créer une page "Activité parlementaire"** sur le front
2. ✅ **Analyse des votes par thématique** (lier avec `thematiques_legislation`)
3. ✅ **Graphiques d'évolution** (votes dans le temps, présence)
4. ✅ **Comparateur de députés** (positions similaires/opposées)
5. ✅ **Alertes citoyennes** ("Votre député a voté sur...")
6. ✅ **Recherche full-text** dans interventions et questions

---

**🏛️ Félicitations ! Tu as maintenant une base de données COMPLÈTE de l'activité parlementaire française ! 🇫🇷**

