# 🏰 COMMANDES D'IMPORT SÉNAT - Guide Complet

## 📊 Vue d'ensemble

Le Sénat a **4 types de données** à importer :
1. ✅ **Profils + Mandats** (déjà fait)
2. ✅ **Mandats locaux** (déjà fait)
3. ✅ **Études/Formations** (déjà fait)
4. ⏳ **Dossiers législatifs** (à faire)
5. ⏳ **Amendements** (à faire)
6. ⏳ **Questions au Gouvernement** (à faire)

---

## 🚀 COMMANDES D'IMPORT (Ordre recommandé)

### 1. Profils Sénateurs (Déjà fait ✅)
```bash
# Import complet : profils + mandats + groupes + commissions
docker compose exec app php artisan import:senateurs-complet --fresh

# Durée : ~2-3 min
# Résultat : ~350 sénateurs actifs + ~1500 groupes + ~500 commissions + ~800 mandats
```

---

### 2. Mandats Locaux Sénateurs (Déjà fait ✅)
```bash
# Import des mandats locaux (Maire, Conseiller municipal, départemental, régional, européen)
docker compose exec app php artisan import:senateurs-mandats-locaux --fresh

# Durée : ~2-3 min
# Résultat : ~2000 mandats locaux
```

---

### 3. Études/Formations Sénateurs (Déjà fait ✅)
```bash
# Import des formations et diplômes
docker compose exec app php artisan import:senateurs-etudes --fresh

# Durée : ~1 min
# Résultat : ~300 formations
```

---

### 4. Dossiers Législatifs Sénat ⭐ À FAIRE
```bash
# Import des dossiers législatifs du Sénat + matching avec l'AN
docker compose exec app php artisan import:dossiers-senat --fresh --match

# Options :
#   --fresh : Vider la table avant import
#   --match : Tenter de lier aux dossiers AN (recommandé)
#   --limit=N : Limiter à N dossiers (pour tests)

# Durée : ~5 min
# Résultat : ~1000 dossiers Sénat
```

**Exemple de test** :
```bash
# Test avec 10 dossiers
docker compose exec app php artisan import:dossiers-senat --limit=10 --match
```

---

### 5. Amendements Sénat ⭐ À FAIRE (NOUVEAU)
```bash
# Import des amendements depuis le CSV OpenData
docker compose exec app php artisan import:amendements-senat --legislature=2024 --fresh

# Options :
#   --legislature=YYYY : Année (ex: 2024)
#   --fresh : Vider la table avant import
#   --limit=N : Limiter à N amendements (pour tests)

# Durée : ~10-15 min (dépend du volume)
# Résultat : Variable selon législature (~5000-10000 par an)
```

**Exemple de test** :
```bash
# Test avec 100 amendements
docker compose exec app php artisan import:amendements-senat --legislature=2024 --limit=100
```

**Vérifier les stats** :
```bash
docker compose exec app php artisan tinker
>>> \App\Models\AmendementSenat::count()
>>> \App\Models\AmendementSenat::where('sort_code', 'ADOPTE')->count()
>>> \App\Models\AmendementSenat::where('sort_code', 'REJETE')->count()
>>> exit
```

---

### 6. Questions au Gouvernement Sénat ⭐ À FAIRE (NOUVEAU)
```bash
# Import des questions au Gouvernement
docker compose exec app php artisan import:questions-senat --fresh

# Options :
#   --fresh : Vider la table avant import
#   --limit=N : Limiter à N questions (pour tests)

# Durée : ~5-10 min
# Résultat : ~10 000 questions
```

**Exemple de test** :
```bash
# Test avec 100 questions
docker compose exec app php artisan import:questions-senat --limit=100
```

**Vérifier les stats** :
```bash
docker compose exec app php artisan tinker
>>> DB::table('senateurs_questions')->count()
>>> DB::table('senateurs_questions')->where('a_reponse', true)->count()
>>> DB::table('senateurs_questions')->where('a_reponse', false)->count()
>>> exit
```

---

## 📋 SCRIPT COMPLET D'IMPORT SÉNAT

### Import complet de toutes les données Sénat
```bash
#!/bin/bash
# import_senat_complet.sh

cd /opt/civicdash

echo "🏰 IMPORT COMPLET SÉNAT"
echo "======================="

# 1. Migrations
echo ""
echo "📊 Étape 1/6 : Migrations..."
php artisan migrate

# 2. Profils + Mandats (déjà fait normalement)
echo ""
echo "👥 Étape 2/6 : Profils sénateurs..."
docker compose exec -T app php artisan import:senateurs-complet --fresh

# 3. Mandats locaux (déjà fait normalement)
echo ""
echo "🏛️  Étape 3/6 : Mandats locaux..."
docker compose exec -T app php artisan import:senateurs-mandats-locaux --fresh

# 4. Études/Formations (déjà fait normalement)
echo ""
echo "🎓 Étape 4/6 : Études et formations..."
docker compose exec -T app php artisan import:senateurs-etudes --fresh

# 5. Dossiers législatifs
echo ""
echo "📜 Étape 5/6 : Dossiers législatifs..."
docker compose exec -T app php artisan import:dossiers-senat --fresh --match

# 6. Amendements
echo ""
echo "📝 Étape 6/6a : Amendements Sénat 2024..."
docker compose exec -T app php artisan import:amendements-senat --legislature=2024 --fresh

# 7. Questions
echo ""
echo "❓ Étape 6/6b : Questions au Gouvernement..."
docker compose exec -T app php artisan import:questions-senat --fresh

# 8. Clear caches
echo ""
echo "🧹 Nettoyage des caches..."
php artisan cache:clear
php artisan config:clear
docker compose restart app

echo ""
echo "✅ IMPORT SÉNAT TERMINÉ !"
echo ""
echo "📊 Statistiques :"
docker compose exec -T app php artisan tinker <<EOF
echo "Sénateurs actifs : " . \App\Models\Senateur::actifs()->count() . "\n";
echo "Mandats locaux : " . \App\Models\SenateurMandatLocal::count() . "\n";
echo "Études : " . \App\Models\SenateurEtude::count() . "\n";
echo "Dossiers : " . \App\Models\DossierLegislatifSenat::count() . "\n";
echo "Amendements : " . \App\Models\AmendementSenat::count() . "\n";
echo "Questions : " . DB::table('senateurs_questions')->count() . "\n";
exit
EOF
```

**Rendre le script exécutable** :
```bash
chmod +x import_senat_complet.sh
./import_senat_complet.sh
```

---

## 🧪 MODE TEST (Rapide)

Pour tester rapidement sans tout importer :

```bash
#!/bin/bash
# test_import_senat.sh

echo "🧪 TEST IMPORT SÉNAT (Limité)"

# Migrations
php artisan migrate

# Dossiers (10 seulement)
docker compose exec -T app php artisan import:dossiers-senat --limit=10 --match

# Amendements (100 seulement)
docker compose exec -T app php artisan import:amendements-senat --legislature=2024 --limit=100

# Questions (100 seulement)
docker compose exec -T app php artisan import:questions-senat --limit=100

echo "✅ TEST TERMINÉ"
```

---

## 📊 VÉRIFICATION DES DONNÉES

### Vérifier les données importées
```bash
docker compose exec app php artisan tinker
```

Puis dans tinker :
```php
// Sénateurs
echo "Sénateurs actifs : " . \App\Models\Senateur::actifs()->count() . "\n";
echo "Sénateurs total : " . \App\Models\Senateur::count() . "\n";

// Mandats
echo "Mandats Sénat : " . \App\Models\SenateurMandat::count() . "\n";
echo "Mandats locaux : " . \App\Models\SenateurMandatLocal::where('en_cours', true)->count() . " en cours\n";

// Études
echo "Formations : " . \App\Models\SenateurEtude::count() . "\n";

// Dossiers
echo "Dossiers Sénat : " . \App\Models\DossierLegislatifSenat::count() . "\n";
echo "Dossiers liés AN : " . \App\Models\DossierLegislatifSenat::whereNotNull('dossier_an_uid')->count() . "\n";

// Amendements
echo "Amendements Sénat : " . \App\Models\AmendementSenat::count() . "\n";
echo "  - Adoptés : " . \App\Models\AmendementSenat::where('sort_code', 'ADOPTE')->count() . "\n";
echo "  - Rejetés : " . \App\Models\AmendementSenat::where('sort_code', 'REJETE')->count() . "\n";

// Questions
echo "Questions : " . DB::table('senateurs_questions')->count() . "\n";
echo "  - Avec réponse : " . DB::table('senateurs_questions')->where('a_reponse', true)->count() . "\n";

exit
```

---

## 🎯 ORDRE RECOMMANDÉ POUR PRODUCTION

1. ✅ **Profils sénateurs** (déjà fait)
2. ✅ **Mandats locaux** (déjà fait)
3. ✅ **Études** (déjà fait)
4. ⏳ **Dossiers Sénat** (5 min)
5. ⏳ **Amendements 2024** (15 min)
6. ⏳ **Questions** (10 min)

**Durée totale** : ~30 minutes

---

## 📁 FICHIERS CRÉÉS

- ✅ `app/Console/Commands/ImportSenateursComplet.php` (existant)
- ✅ `app/Console/Commands/ImportSenateursMandatsLocaux.php` (existant)
- ✅ `app/Console/Commands/ImportSenateursEtudes.php` (existant)
- ✅ `app/Console/Commands/ImportDossiersSenat.php` (existant)
- ✅ `app/Console/Commands/ImportAmendementsSenat.php` ⭐ NOUVEAU
- ✅ `app/Console/Commands/ImportQuestionsSenat.php` ⭐ NOUVEAU

---

**Document créé le** : 21 novembre 2025, 00:10  
**Dernière mise à jour** : 21 novembre 2025, 00:10

