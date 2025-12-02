# 📋 Résumé - Système de Synchronisation des Données

## ✅ Ce qui a été créé

### 1. Script de synchronisation global

**`scripts/sync-all-data.sh`**

Script shell complet pour la synchronisation automatique :
- Synchronise AN + Sénat + HATVP
- Support des options `--an`, `--senat`, `--hatvp`
- Mode `--dry-run` pour simulation
- Mode `--verbose` pour détails
- Gestion du lock file (évite les exécutions simultanées)
- Logs horodatés dans `storage/logs/sync-*.log`

### 2. Script d'installation du cron

**`scripts/install-cron.sh`**

Installation interactive du cron :
- Crée le répertoire `/var/log/demoscratos`
- Configure le cron à 3h du matin
- Nettoyage automatique des vieux logs

### 3. Documentation mise à jour

- `docs/SYNCHRONISATION_DONNEES.md` - Documentation complète
- `scripts/README.md` - Mise à jour avec nouvelle section

---

## 🚀 Utilisation

### Synchronisation manuelle

```bash
# Tout synchroniser
./scripts/sync-all-data.sh

# Sources spécifiques
./scripts/sync-all-data.sh --an
./scripts/sync-all-data.sh --senat
./scripts/sync-all-data.sh --hatvp

# Mode test
./scripts/sync-all-data.sh --dry-run --verbose
```

### Installation du cron

```bash
./scripts/install-cron.sh
```

### Commandes Artisan individuelles

```bash
# Assemblée Nationale
php artisan an:sync --legislature=17
php artisan an:sync scrutins --legislature=17
php artisan scrutins:recalculer --legislature=17

# Sénat
php artisan senat:sync --status
php artisan import:senat-sql senateurs
php artisan senat:sync --textes

# HATVP
php artisan hatvp:sync --status
php artisan hatvp:sync --analyze --parlementaires
php artisan hatvp:sync --import --parlementaires
```

---

## 📅 Configuration Cron recommandée

```cron
# Synchronisation complète quotidienne à 3h du matin
0 3 * * * /var/www/demoscratos/scripts/sync-all-data.sh >> /var/log/demoscratos/sync.log 2>&1

# HATVP hebdomadaire (les déclarations changent peu)
0 4 * * 0 /var/www/demoscratos/scripts/sync-all-data.sh --hatvp >> /var/log/demoscratos/sync-hatvp.log 2>&1

# Nettoyage des vieux logs
0 5 * * 0 find /var/log/demoscratos -name '*.log' -mtime +30 -delete
```

---

## 📁 Architecture des fichiers

```
scripts/
├── sync-all-data.sh          # Script principal de synchronisation
├── install-cron.sh           # Installation du cron
└── README.md                 # Documentation mise à jour

app/Console/Commands/
├── SyncAnDataCommand.php       # php artisan an:sync
├── DownloadAnDataCommand.php   # php artisan an:download
├── SyncSenatDataCommand.php    # php artisan senat:sync
├── ImportSenatSQL.php          # php artisan import:senat-sql
├── SyncHatvpDataCommand.php    # php artisan hatvp:sync
└── RecalculerScrutinsAN.php    # php artisan scrutins:recalculer

app/Services/
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

docs/
├── SYNCHRONISATION_DONNEES.md
├── SOURCES_DONNEES_AN.md
├── SOURCES_DONNEES_SENAT.md
└── SOURCES_DONNEES_HATVP.md
```

---

## 🔧 Prochaines étapes

1. **Tester sur le serveur** :
   ```bash
   git pull
   php artisan migrate
   ./scripts/sync-all-data.sh --dry-run --verbose
   ```

2. **Installer le cron** :
   ```bash
   ./scripts/install-cron.sh
   ```

3. **Surveiller les logs** :
   ```bash
   tail -f /var/log/demoscratos/sync.log
   ```

---

## 📊 Données synchronisées

| Source | Tables | Modèles | Fréquence |
|--------|--------|---------|-----------|
| AN | `scrutins_an`, `acteurs_an`, `amendements_an`, etc. | `ScrutinAN`, `ActeurAN`, `AmendementAN` | Quotidienne |
| Sénat | `senat_senateurs_*`, `senat_ameli_*`, etc. | `Senateur`, `VoteSenat`, `AmendementSenat` | Quotidienne |
| HATVP | `hatvp_declarations`, `hatvp_mandats_electifs`, etc. | `HatvpDeclaration`, `HatvpMandatElectif`, etc. | Hebdomadaire |

---

*Généré le 2 décembre 2025*

