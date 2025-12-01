# 🔧 CORRECTIONS APPORTÉES - SESSION 21 NOV 2025 (SUITE)

## 📊 RÉSUMÉ RAPIDE

### Total commits : 33 (non pushés)
### Focus : Liaisons amendements/votes sénateurs + cohérence modèles

---

## ✅ CORRECTIONS RELATIONS ELOQUENT

### 1. Modèle `Senateur`
```php
// AVANT
public function votesSenat() {
    return $this->hasMany(VoteSenat::class, 'senateur_matricule', 'id');
}
public function amendementsSenat() {
    return $this->hasMany(AmendementSenat::class, 'auteur_senateur_matricule', 'matricule');
}

// APRÈS
public function votesSenat() {
    return $this->hasMany(VoteSenat::class, 'senateur_matricule', 'matricule');
}
public function amendementsSenat() {
    return $this->hasMany(AmendementSenat::class, 'senateur_matricule', 'matricule');
}
```

### 2. Modèle `VoteSenat`
```php
// AVANT
public function senateur() {
    return $this->belongsTo(Senateur::class, 'senateur_matricule', 'id');
}

// APRÈS
public function senateur() {
    return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
}
```

### 3. Modèle `AmendementSenat`
```php
// AVANT
public function auteur() {
    return $this->belongsTo(Senateur::class, 'auteur_senateur_matricule', 'matricule');
}
// Scopes utilisaient 'ADOPTE', 'REJETE', 'RETIRE'

// APRÈS
public function auteur() {
    return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
}
// Scopes supportent ADO/ADOPTE/Adopté, REJ/REJETE/Rejeté, RET/RETIRE/Retiré
```

### 4. Modèles SenateurMandat, SenateurCommission, SenateurHistoriqueGroupe
```php
// AVANT
public function senateur() {
    return $this->belongsTo(Senateur::class, 'matricule', 'matricule');
}

// APRÈS
public function senateur() {
    return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
}
```

---

## ✅ CORRECTIONS CONTROLLER

### RepresentantANController

#### senateurAmendements / senateurActivite
```php
// AVANT
$adoptes = $statsQuery->clone()->where('sort_code', 'ADO')->count();
$rejetes = $statsQuery->clone()->where('sort_code', 'REJ')->count();

// APRÈS
$adoptes = $statsQuery->clone()->adoptes()->count();  // Utilise scopes
$rejetes = $statsQuery->clone()->rejetes()->count();
```

#### Filtre amendements
```php
// AVANT
if ($request->filled('sort')) {
    $query->where('sort_code', $request->sort);
}

// APRÈS
if ($request->filled('sort')) {
    switch ($request->sort) {
        case 'ADO': $query->adoptes(); break;
        case 'REJ': $query->rejetes(); break;
        case 'RET': $query->retires(); break;
    }
}
```

#### showSenateur - Ajout statistiques
```php
// AJOUTÉ
$votesTotal = VoteSenat::where('senateur_matricule', $matricule)->count();
$amendementsTotal = AmendementSenat::where('senateur_matricule', $matricule)->count();
$amendementsAdoptes = AmendementSenat::where('senateur_matricule', $matricule)->adoptes()->count();

$stats = [
    'votes_total' => $votesTotal,
    'amendements_total' => $amendementsTotal,
    'amendements_adoptes' => $amendementsAdoptes,
    'taux_adoption_amendements' => ...
];

// Passé à la vue
'statistiques' => $stats,
```

#### senateurVotes - Optimisation
```php
// AVANT
$votesData = $votes->through(function($vote) {
    $scrutin = ScrutinSenat::find($vote->scrutin_id);  // Requête N+1
    return [...];
});

// APRÈS
$votesData = $votes->through(function($vote) {
    return [
        'scrutin' => $vote->scrutin ? [  // Utilise relation eager loaded
            'pour' => $vote->scrutin->pour ?? 0,
            'contre' => $vote->scrutin->contre ?? 0,
            ...
        ] : null,
    ];
});
```

---

## ✅ CORRECTIONS VUES VUE.JS

### Senateurs/Votes.vue
```vue
<!-- AVANT -->
<div v-if="vote.scrutin_pour !== undefined">
  Pour: {{ vote.scrutin_pour || 0 }}
  Contre: {{ vote.scrutin_contre || 0 }}
</div>

<!-- APRÈS -->
<div v-if="vote.scrutin">
  Pour: {{ vote.scrutin.pour || 0 }}
  Contre: {{ vote.scrutin.contre || 0 }}
</div>
```

### Senateurs/Show.vue
```vue
<!-- AJOUTÉ : Bloc statistiques rapides -->
<div v-if="senateur.statistiques" class="grid grid-cols-3 gap-4 mt-6 mb-4">
  <div class="text-center p-3 bg-blue-50 rounded-lg">
    <div class="text-2xl font-bold text-blue-600">{{ senateur.statistiques.votes_total }}</div>
    <div class="text-xs">Votes</div>
  </div>
  <div class="text-center p-3 bg-green-50 rounded-lg">
    <div class="text-2xl font-bold text-green-600">{{ senateur.statistiques.amendements_total }}</div>
    <div class="text-xs">Amendements</div>
  </div>
  <div class="text-center p-3 bg-purple-50 rounded-lg">
    <div class="text-2xl font-bold text-purple-600">{{ senateur.statistiques.taux_adoption_amendements }}%</div>
    <div class="text-xs">Taux adoption</div>
  </div>
</div>
```

---

## 📁 FICHIERS MODIFIÉS

### Modèles
- `app/Models/Senateur.php`
- `app/Models/VoteSenat.php`
- `app/Models/AmendementSenat.php`
- `app/Models/SenateurMandat.php`
- `app/Models/SenateurCommission.php`
- `app/Models/SenateurHistoriqueGroupe.php`

### Controller
- `app/Http/Controllers/Web/RepresentantANController.php`

### Vues
- `resources/js/Pages/Representants/Senateurs/Votes.vue`
- `resources/js/Pages/Representants/Senateurs/Show.vue`

### Scripts
- `scripts/diagnostic_amendements_votes.sh` (nouveau)

### Documentation
- `ANALYSE_AMENDEMENTS_COMPLET.md` (nouveau)

---

## 🎯 RÉSULTAT ATTENDU

Après ces corrections :
1. ✅ Amendements sénateurs s'affichent avec le bon matricule
2. ✅ Votes sénateurs affichent position + détails scrutin
3. ✅ Statistiques sur page Show sénateur (comme députés)
4. ✅ Scopes supportent tous les formats de codes (ADO/ADOPTE/Adopté)
5. ✅ Relations Eloquent fonctionnent avec les vues SQL

---

## 🔍 POUR TESTER

```bash
# Lancer le script de diagnostic
./scripts/diagnostic_amendements_votes.sh

# Ou manuellement via tinker
php artisan tinker --execute="
\$senateur = App\Models\Senateur::where('etat', 'ACTIF')->first();
echo 'Sénateur: ' . \$senateur->nom_usuel . PHP_EOL;
echo 'Votes: ' . \$senateur->votesSenat()->count() . PHP_EOL;
echo 'Amendements: ' . \$senateur->amendementsSenat()->count() . PHP_EOL;
"
```

---

**Session 21 nov 2025 - 33 commits prêts**

