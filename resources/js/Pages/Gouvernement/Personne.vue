<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    personne: Object,
    historique: Array,
    stats: Object,
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'État', icon: '🇫🇷' },
    { label: 'Gouvernement', href: route('gouvernement.index'), icon: '🏛️' },
    { label: props.personne.nom_complet, current: true, icon: '👤' },
];

// Couleurs par type de fonction
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
    };
    return couleurs[parti] || '#6b7280';
};
</script>

<template>
    <Head :title="personne.nom_complet" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <!-- Photo -->
                    <div class="flex-shrink-0">
                        <img 
                            v-if="personne.photo"
                            :src="personne.photo" 
                            :alt="personne.nom_complet"
                            class="w-40 h-40 md:w-48 md:h-48 rounded-2xl object-cover border-4 border-white/20 shadow-xl"
                        />
                        <div v-else class="w-40 h-40 md:w-48 md:h-48 rounded-2xl bg-white/10 flex items-center justify-center text-6xl border-4 border-white/20">
                            👤
                        </div>
                    </div>
                    
                    <!-- Infos -->
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span v-if="stats.est_actif" class="px-3 py-1 bg-emerald-500 text-white text-xs font-bold rounded-full">
                                EN FONCTION
                            </span>
                            <span v-else class="px-3 py-1 bg-gray-500 text-white text-xs font-bold rounded-full">
                                ANCIEN MEMBRE
                            </span>
                            <span 
                                v-if="personne.parti_politique"
                                class="px-3 py-1 rounded-full text-xs font-semibold"
                                :style="{ 
                                    backgroundColor: getPartiCouleur(personne.parti_politique) + '40', 
                                    color: 'white' 
                                }"
                            >
                                {{ personne.parti_politique }}
                            </span>
                        </div>
                        
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4 tracking-tight">
                            {{ personne.nom_complet }}
                        </h1>
                        
                        <p v-if="stats.poste_actuel" class="text-xl text-blue-200 mb-4">
                            {{ stats.poste_actuel }}
                        </p>
                        
                        <div class="flex flex-wrap gap-4 text-blue-200 text-sm">
                            <span v-if="personne.age" class="flex items-center gap-1">
                                🎂 {{ personne.age }} ans
                            </span>
                            <span v-if="personne.profession" class="flex items-center gap-1">
                                💼 {{ personne.profession }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stats clés -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.nb_postes }}</div>
                        <div class="text-blue-200 text-sm">Postes ministériels</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats.nb_gouvernements }}</div>
                        <div class="text-blue-200 text-sm">Gouvernement(s)</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 md:col-span-2">
                        <div class="text-3xl font-bold text-white">{{ stats.duree_totale }}</div>
                        <div class="text-blue-200 text-sm">Durée totale au gouvernement</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Colonne principale -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Historique des postes -->
                        <Card>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                                📋 Parcours ministériel
                            </h2>
                            
                            <div v-if="historique.length > 0" class="space-y-4">
                                <div 
                                    v-for="(poste, index) in historique" 
                                    :key="poste.id"
                                    class="relative pl-8 pb-6 border-l-2"
                                    :class="poste.actif ? 'border-emerald-500' : 'border-gray-200 dark:border-gray-700'"
                                >
                                    <!-- Point sur la timeline -->
                                    <div 
                                        class="absolute -left-2 top-0 w-4 h-4 rounded-full border-2"
                                        :class="poste.actif 
                                            ? 'bg-emerald-500 border-emerald-500' 
                                            : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600'"
                                    >
                                        <div v-if="poste.actif" class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-50"></div>
                                    </div>
                                    
                                    <!-- Contenu -->
                                    <div class="ml-4">
                                        <div class="flex items-start justify-between gap-4 flex-wrap">
                                            <div>
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span 
                                                        :class="[typeColors[poste.type_fonction], 'px-2 py-0.5 text-white text-xs rounded']"
                                                    >
                                                        {{ poste.type_fonction_libelle }}
                                                    </span>
                                                    <span v-if="poste.actif" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                                        • Actuel
                                                    </span>
                                                </div>
                                                <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg">
                                                    {{ poste.fonction }}
                                                </h3>
                                                <p v-if="poste.ministere" class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ poste.ministere }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ poste.date_debut }}
                                                    <span v-if="poste.date_fin"> → {{ poste.date_fin }}</span>
                                                    <span v-else> → présent</span>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ poste.duree }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Gouvernement -->
                                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <Link 
                                                :href="route('gouvernement.index', { gouvernement: poste.gouvernement_id })"
                                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-2"
                                            >
                                                🏛️ {{ poste.gouvernement }}
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    • PM : {{ poste.premier_ministre }}
                                                </span>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                Aucun historique disponible.
                            </div>
                        </Card>
                        
                        <!-- Biographie Wikipedia -->
                        <Card v-if="personne.wikipedia_extract">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                📖 Biographie
                            </h2>
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                                {{ personne.wikipedia_extract }}
                            </p>
                            <a 
                                v-if="personne.wikipedia_url"
                                :href="personne.wikipedia_url" 
                                target="_blank"
                                class="inline-flex items-center gap-2 mt-4 text-blue-600 dark:text-blue-400 hover:underline text-sm"
                            >
                                📚 Lire sur Wikipedia →
                            </a>
                        </Card>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Informations personnelles -->
                        <Card>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">👤 Informations</h3>
                            <dl class="space-y-3 text-sm">
                                <div v-if="personne.date_naissance">
                                    <dt class="text-gray-500 dark:text-gray-400">Date de naissance</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ personne.date_naissance }}</dd>
                                </div>
                                <div v-if="personne.profession">
                                    <dt class="text-gray-500 dark:text-gray-400">Profession</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ personne.profession }}</dd>
                                </div>
                                <div v-if="personne.parti_politique">
                                    <dt class="text-gray-500 dark:text-gray-400">Parti politique</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">
                                        <span 
                                            class="px-2 py-0.5 rounded text-xs"
                                            :style="{ 
                                                backgroundColor: getPartiCouleur(personne.parti_politique) + '20', 
                                                color: getPartiCouleur(personne.parti_politique) 
                                            }"
                                        >
                                            {{ personne.parti_politique }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </Card>
                        
                        <!-- Résumé carrière -->
                        <Card class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border-indigo-200 dark:border-indigo-700">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">📊 Résumé carrière</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Postes</span>
                                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.nb_postes }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Gouvernements</span>
                                    <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.nb_gouvernements }}</span>
                                </div>
                                <div class="pt-4 border-t border-indigo-200 dark:border-indigo-700">
                                    <span class="text-gray-600 dark:text-gray-400 text-sm">Durée totale</span>
                                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ stats.duree_totale }}
                                    </div>
                                </div>
                            </div>
                        </Card>
                        
                        <!-- Liens externes -->
                        <Card v-if="personne.wikipedia_url">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">🔗 Liens</h3>
                            <div class="space-y-2">
                                <a 
                                    :href="personne.wikipedia_url" 
                                    target="_blank"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <span class="text-xl">📚</span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Wikipedia</span>
                                </a>
                            </div>
                        </Card>
                        
                        <!-- Retour -->
                        <Link 
                            :href="route('gouvernement.index')"
                            class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium"
                        >
                            ← Retour au gouvernement
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
