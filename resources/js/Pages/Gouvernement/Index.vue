<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    gouvernement: Object,
    postesParType: Object,
    stats: Object,
    gouvernementsParPresident: Array,
});

const selectedVue = ref('organigramme'); // organigramme, liste, partis
const showGouvernementSelector = ref(false);

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'État', icon: '🇫🇷' },
    { label: 'Gouvernement', current: true, icon: '🏛️' },
];

// Récupérer tous les membres pour la vue liste
const tousLesMembres = computed(() => {
    if (!props.postesParType) return [];
    const membres = [];
    Object.entries(props.postesParType).forEach(([type, postes]) => {
        postes.forEach(p => membres.push({ ...p, type }));
    });
    return membres;
});

// Labels des types
const typeLabels = {
    'premier_ministre': 'Premier ministre',
    'ministre_etat': 'Ministre d\'État',
    'ministre': 'Ministres',
    'ministre_delegue': 'Ministres délégués',
    'secretaire_etat': 'Secrétaires d\'État',
};

// Couleurs des types
const typeColors = {
    'premier_ministre': 'bg-blue-600',
    'ministre_etat': 'bg-purple-600',
    'ministre': 'bg-indigo-600',
    'ministre_delegue': 'bg-emerald-600',
    'secretaire_etat': 'bg-amber-600',
};

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

// Changer de gouvernement
const selectGouvernement = (gouvId) => {
    router.get(route('gouvernement.index'), { gouvernement: gouvId }, {
        preserveState: false,
        preserveScroll: false,
    });
    showGouvernementSelector.value = false;
};
</script>

<template>
    <Head title="Gouvernement" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#28285a] via-[#1e1e4a] to-slate-900">
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div v-if="gouvernement" class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <!-- Logo Présidence + Infos -->
                    <div class="flex items-start gap-6 flex-1">
                        <!-- Logo de la Présidence -->
                        <Link 
                            :href="route('gouvernement.president')"
                            class="hidden md:flex w-24 h-24 lg:w-28 lg:h-28 bg-white rounded-full p-2 shadow-xl flex-shrink-0 hover:scale-105 transition group"
                            title="Voir la fiche du Président"
                        >
                            <img 
                                src="/images/Logo_de_la_présidence_de_la_République_(2018).svg"
                                alt="Présidence de la République"
                                class="w-full h-full object-contain"
                            />
                        </Link>
                        
                        <div class="flex-1">
                            <!-- Président -->
                            <Link 
                                :href="route('gouvernement.president')"
                                class="inline-flex items-center gap-2 text-blue-200 hover:text-white transition mb-2 group"
                            >
                                <span class="text-sm">🇫🇷 Président :</span>
                                <strong class="text-white group-hover:underline">{{ gouvernement.president }}</strong>
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                            
                            <div class="flex items-center gap-3 mb-2">
                                <span v-if="gouvernement.actif" class="px-3 py-1 bg-emerald-500 text-white text-xs font-bold rounded-full">
                                    ACTIF
                                </span>
                                <span v-else class="px-3 py-1 bg-gray-500 text-white text-xs font-bold rounded-full">
                                    HISTORIQUE
                                </span>
                                <span v-if="gouvernement.numero" class="text-blue-300 text-sm">
                                    {{ gouvernement.numero }}ème gouvernement de la Vème République
                                </span>
                            </div>
                            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                                <span class="text-4xl">🏛️</span>
                                {{ gouvernement.nom_complet || gouvernement.nom }}
                            </h1>
                            <p class="text-blue-200 text-lg">
                                Premier ministre : <strong class="text-white">{{ gouvernement.premier_ministre }}</strong>
                            </p>
                            <p class="text-blue-300 text-sm mt-2">
                                {{ gouvernement.date_debut }} 
                                <span v-if="gouvernement.date_fin">→ {{ gouvernement.date_fin }}</span>
                                • {{ gouvernement.duree }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Boutons d'action -->
                    <div class="flex flex-col sm:flex-row gap-2">
                        <Link 
                            :href="route('donnees.gouvernements')"
                            class="flex items-center gap-2 px-4 py-3 bg-emerald-500/80 hover:bg-emerald-500 border border-emerald-400/50 rounded-lg text-white transition"
                        >
                            <span>📊 Statistiques</span>
                        </Link>
                        <button 
                            @click="showGouvernementSelector = !showGouvernementSelector"
                            class="flex items-center gap-2 px-4 py-3 bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg text-white transition"
                        >
                            <span>📅 Voir un autre gouvernement</span>
                            <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': showGouvernementSelector }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div v-else class="text-center py-12">
                    <div class="text-6xl mb-4">🏛️</div>
                    <h1 class="text-3xl font-bold text-white mb-3">Gouvernement</h1>
                    <p class="text-blue-200">Aucun gouvernement enregistré.</p>
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

        <!-- Overlay + Dropdown sélecteur de gouvernement (hors du container overflow-hidden) -->
        <Teleport to="body">
            <div v-if="showGouvernementSelector">
                <!-- Overlay -->
                <div 
                    @click="showGouvernementSelector = false"
                    class="fixed inset-0 z-40 bg-black/20"
                ></div>
                
                <!-- Dropdown -->
                <div 
                    class="fixed top-20 right-4 sm:right-8 w-[calc(100vw-2rem)] sm:w-96 max-h-[70vh] bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 z-50 flex flex-col"
                >
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">Sélectionner un gouvernement</h3>
                            <button @click="showGouvernementSelector = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="overflow-y-auto flex-1 p-2">
                        <div v-for="groupe in gouvernementsParPresident" :key="groupe.president" class="mb-4">
                            <!-- En-tête du Président -->
                            <div class="px-3 py-2 bg-[#28285a]/10 dark:bg-[#28285a]/30 rounded-lg mb-2 flex items-center gap-3 sticky top-0">
                                <img 
                                    src="/images/Logo_de_la_présidence_de_la_République_(2018).svg"
                                    alt="Présidence"
                                    class="w-6 h-6"
                                />
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                        {{ groupe.president }}
                                    </h4>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ groupe.periode }}
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <button
                                    v-for="gouv in groupe.gouvernements"
                                    :key="gouv.id"
                                    @click="selectGouvernement(gouv.id)"
                                    :class="[
                                        'w-full text-left px-3 py-2 rounded-lg transition flex items-center justify-between group',
                                        gouvernement.id === gouv.id
                                            ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                            : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'
                                    ]"
                                >
                                    <div>
                                        <div class="font-medium text-sm flex items-center gap-2">
                                            <span v-if="gouv.actif" class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                            {{ gouv.nom_complet || gouv.nom }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <span class="font-medium">PM:</span> {{ gouv.premier_ministre }} • {{ gouv.date_debut }}
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                                        {{ gouv.duree }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Contenu principal -->
        <div v-if="gouvernement" class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Avertissement si pas de données -->
                <div v-if="!tousLesMembres.length" 
                     class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <h3 class="font-semibold text-amber-800 dark:text-amber-200">Données à importer</h3>
                            <p class="text-amber-700 dark:text-amber-300 text-sm mt-1">
                                La composition de ce gouvernement n'a pas encore été importée.
                            </p>
                        </div>
                    </div>
                </div>

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
                <div v-if="selectedVue === 'organigramme'" class="space-y-8">
                    <!-- Par type de fonction -->
                    <div v-for="(postes, type) in postesParType" :key="type">
                        <div v-if="postes.length > 0" class="space-y-4">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3">
                                <span :class="[typeColors[type], 'w-3 h-3 rounded-full']"></span>
                                {{ typeLabels[type] }}
                                <span class="text-sm font-normal text-gray-500">({{ postes.length }})</span>
                            </h2>
                            
                            <!-- Premier ministre en grand -->
                            <div v-if="type === 'premier_ministre'" class="max-w-2xl">
                                <Card 
                                    v-for="poste in postes" 
                                    :key="poste.id"
                                    class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-blue-200 dark:border-blue-700"
                                >
                                    <Link 
                                        v-if="poste.personne?.slug"
                                        :href="route('gouvernement.personne', poste.personne.slug)"
                                        class="flex items-center gap-6 hover:opacity-90 transition"
                                    >
                                        <img 
                                            v-if="poste.personne?.photo"
                                            :src="poste.personne.photo" 
                                            :alt="poste.personne?.nom_complet"
                                            class="w-24 h-24 rounded-full object-cover border-4 border-blue-500"
                                        />
                                        <div v-else class="w-24 h-24 rounded-full bg-blue-200 dark:bg-blue-800 flex items-center justify-center text-3xl border-4 border-blue-500">
                                            👤
                                        </div>
                                        <div>
                                            <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full uppercase">
                                                Premier ministre
                                            </span>
                                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                                {{ poste.personne?.nom_complet }}
                                            </h3>
                                            <p class="text-gray-600 dark:text-gray-400">
                                                {{ poste.personne?.parti_politique || 'Sans étiquette' }}
                                            </p>
                                            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                                Voir la fiche →
                                            </p>
                                        </div>
                                    </Link>
                                    <div v-else class="flex items-center gap-6">
                                        <div class="w-24 h-24 rounded-full bg-blue-200 dark:bg-blue-800 flex items-center justify-center text-3xl border-4 border-blue-500">
                                            👤
                                        </div>
                                        <div>
                                            <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full uppercase">
                                                Premier ministre
                                            </span>
                                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                                {{ poste.personne?.nom_complet || gouvernement.premier_ministre }}
                                            </h3>
                                        </div>
                                    </div>
                                </Card>
                            </div>

                            <!-- Autres membres en grille -->
                            <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <Card 
                                    v-for="poste in postes" 
                                    :key="poste.id"
                                    class="hover:shadow-lg transition"
                                    :style="poste.ministere?.couleur ? { borderLeftColor: poste.ministere.couleur, borderLeftWidth: '4px' } : {}"
                                >
                                    <Link 
                                        v-if="poste.personne?.slug"
                                        :href="route('gouvernement.personne', poste.personne.slug)"
                                        class="flex items-start gap-4 hover:opacity-90 transition"
                                    >
                                        <img 
                                            v-if="poste.personne?.photo"
                                            :src="poste.personne.photo" 
                                            :alt="poste.personne?.nom_complet"
                                            class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700 flex-shrink-0"
                                        />
                                        <div v-else class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-2xl flex-shrink-0">
                                            👤
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                                {{ poste.personne?.nom_complet }}
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mt-1">
                                                {{ poste.fonction }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                                <span 
                                                    v-if="poste.ministere?.sigle"
                                                    class="px-2 py-0.5 text-xs rounded"
                                                    :style="{ backgroundColor: poste.ministere.couleur + '20', color: poste.ministere.couleur }"
                                                >
                                                    {{ poste.ministere.sigle }}
                                                </span>
                                                <span 
                                                    v-if="poste.personne?.parti_politique"
                                                    class="px-2 py-0.5 text-xs rounded-full"
                                                    :style="{ 
                                                        backgroundColor: getPartiCouleur(poste.personne.parti_politique) + '20', 
                                                        color: getPartiCouleur(poste.personne.parti_politique) 
                                                    }"
                                                >
                                                    {{ poste.personne.parti_politique }}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                    <div v-else class="flex items-start gap-4">
                                        <div class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-2xl flex-shrink-0">
                                            👤
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                                {{ poste.personne?.nom_complet || 'Non renseigné' }}
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mt-1">
                                                {{ poste.fonction }}
                                            </p>
                                        </div>
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vue Liste -->
                <div v-if="selectedVue === 'liste'" class="space-y-6">
                    <Card>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            📋 Tous les membres du gouvernement ({{ tousLesMembres.length }})
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Membre</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Fonction</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Type</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">Parti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="poste in tousLesMembres" 
                                        :key="poste.id"
                                        class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition"
                                    >
                                        <td class="py-3 px-4">
                                            <Link 
                                                v-if="poste.personne?.slug"
                                                :href="route('gouvernement.personne', poste.personne.slug)"
                                                class="flex items-center gap-3 hover:opacity-80 transition"
                                            >
                                                <img 
                                                    v-if="poste.personne?.photo"
                                                    :src="poste.personne.photo" 
                                                    :alt="poste.personne?.nom_complet"
                                                    class="w-10 h-10 rounded-full object-cover"
                                                />
                                                <div v-else class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                    👤
                                                </div>
                                                <span class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                                    {{ poste.personne?.nom_complet }}
                                                </span>
                                            </Link>
                                            <div v-else class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                    👤
                                                </div>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ poste.personne?.nom_complet || 'Non renseigné' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300 text-sm">
                                            {{ poste.fonction }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span 
                                                :class="[typeColors[poste.type], 'px-2 py-1 text-white rounded text-xs']"
                                            >
                                                {{ typeLabels[poste.type] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span 
                                                v-if="poste.personne?.parti_politique"
                                                class="px-2 py-1 rounded text-xs"
                                                :style="{ 
                                                    backgroundColor: getPartiCouleur(poste.personne.parti_politique) + '20', 
                                                    color: getPartiCouleur(poste.personne.parti_politique) 
                                                }"
                                            >
                                                {{ poste.personne.parti_politique }}
                                            </span>
                                            <span v-else class="text-gray-400 text-xs">N/A</span>
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
                        <div v-if="stats?.partis && Object.keys(stats.partis).length > 0" class="space-y-4">
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
                        <div v-else class="text-center py-8 text-gray-500">
                            Aucune donnée de parti disponible.
                        </div>
                    </Card>
                </div>

                <!-- Source des données -->
                <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        🏛️ Source officielle : 
                        <a href="https://www.info.gouv.fr/composition-du-gouvernement" target="_blank" class="text-blue-600 hover:underline">
                            info.gouv.fr/composition-du-gouvernement
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
