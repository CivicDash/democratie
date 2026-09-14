<template>
    <div class="relative">
        <label :for="id" class="sr-only">{{ label }}</label>
        <input :id="id"
               v-model="terme"
               type="search"
               :placeholder="placeholder"
               class="w-full sm:w-80 px-3 py-2 min-h-[40px] border rounded-lg dark:bg-gray-700 dark:border-gray-600 text-sm" />
        <p v-if="total !== null" class="mt-1 text-xs text-gray-500 dark:text-gray-400" aria-live="polite">
            {{ total }} résultat{{ total > 1 ? 's' : '' }}
        </p>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Champ de recherche pour une file de modération.
 *
 * Les files présidentielle sont paginées vingt-cinq par page et grossissent à chaque
 * source ingérée ; il n'y avait aucun moyen d'y chercher un nom. Sur les affaires
 * judiciaires, le contrôleur implémentait déjà la recherche — seul le champ manquait.
 */
const props = defineProps({
    valeur: { type: String, default: '' },
    routeNom: { type: String, required: true },
    params: { type: Object, default: () => ({}) },
    placeholder: { type: String, default: 'Rechercher…' },
    label: { type: String, default: 'Rechercher dans la file' },
    cle: { type: String, default: 'q' },
    total: { type: Number, default: null },
    id: { type: String, default: 'recherche-file' },
});

const terme = ref(props.valeur ?? '');
let minuterie = null;

watch(terme, (valeur) => {
    clearTimeout(minuterie);
    // Un délai court : la frappe ne doit pas déclencher une requête par caractère,
    // mais l'attente ne doit pas se sentir.
    minuterie = setTimeout(() => {
        router.get(route(props.routeNom),
            { ...props.params, [props.cle]: valeur || undefined },
            { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});
</script>
