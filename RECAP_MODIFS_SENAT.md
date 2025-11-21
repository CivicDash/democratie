# 📋 Récapitulatif des modifications Sénat - Prêt à pusher

## ✅ Commits locaux (2)

1. **`ac988bf`** - `fix(senat): Correction affichage liste sénateurs + nettoyage code`
   - Vue `senateurs`: Ajout colonne 'id' mappée à senmat (PK Laravel)
   - Model `Senateur`: PK changée de 'matricule' à 'id'
   - `RepresentantANController`: photo_url → wikipedia_photo, profession → description_profession
   - `RepresentantController`: Méthodes obsolètes commentées
   - Documentation déploiement ajoutée

2. **`1b1f78b`** - `fix(senat): Supprimer code orphelin dans RepresentantController`
   - Résout ParseError "unexpected single-quoted string 'nom'"

---

## 📁 Fichiers modifiés

### Migrations SQL
- `database/migrations/2025_11_21_030000_transform_senateurs_to_view.php`
  - Ajout `sen.senmat AS id` pour compatibilité Laravel

### Models
- `app/Models/Senateur.php`
  - `protected $primaryKey = 'id'` (au lieu de 'matricule')

### Controllers
- `app/Http/Controllers/Web/RepresentantANController.php`
  - Correction `photo_url` → `wikipedia_photo`
  - Correction `profession` → `description_profession`

- `app/Http/Controllers/Web/RepresentantController.php`
  - Suppression méthodes obsolètes `senateurs()` et `showSenateur()`
  - Code orphelin supprimé

### Documentation
- `DEPLOY_FIX_SENATEURS.md` (nouveau)
- `TODO_SENAT_FINAL.md` (nouveau)

---

## 🚀 Commande pour pusher

```bash
git push origin main
```

---

## 🔧 À exécuter sur le serveur APRÈS le push

```bash
cd /opt/civicdash

# 1. Supprimer tables alias
docker compose exec -T app php artisan tinker --execute="
DB::statement('DROP TABLE IF EXISTS votes_senat CASCADE');
DB::statement('DROP TABLE IF EXISTS scrutins_senat CASCADE');
echo '✅ Tables alias supprimées\n';
"

# 2. Pull + Deploy
git pull origin main
./deploy.sh

# 3. Vérifier
docker compose exec app php artisan tinker --execute="
echo 'Sénateurs actifs : ' . App\Models\Senateur::where('etat', 'ACTIF')->count() . '\n';
\$sen = App\Models\Senateur::where('etat', 'ACTIF')->first();
if (\$sen) {
    echo 'Exemple : ' . \$sen->nom_complet . '\n';
    echo 'Groupe : ' . \$sen->groupe_politique . '\n';
}
"

# 4. Tester sur le site
# https://demoscratos.fr/representants/senateurs
```

---

## 🔍 Diagnostic des vues à 0 (prochaine étape)

```bash
docker compose exec app php artisan tinker --execute="
echo '=== TABLES RAW ===\n';
echo 'senat_senateurs_elusen: ' . DB::table('senat_senateurs_elusen')->count() . '\n';
echo 'senat_senateurs_memcom: ' . DB::table('senat_senateurs_memcom')->count() . '\n';
echo 'senat_senateurs_memgrpsen: ' . DB::table('senat_senateurs_memgrpsen')->count() . '\n';
echo '\n=== VUES ===\n';
echo 'senateurs_mandats: ' . DB::table('senateurs_mandats')->count() . '\n';
echo 'senateurs_commissions: ' . DB::table('senateurs_commissions')->count() . '\n';
echo 'senateurs_historique_groupes: ' . DB::table('senateurs_historique_groupes')->count() . '\n';
"
```

Si les tables RAW sont vides → Re-importer la base SENATEURS
Si les tables RAW sont pleines mais les vues vides → Problème de mapping SQL

---

## ✨ Ce qui devrait fonctionner après le deploy

✅ Liste des sénateurs sur `/representants/senateurs`
✅ Filtres par groupe, circonscription, recherche
✅ Profils sénateurs individuels (données basiques)
❓ Mandats, commissions, historique groupes (à diagnostiquer)

---

## 📌 TODO après résolution

1. Enrichir Wikipedia pour sénateurs
2. Lier dossiers législatifs AN ↔ Sénat
3. Afficher amendements Sénat dans les dossiers
4. Compléter la page comparaison AN vs Sénat

