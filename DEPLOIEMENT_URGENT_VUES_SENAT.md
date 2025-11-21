# 🚀 DÉPLOIEMENT URGENT - Correction vues Sénat

## ⚠️ PROBLÈME ACTUEL

- ❌ Erreur : `invalid input syntax for type integer: "14357A"`
- ❌ Scrutins affichent tous "?" et "égalité"
- ❌ Votes/Amendements ne s'affichent pas

**Cause** : Les migrations n'ont pas été appliquées correctement sur prod (les cast `::text` manquent)

---

## ✅ SOLUTION (2 commandes)

### Sur ta machine (LOCAL)
```bash
cd /home/kevin/www/demoscratos
git push origin main
```

### Sur le serveur (PROD)
```bash
cd /opt/civicdash
git pull origin main
./scripts/fix_vues_senat_prod.sh
```

**C'est tout !** ✅

---

## 📝 Ce que fait le script

1. ✅ Re-crée `amendements_senat` avec `senateur_matricule::text`
2. ✅ Re-crée `senateurs_votes` avec `senmat::text`
3. 🔍 Diagnostique les données scrutins (pour/contre)
4. ✅ Vérifie les counts (amendements/votes/scrutins)
5. 🧹 Clear tous les caches Laravel

**Temps** : ~30 secondes

---

## 🧪 APRÈS LE SCRIPT - Tester

1. Va sur un profil sénateur : `https://demo.objectif2027.fr/representants/senateurs/14357A`
2. Clique sur "📝 Amendements" → Devrait fonctionner
3. Clique sur "🗳️ Voir les votes" → Devrait fonctionner
4. Clique sur "📊 Activité" → Devrait fonctionner

---

## 🐛 Si ça ne marche toujours pas

### Diagnostic rapide
```bash
cd /opt/civicdash
docker compose exec app php artisan tinker
```

Dans Tinker :
```php
// Test 1: Amendements
\App\Models\AmendementSenat::where('senateur_matricule', '14357A')->count();

// Test 2: Votes
\App\Models\VoteSenat::where('senateur_matricule', '14357A')->count();

// Test 3: Scrutins
$s = \App\Models\ScrutinSenat::first();
echo "Pour: {$s->pour} | Contre: {$s->contre} | Resultat: {$s->resultat}\n";
```

**Si Test 1 ou 2 échoue** → Les cast `::text` ne sont pas appliqués
**Si Test 3 montre `pour=0, contre=0`** → Problème dans les données source SQL

---

## 📊 Pour le problème "Pour/Contre = 0"

Si les scrutins affichent toujours 0 pour/contre après le script, c'est que les colonnes `scrpou` et `scrcon` sont NULL dans la table raw `senat_senateurs_scr`.

**Vérification** :
```bash
docker compose exec app php artisan tinker
```

```php
DB::table('senat_senateurs_scr')->whereNotNull('scrpou')->count();
DB::table('senat_senateurs_scr')->select('scrpou', 'scrcon', 'scrint')->limit(5)->get();
```

**Si toutes les valeurs sont NULL** → Il faut ré-importer la base SQL `SENATEURS` :
```bash
docker compose exec app php artisan import:senat-sql senateurs --fresh --no-interaction
```

---

## 🎯 RÉCAP : Les 11 commits à deployer

```
7296a8c - Désactivation seeders fake data
95bc238 - Fix GroupeParlementaire
24c8df5 - Guide déploiement
57b2e01 - Fix Dashboard crash
713115a - Fix Wikipedia table annexe
1c9db3e - Adapter models VoteSenat/ScrutinSenat
d4d0c25 - Retirer colonne sennompatnai
4e435cb - Pages Votes/Amendements/Activité
f0d6a70 - Afficher Wikipedia + stats
038e01a - Fix erreurs critiques + uniformisation
30f382b - Récapitulatif final
3ee5a84 - Script correction prod (CELUI-CI)
```

---

**Dernière mise à jour** : 21 nov 2025
**Status** : ✅ Prêt à exécuter

