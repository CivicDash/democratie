<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    question: { type: Object, required: true },
    similaires: { type: Array, default: () => [] },
    autresSenateur: { type: Array, default: () => [] },
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function getDelaiJours() {
    if (!props.question.date_question || !props.question.date_reponse) return null;
    const dateQ = new Date(props.question.date_question);
    const dateR = new Date(props.question.date_reponse);
    return Math.floor((dateR - dateQ) / (1000 * 60 * 60 * 24));
}

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Sénateurs', href: route('representants.senateurs.index'), icon: '🏛️' },
    { label: 'Questions', href: route('questions.senat.index'), icon: '❓' },
    { label: `Question #${props.question.numero}`, current: true },
];
</script>

<template>
    <Head :title="`Question #${question.numero} - Sénat`" />

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
                        <div class="flex items-start gap-4">
                            <!-- Photo sénateur -->
                            <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center overflow-hidden shrink-0">
                                <img 
                                    v-if="question.senateur?.photo_url" 
                                    :src="question.senateur.photo_url"
                                    :alt="question.senateur.nom"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-4xl">👤</span>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="px-3 py-1 bg-white/20 text-white text-sm font-medium rounded-full">
                                        {{ question.type || 'Question' }}
                                    </span>
                                    <span class="px-3 py-1 bg-rose-100/30 text-rose-100 text-sm font-medium rounded-full">
                                        Sénat
                                    </span>
                                    <span 
                                        v-if="question.a_reponse"
                                        class="px-3 py-1 bg-emerald-500/30 text-emerald-200 text-sm font-medium rounded-full"
                                    >
                                        ✅ Répondue
                                    </span>
                                    <span 
                                        v-else
                                        class="px-3 py-1 bg-amber-500/30 text-amber-200 text-sm font-medium rounded-full"
                                    >
                                        ⏳ En attente
                                    </span>
                                </div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
                                    Question #{{ question.numero }}
                                </h1>
                                <p class="text-rose-100">
                                    Par 
                                    <Link 
                                        v-if="question.senateur"
                                        :href="route('representants.senateurs.show', question.senateur_matricule)"
                                        class="font-semibold hover:underline"
                                    >
                                        {{ question.senateur.prenom }} {{ question.senateur.nom }}
                                    </Link>
                                    <span v-else>Sénateur inconnu</span>
                                    • {{ formatDate(question.date_question) }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="flex flex-wrap gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl font-bold text-white">{{ formatDate(question.date_question) }}</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Date dépôt</div>
                            </div>
                            <div v-if="question.a_reponse" class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center min-w-[100px]">
                                <div class="text-2xl font-bold text-emerald-400">{{ getDelaiJours() }} jours</div>
                                <div class="text-rose-200 text-xs uppercase tracking-wide">Délai réponse</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Thème -->
                        <div v-if="question.theme" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Thème</h2>
                            <p class="text-lg text-gray-900 dark:text-white font-medium">{{ question.theme }}</p>
                            <p v-if="question.sous_theme" class="text-gray-600 dark:text-gray-400 mt-1">{{ question.sous_theme }}</p>
                        </div>

                        <!-- Question -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                ❓ Texte de la question
                            </h2>
                            <div class="prose prose-gray dark:prose-invert max-w-none">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">
                                    {{ question.texte_question || 'Texte non disponible' }}
                                </p>
                            </div>
                        </div>

                        <!-- Réponse -->
                        <div v-if="question.a_reponse" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-700 p-6">
                            <h2 class="text-lg font-semibold text-emerald-900 dark:text-emerald-200 mb-4 flex items-center gap-2">
                                ✅ Réponse ministérielle
                                <span class="text-sm font-normal text-emerald-600 dark:text-emerald-400">
                                    • {{ formatDate(question.date_reponse) }}
                                </span>
                            </h2>
                            <div class="prose prose-emerald dark:prose-invert max-w-none">
                                <p class="text-emerald-800 dark:text-emerald-200 whitespace-pre-wrap leading-relaxed">
                                    {{ question.texte_reponse }}
                                </p>
                            </div>
                        </div>

                        <!-- En attente -->
                        <div v-else class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-700 p-6">
                            <h2 class="text-lg font-semibold text-amber-900 dark:text-amber-200 mb-2 flex items-center gap-2">
                                ⏳ En attente de réponse
                            </h2>
                            <p class="text-amber-700 dark:text-amber-300">
                                Cette question n'a pas encore reçu de réponse ministérielle.
                                Les ministres disposent d'un délai de 2 mois pour répondre.
                            </p>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <aside class="space-y-6">
                        
                        <!-- Informations -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">📋 Informations</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Numéro</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ question.numero }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Type</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ question.type }}</dd>
                                </div>
                                <div v-if="question.ministre_destinataire">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Ministère</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ question.ministre_destinataire }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Date de dépôt</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ formatDate(question.date_question) }}</dd>
                                </div>
                                <div v-if="question.date_reponse">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Date de réponse</dt>
                                    <dd class="font-medium text-emerald-600 dark:text-emerald-400">{{ formatDate(question.date_reponse) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Auteur -->
                        <div v-if="question.senateur" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">👤 Auteur</h3>
                            <Link 
                                :href="route('representants.senateurs.show', question.senateur_matricule)"
                                class="flex items-center gap-3 group"
                            >
                                <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                    <img 
                                        v-if="question.senateur.photo_url" 
                                        :src="question.senateur.photo_url"
                                        :alt="question.senateur.nom"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                                        {{ question.senateur.prenom }} {{ question.senateur.nom }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Sénateur</div>
                                </div>
                            </Link>
                            <Link 
                                :href="route('questions.senateur', question.senateur_matricule)"
                                class="mt-4 block text-center px-4 py-2 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-lg text-sm font-medium hover:bg-rose-200 dark:hover:bg-rose-900/50 transition-colors"
                            >
                                Voir toutes ses questions →
                            </Link>
                        </div>

                        <!-- Questions similaires -->
                        <div v-if="similaires.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">📚 Questions similaires</h3>
                            <div class="space-y-3">
                                <Link 
                                    v-for="q in similaires"
                                    :key="q.numero"
                                    :href="route('questions.senat.show', q.numero)"
                                    class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <div class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">
                                        {{ q.theme || 'Question #' + q.numero }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ q.senateur?.prenom }} {{ q.senateur?.nom }}
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Autres questions du sénateur -->
                        <div v-if="autresSenateur.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">📝 Autres questions</h3>
                            <div class="space-y-3">
                                <Link 
                                    v-for="q in autresSenateur"
                                    :key="q.numero"
                                    :href="route('questions.senat.show', q.numero)"
                                    class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <div class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">
                                        {{ q.theme || 'Question #' + q.numero }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                                        <span 
                                            :class="q.a_reponse ? 'text-emerald-600' : 'text-amber-600'"
                                        >
                                            {{ q.a_reponse ? '✅' : '⏳' }}
                                        </span>
                                        {{ formatDate(q.date_question) }}
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
