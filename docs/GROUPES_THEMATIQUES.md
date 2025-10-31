# Groupes Parlementaires & Thématiques Législatives

## 📋 Vue d'ensemble

Cette fonctionnalité enrichit CivicDash avec deux axes majeurs :
1. **Groupes parlementaires** : Analyse des votes par groupe politique
2. **Thématiques législatives** : Classification automatique des propositions de loi

---

## ✅ IMPLÉMENTATION COMPLÈTE (Backend 100%)

### 1. Base de données

#### Tables créées :
- `groupes_parlementaires` : Groupes politiques Assemblée/Sénat
- `thematiques_legislation` : 15 catégories thématiques
- `proposition_loi_thematique` : Pivot propositions ↔ thématiques
- `votes_groupes_parlementaires` : Votes détaillés par groupe

#### Migrations :
```bash
database/migrations/
  └── 2025_10_31_120001_create_groupes_parlementaires_table.php
  └── 2025_10_31_120002_create_thematiques_legislation_table.php
  └── 2025_10_31_120003_create_proposition_loi_thematique_table.php
  └── 2025_10_31_120004_create_votes_groupes_parlementaires_table.php
```

### 2. Models Eloquent

```php
app/Models/
  ├── GroupeParlementaire.php (NOUVEAU)
  ├── ThematiqueLegislation.php (NOUVEAU)
  ├── VoteGroupeParlementaire.php (NOUVEAU)
  ├── PropositionLoi.php (enrichi avec relation thematiques())
  └── VoteLegislatif.php (enrichi avec relation votesGroupes())
```

**Fonctionnalités des models :**
- `GroupeParlementaire` :
  - `getStatistiquesVote()` : Stats de vote du groupe
  - `getThematiquesFavorites()` : Thématiques préférées
  - `isGauche()`, `isDroite()`, `isCentre()` : Helpers position

- `ThematiqueLegislation` :
  - `calculerScore()` : Scoring 0-100 pour un texte
  - `getStatistiques()` : Stats de la thématique
  - `getGroupesActifs()` : Groupes les plus actifs

- `VoteGroupeParlementaire` :
  - `calculerDiscipline()` : % de cohésion du groupe
  - `aVoteAvecSucces()` : Vote dans le sens du résultat

### 3. Services

#### ThematiqueDetectionService
```php
app/Services/ThematiqueDetectionService.php
```

**Méthodes principales :**
- `detecter(PropositionLoi)` : Détection automatique par mots-clés
- `detecterBatch(Collection)` : Traitement en masse
- `recalculer(PropositionLoi)` : Recalcul thématiques
- `attacherManuellement()` : Attribution manuelle
- `getStatistiques()` : Dashboard admin

**Algorithme de détection :**
- Analyse du titre (poids 3x)
- Analyse du résumé (poids 2x)
- Analyse du texte intégral (poids 1x)
- Score de pertinence 0-100
- Seuil minimum : 15
- Seuil principal : 40

#### LegislationService (enrichi)
```php
app/Services/LegislationService.php
```

**Nouvelles méthodes :**
- `getGroupesParlementaires(source, legislature)`
- `getVoteDetailsByGroupe(source, scrutin, legislature)`
- `getDeputesByGroupe(source, sigle, legislature)`

### 4. Controllers API

#### GroupesParlementairesController
```php
app/Http/Controllers/Api/GroupesParlementairesController.php
```

**Endpoints :**
- `GET /api/groupes-parlementaires` : Liste
- `GET /api/groupes-parlementaires/{id}` : Détails
- `GET /api/groupes-parlementaires/{id}/statistiques` : Stats
- `GET /api/groupes-parlementaires/{id}/membres` : Députés/sénateurs
- `GET /api/groupes-parlementaires/{id}/votes` : Votes récents
- `GET /api/groupes-parlementaires/comparaison?ids=1,2,3` : Comparaison
- `POST /api/groupes-parlementaires/sync` : Synchronisation API

#### ThematiquesController
```php
app/Http/Controllers/Api/ThematiquesController.php
```

**Endpoints :**
- `GET /api/thematiques` : Liste
- `GET /api/thematiques/{code}` : Détails
- `GET /api/thematiques/{code}/propositions` : Propositions
- `GET /api/thematiques/populaires` : Top thématiques
- `GET /api/thematiques/statistiques` : Stats globales
- `POST /api/thematiques/detecter` : Détection
- `POST /api/thematiques/detecter-batch` : Détection en masse
- `POST /api/thematiques/attacher` : Attachement manuel
- `DELETE /api/thematiques/detacher` : Détachement
- `POST /api/thematiques/recalculer` : Recalcul

### 5. Routes API

```php
routes/api.php (15 nouvelles routes)
```

### 6. Commandes Artisan

#### ImportGroupesCommand
```bash
php artisan groupes:import [--source=assemblee] [--legislature=17] [--force]
```

Import des groupes parlementaires depuis les APIs Assemblée/Sénat.

#### DetectThematiquesCommand
```bash
# Afficher les statistiques
php artisan thematiques:detect

# Détecter pour les propositions sans thématique
php artisan thematiques:detect --all

# Recalculer toutes les thématiques
php artisan thematiques:detect --recalculate

# Détecter pour une proposition spécifique
php artisan thematiques:detect --id=123

# Limiter le nombre de propositions à traiter
php artisan thematiques:detect --all --limit=50
```

### 7. Seeder

#### ThematiqueLegislationSeeder
```bash
php artisan db:seed --class=ThematiqueLegislationSeeder
```

**15 thématiques créées :**
1. 🛡️ Sécurité & Justice (SECU)
2. 💰 Finance & Fiscalité (FISC)
3. 🏥 Santé & Protection sociale (SANTE)
4. 🎓 Éducation & Recherche (EDUC)
5. 🌍 Environnement & Climat (ENVT)
6. 🏭 Économie & Entreprises (ECO)
7. 🏠 Logement & Urbanisme (LOG)
8. 🌾 Agriculture & Alimentation (AGRI)
9. ⚡ Énergie & Transports (TRANS)
10. 🌐 Numérique & Technologies (NUM)
11. 🗳️ Institutions & Démocratie (INST)
12. 🌍 International & Défense (INTER)
13. 🎭 Culture & Médias (CULT)
14. ⚖️ Droits & Libertés (DROIT)
15. 👥 Immigration & Intégration (IMMIG)

Chaque thématique inclut :
- Nom + description
- Code unique
- Couleur hex + icône emoji
- 10-20 mots-clés
- Synonymes

---

## 🎨 FRONTEND (En cours)

### Pages Vue créées :

#### 1. Groupes/Index.vue ✅
- Liste des groupes avec filtres par position politique
- Affichage : nom, sigle, couleur, nombre de membres
- Lien vers page détail

### Pages Vue à créer :

#### 2. Groupes/Show.vue
```vue
<!-- resources/js/Pages/Groupes/Show.vue -->
```

**Sections nécessaires :**
- Infos groupe (nom, président, nb membres)
- Statistiques de vote (cohésion, positions)
- Graphique évolution
- Liste des députés/sénateurs
- Thématiques favorites
- Historique votes récents

#### 3. Thematiques/Index.vue
```vue
<!-- resources/js/Pages/Thematiques/Index.vue -->
```

**Affichage :**
- Grille des 15 thématiques
- Couleur + icône + nom
- Nombre de propositions
- Recherche par nom

#### 4. Thematiques/Show.vue
```vue
<!-- resources/js/Pages/Thematiques/Show.vue -->
```

**Sections :**
- Détails thématique
- Statistiques (adoptées, rejetées, en cours)
- Liste des propositions
- Groupes les plus actifs
- Graphiques

### Composants Vue à créer :

#### 1. HemicycleChart.vue
Visualisation en hémicycle des sièges par groupe (SVG interactif)

#### 2. GroupeVoteChart.vue
Graphique barre empilée pour les votes par groupe (pour/contre/abstention)

#### 3. ThematiqueCard.vue
Carte réutilisable pour afficher une thématique

### Enrichissement Legislation/Show.vue

Ajouter une section "Vote par groupe" :
```vue
<!-- Section à ajouter dans resources/js/Pages/Legislation/Show.vue -->

<div v-if="voteGroupes && voteGroupes.length > 0" class="mt-8">
  <h3 class="text-xl font-bold mb-4">Vote par groupe</h3>
  
  <GroupeVoteChart :votes="voteGroupes" />
  
  <div class="mt-4 space-y-2">
    <div v-for="vote in voteGroupes" :key="vote.groupe_id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
      <div class="flex items-center gap-3">
        <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: vote.groupe.couleur_hex }"></div>
        <span class="font-medium">{{ vote.groupe.nom }}</span>
      </div>
      <div class="flex items-center gap-4 text-sm">
        <span class="text-green-600">{{ vote.pour }} pour</span>
        <span class="text-red-600">{{ vote.contre }} contre</span>
        <span class="text-gray-500">{{ vote.abstention }} abstention</span>
      </div>
    </div>
  </div>
</div>
```

---

## 📦 INSTALLATION & UTILISATION

### 1. Migrations

```bash
# Lancer les migrations
php artisan migrate

# Seed les thématiques
php artisan db:seed --class=ThematiqueLegislationSeeder
```

### 2. Import des groupes

```bash
# Importer les groupes de l'Assemblée
php artisan groupes:import --source=assemblee

# Importer les groupes du Sénat
php artisan groupes:import --source=senat
```

### 3. Détection des thématiques

```bash
# Détecter pour toutes les propositions sans thématique
php artisan thematiques:detect --all

# Détecter pour une proposition spécifique
php artisan thematiques:detect --id=1

# Voir les statistiques
php artisan thematiques:detect
```

### 4. Routes web (à ajouter)

```php
// routes/web.php

Route::prefix('legislation')->name('legislation.')->group(function () {
    // Groupes
    Route::get('/groupes', function () {
        return Inertia::render('Groupes/Index', ['source' => 'assemblee']);
    })->name('groupes.index');
    
    Route::get('/groupes/{id}', function ($id) {
        return Inertia::render('Groupes/Show', ['groupeId' => $id]);
    })->name('groupes.show');
    
    // Thématiques
    Route::get('/thematiques', function () {
        return Inertia::render('Thematiques/Index');
    })->name('thematiques.index');
    
    Route::get('/thematiques/{code}', function ($code) {
        return Inertia::render('Thematiques/Show', ['code' => $code]);
    })->name('thematiques.show');
});
```

---

## 🧪 TESTS

### Tests API

```bash
# Groupes
curl http://localhost:7777/api/groupes-parlementaires
curl http://localhost:7777/api/groupes-parlementaires/1
curl http://localhost:7777/api/groupes-parlementaires/1/statistiques
curl http://localhost:7777/api/groupes-parlementaires/comparaison?ids=1,2,3

# Thématiques
curl http://localhost:7777/api/thematiques
curl http://localhost:7777/api/thematiques/SECU
curl http://localhost:7777/api/thematiques/SECU/propositions
curl http://localhost:7777/api/thematiques/populaires
curl http://localhost:7777/api/thematiques/statistiques
```

### Tests détection

```bash
# Tester sur une proposition
php artisan tinker

$proposition = \App\Models\PropositionLoi::first();
$service = app(\App\Services\ThematiqueDetectionService::class);
$thematiques = $service->detecter($proposition);

foreach ($thematiques as $item) {
    echo "{$item['thematique']->nom} : {$item['score']}%\n";
}
```

---

## 📊 STATISTIQUES

### Fichiers créés :
- **Migrations** : 4
- **Models** : 3 nouveaux + 2 enrichis
- **Services** : 2 (1 nouveau + 1 enrichi)
- **Controllers** : 2
- **Commands** : 2
- **Seeders** : 1
- **Routes API** : 15
- **Pages Vue** : 1/4 (25%)
- **Composants Vue** : 0/3 (0%)

### Lignes de code :
- Backend : ~3500 lignes ✅
- Frontend : ~150 lignes (25%) 🚧

### Temps estimé :
- Backend : 3h30 ✅ TERMINÉ
- Frontend : 2h30 🚧 EN COURS
- Total : 6h

---

## 🔮 PROCHAINES ÉTAPES

### Priorité 1 : Compléter le frontend (2h)
1. Créer Groupes/Show.vue
2. Créer Thematiques/Index.vue
3. Créer Thematiques/Show.vue
4. Créer les 3 composants (HemicycleChart, GroupeVoteChart, ThematiqueCard)
5. Enrichir Legislation/Show.vue

### Priorité 2 : Améliorations (optionnelles)
1. Hémicycle interactif avec D3.js ou SVG custom
2. Graphiques avancés avec Chart.js
3. Export PDF des statistiques
4. Notifications sur nouvelles thématiques
5. Comparaison avancée de groupes

### Priorité 3 : Intégration Légifrance (Axe 2)
Voir plan complet dans `/tmp/plan_legislation_avancee.md`

---

## 🎯 IMPACT & VALEUR AJOUTÉE

### Pour les citoyens :
- ✅ Comprendre les positions des groupes politiques
- ✅ Identifier les thématiques des propositions de loi
- ✅ Comparer les groupes entre eux
- ✅ Suivre les thématiques qui les intéressent
- ✅ Voir quels groupes sont actifs sur quelles thématiques

### Pour la plateforme :
- ✅ Différenciation forte vs autres plateformes
- ✅ Classification automatique (gain de temps)
- ✅ Analyse approfondie du paysage législatif
- ✅ Meilleure UX (filtres, recherche par thématique)
- ✅ Base solide pour Axe 2 (Légifrance)

### Métriques attendues :
- Taux de couverture : > 90% des propositions avec thématique
- Précision : > 80% (thématique principale correcte)
- Performance : Détection < 100ms par proposition
- Utilisation : +30% engagement sur pages législatives

---

## 📞 SUPPORT

Pour toute question ou problème :
1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Tester les commandes Artisan
3. Vérifier les migrations : `php artisan migrate:status`
4. Consulter les stats : `php artisan thematiques:detect`

---

**Créé le** : 31 octobre 2025  
**Version** : 1.0.0  
**Statut** : Backend 100% ✅ | Frontend 25% 🚧

