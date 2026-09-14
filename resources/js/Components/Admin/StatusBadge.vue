<template>
    <span :class="['inline-block px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap', classe]">
        {{ libelle }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Pastille de statut.
 *
 * Les affaires judiciaires coloraient leurs statuts ; le module présidentielle les
 * rendait en gris uniforme, avec le nom brut de la colonne — « a_completer ». Une file
 * ne se scannait donc pas du regard, et les libellés étaient du jargon de base de
 * données. La carte de couleurs est celle qui existait déjà côté affaires.
 *
 * Le vert plein est réservé à « publié », en cohérence avec le référentiel d'actions :
 * l'emerald veut dire « c'est sur le site public », et rien d'autre.
 */
const props = defineProps({
    statut: { type: String, default: null },
    publie: { type: Boolean, default: null },
});

const CARTE = {
    detecte: ['Détecté', 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'],
    en_review: ['En cours d\'examen', 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300'],
    a_completer: ['À compléter', 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300'],
    valide: ['Validé', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
    validee: ['Validée', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
    rejete: ['Rejeté', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'],
    rejetee: ['Rejetée', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'],
    conteste: ['Contesté', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'],
    archive: ['Archivé', 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
    rattachee: ['Rattachée', 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300'],
    nouveau: ['Nouveau', 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'],
    en_cours: ['En cours', 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'],
    resolu: ['Résolu', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
    publie: ['Publié', 'bg-emerald-600 text-white'],
};

const cle = computed(() => (props.publie === true ? 'publie' : props.statut));

const libelle = computed(() => CARTE[cle.value]?.[0]
    ?? (cle.value ? cle.value.replace(/_/g, ' ') : '—'));

const classe = computed(() => CARTE[cle.value]?.[1]
    ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300');
</script>
