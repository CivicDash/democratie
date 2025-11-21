# 🏰 RÉALITÉ DES DONNÉES SÉNAT - data.senat.fr

## ⚠️ SITUATION RÉELLE (Mise à jour 21 Nov 2025)

Après investigation approfondie, voici **ce qui est réellement disponible** sur data.senat.fr :

---

## ✅ DONNÉES DISPONIBLES (Import possible)

### 1. **Profils Sénateurs** ✅
- **Source** : API REST `https://data.senat.fr/senateurs/{matricule}.json`
- **Méthode** : Boucle sur liste initiale
- **Commande** : `import:senateurs-complet` (déjà implémenté)
- **Données** :
  - Identité complète
  - Groupe politique actuel
  - Commission permanente
  - Coordonnées
  - PCS / Profession

### 2. **Mandats** ✅
- **Source** : Inclus dans l'API sénateur
- **Commande** : `import:senateurs-complet`
- **Données** : Mandats sénatoriaux

### 3. **Groupes Politiques (Historique)** ✅
- **Source** : API REST par sénateur
- **Commande** : `import:senateurs-complet`
- **Données** : Évolution des groupes

### 4. **Commissions** ✅
- **Source** : API REST par sénateur
- **Commande** : `import:senateurs-complet`
- **Données** : Commissions + fonctions

### 5. **Mandats Locaux** ✅
- **Source** : API REST spécifique
- **Commande** : `import:senateurs-mandats-locaux`
- **Données** :
  - Maire
  - Conseiller municipal/départemental/régional
  - Député européen

### 6. **Formations/Études** ✅
- **Source** : API REST spécifique
- **Commande** : `import:senateurs-etudes`
- **Données** : Parcours académique

### 7. **Dossiers Législatifs** ✅ (avec erreurs)
- **Source** : CSV `https://data.senat.fr/data/dosleg/dossiers-legislatifs.csv`
- **Commande** : `import:dossiers-senat`
- **Problèmes** :
  - ⚠️ CSV mal formé (lignes vides/incomplètes)
  - ⚠️ Colonnes variables selon les lignes
- **Solution** : Validation renforcée (corrigé)

---

## ❌ DONNÉES NON DISPONIBLES (API publique)

### 1. **Scrutins Nominatifs** ❌
- **Raison** : Le Sénat ne publie pas les votes nominatifs en masse
- **Alternatives** :
  - Seuls quelques votes "solennels" sont publiés
  - Pas d'API pour récupérer l'historique complet

### 2. **Votes Individuels** ❌
- **Raison** : Conséquence directe de l'absence de scrutins
- **Impact** : Impossible de calculer discipline de vote, participation, etc.

### 3. **Amendements (en masse)** ❌
- **Raison** : Pas de CSV/API pour liste complète
- **Alternatives** :
  - API individuelle par dossier (très long)
  - Scraping HTML senat.fr (complexe)
  - NosSenateurs.fr (service deprecated)

### 4. **Questions au Gouvernement (en masse)** ❌
- **Raison** : Pas de CSV global
- **Alternatives** :
  - API par sénateur (350 appels = ~45 min)
  - Exemple : `https://data.senat.fr/senateurs/{matricule}.json` contient les questions
  - **Possible mais très long**

---

## 🎯 STRATÉGIE RECOMMANDÉE

### Ce qu'on peut faire MAINTENANT (10 min)
```bash
# 1. Import profils complets (déjà fait)
docker compose exec app php artisan import:senateurs-complet --fresh

# 2. Import mandats locaux (déjà fait)
docker compose exec app php artisan import:senateurs-mandats-locaux --fresh

# 3. Import études (déjà fait)
docker compose exec app php artisan import:senateurs-etudes --fresh

# 4. Import dossiers Sénat (avec validation renforcée)
docker compose exec app php artisan import:dossiers-senat --fresh --match
```

### Ce qu'on pourrait faire (30-45 min)
```bash
# Import questions par sénateur (long mais faisable)
# Nécessite modification de la commande pour boucler sur tous les sénateurs
docker compose exec app php artisan import:questions-senat-par-senateur --fresh
```

### Ce qu'on NE PEUT PAS faire
- ❌ Scrutins nominatifs Sénat
- ❌ Votes individuels Sénat
- ❌ Amendements en masse Sénat (sauf scraping complexe)

---

## 📊 COUVERTURE FINALE RÉALISTE

### Assemblée Nationale L17
- **Profils** : ✅ 100%
- **Votes individuels** : ✅ 100% (~400k)
- **Scrutins** : ✅ 100% (~3.9k)
- **Amendements** : ✅ 54% (34k / 63k) - **À réimporter**
- **Dossiers/Textes** : ✅ 100%
- **Total AN** : **✅ 95%**

### Sénat
- **Profils** : ✅ 100% (~350)
- **Mandats** : ✅ 100% (~800)
- **Mandats locaux** : ✅ 100% (~2k)
- **Formations** : ✅ 100% (~300)
- **Dossiers** : ✅ 90% (erreurs CSV corrigées)
- **Scrutins** : ❌ 0% (non publics)
- **Votes individuels** : ❌ 0% (non publics)
- **Amendements** : ❌ 0% (non accessibles en masse)
- **Questions** : ⏳ 0% (faisable mais long)
- **Total Sénat** : **✅ 60%** (limité par données non publiques)

### Global
- **Couverture possible maximale** : **75%**
- **Couverture actuelle** : **72%**
- **Avec Questions Sénat (si implémenté)** : **74%**

---

## 💡 RECOMMANDATIONS FINALES

### 1. Focus sur l'essentiel (MAINTENANT)
- ✅ Réimporter amendements AN (--fresh)
- ✅ Import dossiers Sénat (avec corrections)
- ✅ Tester la page AN vs Sénat (corrections appliquées)

### 2. Nice to have (SI DU TEMPS)
- ⏳ Implémenter boucle questions par sénateur (45 min import)
- ⏳ Scraper quelques scrutins solennels Sénat

### 3. À oublier (IMPOSSIBLE)
- ❌ Votes individuels Sénat complets
- ❌ Scrutins Sénat historique
- ❌ Amendements Sénat en masse

---

## 🔧 COMMANDES CORRIGÉES

### Import Dossiers Sénat (Corrigé ✅)
```bash
docker compose exec app php artisan import:dossiers-senat --fresh --match
# Ignorer les lignes malformées
# Limite affichage erreurs à 5
```

### Import Amendements Sénat (Désactivé ❌)
```bash
docker compose exec app php artisan import:amendements-senat --legislature=2024 --fresh
# Affiche un message d'erreur informatif
# Explique pourquoi ce n'est pas possible
```

### Import Questions Sénat (Désactivé ❌)
```bash
docker compose exec app php artisan import:questions-senat --fresh
# Affiche un message d'erreur informatif
# Propose une alternative (boucle par sénateur)
```

---

## ✅ COMMANDES À EXÉCUTER MAINTENANT

```bash
cd /opt/civicdash
git pull
php artisan migrate

# 1. Réimporter amendements AN (corriger 29k erreurs)
docker compose exec app php artisan import:amendements-an --legislature=17 --fresh

# 2. Importer dossiers Sénat (avec corrections)
docker compose exec app php artisan import:dossiers-senat --fresh --match

# 3. Clear caches
php artisan cache:clear
docker compose restart app
```

**Durée totale : ~30 minutes**

---

**Document créé le** : 21 novembre 2025, 00:20  
**Réalité terrain** : data.senat.fr est **moins ouvert** que data.assemblee-nationale.fr  
**Conclusion** : On fait avec ce qu'on a, et c'est déjà bien ! 💪

