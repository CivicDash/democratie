<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    loiCod: {
        type: String,
        required: true
    },
    // Optionnel: stats pré-chargées depuis le serveur
    initialStats: {
        type: Object,
        default: null
    }
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const stats = ref({
    pour: 0,
    contre: 0,
    total: 0,
    pct_pour: 0,
    pct_contre: 0,
});

const userVote = ref(null); // 1, -1, ou null
const loading = ref(false);
const error = ref(null);
const justVoted = ref(false);

// Score de popularité calculé
const popularityScore = computed(() => {
    if (stats.value.total === 0) return 0;
    // Score de -100 à +100
    return Math.round(((stats.value.pour - stats.value.contre) / stats.value.total) * 100);
});

const popularityLabel = computed(() => {
    const score = popularityScore.value;
    if (score >= 70) return { label: 'Très populaire', emoji: '🔥', color: 'text-emerald-600 dark:text-emerald-400' };
    if (score >= 40) return { label: 'Populaire', emoji: '👍', color: 'text-emerald-500 dark:text-emerald-400' };
    if (score >= 10) return { label: 'Plutôt favorable', emoji: '✓', color: 'text-sky-600 dark:text-sky-400' };
    if (score >= -10) return { label: 'Avis partagés', emoji: '⚖️', color: 'text-amber-600 dark:text-amber-400' };
    if (score >= -40) return { label: 'Controversée', emoji: '⚠️', color: 'text-orange-600 dark:text-orange-400' };
    if (score >= -70) return { label: 'Impopulaire', emoji: '👎', color: 'text-rose-500 dark:text-rose-400' };
    return { label: 'Très impopulaire', emoji: '🚫', color: 'text-rose-600 dark:text-rose-400' };
});

const hasEnoughVotes = computed(() => stats.value.total >= 5);

// Charger les stats au montage
onMounted(async () => {
    if (props.initialStats) {
        stats.value = props.initialStats.stats || stats.value;
        userVote.value = props.initialStats.user_vote;
    } else {
        await fetchStats();
    }
});

async function fetchStats() {
    try {
        const response = await fetch(`/api/lois/${props.loiCod}/votes`);
        const data = await response.json();
        stats.value = data.stats;
        userVote.value = data.user_vote;
    } catch (e) {
        console.error('Erreur chargement stats vote:', e);
    }
}

async function vote(value) {
    if (!isAuthenticated.value) {
        // Rediriger vers login
        window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
        return;
    }

    if (loading.value) return;

    loading.value = true;
    error.value = null;
    justVoted.value = false;

    try {
        const response = await fetch(`/api/lois/${props.loiCod}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ vote: value }),
        });

        const data = await response.json();

        if (data.success) {
            stats.value = data.stats;
            userVote.value = data.vote;
            justVoted.value = true;
            
            setTimeout(() => {
                justVoted.value = false;
            }, 2000);
        } else {
            error.value = data.error || 'Erreur lors du vote';
        }
    } catch (e) {
        error.value = 'Erreur de connexion';
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function removeVote() {
    if (!isAuthenticated.value || loading.value) return;

    loading.value = true;

    try {
        const response = await fetch(`/api/lois/${props.loiCod}/vote`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });

        const data = await response.json();
        if (data.success) {
            stats.value = data.stats;
            userVote.value = null;
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                <span class="text-2xl">🗳️</span>
                Vote citoyen
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                {{ stats.total }} vote{{ stats.total > 1 ? 's' : '' }}
            </span>
        </div>

        <!-- Score de popularité -->
        <div v-if="hasEnoughVotes" class="mb-4 p-3 rounded-lg" :class="[
            popularityScore >= 10 ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' :
            popularityScore <= -10 ? 'bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800' :
            'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800'
        ]">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">{{ popularityLabel.emoji }}</span>
                    <div>
                        <p :class="['font-semibold text-sm', popularityLabel.color]">{{ popularityLabel.label }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Score citoyen</p>
                    </div>
                </div>
                <div class="text-right">
                    <span :class="[
                        'text-2xl font-bold',
                        popularityScore > 0 ? 'text-emerald-600 dark:text-emerald-400' :
                        popularityScore < 0 ? 'text-rose-600 dark:text-rose-400' :
                        'text-slate-600 dark:text-slate-400'
                    ]">
                        {{ popularityScore > 0 ? '+' : '' }}{{ popularityScore }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Message si pas assez de votes -->
        <div v-else-if="stats.total > 0" class="mb-4 p-3 bg-slate-100 dark:bg-slate-700/50 rounded-lg text-center">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                ⏳ {{ 5 - stats.total }} vote(s) de plus pour révéler le score
            </p>
        </div>

        <!-- Barre de progression -->
        <div class="mb-4">
            <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden flex">
                <div 
                    class="bg-emerald-500 transition-all duration-500"
                    :style="{ width: stats.pct_pour + '%' }"
                ></div>
                <div 
                    class="bg-rose-500 transition-all duration-500"
                    :style="{ width: stats.pct_contre + '%' }"
                ></div>
            </div>
            
            <!-- Légendes -->
            <div class="flex justify-between mt-2 text-sm">
                <div class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full"></span>
                    <span class="text-slate-600 dark:text-slate-400">
                        Pour: <strong class="text-emerald-600 dark:text-emerald-400">{{ stats.pct_pour }}%</strong>
                        <span class="text-slate-400">({{ stats.pour }})</span>
                    </span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-rose-500 rounded-full"></span>
                    <span class="text-slate-600 dark:text-slate-400">
                        Contre: <strong class="text-rose-600 dark:text-rose-400">{{ stats.pct_contre }}%</strong>
                        <span class="text-slate-400">({{ stats.contre }})</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Boutons de vote -->
        <div class="flex gap-3">
            <button
                @click="vote(1)"
                :disabled="loading"
                :class="[
                    'flex-1 py-3 px-4 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2',
                    userVote === 1 
                        ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 ring-2 ring-emerald-300' 
                        : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50',
                    loading ? 'opacity-50 cursor-not-allowed' : ''
                ]"
            >
                <span class="text-xl">👍</span>
                <span>Pour</span>
                <span v-if="userVote === 1" class="text-xs ml-1">✓</span>
            </button>
            
            <button
                @click="vote(-1)"
                :disabled="loading"
                :class="[
                    'flex-1 py-3 px-4 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2',
                    userVote === -1 
                        ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/30 ring-2 ring-rose-300' 
                        : 'bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:hover:bg-rose-900/50',
                    loading ? 'opacity-50 cursor-not-allowed' : ''
                ]"
            >
                <span class="text-xl">👎</span>
                <span>Contre</span>
                <span v-if="userVote === -1" class="text-xs ml-1">✓</span>
            </button>
        </div>

        <!-- Message après vote -->
        <transition
            enter-active-class="transition-all duration-300 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="opacity-0 translate-y-2"
            leave-to-class="opacity-0"
        >
            <div v-if="justVoted" class="mt-3 text-center text-sm text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 py-2 px-3 rounded-lg">
                ✅ Votre vote a été enregistré !
            </div>
        </transition>

        <!-- Annuler le vote -->
        <div v-if="userVote && !justVoted" class="mt-3 text-center">
            <button
                @click="removeVote"
                class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 underline"
            >
                Annuler mon vote
            </button>
        </div>

        <!-- Message non connecté -->
        <div v-if="!isAuthenticated" class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400">
            <a href="/login" class="text-sky-600 hover:underline">Connectez-vous</a> pour voter
        </div>

        <!-- Erreur -->
        <div v-if="error" class="mt-3 text-center text-sm text-rose-600 bg-rose-50 dark:bg-rose-900/20 py-2 px-3 rounded-lg">
            {{ error }}
        </div>
    </div>
</template>
