<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    reunions: Array,
    reunionsParJour: Object,
    stats: Object,
    mois: Number,
    annee: Number,
    dateRef: String,
    filtres: Object,
    typesDisponibles: Array,
    organesDisponibles: Array,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Parlement' },
    { label: 'Calendrier Législatif', icon: '📅' },
];

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
    router.get(route('parlement.calendrier.index'), { mois: m, annee: a }, { preserveState: true });
};

const moisSuivant = () => {
    let m = props.mois + 1;
    let a = props.annee;
    if (m > 12) { m = 1; a++; }
    router.get(route('parlement.calendrier.index'), { mois: m, annee: a }, { preserveState: true });
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
            reunions: [],
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
            reunions: props.reunionsParJour[dateStr] || [],
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
            reunions: [],
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

// Couleur selon le type
const getCouleurType = (type) => {
    const couleurs = {
        'Commission': 'bg-blue-500',
        'Séance publique': 'bg-purple-500',
        'Délégation': 'bg-green-500',
        'Mission': 'bg-orange-500',
        'Groupe': 'bg-pink-500',
    };
    return couleurs[type] || 'bg-gray-500';
};
</script>

<template>
    <Head title="Calendrier Législatif" />
    
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-950/20">
            <div class="py-8 px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbItems" class="mb-6" />
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-xl p-8 text-white mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <h1 class="text-4xl font-bold mb-2 flex items-center gap-3">
                                📅 Calendrier Législatif
                            </h1>
                            <p class="text-indigo-100 text-lg">
                                Agenda des réunions de l'Assemblée Nationale
                            </p>
                        </div>
                        
                        <!-- Stats rapides -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-white/10 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-3xl font-bold">{{ stats.total }}</p>
                                <p class="text-xs text-indigo-200">Réunions</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-3xl font-bold text-green-300">{{ stats.confirmees }}</p>
                                <p class="text-xs text-indigo-200">Confirmées</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-3xl font-bold">{{ stats.commissions }}</p>
                                <p class="text-xs text-indigo-200">Commissions</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-xl px-4 py-3 text-center">
                                <p class="text-3xl font-bold">{{ stats.seances }}</p>
                                <p class="text-xs text-indigo-200">Séances</p>
                            </div>
                        </div>
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
                                <span v-if="jour.reunions.length > 0" class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ jour.reunions.length }}
                                </span>
                            </div>
                            
                            <!-- Réunions du jour -->
                            <div class="space-y-1">
                                <Link
                                    v-for="reunion in jour.reunions.slice(0, 3)"
                                    :key="reunion.uid"
                                    :href="route('parlement.calendrier.show', reunion.uid)"
                                    class="block text-xs p-1.5 rounded truncate hover:opacity-80 transition text-white"
                                    :class="getCouleurType(reunion.type_reunion)"
                                    :title="reunion.titre"
                                >
                                    <span class="font-medium">{{ reunion.heure }}</span>
                                    {{ reunion.organe?.nom || reunion.type_reunion }}
                                </Link>
                                
                                <div v-if="jour.reunions.length > 3" class="text-xs text-slate-500 dark:text-slate-400 pl-1">
                                    +{{ jour.reunions.length - 3 }} autres
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Légende -->
                <div class="mt-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Légende</h3>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-blue-500"></div>
                            <span class="text-sm text-slate-600 dark:text-slate-400">Commission</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-purple-500"></div>
                            <span class="text-sm text-slate-600 dark:text-slate-400">Séance publique</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-green-500"></div>
                            <span class="text-sm text-slate-600 dark:text-slate-400">Délégation</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-orange-500"></div>
                            <span class="text-sm text-slate-600 dark:text-slate-400">Mission</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-pink-500"></div>
                            <span class="text-sm text-slate-600 dark:text-slate-400">Groupe</span>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des réunions du mois -->
                <div class="mt-8">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                        📋 Toutes les réunions de {{ nomsMois[mois - 1] }}
                    </h2>
                    
                    <div class="grid gap-4">
                        <Link
                            v-for="reunion in reunions"
                            :key="reunion.uid"
                            :href="route('parlement.calendrier.show', reunion.uid)"
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-indigo-500 dark:hover:border-indigo-600 transition-all hover:shadow-md"
                        >
                            <div class="flex items-start gap-4">
                                <!-- Date/Heure -->
                                <div class="flex-shrink-0 text-center bg-slate-100 dark:bg-slate-700 rounded-lg p-3 min-w-[80px]">
                                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ new Date(reunion.date_debut).getDate() }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">
                                        {{ nomsMois[new Date(reunion.date_debut).getMonth()].slice(0, 3) }}
                                    </p>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mt-1">
                                        {{ reunion.heure }}
                                    </p>
                                </div>
                                
                                <!-- Contenu -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-lg">{{ reunion.emoji }}</span>
                                        <span
                                            v-if="reunion.organe"
                                            class="px-2 py-1 text-xs font-semibold rounded text-white"
                                            :style="{ backgroundColor: reunion.organe.couleur }"
                                        >
                                            {{ reunion.organe.nom }}
                                        </span>
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded"
                                            :class="{
                                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': reunion.etat === 'Confirmé',
                                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': reunion.etat === 'Annulé',
                                                'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': !['Confirmé', 'Annulé'].includes(reunion.etat),
                                            }"
                                        >
                                            {{ reunion.etat || 'Prévu' }}
                                        </span>
                                    </div>
                                    
                                    <h3 class="font-semibold text-slate-900 dark:text-white line-clamp-2">
                                        {{ reunion.titre }}
                                    </h3>
                                    
                                    <div class="flex flex-wrap gap-3 mt-2 text-sm text-slate-500 dark:text-slate-400">
                                        <span v-if="reunion.lieu" class="flex items-center gap-1">
                                            📍 {{ reunion.lieu }}
                                        </span>
                                        <span v-if="reunion.nb_points_odj" class="flex items-center gap-1">
                                            📋 {{ reunion.nb_points_odj }} point(s) à l'ordre du jour
                                        </span>
                                        <span v-if="reunion.visio" class="flex items-center gap-1">
                                            💻 Visioconférence
                                        </span>
                                        <span v-if="reunion.presse" class="flex items-center gap-1">
                                            📰 Ouverte à la presse
                                        </span>
                                        <span v-if="reunion.video" class="flex items-center gap-1">
                                            🎥 Captation vidéo
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                        
                        <div v-if="reunions.length === 0" class="text-center py-12 text-slate-500 dark:text-slate-400">
                            <p class="text-5xl mb-4">📅</p>
                            <p class="text-lg">Aucune réunion prévue ce mois-ci</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>

