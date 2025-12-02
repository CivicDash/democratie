# 🗺️ ROADMAP & STATUS - CivicDash / Objectif 2027

**Dernière mise à jour :** 1er décembre 2025 (v2 - après analyse approfondie)

---

## 📊 ÉTAT ACTUEL DU PROJET

### ✅ Fonctionnalités Opérationnelles

#### 1. **Sénateurs** (100% fonctionnel)
- ✅ Liste des sénateurs avec filtres (groupe, département, recherche)
- ✅ Fiches individuelles détaillées
- ✅ Page Amendements avec statistiques (Adoptés/Rejetés/Retirés)
- ✅ Page Activité avec votes et amendements récents
- ✅ Page Votes avec détails des scrutins
- ✅ Statistiques correctes (codes sort: A, RJS, R)

#### 2. **Députés AN** (Partiellement fonctionnel)
- ✅ Liste des députés (via ActeurAN)
- ✅ Fiches individuelles
- ✅ Page Votes avec détails
- ⚠️ Amendements : données à 0 (import non effectué)
- ⚠️ Statistiques activité à 0%

#### 3. **Scrutins**
- ✅ Liste des scrutins AN avec pagination
- ✅ Détail d'un scrutin avec votes pour/contre/abstention
- ⚠️ **BUG CORRIGÉ** : Les totaux (pour/contre/abstentions) étaient à 0
- ✅ Commande `php artisan scrutins:recalculer` créée pour corriger

#### 4. **Comparaison AN / Sénat**
- ✅ Page statistiques comparative
- ✅ Effectifs, âge moyen, parité
- ✅ Top professions
- ✅ Groupes parlementaires

#### 5. **Forum Citoyen (Topics)**
- ✅ Création/lecture de topics
- ✅ Système de posts/réponses
- ✅ Votes citoyens

#### 6. **Budget Participatif**
- ✅ Interface d'allocation
- ✅ Statistiques par secteur

#### 7. **Statistiques France**
- ✅ Carte interactive régions/départements
- ✅ Données démographiques

---

## 🔴 BUGS À CORRIGER

### Priorité Haute

| Bug | Description | Status |
|-----|-------------|--------|
| Scrutins à 0 | Les colonnes pour/contre/abstentions sont à 0 | 🔧 Commande créée, **à exécuter** |
| Amendements députés | Données non importées | ⏳ Import à faire |
| Table `sen_ameli` | Manquante pour lier amendements → sénateurs | 🔧 Migration créée |

### Priorité Moyenne

| Bug | Description | Status |
|-----|-------------|--------|
| Recherche code postal | Députés non trouvés (table `deputes_senateurs` inexistante) | ✅ Contourné |
| Tags vides | Aucun tag en base | ⏳ Seeder à exécuter |
| HTML non décodé | `&#233;` dans les amendements | ⏳ À corriger |

---

## 📋 COMMANDES À EXÉCUTER EN PRODUCTION

```bash
# 1. Recalculer les totaux des scrutins AN
php artisan scrutins:recalculer --legislature=17

# 2. Créer la table sen_ameli et recréer la vue amendements_senat
php artisan migrate

# 3. Seeder les tags (si vide)
php artisan db:seed --class=TagsSeeder
```

---

## 🚀 FEATURES À IMPLÉMENTER

### Phase 1 : Corrections Urgentes (1-2h)

1. **Importer les données `sen_ameli`** pour lier amendements → sénateurs
   - Créer un script d'import depuis le dump SQL
   - Recréer la vue `amendements_senat`

2. **Décoder le HTML** dans les amendements
   - Utiliser `html_entity_decode()` dans le controller ou un accessor

3. **Exécuter `scrutins:recalculer`** pour corriger les totaux

### Phase 2 : Données Députés (4-6h)

1. **Import amendements AN**
   - Trouver une source de données (data.assemblee-nationale.fr)
   - Créer commande d'import
   - Lier aux acteurs

2. **Statistiques activité députés**
   - Calculer depuis votes individuels
   - Afficher dans les fiches

### Phase 3 : Améliorations UX (2-4h)

1. **Page "Mes Représentants"**
   - Afficher les députés (actuellement null car table manquante)
   - Utiliser `ActeurAN` avec données de circonscription

2. **Recherche par code postal**
   - Implémenter la recherche de députés via `ActeurAN`
   - Nécessite données de circonscription dans les mandats

3. **Améliorer l'affichage des amendements**
   - Nettoyer le HTML
   - Afficher le texte de loi concerné
   - Liens vers le site officiel

### Phase 4 : Nouvelles Fonctionnalités (8-16h)

1. **Discipline de groupe**
   - Calculer le taux d'alignement avec le groupe
   - Afficher dans les fiches

2. **Historique des votes**
   - Timeline des votes par parlementaire
   - Comparaison avec le groupe

3. **Alertes personnalisées**
   - Notifications sur les votes de ses représentants
   - Suivi de thématiques

4. **Export de données**
   - PDF des fiches parlementaires
   - CSV des votes

---

## 📁 STRUCTURE DES DONNÉES

### Tables AN (Assemblée Nationale)
- `acteurs_an` : 1000+ députés (historique)
- `mandats_an` : Mandats avec dates
- `organes_an` : Groupes, commissions
- `scrutins_an` : ~4000 scrutins L17
- `votes_individuels_an` : ~300k votes
- `amendements_an` : ⚠️ Vide (à importer)
- `dossiers_legislatifs_an` : Dossiers législatifs
- `textes_legislatifs_an` : Textes de loi

### Tables Sénat
- `senateurs` : ~350 sénateurs actifs
- `scrutins_senat` : Scrutins
- `votes_senat` : Votes individuels
- `amendements_senat` : Vue SQL (~150k amendements)
- `senat_senateurs_sen` : Données brutes sénateurs
- `senat_ameli_*` : Données AMELI (amendements)

### Tables Manquantes
- `sen_ameli` : Table de correspondance entid → matricule
- `deputes_senateurs` : Ancienne table (remplacée par acteurs_an)

---

## 🔗 LIENS UTILES

- **Production** : https://demo.objectif2027.fr
- **API AN** : https://data.assemblee-nationale.fr
- **API Sénat** : https://data.senat.fr
- **Open Data** : https://www.data.gouv.fr

---

## 📝 NOTES TECHNIQUES

### Codes Sort Amendements Sénat
| Code | Libellé | Scope Laravel |
|------|---------|---------------|
| A | Adopté | `adoptes()` |
| AM | Adopté modifié | `adoptes()` |
| AB | Adopté vote unique | `adoptes()` |
| RJS | Rejeté | `rejetes()` |
| RJ | Rejeté | `rejetes()` |
| RJB | Rejeté vote unique | `rejetes()` |
| R | Retiré | `retires()` |
| RET | Retiré | `retires()` |
| S | Tombé | `tombes()` |
| N | Non soutenu | `nonSoutenus()` |
| SO | Satisfait | `satisfaits()` |

### Correspondance Tables
- `senat_ameli_amdsen.senid` = ID numérique (ex: 7577)
- `sen_ameli.entid` = Même ID numérique
- `sen_ameli.mat` = Matricule (ex: "20110Q")
- `senateurs.matricule` = Même matricule

---

*Document généré automatiquement - Session du 1er décembre 2025*

