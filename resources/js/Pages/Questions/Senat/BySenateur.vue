<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    senateur: { type: Object, required: true },
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

const tauxReponse = props.stats.total > 0 
    ? Math.round((props.stats.repondues / props.stats.total) * 100) 
    : 0;

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Sénateurs', href: route('representants.senateurs.index'), icon: '🏛️' },
    { label: 'Questions', href: route('questions.senat.index'), icon: '❓' },
    { label: props.senateur.nom, current: true, icon: '👤' },
];
</script>

<template>
    <Head :title="`Questions de ${senateur.prenom} ${senateur.nom}`" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            
            <!-- Hero Section -->
            <section class="relative overflow-hidden bg-gradient-to-br from-rose-800 via-pink-700 to-fuchsia-800">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                </div>
                
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                    <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                    
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-full bg-white/20 overflow-hidden shrink-0">
                                <img 
                                    v-if="senateur.photo_url" 
                                    :src="senateur.photo_url"
                                    :alt="senateur.nom"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-4xl">👤</div>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
                                    Questions de {{ senateur.prenom }} {{ senateur.nom }}
                                </h1>
                                <p class="text-rose-100">
                                    Sénateur • 
                                    <Link 
                                        :href="route('representants.senateurs.show', senateur.matricule)"
                                        class="underline hover:text-white"
                                    >
                                        Voir le profil complet →
                                    </Link>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="flex flex-wrap gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-white">{{ stats.total }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Questions</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl md:text-3xl font-bold text-emerald-400">{{ tauxReponse }}%</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Répondues</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Questions List -->
                    <main class="lg:col-span-2">
                        <div class="mb-4 text-gray-600 dark:text-gray-400">
                            <span class="text-gray-900 dark:text-white font-semibold">{{ questions.total }}</span> questions
                        </div>

                        <div v-if="questions.data?.length > 0" class="space-y-4">
                            <Link
                                v-for="q in questions.data"
                                :key="q.numero"
                                :href="route('questions.senat.show', q.numero)"
                                class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-rose-300 dark:hover:border-rose-600 hover:shadow-md transition-all group"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex-1 min-w-0">
                                        <!-- Badges -->
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <span class="px-2 py-0.5 bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 text-xs font-medium rounded-full">
                                                {{ q.type }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(q.date_question) }}
                                            </span>
                                        </div>

                                        <!-- Titre -->
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors line-clamp-2">
                                            {{ q.theme || (q.texte_question?.substring(0, 100) + '...') || 'Question #' + q.numero }}
                                        </h3>

                                        <!-- Ministère -->
                                        <div v-if="q.ministre_destinataire" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            🏛️ {{ q.ministre_destinataire }}
                                        </div>
                                    </div>

                                    <!-- Statut -->
                                    <div class="shrink-0">
                                        <div 
                                            v-if="q.a_reponse"
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
                            <div class="text-6xl mb-4">🔍</div>
                            <p class="text-gray-500 dark:text-gray-400">Aucune question trouvée</p>
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
                                        ? 'bg-rose-600 text-white'
                                        : link.url
                                            ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </main>

                    <!-- Sidebar -->
                    <aside class="space-y-6">
                        <!-- Par thème -->
                        <div v-if="stats.par_theme?.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">🏷️ Thèmes favoris</h3>
                            <div class="space-y-2">
                                <div 
                                    v-for="theme in stats.par_theme"
                                    :key="theme.theme"
                                    class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                >
                                    <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ theme.theme }}</span>
                                    <span class="px-2 py-0.5 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-xs font-medium rounded-full">
                                        {{ theme.nb }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Par type -->
                        <div v-if="stats.par_type?.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">📝 Par type</h3>
                            <div class="space-y-2">
                                <div 
                                    v-for="type in stats.par_type"
                                    :key="type.type"
                                    class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                >
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ type.type }}</span>
                                    <span class="px-2 py-0.5 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 text-xs font-medium rounded-full">
                                        {{ type.nb }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
