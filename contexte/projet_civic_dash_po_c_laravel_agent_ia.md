# CivicDash — PoC open‑source (Laravel) pour débat citoyen, vote anonyme, et répartition budgétaire participative

> Objectif : livrer un **proof‑of‑concept** rapidement opérationnel (OSS) qui démontre :
> 1) un forum sans starification (pas de followers/DM/avatar),
> 2) un **vote anonyme** (up/down + scrutins) avec **délais/échéances**,
> 3) une **affectation de son impôt** (simulation PoC) par secteurs avec **planchers/plafonds** dynamiques,
> 4) une **transparence des recettes/dépenses** (flux quasi temps réel simulés),
> 5) une **gouvernance locale** (région/département),
> 6) un **processus de modération** outillé + signalements, sans images ni liens côté citoyens,
> 7) intégration future **FranceConnect+** (PoC : identifiants génériques).

---

## 1) Périmètre & principes

### 1.1 PoC (3–6 semaines)
- Auth PoC : comptes génériques (email+mdp) via Laravel Breeze, RBAC simple (citoyen, modérateur, journaliste/ONG, législateur, admin).
- Forum : sujets (nationaux/locaux), messages texte brut (markdown léger sans liens/images), filtres région/département.
- Vote : up/down sur messages + **scrutin par sujet** (révélé à l’échéance). 1 vote/citoyen/sujet.
- Anonymisation : séparation **Identité ↔ Vote** (pseudonymes PoC), hash + pepper ; stockage des votes séparé.
- Budgets : **simulateur** d’affectation personnelle (vectoriel % par secteur) avec bornes min/max par secteur ; agrégations globales.
- Transparence : ingestion CSV simulée (recettes/dépenses), graphiques et delta vs préférences citoyennes.
- Modération : workflow de signalement, file de tri, sanctions « logiques » (mute temporaire) ; pas d’amendes réelles au PoC.
- Docs vérifiées : pièces attachées **uniquement** par rôles Législateur/État après **vérification journaliste/ONG** (statut requis).
- OSS : licence **AGPL‑3.0** recommandée (ou EUPL v1.2), contribution guide, code formatter, tests de base.

### 1.2 V1 (post‑PoC)
- Auth OIDC **FranceConnect+** (production) ; mappage territorial automatisé par INSEE/GeoAPI.
- Votes renforcés (commit‑reveal/mixnet, bulletin chiffré). Journal d’audit inviolable (append‑only) + key rotation.
- Données fiscales : connecteurs officiels (si disponibles) ou dépôt volontaire (import justificatif) – conformité CNIL/DPIA.
- Anti‑brigading & intégrité : rate‑limit adaptatif, preuve d’unicité, heuristiques d’anomalies.
- Observabilité : métriques, journaux, traçage ; publication de tableaux de bord publics.

---

## 2) Architecture (vue d’ensemble)

**Stack** : Laravel 11 (PHP 8.3+), PostgreSQL 15, Redis, Horizon (files d’attente), Scout + Meilisearch (recherche), Pest (tests), Inertia + Vue/Tailwind (UI) ou Blade + Livewire.

**Services** :
- `web` (Laravel), `queue` (Horizon), `db` (Postgres), `cache` (Redis), `search` (Meilisearch), `worker` (scheduler).

**Sécurité & confidentialité** :
- Séparation de domaines :
  - DB `core` (utilisateurs/profils/territoires)
  - DB `vote` (bulletins chiffrés, *no foreign key* vers core ; réconciliation via token éphémère opaque)
- Secret management : `.env` chiffrés, **pepper** en KMS/HashiCorp Vault (PoC : variable d’environnement).
- Chiffrement applicatif (Laravel Crypt) pour bulletins + tables sensibles.

---

## 3) Modèle de données (schéma logique minimal)

### 3.1 Identité & territoires
- `users` {id, email, password_hash (PoC), role, region_code, dept_code, status, created_at}
- `profiles` {user_id, pseudo (aléatoire), citizen_ref_hash, tos_accepted_at}
- `territories_regions` {code, name}
- `territories_departments` {code, name, region_code}

### 3.2 Forum & modération
- `topics` {id, scope (national/reg/dept), region_code?, dept_code?, title, body_md, created_by, kind (debate|law_proposal), allows_docs (bool), voting_deadline_at, status}
- `posts` {id, topic_id, body_md, created_by, created_at}
- `post_votes` {id, post_id, voter_user_id, value(+1/-1), created_at}  // PoC : non secret
- `reports` {id, target_type(post/topic), target_id, reason_text, created_by, status(pending/reviewed/accepted/rejected)}
- `sanctions` {id, user_id, type(mute/ban), reason, starts_at, ends_at, created_by}
- Rôles : `roles` {id, name} ; `user_roles` {user_id, role_id}

### 3.3 Scrutins & anonymisation
- `topic_ballots` {id, topic_id, ballot_ciphertext, salt, created_at} // pas de user_id ; traçabilité via `ballot_tokens`
- `ballot_tokens` {id, topic_id, user_id, token, consumed_at} // délivré 1x ; détruit après vote
- (Option V1) `commitments` {id, topic_id, user_id, commitment_hash, revealed_at}

### 3.4 Budgets & préférences
- `sectors` {code, name, min_pct, max_pct}
- `user_allocations` {user_id, sector_code, pct, updated_at}
- `public_revenue` {id, date, amount, source}
- `public_spend` {id, date, sector_code, amount, source}

### 3.5 Documents & vérifications
- `documents` {id, topic_id, kind (fact_sheet|draft_law|dataset), url/storage_path, added_by, added_at}
- `verifications` {id, document_id, by_role(journalist/ong), status(pending/approved/rejected), notes}

### 3.6 Journal & audit
- `audit_logs` {id, actor_id?, action, entity, entity_id, meta_json, created_at}

---

## 4) Règles métier clés

- **Pas d’images/liens** côté citoyens : validation serveur + sanitizer Markdown strict ; exceptions pour `roles ∈ {legislator,state}`.
- **Votes anonymes** :
  - Le serveur émet un `ballot_token` signé/éphémère (1 par user/sujet).
  - Le client soumet un bulletin chiffré (PoC: chiffrement app) + le token ; serveur vérifie + enregistre dans `topic_ballots` sans user_id.
  - **Résultat** : agrégation uniquement **après `voting_deadline_at`**.
- **Up/Down votes** sur posts : visibles en continu, mais non utilisés comme « score social » (pas de tri par karma d’auteur).
- **Affectations budgetaires** :
  - Somme des % par user = 100 ; respect min/max sectoriels.
  - Agrégé → **profil souhaité** global ; on affiche l’écart avec `public_spend` courant.
- **Modération** : signalements → file → décision → sanction (mute/ban) ; **amende** hors PoC.
- **Gouvernance locale** : accès lecture/écriture aux sujets de sa région/département ; national accessible à tous.

---

## 5) API (esquisse REST)

`POST /auth/register` (PoC) — `POST /auth/login`

`GET /territories/regions` — `GET /territories/departments?region=XX`

`GET /topics?scope=national|region:XX|dept:YY` — `POST /topics` (roles: citizen min)

`GET /topics/{id}` — `GET /topics/{id}/posts` — `POST /topics/{id}/posts`

`POST /posts/{id}/vote {value:+1|-1}`

`POST /topics/{id}/ballot/token` — délivrer un token si pas encore voté

`POST /topics/{id}/ballot/submit {token, ballot_ciphertext}`

`GET /topics/{id}/results` — **HTTP 403** si avant `deadline`; sinon agrégat

`POST /reports {target_type, target_id, reason_text}` — `POST /sanctions` (modérateur)

`POST /topics/{id}/documents` (législateur/État) — `POST /documents/{id}/verify` (journaliste/ONG)

`GET /budget/sectors` — `POST /budget/allocations` — `GET /budget/aggregate`

`GET /finance/revenue` — `GET /finance/spend`

---

## 6) UI (parcours)

- **Accueil** : explication, charte, stats globales, CTA « Explorer ».
- **Explorer** : filtres (national / ma région / mon département), recherche.
- **Sujet** : description, documents vérifiés, débat (posts), up/down, **compte à rebours** jusqu’à la révélation des résultats.
- **Vote** : bouton « Obtenir jeton » → « Voter » (bulletin anonyme).
- **Budget** : sliders par secteur (min/max), donut « mes préférences », heatmap écart vs dépenses.
- **Modération** : file de signalements, actions (mute/ban), journaux.
- **Transparence** : tableaux recettes/dépenses, sources, mises à jour.

---

## 7) Intégration FranceConnect+ (plan)

- **PoC** : Auth locale.
- **V1** : OIDC (PKCE), scopes minimaux (identité, territorialité si disponible via API autorisée). **Aucun** identifiant fiscal stocké en clair ; si une preuve d’éligibilité est nécessaire, utiliser **preuve d’unicité** sans persister l’identité.
- **Données d’impôt** : pas d’API publique simple ; prévoir soit **déclaration volontaire** (upload), soit connecteur officiel si autorisé. Toujours passer par **DPIA** et **registre de traitement** (CNIL).

---

## 8) Sécurité & conformité (PoC pragmatique)

- Password hashing Argon2id ; 2FA TOTP (poC optionnel).
- CSRF, CORS strict, rate limits (Throttle API + Redis), captcha invisible sur endpoints sensibles.
- Validation forte (Markdown whitelist), désactivation des URL automatiques.
- Logs d’audit immuables (append‑only table) + export public non identifiant.
- **DPIA** light PoC, politique de conservation courte, anonymisation des IP par défaut.

---

## 9) Mécanisme d’anonymisation (PoC → V1)

- PoC : `profiles.citizen_ref_hash = H(pepper || user_id || random_salt)`
- Token de vote : opaque, signé, usage unique, stocké côté serveur avec TTL.
- Bulletin : `Encrypt( choice, key_app )` ; publication agrégée après échéance.
- V1 : schéma **commit‑reveal** (hash du choix + sel → reveal à échéance) **ou** mixnet simple ; clé de déchiffrement publiée à l’échéance.

---

## 10) Moteur d’allocation budgétaire

- Contraintes : `min_pct[sector] ≤ p_i[sector] ≤ max_pct[sector]`, `Σ p_i = 100`.
- Agrégation : `P_global = (1/N) Σ p_i` (pondération égale au PoC). Affichage **écart** vs `spend_actuel`.
- Scénarios : par territoire, par période ; export CSV.

---

## 11) Modération & sanctions

- Signalement citoyen : motif libre + catégorisation (injure, haine, spam, infox…).
- Workflow : *pending → review → (accepted|rejected)* ; auto‑mute si X signalements concordants (seuils ajustables).
- Sanctions PoC : mute 24/72h, ban 7j ; **amendes** non activées au PoC (cadre légal nécessaire).

---

## 12) Plan de livraison (roadmap)

**Semaine 1** :
- Squelette Laravel, Breeze, RBAC, migrations core, territoires seed INSEE (dump), pages publiques.

**Semaine 2** :
- Forum (topics/posts), sanitizer, votes post, recherche, pagination ; composants UI.

**Semaine 3** :
- Scrutins anonymes (tokens + bulletins chiffrés), deadline + révélation.

**Semaine 4** :
- Budget : secteurs + sliders + agrégation ; transparence : ingestion CSV + graphiques.

**Semaine 5** :
- Modération : signalements, files, sanctions ; documents vérifiés (rôles spéciaux).

**Semaine 6** :
- Harden/QA : rate‑limit, logs, tests E2E, CI/CD, docs OSS.

---

## 13) DevOps & qualité

- **Docker Compose** (dev) ; **GitLab CI** : lint+tests+build ; review apps.
- Seeds & factories (Faker FR) ; jeux de données de démo.
- Observabilité : Laravel Telescope (dev), Horizon dashboard, health checks.

**.env-clés** : DB, REDIS, APP_KEY, PEPPER, SEARCH, FEATURE_FLAGS (images=false, links=false, docs_whitelist=true).

---

## 14) Licence & gouvernance OSS

- **AGPL‑3.0** (force contribution des forks serveurs), code of conduct, contribution guide, issues templatisées, Roadmap publique.

---

## 15) Exemple de migrations (extraits)

```sql
-- sectors
(code text primary key, name text not null, min_pct numeric not null default 0, max_pct numeric not null default 100)
```

```sql
-- topic_ballots (sans user_id)
(id bigserial pk, topic_id bigint not null, ballot_ciphertext bytea not null, salt bytea not null, created_at timestamptz not null)
```

```sql
-- ballot_tokens
(id bigserial pk, topic_id bigint not null, user_id bigint not null, token uuid unique not null, consumed_at timestamptz)
```

---

## 16) Backlog initial (issues)

- [ ] Auth PoC + RBAC
- [ ] Territoires seed
- [ ] Forum (topics/posts) + sanitizer
- [ ] Up/Down votes
- [ ] Scrutin anonyme + deadline + reveal
- [ ] Budget sliders + contraintes + agrégation
- [ ] Ingestion CSV dépenses/recettes + charts
- [ ] Modération + sanctions
- [ ] Docs vérifiées (workflow)
- [ ] API tokens, rate limits
- [ ] CI/CD + tests
- [ ] Docs & seed demo

---

## 17) Agent IA d’assistance (spécification)

### 17.1 Rôles & capacités
- **Architecte** : propose schémas, migrations, endpoints ; vérifie cohérence.
- **Assistant développeur** : génère contrôleurs, policies, tests, seeders ; reviews PR.
- **Modération assistée** : classifie signalements (NLP local/offline), suggère actions.
- **Data steward** : vérifie CSV recettes/dépenses, détecte anomalies, met à jour schémas.
- **Rédacteur OSS** : README, CONTRIBUTING, SECURITY.md.

### 17.2 Outils (connecteurs)
- GitLab API (issues/MR), PostgreSQL (lecture dev), Static Analyzer (PHPStan), Test Runner (Pest), Linter (PHP-CS-Fixer), File System.

### 17.3 Prompt système (exemple)
```
Tu es l’Agent CivicDash. Tu respectes strictement :
- Pas d’images/liens pour contenus citoyens ;
- Confidentialité des votes ;
- Conformité min/max budgets ;
- Rôles et permissions ;
- Style Laravel 11, PHP 8.3, Postgres, Redis.
Quand tu écris du code : tests d’abord (Pest), puis contrôleurs/policies, puis docs.
```

### 17.4 Playbooks (exemples)
- **Créer un sujet** : générer migration + contrôleur + policy + test `TopicTest::can_create_with_deadline`.
- **Scrutin** : implémenter service `BallotService` (émettre token, vérifier unicité, consommer token, chiffrer bulletin, révéler agrégat).
- **Budget** : contrainte simplex (normalisation des %), test de respect min/max, agrégation par territoire.
- **Modération** : état `report.status` transitions contrôlées par policy + tests.

---

## 18) Prochaines actions (concrètes)

1. Initialiser dépôt OSS (licence AGPL + templates).
2. Générer projet Laravel 11 + Breeze + roles (spatie/laravel-permission).
3. Créer migrations : users/profiles/territoires/topics/posts/votes/reports/sanctions/sectors/allocations/ballots/tokens.
4. Implémenter **scrutin anonyme** (token + bulletin chiffré) + endpoint reveal.
5. UI de base (Inertia/Tailwind) : Explore, Topic, Vote, Budget, Transparence, Modération.
6. Seeds démo (territoires FR, secteurs : education, écologie, santé, armée, etc.).
7. CI GitLab (lint+tests+build) + Docker Compose.

---

*Ce document sert de base de travail. Nous pourrons itérer : préciser migrations, écrire le premier contrôleur, et brancher l’agent IA sur GitLab.*

