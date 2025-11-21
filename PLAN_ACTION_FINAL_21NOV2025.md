# 🎯 PLAN D'ACTION FINAL - Données Manquantes

## 📊 Situation Actuelle

### ✅ COMPLET (95%+)
- **Députés AN** : Profils, mandats, groupes, commissions ✅
- **Votes individuels AN** : ~400k enregistrements ✅
- **Scrutins AN** : 3 876 ✅
- **Amendements AN** : 34 629 / 63 677 (54%) ⚠️
- **Dossiers/Textes AN** : Complet ✅
- **Sénateurs** : Profils, mandats, groupes, commissions ✅
- **Mandats locaux sénateurs** : ~2k ✅
- **Études sénateurs** : ~300 ✅

### ⚠️ PARTIEL (50-80%)
- **Amendements AN** : 29 048 erreurs à réimporter
- **Amendements Sénat** : Migration + Commande créées, **à importer**
- **Dossiers Sénat** : Table créée, **à importer**

### ❌ MANQUANT (0%)
- **Scrutins Sénat** : **Non disponibles** (pas de votes nominatifs publics)
- **Votes individuels Sénat** : **Non disponibles**
- **Questions Sénat** : Migration + Commande créées, **à importer**

---

## 🚀 ACTIONS À EXÉCUTER (Par Ordre de Priorité)

### 🔥 **CRITIQUE (À faire maintenant)**

#### 1. Réimporter les amendements AN (corriger 29k erreurs)
```bash
cd /opt/civicdash
docker compose exec app php artisan import:amendements-an --legislature=17 --fresh
```
**Durée** : ~20-30 min  
**Impact** : Statistiques complètes sur profils députés

---

### ⭐ **HAUTE PRIORITÉ (Cette semaine)**

#### 2. Importer les amendements Sénat
```bash
php artisan migrate  # Créer la table amendements_senat
php artisan import:amendements-senat --legislature=2024 --fresh
```
**Durée** : ~10-15 min  
**Impact** : Statistiques amendements sénateurs

#### 3. Importer les Questions au Gouvernement Sénat
```bash
php artisan migrate  # Créer la table senateurs_questions
php artisan import:questions-senat --fresh
```
**Durée** : ~5-10 min  
**Impact** : Activité complète sénateurs

#### 4. Importer les Dossiers Sénat
```bash
php artisan import:dossiers-senat --fresh --match
```
**Durée** : ~5 min  
**Impact** : Timeline bicamérale AN/Sénat

---

### 📊 **MOYENNE PRIORITÉ (Cette semaine/mois)**

#### 5. Recalculer les totaux scrutins AN
```bash
php artisan scrutins:recalculate-totals --legislature=17
```
**Durée** : ~5 min  
**Impact** : Statistiques globales scrutins correctes

#### 6. Créer des stats pré-calculées députés
**À créer** : Commande `calculate:deputes-stats`
- Taux de participation
- Taux de discipline
- Nombre de scrutins votés par mois

---

### 🎨 **BASSE PRIORITÉ (Nice to have)**

#### 7. Wikipedia pour sénateurs
**À créer** : Commande `enrich:senateurs-wikipedia`
- Photos
- Biographies
- Extracts

#### 8. Photos sénateurs depuis Sénat.fr
**À créer** : Scraper ou API manuelle
- Photos officielles

---

## 📝 COMMANDES CRÉÉES AUJOURD'HUI

### ✅ Prêtes à l'emploi
1. ✅ `import:amendements-senat` - Amendements Sénat depuis CSV
2. ✅ `import:questions-senat` - Questions au Gouvernement Sénat
3. ✅ `scrutins:recalculate-totals` - Recalculer totaux scrutins

### 📁 Fichiers créés
- `app/Console/Commands/ImportAmendementsSenat.php` (253 lignes)
- `app/Console/Commands/ImportQuestionsSenat.php` (192 lignes)
- `database/migrations/2025_11_20_220000_create_amendements_senat_table.php`
- `database/migrations/2025_11_20_230000_create_senateurs_questions_table.php`
- `app/Models/AmendementSenat.php`

---

## 🎯 OBJECTIFS SEMAINE 47 (25-29 Nov 2025)

### Lundi 25 Nov
- [ ] Réimporter amendements AN (--fresh)
- [ ] Tester profils députés (stats amendements)
- [ ] Tester recherche globale

### Mardi 26 Nov
- [ ] Importer amendements Sénat
- [ ] Importer questions Sénat
- [ ] Importer dossiers Sénat

### Mercredi 27 Nov
- [ ] Créer pages Vue pour questions sénateurs
- [ ] Ajouter onglet "Questions" sur profils sénateurs
- [ ] Tester timeline bicamérale

### Jeudi 28 Nov
- [ ] Recalculer totaux scrutins
- [ ] Créer commande stats députés
- [ ] Tests E2E complets

### Vendredi 29 Nov
- [ ] Documentation finale
- [ ] Tests de charge
- [ ] Déploiement production

---

## 📊 COUVERTURE FINALE ATTENDUE

| Catégorie | Avant | Après Actions | Objectif |
|-----------|-------|---------------|----------|
| Profils députés | 95% | 98% | 100% |
| Votes individuels AN | 100% | 100% | 100% |
| Amendements AN | 54% | 100% | 100% |
| Profils sénateurs | 80% | 95% | 95% |
| Amendements Sénat | 0% | 100% | 100% |
| Questions Sénat | 0% | 100% | 100% |
| Dossiers bicaméraux | 0% | 100% | 100% |
| **GLOBAL** | **75%** | **97%** | **98%** |

*Note : 100% impossible car scrutins/votes Sénat non publics*

---

## 🔧 COMMANDES SERVEUR - QUICKSTART

### Import complet données manquantes
```bash
cd /opt/civicdash
git pull
php artisan migrate

# 1. Amendements AN (réimport complet)
docker compose exec app php artisan import:amendements-an --legislature=17 --fresh

# 2. Amendements Sénat
docker compose exec app php artisan import:amendements-senat --legislature=2024 --fresh

# 3. Questions Sénat
docker compose exec app php artisan import:questions-senat --fresh

# 4. Dossiers Sénat
docker compose exec app php artisan import:dossiers-senat --fresh --match

# 5. Recalculer totaux scrutins
docker compose exec app php artisan scrutins:recalculate-totals --legislature=17

# 6. Vider caches
php artisan cache:clear
php artisan config:clear
docker compose restart app
```

**Durée totale estimée** : **1h15-1h30**

---

**Document créé le** : 21 novembre 2025, 00:00  
**Prochaine revue** : 25 novembre 2025  
**Statut** : ✅ PRÊT POUR EXÉCUTION

