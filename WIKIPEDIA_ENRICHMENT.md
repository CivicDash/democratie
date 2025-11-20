# 📚 ENRICHISSEMENT WIKIPEDIA - DÉPUTÉS AN

**Auteur :** CivicDash Team  
**Date :** 20 novembre 2025  
**Durée d'implémentation :** 1h30

---

## 🎯 **OBJECTIF**

Enrichir les données des députés de l'Assemblée Nationale avec :
- ✅ **URL Wikipedia** (lien vers leur page biographique)
- ✅ **Photo Wikipedia** (image haute qualité)
- ✅ **Extrait Wikipedia** (résumé biographique)

---

## 🏗️ **ARCHITECTURE**

### **1. Migration**
```
database/migrations/2025_11_20_091128_add_wikipedia_fields_to_acteurs_an_table.php
```

**Colonnes ajoutées :**
- `wikipedia_url` (VARCHAR 500, nullable)
- `photo_wikipedia_url` (VARCHAR 500, nullable)
- `wikipedia_extract` (TEXT, nullable)
- `wikipedia_last_sync` (TIMESTAMP, nullable)

### **2. Service**
```
app/Services/WikipediaService.php
```

**Méthodes principales :**
- `parseDeputesL17()` - Parse le tableau Wikipedia L17 (577 députés)
- `getPageSummary($pageTitle)` - Appelle l'API MediaWiki REST
- `matchActeur($acteur, $deputesWikipedia)` - Matching intelligent (Levenshtein)
- `enrichActeur($acteur, $deputesWikipedia)` - Enrichissement complet

### **3. Commande Artisan**
```
app/Console/Commands/ImportDeputesWikipedia.php
```

**Signature :**
```bash
php artisan import:deputes-wikipedia
    [--legislature=17]     # Législature (défaut: 17)
    [--limit=N]            # Limiter à N députés (tests)
    [--force]              # Forcer la mise à jour même si déjà sync
    [--dry-run]            # Mode simulation (pas d'écriture en base)
```

### **4. Script Shell**
```
scripts/import_wikipedia_deputes.sh
```

**Modes disponibles :**
1. 🧪 TEST (--limit=10 --dry-run)
2. 🔍 SIMULATION COMPLÈTE (--dry-run)
3. ✅ IMPORT COMPLET
4. 🔄 RÉIMPORT FORCÉ
5. 🎯 IMPORT LIMITÉ (personnalisé)

---

## 🔧 **STRATÉGIE D'IMPLÉMENTATION**

### **Mode Hybride (Parsing + API)**

#### **Étape 1 : Parsing HTML**
1. Récupérer la page Wikipedia L17 :
   ```
   https://fr.wikipedia.org/wiki/Liste_des_députés_de_la_XVIIe_législature_de_la_Cinquième_République
   ```

2. Parser le tableau HTML pour extraire :
   - Nom complet du député
   - Lien vers sa page Wikipedia (`/wiki/Marine_Le_Pen`)
   - Titre de la page

3. **Regex utilisée :**
   ```regex
   /<tr[^>]*>.*?<td[^>]*>.*?<a href="(\/wiki\/[^"]+)"[^>]*title="([^"]*)"[^>]*>([^<]+)<\/a>.*?<\/tr>/si
   ```

#### **Étape 2 : API MediaWiki**
1. Pour chaque député matché, appeler l'API REST :
   ```
   GET https://fr.wikipedia.org/api/rest_v1/page/summary/{page_title}
   ```

2. Récupérer :
   - `extract` : Résumé biographique (premier paragraphe)
   - `thumbnail.source` : URL de la photo principale
   - `content_urls.desktop.page` : URL canonique de la page

#### **Étape 3 : Matching**
1. **Normalisation** des noms (suppression accents, minuscules)
2. **Calcul de similarité** avec Levenshtein
3. **Seuil d'acceptation** : 80% de similarité minimum
4. **Stockage** en base avec `wikipedia_last_sync`

---

## 📊 **EXEMPLE DE FLUX**

### **1. Input : Acteur AN**
```php
[
  'uid' => 'PA1008',
  'prenom' => 'Alain',
  'nom' => 'David',
]
```

### **2. Parsing Wikipedia → Match trouvé**
```php
[
  'nom_complet' => 'Alain David',
  'wikipedia_path' => '/wiki/Alain_David_(homme_politique)',
  'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Alain_David_(homme_politique)',
  'similarity_score' => 1.0,
]
```

### **3. API MediaWiki → Données enrichies**
```php
[
  'title' => 'Alain David (homme politique)',
  'extract' => 'Alain David, né le 2 juin 1949 à Brest, est un homme politique français...',
  'thumbnail' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/Alain_David_2017.jpg/220px-Alain_David_2017.jpg',
  'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Alain_David_(homme_politique)',
]
```

### **4. Output : Acteur AN enrichi**
```php
ActeurAN::update([
  'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Alain_David_(homme_politique)',
  'photo_wikipedia_url' => 'https://upload.wikimedia.org/.../Alain_David_2017.jpg',
  'wikipedia_extract' => 'Alain David, né le 2 juin 1949...',
  'wikipedia_last_sync' => '2025-11-20 10:30:00',
]);
```

---

## 🚀 **UTILISATION**

### **Test rapide (10 députés)**
```bash
bash scripts/import_wikipedia_deputes.sh
# Choisir : 1) TEST
```

### **Simulation complète (sans écriture)**
```bash
docker compose exec app php artisan import:deputes-wikipedia --dry-run
```

### **Import complet (production)**
```bash
bash scripts/import_wikipedia_deputes.sh
# Choisir : 3) IMPORT COMPLET
```

### **Réimport forcé (mise à jour)**
```bash
docker compose exec app php artisan import:deputes-wikipedia --force
```

---

## 📈 **STATISTIQUES ATTENDUES**

| Métrique | Valeur estimée | Pourcentage |
|----------|----------------|-------------|
| Total députés L17 | 577 | 100% |
| Matchés | ~550 | ~95% |
| Avec photo | ~500 | ~87% |
| Non matchés | ~27 | ~5% |

**Raisons de non-match :**
- Députés très récents (pas encore de page Wikipedia)
- Homonymes difficiles à différencier
- Noms complexes (particules, tirets)

---

## 🔗 **INTÉGRATION API**

### **Endpoint : GET /api/v1/acteurs/{uid}**

**Réponse enrichie :**
```json
{
  "data": {
    "uid": "PA1008",
    "nom_complet": "M. Alain David",
    "profession": "Ingénieur",
    "wikipedia_url": "https://fr.wikipedia.org/wiki/Alain_David_(homme_politique)",
    "photo_wikipedia_url": "https://upload.wikimedia.org/.../Alain_David_2017.jpg",
    "wikipedia_extract": "Alain David, né le 2 juin 1949 à Brest...",
    "wikipedia_last_sync": "2025-11-20 10:30:00"
  },
  "groupe_actuel": {...},
  "commissions_actuelles": [...],
  "wikipedia": {
    "url": "https://fr.wikipedia.org/wiki/Alain_David_(homme_politique)",
    "photo_url": "https://upload.wikimedia.org/.../Alain_David_2017.jpg",
    "extract": "Alain David, né le 2 juin 1949...",
    "last_sync": "2025-11-20 10:30:00"
  }
}
```

---

## ⚙️ **CONFIGURATION**

### **Rate Limiting**
- **Wikipedia API** : 200 req/s max (largement suffisant)
- **Délai entre requêtes** : 100ms (configurable)
- **Timeout** : 10s par requête

### **User-Agent**
```
CivicDash/1.0 (https://demo.objectif2027.fr)
```

---

## 🛠️ **MAINTENANCE**

### **Réimport mensuel recommandé**
```bash
# Cron job
0 3 1 * * cd /opt/civicdash && docker compose exec app php artisan import:deputes-wikipedia --force
```

**Raison :** Mise à jour des photos, biographies, nouveaux députés

### **Vérification des données**
```sql
-- Compter les députés avec données Wikipedia
SELECT 
  COUNT(*) as total,
  COUNT(wikipedia_url) as avec_wikipedia,
  COUNT(photo_wikipedia_url) as avec_photo,
  ROUND(COUNT(wikipedia_url) * 100.0 / COUNT(*), 2) as taux_match
FROM acteurs_an;

-- Dernière synchronisation
SELECT MAX(wikipedia_last_sync) FROM acteurs_an;

-- Députés sans match
SELECT uid, nom, prenom 
FROM acteurs_an 
WHERE wikipedia_url IS NULL
ORDER BY nom;
```

---

## 📚 **SOURCES & RÉFÉRENCES**

- **Wikipedia L17** : https://fr.wikipedia.org/wiki/Liste_des_députés_de_la_XVIIe_législature_de_la_Cinquième_République
- **API MediaWiki REST** : https://fr.wikipedia.org/api/rest_v1/
- **Documentation API** : https://www.mediawiki.org/wiki/API:REST_API

---

## 🎯 **PROCHAINES ÉVOLUTIONS**

- [ ] Extension aux **sénateurs** (même principe)
- [ ] Détection automatique des **nouveaux députés** (webhooks Wikipedia)
- [ ] Récupération des **galeries photos** (pas seulement la photo principale)
- [ ] Extraction des **dates clés** (élections, mandats précédents)
- [ ] Cache Redis pour limiter les appels API

---

## ✅ **LIVRABLE**

| Fichier | Type | Description |
|---------|------|-------------|
| `2025_11_20_091128_add_wikipedia_fields_to_acteurs_an_table.php` | Migration | Ajout colonnes Wikipedia |
| `WikipediaService.php` | Service | Parsing + API MediaWiki |
| `ImportDeputesWikipedia.php` | Command | Commande Artisan d'import |
| `import_wikipedia_deputes.sh` | Script | Orchestration shell interactive |
| `ActeurAN.php` | Model | Ajout fillable + casts |
| `ActeursANController.php` | Controller | Exposition données Wikipedia |
| `WIKIPEDIA_ENRICHMENT.md` | Doc | Cette documentation |

**Total : 7 fichiers créés/modifiés**

---

**🎊 Fonctionnalité prête à l'emploi ! 🎊**

