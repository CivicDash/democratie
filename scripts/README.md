# 📁 Scripts CivicDash

Ce répertoire contient des scripts utilitaires pour la gestion et le debug de l'application.

## 📂 Structure

```
scripts/
├── debug/               # Scripts de debug (gitignored)
├── import_postal_codes_local.sh
├── check_postal_codes.sh
├── check_thematiques.sh
└── test_postal_search.sh
```

## 🔧 Scripts disponibles

### Import et diagnostic codes postaux

#### `import_postal_codes_local.sh`
Importe les codes postaux depuis le fichier CSV local.
```bash
bash scripts/import_postal_codes_local.sh
```

#### `check_postal_codes.sh`
Diagnostic complet des codes postaux en base.
```bash
bash scripts/check_postal_codes.sh
```

#### `test_postal_search.sh`
Teste les recherches par code postal ET par ville.
```bash
bash scripts/test_postal_search.sh
```

### Diagnostic thématiques

#### `check_thematiques.sh`
Vérifie les associations propositions ↔ thématiques.
```bash
bash scripts/check_thematiques.sh
```

---

## 🚀 Déploiement

Le script de déploiement principal est à la racine :
```bash
bash deploy.sh [--fresh-db] [--optimize]
```

---

## 📝 Notes

- Les scripts dans `debug/` sont gitignorés
- Tous les scripts utilisent `docker compose` pour accéder aux containers
- Les logs sont affichés avec des couleurs pour faciliter la lecture

---

*Pour plus d'informations, voir `/CODES_POSTAUX_ET_CARTE.md`*

