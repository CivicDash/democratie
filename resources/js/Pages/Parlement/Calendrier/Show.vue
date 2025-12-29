<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    reunion: Object,
    reunionsSimilaires: Array,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Calendrier', href: route('parlement.calendrier.index'), icon: '📅' },
    { label: props.reunion.organe?.nom || 'Réunion', icon: props.reunion.emoji },
];

// Couleur d'état
const getEtatClass = (etat) => {
    switch (etat) {
        case 'Confirmé': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'Annulé': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        case 'Terminé': return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
        default: return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    }
};
</script>

<template>
    <Head :title="reunion.titre" />
    
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-950/20">
            <div class="py-8 px-4 sm:px-6 lg:px-8 xl:px-16 2xl:px-24">
                
                <!-- Breadcrumb -->
                <Breadcrumb :items="breadcrumbItems" class="mb-6" />
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-xl p-8 text-white mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                        <!-- Icône -->
                        <div class="flex-shrink-0">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-4xl">
                                {{ reunion.emoji }}
                            </div>
                        </div>
                        
                        <!-- Infos -->
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                <span :class="getEtatClass(reunion.etat)" class="px-3 py-1 rounded-full text-sm font-semibold">
                                    {{ reunion.etat || 'Prévu' }}
                                </span>
                                <span v-if="reunion.type_reunion" class="bg-white/20 px-3 py-1 rounded-full text-sm">
                                    {{ reunion.type_reunion }}
                                </span>
                                <span v-if="reunion.format_reunion" class="bg-white/20 px-3 py-1 rounded-full text-sm">
                                    {{ reunion.format_reunion }}
                                </span>
                            </div>
                            
                            <h1 class="text-3xl lg:text-4xl font-bold mb-3">
                                {{ reunion.titre }}
                            </h1>
                            
                            <div class="flex flex-wrap gap-4 text-indigo-100">
                                <span class="flex items-center gap-2">
                                    📅 {{ reunion.date_formatee }}
                                </span>
                                <span v-if="reunion.lieu" class="flex items-center gap-2">
                                    📍 {{ reunion.lieu }}
                                </span>
                                <span v-if="reunion.organe" class="flex items-center gap-2">
                                    🏛️ {{ reunion.organe.nom }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-3 mt-6">
                        <span v-if="reunion.visio" class="bg-white/20 px-3 py-1.5 rounded-lg text-sm flex items-center gap-2">
                            💻 Visioconférence
                        </span>
                        <span v-if="reunion.presse" class="bg-white/20 px-3 py-1.5 rounded-lg text-sm flex items-center gap-2">
                            📰 Ouverte à la presse
                        </span>
                        <span v-if="reunion.video" class="bg-white/20 px-3 py-1.5 rounded-lg text-sm flex items-center gap-2">
                            🎥 Captation vidéo
                        </span>
                        <span v-if="reunion.reunion_internationale" class="bg-white/20 px-3 py-1.5 rounded-lg text-sm flex items-center gap-2">
                            🌍 Réunion internationale
                        </span>
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Contenu principal -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Ordre du jour -->
                        <div v-if="reunion.odj_resume?.length || reunion.odj_convocation?.length" 
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                📋 Ordre du jour
                            </h2>
                            
                            <ol class="space-y-3">
                                <li 
                                    v-for="(item, index) in (reunion.odj_resume || reunion.odj_convocation)"
                                    :key="index"
                                    class="flex gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg"
                                >
                                    <span class="flex-shrink-0 w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center font-bold text-sm">
                                        {{ index + 1 }}
                                    </span>
                                    <span class="text-slate-700 dark:text-slate-300 flex-1">
                                        {{ item }}
                                    </span>
                                </li>
                            </ol>
                        </div>
                        
                        <!-- Personnes auditionnées -->
                        <div v-if="reunion.personnes_auditionnees?.length" 
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                👥 Personnes auditionnées
                            </h2>
                            
                            <div class="space-y-3">
                                <div 
                                    v-for="(personne, index) in reunion.personnes_auditionnees"
                                    :key="index"
                                    class="flex items-center gap-4 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg"
                                >
                                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center text-xl">
                                        👤
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            {{ personne.identite }}
                                        </p>
                                        <p v-if="personne.qualite" class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ personne.qualite }}
                                        </p>
                                        <p v-if="personne.organisme" class="text-sm text-indigo-600 dark:text-indigo-400">
                                            {{ personne.organisme }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Métadonnées -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                ℹ️ Informations
                            </h2>
                            
                            <dl class="grid sm:grid-cols-2 gap-4">
                                <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Identifiant</dt>
                                    <dd class="font-mono text-sm text-slate-900 dark:text-white">{{ reunion.uid }}</dd>
                                </div>
                                <div v-if="reunion.date_creation" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Date de création</dt>
                                    <dd class="text-slate-900 dark:text-white">{{ reunion.date_creation }}</dd>
                                </div>
                                <div v-if="reunion.compte_rendu_ref" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Compte-rendu</dt>
                                    <dd class="font-mono text-sm text-indigo-600 dark:text-indigo-400">{{ reunion.compte_rendu_ref }}</dd>
                                </div>
                                <div v-if="reunion.pays?.length" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">Pays concernés</dt>
                                    <dd class="text-slate-900 dark:text-white">{{ reunion.pays.join(', ') }}</dd>
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
                                <Link
                                    :href="route('parlement.calendrier.index')"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                                >
                                    📅 Retour au calendrier
                                </Link>
                                
                                <a
                                    v-if="reunion.organe"
                                    :href="`https://www.assemblee-nationale.fr/dyn/${reunion.organe.uid}`"
                                    target="_blank"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                                >
                                    🌐 Voir sur assemblee-nationale.fr
                                </a>
                            </div>
                        </div>
                        
                        <!-- Réunions similaires -->
                        <div v-if="reunionsSimilaires?.length" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                                Autres réunions de {{ reunion.organe?.nom || 'cet organe' }}
                            </h3>
                            
                            <div class="space-y-3">
                                <Link
                                    v-for="r in reunionsSimilaires"
                                    :key="r.uid"
                                    :href="route('parlement.calendrier.show', r.uid)"
                                    class="block p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600/50 transition"
                                >
                                    <p class="font-semibold text-slate-900 dark:text-white text-sm">
                                        {{ r.date_courte }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-1">
                                        {{ r.titre }}
                                    </p>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>

