<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    personne: Object,
    historique: Array,
    stats: Object,
    autres_mandats: Array,
});

const breadcrumbs = [
    { label: 'État', href: route('dashboard'), icon: '🇫🇷' },
    { label: 'Gouvernement', href: route('gouvernement.index'), icon: '🏛️' },
    { label: 'Personnes', href: route('gouvernement.personnes'), icon: '👥' },
    { label: props.personne.nom_complet, current: true },
];

// Couleurs par type de fonction
const typeColors = {
    premier_ministre: 'bg-blue-600 text-white',
    ministre_etat: 'bg-indigo-600 text-white',
    ministre: 'bg-violet-600 text-white',
    ministre_delegue: 'bg-purple-600 text-white',
    secretaire_etat: 'bg-pink-600 text-white',
};
</script>

<template>
    <Head :title="personne.nom_complet + ' - Fiche'" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 py-12">
            <!-- Motif de fond -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid-pers" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid-pers)"/>
                </svg>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />

                <div class="flex flex-col md:flex-row items-start gap-8">
                    <!-- Photo -->
                    <div class="flex-shrink-0">
                        <img 
                            v-if="personne.photo"
                            :src="personne.photo"
                            :alt="personne.nom_complet"
                            class="w-40 h-40 rounded-2xl object-cover border-4 border-white/20 shadow-xl"
                        />
                        <div v-else class="w-40 h-40 rounded-2xl bg-white/10 flex items-center justify-center text-6xl border-4 border-white/20">
                            {{ personne.civilite === 'Mme' ? '👩' : '👨' }}
                        </div>
                    </div>

                    <!-- Infos principales -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 flex-wrap mb-2">
                            <h1 class="text-3xl lg:text-4xl font-bold text-white">
                                {{ personne.nom_complet }}
                            </h1>
                            <span 
                                v-if="stats.est_actif"
                                class="px-3 py-1 bg-emerald-500 text-white text-sm font-bold rounded-full"
                            >
                                EN FONCTION
                            </span>
                        </div>

                        <p v-if="stats.poste_actuel" class="text-indigo-200 text-xl mb-4">
                            {{ stats.poste_actuel }}
                        </p>

                        <div class="flex flex-wrap gap-3 mb-4">
                            <span 
                                v-if="personne.parti_politique"
                                class="px-3 py-1 bg-white/20 text-white rounded-lg"
                            >
                                🏛️ {{ personne.parti_politique }}
                            </span>
                            <span v-if="personne.age" class="px-3 py-1 bg-white/20 text-white rounded-lg">
                                🎂 {{ personne.age }} ans
                            </span>
                            <span v-if="personne.profession" class="px-3 py-1 bg-white/20 text-white rounded-lg">
                                💼 {{ personne.profession }}
                            </span>
                        </div>

                        <!-- Statistiques clés -->
                        <div class="grid grid-cols-3 gap-4 mt-6">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                                <div class="text-3xl font-bold text-white">{{ stats.nb_postes }}</div>
                                <div class="text-indigo-200 text-sm">Poste{{ stats.nb_postes > 1 ? 's' : '' }}</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                                <div class="text-3xl font-bold text-white">{{ stats.nb_gouvernements }}</div>
                                <div class="text-indigo-200 text-sm">Gouvernement{{ stats.nb_gouvernements > 1 ? 's' : '' }}</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                                <div class="text-xl font-bold text-white">{{ stats.duree_totale }}</div>
                                <div class="text-indigo-200 text-sm">Durée totale</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    
                    <!-- Colonne principale -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Historique des postes -->
                        <Card>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                                📜 Parcours ministériel
                            </h2>

                            <div class="relative">
                                <!-- Timeline -->
                                <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                                <div class="space-y-6">
                                    <div 
                                        v-for="(poste, index) in historique" 
                                        :key="poste.id"
                                        class="relative pl-14"
                                    >
                                        <!-- Point timeline -->
                                        <div 
                                            :class="[
                                                'absolute left-4 w-5 h-5 rounded-full border-4 border-white dark:border-gray-900',
                                                poste.actif ? 'bg-emerald-500' : 'bg-gray-400'
                                            ]"
                                        ></div>

                                        <div 
                                            :class="[
                                                'p-4 rounded-xl border transition',
                                                poste.actif 
                                                    ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-700' 
                                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
                                            ]"
                                        >
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span 
                                                            :class="[
                                                                'px-2 py-0.5 text-xs rounded-full',
                                                                typeColors[poste.type_fonction] || 'bg-gray-500 text-white'
                                                            ]"
                                                        >
                                                            {{ poste.type_fonction_libelle }}
                                                        </span>
                                                        <span 
                                                            v-if="poste.actif"
                                                            class="px-2 py-0.5 bg-emerald-500 text-white text-xs rounded-full"
                                                        >
                                                            En cours
                                                        </span>
                                                    </div>
                                                    <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                                        {{ poste.fonction }}
                                                    </h3>
                                                    <p v-if="poste.ministere" class="text-sm text-blue-600 dark:text-blue-400">
                                                        🏢 {{ poste.ministere }}
                                                    </p>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        Sous {{ poste.gouvernement }}
                                                        <span class="text-gray-400">({{ poste.premier_ministre }})</span>
                                                    </p>
                                                </div>
                                                <div class="text-right text-sm">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ poste.date_debut }}
                                                    </div>
                                                    <div class="text-gray-500">
                                                        {{ poste.date_fin || 'en cours' }}
                                                    </div>
                                                    <div class="text-gray-400 mt-1">
                                                        {{ poste.duree }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Biographie Wikipedia -->
                        <Card v-if="personne.wikipedia_extract">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                📖 Biographie
                            </h2>
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                {{ personne.wikipedia_extract }}
                            </p>
                            <a 
                                v-if="personne.wikipedia_url"
                                :href="personne.wikipedia_url"
                                target="_blank"
                                class="inline-flex items-center gap-2 mt-4 text-blue-600 dark:text-blue-400 hover:underline"
                            >
                                📚 Lire la suite sur Wikipedia →
                            </a>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        
                        <!-- Infos personnelles -->
                        <Card>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                                👤 Informations
                            </h2>
                            <dl class="space-y-3">
                                <div v-if="personne.date_naissance" class="flex justify-between">
                                    <dt class="text-gray-500">Naissance</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ personne.date_naissance }}</dd>
                                </div>
                                <div v-if="personne.lieu_naissance" class="flex justify-between">
                                    <dt class="text-gray-500">Lieu</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ personne.lieu_naissance }}</dd>
                                </div>
                                <div v-if="personne.profession" class="flex justify-between">
                                    <dt class="text-gray-500">Profession</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ personne.profession }}</dd>
                                </div>
                                <div v-if="personne.parti_politique" class="flex justify-between">
                                    <dt class="text-gray-500">Parti</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ personne.parti_politique }}</dd>
                                </div>
                            </dl>
                        </Card>

                        <!-- Liens externes -->
                        <Card v-if="personne.wikipedia_url || personne.twitter_url || personne.site_web">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                                🔗 Liens
                            </h2>
                            <div class="space-y-2">
                                <a 
                                    v-if="personne.wikipedia_url"
                                    :href="personne.wikipedia_url"
                                    target="_blank"
                                    class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <span class="text-xl">📚</span>
                                    <span class="text-gray-900 dark:text-gray-100">Wikipedia</span>
                                </a>
                                <a 
                                    v-if="personne.twitter_url"
                                    :href="personne.twitter_url"
                                    target="_blank"
                                    class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <span class="text-xl">🐦</span>
                                    <span class="text-gray-900 dark:text-gray-100">Twitter / X</span>
                                </a>
                                <a 
                                    v-if="personne.site_web"
                                    :href="personne.site_web"
                                    target="_blank"
                                    class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <span class="text-xl">🌐</span>
                                    <span class="text-gray-900 dark:text-gray-100">Site officiel</span>
                                </a>
                            </div>
                        </Card>

                        <!-- Autres mandats -->
                        <Card v-if="autres_mandats.length > 0">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                                🏛️ Autres mandats
                            </h2>
                            <div class="space-y-2">
                                <Link
                                    v-for="mandat in autres_mandats"
                                    :key="mandat.type + mandat.uid"
                                    :href="mandat.type === 'depute' ? route('deputes.show', mandat.uid) : route('senateurs.show', mandat.uid)"
                                    class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition"
                                >
                                    <span class="text-xl">{{ mandat.type === 'depute' ? '🔵' : '🔴' }}</span>
                                    <span class="text-blue-700 dark:text-blue-300">
                                        {{ mandat.type === 'depute' ? 'Député' : 'Sénateur' }}
                                    </span>
                                    <span class="ml-auto">→</span>
                                </Link>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
