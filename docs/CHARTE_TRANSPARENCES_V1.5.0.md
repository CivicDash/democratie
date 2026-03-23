# 🛡️ CivicDash — Charte de Transparence & Workflow de Confiance
## Affaires Judiciaires des Élus — v1.5.0

> **Objectif** : Ce document décrit la démarche méthodologique de CivicDash pour le  
> référencement des affaires judiciaires. Il sert de guide interne pour l'agent qui code  
> ET de base pour la page publique "Notre démarche" affichée sur le site.

---

## 🎯 Pourquoi ce document existe

casier-politique.fr utilise une IA qui se contente de copier-coller Wikipedia. Les critiques sont immédiates et justifiées : données manquantes (Mélenchon condamné pour rébellion absent, Quatennens pour violences conjugales absent), biais évidents : un parti "jeune" a eu moins de temps pour accumuler les points, un parti avec peu d'élus aura mécaniquement moins de points, et le site bug tellement que les filtres ne fonctionnent sur aucun navigateur.

CivicDash refuse cette approche. Notre démarche repose sur un principe simple :

> **L'algorithme détecte, l'humain vérifie, le citoyen consulte.**  
> Rien ne s'affiche sans qu'un modérateur ait personnellement vérifié chaque information.

---

## 📋 Les 7 engagements de CivicDash

### 1. Validation humaine obligatoire

Chaque affaire judiciaire passe par un workflow de validation avant toute publication. Un algorithme peut détecter une information dans Wikidata ou Wikipedia, mais **seul un modérateur humain peut décider de la publier** après vérification des sources.

Le statut de validation suit ce parcours :

```
  DÉTECTÉ          EN REVIEW         VALIDÉ            PUBLIÉ
  (algo)    ──→    (modo prend    ──→ (modo vérifie  ──→ (visible sur
                    en charge)         sources, corrige    la fiche élu)
                                       et confirme)
                        │
                        ├──→ REJETÉ (faux positif, erreur)
                        ├──→ À COMPLÉTER (infos partielles)
                        └──→ CONTESTÉ (l'élu ou un citoyen signale une erreur)
```

**Règle technique** : le champ `affiche_publiquement` est à `false` par défaut dans la base de données. Il ne passe à `true` que lorsqu'un modérateur exécute explicitement l'action "Valider et publier". Aucun processus automatique ne peut modifier ce champ.

### 2. Sources vérifiables obligatoires

Pour qu'un modérateur puisse valider une affaire, il doit obligatoirement :
- Renseigner **au moins une source** de fiabilité "haute" ou "moyenne"
- Chaque source a une URL vérifiable que n'importe quel citoyen peut consulter

**Niveaux de fiabilité des sources :**

| Niveau | Types de sources | Exemples |
|--------|-----------------|----------|
| **Haute** | Décisions de justice publiées, Journal Officiel, rapports d'institutions | Légifrance, HATVP, CNCCFP, JO |
| **Moyenne** | Presse nationale de référence, agences de presse | Le Monde, Mediapart, AFP, Le Figaro, Libération |
| **Basse** | Presse locale, blogs, réseaux sociaux | *(non suffisant seul pour valider)* |

Une source de fiabilité "basse" seule ne permet pas la publication. Il faut au minimum une source "haute" ou "moyenne".

### 3. Distinction claire des statuts judiciaires

CivicDash distingue rigoureusement les statuts suivants et les affiche avec des codes couleur différents :

| Statut | Signification | Couleur | Mention affichée |
|--------|--------------|---------|-----------------|
| Condamné (définitif) | Condamnation sans recours possible | 🔴 Rouge | "Condamnation définitive" |
| Condamné (appel) | Condamné mais appel en cours | 🟠 Orange | "Condamné — pourvoi en cours" |
| Condamné (1ère inst.) | Condamné, appel possible ou en cours | 🟡 Jaune | "Condamné en 1ère instance" |
| Mis en examen | Procédure en cours, pas de jugement | 🟡 Jaune | "Mis en examen" |
| Procédure en cours | Enquête ou instruction | ⚪ Gris | "Procédure en cours" |
| Relaxé / Acquitté | Innocenté par la justice | 🟢 Vert | "Relaxé" / "Acquitté" |

**Une mise en examen n'est PAS une condamnation.** CivicDash l'indique systématiquement.

### 4. Présomption d'innocence respectée

Chaque fiche d'élu comportant des affaires judiciaires affiche obligatoirement le disclaimer suivant :

> *Conformément à l'article 9-1 du Code civil, toute personne a droit au respect de la présomption d'innocence. Une mise en examen ne constitue pas une condamnation. Seule une condamnation définitive établit la responsabilité pénale. Données vérifiées par l'équipe CivicDash à partir de sources publiques.*

Ce texte est codé dans un composant Vue dédié (`PresomptionInnocence.vue`) qui est **obligatoirement inclus** partout où des affaires judiciaires sont affichées. Il ne peut pas être désactivé ou masqué.

### 5. Ratios normalisés, jamais de totaux bruts seuls

Quand CivicDash présente des statistiques par parti politique, les données sont **toujours normalisées** :

**Ce que CivicDash affiche :**
```
Les Républicains : 0,34 condamnation(s) pour 100 élus
   → 28 condamnations définitives / 8 200 élus recensés
   
Rassemblement National : 0,28 condamnation(s) pour 100 élus
   → 12 condamnations définitives / 4 300 élus recensés
```

**Ce que CivicDash ne fait PAS :**
```
❌ "LR : 28 condamnations" (sans contexte)
❌ Graphique de totaux bruts sans ratio
❌ Classement par total sans normalisation
```

Les biais connus sont explicitement mentionnés dans la page de statistiques :

> *Biais temporel : un parti historique accumule mécaniquement plus d'affaires qu'un parti récent. Les données couvrent la période [date] à [date].*
>
> *Biais de couverture : les élus nationaux (députés, sénateurs) sont mieux documentés que les élus locaux. La couverture des maires est limitée aux communes de plus de 10 000 habitants.*
>
> *Biais médiatique : les élus très médiatisés font l'objet de plus d'articles de presse, ce qui augmente la probabilité de détection automatique.*

### 6. Droit de réponse et contestation

Tout élu ou citoyen peut contester une information en écrivant à contact@civicdash.fr. Le processus de contestation est :

1. L'affaire passe en statut "Contesté"
2. Un modérateur examine la contestation sous 72h
3. Trois issues possibles :
   - **Maintien** : les sources confirment l'information → retour à "Validé" avec mention "Contesté et maintenu"
   - **Correction** : l'information était partiellement inexacte → mise à jour + log de modification
   - **Retrait** : l'information était fausse → passage en "Archivé", retrait de l'affichage public

Chaque contestation et sa résolution sont tracées dans les logs de modération.

### 7. Traçabilité complète

Chaque action sur une affaire judiciaire est journalisée dans `affaires_moderation_logs` :

| Action | Qui | Quand | Commentaire |
|--------|-----|-------|-------------|
| Détection | Système (auto) | 2026-03-25 02:00 | Source: Wikidata, confiance: 0.82 |
| Prise en charge | @moderateur1 | 2026-03-25 10:15 | — |
| Validation | @moderateur1 | 2026-03-25 10:45 | "Vérifié via Légifrance ref. XYZ + Le Monde 15/03/2024" |
| Contestation | contact@civicdash.fr | 2026-04-02 | "L'élu a été relaxé en appel" |
| Mise à jour | @admin1 | 2026-04-03 | "Statut passé de condamné_premiere_instance à relaxe. Source: CA Paris, 01/02/2026" |

Un modérateur peut à tout moment consulter l'historique complet d'une affaire.

---

## 🏗️ Implémentation technique du workflow de confiance

### Badges de confiance affichés publiquement

Chaque affaire affichée sur la fiche d'un élu porte un **badge de vérification** visible par le citoyen :

```vue
<!-- Composant VerificationBadge.vue -->
<template>
  <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
       :class="badgeClasses">
    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
      <!-- Icône shield-check -->
      <path fill-rule="evenodd" d="M10 1a.75.75 0 01.59.29l..." />
    </svg>
    <span>{{ label }}</span>
  </div>
</template>

<!--
  Trois variantes :
  
  🟢 "Vérifié par CivicDash" (bg-green-100 text-green-800)
     → Affiché quand statut_validation = 'valide'
     → Tooltip : "Information vérifiée par un modérateur le {date}.
                  Sources : {nb} source(s) vérifiée(s)."
  
  🟡 "Contesté — en cours de vérification" (bg-yellow-100 text-yellow-800)
     → Affiché quand statut_validation = 'conteste'
     
  🔵 "Mis à jour le {date}" (bg-blue-100 text-blue-800)
     → Affiché quand l'affaire a été modifiée après sa validation initiale
-->
```

### Section "Notre démarche" — Page publique

Créer une page accessible depuis le footer et depuis chaque section affaires judiciaires :

```
Route : /transparence/notre-demarche
Page Vue : Transparence/NotreDemarche.vue
```

Contenu de la page (rendu en Markdown ou Vue statique) :

```
┌─────────────────────────────────────────────────────────┐
│  🛡️ Notre démarche                                      │
│                                                         │
│  CivicDash n'est PAS un copier-coller de Wikipedia.     │
│  Voici comment nous travaillons.                        │
│                                                         │
│  ┌─ ÉTAPE 1 : DÉTECTION ────────────────────────────┐  │
│  │ Nos algorithmes analysent des sources publiques   │  │
│  │ structurées (Wikidata, HATVP, CNCCFP) pour        │  │
│  │ identifier d'éventuelles affaires judiciaires.    │  │
│  │                                                    │  │
│  │ ⚠️ Rien n'est publié à cette étape.               │  │
│  └────────────────────────────────────────────────────┘  │
│           │                                              │
│           ▼                                              │
│  ┌─ ÉTAPE 2 : VÉRIFICATION HUMAINE ─────────────────┐  │
│  │ Un modérateur CivicDash examine chaque détection. │  │
│  │ Il doit :                                          │  │
│  │  ✓ Vérifier au moins 1 source fiable (presse      │  │
│  │    nationale, décision de justice, rapport officiel)│  │
│  │  ✓ Qualifier précisément le statut judiciaire      │  │
│  │    (mis en examen ≠ condamné ≠ relaxé)             │  │
│  │  ✓ Renseigner les dates et la juridiction          │  │
│  │                                                    │  │
│  │ Si l'info est incorrecte ou non vérifiable,        │  │
│  │ elle est rejetée. Elle ne sera JAMAIS publiée.     │  │
│  └────────────────────────────────────────────────────┘  │
│           │                                              │
│           ▼                                              │
│  ┌─ ÉTAPE 3 : PUBLICATION ──────────────────────────┐  │
│  │ L'information apparaît sur la fiche de l'élu      │  │
│  │ avec :                                             │  │
│  │  🟢 Un badge "Vérifié par CivicDash"              │  │
│  │  📰 La liste des sources vérifiables               │  │
│  │  ⚖️ Le statut judiciaire précis avec code couleur │  │
│  │  📜 La mention de présomption d'innocence          │  │
│  └────────────────────────────────────────────────────┘  │
│           │                                              │
│           ▼                                              │
│  ┌─ ÉTAPE 4 : SUIVI CONTINU ────────────────────────┐  │
│  │ Les affaires sont mises à jour quand la situation │  │
│  │ évolue : relaxe, appel, condamnation définitive.  │  │
│  │                                                    │  │
│  │ Tout citoyen ou élu peut contester une info.       │  │
│  │ Un modérateur traite chaque contestation sous 72h. │  │
│  │                                                    │  │
│  │ L'historique complet des modifications est          │  │
│  │ consultable pour chaque affaire.                   │  │
│  └────────────────────────────────────────────────────┘  │
│                                                         │
│  ── Ce que nous ne faisons PAS ──                       │
│                                                         │
│  ❌ Publier des données brutes sans vérification        │
│  ❌ Copier-coller Wikipedia ou Wikidata aveuglément     │
│  ❌ Afficher des totaux bruts par parti sans ratio      │
│  ❌ Mélanger mis en examen et condamnés                 │
│  ❌ Inclure des infractions sans lien avec la fonction  │
│  ❌ Ignorer les relaxes et acquittements                │
│                                                         │
│  ── Nos biais connus (honnêteté intellectuelle) ──      │
│                                                         │
│  ⚠️ Biais temporel : les partis anciens cumulent       │
│     mécaniquement plus d'affaires.                      │
│  ⚠️ Biais de couverture : les élus nationaux sont      │
│     mieux documentés que les élus locaux.               │
│  ⚠️ Biais médiatique : les élus médiatisés génèrent   │
│     plus d'articles → plus de détections.               │
│                                                         │
│  C'est pourquoi nous normalisons toujours nos stats     │
│  par le ratio condamnations / nombre d'élus.            │
│                                                         │
│  ── Contact ──                                          │
│  Signaler une erreur : contact@civicdash.fr             │
│  Code source : github.com/CivicDash/democratie (AGPL)  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Lien vers "Notre démarche" partout

Le lien apparaît à trois endroits :

1. **Dans `PresomptionInnocence.vue`** (affiché sous chaque section affaires) :
   ```
   📖 Comment vérifions-nous ces informations ? → /transparence/notre-demarche
   ```

2. **Dans la page Transparence/AffairesJudiciaires.vue** (stats publiques) :
   ```
   En-tête : "Toutes les données présentées ici sont vérifiées par un modérateur."
   Lien : "Découvrir notre démarche →"
   ```

3. **Dans le footer global du site** :
   ```
   Transparence | Notre démarche | Mentions légales
   ```

### Indicateurs de confiance dans l'admin

Le dashboard admin affiche des métriques de santé du workflow :

```php
// Dans AdminAffairesJudiciairesController::index()
'health_metrics' => [
    'detectees_non_reviewees' => AffaireJudiciaire::enAttente()
        ->where('detecte_at', '<', now()->subDays(7))->count(),
    // ↑ Affaires en attente depuis plus de 7 jours = problème
    
    'contestees_non_traitees' => AffaireJudiciaire::where('statut_validation', 'conteste')
        ->where('updated_at', '<', now()->subHours(72))->count(),
    // ↑ Contestations > 72h non traitées = urgence
    
    'sans_source_haute' => AffaireJudiciaire::publiques()
        ->whereDoesntHave('sources', fn($q) => $q->where('fiabilite', 'haute'))
        ->count(),
    // ↑ Affaires publiées sans source haute = à renforcer
    
    'taux_rejet' => // % d'affaires détectées puis rejetées (qualité de l'algo)
    'delai_moyen_validation' => // Temps moyen entre détection et validation
],
```

---

## 🆚 Comparaison CivicDash vs casier-politique.fr

| Critère | casier-politique.fr | CivicDash |
|---------|-------------------|-----------|
| **Source des données** | IA copie Wikipedia | Wikidata structuré + Wikipedia NLP + HATVP officiel |
| **Validation humaine** | ❌ Aucune | ✅ Obligatoire avant publication |
| **Sources vérifiables** | ❌ Juste "Wikipedia" | ✅ URL cliquables, fiabilité notée |
| **Statuts judiciaires** | ❌ Tout mélangé | ✅ 10 statuts distincts avec couleurs |
| **Présomption d'innocence** | ❌ Mention générique | ✅ Disclaimer juridique sur chaque fiche |
| **Normalisation stats** | ❌ Totaux bruts | ✅ Ratio pour 100 élus systématique |
| **Biais déclarés** | ❌ Non mentionnés | ✅ Listés publiquement |
| **Droit de réponse** | ❌ Inexistant | ✅ Contestation traitée sous 72h |
| **Traçabilité** | ❌ Opaque | ✅ Historique complet consultable |
| **Relaxes/acquittements** | ❌ Souvent absents | ✅ Statut vert, mis à jour |
| **Code source** | ❌ Fermé | ✅ AGPL-3.0 auditable |
| **Non-élus inclus** | ⚠️ Zemmour dedans | ✅ Uniquement élus en exercice ou anciens élus |

---

## 📝 Fichiers à créer/modifier pour cette charte

### Nouveaux fichiers

```
resources/js/Pages/Transparence/NotreDemarche.vue     — Page publique "Notre démarche"
resources/js/Components/AffairesJudiciaires/
  ├── VerificationBadge.vue                            — Badge vert/jaune/bleu
  └── PresomptionInnocence.vue                         — Disclaimer + lien démarche
```

### Modifications

```
routes/web.php
  → Ajouter Route::get('/transparence/notre-demarche', ...)

resources/js/Layouts/AppLayout.vue (ou Footer.vue)
  → Ajouter lien "Notre démarche" dans le footer
```

### Données de la page (pas besoin de controller complexe)

La page "Notre démarche" est essentiellement statique. Elle peut être un simple composant Vue avec du contenu hardcodé, éventuellement enrichi de quelques chiffres dynamiques :

```php
// Route simple
Route::get('/transparence/notre-demarche', function () {
    return Inertia::render('Transparence/NotreDemarche', [
        'stats' => [
            'total_validees' => AffaireJudiciaire::publiques()->count(),
            'total_rejetees' => AffaireJudiciaire::where('statut_validation', 'rejete')->count(),
            'taux_rejet_pct' => // calculé
            'total_sources' => AffaireSource::whereHas('affaire', fn($q) => $q->publiques())->count(),
            'derniere_validation' => AffaireJudiciaire::publiques()->max('valide_at'),
        ],
    ]);
})->name('transparence.demarche');
```

---

## 🔒 Règles de validation du FormRequest (récapitulatif)

Pour renforcer le workflow au niveau code, le `ValiderAffaireRequest` impose :

```php
// Impossible de publier sans :
'titre'              => 'required|string|max:500',
'type_affaire'       => 'required|in:...',
'categorie'          => 'required|in:...',
'statut_judiciaire'  => 'required|in:...',

// Au moins une date
'date_mise_en_examen|date_jugement_premiere_instance|date_condamnation_definitive'
  => 'au moins 1 remplie',

// Au moins une source de fiabilité haute ou moyenne
'sources'            => 'required|array|min:1',
'sources.*.url'      => 'required|url',
'sources.*.fiabilite'=> 'required|in:haute,moyenne,basse',

// Validation custom : au moins 1 source NON basse
public function withValidator($validator) {
    $validator->after(function ($v) {
        $sources = collect($this->input('sources', []));
        $hasReliable = $sources->contains(fn($s) =>
            in_array($s['fiabilite'] ?? '', ['haute', 'moyenne'])
        );
        if (!$hasReliable) {
            $v->errors()->add('sources',
                'Au moins une source de fiabilité "haute" ou "moyenne" est requise.');
        }
    });
}
```

---

*Ce document fait partie intégrante du plan v1.5.0 de CivicDash.*
*Il sera affiché publiquement sur le site sous une forme adaptée.*