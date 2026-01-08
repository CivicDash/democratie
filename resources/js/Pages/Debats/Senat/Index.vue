<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    debats: Object,
    stats: Object,
    filtres: Object,
});

const breadcrumbItems = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Sénat', href: route('representants.senateurs.index'), icon: '🏛️' },
    { label: 'Débats en séance', current: true, icon: '💬' },
];

// Filtres
const anneeFiltre = ref(props.filtres?.annee || '');
const moisFiltre = ref(props.filtres?.mois || '');

const moisOptions = [
    { value: '', label: 'Tous les mois' },
    { value: 1, label: 'Janvier' },
    { value: 2, label: 'Février' },
    { value: 3, label: 'Mars' },
    { value: 4, label: 'Avril' },
    { value: 5, label: 'Mai' },
    { value: 6, label: 'Juin' },
    { value: 7, label: 'Juillet' },
    { value: 8, label: 'Août' },
    { value: 9, label: 'Septembre' },
    { value: 10, label: 'Octobre' },
    { value: 11, label: 'Novembre' },
    { value: 12, label: 'Décembre' },
];

const appliquerFiltres = () => {
    router.get(route('debats.senat.index'), {
        annee: anneeFiltre.value || undefined,
        mois: moisFiltre.value || undefined,
    }, { preserveState: true });
};

const formatDate = (dateIso) => {
    const d = new Date(dateIso);
    return d.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatNumber = (n) => new Intl.NumberFormat('fr-FR').format(n);
</script>

<template>
    <Head title="Débats en séance - Sénat" />
    
    <AuthenticatedLayout>
        <!-- Hero Banner -->
        <div class="bg-gradient-to-br from-rose-600 via-pink-600 to-fuchsia-700 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Breadcrumb :items="breadcrumbItems" variant="light" class="mb-4" />
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold flex items-center gap-3">
                            💬 Débats en séance publique
                        </h1>
                        <p class="mt-2 text-rose-100 text-lg">
                            Comptes-rendus des séances du Sénat
                        </p>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-xl">
                            <p class="text-2xl font-bold">{{ formatNumber(stats.total_seances) }}</p>
                            <p class="text-xs text-rose-200">Séances</p>
                        </div>
                        <div class="text-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-xl">
                            <p class="text-2xl font-bold">{{ formatNumber(stats.total_sections) }}</p>
                            <p class="text-xs text-rose-200">Sections</p>
                        </div>
                        <div class="text-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-xl">
                            <p class="text-2xl font-bold">{{ formatNumber(stats.total_interventions) }}</p>
                            <p class="text-xs text-rose-200">Interventions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid lg:grid-cols-4 gap-8">
                <!-- Sidebar filtres -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sticky top-24">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            🔍 Filtres
                        </h3>
                        
                        <div class="space-y-4">
                            <!-- Année -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Année
                                </label>
                                <select 
                                    v-model="anneeFiltre"
                                    @change="appliquerFiltres"
                                    class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700"
                                >
                                    <option value="">Toutes les années</option>
                                    <option 
                                        v-for="annee in stats.annees_disponibles" 
                                        :key="annee" 
                                        :value="annee"
                                    >
                                        {{ annee }}
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Mois -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Mois
                                </label>
                                <select 
                                    v-model="moisFiltre"
                                    @change="appliquerFiltres"
                                    class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700"
                                >
                                    <option 
                                        v-for="m in moisOptions" 
                                        :key="m.value" 
                                        :value="m.value"
                                    >
                                        {{ m.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Info source -->
                        <div class="mt-6 p-4 bg-rose-50 dark:bg-rose-900/20 rounded-lg">
                            <p class="text-sm text-rose-700 dark:text-rose-300">
                                📊 <strong>Source:</strong> data.senat.fr<br/>
                                Les comptes-rendus sont disponibles depuis 1997.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des débats -->
                <div class="lg:col-span-3">
                    <div class="space-y-4">
                        <Link
                            v-for="debat in debats.data"
                            :key="debat.date_seance"
                            :href="route('debats.senat.show', debat.date_seance.split('T')[0])"
                            class="block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:border-rose-400 dark:hover:border-rose-500 transition-all hover:shadow-md group"
                        >
                            <div class="flex items-center gap-4">
                                <!-- Date -->
                                <div class="flex-shrink-0 w-20 h-20 bg-gradient-to-br from-rose-100 to-pink-100 dark:from-rose-900/30 dark:to-pink-900/30 rounded-xl flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold text-rose-600 dark:text-rose-400">
                                        {{ new Date(debat.date_seance).getDate() }}
                                    </span>
                                    <span class="text-xs text-rose-500 dark:text-rose-400 uppercase">
                                        {{ new Date(debat.date_seance).toLocaleDateString('fr-FR', { month: 'short' }) }}
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        {{ new Date(debat.date_seance).getFullYear() }}
                                    </span>
                                </div>
                                
                                <!-- Contenu -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-lg text-slate-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition">
                                        Séance n°{{ debat.numero || '?' }}
                                    </h3>
                                    <p class="text-slate-600 dark:text-slate-400">
                                        {{ formatDate(debat.date_seance) }}
                                    </p>
                                    <p v-if="debat.libelle_special" class="mt-1 text-sm text-rose-600 dark:text-rose-400">
                                        {{ debat.libelle_special }}
                                    </p>
                                    <div v-if="debat.est_congres" class="mt-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                            🏛️ Congrès
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Flèche -->
                                <div class="flex-shrink-0 text-slate-400 group-hover:text-rose-500 transition">
                                    →
                                </div>
                            </div>
                        </Link>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="debats.links && debats.links.length > 3" class="mt-8 flex justify-center gap-2">
                        <template v-for="link in debats.links" :key="link.label">
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
                    
                    <!-- Message vide -->
                    <div v-if="!debats.data?.length" class="text-center py-12">
                        <p class="text-slate-500 dark:text-slate-400">
                            Aucun débat trouvé pour ces critères.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
