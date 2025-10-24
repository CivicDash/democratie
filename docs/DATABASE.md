# Schéma de base de données - CivicDash

## 📋 Vue d'ensemble

Le schéma de base de données CivicDash est organisé en 6 domaines fonctionnels :

1. **Identité & Territoires** (3 tables)
2. **Forum & Discussions** (3 tables)
3. **Modération** (2 tables)
4. **Vote Anonyme** (2 tables) ⚠️ Architecture spéciale
5. **Budget Participatif** (4 tables)
6. **Documents Vérifiés** (2 tables)

**Total : 16 tables métier** + 3 tables Laravel (users, cache, jobs)

---

## 🗂️ Domaine 1 : Identité & Territoires

### `territories_regions`
Régions françaises (INSEE).

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| code | string(2) | Code INSEE (ex: 11, 93) |
| name | string | Nom (ex: Île-de-France) |

### `territories_departments`
Départements français (INSEE).

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| code | string(3) | Code INSEE (ex: 75, 2A) |
| name | string | Nom (ex: Paris) |
| region_id | bigint | FK → regions |

### `profiles`
Profils citoyens avec pseudonymes et scope territorial.

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| display_name | string | Pseudonyme aléatoire (Citoyen123) |
| citizen_ref_hash | string | **Hash numéro sécu + PEPPER** |
| scope | enum | national / region / dept |
| region_id | bigint | FK → regions (nullable) |
| department_id | bigint | FK → departments (nullable) |
| is_verified | boolean | Identité vérifiée (FranceConnect+) |
| verified_at | timestamp | Date vérification |

**🔐 Sécurité** : `citizen_ref_hash` est unique et permet d'éviter les doublons sans stocker le numéro de sécu en clair.

---

## 💬 Domaine 2 : Forum & Discussions

### `topics`
Sujets de débat, projets de loi, référendums.

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| title | string | Titre |
| description | text | Markdown restreint |
| scope | enum | national / region / dept |
| region_id | bigint | FK → regions (nullable) |
| department_id | bigint | FK → departments (nullable) |
| type | enum | debate / bill / referendum |
| status | enum | draft / open / closed / archived |
| author_id | bigint | FK → users (legislator/admin) |
| **has_ballot** | boolean | Active le scrutin |
| **voting_opens_at** | timestamp | Ouverture scrutin |
| **voting_deadline_at** | timestamp | Fermeture + révélation |
| **ballot_type** | enum | yes_no / multiple_choice / preferential |
| **ballot_options** | json | Options (si multiple_choice) |

### `posts`
Messages de débat (avec threading).

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| topic_id | bigint | FK → topics |
| user_id | bigint | FK → users |
| parent_id | bigint | FK → posts (nullable, threading) |
| content | text | Markdown restreint |
| is_official | boolean | Post officiel (legislator/state) |
| upvotes | int | Compteur votes positifs |
| downvotes | int | Compteur votes négatifs |
| is_pinned | boolean | Épinglé |
| is_hidden | boolean | Masqué par modération |
| hidden_reason | string | Raison masquage |

### `post_votes`
Votes up/down sur posts.

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| post_id | bigint | FK → posts |
| user_id | bigint | FK → users |
| vote | enum | up / down |

**Contrainte** : `UNIQUE(post_id, user_id)` → 1 vote par user par post.

---

## 🛡️ Domaine 3 : Modération

### `reports`
Signalements de contenus.

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| reporter_id | bigint | FK → users |
| reportable_type | string | posts / topics / ... (polymorphic) |
| reportable_id | bigint | ID du contenu |
| reason | enum | spam / harassment / misinformation / ... |
| description | text | Détails |
| status | enum | pending / reviewing / resolved / dismissed |
| moderator_id | bigint | FK → users (nullable) |
| moderator_notes | text | Notes modérateur |
| resolved_at | timestamp | Date résolution |

### `sanctions`
Sanctions (avertissements, mutes, bans).

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users (sanctionné) |
| moderator_id | bigint | FK → users (modérateur) |
| report_id | bigint | FK → reports (nullable) |
| type | enum | warning / mute / ban |
| reason | text | Motif |
| starts_at | timestamp | Début |
| expires_at | timestamp | Fin (NULL = permanent) |
| is_active | boolean | Active |

---

## 🗳️ Domaine 4 : Vote Anonyme ⚠️

### `ballot_tokens`
Jetons éphémères pour voter (liaison identité ↔ vote).

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| topic_id | bigint | FK → topics |
| user_id | bigint | FK → users |
| token | string(128) | **Jeton opaque signé à usage unique** |
| consumed | boolean | Jeton consommé |
| consumed_at | timestamp | Date consommation |
| expires_at | timestamp | Expiration (= voting_deadline_at) |

**Contrainte** : `UNIQUE(topic_id, user_id)` → 1 jeton par user par scrutin.

### `topic_ballots`
Bulletins de vote anonymes **SANS user_id**.

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| topic_id | bigint | FK → topics |
| **⚠️ PAS de user_id** | | **Anonymat garanti** |
| encrypted_vote | string | **Vote chiffré (Laravel Crypt)** |
| vote_hash | string(64) | Hash du vote (unicité) |
| cast_at | timestamp | Date du vote |

**🔐 Architecture anonyme** :
1. User obtient un `ballot_token`
2. User vote avec le token (API)
3. Token consommé, `topic_ballot` créé **sans user_id**
4. Liaison identité/vote **supprimée**
5. Résultats révélés uniquement après `voting_deadline_at`

**Contrainte** : `UNIQUE(vote_hash)` → évite les votes dupliqués.

---

## 💰 Domaine 5 : Budget Participatif

### `sectors`
Secteurs budgétaires (éducation, santé, etc.).

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| code | string(50) | Code unique (EDU, HEALTH) |
| name | string | Nom (Éducation, Santé) |
| description | text | Description |
| icon | string(50) | Nom icône (graduation-cap) |
| color | string(7) | Couleur hex (#0055a4) |
| min_percent | decimal(5,2) | % minimum allouable |
| max_percent | decimal(5,2) | % maximum allouable |
| display_order | int | Ordre affichage |
| is_active | boolean | Actif |

### `user_allocations`
Répartitions budgétaires des citoyens.

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| sector_id | bigint | FK → sectors |
| percent | decimal(5,2) | % alloué |

**Contraintes** :
- `UNIQUE(user_id, sector_id)` → 1 allocation par user par secteur
- Somme des `percent` par user = 100%
- `percent` entre `min_percent` et `max_percent` du secteur

### `public_revenue`
Recettes publiques (transparence).

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| year | int | Année fiscale |
| scope | enum | national / region / dept |
| region_id | bigint | FK → regions (nullable) |
| department_id | bigint | FK → departments (nullable) |
| category | string(100) | Catégorie (TVA, IRPP) |
| amount | decimal(15,2) | Montant (€) |
| source | string(255) | Source données (INSEE, DGFiP) |

### `public_spend`
Dépenses publiques par secteur.

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| year | int | Année fiscale |
| scope | enum | national / region / dept |
| region_id | bigint | FK → regions (nullable) |
| department_id | bigint | FK → departments (nullable) |
| sector_id | bigint | FK → sectors |
| amount | decimal(15,2) | Montant dépensé (€) |
| program | string(255) | Programme spécifique |
| source | string(255) | Source données |

---

## 📄 Domaine 6 : Documents Vérifiés

### `documents`
Pièces jointes (uploadables par legislator/state).

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| title | string | Titre |
| description | text | Description |
| filename | string | Nom fichier |
| path | string | Chemin stockage |
| mime_type | string(100) | Type MIME |
| size | bigint | Taille (bytes) |
| hash | string(64) | SHA256 du fichier |
| documentable_type | string | topics / posts / ... (polymorphic) |
| documentable_id | bigint | ID du contenu |
| uploader_id | bigint | FK → users (legislator/state) |
| status | enum | pending / verified / rejected |
| is_public | boolean | Public |

### `verifications`
Vérifications par journalistes/ONG.

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| document_id | bigint | FK → documents |
| verifier_id | bigint | FK → users (journalist/ong) |
| status | enum | verified / rejected / needs_review |
| notes | text | Commentaires |
| metadata | json | Données supplémentaires |

---

## 🔗 Relations clés

### Séparation identité/vote
```
users → ballot_tokens → [TOKEN] → topic_ballots (PAS de user_id)
        ↓ consumed=true
```

### Forum hiérarchique
```
topics
  ├─ posts (parent_id = NULL)
  │   └─ posts (parent_id → post parent)
  └─ post_votes
```

### Modération
```
posts/topics → reports → sanctions → users
```

### Budget
```
users → user_allocations → sectors
sectors → public_spend (comparaison)
```

---

## 📊 Indexes importants

- **profiles** : `citizen_ref_hash` (unique), `[scope, region_id, department_id]`
- **topics** : `[scope, status]`, `voting_deadline_at`
- **posts** : `[topic_id, created_at]`
- **ballot_tokens** : `token` (unique), `[topic_id, consumed]`
- **topic_ballots** : `topic_id`, `vote_hash` (unique)
- **user_allocations** : `[user_id, sector_id]` (unique)

---

## 🚀 Prochaines étapes

1. ✅ Migrations créées
2. 🔄 Créer les modèles Eloquent
3. 🔄 Créer les seeders (rôles, territoires, secteurs)
4. 🔄 Créer les factories pour tests
5. 🔄 Implémenter les services métier (BallotService, BudgetService)

---

**Note** : Ce schéma respecte strictement les principes CivicDash :
- ✅ Anonymat des votes garanti (pas de user_id dans topic_ballots)
- ✅ Pas d'images/liens citoyens (validation serveur)
- ✅ Scope territorial (national/region/dept)
- ✅ Modération workflow complet
- ✅ Transparence budgétaire

