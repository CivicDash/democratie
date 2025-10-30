# 🎨 COMPOSANTS UX - Documentation

## 📋 Vue d'ensemble

CivicDash dispose d'une bibliothèque complète de composants UX pour améliorer l'expérience utilisateur avec des loading states, notifications toast, empty states et modales de confirmation.

---

## 📦 Composants disponibles

### 1. LoadingSkeleton.vue

Placeholders élégants pendant le chargement des données.

**Props :**

| Prop      | Type    | Default  | Description                                          |
|-----------|---------|----------|------------------------------------------------------|
| `type`    | String  | `'card'` | Type de skeleton (card, list, text, avatar, button, table) |
| `count`   | Number  | `1`      | Nombre de skeletons à afficher                      |
| `height`  | String  | `null`   | Hauteur personnalisée                               |
| `width`   | String  | `'100%'` | Largeur personnalisée                               |
| `rounded` | Boolean | `true`   | Coins arrondis                                      |
| `animated`| Boolean | `true`   | Animation shimmer                                   |

**Exemples :**

```vue
<template>
  <!-- Card skeleton -->
  <LoadingSkeleton type="card" :count="3" />

  <!-- List skeleton -->
  <LoadingSkeleton type="list" :count="5" />

  <!-- Text skeleton -->
  <LoadingSkeleton type="text" :count="3" />

  <!-- Avatar skeleton -->
  <LoadingSkeleton type="avatar" />

  <!-- Button skeleton -->
  <LoadingSkeleton type="button" width="120px" height="40px" />

  <!-- Table skeleton -->
  <LoadingSkeleton type="table" />
</template>

<script setup>
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';
</script>
```

---

### 2. Toast + useToast

Notifications toast modernes avec animations.

**Composable useToast :**

```javascript
import { useToast } from '@/composables/useToast';

const toast = useToast();

// Méthodes disponibles
toast.success('Opération réussie !');
toast.error('Une erreur est survenue');
toast.warning('Attention !');
toast.info('Information importante');

// Avec titre et durée personnalisée
toast.success(
  'Votre vote a été enregistré',
  'Merci !',
  3000
);

// Avec toutes les options
toast.show({
  type: 'success',
  title: 'Succès',
  message: 'Opération terminée',
  duration: 5000,
  closable: true,
});

// Fermer un toast spécifique
const toastId = toast.success('Message');
toast.close(toastId);

// Fermer tous les toasts
toast.clearAll();
```

**Exemple complet :**

```vue
<template>
  <div>
    <button @click="handleAction">Déclencher action</button>
  </div>
</template>

<script setup>
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const toast = useToast();

const handleAction = async () => {
  try {
    await axios.post('/api/action');
    toast.success('Action réussie !', 'Succès');
  } catch (error) {
    toast.error(
      error.response?.data?.message || 'Erreur',
      'Oops !'
    );
  }
};
</script>
```

**Types de toast :**

- ✓ **success** : Vert, pour les actions réussies
- ✕ **error** : Rouge, pour les erreurs
- ⚠ **warning** : Orange, pour les avertissements
- ℹ **info** : Bleu, pour les informations

---

### 3. EmptyState.vue

États vides avec illustrations et call-to-action.

**Props :**

| Prop          | Type    | Default           | Description                    |
|---------------|---------|-------------------|--------------------------------|
| `icon`        | String  | `'📭'`            | Icône emoji                    |
| `title`       | String  | `'Aucun contenu'` | Titre                          |
| `description` | String  | `'...'`           | Description                    |
| `actionLabel` | String  | `null`            | Label du bouton                |
| `actionHref`  | String  | `null`            | Lien du bouton                 |
| `actionType`  | String  | `'button'`        | Type ('button' ou 'link')      |
| `size`        | String  | `'medium'`        | Taille (small, medium, large)  |

**Events :**

- `@action` : Émis lors du clic sur le bouton

**Exemples :**

```vue
<template>
  <!-- Empty state simple -->
  <EmptyState
    icon="📝"
    title="Aucun topic"
    description="Il n'y a pas encore de topics dans cette catégorie"
  />

  <!-- Avec bouton -->
  <EmptyState
    icon="🗳️"
    title="Aucun vote en cours"
    description="Vous n'avez pas de votes actifs pour le moment"
    action-label="Découvrir les votes"
    action-href="/vote"
    action-type="link"
  />

  <!-- Avec action personnalisée -->
  <EmptyState
    icon="💬"
    title="Aucun message"
    description="Soyez le premier à lancer la discussion !"
    action-label="Créer un post"
    @action="handleCreate"
  />

  <!-- Avec slot pour contenu personnalisé -->
  <EmptyState
    icon="🏛️"
    title="Aucune proposition"
    description="Aucune proposition de loi ne correspond à vos critères"
  >
    <button @click="resetFilters">Réinitialiser les filtres</button>
  </EmptyState>

  <!-- Taille large -->
  <EmptyState
    size="large"
    icon="🎉"
    title="Bienvenue sur CivicDash !"
    description="Commencez par explorer les sujets de débat ou créez votre première proposition"
    action-label="Explorer"
    action-href="/topics"
  />
</template>

<script setup>
import EmptyState from '@/Components/EmptyState.vue';

const handleCreate = () => {
  // Logique de création
};

const resetFilters = () => {
  // Réinitialiser les filtres
};
</script>
```

---

### 4. ConfirmModal + useConfirm

Modales de confirmation avec promesses.

**Composable useConfirm :**

```javascript
import { useConfirm } from '@/composables/useConfirm';

const { confirm, confirmDanger, confirmWarning, confirmInfo } = useConfirm();

// Confirmation simple
const result = await confirm({
  title: 'Confirmation',
  message: 'Êtes-vous sûr de vouloir continuer ?',
  confirmLabel: 'Oui',
  cancelLabel: 'Non',
});

if (result) {
  // Utilisateur a confirmé
}

// Confirmation danger (rouge)
const deleted = await confirmDanger(
  'Cette action est irréversible. Êtes-vous sûr ?',
  'Supprimer définitivement'
);

// Confirmation warning (orange)
const continued = await confirmWarning(
  'Cela peut prendre plusieurs minutes',
  'Lancer le traitement'
);

// Confirmation info (bleu)
const understood = await confirmInfo(
  'Votre compte sera créé avec ces informations',
  'Information'
);

// Avec action asynchrone
await confirm({
  title: 'Supprimer le topic',
  message: 'Voulez-vous vraiment supprimer ce topic ?',
  type: 'danger',
  confirmLabel: 'Supprimer',
  onConfirm: async () => {
    await axios.delete(`/api/topics/${topicId}`);
    toast.success('Topic supprimé');
  },
  onCancel: () => {
    toast.info('Annulé');
  },
});
```

**Exemple complet :**

```vue
<template>
  <div>
    <button @click="handleDelete">Supprimer</button>
  </div>
</template>

<script setup>
import { useConfirm } from '@/composables/useConfirm';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const { confirmDanger } = useConfirm();
const toast = useToast();

const handleDelete = async () => {
  const confirmed = await confirmDanger(
    'Cette action est irréversible. Le topic et tous ses posts seront supprimés.',
    'Supprimer le topic',
    {
      confirmLabel: 'Oui, supprimer',
      onConfirm: async () => {
        await axios.delete('/api/topics/123');
        toast.success('Topic supprimé avec succès');
      },
    }
  );

  if (confirmed) {
    // Redirection ou autre action
  }
};
</script>
```

**Types de modales :**

- **info** : Bleu, pour les informations
- **warning** : Orange, pour les avertissements
- **danger** : Rouge, pour les actions destructives

---

## 🎯 Intégration globale

Les composants Toast et ConfirmModal sont déjà intégrés dans `AuthenticatedLayout.vue` et disponibles partout dans l'application.

```vue
<!-- resources/js/Layouts/AuthenticatedLayout.vue -->
<script setup>
import ToastContainer from '@/Components/ToastContainer.vue';
import ConfirmContainer from '@/Components/ConfirmContainer.vue';
</script>

<template>
  <div>
    <!-- Votre layout -->

    <!-- Global Toast Notifications -->
    <ToastContainer />

    <!-- Global Confirm Modals -->
    <ConfirmContainer />
  </div>
</template>
```

---

## 💡 Exemples d'utilisation combinés

### Exemple 1 : Formulaire avec loading + toast

```vue
<template>
  <div>
    <form v-if="!loading" @submit.prevent="handleSubmit">
      <!-- Formulaire -->
    </form>

    <LoadingSkeleton v-else type="card" :count="1" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const loading = ref(false);
const toast = useToast();

const handleSubmit = async () => {
  loading.value = true;

  try {
    await axios.post('/api/topics', formData);
    toast.success('Topic créé avec succès !');
  } catch (error) {
    toast.error('Erreur lors de la création');
  } finally {
    loading.value = false;
  }
};
</script>
```

### Exemple 2 : Liste avec empty state + confirm

```vue
<template>
  <div>
    <LoadingSkeleton v-if="loading" type="list" :count="5" />

    <EmptyState
      v-else-if="items.length === 0"
      icon="📝"
      title="Aucun élément"
      description="Commencez par créer votre premier élément"
      action-label="Créer"
      @action="handleCreate"
    />

    <div v-else>
      <div v-for="item in items" :key="item.id">
        <h3>{{ item.title }}</h3>
        <button @click="handleDelete(item.id)">Supprimer</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import { useConfirm } from '@/composables/useConfirm';
import axios from 'axios';

const loading = ref(true);
const items = ref([]);
const toast = useToast();
const { confirmDanger } = useConfirm();

onMounted(async () => {
  try {
    const response = await axios.get('/api/items');
    items.value = response.data;
  } catch (error) {
    toast.error('Erreur de chargement');
  } finally {
    loading.value = false;
  }
});

const handleDelete = async (id) => {
  const confirmed = await confirmDanger(
    'Voulez-vous vraiment supprimer cet élément ?',
    'Supprimer'
  );

  if (confirmed) {
    try {
      await axios.delete(`/api/items/${id}`);
      items.value = items.value.filter(item => item.id !== id);
      toast.success('Élément supprimé');
    } catch (error) {
      toast.error('Erreur lors de la suppression');
    }
  }
};

const handleCreate = () => {
  router.visit('/items/create');
};
</script>
```

---

## 🎨 Personnalisation

### Dark Mode

Tous les composants supportent automatiquement le dark mode via `@media (prefers-color-scheme: dark)`.

### Responsive

Tous les composants sont responsive et s'adaptent aux écrans mobiles.

---

## 📚 Ressources

- Tous les composants sont dans `resources/js/Components/`
- Les composables sont dans `resources/js/composables/`
- Les containers globaux sont intégrés dans `AuthenticatedLayout.vue`

---

**Maintenu par** : CivicDash Core Team  
**Version** : 1.0  
**Dernière mise à jour** : 30 octobre 2025
