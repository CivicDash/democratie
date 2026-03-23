# 🔄 Synchronisation des Données Parlementaires

Ce document décrit le système de synchronisation automatique des données depuis les sources officielles.

> **Dernière mise à jour** : Décembre 2025

## 📋 Vue d'ensemble

Le système synchronise les données de trois sources :

| Source | Format | Fréquence recommandée | Données |
|--------|--------|----------------------|---------|
| **Assemblée Nationale** | XML/JSON | Quotidienne | Députés, scrutins, amendements, organes |
| **Sénat** | PostgreSQL + XML | Quotidienne | Sénateurs, votes, amendements, textes |
| **HATVP** | XML | Hebdomadaire | Déclarations d'intérêts et patrimoine |
| **Wikipedia** | API | Hebdomadaire | Photos, extraits biographiques |

---

## ⚡ Commande Principale

```bash
# Synchronisation complète (tout en une commande)
php artisan sync:all

# Options disponibles
php artisan sync:all --quick          # Mode rapide (données récentes uniquement)
php artisan sync:all --senat          # Sénat uniquement
php artisan sync:all --an             # Assemblée uniquement
php artisan sync:all --hatvp          # HATVP uniquement
php artisan sync:all --photos         # Photos Wikipedia uniquement
php artisan sync:all --fresh          # Réimport complet (LONG!)
php artisan sync:all --dry-run        # Simulation sans exécution
```

---

## 🚀 Utilisation Rapide

### Script de synchronisation globale

```bash
# Synchronisation complète
./scripts/sync-all-data.sh

# Assemblée Nationale uniquement
./scripts/sync-all-data.sh --an

# Sénat uniquement
./scripts/sync-all-data.sh --senat

# HATVP uniquement
./scripts/sync-all-data.sh --hatvp

# Mode simulation (dry-run)
./scripts/sync-all-data.sh --dry-run --verbose
```

### Installation du cron

```bash
# ⚠️ Si le scheduler Laravel est actif, ne pas installer ce cron.
# Installation interactive (usage manuel uniquement)
./scripts/install-cron.sh

# Ou manuellement (crontab -e) :
0 3 * * * cd /opt/civicdash && ./scripts/sync-all-data.sh >> /opt/civicdash/storage/logs/sync.log 2>&1

### Scheduler Laravel (recommandé en production)

Le scheduler Laravel peut être exécuté via cron système :

```bash
* * * * * cd /opt/civicdash && docker compose exec -T app php artisan schedule:run >> /opt/civicdash/storage/logs/scheduler.log 2>&1
```

Si vous utilisez ce mode, évitez d’installer un cron séparé pour `sync-all-data.sh`.
```

---

## 🏛️ Assemblée Nationale

### Commandes Artisan

```bash
# Synchronisation complète (toutes les sources)
php artisan an:sync --legislature=17

# Source spécifique
php artisan an:sync scrutins --legislature=17
php artisan an:sync deputes_actifs --legislature=17
php artisan an:sync amendements --legislature=17

# Options
php artisan an:sync scrutins --fresh        # Vide la table avant import
php artisan an:sync scrutins --dry-run      # Simulation
php artisan an:sync scrutins --limit=100    # Limite le nombre
php artisan an:sync scrutins --sample=5     # Affiche un échantillon

# Recalcul des statistiques
php artisan scrutins:recalculer --legislature=17
```

### Sources de données

| Source | Description | Modèle |
|--------|-------------|--------|
| `scrutins` | Votes de l'Assemblée | `ScrutinAN` |
| `deputes_actifs` | Députés en exercice | `ActeurAN` |
| `acteurs` | Tous les acteurs (historique) | `ActeurAN` |
| `organes` | Groupes, commissions | `OrganeAN` |
| `amendements` | Amendements déposés | `AmendementAN` |
| `dossiers` | Dossiers législatifs | `DossierLegislatif` |

### Commandes d'import détaillées

```bash
# Import des amendements AN (depuis fichiers JSON téléchargés)
php artisan import:amendements-an
php artisan import:amendements-an --legislature=17 --limit=100

# Import des acteurs (députés)
php artisan import:acteurs-an
php artisan import:acteurs-an --legislature=17

# Import des scrutins
php artisan import:scrutins-an
php artisan import:scrutins-an --legislature=17 --fresh

# Import des organes (groupes, commissions)
php artisan import:organes-an

# Import des mandats
php artisan import:mandats-an

# Import des dossiers législatifs
php artisan import:dossiers-an
```

### Commandes d'enrichissement

```bash
# Enrichir les données députés depuis NosDéputés.fr
php artisan enrich:deputes-from-api
php artisan enrich:deputes-from-api --limit=50

# Enrichir avec photos Wikipedia
php artisan enrich:photos-wikipedia
php artisan enrich:photos-wikipedia --deputes
php artisan enrich:photos-wikipedia --senateurs
```

### Configuration

Fichier : `config/assemblee-nationale.php`

```php
return [
    'base_url' => 'http://data.assemblee-nationale.fr/static/openData/repository',
    'legislature' => env('AN_LEGISLATURE', 17),
    'sources' => [
        'scrutins' => [
            'path' => '{legislature}/loi/scrutins/Scrutins.xml.zip',
            'model' => \App\Models\ScrutinAN::class,
            'priority' => 1,
        ],
        // ...
    ],
];
```

---

## 🏛️ Sénat

### Commandes Artisan

```bash
# Afficher le statut
php artisan senat:sync --status

# Import SQL (bases complètes)
php artisan import:senat-sql senateurs    # Base des sénateurs
php artisan import:senat-sql ameli        # Amendements
php artisan import:senat-sql dosleg       # Dossiers législatifs
php artisan import:senat-sql questions    # Questions

# Lister les bases disponibles
php artisan import:senat-sql --list

# Synchroniser les textes Akoma Ntoso
php artisan senat:sync --textes
php artisan senat:sync --textes --analyze  # Analyser sans importer
```

### Sources de données

| Base | URL | Tables créées |
|------|-----|---------------|
| `senateurs` | data.senat.fr/data/senateurs | `senat_senateurs_*` |
| `ameli` | data.senat.fr/data/ameli | `senat_ameli_*` |
| `dosleg` | data.senat.fr/data/dosleg | `senat_dosleg_*` |
| `questions` | data.senat.fr/data/questions | `senat_questions_*` |

### Textes Akoma Ntoso

Les textes législatifs sont disponibles au format Akoma Ntoso XML :

- **Textes déposés** : `https://www.senat.fr/akomantoso/depots.xml`
- **Textes adoptés** : `https://www.senat.fr/akomantoso/adoptions.xml`

### Configuration

Fichier : `config/senat.php`

```php
return [
    'databases' => [
        'senateurs' => [
            'url' => 'https://data.senat.fr/data/senateurs/...',
            'table_prefix' => 'senat_senateurs_',
            'priority' => 1,
        ],
        // ...
    ],
    'akoma_ntoso' => [
        'depots' => 'https://www.senat.fr/akomantoso/depots.xml',
        'adoptions' => 'https://www.senat.fr/akomantoso/adoptions.xml',
    ],
];
```

---

## 🏛️ HATVP

### Commandes Artisan

```bash
# Afficher le statut
php artisan hatvp:sync --status

# Analyser sans importer
php artisan hatvp:sync --analyze
php artisan hatvp:sync --analyze --parlementaires

# Importer les déclarations
php artisan hatvp:sync --import --parlementaires
php artisan hatvp:sync --import --type=senateur
php artisan hatvp:sync --import --type=depute

# Options
php artisan hatvp:sync --import --limit=100
php artisan hatvp:sync --import --force  # Force le re-téléchargement
```

### Types de déclarations

| Code | Description |
|------|-------------|
| `DIA` | Déclaration d'Intérêts et d'Activités |
| `DSP` | Déclaration de Situation Patrimoniale |
| `DSPFM` | DSP de fin de mandat |
| `DIAMOD` | DIA modificative |

### Tables créées

```
hatvp_declarations           # Déclaration principale
hatvp_mandats_electifs       # Mandats déclarés
hatvp_remunerations          # Rémunérations par mandat
hatvp_fonctions_benevoles    # Fonctions bénévoles
hatvp_participations_*       # Participations diverses
hatvp_collaborateurs         # Collaborateurs parlementaires
hatvp_activites_*            # Activités professionnelles
hatvp_immeubles              # Biens immobiliers
hatvp_vehicules              # Véhicules
hatvp_comptes_bancaires      # Comptes bancaires
hatvp_assurances_vie         # Assurances vie
hatvp_valeurs_*              # Valeurs mobilières
hatvp_passifs                # Dettes
hatvp_revenus                # Revenus
```

### Configuration

Fichier : `config/hatvp.php`

```php
return [
    'base_url' => 'https://www.hatvp.fr/livraison',
    'declarations_file' => 'merge/declarations.xml',
    'types_declarations' => [
        'DIA' => 'Déclaration d\'intérêts',
        'DSP' => 'Déclaration de patrimoine',
        // ...
    ],
];
```

---

## 📅 Configuration Cron Recommandée

```cron
# ============================================================================
# DEMOSCRATOS - Synchronisation des données parlementaires
# ============================================================================

# Synchronisation complète quotidienne à 3h du matin
0 3 * * * /var/www/demoscratos/scripts/sync-all-data.sh >> /var/log/demoscratos/sync.log 2>&1

# Synchronisation légère des scrutins AN toutes les 6h (pendant les sessions)
# 0 */6 * * * /var/www/demoscratos/scripts/sync-all-data.sh --an >> /var/log/demoscratos/sync-an.log 2>&1

# HATVP hebdomadaire (les déclarations changent peu)
0 4 * * 0 /var/www/demoscratos/scripts/sync-all-data.sh --hatvp >> /var/log/demoscratos/sync-hatvp.log 2>&1

# Nettoyage des vieux logs (garder 30 jours)
0 5 * * 0 find /var/log/demoscratos -name '*.log' -mtime +30 -delete
```

---

## 📊 Monitoring

### Logs

Les logs sont stockés dans plusieurs emplacements :

```bash
# Logs Laravel
storage/logs/sync-*.log           # Logs quotidiens du script
storage/logs/laravel.log          # Logs généraux Laravel

# Logs système (si configuré)
/var/log/demoscratos/sync.log     # Sortie du cron
```

### Canaux de log dédiés

Configurés dans `config/logging.php` :

- `an-sync` : Synchronisation Assemblée Nationale
- `senat-sync` : Synchronisation Sénat
- `hatvp-sync` : Synchronisation HATVP

### Vérification du statut

```bash
# Statut global
./scripts/sync-all-data.sh --dry-run

# Statut par source
php artisan an:sync --dry-run
php artisan senat:sync --status
php artisan hatvp:sync --status
```

---

## 🔧 Dépannage

### Erreurs courantes

**1. Timeout lors du téléchargement**

```bash
# Augmenter le timeout PHP
php -d max_execution_time=0 artisan an:sync
```

**2. Erreur mémoire**

```bash
# Augmenter la mémoire
php -d memory_limit=512M artisan hatvp:sync --import
```

**3. Fichier lock orphelin**

```bash
# Supprimer le lock
rm /tmp/demoscratos-sync.lock
```

**4. Tables non créées**

```bash
# Exécuter les migrations
php artisan migrate
```

### Réinitialisation complète

```bash
# Attention : supprime toutes les données !
php artisan migrate:fresh --seed

# Puis resynchroniser
./scripts/sync-all-data.sh
```

---

## 📊 Statistiques Dashboard Pré-calculées

Pour optimiser les performances, les statistiques du dashboard sont pré-calculées et stockées dans la table `dashboard_stats`.

### Commande de calcul

```bash
# Calcul des statistiques (12 secondes environ)
php artisan dashboard:calculate-stats

# Forcer le recalcul même si frais (< 12h)
php artisan dashboard:calculate-stats --force
```

### Données calculées

| Clé | Description | Taille |
|-----|-------------|--------|
| `top_deputes` | Top 10 députés par nombre de votes | ~10 entrées |
| `top_senateurs` | Top 10 sénateurs par nombre d'amendements | ~10 entrées |
| `groupes_actifs` | Top 10 groupes par nombre de membres | ~10 entrées |
| `derniers_scrutins` | 10 derniers scrutins | ~10 entrées |
| `global_stats` | Compteurs globaux (députés, sénateurs, scrutins, amendements) | 4 valeurs |

### Planification automatique

La commande est planifiée automatiquement via le scheduler Laravel :

```php
// routes/console.php
Schedule::command('dashboard:calculate-stats --force')
    ->dailyAt('04:00')
    ->description('Recalcul quotidien des statistiques dashboard');
```

**Configuration cron requise** :
```bash
* * * * * php /var/www/artisan schedule:run >> /dev/null 2>&1
```

### Utilisation dans le code

```php
use App\Models\DashboardStat;

// Récupérer une statistique
$topDeputes = DashboardStat::get('top_deputes', []);

// Mettre à jour une statistique
DashboardStat::set('global_stats', ['nb_deputes' => 577, ...]);

// Vérifier si les stats sont fraîches (< 24h)
if (!DashboardStat::isFresh('top_deputes', 24)) {
    Artisan::call('dashboard:calculate-stats');
}
```

### Table `dashboard_stats`

```sql
CREATE TABLE dashboard_stats (
    id BIGSERIAL PRIMARY KEY,
    key VARCHAR(255) UNIQUE,      -- Identifiant unique
    value JSON,                   -- Données pré-calculées
    calculated_at TIMESTAMP,      -- Date du dernier calcul
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🐛 Bugs Connus et Correctifs

### 1. Amendements AN sans auteur (corrigé Déc. 2025)

**Symptôme** : Les amendements importés avaient `auteur_acteur_ref: NULL`, ce qui empêchait le calcul des statistiques par député.

**Cause** : Le fichier `ImportAmendementsAN.php` cherchait l'auteur à un mauvais chemin JSON.

**Correction** :
```php
// Avant (incorrect) :
$auteur = $amendement['auteur'] ?? [];

// Après (correct) :
$auteur = $amendement['signataires']['auteur'] ?? [];
```

**Fichier** : `app/Console/Commands/ImportAmendementsAN.php`

**Vérification** :
```bash
# Vérifier le lien auteur/amendements
docker compose exec app php artisan tinker --execute="
\$avec = \App\Models\AmendementAN::whereNotNull('auteur_acteur_ref')->count();
\$sans = \App\Models\AmendementAN::whereNull('auteur_acteur_ref')->count();
echo 'Avec auteur: ' . \$avec . PHP_EOL;
echo 'Sans auteur: ' . \$sans . PHP_EOL;
"
```

---

## 📁 Architecture des fichiers

```
scripts/
├── sync-all-data.sh          # Script principal de synchronisation
└── install-cron.sh           # Installation du cron

app/
├── Console/Commands/
│   ├── SyncAnDataCommand.php       # an:sync
│   ├── DownloadAnDataCommand.php   # an:download
│   ├── SyncSenatDataCommand.php    # senat:sync
│   ├── ImportSenatSQL.php          # import:senat-sql
│   ├── SyncHatvpDataCommand.php    # hatvp:sync
│   └── RecalculerScrutinsAN.php    # scrutins:recalculer
│
└── Services/
    ├── AssembleeNationale/
    │   ├── XmlDownloader.php
    │   └── XmlParser.php
    ├── Senat/
    │   ├── SenatDataDownloader.php
    │   └── AkomaNtosoParser.php
    └── Hatvp/
        ├── HatvpDataDownloader.php
        └── HatvpXmlParser.php

config/
├── assemblee-nationale.php
├── senat.php
├── hatvp.php
└── logging.php

storage/
└── app/
    ├── an-data/              # Cache AN
    ├── senat-data/           # Cache Sénat
    └── hatvp-data/           # Cache HATVP
```

---

## 🔗 Liens utiles

### Assemblée Nationale
- [Open Data AN](https://data.assemblee-nationale.fr/)
- [Documentation API](https://data.assemblee-nationale.fr/documentation)

### Sénat
- [Open Data Sénat](https://data.senat.fr/)
- [Documentation Dosleg](https://data.senat.fr/aide/travaux-legislatifs-base-dosleg/)
- [Documentation Ameli](https://data.senat.fr/aide/notice-explicative-ameli/)
- [Documentation Akoma Ntoso](https://data.senat.fr/wp-content/uploads/2021/03/akomantoso.pdf)

### HATVP
- [Open Data HATVP](https://www.hatvp.fr/open-data/)
- [Fichier declarations.xml](https://www.hatvp.fr/livraison/merge/declarations.xml)
