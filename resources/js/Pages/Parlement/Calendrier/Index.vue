<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    evenements: Array,
    evenementsParJour: Object,
    stats: Object,
    mois: Number,
    annee: Number,
    dateRef: String,
    filtres: Object,
    typesDisponibles: Array,
    sourcesDisponibles: Array,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Calendrier', current: true, icon: '📅' },
];

// Filtres locaux
const filtreSource = ref(props.filtres?.source || null);
const filtreType = ref(props.filtres?.type || null);

// Noms des mois
const nomsMois = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
];

// Navigation entre mois
const moisPrecedent = () => {
    let m = props.mois - 1;
    let a = props.annee;
    if (m < 1) { m = 12; a--; }
    naviguer({ mois: m, annee: a });
};

const moisSuivant = () => {
    let m = props.mois + 1;
    let a = props.annee;
    if (m > 12) { m = 1; a++; }
    naviguer({ mois: m, annee: a });
};

const naviguer = (params = {}) => {
    router.get(route('parlement.calendrier.index'), {
        mois: params.mois ?? props.mois,
        annee: params.annee ?? props.annee,
        source: filtreSource.value,
        type: filtreType.value,
    }, { preserveState: true });
};

const appliquerFiltres = () => {
    naviguer({});
};

const reinitialiserFiltres = () => {
    filtreSource.value = null;
    filtreType.value = null;
    naviguer({});
};

// Générer les jours du calendrier
const joursCalendrier = computed(() => {
    const premierJour = new Date(props.annee, props.mois - 1, 1);
    const dernierJour = new Date(props.annee, props.mois, 0);
    const joursSemainePremier = (premierJour.getDay() + 6) % 7; // Lundi = 0
    const nbJours = dernierJour.getDate();
    
    const jours = [];
    
    // Jours du mois précédent
    for (let i = joursSemainePremier - 1; i >= 0; i--) {
        const d = new Date(props.annee, props.mois - 1, -i);
        jours.push({
            date: d.toISOString().split('T')[0],
            numero: d.getDate(),
            estMoisCourant: false,
            estAujourdhui: false,
            evenements: [],
        });
    }
    
    // Jours du mois
    const aujourdhui = new Date().toISOString().split('T')[0];
    for (let i = 1; i <= nbJours; i++) {
        const d = new Date(props.annee, props.mois - 1, i);
        const dateStr = d.toISOString().split('T')[0];
        jours.push({
            date: dateStr,
            numero: i,
            estMoisCourant: true,
            estAujourdhui: dateStr === aujourdhui,
            evenements: props.evenementsParJour[dateStr] || [],
        });
    }
    
    // Compléter pour avoir 6 semaines (42 jours)
    const reste = 42 - jours.length;
    for (let i = 1; i <= reste; i++) {
        const d = new Date(props.annee, props.mois, i);
        jours.push({
            date: d.toISOString().split('T')[0],
            numero: i,
            estMoisCourant: false,
            estAujourdhui: false,
            evenements: [],
        });
    }
    
    return jours;
});

// Grouper par semaines
const semainesCalendrier = computed(() => {
    const semaines = [];
    for (let i = 0; i < joursCalendrier.value.length; i += 7) {
        semaines.push(joursCalendrier.value.slice(i, i + 7));
    }
    return semaines;
});

// Formater l'heure depuis une date ISO
const formatHeure = (dateIso) => {
    if (!dateIso) return '';
    const d = new Date(dateIso);
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Calendrier" />
    
    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <Breadcrumb :items="breadcrumbItems" variant="light" class="mb-6" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
                    <div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 tracking-tight flex items-center gap-4">
                            <span class="text-4xl">📅</span>
                            Calendrier
                        </h1>
                        <p class="text-indigo-200 text-lg max-w-xl">
                            Agenda de l'Assemblée Nationale, du Sénat et de l'Élysée
                        </p>
                        <p class="text-indigo-300 text-sm mt-2">
                            {{ nomsMois[mois - 1] }} {{ annee }}
                        </p>
                    </div>
                    
                    <!-- Stats rapides -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats.total }}</p>
                            <p class="text-xs text-indigo-200">Événements</p>
                        </div>
                        <div class="bg-blue-500/20 backdrop-blur-sm rounded-xl px-4 py-3 text-center border border-blue-400/30">
                            <p class="text-3xl font-bold text-white">{{ stats.an }}</p>
                            <p class="text-xs text-blue-200">Assemblée</p>
                        </div>
                        <div class="bg-red-500/20 backdrop-blur-sm rounded-xl px-4 py-3 text-center border border-red-400/30">
                            <p class="text-3xl font-bold text-white">{{ stats.senat }}</p>
                            <p class="text-xs text-red-200">Sénat</p>
                        </div>
                        <div class="bg-slate-500/20 backdrop-blur-sm rounded-xl px-4 py-3 text-center border border-slate-400/30">
                            <p class="text-3xl font-bold text-white">{{ stats.elysee || 0 }}</p>
                            <p class="text-xs text-slate-200">Élysée</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Filtres -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Filtre Source -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Source:</span>
                            <div class="flex gap-2">
                                <button
                                    @click="filtreSource = null; appliquerFiltres()"
                                    class="px-3 py-1.5 text-sm rounded-lg transition"
                                    :class="filtreSource === null 
                                        ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 font-semibold' 
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                >
                                    Tous
                                </button>
                                <button
                                    @click="filtreSource = 'an'; appliquerFiltres()"
                                    class="px-3 py-1.5 text-sm rounded-lg transition flex items-center gap-1"
                                    :class="filtreSource === 'an' 
                                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 font-semibold' 
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                >
                                    🔵 AN
                                </button>
                                <button
                                    @click="filtreSource = 'senat'; appliquerFiltres()"
                                    class="px-3 py-1.5 text-sm rounded-lg transition flex items-center gap-1"
                                    :class="filtreSource === 'senat' 
                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 font-semibold' 
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                >
                                    🔴 Sénat
                                </button>
                                <button
                                    @click="filtreSource = 'elysee'; appliquerFiltres()"
                                    class="px-3 py-1.5 text-sm rounded-lg transition flex items-center gap-1"
                                    :class="filtreSource === 'elysee' 
                                        ? 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-200 font-semibold' 
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                >
                                    🟡 Élysée
                                </button>
                            </div>
                        </div>
                        
                        <!-- Séparateur -->
                        <div class="hidden sm:block w-px h-6 bg-slate-300 dark:bg-slate-600"></div>
                        
                        <!-- Filtre Type -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Type:</span>
                            <select
                                v-model="filtreType"
                                @change="appliquerFiltres()"
                                class="text-sm rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 focus:ring-indigo-500"
                            >
                                <option :value="null">Tous les types</option>
                                <option value="seance">🏛️ Séance publique</option>
                                <option value="commission">👥 Commission</option>
                                <option value="reunion">📋 Réunion</option>
                                <option value="audition">🎤 Audition</option>
                            </select>
                        </div>
                        
                        <!-- Reset -->
                        <button
                            v-if="filtreSource || filtreType"
                            @click="reinitialiserFiltres()"
                            class="ml-auto text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                        >
                            ✕ Réinitialiser
                        </button>
                    </div>
                </div>
                
                <!-- Navigation mois -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <button
                            @click="moisPrecedent"
                            class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                            {{ nomsMois[mois - 1] }} {{ annee }}
                        </h2>
                        
                        <button
                            @click="moisSuivant"
                            class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Calendrier -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <!-- En-têtes jours -->
                    <div class="grid grid-cols-7 bg-slate-50 dark:bg-slate-700/50">
                        <div v-for="jour in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']" :key="jour"
                            class="py-3 text-center text-sm font-semibold text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-600">
                            {{ jour }}
                        </div>
                    </div>
                    
                    <!-- Grille des jours -->
                    <div class="grid grid-cols-7">
                        <div
                            v-for="(jour, index) in joursCalendrier"
                            :key="jour.date"
                            class="min-h-[120px] p-2 border-b border-r border-slate-200 dark:border-slate-700 transition-colors"
                            :class="{
                                'bg-slate-50/50 dark:bg-slate-900/30': !jour.estMoisCourant,
                                'bg-indigo-50 dark:bg-indigo-900/20': jour.estAujourdhui,
                                'hover:bg-slate-50 dark:hover:bg-slate-700/30': jour.estMoisCourant,
                            }"
                        >
                            <!-- Numéro du jour -->
                            <div class="flex items-center justify-between mb-2">
                                <span
                                    class="text-sm font-medium"
                                    :class="{
                                        'text-slate-400 dark:text-slate-500': !jour.estMoisCourant,
                                        'text-indigo-600 dark:text-indigo-400 font-bold': jour.estAujourdhui,
                                        'text-slate-700 dark:text-slate-300': jour.estMoisCourant && !jour.estAujourdhui,
                                    }"
                                >
                                    {{ jour.numero }}
                                </span>
                                <span v-if="jour.evenements.length > 0" class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ jour.evenements.length }}
                                </span>
                            </div>
                            
                            <!-- Événements du jour -->
                            <div class="space-y-1">
                                <Link
                                    v-for="evt in jour.evenements.slice(0, 3)"
                                    :key="evt.uid"
                                    :href="route('parlement.calendrier.show', evt.uid)"
                                    class="block text-xs p-1.5 rounded truncate hover:opacity-80 transition text-white"
                                    :style="{ backgroundColor: evt.color }"
                                    :title="evt.title"
                                >
                                    <span class="font-medium">{{ formatHeure(evt.start) }}</span>
                                    {{ evt.icon }} {{ evt.instance || evt.typeLabel }}
                                </Link>
                                
                                <div v-if="jour.evenements.length > 3" class="text-xs text-slate-500 dark:text-slate-400 pl-1">
                                    +{{ jour.evenements.length - 3 }} autres
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Légende -->
                <div class="mt-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Légende</h3>
                    <div class="flex flex-wrap gap-6">
                        <!-- Sources -->
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Sources:</span>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded" style="background-color: #0055A4"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">Assemblée nationale</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded" style="background-color: #DC143C"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">Sénat</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded" style="background-color: #FFD700"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">Élysée</span>
                            </div>
                        </div>
                        
                        <!-- Types -->
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Types:</span>
                            <span class="text-sm text-slate-600 dark:text-slate-400">🏛️ Séance</span>
                            <span class="text-sm text-slate-600 dark:text-slate-400">👥 Commission</span>
                            <span class="text-sm text-slate-600 dark:text-slate-400">📋 Réunion</span>
                            <span class="text-sm text-slate-600 dark:text-slate-400">🎤 Audition</span>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des événements du mois -->
                <div class="mt-8">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                        📋 Tous les événements de {{ nomsMois[mois - 1] }}
                    </h2>
                    
                    <div class="grid gap-4">
                        <Link
                            v-for="evt in evenements"
                            :key="evt.uid"
                            :href="route('parlement.calendrier.show', evt.uid)"
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-indigo-500 dark:hover:border-indigo-600 transition-all hover:shadow-md"
                        >
                            <div class="flex items-start gap-4">
                                <!-- Date/Heure -->
                                <div class="flex-shrink-0 text-center rounded-lg p-3 min-w-[80px]"
                                    :style="{ backgroundColor: evt.color + '15' }">
                                    <p class="text-2xl font-bold" :style="{ color: evt.color }">
                                        {{ new Date(evt.start).getDate() }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">
                                        {{ nomsMois[new Date(evt.start).getMonth()].slice(0, 3) }}
                                    </p>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mt-1">
                                        {{ formatHeure(evt.start) }}
                                    </p>
                                </div>
                                
                                <!-- Contenu -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <span class="text-lg">{{ evt.icon }}</span>
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded text-white"
                                            :style="{ backgroundColor: evt.color }"
                                        >
                                            {{ evt.sourceLabel }}
                                        </span>
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                            {{ evt.typeLabel }}
                                        </span>
                                        <span v-if="evt.instance" class="text-xs text-slate-500 dark:text-slate-400">
                                            • {{ evt.instance }}
                                        </span>
                                    </div>
                                    
                                    <h3 class="font-semibold text-slate-900 dark:text-white line-clamp-2">
                                        {{ evt.title }}
                                    </h3>
                                    
                                    <div class="flex flex-wrap gap-3 mt-2 text-sm text-slate-500 dark:text-slate-400">
                                        <span v-if="evt.lieu" class="flex items-center gap-1">
                                            📍 {{ evt.lieu }}
                                        </span>
                                        <span v-if="evt.urlVideo" class="flex items-center gap-1 text-blue-500">
                                            🎥 Vidéo disponible
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                        
                        <div v-if="evenements.length === 0" class="text-center py-12 text-slate-500 dark:text-slate-400">
                            <p class="text-5xl mb-4">📅</p>
                            <p class="text-lg">Aucun événement prévu ce mois-ci</p>
                            <p v-if="filtreSource || filtreType" class="text-sm mt-2">
                                Essayez de modifier les filtres
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
