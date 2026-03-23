<script setup>
import { computed } from 'vue';

const props = defineProps({
    affaires: { type: Array, default: () => [] },
    variant: { type: String, default: 'light' }, // 'light' pour Card, 'dark' pour hero gradient
});

const stats = computed(() => {
    const condamnations = props.affaires.filter(a =>
        ['condamne_definitif', 'condamne_appel', 'condamne_premiere_instance'].includes(a.statut_judiciaire)
    );
    const definitives = condamnations.filter(a => a.statut_judiciaire === 'condamne_definitif');
    const enCours = props.affaires.filter(a =>
        ['en_cours', 'mis_en_examen'].includes(a.statut_judiciaire)
    );
    const relaxes = props.affaires.filter(a =>
        ['relaxe', 'acquitte', 'non_lieu', 'prescrit', 'amnistie'].includes(a.statut_judiciaire)
    );

    return { condamnations, definitives, enCours, relaxes };
});

const scrollToSection = () => {
    document.getElementById('affaires-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <div v-if="affaires.length > 0" class="flex flex-wrap items-center gap-2">
        <!-- Condamnations definitives -->
        <button
            v-if="stats.definitives.length"
            @click="scrollToSection"
            :class="[
                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold cursor-pointer transition-all hover:scale-105',
                variant === 'dark'
                    ? 'bg-red-500/20 text-red-200 border border-red-400/30 hover:bg-red-500/30'
                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50'
            ]"
            :title="`${stats.definitives.length} condamnation(s) définitive(s)`"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
            </svg>
            {{ stats.definitives.length }} condamnation{{ stats.definitives.length > 1 ? 's' : '' }}
        </button>

        <!-- Autres condamnations (appel, premiere instance) -->
        <button
            v-else-if="stats.condamnations.length"
            @click="scrollToSection"
            :class="[
                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold cursor-pointer transition-all hover:scale-105',
                variant === 'dark'
                    ? 'bg-orange-500/20 text-orange-200 border border-orange-400/30 hover:bg-orange-500/30'
                    : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300 hover:bg-orange-200 dark:hover:bg-orange-900/50'
            ]"
            :title="`${stats.condamnations.length} condamnation(s) non définitive(s)`"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
            </svg>
            {{ stats.condamnations.length }} condamnation{{ stats.condamnations.length > 1 ? 's' : '' }}
        </button>

        <!-- Procedures en cours -->
        <button
            v-if="stats.enCours.length"
            @click="scrollToSection"
            :class="[
                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold cursor-pointer transition-all hover:scale-105',
                variant === 'dark'
                    ? 'bg-amber-500/20 text-amber-200 border border-amber-400/30 hover:bg-amber-500/30'
                    : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900/50'
            ]"
            :title="`${stats.enCours.length} procédure(s) en cours`"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ stats.enCours.length }} en cours
        </button>
    </div>
</template>
