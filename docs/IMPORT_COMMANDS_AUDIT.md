## Audit détaillé des commandes d'import

Ce document synthétise l'ordre strict, les dépendances et les prérequis des
imports planifiés par le scheduler Laravel.

## Source de vérité
- Planning : `routes/console.php`
- Orchestrateur manuel : `scripts/sync-all-data.sh`
- Orchestrateur complet : `scripts/import_parlement_master.sh`

## Pré-requis transverses
- Base PostgreSQL accessible et à jour (migrations appliquées).
- Stockage disponible et writable (`storage/app`, `storage/logs`).
- Accès réseau aux sources publiques (AN, Sénat, HATVP, Wikipedia).
- Variables d’environnement configurées pour AN/Sénat/HATVP si nécessaire.

## Ordre strict recommandé (scheduler Laravel)

### 1) Données de base (01:00–02:00)
1. `import:acteurs-an`
   - Crée/maj `acteurs_an` (base pour mandats, scrutins, votes).
2. `import:organes-an`
   - Crée/maj `organes_an` (groupes, commissions).
3. `senat:sync`
   - Synchronisation Sénat (SQL + Akoma Ntoso selon options internes).

### 2) Dossiers législatifs (02:00–03:00)
4. `import:dossiers-textes-an`
5. `import:dossiers-senat`
6. `import:jorf`

### 3) Amendements (03:00–03:30)
7. `import:amendements-an`
8. `import:amendements-senat`

### 4) Scrutins et votes (03:30–04:00)
9. `import:scrutins-an`
10. `extract:votes-individuels-an`
    - Dépendance explicite : nécessite `import:scrutins-an`.
    - Erreur si aucun scrutin présent.

### 5) Statistiques (04:00–05:00)
11. `dashboard:calculate-stats --force`
12. `calculate:parlementaires-stats --force`
13. `calculate:lois-stats --force`
14. `calculate:elus-global-stats --force`

### 6) Agenda & calendrier (05:00–06:00)
15. `import:reunions-an`
16. `import:agenda-senat`
17. `import:agenda-elysee`
18. `sync:debats-calendar`
19. `sync:evenements-an`

### 7) Questions au gouvernement (06:00–07:00)
20. `import:questions-an`
21. `import:questions-senat`

### 8) Enrichissements (07:00–08:00)
22. `sync:wikipedia-personnes --limit=50`
23. `enrich:deputes-votes`
24. `enrich:senateurs-votes`

### 9) Notifications élus suivis (horaire)
25. `elu:process-activities`
    - Dépendance : `votes_individuels_an`, `scrutins_an`, `acteurs_an`.
    - Doit être exécuté après import scrutins + extraction votes.

### 10) Hebdomadaire (dimanche)
26. `sync:all`
    - Orchestrateur partiel : Sénat (SQL), Akoma Ntoso, HATVP, photos, questions AN.
    - Diffère des imports journaliers (attention aux doubles exécutions).
27. `scrutins:recalculate-totals`
28. `senat:import-debats --download`

### 11) Candidatures (quotidien)
29. `candidatures:send-reminders`
    - Dépend des données d’élections et calendriers.

## Dépendances explicites relevées
- `import:mandats-an` : nécessite `import:acteurs-an`.
- `extract:votes-individuels-an` : nécessite `import:scrutins-an`.
- `elu:process-activities` : nécessite scrutins + votes individuels + acteurs.

## Pré-requis par commande (exemples)
- `import:questions-an` : nécessite l’archive XML téléchargée.
- `import:questions-senat` : nécessite un dump SQL disponible.
- `import:akoma-ntoso` : nécessite accès aux flux Akoma Ntoso Sénat.
- `import:deputes-wikipedia` / `sync:wikipedia-personnes` : accès API MediaWiki.

## Orchestrateurs manuels (usage hors scheduler)
- `scripts/sync-all-data.sh` : AN (an:sync), Sénat (import:senat-sql + akoma ntoso),
  HATVP, enrichissements, cache:clear.
- `scripts/import_parlement_master.sh` : import complet AN+Sénat (long, --fresh).

## Risques maintenabilité
- `sync:all` ne reproduit pas le flux journalier complet (différences d’étapes).
- Deux orchestrateurs concurrents peuvent exécuter des imports redondants.
- Les scripts historiques doivent rester “manuel uniquement”.
