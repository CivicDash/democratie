<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import VideoButton from '@/Components/VideoButton.vue';

const props = defineProps({
    question: { type: Object, required: true },
    similaires: { type: Array, default: () => [] },
    autresDepute: { type: Array, default: () => [] },
    video_url: { type: String, default: null },
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
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
    { label: `Question n°${props.question.numero}`, current: true },
];
</script>

<template>
    <Head :title="question.analyse || 'Question #' + question.numero" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-800 dark:to-indigo-900">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" class="mb-4 text-indigo-100" />

                    <div class="flex items-start gap-4">
                        <!-- Type badge -->
                        <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-3xl shrink-0">
                            ❓
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="px-2.5 py-1 bg-white/20 text-white text-sm font-medium rounded-full">
                                    {{ question.type }}
                                </span>
                                <span 
                                    v-if="question.groupe_sigle"
                                    class="px-2.5 py-1 text-sm font-medium rounded-full"
                                    :class="getGroupeColor(question.groupe_sigle)"
                                >
                                    {{ question.groupe_sigle }}
                                </span>
                                <span 
                                    v-if="question.date_reponse"
                                    class="px-2.5 py-1 bg-emerald-500 text-white text-sm font-medium rounded-full"
                                >
                                    ✅ Répondue
                                </span>
                                <span 
                                    v-else
                                    class="px-2.5 py-1 bg-amber-500 text-white text-sm font-medium rounded-full"
                                >
                                    ⏳ En attente
                                </span>
                            </div>
                            
                            <h1 class="text-2xl md:text-3xl font-bold text-white">
                                {{ question.analyse || 'Question n°' + question.numero }}
                            </h1>
                            
                            <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-indigo-100">
                                <span>📅 {{ formatDate(question.date_question) }}</span>
                                <span v-if="question.rubrique">📋 {{ question.rubrique }}</span>
                                <span v-if="question.ministere_nom">🏛️ {{ question.ministere_nom }}</span>
                                <VideoButton v-if="video_url" :href="video_url" variant="hero" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    
                    <!-- Main Content -->
                    <main class="lg:col-span-2 space-y-6">
                        <!-- Question -->
                        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <span class="text-indigo-500">❓</span> La Question
                            </h2>
                            <div 
                                v-if="question.texte_question"
                                class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300"
                                v-html="question.texte_question"
                            />
                            <p v-else class="text-gray-500 dark:text-gray-400 italic">
                                Texte de la question non disponible
                            </p>
                        </section>

                        <!-- Réponse -->
                        <section 
                            v-if="question.texte_reponse || question.date_reponse"
                            class="bg-white dark:bg-gray-800 border-2 border-emerald-200 dark:border-emerald-800 rounded-xl p-6 shadow-sm"
                        >
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <span class="text-emerald-500">✅</span> La Réponse
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    ({{ formatDate(question.date_reponse) }})
                                </span>
                            </h2>
                            <div 
                                v-if="question.texte_reponse"
                                class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300"
                                v-html="question.texte_reponse"
                            />
                            <p v-else class="text-gray-500 dark:text-gray-400 italic">
                                Texte de la réponse non disponible
                            </p>
                        </section>

                        <!-- En attente -->
                        <section 
                            v-else
                            class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 text-center"
                        >
                            <div class="text-4xl mb-3">⏳</div>
                            <h2 class="text-lg font-semibold text-amber-700 dark:text-amber-400">En attente de réponse</h2>
                            <p class="text-sm text-amber-600 dark:text-amber-300 mt-2">
                                Cette question n'a pas encore reçu de réponse du gouvernement.
                            </p>
                        </section>

                        <!-- Lien officiel -->
                        <div class="flex gap-4">
                            <a 
                                :href="`https://questions.assemblee-nationale.fr/q${question.legislature}/17-${question.numero}QG.htm`"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition-colors"
                            >
                                🔗 Voir sur Assemblée Nationale
                            </a>
                        </div>
                    </main>

                    <!-- Sidebar -->
                    <aside class="space-y-6">
                        <!-- Député -->
                        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                                Auteur de la question
                            </h3>
                            <Link 
                                v-if="question.acteur"
                                :href="route('representants.deputes.show', question.acteur_ref)"
                                class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            >
                                <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-2xl overflow-hidden">
                                    <img 
                                        v-if="question.acteur.photo_url" 
                                        :src="question.acteur.photo_url" 
                                        :alt="question.acteur.nom"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else>👤</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ question.acteur.prenom }} {{ question.acteur.nom }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ question.groupe_nom }}
                                    </p>
                                </div>
                            </Link>
                            <div v-else class="text-gray-500 dark:text-gray-400 text-sm">
                                Député non identifié
                            </div>

                            <!-- Autres questions du député -->
                            <div v-if="autresDepute.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Autres questions de ce député :</p>
                                <div class="space-y-2">
                                    <Link
                                        v-for="q in autresDepute"
                                        :key="q.uid"
                                        :href="route('questions.show', q.uid)"
                                        class="block p-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 text-sm transition-colors"
                                    >
                                        <p class="text-gray-700 dark:text-gray-300 line-clamp-1">
                                            {{ q.analyse || q.rubrique }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(q.date_question) }}
                                        </p>
                                    </Link>
                                </div>
                                <Link
                                    v-if="question.acteur"
                                    :href="route('questions.depute', question.acteur_ref)"
                                    class="block mt-3 text-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                                >
                                    Voir toutes ses questions →
                                </Link>
                            </div>
                        </section>

                        <!-- Ministère -->
                        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                                Ministère interrogé
                            </h3>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-xl">
                                    🏛️
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ question.ministere_nom || question.ministere_sigle }}</p>
                                    <p v-if="question.ministere_sigle" class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ question.ministere_sigle }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Questions similaires -->
                        <section 
                            v-if="similaires.length > 0"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm"
                        >
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                                Questions similaires
                            </h3>
                            <div class="space-y-2">
                                <Link
                                    v-for="q in similaires"
                                    :key="q.uid"
                                    :href="route('questions.show', q.uid)"
                                    class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">
                                        {{ q.analyse || q.rubrique }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ q.acteur?.prenom }} {{ q.acteur?.nom }}</span>
                                        <span>•</span>
                                        <span>{{ formatDate(q.date_question) }}</span>
                                    </div>
                                </Link>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
