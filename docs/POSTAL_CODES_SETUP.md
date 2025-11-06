# 📮 Configuration des Codes Postaux Français

Ce document explique comment configurer et importer la base de données des codes postaux français pour le système de localisation des citoyens.

## 🎯 Fonctionnalités

- **36 000+ codes postaux** français (métropole + DOM-TOM)
- **Autocomplétion intelligente** par code postal ou nom de ville
- **Association automatique** aux circonscriptions législatives
- **Géolocalisation** (latitude/longitude) pour chaque commune
- **API publique** pour la recherche

## 🚀 Installation sur le serveur de production

### 1. Exécuter les migrations

```bash
php artisan migrate --force
```

Cela créera la table `french_postal_codes` avec toutes les colonnes nécessaires.

### 2. Importer les données depuis l'API geo.api.gouv.fr

```bash
php artisan app:import-french-postal-codes --fresh
```

**⚠️ Attention :** Cette commande peut prendre **10-15 minutes** car elle récupère les données de tous les départements français depuis l'API publique.

Options disponibles :
- `--fresh` : Vide la table avant l'import (recommandé pour la première fois)

### 3. Vérifier l'import

```bash
php artisan tinker
```

Puis dans Tinker :

```php
// Vérifier le nombre total de codes postaux
App\Models\FrenchPostalCode::count();
// Devrait retourner environ 36 000

// Tester une recherche
App\Models\FrenchPostalCode::autocomplete('75001')->get();
```

## 📡 API Endpoints

Une fois les données importées, les endpoints suivants sont disponibles :

### Recherche par autocomplétion

```
GET /api/postal-codes/search?q=75001
GET /api/postal-codes/search?q=Paris
```

**Réponse :**
```json
{
  "results": [
    {
      "id": 1,
      "postal_code": "75001",
      "city_name": "Paris",
      "department_code": "75",
      "department_name": "Paris",
      "circonscription": "75-01",
      "latitude": 48.8606,
      "longitude": 2.3376,
      "label": "75001 - Paris",
      "full_label": "75001 - Paris (Paris)"
    }
  ],
  "count": 1
}
```

### Détails d'un code postal

```
GET /api/postal-codes/75001
```

### Villes d'un département

```
GET /api/postal-codes/department/75
```

### Villes d'une circonscription

```
GET /api/postal-codes/circonscription/75-01
```

## 🔄 Mise à jour des données

Pour mettre à jour les données (par exemple, après un redécoupage des circonscriptions) :

```bash
php artisan app:import-french-postal-codes --fresh
```

## 🎨 Utilisation dans le frontend

Le formulaire de localisation (`resources/js/Pages/Profile/Partials/UpdateLocationForm.vue`) utilise déjà l'autocomplétion.

**Exemple d'utilisation :**

```javascript
import axios from 'axios';

// Rechercher des codes postaux
const searchPostalCodes = async (query) => {
    const response = await axios.get('/api/postal-codes/search', {
        params: { q: query }
    });
    return response.data.results;
};

// Utilisation
const results = await searchPostalCodes('Paris');
console.log(results);
```

## 📊 Statistiques

Après l'import, vous devriez avoir :
- **~36 000 codes postaux**
- **~35 000 communes**
- **101 départements** (95 métropole + 6 DOM-TOM)
- **577 circonscriptions** législatives

## 🐛 Dépannage

### L'import échoue avec une erreur de timeout

L'API geo.api.gouv.fr peut être lente. Augmentez le timeout PHP :

```bash
php -d max_execution_time=600 artisan app:import-french-postal-codes --fresh
```

### Les circonscriptions ne sont pas correctes

Pour l'instant, les circonscriptions sont attribuées de manière simplifiée (format `XX-01`). 

Pour une correspondance précise commune → circonscription, il faudrait :
1. Récupérer les données officielles de l'Assemblée Nationale
2. Créer une table de correspondance `commune_circonscription`
3. Mettre à jour la commande d'import

## 🔗 Sources de données

- **API Découpage Administratif** : https://geo.api.gouv.fr/
- **Base Officielle des Codes Postaux** : https://www.data.gouv.fr/
- **Circonscriptions législatives** : https://www.assemblee-nationale.fr/

## 📝 Notes

- Les données sont mises en cache dans la base de données pour des performances optimales
- L'autocomplétion fonctionne avec un debounce de 300ms
- Les résultats sont limités à 20 par recherche
- La recherche est insensible à la casse (ILIKE)

---

**Dernière mise à jour :** 6 novembre 2025

