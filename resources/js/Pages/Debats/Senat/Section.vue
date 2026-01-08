<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    section: Object,
    debat: Object,
    interventions: Array,
    enfants: Array,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Débats Sénat', href: route('debats.senat.index'), icon: '💬' },
    { label: props.debat.date_formatee, href: route('debats.senat.show', props.debat.date_seance.split('T')[0]), icon: '📅' },
    { label: props.section.type_libelle, current: true, icon: '📄' },
];

const formatNumber = (n) => new Intl.NumberFormat('fr-FR').format(n);
</script>

<template>
    <Head :title="`${section.type_libelle} - ${debat.date_formatee}`" />
    
    <AuthenticatedLayout>
        <!-- Hero Banner -->
        <div class="bg-gradient-to-br from-rose-600 via-pink-600 to-fuchsia-700 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Breadcrumb :items="breadcrumbItems" variant="light" class="mb-4" />
                
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 bg-white/20 rounded-lg text-sm">
                            {{ section.type_libelle }}
                        </span>
                        <span v-if="section.numero" class="text-rose-200">
                            {{ section.numero }}
                        </span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold">
                        {{ section.objet || 'Section de discussion' }}
                    </h1>
                    <p class="mt-2 text-rose-100">
                        📅 {{ debat.date_formatee }} • 
                        💬 {{ formatNumber(interventions.length) }} interventions
                    </p>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid lg:grid-cols-4 gap-8">
                <!-- Liste des interventions -->
                <div class="lg:col-span-3 space-y-4">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                        🎤 Interventions
                    </h2>
                    
                    <div v-if="interventions.length === 0" class="text-center py-12 text-slate-500">
                        Aucune intervention enregistrée pour cette section.
                    </div>
                    
                    <div 
                        v-for="(intervention, index) in interventions" 
                        :key="intervention.id"
                        class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5"
                    >
                        <div class="flex gap-4">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <template v-if="intervention.auteur?.matricule">
                                    <Link :href="route('representants.senateurs.show', intervention.auteur.matricule)">
                                        <img 
                                            v-if="intervention.auteur.photo_url"
                                            :src="intervention.auteur.photo_url"
                                            :alt="intervention.auteur.nom"
                                            class="w-12 h-12 rounded-full object-cover ring-2 ring-rose-200 dark:ring-rose-800"
                                        />
                                        <div 
                                            v-else
                                            class="w-12 h-12 rounded-full bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center text-white font-bold"
                                        >
                                            {{ intervention.auteur.prenom?.[0] }}{{ intervention.auteur.nom?.[0] }}
                                        </div>
                                    </Link>
                                </template>
                                <div 
                                    v-else
                                    class="w-12 h-12 rounded-full bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center text-white font-bold"
                                >
                                    {{ intervention.auteur?.prenom?.[0] || '?' }}{{ intervention.auteur?.nom?.[0] || '' }}
                                </div>
                            </div>
                            
                            <!-- Contenu -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <template v-if="intervention.auteur?.matricule">
                                        <Link 
                                            :href="route('representants.senateurs.show', intervention.auteur.matricule)"
                                            class="font-semibold text-slate-900 dark:text-white hover:text-rose-600 transition"
                                        >
                                            {{ intervention.auteur.prenom }} {{ intervention.auteur.nom }}
                                        </Link>
                                    </template>
                                    <template v-else>
                                        <span class="font-semibold text-slate-900 dark:text-white">
                                            {{ intervention.auteur?.prenom }} {{ intervention.auteur?.nom }}
                                        </span>
                                    </template>
                                    
                                    <span v-if="intervention.fonction" class="text-xs text-slate-500">
                                        ({{ intervention.fonction }})
                                    </span>
                                </div>
                                
                                <p v-if="intervention.analyse" class="text-slate-700 dark:text-slate-300 leading-relaxed">
                                    {{ intervention.analyse }}
                                </p>
                                <p v-else class="text-slate-500 italic">
                                    Intervention sans analyse disponible
                                </p>
                                
                                <div class="mt-3 flex items-center gap-4">
                                    <span class="text-xs text-slate-400">
                                        #{{ index + 1 }}
                                    </span>
                                    <a 
                                        v-if="intervention.url"
                                        :href="intervention.url"
                                        target="_blank"
                                        class="text-xs text-rose-600 hover:text-rose-700 hover:underline"
                                    >
                                        📖 Voir sur senat.fr ↗
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Infos section -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                            ℹ️ Informations
                        </h3>
                        
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500 dark:text-slate-400">Type</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">
                                    {{ section.type_libelle }}
                                </dd>
                            </div>
                            <div v-if="section.numero">
                                <dt class="text-slate-500 dark:text-slate-400">Numéro</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">
                                    {{ section.numero }}
                                </dd>
                            </div>
                            <div v-if="section.lecture_id">
                                <dt class="text-slate-500 dark:text-slate-400">Lecture</dt>
                                <dd class="font-mono text-xs text-slate-700 dark:text-slate-300">
                                    {{ section.lecture_id }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500 dark:text-slate-400">Interventions</dt>
                                <dd class="font-medium text-rose-600 dark:text-rose-400">
                                    {{ formatNumber(interventions.length) }}
                                </dd>
                            </div>
                        </dl>
                        
                        <a 
                            v-if="section.url"
                            :href="section.url"
                            target="_blank"
                            class="mt-4 block w-full text-center px-4 py-2 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 rounded-lg hover:bg-rose-200 dark:hover:bg-rose-900/50 transition text-sm"
                        >
                            📖 Voir sur senat.fr ↗
                        </a>
                    </div>
                    
                    <!-- Sous-sections -->
                    <div v-if="enfants?.length" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                            📂 Sous-sections ({{ enfants.length }})
                        </h3>
                        
                        <div class="space-y-2">
                            <Link
                                v-for="enfant in enfants"
                                :key="enfant.id"
                                :href="route('debats.senat.section', enfant.id)"
                                class="block p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition"
                            >
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                    {{ enfant.objet || enfant.numero || enfant.type_libelle }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ enfant.nb_interventions }} interventions
                                </p>
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                            🔗 Navigation
                        </h3>
                        <div class="space-y-2">
                            <Link 
                                :href="route('debats.senat.show', debat.date_seance.split('T')[0])"
                                class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-400 hover:underline"
                            >
                                ← Retour à la séance
                            </Link>
                            <Link 
                                :href="route('debats.senat.index')"
                                class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-400 hover:underline"
                            >
                                📋 Toutes les séances
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
