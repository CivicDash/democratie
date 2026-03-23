<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    listes: Object,
    stats: Object,
});
</script>

<template>
    <Head title="Modération - Élections Municipales" />

    <AuthenticatedLayout>
        <div class="bg-gradient-to-r from-purple-700 to-indigo-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3">
                    🛡️ Modération des candidatures
                </h1>
                <p class="text-purple-200 mt-1">
                    Vérifiez les listes et documents avant validation
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-xl border border-yellow-200 dark:border-yellow-700 p-6 text-center">
                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ stats.en_attente }}
                    </div>
                    <div class="text-yellow-700 dark:text-yellow-300">En attente</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-700 p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ stats.en_verification }}
                    </div>
                    <div class="text-blue-700 dark:text-blue-300">En vérification</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/30 rounded-xl border border-green-200 dark:border-green-700 p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ stats.valides_aujourdhui }}
                    </div>
                    <div class="text-green-700 dark:text-green-300">Validées aujourd'hui</div>
                </div>
            </div>

            <!-- Liste des candidatures -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        📋 Candidatures à traiter ({{ listes.total }})
                    </h2>
                </div>

                <div v-if="listes.data.length === 0" class="p-12 text-center">
                    <span class="text-5xl">🎉</span>
                    <p class="text-gray-600 dark:text-gray-400 mt-4">
                        Aucune candidature en attente de modération !
                    </p>
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <Link
                        v-for="liste in listes.data"
                        :key="liste.uuid"
                        :href="route('elections.municipales.moderation.show', liste.uuid)"
                        class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ liste.nom_liste }}
                                    </h3>
                                    <Badge
                                        :class="liste.statut === 'en_attente' ? 'bg-yellow-100 text-yellow-600' : 'bg-blue-100 text-blue-600'"
                                    >
                                        {{ liste.statut_formate }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    📍 {{ liste.commune_nom }} ({{ liste.departement_code }})
                                </p>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                    <span>👥 {{ liste.nombre_candidats }} candidats</span>
                                    <span>📁 {{ liste.nombre_documents }} documents</span>
                                    <span v-if="liste.documents_en_attente > 0" class="text-yellow-600">
                                        ⏳ {{ liste.documents_en_attente }} docs à vérifier
                                    </span>
                                </div>
                            </div>
                            <div class="text-right text-sm text-gray-500 flex-shrink-0">
                                <div>{{ liste.created_at }}</div>
                                <div v-if="liste.createur">par {{ liste.createur }}</div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="listes.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-2">
                    <Link
                        v-for="page in listes.last_page"
                        :key="page"
                        :href="listes.path + '?page=' + page"
                        :class="[
                            'px-3 py-1 rounded',
                            page === listes.current_page
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        {{ page }}
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
