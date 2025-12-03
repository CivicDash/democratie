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
**Statut** : 🔄 En cours

- [x] Correction doublons sénateurs (vue DISTINCT)
- [x] Libellés commissions et groupes parlementaires
- [x] Photos officielles prioritaires
- [x] Affichage compact mandats/commissions
- [ ] Vérifier import HATVP rémunérations serveur
- [ ] Import amendements AN complet
- [ ] Tests de régression

### 0.2 : 📊 Données Complémentaires
**Priorité** : 🟡 HAUTE

- [ ] **Questions au Gouvernement** (AN) - Import XML
- [ ] **Questions Écrites** (Sénat) - Import
- [ ] **Textes Akoma Ntoso** (Sénat) - Documents législatifs
- [ ] Proportion hommes/femmes (statistiques)

---

## 🏠 PHASE 1 : REFONTE UX & ADMIN (T1 2026)
**Objectif** : Améliorer l'expérience utilisateur et les outils admin

### 1.1 : 🎨 Refonte Menu & Navigation
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1 semaine

**User Stories** :
- [ ] En tant qu'utilisateur, je veux une navigation claire et intuitive
- [ ] En tant qu'utilisateur mobile, je veux un menu adapté au touch
- [ ] En tant qu'utilisateur, je veux accéder rapidement aux sections clés

**Tâches** :
- [ ] Restructurer la navigation principale
- [ ] Mega-menu avec catégories (Parlement, Données, Participation)
- [ ] Breadcrumbs sur toutes les pages
- [ ] Raccourcis clavier (recherche, navigation)
- [ ] Menu contextuel selon la page

---

### 1.2 : 🏠 Refonte Page d'Accueil
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1 semaine

**User Stories** :
- [ ] En tant que visiteur, je veux comprendre immédiatement le projet
- [ ] En tant que citoyen, je veux voir l'actualité parlementaire récente
- [ ] En tant qu'utilisateur, je veux accéder aux données les plus consultées

**Tâches** :
- [ ] Hero section avec message clair et CTA
- [ ] Derniers scrutins (AN + Sénat)
- [ ] Derniers amendements adoptés
- [ ] Statistiques clés en temps réel
- [ ] Carte interactive en preview
- [ ] Section "Comment ça marche"
- [ ] Témoignages / Cas d'usage

---

### 1.3 : 🛠️ Dashboard Admin
**Priorité** : 🔴 CRITIQUE  
**Durée** : 1-2 semaines

**User Stories** :
- [ ] En tant qu'admin, je veux voir l'état de santé des imports
- [ ] En tant qu'admin, je veux modérer le contenu utilisateur
- [ ] En tant qu'admin, je veux des statistiques d'utilisation

**Tâches** :

**Statistiques** :
- [ ] Nombre d'utilisateurs inscrits / actifs
- [ ] Pages les plus consultées
- [ ] Recherches populaires
- [ ] Temps de réponse API

**Imports & Sync** :
- [ ] Tableau des derniers imports (date, durée, statut)
- [ ] Boutons pour relancer manuellement
- [ ] Logs d'erreurs consultables
- [ ] Alertes si import échoue

**Modération** :
- [ ] File de signalements
- [ ] Actions rapides (supprimer, avertir, bannir)
- [ ] Historique des actions
- [ ] Statistiques modération

---

### 1.4 : 📅 Calendrier Législatif
**Priorité** : 🟡 HAUTE  
**Durée** : 1 semaine

**User Stories** :
- [ ] En tant que citoyen, je veux voir les prochains débats
- [ ] En tant que citoyen, je veux être notifié des votes importants
- [ ] En tant que journaliste, je veux suivre l'agenda parlementaire

**Sources de données** :
- Agenda AN : https://www2.assemblee-nationale.fr/agendas/
- Agenda Sénat : https://www.senat.fr/ordre-du-jour/

**Tâches** :
- [ ] Composant calendrier Vue (vue mois/semaine/jour)
- [ ] Import agenda AN (scraping ou API)
- [ ] Import agenda Sénat
- [ ] Filtres par type (débat, vote, commission)
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

## 📜 PHASE 2 : PARCOURS LÉGISLATIF & VOTE CITOYEN (T2 2026)
**Objectif** : Suivre les textes de loi et permettre l'expression citoyenne

### 2.1 : 📜 Vie d'un Texte de Loi
**Priorité** : 🔴 CRITIQUE  
**Durée** : 2-3 semaines

**User Stories** :
- [ ] En tant que citoyen, je veux comprendre le parcours d'une loi
- [ ] En tant que citoyen, je veux voir les amendements déposés
- [ ] En tant que citoyen, je veux comparer les versions du texte

**Parcours législatif à visualiser** :
```
📋 Dépôt → 🏛️ Commission → 📖 1ère lecture AN → 📖 1ère lecture Sénat
        → 🔄 Navette → 📖 2ème lecture → ⚖️ CMP → 🏛️ Conseil Constitutionnel
        → 📜 Promulgation → 📰 JO
```

**Tâches** :
- [ ] Timeline visuelle du parcours
- [ ] Temps passé à chaque étape
- [ ] Versions du texte (diff)
- [ ] Amendements par étape
- [ ] Votes par chambre
- [ ] Lien vers texte final (Légifrance)
- [ ] Intégration Légifrance API

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
**Version** : 2.0  
**Dernière mise à jour** : 3 décembre 2025  
**Licence** : AGPL-3.0 Open Source
