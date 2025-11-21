# 🚀 Déploiement Sénat Complet - 21 novembre 2025

## ✅ Ce qui a été fait (LOCAL - prêt à pusher)

### 1️⃣ Architecture SQL Views (12 migrations)
- ✅ `senateurs` (vue depuis `senat_senateurs_sen`)
- ✅ `senateurs_mandats` (vue depuis `senat_senateurs_elusen`)
- ✅ `senateurs_commissions` (vue depuis `senat_senateurs_memcom`)
- ✅ `senateurs_historique_groupes` (vue depuis `senat_senateurs_memgrpsen`)
- ✅ `senateurs_votes` (vue depuis `senat_senateurs_votes`)
- ✅ `senateurs_scrutins` (vue depuis `senat_senateurs_scr`)
- ✅ `amendements_senat` (vue depuis `senat_ameli_amd`)
- ✅ `dossiers_legislatifs_senat` (vue depuis `senat_dosleg_doc`)
- ✅ Alias views (`votes_senat`, `scrutins_senat`)

### 2️⃣ Models Laravel adaptés
- ✅ `Senateur` (relations mandats, commissions, groupes, votes)
- ✅ `SenateurMandat`, `SenateurCommission`, `SenateurHistoriqueGroupe`
- ✅ `VoteSenat`, `ScrutinSenat`, `AmendementSenat`, `DossierLegislatifSenat`

### 3️⃣ Controllers corrigés
- ✅ `RepresentantANController`: Ajout méthodes sénateurs (index, show)
- ✅ `DashboardController`: Corriger relation `deputes()` vers `ActeurAN`
- ✅ `GroupeParlementaire`: Relation `deputes()` pointe vers `ActeurAN` (plus `DeputeSenateur`)

### 4️⃣ Vues frontend
- ✅ `Senateurs/Index.vue` (liste ISO députés)
- ✅ `Senateurs/Show.vue` (profil détaillé avec mandats, commissions, groupes)

### 5️⃣ Commandes import
- ✅ `ImportSenatSQL.php` (avec préfixe automatique `senat_raw_*`)
- ✅ Script shell `import_senat_sql.sh` (3 modes: Essentiel, Complet, Intégral)

### 6️⃣ Seeders désactivés
- ✅ `DeputesSenateursSeeder.php` → `.disabled` (plus de fake data)

---

## 📦 COMMANDES DÉPLOIEMENT PRODUCTION

### Étape 1 : Pull et build
```bash
cd /opt/civicdash
git pull origin main
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec node npm run build
```

### Étape 2 : Migrations (créer les vues SQL)
```bash
docker compose exec app php artisan migrate --force
```

### Étape 3 : Vérifier les vues créées
```bash
docker compose exec app php artisan tinker
# Dans Tinker :
\App\Models\Senateur::count();        # Devrait retourner 348
\App\Models\SenateurMandat::count();  # Devrait retourner ~500+
```

### Étape 4 : Cache et redémarrage
```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan opcache:clear
sudo systemctl restart php8.2-fpm
```

### Étape 5 : Tester
- `https://demo.objectif2027.fr` → Dashboard (ne devrait plus crasher)
- `https://demo.objectif2027.fr/representants/senateurs` → Liste 348 sénateurs
- `https://demo.objectif2027.fr/representants/senateurs/19760E` → Profil Larcher

---

## 🔮 Prochaines étapes (TODO après déploiement)

### 1️⃣ Enrichissement Wikipedia (30 min)
```bash
docker compose exec app php artisan enrich:senateurs-wikipedia --limit=348
```

### 2️⃣ Pages détaillées sénateurs (à créer)
- `/senateurs/{id}/votes` (liste des votes du sénateur)
- `/senateurs/{id}/amendements` (liste des amendements)
- `/senateurs/{id}/activite` (dashboard activité)
- `/senateurs/{id}/questions` (questions au Gouvernement)

### 3️⃣ Import Questions au Gouvernement
```bash
docker compose exec app php artisan import:senat-sql questions --fresh --no-interaction
```

### 4️⃣ Import Débats (optionnel, lourd)
```bash
docker compose exec app php artisan import:senat-sql debats --fresh --no-interaction
```

### 5️⃣ Liaison dossiers bicaméraux
- Créer `DossierLegislatifAN->dossierSenat()` relation
- Créer `DossierLegislatifSenat->dossierAN()` relation
- Afficher timeline bicamérale (AN + Sénat) sur `/legislation/dossiers/{uid}`

---

## 🎯 Résumé ultra-rapide

**AVANT** : 
- Sénateurs = fake data dans `deputes_senateurs` (obsolète)
- Dashboard crashe
- Aucune donnée réelle

**APRÈS** :
- Sénateurs = vraies données SQL Sénat (348 actifs)
- 12 vues SQL créées automatiquement
- Models Laravel fonctionnels
- Frontend ISO députés
- Dashboard opérationnel

**TEMPS TOTAL DÉPLOIEMENT** : ~5 minutes
**TEMPS ENRICHISSEMENT WIKIPEDIA** : ~30 minutes

---

## 📚 Fichiers modifiés (ce commit)

### Nouveaux fichiers
- `database/migrations/2025_11_21_030000_transform_senateurs_to_view.php` (+12 migrations)
- `INTEGRATION_SENAT_COMPLETE.md`
- `DEPLOIEMENT_SENAT_COMPLET_21NOV2025.md` (ce fichier)

### Fichiers modifiés
- `app/Models/GroupeParlementaire.php` (relation `deputes()` → `ActeurAN`)
- `app/Models/Senateur.php` (relations + accessors)
- `app/Http/Controllers/Web/RepresentantANController.php` (méthodes sénateurs)
- `resources/js/Pages/Representants/Senateurs/Index.vue`
- `resources/js/Pages/Representants/Senateurs/Show.vue`

### Fichiers supprimés
- `app/Console/Commands/EnrichSenateurWikipedia.php` (ancien, obsolète)
- `database/seeders/DeputesSenateursSeeder.php` (renommé → `.disabled`)

---

## ⚠️ Points de vigilance

1. **Ne PAS re-importer les SQL dumps maintenant** → Les vues pointent vers tables `senat_raw_*` déjà existantes
2. **Pas besoin de seeders** → Toutes les données viennent des dumps SQL
3. **Wikipedia optionnel** → Peut être fait après le déploiement, pas bloquant
4. **PHP 8.2+ requis** → Pour les propriétés readonly et autres syntaxes modernes

---

**Dernier commit** : `95bc238f8` (21 nov 2025)
**Prêt à déployer** : ✅ OUI

