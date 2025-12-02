# 📊 Sources de Données - HATVP

**Documentation des sources Open Data de la Haute Autorité pour la Transparence de la Vie Publique**

> Site officiel : https://www.hatvp.fr/

---

## 🔗 Vue d'Ensemble

La HATVP publie les déclarations d'intérêts et de patrimoine des responsables publics, incluant :
- **Députés** et **Sénateurs**
- Membres du Gouvernement
- Élus locaux
- Hauts fonctionnaires

---

## 📁 Sources de Données

### URLs Principales

| Source | URL | Description |
|--------|-----|-------------|
| **Toutes déclarations** | `https://www.hatvp.fr/livraison/merge/declarations.xml` | Export complet de toutes les déclarations |
| **Déclaration individuelle** | `https://www.hatvp.fr/livraison/dossiers/{slug}-{type}{id}-{mandat}-{dept}.xml` | Déclaration d'un élu spécifique |
| **Fiche nominative** | `https://www.hatvp.fr/fiche-nominative/?declarant={slug}` | Page web de consultation |

### Format des URLs Individuelles

```
https://www.hatvp.fr/livraison/dossiers/{nom}-{prenom}-{type}{id}-{mandat}-{dept}.xml
```

Exemples :
- `vermeillet-sylvie-diam30437-senateur-39.xml`
- `pernot-clement-diam28489-senateur-39.xml`

| Composant | Description | Exemple |
|-----------|-------------|---------|
| `nom-prenom` | Slug du nom | `vermeillet-sylvie` |
| `type` | Type de déclaration | `dia` (intérêts), `dsp` (patrimoine) |
| `id` | Identifiant unique | `m30437` |
| `mandat` | Type de mandat | `senateur`, `depute` |
| `dept` | Code département | `39` |

---

## 📋 Types de Déclarations

### Déclarations d'Intérêts et d'Activités (DIA)

| Code | Libellé |
|------|---------|
| `DIA` | Déclaration d'intérêts et d'activités modificative |
| `DIAC` | Déclaration d'intérêts et d'activités de fin de mandat |
| `DIAI` | Déclaration initiale d'intérêts et d'activités |

### Déclarations de Situation Patrimoniale (DSP)

| Code | Libellé |
|------|---------|
| `DSP` | Déclaration de situation patrimoniale modificative |
| `DSPC` | Déclaration de situation patrimoniale de fin de mandat |
| `DSPI` | Déclaration initiale de situation patrimoniale |

---

## 🏗️ Structure XML - Déclaration d'Intérêts

### Métadonnées

```xml
<?xml version="1.0" encoding="UTF-8"?>
<declaration>
  <dateDepot>18/11/2024 10:30:48</dateDepot>
  <uuid>2f3519f6-cd1c-4ca8-9f1b-b5356b9f1f61</uuid>
  <origine>ADEL</origine>
  <complete>true</complete>
  <declarationVersion>20171221</declarationVersion>
  
  <!-- Sections de données -->
</declaration>
```

### Sections Principales

| Section | XPath | Description |
|---------|-------|-------------|
| **Activités consultant** | `/declaration/activConsultantDto` | Activités de conseil |
| **Activités professionnelles** | `/declaration/activProfCinqDerniereDto` | 5 dernières années |
| **Activités conjoint** | `/declaration/activProfConjointDto` | Profession du conjoint |
| **Fonctions bénévoles** | `/declaration/fonctionBenevoleDto` | Associations, etc. |
| **Mandats électifs** | `/declaration/mandatElectifDto` | Fonctions électives |
| **Organes dirigeants** | `/declaration/participationDirigeantDto` | Conseils d'administration |
| **Participations financières** | `/declaration/participationFinanciereDto` | Parts de sociétés |
| **Collaborateurs** | `/declaration/activCollaborateursDto` | Assistants parlementaires |
| **Observations** | `/declaration/observationInteretDto` | Notes libres |
| **Informations générales** | `/declaration/general` | Identité du déclarant |

### Structure d'une Activité

```xml
<mandatElectifDto>
  <items>
    <items>
      <motif>
        <id>CREATION</id>
        <label/>
      </motif>
      <commentaire>Commentaire optionnel</commentaire>
      <conservee>true</conservee>
      <descriptionMandat>Sénatrice</descriptionMandat>
      <remuneration>
        <brutNet>Net</brutNet>
        <montant>
          <montant>
            <annee>2023</annee>
            <montant>52 337</montant>
          </montant>
        </montant>
      </remuneration>
      <dateDebut>01/2018</dateDebut>
      <dateFin>09/2023</dateFin>
    </items>
  </items>
  <neant>false</neant>
</mandatElectifDto>
```

### Informations du Déclarant

```xml
<general>
  <typeDeclaration>
    <id>DIA</id>
    <label>Déclaration d'intérêts et d'activités modificative</label>
  </typeDeclaration>
  <qualiteMandat>
    <typeMandat>Sénateur</typeMandat>
    <codCategorieMandat>PAR</codCategorieMandat>
    <labelTypeMandat>Sénateur</labelTypeMandat>
  </qualiteMandat>
  <organe>
    <codeOrgane>39</codeOrgane>
    <labelOrgane>Jura(39)</labelOrgane>
  </organe>
  <dateDebutMandat>24/09/2024</dateDebutMandat>
  <declarant>
    <civilite>Mme</civilite>
    <nom>VERMEILLET</nom>
    <prenom>SYLVIE</prenom>
    <dateNaissance>10/06/1967</dateNaissance>
    <!-- Données personnelles non publiées -->
  </declarant>
</general>
```

---

## 🏗️ Structure XML - Déclaration de Patrimoine

### Sections Principales

| Section | XPath | Description |
|---------|-------|-------------|
| **Immeubles** | `/declaration/immeubleDto` | Biens immobiliers |
| **SCI** | `/declaration/sciDto` | Parts de SCI |
| **Valeurs non cotées** | `/declaration/valeursNonEnBourseDto` | Actions non cotées |
| **Valeurs cotées** | `/declaration/valeursEnBourseDto` | Actions cotées |
| **Assurances vie** | `/declaration/assuranceVieDto` | Contrats d'assurance |
| **Comptes bancaires** | `/declaration/comptesBancaireDto` | Comptes et épargne |
| **Biens divers** | `/declaration/bienDiverDto` | Biens > 10 000€ |
| **Véhicules** | `/declaration/vehiculeDto` | Véhicules à moteur |
| **Fonds de commerce** | `/declaration/fondDto` | Activités commerciales |
| **Autres biens** | `/declaration/autreBienDto` | Stock-options, etc. |
| **Biens à l'étranger** | `/declaration/bienEtrangerDto` | Patrimoine étranger |
| **Passif** | `/declaration/passifDto` | Dettes et emprunts |
| **Revenus** | `/declaration/revenuMandatDto` | Revenus annuels |
| **Événements majeurs** | `/declaration/evenementMajeurDto` | Héritages, etc. |

### Structure d'un Immeuble

```xml
<immeubleDto>
  <items>
    <items>
      <nature>Maison</nature>
      <adresse>[Non publié]</adresse>
      <codePostal>39000</codePostal>
      <localite>Lons-le-Saunier</localite>
      <superficieBati>150</superficieBati>
      <superficieNonBati>500</superficieNonBati>
      <dateAcquisition>2005</dateAcquisition>
      <origine>Acquisition</origine>
      <droitReel>Pleine propriété</droitReel>
      <quotePart>100</quotePart>
      <prixAcquisition>200000</prixAcquisition>
      <prixTravaux>50000</prixTravaux>
      <valeurVenale>350000</valeurVenale>
    </items>
  </items>
  <neant>false</neant>
</immeubleDto>
```

### Structure des Revenus

```xml
<revenuMandatDto>
  <items>
    <items>
      <annee>2023</annee>
      <revenuMandatItem0>
        <typeRevenu>Net</typeRevenu>
        <revenuElu>67088</revenuElu>
        <revenuConjoint>0</revenuConjoint>
      </revenuMandatItem0>
      <!-- Items 1-8 pour autres types de revenus -->
      <totalElu>67088</totalElu>
      <totalConjoint>0</totalConjoint>
    </items>
  </items>
  <neant>false</neant>
</revenuMandatDto>
```

---

## 🗄️ Schéma Base de Données Proposé

### Tables Principales

```sql
-- Déclarations (table mère)
CREATE TABLE hatvp_declarations (
    id SERIAL PRIMARY KEY,
    uuid UUID UNIQUE NOT NULL,
    date_depot TIMESTAMP NOT NULL,
    type_declaration VARCHAR(10) NOT NULL,  -- DIA, DSP, DIAC, etc.
    origine VARCHAR(20),                     -- ADEL
    complete BOOLEAN DEFAULT true,
    version VARCHAR(20),
    
    -- Lien vers le parlementaire
    parlementaire_type VARCHAR(20),          -- senateur, depute
    parlementaire_id VARCHAR(20),            -- matricule ou uid
    
    -- Infos déclarant
    civilite VARCHAR(10),
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    
    -- Mandat
    type_mandat VARCHAR(50),
    code_departement VARCHAR(10),
    date_debut_mandat DATE,
    date_fin_mandat DATE,
    
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Mandats électifs déclarés
CREATE TABLE hatvp_mandats_electifs (
    id SERIAL PRIMARY KEY,
    declaration_id INTEGER REFERENCES hatvp_declarations(id),
    description VARCHAR(500),
    date_debut DATE,
    date_fin DATE,
    conservee BOOLEAN,
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Rémunérations par année
CREATE TABLE hatvp_remunerations (
    id SERIAL PRIMARY KEY,
    mandat_id INTEGER REFERENCES hatvp_mandats_electifs(id),
    annee INTEGER NOT NULL,
    montant DECIMAL(12,2),
    brut_net VARCHAR(10),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Fonctions bénévoles
CREATE TABLE hatvp_fonctions_benevoles (
    id SERIAL PRIMARY KEY,
    declaration_id INTEGER REFERENCES hatvp_declarations(id),
    nom_structure VARCHAR(500),
    description_activite TEXT,
    conservee BOOLEAN,
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Participations dirigeantes
CREATE TABLE hatvp_participations_dirigeantes (
    id SERIAL PRIMARY KEY,
    declaration_id INTEGER REFERENCES hatvp_declarations(id),
    nom_societe VARCHAR(500),
    activite TEXT,
    date_debut DATE,
    date_fin DATE,
    conservee BOOLEAN,
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Collaborateurs parlementaires
CREATE TABLE hatvp_collaborateurs (
    id SERIAL PRIMARY KEY,
    declaration_id INTEGER REFERENCES hatvp_declarations(id),
    nom VARCHAR(200),
    employeur VARCHAR(500),
    description_activite TEXT,
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Patrimoine immobilier (DSP uniquement)
CREATE TABLE hatvp_immeubles (
    id SERIAL PRIMARY KEY,
    declaration_id INTEGER REFERENCES hatvp_declarations(id),
    nature VARCHAR(100),
    code_postal VARCHAR(10),
    localite VARCHAR(200),
    superficie_bati INTEGER,
    superficie_non_bati INTEGER,
    date_acquisition INTEGER,  -- Année
    origine VARCHAR(100),
    droit_reel VARCHAR(100),
    quote_part INTEGER,
    prix_acquisition DECIMAL(12,2),
    prix_travaux DECIMAL(12,2),
    valeur_venale DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Revenus annuels (DSP uniquement)
CREATE TABLE hatvp_revenus (
    id SERIAL PRIMARY KEY,
    declaration_id INTEGER REFERENCES hatvp_declarations(id),
    annee INTEGER NOT NULL,
    type_revenu VARCHAR(50),  -- indemnites, salaires, pensions, etc.
    montant_elu DECIMAL(12,2),
    montant_conjoint DECIMAL(12,2),
    brut_net VARCHAR(10),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Index pour les recherches
CREATE INDEX idx_hatvp_declarations_uuid ON hatvp_declarations(uuid);
CREATE INDEX idx_hatvp_declarations_nom ON hatvp_declarations(nom, prenom);
CREATE INDEX idx_hatvp_declarations_type ON hatvp_declarations(type_declaration);
CREATE INDEX idx_hatvp_declarations_parlementaire ON hatvp_declarations(parlementaire_type, parlementaire_id);
```

---

## 🔄 Architecture de Synchronisation

### Configuration (config/hatvp.php)

```php
<?php

return [
    'base_url' => 'https://www.hatvp.fr/livraison',
    
    'sources' => [
        'declarations' => [
            'url' => 'https://www.hatvp.fr/livraison/merge/declarations.xml',
            'description' => 'Export complet de toutes les déclarations',
        ],
        'dossiers' => [
            'url' => 'https://www.hatvp.fr/livraison/dossiers/',
            'description' => 'Déclarations individuelles',
        ],
    ],
    
    'types_declarations' => [
        'DIA' => 'Déclaration d\'intérêts et d\'activités modificative',
        'DIAC' => 'Déclaration d\'intérêts de fin de mandat',
        'DIAI' => 'Déclaration initiale d\'intérêts',
        'DSP' => 'Déclaration de patrimoine modificative',
        'DSPC' => 'Déclaration de patrimoine de fin de mandat',
        'DSPI' => 'Déclaration initiale de patrimoine',
    ],
    
    'types_mandats' => [
        'senateur' => 'Sénateur',
        'depute' => 'Député',
        'depute-europeen' => 'Député européen',
        'ministre' => 'Membre du Gouvernement',
    ],
    
    'filtres_parlementaires' => [
        'senateur',
        'depute',
    ],
    
    'storage' => [
        'path' => storage_path('app/hatvp-data'),
        'xml_path' => storage_path('app/hatvp-data/xml'),
        'cache_path' => storage_path('app/hatvp-data/cache'),
    ],
    
    'cache' => [
        'enabled' => true,
        'duration' => 86400, // 24 heures
    ],
];
```

### Commandes Artisan

```bash
# Synchroniser toutes les déclarations
php artisan hatvp:sync

# Synchroniser uniquement les parlementaires
php artisan hatvp:sync --parlementaires

# Synchroniser un type spécifique
php artisan hatvp:sync --type=senateur

# Voir le statut
php artisan hatvp:sync --status

# Forcer le re-téléchargement
php artisan hatvp:sync --force
```

---

## 📊 Cas d'Usage

### 1. Affichage sur la Fiche Parlementaire

```php
// Dans le contrôleur
public function show(Senateur $senateur)
{
    $declarations = HatvpDeclaration::where('parlementaire_type', 'senateur')
        ->where('parlementaire_id', $senateur->matricule)
        ->orderBy('date_depot', 'desc')
        ->get();
    
    $derniereDeclaration = $declarations->first();
    
    return view('senateur.show', [
        'senateur' => $senateur,
        'declarations' => $declarations,
        'mandatsElectifs' => $derniereDeclaration?->mandatsElectifs,
        'collaborateurs' => $derniereDeclaration?->collaborateurs,
    ]);
}
```

### 2. Statistiques Globales

```php
// Revenus moyens des sénateurs
$revenuMoyen = HatvpRevenu::whereHas('declaration', function ($q) {
    $q->where('type_mandat', 'Sénateur');
})
->where('annee', 2023)
->where('type_revenu', 'indemnites')
->avg('montant_elu');

// Nombre de mandats cumulés
$mandatsCumules = HatvpMandatElectif::whereHas('declaration', function ($q) {
    $q->where('type_mandat', 'Sénateur')
      ->where('type_declaration', 'DIA');
})
->where('conservee', true)
->count();
```

### 3. Recherche de Conflits d'Intérêts

```php
// Parlementaires avec participations financières
$avecParticipations = HatvpDeclaration::whereHas('participationsFinancieres')
    ->where('type_mandat', 'LIKE', '%Sénateur%')
    ->get();
```

---

## ⚠️ Données Non Publiées

Certaines informations sont masquées pour protéger la vie privée :

| Donnée | Statut |
|--------|--------|
| Email | `[Données non publiées]` |
| Téléphone | `[Données non publiées]` |
| Adresse complète | `[Données non publiées]` |
| Numéros de compte | `[Données non publiées]` |

Ces champs sont présents dans le XML mais contiennent la mention `[Données non publiées]`.

---

## 🔗 Liaison avec les Données Parlementaires

### Correspondance Sénateurs

```php
// Matcher par nom/prénom
$senateur = Senateur::where('nom', $declaration->nom)
    ->where('prenom', $declaration->prenom)
    ->first();

// Ou par département
$senateur = Senateur::where('nom', $declaration->nom)
    ->where('departement_code', $declaration->code_departement)
    ->first();
```

### Correspondance Députés

```php
// Matcher par nom/prénom
$depute = ActeurAN::whereHas('mandats', function ($q) {
    $q->where('type_organe', 'ASSEMBLEE');
})
->where('nom', $declaration->nom)
->where('prenom', $declaration->prenom)
->first();
```

---

## 📈 Volumétrie

| Type | Estimation |
|------|------------|
| Sénateurs actifs | ~350 déclarations |
| Députés actifs | ~580 déclarations |
| Historique complet | ~5000 déclarations |
| Taille fichier global | ~100 Mo |

---

## 🚀 Plan d'Implémentation

### Phase 1 : Infrastructure
- [ ] Créer `config/hatvp.php`
- [ ] Créer migrations pour les tables
- [ ] Créer modèles Eloquent

### Phase 2 : Parser XML
- [ ] Créer `HatvpXmlParser` service
- [ ] Parser déclarations d'intérêts
- [ ] Parser déclarations de patrimoine

### Phase 3 : Import
- [ ] Commande `hatvp:sync`
- [ ] Liaison avec sénateurs/députés
- [ ] Gestion incrémentale

### Phase 4 : Affichage
- [ ] Onglet "Déclarations" sur fiche parlementaire
- [ ] Page statistiques HATVP
- [ ] Recherche par critères

---

## 🔗 Ressources

- [HATVP Open Data](https://www.hatvp.fr/open-data/)
- [Documentation interet.csv](public/data/interet.csv)
- [Documentation patrimoine.csv](public/data/patrimoine.csv)
- [Fiche nominative](https://www.hatvp.fr/fiche-nominative/)

