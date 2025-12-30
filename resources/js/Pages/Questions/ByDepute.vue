<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    depute: { type: Object, required: true },
    questions: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function getGroupeColor(sigle) {
    const colors = {
        'RN': 'bg-blue-900 text-blue-100',
        'DR': 'bg-blue-700 text-white',
        'EPR': 'bg-amber-500 text-white',
        'DEM': 'bg-yellow-500 text-black',
        'HOR': 'bg-cyan-500 text-white',
        'LIOT': 'bg-teal-500 text-white',
        'SOC': 'bg-rose-600 text-white',
        'ECO': 'bg-green-600 text-white',
        'LFI': 'bg-red-600 text-white',
        'GDR': 'bg-red-700 text-white',
        'UDR': 'bg-slate-600 text-white',
    };
    return colors[sigle] || 'bg-slate-600 text-white';
}

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Députés', href: route('representants.deputes.index'), icon: '👥' },
    { label: 'Questions au Gouvernement', href: route('questions.index'), icon: '❓' },
    { label: `${props.depute.prenom} ${props.depute.nom}`, current: true },
];
</script>

<template>
    <Head :title="`Questions de ${depute.prenom} ${depute.nom}`" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-800 dark:to-indigo-900">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" class="mb-4 text-indigo-100" />

                    <div class="flex items-center gap-6">
                        <!-- Photo -->
                        <div class="w-20 h-20 rounded-full bg-white/20 overflow-hidden shrink-0">
                            <img 
                                v-if="depute.photo_url"
                                :src="depute.photo_url"
                                :alt="depute.prenom + ' ' + depute.nom"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-4xl">
                                👤
                            </div>
                        </div>

                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-white">
                                Questions de {{ depute.prenom }} {{ depute.nom }}
                            </h1>
                            <div class="flex items-center gap-3 mt-2">
                                <span 
                                    v-if="depute.groupe_sigle"
                                    class="px-3 py-1 text-sm font-medium rounded-full"
                                    :class="getGroupeColor(depute.groupe_sigle)"
                                >
                                    {{ depute.groupe_sigle }}
                                </span>
                                <Link
                                    :href="route('representants.deputes.show', depute.uid)"
                                    class="text-sm text-indigo-100 hover:text-white"
                                >
                                    Voir le profil complet →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Stats Sidebar -->
                    <aside class="space-y-6">
                        <!-- Compteurs -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                            <div class="text-center mb-4">
                                <div class="text-4xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.total || 0 }}</div>
                                <div class="text-gray-500 dark:text-gray-400">Questions posées</div>
                            </div>
                            <div class="flex justify-center gap-6 text-sm">
                                <div class="text-center">
                                    <div class="text-xl font-semibold text-emerald-600 dark:text-emerald-400">{{ stats.repondues || 0 }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">Répondues</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-semibold text-amber-600 dark:text-amber-400">{{ (stats.total || 0) - (stats.repondues || 0) }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">En attente</div>
                                </div>
                            </div>
                        </div>

                        <!-- Par rubrique -->
                        <div 
                            v-if="stats.par_rubrique?.length > 0"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm"
                        >
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                                Par thème
                            </h3>
                            <div class="space-y-2">
                                <div 
                                    v-for="r in stats.par_rubrique"
                                    :key="r.rubrique"
                                    class="flex justify-between items-center"
                                >
                                    <span class="text-sm text-gray-700 dark:text-gray-300 capitalize truncate">{{ r.rubrique }}</span>
                                    <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ r.nb }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Par ministère -->
                        <div 
                            v-if="stats.par_ministere?.length > 0"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm"
                        >
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                                Par ministère
                            </h3>
                            <div class="space-y-2">
                                <div 
                                    v-for="m in stats.par_ministere"
                                    :key="m.ministere_sigle"
                                    class="flex justify-between items-center"
                                >
                                    <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ m.ministere_sigle }}</span>
                                    <span class="text-sm font-medium text-violet-600 dark:text-violet-400">{{ m.nb }}</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Questions List -->
                    <main class="lg:col-span-2">
                        <div class="mb-4 text-gray-600 dark:text-gray-400">
                            <span class="text-gray-900 dark:text-white font-semibold">{{ questions.total }}</span> questions
                        </div>

                        <div v-if="questions.data?.length > 0" class="space-y-4">
                            <Link
                                v-for="q in questions.data"
                                :key="q.uid"
                                :href="route('questions.show', q.uid)"
                                class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md transition-all group"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs font-medium rounded-full">
                                                {{ q.type }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(q.date_question) }}
                                            </span>
                                        </div>

                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                            {{ q.analyse || q.rubrique || 'Question #' + q.numero }}
                                        </h3>

                                        <div class="flex items-center gap-3 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span v-if="q.rubrique">📋 {{ q.rubrique }}</span>
                                            <span v-if="q.ministere_sigle">🏛️ {{ q.ministere_sigle }}</span>
                                        </div>
                                    </div>

                                    <div class="shrink-0">
                                        <div 
                                            v-if="q.date_reponse"
                                            class="px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-medium rounded-full"
                                        >
                                            ✅ Répondue
                                        </div>
                                        <div 
                                            v-else
                                            class="px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-medium rounded-full"
                                        >
                                            ⏳ En attente
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Empty -->
                        <div v-else class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="text-6xl mb-4">📭</div>
                            <p class="text-gray-500 dark:text-gray-400">Ce député n'a pas encore posé de questions</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="questions.last_page > 1" class="flex justify-center gap-2 mt-8">
                            <Link
                                v-for="link in questions.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : link.url
                                            ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
