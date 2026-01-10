<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    stats: Object,
    dernieres_listes: Array,
    dates_election: Object,
    etapes_candidature: Array,
});

// Calcul du temps restant
const getJoursRestants = (dateStr) => {
    const target = new Date(dateStr);
    const now = new Date();
    const diff = Math.ceil((target - now) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 0;
};

const joursAvantPremierTour = getJoursRestants(props.dates_election.premier_tour);
const joursAvantDepot = getJoursRestants(props.dates_election.limite_depot);
</script>

<template>
    <Head title="Élections Municipales 2026" />

    <AuthenticatedLayout>
        <!-- Hero Section -->
        <div class="relative bg-gradient-to-br from-indigo-900 via-purple-900 to-fuchsia-900 overflow-hidden">
            <!-- Pattern SVG -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid-muni" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid-muni)" />
                </svg>
            </div>
            
            <!-- Cercles décoratifs -->
            <div class="absolute -top-20 -left-20 w-64 h-64 bg-fuchsia-500/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
                <div class="text-center">
                    <div class="flex items-center justify-center gap-3 mb-6">
                        <span class="text-5xl">🗳️</span>
                        <Badge class="bg-fuchsia-500/30 text-fuchsia-200 border border-fuchsia-400/30 text-lg px-4 py-2">
                            15 & 22 mars 2026
                        </Badge>
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl font-bold text-white tracking-tight mb-6">
                        Élections Municipales <span class="text-fuchsia-300">2026</span>
                    </h1>
                    
                    <p class="text-xl text-indigo-200 max-w-3xl mx-auto mb-8">
                        Découvrez les candidats dans votre commune, suivez les programmes et préparez votre vote. 
                        <span class="text-fuchsia-300 font-semibold">Candidats : inscrivez votre liste dès maintenant !</span>
                    </p>

                    <!-- Countdown -->
                    <div class="flex flex-wrap justify-center gap-4 mb-10">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-6 py-4 border border-white/20">
                            <div class="text-4xl font-bold text-white">{{ joursAvantPremierTour }}</div>
                            <div class="text-sm text-indigo-200">jours avant le 1er tour</div>
                        </div>
                        <div class="bg-fuchsia-500/20 backdrop-blur-sm rounded-xl px-6 py-4 border border-fuchsia-400/30">
                            <div class="text-4xl font-bold text-fuchsia-300">{{ joursAvantDepot }}</div>
                            <div class="text-sm text-fuchsia-200">jours pour déposer sa candidature</div>
                        </div>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap justify-center gap-4">
                        <Link
                            :href="route('elections.municipales.recherche')"
                            class="inline-flex items-center gap-2 px-8 py-4 bg-white text-indigo-900 font-bold rounded-xl hover:bg-indigo-50 transition shadow-lg hover:shadow-xl"
                        >
                            🔍 Trouver les candidats dans ma commune
                        </Link>
                        <Link
                            :href="route('elections.municipales.carte')"
                            class="inline-flex items-center gap-2 px-8 py-4 bg-white/20 text-white font-bold rounded-xl hover:bg-white/30 transition shadow-lg border border-white/30"
                        >
                            🗺️ Voir la carte
                        </Link>
                        <Link
                            :href="route('elections.municipales.tutoriel')"
                            class="inline-flex items-center gap-2 px-8 py-4 bg-fuchsia-500 text-white font-bold rounded-xl hover:bg-fuchsia-400 transition shadow-lg hover:shadow-xl"
                        >
                            📝 Je veux me présenter
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 -mt-8 relative z-10">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="text-4xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ stats.total_listes }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400 mt-1">Listes inscrites</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="text-4xl font-bold text-fuchsia-600 dark:text-fuchsia-400">
                        {{ stats.total_candidats }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400 mt-1">Candidats</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="text-4xl font-bold text-purple-600 dark:text-purple-400">
                        {{ stats.communes_couvertes }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400 mt-1">Communes couvertes</div>
                </div>
            </div>

            <!-- Dates clés -->
            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    📅 Dates clés
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/30 rounded-xl p-5 border border-amber-200 dark:border-amber-700">
                        <div class="text-amber-600 dark:text-amber-400 text-sm font-medium">Limite de dépôt</div>
                        <div class="text-2xl font-bold text-amber-900 dark:text-amber-200 mt-1">27 février</div>
                        <div class="text-amber-700 dark:text-amber-300 text-sm mt-1">à 18h00 en préfecture</div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl p-5 border border-blue-200 dark:border-blue-700">
                        <div class="text-blue-600 dark:text-blue-400 text-sm font-medium">Début de campagne</div>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-200 mt-1">2 mars</div>
                        <div class="text-blue-700 dark:text-blue-300 text-sm mt-1">Campagne officielle</div>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 rounded-xl p-5 border border-indigo-200 dark:border-indigo-700">
                        <div class="text-indigo-600 dark:text-indigo-400 text-sm font-medium">Premier tour</div>
                        <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-200 mt-1">15 mars</div>
                        <div class="text-indigo-700 dark:text-indigo-300 text-sm mt-1">🗳️ Dimanche</div>
                    </div>
                    <div class="bg-gradient-to-br from-fuchsia-50 to-fuchsia-100 dark:from-fuchsia-900/30 dark:to-fuchsia-800/30 rounded-xl p-5 border border-fuchsia-200 dark:border-fuchsia-700">
                        <div class="text-fuchsia-600 dark:text-fuchsia-400 text-sm font-medium">Second tour</div>
                        <div class="text-2xl font-bold text-fuchsia-900 dark:text-fuchsia-200 mt-1">22 mars</div>
                        <div class="text-fuchsia-700 dark:text-fuchsia-300 text-sm mt-1">🗳️ Dimanche</div>
                    </div>
                </div>
            </section>

            <!-- Comment se présenter (Aperçu) -->
            <section class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        📋 Comment se présenter ?
                    </h2>
                    <Link
                        :href="route('elections.municipales.tutoriel')"
                        class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                    >
                        Voir le guide complet →
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div
                        v-for="etape in etapes_candidature"
                        :key="etape.numero"
                        class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition group"
                    >
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-2xl">{{ etape.icone }}</span>
                            <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-sm font-bold flex items-center justify-center">
                                {{ etape.numero }}
                            </span>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                            {{ etape.titre }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                            {{ etape.description }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Dernières listes -->
            <section v-if="dernieres_listes.length > 0" class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        ✨ Dernières listes validées
                    </h2>
                    <Link
                        :href="route('elections.municipales.recherche')"
                        class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                    >
                        Voir toutes →
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="liste in dernieres_listes"
                        :key="liste.uuid"
                        :href="route('elections.municipales.liste', liste.uuid)"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition group"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                v-if="liste.logo_url"
                                class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0"
                            >
                                <img :src="liste.logo_url" :alt="liste.nom_liste" class="w-full h-full object-cover" />
                            </div>
                            <div
                                v-else
                                class="w-16 h-16 rounded-xl flex-shrink-0 flex items-center justify-center text-2xl"
                                :style="{ backgroundColor: liste.couleur + '20' }"
                            >
                                🏛️
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition truncate">
                                    {{ liste.nom_liste }}
                                </h3>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    📍 {{ liste.commune_nom }} ({{ liste.departement_code }})
                                </div>
                                <div v-if="liste.tete_de_liste" class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                    👤 {{ liste.tete_de_liste }}
                                </div>
                                <Badge
                                    v-if="liste.nuance_politique"
                                    class="mt-2 text-xs"
                                    :style="{ backgroundColor: liste.couleur + '30', color: liste.couleur }"
                                >
                                    {{ liste.nuance_politique }}
                                </Badge>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- CTA Candidat -->
            <section class="bg-gradient-to-br from-indigo-600 to-fuchsia-600 rounded-2xl p-8 md:p-12 text-center text-white">
                <h2 class="text-3xl font-bold mb-4">
                    Vous êtes candidat(e) ? 🎯
                </h2>
                <p class="text-lg text-indigo-100 mb-8 max-w-2xl mx-auto">
                    Créez votre profil gratuitement, présentez votre programme et gagnez en visibilité auprès de vos électeurs.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <Link
                        :href="route('elections.municipales.espace-candidat.index')"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-white text-indigo-600 font-bold rounded-xl hover:bg-indigo-50 transition shadow-lg"
                    >
                        🚀 Créer ma liste
                    </Link>
                    <Link
                        :href="route('elections.municipales.tutoriel')"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-white/20 text-white font-bold rounded-xl hover:bg-white/30 transition border border-white/30"
                    >
                        📖 Guide de candidature
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
