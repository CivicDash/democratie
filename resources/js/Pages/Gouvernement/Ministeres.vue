<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    domaines: Array,
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'État', icon: '🇫🇷' },
    { label: 'Gouvernement', href: route('gouvernement.index'), icon: '🏛️' },
    { label: 'Ministères', current: true, icon: '📋' },
];
</script>

<template>
    <Head title="Ministères de France" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#28285a] via-[#1e1e4a] to-slate-900">
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <Breadcrumb :items="breadcrumbs" variant="light" class="mb-6" />
                
                <div class="flex items-center gap-6 mb-8">
                    <div class="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center text-4xl">
                        🏛️
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                            Ministères de France
                        </h1>
                        <p class="text-blue-200 text-lg">
                            Les grands domaines ministériels et leur histoire
                        </p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-white">{{ domaines.length }}</div>
                        <div class="text-blue-200 text-sm">Domaines ministériels</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-emerald-400">
                            {{ domaines.filter(d => d.ministre_actuel).length }}
                        </div>
                        <div class="text-blue-200 text-sm">Avec ministre actuel</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <div class="text-3xl font-bold text-amber-400">
                            {{ domaines.reduce((sum, d) => sum + d.nb_ministres_historiques, 0) }}
                        </div>
                        <div class="text-blue-200 text-sm">Ministres historiques</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contenu -->
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="domaine in domaines"
                        :key="domaine.id"
                        :href="route('gouvernement.ministere.show', domaine.slug)"
                        class="group"
                    >
                        <Card class="h-full hover:shadow-lg transition-all duration-300 hover:-translate-y-1 overflow-hidden">
                            <!-- Barre de couleur -->
                            <div 
                                class="h-2 -mx-6 -mt-6 mb-4"
                                :style="{ backgroundColor: domaine.couleur }"
                            ></div>
                            
                            <div class="flex items-start gap-4">
                                <!-- Icône ou sigle -->
                                <div 
                                    class="w-14 h-14 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0"
                                    :style="{ backgroundColor: domaine.couleur }"
                                >
                                    {{ domaine.sigle || domaine.nom.substring(0, 2) }}
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition">
                                        {{ domaine.nom }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mt-1">
                                        {{ domaine.description || 'Ministère de la République française' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Ministre actuel -->
                            <div v-if="domaine.ministre_actuel" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <img 
                                        v-if="domaine.ministre_actuel.photo"
                                        :src="domaine.ministre_actuel.photo"
                                        :alt="domaine.ministre_actuel.nom"
                                        class="w-10 h-10 rounded-full object-cover"
                                    />
                                    <div v-else class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        👤
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ domaine.ministre_actuel.nom }}
                                        </div>
                                        <div class="text-xs text-emerald-600 dark:text-emerald-400">
                                            En fonction
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="text-sm text-gray-400">
                                    Aucun ministre actuellement
                                </div>
                            </div>

                            <!-- Footer stats -->
                            <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
                                <span>{{ domaine.nb_ministres_historiques }} ministres historiques</span>
                                <span class="text-blue-600 group-hover:underline">Voir l'historique →</span>
                            </div>
                        </Card>
                    </Link>
                </div>

                <!-- Lien retour -->
                <div class="mt-8 text-center">
                    <Link 
                        :href="route('gouvernement.index')"
                        class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour au gouvernement
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
