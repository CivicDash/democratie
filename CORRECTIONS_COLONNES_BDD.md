# 🔧 CORRECTIONS NOMS DE COLONNES - Base de données AN

## ❌ Problème
Les contrôleurs et services utilisaient des noms de colonnes incorrects, provoquant des erreurs SQL `SQLSTATE[42703]: Undefined column`.

---

## ✅ Noms de colonnes CORRECTS

### Table `votes_individuels_an`
```sql
scrutin_ref       (et NON scrutin_uid)
acteur_ref        (et NON acteur_uid)
mandat_ref        (et NON mandat_uid)
groupe_ref        (et NON groupe_uid)
position          (et NON position_vote)
position_groupe   (OK)
```

### Table `amendements_an`
```sql
auteur_acteur_ref (et NON acteur_ref)
auteur_groupe_ref (et NON groupe_ref)
auteur_type       (OK)
```

### Table `mandats_an`
```sql
acteur_ref        (et NON acteur_uid)
organe_ref        (et NON organe_uid)
```

---

## 📝 Fichiers corrigés (29 occurrences)

### 1️⃣ `app/Http/Controllers/Web/RepresentantANController.php` (10 corrections)
- ✅ `position_vote` → `position` (5×)
- ✅ `acteur_ref` → `auteur_acteur_ref` (5×) pour les amendements

**Lignes :**
- 214 : Filtre par type de vote
- 226-228 : Stats votes (pour/contre/abstention)
- 244 : Transformation votes
- 273 : Query amendements du député
- 308 : Stats amendements
- 365-367 : Stats globales activité
- 370 : Query amendements activité
- 409 : Comptage amendements mensuels
- 432 : Transformation votes récents
- 441 : Derniers amendements

### 2️⃣ `app/Http/Controllers/Web/LegislationController.php` (6 corrections)
- ✅ `scrutin_uid` → `scrutin_ref` (2×)
- ✅ `position_vote` → `position` (4×)

**Lignes :**
- 187 : Votes par groupe
- 202-204 : Comptage votes (pour/contre/abstention)
- 210 : Députés ayant voté
- 218 : Position du vote

### 3️⃣ `app/Services/DisciplineGroupeService.php` (13 corrections)
- ✅ `acteur_uid` → `acteur_ref` (2×)
- ✅ `scrutin_uid` → `scrutin_ref` (1×)
- ✅ `organe_uid` → `organe_ref` (2×)
- ✅ `position_vote` → `position` (8×)

**Lignes :**
- 27 : Query votes député
- 50 : Comparaison position
- 69 : Query votes par scrutin
- 71 : Filter mandats par organe
- 74 : Select position
- 79 : Return position
- 93 : Filter mandats groupe
- 136 : Query votes rebelles
- 151 : Comparaison vote rebelle
- 154 : Vote député dans résultat

---

## 🎯 Total : 29 corrections appliquées

### Par type d'erreur :
- `position_vote` → `position` : **17 fois**
- `acteur_uid` / `acteur_ref` (amendements) → `acteur_ref` / `auteur_acteur_ref` : **7 fois**
- `scrutin_uid` → `scrutin_ref` : **3 fois**
- `organe_uid` → `organe_ref` : **2 fois**

---

## ✅ Pages fonctionnelles après corrections

1. **Liste des députés** - `/representants/deputes`
2. **Fiche député** - `/representants/deputes/{uid}`
3. **Votes du député** - `/representants/deputes/{uid}/votes`
4. **Amendements du député** - `/representants/deputes/{uid}/amendements`
5. **Activité du député** - `/representants/deputes/{uid}/activite`
6. **Scrutin détaillé** - `/legislation/scrutins/{uid}`

---

## 📊 État après corrections

### Votes individuels
- ✅ Comptage votes (pour/contre/abstention)
- ✅ Filtrage par position
- ✅ Statistiques par député
- ✅ Discipline de groupe
- ✅ Votes rebelles

### Amendements
- ✅ Amendements par auteur
- ✅ Filtres (adopté/rejeté/retiré)
- ✅ Statistiques par député
- ✅ Activité mensuelle

---

**Date :** 20 novembre 2025  
**Status :** ✅ Toutes les corrections appliquées

