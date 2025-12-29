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

### 🔧 Infrastructure
- [x] **Docker** : Environnement containerisé
- [x] **PostgreSQL** : Base de données avec vues SQL
- [x] **Synchronisation automatique** : Commandes Artisan
- [x] **Photos officielles** : AN et Sénat prioritaires

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
- [ ] **Questions Écrites** (Sénat) - Import base SQL externe
- [ ] **Textes Akoma Ntoso** (Sénat) - Documents législatifs
- [ ] Proportion hommes/femmes (statistiques)

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
- [ ] Export iCal / Google Calendar
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

### 1.6 : 🚀 Migration PHP 8.5 + FrankenPHP
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine

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
- [ ] Créer branche `feature/frankenphp`
- [ ] Mettre à jour Dockerfile vers `dunglas/frankenphp:php8.5-alpine`
- [ ] Configurer Laravel Octane avec driver FrankenPHP
- [ ] Tester imports lourds (amendements, réunions) - détection memory leaks
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

### 2.1.6 : 📚 Enrichissement Légifrance (Futur)
**Priorité** : 🟢 MOYENNE  
**Statut** : ⏸️ En attente (API restreinte)

**Note** : L'API Légifrance sur PISTE nécessite une accréditation spécifique (éditeurs juridiques, administrations). Pour un accès complet aux textes consolidés et à la jurisprudence, explorer :
- Partenariat avec un éditeur accrédité
- Demande d'accès institutionnel
- Scraping Légifrance.gouv.fr (CGU à vérifier)

---

### 2.2 : 🗳️ Vote Citoyen sur Textes de Loi
**Priorité** : 🔴 CRITIQUE  
**Durée** : 2 semaines

**User Stories** :
- [ ] En tant que citoyen, je veux donner mon avis sur un texte
- [ ] En tant que citoyen, je veux voir comment les élus ont voté
- [ ] En tant que citoyen, je veux comparer le vote citoyen vs parlementaire

**Fonctionnalités** :
- [ ] Vote Pour / Contre / Abstention sur textes en cours
- [ ] Affichage comparatif : 
  - 📊 Vote citoyen : 65% Pour
  - 🏛️ Vote AN : 48% Pour
  - 🏛️ Vote Sénat : 52% Pour
- [ ] Graphiques de divergence
- [ ] Historique de mes votes
- [ ] Partage social

**Règles** :
- [ ] 1 vote par citoyen par texte
- [ ] Vote modifiable jusqu'à la clôture
- [ ] Résultats visibles après clôture
- [ ] Authentification requise (FranceConnect optionnel)

---

### 2.3 : 📊 Sondages Ouverts
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine

**User Stories** :
- [ ] En tant que citoyen, je veux créer un sondage sur un sujet
- [ ] En tant que citoyen, je veux participer aux sondages
- [ ] En tant qu'admin, je veux modérer les sondages

**Tâches** :
- [ ] Création de sondages (question + options)
- [ ] Types : choix unique, choix multiple, échelle
- [ ] Durée configurable
- [ ] Résultats en temps réel
- [ ] Modération avant publication
- [ ] Export résultats

---

### 2.4 : 💬 Refonte Forum & Topics
**Priorité** : 🟡 HAUTE  
**Durée** : 2 semaines

**User Stories** :
- [ ] En tant que citoyen, je veux discuter de sujets locaux
- [ ] En tant que citoyen, je veux taguer mes discussions
- [ ] En tant que citoyen, je veux rechercher par région/ville

**Améliorations** :
- [ ] **Tags prédéfinis** : Thématiques (santé, éducation, transport...)
- [ ] **Géolocalisation** : Région / Département / Ville / Code postal
- [ ] **Recherche avancée** : Par tags, lieu, date
- [ ] **Lien avec textes de loi** : Associer discussion à un dossier législatif
- [ ] **Upvote/Downvote** : Mise en avant des contributions
- [ ] **Réponses imbriquées** : Threads de discussion

---

### 2.5 : 💰 Budget Participatif Approfondi
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

### À intégrer 🔄
| Source | URL | Priorité |
|--------|-----|----------|
| Légifrance (PISTE) | piste.gouv.fr | 🔴 Critique |
| data.economie.gouv.fr | API v2.1 | 🟡 Haute |
| data.drees.solidarites-sante.gouv.fr | API | 🟡 Haute |
| Conseil Constitutionnel | À identifier | 🟢 Moyenne |
| Cour des Comptes | À identifier | 🟢 Moyenne |

### À explorer 🔍
| Source | Contact |
|--------|---------|
| Open Data France | opendatafrance.fr |
| data.gouv.fr | Catalogue général |
| HAL / OpenEdition | Recherche académique |

---

## 🎯 PRIORITÉS IMMÉDIATES (Décembre 2025)

### Cette semaine
1. ✅ Corriger migrations commissions/groupes
2. [ ] Pousser et déployer corrections
3. [ ] Vérifier imports HATVP sur serveur
4. [ ] Documenter état actuel

### Semaine prochaine
1. [ ] Import Questions au Gouvernement
2. [ ] Tests de non-régression
3. [ ] Début refonte menu

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

**Maintenu par** : CivicDash Core Team  
**Version** : 2.1  
**Dernière mise à jour** : 29 décembre 2025  
**Licence** : AGPL-3.0 Open Source
