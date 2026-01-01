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
    autres_mandats: Array,
});

const breadcrumbs = computed(() => [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'État', icon: '🇫🇷' },
    { label: 'Gouvernement', href: route('gouvernement.index'), icon: '🏛️' },
    { label: props.personne?.nom_complet || 'Ministre', current: true, icon: '👔' },
]);

// Couleurs par type de fonction
const getTypeCouleur = (type) => {
    const couleurs = {
        'premier_ministre': 'bg-blue-600 text-white',
        'ministre_etat': 'bg-purple-600 text-white',
        'ministre': 'bg-indigo-600 text-white',
        'ministre_delegue': 'bg-teal-600 text-white',
        'secretaire_etat': 'bg-gray-600 text-white',
    };
    return couleurs[type] || 'bg-gray-500 text-white';
};

// Couleur par parti
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
    <Head :title="personne?.nom_complet || 'Ministre'" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-start gap-8">
                    <!-- Photo -->
                    <div class="flex-shrink-0">
                        <div class="relative">
                            <img 
                                v-if="personne?.photo"
                                :src="personne.photo" 
                                :alt="personne.nom_complet"
                                class="w-40 h-40 lg:w-48 lg:h-48 rounded-full object-cover border-4 border-white/30 shadow-2xl"
                            />
                            <div v-else class="w-40 h-40 lg:w-48 lg:h-48 rounded-full bg-white/20 flex items-center justify-center text-6xl">
                                {{ personne?.civilite === 'Mme' ? '👩' : '👨' }}
                            </div>
                            <!-- Badge actif -->
                            <div 
                                v-if="stats?.est_actif"
                                class="absolute -bottom-2 -right-2 px-3 py-1 bg-green-500 text-white text-sm font-bold rounded-full flex items-center gap-1"
                            >
                                <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                En fonction
                            </div>
                        </div>
                    </div>
                    
                    <!-- Infos -->
                    <div class="flex-1">
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight">
                            {{ personne?.civilite }} {{ personne?.nom_complet }}
                        </h1>
                        
                        <!-- Poste actuel -->
                        <div v-if="stats?.poste_actuel" class="mb-4">
                            <span class="px-4 py-2 bg-blue-600 text-white text-lg font-semibold rounded-lg inline-block">
                                {{ stats.poste_actuel }}
                            </span>
                        </div>
                        
                        <!-- Parti politique -->
                        <div v-if="personne?.parti_politique" class="mb-4">
                            <span 
                                class="px-3 py-1 rounded-full text-sm font-medium"
                                :style="{ backgroundColor: getPartiCouleur(personne.parti_politique) + '30', color: 'white' }"
                            >
                                {{ personne.parti_politique }}
                            </span>
                        </div>
                        
                        <!-- Infos complémentaires -->
                        <div class="flex flex-wrap gap-4 text-blue-200 text-sm">
                            <span v-if="personne?.date_naissance">
                                🎂 Né(e) le {{ personne.date_naissance }}
                                <span v-if="personne?.age">({{ personne.age }} ans)</span>
                            </span>
                            <span v-if="personne?.lieu_naissance">
                                📍 {{ personne.lieu_naissance }}
                            </span>
                            <span v-if="personne?.profession">
                                💼 {{ personne.profession }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Stats clés -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats?.nb_postes || 0 }}</div>
                        <div class="text-blue-200 text-sm">Poste(s) ministériel(s)</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats?.nb_gouvernements || 0 }}</div>
                        <div class="text-blue-200 text-sm">Gouvernement(s)</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="text-3xl font-bold text-white">{{ stats?.duree_totale || '—' }}</div>
                        <div class="text-blue-200 text-sm">Durée totale</div>
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
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-3">
                                📜 Historique des postes ministériels
                            </h2>
                            
                            <div v-if="historique && historique.length > 0" class="space-y-4">
                                <div 
                                    v-for="(poste, index) in historique" 
                                    :key="poste.id"
                                    class="relative pl-8 pb-6"
                                    :class="{ 'border-l-2 border-gray-200 dark:border-gray-700': index < historique.length - 1 }"
                                >
                                    <!-- Point sur la timeline -->
                                    <div 
                                        class="absolute left-0 top-0 w-4 h-4 rounded-full -translate-x-1/2"
                                        :class="poste.actif ? 'bg-green-500 ring-4 ring-green-200 dark:ring-green-900' : 'bg-gray-400'"
                                    ></div>
                                    
                                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition">
                                        <!-- Badge type -->
                                        <span :class="[getTypeCouleur(poste.type_fonction), 'px-2 py-0.5 text-xs rounded-full']">
                                            {{ poste.type_fonction_libelle }}
                                        </span>
                                        
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-2">
                                            {{ poste.fonction }}
                                        </h3>
                                        
                                        <p v-if="poste.ministere" class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            🏢 {{ poste.ministere }}
                                        </p>
                                        
                                        <div class="flex flex-wrap items-center gap-4 mt-3 text-sm">
                                            <Link 
                                                :href="route('gouvernement.index', { gouvernement: poste.gouvernement_id })"
                                                class="text-blue-600 dark:text-blue-400 hover:underline"
                                            >
                                                🏛️ {{ poste.gouvernement }}
                                            </Link>
                                            <span class="text-gray-500 dark:text-gray-400">
                                                👔 {{ poste.premier_ministre }}
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span>
                                                📅 {{ poste.date_debut }}
                                                <template v-if="poste.date_fin"> - {{ poste.date_fin }}</template>
                                                <template v-else> - en cours</template>
                                            </span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                                ⏱️ {{ poste.duree }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-2">📋</div>
                                <p>Aucun historique disponible</p>
                            </div>
                        </Card>
                        
                        <!-- Biographie Wikipedia -->
                        <Card v-if="personne?.wikipedia_extract">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-3">
                                📖 Biographie
                            </h2>
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                {{ personne.wikipedia_extract }}
                            </p>
                            <a 
                                v-if="personne?.wikipedia_url"
                                :href="personne.wikipedia_url" 
                                target="_blank"
                                class="inline-flex items-center gap-2 mt-4 text-blue-600 dark:text-blue-400 hover:underline"
                            >
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5a/Wikipedia%27s_W.svg" alt="Wikipedia" class="w-4 h-4" />
                                Lire la suite sur Wikipedia →
                            </a>
                        </Card>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="space-y-6">
                        
                        <!-- Autres mandats -->
                        <Card v-if="autres_mandats && autres_mandats.length > 0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                🏛️ Autres mandats
                            </h3>
                            <div class="space-y-3">
                                <Link 
                                    v-for="mandat in autres_mandats" 
                                    :key="mandat.type + mandat.uid"
                                    :href="mandat.type === 'depute' ? route('deputes.show', mandat.uid) : route('senateurs.show', mandat.uid)"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <span class="text-2xl">
                                        {{ mandat.type === 'depute' ? '🏛️' : '🏛️' }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ mandat.type === 'depute' ? 'Député(e)' : 'Sénateur/Sénatrice' }}
                                    </span>
                                </Link>
                            </div>
                        </Card>
                        
                        <!-- Liens -->
                        <Card>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                🔗 Liens
                            </h3>
                            <div class="space-y-3">
                                <a 
                                    v-if="personne?.wikipedia_url"
                                    :href="personne.wikipedia_url" 
                                    target="_blank"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5a/Wikipedia%27s_W.svg" alt="Wikipedia" class="w-5 h-5" />
                                    <span class="text-gray-700 dark:text-gray-300">Wikipedia</span>
                                </a>
                                <a 
                                    v-if="personne?.twitter_url"
                                    :href="personne.twitter_url" 
                                    target="_blank"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">X (Twitter)</span>
                                </a>
                                <a 
                                    v-if="personne?.site_web"
                                    :href="personne.site_web" 
                                    target="_blank"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <span class="text-xl">🌐</span>
                                    <span class="text-gray-700 dark:text-gray-300">Site web</span>
                                </a>
                                
                                <div v-if="!personne?.wikipedia_url && !personne?.twitter_url && !personne?.site_web" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                    <p class="text-sm">Aucun lien disponible</p>
                                </div>
                            </div>
                        </Card>
                        
                        <!-- Résumé rapide -->
                        <Card>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                📊 En bref
                            </h3>
                            <dl class="space-y-3">
                                <div v-if="personne?.profession" class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Profession</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100 text-right">{{ personne.profession }}</dd>
                                </div>
                                <div v-if="personne?.parti_politique" class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Parti</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100 text-right">{{ personne.parti_politique }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Postes occupés</dt>
                                    <dd class="font-bold text-blue-600 dark:text-blue-400 text-right">{{ stats?.nb_postes || 0 }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Gouvernements</dt>
                                    <dd class="font-bold text-indigo-600 dark:text-indigo-400 text-right">{{ stats?.nb_gouvernements || 0 }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Durée totale</dt>
                                    <dd class="font-bold text-emerald-600 dark:text-emerald-400 text-right">{{ stats?.duree_totale || '—' }}</dd>
                                </div>
                            </dl>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
