<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CommuneResultCard from '@/Components/Municipales/CommuneResultCard.vue';

const props = defineProps({
    code_departement: String,
    stats: Object,
    resultats: Array,
});

const participation = props.stats?.participation;
const communes = props.stats?.communes;
const formatNumber = (n) => n?.toLocaleString('fr-FR') ?? '-';
</script>

<template>
    <Head :title="`Résultats Département ${code_departement} — Municipales 2026`" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                <Link :href="route('elections.municipales.resultats.index')" class="hover:text-indigo-600">Résultats</Link>
                <span>/</span>
                <span class="text-gray-900 dark:text-gray-100 font-medium">Département {{ code_departement }}</span>
            </nav>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">
                Département {{ code_departement }}
            </h1>

            <!-- Stats départementales -->
            <div v-if="communes" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(communes.total) }}</div>
                    <div class="text-xs text-gray-500">communes</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-2xl font-bold text-emerald-600">{{ formatNumber(communes.elues_t1) }}</div>
                    <div class="text-xs text-gray-500">élues T1</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-2xl font-bold text-amber-600">{{ formatNumber(communes.second_tour) }}</div>
                    <div class="text-xs text-gray-500">second tour</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-2xl font-bold text-indigo-600">{{ participation?.t1?.taux?.toFixed(1) || '-' }}%</div>
                    <div class="text-xs text-gray-500">participation</div>
                </div>
            </div>

            <!-- Liste des communes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <CommuneResultCard
                    v-for="r in resultats"
                    :key="r.code_commune"
                    :code-commune="r.code_commune"
                    :nom-commune="r.nom_commune"
                    :code-departement="code_departement"
                    :taux-participation="r.taux_participation"
                    :statut-commune="r.statut_commune"
                    :statut-libelle="r.statut_libelle"
                    :liste-gagnante="r.liste_gagnante"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
