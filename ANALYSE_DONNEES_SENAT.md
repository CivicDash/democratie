# 📊 ANALYSE COMPLÈTE - DONNÉES SÉNAT

**Date d'analyse :** 18 novembre 2025  
**Source :** https://data.senat.fr/  
**Format :** JSON direct (APIs REST) + XML (AkomaNtoso) + CSV

---

## 🎯 **VUE D'ENSEMBLE**

Contrairement à l'Assemblée Nationale qui fournit des fichiers JSON locaux, le **Sénat expose ses données via des APIs REST directes** et des fichiers téléchargeables.

**Avantages :**
- ✅ Pas besoin de télécharger/stocker des milliers de fichiers
- ✅ Données toujours à jour
- ✅ Structure plate et simple (vs hiérarchique AN)
- ✅ Documentation claire

**Inconvénients :**
- ⚠️ Moins de détails que l'AN (pas de scrutins individuels détaillés)
- ⚠️ Format différent (nécessite 2 systèmes)

---

## 📁 **STRUCTURE DES DONNÉES SÉNAT**

### **14 Endpoints JSON Disponibles**

| Endpoint | Description | Volumétrie estimée |
|----------|-------------|-------------------|
| `ODSEN_GENERAL.json` | **Données générales des sénateurs** (tous, actifs + anciens) | ~2 000 sénateurs (toutes époques) |
| `ODSEN_HISTOGROUPES.json` | **Historique des groupes politiques** | ~50 groupes (historique complet) |
| `ODSEN_CANDEP.json` | **Candidatures départementales** | ~5 000 candidatures |
| `ODSEN_COMS.json` | **Commissions permanentes** | ~350 sénateurs en commission |
| `ODSEN_OFFDEL.json` | **Offices et délégations** | ~200 membres |
| `ODSEN_CUR_OFFDEL.json` | **Offices et délégations actuels** | ~100 membres actifs |
| `ODSEN_ELUDEP.json` | **Mandats de député** (sénateurs ayant été députés) | ~200 mandats |
| `ODSEN_ELUEUR.json` | **Mandats européens** | ~50 mandats |
| `ODSEN_ELUMET.json` | **Mandats métropolitains** | ~1 000 mandats |
| `ODSEN_ELUSEN.json` | **Mandats de sénateur** | ~2 500 mandats |
| `ODSEN_ELUVIL.json` | **Mandats municipaux** | ~3 000 mandats |
| `ODSEN_ETUDES.json` | **Études et formations** | ~1 500 formations |
| `ODSEN_OEP.json` | **Organismes extraparlementaires** | ~500 participations |
| `ODSEN_MEMOEP.json` | **Membres d'organismes extraparlementaires** | ~300 membres |

### **Fichiers CSV Législatifs**

| Fichier | Description | URL |
|---------|-------------|-----|
| **Dossiers législatifs** | Liste complète des dossiers législatifs | https://data.senat.fr/data/dosleg/dossiers-legislatifs.csv |
| **Lois promulguées** | Liste des lois votées et promulguées | https://data.senat.fr/data/dosleg/promulguees.csv |

### **Fichiers XML (AkomaNtoso)**

| Type | Description | URL |
|------|-------------|-----|
| **Textes déposés** | Propositions/projets de loi déposés | https://www.senat.fr/akomantoso/depots.xml |
| **Textes adoptés** | Textes votés par le Sénat | https://www.senat.fr/akomantoso/adoptions.xml |
| **Documentation** | Spec AkomaNtoso | https://data.senat.fr/wp-content/uploads/2021/03/akomantoso.pdf |

---

## 🔍 **ANALYSE DÉTAILLÉE DES ENDPOINTS**

### **1. ODSEN_GENERAL.json** ⭐ **[PRIORITÉ MAX]**

**Structure d'un sénateur :**

```json
{
  "Matricule": "21077M",
  "Qualite": "M.",
  "Nom_usuel": "Ziane",
  "Prenom_usuel": "Adel",
  "Etat": "ACTIF",
  "Date_naissance": "1979/04/05 00:00:00",
  "Date_de_deces": null,
  "Groupe_politique": "SER",
  "Type_d_app_au_grp_politique": null,
  "Commission_permanente": "commission de la culture",
  "Circonscription": "Seine-Saint-Denis",
  "Fonction_au_Bureau_du_Senat": null,
  "Courrier_electronique": "a.ziane@senat.fr",
  "PCS_INSEE": "Magistrats",
  "Categorie_professionnelle": "Professions judiciaires (Magistrats)",
  "Description_de_la_profession": "Cadre supérieur de la fonction publique"
}
```

**Champs clés :**
- `Matricule` : ID unique (ex: `21077M`)
- `Etat` : `ACTIF` / `ANCIEN`
- `Groupe_politique` : Sigle du groupe (ex: `SER`, `Les Républicains`, `CRCE-K`)
- `Commission_permanente` : Commission d'affectation
- `Fonction_au_Bureau_du_Senat` : Président, Vice-président, etc.
- `Circonscription` : Département d'élection
- `PCS_INSEE` + `Categorie_professionnelle` : Profession détaillée

**Utilité pour CivicDash :**
- ✅ Base de données des sénateurs (actifs + historique)
- ✅ Groupe politique actuel
- ✅ Commission d'affectation
- ✅ Département de représentation
- ✅ Email de contact
- ✅ Profession détaillée

**Volumétrie :** ~2 000 sénateurs (depuis 1958)

---

### **2. ODSEN_HISTOGROUPES.json**

**Structure :**

```json
{
  "Matricule": "21077M",
  "Groupe_politique": "SER",
  "Type_d_app_au_grp_politique": null,
  "Date_debut": "2023/10/01 00:00:00",
  "Date_fin": null
}
```

**Champs clés :**
- Historique complet des affiliations politiques
- Dates de début/fin de chaque groupe
- Type d'appartenance (Membre, Apparenté, Rattaché)

**Utilité pour CivicDash :**
- ✅ Tracer l'évolution politique des sénateurs
- ✅ Identifier les changements de groupe
- ✅ Calculer la durée dans chaque groupe

**Volumétrie :** ~50 groupes historiques

---

### **3. ODSEN_COMS.json** ⭐ **[PRIORITÉ HAUTE]**

**Structure :**

```json
{
  "Matricule": "21077M",
  "Commission_permanente": "commission de la culture",
  "Date_debut": "2023/10/01 00:00:00",
  "Date_fin": null,
  "Fonction": "Membre"
}
```

**Champs clés :**
- Commission d'affectation
- Fonction (Président, Vice-président, Rapporteur, Membre)
- Dates de début/fin

**Utilité pour CivicDash :**
- ✅ Afficher les commissions actuelles/passées
- ✅ Identifier les présidents de commission
- ✅ Lien avec les dossiers législatifs

**Volumétrie :** ~350 sénateurs actifs en commission

---

### **4. ODSEN_ELUSEN.json** ⭐ **[PRIORITÉ HAUTE]**

**Structure :**

```json
{
  "Matricule": "21077M",
  "Circonscription": "Seine-Saint-Denis",
  "Date_debut": "2023/10/01 00:00:00",
  "Date_fin": null,
  "Motif_fin": null,
  "Numero_mandat": 1
}
```

**Champs clés :**
- Historique des mandats de sénateur
- Dates de début/fin
- Motif de fin (démission, décès, fin de mandat)
- Numéro du mandat (1er, 2e, 3e...)

**Utilité pour CivicDash :**
- ✅ Historique complet des mandats
- ✅ Calcul de l'ancienneté
- ✅ Identification des changements de circonscription

**Volumétrie :** ~2 500 mandats

---

### **5. ODSEN_ELUVIL.json, ODSEN_ELUMET.json, ODSEN_ELUDEP.json, ODSEN_ELUEUR.json**

**Utilité pour CivicDash :**
- ✅ Cumul des mandats (maire, conseiller départemental, député, MEP)
- ✅ Afficher le parcours politique complet
- ✅ Statistiques sur le cumul des mandats

**Volumétrie :** ~4 000 mandats locaux

---

### **6. ODSEN_OFFDEL.json, ODSEN_CUR_OFFDEL.json**

**Structure :**

```json
{
  "Matricule": "21077M",
  "Office_ou_delegation": "Délégation aux collectivités territoriales",
  "Date_debut": "2023/10/01 00:00:00",
  "Date_fin": null,
  "Fonction": "Membre"
}
```

**Utilité pour CivicDash :**
- ✅ Participation aux offices et délégations
- ✅ Afficher les spécialisations thématiques

**Volumétrie :** ~200 membres

---

### **7. ODSEN_ETUDES.json**

**Structure :**

```json
{
  "Matricule": "21077M",
  "Diplome": "Master de droit",
  "Etablissement": "Université Paris II",
  "Annee_obtention": "2005"
}
```

**Utilité pour CivicDash :**
- ✅ Afficher le parcours académique
- ✅ Statistiques sur les profils (ENA, X, etc.)

**Volumétrie :** ~1 500 formations

---

### **8. ODSEN_OEP.json, ODSEN_MEMOEP.json**

**Structure :**

```json
{
  "Matricule": "21077M",
  "Organisme": "Conseil constitutionnel",
  "Date_debut": "2023/10/01 00:00:00",
  "Date_fin": null,
  "Fonction": "Membre"
}
```

**Utilité pour CivicDash :**
- ✅ Participation à des organismes extraparlementaires
- ✅ Afficher les fonctions institutionnelles

**Volumétrie :** ~500 participations

---

### **9. ODSEN_CANDEP.json**

**Utilité pour CivicDash :**
- ⚠️ **PRIORITÉ BASSE** : Données de candidature (moins pertinent pour l'app)

---

## 📄 **FICHIERS CSV LÉGISLATIFS**

### **1. dossiers-legislatifs.csv**

**Structure attendue :**

```csv
numero_dossier,titre,date_creation,url,etat
2024-001,"Loi sur le climat","2024-01-15","https://www.senat.fr/dossier-legislatif/...","En cours"
```

**Utilité pour CivicDash :**
- ✅ Liste des dossiers législatifs traités par le Sénat
- ✅ Lien avec les textes adoptés/déposés
- ✅ Suivi de l'avancement législatif

**Documentation :** https://data.senat.fr/aide/liste-des-dossiers-legislatifs/

---

### **2. promulguees.csv**

**Structure attendue :**

```csv
numero_loi,titre,date_promulgation,url,journal_officiel
2024-123,"Loi relative au climat","2024-03-20","https://www.legifrance.gouv.fr/...","JO du 21/03/2024"
```

**Utilité pour CivicDash :**
- ✅ Liste des lois promulguées issues de textes du Sénat
- ✅ Suivi des résultats législatifs
- ✅ Lien avec Légifrance

**Documentation :** https://data.senat.fr/aide/liste-des-lois-promulguees/

---

## 📄 **FICHIERS XML (AkomaNtoso)**

### **Format AkomaNtoso**

**AkomaNtoso** est un standard international XML pour les documents parlementaires et législatifs.

**Documentation :** https://data.senat.fr/wp-content/uploads/2021/03/akomantoso.pdf

### **1. depots.xml**

**URL :** https://www.senat.fr/akomantoso/depots.xml

**Contenu :** Propositions et projets de loi déposés au Sénat (XML structuré)

**Utilité pour CivicDash :**
- ✅ Texte intégral des propositions de loi
- ✅ Parsing XML pour extraire articles, amendements, etc.
- ⚠️ **Complexité élevée** : nécessite un parser XML AkomaNtoso

---

### **2. adoptions.xml**

**URL :** https://www.senat.fr/akomantoso/adoptions.xml

**Contenu :** Textes adoptés par le Sénat (XML structuré)

**Utilité pour CivicDash :**
- ✅ Texte intégral des lois votées
- ✅ Parsing XML pour extraire le contenu
- ⚠️ **Complexité élevée** : nécessite un parser XML AkomaNtoso

---

## 🔄 **COMPARAISON SÉNAT vs ASSEMBLÉE**

| Critère | Assemblée Nationale | Sénat |
|---------|---------------------|-------|
| **Format principal** | JSON local (99 797 fichiers) | JSON REST (14 endpoints) |
| **Volumétrie** | ~2 GB (fichiers locaux) | ~50 MB (API REST) |
| **Scrutins détaillés** | ✅ Oui (JSON par scrutin) | ❌ Non (pas d'API scrutins individuels) |
| **Votes individuels** | ✅ Oui (position par député) | ❌ Non (synthèse uniquement) |
| **Amendements** | ✅ Oui (~68 000 L17) | ⚠️ XML AkomaNtoso (complexe) |
| **Groupes politiques** | ✅ Oui (organes) | ✅ Oui (historique détaillé) |
| **Mandats** | ✅ Oui (JSON) | ✅ Oui (JSON + 5 types de mandats) |
| **Commissions** | ✅ Oui (organes) | ✅ Oui (endpoint dédié) |
| **Textes législatifs** | ✅ Oui (JSON par texte) | ⚠️ XML AkomaNtoso + CSV |
| **Facilité d'import** | ⚠️ Complexe (hiérarchie) | ✅ Simple (API REST) |
| **Fraîcheur données** | ⚠️ Nécessite téléchargement | ✅ Temps réel (API) |

---

## 🎯 **PLAN D'ACTION UNIFIÉ : AN + SÉNAT**

### **OPTION A : Import COMPLET (AN + Sénat)**

**Avantages :**
- ✅ Base de données exhaustive
- ✅ Comparaison AN ↔ Sénat possible
- ✅ Suivi législatif bicaméral complet

**Inconvénients :**
- ⚠️ Durée d'implémentation : **15-20h** (vs 9-11h AN seule)
- ⚠️ Complexité : 2 systèmes différents (JSON local + API REST)
- ⚠️ Sénat : pas de scrutins détaillés (limité pour l'analyse de votes)

---

### **OPTION B : Import SÉNAT BASIQUE (profils + mandats uniquement)**

**Ce qu'on importe :**
- ✅ `ODSEN_GENERAL.json` : Profils des sénateurs
- ✅ `ODSEN_HISTOGROUPES.json` : Historique groupes
- ✅ `ODSEN_COMS.json` : Commissions
- ✅ `ODSEN_ELUSEN.json` : Mandats de sénateur
- ✅ `ODSEN_ELUVIL.json`, `ODSEN_ELUMET.json`, `ODSEN_ELUDEP.json` : Mandats locaux

**Ce qu'on NE fait PAS (dans un premier temps) :**
- ❌ Scrutins détaillés (pas disponibles en JSON)
- ❌ Amendements Sénat (XML complexe)
- ❌ Textes législatifs (XML AkomaNtoso)

**Avantages :**
- ✅ Rapide : **+3-4h** au plan AN
- ✅ Simple : API REST directes
- ✅ Suffisant pour "Mes Représentants" (profil + groupe + mandat)

**Inconvénients :**
- ⚠️ Pas d'analyse de votes Sénat (limité)
- ⚠️ Pas d'amendements Sénat

---

### **OPTION C : AN COMPLET + SÉNAT BASIQUE** ⭐ **[RECOMMANDÉ]**

**Phase 1 : Assemblée Nationale (L17)**
- ✅ 10 tables AN (acteurs, organes, mandats, scrutins, votes, amendements, etc.)
- ✅ 8 commandes d'import
- ✅ Durée : **9-11h**

**Phase 2 : Sénat (Basique)**
- ✅ 5 tables Sénat (sénateurs, groupes, commissions, mandats, mandats locaux)
- ✅ 5 commandes d'import (API REST)
- ✅ Durée : **+3-4h**

**Total : 12-15h**

**Avantages :**
- ✅ Couverture complète des **représentants** (députés + sénateurs)
- ✅ Analyse détaillée des votes AN (prioritaire)
- ✅ Profils complets Sénat (suffisant pour l'app)
- ✅ Incrémental : on peut ajouter scrutins Sénat plus tard si besoin

---

## 📊 **STRUCTURE BDD PROPOSÉE (SÉNAT)**

### **Table : `senateurs`**

```php
Schema::create('senateurs', function (Blueprint $table) {
    $table->string('matricule', 10)->primary(); // 21077M
    $table->string('civilite', 10);
    $table->string('nom_usuel', 100);
    $table->string('prenom_usuel', 100);
    $table->enum('etat', ['ACTIF', 'ANCIEN'])->index();
    $table->date('date_naissance')->nullable();
    $table->date('date_deces')->nullable();
    $table->string('groupe_politique', 100)->nullable()->index();
    $table->string('type_appartenance_groupe', 50)->nullable();
    $table->string('commission_permanente', 100)->nullable();
    $table->string('circonscription', 100)->nullable()->index();
    $table->string('fonction_bureau_senat', 100)->nullable();
    $table->string('email')->nullable();
    $table->string('pcs_insee')->nullable();
    $table->string('categorie_socio_pro')->nullable();
    $table->string('description_profession')->nullable();
    $table->timestamps();
    
    $table->index(['nom_usuel', 'prenom_usuel']);
    $table->fullText(['nom_usuel', 'prenom_usuel']);
});
```

### **Table : `senateurs_historique_groupes`**

```php
Schema::create('senateurs_historique_groupes', function (Blueprint $table) {
    $table->id();
    $table->string('matricule', 10)->index();
    $table->string('groupe_politique', 100);
    $table->string('type_appartenance', 50)->nullable();
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->timestamps();
    
    $table->foreign('matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
    $table->index(['matricule', 'date_debut']);
});
```

### **Table : `senateurs_commissions`**

```php
Schema::create('senateurs_commissions', function (Blueprint $table) {
    $table->id();
    $table->string('matricule', 10)->index();
    $table->string('commission', 100);
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->string('fonction', 50)->nullable(); // Président, Membre, etc.
    $table->timestamps();
    
    $table->foreign('matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
    $table->index(['matricule', 'commission']);
});
```

### **Table : `senateurs_mandats`**

```php
Schema::create('senateurs_mandats', function (Blueprint $table) {
    $table->id();
    $table->string('matricule', 10)->index();
    $table->enum('type_mandat', ['SENATEUR', 'DEPUTE', 'EUROPEEN', 'METROPOLITAIN', 'MUNICIPAL']);
    $table->string('circonscription', 100)->nullable();
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->string('motif_fin', 50)->nullable();
    $table->integer('numero_mandat')->nullable();
    $table->timestamps();
    
    $table->foreign('matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
    $table->index(['matricule', 'type_mandat']);
    $table->index(['type_mandat', 'date_debut']);
});
```

### **Table : `senateurs_etudes`** (optionnel)

```php
Schema::create('senateurs_etudes', function (Blueprint $table) {
    $table->id();
    $table->string('matricule', 10)->index();
    $table->string('diplome')->nullable();
    $table->string('etablissement')->nullable();
    $table->integer('annee_obtention')->nullable();
    $table->timestamps();
    
    $table->foreign('matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
});
```

---

## 📦 **COMMANDES D'IMPORT SÉNAT**

### **1. `ImportSenateursFromApi`**

```bash
php artisan import:senateurs-api
```

**Endpoints utilisés :**
- `ODSEN_GENERAL.json`

**Durée estimée :** 20-30 min (2 000 sénateurs)

---

### **2. `ImportSenateursHistoriqueGroupes`**

```bash
php artisan import:senateurs-groupes
```

**Endpoints utilisés :**
- `ODSEN_HISTOGROUPES.json`

**Durée estimée :** 15 min

---

### **3. `ImportSenateursCommissions`**

```bash
php artisan import:senateurs-commissions
```

**Endpoints utilisés :**
- `ODSEN_COMS.json`

**Durée estimée :** 10 min

---

### **4. `ImportSenateursMandats`**

```bash
php artisan import:senateurs-mandats --all
```

**Endpoints utilisés :**
- `ODSEN_ELUSEN.json`
- `ODSEN_ELUDEP.json`
- `ODSEN_ELUEUR.json`
- `ODSEN_ELUMET.json`
- `ODSEN_ELUVIL.json`

**Durée estimée :** 30-40 min (4 000 mandats)

---

### **5. `ImportSenateursEtudes`** (optionnel)

```bash
php artisan import:senateurs-etudes
```

**Endpoints utilisés :**
- `ODSEN_ETUDES.json`

**Durée estimée :** 10 min

---

## 📋 **SCRIPT MASTER SÉNAT**

### **`scripts/import_senateurs_complet.sh`**

```bash
#!/bin/bash
echo "========================================="
echo "🏛️ IMPORT COMPLET SÉNAT"
echo "========================================="

echo "📥 1/5 - Import profils sénateurs..."
docker compose exec app php artisan import:senateurs-api

echo "📥 2/5 - Import historique groupes..."
docker compose exec app php artisan import:senateurs-groupes

echo "📥 3/5 - Import commissions..."
docker compose exec app php artisan import:senateurs-commissions

echo "📥 4/5 - Import mandats (tous types)..."
docker compose exec app php artisan import:senateurs-mandats --all

echo "📥 5/5 - Import études (optionnel)..."
docker compose exec app php artisan import:senateurs-etudes

echo "========================================="
echo "✅ Import Sénat terminé !"
echo "========================================="

# Stats
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 'senateurs' as table, COUNT(*) as total FROM senateurs
UNION ALL
SELECT 'groupes', COUNT(*) FROM senateurs_historique_groupes
UNION ALL
SELECT 'commissions', COUNT(*) FROM senateurs_commissions
UNION ALL
SELECT 'mandats', COUNT(*) FROM senateurs_mandats
UNION ALL
SELECT 'etudes', COUNT(*) FROM senateurs_etudes;
"
```

---

## 🎯 **PLAN D'ACTION FINAL RECOMMANDÉ**

### **Phase 1 : AN Législature 17 (9-11h)**
✅ 10 tables + 8 commandes + scripts

### **Phase 2 : Sénat Basique (3-4h)**
✅ 5 tables + 5 commandes + script

### **Phase 3 : API Endpoints + Frontend (4-5h)**
✅ Routes API pour députés + sénateurs  
✅ Page "Mon Député" + "Mon Sénateur"  
✅ Carte interactive complète

### **Phase 4 : Enrichissements futurs (optionnel)**
⏳ Scrutins Sénat (si API disponible)  
⏳ Amendements Sénat (parsing XML AkomaNtoso)  
⏳ Textes législatifs complets (XML)

---

## ✅ **RÉSUMÉ**

| Métrique | Valeur |
|----------|--------|
| **Endpoints Sénat** | 14 JSON REST + 2 CSV + 2 XML |
| **Sénateurs totaux** | ~2 000 (actifs + anciens) |
| **Sénateurs actifs** | ~350 |
| **Tables BDD** | 5 (sénateurs, groupes, commissions, mandats, études) |
| **Commandes d'import** | 5 |
| **Durée estimée** | 3-4h |
| **Complexité** | ⚠️ MOYENNE (API REST simple) |

---

## 📊 **INDICATEURS DE SUCCÈS**

| Table | Attendu | Tolérance |
|-------|---------|-----------|
| `senateurs` | ~2 000 | ±50 |
| `senateurs_historique_groupes` | ~50 | ±10 |
| `senateurs_commissions` | ~350 | ±50 |
| `senateurs_mandats` | ~4 000 | ±500 |
| `senateurs_etudes` | ~1 500 | ±200 |

---

## 🚀 **PRÊT À DÉMARRER !**

✅ Analyse Sénat complète  
✅ Structure BDD définie  
✅ Comparaison AN ↔ Sénat  
✅ Plan d'action unifié  

**Dis-moi : on part sur l'OPTION C (AN L17 + Sénat Basique) ? 🚀**

