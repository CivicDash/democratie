# Import de la Composition du Gouvernement

## Source Officielle

📍 **Source** : https://www.info.gouv.fr/composition-du-gouvernement

⚠️ **Note** : Le site `info.gouv.fr` bloque les requêtes automatisées (403 Forbidden). Les données doivent être mises à jour manuellement.

## Méthode d'import

### 1. Mettre à jour le fichier JSON

Le fichier de données se trouve ici :
```
/database/data/gouvernement.json
```

### 2. Structure du fichier

```json
{
    "gouvernement": {
        "nom": "Gouvernement Lecornu",
        "premier_ministre": "Sébastien Lecornu",
        "president": "Emmanuel Macron",
        "date_debut": "2025-10-05",
        "legislature": "17"
    },
    "membres": [
        {
            "fonction": "Premier ministre",
            "prenom": "Sébastien",
            "nom": "Lecornu",
            "type": "premier_ministre",
            "ministere": null,
            "parti": "Renaissance",
            "photo_url": "https://..."
        },
        {
            "fonction": "Ministre de l'Économie",
            "prenom": "Bruno",
            "nom": "Le Maire",
            "type": "ministre",
            "ministere": "Économie",
            "parti": "Renaissance",
            "photo_url": null
        }
    ]
}
```

### 3. Types de fonction valides

| Type | Description |
|------|-------------|
| `premier_ministre` | Premier ministre |
| `ministre` | Ministre de plein exercice |
| `ministre_delegue` | Ministre délégué(e) |
| `secretaire_etat` | Secrétaire d'État |

### 4. Exécuter l'import

```bash
# Mode simulation (affiche les données sans les importer)
php artisan import:gouvernement-json --dry-run

# Import réel
php artisan import:gouvernement-json

# Forcer le remplacement des données existantes
php artisan import:gouvernement-json --force
```

## Sources de données alternatives

### Wikidata (expérimental)

Une commande de synchronisation via Wikidata existe mais n'est pas fiable car les données Wikidata ne sont pas toujours à jour :

```bash
php artisan sync:gouvernement --dry-run
```

### JORF (Journal Officiel)

Les décrets de nomination sont publiés au JORF. Une future évolution pourrait extraire les données depuis :
- `https://echanges.dila.gouv.fr/OPENDATA/JORF/`

### data.gouv.fr

À ce jour (janvier 2026), aucun dataset structuré n'existe sur data.gouv.fr pour la composition actuelle du gouvernement.

## Historique des gouvernements

Pour maintenir l'historique, créez un nouveau fichier JSON par gouvernement :

```
/database/data/gouvernement_bayrou_2024.json
/database/data/gouvernement_lecornu_2025.json
```

Et importez chaque fichier avec `--force` pour créer les entrées historiques.

## Automatisation future

Des pistes pour automatiser :

1. **Scraping avec Selenium/Puppeteer** - Contourner le blocage 403
2. **Abonnement JORF** - Parser les décrets de nomination
3. **API gouvernementale** - Demander l'ouverture d'une API officielle

---

*Dernière mise à jour : Janvier 2026*
