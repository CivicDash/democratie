# 🎯 GUIDE COMPLET - Recherche de Représentants par Code Postal

## 📋 Vue d'ensemble

Système complet permettant à un citoyen de **trouver tous ses représentants** (Maire, Député, Sénateur) en saisissant simplement son **code postal** ou le **nom de sa ville**.

---

## 🏗️ Architecture

### 1. **Tables de données**

| Table | Lignes | Description |
|-------|--------|-------------|
| `french_postal_codes` | ~39,000 | Codes postaux avec code INSEE, circonscription, département |
| `deputes_senateurs` | 923 | 575 députés + 348 sénateurs |
| `maires` | 34,867 | Tous les maires de France |

### 2. **Relations**

```
Code Postal (75001)
    ├─> Code INSEE (75101)
    │     └─> Maire (WHERE code_commune = '75101')
    │
    ├─> Circonscription (75-01)
    │     └─> Député (WHERE circonscription = '75-01')
    │
    └─> Département (75)
          └─> Sénateurs (WHERE code_departement = '75')
```

---

## 🚀 Étape 1 : Importer les données

### A. **Codes postaux** (PRIORITAIRE)

```bash
cd /opt/civicdash

# Appliquer la migration de correction
docker compose exec app php artisan migrate --force

# Importer les codes postaux
bash scripts/fix_postal_codes.sh
# OU manuellement :
docker compose exec app php artisan postal-codes:import-local --fresh
```

**Résultat attendu :** ~39,000 codes postaux importés

### B. **Députés et Sénateurs**

```bash
bash scripts/import_representants.sh
# OU manuellement :
docker compose exec app php artisan import:deputes --fresh
docker compose exec app php artisan import:senateurs --fresh
```

**Résultat attendu :** 
- 575 députés
- 348 sénateurs

### C. **Maires** (optionnel mais recommandé)

```bash
bash scripts/import_maires.sh
```

**Choix proposé :**
1. Import COMPLET (~35k maires, ~10 min)
2. Import TEST (100 maires, rapide)

**Résultat attendu :** 34,867 maires

---

## 🔍 Étape 2 : Utiliser l'API de recherche

### **Endpoint principal**

```
GET /api/representants/search
```

### **Paramètres**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `q` | string | Code postal (5 chiffres) ou nom de ville | `75001`, `Paris` |
| `postal_code` | string | Code postal (5 chiffres) | `75001` |
| `insee_code` | string | Code INSEE de la commune | `75101` |

### **Exemples d'utilisation**

#### 1. Recherche par code postal

```bash
curl "https://demo.objectif2027.fr/api/representants/search?q=75001"
```

**Réponse :**
```json
{
  "commune": {
    "insee_code": "75101",
    "nom": "PARIS 1ER ARRONDISSEMENT",
    "code_postal": "75001",
    "departement": {
      "code": "75",
      "nom": "Paris"
    },
    "circonscription": "75-01"
  },
  "representants": {
    "maire": {
      "id": 1234,
      "nom_complet": "Mme Anne HIDALGO",
      "commune": {
        "code": "75056",
        "nom": "Paris",
        "population": 2165423
      },
      ...
    },
    "depute": {
      "id": 567,
      "nom_complet": "M. Pierre DUPONT",
      "circonscription": "75-01",
      "groupe_politique": "Renaissance",
      ...
    },
    "senateurs": [
      {
        "id": 890,
        "nom_complet": "Mme Marie MARTIN",
        "circonscription": "75 - Paris",
        ...
      },
      ...
    ]
  },
  "stats": {
    "total_representants": 4,
    "has_maire": true,
    "has_depute": true,
    "nb_senateurs": 2
  }
}
```

#### 2. Recherche par nom de ville

```bash
curl "https://demo.objectif2027.fr/api/representants/search?q=Lyon"
```

**Si plusieurs communes :**
```json
{
  "multiple_results": true,
  "communes": [
    {
      "insee_code": "69381",
      "city_name": "LYON",
      "postal_code": "69001",
      "department_name": "Rhône"
    },
    {
      "insee_code": "69382",
      "city_name": "LYON",
      "postal_code": "69002",
      "department_name": "Rhône"
    },
    ...
  ],
  "message": "Plusieurs communes trouvées. Veuillez sélectionner une commune."
}
```

#### 3. Recherche par code INSEE (le plus précis)

```bash
curl "https://demo.objectif2027.fr/api/representants/search?insee_code=75101"
```

---

## 📊 Vérifications

### 1. **Vérifier les codes postaux**

```bash
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT COUNT(*) as total, 
       COUNT(DISTINCT postal_code) as codes_uniques
FROM french_postal_codes;
"
```

**Attendu :** ~39,000 lignes

### 2. **Vérifier les députés/sénateurs**

```bash
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT source, COUNT(*) as total 
FROM deputes_senateurs 
GROUP BY source;
"
```

**Attendu :**
```
   source   | total 
-----------+-------
 assemblee |   575
 senat     |   348
```

### 3. **Vérifier les maires**

```bash
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT COUNT(*) as total, COUNT(DISTINCT code_commune) as communes 
FROM maires;
"
```

**Attendu :** ~34,867 maires

### 4. **Test de recherche complète**

```bash
# Recherche d'un code postal
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    fp.postal_code,
    fp.city_name,
    fp.circonscription,
    m.nom_complet as maire,
    d.nom_complet as depute,
    COUNT(s.id) as nb_senateurs
FROM french_postal_codes fp
LEFT JOIN maires m ON m.code_commune = fp.insee_code
LEFT JOIN deputes_senateurs d ON d.source = 'assemblee' AND d.circonscription = fp.circonscription
LEFT JOIN deputes_senateurs s ON s.source = 'senat' AND s.code_departement = fp.department_code
WHERE fp.postal_code = '75001'
GROUP BY fp.postal_code, fp.city_name, fp.circonscription, m.nom_complet, d.nom_complet;
"
```

---

## 🎯 Cas d'usage front-end

### Formulaire de recherche

```vue
<template>
  <div class="search-representants">
    <input 
      v-model="searchQuery" 
      @input="searchRepresentants"
      placeholder="Entrez votre code postal ou ville..."
    />
    
    <!-- Si plusieurs communes -->
    <div v-if="multipleCommunesFound">
      <h3>Plusieurs communes trouvées :</h3>
      <button 
        v-for="commune in communes" 
        :key="commune.insee_code"
        @click="selectCommune(commune.insee_code)"
      >
        {{ commune.city_name }} ({{ commune.postal_code }})
      </button>
    </div>
    
    <!-- Résultats -->
    <div v-if="representants">
      <div class="maire" v-if="representants.maire">
        <h3>Votre Maire</h3>
        <p>{{ representants.maire.nom_complet }}</p>
        <p>{{ representants.maire.commune.nom }}</p>
      </div>
      
      <div class="depute" v-if="representants.depute">
        <h3>Votre Député</h3>
        <p>{{ representants.depute.nom_complet }}</p>
        <p>Circonscription {{ representants.depute.circonscription }}</p>
      </div>
      
      <div class="senateurs" v-if="representants.senateurs.length">
        <h3>Vos Sénateurs</h3>
        <div v-for="senateur in representants.senateurs" :key="senateur.id">
          <p>{{ senateur.nom_complet }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const searchQuery = ref('');
const multipleCommunesFound = ref(false);
const communes = ref([]);
const representants = ref(null);

async function searchRepresentants() {
  if (searchQuery.value.length < 2) return;
  
  try {
    const { data } = await axios.get('/api/representants/search', {
      params: { q: searchQuery.value }
    });
    
    if (data.multiple_results || data.multiple_communes) {
      multipleCommunesFound.value = true;
      communes.value = data.communes;
    } else {
      representants.value = data.representants;
    }
  } catch (error) {
    console.error('Erreur recherche:', error);
  }
}

async function selectCommune(inseeCode) {
  const { data } = await axios.get('/api/representants/search', {
    params: { insee_code: inseeCode }
  });
  
  multipleCommunesFound.value = false;
  representants.value = data.representants;
}
</script>
```

---

## 📁 Fichiers créés

| Type | Fichier | Description |
|------|---------|-------------|
| **Migration** | `2025_11_08_141000_create_maires_table.php` | Table maires |
| **Modèle** | `app/Models/Maire.php` | Modèle Eloquent |
| **Commande** | `app/Console/Commands/ImportMairesFromCsv.php` | Import maires |
| **Contrôleur** | `app/Http/Controllers/Api/RepresentantsSearchController.php` | API recherche |
| **Route** | `routes/api.php` | Route `/api/representants/search` |
| **Script** | `scripts/import_maires.sh` | Script automatisé |

---

## 🎉 Résumé des imports

| Étape | Script | Durée | Résultat |
|-------|--------|-------|----------|
| 1️⃣ Codes postaux | `bash scripts/fix_postal_codes.sh` | ~1 min | 39,000 CP |
| 2️⃣ Députés/Sénateurs | `bash scripts/import_representants.sh` | ~30 sec | 923 élus |
| 3️⃣ Maires | `bash scripts/import_maires.sh` | ~10 min | 34,867 maires |

**TOTAL :** ~75,000 enregistrements

---

## ✅ Checklist finale

- [ ] Codes postaux importés (`~39k`)
- [ ] Députés importés (`575`)
- [ ] Sénateurs importés (`348`)
- [ ] Maires importés (`~35k` ou test `100`)
- [ ] API `/api/representants/search` fonctionne
- [ ] Test recherche par code postal
- [ ] Test recherche par ville
- [ ] Front-end intégré (optionnel)

---

**Tout est prêt ! Le système de recherche de représentants est opérationnel. 🚀**

