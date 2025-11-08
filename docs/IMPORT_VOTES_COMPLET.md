# 📊 Import Complet des Votes, Interventions & Questions

## 🎯 Objectif

Importer **TOUTES** les données d'activité parlementaire depuis les APIs NosDéputés.fr et NosSénateurs.fr :
- **Votes détaillés** : position (pour/contre/abstention), résultat, contexte
- **Interventions** : discours en séance, commissions, nombre de mots
- **Questions au gouvernement** : questions écrites/orales + réponses

---

## 🗄️ Structure de données

### Tables créées

#### 1. `votes_deputes`
Tous les votes détaillés de chaque député/sénateur.

| Colonne | Type | Description |
|---------|------|-------------|
| `depute_senateur_id` | FK | Lien vers député/sénateur |
| `numero_scrutin` | string | Numéro du scrutin |
| `date_vote` | date | Date du vote |
| `titre` | text | Titre du scrutin |
| `position` | enum | pour/contre/abstention/absent |
| `resultat` | enum | adopte/rejete |
| `pour` / `contre` / `abstentions` / `absents` | int | Résultats globaux |
| `type_vote` | string | solennel/ordinaire |
| `url_scrutin` | string | Lien vers NosDéputés/Sénateurs |

#### 2. `interventions_parlementaires`
Toutes les interventions (discours, débats).

| Colonne | Type | Description |
|---------|------|-------------|
| `depute_senateur_id` | FK | Lien vers député/sénateur |
| `date_intervention` | date | Date de l'intervention |
| `type` | string | seance/commission/question_orale |
| `titre` | string | Titre/sujet |
| `contenu` | text | Texte intégral |
| `nb_mots` | int | Nombre de mots |
| `duree_secondes` | int | Durée en secondes |
| `url_video` / `url_texte` | string | Liens vidéo/texte |

#### 3. `questions_gouvernement`
Questions écrites/orales au gouvernement.

| Colonne | Type | Description |
|---------|------|-------------|
| `depute_senateur_id` | FK | Lien vers député/sénateur |
| `type` | enum | ecrite/orale |
| `numero` | string | Numéro question |
| `date_depot` / `date_reponse` | date | Dates |
| `ministere` | string | Ministère concerné |
| `question` / `reponse` | text | Textes |
| `statut` | enum | en_attente/repondu/retire |

---

## 🚀 Utilisation

### 1️⃣ Test rapide (3 députés + 2 sénateurs)
```bash
bash scripts/test_enrich_votes.sh
```

⏱️ **Durée :** ~10 secondes

---

### 2️⃣ Import complet (tous les députés et sénateurs)
```bash
bash scripts/enrich_complete.sh
```

⏱️ **Durée :** ~32 minutes
- 575 députés × 2s = ~20 min
- 348 sénateurs × 2s = ~12 min

---

### 3️⃣ Import séparé

**Députés uniquement :**
```bash
docker compose exec app php artisan enrich:deputes-votes
```

**Sénateurs uniquement :**
```bash
docker compose exec app php artisan enrich:senateurs-votes
```

**Avec options :**
```bash
# Test sur 10 députés
php artisan enrich:deputes-votes --limit=10

# Votes uniquement (skip interventions/questions)
php artisan enrich:deputes-votes --votes-only

# Un député spécifique
php artisan enrich:deputes-votes --depute=PA267350
```

---

## 📊 Statistiques après import

### Requêtes utiles

**Total par type :**
```sql
SELECT COUNT(*) FROM votes_deputes;
SELECT COUNT(*) FROM interventions_parlementaires;
SELECT COUNT(*) FROM questions_gouvernement;
```

**Top 5 députés les plus actifs :**
```sql
SELECT 
    ds.nom_complet,
    COUNT(vd.id) as nb_votes
FROM deputes_senateurs ds
JOIN votes_deputes vd ON vd.depute_senateur_id = ds.id
GROUP BY ds.nom_complet
ORDER BY nb_votes DESC
LIMIT 5;
```

**Analyse par groupe politique :**
```sql
SELECT 
    ds.groupe_politique,
    COUNT(DISTINCT ds.id) as nb_elus,
    COUNT(vd.id) as nb_votes,
    ROUND(COUNT(vd.id)::numeric / COUNT(DISTINCT ds.id), 0) as votes_par_elu
FROM deputes_senateurs ds
LEFT JOIN votes_deputes vd ON vd.depute_senateur_id = ds.id
WHERE ds.source = 'assemblee'
GROUP BY ds.groupe_politique
ORDER BY nb_votes DESC;
```

---

## 🔗 Relations Eloquent

Dans `DeputeSenateur.php`, vous avez maintenant :

```php
// Tous les votes du député
$depute->votes()->pour()->count();
$depute->votes()->contre()->count();

// Interventions
$depute->interventions()->where('type', 'seance')->count();

// Questions
$depute->questions()->repondues()->count();
$depute->questions()->enAttente()->count();
```

---

## 💡 Cas d'usage

### 1. Afficher l'activité d'un député
```php
$depute = DeputeSenateur::with(['votes', 'interventions', 'questions'])->find($id);

return [
    'nb_votes' => $depute->votes->count(),
    'nb_pour' => $depute->votes->where('position', 'pour')->count(),
    'nb_interventions' => $depute->interventions->count(),
    'nb_questions' => $depute->questions->count(),
];
```

### 2. Comparer les positions sur un scrutin
```php
$scrutin = '1234';
$votes = VoteDepute::where('numero_scrutin', $scrutin)
    ->with('deputeSenateur')
    ->get()
    ->groupBy('position');
```

### 3. Analyser les thématiques d'interventions
```php
$interventions = InterventionParlementaire::where('depute_senateur_id', $id)
    ->orderBy('date_intervention', 'desc')
    ->limit(10)
    ->get();
```

---

## ⚠️ Notes importantes

1. **Rate limiting** : Pause de 2 secondes entre chaque élu (obligatoire pour respecter les APIs)
2. **Données volumineuses** : Compter ~1000 votes par député actif
3. **Mises à jour** : Relancer périodiquement pour avoir les derniers votes
4. **Stockage** : Prévoir ~500 Mo pour l'ensemble des données

---

## 🐛 Dépannage

### Erreur 429 (Too Many Requests)
➡️ Augmenter la pause entre appels (modifier `sleep(2)` en `sleep(3)`)

### Députés non trouvés
➡️ L'API utilise des slugs (prenom-nom). Vérifier que `nom` et `prenom` sont corrects

### Timeout
➡️ Augmenter le timeout HTTP (actuellement 30s)

---

## 📚 Sources

- **API Députés** : https://www.nosdeputes.fr/
- **API Sénateurs** : https://www.nossenateurs.fr/
- **Documentation** : https://github.com/regardscitoyens/nosdeputes.fr

---

**✨ Avec ces données, vous pouvez créer des analyses poussées de l'activité parlementaire ! 🏛️**

