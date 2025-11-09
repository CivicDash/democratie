# 🗺️ ROADMAP - Enrichissement Données Parlementaires

> Basé sur les ressources de [NosDéputés.fr](https://github.com/regardscitoyens/nosdeputes.fr/blob/master/doc/api.md) et [OpenData Parlementaire](https://github.com/regardscitoyens/nosdeputes.fr/blob/master/doc/opendata.md)

---

## ✅ **PHASE 0 : FONDATIONS (TERMINÉ)**

- ✅ Import députés & sénateurs depuis CSV
- ✅ Import maires depuis CSV
- ✅ Enrichissement profils (groupes, photos, stats de base)
- ✅ **Tables créées** : `votes_deputes`, `interventions_parlementaires`, `questions_gouvernement`
- ✅ **Commandes** : `enrich:deputes-votes`, `enrich:senateurs-votes`
- ✅ **Fix API** : Utilisation endpoints séparés (`/slug/votes/json`, etc.)

---

## 🔥 **PHASE 1 : ACTIVITÉ PARLEMENTAIRE COMPLÈTE (EN COURS)**

### 1.1 ✅ Votes détaillés (FAIT)
- Endpoint : `/slug/votes/json`
- Import de tous les votes avec position, résultat, contexte
- **Estimation** : ~150-200k votes

### 1.2 ✅ Interventions (FAIT)
- Endpoint : `/slug/interventions/json`
- Discours, prises de parole, débats
- **Estimation** : ~40-60k interventions

### 1.3 ✅ Questions au gouvernement (FAIT)
- Endpoint : `/slug/questions/json`
- Questions écrites/orales + réponses
- **Estimation** : ~15-25k questions

### 1.4 🔄 Amendements détaillés (FAIT ✅)
- **Endpoint** : `/slug/amendements/json`
- **Table** : `amendements_parlementaires`
- **Commande** : `enrich:amendements`
- **Estimation** : ~100-150k amendements
- **Statut** : ✅ Terminé !

### 1.5 ✅ Commissions & Organes parlementaires (FAIT ✅)
- **Endpoints** :
  - `/organismes/groupe/json` → Groupes politiques
  - `/organismes/parlementaire/json` → Commissions
  - `/organisme/{slug}/json` → Membres d'un organisme
- **Tables** : `organes_parlementaires`, `membres_organes`
- **Commande** : `import:organes-parlementaires`
- **Estimation** : ~60 organes, ~1000 membres
- **Statut** : ✅ Terminé !
- **Script** : `bash scripts/import_organes.sh`

---

## 🎯 **PHASE 2 : DONNÉES AVANCÉES (Court terme - 1 mois)**

### 2.1 Présences en séance
- **Source** : [Dumps SQL](https://www.regardscitoyens.org/telechargement/donnees/)
- **Tables** :
  ```sql
  CREATE TABLE presences_seances (
    id BIGSERIAL PRIMARY KEY,
    depute_senateur_id BIGINT,
    date_seance DATE,
    type_seance VARCHAR(50), -- pleniere/commission
    present BOOLEAN,
    duree_minutes INT
  );
  ```
- **KPI** : Taux de présence réel (vs stats globales)
- **Délai** : 1 semaine

### 2.2 Moteur de recherche full-text
- **Endpoint** : `/recherche/{query}?format=json`
- **Fonctionnalités** :
  - Recherche dans interventions, amendements, questions, propositions
  - Filtres : député, date, thématique, type de document
  - Pagination & statistiques
- **Tables** :
  ```sql
  CREATE INDEX idx_interventions_fulltext ON interventions_parlementaires USING gin(to_tsvector('french', contenu));
  CREATE INDEX idx_amendements_fulltext ON amendements_parlementaires USING gin(to_tsvector('french', contenu));
  ```
- **Délai** : 1 semaine

### 2.3 Mots-clés & Analyse sémantique
- **Source** : Tags automatiques de NosDéputés
- **Tables** :
  ```sql
  CREATE TABLE tags_activites (
    id BIGSERIAL PRIMARY KEY,
    taggable_type VARCHAR(100), -- Intervention/Amendement/Question
    taggable_id BIGINT,
    tag VARCHAR(100),
    weight DECIMAL(3,2) -- Importance du tag (0-1)
  );
  ```
- **Visualisations** :
  - Nuage de mots par député
  - Thèmes principaux par groupe politique
- **Délai** : 4-5 jours

### 2.4 Visualisations avancées
- **A. Réseau de co-signatures** (D3.js)
  - Graphe interactif : qui cosigne avec qui
  - Détection de communautés/alliances
- **B. Timeline des votes**
  - Évolution temporelle des positions
  - Comparaison député vs groupe
- **C. Carte géographique enrichie**
  - Heatmap : taux de présence par circonscription
  - Overlay : amendements adoptés par région
- **Délai** : 2 semaines

---

## 🔍 **PHASE 3 : TRANSPARENCE & INFLUENCE (Moyen terme - 2-3 mois)**

### 3.1 Lobbying & Auditions
- **Sources** :
  - [Représentants d'intérêts](https://github.com/regardscitoyens/registres-lobbying)
  - [Personnes auditionnées](http://www.nosdonnees.fr/package/influence-auditions-deputes-lobbying)
- **Tables** :
  ```sql
  CREATE TABLE lobbyistes (
    id BIGSERIAL PRIMARY KEY,
    nom VARCHAR(255),
    organisation VARCHAR(255),
    secteur VARCHAR(100),
    date_enregistrement DATE,
    url_fiche TEXT
  );
  
  CREATE TABLE auditions (
    id BIGSERIAL PRIMARY KEY,
    depute_senateur_id BIGINT,
    personne_auditionnee VARCHAR(255),
    organisation VARCHAR(255),
    date_audition DATE,
    sujet TEXT,
    organe_id BIGINT NULLABLE
  );
  ```
- **Fonctionnalités** :
  - Liste des lobbyistes rencontrés par député
  - Analyse croisée : auditions vs votes
  - Réseau d'influence interactif
- **Délai** : 2 semaines

### 3.2 Collaborateurs parlementaires
- **Source** : [GitHub - Collaborateurs](https://github.com/regardscitoyens/Collaborateurs-Parlement)
- **Tables** :
  ```sql
  CREATE TABLE collaborateurs (
    id BIGSERIAL PRIMARY KEY,
    depute_senateur_id BIGINT,
    nom VARCHAR(255),
    prenom VARCHAR(255),
    fonction VARCHAR(100),
    date_debut DATE,
    date_fin DATE NULLABLE
  );
  ```
- **Affichage** : Liste sur la fiche du député
- **Délai** : 3-4 jours

### 3.3 Rattachement financier
- **Source** : [GitHub - Rattachement financier](https://github.com/regardscitoyens/rattachement-financier-parlementaires)
- **Tables** :
  ```sql
  CREATE TABLE rattachements_financiers (
    id BIGSERIAL PRIMARY KEY,
    depute_senateur_id BIGINT,
    annee INT,
    parti VARCHAR(100),
    montant_euros DECIMAL(10,2) NULLABLE
  );
  ```
- **Alerte** : Changement de rattachement
- **Analyse** : Rattachement vs groupe politique
- **Délai** : 3 jours

### 3.4 Comptes Twitter
- **Source** : [GitHub - Twitter Parlementaires](https://github.com/regardscitoyens/twitter-parlementaires)
- **Champs** : Ajouter `twitter_handle` à `deputes_senateurs`
- **Affichage** : Badge Twitter sur la fiche
- **Analyse** : Activité Twitter vs activité parlementaire
- **Délai** : 2 jours

---

## 📚 **PHASE 4 : DOSSIERS LÉGISLATIFS (Long terme - 6 mois)**

### 4.1 Dossiers législatifs complets
- **Sources** :
  - [ParlAPI.fr](http://parlapi.fr)
  - [LaFabriqueDeLaLoi.fr API](https://www.lafabriquedelaloi.fr/api/)
- **Tables** :
  ```sql
  CREATE TABLE dossiers_legislatifs (
    id BIGSERIAL PRIMARY KEY,
    numero VARCHAR(50) UNIQUE,
    titre TEXT,
    type VARCHAR(50), -- projet/proposition
    date_depot DATE,
    date_adoption DATE NULLABLE,
    statut VARCHAR(50),
    url TEXT
  );
  
  CREATE TABLE etapes_dossiers (
    id BIGSERIAL PRIMARY KEY,
    dossier_id BIGINT,
    type_etape VARCHAR(50), -- depot/commission/vote/promulgation
    date_etape DATE,
    description TEXT,
    organe_id BIGINT NULLABLE
  );
  ```
- **Fonctionnalités** :
  - Timeline interactive du parcours législatif
  - Analyse : temps moyen par thématique
  - Lien avec votes/amendements
- **Estimation** : 300+ dossiers
- **Délai** : 1 mois

### 4.2 Réserve parlementaire (historique)
- **Source** : [GitHub - Réserve](https://github.com/regardscitoyens/reserveparlementaire_parser)
- **Tables** :
  ```sql
  CREATE TABLE reserve_parlementaire (
    id BIGSERIAL PRIMARY KEY,
    depute_senateur_id BIGINT,
    annee INT,
    beneficiaire VARCHAR(255),
    type_beneficiaire VARCHAR(50), -- association/commune/...
    montant_euros DECIMAL(10,2),
    objet TEXT
  );
  ```
- **Visualisation** : Carte de répartition
- **Délai** : 1 semaine

### 4.3 Déclarations d'intérêts
- **Source** : [Data.gouv.fr](https://www.data.gouv.fr/fr/datasets/declarations-d-interets-des-parlementaires-publiees-par-la-haute-autorite-pour-la-transparence/)
- **Champs** : Ajouter `url_declaration_interets` à `deputes_senateurs`
- **Affichage** : Lien vers la déclaration HATVP
- **Délai** : 2 jours

---

## 📊 **INDICATEURS DE SUCCÈS**

### **Phase 1**
- ✅ 150-200k votes importés
- ✅ 40-60k interventions importées
- ✅ 15-25k questions importées
- 🎯 100-150k amendements importés
- 🎯 30+ commissions importées

### **Phase 2**
- 🎯 Moteur de recherche opérationnel (< 1s par requête)
- 🎯 3+ visualisations interactives
- 🎯 Taux de présence réel calculé pour 100% des députés

### **Phase 3**
- 🎯 Base de 500+ lobbyistes
- 🎯 1000+ auditions tracées
- 🎯 Comptes Twitter pour 90% des députés

### **Phase 4**
- 🎯 300+ dossiers législatifs complets
- 🎯 Timeline interactive fonctionnelle
- 🎯 Réserve parlementaire historique (2012-2017)

---

## ⏱️ **CALENDRIER PRÉVISIONNEL**

| Phase | Début | Fin | Durée |
|-------|-------|-----|-------|
| Phase 0 | Oct 2025 | Nov 2025 | ✅ Terminé |
| **Phase 1** | **Nov 2025** | **Nov 2025** | **3 semaines** |
| Phase 2 | Déc 2025 | Jan 2026 | 1 mois |
| Phase 3 | Jan 2026 | Mar 2026 | 2-3 mois |
| Phase 4 | Mar 2026 | Août 2026 | 6 mois |

---

## 🎯 **PROCHAINES ACTIONS IMMÉDIATES**

### **Cette semaine (Novembre 2025)**
1. ✅ Fix API votes/interventions/questions
2. 🔄 **Créer migration amendements**
3. 🔄 **Créer modèle Amendement**
4. 🔄 **Créer commande `enrich:amendements`**
5. 🔄 **Tester import sur 10 députés**

### **Semaine prochaine**
6. Import amendements complet
7. Créer migration commissions/organes
8. Créer commande `import:organes-parlementaires`
9. Afficher amendements sur fiche député
10. Statistiques amendements (taux d'adoption)

---

## 📚 **RESSOURCES & RÉFÉRENCES**

- 📖 [API NosDéputés.fr](https://github.com/regardscitoyens/nosdeputes.fr/blob/master/doc/api.md)
- 📖 [OpenData Parlementaire](https://github.com/regardscitoyens/nosdeputes.fr/blob/master/doc/opendata.md)
- 📖 [Modèle de données](https://github.com/regardscitoyens/nosdeputes.fr/blob/master/doc/data_model.md)
- 🗄️ [Dumps SQL](https://www.regardscitoyens.org/telechargement/donnees/)
- 🌐 [ParlAPI.fr](http://parlapi.fr)
- 🏛️ [LaFabriqueDeLaLoi.fr API](https://www.lafabriquedelaloi.fr/api/)

---

**💪 Let's go ! Phase 1 en cours d'exécution ! 🚀**

