<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import ShareButtons from '@/Components/Commune/ShareButtons.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    ville: Object,
    consultation: Object,
    a_vote: Boolean,
    votes_utilisateur: Array,
    resultats: Array,
    seo: Object,
});

const auth = computed(() => usePage().props.auth?.user);
const selectedOptions = ref([]);
const voting = ref(false);

const toggleOption = (key) => {
    if (props.consultation.multiple) {
        const idx = selectedOptions.value.indexOf(key);
        if (idx >= 0) selectedOptions.value.splice(idx, 1);
        else selectedOptions.value.push(key);
    } else {
        selectedOptions.value = [key];
    }
};

const submitVote = () => {
    if (!selectedOptions.value.length) return;
    voting.value = true;
    router.post(
        route('commune.consultations.voter', [props.ville.code_insee, props.consultation.slug]),
        { options: selectedOptions.value },
        {
            preserveScroll: true,
            onFinish: () => voting.value = false,
        }
    );
};

const maxVotes = computed(() => {
    if (!props.resultats) return 0;
    return Math.max(...props.resultats.map(r => r.votes), 1);
});

const barColors = ['bg-blue-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-violet-500', 'bg-cyan-500', 'bg-orange-500'];
</script>

<template>
    <CommuneLayout :ville="ville" :seo="seo" :titre="`${consultation.titre} - ${ville.nom}`">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
                <Link :href="route('commune.index', ville.code_insee)" class="hover:text-blue-600">{{ ville.nom }}</Link>
                <span>/</span>
                <Link :href="route('commune.consultations', ville.code_insee)" class="hover:text-blue-600">Consultations</Link>
                <span>/</span>
                <span class="text-slate-900 dark:text-white truncate">{{ consultation.titre }}</span>
            </nav>

            <div class="flex items-center gap-2 mb-3">
                <span
                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                    :class="consultation.est_ouverte
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                        : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                >
                    {{ consultation.est_ouverte ? 'En cours' : 'Terminee' }}
                </span>
                <span class="text-xs text-slate-400">{{ consultation.votes_count }} votes</span>
                <span v-if="consultation.ferme_at" class="text-xs text-slate-400">- Termine le {{ consultation.ferme_at }}</span>
            </div>

            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ consultation.titre }}</h1>
            <p v-if="consultation.description" class="text-slate-600 dark:text-slate-300 mb-8 leading-relaxed whitespace-pre-line">{{ consultation.description }}</p>

            <!-- Resultats (after voting or when closed) -->
            <div v-if="resultats" class="space-y-3 mb-8">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Resultats</h2>
                <div v-for="(r, i) in resultats" :key="r.key" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-slate-900 dark:text-white" :class="{ 'font-bold': votes_utilisateur.includes(r.key) }">
                            {{ r.label }}
                            <span v-if="votes_utilisateur.includes(r.key)" class="text-blue-600 text-xs ml-1">(votre vote)</span>
                        </span>
                        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ r.pourcentage }}% ({{ r.votes }})</span>
                    </div>
                    <div class="h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-700"
                            :class="barColors[i % barColors.length]"
                            :style="{ width: `${r.pourcentage}%` }"
                        />
                    </div>
                </div>
            </div>

            <!-- Vote form (when open and not yet voted) -->
            <div v-else-if="consultation.est_ouverte && auth && !a_vote" class="mb-8">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">
                    {{ consultation.multiple ? 'Selectionnez une ou plusieurs options' : 'Selectionnez une option' }}
                </h2>
                <div class="space-y-2">
                    <button
                        v-for="opt in consultation.options"
                        :key="opt.key"
                        @click="toggleOption(opt.key)"
                        class="w-full text-left p-4 rounded-xl border-2 transition-all"
                        :class="selectedOptions.includes(opt.key)
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                            : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-300 dark:hover:border-blue-700'"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                                :class="selectedOptions.includes(opt.key) ? 'border-blue-500 bg-blue-500' : 'border-slate-300 dark:border-slate-600'"
                            >
                                <svg v-if="selectedOptions.includes(opt.key)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </div>
                            <span class="font-medium text-slate-900 dark:text-white">{{ opt.label }}</span>
                        </div>
                    </button>
                </div>
                <button
                    @click="submitVote"
                    :disabled="!selectedOptions.length || voting"
                    class="mt-4 w-full py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    {{ voting ? 'Envoi...' : 'Voter' }}
                </button>
            </div>

            <!-- Not authenticated -->
            <div v-else-if="consultation.est_ouverte && !auth" class="mb-8 text-center py-8 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                <p class="text-slate-500 dark:text-slate-400">
                    <a :href="route('login')" class="text-blue-600 hover:underline font-medium">Connectez-vous</a> pour participer a cette consultation
                </p>
            </div>

            <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                <ShareButtons :url="seo?.url || ''" :title="consultation.titre" :description="consultation.description || ''" />
            </div>
        </div>
    </CommuneLayout>
</template>
