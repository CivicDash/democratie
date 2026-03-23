<script setup>
const props = defineProps({
    statut: { type: String, required: true },
    size: { type: String, default: 'sm' },
});

const config = {
    condamne_definitif: { label: 'Condamnation définitive', bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-800 dark:text-red-300', dot: 'bg-red-500' },
    condamne_appel: { label: 'Condamné — pourvoi en cours', bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-800 dark:text-orange-300', dot: 'bg-orange-500' },
    condamne_premiere_instance: { label: 'Condamné en 1re instance', bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-800 dark:text-yellow-300', dot: 'bg-yellow-500' },
    mis_en_examen: { label: 'Mis en examen', bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-800 dark:text-yellow-300', dot: 'bg-yellow-500' },
    en_cours: { label: 'Procédure en cours', bg: 'bg-gray-100 dark:bg-gray-700', text: 'text-gray-700 dark:text-gray-300', dot: 'bg-gray-400' },
    relaxe: { label: 'Relaxé', bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-800 dark:text-green-300', dot: 'bg-green-500' },
    acquitte: { label: 'Acquitté', bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-800 dark:text-green-300', dot: 'bg-green-500' },
    prescrit: { label: 'Prescrit', bg: 'bg-slate-100 dark:bg-slate-700', text: 'text-slate-700 dark:text-slate-300', dot: 'bg-slate-400' },
    non_lieu: { label: 'Non-lieu', bg: 'bg-slate-100 dark:bg-slate-700', text: 'text-slate-700 dark:text-slate-300', dot: 'bg-slate-400' },
    amnistie: { label: 'Amnistié', bg: 'bg-slate-100 dark:bg-slate-700', text: 'text-slate-700 dark:text-slate-300', dot: 'bg-slate-400' },
};

const c = config[props.statut] || { label: props.statut, bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-400' };
</script>

<template>
    <span :class="[
        'inline-flex items-center gap-1.5 rounded-full font-medium',
        c.bg, c.text,
        size === 'xs' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs'
    ]">
        <span :class="['w-1.5 h-1.5 rounded-full', c.dot]"></span>
        {{ c.label }}
    </span>
</template>
