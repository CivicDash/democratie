# 🔧 CORRECTIONS & AMÉLIORATIONS - 20 Nov 2025

## ✅ **1. MENU MOBILE MIS À JOUR**

**Fichier :** `resources/js/Layouts/AuthenticatedLayout.vue`

### Changements appliqués :

**Structure finale du menu mobile :**

```
📋 LÉGISLATION
  └─ 🗳️ Scrutins
  └─ 📜 Dossiers législatifs
  └─ 🏷️ Explorer par thème
  └─ 🎨 Groupes

🏛️ PARLEMENT
  └─ 📍 Mes Représentants
  └─ 👥 Députés
  └─ 🏰 Sénateurs
  └─ 🗺️ Par région
  └─ ⚖️ AN vs Sénat

🗨️ DÉBAT CITOYEN
  └─ 💬 Topics
  └─ 🔥 Tendances
  └─ ➕ Créer

🔧 AUTRES
  └─ 💰 Budget Participatif
  └─ 📊 Statistiques France
  └─ 📄 Documents
```

**Correspondance avec le menu desktop :** ✅ Parfait alignement

---

## 🔍 **2. DIAGNOSTIC AMENDEMENTS DÉPUTÉS**

### Problème rapporté :
- ✅ Votes affichés correctement
- ❌ Amendements = 0
- ❌ Taux d'adoption manquant

### Analyse du code :

**Contrôleur** (`RepresentantANController::deputeAmendements`) : ✅ **CODE CORRECT**
```php
// Ligne 308-320
$statsQuery = AmendementAN::where('auteur_acteur_ref', $uid);
$total = $statsQuery->count();
$adoptes = $statsQuery->clone()->adoptes()->count(); // etat_code = 'ADO'
$rejetes = $statsQuery->clone()->rejetes()->count(); // etat_code = 'REJ'
$retires = $statsQuery->clone()->retires()->count(); // etat_code = 'RET'
$taux_adoption = $total > 0 ? round(($adoptes / $total) * 100, 1) : 0;
```

**Modèle** (`AmendementAN`) : ✅ **SCOPES CORRECTS**
```php
scopeAdoptes() -> etat_code = 'ADO'
scopeRejetes() -> etat_code = 'REJ'
scopeRetires() -> etat_code = 'RET'
```

**Frontend** (`Deputes/Amendements.vue`) : ✅ **AFFICHAGE CORRECT**
- Affiche `statistiques.total`, `statistiques.adoptes`, `statistiques.taux_adoption`

### 🔴 **CAUSE PROBABLE DU PROBLÈME**

**L'import des amendements n'a probablement pas été exécuté ou a échoué.**

#### Vérifications à faire sur le serveur :

```bash
# 1. Vérifier si des amendements existent
php artisan tinker
App\Models\AmendementAN::count();
# Devrait retourner > 0

# 2. Vérifier un député spécifique (ex: PA720552 - Jordan Bardella)
App\Models\AmendementAN::where('auteur_acteur_ref', 'PA720552')->count();

# 3. Vérifier les etat_code
App\Models\AmendementAN::select('etat_code', DB::raw('count(*) as count'))
  ->groupBy('etat_code')
  ->get();
```

#### Solutions :

**Option A : Réimporter les amendements** ✅ **RECOMMANDÉ**

```bash
# Dans /opt/civicdash/
php artisan import:amendements-an --legislature=17 --fresh
```

**Option B : Vérifier les données source**

```bash
# Compter les fichiers JSON d'amendements
find public/data/amendements -name "*.json" | wc -l
# Devrait retourner > 0
```

**Option C : Vérifier les logs d'import**

```bash
# Relancer l'import avec verbosité
php artisan import:amendements-an --legislature=17 --limit=10
# Observer les erreurs éventuelles
```

---

## 📊 **3. TAUX D'ADOPTION : AUCUN RECALCUL NÉCESSAIRE**

Le taux d'adoption est **calculé dynamiquement** à chaque chargement de page :

```php
'taux_adoption' => $total > 0 ? round(($adoptes / $total) * 100, 1) : 0
```

**Si le taux est à 0, c'est parce que :**
1. Soit `$total = 0` (aucun amendement)
2. Soit `$adoptes = 0` (aucun amendement avec `etat_code = 'ADO'`)

**Donc : PAS besoin de recalcul, juste réimporter les amendements.**

---

## 🏰 **4. ADAPTATION OPTION C : SCRUTINS SÉNAT (data.senat.fr)**

### Changement de stratégie :

❌ ~~NosSénateurs.fr~~ (service arrêté)  
✅ **data.senat.fr** (Open Data officiel du Sénat)

### Sources disponibles sur data.senat.fr :

D'après [data.senat.fr](https://data.senat.fr), voici les données exploitables :

#### 📊 **Données sénateurs** (JSON REST) :
- ✅ `ODSEN_GENERAL.json` : Profils sénateurs *(déjà importé)*
- ✅ `ODSEN_ELUVIL.json` : Mandats municipaux
- ✅ `ODSEN_ELUMET.json` : Mandats départementaux
- ✅ `ODSEN_ELUDEP.json` : Anciens députés
- ✅ `ODSEN_ELUEUR.json` : Mandats européens
- ✅ `ODSEN_ETUDES.json` : Formations
- ✅ `ODSEN_HISTOGROUPES.json` : Historique groupes
- ✅ `ODSEN_COMS.json` : Commissions

#### 📜 **Données législatives** :

**Format CSV :**
- ✅ `dossiers-legislatifs.csv` : Liste complète des dossiers
- ✅ `promulguees.csv` : Lois promulguées

**Format XML (AkomaNtoso) :**
- ✅ `depots.xml` : Textes déposés
- ✅ `adoptions.xml` : Textes adoptés

**Format PostgreSQL (dump complet) :**
- ✅ Base `AMELI` : Tous les amendements Sénat
- ✅ Base `DOSLEG` : Dossiers législatifs complets
- ✅ Base `Questions` : Questions écrites/orales
- ✅ Base `Comptes rendus` : Débats en séance

### ❌ **PROBLÈME : PAS DE SCRUTINS DÉTAILLÉS**

Le Sénat **ne publie PAS** de scrutins avec votes individuels en Open Data :
- Pas d'API REST pour les scrutins
- Pas de fichiers JSON/CSV
- Seule solution : **Scraping du site web** (complexe et fragile)

### 💡 **NOUVELLE APPROCHE RECOMMANDÉE**

#### **Option A : Amendements Sénat (via base AMELI)**

**Avantages :**
- ✅ Données complètes depuis 2001 (séance) et 2010 (commission)
- ✅ Format PostgreSQL (facile à importer)
- ✅ Auteurs, contenu, votes (adopté/rejeté)

**Implémentation :**
1. Télécharger le dump PostgreSQL AMELI
2. Créer une commande d'import Laravel
3. Afficher les amendements sur les fiches sénateurs
4. Créer une page `/representants/senateurs/{matricule}/amendements`

#### **Option B : Dossiers législatifs bicaméraux (CSV)**

**Avantages :**
- ✅ Données complètes depuis 1977
- ✅ Format CSV simple
- ✅ Permet de comparer AN ↔ Sénat

**Implémentation :**
1. Télécharger `dossiers-legislatifs.csv`
2. Créer migration + modèle `DossierLegislatifSenat`
3. Lier avec `DossierLegislatifAN` par numéro/titre
4. Page détaillée avec timeline AN + Sénat

#### **Option C : Questions au Gouvernement**

**Avantages :**
- ✅ Données complètes depuis 1978
- ✅ Format PostgreSQL
- ✅ Questions écrites + orales

**Implémentation :**
1. Télécharger le dump PostgreSQL Questions
2. Afficher sur les fiches sénateurs
3. Page `/representants/senateurs/{matricule}/questions`

---

## 🎯 **PLAN D'ACTION RÉVISÉ**

### **Immédiat (à faire sur le serveur) :**

1. ✅ Menu mobile mis à jour
2. 🔧 **Réimporter les amendements AN** :
   ```bash
   php artisan import:amendements-an --legislature=17 --fresh
   ```
3. ✅ Tester la page `/representants/deputes/{uid}/amendements`

### **Option C révisée : Amendements Sénat (au lieu des scrutins) :**

1. Télécharger dump PostgreSQL AMELI
2. Créer migration `amendements_senat`
3. Créer modèle `AmendementSenat`
4. Créer commande `import:amendements-senat`
5. Créer page `/representants/senateurs/{matricule}/amendements`

### **Option D : Dossiers bicaméraux :**

1. Télécharger `dossiers-legislatifs.csv`
2. Importer dans `dossiers_legislatifs_senat`
3. Lier avec `dossiers_legislatifs_an`
4. Page détaillée avec timeline complète

---

## 📝 **RÉSUMÉ DES ACTIONS**

| Action | Status | Priorité |
|--------|--------|----------|
| Menu mobile mis à jour | ✅ Terminé | P0 |
| Diagnostic amendements députés | ✅ Terminé | P0 |
| Réimporter amendements AN | 🔧 À faire serveur | **P1** |
| Option C : Amendements Sénat | ⏳ Nouveau plan | P2 |
| Option D : Dossiers bicaméraux | ⏳ À faire | P3 |

---

## 🚀 **COMMANDE À EXÉCUTER SUR LE SERVEUR**

```bash
# 1. Pull des dernières modifications
cd /opt/civicdash
git pull

# 2. Réimporter les amendements (si vides)
php artisan import:amendements-an --legislature=17 --fresh

# 3. Compiler le frontend
npm run build

# 4. Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

**✅ Menu mobile : FAIT**  
**🔧 Amendements : À tester après réimport**  
**📊 Taux d'adoption : Automatique (pas de recalcul)**  
**🏰 Sénat : Nouvelle stratégie basée sur data.senat.fr**

