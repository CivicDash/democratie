# 🔗 PONT PROPOSITIONS ↔ DONNÉES AN

## 🎯 Objectif

Connecter les propositions de loi citoyennes aux vraies données parlementaires pour afficher :
- Timeline réelle des étapes législatives
- Votes réels des scrutins
- Répartition par groupe politique
- Amendements déposés
- Comparaison vote AN vs vote citoyen

---

## 📊 ARCHITECTURE ACTUELLE

### Tables existantes

**Ancien système (citoyen) :**
- `propositions` - Propositions citoyennes
- `topic_ballots` - Votes citoyens

**Nouveau système (AN) :**
- `dossiers_legislatifs_an` - Dossiers officiels
- `textes_legislatifs_an` - Textes de loi
- `scrutins_an` - Scrutins officiels
- `amendements_an` - Amendements
- `votes_individuels_an` - Votes des députés

---

## 🔧 SOLUTION 1 : Lier Proposition → Dossier AN

### Migration : Ajouter colonne de liaison

```php
Schema::table('propositions', function (Blueprint $table) {
    $table->string('dossier_legislatif_uid', 30)->nullable()->after('numero');
    $table->foreign('dossier_legislatif_uid')
        ->references('uid')
        ->on('dossiers_legislatifs_an')
        ->onDelete('set null');
    
    $table->index('dossier_legislatif_uid');
});
```

### Modèle Proposition : Ajouter relation

```php
public function dossierLegislatif(): BelongsTo
{
    return $this->belongsTo(DossierLegislatifAN::class, 'dossier_legislatif_uid', 'uid');
}
```

---

## 🔧 SOLUTION 2 : Créer table de mapping

Si une proposition peut être liée à plusieurs dossiers ou vice-versa :

```php
Schema::create('proposition_dossier_mapping', function (Blueprint $table) {
    $table->id();
    $table->foreignId('proposition_id')->constrained()->onDelete('cascade');
    $table->string('dossier_legislatif_uid', 30);
    $table->foreign('dossier_legislatif_uid')
        ->references('uid')
        ->on('dossiers_legislatifs_an')
        ->onDelete('cascade');
    $table->enum('type', ['inspire', 'similaire', 'oppose'])->default('similaire');
    $table->timestamps();
    
    $table->unique(['proposition_id', 'dossier_legislatif_uid']);
});
```

---

## 📋 ÉTAPES D'IMPLÉMENTATION

### 1. Migration + Modèle

```bash
php artisan make:migration add_dossier_link_to_propositions
php artisan migrate
```

### 2. Contrôleur : Enrichir les données

```php
// LegislationController::show()
public function show(string $id): Response
{
    $proposition = Proposition::with([
        'dossierLegislatif',
        'dossierLegislatif.textesLegislatifs',
        'dossierLegislatif.textesLegislatifs.amendements',
    ])->findOrFail($id);

    // Si lié à un dossier AN
    if ($proposition->dossierLegislatif) {
        $dossier = $proposition->dossierLegislatif;
        
        // Récupérer les scrutins liés
        $scrutins = ScrutinAN::whereHas('texte.dossier', function ($q) use ($dossier) {
            $q->where('uid', $dossier->uid);
        })
        ->orderBy('date_scrutin')
        ->get();
        
        // Récupérer les amendements
        $amendements = AmendementAN::whereHas('texte', function ($q) use ($dossier) {
            $q->where('dossier_ref', $dossier->uid);
        })
        ->with('auteur')
        ->latest()
        ->limit(20)
        ->get();
        
        // Timeline réelle
        $timeline = $this->buildRealTimeline($dossier, $scrutins);
        
        // Votes par groupe
        $votesParGroupe = $this->getVotesParGroupe($scrutins);
        
        return Inertia::render('Legislation/Show', [
            'proposition' => $proposition,
            'dossier' => $dossier,
            'scrutins' => $scrutins,
            'amendements' => $amendements,
            'timeline' => $timeline,
            'votesParGroupe' => $votesParGroupe,
            'hasRealData' => true,
        ]);
    }
    
    // Sinon, données synthétiques (comme avant)
    return Inertia::render('Legislation/Show', [
        'proposition' => $proposition,
        'hasRealData' => false,
        // ... données synthétiques
    ]);
}
```

### 3. Frontend : Afficher données réelles

```vue
<template>
  <div v-if="hasRealData">
    <!-- Timeline réelle -->
    <LegislativeTimeline :events="timeline" />
    
    <!-- Scrutins réels -->
    <Card>
      <h2>🗳️ Scrutins</h2>
      <div v-for="scrutin in scrutins" :key="scrutin.uid">
        <Link :href="route('legislation.scrutins.show', scrutin.uid)">
          Scrutin n°{{ scrutin.numero }} - {{ scrutin.date }}
        </Link>
        <div>{{ scrutin.pour }} pour / {{ scrutin.contre }} contre</div>
      </div>
    </Card>
    
    <!-- Amendements réels -->
    <Card>
      <h2>📝 Amendements</h2>
      <div v-for="amendement in amendements" :key="amendement.uid">
        <Link :href="route('legislation.amendements.show', amendement.uid)">
          {{ amendement.numero }} - {{ amendement.auteur?.nom }}
        </Link>
      </div>
    </Card>
    
    <!-- Comparaison vote AN vs citoyen -->
    <Link :href="route('legislation.scrutins.comparaison', scrutins[0].uid)">
      ⚖️ Comparer avec vote citoyen
    </Link>
  </div>
  
  <div v-else>
    <!-- Données synthétiques (ancien système) -->
    ...
  </div>
</template>
```

---

## 🎯 WORKFLOW COMPLET

### Pour une proposition citoyenne nouvelle

1. Citoyen crée proposition
2. Admin/modérateur peut lier à un dossier AN existant
3. → Affichage automatique des vraies données

### Pour un dossier AN existant

1. Import automatique via commandes
2. Création automatique d'un Topic pour débat citoyen
3. → Lien bidirectionnel Topic ↔ Dossier

---

## 🚀 COMMANDE D'IMPORT INTELLIGENTE

```php
// ImportDossiersTextes avec création Topics
foreach ($dossiers as $dossier) {
    // Créer/mettre à jour dossier
    $dossierAN = DossierLegislatifAN::updateOrCreate(...);
    
    // Créer topic citoyen si important
    if ($this->isImportantDossier($dossier)) {
        $topic = Topic::firstOrCreate([
            'dossier_legislatif_uid' => $dossierAN->uid,
        ], [
            'title' => $dossierAN->titre_court,
            'description' => $dossierAN->titre,
            'type' => 'bill',
            'status' => 'open',
            'has_ballot' => true,
            'ballot_type' => 'yes_no',
        ]);
        
        // Attacher tags automatiquement
        $tags = $this->detectTags($dossierAN->titre);
        $topic->tags()->sync($tags);
    }
}
```

---

## ✅ RÉSULTAT FINAL

**Page proposition enrichie :**
- ✅ Timeline réelle des étapes
- ✅ Scrutins officiels avec résultats
- ✅ Amendements déposés
- ✅ Votes par groupe politique
- ✅ Comparaison AN vs citoyen
- ✅ Lien vers débat citoyen

**Cohérence totale entre :**
- Données officielles AN
- Débats citoyens
- Votes citoyens
- Comparaisons

