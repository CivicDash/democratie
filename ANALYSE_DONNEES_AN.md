# 🏛️ ANALYSE DES DONNÉES OFFICIELLES DE L'ASSEMBLÉE NATIONALE

**Date de génération :** 18 novembre 2025  
**Source :** `public/data/` (JSON officiels AN)

---

## 📊 **INVENTAIRE DES FICHIERS**

| Répertoire | Fichiers | Description |
|------------|----------|-------------|
| **acteur/** | 603 JSON | Profils des acteurs politiques (députés, sénateurs, ministres) |
| **mandat/** | 29 702 JSON | Tous les mandats parlementaires (1 mandat = 1 fichier) |
| **organe/** | 8 957 JSON | Organes parlementaires (groupes, commissions, délégations) |
| **scrutins/** | 3 876 JSON | Scrutins publics avec votes nominatifs détaillés |
| **reunion/** | 4 601 JSON | Réunions de commission et séances plénières |
| **deport/** | 37 JSON | Déports (absences justifiées, conflits d'intérêt) |
| **pays/** | 199 JSON | Liste des pays (pour origines, missions diplomatiques) |

**Total : ~47 975 fichiers JSON** (données exhaustives de l'Assemblée Nationale) 🎉

---

## 🔍 **STRUCTURE DES DONNÉES**

### 1️⃣ **ACTEUR** (`acteur/PA1008.json`)

**Représente un individu** (député, sénateur, ministre, etc.)

```json
{
  "acteur": {
    "uid": "PA1008",
    "etatCivil": {
      "ident": {
        "civ": "M.",
        "prenom": "Alain",
        "nom": "David",
        "trigramme": "ADA"
      },
      "infoNaissance": {
        "dateNais": "1949-06-02",
        "villeNais": "Libourne",
        "depNais": "Gironde"
      }
    },
    "profession": {
      "libelleCourant": "Ingénieur",
      "socProcINSEE": {
        "catSocPro": "Cadres des services administratifs..."
      }
    },
    "uri_hatvp": "https://www.hatvp.fr/pages_nominatives/...",
    "adresses": [
      {
        "type": "0",
        "typeLibelle": "Adresse officielle",
        "numeroRue": "126",
        "nomRue": "Rue de l'Université",
        "ville": "Paris 07 SP"
      }
    ]
  }
}
```

**Clés importantes :**
- `uid` : Identifiant unique (PAxxxx)
- `etatCivil.ident` : Nom, prénom, trigramme
- `profession` : Catégorie socio-professionnelle INSEE
- `uri_hatvp` : Lien vers déclarations d'intérêts (HATVP)
- `adresses` : Coordonnées (bureaux, permanences)

---

### 2️⃣ **MANDAT** (`mandat/PM115583.json`)

**Représente une fonction parlementaire** (1 acteur = plusieurs mandats)

```json
{
  "mandat": {
    "uid": "PM115583",
    "acteurRef": "PA1654",
    "legislature": "9",
    "typeOrgane": "COMPER",
    "dateDebut": "1988-06-23",
    "dateFin": "1988-06-27",
    "infosQualite": {
      "codeQualite": "Membre",
      "libQualite": "Membre"
    },
    "organes": {
      "organeRef": "PO59048"
    }
  }
}
```

**Types de mandats :**
- `ASSEMBLEE` : Mandat de député
- `COMPER` : Membre de commission permanente
- `DELEG` : Membre de délégation
- `GP` : Membre de groupe parlementaire
- `ORGEXTPARL` : Organe extra-parlementaire (missions, commissions d'enquête)

**Relations :**
- `acteurRef` → `acteur/PAxxxx.json`
- `organeRef` → `organe/POxxxxx.json`

---

### 3️⃣ **ORGANE** (`organe/PO191887.json`)

**Représente une structure parlementaire** (groupes, commissions, etc.)

```json
{
  "organe": {
    "uid": "PO191887",
    "codeType": "ORGEXTPARL",
    "libelle": "Commission nationale pour l'élimination des mines antipersonnel",
    "libelleAbrege": "Mines antipersonnel",
    "viMoDe": {
      "dateDebut": "1999-05-11",
      "dateFin": null
    },
    "regime": "5ème République",
    "legislature": null,
    "siteInternet": "https://..."
  }
}
```

**Types d'organes :**
- `ASSEMBLEE` : Assemblée Nationale
- `GP` : Groupe parlementaire (Renaissance, LFI, LR, etc.)
- `COMPER` : Commission permanente (Finances, Affaires sociales, etc.)
- `DELEG` : Délégation (Européenne, Outre-mer, etc.)
- `ORGEXTPARL` : Organe extra-parlementaire

---

### 4️⃣ **SCRUTIN** (`scrutins/VTANR5L17V1000.json`)

**Représente un vote public** avec détails nominatifs

```json
{
  "scrutin": {
    "uid": "VTANR5L17V1000",
    "numero": "1000",
    "legislature": "17",
    "dateScrutin": "2025-03-13",
    "typeVote": {
      "codeTypeVote": "SPO",
      "libelleTypeVote": "scrutin public ordinaire"
    },
    "sort": {
      "code": "adopté",
      "libelle": "l'Assemblée nationale a adopté"
    },
    "titre": "l'amendement n° 29 de Mme Pirès Beaune...",
    "syntheseVote": {
      "nombreVotants": "41",
      "suffragesExprimes": "40",
      "decompte": {
        "pour": "26",
        "contre": "14",
        "abstentions": "1"
      }
    },
    "ventilationVotes": {
      "organe": {
        "groupes": {
          "groupe": [
            {
              "organeRef": "PO845401",
              "vote": {
                "positionMajoritaire": "pour",
                "decompteNominatif": {
                  "pours": {
                    "votant": [
                      {
                        "acteurRef": "PA793238",
                        "mandatRef": "PM842426",
                        "numPlace": "073"
                      }
                    ]
                  }
                }
              }
            }
          ]
        }
      }
    }
  }
}
```

**Informations exploitables :**
- Vote **individuel** de chaque député (pour/contre/abstention)
- Vote **par groupe politique**
- Lien avec les **mandats** et **acteurs**
- Contexte du vote (amendement, proposition de loi, etc.)

---

### 5️⃣ **REUNION** (`reunion/`)

**Représente une séance** (commission, hémicycle, etc.)

**À analyser** : Ordre du jour, présences, interventions

---

### 6️⃣ **DEPORT** (`deport/`)

**Représente un déport** (conflit d'intérêt, absence justifiée)

```
DPTR5L15PA335999D0001.json
DPTR5L16PA610002D0001.json
```

**Format :** `DPTR` + Législature + Acteur + Séquence

---

## 🎯 **OPPORTUNITÉS D'EXPLOITATION**

### ✅ **CE QU'ON PEUT FAIRE IMMÉDIATEMENT**

#### 1. **Import des acteurs actuels (législature 17)**

```bash
# Remplacer les données NosDéputés.fr par les données officielles AN
php artisan import:acteurs-an --legislature=17
```

**Bénéfices :**
- ✅ Données **officielles et à jour**
- ✅ Députés de la législature actuelle (2024-2029)
- ✅ Photos, professions, HATVP, adresses

---

#### 2. **Import des votes nominatifs**

```bash
# Importer TOUS les scrutins publics
php artisan import:scrutins-an --legislature=17
```

**Bénéfices :**
- ✅ 3 876 scrutins disponibles (multiple législatures)
- ✅ Vote **individuel** de chaque député
- ✅ Calcul de statistiques : taux de présence, cohérence de groupe, etc.

---

#### 3. **Cartographie des groupes et commissions**

```bash
# Importer organes + membres
php artisan import:organes-an --legislature=17
```

**Bénéfices :**
- ✅ Liste complète des groupes parlementaires
- ✅ Composition des 8 commissions permanentes
- ✅ Historique des rattachements (changements de groupe)

---

#### 4. **Analyse croisée : "Qui vote avec qui ?"**

**Exemple de requêtes SQL possibles :**

```sql
-- Députés les plus rebelles (vote contre leur groupe)
SELECT a.nom, COUNT(*) as votes_contre_groupe
FROM votes v
JOIN acteurs a ON v.acteur_ref = a.uid
WHERE v.position != v.position_groupe
GROUP BY a.nom
ORDER BY votes_contre_groupe DESC
LIMIT 20;

-- Taux de cohésion par groupe
SELECT g.libelle, 
       AVG(CASE WHEN v.position = v.position_groupe THEN 1 ELSE 0 END) * 100 as coherence
FROM votes v
JOIN groupes g ON v.groupe_ref = g.uid
GROUP BY g.libelle;
```

---

### 🚀 **FONCTIONNALITÉS AVANCÉES**

#### 1. **Graphe relationnel des votes**

- Calculer la **proximité de vote** entre députés
- Identifier les **coalitions informelles**
- Détecter les **dissidences**

#### 2. **Timeline d'activité**

- Visualiser l'évolution du positionnement d'un député
- Comparer les groupes au fil des scrutins

#### 3. **Alertes citoyennes**

- Notifier les citoyens quand leur député vote sur un sujet spécifique
- Afficher le vote de leur député par rapport au groupe

---

## 📐 **MODÈLE DE DONNÉES PROPOSÉ**

### **Nouvelles tables à créer :**

```php
// 1. Table acteurs (remplace deputes_senateurs ?)
Schema::create('acteurs_an', function (Blueprint $table) {
    $table->string('uid', 20)->primary(); // PA1008
    $table->string('civilite', 10);
    $table->string('prenom', 100);
    $table->string('nom', 100);
    $table->string('trigramme', 3)->index();
    $table->date('date_naissance')->nullable();
    $table->string('ville_naissance')->nullable();
    $table->string('departement_naissance')->nullable();
    $table->string('profession')->nullable();
    $table->string('categorie_socio_pro')->nullable();
    $table->string('url_hatvp')->nullable();
    $table->json('adresses')->nullable();
    $table->timestamps();
});

// 2. Table mandats
Schema::create('mandats_an', function (Blueprint $table) {
    $table->string('uid', 20)->primary(); // PM115583
    $table->string('acteur_ref', 20)->index(); // FK acteurs_an
    $table->integer('legislature')->index();
    $table->string('type_organe', 50); // ASSEMBLEE, COMPER, GP, etc.
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->string('qualite', 50); // Membre, Président, etc.
    $table->string('organe_ref', 20)->index(); // FK organes_an
    $table->timestamps();
    
    $table->foreign('acteur_ref')->references('uid')->on('acteurs_an');
    $table->foreign('organe_ref')->references('uid')->on('organes_an');
});

// 3. Table organes
Schema::create('organes_an', function (Blueprint $table) {
    $table->string('uid', 20)->primary(); // PO191887
    $table->string('code_type', 50); // GP, COMPER, DELEG, etc.
    $table->string('libelle', 255);
    $table->string('libelle_abrege', 100)->nullable();
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->integer('legislature')->nullable()->index();
    $table->string('site_internet')->nullable();
    $table->timestamps();
});

// 4. Table scrutins
Schema::create('scrutins_an', function (Blueprint $table) {
    $table->string('uid', 30)->primary(); // VTANR5L17V1000
    $table->integer('numero')->index();
    $table->integer('legislature')->index();
    $table->date('date_scrutin')->index();
    $table->string('type_vote', 50); // SPO = scrutin public ordinaire
    $table->string('resultat', 20); // adopté, rejeté
    $table->text('titre');
    $table->integer('nombre_votants');
    $table->integer('suffrages_exprimes');
    $table->integer('pour');
    $table->integer('contre');
    $table->integer('abstentions');
    $table->json('votes_nominatifs'); // Détail complet
    $table->timestamps();
});

// 5. Table votes_individuels (dénormalisé pour perfs)
Schema::create('votes_individuels_an', function (Blueprint $table) {
    $table->id();
    $table->string('scrutin_ref', 30)->index(); // FK scrutins_an
    $table->string('acteur_ref', 20)->index(); // FK acteurs_an
    $table->string('mandat_ref', 20); // FK mandats_an
    $table->string('groupe_ref', 20)->index(); // FK organes_an
    $table->enum('position', ['pour', 'contre', 'abstention', 'non_votant']);
    $table->enum('position_groupe', ['pour', 'contre', 'abstention'])->nullable();
    $table->string('numero_place', 10)->nullable();
    $table->boolean('par_delegation')->default(false);
    $table->timestamps();
    
    $table->foreign('scrutin_ref')->references('uid')->on('scrutins_an');
    $table->foreign('acteur_ref')->references('uid')->on('acteurs_an');
    $table->foreign('groupe_ref')->references('uid')->on('organes_an');
});

// 6. Table deports
Schema::create('deports_an', function (Blueprint $table) {
    $table->string('uid', 50)->primary();
    $table->string('acteur_ref', 20)->index();
    $table->integer('legislature');
    $table->json('details');
    $table->timestamps();
    
    $table->foreign('acteur_ref')->references('uid')->on('acteurs_an');
});
```

---

## 🛠️ **PLAN D'IMPLÉMENTATION**

### **PHASE 1 : Import des données de base (2-3h)**

1. ✅ Migration pour créer les 6 nouvelles tables
2. ✅ Modèles Eloquent (`ActeurAN`, `MandatAN`, `OrganeAN`, etc.)
3. ✅ Commande `ImportActeursAN` (parse `acteur/*.json`)
4. ✅ Commande `ImportOrganes AN` (parse `organe/*.json`)
5. ✅ Commande `ImportMandatsAN` (parse `mandat/*.json`)
6. ✅ Script shell `scripts/import_donnees_an.sh`

### **PHASE 2 : Import des scrutins (2-3h)**

1. ✅ Commande `ImportScrutinsAN` (parse `scrutins/*.json`)
2. ✅ Normalisation des votes individuels
3. ✅ Calcul des positions majoritaires par groupe
4. ✅ Script shell `scripts/import_scrutins_an.sh`

### **PHASE 3 : Analyse et visualisation (4-6h)**

1. ✅ API endpoint `/api/representants/{uid}/votes`
2. ✅ Calcul des statistiques : taux de présence, cohésion, etc.
3. ✅ Graphique : "Historique des votes" (Vue.js)
4. ✅ Graphique : "Qui vote avec qui ?" (graphe de proximité)

### **PHASE 4 : Features avancées (optionnel)**

1. Import des `reunion/*.json` (présences, interventions)
2. Import des `deport/*.json` (conflits d'intérêt)
3. Scraping des documents PDF (rapports, amendements)
4. Machine Learning : prédiction de vote

---

## ❓ **QUESTIONS POUR TOI**

1. **Veut-on remplacer `deputes_senateurs` par `acteurs_an` ?**
   - ✅ Avantage : Données officielles + à jour
   - ⚠️ Inconvénient : Migration complexe

2. **Quel scope de législatures ?**
   - Option A : Uniquement législature 17 (2024-2029)
   - Option B : Toutes les législatures (historique complet)

3. **Priorité immédiate ?**
   - Option A : Import acteurs + organes (remplacement des anciennes données)
   - Option B : Import scrutins (nouvelle fonctionnalité)
   - Option C : Les deux en parallèle

4. **Niveau de détail des votes ?**
   - Option A : Synthèse uniquement (pour/contre/abstention)
   - Option B : Vote nominatif complet + analyse de cohésion

---

## 🎯 **MA RECOMMANDATION**

### **Approche progressive :**

1. **AUJOURD'HUI :** Import acteurs + organes (législature 17 uniquement)
2. **ENSUITE :** Import scrutins (100 derniers pour tester)
3. **PUIS :** Visualisation basique (liste des votes d'un député)
4. **ENFIN :** Analyse avancée (graphes, statistiques, alertes)

**Durée estimée : 8-10h de dev + tests**

---

## 🚀 **PRÊT À DÉMARRER ?**

Dis-moi :
1. ✅ Quelle phase lancer en premier ?
2. ✅ Scope de législatures (17 seule ou historique) ?
3. ✅ On garde `deputes_senateurs` ou on bascule vers `acteurs_an` ?

**Let's go ! 💪**

