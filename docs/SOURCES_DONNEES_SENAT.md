# 📊 Sources de Données - Sénat

**Documentation des sources Open Data du Sénat français**

> Site officiel : https://data.senat.fr/

---

## 🔗 Vue d'Ensemble des Sources

Le Sénat propose deux types de sources de données :

| Type | Format | Description |
|------|--------|-------------|
| **Bases SQL** | PostgreSQL dump (.zip) | Données structurées complètes |
| **Textes Akoma Ntoso** | XML (.akn.xml) | Textes législatifs au format standard |

---

## 📁 Bases SQL PostgreSQL

### URLs des Exports

| Base | URL | Description | Taille estimée |
|------|-----|-------------|----------------|
| **Sénateurs** | `https://data.senat.fr/data/senateurs/export_sens.zip` | Profils complets des sénateurs | ~5 Mo |
| **Dossiers Législatifs** | `https://data.senat.fr/data/dosleg/dosleg.zip` | Travaux législatifs (DOSLEG) | ~50 Mo |
| **Questions** | `https://data.senat.fr/data/questions/questions.zip` | Questions au Gouvernement | ~30 Mo |
| **Débats** | `https://data.senat.fr/data/debats/debats.zip` | Comptes rendus des débats | ~200 Mo |
| **Amendements** | `https://data.senat.fr/data/ameli/ameli.zip` | Base AMELI | ~100 Mo |

### Préfixes de Tables

| Base | Préfixe | Exemple |
|------|---------|---------|
| Sénateurs | `senat_senateurs_` | `senat_senateurs_sen`, `senat_senateurs_votes` |
| Dossiers | `senat_dosleg_` | `senat_dosleg_dos`, `senat_dosleg_tex` |
| Questions | `senat_questions_` | `senat_questions_que`, `senat_questions_rep` |
| Débats | `senat_debats_` | `senat_debats_sea`, `senat_debats_int` |
| Amendements | `senat_ameli_` | `senat_ameli_amd`, `senat_ameli_amdsen` |

---

## 🧑‍💼 Base Sénateurs (export_sens)

> Documentation : https://data.senat.fr/aide/senateurs/

### Tables Principales

| Table | Description | Clé primaire |
|-------|-------------|--------------|
| `sen` | Sénateurs (profils) | `senmat` (matricule) |
| `qua` | Civilités | `quacod` |
| `votes` | Votes individuels | `votesid` |
| `scr` | Scrutins | `scrid` |
| `typscr` | Types de scrutins | `typscrcod` |
| `grppol` | Groupes politiques | `grppolcod` |
| `com` | Commissions | `comcod` |
| `man` | Mandats | `manid` |

### Structure Table `sen` (Sénateurs)

```sql
CREATE TABLE sen (
    senmat CHARACTER(6) PRIMARY KEY,  -- Matricule (ex: 20032T)
    quacod CHARACTER(1),              -- Code civilité
    sennomuse VARCHAR(64),            -- Nom d'usage
    senprenomuse VARCHAR(64),         -- Prénom d'usage
    sendatnai DATE,                   -- Date de naissance
    sendatdec DATE,                   -- Date de décès
    etasencod VARCHAR(10),            -- État (ACTIF, ANCIEN)
    sengrppolliccou VARCHAR(128),     -- Groupe politique actuel
    sengrppolcodcou VARCHAR(10),      -- Code groupe politique
    sentypappcou VARCHAR(20),         -- Type appartenance (A, R, N)
    sencomliccou VARCHAR(128),        -- Commission actuelle
    sencircou VARCHAR(128),           -- Circonscription
    sencirnumcou INTEGER,             -- Numéro département
    senema VARCHAR(128),              -- Email
    sendespro TEXT,                   -- Profession
    pcscod VARCHAR(10),               -- Code PCS INSEE
    sendaiurl VARCHAR(256)            -- URL fiche sénateur
);
```

### Structure Table `votes` (Votes individuels)

```sql
CREATE TABLE votes (
    votesid INTEGER PRIMARY KEY,
    senmat CHARACTER(6),              -- Matricule sénateur
    scrid INTEGER,                    -- ID scrutin
    posvotcod CHARACTER(2)            -- Position: P=Pour, C=Contre, A=Abstention, NV=Non votant
);
```

### Structure Table `scr` (Scrutins)

```sql
CREATE TABLE scr (
    scrid INTEGER PRIMARY KEY,
    sesann INTEGER,                   -- Année session
    scrnum INTEGER,                   -- Numéro scrutin
    typscrcod VARCHAR(10),            -- Type scrutin
    scrdat TIMESTAMP,                 -- Date scrutin
    scrint VARCHAR(512),              -- Intitulé court
    scrintext TEXT,                   -- Intitulé complet
    scrpou INTEGER,                   -- Votes pour
    scrcon INTEGER,                   -- Votes contre
    scrvot INTEGER,                   -- Nombre votants
    scrsuf INTEGER,                   -- Suffrages exprimés
    scrmaj INTEGER                    -- Majorité requise
);
```

---

## 📋 Base Dossiers Législatifs (DOSLEG)

> Documentation : https://data.senat.fr/aide/travaux-legislatifs-base-dosleg/

### Tables Principales

| Table | Description | Clé primaire |
|-------|-------------|--------------|
| `dos` | Dossiers législatifs | `dosid` |
| `tex` | Textes | `texid` |
| `typtex` | Types de textes | `typtexcod` |
| `eta` | Étapes législatives | `etaid` |
| `typeta` | Types d'étapes | `typetacod` |
| `rap` | Rapports | `rapid` |
| `amd` | Amendements (résumé) | `amdid` |
| `autdos` | Auteurs des dossiers | `autdosid` |

### Structure Table `dos` (Dossiers)

```sql
CREATE TABLE dos (
    dosid INTEGER PRIMARY KEY,
    dostitcou VARCHAR(512),           -- Titre courant
    dostitlon TEXT,                   -- Titre long
    dostyplib VARCHAR(64),            -- Type (Projet, Proposition...)
    dosurl VARCHAR(256),              -- URL dossier
    dosdatdep DATE,                   -- Date dépôt
    dosdatpro DATE,                   -- Date promulgation
    dosref VARCHAR(64),               -- Référence
    dosleg INTEGER                    -- Législature
);
```

### Structure Table `tex` (Textes)

```sql
CREATE TABLE tex (
    texid INTEGER PRIMARY KEY,
    dosid INTEGER,                    -- Dossier parent
    typtexcod VARCHAR(10),            -- Type texte
    texnum INTEGER,                   -- Numéro texte
    texses VARCHAR(10),               -- Session
    texdat DATE,                      -- Date
    textit VARCHAR(512),              -- Titre
    texurl VARCHAR(256)               -- URL
);
```

---

## ❓ Base Questions (questions)

> Documentation : https://data.senat.fr/aide/notice-explicative-questions/

### Tables Principales

| Table | Description | Clé primaire |
|-------|-------------|--------------|
| `que` | Questions | `queid` |
| `rep` | Réponses | `repid` |
| `typque` | Types de questions | `typquecod` |
| `min` | Ministères | `mincod` |
| `the` | Thèmes | `thecod` |
| `quethe` | Liens question-thème | composite |

### Types de Questions

| Code | Description |
|------|-------------|
| `QE` | Question écrite |
| `QO` | Question orale |
| `QOG` | Question orale avec débat |
| `QAG` | Question d'actualité au Gouvernement |

### Structure Table `que` (Questions)

```sql
CREATE TABLE que (
    queid INTEGER PRIMARY KEY,
    typquecod VARCHAR(10),            -- Type question
    quenum INTEGER,                   -- Numéro
    queses VARCHAR(10),               -- Session
    senmat CHARACTER(6),              -- Auteur (matricule)
    mincod VARCHAR(10),               -- Ministère destinataire
    quedat DATE,                      -- Date question
    quetit VARCHAR(512),              -- Titre
    quetex TEXT,                      -- Texte complet
    queurl VARCHAR(256)               -- URL
);
```

### Structure Table `rep` (Réponses)

```sql
CREATE TABLE rep (
    repid INTEGER PRIMARY KEY,
    queid INTEGER,                    -- Question parent
    repdat DATE,                      -- Date réponse
    reptex TEXT,                      -- Texte réponse
    repurl VARCHAR(256)               -- URL
);
```

---

## 📝 Base Amendements (AMELI)

> Documentation : https://data.senat.fr/aide/notice-explicative-ameli/

### Tables Principales

| Table | Description | Clé primaire |
|-------|-------------|--------------|
| `amd` | Amendements | `id` |
| `amdsen` | Auteurs sénateurs | composite |
| `sor` | Sorts (résultats) | `id` |
| `tex` | Textes législatifs | `id` |
| `art` | Articles | `id` |

### Sorts des Amendements

| Code | Libellé |
|------|---------|
| `A` | Adopté |
| `AM` | Adopté (modifié) |
| `AB` | Adopté (sous-amendé) |
| `RJS` | Rejeté (scrutin) |
| `RJ` | Rejeté |
| `RJB` | Rejeté (sous-amendement) |
| `R` | Retiré |
| `RET` | Retiré (avant discussion) |
| `S` | Tombé (satisfait) |
| `N` | Non soutenu |
| `SO` | Sans objet |

### Structure Table `amd` (Amendements)

```sql
CREATE TABLE amd (
    id INTEGER PRIMARY KEY,
    texid INTEGER,                    -- Texte législatif
    num VARCHAR(20),                  -- Numéro amendement
    typ VARCHAR(10),                  -- Type (A=Article, AA=Après article...)
    artid INTEGER,                    -- Article concerné
    dis TEXT,                         -- Dispositif
    obj TEXT,                         -- Exposé des motifs
    datdep TIMESTAMP,                 -- Date dépôt
    sorid INTEGER                     -- Sort (résultat)
);
```

### Structure Table `amdsen` (Auteurs)

```sql
CREATE TABLE amdsen (
    amdid INTEGER,                    -- Amendement
    senid INTEGER,                    -- ID sénateur (entid dans sen_ameli)
    rng INTEGER,                      -- Rang (1=auteur principal)
    nomuse VARCHAR(64),               -- Nom
    prenomuse VARCHAR(64),            -- Prénom
    grpid INTEGER                     -- Groupe politique
);
```

### Table de Correspondance `sen_ameli`

Cette table fait le lien entre les IDs numériques d'AMELI et les matricules sénateurs :

```sql
CREATE TABLE sen_ameli (
    entid INTEGER PRIMARY KEY,        -- ID numérique AMELI
    mat CHARACTER(6),                 -- Matricule sénateur (ex: 20032T)
    grpid INTEGER,
    comid INTEGER,
    nomuse VARCHAR(64),
    prenomuse VARCHAR(64)
);
```

---

## 📄 Textes Akoma Ntoso (XML)

> Documentation : https://data.senat.fr/wp-content/uploads/2021/03/akomantoso.pdf

### Flux XML Disponibles

| Flux | URL | Description |
|------|-----|-------------|
| **Textes déposés** | `https://www.senat.fr/akomantoso/depots.xml` | Liste des textes récemment déposés |
| **Textes adoptés** | `https://www.senat.fr/akomantoso/adoptions.xml` | Liste des textes adoptés |

### Structure du Flux (depots.xml)

```xml
<?xml version='1.0' encoding='UTF-8' ?>
<texts xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <text>
    <url>https://www.senat.fr/akomantoso/ppl25-170.akn.xml</url>
    <lastModified>Tue Dec 02 11:56:58 CET 2025</lastModified>
    <lastModifiedDateTime>2025-12-02T11:56:58</lastModifiedDateTime>
  </text>
  <!-- ... autres textes ... -->
</texts>
```

### Nomenclature des Fichiers

| Préfixe | Signification | Exemple |
|---------|---------------|---------|
| `ppl` | Proposition de loi | `ppl25-170.akn.xml` |
| `pjl` | Projet de loi | `pjl25-160.akn.xml` |
| `ppr` | Proposition de résolution | `ppr25-152.akn.xml` |

Le numéro après le préfixe indique :
- `25` : Année (2025)
- `170` : Numéro du texte

### Structure Akoma Ntoso

```xml
<?xml version="1.0" encoding="UTF-8"?>
<akomaNtoso xmlns="http://docs.oasis-open.org/legaldocml/ns/akn/3.0">
  <bill name="ppl25-170">
    <meta>
      <identification source="#senat">
        <FRBRWork>
          <FRBRthis value="/akn/fr/bill/senat/2025/ppl25-170"/>
          <FRBRdate date="2025-12-02" name="deposit"/>
          <FRBRauthor href="#senat"/>
        </FRBRWork>
      </identification>
      <references>
        <TLCOrganization eId="senat" href="/ontology/organization/fr/senat" showAs="Sénat"/>
        <TLCPerson eId="author" href="/ontology/person/fr/senateur/20032T" showAs="M. DUPONT Jean"/>
      </references>
    </meta>
    <preface>
      <docTitle>Proposition de loi relative à...</docTitle>
      <docProponent>Présenté par M. Jean DUPONT, Sénateur</docProponent>
    </preface>
    <body>
      <article eId="art_1">
        <num>Article 1er</num>
        <content>
          <p>Le code de... est ainsi modifié...</p>
        </content>
      </article>
    </body>
  </bill>
</akomaNtoso>
```

---

## 🔄 Architecture de Synchronisation

### Commandes Existantes

```bash
# Import base SQL PostgreSQL
php artisan import:senat-sql senateurs
php artisan import:senat-sql dosleg
php artisan import:senat-sql questions
php artisan import:senat-sql debats
php artisan import:senat-sql ameli

# Options
--fresh      # Vider les tables avant import
--analyze    # Analyser la structure sans importer
```

### Nouvelles Commandes Proposées

```bash
# Synchronisation globale
php artisan senat:sync                    # Tout synchroniser
php artisan senat:sync senateurs          # Source spécifique
php artisan senat:sync --incremental      # Mode incrémental

# Textes Akoma Ntoso
php artisan senat:sync-textes             # Sync textes déposés/adoptés
php artisan senat:sync-textes --since=7   # Depuis 7 jours

# Statut
php artisan senat:status                  # État des données
```

---

## 📊 Volumétrie

| Source | Enregistrements | Taille |
|--------|-----------------|--------|
| Sénateurs (sen) | ~2000 | 5 Mo |
| Votes (votes) | ~500 000 | 50 Mo |
| Scrutins (scr) | ~5000 | 5 Mo |
| Dossiers (dos) | ~10 000 | 20 Mo |
| Textes (tex) | ~50 000 | 30 Mo |
| Questions (que) | ~100 000 | 100 Mo |
| Amendements (amd) | ~200 000 | 150 Mo |

**Total estimé** : ~500 Mo de données

---

## 🚀 Plan d'Implémentation

### Phase 1 : Configuration (fait ✅)
- [x] Commande `import:senat-sql` existante
- [x] Préfixes de tables configurés
- [x] Import PostgreSQL fonctionnel

### Phase 2 : Service Unifié
- [ ] Créer `config/senat.php`
- [ ] Créer `SenatDataDownloader` service
- [ ] Créer commande `senat:sync`

### Phase 3 : Textes Akoma Ntoso
- [ ] Parser XML Akoma Ntoso
- [ ] Modèle `TexteSenat`
- [ ] Commande `senat:sync-textes`

### Phase 4 : Vues SQL
- [ ] Consolider les vues existantes
- [ ] Ajouter vues manquantes
- [ ] Tests de cohérence

---

## 🔗 Ressources

- [Open Data Sénat](https://data.senat.fr/)
- [Documentation DOSLEG](https://data.senat.fr/aide/travaux-legislatifs-base-dosleg/)
- [Documentation AMELI](https://data.senat.fr/aide/notice-explicative-ameli/)
- [Documentation Questions](https://data.senat.fr/aide/notice-explicative-questions/)
- [Akoma Ntoso (PDF)](https://data.senat.fr/wp-content/uploads/2021/03/akomantoso.pdf)
- [Akoma Ntoso Standard](http://docs.oasis-open.org/legaldocml/akn-core/v1.0/)

