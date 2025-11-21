# Plan d'enrichissement des fiches Sénateurs

## 📊 État actuel (à vérifier sur le serveur)

### Données déjà importées via SQL
- ✅ **Profil** : nom, prénom, âge, profession, groupe, commission, email
- ❓ **Mandats** : `senateurs_mandats` (vue de `senat_senateurs_elusen`)
- ❓ **Commissions** : `senateurs_commissions` (vue de `senat_senateurs_memcom`)
- ❓ **Historique groupes** : `senateurs_historique_groupes` (vue de `senat_senateurs_memgrpsen`)
- ✅ **Votes individuels** : `senateurs_votes` (34423 votes)
- ✅ **Scrutins** : `senateurs_scrutins` (99 scrutins)
- ❓ **Amendements** : `amendements_senat` (à vérifier)
- ❓ **Questions** : `senateurs_questions` (table créée mais données ?)

### Données manquantes
- ❌ **Wikipedia** : photo, URL, extrait bio
- ❌ **Mandats locaux** : maire, conseiller départemental/régional
- ❌ **Études** : diplômes, formations

---

## 🎯 Plan d'action par priorité

### 1️⃣ PRIORITÉ HAUTE - Affichage immédiat ⚡

#### A. Enrichir Wikipedia (30 min)
```bash
# Commande à créer (similaire à ImportDeputesWikipedia)
php artisan enrich:senateurs-wikipedia --limit=348
```

**Fichiers à créer/modifier** :
- `app/Console/Commands/EnrichSenateursWikipedia.php`
- Utiliser l'API Wikipedia : `https://fr.wikipedia.org/w/api.php`
- Source : `https://fr.wikipedia.org/wiki/Liste_des_sénateurs_français_de_2023_à_2026`

#### B. Vérifier et afficher les données existantes (1h)
1. **Diagnostiquer les vues à 0** :
   ```sql
   -- Si les tables raw sont vides, c'est que l'import SQL n'a pas tout importé
   SELECT COUNT(*) FROM senat_senateurs_elusen;
   SELECT COUNT(*) FROM senat_senateurs_memcom;
   SELECT COUNT(*) FROM senat_senateurs_memgrpsen;
   ```

2. **Si vides → Re-importer la base SENATEURS** :
   ```bash
   ./scripts/import_senat_sql.sh senateurs --fresh
   ```

3. **Si pleines mais vues vides → Corriger les vues SQL** :
   - Vérifier les colonnes de jointure
   - Vérifier les filtres WHERE

---

### 2️⃣ PRIORITÉ MOYENNE - Pages détaillées 📄

#### C. Créer les pages Votes/Amendements/Activité (2h)
Copier/adapter depuis les pages Députés :

**Fichiers à créer** :
- `resources/js/Pages/Representants/Senateurs/Votes.vue`
- `resources/js/Pages/Representants/Senateurs/Amendements.vue`
- `resources/js/Pages/Representants/Senateurs/Activite.vue`

**Routes à ajouter** :
```php
// routes/web.php
Route::get('/senateurs/{matricule}/votes', [RepresentantANController::class, 'senateurVotes'])->name('senateurs.votes');
Route::get('/senateurs/{matricule}/amendements', [RepresentantANController::class, 'senateurAmendements'])->name('senateurs.amendements');
Route::get('/senateurs/{matricule}/activite', [RepresentantANController::class, 'senateurActivite'])->name('senateurs.activite');
```

**Méthodes controller à ajouter** :
- `senateurVotes()` : Liste des votes avec stats
- `senateurAmendements()` : Liste des amendements avec taux d'adoption
- `senateurActivite()` : Dashboard avec graphiques

---

### 3️⃣ PRIORITÉ BASSE - Données avancées 🔬

#### D. Importer Questions au Gouvernement (1h)
La base SQL `questions.zip` a déjà été importée, mais il faut :
1. Vérifier que la table `senat_questions_tam_questions` existe
2. Créer une vue `senateurs_questions` si elle n'existe pas
3. Ajouter une page dédiée pour afficher les questions

#### E. Lier dossiers législatifs AN ↔ Sénat (2h)
**Stratégie** :
- Matcher par numéro de dossier et session
- OU créer une table de correspondance manuelle
- Afficher la timeline bicamérale dans `DossierShow.vue`

**Colonne à ajouter** :
```php
// Migration pour dossiers_legislatifs_senat
$table->string('dossier_an_uid', 30)->nullable();
$table->foreign('dossier_an_uid')->references('uid')->on('dossiers_legislatifs_an');
```

---

## 📝 Ordre d'exécution recommandé

### Phase 1 : Diagnostic (10 min)
```bash
# Vérifier toutes les données disponibles
docker compose exec app php artisan tinker --execute="..."
```

### Phase 2 : Wikipedia (30 min)
```bash
# Créer la commande + lancer l'enrichissement
php artisan make:command EnrichSenateursWikipedia
php artisan enrich:senateurs-wikipedia
```

### Phase 3 : Corriger les vues vides (1h)
```bash
# Si mandats/commissions = 0, diagnostiquer et corriger
```

### Phase 4 : Pages détaillées (2h)
```bash
# Créer Votes.vue, Amendements.vue, Activite.vue
# Ajouter routes et méthodes controller
```

### Phase 5 : Questions et dossiers bicaméraux (3h)
```bash
# Importer questions + lier dossiers AN/Sénat
```

---

## 🎬 Prochaine étape immédiate

**Exécute sur le serveur** :
```bash
docker compose exec app php artisan tinker --execute="
\$sen = App\Models\Senateur::where('etat', 'ACTIF')->with(['mandats', 'commissions', 'historiqueGroupes'])->first();
echo 'Sénateur : ' . \$sen->nom_complet . '\n';
echo 'Mandats : ' . \$sen->mandats->count() . '\n';
echo 'Commissions : ' . \$sen->commissions->count() . '\n';
echo 'Historique groupes : ' . \$sen->historiqueGroupes->count() . '\n';
echo 'Votes : ' . \$sen->votesSenat()->count() . '\n';
"
```

**Si tout = 0** → On diagnostique et corrige les vues SQL
**Si > 0** → On passe à Wikipedia et pages détaillées

Dis-moi les résultats ! 🚀

