<template>
    <div v-if="messages.length"
         role="alert"
         class="rounded-lg border border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-900/20 p-4">
        <p class="font-semibold text-red-800 dark:text-red-200">{{ titre }}</p>
        <ul class="mt-2 list-disc pl-5 space-y-1 text-sm text-red-700 dark:text-red-300">
            <li v-for="m in messages" :key="m">{{ m }}</li>
        </ul>
    </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Bloc d'erreurs de validation, à placer juste au-dessus du bouton de soumission —
 * c'est là que se trouve le regard quand on vient de cliquer, et sur un formulaire
 * long l'erreur d'un champ situé plus haut passait inaperçue.
 */
const props = defineProps({
    errors: { type: Object, default: () => ({}) },
    only: { type: Array, default: null },
    titre: { type: String, default: 'Enregistrement refusé' },
});

const messages = computed(() => Object.entries(props.errors ?? {})
    .filter(([champ, message]) => typeof message === 'string'
        && message.length
        && (props.only === null || props.only.includes(champ)))
    .map(([, message]) => message));
</script>
