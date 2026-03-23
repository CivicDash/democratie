<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
    mes_listes: Array,
    ma_candidature: Object,
});

const getStatutBadgeClass = (couleur) => {
    const colors = {
        gray: 'bg-gray-100 text-gray-600 dark:bg-gray-900/50 dark:text-gray-400',
        yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400',
        orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/50 dark:text-orange-400',
        blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400',
        green: 'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400',
        red: 'bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-400',
    };
    return colors[couleur] || colors.gray;
};
</script>

<template>
    <Head title="Espace Candidat - Élections Municipales" />

    <AuthenticatedLayout>
        <!-- Hero -->
        <div class="bg-gradient-to-r from-indigo-600 to-fuchsia-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <Link
                            :href="route('elections.municipales.index')"
                            class="text-indigo-200 hover:text-white text-sm mb-2 inline-block"
                        >
                            ← Retour aux municipales
                        </Link>
                        <h1 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3">
                            👤 Espace Candidat
                        </h1>
                        <p class="text-indigo-100 mt-1">
                            Gérez vos listes et candidatures pour les municipales 2026
                        </p>
                    </div>
                    <Link
                        :href="route('elections.municipales.espace-candidat.create-liste')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-indigo-600 font-bold rounded-xl hover:bg-indigo-50 transition shadow-lg"
                    >
                        ➕ Créer une nouvelle liste
                    </Link>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Ma candidature personnelle -->
            <section v-if="ma_candidature" class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    🎯 Ma candidature
                </h2>
                <div class="bg-gradient-to-br from-indigo-50 to-fuchsia-50 dark:from-indigo-900/30 dark:to-fuchsia-900/30 rounded-xl border border-indigo-200 dark:border-indigo-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-xl bg-indigo-500 text-white flex items-center justify-center text-2xl font-bold">
                            {{ ma_candidature.nom_complet.split(' ').map(n => n[0]).join('') }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">
                                {{ ma_candidature.nom_complet }}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Position {{ ma_candidature.position }} sur la liste "{{ ma_candidature.liste_nom }}"
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                📍 {{ ma_candidature.commune_nom }}
                            </p>
                        </div>
                        <Link
                            :href="route('elections.municipales.espace-candidat.edit-liste', ma_candidature.liste_uuid)"
                            class="ml-auto text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            Voir la liste →
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Mes listes -->
            <section>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    📋 Mes listes
                </h2>

                <div v-if="mes_listes.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <span class="text-5xl mb-4 block">🗳️</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        Vous n'avez pas encore créé de liste
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Créez votre première liste pour les élections municipales 2026
                    </p>
                    <Link
                        :href="route('elections.municipales.espace-candidat.create-liste')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 transition"
                    >
                        ➕ Créer ma liste
                    </Link>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        v-for="liste in mes_listes"
                        :key="liste.uuid"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition"
                    >
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">
                                    {{ liste.nom_liste }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    📍 {{ liste.commune_nom }}
                                </p>
                            </div>
                            <Badge :class="getStatutBadgeClass(liste.statut_couleur)">
                                {{ liste.statut_formate }}
                            </Badge>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-4 text-center">
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                                <div class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ liste.nombre_candidats }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Candidats</div>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-3">
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                    {{ liste.documents_valides }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Docs validés</div>
                            </div>
                            <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-3">
                                <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ liste.documents_en_attente }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">En attente</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link
                                v-if="liste.peut_etre_modifiee"
                                :href="route('elections.municipales.espace-candidat.edit-liste', liste.uuid)"
                                class="flex-1 text-center py-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 transition font-medium"
                            >
                                ✏️ Modifier
                            </Link>
                            <Link
                                v-else
                                :href="route('elections.municipales.liste', liste.uuid)"
                                class="flex-1 text-center py-2 px-4 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium"
                            >
                                👁️ Voir
                            </Link>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-3 text-center">
                            Créée le {{ liste.created_at }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Aide -->
            <section class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6">
                <h3 class="font-bold text-blue-900 dark:text-blue-200 mb-3 flex items-center gap-2">
                    💡 Besoin d'aide ?
                </h3>
                <p class="text-blue-800 dark:text-blue-300 mb-4">
                    Consultez notre guide complet pour savoir comment déposer votre candidature et quels documents sont nécessaires.
                </p>
                <Link
                    :href="route('elections.municipales.tutoriel')"
                    class="inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:underline font-medium"
                >
                    📋 Voir le guide de candidature →
                </Link>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
