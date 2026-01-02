<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    domaine: Object,
    postes: Array,
    ministres: Array,
    stats: Object,
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'État', icon: '🇫🇷' },
    { label: 'Gouvernement', href: route('gouvernement.index'), icon: '🏛️' },
    { label: 'Ministères', href: route('gouvernement.ministeres'), icon: '📋' },
    { label: props.domaine.nom, current: true },
];

const activeTab = ref('chronologie');

// Grouper les postes par gouvernement
const postesParGouvernement = computed(() => {
    const grouped = {};
    props.postes.forEach(poste => {
        const key = poste.gouvernement.id;
        if (!grouped[key]) {
            grouped[key] = {
                gouvernement: poste.gouvernement,
                postes: [],
            };
        }
        grouped[key].postes.push(poste);
    });
    return Object.values(grouped);
});
</script>

<template>
    <Head :title="domaine.nom" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section 
            class="relative overflow-hidden"
            :style="{ background: `linear-gradient(135deg, ${domaine.couleur}dd 0%, ${domaine.couleur}88 50%, #1e1e4a 100%)` }"
        >
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex flex-col md:flex-row items-start gap-6">
                    <!-- Logo / Sigle -->
                    <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white font-bold text-2xl flex-shrink-0">
                        {{ domaine.sigle || domaine.nom.substring(0, 3) }}
                    </div>
                    
                    <div class="flex-1">
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                            {{ domaine.nom }}
                        </h1>
                        <p v-if="domaine.sigle" class="text-white/80 text-lg mb-4">
                            {{ domaine.sigle }}
                        </p>
                        
                        <!-- Coordonnées -->
                        <div class="flex flex-wrap gap-4 text-white/80 text-sm">
                            <a 
                                v-if="domaine.site_web"
                                :href="domaine.site_web"
                                target="_blank"
                                class="flex items-center gap-1 hover:text-white transition"
                            >
                                🌐 Site officiel
                            </a>
                            <a 
                                v-if="domaine.wikipedia_url"
                                :href="domaine.wikipedia_url"
                                target="_blank"
                                class="flex items-center gap-1 hover:text-white transition"
                            >
                                📖 Wikipedia
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Ministre actuel -->
                <div v-if="stats.ministre_actuel" class="mt-8 bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                    <div class="text-white/60 text-sm mb-3">Ministre actuel</div>
                    <div class="flex items-center gap-4">
                        <img 
                            v-if="stats.ministre_actuel.personne.photo"
                            :src="stats.ministre_actuel.personne.photo"
                            :alt="stats.ministre_actuel.personne.nom"
                            class="w-16 h-16 rounded-full object-cover border-2 border-white/30"
                        />
                        <div v-else class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-3xl">
                            👤
                        </div>
                        <div>
                            <Link 
                                :href="route('gouvernement.personne', stats.ministre_actuel.personne.slug)"
                                class="text-xl font-bold text-white hover:underline"
                            >
                                {{ stats.ministre_actuel.personne.nom }}
                            </Link>
                            <div class="text-white/80">{{ stats.ministre_actuel.fonction }}</div>
                            <div class="text-white/60 text-sm">
                                Depuis le {{ stats.ministre_actuel.date_debut }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-white">{{ stats.total_ministres }}</div>
                        <div class="text-white/60 text-sm">Ministres distincts</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-white">{{ stats.total_postes }}</div>
                        <div class="text-white/60 text-sm">Mandats ministériels</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-white">{{ postesParGouvernement.length }}</div>
                        <div class="text-white/60 text-sm">Gouvernements</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Description Wikipedia -->
                <Card v-if="domaine.wikipedia_extract" class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        📖 Présentation
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ domaine.wikipedia_extract }}
                    </p>
                    <a 
                        v-if="domaine.wikipedia_url"
                        :href="domaine.wikipedia_url"
                        target="_blank"
                        class="inline-flex items-center gap-1 mt-4 text-blue-600 hover:underline text-sm"
                    >
                        En savoir plus sur Wikipedia →
                    </a>
                </Card>

                <!-- Onglets -->
                <div class="flex gap-2 mb-6">
                    <button
                        @click="activeTab = 'chronologie'"
                        class="px-4 py-2 rounded-lg font-medium transition"
                        :class="activeTab === 'chronologie' 
                            ? 'bg-blue-600 text-white' 
                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                    >
                        📅 Chronologie
                    </button>
                    <button
                        @click="activeTab = 'ministres'"
                        class="px-4 py-2 rounded-lg font-medium transition"
                        :class="activeTab === 'ministres' 
                            ? 'bg-blue-600 text-white' 
                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                    >
                        👥 Ministres ({{ ministres.length }})
                    </button>
                </div>

                <!-- Chronologie -->
                <div v-if="activeTab === 'chronologie'" class="space-y-6">
                    <Card 
                        v-for="item in postesParGouvernement"
                        :key="item.gouvernement.id"
                    >
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: domaine.couleur }"></div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                {{ item.gouvernement.nom }}
                            </h3>
                            <span class="text-sm text-gray-500">
                                PM: {{ item.gouvernement.premier_ministre }}
                            </span>
                        </div>
                        
                        <div class="space-y-3 pl-5 border-l-2" :style="{ borderColor: domaine.couleur }">
                            <Link
                                v-for="poste in item.postes"
                                :key="poste.id"
                                :href="route('gouvernement.personne', poste.personne.slug)"
                                class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group"
                            >
                                <img 
                                    v-if="poste.personne.photo"
                                    :src="poste.personne.photo"
                                    :alt="poste.personne.nom"
                                    class="w-12 h-12 rounded-full object-cover"
                                />
                                <div v-else class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    👤
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition">
                                        {{ poste.personne.nom }}
                                    </div>
                                    <div class="text-sm text-gray-500">{{ poste.fonction }}</div>
                                </div>
                                <div class="text-right text-sm">
                                    <div class="text-gray-600 dark:text-gray-400">
                                        {{ poste.date_debut }} → {{ poste.date_fin || 'En cours' }}
                                    </div>
                                    <div v-if="poste.duree" class="text-gray-400">{{ poste.duree }}</div>
                                </div>
                                <span 
                                    v-if="poste.actif"
                                    class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-full"
                                >
                                    ACTUEL
                                </span>
                            </Link>
                        </div>
                    </Card>
                </div>

                <!-- Liste des ministres -->
                <div v-if="activeTab === 'ministres'" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        v-for="ministre in ministres"
                        :key="ministre.personne.id"
                        :href="route('gouvernement.personne', ministre.personne.slug)"
                        class="group"
                    >
                        <Card class="h-full hover:shadow-lg transition-all duration-300">
                            <div class="flex items-center gap-4">
                                <img 
                                    v-if="ministre.personne.photo"
                                    :src="ministre.personne.photo"
                                    :alt="ministre.personne.nom"
                                    class="w-16 h-16 rounded-full object-cover"
                                />
                                <div v-else class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-2xl">
                                    👤
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition truncate">
                                        {{ ministre.personne.nom }}
                                    </div>
                                    <div v-if="ministre.personne.parti" class="text-sm text-gray-500 truncate">
                                        {{ ministre.personne.parti }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ ministre.nb_postes }} mandat{{ ministre.nb_postes > 1 ? 's' : '' }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
                                {{ ministre.premier_poste }} → {{ ministre.dernier_poste }}
                            </div>
                        </Card>
                    </Link>
                </div>

                <!-- Lien retour -->
                <div class="mt-8 text-center">
                    <Link 
                        :href="route('gouvernement.ministeres')"
                        class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux ministères
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
