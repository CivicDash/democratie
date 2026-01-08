<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    debat: Object,
    sectionsLegislatives: Array,
    sectionsDiverses: Array,
    topIntervenants: Array,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Sénat', href: route('representants.senateurs.index'), icon: '🏛️' },
    { label: 'Débats', href: route('debats.senat.index'), icon: '💬' },
    { label: props.debat.date_formatee, current: true, icon: '📅' },
];

const activeTab = ref('legislatif');

const formatNumber = (n) => new Intl.NumberFormat('fr-FR').format(n);

// Icône par type de section
const getTypeIcon = (type) => {
    const icons = {
        'debut_pdl': '📋',
        'fin_section': '✅',
        '0': '💬',
        '1': '📄',
        '2': '🗳️',
        'titre': '📝',
        'allocution': '🎤',
        'conference_presidents': '👥',
        'paraaddi': '📑',
        'excirrec': '⚠️',
    };
    return icons[type] || '📌';
};
</script>

<template>
    <Head :title="`Séance du ${debat.date_formatee} - Sénat`" />
    
    <AuthenticatedLayout>
        <!-- Hero Banner -->
        <div class="bg-gradient-to-br from-rose-600 via-pink-600 to-fuchsia-700 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Breadcrumb :items="breadcrumbItems" variant="light" class="mb-4" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold">
                            📅 {{ debat.date_formatee }}
                        </h1>
                        <p class="mt-2 text-rose-100 text-lg">
                            Séance n°{{ debat.numero || '?' }}
                            <span v-if="debat.est_congres" class="ml-2 px-2 py-1 bg-amber-500 rounded text-sm">
                                🏛️ Congrès
                            </span>
                        </p>
                        <p v-if="debat.libelle_special" class="mt-1 text-rose-200">
                            {{ debat.libelle_special }}
                        </p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex gap-3">
                        <a 
                            :href="debat.url_compte_rendu"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition"
                        >
                            📖 Compte-rendu officiel
                            <span class="text-xs">↗</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid lg:grid-cols-4 gap-8">
                <!-- Contenu principal -->
                <div class="lg:col-span-3">
                    <!-- Tabs -->
                    <div class="flex gap-2 mb-6 border-b border-slate-200 dark:border-slate-700">
                        <button 
                            @click="activeTab = 'legislatif'"
                            class="px-4 py-3 font-medium transition border-b-2 -mb-px"
                            :class="activeTab === 'legislatif' 
                                ? 'border-rose-500 text-rose-600 dark:text-rose-400' 
                                : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-rose-500'"
                        >
                            📜 Travaux législatifs
                            <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">
                                {{ sectionsLegislatives.length }}
                            </span>
                        </button>
                        <button 
                            @click="activeTab = 'divers'"
                            class="px-4 py-3 font-medium transition border-b-2 -mb-px"
                            :class="activeTab === 'divers' 
                                ? 'border-rose-500 text-rose-600 dark:text-rose-400' 
                                : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-rose-500'"
                        >
                            💬 Autres travaux
                            <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                {{ sectionsDiverses.length }}
                            </span>
                        </button>
                    </div>
                    
                    <!-- Sections législatives -->
                    <div v-if="activeTab === 'legislatif'" class="space-y-4">
                        <div v-if="sectionsLegislatives.length === 0" class="text-center py-12 text-slate-500">
                            Aucune section législative pour cette séance.
                        </div>
                        
                        <div 
                            v-for="section in sectionsLegislatives" 
                            :key="section.id"
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
                        >
                            <Link 
                                :href="route('debats.senat.section', section.id)"
                                class="block p-5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="text-2xl">{{ getTypeIcon(section.type) }}</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="px-2 py-0.5 text-xs font-medium rounded bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">
                                                {{ section.type_libelle }}
                                            </span>
                                            <span v-if="section.numero" class="text-sm text-slate-500">
                                                {{ section.numero }}
                                            </span>
                                        </div>
                                        <h3 class="font-semibold text-slate-900 dark:text-white">
                                            {{ section.objet || 'Section de discussion' }}
                                        </h3>
                                        <div class="mt-2 flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                                            <span>💬 {{ formatNumber(section.nb_interventions) }} interventions</span>
                                            <span v-if="section.enfants?.length">
                                                📂 {{ section.enfants.length }} sous-sections
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-slate-400">→</div>
                                </div>
                            </Link>
                            
                            <!-- Sous-sections -->
                            <div v-if="section.enfants?.length" class="border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                                <Link
                                    v-for="enfant in section.enfants.slice(0, 5)"
                                    :key="enfant.id"
                                    :href="route('debats.senat.section', enfant.id)"
                                    class="block px-5 py-3 pl-14 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition border-b border-slate-100 dark:border-slate-700 last:border-b-0"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm">{{ getTypeIcon(enfant.type) }}</span>
                                        <span class="flex-1 text-sm text-slate-700 dark:text-slate-300 truncate">
                                            {{ enfant.objet || enfant.numero || enfant.type_libelle }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            {{ enfant.nb_interventions }} int.
                                        </span>
                                    </div>
                                </Link>
                                <div v-if="section.enfants.length > 5" class="px-5 py-2 text-center text-sm text-rose-600">
                                    + {{ section.enfants.length - 5 }} autres...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sections diverses -->
                    <div v-if="activeTab === 'divers'" class="space-y-3">
                        <div v-if="sectionsDiverses.length === 0" class="text-center py-12 text-slate-500">
                            Aucune autre section pour cette séance.
                        </div>
                        
                        <div 
                            v-for="section in sectionsDiverses" 
                            :key="section.id"
                            class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div class="text-xl">{{ getTypeIcon(section.type) }}</div>
                                <div class="flex-1">
                                    <span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                        {{ section.type }}
                                    </span>
                                    <p class="mt-1 text-slate-900 dark:text-white">
                                        {{ section.objet || 'Section diverse' }}
                                    </p>
                                </div>
                                <a 
                                    v-if="section.url"
                                    :href="section.url"
                                    target="_blank"
                                    class="text-rose-600 hover:text-rose-700 text-sm"
                                >
                                    ↗
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Top intervenants -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            🎤 Top intervenants
                        </h3>
                        
                        <div class="space-y-3">
                            <div 
                                v-for="(item, index) in topIntervenants.slice(0, 8)" 
                                :key="item.code"
                                class="flex items-center gap-3"
                            >
                                <span class="w-6 h-6 flex items-center justify-center text-sm font-bold rounded-full"
                                    :class="index < 3 ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'"
                                >
                                    {{ index + 1 }}
                                </span>
                                
                                <template v-if="item.auteur">
                                    <component 
                                        :is="item.auteur.matricule ? Link : 'div'"
                                        :href="item.auteur.matricule ? route('representants.senateurs.show', item.auteur.matricule) : undefined"
                                        class="flex items-center gap-2 flex-1 min-w-0"
                                        :class="item.auteur.matricule ? 'hover:text-rose-600 transition cursor-pointer' : ''"
                                    >
                                        <img 
                                            v-if="item.auteur.photo_url"
                                            :src="item.auteur.photo_url"
                                            :alt="item.auteur.nom"
                                            class="w-8 h-8 rounded-full object-cover"
                                        />
                                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-xs" v-else>
                                            {{ item.auteur.prenom?.[0] }}{{ item.auteur.nom?.[0] }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                                {{ item.auteur.prenom }} {{ item.auteur.nom }}
                                            </p>
                                        </div>
                                    </component>
                                </template>
                                <template v-else>
                                    <span class="text-sm text-slate-600 dark:text-slate-400 flex-1">
                                        {{ item.code }}
                                    </span>
                                </template>
                                
                                <span class="text-xs font-medium text-rose-600 dark:text-rose-400">
                                    {{ item.nb_interventions }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liens utiles -->
                    <div class="bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                            🔗 Liens utiles
                        </h3>
                        <div class="space-y-2">
                            <a 
                                :href="debat.url_compte_rendu"
                                target="_blank"
                                class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-400 hover:underline"
                            >
                                📖 Compte-rendu intégral
                            </a>
                            <Link 
                                :href="route('parlement.calendrier.index')"
                                class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-400 hover:underline"
                            >
                                📅 Calendrier législatif
                            </Link>
                            <Link 
                                :href="route('representants.senateurs.index')"
                                class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-400 hover:underline"
                            >
                                🏛️ Tous les sénateurs
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
