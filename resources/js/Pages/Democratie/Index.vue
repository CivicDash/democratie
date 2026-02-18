<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Comprendre la Démocratie', current: true, icon: '🎓' },
];

const visited = ref({});

onMounted(() => {
    try {
        const stored = localStorage.getItem('democratie_visited');
        if (stored) visited.value = JSON.parse(stored);
    } catch {}
});

function markVisited(key) {
    visited.value[key] = true;
    try { localStorage.setItem('democratie_visited', JSON.stringify(visited.value)); } catch {}
}

const modules = [
    {
        key: 'parcours-loi',
        icon: '📜',
        title: 'Le Parcours d\'une Loi',
        description: 'De l\'initiative à la promulgation : découvrez chaque étape du processus législatif français.',
        color: 'amber',
        route: 'democratie.parcours-loi',
    },
    {
        key: 'elections',
        icon: '🗳️',
        title: 'Les Élections en France',
        description: 'Présidentielle, législatives, municipales… Comprenez les 7 types de scrutin et leurs enjeux.',
        color: 'blue',
        route: 'democratie.elections',
    },
    {
        key: 'representants',
        icon: '👥',
        title: 'Qui sont nos Représentants ?',
        description: 'Députés, sénateurs, maires : qui sont-ils, que font-ils, comment sont-ils élus ?',
        color: 'indigo',
        route: 'democratie.representants',
    },
    {
        key: 'votes',
        icon: '🗳️',
        title: 'Comment Votent-ils ?',
        description: 'Scrutins publics, solennels, discipline de groupe : décryptez les votes à l\'Assemblée.',
        color: 'emerald',
        route: 'democratie.votes',
    },
    {
        key: 'gouvernement',
        icon: '🏛️',
        title: 'Le Gouvernement',
        description: 'Président, Premier Ministre, ministères : la structure du pouvoir exécutif expliquée.',
        color: 'rose',
        route: 'democratie.gouvernement',
    },
    {
        key: 'conseil-constitutionnel',
        icon: '⚖️',
        title: 'Le Conseil Constitutionnel',
        description: 'Gardien de la Constitution, contrôle de constitutionnalité et QPC : comprendre son rôle.',
        color: 'purple',
        route: 'democratie.conseil-constitutionnel',
    },
];

const completedCount = ref(0);

onMounted(() => {
    completedCount.value = modules.filter(m => visited.value[m.key]).length;
});

const colorClasses = {
    amber: {
        bg: 'bg-amber-50 dark:bg-amber-900/20',
        border: 'border-amber-200 dark:border-amber-800',
        icon: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        hover: 'hover:border-amber-400 dark:hover:border-amber-600',
        badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    },
    blue: {
        bg: 'bg-blue-50 dark:bg-blue-900/20',
        border: 'border-blue-200 dark:border-blue-800',
        icon: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
        hover: 'hover:border-blue-400 dark:hover:border-blue-600',
        badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    },
    indigo: {
        bg: 'bg-indigo-50 dark:bg-indigo-900/20',
        border: 'border-indigo-200 dark:border-indigo-800',
        icon: 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300',
        hover: 'hover:border-indigo-400 dark:hover:border-indigo-600',
        badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    },
    emerald: {
        bg: 'bg-emerald-50 dark:bg-emerald-900/20',
        border: 'border-emerald-200 dark:border-emerald-800',
        icon: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
        hover: 'hover:border-emerald-400 dark:hover:border-emerald-600',
        badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    },
    rose: {
        bg: 'bg-rose-50 dark:bg-rose-900/20',
        border: 'border-rose-200 dark:border-rose-800',
        icon: 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',
        hover: 'hover:border-rose-400 dark:hover:border-rose-600',
        badge: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    },
    purple: {
        bg: 'bg-purple-50 dark:bg-purple-900/20',
        border: 'border-purple-200 dark:border-purple-800',
        icon: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
        hover: 'hover:border-purple-400 dark:hover:border-purple-600',
        badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200',
    },
};
</script>

<template>
    <Head title="Comprendre la Démocratie" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" />

                <!-- Hero -->
                <div class="mt-6 text-center">
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">
                        🎓 Comprendre la Démocratie
                    </h1>
                    <p class="mt-3 text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Explorez le fonctionnement de la démocratie française à travers 6 modules interactifs.
                        Chaque module vous renvoie vers les données réelles de CivicDash.
                    </p>

                    <!-- Progress bar -->
                    <div class="mt-6 max-w-md mx-auto" v-if="completedCount > 0">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-1">
                            <span>Votre progression</span>
                            <span>{{ completedCount }} / {{ modules.length }} modules</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div
                                class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500"
                                :style="{ width: (completedCount / modules.length * 100) + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Module cards grid -->
                <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="mod in modules"
                        :key="mod.key"
                        :href="route(mod.route)"
                        @click="markVisited(mod.key)"
                        class="group relative block rounded-xl border p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-1"
                        :class="[
                            colorClasses[mod.color].bg,
                            colorClasses[mod.color].border,
                            colorClasses[mod.color].hover,
                        ]"
                    >
                        <!-- Visited badge -->
                        <div
                            v-if="visited[mod.key]"
                            class="absolute top-3 right-3 flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
                        >
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Consulté
                        </div>

                        <div
                            class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl mb-4"
                            :class="colorClasses[mod.color].icon"
                        >
                            {{ mod.icon }}
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:underline">
                            {{ mod.title }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            {{ mod.description }}
                        </p>

                        <div class="mt-4 flex items-center text-sm font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                            Découvrir
                            <svg class="ml-1 w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </Link>
                </div>

                <!-- CTA -->
                <div class="mt-12 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Envie d'aller plus loin ?
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Explorez les données réelles de CivicDash : lois en cours, votes des députés, budgets des communes...
                    </p>
                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        <Link :href="route('lois.index')" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                            📜 Explorer les lois
                        </Link>
                        <Link :href="route('representants.mes-representants')" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            📍 Trouver mes élus
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
