# TODO SÉNAT - Intégration finale

## ✅ TERMINÉ

1. Import SQL brut avec préfixes `senat_raw_*`
2. Création de 12 vues SQL Laravel-friendly
3. 9085 sénateurs importés
4. 34423 votes importés
5. 99 scrutins importés

---

## 🔴 URGENT - Sénateurs invisibles sur le site

### Problème
Les sénateurs ne s'affichent plus sur `/representants/senateurs`

### Diagnostic à faire
```bash
# 1. Vérifier le controller
docker compose exec app php artisan tinker --execute="
\$senateurs = App\Models\Senateur::limit(5)->get();
echo 'Sénateurs via Eloquent : ' . \$senateurs->count() . '\n';
foreach (\$senateurs as \$s) {
    echo '  - ' . \$s->nom_complet . '\n';
}
"

# 2. Vérifier les logs d'erreur
tail -50 storage/logs/laravel.log
```

### Solutions possibles
- [ ] Vérifier que le model `Senateur` utilise bien la vue `senateurs`
- [ ] Vérifier que `RepresentantController::senateurs()` filtre correctement
- [ ] Vider le cache Opcache : `sudo systemctl restart php8.2-fpm`

---

## 🚧 EN COURS - Dossiers législatifs bicaméraux

### État actuel
`LegislationController::showDossier()` cherche déjà un `DossierLegislatifSenat` via :
```php
$dossierSenat = \App\Models\DossierLegislatifSenat::where('dossier_an_uid', $uid)->first();
```

### Problème
❌ La colonne `dossier_an_uid` n'existe PAS dans la vue `dossiers_legislatifs_senat`

### Solution
**Option A : Matcher par numéro/session** (recommandé)
- Les dossiers législatifs ont souvent le même numéro AN/Sénat
- On peut matcher via `docnum` et `sesann`

**Option B : Créer une table de correspondance**
- Table `dossiers_bicameraux` (dossier_an_uid, dossier_senat_id)
- Import manuel ou automatique via regex sur les titres

### Actions
- [ ] Analyser la structure de `senat_dosleg_doc` pour comprendre le lien
- [ ] Créer une migration pour ajouter `dossier_an_uid` à la vue (si possible)
- [ ] OU créer un matcher intelligent dans le controller

---

## 📊 DIAGNOSTICS - Vues retournant 0

### Mandats sénateurs : 0
```bash
docker compose exec app php artisan tinker --execute="
echo 'senat_senateurs_elusen: ' . DB::table('senat_senateurs_elusen')->count() . '\n';
echo 'senateurs_mandats (vue): ' . DB::table('senateurs_mandats')->count() . '\n';
"
```

### Commissions sénateurs : 0
```bash
docker compose exec app php artisan tinker --execute="
echo 'senat_senateurs_memcom: ' . DB::table('senat_senateurs_memcom')->count() . '\n';
echo 'senat_senateurs_org (COM): ' . DB::table('senat_senateurs_org')->where('typorgcod', 'COM')->count() . '\n';
echo 'senateurs_commissions (vue): ' . DB::table('senateurs_commissions')->count() . '\n';
"
```

### Historique groupes : 0
```bash
docker compose exec app php artisan tinker --execute="
echo 'senat_senateurs_memgrpsen: ' . DB::table('senat_senateurs_memgrpsen')->count() . '\n';
echo 'senat_senateurs_org (GP): ' . DB::table('senat_senateurs_org')->where('typorgcod', 'GP')->count() . '\n';
echo 'senateurs_historique_groupes (vue): ' . DB::table('senateurs_historique_groupes')->count() . '\n';
"
```

**Hypothèse** : Les tables raw sont vides ou le mapping SQL est incorrect.

---

## 🎯 PROCHAINES ÉTAPES

### 1. Corriger l'affichage des sénateurs ⚡ URGENT
- [ ] Diagnostiquer pourquoi la liste est vide
- [ ] Vérifier le controller `RepresentantController::senateurs()`
- [ ] Tester `/representants/senateurs` sur le site

### 2. Enrichir les profils sénateurs 📖
- [ ] Lancer `php artisan enrich:senateurs-wikipedia`
- [ ] Ajouter photos, extraits bio, liens Wikipedia
- [ ] Vérifier que les profils s'affichent correctement

### 3. Intégrer les données Sénat dans la législation 📚
- [ ] Créer le matcher AN ↔ Sénat pour les dossiers
- [ ] Afficher les amendements Sénat dans les dossiers bicaméraux
- [ ] Afficher les scrutins Sénat dans la timeline

### 4. Compléter les données manquantes 🔍
- [ ] Vérifier pourquoi mandats/commissions/groupes = 0
- [ ] Re-importer si nécessaire
- [ ] Corriger les vues SQL si mapping incorrect

### 5. Adapter les pages frontend 🎨
- [ ] `/representants/senateurs` : Afficher tous les champs (groupe, commission, âge)
- [ ] `/representants/senateurs/{id}` : Profil complet avec mandats, commissions, votes
- [ ] `/legislation/dossiers/{uid}` : Timeline bicamérale AN + Sénat

---

## 📋 COMMANDES UTILES

### Diagnostic complet
```bash
./scripts/diagnostic_tables_senat.sh
```

### Re-créer les vues
```bash
docker compose exec -T app php artisan migrate:rollback --step=12
docker compose exec -T app php artisan migrate
```

### Nettoyer le cache
```bash
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan view:clear
sudo systemctl restart php8.2-fpm
```

### Tester les vues
```bash
docker compose exec app php artisan tinker --execute="
echo 'Sénateurs : ' . App\Models\Senateur::count() . '\n';
echo 'Sénateurs actifs : ' . App\Models\Senateur::where('etat', 'ACTIF')->count() . '\n';
\$sen = App\Models\Senateur::where('etat', 'ACTIF')->first();
if (\$sen) {
    echo 'Exemple : ' . \$sen->nom_complet . ' (' . \$sen->groupe_politique . ')\n';
    echo 'Mandats : ' . \$sen->mandats()->count() . '\n';
    echo 'Commissions : ' . \$sen->commissions()->count() . '\n';
    echo 'Votes : ' . \$sen->votesSenat()->count() . '\n';
}
"
```

---

## 🎬 ORDRE D'EXÉCUTION RECOMMANDÉ

1. **Fixer l'affichage des sénateurs** (5 min)
2. **Diagnostiquer les vues à 0** (10 min)
3. **Pull + deploy des corrections** (5 min)
4. **Enrichir Wikipedia** (30 min - en background)
5. **Adapter les controllers** (1h)
6. **Tester sur le site** (15 min)

---

## ❓ QUESTIONS EN SUSPENS

1. Pourquoi certaines tables raw du dump SENATEURS sont vides ?
2. Comment lier les dossiers AN et Sénat sans colonne explicite ?
3. Faut-il importer les bases QUESTIONS et DÉBATS (volumineuses) ?

