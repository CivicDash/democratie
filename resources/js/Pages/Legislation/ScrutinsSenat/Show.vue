<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    scrutin: Object,
    votesParPosition: Object,
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const hasVoteData = computed(() => {
    const s = props.scrutin;
    return (s.pour > 0 || s.contre > 0 || s.votants > 0 || s.suffrages_exprimes > 0);
});

const abstentions = computed(() => {
    if (!hasVoteData.value) return 0;
    return (props.scrutin.suffrages_exprimes || 0) - (props.scrutin.pour || 0) - (props.scrutin.contre || 0);
});

const tauxAdoption = computed(() => {
    const exprimes = (props.scrutin.pour || 0) + (props.scrutin.contre || 0);
    return exprimes > 0 ? Math.round((props.scrutin.pour / exprimes) * 100) : 0;
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard') },
    { label: 'Sénat', href: route('representants.senateurs.index') },
    { label: 'Scrutins', href: route('legislation.scrutins-senat.index') },
    { label: `Scrutin n°${props.scrutin.numero}`, current: true },
];

const positionLabels = {
    'pour': { label: 'Pour', color: 'green', icon: '✅' },
    'contre': { label: 'Contre', color: 'red', icon: '❌' },
    'abstention': { label: 'Abstention', color: 'gray', icon: '⚪' },
    'non votant': { label: 'Non votant', color: 'gray', icon: '➖' },
};
</script>

<template>
    <Head :title="`Scrutin n°${scrutin.numero} - Sénat`" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-rose-50 via-white to-slate-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <!-- Header -->
            <div class="bg-gradient-to-r from-rose-600 to-rose-700 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <Breadcrumb :items="breadcrumbs" class="mb-4 text-rose-100" />
                    
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center flex-shrink-0">
                            <span class="text-3xl">🗳️</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span
                                    class="px-3 py-1 rounded-full text-sm font-bold"
                                    :class="scrutin.resultat === 'Adopté' 
                                        ? 'bg-green-500 text-white' 
                                        : 'bg-red-500 text-white'"
                                >
                                    {{ scrutin.resultat }}
                                </span>
                                <span class="text-rose-200">
                                    Scrutin n°{{ scrutin.numero }}
                                </span>
                                <span class="text-rose-200">
                                    {{ formatDate(scrutin.date_scrutin) }}
                                </span>
                            </div>
                            <h1 class="text-2xl font-bold">
                                {{ scrutin.titre || scrutin.objet || 'Sans titre' }}
                            </h1>
                            <p v-if="scrutin.objet && scrutin.titre" class="text-rose-100 mt-2">
                                {{ scrutin.objet }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Pas de données -->
                <div v-if="!hasVoteData" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-8 mb-8 text-center">
                    <div class="text-4xl mb-3">📊</div>
                    <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-300 mb-2">
                        Donnees de vote non disponibles
                    </h2>
                    <p class="text-amber-700 dark:text-amber-400 text-sm max-w-md mx-auto">
                        Les resultats detailles de ce scrutin ne sont pas encore disponibles dans notre base de donnees.
                        Les compteurs agreges sont absents de la source Senat pour ce scrutin.
                    </p>
                </div>

                <!-- Résumé des votes -->
                <div v-if="hasVoteData" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Résultat du vote
                    </h2>
                    
                    <!-- Barre de votes -->
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex mb-4">
                        <div
                            class="h-full bg-green-500 flex items-center justify-center text-white text-sm font-bold"
                            :style="{ width: `${scrutin.suffrages_exprimes ? (scrutin.pour / scrutin.suffrages_exprimes) * 100 : 0}%` }"
                        >
                            <span v-if="scrutin.pour > 20">{{ scrutin.pour }}</span>
                        </div>
                        <div
                            class="h-full bg-red-500 flex items-center justify-center text-white text-sm font-bold"
                            :style="{ width: `${scrutin.suffrages_exprimes ? (scrutin.contre / scrutin.suffrages_exprimes) * 100 : 0}%` }"
                        >
                            <span v-if="scrutin.contre > 20">{{ scrutin.contre }}</span>
                        </div>
                        <div
                            class="h-full bg-gray-400 flex items-center justify-center text-white text-sm font-bold"
                            :style="{ width: `${scrutin.suffrages_exprimes ? (abstentions / scrutin.suffrages_exprimes) * 100 : 0}%` }"
                        >
                            <span v-if="abstentions > 20">{{ abstentions }}</span>
                        </div>
                    </div>
                    
                    <!-- Statistiques -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ scrutin.pour }}
                            </div>
                            <div class="text-sm text-green-700 dark:text-green-300">Pour</div>
                        </div>
                        <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                                {{ scrutin.contre }}
                            </div>
                            <div class="text-sm text-red-700 dark:text-red-300">Contre</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <div class="text-3xl font-bold text-gray-600 dark:text-gray-400">
                                {{ abstentions }}
                            </div>
                            <div class="text-sm text-gray-700 dark:text-gray-300">Abstentions</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {{ scrutin.votants }}
                            </div>
                            <div class="text-sm text-blue-700 dark:text-blue-300">Votants</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                {{ tauxAdoption }}%
                            </div>
                            <div class="text-sm text-purple-700 dark:text-purple-300">Taux d'adoption</div>
                        </div>
                    </div>
                </div>

                <!-- Votes par position -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <template v-for="(position, key) in ['pour', 'contre', 'abstention']" :key="position">
                        <div 
                            v-if="votesParPosition[position]?.length"
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden"
                        >
                            <div 
                                class="px-6 py-4 font-semibold flex items-center justify-between"
                                :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': position === 'pour',
                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': position === 'contre',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': position === 'abstention',
                                }"
                            >
                                <span>{{ positionLabels[position]?.label || position }}</span>
                                <span class="text-sm font-normal opacity-75">
                                    {{ votesParPosition[position]?.length || 0 }} sénateurs
                                </span>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <div 
                                    v-for="vote in votesParPosition[position]" 
                                    :key="vote.senateur?.matricule"
                                    class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <Link 
                                        :href="route('representants.senateurs.show', vote.senateur.matricule)"
                                        class="flex items-center gap-3"
                                    >
                                        <img 
                                            v-if="vote.senateur.photo_url"
                                            :src="vote.senateur.photo_url" 
                                            :alt="vote.senateur.nom"
                                            class="w-10 h-10 rounded-full object-cover"
                                        />
                                        <div 
                                            v-else
                                            class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-400"
                                        >
                                            👤
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white truncate">
                                                {{ vote.senateur.prenom }} {{ vote.senateur.nom }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ vote.senateur.groupe }}
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Informations complémentaires -->
                <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Informations complémentaires
                    </h2>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Session</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ scrutin.session_annee }}-{{ scrutin.session_annee + 1 }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Majorité requise</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ scrutin.majorite_requise }} voix
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Suffrages exprimés</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ scrutin.suffrages_exprimes }}
                            </dd>
                        </div>
                        <div v-if="scrutin.url_senat">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Lien officiel</dt>
                            <dd>
                                <a 
                                    :href="scrutin.url_senat" 
                                    target="_blank"
                                    class="text-rose-600 hover:text-rose-700 dark:text-rose-400"
                                >
                                    Voir sur senat.fr →
                                </a>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Retour -->
                <div class="mt-8 flex justify-center">
                    <Link
                        :href="route('legislation.scrutins-senat.index')"
                        class="px-6 py-3 bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-colors font-medium"
                    >
                        ← Retour à la liste des scrutins
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
