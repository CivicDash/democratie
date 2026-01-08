<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    senateur: Object,
    interventions: Object,
    stats: Object,
    filtres: Object,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Sénateurs', href: route('representants.senateurs.index'), icon: '🏛️' },
    { label: `${props.senateur.prenom} ${props.senateur.nom}`, href: route('representants.senateurs.show', props.senateur.matricule), icon: '👤' },
    { label: 'Interventions en séance', current: true, icon: '🎤' },
];

const anneeFiltre = ref(props.filtres?.annee || '');

const appliquerFiltre = () => {
    router.get(route('debats.senat.senateur', props.senateur.matricule), {
        annee: anneeFiltre.value || undefined,
    }, { preserveState: true });
};

const formatDate = (dateIso) => {
    const d = new Date(dateIso);
    return d.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatNumber = (n) => new Intl.NumberFormat('fr-FR').format(n);
</script>

<template>
    <Head :title="`Interventions de ${senateur.prenom} ${senateur.nom}`" />
    
    <AuthenticatedLayout>
        <!-- Hero Banner -->
        <div class="bg-gradient-to-br from-rose-600 via-pink-600 to-fuchsia-700 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Breadcrumb :items="breadcrumbItems" variant="light" class="mb-4" />
                
                <div class="flex items-center gap-6">
                    <!-- Photo -->
                    <img 
                        v-if="senateur.photo_url"
                        :src="senateur.photo_url"
                        :alt="senateur.nom"
                        class="w-20 h-20 rounded-full object-cover ring-4 ring-white/30"
                    />
                    <div 
                        v-else
                        class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center text-3xl font-bold"
                    >
                        {{ senateur.prenom?.[0] }}{{ senateur.nom?.[0] }}
                    </div>
                    
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold">
                            🎤 Interventions en séance
                        </h1>
                        <p class="mt-1 text-rose-100 text-lg">
                            {{ senateur.prenom }} {{ senateur.nom }}
                        </p>
                        <p v-if="senateur.groupe" class="text-rose-200 text-sm">
                            {{ senateur.groupe }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid lg:grid-cols-4 gap-8">
                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Stats -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                            📊 Statistiques
                        </h3>
                        
                        <div class="text-center mb-6">
                            <p class="text-4xl font-bold text-rose-600 dark:text-rose-400">
                                {{ formatNumber(stats.total) }}
                            </p>
                            <p class="text-sm text-slate-500">interventions totales</p>
                        </div>
                        
                        <div class="space-y-2">
                            <div 
                                v-for="item in stats.par_annee"
                                :key="item.annee"
                                class="flex justify-between items-center text-sm"
                            >
                                <span class="text-slate-600 dark:text-slate-400">{{ item.annee }}</span>
                                <span class="font-medium text-slate-900 dark:text-white">
                                    {{ formatNumber(item.nb) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtre -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                            🔍 Filtrer
                        </h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Année
                            </label>
                            <select 
                                v-model="anneeFiltre"
                                @change="appliquerFiltre"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700"
                            >
                                <option value="">Toutes les années</option>
                                <option 
                                    v-for="item in stats.par_annee" 
                                    :key="item.annee" 
                                    :value="item.annee"
                                >
                                    {{ item.annee }} ({{ item.nb }})
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4">
                            🔗 Voir aussi
                        </h3>
                        <div class="space-y-2">
                            <Link 
                                :href="route('representants.senateurs.show', senateur.matricule)"
                                class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-400 hover:underline"
                            >
                                👤 Fiche complète
                            </Link>
                            <Link 
                                :href="route('representants.senateurs.votes', senateur.matricule)"
                                class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-400 hover:underline"
                            >
                                🗳️ Votes
                            </Link>
                            <Link 
                                :href="route('representants.senateurs.amendements', senateur.matricule)"
                                class="flex items-center gap-2 text-sm text-rose-700 dark:text-rose-400 hover:underline"
                            >
                                📝 Amendements
                            </Link>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des interventions -->
                <div class="lg:col-span-3 space-y-4">
                    <div v-if="interventions.data?.length === 0" class="text-center py-12 text-slate-500">
                        Aucune intervention trouvée pour ces critères.
                    </div>
                    
                    <div 
                        v-for="intervention in interventions.data" 
                        :key="intervention.id"
                        class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 bg-rose-100 dark:bg-rose-900/30 rounded-lg flex flex-col items-center justify-center">
                                <span class="text-lg font-bold text-rose-600 dark:text-rose-400">
                                    {{ new Date(intervention.date_seance).getDate() }}
                                </span>
                                <span class="text-xs text-rose-500">
                                    {{ new Date(intervention.date_seance).toLocaleDateString('fr-FR', { month: 'short' }) }}
                                </span>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                        {{ intervention.type_section }}
                                    </span>
                                    <span v-if="intervention.fonction" class="text-xs text-slate-500">
                                        ({{ intervention.fonction }})
                                    </span>
                                </div>
                                
                                <h3 v-if="intervention.section_objet" class="font-medium text-slate-900 dark:text-white mb-2">
                                    {{ intervention.section_objet }}
                                </h3>
                                
                                <p v-if="intervention.analyse" class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                                    {{ intervention.analyse }}
                                </p>
                                <p v-else class="text-slate-500 italic text-sm">
                                    Intervention sans analyse disponible
                                </p>
                                
                                <div class="mt-3 flex items-center gap-4">
                                    <Link 
                                        :href="route('debats.senat.show', intervention.date_seance.split('T')[0])"
                                        class="text-xs text-rose-600 hover:underline"
                                    >
                                        📅 Voir la séance
                                    </Link>
                                    <a 
                                        v-if="intervention.url"
                                        :href="'https://www.senat.fr' + intervention.url"
                                        target="_blank"
                                        class="text-xs text-rose-600 hover:underline"
                                    >
                                        📖 senat.fr ↗
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="interventions.links && interventions.links.length > 3" class="mt-8 flex justify-center gap-2">
                        <template v-for="link in interventions.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-4 py-2 rounded-lg border transition"
                                :class="link.active 
                                    ? 'bg-rose-600 text-white border-rose-600' 
                                    : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 hover:border-rose-400'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-4 py-2 text-slate-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
