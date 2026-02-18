<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import HemicycleChart from '@/Components/HemicycleChart.vue';

const props = defineProps({
    groupesAN: { type: Array, default: () => [] },
    groupesSenat: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const breadcrumbs = [
    { label: 'Accueil', href: route('dashboard'), icon: '🏠' },
    { label: 'Comprendre', href: route('democratie.index'), icon: '🎓' },
    { label: 'Nos Représentants', current: true, icon: '👥' },
];

const comparison = [
    { label: 'Nom officiel', an: 'Assemblée nationale', senat: 'Sénat' },
    { label: 'Nombre d\'élus', an: '577 députés', senat: '348 sénateurs' },
    { label: 'Mode d\'élection', an: 'Suffrage universel direct', senat: 'Suffrage indirect (grands électeurs)' },
    { label: 'Durée du mandat', an: '5 ans', senat: '6 ans (renouvellement par moitié tous les 3 ans)' },
    { label: 'Âge minimum', an: '18 ans', senat: '24 ans' },
    { label: 'Président actuel', an: 'Yaël Braun-Pivet', senat: 'Gérard Larcher' },
    { label: 'Pouvoir spécifique', an: 'Dernier mot sur les lois, motion de censure', senat: 'Représente les collectivités, révision constitutionnelle' },
    { label: 'Dissolution possible', an: 'Oui (par le Président)', senat: 'Non' },
];

const dailyTasks = [
    { icon: '📋', title: 'Commissions', description: 'Étudier les textes de loi en commission permanente (mardi et mercredi matin)' },
    { icon: '🎙️', title: 'Séance publique', description: 'Débattre et voter les lois en hémicycle (mardi après-midi au vendredi)' },
    { icon: '❓', title: 'Questions au Gouvernement', description: 'Interroger les ministres chaque mardi et mercredi (retransmis en direct)' },
    { icon: '✍️', title: 'Amendements', description: 'Rédiger et défendre des modifications aux textes de loi' },
    { icon: '📍', title: 'Circonscription', description: 'Permanences, rencontres avec les citoyens, événements locaux' },
    { icon: '🌍', title: 'Missions', description: 'Commissions d\'enquête, missions d\'information, diplomatie parlementaire' },
];

const totalAN = computed(() => props.groupesAN.reduce((s, g) => s + (g.nombre_membres || 0), 0));
const totalSenat = computed(() => props.groupesSenat.reduce((s, g) => s + (g.nombre_membres || 0), 0));

const parite = computed(() => props.stats?.parite || {});
</script>

<template>
    <Head title="Nos Représentants - Comprendre la Démocratie" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb :items="breadcrumbs" />

                <div class="mt-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">👥 Qui sont nos Représentants ?</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">
                        Députés et sénateurs : deux chambres, deux rôles complémentaires dans la démocratie française.
                    </p>
                </div>

                <!-- AN vs Sénat Comparison Table -->
                <div class="mt-10 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="grid grid-cols-3 text-center">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-r border-gray-200 dark:border-gray-700">
                            <span class="text-2xl">🏛️</span>
                            <h3 class="font-bold text-blue-900 dark:text-blue-200 mt-1">Assemblée Nationale</h3>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-b border-r border-gray-200 dark:border-gray-700">
                            <span class="text-lg font-semibold text-gray-500 dark:text-gray-400">VS</span>
                        </div>
                        <div class="p-4 bg-rose-50 dark:bg-rose-900/20 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-2xl">🏛️</span>
                            <h3 class="font-bold text-rose-900 dark:text-rose-200 mt-1">Sénat</h3>
                        </div>
                    </div>

                    <div v-for="(row, idx) in comparison" :key="row.label" class="grid grid-cols-3" :class="idx % 2 === 0 ? '' : 'bg-gray-50/50 dark:bg-gray-700/20'">
                        <div class="p-3 text-sm text-blue-800 dark:text-blue-200 border-r border-gray-100 dark:border-gray-700">
                            {{ row.an }}
                        </div>
                        <div class="p-3 text-xs font-medium text-gray-500 dark:text-gray-400 text-center border-r border-gray-100 dark:border-gray-700 flex items-center justify-center">
                            {{ row.label }}
                        </div>
                        <div class="p-3 text-sm text-rose-800 dark:text-rose-200">
                            {{ row.senat }}
                        </div>
                    </div>
                </div>

                <!-- Hemicycles -->
                <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">🏛️ Assemblée Nationale</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ totalAN }} membres dans {{ groupesAN.length }} groupes</p>
                        <HemicycleChart v-if="groupesAN.length" :groupes="groupesAN" :width="500" :height="300" />
                        <div v-else class="text-center py-8 text-gray-400">Données non disponibles</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">🏛️ Sénat</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ totalSenat }} membres dans {{ groupesSenat.length }} groupes</p>
                        <HemicycleChart v-if="groupesSenat.length" :groupes="groupesSenat" :width="500" :height="300" />
                        <div v-else class="text-center py-8 text-gray-400">Données non disponibles</div>
                    </div>
                </div>

                <!-- Parité stats -->
                <div v-if="parite.deputes || parite.senateurs" class="mt-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⚖️ Parité Femmes / Hommes</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div v-if="parite.deputes">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assemblée Nationale</p>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full" :style="{ width: parite.deputes.pct_femmes + '%' }"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 w-14 text-right">{{ parite.deputes.pct_femmes }}%</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ parite.deputes.femmes }} femmes · {{ parite.deputes.hommes }} hommes</p>
                        </div>
                        <div v-if="parite.senateurs">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sénat</p>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                    <div class="h-full bg-rose-500 rounded-full" :style="{ width: parite.senateurs.pct_femmes + '%' }"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 w-14 text-right">{{ parite.senateurs.pct_femmes }}%</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ parite.senateurs.femmes }} femmes · {{ parite.senateurs.hommes }} hommes</p>
                        </div>
                    </div>
                </div>

                <!-- Que fait un député -->
                <div class="mt-12">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📋 Que fait un député au quotidien ?</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="task in dailyTasks" :key="task.title" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                            <div class="text-2xl mb-2">{{ task.icon }}</div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ task.title }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ task.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="mt-12 text-center bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-8">
                    <h2 class="text-xl font-semibold text-indigo-900 dark:text-indigo-200">📍 Trouvez vos élus</h2>
                    <p class="mt-2 text-indigo-700 dark:text-indigo-300 text-sm">
                        Entrez votre code postal pour découvrir qui vous représente à l'Assemblée nationale, au Sénat et à la mairie.
                    </p>
                    <Link
                        :href="route('representants.mes-representants')"
                        class="mt-4 inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition"
                    >
                        📍 Mes Représentants
                    </Link>
                </div>

                <!-- Links -->
                <div class="mt-8 flex flex-wrap gap-3 justify-center pb-8">
                    <Link :href="route('representants.deputes.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🏛️ Tous les députés
                    </Link>
                    <Link :href="route('representants.senateurs.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        🏛️ Tous les sénateurs
                    </Link>
                    <Link :href="route('parlement.comparaison')" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        📊 Comparaison AN / Sénat
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
