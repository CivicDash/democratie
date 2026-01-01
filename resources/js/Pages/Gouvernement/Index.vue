<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    gouvernement: Object,
    ministeres: Array,
    ministres: Array,
    stats: Object,
});

const selectedVue = ref('organigramme'); // organigramme, liste, partis

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'État', icon: '🇫🇷' },
    { label: 'Gouvernement', current: true, icon: '🏛️' },
];

// Regrouper les ministres par type
const ministresParType = computed(() => {
    if (!props.ministres) return {};
    return {
        'Premier ministre': props.ministres.filter(m => m.type_fonction === 'premier_ministre'),
        'Ministres': props.ministres.filter(m => m.type_fonction === 'ministre'),
        'Ministres délégués': props.ministres.filter(m => m.type_fonction === 'ministre_delegue'),
        'Secrétaires d\'État': props.ministres.filter(m => m.type_fonction === 'secretaire_etat'),
    };
});

// Couleurs par parti
const getPartiCouleur = (parti) => {
    const couleurs = {
        'Renaissance': '#FFD700',
        'MoDem': '#FF8C00',
        'LR': '#0066CC',
        'Horizons': '#00BFFF',
        'PS': '#FF69B4',
        'EELV': '#228B22',
        'UDI': '#87CEEB',
        'DVG': '#FFC0CB',
        'DVD': '#ADD8E6',
        'Sans étiquette': '#808080',
    };
    return couleurs[parti] || '#6b7280';
};
</script>

<template>
    <Head title="Gouvernement" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div v-if="gouvernement" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                            <span class="text-4xl">🏛️</span>
                            {{ gouvernement.nom }}
                        </h1>
                        <p class="text-blue-200 text-lg">
                            Premier ministre : <strong class="text-white">{{ gouvernement.premier_ministre }}</strong>
                        </p>
                        <p class="text-blue-300 text-sm mt-2">
                            Depuis le {{ gouvernement.date_debut }} • {{ gouvernement.duree }}
                        </p>
                    </div>
                </div>
                <div v-else class="text-center py-12">
                    <div class="text-6xl mb-4">🏛️</div>
                    <h1 class="text-3xl font-bold text-white mb-3">Gouvernement</h1>
                    <p class="text-blue-200">Aucun gouvernement enregistré. Exécutez la commande d'import.</p>
                </div>

                <!-- Stats clés -->
                <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.total }}</div>
                        <div class="text-blue-200 text-sm">Membres du gouvernement</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.nb_ministres }}</div>
                        <div class="text-blue-200 text-sm">Ministres</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.nb_ministres_delegues }}</div>
                        <div class="text-blue-200 text-sm">Ministres délégués</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.nb_secretaires_etat }}</div>
                        <div class="text-blue-200 text-sm">Secrétaires d'État</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div v-if="gouvernement" class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Tabs de vue -->
                <div class="flex gap-2 mb-6 flex-wrap">
                    <button 
                        @click="selectedVue = 'organigramme'"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'organigramme' 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        🏢 Organigramme
                    </button>
                    <button 
                        @click="selectedVue = 'liste'"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'liste' 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        📋 Liste
                    </button>
                    <button 
                        @click="selectedVue = 'partis'"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            selectedVue === 'partis' 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        🎨 Par parti
                    </button>
                </div>

                <!-- Vue Organigramme -->
                <div v-if="selectedVue === 'organigramme'" class="space-y-6">
                    <!-- Premier ministre -->
                    <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-blue-200 dark:border-blue-700">
                        <div v-if="ministresParType['Premier ministre']?.[0]" class="flex items-center gap-6">
                            <img 
                                :src="ministresParType['Premier ministre'][0].photo_url" 
                                :alt="ministresParType['Premier ministre'][0].nom_complet"
                                class="w-24 h-24 rounded-full object-cover border-4 border-blue-500"
                            />
                            <div>
                                <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full uppercase">
                                    Premier ministre
                                </span>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                    {{ ministresParType['Premier ministre'][0].nom_complet }}
                                </h2>
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ ministresParType['Premier ministre'][0].parti || 'Sans étiquette' }}
                                </p>
                            </div>
                        </div>
                    </Card>

                    <!-- Ministères -->
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card 
                            v-for="ministere in ministeres" 
                            :key="ministere.id"
                            class="hover:shadow-lg transition"
                            :style="{ borderLeftColor: ministere.couleur, borderLeftWidth: '4px' }"
                        >
                            <div class="flex items-start gap-4">
                                <div v-if="ministere.ministre" class="flex-shrink-0">
                                    <img 
                                        :src="ministere.ministre.photo_url" 
                                        :alt="ministere.ministre.nom_complet"
                                        class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span 
                                        class="px-2 py-1 text-xs font-bold text-white rounded"
                                        :style="{ backgroundColor: ministere.couleur }"
                                    >
                                        {{ ministere.sigle }}
                                    </span>
                                    <h3 class="font-medium text-gray-900 dark:text-gray-100 mt-2 line-clamp-2 text-sm">
                                        {{ ministere.nom }}
                                    </h3>
                                    <p v-if="ministere.ministre" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ ministere.ministre.nom_complet }}
                                    </p>
                                    <span 
                                        v-if="ministere.ministre?.parti"
                                        class="inline-block mt-2 px-2 py-0.5 text-xs rounded-full"
                                        :style="{ backgroundColor: getPartiCouleur(ministere.ministre.parti) + '20', color: getPartiCouleur(ministere.ministre.parti) }"
                                    >
                                        {{ ministere.ministre.parti }}
                                    </span>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Vue Liste -->
                <div v-if="selectedVue === 'liste'" class="space-y-6">
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📋 Tous les membres du gouvernement
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Membre</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Fonction</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Ministère</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Parti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="ministre in ministres" 
                                        :key="ministre.id"
                                        class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition"
                                    >
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-3">
                                                <img 
                                                    :src="ministre.photo_url" 
                                                    :alt="ministre.nom_complet"
                                                    class="w-10 h-10 rounded-full object-cover"
                                                />
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ ministre.nom_complet }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300 text-sm">
                                            {{ ministre.fonction }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span v-if="ministre.ministere_sigle" class="px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded text-xs">
                                                {{ ministre.ministere_sigle }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span 
                                                class="px-2 py-1 rounded text-xs"
                                                :style="{ 
                                                    backgroundColor: getPartiCouleur(ministre.parti) + '20', 
                                                    color: getPartiCouleur(ministre.parti) 
                                                }"
                                            >
                                                {{ ministre.parti || 'N/A' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Vue Partis -->
                <div v-if="selectedVue === 'partis'" class="space-y-6">
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            🎨 Répartition par parti politique
                        </h2>
                        <div class="space-y-4">
                            <div v-for="(count, parti) in stats.partis" :key="parti" class="relative">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ parti }}
                                    </span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ count }} membre(s)
                                    </span>
                                </div>
                                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{ 
                                            width: (count / stats.total * 100) + '%', 
                                            backgroundColor: getPartiCouleur(parti),
                                            minWidth: '2%'
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Source des données -->
                <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        🏛️ Données issues de 
                        <a href="https://www.gouvernement.fr/composition-du-gouvernement" target="_blank" class="text-blue-600 hover:underline">
                            gouvernement.fr
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
