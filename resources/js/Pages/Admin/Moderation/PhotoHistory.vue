<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

/**
 * Historique des décisions de modération photo.
 *
 * Le bouton « Historique » existait dans Photos.vue et menait à un écran blanc :
 * la seule trace des décisions passées était inatteignable.
 */
defineProps({
    history: Object,
});

const classeAction = (couleur) => ({
    green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    amber: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
}[couleur] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300');
</script>

<template>
    <Head title="Historique de modération photo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Historique de modération photo
                </h2>
                <Link :href="route('admin.moderation.photos.index')"
                      class="px-3 py-2 min-h-[36px] rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    ← File d'attente
                </Link>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 py-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Date</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Personne</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Décision</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Motif</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Modérateur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="entree in history.data" :key="entree.id">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    {{ entree.created_at }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ entree.user.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ entree.user.email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', classeAction(entree.action_color)]">
                                        {{ entree.action_label || entree.action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-md">
                                    {{ entree.reason || '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ entree.moderator?.name ?? 'Système' }}
                                </td>
                            </tr>

                            <tr v-if="!history.data.length">
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <p class="text-gray-600 dark:text-gray-400">Aucune décision enregistrée.</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                        L'historique se remplit dès qu'une photo est approuvée ou refusée
                                        depuis la file d'attente.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="history.links?.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <Pagination :links="history.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
