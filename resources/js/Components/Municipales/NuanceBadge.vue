<script setup>
const props = defineProps({
    nuance: String,
    size: { type: String, default: 'sm' },
});

const nuanceConfig = {
    'LEXG': { label: 'Extrême gauche', color: 'bg-red-100 text-red-900 border-red-300' },
    'LCOM': { label: 'Communiste', color: 'bg-red-100 text-red-800 border-red-200' },
    'LFI': { label: 'La France Insoumise', color: 'bg-red-100 text-red-700 border-red-200' },
    'LSOC': { label: 'Socialiste', color: 'bg-pink-100 text-pink-800 border-pink-200' },
    'LDVG': { label: 'Divers gauche', color: 'bg-rose-100 text-rose-800 border-rose-200' },
    'LUG': { label: 'Union de la gauche', color: 'bg-rose-200 text-rose-900 border-rose-300' },
    'LECO': { label: 'Écologiste', color: 'bg-green-100 text-green-800 border-green-200' },
    'LVEC': { label: 'Les Écologistes', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
    'LREG': { label: 'Régionaliste', color: 'bg-lime-100 text-lime-800 border-lime-200' },
    'LDVC': { label: 'Divers centre', color: 'bg-yellow-100 text-yellow-800 border-yellow-200' },
    'LMDM': { label: 'Modem', color: 'bg-orange-100 text-orange-800 border-orange-200' },
    'LREN': { label: 'Renaissance', color: 'bg-amber-100 text-amber-800 border-amber-200' },
    'LUC': { label: 'Union du centre', color: 'bg-amber-200 text-amber-900 border-amber-300' },
    'LUDI': { label: 'UDI', color: 'bg-sky-100 text-sky-800 border-sky-200' },
    'LUD': { label: 'Union de la droite', color: 'bg-sky-200 text-sky-900 border-sky-300' },
    'LUDR': { label: 'Union droite & centre', color: 'bg-blue-200 text-blue-900 border-blue-300' },
    'LDVD': { label: 'Divers droite', color: 'bg-blue-100 text-blue-800 border-blue-200' },
    'LLR': { label: 'Les Républicains', color: 'bg-blue-100 text-blue-900 border-blue-300' },
    'LRN': { label: 'Rassemblement National', color: 'bg-slate-100 text-slate-800 border-slate-300' },
    'LEXD': { label: 'Extrême droite', color: 'bg-gray-200 text-gray-900 border-gray-400' },
    'LUXD': { label: 'Union extrême droite', color: 'bg-slate-200 text-slate-900 border-slate-400' },
    'LREC': { label: 'Reconquête', color: 'bg-slate-200 text-slate-900 border-slate-400' },
    'LDSV': { label: 'Droite souverainiste', color: 'bg-purple-100 text-purple-800 border-purple-200' },
    'LHOR': { label: 'Hors spectre', color: 'bg-stone-100 text-stone-700 border-stone-200' },
    'LDIV': { label: 'Divers', color: 'bg-gray-100 text-gray-700 border-gray-200' },
};

const config = nuanceConfig[props.nuance] || { label: props.nuance || 'N/A', color: 'bg-gray-100 text-gray-600 border-gray-200' };

const sizeClass = props.size === 'xs' ? 'text-xs px-1.5 py-0.5' : props.size === 'sm' ? 'text-xs px-2 py-1' : 'text-sm px-3 py-1';
</script>

<template>
    <span :class="['inline-flex items-center rounded-full border font-medium', config.color, sizeClass]">
        {{ config.label }}
    </span>
</template>
