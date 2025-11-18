# 📚 API DOCUMENTATION - CivicDash v1

**Base URL :** `https://demo.objectif2027.fr/api/v1`  
**Format :** JSON  
**Authentification :** Aucune (routes publiques)

---

## 🏛️ **ACTEURS AN (Députés)**

### **GET /v1/acteurs**

Liste des acteurs avec filtres et pagination.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `nom` | string | Filtrer par nom (ILIKE) | `?nom=David` |
| `prenom` | string | Filtrer par prénom (ILIKE) | `?prenom=Alain` |
| `search` | string | Recherche dans nom OU prénom | `?search=David` |
| `deputes_only` | boolean | Uniquement les députés actifs | `?deputes_only=true` |
| `with_mandats` | boolean | Inclure les mandats | `?with_mandats=true` |
| `with_groupe` | boolean | Inclure le groupe politique | `?with_groupe=true` |
| `per_page` | integer | Résultats par page (max 100) | `?per_page=20` |
| `page` | integer | Numéro de page | `?page=2` |

**Exemple de requête :**
```bash
GET /api/v1/acteurs?search=David&deputes_only=true&with_mandats=true&per_page=10
```

**Réponse (200 OK) :**
```json
{
  "current_page": 1,
  "data": [
    {
      "uid": "PA1008",
      "civilite": "M.",
      "prenom": "Alain",
      "nom": "David",
      "trigramme": "ADA",
      "date_naissance": "1949-06-02",
      "profession": "Ingénieur",
      "mandats": [
        {
          "uid": "PM842621",
          "type_organe": "ASSEMBLEE",
          "date_debut": "2024-07-07",
          "date_fin": null,
          "organe": {
            "uid": "PO838901",
            "libelle": "Assemblée nationale de la 17ème législature"
          }
        }
      ]
    }
  ],
  "per_page": 10,
  "total": 603
}
```

---

### **GET /v1/acteurs/{uid}**

Détails complets d'un acteur.

**Paramètres :**
- `{uid}` : UID de l'acteur (ex: `PA1008`)

**Exemple de requête :**
```bash
GET /api/v1/acteurs/PA1008
```

**Réponse (200 OK) :**
```json
{
  "data": {
    "uid": "PA1008",
    "civilite": "M.",
    "prenom": "Alain",
    "nom": "David",
    "trigramme": "ADA",
    "date_naissance": "1949-06-02",
    "profession": "Ingénieur",
    "url_hatvp": "https://www.hatvp.fr/pages_nominatives/david-alain",
    "adresses": [
      {
        "type": "Adresse officielle",
        "intitule": "Assemblée nationale",
        "numero_rue": "126",
        "nom_rue": "Rue de l'Université",
        "code_postal": "75355",
        "ville": "Paris 07 SP"
      }
    ],
    "mandats": [...]
  },
  "groupe_actuel": {
    "uid": "PO845419",
    "libelle": "Socialistes et apparentés",
    "libelle_abrege": "SOC"
  },
  "commissions_actuelles": [
    {
      "uid": "PO59047",
      "libelle": "Commission des affaires économiques"
    }
  ]
}
```

---

### **GET /v1/acteurs/{uid}/votes**

Historique des votes d'un acteur.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `legislature` | integer | Filtrer par législature | `?legislature=17` |
| `position` | string | Filtrer par position (pour, contre, abstention, non_votant) | `?position=pour` |
| `date_min` | date | Date minimale (YYYY-MM-DD) | `?date_min=2024-01-01` |
| `date_max` | date | Date maximale (YYYY-MM-DD) | `?date_max=2024-12-31` |
| `per_page` | integer | Résultats par page (max 100) | `?per_page=20` |

**Exemple de requête :**
```bash
GET /api/v1/acteurs/PA1008/votes?legislature=17&per_page=20
```

**Réponse (200 OK) :**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 12345,
        "scrutin_ref": "VTANR5L17V1000",
        "position": "pour",
        "position_groupe": "pour",
        "par_delegation": false,
        "scrutin": {
          "uid": "VTANR5L17V1000",
          "numero": 1000,
          "date_scrutin": "2025-03-13",
          "titre": "l'amendement n°29...",
          "resultat_code": "adopté"
        },
        "groupe": {
          "uid": "PO845419",
          "libelle_abrege": "SOC"
        }
      }
    ],
    "per_page": 20,
    "total": 1523
  },
  "stats": {
    "total": 1523,
    "pour": 892,
    "contre": 421,
    "abstention": 156,
    "non_votant": 54
  }
}
```

---

### **GET /v1/acteurs/{uid}/amendements**

Amendements déposés par un acteur.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `legislature` | integer | Filtrer par législature | `?legislature=17` |
| `etat` | string | Filtrer par état (ADO, REJ, etc.) | `?etat=ADO` |
| `adoptes_only` | boolean | Uniquement les amendements adoptés | `?adoptes_only=true` |
| `rejetes_only` | boolean | Uniquement les amendements rejetés | `?rejetes_only=true` |
| `per_page` | integer | Résultats par page (max 100) | `?per_page=20` |

**Exemple de requête :**
```bash
GET /api/v1/acteurs/PA1008/amendements?legislature=17&adoptes_only=true
```

**Réponse (200 OK) :**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "uid": "AMANR5L17PO838901B0689P0D1N000007",
        "numero_long": "N°7",
        "date_depot": "2024-10-15",
        "etat_code": "ADO",
        "etat_libelle": "Adopté",
        "dispositif": "Texte du dispositif...",
        "expose": "Exposé des motifs...",
        "nombre_cosignataires": 12
      }
    ],
    "per_page": 20,
    "total": 45
  },
  "stats": {
    "total": 156,
    "adoptes": 45,
    "rejetes": 89,
    "taux_adoption": 28.85
  }
}
```

---

### **GET /v1/acteurs/{uid}/stats**

Statistiques d'activité d'un acteur.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `legislature` | integer | Législature (défaut: 17) | `?legislature=17` |

**Exemple de requête :**
```bash
GET /api/v1/acteurs/PA1008/stats?legislature=17
```

**Réponse (200 OK) :**
```json
{
  "acteur": {
    "uid": "PA1008",
    "nom_complet": "M. Alain David",
    "groupe_actuel": {
      "uid": "PO845419",
      "libelle_abrege": "SOC"
    }
  },
  "legislature": 17,
  "votes": {
    "total": 1523,
    "pour": 892,
    "contre": 421,
    "abstention": 156,
    "non_votant": 54,
    "taux_participation": 96.45
  },
  "amendements": {
    "total": 156,
    "adoptes": 45,
    "rejetes": 89,
    "taux_adoption": 28.85
  }
}
```

---

## 🗳️ **SCRUTINS AN**

### **GET /v1/scrutins**

Liste des scrutins avec filtres.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `legislature` | integer | Filtrer par législature | `?legislature=17` |
| `date_min` | date | Date minimale | `?date_min=2024-01-01` |
| `date_max` | date | Date maximale | `?date_max=2024-12-31` |
| `resultat` | string | Filtrer par résultat (adopté, rejeté) | `?resultat=adopté` |
| `adoptes_only` | boolean | Uniquement les scrutins adoptés | `?adoptes_only=true` |
| `rejetes_only` | boolean | Uniquement les scrutins rejetés | `?rejetes_only=true` |
| `search` | string | Recherche full-text dans le titre | `?search=climat` |
| `with_organe` | boolean | Inclure l'organe | `?with_organe=true` |
| `sort_by` | string | Tri (date_scrutin, numero) | `?sort_by=date_scrutin` |
| `sort_order` | string | Ordre (asc, desc) | `?sort_order=desc` |
| `per_page` | integer | Résultats par page (max 100) | `?per_page=20` |

**Exemple de requête :**
```bash
GET /api/v1/scrutins?legislature=17&date_min=2024-01-01&adoptes_only=true&per_page=20
```

**Réponse (200 OK) :**
```json
{
  "current_page": 1,
  "data": [
    {
      "uid": "VTANR5L17V1000",
      "numero": 1000,
      "legislature": 17,
      "date_scrutin": "2025-03-13",
      "type_vote_libelle": "scrutin public ordinaire",
      "resultat_code": "adopté",
      "titre": "l'amendement n°29 de Mme Pirès Beaune...",
      "nombre_votants": 41,
      "pour": 26,
      "contre": 14,
      "abstentions": 1
    }
  ],
  "per_page": 20,
  "total": 1523
}
```

---

### **GET /v1/scrutins/{uid}**

Détails d'un scrutin.

**Exemple de requête :**
```bash
GET /api/v1/scrutins/VTANR5L17V1000
```

**Réponse (200 OK) :**
```json
{
  "data": {
    "uid": "VTANR5L17V1000",
    "numero": 1000,
    "legislature": 17,
    "date_scrutin": "2025-03-13",
    "titre": "l'amendement n°29 de Mme Pirès Beaune...",
    "resultat_code": "adopté",
    "nombre_votants": 41,
    "suffrages_exprimes": 40,
    "pour": 26,
    "contre": 14,
    "abstentions": 1,
    "organe": {
      "uid": "PO838901",
      "libelle": "Assemblée nationale de la 17ème législature"
    }
  },
  "stats": {
    "taux_participation": 7.11,
    "taux_pour": 65.0,
    "taux_contre": 35.0,
    "taux_abstention": 2.44
  }
}
```

---

### **GET /v1/scrutins/{uid}/votes**

Votes individuels d'un scrutin.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `position` | string | Filtrer par position | `?position=pour` |
| `groupe` | string | Filtrer par groupe | `?groupe=PO845419` |
| `rebelles_only` | boolean | Uniquement les votes rebelles | `?rebelles_only=true` |
| `per_page` | integer | Résultats par page (max 200) | `?per_page=50` |

**Exemple de requête :**
```bash
GET /api/v1/scrutins/VTANR5L17V1000/votes?rebelles_only=true
```

**Réponse (200 OK) :**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 12345,
      "position": "contre",
      "position_groupe": "pour",
      "acteur": {
        "uid": "PA1008",
        "nom_complet": "M. Alain David"
      },
      "groupe": {
        "uid": "PO845419",
        "libelle_abrege": "SOC"
      }
    }
  ],
  "per_page": 50,
  "total": 8
}
```

---

### **GET /v1/scrutins/{uid}/stats-par-groupe**

Statistiques par groupe politique pour un scrutin.

**Exemple de requête :**
```bash
GET /api/v1/scrutins/VTANR5L17V1000/stats-par-groupe
```

**Réponse (200 OK) :**
```json
{
  "scrutin": {
    "uid": "VTANR5L17V1000",
    "numero": 1000,
    "titre": "l'amendement n°29...",
    "resultat": "adopté"
  },
  "stats_par_groupe": [
    {
      "groupe": {
        "uid": "PO845401",
        "libelle": "Renaissance"
      },
      "total": 16,
      "pour": 16,
      "contre": 0,
      "abstention": 0,
      "non_votant": 0,
      "position_majoritaire": "pour"
    },
    {
      "groupe": {
        "uid": "PO845407",
        "libelle": "Rassemblement National"
      },
      "total": 8,
      "pour": 0,
      "contre": 7,
      "abstention": 0,
      "non_votant": 1,
      "position_majoritaire": "contre"
    }
  ]
}
```

---

## 📝 **AMENDEMENTS AN**

### **GET /v1/amendements**

Liste des amendements avec filtres.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `legislature` | integer | Filtrer par législature | `?legislature=17` |
| `auteur` | string | Filtrer par auteur (UID acteur) | `?auteur=PA1008` |
| `groupe` | string | Filtrer par groupe (UID organe) | `?groupe=PO845419` |
| `etat` | string | Filtrer par état (ADO, REJ, etc.) | `?etat=ADO` |
| `adoptes_only` | boolean | Uniquement les adoptés | `?adoptes_only=true` |
| `rejetes_only` | boolean | Uniquement les rejetés | `?rejetes_only=true` |
| `gouvernement_only` | boolean | Uniquement du gouvernement | `?gouvernement_only=true` |
| `texte` | string | Filtrer par texte législatif | `?texte=PIONANR5L17B0689` |
| `date_min` | date | Date minimale de dépôt | `?date_min=2024-01-01` |
| `date_max` | date | Date maximale de dépôt | `?date_max=2024-12-31` |
| `search` | string | Recherche full-text (dispositif + exposé) | `?search=transition écologique` |
| `with_auteur` | boolean | Inclure l'auteur | `?with_auteur=true` |
| `with_groupe` | boolean | Inclure le groupe | `?with_groupe=true` |
| `with_texte` | boolean | Inclure le texte législatif | `?with_texte=true` |
| `sort_by` | string | Tri (date_depot, etc.) | `?sort_by=date_depot` |
| `sort_order` | string | Ordre (asc, desc) | `?sort_order=desc` |
| `per_page` | integer | Résultats par page (max 100) | `?per_page=20` |

**Exemple de requête :**
```bash
GET /api/v1/amendements?legislature=17&search=climat&adoptes_only=true&per_page=20
```

**Réponse (200 OK) :**
```json
{
  "current_page": 1,
  "data": [
    {
      "uid": "AMANR5L17PO838901B0689P0D1N000007",
      "legislature": 17,
      "numero_long": "N°7",
      "date_depot": "2024-10-15",
      "etat_code": "ADO",
      "etat_libelle": "Adopté",
      "auteur_type": "Député",
      "dispositif": "Texte du dispositif...",
      "expose": "Exposé des motifs...",
      "nombre_cosignataires": 12
    }
  ],
  "per_page": 20,
  "total": 234
}
```

---

### **GET /v1/amendements/stats**

Statistiques générales des amendements.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `legislature` | integer | Législature (défaut: 17) | `?legislature=17` |

**Exemple de requête :**
```bash
GET /api/v1/amendements/stats?legislature=17
```

**Réponse (200 OK) :**
```json
{
  "legislature": 17,
  "stats": {
    "total": 68539,
    "adoptes": 12456,
    "rejetes": 45123,
    "gouvernement": 2341,
    "taux_adoption": 18.17
  },
  "top_auteurs": [
    {
      "acteur": {
        "uid": "PA1008",
        "nom_complet": "M. Alain David"
      },
      "total": 234
    }
  ]
}
```

---

### **GET /v1/amendements/{uid}**

Détails d'un amendement.

**Exemple de requête :**
```bash
GET /api/v1/amendements/AMANR5L17PO838901B0689P0D1N000007
```

**Réponse (200 OK) :**
```json
{
  "data": {
    "uid": "AMANR5L17PO838901B0689P0D1N000007",
    "legislature": 17,
    "numero_long": "N°7",
    "date_depot": "2024-10-15",
    "etat_code": "ADO",
    "etat_libelle": "Adopté",
    "auteur_type": "Député",
    "auteur_acteur": {
      "uid": "PA1008",
      "nom_complet": "M. Alain David"
    },
    "auteur_groupe": {
      "uid": "PO845419",
      "libelle_abrege": "SOC"
    },
    "dispositif": "Texte complet du dispositif...",
    "expose": "Exposé complet des motifs...",
    "cosignataires_acteur_refs": ["PA1327", "PA1567"],
    "nombre_cosignataires": 12,
    "texte_legislatif": {
      "uid": "PIONANR5L17B0689",
      "titre": "Proposition de loi...",
      "dossier": {
        "uid": "DLR5L17N51035",
        "titre": "Dossier législatif..."
      }
    }
  },
  "statut": {
    "est_adopte": true,
    "est_rejete": false,
    "est_irrecevable": false
  },
  "cosignataires_count": 12
}
```

---

## 🏛️ **SÉNATEURS**

### **GET /v1/senateurs**

Liste des sénateurs avec filtres.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `nom` | string | Filtrer par nom | `?nom=Ziane` |
| `prenom` | string | Filtrer par prénom | `?prenom=Adel` |
| `search` | string | Recherche dans nom OU prénom | `?search=Ziane` |
| `etat` | string | Filtrer par état (ACTIF, ANCIEN) | `?etat=actif` |
| `actifs_only` | boolean | Uniquement les sénateurs actifs | `?actifs_only=true` |
| `circonscription` | string | Filtrer par circonscription | `?circonscription=Paris` |
| `groupe` | string | Filtrer par groupe politique | `?groupe=SER` |
| `with_commissions` | boolean | Inclure les commissions | `?with_commissions=true` |
| `with_mandats` | boolean | Inclure les mandats | `?with_mandats=true` |
| `with_groupes` | boolean | Inclure l'historique des groupes | `?with_groupes=true` |
| `sort_by` | string | Tri (nom_usuel, prenom_usuel) | `?sort_by=nom_usuel` |
| `sort_order` | string | Ordre (asc, desc) | `?sort_order=asc` |
| `per_page` | integer | Résultats par page (max 100) | `?per_page=15` |

**Exemple de requête :**
```bash
GET /api/v1/senateurs?actifs_only=true&circonscription=Paris&per_page=10
```

**Réponse (200 OK) :**
```json
{
  "current_page": 1,
  "data": [
    {
      "matricule": "21077M",
      "civilite": "M.",
      "nom_usuel": "Ziane",
      "prenom_usuel": "Adel",
      "etat": "ACTIF",
      "date_naissance": "1979-04-05",
      "groupe_politique": "SER",
      "commission_permanente": "commission de la culture",
      "circonscription": "Seine-Saint-Denis",
      "email": "a.ziane@senat.fr"
    }
  ],
  "per_page": 10,
  "total": 350
}
```

---

### **GET /v1/senateurs/stats**

Statistiques générales des sénateurs.

**Exemple de requête :**
```bash
GET /api/v1/senateurs/stats
```

**Réponse (200 OK) :**
```json
{
  "stats": {
    "total": 2000,
    "actifs": 350,
    "anciens": 1650
  },
  "par_groupe": [
    {
      "groupe": "Les Républicains",
      "total": 145
    },
    {
      "groupe": "SER",
      "total": 69
    }
  ]
}
```

---

### **GET /v1/senateurs/{matricule}**

Détails d'un sénateur.

**Exemple de requête :**
```bash
GET /api/v1/senateurs/21077M
```

**Réponse (200 OK) :**
```json
{
  "data": {
    "matricule": "21077M",
    "civilite": "M.",
    "nom_usuel": "Ziane",
    "prenom_usuel": "Adel",
    "etat": "ACTIF",
    "date_naissance": "1979-04-05",
    "groupe_politique": "SER",
    "commission_permanente": "commission de la culture",
    "circonscription": "Seine-Saint-Denis",
    "email": "a.ziane@senat.fr",
    "commissions": [...],
    "mandats": [...],
    "historique_groupes": [...]
  },
  "commissions_actuelles": [
    {
      "commission": "commission de la culture",
      "date_debut": "2023-10-01",
      "fonction": "Membre"
    }
  ],
  "mandats_actifs": [
    {
      "type_mandat": "SENATEUR",
      "circonscription": "Seine-Saint-Denis",
      "date_debut": "2023-10-01"
    }
  ]
}
```

---

### **GET /v1/senateurs/{matricule}/mandats**

Mandats d'un sénateur (tous types).

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `type` | string | Filtrer par type (SENATEUR, DEPUTE, MUNICIPAL, etc.) | `?type=senateur` |
| `actifs_only` | boolean | Uniquement les mandats actifs | `?actifs_only=true` |

**Exemple de requête :**
```bash
GET /api/v1/senateurs/21077M/mandats?actifs_only=true
```

**Réponse (200 OK) :**
```json
{
  "data": [
    {
      "type_mandat": "SENATEUR",
      "circonscription": "Seine-Saint-Denis",
      "date_debut": "2023-10-01",
      "date_fin": null,
      "numero_mandat": 1
    }
  ],
  "stats": {
    "total": 3,
    "senateur": 1,
    "depute": 0,
    "municipal": 2,
    "actifs": 1
  }
}
```

---

### **GET /v1/senateurs/{matricule}/commissions**

Commissions d'un sénateur.

**Paramètres de requête :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `actuelles_only` | boolean | Uniquement les commissions actuelles | `?actuelles_only=true` |

**Exemple de requête :**
```bash
GET /api/v1/senateurs/21077M/commissions?actuelles_only=true
```

**Réponse (200 OK) :**
```json
{
  "data": [
    {
      "commission": "commission de la culture",
      "date_debut": "2023-10-01",
      "date_fin": null,
      "fonction": "Membre"
    }
  ],
  "total": 1,
  "actuelles": 1
}
```

---

### **GET /v1/senateurs/{matricule}/groupes**

Historique des groupes politiques d'un sénateur.

**Exemple de requête :**
```bash
GET /api/v1/senateurs/21077M/groupes
```

**Réponse (200 OK) :**
```json
{
  "data": [
    {
      "groupe_politique": "SER",
      "type_appartenance": null,
      "date_debut": "2023-10-01",
      "date_fin": null
    }
  ],
  "groupe_actuel": "SER"
}
```

---

## 🔒 **CODES D'ERREUR**

| Code | Description |
|------|-------------|
| 200 | Succès |
| 404 | Ressource introuvable |
| 422 | Validation échouée (paramètres invalides) |
| 500 | Erreur serveur |

**Exemple d'erreur (404) :**
```json
{
  "message": "No query results for model [App\\Models\\ActeurAN] PA9999"
}
```

---

## 💡 **EXEMPLES D'UTILISATION**

### **JavaScript (Fetch API)**

```javascript
// Rechercher un député
const response = await fetch('https://demo.objectif2027.fr/api/v1/acteurs?search=David&deputes_only=true');
const data = await response.json();
console.log(data.data[0].nom_complet);

// Statistiques d'un député
const stats = await fetch('https://demo.objectif2027.fr/api/v1/acteurs/PA1008/stats?legislature=17');
const statsData = await stats.json();
console.log(`Taux de participation: ${statsData.votes.taux_participation}%`);
```

### **cURL**

```bash
# Liste des scrutins adoptés en 2024
curl "https://demo.objectif2027.fr/api/v1/scrutins?legislature=17&date_min=2024-01-01&adoptes_only=true"

# Amendements adoptés sur le climat
curl "https://demo.objectif2027.fr/api/v1/amendements?legislature=17&search=climat&adoptes_only=true"

# Sénateurs actifs de Paris
curl "https://demo.objectif2027.fr/api/v1/senateurs?actifs_only=true&circonscription=Paris"
```

### **Python (requests)**

```python
import requests

# Récupérer un député
response = requests.get('https://demo.objectif2027.fr/api/v1/acteurs/PA1008')
depute = response.json()['data']
print(f"{depute['nom_complet']} - {depute['profession']}")

# Top 10 auteurs d'amendements
stats = requests.get('https://demo.objectif2027.fr/api/v1/amendements/stats?legislature=17')
top_auteurs = stats.json()['top_auteurs']
for auteur in top_auteurs:
    print(f"{auteur['acteur']['nom_complet']}: {auteur['total']} amendements")
```

---

## 📊 **LIMITES & PERFORMANCES**

- **Pagination :** Maximum 100 résultats par page (200 pour votes individuels)
- **Rate limiting :** Aucune limite actuellement (à venir)
- **Cache :** Aucun cache actuellement (données temps réel)
- **Full-text search :** Utilise PostgreSQL GIN index (performant)

---

## 🚀 **PROCHAINES ÉVOLUTIONS**

- [ ] Authentification API (Sanctum)
- [ ] Rate limiting (100 req/min)
- [ ] Cache Redis (TTL 1h)
- [ ] Webhooks pour nouveaux scrutins
- [ ] Export CSV/PDF
- [ ] GraphQL endpoint

---

**Documentation mise à jour le :** 18 novembre 2025  
**Version API :** v1.0.0  
**Contact :** https://demo.objectif2027.fr

