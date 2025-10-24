# 🎨 Documentation Frontend - CivicDash

## 📖 Vue d'ensemble

Le frontend de CivicDash est construit avec :
- **Vue 3** (Composition API)
- **Inertia.js** (SPA avec Laravel)
- **Tailwind CSS** (Styling)
- **Vite** (Build tool)

## 🏗️ Structure du Projet

```
resources/js/
├── Components/          # Composants réutilisables
│   ├── Alert.vue       # Alertes (success, error, warning, info)
│   ├── Badge.vue       # Badges colorés
│   ├── Card.vue        # Container card
│   ├── EmptyState.vue  # État vide
│   ├── LoadingSpinner.vue  # Spinner de chargement
│   ├── Pagination.vue  # Pagination des listes
│   └── ...             # Composants Breeze (Buttons, Inputs, etc.)
├── Layouts/            # Layouts de page
│   ├── MainLayout.vue  # Layout public avec navigation
│   ├── AuthenticatedLayout.vue  # Layout authentifié
│   └── GuestLayout.vue # Layout invité
├── Pages/              # Pages de l'application
│   ├── Topics/         # Forum citoyen
│   │   ├── Index.vue   # Liste des topics
│   │   ├── Show.vue    # Détails d'un topic
│   │   └── Create.vue  # Création d'un topic
│   ├── Vote/           # Vote anonyme
│   │   └── Show.vue    # Page de vote
│   ├── Budget/         # Budget participatif
│   │   ├── Index.vue   # Allocation du budget
│   │   └── Stats.vue   # Statistiques du budget
│   ├── Moderation/     # Modération
│   │   └── Dashboard.vue  # Dashboard modérateur
│   ├── Documents/      # Documents publics
│   │   └── Index.vue   # Liste et upload de documents
│   └── Auth/           # Authentification (Breeze)
└── app.js              # Point d'entrée

```

## 🎨 Composants UI

### Alert
Affiche des messages avec différents types.

```vue
<Alert type="success" dismissible @dismiss="handleDismiss">
  ✅ Votre allocation a été enregistrée !
</Alert>
```

**Props:**
- `type`: `'info' | 'success' | 'warning' | 'error'` (défaut: `'info'`)
- `dismissible`: `boolean` (défaut: `false`)

**Events:**
- `dismiss`: Émis quand l'alerte est fermée

### Badge
Badge coloré pour afficher des statuts ou catégories.

```vue
<Badge variant="green" size="md">✅ Vérifié</Badge>
```

**Props:**
- `variant`: `'gray' | 'blue' | 'green' | 'yellow' | 'red' | 'indigo'` (défaut: `'gray'`)
- `size`: `'sm' | 'md' | 'lg'` (défaut: `'md'`)

### Card
Container avec ombre et padding.

```vue
<Card padding="p-6">
  <h3>Titre</h3>
  <p>Contenu...</p>
</Card>
```

**Props:**
- `padding`: `string` (défaut: `'p-6'`)

### EmptyState
État vide avec icône et message.

```vue
<EmptyState 
  icon="📭" 
  title="Aucun sujet trouvé"
  description="Il n'y a pas encore de sujet."
>
  <PrimaryButton>Créer le premier</PrimaryButton>
</EmptyState>
```

**Props:**
- `icon`: `string` (défaut: `'📭'`)
- `title`: `string` (requis)
- `description`: `string` (optionnel)

### LoadingSpinner
Spinner de chargement animé.

```vue
<LoadingSpinner size="lg" />
```

**Props:**
- `size`: `'sm' | 'md' | 'lg'` (défaut: `'md'`)

### Pagination
Pagination avec liens.

```vue
<Pagination :links="topics.links" />
```

**Props:**
- `links`: `Array` (requis, format Laravel pagination)

## 📄 Pages

### Topics (Forum Citoyen)

#### Index.vue
Liste des topics avec filtres (recherche, scope, type).

**Props:**
- `topics`: Pagination object
- `filters`: Object avec search, scope, type

**Features:**
- Recherche par titre
- Filtres par portée (national, régional, départemental)
- Filtres par type (débat, proposition, question, annonce)
- Affichage des badges de statut
- Lien vers la création de topic

#### Show.vue
Détails d'un topic avec posts et formulaire de réponse.

**Props:**
- `topic`: Object
- `posts`: Array
- `can`: Object (permissions)

**Features:**
- Affichage du topic avec métadonnées
- Liste des réponses avec système de vote (upvote/downvote)
- Formulaire de réponse (si authentifié et autorisé)
- Lien vers le vote (si scrutin ouvert)

#### Create.vue
Formulaire de création de topic.

**Props:**
- `regions`: Array
- `departments`: Array

**Features:**
- Sélection du type de sujet
- Sélection de la portée géographique
- Sélection région/département (si applicable)
- Validation en temps réel

### Vote

#### Show.vue
Page de vote anonyme en 3 étapes.

**Props:**
- `topic`: Object
- `ballotOptions`: Array (pour vote multiple)
- `hasVoted`: Boolean
- `results`: Object

**Features:**
- **Étape 1:** Demande de jeton anonyme
- **Étape 2:** Vote (binaire Oui/Non ou choix multiple)
- **Étape 3:** Résultats avec graphiques
- Protection : un seul vote par utilisateur
- Affichage en temps réel des résultats

### Budget

#### Index.vue
Allocation du budget participatif (100%).

**Props:**
- `sectors`: Array
- `userAllocations`: Object
- `averages`: Object (moyennes communauté)
- `stats`: Object

**Features:**
- Sliders/inputs pour chaque secteur
- Validation 100% total
- Comparaison avec moyennes communauté
- Boutons "Égaliser" et "Moyennes"
- Sauvegarde de l'allocation
- Réinitialisation possible

#### Stats.vue
Statistiques du budget participatif.

**Props:**
- `sectors`: Array
- `averages`: Object
- `ranking`: Array
- `stats`: Object

**Features:**
- Vue d'ensemble (participants, secteurs)
- Graphiques des allocations moyennes
- Classement des secteurs prioritaires
- Badges podium (🥇🥈🥉)

### Moderation

#### Dashboard.vue
Dashboard pour modérateurs.

**Props:**
- `stats`: Object
- `recentReports`: Array
- `topModerators`: Array

**Features:**
- Vue d'ensemble (en attente, investigation, résolus)
- Liste des signalements récents
- Top modérateurs
- Actions rapides (prioritaires, en attente, sanctions, stats)

### Documents

#### Index.vue
Liste et upload de documents publics.

**Props:**
- `documents`: Pagination object
- `filters`: Object
- `stats`: Object

**Features:**
- Liste des documents avec filtres (type, statut)
- Formulaire d'upload (si authentifié)
- Téléchargement de documents
- Badges de type et statut de vérification
- Stats (total, vérifiés, en attente)

## 🎭 Layouts

### MainLayout
Layout principal pour pages publiques.

**Features:**
- Navigation avec logo CivicDash
- Liens : Forum, Budget, Documents
- Menu utilisateur (si authentifié) ou liens Connexion/Inscription
- Footer avec liens et infos open source
- Responsive avec menu hamburger

### AuthenticatedLayout
Layout pour pages authentifiées (provient de Breeze).

**Features:**
- Navigation avec dropdown utilisateur
- Lien Dashboard
- Responsive

### GuestLayout
Layout pour pages invités (provient de Breeze).

**Features:**
- Logo centré
- Design épuré pour login/register

## 🎨 Conventions de Design

### Couleurs
- **Primary:** Indigo (`bg-indigo-600`, `text-indigo-600`)
- **Success:** Green (`bg-green-600`, `text-green-600`)
- **Warning:** Yellow (`bg-yellow-600`, `text-yellow-600`)
- **Error:** Red (`bg-red-600`, `text-red-600`)
- **Info:** Blue (`bg-blue-600`, `text-blue-600`)

### Dark Mode
Tous les composants supportent le dark mode avec les classes Tailwind (`dark:`).

### Icônes
On utilise des emojis pour les icônes :
- 📝 Forum
- 💰 Budget
- 🗳️ Vote
- 📄 Documents
- 🚨 Modération
- 👤 Utilisateur
- etc.

### Animations
- Transitions CSS pour hover states
- `transition-all duration-300` pour les animations douces
- `hover:shadow-md` pour les cards

## 🔧 Utilitaires

### Formatage de Date
```js
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
```

### Formatage de Taille de Fichier
```js
const formatFileSize = (bytes) => {
  if (!bytes) return 'N/A';
  const mb = bytes / (1024 * 1024);
  return `${mb.toFixed(2)} MB`;
};
```

### Calcul de Pourcentage
```js
const getPercentage = (votes, total) => {
  if (total === 0) return 0;
  return ((votes / total) * 100).toFixed(1);
};
```

## 📦 Dépendances Frontend

```json
{
  "@inertiajs/vue3": "^2.0",
  "@vitejs/plugin-vue": "^6.0",
  "autoprefixer": "^10.4",
  "axios": "^1.7",
  "laravel-vite-plugin": "^1.1",
  "postcss": "^8.4",
  "tailwindcss": "^3.4",
  "vite": "^6.0",
  "vue": "^3.5"
}
```

## 🚀 Commandes de Développement

```bash
# Installer les dépendances
npm install

# Développement avec hot reload
npm run dev

# Build pour production
npm run build

# Build avec watch
npm run watch
```

## 📱 Responsive Design

Tous les composants sont responsive avec breakpoints Tailwind :
- `sm:` 640px
- `md:` 768px
- `lg:` 1024px
- `xl:` 1280px

Stratégie mobile-first :
1. Design desktop d'abord (préférence utilisateur)
2. Adaptation mobile avec media queries
3. Menu hamburger pour navigation mobile
4. Grids responsive (`grid-cols-1 md:grid-cols-2 lg:grid-cols-3`)

## 🔐 Authentification & Permissions

L'état d'authentification est accessible via :
```vue
<template>
  <div v-if="$page.props.auth.user">
    <!-- Contenu pour utilisateur authentifié -->
  </div>
</template>
```

Les permissions sont passées via props `can` :
```vue
<template>
  <button v-if="can.update">Modifier</button>
</template>
```

## 🌐 Routes (Ziggy)

Utilisation de `route()` helper pour générer les URLs :
```vue
<Link :href="route('topics.index')">Forum</Link>
<Link :href="route('topics.show', topic.id)">Détails</Link>
```

## 📝 Formulaires (Inertia)

Utilisation de `useForm` pour les formulaires :
```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  title: '',
  description: '',
});

const submit = () => {
  form.post(route('topics.store'), {
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>
```

## ✨ Bonnes Pratiques

1. **Composition API** : Utiliser `<script setup>` pour tous les composants
2. **Props & Emits** : Déclarer explicitement avec `defineProps()` et `defineEmits()`
3. **Computed** : Utiliser `computed()` pour les valeurs dérivées
4. **Refs** : Utiliser `ref()` pour la réactivité
5. **Dark Mode** : Toujours inclure les classes `dark:`
6. **Accessibilité** : Labels pour inputs, `aria-*` attributes si nécessaire
7. **Performance** : `v-if` vs `v-show`, `:key` pour listes

## 🎯 Prochaines Étapes

1. **Tests E2E** : Ajouter Cypress ou Playwright
2. **Composables** : Extraire la logique réutilisable
3. **Stores** : Ajouter Pinia si état global complexe
4. **i18n** : Support multi-langues
5. **PWA** : Progressive Web App

## 🤝 Contribution

Voir `CONTRIBUTING.md` pour les guidelines de contribution au frontend.

---

💙 CivicDash - Démocratie Participative Open Source

