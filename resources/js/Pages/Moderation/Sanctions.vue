<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

/**
 * Liste des sanctions de contenu (avertissement, mise en sourdine, bannissement de
 * publication). Distinctes des sanctions de compte, qui bloquent la connexion et se
 * gèrent depuis la fiche utilisateur.
 */
const props = defineProps({
    sanctions: Object,
    filters: { type: Object, default: () => ({}) },
});

const type = ref(props.filters.type ?? '');
const actives = ref(props.filters.active ?? '');

watch([type, actives], () => {
    router.get(route('moderation.sanctions.index'),
        { type: type.value || undefined, active: actives.value || undefined },
        { preserveState: true, replace: true });
});

const TYPES = {
    warning: { label: 'Avertissement', classe: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' },
    mute: { label: 'Mise en sourdine', classe: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' },
    ban: { label: 'Bannissement', classe: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' },
};

const typeDe = (t) => TYPES[t] ?? { label: t, classe: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' };

const formatDate = (iso) => (iso
    ? new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—');
</script>

<template>
    <Head title="Sanctions de contenu" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                Sanctions de contenu
            </h2>
        </template>

        <div class="max-w-6xl mx-auto px-4 py-8 space-y-4">
            <div class="flex flex-wrap gap-4">
                <div>
                    <label for="filtre-type" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                    <select id="filtre-type" v-model="type"
                            class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 text-sm">
                        <option value="">Tous</option>
                        <option value="warning">Avertissement</option>
                        <option value="mute">Mise en sourdine</option>
                        <option value="ban">Bannissement</option>
                    </select>
                </div>
                <div>
                    <label for="filtre-actif" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">État</label>
                    <select id="filtre-actif" v-model="actives"
                            class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 text-sm">
                        <option value="">Toutes</option>
                        <option value="true">En cours</option>
                        <option value="false">Échues</option>
                    </select>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Personne</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Type</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Motif</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Période</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Prononcée par</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Détail</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="s in sanctions.data" :key="s.id">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ s.user?.name ?? 'Compte supprimé' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', typeDe(s.type).classe]">
                                        {{ typeDe(s.type).label }}
                                    </span>
                                    <span v-if="!s.is_active" class="ml-2 text-xs text-gray-500">échue</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-sm truncate">{{ s.reason }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    {{ formatDate(s.starts_at) }}
                                    <span v-if="s.expires_at"> → {{ formatDate(s.expires_at) }}</span>
                                    <span v-else class="text-xs"> (sans échéance)</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ s.moderator?.name ?? 'Système' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('moderation.sanctions.show', s.id)"
                                          class="text-blue-600 dark:text-blue-400 hover:underline">
                                        Détail
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!sanctions.data.length">
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <p class="text-gray-600 dark:text-gray-400">Aucune sanction ne correspond à ces filtres.</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                        Les sanctions de contenu naissent du traitement d'un signalement.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="sanctions.links?.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <Pagination :links="sanctions.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
