# 🎬 Mode Démonstration CivicDash

## 📋 Vue d'ensemble

Le mode démonstration de CivicDash permet de configurer rapidement l'application avec des **données synthétiques réalistes** pour présenter toutes les fonctionnalités de la plateforme.

---

## 🚀 Installation rapide

### Prérequis

- PHP 8.2+
- Composer
- Base de données (PostgreSQL recommandé)
- Meilisearch (pour la recherche)

### Configuration en une commande

```bash
php artisan demo:setup --fresh
```

Cette commande va :
1. ✅ Réinitialiser la base de données (migrations)
2. ✅ Charger les données de référence (territoires, thématiques, etc.)
3. ✅ Créer les comptes de test
4. ✅ Générer 50 citoyens avec profils
5. ✅ Générer 20 députés fictifs
6. ✅ Créer 30 propositions de loi réalistes
7. ✅ Créer 25 topics de débat
8. ✅ Générer 200+ posts et réponses
9. ✅ Créer 1500+ votes citoyens
10. ✅ Ajouter des événements législatifs
11. ✅ Indexer les données pour la recherche

⏱️ **Durée estimée** : 2-3 minutes

---

## 🔐 Comptes de test

### Comptes administratifs

| Rôle | Email | Mot de passe | Permissions |
|------|-------|--------------|-------------|
| **Admin** | `admin@civicdash.fr` | `password` | Accès complet |
| **Modérateur** | `moderator@civicdash.fr` | `password` | Modération des contenus |
| **Législateur** | `legislator@civicdash.fr` | `password` | Dépôt de propositions |
| **Journaliste** | `journalist@civicdash.fr` | `password` | Accès presse |
| **Citoyen** | `citizen@civicdash.fr` | `password` | Compte citoyen standard |

### Comptes de démonstration

#### 50 Citoyens
- **Emails** : `citoyen1@demo.civicdash.fr` à `citoyen50@demo.civicdash.fr`
- **Mot de passe** : `demo2025`
- **Caractéristiques** :
  - Pseudonymes aléatoires (anonymisation)
  - Répartition sur toutes les régions françaises
  - 70% de comptes vérifiés
  - Scopes variés (national, régional, départemental)

#### 50 Députés
- **Emails** : `depute1@demo.assemblee-nationale.fr` à `depute50@demo.assemblee-nationale.fr`
- **Mot de passe** : `demo2025`
- **Caractéristiques** :
  - Noms réalistes (Sophie Martineau, Jean-Pierre Dubois, etc.)
  - Répartis dans **tous les groupes parlementaires réels** (Renaissance, RN, LFI-NFP, LR, PS, Horizons, Écologistes, Démocrate, LIOT, GDR)
  - Circonscriptions réelles
  - Profils publics (pas d'anonymisation)
  - Auteurs de propositions de loi

#### 16 Groupes Parlementaires
- **10 groupes Assemblée Nationale** : Renaissance, RN, LFI-NFP, LR, Socialistes, Horizons, Écologistes, Démocrate, LIOT, GDR
- **6 groupes Sénat** : LR, UC, SER, RDSE, CRCE, RDPI
- **Données réelles** : sigles, couleurs, nombres de membres, présidents

---

## 📊 Données générées

### Propositions de loi (30)

**10 propositions détaillées** avec :
- Titre, résumé et texte intégral
- Auteurs et groupes parlementaires
- Étapes législatives
- Résultats de votes
- Thématiques associées
- Statuts variés : `en_commission`, `en_discussion`, `adopte`, `rejete`

**Exemples** :
- "Proposition de loi visant à renforcer la transparence de la vie publique"
- "Projet de loi relatif à la transition énergétique et écologique"
- "Proposition de loi pour l'amélioration de l'accès aux soins"
- "Projet de loi de finances pour 2025"

**20 propositions supplémentaires** plus courtes pour enrichir le catalogue.

### Topics de débat (25)

**5 topics détaillés** avec scrutins :
- "Faut-il instaurer un revenu universel en France ?" (vote oui/non)
- "Réforme de la fiscalité écologique : quelles mesures prioritaires ?"
- "Budget participatif 2025 : vos priorités pour l'éducation" (choix multiples)
- "Gratuité des transports en commun : pour ou contre ?"
- "Quelle politique migratoire pour la France ?"

**20 topics supplémentaires** sur des sujets variés (IA, inégalités, démocratie locale, etc.)

### Posts et discussions (200+)

- 5 à 15 posts par topic
- 0 à 5 réponses par post
- Contenus réalistes et variés
- Votes (upvotes/downvotes) aléatoires

### Votes législatifs et groupes parlementaires

**Votes législatifs** :
- Votes solennels pour chaque proposition adoptée ou rejetée
- Résultats détaillés (pour/contre/abstention)
- Dates de vote réalistes

**Votes par groupe parlementaire** :
- Vote détaillé de chaque groupe politique
- Logique de vote cohérente selon :
  - Position politique (gauche/centre/droite)
  - Thématique de la proposition
  - Discipline de groupe
- Permet d'analyser les positions politiques

### Amendements (100+)

- 3 à 8 amendements par proposition en discussion
- Auteurs députés avec groupes parlementaires
- Statuts variés : déposé, adopté, rejeté, retiré
- Objets, dispositifs et exposés réalistes

### Votes citoyens (1500+)

- 20 à 100 votes par proposition de loi
- Répartition : pour / contre / abstention
- Commentaires optionnels (30% des votes)

### Événements législatifs (14)

**4 événements à venir** :
- Session de questions au gouvernement
- Commission des finances - Examen du PLF 2025
- Débat sur la transition énergétique
- Vote solennel - Loi sur l'égalité salariale

**10 événements passés** pour historique

### Références juridiques (3)

- Code civil - Article 1
- Loi Informatique et Libertés (Loi n°78-17)
- Constitution - Article 1

### Hashtags (8)

| Hashtag | Usage | Trending | Officiel |
|---------|-------|----------|----------|
| #DémocratieParticipative | 150 | ✅ | ✅ |
| #TransitionÉcologique | 120 | ✅ | ❌ |
| #Justicesociale | 95 | ❌ | ❌ |
| #Éducation | 80 | ❌ | ✅ |
| #Santé | 75 | ❌ | ✅ |
| #Numérique | 60 | ✅ | ❌ |
| #Budget2025 | 55 | ✅ | ✅ |
| #Transparence | 50 | ❌ | ✅ |

---

## 🛠️ Options de la commande

### Réinitialisation complète

```bash
php artisan demo:setup --fresh
```

Supprime toutes les données existantes et recrée la base de données.

### Sans confirmation

```bash
php artisan demo:setup --force
```

Exécute la commande sans demander de confirmation (utile pour les scripts CI/CD).

### Combinaison

```bash
php artisan demo:setup --fresh --force
```

---

## 📦 Seeders disponibles

Si vous souhaitez charger les données séparément :

```bash
# Données de référence uniquement
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=TerritoriesSeeder
php artisan db:seed --class=SectorsSeeder
php artisan db:seed --class=ThematiqueLegislationSeeder
php artisan db:seed --class=PolicyVersionSeeder
php artisan db:seed --class=AchievementSeeder

# Données de démonstration uniquement
php artisan db:seed --class=DemoDataSeeder
```

---

## 🔍 Indexation de la recherche

Après avoir chargé les données, indexez-les pour Meilisearch :

```bash
php artisan scout:import "App\Models\PropositionLoi"
php artisan scout:import "App\Models\Topic"
php artisan scout:import "App\Models\Post"
```

---

## 🎯 Cas d'usage du mode démo

### 1. Présentation client / investisseur
- Données réalistes et cohérentes
- Tous les rôles utilisateurs représentés
- Historique d'activité crédible

### 2. Tests fonctionnels
- 50 citoyens pour tester les interactions
- 20 députés pour tester les workflows législatifs
- Données variées pour tester les filtres et recherches

### 3. Développement frontend
- Données immédiatement disponibles
- Pas besoin de créer manuellement des contenus
- Cas d'usage réels (votes, débats, propositions)

### 4. Formation / Documentation
- Environnement pré-configuré pour les tutoriels
- Comptes de test pour chaque rôle
- Scénarios d'utilisation prêts à l'emploi

---

## ⚠️ Avertissements

### Sécurité

- **NE JAMAIS** utiliser le mode démo en production
- Les mots de passe sont simples (`password`, `demo2025`)
- Les données sont publiques et non confidentielles

### Performance

- La génération complète prend 2-3 minutes
- L'indexation Meilisearch peut prendre du temps
- Pensez à optimiser le cache après génération

### Base de données

- L'option `--fresh` **supprime toutes les données**
- Faites une sauvegarde avant d'exécuter en environnement de test

---

## 🧪 Vérification de l'installation

### Connexion

1. Démarrez le serveur :
```bash
php artisan serve
```

2. Accédez à : `http://localhost:8000`

3. Connectez-vous avec un compte de test

### Vérifications

- ✅ Voir les 30 propositions de loi sur le dashboard
- ✅ Consulter les 25 topics de débat
- ✅ Voter sur une proposition
- ✅ Poster un commentaire
- ✅ Utiliser la recherche
- ✅ Consulter les événements législatifs
- ✅ Voir les hashtags trending

---

## 🔧 Dépannage

### Erreur "Class DemoDataSeeder not found"

```bash
composer dump-autoload
```

### Erreur Meilisearch

Vérifiez que Meilisearch est démarré :
```bash
meilisearch --master-key=YOUR_KEY
```

### Erreur PEPPER

Ajoutez dans `.env` :
```env
PEPPER=votre_cle_secrete_32_caracteres_minimum
```

Générez une clé :
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Base de données vide après seeding

Vérifiez les logs :
```bash
tail -f storage/logs/laravel.log
```

---

## 📚 Ressources

- [Documentation complète](../README.md)
- [Architecture de la base de données](./DATABASE.md)
- [Modèles Eloquent](./MODELS.md)
- [API Documentation](./API.md)

---

## 🤝 Contribution

Pour améliorer le mode démo :

1. Ajoutez des données plus variées dans `DemoDataSeeder.php`
2. Créez des scénarios d'usage spécifiques
3. Proposez des comptes de test supplémentaires
4. Améliorez le réalisme des contenus générés

---

**Développé avec ❤️ par Civis-Consilium**

*Association française Loi 1901 pour la démocratie participative*

