# 🗺️ ROADMAP CIVICDASH - Features & Développement

## 📊 Vue d'ensemble

**Projet** : CivicDash - Plateforme citoyenne de transparence démocratique  
**Objectif** : Rendre la vie politique française accessible et compréhensible  
**Licence** : AGPL-3.0 Open Source

---

## ✅ RÉALISATIONS ACTUELLES (Décembre 2025)

### 🏛️ Données Parlementaires

#### Assemblée Nationale (Législature 17)
- [x] **Députés** : 577 profils complets avec photos officielles
- [x] **Scrutins publics** : Import et affichage des votes
- [x] **Votes individuels** : Détail par député
- [x] **Amendements** : Textes et auteurs
- [x] **Dossiers législatifs** : Suivi des textes de loi
- [x] **Organes** : Commissions, groupes parlementaires
- [x] **Mandats** : Historique des fonctions

#### Sénat
- [x] **Sénateurs** : 348 profils avec photos officielles senat.fr
- [x] **Scrutins** : Votes publics
- [x] **Votes individuels** : Détail par sénateur
- [x] **Amendements** : Avec auteurs et sort
- [x] **Commissions** : Libellés complets (migration récente)
- [x] **Groupes parlementaires** : Historique avec libellés
- [x] **Mandats locaux** : Fonctions électives

#### HATVP (Haute Autorité pour la Transparence)
- [x] **Déclarations de patrimoine** : Import et affichage
- [x] **Rémunérations** : Graphique par année
- [x] **Mandats électifs** : Avec rémunérations associées
- [x] **Activités professionnelles** : Détails
- [x] **Participations** : Entreprises et fonctions

### 🗺️ Cartographie & Statistiques
- [x] **Carte interactive France** : Départements cliquables
- [x] **Hémicycles** : Visualisation AN et Sénat
- [x] **Statistiques régionales** : Population, densité
- [x] **Codes postaux** : Recherche par localisation
- [x] **Indicateurs INSEE** : Données socio-économiques

### 📱 Interface Utilisateur
- [x] **Responsive mobile** : Navigation adaptée
- [x] **Dark mode** : Thème sombre
- [x] **Fiches parlementaires** : Vue détaillée compacte
- [x] **Tableaux accordéon** : Mandats, commissions
- [x] **Comparateur** : AN vs Sénat

### 🎨 Design System (Décembre 2025)
- [x] **Hero banners unifiés** : Gradient coloré + stats + breadcrumb light
  - Députés : gradient blue/indigo
  - Sénateurs : gradient rose/pink  
  - Lois : gradient slate/indigo
  - Idées citoyennes : gradient emerald/teal
- [x] **Breadcrumb component** : Prop `variant="light"` pour fonds sombres
- [x] **Filtres mobile** : Dropdown accordéon avec compteur
- [x] **Recherche globale** : Suggestions temps réel multi-sources
  - Composant `GlobalSearch.vue`
  - API `/api/search/suggestions`
  - Photos élus, icônes catégories
  - Navigation clavier + raccourci ⌘K

### 🔧 Infrastructure
- [x] **Docker** : Environnement containerisé
- [x] **FrankenPHP + PHP 8.4** : Migration complète (31/12/2025)
  - Laravel Octane worker mode
  - ~20-30ms/request (5x plus rapide que PHP-FPM)
  - HTTP/2 + HTTP/3 ready
- [x] **PostgreSQL 15** : Base de données avec vues SQL
- [x] **Redis 7** : Cache et sessions
- [x] **Meilisearch v1.5** : Recherche full-text 60K+ documents
- [x] **Synchronisation automatique** : Commandes Artisan
- [x] **Photos officielles** : AN et Sénat prioritaires

### ⚡ Optimisation Performances (Décembre 2025)
- [x] **Stats pré-calculées parlementaires** : Table `parlementaires_stats`
  - Taux de présence, votes pour/contre/abstention
  - Amendements déposés/adoptés/rejetés
  - Discipline de groupe (députés)
  - Calcul quotidien via scheduler (`04:30`)
- [x] **Stats pré-calculées lois** : Table `lois_stats`
  - Nombre d'étapes par chambre
  - Amendements et taux d'adoption
  - Scrutins liés
  - Score d'engagement
  - Calcul quotidien via scheduler (`04:45`)
- [x] **Commandes Artisan dédiées** :
  - `calculate:parlementaires-stats` : Recalcul stats députés/sénateurs
  - `calculate:lois-stats` : Recalcul stats lois
- [x] **Fallback intelligent** : Calcul à la volée si stats absentes ou obsolètes (>24h)

---

## 📅 TIMELINE 2025-2026

```
2025 T4 (Déc)     → 🔧 Consolidation & Données
2026 T1 (Jan-Mar) → 🏠 Refonte UX & Admin
2026 T2 (Avr-Jun) → 📜 Parcours Législatif & Vote Citoyen
2026 T3 (Jul-Sep) → 🌐 Open Data & Intégrations
2026 T4 (Oct-Déc) → 🚀 Lancement Asso & Scale
```

---

## 🔧 PHASE 0 : CONSOLIDATION (Décembre 2025)
**Objectif** : Stabiliser l'existant et préparer les nouvelles features

### 0.1 : 🧹 Nettoyage & Corrections
**Statut** : ✅ Terminé

- [x] Correction doublons sénateurs (vue DISTINCT)
- [x] Libellés commissions et groupes parlementaires
- [x] Photos officielles prioritaires
- [x] Affichage compact mandats/commissions
- [x] Vérifier import HATVP rémunérations serveur
- [x] Import amendements AN complet
- [ ] Tests de régression

### 0.2 : 📊 Données Complémentaires
**Priorité** : 🟡 HAUTE

- [x] **Questions au Gouvernement** (AN) - Import XML
- [x] **Circonscriptions députés** - Liaison député-circonscription avec :
  - Département et numéro de circonscription
  - Région et type (métropolitain/outre-mer)
  - Place dans l'hémicycle
  - Suppléant
  - Commande : `import:circonscriptions-an`
- [x] **Maires enrichis** - Données data.gouv.fr avec :
  - Nuance politique (LDVD, LLR, LSOC, etc.)
  - Téléphone et site web de la mairie
  - Coordonnées GPS
  - Mandature (2020-2026)
  - Commande : `import:maires-datagouv`
- [x] **Questions Écrites Sénat** - Import base SQL data.senat.fr ✅ *Terminé 03/01/2026*
  - 289 433 questions brutes disponibles (depuis 1978)
  - Commande : `import:questions-senat`
  - Pages `/questions/senat` avec filtres, stats, détail
  - Liaison avec sénateurs existants
- [x] **Débats en séance publique Sénat** - Import SQL data.senat.fr ✅ *Terminé 08/01/2026*
  - Source : `https://data.senat.fr/data/debats/debats.zip`
  - Sections de discussion législatives et diverses
  - Interventions des sénateurs avec analyse
  - Liaison avec lectures et dossiers législatifs
  - Commande : `senat:import-debats`
- [ ] **Textes Akoma Ntoso** (Sénat) - Documents législatifs
- [x] **Proportion hommes/femmes (statistiques)** ✅ *Terminé 07/01/2026*
  - Stats parité dans `ParlementController::calculateStats()`
  - Page `/parlement/comparaison` avec visualisation H/F
  - Calcul automatique via `CalculateElusGlobalStats`

---

## 🏠 PHASE 1 : REFONTE UX & ADMIN (T1 2026)
**Objectif** : Améliorer l'expérience utilisateur et les outils admin

### 1.1 : 🎨 Refonte Menu & Navigation
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1 semaine  
**Statut** : ✅ TERMINÉ (Décembre 2025)

**User Stories** :
- [x] En tant qu'utilisateur, je veux une navigation claire et intuitive
- [x] En tant qu'utilisateur mobile, je veux un menu adapté au touch
- [x] En tant qu'utilisateur, je veux accéder rapidement aux sections clés

**Tâches** :
- [x] Restructurer la navigation principale
- [x] Mega-menu avec catégories (Parlement, Données, Participation)
- [x] Breadcrumbs sur toutes les pages (composant réutilisable)
- [x] Raccourcis clavier (Cmd/Ctrl+K recherche, G+H accueil, G+D députés, G+S sénateurs)
- [x] CommandPalette pour navigation rapide
- [ ] Menu contextuel selon la page (reporté)

---

### 1.2 : 🏠 Refonte Page d'Accueil
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1 semaine  
**Statut** : ✅ TERMINÉ (Décembre 2025)

**User Stories** :
- [x] En tant que visiteur, je veux comprendre immédiatement le projet
- [x] En tant que citoyen, je veux voir l'actualité parlementaire récente
- [x] En tant qu'utilisateur, je veux accéder aux données les plus consultées

**Tâches** :
- [x] Hero section avec message clair et CTA
- [x] Derniers scrutins (AN) avec widget cliquable
- [x] Derniers amendements adoptés
- [x] Statistiques clés en temps réel (compteurs animés)
- [x] Design moderne emerald/teal avec dark mode
- [ ] Carte interactive en preview (reporté)
- [ ] Section "Comment ça marche" (reporté)
- [ ] Témoignages / Cas d'usage (reporté)

---

### 1.3 : 🛠️ Dashboard Utilisateur & Admin
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1-2 semaines  
**Statut** : 🔄 EN COURS (Décembre 2025)

**User Stories** :
- [x] En tant qu'utilisateur, je veux un dashboard interactif
- [ ] En tant qu'admin, je veux voir l'état de santé des imports
- [ ] En tant qu'admin, je veux modérer le contenu utilisateur

**Dashboard Utilisateur** (✅ Terminé) :
- [x] Refonte full-page responsive
- [x] Widget Top 5 députés actifs (par votes)
- [x] Widget Top 5 sénateurs actifs (par amendements)
- [x] Widget Groupes parlementaires avec couleurs
- [x] Widget Derniers scrutins cliquables
- [x] Widget Agenda AN (prochaines réunions)
- [x] Table `dashboard_stats` pré-calculée + scheduler quotidien
- [x] Commande `dashboard:calculate-stats`

**Dashboard Admin** (✅ Terminé - Décembre 2025) :

**Statistiques** :
- [x] Nombre d'utilisateurs inscrits / actifs
- [ ] Pages les plus consultées (analytics externe recommandé)
- [ ] Recherches populaires (reporté)
- [ ] Temps de réponse API (monitoring externe recommandé)

**Imports & Sync** :
- [x] Tableau des derniers imports (date, durée, statut)
- [x] Boutons pour relancer manuellement
- [x] Logs d'erreurs consultables
- [x] Alertes si import échoue
- [x] Table `import_logs` pour traçabilité
- [x] Modèle `ImportLog` avec helpers (start, finish, fail)

**Modération** (existant) :
- [x] File de signalements
- [x] Actions rapides (supprimer, avertir, bannir)
- [x] Historique des actions
- [x] Statistiques modération

---

### 1.4 : 📅 Calendrier Législatif
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine  
**Statut** : 🔄 EN COURS (Décembre 2025)

**User Stories** :
- [x] En tant que citoyen, je veux voir les prochains débats
- [ ] En tant que citoyen, je veux être notifié des votes importants
- [x] En tant que journaliste, je veux suivre l'agenda parlementaire

**Sources de données** :
- Agenda AN : https://www2.assemblee-nationale.fr/agendas/ → Agenda.json.zip ✅
- Agenda Sénat : https://www.senat.fr/ordre-du-jour/

**Tâches** :
- [x] Modèle ReunionAN + migration
- [x] Commande `import:reunions-an` depuis Agenda.json.zip
- [x] Page calendrier mensuel `/parlement/calendrier`
- [x] Page détail réunion `/parlement/calendrier/reunion/{uid}`
- [x] Widget "Prochaines réunions" sur dashboard
- [x] Intégration menu navigation
- [x] Import agenda Sénat (iCal via sabre/vobject)
- [x] Import agenda Élysée (scraping HTML)
- [x] Table unifiée `evenements_legislatifs`
- [x] Filtres par source (AN, Sénat, Élysée)
- [x] **Export iCal / Google Calendar** ✅ *Terminé 07/01/2026*
  - Controller `CalendarExportController` avec export + flux
  - Routes `/api/calendar/export.ics`, `/api/calendar/feed.ics`
  - Bouton export dans page calendrier
  - Support abonnement dynamique (Google Calendar, Apple, Outlook)
- [ ] Notifications optionnelles

---

### 1.5 : 📅 Calendrier des Réunions
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine

**User Stories** :
- [ ] En tant que citoyen, je veux voir les réunions de commissions
- [ ] En tant que citoyen, je veux accéder aux comptes-rendus

**Tâches** :
- [ ] Import réunions commissions (AN + Sénat)
- [ ] Lien vers vidéos/comptes-rendus
- [ ] Filtres par commission
- [ ] Recherche par sujet

---

## 🔧 PHASE 1.5 : MIGRATION TECHNIQUE (T1 2026)
**Objectif** : Moderniser l'infrastructure pour de meilleures performances

### 1.6 : 🚀 Migration PHP 8.4 + FrankenPHP ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 1 jour → **Terminé le 31/12/2025**

**Contexte** :
PHP 8.5 (sorti le 20 novembre 2025) apporte des fonctionnalités clés pour CivicDash.
FrankenPHP remplace Nginx + PHP-FPM par un serveur Go moderne avec mode worker.

**Nouvelles fonctionnalités PHP 8.5** :
- **Opérateur Pipe `|>`** : Chaînage élégant des transformations de données
- **Extension URI native** : Parsing URLs API AN/Sénat plus propre
- **cURL handles persistants** : ~10x plus rapide pour imports massifs
- **Attribut `#[\NoDiscard]`** : Sécurité API renforcée
- **Clone avec modification** : Simplification DTOs readonly

**Avantages FrankenPHP** :
- Mode worker = application en mémoire (~10x plus rapide que PHP-FPM)
- HTTP/2 + HTTP/3 + Early Hints natifs
- Caddy intégré = HTTPS automatique
- Un seul binaire, configuration simplifiée
- Support Laravel Octane natif

**Tâches** :
- [x] Créer `Dockerfile.frankenphp` avec PHP 8.4
- [x] Créer `docker-compose.frankenphp.yml` (remplace Nginx + PHP-FPM)
- [x] Installer Laravel Octane avec driver FrankenPHP
- [x] Configurer workers et memory management
- [x] Tester performances: ~20-30ms/request (5x plus rapide)
- [ ] Adapter docker-compose.yml (suppression nginx)
- [ ] Tests de charge et benchmarks
- [ ] Migration production

**Configuration cible** :
```yaml
# docker-compose.yml
services:
  app:
    image: dunglas/frankenphp:php8.5-alpine
    environment:
      - FRANKENPHP_CONFIG=worker ./public/index.php
    # Plus besoin de nginx séparé !
```

**Points de vigilance** :
- [ ] Vérifier compatibilité dépendances (Meilisearch SDK, Spatie, Inertia)
- [ ] Auditer singletons et états persistants (mode worker)
- [ ] Configurer Xdebug/Telescope pour debug

---

## 📜 PHASE 2 : PARCOURS LÉGISLATIF & VOTE CITOYEN (T2 2026)
**Objectif** : Suivre les textes de loi et permettre l'expression citoyenne

### 2.1 : 📜 Vie d'un Texte de Loi ✅ IMPLÉMENTÉ
**Priorité** : 🔴 CRITIQUE  
**Durée** : 2-3 semaines → **Terminé le 29/12/2025**

**User Stories** :
- [x] En tant que citoyen, je veux comprendre le parcours d'une loi
- [x] En tant que citoyen, je veux voir les amendements déposés
- [ ] En tant que citoyen, je veux comparer les versions du texte

**Parcours législatif visualisé** :
```
📋 Dépôt → 🏛️ Commission → 📖 1ère lecture AN → 📖 1ère lecture Sénat
        → 🔄 Navette → 📖 2ème lecture → ⚖️ CMP → 🏛️ Conseil Constitutionnel
        → 📜 Promulgation → 📰 JO
```

**Documentation** : `docs/ARCHITECTURE_LOIS.md`

**Données disponibles** :
- 12 088 lois dans `senat_dosleg_loi`
- 16 337 lectures (navette) dans `senat_dosleg_lecture`
- 21 350 passages par chambre dans `senat_dosleg_lecass`
- 30 thématiques dans `senat_dosleg_the`
- 9 types de lecture (1ère, 2ème, CMP, définitive...)
- 6 états (promulgué, en cours, rejeté, caduc, fusionné, retiré)

**Tâches** :
- [x] Modèles Laravel (`Loi`, `LectureLoi`, `PassageChambre`, `TypeLecture`, `EtatLoi`, `ThematiqueLoi`)
- [x] `LoiController` avec index, show, timeline, search, statistiques
- [x] Page liste des lois avec filtres (état, type, année, recherche)
- [x] Page détail avec timeline visuelle du parcours AN ↔ Sénat
- [x] Barre de progression dynamique
- [x] Lien vers JO Légifrance
- [x] Lois similaires par thématique
- [ ] Versions du texte (diff) - *Phase 2.1.5*
- [ ] Intégration Légifrance API - *Phase 2.1.5*

---

### 2.1.5 : 📰 Import Journal Officiel (DILA/data.gouv.fr) ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 1 jour → **Terminé le 29/12/2025**

**Source** : [Exports DILA sur data.gouv.fr](https://echanges.dila.gouv.fr/OPENDATA/JORF/) - Open Data gratuit

**Contexte** : L'API Légifrance (PISTE) est réservée aux éditeurs juridiques accrédités. Alternative : import des exports XML quotidiens du Journal Officiel.

**Données importées** :
- 📜 **Lois** publiées au JO avec numéro, NOR, date
- 📋 **Décrets** d'application
- ⚖️ **Ordonnances**
- 📰 **Articles** (contenu optionnel)
- 🔗 **URL Légifrance** générée automatiquement

**Implémentation** :
- [x] Table `textes_jo` (métadonnées : titre, nature, numéro, NOR, dates)
- [x] Table `articles_jo` (contenu des articles, optionnel)
- [x] Modèles `TexteJO` et `ArticleJO`
- [x] Commande `import:jorf` avec options :
  - `--days=N` : importer les N derniers jours
  - `--date=YYYYMMDD` : date spécifique
  - `--lois-only` : filtrer LOI, DECRET, ORDONNANCE
  - `--with-articles` : importer le contenu des articles
- [x] Liaison automatique avec `senat_dosleg_loi` via numéro
- [x] Nettoyage automatique après import (économie espace disque)

**Résultats test (28/12/2025)** :
```
148 textes importés :
├── 122 décrets
├── 20 lois  
├── 6 ordonnances
└── 19 liés aux lois existantes ✅
```

**Optimisation espace disque** :
- Archives ~5 MB/jour, supprimées après traitement
- Stockage 0 fichiers permanents
- Option `--lois-only` pour réduire le volume

---

### 2.1.6 : 🏷️ Système de Tags & Thématiques ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 1 jour → **Terminé le 29/12/2025**

**Implémentation** :
- [x] Table `tags` étendue (type, source, validated, usage_count)
- [x] Tables pivot `loi_tag`, `texte_jo_tag`, `topic_tag`
- [x] Table `tag_suggestions` pour contributions communauté
- [x] Modèles `Tag`, `TagSuggestion`
- [x] Commande `sync:tags-senat` pour synchroniser les 30 thématiques officielles
- [x] 16 962 associations lois ↔ tags créées automatiquement
- [x] Filtres par thématique sur `/legislation/lois`

**Prochaines étapes** :
- [ ] Facettes Meilisearch pour recherche
- [ ] UI suggestion de tags par utilisateurs
- [ ] Classification IA (CamemBERT) - long terme

---

### 2.1.7 : 🔍 Recherche Globale Intelligente
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1-2 semaines  
**Statut** : 🔄 EN COURS (Décembre 2025)

**Objectif** : Un seul champ de recherche qui trouve tout, avec résultats groupés par type.

**User Stories** :
- [x] En tant que citoyen, je tape "retraite" et je vois lois, députés, scrutins, discussions
- [x] En tant que citoyen, je veux des suggestions pendant la frappe
- [ ] En tant que citoyen, je veux filtrer par type après la recherche

**Fonctionnalités** :
```
Recherche "retraite"
├── 👤 Députés/Sénateurs (5)
├── 📜 Lois (12)
├── 📰 Textes JO (8)
├── 🗳️ Scrutins (3)
└── 💬 Discussions (15)
```

**Phase 1 - Suggestions temps réel (✅ Terminé 30/12/2025)** :
- [x] Composant `GlobalSearch.vue` avec dropdown suggestions
- [x] API `/api/search/suggestions` multi-sources
- [x] Recherche Députés (nom, prénom, groupe)
- [x] Recherche Sénateurs (nom, prénom, circonscription)
- [x] Recherche Lois (titre, numéro)
- [x] Recherche Idées citoyennes (titre, description)
- [x] Recherche Maires (nom, commune)
- [x] Photos des élus dans les suggestions
- [x] Navigation clavier (↑↓ Enter Escape)
- [x] Raccourci global ⌘K / Ctrl+K
- [x] Scoring de pertinence pour tri
- [x] Résultats groupés par catégorie avec icônes
- [x] Intégration header desktop + mobile

**Phase 2 - Meilisearch ✅ COMPLÈTE (31/12/2025)** :
- [x] Driver Meilisearch activé (collection → meilisearch)
- [x] Index multi-modèles (Loi, ActeurAN, Senateur, ScrutinAN, Topic, Maire)
- [x] Typo-tolerant ("retrait" → "retraite") ✅
- [x] Highlighting des résultats (`<em>...</em>`)
- [x] Facettes par type de contenu (filterableAttributes)
- [x] Recherche < 1ms sur 60K+ documents ⚡

---

### 2.1.8 : 🏛️ Hub Législation Unifié ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine → **Terminé le 29/12/2025**

**Objectif** : Unifier `/tags` et `/legislation/lois` en un seul point d'entrée cohérent.

**Architecture** :
```
/legislation (Hub)
├── 📊 Vue d'ensemble (stats clés, dernières lois promulguées)
├── 🏷️ Explorer par thématique (30 catégories avec icônes/couleurs)
└── 📜 Accès rapide vers /lois (liste complète avec filtres)

/lois
├── 📋 Liste paginée avec filtres (état, type, année, thématique)
├── 🔍 Recherche textuelle
└── 🏷️ Barre de filtres thématiques cliquables

/lois/{loicod}
├── 📜 Détail de la loi (titre, numéro, état, dates)
├── 🔄 Timeline du parcours législatif AN ↔ Sénat
├── 📝 Amendements par étape (nombre + adoptés)
├── 🗳️ Scrutins AN liés (matching textuel automatique)
└── 📚 Lois similaires par thématique
```

**Tâches** :
- [x] Page hub `/legislation` avec stats et thématiques visuelles
- [x] Redirection `/legislation/lois` → `/lois`
- [x] Visualisation par thématique (cards avec icônes, couleurs, stats)
- [x] Filtres combinables (État + Année + Thématique) sur `/lois`
- [x] Liaison scrutins AN ↔ lois par matching textuel
- [x] Affichage scrutins dans sidebar page détail
- [x] Hero banner page `/lois` (gradient slate/indigo, stats, filtres sidebar)
- [x] Barre de recherche globale avec suggestions (Phase 2.1.7)

---

### 2.1.9 : 📚 Enrichissement Légifrance (Futur)
**Priorité** : 🟢 MOYENNE  
**Statut** : ⏸️ En attente (API restreinte)

**Note** : L'API Légifrance sur PISTE nécessite une accréditation spécifique (éditeurs juridiques, administrations). Pour un accès complet aux textes consolidés et à la jurisprudence, explorer :
- Partenariat avec un éditeur accrédité
- Demande d'accès institutionnel
- Scraping Légifrance.gouv.fr (CGU à vérifier)

---

## 🧭 VISION UX : DEUX PARCOURS UTILISATEUR FONDAMENTAUX

**Principe** : L'utilisateur citoyen a deux besoins fondamentaux que CivicDash doit servir parfaitement.

### Parcours 1️⃣ : "Mes Représentants" (Bottom-up)
> *"Qui me représente et que fait-il/elle ?"*

```
🏠 Ma Localisation (code postal / ville)
    ↓
👤 Mon Député / Mes Sénateurs
    ├── 📊 Statistiques d'activité
    │   ├── Présence aux scrutins
    │   ├── Interventions en séance
    │   └── Questions au gouvernement
    │
    ├── 🗳️ Votes sur les lois majeures
    │   └── Position : Pour / Contre / Abstention
    │       (sans détail amendements - trop technique)
    │
    ├── 👥 Groupe parlementaire
    │   └── Cohésion du groupe sur les votes
    │
    ├── 📋 Déclarations HATVP
    │   ├── Patrimoine
    │   └── Intérêts
    │
    ├── 📞 Coordonnées
    │   ├── Email, téléphone
    │   └── Réseaux sociaux
    │
    └── 📖 Biographie (Wikipedia)
```

**Valeur** : Transparence sur *qui* me représente et *comment* cette personne vote.

### Parcours 2️⃣ : "Suivre une Loi" (Top-down)
> *"Que devient cette loi qui m'intéresse ?"*

```
🔍 Recherche / Thématique
    ↓
📜 Une Loi
    ├── 📍 Parcours législatif (timeline visuelle)
    │   ├── Dépôt → Commission → Séance → Navette → Promulgation
    │   └── Durée totale du processus
    │
    ├── 📝 Amendements
    │   ├── Combien déposés / adoptés
    │   ├── Par qui (personne/groupe)
    │   └── Qui s'oppose (groupes contre)
    │
    ├── 🗳️ Scrutins publics
    │   ├── Résultat final
    │   └── Répartition par groupe politique
    │
    ├── 📰 Publication JO
    │   └── Lien Légifrance si promulguée
    │
    └── 🔔 Suivre cette loi (notifications)
```

**Valeur** : Comprendre le *processus* et *qui* influence le texte final.

### Architecture UX Cible
```
┌─────────────────────────────────────────────────────────────┐
│                     🏛️ CivicDash                            │
├─────────────────────────────────────────────────────────────┤
│  🏠 Accueil                                                  │
│  ├── 🔍 Recherche globale (députés, sénateurs, lois)        │
│  ├── 📍 "Mes Représentants" (géolocalisation ou CP)         │
│  └── 📰 Actualité parlementaire                             │
├─────────────────────────────────────────────────────────────┤
│  👥 PARLEMENTAIRES                                           │
│  ├── /deputes          → Liste + recherche                  │
│  ├── /deputes/{slug}   → Fiche complète                     │
│  ├── /senateurs        → Liste + recherche                  │
│  └── /senateurs/{slug} → Fiche complète                     │
├─────────────────────────────────────────────────────────────┤
│  📜 LÉGISLATION                                              │
│  ├── /legislation      → Hub (recherche + thématiques)      │
│  ├── /lois             → Liste avec filtres                 │
│  └── /lois/{id}        → Parcours complet de la loi         │
├─────────────────────────────────────────────────────────────┤
│  📅 CALENDRIER                                               │
│  └── /calendrier       → Séances, commissions, événements   │
└─────────────────────────────────────────────────────────────┘
```

---

### 2.1.10 : 👤 Refonte Fiches Parlementaires
**Priorité** : 🔴 CRITIQUE  
**Durée** : 2 semaines  
**Statut** : 🔄 EN COURS (Décembre 2025)

**User Stories** :
- [ ] En tant que citoyen, je veux voir comment mon député vote sur les lois importantes
- [ ] En tant que citoyen, je veux accéder facilement aux déclarations HATVP
- [ ] En tant que citoyen, je veux contacter mon élu
- [ ] En tant que citoyen, je veux voir la cohésion de son groupe politique

**Fiche Député Complète** :
```
/deputes/{slug}
├── 📸 Photo + Identité
│   ├── Nom, prénom, âge
│   ├── Circonscription
│   └── Groupe politique (couleur)
│
├── 📊 Statistiques clés (cards)
│   ├── Taux de présence aux scrutins (%)
│   ├── Nombre d'interventions en séance
│   ├── Questions au gouvernement
│   └── Amendements déposés/adoptés
│
├── 🗳️ Votes récents (widget principal)
│   ├── 10 derniers scrutins importants
│   ├── Position : Pour/Contre/Abstention (badge couleur)
│   ├── Lien vers la loi concernée
│   └── Alignement avec le groupe (%)
│
├── 📋 Déclarations HATVP (accordéon)
│   ├── Patrimoine résumé
│   ├── Intérêts déclarés
│   └── Rémunérations par année
│
├── 👥 Groupe Parlementaire (sidebar)
│   ├── Nom + logo
│   ├── Cohésion du groupe sur les votes (%)
│   └── Autres membres du groupe
│
├── 📞 Coordonnées (footer ou sidebar)
│   ├── Email officiel
│   ├── Téléphone
│   ├── Adresse permanence
│   └── Réseaux sociaux
│
└── 📖 Biographie Wikipedia (optionnel)
```

**Fiche Sénateur Complète** :
- [ ] Même structure que député
- [ ] Adaptation spécifique Sénat (département vs circonscription)

**Tâches Techniques** :
- [ ] Refonte `ActeurController@show` avec données complètes
- [ ] Nouveau composant `VotesRecentsWidget.vue`
- [ ] Intégration HATVP dans la fiche (déjà importé)
- [ ] Calcul taux de présence aux scrutins
- [ ] Calcul cohésion groupe politique
- [ ] Liens vers lois depuis les votes
- [ ] Design responsive mobile-friendly

---

### 2.1.11 : 📍 Géolocalisation "Mes Représentants" ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine → **Terminé le 07/01/2026**

**User Stories** :
- [x] En tant que citoyen, je saisis mon code postal et je vois mes représentants
- [x] En tant que citoyen, je peux utiliser la géolocalisation du navigateur

**Fonctionnalités** :
```
🏠 Entrez votre code postal : [75001] [🔍]
    ↓
📍 Vous êtes dans :
├── 🗳️ 1ère circonscription de Paris
│   └── 👤 Député : Sylvain Maillard (Renaissance)
│       → Voir sa fiche
│
├── 🏛️ Sénateurs de Paris (12)
│   ├── 👤 Jean-Pierre Sueur (PS)
│   ├── 👤 Catherine Dumas (LR)
│   └── ... voir tous
│
└── 🗺️ Voir sur la carte
```

**Implémentation** :
- [x] `MesRepresentants.vue` : Page complète avec recherche dynamique
- [x] `RepresentantController.php` : Controller avec simulation par CP
- [x] `LocalisationService.php` : Service unifié de géolocalisation
- [x] `RepresentantsSearchController.php` : API recherche
- [x] Table `french_postal_codes` : 39K+ codes postaux avec circonscriptions
- [x] Gestion arrondissements (Paris, Lyon, Marseille)
- [x] Affichage : Député + Sénateurs + Maire

---

### 2.2 : 🗳️ Vote Citoyen sur Textes de Loi ✅ IMPLÉMENTÉ
**Priorité** : 🔴 CRITIQUE  
**Durée** : 2 semaines → **Terminé le 30/12/2025**

**User Stories** :
- [x] En tant que citoyen, je veux donner mon avis sur une loi
- [x] En tant que citoyen, je veux voir comment les élus ont voté
- [x] En tant que citoyen, je veux comparer le vote citoyen vs parlementaire

**Architecture technique** :
```sql
-- Tables implémentées
CREATE TABLE citizen_law_votes (...);     -- Votes individuels
CREATE TABLE citizen_law_stats (...);     -- Stats pré-calculées
```

**Fonctionnalités** :
- [x] Widget vote sur page `/lois/{id}` (👍 Pour / 👎 Contre)
- [x] **Score de popularité** (-100 à +100) avec labels :
  - 🔥 Très populaire / 👍 Populaire / ⚖️ Avis partagés / ⚠️ Controversée / 🚫 Impopulaire
- [x] Seuil 5 votes minimum pour afficher le score
- [x] Barre de progression visuelle Pour/Contre
- [x] Stats pré-calculées (table `citizen_law_stats`)
- [x] Recalcul automatique après chaque vote

**Règles** :
- [x] 1 vote par utilisateur par loi
- [x] Vote modifiable à tout moment
- [x] Résultats visibles en temps réel
- [x] Authentification requise

**Tâches** :
- [x] Migration `citizen_law_votes` + `citizen_law_stats`
- [x] Modèles `CitizenLawVote`, `CitizenLawStats`
- [x] API vote (POST/DELETE `/api/lois/{loiCod}/vote`)
- [x] Composant `LawVoteWidget.vue` avec score de popularité
- [x] Intégration page `/lois/{id}` (sidebar)
- [x] Commande `demo:generate-votes` pour votes synthétiques

---

### 2.2.5 : 💬 Débats Citoyens Liés aux Lois ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 1 jour → **Terminé le 30/12/2025**

**Fonctionnalités** :
- [x] Liaison `Topic` ↔ `Loi` (colonne `loi_cod` sur topics)
- [x] Section "Débat citoyen" sur page détail loi
- [x] Bouton "Lancer le débat" avec pré-remplissage
- [x] Liste des débats existants liés à la loi
- [x] Création topic avec loi pré-sélectionnée (`/topics/create?loi_cod=...`)

---

### 2.2.6 : 📝 Enrichissement Page Loi ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 1 jour → **Terminé le 30/12/2025**

**Améliorations** :
- [x] **Amendements cliquables** : Liens vers AN/Sénat
  - AN : `assemblee-nationale.fr/dyn/17/amendements/{texte}/{numero}`
  - Sénat : `senat.fr/amendements/{session}/{texte}/{numero}.html`
- [x] **Scrutins dans le corps principal** (pas sidebar)
  - Catégorisation : Vote final ⭐ / Articles 📄 / Amendements 📝
  - Design en grille avec couleurs par catégorie
- [x] **Durée corrigée** : Calcul basé sur dates réelles du parcours
- [x] **Parlementaires impliqués** : Rapporteurs et auteurs principaux d'amendements

---

### 2.3 : 💡 Idées Citoyennes & Propositions (REFONTE) ✅ PHASE 1 IMPLÉMENTÉE
**Priorité** : 🔴 CRITIQUE  
**Durée** : 3 semaines  
**Statut** : ✅ Phase 1 terminée (30/12/2025)

**Objectif** : Refondre le système de Topics existant en un système d'idées/propositions citoyennes structuré avec assistant de création.

**User Stories** :
- [x] En tant que citoyen, je veux soumettre une idée/proposition guidée
- [x] En tant que citoyen, je veux voter pour les idées que je soutiens
- [x] En tant que citoyen, je veux interpeller mes élus sur des sujets concrets
- [x] En tant que citoyen, je veux filtrer par niveau géographique et thématique
- [x] En tant que citoyen, je veux lier mon idée à une loi ou un élu

**Niveaux géographiques** :
```
🇫🇷 National     → Tous les citoyens, tous les élus
🗺️ Régional      → Citoyens de la région
📍 Départemental → Citoyens du département, sénateurs
🏠 Communal      → Citoyens de la commune, député, maire
```

**Architecture technique** (refonte table `topics`) :
```sql
-- Extension table topics existante
ALTER TABLE topics ADD COLUMN IF NOT EXISTS idea_type VARCHAR(30);
-- Types: proposal, question, debate, petition, interpellation

ALTER TABLE topics ADD COLUMN IF NOT EXISTS loi_cod VARCHAR(20);
-- Liaison avec une loi (déjà fait ✅)

-- Liaisons avec élus (nouvelle table)
CREATE TABLE topic_elus (
    id BIGSERIAL PRIMARY KEY,
    topic_id BIGINT REFERENCES topics(id),
    elu_type VARCHAR(20),       -- depute, senateur, maire
    elu_id VARCHAR(50),         -- uid AN, id sénateur, id maire
    interpellation BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP
);

-- Statistiques pré-calculées
ALTER TABLE topics ADD COLUMN votes_pour INT DEFAULT 0;
ALTER TABLE topics ADD COLUMN votes_contre INT DEFAULT 0;
ALTER TABLE topics ADD COLUMN score INT DEFAULT 0;
```

**Assistant de création (étapes)** :
```
┌─────────────────────────────────────────────────────────────────┐
│  📝 CRÉER UNE PROPOSITION                          Étape 1/5   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  🎯 Quel type de contribution ?                                 │
│                                                                 │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐               │
│  │ 💡 Idée     │ │ ❓ Question │ │ 💬 Débat    │               │
│  │ Proposer    │ │ Demander    │ │ Discuter    │               │
│  └─────────────┘ └─────────────┘ └─────────────┘               │
│                                                                 │
│  ┌─────────────┐ ┌─────────────┐                               │
│  │ 📜 Pétition │ │ 📢 Interpel │                               │
│  │ Mobiliser   │ │ Élus        │                               │
│  └─────────────┘ └─────────────┘                               │
│                                                                 │
│                                            [Suivant →]          │
└─────────────────────────────────────────────────────────────────┘

Étape 2: 📍 Portée géographique
Étape 3: 🏷️ Catégorie & Tags (suggestions IA)
Étape 4: 📜 Loi liée (optionnel, recherche)
Étape 5: 👤 Élus concernés (optionnel, géo-suggéré)
```

**Interface liste** :
```
┌─────────────────────────────────────────────────────────────────┐
│  💡 PROPOSITIONS CITOYENNES              [📝 Nouvelle idée]    │
├─────────────────────────────────────────────────────────────────┤
│  📍 [🇫🇷 Tous] [📍 Mon territoire]                              │
│  🎯 [Tous] [💡 Idées] [❓ Questions] [📢 Interpellations]       │
│  🏷️ [Santé] [Éducation] [Transport] [Environnement] [+]        │
│  📊 [🔥 Tendance] [🆕 Récent] [💬 Actif] [✅ Résolu]            │
├─────────────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────────────────────────────┐ │
│  │ 💡 IDÉE · 📍 National · 🏷️ Logement                       │ │
│  │                                                           │ │
│  │ Réduire les frais de notaire pour les primo-accédants     │ │
│  │                                                           │ │
│  │ 📜 PLF 2025 · 👤 3 élus interpellés                       │ │
│  │                                                           │ │
│  │ [👍 1,234] [👎 156] [💬 89]  ████████░░ 89%  🔥 Tendance  │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │ 📢 INTERPELLATION · 📍 Loire-Atlantique                   │ │
│  │                                                           │ │
│  │ @SophieBlutel : Position sur la réforme des retraites ?   │ │
│  │                                                           │ │
│  │ 👤 Députée 4e circo · ⏳ En attente de réponse            │ │
│  │                                                           │ │
│  │ [👍 567] [💬 34]  👥 234 soutiens                         │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

**Tâches Phase 1 (✅ Complétées)** :
- [x] Migration extension `topics` (idea_type, votes_pour, score, slug, published_at)
- [x] Table `topic_elus` pour liaisons avec élus
- [x] Table `topic_votes` pour votes citoyens
- [x] Table `topic_tags` pour tags multiples
- [x] Modèle `TopicElu.php` avec relations et accessors
- [x] Modèle `TopicVote.php` avec Wilson score
- [x] Extension modèle `Topic.php` (scopes, accessors, boot)
- [x] Controller `ParticipationController` (hub, index, create, store, vote)
- [x] Page `/participation` - Hub participation citoyenne
- [x] Page `/participation/idees` - Liste avec filtres avancés
- [x] Page `/participation/idees/nouvelle` - Wizard 6 étapes
- [x] Routes web participation
- [x] Composant `CreateIdea.vue` (wizard 6 étapes avec loi liée)
- [x] Composant `Ideas/Index.vue` (liste + filtres + tri)
- [x] Composant `Hub.vue` (page d'accueil participation)

**Tâches Phase 2 (🔄 En cours - Décembre 2025)** :
- [x] Page `/participation/idees/{id}` (détail + débat + élus)
- [x] Composant `Ideas/Show.vue` avec :
  - Hero banner dynamique selon type d'idée
  - Widget de vote Pour/Contre avec barres visuelles
  - Affichage élus concernés avec statut interpellation
  - Loi liée avec lien direct
  - Commentaires (posts) paginés
  - Idées similaires en sidebar
- [x] Wizard étape 6 : Rattacher à une loi existante (recherche API)
- [x] API `/api/lois/search` pour recherche lois
- [x] Géo-suggestion élus selon scope (API `/api/legislation/elus/suggest`)
- [x] Recherche élus par département/région basée sur la portée géographique
- [x] Mise à jour contraintes CHECK PostgreSQL (scope, status, type)
- [x] Design full-width + filtres mobile redesignés
- [x] **Interpellation : notification à l'élu (email)** ✅ *Terminé 03/01/2026*
  - Mail `InterpellationNotificationMail` avec template Blade
  - Service `EluNotificationService` centralisé
  - Notification in-app + email aux élus ayant un compte
  - Statuts de notification : `notified_at`, `email_sent_at`, `viewed_at`
- [x] **Réponses d'élus (interface améliorée)** ✅ *Terminé 03/01/2026*
  - Modèles de réponse pré-rédigés (prise en compte, détaillée, orientation, action législative)
  - Conseils de rédaction affichables
  - Indicateur d'urgence (récente, à traiter, urgente)
  - Prévisualisation de la réponse
  - Notification à l'auteur lors de la réponse
- [x] **Références internes avec preview** ✅ *Terminé 03/01/2026*
  - Composant `ReferencePreview.vue` : card au hover sur @depute:, @senateur:, etc.
  - Composant `RichContent.vue` : parser et affichage avec références enrichies
  - Composant `ReferenceInput.vue` : autocomplete lors de la saisie des mentions
  - API `/api/references/preview/{type}/{identifier}` pour les données
  - Notifications aux élus mentionnés dans le contenu
- [ ] Suggestions IA pour tags (basé sur titre/description)
- [ ] Modération admin améliorée
- [ ] Gamification (badges participation)

---

### 2.4 : 💬 Débat Structuré & Forum ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 2 semaines → **Terminé le 07/01/2026**

**User Stories** :
- [x] En tant que citoyen, je veux débattre sur une idée ou une loi
- [x] En tant que citoyen, je veux répondre à un commentaire
- [x] En tant que citoyen, je veux signaler un contenu inapproprié

**Fonctionnalités** :
- [x] Commentaires imbriqués (threads) - `Post.php` avec `parent_id` + `replies()`
- [x] Vote sur les commentaires (👍/👎) - `upvotes`/`downvotes` + `PostVote`
- [x] Mise en avant des contributions de qualité - Score net, tri par score
- [x] Signalement + modération - `Report` polymorphique
- [x] Mention d'utilisateurs (@pseudo) - `ReferenceInput.vue`
- [x] Notifications en temps réel - `NotificationService`

**Modèles implémentés** :
- [x] `Post.php` : Threading complet (parent/replies), votes, signalements
- [x] `CitizenIdeaComment.php` : Commentaires idées citoyennes avec threads
- [x] `PostVote.php` : Votes up/down sur posts
- [x] `CitizenCommentVote.php` : Votes sur commentaires idées

**Règles de modération** :
```yaml
Création:
  - Authentification requise ✅
  - Contenu: 10-5000 caractères ✅
  - Rate limit: 10 commentaires/heure ✅

Anti-manipulation:
  - Détection multi-comptes (à améliorer)
  - Captcha après actions suspectes (à implémenter)
  - Bannissement progressif ✅

Modération:
  - File de signalements ✅
  - Actions: avertir, masquer, supprimer, bannir ✅
  - Historique des actions ✅ (moderation_logs)
```

---

### 2.5 : 🎮 Gamification Participation ✅ IMPLÉMENTÉ
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine → **Terminé le 03/01/2026**

**Badges citoyens** (38 badges implémentés) :
```
🏅 "Premier Pas"         → Premier vote citoyen
🗳️ "Voix Active"         → 10 votes donnés
⭐ "Voix de la Nation"   → 500 votes donnés
💡 "Visionnaire"         → Créer un premier sujet
💬 "Contributeur Actif"  → 25 commentaires
🔥 "Marathonien"         → 7 jours consécutifs
🏛️ "Citoyen Éternel"     → 365 jours consécutifs (secret)
👑 "Légende Démocratique"→ Niveau 50 (secret)
```

**Catégories de badges** :
- 🗳️ Participation (votes, sujets, commentaires)
- 📜 Législatif (votes sur lois, suivi)
- 💰 Budget (allocations budgétaires)
- 👥 Social (upvotes reçus)
- 🔥 Engagement (streaks, régularité)
- 🎓 Expertise (niveaux atteints)

**Composants implémentés** :
- [x] `GamificationWidget.vue` : Widget compact pour header
- [x] `LevelProgressBar.vue` : Barre de progression XP
- [x] `BadgeCard.vue` : Affichage d'un badge
- [x] `AchievementUnlocked.vue` : Popup déblocage
- [x] Page `/profile/gamification` : Tableau de bord complet

**API Gamification** :
- [x] `GET /api/gamification/my-stats` : Mes statistiques
- [x] `GET /api/gamification/achievements` : Tous les badges
- [x] `GET /api/gamification/leaderboard` : Classement

**Tableau de bord utilisateur** :
- [x] Mes idées et leur score
- [x] Mes badges débloqués et en cours
- [x] Progression niveau + XP
- [x] Streak actuel et record
- [ ] Comparaison avec vote parlementaire (à venir)
- [ ] "Vous êtes aligné à X% avec le groupe Y" (à venir)

---

### 2.6 : 💰 Budget Participatif Approfondi
**Priorité** : 🟢 MOYENNE  
**Durée** : 2 semaines

**User Stories** :
- [ ] En tant que citoyen, je veux comprendre le budget de l'État
- [ ] En tant que citoyen, je veux simuler ma propre allocation
- [ ] En tant que citoyen, je veux comparer avec le budget réel

**Fonctionnalités** :
- [ ] Visualisation budget État (PLF/PLFSS)
- [ ] Simulation : "Si j'étais ministre des finances..."
- [ ] Comparaison allocation citoyenne vs budget voté
- [ ] Historique budgets (n-5 ans)
- [ ] Données open data économie.gouv.fr

---

## 🏛️ PHASE 2.7 : MENU ÉTAT & DONNÉES GOUVERNEMENTALES (T1-T2 2026)
**Objectif** : Vue d'ensemble complète de l'État français

### 2.7.1 : 🏛️ Restructuration Menu "État" - Vue d'ensemble de la République
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1 semaine  
**Statut** : 📋 Planifié

**Objectif** : Créer un menu unifié "État" regroupant toutes les institutions françaises pour offrir une vue d'ensemble complète du fonctionnement de la République.

**Architecture proposée** :
```
📊 État
├── 🏛️ Parlement (existant, à déplacer)
│   ├── Assemblée Nationale (577 députés)
│   ├── Sénat (348 sénateurs)
│   ├── Commissions parlementaires
│   ├── Groupes politiques
│   └── Scrutins & Votes
│
├── 🏰 Gouvernement (NOUVEAU)
│   ├── Premier Ministre
│   ├── Ministères (avec budget/actions)
│   ├── Secrétaires d'État
│   └── Décrets & Nominations récentes
│
├── 🏛️ Élysée (NOUVEAU)
│   ├── Président de la République
│   ├── Agenda présidentiel (déjà implémenté!)
│   └── Discours & Communications
│
├── ⚖️ Institutions (NOUVEAU)
│   ├── Conseil Constitutionnel
│   ├── Conseil d'État
│   └── Cour des Comptes
│
├── 🗺️ Collectivités (existant, maires)
│   ├── Régions (18)
│   ├── Départements (101)
│   └── Communes & Maires
│
└── 📈 Statistiques & Budget
    ├── Statistiques France (existant)
    ├── Budget de l'État
    └── Finances publiques
```

**📡 Sources de Données Identifiées** :

| Source | Données | URL/API | Statut |
|--------|---------|---------|--------|
| Annuaire Service-Public | Ministères, services | `lannuaire.service-public.fr/api/explore/v2.1/` | ✅ Disponible |
| data.gouv.fr - Budget | PLF, dépenses, recettes | Datasets budget État | ✅ Disponible |
| JORF | Décrets nomination ministres | Via commande existante | ✅ Implémenté |
| Élysée Agenda | Agenda présidentiel | `ImportAgendaElysee.php` | ✅ Implémenté |
| Légifrance API | Composition gouvernement | Via décrets | 🟡 À explorer |
| Vie-Publique | Fiches ministères | Scraping possible | 🟡 À explorer |
| info.gouv.fr/ministere | Composition gouvernement actuel | À scraper | 🔍 À valider |
| economie.gouv.fr | Budget, finances publiques | API à identifier | 🔍 À valider |
| conseil-constitutionnel.fr | Décisions, membres | À identifier | 🔍 À valider |
| ccomptes.fr | Rapports, recommandations | À identifier | 🔍 À valider |

**🛠️ Plan d'implémentation** :

**Phase 1 : Structure du menu** (immédiat)
- [ ] Réorganiser le menu navigation pour inclure "État" comme menu principal
- [ ] Logos officiels SVG (AN, Sénat) ✅ Fait
- [ ] Pages hub pour chaque section
- [ ] Breadcrumbs contextuels

**Phase 2 : Gouvernement** (1-2 jours)
- [ ] Créer modèles `Ministre`, `Ministere`, `Gouvernement`
- [ ] Import depuis JORF (décrets de nomination)
- [ ] Scraper `info.gouv.fr/ministere`
- [ ] Page `/gouvernement` avec organigramme

**Phase 3 : Budget de l'État** (2-3 jours)
- [ ] Import PLF (Projet de Loi de Finances)
- [ ] Visualisations budget par ministère (Treemap, barres)
- [ ] Évolution dépenses/recettes (n-5 ans)
- [ ] Page `/budget` avec filtres

**Phase 4 : Institutions** (optionnel)
- [ ] Conseil Constitutionnel (membres, décisions QPC)
- [ ] Cour des Comptes (rapports annuels)
- [ ] Conseil d'État (avis)

---

### 2.7.2 : 💰 Import Budget de l'État
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1-2 semaines  
**Statut** : ✅ TERMINÉ (01/01/2026)

**Source** : data.gouv.fr - Budget de l'État par programme
- URL : `https://www.data.gouv.fr/fr/datasets/budget-de-letat-par-programme-loi-de-finances/`
- Format : CSV
- Mise à jour : Annuelle (PLF)

**Données à importer** :
- Missions budgétaires (35+)
- Programmes (150+)
- Crédits autorisés / consommés
- Évolution pluriannuelle (n-5 ans)

**Tables à créer** :
```sql
CREATE TABLE budget_missions (
    id SERIAL PRIMARY KEY,
    code VARCHAR(10),
    libelle VARCHAR(255),
    annee INT
);

CREATE TABLE budget_programmes (
    id SERIAL PRIMARY KEY,
    mission_id INT REFERENCES budget_missions(id),
    code VARCHAR(10),
    libelle VARCHAR(255),
    credits_ae DECIMAL(15,2),  -- Autorisations d'engagement
    credits_cp DECIMAL(15,2),  -- Crédits de paiement
    annee INT
);

CREATE TABLE budget_ministeres (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(255),
    budget_total DECIMAL(15,2),
    effectifs INT,
    annee INT
);
```

**Tâches** :
- [ ] Modèles Laravel (BudgetMission, BudgetProgramme, BudgetMinistere)
- [ ] Commande `import:budget-etat`
- [ ] Page `/budget` avec visualisations (Treemap, barres)
- [ ] Filtres par ministère, mission, année
- [ ] Export comparatif année N vs N-1

---

### 2.7.3 : 📊 Import INSEE Complet
**Priorité** : 🔴 CRITIQUE  
**Durée** : 2 semaines  
**Statut** : ✅ TERMINÉ (01/01/2026) - Base régions/départements importée

**Sources** :
- API INSEE : `https://portail-api.insee.fr/`
- Data.gouv.fr : Datasets démographiques

**Données à importer** :
- Population par commune/département/région
- Revenus médians
- Taux de chômage
- Pyramide des âges
- Densité de population
- Évolution démographique

**Enrichissements** :
- Fiches département enrichies
- Comparaison régionale
- Corrélation avec votes

**Tâches** :
- [ ] Inscription API INSEE (clé API)
- [ ] Modèles (DonneesDemographiques, DonneesEconomiques)
- [ ] Commande `import:insee`
- [ ] Enrichissement page Statistiques France
- [ ] Visualisations cartographiques

---

### 2.7.4 : 🏛️ Import Composition Gouvernement
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine  
**Statut** : ✅ TERMINÉ (02/01/2026) - Gouvernements importés + Domaines ministériels

**Sources** :
- info.gouv.fr/ministere (scraping)
- Wikipedia (composition gouvernements)
- JORF (décrets de nomination)
- data.gouv.fr (RNE - élus)

**Données importées** :
- [x] Ministres en fonction (1941 postes importés)
- [x] Ministères et attributions
- [x] Secrétaires d'État
- [x] Historique des gouvernements (50+ gouvernements)
- [x] Photos des ministres (Wikipedia)
- [x] Biographies des ministres (Wikipedia)

**Architecture Domaines Ministériels (V2 - Janvier 2026)** :

Le système utilise maintenant des "domaines ministériels" permanents (Intérieur, Justice, Économie...)
qui servent de catégories de référence à travers les différents gouvernements.

```sql
-- Catégories permanentes (16 domaines)
CREATE TABLE domaines_ministeriels (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(255),              -- Ex: "Intérieur", "Justice"
    slug VARCHAR(100) UNIQUE,
    sigle VARCHAR(20),             -- Ex: "MI", "MJ"
    description TEXT,
    wikipedia_url VARCHAR(500),
    wikipedia_extract TEXT,
    site_web VARCHAR(500),
    couleur VARCHAR(10),
    ...
);

-- Liaison postes → domaines
ALTER TABLE postes_ministeriels 
    ADD COLUMN domaine_ministeriel_id BIGINT REFERENCES domaines_ministeriels(id);
```

**Tâches réalisées** :
- [x] Modèles Laravel (Gouvernement, Ministere, PersonnePolitique, PosteMinisteriel, DomaineMinisteriel)
- [x] Commandes : `import:gouvernement-json`, `import:gouvernement-wikipedia`
- [x] Commande `sync:domaines-ministeriels` (--init, --link, --enrich)
- [x] Page `/gouvernement` avec organigramme et sélection par présidence
- [x] Page `/gouvernement/ministeres` - Liste des 16 domaines ministériels
- [x] Page `/gouvernement/ministeres/{slug}` - Historique complet d'un ministère
- [x] Fiches ministres avec historique des postes
- [x] Photos ministres depuis Wikipedia
- [x] Enrichissement biographies depuis Wikipedia
- [x] Historique des gouvernements (Ve République)
- [x] Statistiques gouvernementales (`/donnees/gouvernements`)

**Tâches restantes** :
- [ ] Liaison avec déclarations HATVP
- [ ] Import logos officiels des ministères
- [ ] Coordonnées complètes (adresse, téléphone)
- [ ] Organigrammes internes des ministères

---

### 2.7.5 : 💰 Import Finances Communales OFGL
**Priorité** : 🟡 HAUTE  
**Durée** : 1-2 semaines  
**Statut** : ✅ Script prêt - Import par département recommandé

**Source** : [OFGL - Observatoire des Finances et de la Gestion publique Locale](https://data.ofgl.fr/)
- API : `https://data.ofgl.fr/api/explore/v2.1/`
- Dataset : `ofgl-base-communes-consolidee`
- Période : 2017-2024
- Format : JSON/CSV via API

**⚠️ Note volume de données** :
- ~35 000 communes × 8 années = ~280 000 records potentiels
- **Recommandation** : Import progressif par département (101 imports séparés)
- Rate limiting intégré (50ms entre chaque requête)

**Données disponibles** :
- 📊 **Comptes consolidés des communes** (BP + BA)
- 💰 **Recettes de fonctionnement** (impôts, dotations, subventions)
- 💸 **Dépenses de fonctionnement** (charges, personnel)
- 🏗️ **Investissements** (équipement, emprunts)
- 📈 **Dotations** (DGF, DSU, DSR, FPIC...)
- 👥 **Population et revenus** (tranches)
- 🗺️ **EPCI, département, région** (agrégations)

**Tables à créer** :
```sql
CREATE TABLE communes_finances (
    id SERIAL PRIMARY KEY,
    code_commune VARCHAR(10),
    nom_commune VARCHAR(255),
    code_epci VARCHAR(20),
    nom_epci VARCHAR(255),
    code_departement VARCHAR(5),
    code_region VARCHAR(5),
    exercice INT,                        -- Année (2017-2024)
    
    -- Recettes
    recettes_fonctionnement DECIMAL(15,2),
    impots_locaux DECIMAL(15,2),
    dotations_subventions DECIMAL(15,2),
    
    -- Dépenses
    depenses_fonctionnement DECIMAL(15,2),
    charges_personnel DECIMAL(15,2),
    achats_services DECIMAL(15,2),
    
    -- Investissement
    depenses_investissement DECIMAL(15,2),
    recettes_investissement DECIMAL(15,2),
    
    -- Soldes
    epargne_brute DECIMAL(15,2),
    capacite_autofinancement DECIMAL(15,2),
    
    -- Indicateurs
    population INT,
    revenu_moyen_habitant DECIMAL(10,2),
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Dotations détaillées par commune
CREATE TABLE communes_dotations (
    id SERIAL PRIMARY KEY,
    code_commune VARCHAR(10),
    exercice INT,
    type_dotation VARCHAR(50),           -- DGF, DSU, DSR, FPIC, etc.
    montant DECIMAL(15,2),
    montant_par_habitant DECIMAL(10,2),
    created_at TIMESTAMP
);
```

**API OFGL - Exemples** :
```bash
# Toutes les communes 2024
curl "https://data.ofgl.fr/api/explore/v2.1/catalog/datasets/ofgl-base-communes-consolidee/records?limit=100&refine=exer:2024"

# Filtrer par département
curl "https://data.ofgl.fr/api/explore/v2.1/catalog/datasets/ofgl-base-communes-consolidee/records?refine=exer:2024&refine=dep_code:39"

# Exporter en CSV
curl "https://data.ofgl.fr/api/explore/v2.1/catalog/datasets/ofgl-base-communes-consolidee/exports/csv?refine=exer:2024"
```

**Tâches** :
- [x] Modèles Laravel (`CommuneBudget`) ✅
- [x] Commande `import:ofgl-budgets` ✅ avec options :
  - `--annee=2024` : Année spécifique
  - `--departement=39` : Filtrer par département
  - `--commune=75056` : Une commune spécifique (test)
  - `--force` : Forcer mise à jour
- [x] Rate limiting intégré (50ms entre requêtes) ✅
- [x] Mapping agrégats OFGL vers colonnes DB ✅
- [ ] Lancer imports par département (à planifier)
- [ ] Migration tables finances communales
- [ ] Page `/collectivites/commune/{code}` enrichie avec onglet Finances
- [ ] Graphiques : évolution recettes/dépenses, comparaison moyenne départementale
- [ ] Enrichissement fiche maire avec budget de la commune
- [ ] Export CSV des données financières

**Visualisations prévues** :
- 📊 Évolution budget sur 5 ans (courbe)
- 🥧 Répartition recettes/dépenses (pie chart)
- 📍 Carte dotations par habitant (choroplèthe)
- 📈 Comparaison avec moyenne départementale/nationale

---

### 2.7.6 : 📸 Photos des Maires
**Priorité** : 🟢 MOYENNE  
**Durée** : 1 semaine  
**Statut** : 📋 Planifié

**Sources possibles** :
- Wikipedia (API Wikidata/Commons)
- Sites mairies officiels
- Réseaux sociaux (validation manuelle)

**Tâches** :
- [x] Migration : ajout champs `photo_url`, `photo_wikipedia_url`
- [ ] Commande `sync:maires-photos` via Wikidata
- [ ] Interface admin pour upload/correction manuelle
- [ ] Affichage photos sur fiches maires

---

## 🌐 PHASE 3 : OPEN DATA & INTÉGRATIONS (T3 2026)
**Objectif** : Enrichir avec des sources externes

### 3.1 : 📚 Intégrations Légifrance
**Priorité** : 🔴 CRITIQUE  
**Durée** : 2 semaines

**Sources** :
- API PISTE (Légifrance) : https://piste.gouv.fr/
- Textes consolidés
- Jurisprudence

**Tâches** :
- [ ] Authentification API PISTE
- [ ] Import textes de loi consolidés
- [ ] Lien vers articles de loi depuis discussions
- [ ] Recherche dans les codes
- [ ] Affichage jurisprudence associée

---

### 3.2 : 📊 Données Open Data Gouvernementales
**Priorité** : 🟡 HAUTE  
**Durée** : 2-3 semaines

**Sources identifiées** :

**Économie** :
- https://data.economie.gouv.fr/api/explore/v2.1/console
- Budget de l'État, dépenses publiques
- Marchés publics

**Santé** :
- https://data.drees.solidarites-sante.gouv.fr/
- Statistiques hospitalières
- Indicateurs de santé publique

**INSEE** :
- Déjà partiellement intégré
- Enrichir avec données économiques

**Tâches** :
- [ ] Connecteurs API pour chaque source
- [ ] Synchronisation périodique
- [ ] Visualisations dédiées
- [ ] Corrélations avec votes parlementaires

---

### 3.3 : ⚖️ Conseil Constitutionnel & Ministères
**Priorité** : 🟢 MOYENNE  
**Durée** : 1-2 semaines

**Sources à explorer** :
- Conseil Constitutionnel : Décisions QPC
- Ministères : Données sectorielles
- Cour des Comptes : Rapports

**Tâches** :
- [ ] Inventaire open data disponible
- [ ] Import décisions CC
- [ ] Lien avec textes de loi censurés/validés

---

### 3.4 : 📰 Papiers de Recherche Open Source
**Priorité** : 🟢 MOYENNE  
**Durée** : 1 semaine

**Sources** :
- HAL (archives-ouvertes.fr)
- OpenEdition
- Cairn (accès libre)

**Tâches** :
- [ ] Recherche par thématique politique
- [ ] Liens vers études sur sujets débattus
- [ ] Citation dans discussions

---

### 3.5 : 🤝 Partenariat Open Data France
**Priorité** : 🟢 MOYENNE

**Contact** : https://opendatafrance.fr

**Objectifs** :
- [ ] Présenter le projet
- [ ] Identifier synergies
- [ ] Accès à ressources/conseils
- [ ] Visibilité dans l'écosystème

---

## 🚀 PHASE 4 : SCALE (T4 2026)
**Objectif** : Structurer le projet et grandir

### 4.1 : 📜 Charte Éthique & Bienséance
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1 semaine

**User Stories** :
- [ ] En tant que nouvel utilisateur, je dois accepter la charte
- [ ] En tant que modérateur, je peux sanctionner les violations
- [ ] En tant qu'utilisateur, je comprends les règles du débat

**Contenu de la charte** :
- [ ] Respect et bienveillance
- [ ] Pas de diffamation ni injures
- [ ] Sources et vérification
- [ ] Transparence sur les conflits d'intérêts
- [ ] Protection des données personnelles
- [ ] Sanctions progressives

**Tâches** :
- [ ] Page dédiée `/charte`
- [ ] Modal d'acceptation à l'inscription
- [ ] Rappel périodique
- [ ] Lien vers charte dans footer

---

### 4.3 : 🎮 Révision Gamification
**Priorité** : 🟡 HAUTE  
**Durée** : 1-2 semaines

**Objectif** : Encourager la participation constructive

**Mécaniques** :
- [ ] **Badges** : Contributeur, Fact-checker, Médiateur
- [ ] **Niveaux** : Basés sur la qualité (pas quantité)
- [ ] **Points** : Pour actions positives uniquement
- [ ] **Classements** : Optionnels, par thématique
- [ ] **Récompenses** : Visibilité accrue, accès anticipé

**Anti-gaming** :
- [ ] Pas de points pour volume de posts
- [ ] Valoriser les sources
- [ ] Valoriser les réponses constructives
- [ ] Pénaliser les signalements abusifs

---

### 4.4 : 🔍 Révision Complète du Site
**Priorité** : 🟡 HAUTE  
**Durée** : 2 semaines

**Audit à réaliser** :
- [ ] Performance (Lighthouse)
- [ ] Accessibilité (WCAG 2.1)
- [ ] SEO (meta, sitemap, structured data)
- [ ] Sécurité (headers, OWASP)
- [ ] UX (tests utilisateurs)
- [ ] Mobile (responsive parfait)

**Corrections** :
- [ ] Optimisation images
- [ ] Lazy loading
- [ ] Cache stratégique
- [ ] Compression assets

---

### 4.5 : 🇫🇷 FranceConnect+
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine

**Tâches** :
- [ ] Inscription partenaires.franceconnect.gouv.fr
- [ ] Intégration OAuth2
- [ ] Badge "Vérifié par l'État"
- [ ] Droits étendus pour utilisateurs vérifiés

---

## 📊 SOURCES DE DONNÉES - RÉCAPITULATIF

### Déjà intégrées ✅
| Source | Type | Statut |
|--------|------|--------|
| Assemblée Nationale | XML/JSON | ✅ Complet |
| Sénat | SQL/XML | ✅ Complet |
| HATVP | XML | ✅ Complet |
| INSEE | API | ✅ Partiel |
| Wikipedia | API | ✅ Photos/extraits |
| DILA/JORF | XML (data.gouv.fr) | ✅ Lois, décrets, ordonnances |
| Élysée | HTML (scraping) | ✅ Agenda présidentiel |

### À intégrer 🔄 - PRIORITÉ T1 2026
| Source | URL | Priorité | Données |
|--------|-----|----------|---------|
| **Budget de l'État** | data.gouv.fr/budget | 🔴 CRITIQUE | PLF, dépenses/recettes par ministère |
| **INSEE Démographie** | api.insee.fr | 🔴 CRITIQUE | Population, revenus, emploi par commune |
| **Gouvernement** | info.gouv.fr/ministere | 🔴 CRITIQUE | Ministres, ministères, organigramme |
| Légifrance (PISTE) | piste.gouv.fr | 🟡 Haute | Textes consolidés (accès restreint) |
| data.economie.gouv.fr | API v2.1 | 🟡 Haute | Marchés publics, finances |
| Résultats électoraux | data.gouv.fr/elections | 🟡 Haute | Historique votes par bureau |
| **OFGL Communes** | data.ofgl.fr | 🟡 Haute | Budgets, dotations, recettes communales |
| Conseil Constitutionnel | conseil-constitutionnel.fr | 🟢 Moyenne | Décisions QPC |
| Cour des Comptes | ccomptes.fr | 🟢 Moyenne | Rapports annuels |

### À explorer 🔍
| Source | Contact | Potentiel |
|--------|---------|-----------|
| Open Data France | opendatafrance.fr | Partenariat |
| data.gouv.fr | Catalogue général | Nouveaux datasets |
| HAL / OpenEdition | Recherche académique | Études politiques |
| HATVP complet | hatvp.fr/open-data | Déclarations exhaustives |

---

## 🎯 PRIORITÉS IMMÉDIATES (Décembre 2025 - Janvier 2026)

### ✅ Accompli semaine 1 (23-29 décembre)
1. ✅ Corriger migrations commissions/groupes
2. ✅ Refonte menu & navigation avec mega-menu
3. ✅ Breadcrumbs + raccourcis clavier (Cmd+K, G+H, G+D, G+S, G+L)
4. ✅ Refonte page d'accueil + dashboard utilisateur
5. ✅ Calendrier législatif (AN + Sénat + Élysée)
6. ✅ Dashboard admin avec gestion imports
7. ✅ Cycle de vie des lois avec timeline visuelle
8. ✅ Import Journal Officiel (DILA/data.gouv.fr)
9. ✅ Système de tags thématiques (30 catégories Sénat)
10. ✅ Hub législation unifié `/legislation`
11. ✅ Liaison scrutins AN ↔ lois

### ✅ Accompli semaine 2 (30 décembre)
12. ✅ Vote citoyen sur les lois avec score de popularité
13. ✅ Liaison débats citoyens ↔ lois
14. ✅ Amendements cliquables (liens AN/Sénat)
15. ✅ Scrutins dans le corps principal de la page loi
16. ✅ Parlementaires impliqués (rapporteurs, auteurs amendements)
17. ✅ Stats pré-calculées élus (Députés/Sénateurs/Maires)
18. ✅ Correction durée des lois (valeurs négatives)

### ✅ Accompli semaine 3 (31 décembre)
19. ✅ Fix menu Données (dropdown non cliquable)
20. ✅ Recherche globale améliorée (CommandPalette + API)
21. ✅ Logos officiels SVG (AN, Sénat) dans le menu
22. ✅ Dashboard widgets cliquables + calcul scrutins
23. ✅ Hero banner dashboard uniformisé

### ✅ Accompli semaine 4 (2 janvier 2026)
24. ✅ **Domaines Ministériels** : Architecture avec 16 catégories permanentes
    - Table `domaines_ministeriels` + FK sur postes/ministères
    - Commande `sync:domaines-ministeriels` (--init, --link, --enrich)
    - Pages `/gouvernement/ministeres` et `/gouvernement/ministeres/{slug}`
    - Admin : catégorisation des postes ministériels
25. ✅ **Statistiques gouvernementales** : Page `/donnees/gouvernements/statistiques`
    - Stats par président, évolution parité, durée moyenne
    - Déplacée dans menu Données
26. ✅ **Recherche globale étendue** : Ministres et Présidents dans les résultats
27. ✅ **Dashboard Élu complet** :
    - Routes `/elu/dashboard`, `/elu/interpellations`, `/elu/stats`, `/elu/ma-fiche`
    - Stats avancées : délai moyen de réponse, taux de réponse, évolution mensuelle
    - Page statistiques élu avec thématiques et conseils
    - Lien "Espace Élu" dans le menu pour élus vérifiés
    - Compte démo élu : `demo-elu@civicdash.fr` / `DemoElu2026!`
28. ✅ **Corrections diverses** :
    - Couleur Élysée moins criarde sur calendrier (amber → slate)
    - Photos auteurs sur Questions au Gouvernement
    - Slug unique ministres (fix doublon)
    - Page votes sénateurs : hero banner + breadcrumb modernisés
29. ✅ **Migration users** : Champs élu ajoutés (elu_type, elu_ref, is_verified_elu, etc.)

### ✅ Accompli semaine 5 (3 janvier 2026)
30. ✅ **Double Authentification (2FA OTP)** :
    - Package `pragmarx/google2fa-laravel` + QR code
    - Contrôleur `TwoFactorAuthController` complet
    - Pages Vue : Index, Enable, RecoveryCodes, Challenge
    - Middleware `two-factor` sur routes admin, élu, modération
    - Bandeau de sécurité sur le profil utilisateur
    - Codes de récupération (8 codes, usage unique)
    - Migration : champs `two_factor_*` sur users
31. ✅ **Modération automatique des mots bannis** :
    - Tables `banned_words`, `nice_words`, `moderation_logs`
    - Service `ContentModerationService` avec détection variantes (m3rde, p*tain)
    - Remplacement humoristique par mots gentils/emojis aléatoires
    - Intégration dans TopicService (création topics/posts)
    - Interface admin `/admin/moderation/words` avec :
      - Dashboard statistiques (remplacements aujourd'hui/semaine)
      - Gestion mots bannis (catégories, sévérité)
      - Gestion mots gentils (emojis, animaux, compliments)
      - Outil de test en temps réel
      - Historique des remplacements
    - Commande `php artisan moderation:seed`
32. ✅ **Système de notifications complet** :
    - Service `NotificationService` centralisé
    - Centre de notifications `/notifications` avec :
      - Liste paginée avec filtres (toutes, non lues, à acquitter)
      - Statuts : non lue → lue → acquittée → traitée
      - Actions rapides (marquer comme lu, acquitter, supprimer)
    - Préférences utilisateur `/notifications/preferences` :
      - Canaux : notifications site, notifications email
      - Fréquence email (instantané, quotidien, hebdomadaire)
      - Types : réponses, mentions, votes, législatif, système, etc.
      - Options avancées (regroupement, heures calmes)
    - Dropdown notifications dans le header (cloche avec badge)
    - Intégration avec le modèle existant `notification_preferences`
33. ✅ **Questions Écrites Sénat** :
    - Import depuis data.senat.fr (289 433 questions depuis 1978)
    - Commande `import:questions-senat` avec options :
      - `--limit=N` : Limite de questions
      - `--year=YYYY` : Année spécifique
      - `--sync-only` : Synchro sans téléchargement
    - Pages Vue complètes :
      - `/questions/senat` : Liste avec filtres (type, thème, statut)
      - `/questions/senat/{numero}` : Détail avec réponse ministérielle
      - `/questions/senat/stats` : Statistiques détaillées
      - `/questions/senat/senateur/{matricule}` : Questions par sénateur
    - Hero banner rose/fuchsia cohérent avec l'identité Sénat
34. ✅ **Whitelist de domaines & Références internes** :
    - Configuration `config/moderation.php` avec :
      - Liste des domaines autorisés (*.gouv.fr, insee.fr, europa.eu, etc.)
      - Patterns de références internes (@loi:, @depute:, @senateur:, etc.)
    - Service `ContentModerationService` étendu :
      - `sanitizeLinks()` : Supprime les liens non whitelistés
      - `parseInternalReferences()` : Transforme les @mentions en liens
      - `fullModerate()` : Modération complète (mots + liens + références)
      - `validate()` : Validation sans modification
    - API `/api/content-moderation/*` :
      - `GET /whitelisted-domains` : Liste des domaines autorisés
      - `GET /reference-formats` : Formats des mentions supportées
      - `POST /validate` : Validation de contenu
      - `POST /preview` : Aperçu après modération
      - `POST /resolve-references` : Résolution des références
    - Intégration dans le wizard de création d'idées
35. ✅ **Notifications emails aux élus interpellés** :
    - Mail `InterpellationNotificationMail` avec template Blade responsive
    - Service `EluNotificationService` pour envoi coordonné in-app + email
    - Colonnes `notified_at`, `email_sent_at`, `viewed_at` sur `topic_elus`
    - Notification à l'auteur lorsque l'élu répond
36. ✅ **Interface réponses élus améliorée** :
    - 4 modèles de réponse pré-rédigés (prise en compte, détaillée, orientation, législatif)
    - 6 conseils de rédaction affichables/masquables
    - Indicateur d'urgence avec couleur (récente/à traiter/urgente)
    - Prévisualisation live de la réponse
    - Compteur de caractères et validation
37. ✅ **Composants Vue pour références enrichies** :
    - `ReferencePreview.vue` : Card preview au hover avec photo, groupe, lien
    - `RichContent.vue` : Parser de contenu avec badges colorés pour @mentions
    - `ReferenceInput.vue` : Textarea avec autocomplete intelligent
    - API `/api/references/preview/{type}/{identifier}`
    - Support : @depute:, @senateur:, @maire:, @loi:, @scrutin:, @amendement:
38. ✅ **Visite guidée enrichie** :
    - Composable `useGuidedTour.js` avec 8 tours interactifs
    - Tours : Bienvenue, Dashboard, Participation, Lois, Députés, Sénateurs, Gouvernement, Élu
    - Composant `TourOverlay.vue` : Interface élégante avec barre de progression
    - Composant `TourMenu.vue` : Menu dropdown avec sélection des tours
    - Sauvegarde progression en localStorage
    - Attributs `data-tour` sur tous les éléments clés
    - Intégration dans le header (icône 🎯)
39. ✅ **Gamification enrichie (38 badges)** :
    - 14 nouveaux badges ajoutés (total : 38)
    - Catégories : Participation, Législatif, Budget, Social, Engagement, Expertise
    - Raretés : Commun, Rare, Épique, Légendaire
    - Badges secrets pour les accomplissements exceptionnels
    - Seeder `AchievementSeeder` enrichi

### ✅ Accompli semaine 6 (7 janvier 2026)
40. ✅ **Infrastructure CI/CD professionnelle** :
    - Workflow Git : `feature` → `dev` → `main` → `tag`
    - GitHub Actions : Tests automatiques, Deploy DEV/PROD
    - Release v1.0.0 publiée 🎉
    - Documentation complète (ARCHITECTURE_DOCKER, GIT_WORKFLOW, GITHUB_SETUP)
41. ✅ **Vérification fonctionnalités existantes** :
    - "Mes Représentants" (`MesRepresentants.vue`) → CONFIRMÉ FONCTIONNEL
    - Statistiques H/F parité → CONFIRMÉ FONCTIONNEL
    - Débat structuré (threads, votes, signalements) → CONFIRMÉ FONCTIONNEL
    - Import OFGL → SCRIPT PRÊT (`import:ofgl-budgets`)

### ✅ Accompli semaine 7 (8 janvier 2026)
42. ✅ **Import Débats Sénat** : Comptes-rendus des séances publiques
    - Source : `https://data.senat.fr/data/debats/debats.zip` (31 Mo)
    - Tables : `senat_debats`, `senat_sections_discussion`, `senat_sections_diverses`
    - Tables : `senat_interventions_legislatives`, `senat_interventions_diverses`
    - Tables : `senat_types_section`, `senat_lectures_debats`
    - Migration complète `2026_01_08_140000_create_debats_senat_tables.php`
    - Commande `senat:import-debats` avec options :
      - `--fresh` : Vider les tables avant import
      - `--download` : Télécharger le fichier avant import
      - `--since=YYYY-MM-DD` : Importer depuis une date
      - `--year=YYYY` : Année spécifique
    - Modèles Laravel : `SenatDebat`, `SenatSectionDiscussion`, `SenatSectionDiverse`
    - Modèles Laravel : `SenatInterventionLegislative`, `SenatInterventionDiverse`
    - Modèles Laravel : `SenatTypeSection`, `SenatLectureDebat`
    - Liaison interventions ↔ sénateurs via matricule
43. ✅ **Amélioration contextualisation amendements** :
    - Champs `dispositif` et `expose` déjà présents sur `AmendementAN`
    - Relations vers textes législatifs et dossiers
44. ✅ **Améliorations UI/UX** :
    - Composant `Toggle.vue` compact et élégant
    - Composant `Checkbox.vue` avec tailles configurables (sm/md/lg)
    - Meilleur espacement sur Login, NotificationSettings, EluFollowButton
45. ✅ **Fix Suivi d'élus** : Correction requêtes SQL et middleware CSRF

### 🔄 En cours
1. ✅ Refonte système Idées/Propositions citoyennes → **TERMINÉ**
2. ✅ Interpellation des élus → **TERMINÉ**
3. ✅ Menu "État" restructuré → **TERMINÉ**
4. ✅ Notifications élus & Interface réponses → **TERMINÉ**
5. ✅ Visite guidée enrichie → **TERMINÉ 03/01/2026**
6. ✅ Gamification (38 badges) → **TERMINÉ 03/01/2026**
7. 📝 Import OFGL communes → **Script prêt, import progressif recommandé**
8. 🔄 Export iCal calendrier → **À développer**

### 🔴 Priorité T1 2026 - Données Gouvernementales
1. [x] **Import Budget de l'État** (data.gouv.fr PLF) ✅ *Terminé 01/01/2026*
2. [x] **Import INSEE complet** (démographie, économie) ✅ *Terminé 01/01/2026*
3. [x] **Import Gouvernement** (ministres, ministères) ✅ *Terminé 01/01/2026*
4. [x] Menu "État" unifié (Parlement, Gouvernement, Élysée) ✅ *Terminé 31/12/2025*
5. [x] Pages hub par institution ✅

### Prochaines étapes (Janvier-Février 2026)
1. [x] Wizard création idée (5 étapes) ✅
2. [x] Liaison idées ↔ élus (interpellation) ✅
3. [ ] Suggestions IA pour catégories/tags
4. [x] Questions Écrites Sénat (import base SQL) ✅
5. [ ] Recherche globale Meilisearch multi-modèles
6. [ ] Résultats électoraux historiques
7. [ ] **Export iCal calendrier** (AN + Sénat + Élysée) → 🔴 Priorité
8. [ ] Import OFGL par département (progressif)

### 🔐 Sécurité & Authentification (T1 2026) ✅ IMPLÉMENTÉ
1. [x] **Double Authentification (2FA OTP)** : Pour utilisateurs non-FranceConnect
   - Package `pragmarx/google2fa-laravel` + `bacon/bacon-qr-code`
   - Configuration 2FA avec QR code et clé manuelle
   - Codes de récupération (8 codes, usage unique)
   - Challenge 2FA lors de la connexion
   - Middleware `two-factor` pour routes sensibles (admin, elu, moderation)
   - Pages Vue : Index, Enable, RecoveryCodes, Challenge
2. [x] **Bandeau de sécurité sur le profil**
   - Recommandation 2FA pour élus et admins sans 2FA
   - Info FranceConnect pour utilisateurs déjà sécurisés
   - Lien rapide vers configuration 2FA
3. [x] **Migration users** : Champs 2FA ajoutés
   - `two_factor_secret`, `two_factor_recovery_codes` (chiffrés)
   - `two_factor_enabled`, `two_factor_confirmed_at`

### 🛡️ Modération & Qualité du contenu (T1 2026) ✅ IMPLÉMENTÉ
1. [x] **Liste de mots interdits** : Système de ban words (insultes FR, spam)
   - Table `banned_words` avec catégories (insultes, spam, politique extrême, racisme, violence)
   - Table `nice_words` avec mots gentils de remplacement (emojis, animaux, compliments)
   - Remplacement automatique humoristique ("crétin" → "🌈", "merde" → "petit chaton")
   - 35 mots bannis + 58 mots gentils par défaut
   - Validation côté serveur avant publication (Topics, Posts)
   - Blocage si racisme/violence grave
   - Interface admin `/admin/moderation/words` pour gérer les mots
   - Historique des remplacements (table `moderation_logs`)
   - Commande `php artisan moderation:seed` pour initialiser
2. [x] **Whitelist de domaines** : Seuls les liens officiels sont autorisés ✅ *Terminé 03/01/2026*
   - Configuration centralisée `config/moderation.php`
   - Domaines whitelistés : `*.gouv.fr`, `insee.fr`, `assemblee-nationale.fr`, `senat.fr`, `europa.eu`
   - Sanitization automatique des contenus (liens non autorisés remplacés)
   - API `/api/content-moderation/whitelisted-domains` pour consulter la liste
   - Intégration dans `ParticipationController::ideasStore()`
3. [x] **Références internes** : Mentions automatiques ✅ *Terminé 03/01/2026*
   - Parser `@loi:`, `@depute:`, `@senateur:`, `@maire:`, `@scrutin:`, `@amendement:`
   - Résolution automatique vers les entités existantes
   - Transformation en liens cliquables HTML
   - API `/api/content-moderation/resolve-references`
   - Validation des références existantes
4. [ ] **À venir** :
   - Affichage enrichi (card preview au hover)
   - Notifications aux élus mentionnés
   - Interface utilisateur pour insérer des références

### 👔 Espace Élus (T1 2026) ✅ IMPLÉMENTÉ
1. [x] **Authentification élu** : Vérification identité
   - Validation par email officiel (@assemblee-nationale.fr, @senat.fr)
   - Badge "Compte vérifié" sur profil
   - Champs users : `elu_type`, `elu_ref`, `is_verified_elu`, `verified_at`
   - Compte démo : `demo-elu@civicdash.fr` / `DemoElu2026!`
2. [x] **Dashboard élu** : Interface dédiée `/elu/dashboard`
   - Vue des interpellations reçues avec filtres
   - Stats temps réel : total, en attente, répondues, taux de réponse, délai moyen
   - Lien "Espace Élu" dans menu (desktop + mobile)
   - Actions rapides : Ma fiche, Interpellations, Stats
3. [x] **Réponses aux interpellations** : `/elu/interpellations`
   - Réponse officielle avec horodatage
   - Possibilité de refuser avec justification
   - Page détail avec topic complet et commentaires
   - Statuts : pending, viewed, answered, declined
4. [x] **Statistiques élu** : `/elu/stats`
   - Score de performance (Excellent/Très bien/Bien/À améliorer)
   - Évolution mensuelle (graphique barres)
   - Thématiques populaires (top 10)
   - Conseils pour améliorer son score
5. [ ] **Communication encadrée** (à venir) :
   - Pas de messages privés (tout est public)
   - Template de réponse suggéré
   - Notifications email aux citoyens

---

## 📈 MÉTRIQUES DE SUCCÈS

### T1 2026 - Fondations
- 🎯 Dashboard admin fonctionnel
- 🎯 Calendrier législatif opérationnel
- 🎯 Navigation refaite
- 🎯 100 utilisateurs beta

### T2 2026 - Participation
- 🎯 Vote citoyen lancé
- 🎯 1000 votes citoyens
- 🎯 Forum actif (100+ topics)
- 🎯 500 utilisateurs

### T3 2026 - Données
- 🎯 5 sources open data intégrées
- 🎯 API Légifrance connectée
- 🎯 2000 utilisateurs

### T4 2026 - Scale
- 🎯 Association créée
- 🎯 Charte acceptée par 100% users
- 🎯 5000 utilisateurs
- 🎯 Couverture presse

---

## 🤝 CONTRIBUTION

### Comment contribuer ?
1. **Code** : Fork + PR sur GitHub
2. **Design** : Propositions UI/UX
3. **Données** : Identifier nouvelles sources
4. **Traduction** : i18n
5. **Documentation** : Améliorer les docs
6. **Tests** : Signaler bugs

### Contact
- GitHub : github.com/CivicDash
- Email : contact@civicdash.fr (à créer)
- Discord : (à créer)

---

## 💙 VISION

CivicDash vise à devenir **la référence citoyenne** pour comprendre et participer à la vie démocratique française.

**Notre mission** : Rendre la politique accessible, transparente et participative.

**Nos valeurs** :
- 🔍 **Transparence** : Toutes les données sont publiques
- 🤝 **Neutralité** : Pas de biais partisan
- 🔓 **Open Source** : Code ouvert à tous
- 🇫🇷 **Citoyenneté** : Pour et par les citoyens

---

**Maintenu par** : CivicDash Core Team / Civis Consilium  
**Version** : 2.7  
**Dernière mise à jour** : 8 janvier 2026  
**Licence** : AGPL-3.0 Open Source
