# 📜 Architecture du Cycle de Vie des Lois

## Vue d'ensemble

CivicDash dispose d'une base de données complète permettant de suivre le **parcours législatif complet** d'une loi, depuis son dépôt jusqu'à sa promulgation au Journal Officiel.

## 📊 Schéma des données

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        SENAT_DOSLEG_LOI                                 │
│                     (Table centrale : 12 088 lois)                      │
├─────────────────────────────────────────────────────────────────────────┤
│ loicod (PK)        │ Identifiant unique de la loi                       │
│ typloicod          │ Type de loi (FK → senat_dosleg_typloi)             │
│ etaloicod          │ État actuel (FK → senat_dosleg_etaloi)             │
│ numero             │ Numéro de la loi (ex: 2025-1079)                   │
│ loitit             │ Titre complet                                      │
│ loiint             │ Intitulé court                                     │
│ urgence            │ Procédure d'urgence (O/N)                          │
│ url_jo             │ Lien vers le Journal Officiel                      │
│ loidatjo           │ Date de publication au JO                          │
│ url_an             │ Lien vers l'Assemblée Nationale                    │
│ date_loi           │ Date de la loi                                     │
└─────────────────────────────────────────────────────────────────────────┘
          │
          │ 1:N
          ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      SENAT_DOSLEG_LECTURE                               │
│                    (Lectures : 16 337 enregistrements)                  │
├─────────────────────────────────────────────────────────────────────────┤
│ lecidt (PK)        │ Identifiant unique de la lecture                   │
│ loicod (FK)        │ Référence vers la loi                              │
│ typleccod          │ Type de lecture (FK → senat_dosleg_typlec)         │
│ leccom             │ Commentaire sur la lecture                         │
└─────────────────────────────────────────────────────────────────────────┘
          │
          │ 1:N
          ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      SENAT_DOSLEG_LECASS                                │
│              (Passages par chambre : 21 350 enregistrements)            │
├─────────────────────────────────────────────────────────────────────────┤
│ lecassidt (PK)     │ Identifiant unique du passage                      │
│ lecidt (FK)        │ Référence vers la lecture                          │
│ codass             │ Code chambre : A=AN, S=Sénat, I=CMP                 │
│ ordreass           │ Ordre de passage dans cette lecture                │
│ sesann             │ Année de session                                   │
│ orgcod             │ Code organe/commission                             │
│ lecassame          │ Nombre d'amendements                               │
│ lecassameado       │ Amendements adoptés                                │
│ debatsurl          │ URL vers les débats                                │
└─────────────────────────────────────────────────────────────────────────┘
```

## 📋 Tables de référence

### senat_dosleg_typlec (Types de lecture)

| Code | Libellé | Ordre |
|------|---------|-------|
| 1 | Première lecture | 1 |
| 2 | Deuxième lecture | 2 |
| 3 | Troisième lecture | 3 |
| 4 | Commission Mixte Paritaire | 100 |
| 5 | Nouvelle lecture | 200 |
| 6 | Lecture définitive | 300 |
| 7 | Quatrième lecture | 4 |
| 8 | Congrès du Parlement | 400 |
| 9 | Référendum | 500 |

### senat_dosleg_etaloi (États des lois)

| Code | Libellé | Nombre |
|------|---------|--------|
| 01 | En cours de discussion | 1 856 |
| 02 | Fusionné | 218 |
| 03 | Rejeté | 182 |
| 04 | Promulgué ou adopté | 4 864 |
| 05 | Caduc | 4 840 |
| 06 | Retiré | 128 |

### senat_dosleg_typloi (Types de lois)

Les types incluent :
- Projet de loi
- Proposition de loi
- Projet de loi constitutionnelle
- Projet de loi organique
- Proposition de résolution
- etc.

## 🔗 Relations et jointures

### Requête pour obtenir le parcours complet d'une loi

```sql
SELECT 
    l.loicod,
    l.loitit AS titre,
    el.etaloilib AS etat,
    tl.typleclib AS type_lecture,
    la.codass AS chambre,
    la.ordreass AS ordre,
    la.sesann AS session,
    la.lecassame AS nb_amendements,
    la.lecassameado AS amendements_adoptes,
    la.debatsurl AS url_debats
FROM senat_dosleg_loi l
JOIN senat_dosleg_etaloi el ON l.etaloicod = el.etaloicod
JOIN senat_dosleg_lecture lec ON l.loicod = lec.loicod
JOIN senat_dosleg_typlec tl ON lec.typleccod = tl.typleccod
JOIN senat_dosleg_lecass la ON lec.lecidt = la.lecidt
WHERE l.loicod = :loicod
ORDER BY tl.typlecord, la.ordreass;
```

### Codes des chambres

| Code | Signification | Couleur suggérée |
|------|---------------|------------------|
| A | Assemblée Nationale | #0066CC (bleu) |
| S | Sénat | #CC0066 (rose/bordeaux) |
| I | Commission Mixte Paritaire | #6B7280 (gris) |

## 🏗️ Architecture Laravel

### Modèles Eloquent

```
app/Models/
├── Loi.php                 # senat_dosleg_loi
├── LectureLoi.php          # senat_dosleg_lecture  
├── PassageChambre.php      # senat_dosleg_lecass
├── TypeLecture.php         # senat_dosleg_typlec
├── EtatLoi.php             # senat_dosleg_etaloi
└── TypeLoi.php             # senat_dosleg_typloi
```

### Relations

```php
// Loi.php
public function lectures(): HasMany
{
    return $this->hasMany(LectureLoi::class, 'loicod', 'loicod');
}

public function etat(): BelongsTo
{
    return $this->belongsTo(EtatLoi::class, 'etaloicod', 'etaloicod');
}

// LectureLoi.php
public function passages(): HasMany
{
    return $this->hasMany(PassageChambre::class, 'lecidt', 'lecidt');
}

public function typeLecture(): BelongsTo
{
    return $this->belongsTo(TypeLecture::class, 'typleccod', 'typleccod');
}
```

## 📱 Routes API

```php
// routes/web.php
Route::prefix('lois')->group(function () {
    Route::get('/', [LoiController::class, 'index']);           // Liste des lois
    Route::get('/{loicod}', [LoiController::class, 'show']);    // Détail + parcours
    Route::get('/{loicod}/timeline', [LoiController::class, 'timeline']); // Timeline JSON
});
```

## 🎨 Composants Vue.js

### TimelineLoi.vue
Affiche le parcours législatif sous forme de timeline verticale avec :
- Icônes par chambre (AN 🏛️, Sénat 🏛️, CMP ⚖️)
- Couleurs par état (en cours, adopté, rejeté)
- Dates et sessions
- Liens vers les débats

### LoiCard.vue
Carte résumé d'une loi avec :
- Titre
- État actuel (badge coloré)
- Progression (barre de progression)
- Date JO si promulguée

## 📈 Statistiques disponibles

```php
// Nombre de lois par état
Loi::select('etaloicod', DB::raw('count(*) as total'))
    ->groupBy('etaloicod')
    ->get();

// Lois en cours de discussion
Loi::where('etaloicod', '01')->count(); // 1856

// Lois promulguées cette année
Loi::where('etaloicod', '04')
    ->whereYear('loidatjo', now()->year)
    ->count();

// Moyenne de lectures par loi
DB::table('senat_dosleg_lecture')
    ->select(DB::raw('AVG(nb) as moyenne'))
    ->fromSub(
        DB::table('senat_dosleg_lecture')
            ->select('loicod', DB::raw('count(*) as nb'))
            ->groupBy('loicod'),
        'sub'
    )
    ->first();
```

## 🔄 Exemple de parcours législatif

### Loi n° 2017-242 - Réforme de la prescription pénale

```
📝 DÉPÔT (2014)
     │
     ▼
🏛️ Assemblée Nationale - 1ère lecture
     │
     ▼
🏛️ Sénat - 1ère lecture (2015)
     │
     ▼
🏛️ Sénat - 1ère lecture (2016) [suite]
     │
     ▼
🏛️ Assemblée Nationale - 2ème lecture
     │
     ▼
🏛️ Sénat - 2ème lecture
     │
     ▼
⚖️ Commission Mixte Paritaire (échec)
     │
     ▼
🏛️ Assemblée Nationale - Nouvelle lecture
     │
     ▼
🏛️ Sénat - Nouvelle lecture
     │
     ▼
🏛️ Assemblée Nationale - Lecture définitive
     │
     ▼
📜 Promulgation (27/02/2017)
     │
     ▼
📰 Journal Officiel
   https://www.legifrance.gouv.fr/eli/loi/2017/2/27/2017-242/jo/texte
```

## 🏷️ Catégorisation des lois

Les lois peuvent être catégorisées via la table `senat_dosleg_loithe` (thématiques) :

```sql
SELECT DISTINCT t.thelib 
FROM senat_dosleg_loithe lt
JOIN senat_dosleg_the t ON lt.thecod = t.thecod
ORDER BY t.thelib;
```

Thématiques typiques :
- Budget et finances
- Défense et sécurité
- Économie et entreprises
- Éducation et recherche
- Environnement et énergie
- Justice et libertés
- Social et santé
- Territoires et collectivités
- etc.

## 📅 Intégration avec le calendrier

Les lectures peuvent être liées aux événements du calendrier législatif via :
- `senat_dosleg_date_seance` : dates de séance par lecture
- `evenements_legislatifs` : événements unifiés AN/Sénat/Élysée

---

*Documentation générée le 29/12/2025 - CivicDash*

