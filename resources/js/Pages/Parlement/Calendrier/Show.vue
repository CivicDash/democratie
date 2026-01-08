<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    evenement: Object,
    similaires: Array,
});

// Alias pour compatibilité
const evt = props.evenement;

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Calendrier', href: route('parlement.calendrier.index'), icon: '📅' },
    { label: evt.instance || evt.typeLabel || 'Événement', icon: evt.icon },
];

// Formater la date
const formatDate = (dateIso) => {
    if (!dateIso) return '';
    const d = new Date(dateIso);
    return d.toLocaleDateString('fr-FR', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Formater l'heure
const formatHeure = (dateIso) => {
    if (!dateIso) return '';
    const d = new Date(dateIso);
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

// Couleur d'état
const getEtatClass = (statut) => {
    switch (statut) {
        case 'confirme': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'annule': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        case 'reporte': return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
        default: return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    }
};

const getStatutLabel = (statut) => {
    switch (statut) {
        case 'confirme': return 'Confirmé';
        case 'annule': return 'Annulé';
        case 'reporte': return 'Reporté';
        default: return 'Prévu';
    }
};
</script>

<template>
    <Head :title="evt.title" />
    
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-950/20">
            <div class="py-8 px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbItems" class="mb-6" />
                
                <!-- Header avec couleur source -->
                <div 
                    class="rounded-2xl shadow-xl p-8 text-white mb-8"
                    :style="{ background: `linear-gradient(135deg, ${evt.color}, ${evt.color}dd)` }"
                >
                    <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                        <!-- Icône -->
                        <div class="flex-shrink-0">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-4xl">
                                {{ evt.icon }}
                            </div>
                        </div>
                        
                        <!-- Infos -->
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                <span :class="getEtatClass(evt.statut)" class="px-3 py-1 rounded-full text-sm font-semibold">
                                    {{ getStatutLabel(evt.statut) }}
                                </span>
                                <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-semibold">
                                    {{ evt.sourceLabel }}
                                </span>
                                <span class="bg-white/20 px-3 py-1 rounded-full text-sm">
                                    {{ evt.typeLabel }}
                                </span>
                            </div>
                            
                            <h1 class="text-3xl lg:text-4xl font-bold mb-3">
                                {{ evt.title }}
                            </h1>
                            
                            <div class="flex flex-wrap gap-4 text-white/90">
                                <span class="flex items-center gap-2">
                                    📅 {{ formatDate(evt.start) }}
                                </span>
                                <span v-if="evt.lieu" class="flex items-center gap-2">
                                    📍 {{ evt.lieu }}
                                </span>
                                <span v-if="evt.instance" class="flex items-center gap-2">
                                    🏛️ {{ evt.instance }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-3 mt-6">
                        <a 
                            v-if="evt.urlVideo" 
                            :href="evt.urlVideo"
                            target="_blank"
                            class="bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg text-sm flex items-center gap-2 transition"
                        >
                            🎥 Voir la vidéo
                        </a>
                        <a 
                            v-if="evt.urlSource" 
                            :href="evt.urlSource"
                            target="_blank"
                            class="bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg text-sm flex items-center gap-2 transition"
                        >
                            🔗 Source officielle
                        </a>
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Contenu principal -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Description -->
                        <div v-if="evt.description" 
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                📋 Description
                            </h2>
                            
                            <div class="prose dark:prose-invert max-w-none">
                                <p class="text-slate-700 dark:text-slate-300 whitespace-pre-line">
                                    {{ evt.description }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Métadonnées -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                ℹ️ Informations
                            </h2>
                            
                            <dl class="grid sm:grid-cols-2 gap-4">
                                <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Source</dt>
                                    <dd class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span 
                                            class="w-3 h-3 rounded-full" 
                                            :style="{ backgroundColor: evt.color }"
                                        ></span>
                                        {{ evt.sourceLabel }}
                                    </dd>
                                </div>
                                <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Type</dt>
                                    <dd class="text-slate-900 dark:text-white">{{ evt.icon }} {{ evt.typeLabel }}</dd>
                                </div>
                                <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Début</dt>
                                    <dd class="text-slate-900 dark:text-white">{{ formatDate(evt.start) }}</dd>
                                </div>
                                <div v-if="evt.end" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Fin</dt>
                                    <dd class="text-slate-900 dark:text-white">{{ formatDate(evt.end) }}</dd>
                                </div>
                                <div v-if="evt.lieu" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg sm:col-span-2">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Lieu</dt>
                                    <dd class="text-slate-900 dark:text-white">📍 {{ evt.lieu }}</dd>
                                </div>
                                <div v-if="evt.instance" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg sm:col-span-2">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Instance / Organe</dt>
                                    <dd class="text-slate-900 dark:text-white">{{ evt.instance }}</dd>
                                </div>
                                <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Identifiant</dt>
                                    <dd class="font-mono text-xs text-slate-700 dark:text-slate-300 break-all">{{ evt.uid }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Actions -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h3 class="font-bold text-slate-900 dark:text-white mb-4">Actions</h3>
                            
                            <div class="space-y-3">
                                <!-- Lien vers les comptes-rendus internes (débats Sénat) -->
                                <Link
                                    v-if="evt.urlDossier && evt.urlDossier.startsWith('/debats')"
                                    :href="evt.urlDossier"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition font-semibold"
                                >
                                    📜 Voir le compte-rendu
                                </Link>
                                
                                <Link
                                    :href="route('parlement.calendrier.index')"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                                >
                                    📅 Retour au calendrier
                                </Link>
                                
                                <a
                                    v-if="evt.urlSource"
                                    :href="evt.urlSource"
                                    target="_blank"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                                >
                                    🌐 Compte-rendu officiel
                                </a>
                                
                                <a
                                    v-if="evt.urlVideo"
                                    :href="evt.urlVideo"
                                    target="_blank"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition"
                                >
                                    🎥 Voir la vidéo
                                </a>
                            </div>
                        </div>
                        
                        <!-- Événements similaires -->
                        <div v-if="similaires?.length" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                                Événements similaires
                            </h3>
                            
                            <div class="space-y-3">
                                <Link
                                    v-for="s in similaires"
                                    :key="s.uid"
                                    :href="route('parlement.calendrier.show', s.uid)"
                                    class="block p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600/50 transition"
                                >
                                    <div class="flex items-center gap-2 mb-1">
                                        <span 
                                            class="w-2 h-2 rounded-full" 
                                            :style="{ backgroundColor: s.color }"
                                        ></span>
                                        <p class="font-semibold text-slate-900 dark:text-white text-sm">
                                            {{ formatHeure(s.start) }}
                                        </p>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                        {{ s.title }}
                                    </p>
                                </Link>
                            </div>
                        </div>
                        
                        <!-- Info source -->
                        <div class="bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 rounded-xl p-6">
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                <span class="font-semibold">Source des données:</span><br/>
                                {{ evt.source === 'an' ? 'Assemblée nationale - Agenda.json' : 'Sénat - Flux iCal' }}
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
